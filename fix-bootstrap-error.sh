#!/bin/bash
# Fix CodeIgniter 4.5+ Bootstrap Error
# This script fixes the "ENVIRONMENT constant undefined" error

echo "=========================================="
echo "CodeIgniter 4.5+ Bootstrap Fix"
echo "=========================================="
echo ""

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Get script directory
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
cd "$SCRIPT_DIR"

echo "📍 Diretório atual: $SCRIPT_DIR"
echo ""

# Step 1: Check if app/Config/Paths.php exists
echo "🔍 [1/5] Verificando arquivo Paths.php..."
if [ ! -f "app/Config/Paths.php" ]; then
    echo -e "${RED}❌ Erro: app/Config/Paths.php não encontrado!${NC}"
    exit 1
fi
echo -e "${GREEN}✓ Arquivo encontrado${NC}"
echo ""

# Step 2: Fix Paths.php - change storage to writable
echo "🔧 [2/5] Corrigindo caminho do writable directory..."
if grep -q "'/../../storage'" app/Config/Paths.php; then
    sed -i "s|'/../../storage'|'/../../writable'|g" app/Config/Paths.php
    echo -e "${GREEN}✓ Paths.php corrigido: storage → writable${NC}"
elif grep -q '"/../../storage"' app/Config/Paths.php; then
    sed -i 's|"/../../storage"|"/../../writable"|g' app/Config/Paths.php
    echo -e "${GREEN}✓ Paths.php corrigido: storage → writable${NC}"
else
    echo -e "${YELLOW}⚠ Paths.php já está usando 'writable'${NC}"
fi
echo ""

# Step 3: Ensure writable directory exists with correct structure
echo "📁 [3/5] Garantindo estrutura do diretório writable..."
mkdir -p writable/{logs,cache,session,debugbar,uploads,biometric,exports}
echo -e "${GREEN}✓ Subdiretórios criados${NC}"
echo ""

# Step 4: Set permissions
echo "🔒 [4/5] Ajustando permissões..."
chmod -R 775 writable
echo -e "${GREEN}✓ Permissões configuradas (775)${NC}"
echo ""

# Step 5: Clear cache
echo "🧹 [5/5] Limpando cache..."
rm -rf writable/cache/*
echo -e "${GREEN}✓ Cache limpo${NC}"
echo ""

echo "=========================================="
echo -e "${GREEN}✅ CORREÇÃO CONCLUÍDA COM SUCESSO!${NC}"
echo "=========================================="
echo ""
echo "📋 O que foi feito:"
echo "  1. ✓ Paths.php atualizado para usar 'writable'"
echo "  2. ✓ Estrutura de diretórios criada"
echo "  3. ✓ Permissões ajustadas (775)"
echo "  4. ✓ Cache limpo"
echo ""
echo "🚀 Próximos passos:"
echo "  1. Recarregue a página no navegador"
echo "  2. O erro 'Undefined constant ENVIRONMENT' deve desaparecer"
echo "  3. O sistema deve carregar normalmente"
echo ""
echo "⚠️  Se o erro persistir:"
echo "  • Verifique se o servidor web tem permissão de leitura"
echo "  • Execute: sudo chown -R www-data:www-data writable/"
echo "  • Verifique os logs: tail -f writable/logs/*.log"
echo ""
