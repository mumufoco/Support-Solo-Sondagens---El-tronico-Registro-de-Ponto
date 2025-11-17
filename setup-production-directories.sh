#!/bin/bash

###############################################################################
# Setup Production Directories
# Sistema de Ponto Eletrônico
#
# Execute este script NO SERVIDOR DE PRODUÇÃO via SSH ou terminal do cPanel
###############################################################################

echo "========================================================================"
echo "📁 CRIANDO ESTRUTURA DE DIRETÓRIOS - PRODUÇÃO"
echo "========================================================================"
echo ""

GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m'

print_success() { echo -e "${GREEN}✅ $1${NC}"; }
print_error() { echo -e "${RED}❌ $1${NC}"; }
print_info() { echo -e "${YELLOW}ℹ️  $1${NC}"; }

###############################################################################
# Verificar se estamos no diretório correto
###############################################################################

if [ ! -f "spark" ] || [ ! -d "app" ]; then
    print_error "Execute este script no diretório raiz do projeto!"
    echo ""
    echo "Exemplo:"
    echo "  cd ~/public_html/ponto.supportsondagens.com.br"
    echo "  bash setup-production-directories.sh"
    exit 1
fi

print_success "Diretório correto detectado"
echo ""

###############################################################################
# Criar diretórios necessários
###############################################################################

echo "📂 Criando diretórios..."
echo ""

DIRECTORIES=(
    "writable"
    "writable/session"
    "writable/cache"
    "writable/cache/data"
    "writable/logs"
    "writable/debugbar"
    "writable/uploads"
    "writable/exports"
    "writable/biometric"
    "writable/biometric/faces"
    "writable/biometric/fingerprints"
)

for dir in "${DIRECTORIES[@]}"; do
    if [ ! -d "$dir" ]; then
        mkdir -p "$dir"
        print_success "Criado: $dir"
    else
        print_info "Já existe: $dir"
    fi
done

echo ""

###############################################################################
# Ajustar permissões
###############################################################################

echo "🔐 Ajustando permissões..."
echo ""

# Permissões de diretórios (775)
find writable -type d -exec chmod 775 {} \;
print_success "Diretórios: 775"

# Permissões de arquivos (664)
find writable -type f -exec chmod 664 {} \;
print_success "Arquivos: 664"

# .env deve ser 600 (somente leitura para owner)
if [ -f ".env" ]; then
    chmod 600 .env
    print_success ".env: 600 (seguro)"
fi

echo ""

###############################################################################
# Criar arquivos de segurança
###############################################################################

echo "🔒 Criando arquivos de segurança..."
echo ""

# .htaccess em writable/
if [ ! -f "writable/.htaccess" ]; then
    cat > writable/.htaccess <<'EOF'
# Deny all direct access to writable directory
<IfModule authz_core_module>
    Require all denied
</IfModule>
<IfModule !authz_core_module>
    Deny from all
</IfModule>
EOF
    print_success "Criado: writable/.htaccess"
else
    print_info "Já existe: writable/.htaccess"
fi

# index.html em cada diretório (previne listagem)
for dir in "${DIRECTORIES[@]}"; do
    if [ ! -f "$dir/index.html" ]; then
        cat > "$dir/index.html" <<'EOF'
<!DOCTYPE html>
<html>
<head>
    <title>403 Forbidden</title>
</head>
<body>
    <h1>Directory access is forbidden.</h1>
</body>
</html>
EOF
    fi
done

print_success "Arquivos index.html criados"

# .htaccess em writable/uploads (extra segurança)
if [ ! -f "writable/uploads/.htaccess" ]; then
    cat > writable/uploads/.htaccess <<'EOF'
# Prevent PHP execution in uploads directory
<FilesMatch "\.(php|php3|php4|php5|phtml|pl|py|jsp|asp|sh|cgi)$">
    <IfModule authz_core_module>
        Require all denied
    </IfModule>
    <IfModule !authz_core_module>
        Deny from all
    </IfModule>
</FilesMatch>

