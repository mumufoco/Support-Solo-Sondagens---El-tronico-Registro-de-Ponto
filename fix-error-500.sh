#!/bin/bash

#############################################################################
# SCRIPT DE CORREÇÃO DO ERRO 500
# Sistema de Ponto Eletrônico
#
# Este script corrige automaticamente a configuração para rodar
# fora do Docker (usando localhost).
#
# Uso:
#   chmod +x fix-error-500.sh
#   ./fix-error-500.sh
#############################################################################

set -e

# Cores
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}"
echo "╔═══════════════════════════════════════════════════════════╗"
echo "║     Correção Automática do Erro 500                       ║"
echo "║     Sistema de Ponto Eletrônico                           ║"
echo "╚═══════════════════════════════════════════════════════════╝"
echo -e "${NC}"

print_step() {
    echo -e "${GREEN}[✓]${NC} $1"
}

print_error() {
    echo -e "${RED}[✗]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[!]${NC} $1"
}

print_info() {
    echo -e "${BLUE}[i]${NC} $1"
}

#############################################################################
# ETAPA 1: Verificações
#############################################################################

echo -e "\n${BLUE}═══ ETAPA 1: Verificações ═══${NC}\n"

# Verificar se está no diretório correto
if [ ! -f "composer.json" ]; then
    print_error "Execute este script no diretório raiz do projeto!"
    exit 1
fi
print_step "Diretório do projeto verificado"

# Verificar PHP
if ! command -v php >/dev/null 2>&1; then
    print_error "PHP não está instalado!"
    exit 1
fi
PHP_VERSION=$(php -v | head -n 1 | cut -d ' ' -f2 | cut -d '.' -f1,2)
print_step "PHP instalado (versão: $PHP_VERSION)"

# Verificar MySQL
if ! command -v mysql >/dev/null 2>&1; then
    print_warning "MySQL client não encontrado"
    print_info "Certifique-se de que o MySQL server está instalado e rodando"
else
    print_step "MySQL client instalado"
fi

#############################################################################
# ETAPA 2: Backup do .env atual
#############################################################################

echo -e "\n${BLUE}═══ ETAPA 2: Backup do .env ═══${NC}\n"

if [ -f ".env" ]; then
    BACKUP_FILE=".env.backup.$(date +%Y%m%d_%H%M%S)"
    cp .env "$BACKUP_FILE"
    print_step "Backup criado: $BACKUP_FILE"
else
    print_warning "Arquivo .env não encontrado (será criado)"
fi

#############################################################################
# ETAPA 3: Criar novo .env para localhost
#############################################################################

echo -e "\n${BLUE}═══ ETAPA 3: Configurando .env para localhost ═══${NC}\n"

if [ -f ".env.localhost" ]; then
    cp .env.localhost .env
    print_step ".env criado a partir de .env.localhost"
else
    print_error ".env.localhost não encontrado!"
    exit 1
fi

#############################################################################
# ETAPA 4: Gerar chave de encriptação
#############################################################################

echo -e "\n${BLUE}═══ ETAPA 4: Gerando chave de encriptação ═══${NC}\n"

if php spark key:generate >/dev/null 2>&1; then
    print_step "Chave de encriptação gerada"
else
    print_warning "Erro ao gerar chave. Execute manualmente: php spark key:generate"
fi

#############################################################################
# ETAPA 5: Configurar permissões
#############################################################################

echo -e "\n${BLUE}═══ ETAPA 5: Configurando permissões ═══${NC}\n"

chmod -R 775 storage/ 2>/dev/null || print_warning "Não foi possível ajustar permissões de storage/"
chmod -R 775 writable/ 2>/dev/null || print_warning "Não foi possível ajustar permissões de writable/"

print_step "Permissões configuradas"

#############################################################################
# ETAPA 6: Limpar cache
#############################################################################

echo -e "\n${BLUE}═══ ETAPA 6: Limpando cache ═══${NC}\n"

rm -rf storage/cache/* 2>/dev/null
rm -rf writable/cache/* 2>/dev/null
rm -rf writable/session/* 2>/dev/null

print_step "Cache limpo"

#############################################################################
# ETAPA 7: Verificar banco de dados
#############################################################################

echo -e "\n${BLUE}═══ ETAPA 7: Banco de Dados ═══${NC}\n"

echo -e "${YELLOW}Deseja criar o banco de dados agora? (s/n)${NC}"
read -r CREATE_DB

if [[ "$CREATE_DB" =~ ^[Ss]$ ]]; then
    echo -e "${YELLOW}Digite a senha do root do MySQL:${NC}"
    read -s MYSQL_ROOT_PASS

    mysql -u root -p"$MYSQL_ROOT_PASS" -e "CREATE DATABASE IF NOT EXISTS ponto_eletronico CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 2>/dev/null

    if [ $? -eq 0 ]; then
        print_step "Banco de dados 'ponto_eletronico' criado/verificado"

        echo -e "${YELLOW}Executar migrations? (s/n)${NC}"
        read -r RUN_MIGRATIONS

        if [[ "$RUN_MIGRATIONS" =~ ^[Ss]$ ]]; then
            if php spark migrate; then
                print_step "Migrations executadas"
            else
                print_warning "Erro ao executar migrations"
            fi
        fi

        echo -e "${YELLOW}Executar seeder AdminSeeder? (s/n)${NC}"
        read -r RUN_SEEDER

        if [[ "$RUN_SEEDER" =~ ^[Ss]$ ]]; then
            if php spark db:seed AdminSeeder; then
                print_step "Seeder executado"
            else
                print_warning "Erro ao executar seeder (pode já existir)"
            fi
        fi
    else
        print_error "Erro ao conectar ao MySQL"
        print_info "Configure manualmente o banco de dados"
    fi
else
    print_warning "Banco de dados não criado automaticamente"
    print_info "Execute manualmente:"
    echo "  mysql -u root -p -e \"CREATE DATABASE ponto_eletronico;\""
    echo "  php spark migrate"
    echo "  php spark db:seed AdminSeeder"
fi

#############################################################################
# FINALIZAÇÃO
#############################################################################

echo -e "\n${GREEN}╔═══════════════════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║              CORREÇÃO CONCLUÍDA! ✓                        ║${NC}"
echo -e "${GREEN}╚═══════════════════════════════════════════════════════════╝${NC}\n"

print_info "Próximos passos:"
echo "  1. Edite o .env e configure a senha do MySQL:"
echo "     nano .env"
echo ""
echo "  2. Inicie o servidor de desenvolvimento:"
echo "     php spark serve"
echo ""
echo "  3. Acesse no navegador:"
echo "     http://localhost:8080"
echo ""

print_warning "IMPORTANTE:"
echo "  • Verifique se o MySQL está rodando"
echo "  • Configure a senha do banco no .env"
echo "  • Se DeepFace API não estiver rodando, ignore erros relacionados"
echo ""

print_info "Documentação completa: FIX_ERROR_500.md"
echo ""
