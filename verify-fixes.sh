#!/bin/bash
# Script de Verificação - Todas as Correções Implementadas
# Valida que todos os fixes estão no código

echo "========================================================"
echo "🔍 VERIFICAÇÃO DE CORREÇÕES IMPLEMENTADAS"
echo "========================================================"
echo ""

GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

CHECKS_PASSED=0
CHECKS_FAILED=0

cd "$(dirname "$0")"

# ============================================================================
# VERIFICAÇÃO 1: public/index.php
# ============================================================================
echo -e "${BLUE}[1/5]${NC} Verificando public/index.php..."

if grep -q "define('ENVIRONMENT'" public/index.php; then
    echo -e "  ${GREEN}✓${NC} ENVIRONMENT constant definida"
    CHECKS_PASSED=$((CHECKS_PASSED + 1))
else
    echo -e "  ${RED}✗${NC} ENVIRONMENT constant NÃO definida"
    CHECKS_FAILED=$((CHECKS_FAILED + 1))
fi

if grep -q "php-config-production.php" public/index.php; then
    echo -e "  ${GREEN}✓${NC} Carrega php-config-production.php"
    CHECKS_PASSED=$((CHECKS_PASSED + 1))
else
    echo -e "  ${RED}✗${NC} NÃO carrega php-config-production.php"
    CHECKS_FAILED=$((CHECKS_FAILED + 1))
fi

if grep -q "bootstrap-exceptions.php" public/index.php; then
    echo -e "  ${GREEN}✓${NC} Carrega bootstrap-exceptions.php"
    CHECKS_PASSED=$((CHECKS_PASSED + 1))
else
    echo -e "  ${RED}✗${NC} NÃO carrega bootstrap-exceptions.php"
    CHECKS_FAILED=$((CHECKS_FAILED + 1))
fi

if grep -q "Boot::bootWeb" public/index.php; then
    echo -e "  ${GREEN}✓${NC} Usa Boot::bootWeb (correto)"
    CHECKS_PASSED=$((CHECKS_PASSED + 1))
else
    echo -e "  ${RED}✗${NC} NÃO usa Boot::bootWeb"
    CHECKS_FAILED=$((CHECKS_FAILED + 1))
fi

if grep -q "system/bootstrap\.php" public/index.php; then
    echo -e "  ${RED}✗${NC} Ainda referencia bootstrap.php antigo!"
    CHECKS_FAILED=$((CHECKS_FAILED + 1))
else
    echo -e "  ${GREEN}✓${NC} Não referencia bootstrap.php antigo"
    CHECKS_PASSED=$((CHECKS_PASSED + 1))
fi

echo ""

# ============================================================================
# VERIFICAÇÃO 2: public/php-config-production.php
# ============================================================================
echo -e "${BLUE}[2/5]${NC} Verificando public/php-config-production.php..."

if [ -f "public/php-config-production.php" ]; then
    echo -e "  ${GREEN}✓${NC} Arquivo existe"
    CHECKS_PASSED=$((CHECKS_PASSED + 1))

    if grep -q "session.save_path" public/php-config-production.php; then
        echo -e "  ${GREEN}✓${NC} Configura session.save_path"
        CHECKS_PASSED=$((CHECKS_PASSED + 1))
    else
        echo -e "  ${RED}✗${NC} NÃO configura session.save_path"
        CHECKS_FAILED=$((CHECKS_FAILED + 1))
    fi

    if grep -q "mkdir.*session" public/php-config-production.php; then
        echo -e "  ${GREEN}✓${NC} Cria diretório session"
        CHECKS_PASSED=$((CHECKS_PASSED + 1))
    else
        echo -e "  ${YELLOW}⚠${NC} Não cria diretório session"
    fi
else
    echo -e "  ${RED}✗${NC} Arquivo NÃO existe!"
    CHECKS_FAILED=$((CHECKS_FAILED + 3))
fi

echo ""

# ============================================================================
# VERIFICAÇÃO 3: public/bootstrap-exceptions.php
# ============================================================================
echo -e "${BLUE}[3/5]${NC} Verificando public/bootstrap-exceptions.php..."

if [ -f "public/bootstrap-exceptions.php" ]; then
    echo -e "  ${GREEN}✓${NC} Arquivo existe"
    CHECKS_PASSED=$((CHECKS_PASSED + 1))

    if grep -q "InvalidArgumentException" public/bootstrap-exceptions.php; then
        echo -e "  ${GREEN}✓${NC} Carrega InvalidArgumentException"
        CHECKS_PASSED=$((CHECKS_PASSED + 1))
    else
        echo -e "  ${RED}✗${NC} NÃO carrega InvalidArgumentException"
        CHECKS_FAILED=$((CHECKS_FAILED + 1))
    fi

    if grep -q "mkdir.*session" public/bootstrap-exceptions.php; then
        echo -e "  ${GREEN}✓${NC} Cria diretório session (backup)"
        CHECKS_PASSED=$((CHECKS_PASSED + 1))
    else
        echo -e "  ${YELLOW}⚠${NC} Não cria diretório session"
    fi

    CLASSES_COUNT=$(grep -c "Exceptions/" public/bootstrap-exceptions.php)
    echo -e "  ${GREEN}✓${NC} Carrega $CLASSES_COUNT classes de exceção"
    CHECKS_PASSED=$((CHECKS_PASSED + 1))
