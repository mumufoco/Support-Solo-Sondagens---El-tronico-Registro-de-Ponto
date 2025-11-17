#!/bin/bash
################################################################################
# Script de Setup de Permissões - Sistema de Ponto Eletrônico
#
# Este script configura as permissões corretas para todos os diretórios
# necessários para o funcionamento do CodeIgniter 4.
#
# USO:
#   chmod +x setup-permissions.sh
#   ./setup-permissions.sh
#
# EM PRODUÇÃO (cPanel/Shared Hosting):
#   bash setup-permissions.sh
################################################################################

set -e  # Exit on error

echo "=========================================="
echo "  Setup de Permissões - Ponto Eletrônico"
echo "=========================================="
echo ""

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Get script directory
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

echo "📁 Diretório: $SCRIPT_DIR"
echo ""

# 1. Create necessary directories
echo "1️⃣  Criando diretórios necessários..."

DIRECTORIES=(
    "writable"
    "writable/cache"
    "writable/cache/data"
    "writable/debugbar"
    "writable/logs"
    "writable/session"
    "writable/uploads"
    "writable/biometric"
    "writable/biometric/faces"
    "writable/biometric/fingerprints"
    "writable/exports"
    "public/uploads"
)

for dir in "${DIRECTORIES[@]}"; do
    if [ ! -d "$dir" ]; then
        mkdir -p "$dir"
        echo "   ${GREEN}✓${NC} Criado: $dir"
    else
        echo "   ${YELLOW}→${NC} Existe: $dir"
    fi
done

echo ""

# 2. Set permissions
echo "2️⃣  Configurando permissões..."

# writable directory and all subdirectories (775 for directories, 664 for files)
find writable -type d -exec chmod 775 {} \; 2>/dev/null || true
find writable -type f -exec chmod 664 {} \; 2>/dev/null || true
echo "   ${GREEN}✓${NC} writable/: 775 (dirs) / 664 (files)"

# public/uploads (if exists)
if [ -d "public/uploads" ]; then
    find public/uploads -type d -exec chmod 775 {} \; 2>/dev/null || true
    find public/uploads -type f -exec chmod 664 {} \; 2>/dev/null || true
    echo "   ${GREEN}✓${NC} public/uploads/: 775 (dirs) / 664 (files)"
fi

# .env file (read-only for owner)
if [ -f ".env" ]; then
    chmod 600 .env
    echo "   ${GREEN}✓${NC} .env: 600 (segurança)"
fi

echo ""

# 3. Create .htaccess files for security
echo "3️⃣  Criando arquivos de segurança..."

# writable/.htaccess (block all web access)
cat > writable/.htaccess << 'EOF'
# Block all web access to writable directory
<IfModule authz_core_module>
    Require all denied
</IfModule>
<IfModule !authz_core_module>
    Deny from all
</IfModule>
EOF
chmod 644 writable/.htaccess
echo "   ${GREEN}✓${NC} writable/.htaccess (proteção web)"

# writable/uploads/.htaccess (allow images but not scripts)
if [ -d "writable/uploads" ]; then
    cat > writable/uploads/.htaccess << 'EOF'
# Allow only image files, block scripts
<FilesMatch "\.(php|php3|php4|php5|phtml|pl|py|jsp|asp|sh|cgi)$">
    <IfModule authz_core_module>
        Require all denied
    </IfModule>
    <IfModule !authz_core_module>
        Deny from all
    </IfModule>
</FilesMatch>
EOF
    chmod 644 writable/uploads/.htaccess
    echo "   ${GREEN}✓${NC} writable/uploads/.htaccess (proteção scripts)"
fi

# public/uploads/.htaccess (allow images but not scripts)
if [ -d "public/uploads" ]; then
    cat > public/uploads/.htaccess << 'EOF'
# Allow only image files, block scripts
<FilesMatch "\.(php|php3|php4|php5|phtml|pl|py|jsp|asp|sh|cgi)$">
    <IfModule authz_core_module>
        Require all denied
    </IfModule>
    <IfModule !authz_core_module>
        Deny from all
    </IfModule>
</FilesMatch>
EOF
    chmod 644 public/uploads/.htaccess
    echo "   ${GREEN}✓${NC} public/uploads/.htaccess (proteção scripts)"
fi

echo ""

# 4. Create index.html files to prevent directory listing
echo "4️⃣  Prevenindo listagem de diretórios..."

for dir in "${DIRECTORIES[@]}"; do
    if [ -d "$dir" ] && [ ! -f "$dir/index.html" ]; then
        echo "<!DOCTYPE html><html><head><title>403 Forbidden</title></head><body><h1>Directory access is forbidden.</h1></body></html>" > "$dir/index.html"
        chmod 644 "$dir/index.html"
    fi
done
echo "   ${GREEN}✓${NC} index.html criados em todos os diretórios"

echo ""

# 5. Verify permissions
echo "5️⃣  Verificando permissões..."

# Check if writable is writable
if [ -w "writable" ]; then
    echo "   ${GREEN}✓${NC} writable/ está gravável"
else
    echo "   ${RED}✗${NC} writable/ NÃO está gravável!"
    exit 1
fi

# Check if writable/session is writable
if [ -w "writable/session" ]; then
    echo "   ${GREEN}✓${NC} writable/session/ está gravável"
else
    echo "   ${RED}✗${NC} writable/session/ NÃO está gravável!"
    exit 1
fi

# Check if writable/logs is writable
if [ -w "writable/logs" ]; then
    echo "   ${GREEN}✓${NC} writable/logs/ está gravável"
else
    echo "   ${RED}✗${NC} writable/logs/ NÃO está gravável!"
    exit 1
fi

# Check if writable/cache is writable
if [ -w "writable/cache" ]; then
    echo "   ${GREEN}✓${NC} writable/cache/ está gravável"
else
    echo "   ${RED}✗${NC} writable/cache/ NÃO está gravável!"
    exit 1
fi

echo ""
echo "${GREEN}=========================================="
echo "  ✓ Setup concluído com sucesso!"
echo "==========================================${NC}"
echo ""
echo "📝 Próximos passos:"
echo "   1. Verifique se o site está funcionando"
echo "   2. Se ainda tiver erros de permissão, execute:"
echo "      chmod -R 777 writable/"
echo "   3. Em produção, ajuste para 755/644 após testes"
echo ""
