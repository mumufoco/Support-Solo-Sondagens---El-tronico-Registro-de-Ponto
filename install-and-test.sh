#!/bin/bash
#
# INSTALAÇÃO COMPLETA E TESTE REAL DO SISTEMA
# Este script vai instalar tudo do zero e testar completamente
#

set -e  # Exit on error

echo ""
echo "════════════════════════════════════════════════════════════════"
echo "  INSTALAÇÃO COMPLETA E TESTE REAL"
echo "════════════════════════════════════════════════════════════════"
echo ""

# Cores
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Diretório base
BASE_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$BASE_DIR"

echo "📁 Diretório base: $BASE_DIR"
echo ""

# Passo 1: Verificar PHP
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "📋 PASSO 1: Verificando PHP"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

PHP_VERSION=$(php -r "echo PHP_VERSION;")
echo -e "${GREEN}✅ PHP Version: $PHP_VERSION${NC}"

# Verificar extensões necessárias
REQUIRED_EXTENSIONS=("mysqli" "pdo" "pdo_mysql" "mbstring" "intl" "json" "curl")
MISSING_EXTENSIONS=()

for ext in "${REQUIRED_EXTENSIONS[@]}"; do
    if php -m | grep -q "^$ext$"; then
        echo -e "${GREEN}✅${NC} Extensão $ext instalada"
    else
        echo -e "${RED}❌${NC} Extensão $ext NÃO instalada"
        MISSING_EXTENSIONS+=("$ext")
    fi
done

if [ ${#MISSING_EXTENSIONS[@]} -ne 0 ]; then
    echo -e "${YELLOW}⚠️  Extensões faltando: ${MISSING_EXTENSIONS[*]}${NC}"
    echo "   Continuando mesmo assim..."
fi

echo ""

# Passo 2: Verificar Composer
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "📋 PASSO 2: Verificando Composer"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

if command -v composer &> /dev/null; then
    COMPOSER_VERSION=$(composer --version | head -1)
    echo -e "${GREEN}✅ $COMPOSER_VERSION${NC}"
else
    echo -e "${RED}❌ Composer não instalado!${NC}"
    echo "Instalando Composer..."

    php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
    php composer-setup.php --quiet
    rm composer-setup.php
    mv composer.phar /usr/local/bin/composer || sudo mv composer.phar /usr/local/bin/composer

    echo -e "${GREEN}✅ Composer instalado!${NC}"
fi

echo ""

# Passo 3: Instalar dependências
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "📋 PASSO 3: Instalando Dependências do Composer"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

if [ -f "composer.json" ]; then
    echo "📦 Executando: composer install --no-dev --optimize-autoloader"
    composer install --no-dev --optimize-autoloader --no-interaction
    echo -e "${GREEN}✅ Dependências instaladas!${NC}"
else
    echo -e "${RED}❌ composer.json não encontrado!${NC}"
    exit 1
fi

echo ""

# Passo 4: Verificar CodeIgniter
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "📋 PASSO 4: Verificando CodeIgniter 4"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

if [ -d "vendor/codeigniter4/framework" ]; then
    CI_VERSION=$(grep -oP "const VERSION = '\K[^']+" vendor/codeigniter4/framework/system/CodeIgniter.php | head -1)
    echo -e "${GREEN}✅ CodeIgniter 4 instalado: $CI_VERSION${NC}"
else
    echo -e "${RED}❌ CodeIgniter 4 NÃO instalado!${NC}"
    exit 1
fi

echo ""

# Passo 5: Verificar estrutura de diretórios
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "📋 PASSO 5: Verificando Estrutura de Diretórios"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

DIRS=("app" "public" "writable" "writable/session" "writable/logs" "vendor")

for dir in "${DIRS[@]}"; do
    if [ -d "$dir" ]; then
        echo -e "${GREEN}✅${NC} $dir"
    else
        echo -e "${YELLOW}⚠️${NC}  $dir não existe, criando..."
        mkdir -p "$dir"
        chmod 755 "$dir"
    fi
done

# Garantir permissões de escrita
chmod -R 755 writable/
echo -e "${GREEN}✅ Permissões ajustadas em writable/${NC}"

echo ""

# Passo 6: Criar .env para testes
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "📋 PASSO 6: Configurando .env para Testes"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

if [ ! -f ".env" ]; then
    cat > .env << 'EOF'
CI_ENVIRONMENT = development

app.baseURL = 'http://localhost:8080/'
app.forceGlobalSecureRequests = false

database.default.hostname = localhost
database.default.database = test_ponto
database.default.username = root
database.default.password =
database.default.DBDriver = MySQLi
database.default.port = 3306

logger.threshold = 9

session.driver = 'CodeIgniter\Session\Handlers\FileHandler'
session.cookieName = 'ci_session'
session.expiration = 7200
session.savePath = writable/session
EOF
    echo -e "${GREEN}✅ .env criado para testes${NC}"
else
    echo -e "${YELLOW}⚠️  .env já existe, mantendo...${NC}"
fi

echo ""

# Passo 7: Executar diagnóstico completo
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "📋 PASSO 7: Executando Diagnóstico Completo"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

if [ -f "diagnostico-404.php" ]; then
    php diagnostico-404.php
else
    echo -e "${YELLOW}⚠️  diagnostico-404.php não encontrado${NC}"
fi

echo ""

# Passo 8: Testar servidor embutido do PHP
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "📋 PASSO 8: Preparando Teste com Servidor PHP Embutido"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

echo ""
echo -e "${GREEN}✅ INSTALAÇÃO COMPLETA!${NC}"
echo ""
echo "Para testar o sistema:"
echo ""
echo "1️⃣  Iniciar servidor de desenvolvimento:"
echo "   php spark serve"
echo ""
echo "2️⃣  Ou com servidor PHP embutido:"
echo "   php -S localhost:8080 -t public/"
echo ""
echo "3️⃣  Acessar no navegador:"
echo "   http://localhost:8080"
echo ""
echo "4️⃣  Executar testes:"
echo "   php comprehensive-test.php"
echo ""
echo "════════════════════════════════════════════════════════════════"
echo ""
