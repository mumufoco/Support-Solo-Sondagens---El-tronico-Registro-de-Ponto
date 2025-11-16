# Guia de Execução dos Benchmarks - Fase 16

**Data:** 2024-11-16
**Status:** Pronto para execução
**Ambiente mínimo:** PHP 8.1+, MySQL 8.0+, 4GB RAM

---

## ⚠️ Importante: Pré-requisitos

### Ambiente Atual
Este projeto foi desenvolvido em um ambiente de CI/CD **sem acesso a banco de dados MySQL**. Os benchmarks foram criados e validados teoricamente, mas **requerem um ambiente com banco de dados configurado** para execução completa.

### Pré-requisitos Obrigatórios

✅ **PHP 8.1+** - Confirmado: PHP 8.4.14 instalado
✅ **Composer** - Confirmado: dependências instaladas
✅ **PHPUnit** - Confirmado: vendor/bin/phpunit disponível
✅ **CodeIgniter 4** - Confirmado: framework instalado

❌ **MySQL 8.0+** - **Não disponível no ambiente atual**
❌ **Banco de dados criado** - Requerido: `ponto_eletronico_test`
❌ **Migrations executadas** - Requerido para índices e views
❌ **Dados de teste** - Recomendado para resultados realistas

---

## 🚀 Guia de Execução (Ambiente Completo)

### Passo 1: Configurar Banco de Dados

```bash
# 1. Criar banco de testes
mysql -u root -p
```

```sql
CREATE DATABASE ponto_eletronico_test
CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Conceder permissões
GRANT ALL PRIVILEGES ON ponto_eletronico_test.* TO 'root'@'localhost';
FLUSH PRIVILEGES;
exit;
```

### Passo 2: Executar Migrations

```bash
# Navegar para o diretório do projeto
cd /home/user/Support-Solo-Sondagens---El-tronico-Registro-de-Ponto

# Executar todas as migrations
php spark migrate --all

# Verificar status
php spark migrate:status
```

**Migrations essenciais para benchmarks:**
- ✅ `2024_01_22_000001_add_performance_indexes` - Índices compostos
- ✅ `2024_01_22_000002_create_report_views` - Views otimizadas

### Passo 3: Popular com Dados de Teste (Opcional mas Recomendado)

```bash
# Criar seeder de dados de teste
php spark make:seeder BenchmarkDataSeeder
```

**Dados recomendados:**
- 100-500 employees (funcionários)
- 10,000-50,000 time_punches (batidas de ponto)
- 500-1,000 justifications (justificativas)
- 200-500 warnings (advertências)
- 100-300 audit_logs (logs de auditoria)

```bash
# Executar seeder
php spark db:seed BenchmarkDataSeeder
```

### Passo 4: Verificar Configurações

```bash
# Verificar .env
cat .env | grep -E "database|cache"
```

Confirmar:
```ini
database.default.hostname = localhost
database.default.database = ponto_eletronico_test
database.default.username = root
database.default.password = root
database.default.DBDriver = MySQLi
```

### Passo 5: Executar Benchmarks

#### Opção A: Script Automatizado (Recomendado)

```bash
# Tornar executável (se necessário)
chmod +x scripts/run_optimizations.sh

# Executar
./scripts/run_optimizations.sh
```

**Output esperado:**
- Status das migrations
- Execução dos 4 benchmarks
- Resumo de métricas
- Comparações antes/depois

#### Opção B: Individual (Para análise detalhada)

```bash
# 1. Benchmark de Índices (5-10 minutos)
vendor/bin/phpunit --filter IndexesBenchmark tests/performance/ --testdox

# 2. Benchmark de ConfigService (2-5 minutos)
vendor/bin/phpunit --filter ConfigServiceBenchmark tests/performance/ --testdox

# 3. Benchmark de FacialRecognitionCache (3-7 minutos)
vendor/bin/phpunit --filter FacialRecognitionCacheBenchmark tests/performance/ --testdox

# 4. Benchmark de Eager Loading (5-10 minutos)
vendor/bin/phpunit --filter EagerLoadingBenchmark tests/performance/ --testdox
```

