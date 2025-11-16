# ==============================================================================
# Dockerfile - Sistema de Ponto Eletrônico Brasileiro
# Multi-stage build para otimização de tamanho e segurança
# ==============================================================================

# ------------------------------------------------------------------------------
# Stage 1: Builder - Instala dependências do Composer
# ------------------------------------------------------------------------------
FROM composer:2.7 AS composer-builder

WORKDIR /app

# Copy apenas arquivos necessários para cache de dependências
COPY composer.json composer.lock ./

# Instalar dependências de produção (sem dev)
RUN composer install \
    --no-dev \
    --no-scripts \
    --no-autoloader \
    --prefer-dist \
    --optimize-autoloader

# Copy código fonte e gerar autoloader otimizado
COPY . .
RUN composer dump-autoload --optimize --no-dev

# ------------------------------------------------------------------------------
# Stage 2: Runtime - Imagem final para produção
# ------------------------------------------------------------------------------
FROM php:8.4-fpm-alpine

LABEL maintainer="Support Solo Sondagens"
LABEL description="Sistema de Ponto Eletrônico - CodeIgniter 4"
LABEL version="1.0.0"

# Variáveis de ambiente para build
ENV COMPOSER_ALLOW_SUPERUSER=1 \
    PHP_MEMORY_LIMIT=512M \
    PHP_UPLOAD_MAX_FILESIZE=10M \
    PHP_POST_MAX_SIZE=10M \
    TZ=America/Sao_Paulo

# Instalar dependências do sistema e extensões PHP
RUN apk add --no-cache \
    # Dependências de build
    $PHPIZE_DEPS \
    # Bibliotecas essenciais
    freetype-dev \
    libjpeg-turbo-dev \
    libpng-dev \
    libzip-dev \
    icu-dev \
    oniguruma-dev \
    # Ferramentas úteis
    git \
    curl \
    nginx \
    supervisor \
    tzdata \
    mysql-client \
    bash \
    && \
    # Configurar extensões GD
    docker-php-ext-configure gd \
        --with-freetype \
        --with-jpeg \
    && \
    # Instalar extensões PHP
    docker-php-ext-install -j$(nproc) \
        gd \
        intl \
        mbstring \
        mysqli \
        pdo \
        pdo_mysql \
        zip \
        opcache \
        exif \
        bcmath \
    && \
    # Instalar Redis via PECL
    pecl install redis-6.0.2 \
    && docker-php-ext-enable redis \
    && \
    # Limpar cache
    apk del $PHPIZE_DEPS \
    && rm -rf /var/cache/apk/* /tmp/*

# Configurar timezone
RUN ln -sf /usr/share/zoneinfo/$TZ /etc/localtime \
    && echo $TZ > /etc/timezone

# Criar usuário não-root para rodar a aplicação
RUN addgroup -g 1000 -S www \
    && adduser -u 1000 -S www -G www

# Diretório de trabalho
WORKDIR /var/www/html

# Copiar vendor do stage builder
COPY --from=composer-builder --chown=www:www /app/vendor ./vendor

# Copiar código fonte
COPY --chown=www:www . .

# Criar diretórios necessários com permissões corretas
RUN mkdir -p \
    writable/cache \
    writable/logs \
    writable/session \
    writable/uploads \
    storage/backups \
    storage/cache \
    storage/faces \
    storage/keys \
    storage/logs \
    storage/qrcodes \
    storage/receipts \
    storage/reports \
    storage/uploads \
    && chown -R www:www writable storage \
    && chmod -R 755 writable storage

# Copiar configurações PHP customizadas
COPY docker/php/php.ini /usr/local/etc/php/conf.d/99-custom.ini
COPY docker/php/php-fpm.conf /usr/local/etc/php-fpm.d/zz-custom.conf

# Copiar configuração Nginx
COPY docker/nginx/nginx.conf /etc/nginx/nginx.conf
COPY docker/nginx/default.conf /etc/nginx/http.d/default.conf

# Copiar supervisor config
COPY docker/supervisor/supervisord.conf /etc/supervisord.conf

# Copiar script de entrypoint
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

# Health check
HEALTHCHECK --interval=30s --timeout=10s --start-period=40s --retries=3 \
    CMD php -r "echo 'healthy';" || exit 1

# Expor portas
EXPOSE 80

# Entrypoint
ENTRYPOINT ["/entrypoint.sh"]

# Comando padrão: iniciar supervisor (gerencia nginx + php-fpm)
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]
