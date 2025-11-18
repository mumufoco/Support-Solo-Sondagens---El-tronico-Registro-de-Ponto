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

# Global variable for docker-compose command
DOCKER_COMPOSE_CMD=""

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

    local has_errors=false

    # Check Docker
    if ! command -v docker &> /dev/null; then
        print_error "Docker não está instalado!"
        echo ""
        echo "Por favor, instale o Docker seguindo as instruções:"
        echo ""
        echo "  Ubuntu/Debian:"
        echo "    curl -fsSL https://get.docker.com -o get-docker.sh"
        echo "    sudo sh get-docker.sh"
        echo "    sudo usermod -aG docker \$USER"
        echo ""
        echo "  Outras distribuições: https://docs.docker.com/get-docker/"
        echo ""
        has_errors=true
    else
        print_success "Docker encontrado: $(docker --version)"
    fi

    # Check Docker Compose (support both old and new syntax)
    if command -v docker-compose &> /dev/null; then
        DOCKER_COMPOSE_CMD="docker-compose"
        print_success "Docker Compose encontrado: $(docker-compose --version)"
    elif docker compose version &> /dev/null; then
        DOCKER_COMPOSE_CMD="docker compose"
        print_success "Docker Compose encontrado: $(docker compose version)"
    else
        print_error "Docker Compose não está disponível!"
        echo ""
        echo "Por favor, instale o Docker Compose:"
        echo "  https://docs.docker.com/compose/install/"
        echo ""
        has_errors=true
    fi

    # Check Git
    if ! command -v git &> /dev/null; then
        print_error "Git não está instalado."
        echo ""
        echo "Instale com: sudo apt-get install git"
        echo ""
        has_errors=true
    else
        print_success "Git encontrado: $(git --version)"
    fi

    # Check if Docker daemon is running
    if command -v docker &> /dev/null; then
        if ! docker info &> /dev/null; then
            print_error "Docker está instalado mas o daemon não está rodando!"
            echo ""
            echo "Inicie o Docker com:"
            echo "  sudo systemctl start docker"
            echo "  sudo systemctl enable docker"
            echo ""
            has_errors=true
        fi
    fi

    if [ "$has_errors" = true ]; then
        echo ""
        print_error "Por favor, resolva os problemas acima e execute o instalador novamente."
        exit 1
    fi
}

generate_secure_password() {
    # Generate a secure random password
    openssl rand -base64 32 | tr -d "=+/" | cut -c1-25
}

setup_environment() {
    print_info "Configurando ambiente..."

    if [ ! -f ".env" ]; then
        # Generate secure passwords
        local DB_PASSWORD=$(generate_secure_password)
        local REDIS_PASSWORD=$(generate_secure_password)
        local DEEPFACE_API_KEY=$(generate_secure_password)
        local ENCRYPTION_KEY=$(openssl rand -base64 32)

        print_info "Gerando senhas seguras automaticamente..."

        # Create .env file from template
        if [ -f ".env.production.example" ]; then
            cp .env.production.example .env
        elif [ -f ".env.example" ]; then
            cp .env.example .env
        else
            print_error "Nenhum template .env encontrado!"
            exit 1
        fi

        # Replace placeholders with generated values
        sed -i "s|DB_PASSWORD:-CHANGE_THIS_PASSWORD|DB_PASSWORD:-${DB_PASSWORD}|g" .env
        sed -i "s|REDIS_PASSWORD:-CHANGE_THIS_REDIS_PASSWORD|REDIS_PASSWORD:-${REDIS_PASSWORD}|g" .env
        sed -i "s|DEEPFACE_API_KEY:-CHANGE_THIS_API_KEY|DEEPFACE_API_KEY:-${DEEPFACE_API_KEY}|g" .env
        sed -i "s|ENCRYPTION_KEY:-YOUR-32-CHARACTER-ENCRYPTION-KEY-HERE|ENCRYPTION_KEY:-${ENCRYPTION_KEY}|g" .env

        # Also set as direct values (not just defaults)
        echo "" >> .env
        echo "# Auto-generated secure credentials" >> .env
        echo "DB_PASSWORD=${DB_PASSWORD}" >> .env
        echo "DB_USERNAME=ponto_user" >> .env
        echo "DB_DATABASE=ponto_eletronico" >> .env
        echo "REDIS_PASSWORD=${REDIS_PASSWORD}" >> .env
        echo "DEEPFACE_API_KEY=${DEEPFACE_API_KEY}" >> .env
        echo "ENCRYPTION_KEY=${ENCRYPTION_KEY}" >> .env

        print_success "Arquivo .env criado com senhas seguras geradas automaticamente"

        # Save credentials to a secure file for reference
        cat > .env.credentials << EOF
====================================================================
CREDENCIAIS GERADAS AUTOMATICAMENTE
Sistema de Registro de Ponto Eletrônico
====================================================================

IMPORTANTE: Guarde estas credenciais em um local seguro!

Database (MySQL):
  Username: ponto_user
  Password: ${DB_PASSWORD}
  Database: ponto_eletronico

Redis:
  Password: ${REDIS_PASSWORD}

DeepFace API:
  API Key: ${DEEPFACE_API_KEY}

Encryption Key:
  ${ENCRYPTION_KEY}

====================================================================
ATENÇÃO: Este arquivo contém informações sensíveis!
Não compartilhe e mantenha em local seguro.
====================================================================
EOF

        chmod 600 .env.credentials
        print_success "Credenciais salvas em .env.credentials (acesso restrito)"

    else
        print_info "Arquivo .env já existe - usando configuração existente"
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

    $DOCKER_COMPOSE_CMD build --no-cache
    print_success "Imagens Docker construídas"
}

