#!/bin/bash

#####################################################################
# Backup Script for Electronic Timesheet System
# Sistema de Registro de Ponto Eletrônico
#
# This script creates backups of database and uploaded files
# Usage: ./scripts/backup.sh
#####################################################################

set -e

# Configuration
BACKUP_DIR="./writable/backups"
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
BACKUP_NAME="ponto_backup_${TIMESTAMP}"
RETENTION_DAYS=30

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

# Functions
print_success() {
    echo -e "${GREEN}✓ $1${NC}"
}

print_info() {
    echo -e "${YELLOW}→ $1${NC}"
}

print_error() {
    echo -e "${RED}✗ $1${NC}"
}

# Create backup directory
create_backup_dir() {
    print_info "Criando diretório de backup..."
    mkdir -p "${BACKUP_DIR}/${BACKUP_NAME}"
    print_success "Diretório criado: ${BACKUP_DIR}/${BACKUP_NAME}"
}

# Backup database
backup_database() {
    print_info "Fazendo backup do banco de dados..."

    # Get database credentials from .env
    DB_NAME=$(grep "^database.default.database" .env | cut -d '=' -f2 | tr -d ' ')
    DB_USER=$(grep "^database.default.username" .env | cut -d '=' -f2 | tr -d ' ')
    DB_PASS=$(grep "^database.default.password" .env | cut -d '=' -f2 | tr -d ' ')

    # Create SQL dump
    docker-compose exec -T mysql mysqldump \
        -u"${DB_USER}" \
        -p"${DB_PASS}" \
        --single-transaction \
        --routines \
        --triggers \
        --events \
        "${DB_NAME}" > "${BACKUP_DIR}/${BACKUP_NAME}/database.sql"

    # Compress SQL file
    gzip "${BACKUP_DIR}/${BACKUP_NAME}/database.sql"

    print_success "Backup do banco de dados concluído"
}

# Backup uploaded files
backup_files() {
    print_info "Fazendo backup de arquivos..."

    # Backup writable directory
    tar -czf "${BACKUP_DIR}/${BACKUP_NAME}/writable.tar.gz" \
        --exclude="${BACKUP_DIR}" \
        writable/ 2>/dev/null || true

    # Backup public uploads
    if [ -d "public/uploads" ]; then
        tar -czf "${BACKUP_DIR}/${BACKUP_NAME}/uploads.tar.gz" \
            public/uploads/ 2>/dev/null || true
    fi

    print_success "Backup de arquivos concluído"
}

# Backup DeepFace faces
backup_deepface() {
    print_info "Fazendo backup de biometrias faciais..."

    # Copy DeepFace volume data
    docker-compose exec -T deepface tar -czf /tmp/faces_backup.tar.gz /app/faces 2>/dev/null || true
    docker cp $(docker-compose ps -q deepface):/tmp/faces_backup.tar.gz "${BACKUP_DIR}/${BACKUP_NAME}/faces.tar.gz" 2>/dev/null || true

    print_success "Backup de biometrias concluído"
}

# Backup configuration
backup_config() {
    print_info "Fazendo backup de configurações..."

    # Copy .env (without sensitive data in logs)
    cp .env "${BACKUP_DIR}/${BACKUP_NAME}/.env.backup"

    # Copy docker-compose.yml
    cp docker-compose.yml "${BACKUP_DIR}/${BACKUP_NAME}/docker-compose.yml.backup"

    print_success "Backup de configurações concluído"
}

# Create backup manifest
create_manifest() {
    print_info "Criando manifesto do backup..."

    cat > "${BACKUP_DIR}/${BACKUP_NAME}/manifest.txt" <<EOF
========================================
BACKUP MANIFEST
========================================
Data: $(date)
Versão: 1.0
Sistema: Electronic Timesheet System

Conteúdo:
- database.sql.gz: Dump completo do MySQL
- writable.tar.gz: Diretório writable
- uploads.tar.gz: Arquivos enviados
- faces.tar.gz: Biometrias faciais
- .env.backup: Configurações
- docker-compose.yml.backup: Configuração Docker

Estatísticas:
$(du -sh "${BACKUP_DIR}/${BACKUP_NAME}" | cut -f1) - Tamanho total
$(find "${BACKUP_DIR}/${BACKUP_NAME}" -type f | wc -l) arquivos

Hash MD5:
$(find "${BACKUP_DIR}/${BACKUP_NAME}" -type f -exec md5sum {} \; | sort)
========================================
EOF

    print_success "Manifesto criado"
}

# Compress final backup
compress_backup() {
    print_info "Comprimindo backup final..."

    cd "${BACKUP_DIR}"
    tar -czf "${BACKUP_NAME}.tar.gz" "${BACKUP_NAME}/"
    rm -rf "${BACKUP_NAME}/"

    BACKUP_SIZE=$(du -sh "${BACKUP_NAME}.tar.gz" | cut -f1)
    print_success "Backup comprimido: ${BACKUP_NAME}.tar.gz (${BACKUP_SIZE})"
}

# Clean old backups
cleanup_old_backups() {
    print_info "Removendo backups antigos (>= ${RETENTION_DAYS} dias)..."

    find "${BACKUP_DIR}" -name "ponto_backup_*.tar.gz" -mtime +${RETENTION_DAYS} -delete

    REMAINING=$(find "${BACKUP_DIR}" -name "ponto_backup_*.tar.gz" | wc -l)
    print_success "${REMAINING} backup(s) mantido(s)"
}

# Send backup to remote (optional)
send_to_remote() {
    if [ ! -z "${BACKUP_REMOTE_PATH}" ]; then
        print_info "Enviando backup para servidor remoto..."

        rsync -avz "${BACKUP_DIR}/${BACKUP_NAME}.tar.gz" "${BACKUP_REMOTE_PATH}/"
        print_success "Backup enviado para: ${BACKUP_REMOTE_PATH}"
    fi
}

# Main backup process
main() {
    echo "======================================================================"
    echo "  Backup do Sistema de Ponto Eletrônico"
    echo "======================================================================"
    echo ""

    # Check if Docker containers are running
    if ! docker-compose ps | grep -q "Up"; then
        print_error "Containers Docker não estão rodando!"
        exit 1
    fi

    create_backup_dir
    backup_database
    backup_files
    backup_deepface
    backup_config
    create_manifest
    compress_backup
    cleanup_old_backups
    send_to_remote

    echo ""
    echo -e "${GREEN}======================================================================"
    echo "  ✓ Backup Concluído com Sucesso!"
    echo "======================================================================${NC}"
    echo ""
    echo "Arquivo de backup: ${BACKUP_DIR}/${BACKUP_NAME}.tar.gz"
    echo "Tamanho: $(du -sh "${BACKUP_DIR}/${BACKUP_NAME}.tar.gz" | cut -f1)"
    echo ""
    echo "Para restaurar este backup, use:"
    echo "  ./scripts/restore.sh ${BACKUP_NAME}.tar.gz"
    echo ""
}

# Run main function
main "$@"
