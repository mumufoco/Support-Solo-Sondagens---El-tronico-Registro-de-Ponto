# 📊 Guia de Monitoramento do Sistema

## Endpoint de Health Check

O sistema possui endpoints de health check para monitoramento automatizado e verificação de saúde.

### Endpoint Principal: `/health`

**URL**: `http://localhost:8080/health` (desenvolvimento)
**Método**: GET
**Autenticação**: Não requerida (público)
**Content-Type**: application/json

**Resposta (200 OK - Sistema Saudável)**:
```json
{
  "status": "healthy",
  "timestamp": "2025-11-18 00:01:49",
  "environment": "development",
  "version": "4.6.3",
  "checks": {
    "database": {
      "status": "ok",
      "driver": "MySQLi",
      "database": "ponto_db"
    },
    "writable": {
      "status": "ok",
      "directories": {
        "writable/cache": "ok",
        "writable/logs": "ok",
        "writable/session": "ok",
        "writable/uploads": "ok",
        "storage": "ok"
      }
    },
    "cache": {
      "status": "ok",
      "handler": "CodeIgniter\\Cache\\Handlers\\FileHandler"
    },
    "session": {
      "status": "ok",
      "driver": "CodeIgniter\\Session\\Handlers\\FileHandler"
    },
    "environment": {
      "status": "ok",
      "php_version": "8.4.14",
      "issues": []
    }
  }
}
```

**Resposta (503 Service Unavailable - Sistema com Problemas)**:
```json
{
  "status": "unhealthy",
  "timestamp": "2025-11-18 00:01:49",
  "environment": "production",
  "version": "4.6.3",
  "checks": {
    "database": {
      "status": "error",
      "message": "Connection refused"
    },
    "writable": {
      "status": "error",
      "directories": {
        "writable/cache": "not writable",
        "writable/logs": "ok",
        "storage": "ok"
      }
    }
  }
}
```

### Endpoint Detalhado: `/health/detailed`

**URL**: `http://localhost:8080/health/detailed`
**Método**: GET
**Ambiente**: Apenas desenvolvimento
**Autenticação**: Não requerida

**Funcionalidade**: Retorna informações detalhadas do sistema incluindo:
- Sistema operacional completo
- Versões de PHP e CodeIgniter
- Informações detalhadas do banco de dados (hostname, port, version)
- Lista completa de extensões PHP carregadas
- Configurações do servidor (memory_limit, max_execution_time, timezone)

**Segurança**: Este endpoint retorna HTTP 403 em ambiente de produção por razões de segurança.

---

## Integração com Ferramentas de Monitoramento

### 1. Uptime Kuma

```yaml
monitor:
  type: http
  url: https://ponto.suaempresa.com.br/health
  method: GET
  interval: 60  # segundos
  timeout: 30
  retries: 3
  expected_status: 200
  expected_body: '"status":"healthy"'
```

### 2. Prometheus + Grafana

```yaml
# prometheus.yml
scrape_configs:
  - job_name: 'ponto-eletronico'
    metrics_path: '/health'
    scrape_interval: 30s
    static_configs:
      - targets: ['ponto.suaempresa.com.br']
    metric_relabel_configs:
      - source_labels: [__name__]
        target_label: __name__
        regex: '(.*)'
        replacement: 'ponto_${1}'
```

### 3. Nagios / Icinga

```bash
# check_health.sh
#!/bin/bash
URL="$1"
RESPONSE=$(curl -s -o /dev/null -w "%{http_code}" "$URL/health")

if [ "$RESPONSE" -eq 200 ]; then
    echo "OK - Sistema saudável"
    exit 0
elif [ "$RESPONSE" -eq 503 ]; then
    echo "CRITICAL - Sistema com problemas"
    exit 2
else
    echo "UNKNOWN - Resposta HTTP $RESPONSE"
    exit 3
fi
```

**Uso**: `./check_health.sh https://ponto.suaempresa.com.br`

### 4. Monitoramento via cron (E-mail)

```bash
#!/bin/bash
# /usr/local/bin/check-ponto-health.sh

URL="https://ponto.suaempresa.com.br/health"
EMAIL="admin@suaempresa.com.br"

STATUS=$(curl -s "$URL" | jq -r '.status')

if [ "$STATUS" != "healthy" ]; then
    DETAILS=$(curl -s "$URL" | jq '.')
    echo -e "ALERTA: Sistema de Ponto Eletrônico com problemas!\n\n$DETAILS" | \
        mail -s "ALERTA: Ponto Eletrônico UNHEALTHY" "$EMAIL"
fi
```

