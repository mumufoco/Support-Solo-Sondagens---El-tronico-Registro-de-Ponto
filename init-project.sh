#!/bin/bash

# Script de Inicialização do Sistema de Ponto Eletrônico
# Este script corrige os principais erros e prepara o ambiente

echo "======================================"
echo "Inicializando Sistema de Ponto Eletrônico"
echo "======================================"
echo ""

# Cores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# 1. Verificar PHP
echo -e "${YELLOW}[1/6] Verificando PHP...${NC}"
if ! command -v php &> /dev/null; then
    echo -e "${RED}❌ PHP não encontrado. Instale PHP 8.1+ primeiro.${NC}"
    exit 1
fi
PHP_VERSION=$(php -r "echo PHP_VERSION;")
echo -e "${GREEN}✓ PHP $PHP_VERSION encontrado${NC}"
echo ""

# 2. Verificar e instalar dependências
echo -e "${YELLOW}[2/6] Verificando dependências do Composer...${NC}"
if [ ! -d "vendor" ]; then
    echo "Instalando dependências..."
    if ! command -v composer &> /dev/null; then
        echo -e "${RED}❌ Composer não encontrado. Instale o Composer primeiro.${NC}"
        exit 1
    fi
    composer install --no-interaction --prefer-dist --optimize-autoloader
    echo -e "${GREEN}✓ Dependências instaladas${NC}"
else
    echo -e "${GREEN}✓ Dependências já instaladas${NC}"
fi
echo ""

# 3. Verificar arquivo .env
echo -e "${YELLOW}[3/6] Verificando arquivo .env...${NC}"
if [ ! -f ".env" ]; then
    echo -e "${RED}❌ Arquivo .env não encontrado${NC}"
    exit 1
fi
echo -e "${GREEN}✓ Arquivo .env configurado${NC}"
echo ""

# 4. Configurar permissões
echo -e "${YELLOW}[4/6] Configurando permissões de diretórios...${NC}"
chmod -R 777 writable/
chmod -R 777 storage/
mkdir -p writable/session writable/cache writable/logs writable/uploads
chmod -R 777 writable/session writable/cache writable/logs writable/uploads
echo -e "${GREEN}✓ Permissões configuradas${NC}"
echo ""

# 5. Verificar conexão com Supabase
echo -e "${YELLOW}[5/6] Verificando configuração do banco de dados...${NC}"
if grep -q "database.default.hostname = aws-0-us-west-1.pooler.supabase.com" .env; then
    echo -e "${GREEN}✓ Configuração Supabase PostgreSQL encontrada${NC}"
else
    echo -e "${YELLOW}⚠ Verifique as configurações do banco de dados no .env${NC}"
fi
echo ""

# 6. Informações finais
echo -e "${YELLOW}[6/6] Setup concluído!${NC}"
echo ""
echo -e "${GREEN}======================================"
echo "Sistema pronto para uso!"
echo "======================================${NC}"
echo ""
echo "Próximos passos:"
echo ""
echo "1. Execute as migrations do banco:"
echo "   ${YELLOW}php spark migrate${NC}"
echo ""
echo "2. Popule os dados iniciais:"
echo "   ${YELLOW}php spark db:seed AdminUserSeeder${NC}"
echo "   ${YELLOW}php spark db:seed SettingsSeeder${NC}"
echo ""
echo "3. Inicie o servidor de desenvolvimento:"
echo "   ${YELLOW}php spark serve --port=8080${NC}"
echo ""
echo "4. Acesse o sistema:"
echo "   ${YELLOW}http://localhost:8080${NC}"
echo ""
echo "Login padrão:"
echo "   Email: admin@ponto.com.br"
echo "   Senha: Admin@123"
echo ""
echo -e "${YELLOW}⚠ IMPORTANTE: Altere a senha padrão após o primeiro login!${NC}"
echo ""