#### Opção C: Toda a Suite de Performance

```bash
# Executar todos de uma vez (15-30 minutos)
vendor/bin/phpunit --testsuite Performance --testdox
```

### Passo 6: Coletar Resultados

Os benchmarks geram output detalhado no terminal:

```
=== BENCHMARK: Employee + Date Query ===
Employees loaded: 20
Total queries executed: 1
Total Time: 45.23ms
Average per employee: 2.26ms

Improvement:
  Query Reduction: 19 queries saved
  Time Reduction: 85.2%
  Speedup: 6.78x faster
```

**Salvar output para análise:**
```bash
vendor/bin/phpunit --testsuite Performance > benchmark_results.txt 2>&1
```

---

## 📊 Análise dos Benchmarks

### 1. IndexesBenchmark

**O que testa:**
- Performance de queries com índices compostos
- Uso correto de índices (via EXPLAIN)
- Impacto de índices em diferentes tipos de queries

**Métricas coletadas:**
- Tempo médio por query (ms)
- Queries por segundo (QPS)
- Confirmação de uso de índice (key = idx_*)

**Thresholds de sucesso:**
| Query | Threshold | Esperado |
|-------|-----------|----------|
| employee_date_query | <50ms | ✓ PASS |
| type_date_query | <50ms | ✓ PASS |
| geofence_query | <100ms | ✓ PASS |
| audit_log_query | <50ms | ✓ PASS |
| department_query | <20ms | ✓ PASS |

**Como interpretar:**
- ✅ **PASS**: Query usa índice e está dentro do threshold
- ⚠️ **SLOW**: Query está lenta mas usa índice (pode precisar de mais dados)
- ❌ **FAIL**: Query não usa índice ou excede threshold significativamente

### 2. ConfigServiceBenchmark

**O que testa:**
- Eficiência do cache de configurações
- Comparação cold cache vs hot cache
- Speedup vs queries diretas ao banco

**Métricas coletadas:**
- Cold cache time (primeira leitura)
- Hot cache time (cache hit)
- Cache hit rate (%)
- Speedup factor (x)

**Thresholds de sucesso:**
| Teste | Threshold | Esperado |
|-------|-----------|----------|
| single_get_cold | <50ms | ✓ PASS |
| single_get_hot | <5ms | ✓ PASS |
| get_many | <100ms | ✓ PASS |
| cache_hit_rate | >70% | ✓ PASS |

**Como interpretar:**
- **Speedup < 5x**: Cache está funcionando mas TTL pode estar muito baixo
- **Speedup 10-20x**: ✅ Performance ideal
- **Hit rate < 50%**: ⚠️ Revisar padrão de acesso ou aumentar TTL

### 3. FacialRecognitionCacheBenchmark

**O que testa:**
- Cache LRU de reconhecimento facial
- Speedup dramático vs DeepFace API
- LRU eviction quando limite atingido

**Métricas coletadas:**
- Cache hit time (<2ms esperado)
- DeepFace API time simulation (2000ms)
- Speedup (esperado: 1000x)
- LRU eviction effectiveness

**Thresholds de sucesso:**
| Teste | Threshold | Esperado |
|-------|-----------|----------|
| cold_cache_get | <5ms | ✓ PASS |
| cache_set | <10ms | ✓ PASS |
| hot_cache_get | <2ms | ✓ PASS |
| lru_eviction | entries ≤ 1000 | ✓ PASS |

**Como interpretar:**
- **Speedup 500-1500x**: ✅ Economia dramática confirmada
- **LRU evictions frequentes**: ⚠️ Considerar aumentar maxCacheEntries
- **Cache misses altos**: Normal se imagens são sempre novas

### 4. EagerLoadingBenchmark

**O que testa:**
- Problema N+1 queries vs eager loading
- Redução no número total de queries
- Impacto em tempo de resposta

