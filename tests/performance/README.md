# Performance Benchmarks - Fase 16

Este diretório contém benchmarks de performance para validar as otimizações implementadas na Fase 16.

## 📊 Benchmarks Disponíveis

### 1. IndexesBenchmark.php
Testa a performance dos **índices compostos** adicionados às tabelas principais:
- `time_punches`: employee_id + date, punch_type + date, geofence, employee + method
- `audit_logs`: user + action + date, action + date, severity + date
- `employees`: department + active, manager hierarchy
- `justifications`, `warnings`, `biometric_templates`

**Métricas:**
- Tempo médio por query (ms)
- Queries por segundo (QPS)
- Verificação de uso de índices (EXPLAIN)

### 2. ConfigServiceBenchmark.php
Testa o **cache de configurações** do `ConfigService`:
- Cold cache (primeira leitura)
- Hot cache (leituras subsequentes)
- Comparação com queries diretas ao banco
- Batch queries (`getMany`)
- Cache hit rate

**Métricas esperadas:**
- Cold cache: <50ms
- Hot cache: <5ms
- Speedup: >10x vs queries diretas
- Hit rate: >70%

### 3. FacialRecognitionCacheBenchmark.php
Testa o **cache LRU de reconhecimento facial**:
- Cache hits/misses
- Performance de set/get
- LRU eviction quando limite é atingido
- Geração de hash SHA-256
- Métricas de tracking

**Impacto esperado:**
- Economia de ~2s por reconhecimento em cache
- Speedup: >1000x vs DeepFace API (2000ms → 2ms)
- Hit rate: >70% para reconhecimentos repetidos

### 4. EagerLoadingBenchmark.php
Testa os **métodos de eager loading** do `EmployeeModel`:
- Comparação N+1 queries vs eager loading
- `getWithRelations()`: carrega manager, punches, justifications, warnings
- `getWithPunchStats()`: estatísticas agregadas
- `getActiveWithDepartment()`: filtro otimizado
- Batch queries vs múltiplas queries pequenas

**Métricas esperadas:**
- Redução de >90% nas queries (ex: 41 queries → 1 query)
- Speedup: 2-5x mais rápido
- Tempo total: <100ms para 20 employees

## 🚀 Como Executar

### Executar todos os benchmarks:
```bash
./scripts/run_optimizations.sh
```

### Executar benchmark individual:
```bash
# Índices
vendor/bin/phpunit --filter IndexesBenchmark tests/performance/

# ConfigService
vendor/bin/phpunit --filter ConfigServiceBenchmark tests/performance/

# Facial Recognition Cache
vendor/bin/phpunit --filter FacialRecognitionCacheBenchmark tests/performance/

# Eager Loading
vendor/bin/phpunit --filter EagerLoadingBenchmark tests/performance/
```

### Executar com output detalhado:
```bash
vendor/bin/phpunit --filter IndexesBenchmark tests/performance/ --testdox
```

## 📋 Pré-requisitos

### 1. Banco de dados configurado
```bash
# Criar banco
mysql -u root -p
CREATE DATABASE ponto_eletronico CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
exit;

# Executar migrations
php spark migrate
```

### 2. Migrations de otimização aplicadas
```bash
# Aplicar índices e views
php spark migrate

# Verificar status
php spark migrate:status
```

As migrations devem estar com status **Migrated**:
- `2024_01_22_000001_add_performance_indexes`
- `2024_01_22_000002_create_report_views`

### 3. Dados de teste (opcional)
Para resultados mais realistas, popule o banco com dados de teste:
```bash
php spark db:seed TestDataSeeder
```

## 📊 Interpretando Resultados

### Bom Desempenho
- ✅ Queries com índices: <50ms
- ✅ Cache hits: <5ms
- ✅ Hit rate: >70%
- ✅ Eager loading: <100ms para 20 registros
- ✅ EXPLAIN mostra uso de índices (key = idx_*)

### Problemas Potenciais
- ❌ Queries lentas (>100ms): índices não estão sendo usados
- ❌ Cache miss rate alto (>50%): revisar TTL ou padrões de acesso
- ❌ EXPLAIN mostra "Using filesort" ou "Using temporary": índice não otimizado
- ❌ N+1 queries ainda ocorrendo: usar métodos de eager loading

## 🔧 Troubleshooting

### Erro: "Class not found"
```bash
composer dump-autoload
```

### Erro: "Database connection failed"
Verifique `.env`:
```ini
database.default.hostname = localhost
database.default.database = ponto_eletronico
database.default.username = root
database.default.password =
```

### Benchmarks muito lentos
1. Verificar se índices foram criados:
```sql
SHOW INDEX FROM time_punches;
```

2. Verificar cache está funcionando:
```php
$cache = \Config\Services::cache();
$cache->save('test', 'value', 60);
var_dump($cache->get('test')); // Should return 'value'
```

3. Limpar cache antes de testar:
```bash
rm -rf writable/cache/*
```

### EXPLAIN não mostra uso de índice
- Pode ser falta de dados suficientes (MySQL escolhe table scan para tabelas pequenas)
- Adicione mais dados de teste
- Force uso de índice: `FORCE INDEX (idx_name)`

## 📈 Resultados Esperados

### Resumo de Performance

| Métrica | Antes | Depois | Melhoria |
|---------|-------|--------|----------|
| Query employee + date | 80-150ms | <50ms | 2-3x |
| Config read (cold) | 40-60ms | 40-60ms | - |
| Config read (hot) | 40-60ms | <5ms | **10x** |
| Facial recognition | 2000ms | <2ms | **1000x** |
| Load 20 employees | 200-400ms | <100ms | **2-4x** |
| Total queries (N+1) | 41 queries | 1 query | **40 queries saved** |

### Impacto em Produção

Para **100 usuários** fazendo **1000 batidas/dia**:

**Sem otimizações:**
- 41 queries × 100 users = 4,100 queries/request
- 200ms × 1000 requests = 200 segundos (3.3 min) de DB time/dia

**Com otimizações:**
- 1 query × 100 users = 100 queries/request
- 50ms × 1000 requests = 50 segundos de DB time/dia

**Economia:** 150 segundos/dia = **60% redução** de carga no banco

## 🎯 Próximos Passos

Após validar os benchmarks:

1. **Aplicar em produção:**
   ```bash
   php spark migrate --env=production
   ```

2. **Configurar MySQL** (ver `scripts/database/mysql_optimization.sql`):
   - Buffer pool size
   - Query cache
   - Slow query log

3. **Monitorar performance:**
   - Ativar slow query log (>1s)
   - Monitorar cache hit rate
   - Revisar métricas semanalmente

4. **Ajustar conforme necessário:**
   - TTL do cache (atualmente 1h para config, 5min para facial)
   - Limite LRU (atualmente 1000 entradas)
   - Índices adicionais se necessário

## 📝 Notas

- Benchmarks foram otimizados para rodar em ambiente de desenvolvimento
- Resultados podem variar em produção dependendo de hardware, carga, e tamanho dos dados
- Execute benchmarks regularmente para detectar regressões de performance
- Compare resultados antes e depois de mudanças no código

---

**Desenvolvido na Fase 16: Otimizações de Performance**
