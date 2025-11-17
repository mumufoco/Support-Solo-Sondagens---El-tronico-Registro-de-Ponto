#!/bin/bash
# CORREÇÃO CRÍTICA - Prioridade Máxima
# Sistema de Ponto Eletrônico - Fix Produção

echo "=========================================="
echo "🚨 CORREÇÃO CRÍTICA - ERRO 500"
echo "=========================================="
echo ""

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
cd "$SCRIPT_DIR"

echo "📍 Diretório: $SCRIPT_DIR"
echo ""

# ============================================================================
# PRIORIDADE CRÍTICA 1: Permissões dos Diretórios
# ============================================================================
echo -e "${BLUE}[CRÍTICO 1/4]${NC} Corrigindo Permissões dos Diretórios..."
echo ""

# Criar todos os diretórios necessários
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

# Ajustar permissões
chmod -R 775 writable/
if [ $? -eq 0 ]; then
    echo -e "${GREEN}  ✅ Permissões 775 aplicadas em writable/${NC}"
else
    echo -e "${RED}  ❌ Falha ao aplicar permissões${NC}"
fi

# Tentar mudar dono (pode falhar se não for root)
chown -R www-data:www-data writable/ 2>/dev/null
if [ $? -eq 0 ]; then
    echo -e "${GREEN}  ✅ Dono alterado para www-data${NC}"
else
    echo -e "${YELLOW}  ⚠️  Não foi possível alterar dono (execute como root se necessário)${NC}"
fi

# Criar arquivos index.html de segurança
for dir in "${DIRS[@]}"; do
    if [ ! -f "$dir/index.html" ]; then
        echo "<!DOCTYPE html><html><head><title>403 Forbidden</title></head><body><h1>Directory access is forbidden.</h1></body></html>" > "$dir/index.html"
    fi
done
echo -e "${GREEN}  ✅ Arquivos de segurança criados${NC}"
echo ""

# ============================================================================
# PRIORIDADE CRÍTICA 2: Verificar PHP
# ============================================================================
echo -e "${BLUE}[CRÍTICO 2/4]${NC} Verificando Versão do PHP..."
echo ""

PHP_VERSION=$(php -v | head -n 1 | awk '{print $2}')
echo "  PHP Version: $PHP_VERSION"

if php -v | grep -q "PHP 8.[1-9]"; then
    echo -e "${GREEN}  ✅ Versão do PHP compatível (>= 8.1)${NC}"
else
    echo -e "${RED}  ❌ AVISO: PHP 8.1+ requerido! Versão atual pode causar problemas.${NC}"
fi

# Verificar extensões críticas
REQUIRED_EXTS=("intl" "mbstring" "json" "mysqli" "curl" "gd")
MISSING_EXTS=()

for ext in "${REQUIRED_EXTS[@]}"; do
    if php -m | grep -qi "^$ext$"; then
        echo -e "  ${GREEN}✓${NC} $ext"
    else
        echo -e "  ${RED}✗${NC} $ext (FALTANDO!)"
        MISSING_EXTS+=("$ext")
    fi
done

