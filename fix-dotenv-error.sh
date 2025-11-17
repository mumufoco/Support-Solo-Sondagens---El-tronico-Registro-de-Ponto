#!/bin/bash
# Fix DotEnv InvalidArgumentException Error
# Resolves class loading issues during bootstrap

echo "=========================================="
echo "🔧 FIX: DotEnv InvalidArgumentException"
echo "=========================================="
echo ""

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
cd "$SCRIPT_DIR"

echo "📍 Diretório: $SCRIPT_DIR"
echo ""

# Step 1: Check if vendor/autoload.php exists
echo -e "${BLUE}[1/6]${NC} Verificando Composer autoload..."
if [ ! -f "vendor/autoload.php" ]; then
    echo -e "${RED}❌ vendor/autoload.php NÃO encontrado!${NC}"
    echo ""
    echo "Executando composer install..."

    if command -v composer &> /dev/null; then
        composer install --no-dev --optimize-autoloader
        if [ $? -eq 0 ]; then
            echo -e "${GREEN}✅ Composer install concluído${NC}"
        else
            echo -e "${RED}❌ Falha no composer install${NC}"
            exit 1
        fi
    else
        echo -e "${RED}❌ Composer não disponível!${NC}"
        echo "Instale o Composer ou execute manualmente: composer install"
        exit 1
    fi
else
    echo -e "${GREEN}✅ vendor/autoload.php existe${NC}"
fi
echo ""

# Step 2: Dump autoload to regenerate classmap
echo -e "${BLUE}[2/6]${NC} Regenerando classmap do autoloader..."
if command -v composer &> /dev/null; then
    composer dump-autoload --optimize --no-dev
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✅ Autoloader regenerado${NC}"
    else
        echo -e "${YELLOW}⚠️  Falha ao regenerar (pode não ser crítico)${NC}"
    fi
else
    echo -e "${YELLOW}⚠️  Composer não disponível${NC}"
fi
echo ""

# Step 3: Verify InvalidArgumentException exists
echo -e "${BLUE}[3/6]${NC} Verificando classe InvalidArgumentException..."
EXCEPTION_FILE="vendor/codeigniter4/framework/system/Exceptions/InvalidArgumentException.php"
if [ -f "$EXCEPTION_FILE" ]; then
    echo -e "${GREEN}✅ InvalidArgumentException.php existe${NC}"
    echo "   Localização: $EXCEPTION_FILE"
else
    echo -e "${RED}❌ InvalidArgumentException.php NÃO encontrado!${NC}"
    echo ""
    echo "Isso indica que o CodeIgniter 4 não está instalado corretamente."
    echo "Execute: composer install"
    exit 1
fi
echo ""

# Step 4: Check .env file
echo -e "${BLUE}[4/6]${NC} Verificando arquivo .env..."
if [ ! -f ".env" ]; then
    echo -e "${RED}❌ .env NÃO encontrado!${NC}"

    if [ -f "env" ]; then
        echo "Arquivo 'env' encontrado. Copiando para .env..."
        cp env .env
        echo -e "${GREEN}✅ .env criado a partir de 'env'${NC}"
    elif [ -f ".env.example" ]; then
        echo "Arquivo '.env.example' encontrado. Copiando para .env..."
        cp .env.example .env
        echo -e "${GREEN}✅ .env criado a partir de .env.example${NC}"
    else
        echo -e "${RED}❌ Nenhum arquivo base encontrado para criar .env!${NC}"
        exit 1
    fi
else
    echo -e "${GREEN}✅ .env existe${NC}"
fi
echo ""