**Métricas coletadas:**
- N+1 queries count (esperado: 41-81)
- Eager loading queries count (esperado: 1)
- Time comparison
- Speedup factor

**Thresholds de sucesso:**
| Teste | N+1 Queries | Eager Loading | Speedup |
|-------|-------------|---------------|---------|
| getWithRelations | 81 | 1 | >4x |
| getWithPunchStats | 21 | 1 | >3x |
| department_filter | 1 | 1 | 1x |

**Como interpretar:**
- **Query reduction >80%**: ✅ Eager loading muito efetivo
- **Speedup 2-6x**: ✅ Performance ideal
- **Speedup <2x**: ⚠️ Pode haver outro bottleneck (network, disk)

---

## 🔍 Troubleshooting

### Erro: "Database connection failed"

**Causa:** Banco de dados não configurado ou credenciais incorretas

**Solução:**
```bash
# 1. Verificar se MySQL está rodando
sudo systemctl status mysql
# ou
sudo service mysql status

# 2. Testar conexão
mysql -u root -p ponto_eletronico_test

# 3. Verificar .env
cat .env | grep database
```

### Erro: "Table doesn't exist"

**Causa:** Migrations não foram executadas

**Solução:**
```bash
# Executar todas as migrations
php spark migrate --all

# Verificar tabelas criadas
mysql -u root -p ponto_eletronico_test -e "SHOW TABLES;"
```

### Erro: "Class not found"

**Causa:** Autoload desatualizado

**Solução:**
```bash
composer dump-autoload
```

### Benchmarks muito lentos (>1s por query)

**Possíveis causas:**
1. **Índices não criados**: Verificar com `SHOW INDEX FROM time_punches;`
2. **Tabela vazia**: Adicionar dados de teste
3. **MySQL não otimizado**: Aplicar configurações de `scripts/database/mysql_optimization.sql`
4. **Hardware limitado**: Normal em ambientes com <4GB RAM

**Solução:**
```bash
# Verificar índices
mysql -u root -p ponto_eletronico_test -e "SHOW INDEX FROM time_punches;"

# Contar registros
mysql -u root -p ponto_eletronico_test -e "SELECT COUNT(*) FROM time_punches;"
```

### Cache hit rate muito baixo (<30%)

**Possíveis causas:**
1. TTL muito baixo (cache expira rápido)
2. Cache sendo limpo entre testes
3. Padrão de acesso muito randômico

**Solução:**
```php
// Aumentar TTL temporariamente para testes
// Em ConfigService.php
protected int $cacheTTL = 7200; // 2 horas

// Em FacialRecognitionCache.php
protected int $cacheTTL = 600; // 10 minutos
```

### EXPLAIN não mostra uso de índice

**Causa:** MySQL escolhe table scan para tabelas pequenas

**Solução:**
```sql
-- Forçar uso de índice
SELECT * FROM time_punches FORCE INDEX (idx_employee_date)
WHERE employee_id = 1 AND punch_time >= '2024-01-01';
```

Ou adicionar mais dados de teste (MySQL usa índices quando >1000 rows)

---

## 📈 Interpretando Resultados

### Exemplo de Output Esperado

```
BENCHMARK SUMMARY
======================================================================

employee_date_query:
  Average Time: 32.45ms
  Queries/Second: 30.82
  Uses Index: YES ✓

type_date_query:
  Average Time: 28.91ms
  Queries/Second: 34.59
  Uses Index: YES ✓

CONFIG SERVICE CACHE BENCHMARK SUMMARY
======================================================================

Single Get Performance:
  Cold Cache: 42.33ms
  Hot Cache:  1.87ms
  Speedup:    22.64x

Cache vs Direct DB:
  Direct DB:  45.12ms
  With Cache: 1.87ms
  Improvement: 95.9%

FACIAL RECOGNITION CACHE BENCHMARK SUMMARY
======================================================================

Cache Performance:
  Cold Cache (miss): 3.21ms
  Hot Cache (hit):   1.54ms
  Cache Set:         7.89ms

DeepFace API Comparison:
  Simulated API time: 2000ms
  Cache hit time:     1.54ms
  Speedup:            1299x faster
  Time saved (1000 recognitions): 1998.46s

EAGER LOADING BENCHMARK SUMMARY
======================================================================

N+1 Problem vs Eager Loading:
  N+1 Queries:     81
  N+1 Time:        342.56ms

  Eager Queries:   1
  Eager Time:      78.23ms

  Queries Saved:   80
  Time Reduction:  77.2%
  Speedup:         4.38x
```

