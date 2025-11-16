#!/bin/bash

#####################################################################
# Install Dependencies Script
# Sistema de Registro de Ponto Eletrônico
#
# Este script instala todas as dependências necessárias
# Usage: ./install-dependencies.sh
#####################################################################

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

print_header() {
    echo -e "${BLUE}"
    echo "======================================================================"
    echo "  Instalação de Dependências - Ponto Eletrônico"
    echo "======================================================================"
    echo -e "${NC}"
}

print_success() {
    echo -e "${GREEN}✓ $1${NC}"
}

print_error() {
    echo -e "${RED}✗ $1${NC}"
}

print_info() {
    echo -e "${YELLOW}→ $1${NC}"
}

check_php() {
    print_info "Verificando PHP..."

    if ! command -v php &> /dev/null; then
        print_error "PHP não está instalado!"
        echo "Instale PHP 8.1+ antes de continuar."
        exit 1
    fi

    PHP_VERSION=$(php -v | head -n 1 | cut -d " " -f 2 | cut -f1 -d"-")
    print_success "PHP encontrado: $PHP_VERSION"

    if ! php -r "exit(version_compare(PHP_VERSION, '8.1.0', '>=') ? 0 : 1);"; then
        print_error "PHP 8.1+ é necessário. Versão atual: $PHP_VERSION"
        exit 1
    fi

    print_success "Versão do PHP compatível"
}

check_composer() {
    print_info "Verificando Composer..."

    if ! command -v composer &> /dev/null; then
        print_error "Composer não está instalado!"
        echo ""
        echo "Instalando Composer automaticamente..."

        # Download installer
        php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"

        # Verify installer
        HASH="$(wget -q -O - https://composer.github.io/installer.sig)"
        php -r "if (hash_file('sha384', 'composer-setup.php') === '$HASH') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); exit(1); } echo PHP_EOL;"

        # Run installer with explicit CLI configuration to ensure $argv is available
        # This prevents "Undefined variable $argv" warnings
        php -d register_argc_argv=On composer-setup.php --quiet

        # Clean up installer
        rm composer-setup.php

        # Move to global location
        if [ -f "composer.phar" ]; then
            sudo mv composer.phar /usr/local/bin/composer
            print_success "Composer instalado com sucesso!"
        else
            print_error "Falha ao instalar Composer. Tente instalar manualmente."
            exit 1
        fi
    else
        print_success "Composer encontrado: $(composer --version)"
    fi
}

install_composer_deps() {
    print_info "Instalando dependências do Composer..."

    if [ ! -f "composer.json" ]; then
        print_error "composer.json não encontrado!"
        exit 1
    fi

    # Install production dependencies
    composer install --no-dev --optimize-autoloader --no-interaction

    print_success "Dependências instaladas com sucesso!"

    # Show installed packages
    echo ""
    print_info "Pacotes principais instalados:"
    composer show --direct | grep -E "codeigniter4|phpoffice|tecnickcom|guzzlehttp|chillerlan|workerman" | head -10
}

check_extensions() {
    print_info "Verificando extensões PHP necessárias..."

    EXTENSIONS=(
        "intl"
        "mbstring"
        "json"
        "mysqlnd"
        "gd"
        "curl"
        "xml"
        "zip"
        "fileinfo"
        "openssl"
    )

    MISSING=()

    for ext in "${EXTENSIONS[@]}"; do
        if php -m | grep -q "^$ext$"; then
            print_success "Extensão $ext instalada"
        else
            print_error "Extensão $ext NÃO instalada"
            MISSING+=("$ext")
        fi
    done

    if [ ${#MISSING[@]} -gt 0 ]; then
        echo ""
        print_error "Extensões faltando: ${MISSING[*]}"
        echo ""
        echo "Instale as extensões faltando:"
        echo "  Ubuntu/Debian: sudo apt-get install php-intl php-mbstring php-xml php-curl php-gd php-zip php-mysql"
        echo "  CentOS/RHEL: sudo yum install php-intl php-mbstring php-xml php-curl php-gd php-zip php-mysqlnd"
        exit 1
    fi

    print_success "Todas as extensões necessárias estão instaladas"
}

create_directories() {
    print_info "Criando diretórios necessários..."

    DIRS=(
        "writable/cache"
        "writable/logs"
        "writable/session"
        "writable/uploads"
        "writable/uploads/biometric"
        "writable/uploads/justifications"
        "writable/uploads/warnings"
        "writable/receipts"
        "writable/backups"
        "storage/qrcodes"
        "storage/exports"
        "public/uploads"
    )

    for dir in "${DIRS[@]}"; do
        if [ ! -d "$dir" ]; then
            mkdir -p "$dir"
            print_success "Criado: $dir"
        else
            print_info "Já existe: $dir"
        fi
    done

    # Set permissions
    chmod -R 755 writable/ storage/ public/uploads/
    chmod -R 777 writable/cache writable/logs writable/session

    print_success "Diretórios criados com permissões corretas"
}

copy_env_file() {
    print_info "Configurando arquivo de ambiente..."

    if [ ! -f ".env" ]; then
        if [ -f ".env.example" ]; then
            cp .env.example .env
            print_success "Arquivo .env criado a partir de .env.example"

            echo ""
            print_info "PRÓXIMO PASSO:"
            echo "  1. Edite o arquivo .env e configure:"
            echo "     - Credenciais do banco de dados"
            echo "     - URL base da aplicação"
            echo "     - Chave de criptografia (execute: php spark key:generate)"
            echo ""
            echo "  2. Após configurar, execute o instalador web:"
            echo "     http://seudominio.com/install.php"
            echo ""
        else
            print_error ".env.example não encontrado!"
        fi
    else
        print_info "Arquivo .env já existe"
    fi
}

print_completion() {
    echo ""
    echo -e "${GREEN}"
    echo "======================================================================"
    echo "  ✓ Dependências Instaladas com Sucesso!"
    echo "======================================================================"
    echo -e "${NC}"
    echo ""
    echo "Próximos passos:"
    echo ""
    echo "  OPÇÃO 1: Instalador Web (Recomendado)"
    echo "  =========================================="
    echo "  1. Configure seu servidor web (Apache/Nginx)"
    echo "  2. Acesse: http://seudominio.com/install.php"
    echo "  3. Siga o assistente de instalação"
    echo ""
    echo "  OPÇÃO 2: Instalação Manual"
    echo "  =========================================="
    echo "  1. Edite o arquivo .env"
    echo "  2. Execute: php spark migrate"
    echo "  3. Execute: php spark db:seed AdminSeeder"
    echo "  4. Configure o servidor web"
    echo ""
    echo "  OPÇÃO 3: Docker (Automático)"
    echo "  =========================================="
    echo "  1. Execute: ./scripts/install.sh"
    echo ""
    echo "Documentação completa: README.md e INSTALLATION.md"
    echo ""
}

# Main execution
main() {
    print_header

    check_php
    check_extensions
    check_composer
    install_composer_deps
    create_directories
    copy_env_file

    print_completion
}

# Run
main
