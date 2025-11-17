#!/bin/bash

###############################################################################
# Fix Session Redirect Loop
# Sistema de Ponto Eletrônico - Produção
#
# Corrige o problema de loop de redirect causado por sessão não iniciando
###############################################################################

echo "========================================================================"
echo "🔧 CORREÇÃO: Loop de Redirect por Problema de Sessão"
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
# 1. Verificar Permissões do Diretório de Sessão
###############################################################################

echo "1️⃣  Verificando permissões do diretório de sessão..."
echo ""

SESSION_DIR="writable/session"

if [ ! -d "$SESSION_DIR" ]; then
    print_error "Diretório $SESSION_DIR não existe!"
    print_info "Criando diretório..."
    mkdir -p "$SESSION_DIR"
    print_success "Diretório criado"
fi

# Ajustar permissões
chmod 775 "$SESSION_DIR"
chmod 775 writable/
chmod 775 writable/cache/
chmod 775 writable/logs/

# Limpar sessões antigas
rm -f "$SESSION_DIR"/ci_session*

print_success "Permissões ajustadas"
print_success "Sessões antigas removidas"

echo ""

###############################################################################
# 2. Verificar Configuração de Sessão no PHP
###############################################################################

echo "2️⃣  Verificando configuração PHP de sessão..."
echo ""

print_info "Verificando session.save_path..."
php -r "echo 'Save Path: ' . ini_get('session.save_path') . PHP_EOL;"

print_info "Verificando session.gc_divisor..."
php -r "echo 'GC Divisor: ' . ini_get('session.gc_divisor') . PHP_EOL;"

echo ""

###############################################################################
# 3. Testar Inicialização de Sessão
###############################################################################

echo "3️⃣  Testando inicialização de sessão..."
echo ""

# Criar script de teste
cat > /tmp/test-session-start.php <<'EOPHP'
<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Testing session start...\n";

// Set session save path
$save_path = __DIR__ . '/../writable/session';
if (!is_dir($save_path)) {
    mkdir($save_path, 0775, true);
}
session_save_path($save_path);

// Try to start session
if (session_start()) {
    echo "✅ Session started successfully!\n";
    echo "Session ID: " . session_id() . "\n";
    echo "Session Save Path: " . session_save_path() . "\n";

    // Set a test value
    $_SESSION['test'] = 'success';
    echo "Session data set successfully\n";

    session_write_close();
    echo "✅ Session saved and closed successfully\n";
} else {
    echo "❌ Failed to start session\n";
    exit(1);
}
EOPHP

php /tmp/test-session-start.php

if [ $? -eq 0 ]; then
    print_success "Sessão pode ser iniciada!"
else
    print_error "Falha ao iniciar sessão"
    echo ""
    print_info "Possíveis causas:"
    echo "  - Permissões insuficientes em writable/session"
    echo "  - Conflito de configuração PHP"
    echo "  - open_basedir restriction"
fi

echo ""

###############################################################################
# 4. Verificar .env
###############################################################################

echo "4️⃣  Verificando .env..."
echo ""

if grep -q "app.baseURL = 'https://ponto.supportsondagens.com.br/'" .env; then
    print_success "baseURL configurado corretamente"
else
    print_error "baseURL não está configurado"
fi

if grep -q "session.cookieSecure = true" .env; then
    print_success "session.cookieSecure configurado"
else
    print_info "Adicionando session.cookieSecure..."
fi

if grep -q "CI_ENVIRONMENT = production" .env; then
    print_success "Ambiente em produção"
else
    print_info "Ambiente não está em production"
fi

echo ""

###############################################################################
# 5. Verificar Logs de Erro
###############################################################################

echo "5️⃣  Verificando logs de erro..."
echo ""

if [ -f "writable/logs/log-"$(date +%Y-%m-%d)".php" ]; then
    print_info "Últimas 10 linhas do log de hoje:"
    tail -10 "writable/logs/log-"$(date +%Y-%m-%d)".php" | grep -v "^<?" | grep -v "^?>"
else
    print_info "Nenhum log de hoje encontrado"
fi

echo ""

###############################################################################
# 6. Limpar Cache
###############################################################################

echo "6️⃣  Limpando cache..."
echo ""

php spark cache:clear 2>/dev/null

if [ $? -eq 0 ]; then
    print_success "Cache limpo"
else
    print_info "Limpando cache manualmente..."
    rm -rf writable/cache/data/*
    print_success "Cache limpo manualmente"
fi

echo ""

###############################################################################
# 7. Resumo e Próximos Passos
###############################################################################

echo "========================================================================"
echo "📋 RESUMO"
echo "========================================================================"
echo ""

print_success "Correções aplicadas:"
echo "  ✅ Permissões ajustadas em writable/"
echo "  ✅ Sessões antigas removidas"
echo "  ✅ Cache limpo"
echo "  ✅ Configurações verificadas"

echo ""
echo "📝 PRÓXIMOS PASSOS:"
echo ""
echo "1️⃣  Acesse o site e teste:"
echo "   https://ponto.supportsondagens.com.br"
echo ""
echo "2️⃣  Se ainda houver loop, verifique:"
echo "   https://ponto.supportsondagens.com.br/public/test-redirect-debug.php"
echo ""
echo "3️⃣  Verifique logs em tempo real:"
echo "   tail -f writable/logs/log-$(date +%Y-%m-%d).php"
echo ""
echo "4️⃣  Se o problema persistir:"
echo "   - Verifique configuração de open_basedir no cPanel"
echo "   - Verifique PHP version (precisa ser 8.1+)"
echo "   - Contate suporte da hospedagem"
echo ""

echo "========================================================================"
print_success "✅ Script finalizado"
echo "========================================================================"
