#!/bin/bash

echo "======================================================================="
echo "  DEPLOY EM PRODUÇÃO - CORREÇÕES DE LOGIN/SESSÃO"
echo "======================================================================="
echo ""

# Ir para o diretório do projeto
cd /home/supportson/public_html/ponto || exit 1

echo "📂 Diretório atual: $(pwd)"
echo ""

# Verificar branch atual
echo "🔍 Branch atual:"
git branch --show-current
echo ""

# Fazer backup do .env
echo "💾 Fazendo backup do .env..."
cp .env .env.backup-$(date +%Y%m%d-%H%M%S)
echo "✓ Backup criado"
echo ""

# Fetch das mudanças
echo "📥 Buscando mudanças do repositório..."
git fetch origin claude/fix-auth-log-errors-01YHVDcAhJNqGjYTrwKTaEe2
echo ""

# Fazer pull das correções
echo "⬇️  Fazendo pull das correções..."
git pull origin claude/fix-auth-log-errors-01YHVDcAhJNqGjYTrwKTaEe2
echo ""

# Restaurar .env de produção se foi sobrescrito
if [ -f .env.production.backup ]; then
    echo "🔄 Restaurando .env de produção..."
    mv .env.production.backup .env
fi
echo ""

# Limpar rate limits
echo "🧹 Limpando rate limits..."
if [ -f public/clear-ratelimit.php ]; then
    php public/clear-ratelimit.php
else
    echo "⚠️  clear-ratelimit.php não encontrado"
fi
echo ""

# Limpar sessões antigas
echo "🧹 Limpando sessões antigas..."
rm -f writable/session/ci_session* 2>/dev/null
echo "✓ Sessões limpas"
echo ""

# Verificar e ajustar permissões
echo "🔐 Verificando permissões..."
chmod 755 writable/session
chmod 755 writable/logs
chmod 755 writable/cache
echo "✓ Permissões ajustadas"
echo ""

# Verificar arquivos críticos
echo "📋 Verificando arquivos críticos..."
echo ""

echo "1. public/index.php - Configuração de sessão:"
grep -A 3 "session_name" public/index.php | head -4
echo ""

echo "2. app/Config/App.php - Sem config duplicada:"
grep -i "sessionDriver" app/Config/App.php && echo "⚠️  AVISO: Config duplicada ainda presente!" || echo "✓ Config duplicada removida"
echo ""

echo "3. Diretório de sessão:"
ls -la writable/session/ | head -5
echo ""

echo "======================================================================="
echo "  ✅ DEPLOY COMPLETO!"
echo "======================================================================="
echo ""
echo "📋 PRÓXIMOS PASSOS:"
echo ""
echo "1. Acesse: https://ponto.supportsondagens.com.br/auth/login"
echo "2. Tente fazer login com suas credenciais"
echo "3. Observe se ainda ocorre o loop de redirecionamento"
echo "4. Monitore os logs:"
echo "   tail -f writable/logs/log-$(date +%Y-%m-%d).log"
echo ""
echo "5. Se houver erros, copie e cole TUDO que aparecer aqui"
echo ""