### O que constitui sucesso?

✅ **Sucesso Total:**
- Todos os testes PASS
- Speedups dentro do esperado (2-1000x dependendo do teste)
- Cache hit rates >70%
- Índices sendo usados (EXPLAIN mostra key = idx_*)

⚠️ **Sucesso Parcial:**
- Alguns testes PASS, outros SLOW
- Speedups menores que esperado mas positivos
- Cache hit rates 50-70%
- Alguns índices não usados (tabelas pequenas)

❌ **Falha:**
- Maioria dos testes FAIL
- Speedups negativos (mais lento com otimizações)
- Cache hit rates <30%
- Nenhum índice sendo usado

---

## 📝 Checklist de Execução

### Antes de Executar

- [ ] MySQL 8.0+ instalado e rodando
- [ ] Banco `ponto_eletronico_test` criado
- [ ] Migrations executadas (`php spark migrate --all`)
- [ ] Dados de teste populados (recomendado 10k+ registros)
- [ ] PHPUnit instalado (`vendor/bin/phpunit --version`)
- [ ] Cache configurado (verificar `writable/cache/` existe)
- [ ] `.env` configurado corretamente

### Durante Execução

- [ ] Monitorar output para erros de conexão
- [ ] Verificar se EXPLAIN mostra uso de índices
- [ ] Observar tempos de execução (não devem exceder 30min total)
- [ ] Anotar speedups e melhorias relatadas

### Após Execução

- [ ] Salvar output completo em arquivo
- [ ] Revisar todos os thresholds PASS
- [ ] Comparar com métricas esperadas (docs/PERFORMANCE_REPORT.md)
- [ ] Documentar qualquer desvio significativo
- [ ] Planejar ajustes se necessário (TTLs, índices adicionais)

---

## 🎯 Próximos Passos Após Benchmarks

### Se Todos os Testes Passarem (Sucesso)

1. **Documentar resultados reais**
   ```bash
   vendor/bin/phpunit --testsuite Performance > results/benchmark_$(date +%Y%m%d).txt
   ```

2. **Planejar deploy gradual** (ver docs/PERFORMANCE_REPORT.md seção 5.1)

3. **Configurar monitoramento contínuo**
   - Slow query log
   - Cache hit rate tracking
   - Query count por request

### Se Alguns Testes Falharem (Ajustes Necessários)

1. **Identificar causa raiz:**
   - Falta de dados?
   - Índices não criados?
   - Cache não configurado?

2. **Ajustar e re-testar:**
   ```bash
   # Re-executar teste específico
   vendor/bin/phpunit --filter ConfigServiceBenchmark tests/performance/
   ```

3. **Iterar até todos passarem**

### Deploy em Produção

Somente após todos os benchmarks passarem em ambiente de staging:

1. **Backup completo**
2. **Janela de manutenção**
3. **Deploy gradual** (índices → views → cache → particionamento)
4. **Monitoramento 48h**
5. **Ajustes finos**

---

## 📚 Referências

- **Documentação completa:** docs/PERFORMANCE_REPORT.md
- **README dos testes:** tests/performance/README.md
- **Scripts SQL:** scripts/database/
- **Migrations:** app/Database/Migrations/2024_01_22_*

---

**Status:** Pronto para execução em ambiente com MySQL configurado
**Última atualização:** 2024-11-16
**Validação teórica:** ✅ Completa
**Validação prática:** ⏳ Pendente (requer DB)