else
    echo -e "  ${RED}✗${NC} Arquivo NÃO existe!"
    CHECKS_FAILED=$((CHECKS_FAILED + 3))
fi

echo ""

# ============================================================================
# VERIFICAÇÃO 4: app/Config/Paths.php
# ============================================================================
echo -e "${BLUE}[4/5]${NC} Verificando app/Config/Paths.php..."

if grep -q "writable" app/Config/Paths.php; then
    echo -e "  ${GREEN}✓${NC} Usa 'writable' (correto)"
    CHECKS_PASSED=$((CHECKS_PASSED + 1))

    if grep -q "storage" app/Config/Paths.php; then
        echo -e "  ${RED}✗${NC} Ainda referencia 'storage'!"
        CHECKS_FAILED=$((CHECKS_FAILED + 1))
    else
        echo -e "  ${GREEN}✓${NC} Não referencia 'storage'"
        CHECKS_PASSED=$((CHECKS_PASSED + 1))
    fi
else
    echo -e "  ${RED}✗${NC} NÃO usa 'writable'!"
    CHECKS_FAILED=$((CHECKS_FAILED + 2))
fi

echo ""

# ============================================================================
# VERIFICAÇÃO 5: .env
# ============================================================================
echo -e "${BLUE}[5/5]${NC} Verificando .env..."

if [ -f ".env" ]; then
    echo -e "  ${GREEN}✓${NC} Arquivo .env existe"
    CHECKS_PASSED=$((CHECKS_PASSED + 1))

    if grep -q "CI_ENVIRONMENT" .env; then
        echo -e "  ${GREEN}✓${NC} CI_ENVIRONMENT definido"
        CHECKS_PASSED=$((CHECKS_PASSED + 1))
    else
        echo -e "  ${RED}✗${NC} CI_ENVIRONMENT NÃO definido"
        CHECKS_FAILED=$((CHECKS_FAILED + 1))
    fi

    if grep -q "session.savePath.*writable/session" .env; then
        echo -e "  ${GREEN}✓${NC} session.savePath configurado"
        CHECKS_PASSED=$((CHECKS_PASSED + 1))
    else
        echo -e "  ${RED}✗${NC} session.savePath NÃO configurado"
        CHECKS_FAILED=$((CHECKS_FAILED + 1))
    fi
else
    echo -e "  ${RED}✗${NC} Arquivo .env NÃO existe!"
    CHECKS_FAILED=$((CHECKS_FAILED + 3))
fi

echo ""

# ============================================================================
# VERIFICAÇÃO DE DIRETÓRIOS
# ============================================================================
echo -e "${BLUE}[EXTRA]${NC} Verificando diretórios writable/..."

if [ -d "writable/session" ]; then
    PERMS=$(stat -c "%a" writable/session 2>/dev/null || echo "???")
    if [ "$PERMS" = "777" ] || [ "$PERMS" = "775" ]; then
        echo -e "  ${GREEN}✓${NC} writable/session existe com permissão $PERMS"
    else
        echo -e "  ${YELLOW}⚠${NC} writable/session existe mas permissão é $PERMS"
    fi
else
    echo -e "  ${YELLOW}⚠${NC} writable/session NÃO existe (será criado automaticamente)"
fi

echo ""

# ============================================================================
# RESUMO FINAL
# ============================================================================
TOTAL_CHECKS=$((CHECKS_PASSED + CHECKS_FAILED))
PERCENTAGE=$((CHECKS_PASSED * 100 / TOTAL_CHECKS))

echo "========================================================"
echo "📊 RESUMO DA VERIFICAÇÃO"
echo "========================================================"
echo ""
echo "Total de verificações: $TOTAL_CHECKS"
echo -e "Passaram: ${GREEN}$CHECKS_PASSED${NC}"
echo -e "Falharam: ${RED}$CHECKS_FAILED${NC}"
echo "Porcentagem: $PERCENTAGE%"
echo ""

if [ $CHECKS_FAILED -eq 0 ]; then
    echo -e "${GREEN}✅ TODAS AS CORREÇÕES ESTÃO IMPLEMENTADAS!${NC}"
    echo ""
    echo "🎯 Sistema pronto para produção!"
    echo "   Teste: https://ponto.supportsondagens.com.br/auth/login"
    exit 0
else
    echo -e "${RED}⚠️ ALGUMAS CORREÇÕES ESTÃO FALTANDO!${NC}"
    echo ""
    echo "Execute o fix automatizado:"
    echo "  ./fix-all-errors.sh"
    echo ""
    echo "Ou atualize manualmente:"
    echo "  git pull origin claude/fix-composer-setup-warning-01NedH4Ms8iQLTecqkxf9rPx"
    exit 1
fi