start_services() {
    print_info "Iniciando serviços..."

    $DOCKER_COMPOSE_CMD up -d
    print_success "Serviços iniciados"

    # Wait for MySQL to be ready
    print_info "Aguardando MySQL inicializar (isso pode levar alguns minutos)..."
    sleep 15

    # Check if MySQL is actually ready
    print_info "Verificando se o MySQL está pronto..."
    for i in {1..30}; do
        if $DOCKER_COMPOSE_CMD exec -T mysql mysqladmin ping -h localhost --silent 2>/dev/null; then
            print_success "MySQL está pronto!"
            break
        fi
        if [ $i -eq 30 ]; then
            print_error "MySQL demorou muito para inicializar. Verifique os logs: $DOCKER_COMPOSE_CMD logs mysql"
            exit 1
        fi
        echo -n "."
        sleep 2
    done

    # Check services status
    echo ""
    print_info "Status dos serviços:"
    $DOCKER_COMPOSE_CMD ps
}

verify_database_connection() {
    print_info "Verificando conexão com o banco de dados..."

    # Load DB password from .env
    if [ -f ".env" ]; then
        export $(grep "^DB_PASSWORD=" .env | xargs)
        export $(grep "^DB_USERNAME=" .env | xargs)
        export $(grep "^DB_DATABASE=" .env | xargs)
    fi

    # Use default if not set
    DB_PASSWORD=${DB_PASSWORD:-CHANGE_THIS_PASSWORD}
    DB_USERNAME=${DB_USERNAME:-ponto_user}
    DB_DATABASE=${DB_DATABASE:-ponto_eletronico}

    # Try to connect to MySQL (wait a bit more for MySQL to be fully ready)
    sleep 5

    if $DOCKER_COMPOSE_CMD exec -T mysql mysql -u "$DB_USERNAME" -p"$DB_PASSWORD" -e "SELECT 1;" "$DB_DATABASE" &> /dev/null; then
        print_success "Conexão com banco de dados estabelecida com sucesso!"
    else
        print_error "Falha ao conectar com o banco de dados!"
        echo ""
        echo "Tentando diagnosticar o problema..."
        echo ""
        echo "Status dos containers:"
        $DOCKER_COMPOSE_CMD ps
        echo ""
        echo "Logs do MySQL (últimas 20 linhas):"
        $DOCKER_COMPOSE_CMD logs --tail=20 mysql
        echo ""
        exit 1
    fi
}

run_migrations() {
    print_info "Executando migrações do banco de dados..."

    if $DOCKER_COMPOSE_CMD exec -T app php spark migrate; then
        print_success "Migrações executadas com sucesso"
    else
        print_error "Falha ao executar migrações!"
        echo ""
        echo "Verifique os logs:"
        echo "  $DOCKER_COMPOSE_CMD logs app"
        echo ""
        exit 1
    fi
}

run_seeders() {
    print_info "Executando seeders..."

    read -p "Deseja executar os seeders (admin, settings, geofences)? (s/N): " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Ss]$ ]]; then
        $DOCKER_COMPOSE_CMD exec -T app php spark db:seed AdminSeeder
        $DOCKER_COMPOSE_CMD exec -T app php spark db:seed SettingsSeeder
        $DOCKER_COMPOSE_CMD exec -T app php spark db:seed GeofenceSeeder
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
    echo "  - Ver logs: $DOCKER_COMPOSE_CMD logs -f"
    echo "  - Parar: $DOCKER_COMPOSE_CMD stop"
    echo "  - Reiniciar: $DOCKER_COMPOSE_CMD restart"
    echo "  - Remover: $DOCKER_COMPOSE_CMD down"
    echo ""
    echo "Credenciais:"
    echo "  - As credenciais geradas foram salvas em: .env.credentials"
    echo "  - Mantenha este arquivo em local seguro!"
    echo ""
}

# Main installation flow
main() {
    print_header

    check_requirements
    setup_environment
    create_directories
    install_composer_dependencies
    build_docker_images
    start_services
    verify_database_connection
    run_migrations
    run_seeders

    print_completion
}

# Run main function
main