# Allow only specific file types
<FilesMatch "\.(jpg|jpeg|png|gif|pdf|doc|docx|xls|xlsx|zip)$">
    <IfModule authz_core_module>
        Require all granted
    </IfModule>
    <IfModule !authz_core_module>
        Allow from all
    </IfModule>
</FilesMatch>
EOF
    print_success "Criado: writable/uploads/.htaccess"
fi

echo ""

###############################################################################
# Limpar sessões antigas (se existirem)
###############################################################################

echo "🧹 Limpando cache e sessões antigas..."
echo ""

# Remover sessões antigas
rm -f writable/session/ci_session* 2>/dev/null
print_success "Sessões antigas removidas"

# Limpar cache
rm -rf writable/cache/data/* 2>/dev/null
print_success "Cache limpo"

echo ""

###############################################################################
# Verificar permissões finais
###############################################################################

echo "✅ Verificando permissões finais..."
echo ""

check_writable() {
    if [ -w "$1" ]; then
        print_success "$1 é gravável"
        return 0
    else
        print_error "$1 NÃO é gravável!"
        return 1
    fi
}

CRITICAL_DIRS=(
    "writable"
    "writable/session"
    "writable/cache"
    "writable/logs"
)

all_ok=true
for dir in "${CRITICAL_DIRS[@]}"; do
    if ! check_writable "$dir"; then
        all_ok=false
    fi
done

echo ""

###############################################################################
# Testar criação de arquivo de sessão
###############################################################################

echo "🧪 Testando criação de arquivo de sessão..."
echo ""

test_file="writable/session/test_$(date +%s).tmp"
if touch "$test_file" 2>/dev/null; then
    print_success "Arquivo de teste criado com sucesso!"
    rm -f "$test_file"
    print_success "✅ SESSÃO PODE SER CRIADA!"
else
    print_error "FALHA ao criar arquivo de sessão!"
    print_error "Verifique permissões do diretório writable/session"
    all_ok=false
fi

echo ""

###############################################################################
# Verificar owner dos arquivos
###############################################################################

echo "👤 Verificando proprietário dos arquivos..."
echo ""

current_user=$(whoami)
file_owner=$(stat -c '%U' writable 2>/dev/null || stat -f '%Su' writable 2>/dev/null)

echo "Usuário atual: $current_user"
echo "Proprietário de writable/: $file_owner"

if [ "$current_user" = "$file_owner" ]; then
    print_success "Proprietário correto!"
else
    print_error "Proprietário diferente! Isso pode causar problemas."
    echo ""
    echo "Se você tem acesso root, execute:"
    echo "  chown -R $current_user:$current_user writable/"
fi

echo ""

###############################################################################
# Resumo Final
###############################################################################

echo "========================================================================"
echo "📊 RESUMO"
echo "========================================================================"
echo ""

if [ "$all_ok" = true ]; then
    print_success "✅ TODOS OS DIRETÓRIOS CRIADOS E CONFIGURADOS!"
    echo ""
    echo "✅ Estrutura pronta:"
    echo "   - Diretórios criados: ${#DIRECTORIES[@]}"
    echo "   - Permissões ajustadas: 775 (dirs) / 664 (files)"
    echo "   - Arquivos de segurança criados"
    echo "   - Sessões podem ser gravadas"
    echo ""
    echo "🎯 PRÓXIMOS PASSOS:"
    echo ""
    echo "1️⃣  Acesse o site:"
    echo "   https://ponto.supportsondagens.com.br"
    echo ""
    echo "2️⃣  O erro de sessão deve estar resolvido!"
    echo ""
    echo "3️⃣  Se ainda houver problema, verifique logs:"
    echo "   tail -f writable/logs/log-$(date +%Y-%m-%d).php"
    echo ""
else
    print_error "⚠️  ALGUNS PROBLEMAS FORAM ENCONTRADOS"
    echo ""
    echo "Verifique:"
    echo "  - Permissões dos diretórios"
    echo "  - Proprietário dos arquivos (deve ser seu usuário)"
    echo "  - Espaço em disco disponível"
    echo ""
    echo "Se necessário, execute com sudo:"
    echo "  sudo bash setup-production-directories.sh"
fi

echo ""
echo "========================================================================"
print_success "Script finalizado!"
echo "========================================================================"
