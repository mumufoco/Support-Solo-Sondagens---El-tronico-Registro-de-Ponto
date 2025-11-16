#!/bin/bash

#####################################################################
# Update Script for Electronic Timesheet System
# Sistema de Registro de Ponto Eletrônico
#
# This script updates the application to the latest version
# Usage: ./scripts/update.sh
#####################################################################

set -e

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
BLUE='\033[0;34m'
NC='\033[0m'

# Functions
print_header() {
    echo -e "${BLUE}"
    echo "======================================================================"
    echo "  Sistema de Ponto Eletrônico - Atualização"
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

confirm_update() {
    echo ""
    print_info "ATENÇÃO: Esta atualização irá:"
    echo "  1. Fazer backup do sistema atual"
    echo "  2. Baixar as atualizações do repositório"
    echo "  3. Atualizar dependências"
    echo "  4. Executar migrações do banco de dados"
    echo "  5. Reiniciar os serviços"
    echo ""
    read -p "Deseja continuar? (s/N): " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Ss]$ ]]; then
        print_error "Atualização cancelada pelo usuário"
        exit 1
    fi
}

enable_maintenance_mode() {
    print_info "Ativando modo de manutenção..."

    touch writable/maintenance.lock
    docker-compose exec -T php touch writable/maintenance.lock

    print_success "Modo de manutenção ativado"
}

disable_maintenance_mode() {
    print_info "Desativando modo de manutenção..."

    rm -f writable/maintenance.lock
    docker-compose exec -T php rm -f writable/maintenance.lock 2>/dev/null || true

    print_success "Modo de manutenção desativado"
}

create_backup() {
    print_info "Criando backup antes da atualização..."

    if [ -f "./scripts/backup.sh" ]; then
        bash ./scripts/backup.sh
        print_success "Backup criado com sucesso"
    else
        print_error "Script de backup não encontrado!"
        exit 1
    fi
}

pull_latest_code() {
    print_info "Baixando atualizações do repositório..."

    # Stash local changes
    git stash save "Auto-stash before update $(date)"

    # Pull latest changes
    git pull origin main

    print_success "Código atualizado"
}

update_composer_dependencies() {
    print_info "Atualizando dependências do Composer..."

    docker run --rm -v $(pwd):/app composer:latest update --no-dev --optimize-autoloader

    print_success "Dependências atualizadas"
}

rebuild_docker_images() {
    print_info "Reconstruindo imagens Docker..."

    docker-compose build --no-cache

    print_success "Imagens Docker reconstruídas"
}

run_migrations() {
    print_info "Executando migrações do banco de dados..."

    docker-compose exec -T php php spark migrate

    print_success "Migrações executadas"
}

clear_cache() {
    print_info "Limpando cache..."

    # Clear application cache
    docker-compose exec -T php php spark cache:clear

    # Clear Redis cache
    docker-compose exec -T redis redis-cli -a "${REDIS_PASSWORD:-redis_password}" FLUSHDB

    # Clear writable/cache
    docker-compose exec -T php rm -rf writable/cache/*

    print_success "Cache limpo"
}

restart_services() {
    print_info "Reiniciando serviços..."

    docker-compose restart

    print_success "Serviços reiniciados"

    # Wait for services to be ready
    print_info "Aguardando serviços inicializarem..."
    sleep 10
}

verify_update() {
    print_info "Verificando atualização..."

    # Check if services are running
    if docker-compose ps | grep -q "Up"; then
        print_success "Serviços estão rodando"
    else
        print_error "Alguns serviços não estão rodando!"
        docker-compose ps
        exit 1
    fi

    # Check database connection
    if docker-compose exec -T php php spark db:check 2>/dev/null; then
        print_success "Conexão com banco de dados OK"
    else
        print_error "Erro na conexão com banco de dados!"
        exit 1
    fi
}

print_completion() {
    echo ""
    echo -e "${GREEN}"
    echo "======================================================================"
    echo "  ✓ Atualização Concluída com Sucesso!"
    echo "======================================================================"
    echo -e "${NC}"
    echo ""
    echo "Versão atual: $(git describe --tags --always)"
    echo "Último commit: $(git log -1 --oneline)"
    echo ""
    echo "O sistema está atualizado e rodando normalmente."
    echo ""
    echo "Verifique em: http://localhost"
    echo ""
}

rollback() {
    print_error "Erro durante a atualização!"
    print_info "Iniciando rollback..."

    # Disable maintenance mode
    disable_maintenance_mode

    # Restore from backup (if available)
    LATEST_BACKUP=$(ls -t writable/backups/ponto_backup_*.tar.gz 2>/dev/null | head -1)

    if [ ! -z "${LATEST_BACKUP}" ]; then
        print_info "Restaurando do backup: ${LATEST_BACKUP}"
        bash ./scripts/restore.sh "${LATEST_BACKUP}"
        print_success "Rollback concluído"
    else
        print_error "Nenhum backup disponível para rollback!"
        print_info "Reverta manualmente usando: git reset --hard HEAD~1"
    fi

    exit 1
}

# Main update process
main() {
    print_header

    # Set error trap
    trap rollback ERR

    confirm_update
    enable_maintenance_mode
    create_backup
    pull_latest_code
    update_composer_dependencies
    rebuild_docker_images
    run_migrations
    clear_cache
    restart_services
    verify_update
    disable_maintenance_mode

    print_completion
}

# Run main function
main "$@"
