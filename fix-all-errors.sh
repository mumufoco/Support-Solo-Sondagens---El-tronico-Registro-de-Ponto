#!/bin/bash
# CORREÇÃO EMERGENCIAL - Sistema de Ponto Eletrônico
# Corrige TODOS os erros críticos de uma vez

echo "=============================================="
echo "🚨 CORREÇÃO EMERGENCIAL - SISTEMA COMPLETO"
echo "=============================================="
echo ""

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

cd "$(dirname "$0")"

echo "📍 Diretório: $(pwd)"
echo ""

# ============================================================================
# CRÍTICO 1: Fazer backup do index.php atual
# ============================================================================
echo -e "${BLUE}[1/6]${NC} Fazendo backup de public/index.php..."
if [ -f "public/index.php" ]; then
    cp public/index.php public/index.php.backup.$(date +%Y%m%d_%H%M%S)
    echo -e "${GREEN}✓ Backup criado${NC}"
else
    echo -e "${RED}✗ index.php não encontrado!${NC}"
fi
echo ""

# ============================================================================
# CRÍTICO 2: Atualizar do repositório
# ============================================================================
echo -e "${BLUE}[2/6]${NC} Atualizando do repositório Git..."
git fetch origin claude/fix-composer-setup-warning-01NedH4Ms8iQLTecqkxf9rPx
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Fetch concluído${NC}"

    # Reset para garantir que está limpo
    git reset --hard origin/claude/fix-composer-setup-warning-01NedH4Ms8iQLTecqkxf9rPx
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✓ Código atualizado${NC}"
    else
        echo -e "${RED}✗ Falha no reset${NC}"
    fi
else
    echo -e "${RED}✗ Falha no fetch${NC}"
fi
echo ""

# ============================================================================
# CRÍTICO 3: Criar diretórios writable
# ============================================================================
echo -e "${BLUE}[3/6]${NC} Criando/verificando diretórios writable..."
DIRS=(
    "writable"
    "writable/session"
    "writable/cache"
    "writable/cache/data"
    "writable/logs"
    "writable/uploads"
    "writable/debugbar"
    "writable/biometric"
    "writable/biometric/faces"
    "writable/biometric/fingerprints"
    "writable/exports"
)

for dir in "${DIRS[@]}"; do
    if [ ! -d "$dir" ]; then
        mkdir -p "$dir"
        echo "  ✓ Criado: $dir"
    fi
done

# Aplicar permissões
chmod -R 777 writable/
echo -e "${GREEN}✓ Permissões 777 aplicadas${NC}"
echo ""

# ============================================================================
# CRÍTICO 4: Verificar arquivos críticos
# ============================================================================
echo -e "${BLUE}[4/6]${NC} Verificando arquivos críticos..."

FILES=(
    "public/index.php:ENVIRONMENT constant"
    "public/php-config-production.php:session.save_path"
    "public/bootstrap-exceptions.php:InvalidArgumentException"
    "app/Config/Paths.php:writable"
    "vendor/autoload.php:Composer autoload"
    ".env:CI_ENVIRONMENT"
)

MISSING=0
for item in "${FILES[@]}"; do
    FILE="${item%%:*}"
    DESC="${item##*:}"

    if [ -f "$FILE" ]; then
        echo -e "  ${GREEN}✓${NC} $FILE"
    else
        echo -e "  ${RED}✗${NC} $FILE (FALTANDO!)"
        MISSING=$((MISSING + 1))
    fi
done

if [ $MISSING -gt 0 ]; then
    echo -e "${RED}✗ $MISSING arquivo(s) crítico(s) faltando!${NC}"
else
    echo -e "${GREEN}✓ Todos os arquivos críticos presentes${NC}"
fi
echo ""

# ============================================================================
# CRÍTICO 5: Verificar index.php
# ============================================================================
echo -e "${BLUE}[5/6]${NC} Verificando conteúdo de public/index.php..."

if grep -q "define('ENVIRONMENT'" public/index.php; then
    echo -e "  ${GREEN}✓${NC} ENVIRONMENT constant definida"
else
    echo -e "  ${RED}✗${NC} ENVIRONMENT constant NÃO definida!"
fi

if grep -q "Boot::bootWeb" public/index.php; then
    echo -e "  ${GREEN}✓${NC} Usa Boot::bootWeb (correto)"
else
    echo -e "  ${RED}✗${NC} NÃO usa Boot::bootWeb (ERRO!)"
fi

if grep -q "bootstrap\.php" public/index.php; then
    echo -e "  ${RED}✗${NC} Ainda referencia bootstrap.php (ERRO!)"
else
    echo -e "  ${GREEN}✓${NC} Não referencia bootstrap.php antigo"
fi

if grep -q "bootstrap-exceptions\.php" public/index.php; then
    echo -e "  ${GREEN}✓${NC} Carrega bootstrap-exceptions.php"
else
    echo -e "  ${YELLOW}⚠${NC} Não carrega bootstrap-exceptions.php"
fi
echo ""

# ============================================================================
# CRÍTICO 6: Limpar caches
# ============================================================================
echo -e "${BLUE}[6/6]${NC} Limpando todos os caches..."
rm -rf writable/cache/*
rm -rf writable/debugbar/*
echo -e "${GREEN}✓ Caches limpos${NC}"
echo ""

# ============================================================================
# RESUMO FINAL
# ============================================================================
echo "=============================================="
echo "📋 RESUMO DA CORREÇÃO"
echo "=============================================="
echo ""

echo "Arquivos verificados:"
ls -lh public/index.php | awk '{print "  index.php: " $5 " - " $6 " " $7 " " $8}'
ls -lh public/php-config-production.php 2>/dev/null | awk '{print "  php-config-production.php: " $5 " - " $6 " " $7 " " $8}' || echo "  php-config-production.php: NÃO ENCONTRADO"
ls -lh public/bootstrap-exceptions.php 2>/dev/null | awk '{print "  bootstrap-exceptions.php: " $5 " - " $6 " " $7 " " $8}' || echo "  bootstrap-exceptions.php: NÃO ENCONTRADO"
echo ""

echo "Diretórios críticos:"
ls -ld writable/session | awk '{print "  writable/session: " $1 " " $3 ":" $4}'
echo ""

echo "Versões instaladas:"
php -v | head -1
echo ""

if [ $MISSING -eq 0 ]; then
    echo -e "${GREEN}✅ CORREÇÃO CONCLUÍDA COM SUCESSO!${NC}"
    echo ""
    echo "🎯 Próximos passos:"
    echo "  1. Teste o acesso: https://ponto.supportsondagens.com.br/auth/login"
    echo "  2. Se houver erro, verifique: tail -f writable/logs/log-*.log"
    echo "  3. Se persistir, execute: composer dump-autoload --optimize"
else
    echo -e "${RED}⚠️ ATENÇÃO: Alguns arquivos estão faltando!${NC}"
    echo ""
    echo "Execute manualmente:"
    echo "  git pull origin claude/fix-composer-setup-warning-01NedH4Ms8iQLTecqkxf9rPx"
    echo "  chmod -R 777 writable/"
fi
echo "=============================================="
echo ""
