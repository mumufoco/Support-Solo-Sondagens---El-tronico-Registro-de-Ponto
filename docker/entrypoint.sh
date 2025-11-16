#!/bin/bash
# ==============================================================================
# Entrypoint Script - Sistema de Ponto Eletrônico
# Inicialização do container e configuração do ambiente
# ==============================================================================

set -e

echo "===================================================================="
echo " Sistema de Ponto Eletrônico - Inicializando Container"
echo "===================================================================="

# Cores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Função para log
log() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

warn() {
    echo -e "${YELLOW}[WARN]${NC} $1"
}

# ==============================================================================
# 1. Verificar Variáveis de Ambiente Obrigatórias
# ==============================================================================
log "Verificando variáveis de ambiente..."

if [ -z "$DB_HOST" ]; then
    error "DB_HOST não está definido!"
    exit 1
fi

log "✓ Variáveis de ambiente OK"

# ==============================================================================
# 2. Aguardar MySQL estar disponível
# ==============================================================================
log "Aguardando MySQL estar disponível..."

MAX_TRIES=30
TRIES=0

while ! mysql -h"$DB_HOST" -u"$DB_USERNAME" -p"$DB_PASSWORD" -e "SELECT 1" >/dev/null 2>&1; do
    TRIES=$((TRIES+1))
    if [ $TRIES -gt $MAX_TRIES ]; then
        error "MySQL não ficou disponível após $MAX_TRIES tentativas"
        exit 1
    fi
    warn "Aguardando MySQL... (tentativa $TRIES/$MAX_TRIES)"
    sleep 2
done

log "✓ MySQL conectado com sucesso"

# ==============================================================================
# 3. Verificar e criar banco de dados se não existir
# ==============================================================================
log "Verificando banco de dados..."

DB_EXISTS=$(mysql -h"$DB_HOST" -u"$DB_USERNAME" -p"$DB_PASSWORD" -e "SHOW DATABASES LIKE '$DB_DATABASE';" | grep "$DB_DATABASE" || true)

if [ -z "$DB_EXISTS" ]; then
    warn "Banco de dados '$DB_DATABASE' não existe. Criando..."
    mysql -h"$DB_HOST" -u"$DB_USERNAME" -p"$DB_PASSWORD" -e "CREATE DATABASE IF NOT EXISTS $DB_DATABASE CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
    log "✓ Banco de dados criado"
else
    log "✓ Banco de dados já existe"
fi

# ==============================================================================
# 4. Executar Migrations (se CI_ENVIRONMENT != production)
# ==============================================================================
if [ "$CI_ENVIRONMENT" != "production" ] || [ "$RUN_MIGRATIONS" = "true" ]; then
    log "Executando migrations do CodeIgniter..."
    php spark migrate --all || warn "Migrations falharam ou não há migrations pendentes"
    log "✓ Migrations executadas"
else
    warn "Skipping migrations (ambiente: $CI_ENVIRONMENT)"
fi

# ==============================================================================
# 5. Gerar encryption key se não existir no .env
# ==============================================================================
if ! grep -q "^encryption.key = " .env 2>/dev/null; then
    log "Gerando encryption key..."
    php spark key:generate --force || warn "Falha ao gerar encryption key"
    log "✓ Encryption key gerada"
fi

# ==============================================================================
# 6. Ajustar Permissões
# ==============================================================================
log "Ajustando permissões de diretórios..."

chown -R www:www /var/www/html/writable /var/www/html/storage 2>/dev/null || true
chmod -R 755 /var/www/html/writable /var/www/html/storage 2>/dev/null || true

log "✓ Permissões ajustadas"

# ==============================================================================
# 7. Limpar cache (se ambiente de desenvolvimento)
# ==============================================================================
if [ "$CI_ENVIRONMENT" = "development" ]; then
    log "Limpando cache..."
    php spark cache:clear || true
    log "✓ Cache limpo"
fi

# ==============================================================================
# 8. Iniciar Aplicação
# ==============================================================================
log "===================================================================="
log " Inicialização completa! Iniciando serviços..."
log "===================================================================="

# Executar comando passado para o container (geralmente supervisord)
exec "$@"
