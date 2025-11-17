#!/bin/bash
# Fix SSL Cookie Configuration
# Resolve session.cookie_secure conflicts

echo "=========================================="
echo "🔒 FIX SSL COOKIE CONFIGURATION"
echo "=========================================="
echo ""

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
cd "$SCRIPT_DIR"

echo "Este script resolve o conflito de session.cookie_secure"
echo "quando SSL não está configurado ou está com problemas."
echo ""

# Detectar se HTTPS está ativo
echo "🔍 Verificando configuração SSL..."
echo ""

# Perguntar ao usuário
echo "Você tem certificado SSL instalado e funcionando?"
echo "  1) SIM - Tenho SSL (Let's Encrypt ou comercial)"
echo "  2) NÃO - Ainda não tenho SSL"
echo "  3) NÃO SEI - Preciso verificar"
echo ""
read -p "Escolha uma opção [1/2/3]: " SSL_CHOICE

case $SSL_CHOICE in
    1)
        echo ""
        echo -e "${GREEN}✅ Mantendo configuração segura com cookie_secure = true${NC}"
        echo ""

        # Configurar para usar SSL
        if [ -f ".env" ]; then
            # Garantir que cookie_secure está true
            if grep -q "session.cookieSecure" .env; then
                sed -i 's/session.cookieSecure = false/session.cookieSecure = true/' .env
                sed -i 's/session.cookieSecure = 0/session.cookieSecure = true/' .env
            else
                echo "session.cookieSecure = true" >> .env
            fi

            # Forçar HTTPS
            if grep -q "app.forceGlobalSecureRequests" .env; then
                sed -i 's/app.forceGlobalSecureRequests = false/app.forceGlobalSecureRequests = true/' .env
            else
                echo "app.forceGlobalSecureRequests = true" >> .env
            fi

            echo -e "${GREEN}✓ .env configurado para HTTPS${NC}"
        fi

        # Atualizar php-config-production.php
        if [ -f "public/php-config-production.php" ]; then
            if grep -q "session.cookie_secure.*0" public/php-config-production.php; then
                sed -i "s/ini_set('session.cookie_secure', '0')/ini_set('session.cookie_secure', '1')/" public/php-config-production.php
                echo -e "${GREEN}✓ php-config-production.php atualizado${NC}"
            fi
        fi

        echo ""
        echo "🔒 Configuração segura aplicada!"
        echo "   ✓ session.cookie_secure = true"
        echo "   ✓ forceGlobalSecureRequests = true"
        echo ""
        ;;

    2|3)
        echo ""
        echo -e "${YELLOW}⚠️  Aplicando configuração temporária SEM SSL${NC}"
        echo ""

        # Configurar para funcionar sem SSL (TEMPORÁRIO)
        if [ -f ".env" ]; then
            # Desabilitar cookie_secure
            if grep -q "session.cookieSecure" .env; then
                sed -i 's/session.cookieSecure = true/session.cookieSecure = false/' .env
                sed -i 's/session.cookieSecure = 1/session.cookieSecure = false/' .env
            else
                echo "session.cookieSecure = false" >> .env
            fi

            # Não forçar HTTPS
            if grep -q "app.forceGlobalSecureRequests" .env; then
                sed -i 's/app.forceGlobalSecureRequests = true/app.forceGlobalSecureRequests = false/' .env
            else
                echo "app.forceGlobalSecureRequests = false" >> .env
            fi

            echo -e "${GREEN}✓ .env configurado para HTTP (temporário)${NC}"
        fi

        # Criar versão modificada do php-config-production.php
        cat > public/php-config-production.php <<'PHPEOF'
<?php
/**
 * Production PHP Configuration (HTTP Mode - Temporary)
 * WARNING: This is not secure for production! Install SSL ASAP.
 */

// Session save path - use project directory
$sessionPath = __DIR__ . '/../writable/session';

if (!is_dir($sessionPath)) {
    @mkdir($sessionPath, 0777, true);
}

if (is_dir($sessionPath) && is_writable($sessionPath)) {
    ini_set('session.save_path', $sessionPath);
}

// TEMPORARY: Allow cookies without HTTPS
ini_set('session.cookie_secure', '0');  // WARNING: Not secure!
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');

// Session garbage collector
ini_set('session.gc_probability', '1');
ini_set('session.gc_divisor', '100');
ini_set('session.gc_maxlifetime', '7200');

// Error handling
ini_set('display_errors', '0');
ini_set('log_errors', '1');
$errorLogPath = __DIR__ . '/../writable/logs/php-errors.log';
ini_set('error_log', $errorLogPath);

// Performance
ini_set('max_execution_time', '300');
ini_set('memory_limit', '256M');

// Timezone
date_default_timezone_set('America/Sao_Paulo');
PHPEOF

        echo -e "${GREEN}✓ php-config-production.php atualizado (HTTP mode)${NC}"
        echo ""
        echo -e "${RED}⚠️  ATENÇÃO: Esta é uma configuração TEMPORÁRIA e INSEGURA!${NC}"
        echo ""
        echo "📋 Para obter SSL gratuito via Let's Encrypt:"
        echo "   1. Acesse cPanel"
        echo "   2. Vá em 'SSL/TLS Status'"
        echo "   3. Procure por 'AutoSSL' ou 'Let's Encrypt'"
        echo "   4. Ative para o domínio ponto.supportsondagens.com.br"
        echo "   5. Aguarde alguns minutos para ativação"
        echo "   6. Execute este script novamente e escolha opção 1"
        echo ""
        ;;

    *)
        echo -e "${RED}❌ Opção inválida!${NC}"
        exit 1
        ;;
esac

# Limpar cache
echo "🧹 Limpando cache..."
rm -rf writable/cache/*
echo -e "${GREEN}✓ Cache limpo${NC}"
echo ""

echo "=========================================="
echo -e "${GREEN}✅ CONFIGURAÇÃO CONCLUÍDA${NC}"
echo "=========================================="
echo ""
echo "📋 Teste agora:"
echo "   https://ponto.supportsondagens.com.br/auth/login"
echo ""
echo "Se ainda tiver erro, verifique:"
echo "   tail -f writable/logs/log-*.log"
echo ""
