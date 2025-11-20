#!/bin/bash
# Correção FORÇADA da linha 41 do autoload_real.php
# Execute via SSH: bash corrigir-linha-41.sh

echo "🔧 CORREÇÃO FORÇADA - Linha 41 do autoload_real.php"
echo "================================================================================"
echo ""

FILE="/home/supportson/public_html/ponto/vendor/composer/autoload_real.php"
BACKUP="${FILE}.backup-forcada-$(date +%Y%m%d%H%M%S)"

# 1. Verificar se arquivo existe
if [ ! -f "$FILE" ]; then
    echo "❌ Arquivo não encontrado: $FILE"
    exit 1
fi

echo "✅ Arquivo encontrado: $FILE"
echo "Tamanho: $(wc -c < "$FILE") bytes"
echo ""

# 2. Criar backup
echo "💾 Criando backup..."
cp "$FILE" "$BACKUP"
if [ $? -eq 0 ]; then
    echo "✅ Backup criado: $BACKUP"
else
    echo "❌ Erro ao criar backup"
    exit 1
fi
echo ""

# 3. Mostrar linha 41 antes
echo "Linha 41 ANTES da correção:"
echo "--------------------------------------------------------------------------------"
sed -n '41p' "$FILE"
echo "--------------------------------------------------------------------------------"
echo ""

# 4. Comentar linha 41
echo "🔧 Aplicando correção..."
sed -i.tmp '41s/^/\/\/ /' "$FILE"
sed -i.tmp '41s/$/ \/\/ DESABILITADO - PHPUnit não instalado/' "$FILE"
rm -f "${FILE}.tmp"

echo "✅ Linha 41 comentada"
echo ""

# 5. Mostrar linha 41 depois
echo "Linha 41 DEPOIS da correção:"
echo "--------------------------------------------------------------------------------"
sed -n '41p' "$FILE"
echo "--------------------------------------------------------------------------------"
echo ""

# 6. Verificar se funcionou
echo "🧪 Verificando sintaxe do PHP..."
php -l "$FILE" > /dev/null 2>&1
if [ $? -eq 0 ]; then
    echo "✅ Sintaxe PHP OK"
else
    echo "❌ Erro de sintaxe! Restaurando backup..."
    cp "$BACKUP" "$FILE"
    echo "Backup restaurado"
    exit 1
fi
echo ""

# 7. Limpar caches
echo "🗑️ Limpando caches..."

# Tentar limpar opcache via CLI
php -r "if (function_exists('opcache_reset')) { opcache_reset(); echo '✅ OPcache limpo\n'; } else { echo '⚠️ OPcache não disponível\n'; }"

# Tentar reiniciar PHP-FPM se disponível
if command -v systemctl &> /dev/null; then
    echo "Tentando reiniciar PHP-FPM..."
    sudo systemctl restart php-fpm 2>/dev/null && echo "✅ PHP-FPM reiniciado" || echo "⚠️ Não foi possível reiniciar PHP-FPM"
fi

echo ""
echo "================================================================================"
echo "✅ CORREÇÃO CONCLUÍDA COM SUCESSO!"
echo "================================================================================"
echo ""
echo "📋 Próximos passos:"
echo "1. Teste o health check: curl https://ponto.supportsondagens.com.br/health"
echo "2. Teste o login: https://ponto.supportsondagens.com.br/auth/login"
echo "3. Execute o instalador: https://ponto.supportsondagens.com.br/install.php"
echo ""
echo "🔄 Para reverter:"
echo "cp $BACKUP $FILE"
echo ""