if [ ${#MISSING_EXTS[@]} -eq 0 ]; then
    echo -e "${GREEN}  ✅ Todas as extensões necessárias estão instaladas${NC}"
else
    echo -e "${RED}  ❌ Extensões faltando: ${MISSING_EXTS[*]}${NC}"
fi
echo ""

# ============================================================================
# PRIORIDADE CRÍTICA 3: Testar Banco de Dados
# ============================================================================
echo -e "${BLUE}[CRÍTICO 3/4]${NC} Testando Conexão com Banco de Dados..."
echo ""

if [ -f ".env" ]; then
    DB_HOST=$(grep "^database.default.hostname" .env | cut -d'=' -f2 | tr -d ' ')
    DB_NAME=$(grep "^database.default.database" .env | cut -d'=' -f2 | tr -d ' ')
    DB_USER=$(grep "^database.default.username" .env | cut -d'=' -f2 | tr -d ' ')
    DB_PASS=$(grep "^database.default.password" .env | cut -d'=' -f2 | tr -d ' ')

    echo "  Host: $DB_HOST"
    echo "  Database: $DB_NAME"
    echo "  User: $DB_USER"
    echo ""

    # Tentar conectar
    if command -v mysql &> /dev/null; then
        if mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" -e "USE $DB_NAME" 2>/dev/null; then
            echo -e "${GREEN}  ✅ Conexão com banco de dados bem-sucedida!${NC}"
        else
            echo -e "${RED}  ❌ Falha ao conectar ao banco de dados${NC}"
            echo "  Teste manualmente: mysql -h $DB_HOST -u $DB_USER -p'$DB_PASS' $DB_NAME"
        fi
    else
        echo -e "${YELLOW}  ⚠️  Cliente MySQL não disponível para teste${NC}"
    fi
else
    echo -e "${RED}  ❌ Arquivo .env não encontrado!${NC}"
fi
echo ""

# ============================================================================
# PRIORIDADE CRÍTICA 4: Regenerar Autoloader
# ============================================================================
echo -e "${BLUE}[CRÍTICO 4/4]${NC} Regenerando Autoloader do Composer..."
echo ""

if [ -f "composer.json" ]; then
    if command -v composer &> /dev/null; then
        composer install --no-dev --optimize-autoloader
        if [ $? -eq 0 ]; then
            echo -e "${GREEN}  ✅ Autoloader regenerado com sucesso${NC}"
        else
            echo -e "${RED}  ❌ Falha ao regenerar autoloader${NC}"
        fi
    else
        echo -e "${YELLOW}  ⚠️  Composer não disponível${NC}"
        echo "  Execute manualmente: composer install --no-dev --optimize-autoloader"
    fi
else
    echo -e "${RED}  ❌ composer.json não encontrado!${NC}"
fi
echo ""

# ============================================================================
# VERIFICAÇÃO FINAL
# ============================================================================
echo "=========================================="
echo "📋 VERIFICAÇÃO FINAL"
echo "=========================================="
echo ""

# Verificar .env
if [ -f ".env" ]; then
    echo -e "${GREEN}✓${NC} .env existe"

    # Verificar session.savePath
    if grep -q "session.savePath = 'writable/session'" .env; then
        echo -e "${GREEN}✓${NC} session.savePath configurado corretamente"
    else
        echo -e "${YELLOW}⚠${NC} session.savePath precisa ser verificado"
    fi
else
    echo -e "${RED}✗${NC} .env NÃO encontrado"
fi

# Verificar php-config-production.php
if [ -f "public/php-config-production.php" ]; then
    echo -e "${GREEN}✓${NC} php-config-production.php existe"
else
    echo -e "${RED}✗${NC} php-config-production.php NÃO encontrado"
fi

# Verificar vendor/autoload.php
if [ -f "vendor/autoload.php" ]; then
    echo -e "${GREEN}✓${NC} vendor/autoload.php existe"
else
    echo -e "${RED}✗${NC} vendor/autoload.php NÃO encontrado"
fi

# Verificar app/Config/Paths.php
if grep -q "writable" app/Config/Paths.php 2>/dev/null; then
    echo -e "${GREEN}✓${NC} Paths.php usa 'writable' (correto)"
else
    echo -e "${YELLOW}⚠${NC} Paths.php pode estar usando 'storage'"
fi

echo ""
echo "=========================================="
echo -e "${GREEN}✅ CORREÇÕES CRÍTICAS APLICADAS${NC}"
echo "=========================================="
echo ""
echo "📋 Próximos passos:"
echo "  1. Acesse: https://ponto.supportsondagens.com.br/fix-session-error.php"
echo "  2. Execute o diagnóstico completo"
echo "  3. Teste o acesso: https://ponto.supportsondagens.com.br/auth/login"
echo ""
echo "📝 Se o erro persistir:"
echo "  - Verifique os logs: tail -f writable/logs/*.log"
echo "  - Configure SSL ou desabilite cookie_secure temporariamente"
echo "  - Execute: bash fix-ssl-cookie.sh"
echo ""
