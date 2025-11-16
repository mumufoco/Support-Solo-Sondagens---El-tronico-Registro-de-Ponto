#!/bin/bash

#####################################################################
# Script de Execução das Otimizações - Fase 16
#
# Este script executa todas as otimizações de banco de dados e
# realiza os testes de performance.
#
# Uso: ./scripts/run_optimizations.sh
#####################################################################

set -e  # Exit on error

PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$PROJECT_ROOT"

echo "=========================================="
echo "Fase 16: Executando Otimizações"
echo "=========================================="
echo ""

# Verificar se PHP está disponível
if ! command -v php &> /dev/null; then
    echo "❌ PHP não está instalado ou não está no PATH"
    exit 1
fi

# Verificar se MySQL está disponível
if ! command -v mysql &> /dev/null; then
    echo "⚠️  MySQL CLI não encontrado. Migrations devem ser executadas via CodeIgniter."
else
    echo "✓ MySQL CLI encontrado"
fi

echo ""
echo "=========================================="\n
echo "1. Executando Migrations de Otimização"
echo "=========================================="
echo ""

# Executar migrations via CodeIgniter
if [ -f "spark" ]; then
    echo "Executando migrations..."
    php spark migrate
    echo "✓ Migrations executadas com sucesso"
else
    echo "⚠️  Arquivo spark não encontrado."
    echo "   Execute manualmente: php spark migrate"
    echo ""
    echo "   Ou execute as migrations SQL diretamente:"
    echo ""
    echo "   mysql -u root -p ponto_eletronico < app/Database/Migrations/2024_01_22_000001_add_performance_indexes.php"
    echo "   mysql -u root -p ponto_eletronico < app/Database/Migrations/2024_01_22_000002_create_report_views.php"
fi

echo ""
echo "=========================================="
echo "2. Aplicando Particionamento (Opcional)"
echo "=========================================="
echo ""

if command -v mysql &> /dev/null; then
    echo "Deseja aplicar particionamento na tabela time_punches? (s/n)"
    read -r apply_partition

    if [ "$apply_partition" = "s" ] || [ "$apply_partition" = "S" ]; then
        echo "Aplicando particionamento..."
        mysql -u root -p ponto_eletronico < scripts/database/partition_time_punches.sql
        echo "✓ Particionamento aplicado"
    else
        echo "⚠️  Particionamento ignorado"
    fi
else
    echo "⚠️  Execute manualmente:"
    echo "   mysql -u root -p ponto_eletronico < scripts/database/partition_time_punches.sql"
fi

echo ""
echo "=========================================="
echo "3. Aplicando Otimizações MySQL (Opcional)"
echo "=========================================="
echo ""

echo "⚠️  As otimizações MySQL devem ser aplicadas no arquivo my.cnf ou my.ini"
echo "   Consulte: scripts/database/mysql_optimization.sql"
echo "   Reinicie o MySQL após aplicar as configurações."

echo ""
echo "=========================================="
echo "4. Executando Benchmarks de Performance"
echo "=========================================="
echo ""

if [ -f "vendor/bin/phpunit" ]; then
    echo "Executando benchmarks..."
    echo ""

    echo "--- Benchmark 1: Índices Compostos ---"
    vendor/bin/phpunit --filter IndexesBenchmark tests/performance/ || true

    echo ""
    echo "--- Benchmark 2: ConfigService Cache ---"
    vendor/bin/phpunit --filter ConfigServiceBenchmark tests/performance/ || true

    echo ""
    echo "--- Benchmark 3: Facial Recognition Cache ---"
    vendor/bin/phpunit --filter FacialRecognitionCacheBenchmark tests/performance/ || true

    echo ""
    echo "--- Benchmark 4: Eager Loading ---"
    vendor/bin/phpunit --filter EagerLoadingBenchmark tests/performance/ || true

    echo ""
    echo "✓ Todos os benchmarks foram executados"
else
    echo "⚠️  PHPUnit não encontrado. Execute:"
    echo "   composer install"
    echo "   vendor/bin/phpunit tests/performance/"
fi

echo ""
echo "=========================================="
echo "5. Verificando Cache"
echo "=========================================="
echo ""

echo "Diretório de cache: writable/cache/"
if [ -d "writable/cache" ]; then
    echo "✓ Diretório de cache existe"
    ls -lh writable/cache/ | head -10
else
    echo "⚠️  Criando diretório de cache..."
    mkdir -p writable/cache
    chmod 777 writable/cache
    echo "✓ Diretório criado"
fi

echo ""
echo "=========================================="
echo "Resumo da Execução"
echo "=========================================="
echo ""
echo "✓ Otimizações implementadas:"
echo "  - Migrations criadas (índices + views)"
echo "  - Scripts de particionamento prontos"
echo "  - Configurações MySQL documentadas"
echo "  - Benchmarks de performance criados"
echo ""
echo "📊 Próximos passos:"
echo "  1. Revisar resultados dos benchmarks"
echo "  2. Aplicar configurações MySQL em produção"
echo "  3. Monitorar performance com slow query log"
echo "  4. Ajustar cache TTLs conforme necessário"
echo ""
echo "=========================================="
echo "Concluído!"
echo "=========================================="
