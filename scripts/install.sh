#!/bin/bash

#####################################################################
# Installation Script for Electronic Timesheet System
# Sistema de Registro de Ponto Eletrônico
#
# This script automates the installation process
# Usage: ./scripts/install.sh
#####################################################################

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Functions
print_header() {
    echo -e "${BLUE}"
    echo "======================================================================"
    echo "  Sistema de Registro de Ponto Eletrônico - Instalação"
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

check_requirements() {
    print_info "Verificando requisitos do sistema..."

    # Check Docker
    if ! command -v docker &> /dev/null; then
        print_error "Docker não está instalado. Instale: https://docs.docker.com/get-docker/"
        exit 1
    fi
    print_success "Docker encontrado: $(docker --version)"

    # Check Docker Compose
    if ! command -v docker-compose &> /dev/null; then
        print_error "Docker Compose não está instalado. Instale: https://docs.docker.com/compose/install/"
        exit 1
    fi
    print_success "Docker Compose encontrado: $(docker-compose --version)"

    # Check Git
    if ! command -v git &> /dev/null; then
        print_error "Git não está instalado."
        exit 1
    fi
    print_success "Git encontrado: $(git --version)"
}

setup_environment() {
    print_info "Configurando ambiente..."

    if [ ! -f ".env" ]; then
        if [ -f ".env.production" ]; then
            cp .env.production .env
            print_success "Arquivo .env criado a partir de .env.production"
        else
            cp .env.example .env
            print_success "Arquivo .env criado a partir de .env.example"
        fi

        print_info "IMPORTANTE: Edite o arquivo .env e configure:"
        echo "  - Senhas do banco de dados"
        echo "  - Chave de criptografia (execute: php spark key:generate)"
        echo "  - Credenciais de email"
        echo "  - URL base da aplicação"
        echo ""
        read -p "Pressione Enter após configurar o .env..."
    else
        print_info "Arquivo .env já existe"
    fi
}

generate_encryption_key() {
    print_info "Gerando chave de criptografia..."

    # Check if encryption key is set
    if grep -q "encryption.key = ''" .env; then
        KEY=$(openssl rand -base64 32)
        sed -i "s|encryption.key = ''|encryption.key = '${KEY}'|g" .env
        print_success "Chave de criptografia gerada"
    else
        print_info "Chave de criptografia já configurada"
    fi
}

create_directories() {
    print_info "Criando diretórios necessários..."

    mkdir -p writable/cache
    mkdir -p writable/logs
    mkdir -p writable/session
    mkdir -p writable/uploads
    mkdir -p writable/backups
    mkdir -p public/uploads

    # Set permissions
    chmod -R 777 writable/
    chmod -R 755 public/

    print_success "Diretórios criados com permissões corretas"
}

install_composer_dependencies() {
    print_info "Instalando dependências do Composer..."

    if [ -f "composer.json" ]; then
        docker run --rm -v $(pwd):/app composer:latest install --no-dev --optimize-autoloader
        print_success "Dependências do Composer instaladas"
    else
        print_error "composer.json não encontrado"
        exit 1
    fi
}

build_docker_images() {
    print_info "Construindo imagens Docker..."

    docker-compose build --no-cache
    print_success "Imagens Docker construídas"
}

start_services() {
    print_info "Iniciando serviços..."

    docker-compose up -d
    print_success "Serviços iniciados"

    # Wait for MySQL to be ready
    print_info "Aguardando MySQL inicializar..."
    sleep 10

    # Check services status
    docker-compose ps
}

run_migrations() {
    print_info "Executando migrações do banco de dados..."

    docker-compose exec -T php php spark migrate
    print_success "Migrações executadas"
}

run_seeders() {
    print_info "Executando seeders..."

    read -p "Deseja executar os seeders (admin, settings, geofences)? (s/N): " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Ss]$ ]]; then
        docker-compose exec -T php php spark db:seed AdminSeeder
        docker-compose exec -T php php spark db:seed SettingsSeeder
        docker-compose exec -T php php spark db:seed GeofenceSeeder
        print_success "Seeders executados"

        print_info "Credenciais padrão:"
        echo "  Email: admin@pontoeletronico.com.br"
        echo "  Senha: Admin@123"
        echo "  IMPORTANTE: Altere a senha após o primeiro login!"
    fi
}

print_completion() {
    echo ""
    echo -e "${GREEN}"
    echo "======================================================================"
    echo "  ✓ Instalação Concluída com Sucesso!"
    echo "======================================================================"
    echo -e "${NC}"
    echo ""
    echo "Próximos passos:"
    echo "  1. Acesse: http://localhost"
    echo "  2. Faça login com as credenciais do administrador"
    echo "  3. Altere a senha padrão"
    echo "  4. Configure os geofences da empresa"
    echo "  5. Cadastre os funcionários"
    echo ""
    echo "Serviços disponíveis:"
    echo "  - Aplicação Web: http://localhost"
    echo "  - DeepFace API: http://localhost:5000"
    echo "  - PHPMyAdmin: http://localhost:8080 (profile: development)"
    echo "  - Mailhog: http://localhost:8025 (profile: development)"
    echo ""
    echo "Comandos úteis:"
    echo "  - Ver logs: docker-compose logs -f"
    echo "  - Parar: docker-compose stop"
    echo "  - Reiniciar: docker-compose restart"
    echo "  - Remover: docker-compose down"
    echo ""
}

# Main installation flow
main() {
    print_header

    check_requirements
    setup_environment
    generate_encryption_key
    create_directories
    install_composer_dependencies
    build_docker_images
    start_services
    run_migrations
    run_seeders

    print_completion
}

# Run main function
main