**Cron** (verificar a cada 5 minutos):
```cron
*/5 * * * * /usr/local/bin/check-ponto-health.sh
```

---

## Verificações Realizadas

### 1. **Database Check**
- Testa conexão com banco de dados
- Executa query simples (`SELECT 1`)
- Retorna driver e nome do banco

**Estados**:
- `ok`: Conexão funcionando
- `error`: Falha de conexão (com mensagem de erro)

### 2. **Writable Directories Check**
- Verifica permissões de escrita em:
  - `writable/cache`
  - `writable/logs`
  - `writable/session`
  - `writable/uploads`
  - `storage`

**Estados**:
- `ok`: Todos os diretórios graváveis
- `error`: Pelo menos um diretório sem permissão

### 3. **Cache Check**
- Testa escrita e leitura do cache
- Cria chave temporária, lê e remove

**Estados**:
- `ok`: Cache funcionando
- `error`: Falha em read/write

### 4. **Session Check**
- Testa sistema de sessões
- Cria valor de teste, lê e remove

**Estados**:
- `ok`: Sessões funcionando
- `error`: Falha em read/write de sessão

### 5. **Environment Check**
- Verifica versão do PHP (>= 8.1.0)
- Valida extensões críticas:
  - `mysqli`
  - `mbstring`
  - `intl`
  - `json`
  - `xml`
- Verifica existência do arquivo `.env`
- Valida encryption key configurada

**Estados**:
- `ok`: Ambiente configurado corretamente
- `warning`: Problemas encontrados (lista em `issues`)

---

## Códigos de Status HTTP

| Código | Significado | Ação |
|--------|-------------|------|
| **200** | Sistema saudável | Nenhuma ação necessária |
| **503** | Sistema com problemas | Investigar campo `checks` na resposta |
| **403** | Endpoint bloqueado | Normal em `/health/detailed` em produção |
| **500** | Erro no servidor | Verificar logs em `writable/logs/` |

---

## Logs do Sistema

### Localização
```
writable/logs/log-YYYY-MM-DD.log
```

### Monitoramento de Logs

**Tail em tempo real**:
```bash
tail -f writable/logs/log-$(date +%Y-%m-%d).log
```

**Buscar erros**:
```bash
grep -i "error\|critical\|fatal" writable/logs/log-$(date +%Y-%m-%d).log
```

**Alertas via logwatch**:
```bash
# /etc/logwatch/conf/services/ponto-eletronico.conf
LogFile = /var/www/ponto-eletronico/writable/logs/*.log
Title = "Sistema de Ponto Eletrônico"
*OnlyService = ponto-eletronico
*RemoveHeaders
```

---

## Métricas Importantes

### Disponibilidade (Uptime)
- **Target**: 99.9% (menos de 8.76 horas de downtime/ano)
- **Monitoramento**: A cada 1 minuto via `/health`

### Tempo de Resposta
- **Target**: < 200ms para `/health`
- **Alert**: > 1 segundo

### Taxa de Erro
- **Target**: < 0.1% de requisições com HTTP 5xx
- **Alert**: > 1% de erros

### Utilização de Recursos
- **CPU**: Alert se > 80% por mais de 5 minutos
- **Memória**: Alert se > 85%
- **Disco**: Alert se > 80%

---

## Troubleshooting

### Sistema retornando `unhealthy`

1. **Verificar logs**:
   ```bash
   tail -n 100 writable/logs/log-$(date +%Y-%m-%d).log
   ```

2. **Testar banco de dados manualmente**:
   ```bash
   mysql -u ponto_user -p ponto_db -e "SELECT 1;"
   ```

3. **Verificar permissões**:
   ```bash
   ls -la writable/
   ls -la storage/
   ```

4. **Verificar PHP**:
   ```bash
   php -v
   php -m | grep -E 'mysqli|mbstring|intl'
   ```

### Endpoint não responde (timeout)

1. **Verificar servidor web**:
   ```bash
   systemctl status nginx
   systemctl status php8.4-fpm
   ```

2. **Verificar logs do servidor**:
   ```bash
   tail -f /var/log/nginx/error.log
   tail -f /var/log/php8.4-fpm.log
   ```

3. **Testar localmente**:
   ```bash
   curl -v http://localhost/health
   ```

---

## Contato e Suporte

Em caso de problemas críticos:
- **E-mail**: admin@suaempresa.com.br
- **Telefone**: (XX) XXXX-XXXX
- **On-call**: verificar planilha de plantão

---

**Última Atualização**: 2025-11-18
**Versão do Documento**: 1.0
**Autor**: Claude (Anthropic)