# Step 5: Validate .env syntax
echo -e "${BLUE}[5/6]${NC} Validando sintaxe do .env..."
if [ -f ".env" ]; then
    # Check for common syntax errors
    ERRORS=0

    # Check for unquoted special characters
    if grep -qE "^[^#].*=.*[;|&]" .env 2>/dev/null; then
        echo -e "${YELLOW}⚠️  Possível erro: caracteres especiais sem aspas${NC}"
        ERRORS=$((ERRORS + 1))
    fi

    # Check for Windows line endings
    if file .env | grep -q "CRLF"; then
        echo -e "${YELLOW}⚠️  Detectado line endings Windows (CRLF)${NC}"
        echo "   Convertendo para Unix (LF)..."
        dos2unix .env 2>/dev/null || sed -i 's/\r$//' .env
        echo -e "${GREEN}✅ Convertido para LF${NC}"
    fi

    # Check for empty critical values
    if grep -qE "^(CI_ENVIRONMENT|app\.baseURL)\s*=\s*$" .env; then
        echo -e "${YELLOW}⚠️  Variáveis críticas vazias detectadas${NC}"
        ERRORS=$((ERRORS + 1))
    fi

    if [ $ERRORS -eq 0 ]; then
        echo -e "${GREEN}✅ Sintaxe do .env parece correta${NC}"
    else
        echo -e "${YELLOW}⚠️  $ERRORS possível(is) problema(s) detectado(s)${NC}"
    fi
else
    echo -e "${RED}❌ .env não existe!${NC}"
fi
echo ""

# Step 6: Clear all caches
echo -e "${BLUE}[6/6]${NC} Limpando caches..."
rm -rf writable/cache/*
rm -rf writable/debugbar/*
echo -e "${GREEN}✅ Caches limpos${NC}"
echo ""

# Final verification
echo "=========================================="
echo "📋 VERIFICAÇÃO FINAL"
echo "=========================================="
echo ""

CHECKS_PASSED=0
CHECKS_TOTAL=6

echo "Verificando componentes críticos:"
echo ""

# 1. vendor/autoload.php
if [ -f "vendor/autoload.php" ]; then
    echo -e "${GREEN}✓${NC} vendor/autoload.php"
    CHECKS_PASSED=$((CHECKS_PASSED + 1))
else
    echo -e "${RED}✗${NC} vendor/autoload.php"
fi

# 2. InvalidArgumentException
if [ -f "$EXCEPTION_FILE" ]; then
    echo -e "${GREEN}✓${NC} InvalidArgumentException.php"
    CHECKS_PASSED=$((CHECKS_PASSED + 1))
else
    echo -e "${RED}✗${NC} InvalidArgumentException.php"
fi

# 3. .env
if [ -f ".env" ]; then
    echo -e "${GREEN}✓${NC} .env"
    CHECKS_PASSED=$((CHECKS_PASSED + 1))
else
    echo -e "${RED}✗${NC} .env"
fi

# 4. writable/session
if [ -d "writable/session" ] && [ -w "writable/session" ]; then
    echo -e "${GREEN}✓${NC} writable/session (gravável)"
    CHECKS_PASSED=$((CHECKS_PASSED + 1))
else
    echo -e "${RED}✗${NC} writable/session (não gravável)"
fi

# 5. php-config-production.php
if [ -f "public/php-config-production.php" ]; then
    echo -e "${GREEN}✓${NC} php-config-production.php"
    CHECKS_PASSED=$((CHECKS_PASSED + 1))
else
    echo -e "${YELLOW}⚠${NC} php-config-production.php (opcional)"
fi

# 6. Paths.php
if grep -q "writable" app/Config/Paths.php 2>/dev/null; then
    echo -e "${GREEN}✓${NC} Paths.php (usa 'writable')"
    CHECKS_PASSED=$((CHECKS_PASSED + 1))
else
    echo -e "${RED}✗${NC} Paths.php (pode estar usando 'storage')"
fi

echo ""
echo "=========================================="
if [ $CHECKS_PASSED -ge 5 ]; then
    echo -e "${GREEN}✅ CORREÇÃO CONCLUÍDA COM SUCESSO!${NC}"
    echo ""
    echo "Checks: $CHECKS_PASSED/$CHECKS_TOTAL passaram"
    echo ""
    echo "🎯 Próximos passos:"
    echo "  1. Recarregue a página do sistema"
    echo "  2. O erro InvalidArgumentException deve estar resolvido"
    echo "  3. Se persistir, verifique os logs:"
    echo "     tail -f writable/logs/log-*.log"
else
    echo -e "${RED}⚠️  ATENÇÃO: Alguns checks falharam${NC}"
    echo ""
    echo "Checks: $CHECKS_PASSED/$CHECKS_TOTAL passaram"
    echo ""
    echo "Execute manualmente:"
    echo "  composer install --no-dev --optimize-autoloader"
fi
echo "=========================================="
echo ""
