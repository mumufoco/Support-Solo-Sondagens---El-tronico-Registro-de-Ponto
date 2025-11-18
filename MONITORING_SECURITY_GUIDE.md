# 📊 Guia de Monitoramento e Segurança
## Sistema de Registro de Ponto Eletrônico

**Versão:** 1.0
**Data:** 18/11/2024
**Objetivo:** Configurar monitoramento proativo de eventos de segurança

---

## 📋 Índice

1. [Visão Geral](#visão-geral)
2. [Arquitetura de Monitoramento](#arquitetura-de-monitoramento)
3. [Configuração de Logs](#configuração-de-logs)
4. [Alertas de Segurança](#alertas-de-segurança)
5. [Métricas e Dashboards](#métricas-e-dashboards)
6. [Incident Response](#incident-response)
7. [Ferramentas Recomendadas](#ferramentas-recomendadas)
8. [Configuração Passo a Passo](#configuração-passo-a-passo)
9. [Queries e Relatórios](#queries-e-relatórios)
10. [Manutenção e Tuning](#manutenção-e-tuning)

---

## 🎯 Visão Geral

### Objetivos do Monitoramento

1. **Detecção de Ataques:** Identificar tentativas de invasão em tempo real
2. **Análise de Comportamento:** Detectar padrões anormais de uso
3. **Compliance:** Manter trilha de auditoria para LGPD e regulamentações
4. **Performance:** Monitorar saúde e performance da aplicação
5. **Alertas Proativos:** Notificar equipe sobre incidentes críticos

### Eventos Críticos a Monitorar

#### 🔴 Prioridade Crítica (Alerta Imediato)
- Multiple failed login attempts (brute force)
- SQL injection attempts
- Path traversal attempts
- Unauthorized access attempts (403/401)
- Data breach indicators
- Privilege escalation attempts
- Malicious file uploads
- Critical errors (500)

#### 🟠 Prioridade Alta (Alerta em 15 min)
- Repeated CSRF failures
- Rate limiting triggers
- Unusual API usage patterns
- Account lockouts
- Password changes
- Role/permission changes
- Biometric verification failures

#### 🟡 Prioridade Média (Revisão Diária)
- Successful logins from new IPs
- File access patterns
- Database slow queries
- Cache misses
- Session expirations

#### 🟢 Prioridade Baixa (Revisão Semanal)
- General application errors
- Performance metrics
- User activity statistics

---

## 🏗️ Arquitetura de Monitoramento

### Stack Recomendado

```
┌─────────────────────────────────────────────────┐
│         Aplicação CodeIgniter 4                 │
│  ┌──────────────────────────────────────────┐   │
│  │  Application Logs (WRITEPATH/logs/)     │   │
│  │  - Error logs                            │   │
│  │  - Audit logs (audit_logs table)         │   │
│  │  - Access logs                           │   │
│  └──────────────────────────────────────────┘   │
└─────────────────────────────────────────────────┘
                      │
                      ▼
┌─────────────────────────────────────────────────┐
│         Log Aggregation Layer                   │
│  ┌──────────────────────────────────────────┐   │
│  │  Filebeat / Fluentd / Logstash          │   │
│  │  - Parse logs                            │   │
│  │  - Enrich with metadata                  │   │
│  │  - Forward to storage                    │   │
│  └──────────────────────────────────────────┘   │
└─────────────────────────────────────────────────┘
                      │
                      ▼
┌─────────────────────────────────────────────────┐
│         Storage & Analysis                      │
│  ┌──────────────────────────────────────────┐   │
│  │  Elasticsearch / ClickHouse              │   │
│  │  - Time-series storage                   │   │
│  │  - Full-text search                      │   │
│  │  - Aggregations                          │   │
│  └──────────────────────────────────────────┘   │
└─────────────────────────────────────────────────┘
                      │
                      ▼
┌─────────────────────────────────────────────────┐
│         Visualization & Alerting                │
│  ┌──────────────────────────────────────────┐   │
│  │  Kibana / Grafana / Custom Dashboard     │   │
│  │  - Real-time dashboards                  │   │
│  │  - Alert rules                           │   │
│  │  - Reports                               │   │
│  └──────────────────────────────────────────┘   │
│  ┌──────────────────────────────────────────┐   │
│  │  Alertmanager / PagerDuty / Email        │   │
│  │  - Notification routing                  │   │
│  │  - Escalation policies                   │   │
│  └──────────────────────────────────────────┘   │
└─────────────────────────────────────────────────┘
```

### Alternativas por Budget

#### 💰 Budget Limitado (Gratuito/Open Source)
- **Logs:** Syslog + Fail2Ban
- **Storage:** Arquivos + SQLite
- **Alerts:** Cron jobs + email
- **Dashboard:** Scripts PHP personalizados

#### 💰💰 Budget Médio
- **Stack ELK:** Elasticsearch + Logstash + Kibana
- **Alerts:** ElastAlert
- **Metrics:** Prometheus + Grafana

#### 💰💰💰 Budget Alto (Enterprise)
- **SIEM:** Splunk / Datadog / New Relic
- **Cloud Logging:** AWS CloudWatch / Google Cloud Logging
- **APM:** Sentry + Datadog APM

---

## 📝 Configuração de Logs

### 1. Application Logging

#### app/Config/Logger.php

```php
<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;
use CodeIgniter\Log\Handlers\FileHandler;

class Logger extends BaseConfig
{
    /**
     * Log levels
     */
    public $threshold = (ENVIRONMENT === 'production') ? 4 : 9;
    // 9 = All logs in development
    // 4 = warning, error, critical, alert, emergency in production

    /**
     * Date format for logs
     */
    public string $dateFormat = 'Y-m-d H:i:s';

    /**
     * Log Handlers
     */
    public array $handlers = [
        // File Handler
        FileHandler::class => [
            'handles' => [
                'critical',
                'alert',
                'emergency',
                'debug',
                'error',
                'info',
                'notice',
                'warning',
            ],
            'config' => [
                'path' => WRITEPATH . 'logs/',
            ],
        ],

        // Security Handler (separate file)
        'SecurityFileHandler' => [
            'class'   => FileHandler::class,
            'handles' => ['security'],
            'config'  => [
                'path'      => WRITEPATH . 'logs/security/',
                'filename'  => 'security-{date}.log',
            ],
        ],

        // Audit Handler (database)
        'AuditDatabaseHandler' => [
            'class'   => \App\Handlers\AuditDatabaseHandler::class,
            'handles' => ['audit'],
        ],
    ];
}
```

#### Custom Security Logger Handler

**app/Handlers/SecurityFileHandler.php:**

```php
<?php

namespace App\Handlers;

use CodeIgniter\Log\Handlers\FileHandler;

class SecurityFileHandler extends FileHandler
{
    public function __construct(array $config = [])
    {
        $config['path'] = WRITEPATH . 'logs/security/';
        $config['filename'] = 'security-' . date('Y-m-d') . '.log';

        parent::__construct($config);
    }

    public function handle($level, $message): bool
    {
        // Adiciona contexto de segurança
        $enrichedMessage = sprintf(
            "[%s] [IP: %s] [User: %s] %s",
            strtoupper($level),
            get_client_ip(),
            session('user_id') ?? 'guest',
            $message
        );

        return parent::handle($level, $enrichedMessage);
    }
}
```

### 2. Structured Logging

#### Helper para Log Estruturado

**app/Helpers/security_log_helper.php:**

```php
<?php

if (!function_exists('security_log')) {
    /**
     * Log security event with structured data
     *
     * @param string $event Event type (LOGIN_FAILED, SQL_INJECTION_ATTEMPT, etc.)
     * @param array $context Additional context
     * @param string $severity Severity level
     */
    function security_log(string $event, array $context = [], string $severity = 'warning'): void
    {
        $data = [
            'timestamp' => date('Y-m-d H:i:s'),
            'event'     => $event,
            'ip'        => get_client_ip(),
            'user_id'   => session('user_id'),
            'user_agent' => get_user_agent(),
            'url'       => current_url(),
            'method'    => $_SERVER['REQUEST_METHOD'] ?? 'CLI',
            'context'   => $context,
        ];

        // Log como JSON para facilitar parsing
        $message = json_encode($data, JSON_UNESCAPED_UNICODE);

        // Log em arquivo
        log_message($severity, $message);

        // Log em banco de dados (audit_logs)
        if (in_array($severity, ['critical', 'alert', 'emergency'])) {
            try {
                $auditModel = new \App\Models\AuditLogModel();
                $auditModel->log(
                    $data['user_id'],
                    $event,
                    $context['table'] ?? null,
                    $context['record_id'] ?? null,
                    $context['old_values'] ?? null,
                    $context['new_values'] ?? null,
                    $context['description'] ?? $event,
                    $severity
                );
            } catch (\Exception $e) {
                log_message('error', 'Failed to write audit log: ' . $e->getMessage());
            }
        }
    }
}

if (!function_exists('log_security_event')) {
    /**
     * Shorthand for common security events
     */
    function log_security_event(string $event, array $context = []): void
    {
        security_log($event, $context, 'security');
    }
}
```

#### Uso nos Controllers

```php
// Login falhado
security_log('LOGIN_FAILED', [
    'email' => sanitize_for_log($email),
    'reason' => 'invalid_credentials',
    'attempts' => $attemptCount,
], 'warning');

// SQL Injection attempt detectado
security_log('SQL_INJECTION_ATTEMPT', [
    'input' => sanitize_for_log($suspiciousInput),
    'parameter' => 'employee_id',
], 'critical');

// Acesso não autorizado
security_log('UNAUTHORIZED_ACCESS', [
    'resource' => 'timesheets',
    'resource_id' => $timesheetId,
    'attempted_action' => 'update',
], 'alert');

// Path traversal attempt
security_log('PATH_TRAVERSAL_ATTEMPT', [
    'requested_path' => sanitize_for_log($path),
], 'critical');

// Brute force bloqueado
security_log('BRUTE_FORCE_BLOCKED', [
    'email' => sanitize_for_log($email),
    'total_attempts' => 5,
    'block_duration_minutes' => 15,
], 'alert');
```

### 3. Nginx/Apache Access Logs

#### Nginx Configuration

**/etc/nginx/sites-available/ponto-eletronico:**

```nginx
server {
    listen 443 ssl http2;
    server_name ponto.empresa.com;

    # SSL configuration
    ssl_certificate /etc/ssl/certs/ponto.crt;
    ssl_certificate_key /etc/ssl/private/ponto.key;

    # Custom log format com informações de segurança
    log_format security_log '$remote_addr - $remote_user [$time_local] '
                            '"$request" $status $body_bytes_sent '
                            '"$http_referer" "$http_user_agent" '
                            '$request_time $upstream_response_time '
                            '$ssl_protocol $ssl_cipher';

    access_log /var/log/nginx/ponto-access.log security_log;
    error_log /var/log/nginx/ponto-error.log warn;

    # Log suspicious patterns
    if ($request_uri ~* "(\.\.\/|%2e%2e%2f|%252e%252e%252f)") {
        access_log /var/log/nginx/ponto-security-alerts.log security_log;
    }

    if ($args ~* "(union.*select|insert.*into|delete.*from|drop.*table)") {
        access_log /var/log/nginx/ponto-security-alerts.log security_log;
    }

    root /var/www/ponto-eletronico/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.4-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

### 4. Database Query Logging

#### Enable Slow Query Log (MySQL)

**my.cnf:**

```ini
[mysqld]
# Slow query log
slow_query_log = 1
slow_query_log_file = /var/log/mysql/slow-query.log
long_query_time = 2  # Queries > 2 segundos

# Log queries sem índices
log_queries_not_using_indexes = 1

# General log (apenas em troubleshooting, muito verboso)
# general_log = 1
# general_log_file = /var/log/mysql/general.log
```

#### Monitor Queries em Tempo Real

```bash
# Ver queries em execução
mysql -e "SHOW FULL PROCESSLIST;"

# Queries lentas
mysql -e "SELECT * FROM mysql.slow_log ORDER BY start_time DESC LIMIT 10;"
```

---

## 🚨 Alertas de Segurança

### 1. Fail2Ban Configuration

#### Instalação

```bash
sudo apt install fail2ban
```

#### Configuração para Aplicação

**/etc/fail2ban/filter.d/ponto-auth.conf:**

```ini
[Definition]
# Filter para logs da aplicação

# Login falhado
failregex = ^.*\[SECURITY\] LOGIN_FAILED.*"ip":"<HOST>".*$
            ^.*\[warning\] Login attempt failed from IP <HOST>.*$

# SQL Injection
            ^.*\[SECURITY\] SQL_INJECTION_ATTEMPT.*"ip":"<HOST>".*$

# Path traversal
            ^.*\[SECURITY\] PATH_TRAVERSAL_ATTEMPT.*"ip":"<HOST>".*$

# Acesso não autorizado
            ^.*\[SECURITY\] UNAUTHORIZED_ACCESS.*"ip":"<HOST>".*$

ignoreregex =
```

**/etc/fail2ban/jail.local:**

```ini
[DEFAULT]
# Ban por 1 hora
bantime = 3600
findtime = 600
maxretry = 5

# Ações
destemail = admin@empresa.com
sender = fail2ban@ponto.empresa.com
action = %(action_mwl)s

[ponto-auth]
enabled = true
port = http,https
filter = ponto-auth
logpath = /var/www/ponto-eletronico/writable/logs/security/*.log
maxretry = 3
bantime = 3600
findtime = 300

[ponto-sql-injection]
enabled = true
port = http,https
filter = ponto-auth
logpath = /var/www/ponto-eletronico/writable/logs/security/*.log
maxretry = 1
bantime = 86400  # 24 horas
findtime = 60
```

#### Comandos Úteis

```bash
# Status
sudo fail2ban-client status

# Status de jail específico
sudo fail2ban-client status ponto-auth

# Desbanir IP
sudo fail2ban-client set ponto-auth unbanip 192.168.1.100

# Recarregar configuração
sudo fail2ban-client reload
```

### 2. Custom Alert Script

#### Alert Watcher Script

**scripts/security_alert_watcher.php:**

```php
#!/usr/bin/env php
<?php

/**
 * Security Alert Watcher
 *
 * Monitora logs de segurança e envia alertas
 * Executar via cron a cada 5 minutos
 */

require __DIR__ . '/../vendor/autoload.php';

use App\Models\AuditLogModel;

// Conectar ao banco
$db = \Config\Database::connect();

// Buscar eventos críticos dos últimos 5 minutos
$auditModel = new AuditLogModel();
$criticalEvents = $auditModel
    ->whereIn('severity', ['critical', 'alert', 'emergency'])
    ->where('created_at >=', date('Y-m-d H:i:s', strtotime('-5 minutes')))
    ->findAll();

if (empty($criticalEvents)) {
    exit(0); // Nenhum evento crítico
}

// Agregar eventos por tipo
$eventsByType = [];
foreach ($criticalEvents as $event) {
    $eventsByType[$event->action][] = $event;
}

// Construir mensagem de alerta
$message = "🚨 ALERTA DE SEGURANÇA - " . count($criticalEvents) . " evento(s) crítico(s) detectado(s):\n\n";

foreach ($eventsByType as $type => $events) {
    $message .= "📍 {$type} (" . count($events) . " ocorrência(s))\n";

    foreach ($events as $event) {
        $message .= sprintf(
            "   - Usuário: %s | IP: %s | Horário: %s\n",
            $event->user_id ?? 'N/A',
            $event->metadata['ip_address'] ?? 'N/A',
            $event->created_at
        );
    }

    $message .= "\n";
}

$message .= "\n🔗 Acesse o painel: https://ponto.empresa.com/admin/security\n";

// Enviar alerta via email
sendAlert($message);

// Enviar para Slack (se configurado)
if (env('SLACK_WEBHOOK_URL')) {
    sendSlackAlert($message);
}

// Enviar para Telegram (se configurado)
if (env('TELEGRAM_BOT_TOKEN') && env('TELEGRAM_CHAT_ID')) {
    sendTelegramAlert($message);
}

echo "Alerta enviado para " . count($criticalEvents) . " evento(s)\n";

/**
 * Enviar email de alerta
 */
function sendAlert(string $message): void
{
    $to = env('SECURITY_ALERT_EMAIL', 'admin@empresa.com');
    $subject = '🚨 [CRÍTICO] Alerta de Segurança - Sistema de Ponto';

    $headers = [
        'From: Security Bot <security@ponto.empresa.com>',
        'X-Priority: 1',
        'X-MSMail-Priority: High',
        'Importance: High',
    ];

    mail($to, $subject, $message, implode("\r\n", $headers));
}

/**
 * Enviar para Slack
 */
function sendSlackAlert(string $message): void
{
    $webhookUrl = env('SLACK_WEBHOOK_URL');

    $payload = json_encode([
        'text' => $message,
        'username' => 'Security Bot',
        'icon_emoji' => ':rotating_light:',
        'channel' => '#security-alerts',
    ]);

    $ch = curl_init($webhookUrl);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_exec($ch);
    curl_close($ch);
}

/**
 * Enviar para Telegram
 */
function sendTelegramAlert(string $message): void
{
    $botToken = env('TELEGRAM_BOT_TOKEN');
    $chatId = env('TELEGRAM_CHAT_ID');

    $url = "https://api.telegram.org/bot{$botToken}/sendMessage";

    $data = [
        'chat_id' => $chatId,
        'text' => $message,
        'parse_mode' => 'Markdown',
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_exec($ch);
    curl_close($ch);
}
```

#### Cron Job

```bash
# Editar crontab
crontab -e

# Adicionar linha
*/5 * * * * /usr/bin/php /var/www/ponto-eletronico/scripts/security_alert_watcher.php >> /var/log/security-alerts.log 2>&1
```

### 3. Real-time Alerting com Redis/Queue

#### Setup Redis Queue

```bash
composer require predis/predis
```

**app/Config/Queue.php:**

```php
<?php

namespace Config;

class Queue extends \CodeIgniter\Config\BaseConfig
{
    public string $default = 'redis';

    public array $connections = [
        'redis' => [
            'driver'  => 'redis',
            'host'    => env('REDIS_HOST', '127.0.0.1'),
            'port'    => env('REDIS_PORT', 6379),
            'timeout' => 0,
            'queue'   => 'security_alerts',
        ],
    ];
}
```

**app/Jobs/SecurityAlertJob.php:**

```php
<?php

namespace App\Jobs;

class SecurityAlertJob
{
    public function fire($job, $data)
    {
        $event = $data['event'];
        $severity = $data['severity'];

        // Enviar alerta conforme severidade
        if ($severity === 'critical') {
            $this->sendImmediateAlert($event);
        } elseif ($severity === 'alert') {
            $this->send15MinAlert($event);
        }

        $job->delete();
    }

    protected function sendImmediateAlert($event)
    {
        // SMS, Push notification, etc.
    }
}
```

---

## 📈 Métricas e Dashboards

### 1. Dashboard de Segurança (PHP/HTML)

**app/Controllers/Admin/SecurityDashboardController.php:**

```php
<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\AuditLogModel;

class SecurityDashboardController extends BaseController
{
    protected $auditModel;

    public function __construct()
    {
        $this->auditModel = new AuditLogModel();
    }

    public function index()
    {
        // Verificar permissão de admin
        if (session('user_role') !== 'admin') {
            return redirect()->to('/dashboard')->with('error', 'Acesso negado');
        }

        $data = [
            'title' => 'Dashboard de Segurança',
            'stats' => $this->getSecurityStats(),
            'recentEvents' => $this->getRecentSecurityEvents(),
            'topThreats' => $this->getTopThreats(),
            'suspiciousIPs' => $this->getSuspiciousIPs(),
        ];

        return view('admin/security_dashboard', $data);
    }

    protected function getSecurityStats(): array
    {
        $db = db_connect();

        return [
            'failed_logins_today' => $this->auditModel
                ->where('action', 'LOGIN_FAILED')
                ->where('DATE(created_at)', date('Y-m-d'))
                ->countAllResults(),

            'blocked_ips' => $this->getBlockedIPsCount(),

            'sql_injection_attempts_week' => $this->auditModel
                ->where('action', 'SQL_INJECTION_ATTEMPT')
                ->where('created_at >=', date('Y-m-d', strtotime('-7 days')))
                ->countAllResults(),

            'unauthorized_access_week' => $this->auditModel
                ->where('action', 'UNAUTHORIZED_ACCESS')
                ->where('created_at >=', date('Y-m-d', strtotime('-7 days')))
                ->countAllResults(),

            'total_security_events_month' => $this->auditModel
                ->whereIn('severity', ['critical', 'alert', 'warning'])
                ->where('created_at >=', date('Y-m-d', strtotime('-30 days')))
                ->countAllResults(),
        ];
    }

    protected function getRecentSecurityEvents(int $limit = 20): array
    {
        return $this->auditModel
            ->whereIn('severity', ['critical', 'alert', 'emergency'])
            ->orderBy('created_at', 'DESC')
            ->limit($limit)
            ->findAll();
    }

    protected function getTopThreats(): array
    {
        return $this->auditModel
            ->select('action, COUNT(*) as count')
            ->whereIn('severity', ['critical', 'alert'])
            ->where('created_at >=', date('Y-m-d', strtotime('-7 days')))
            ->groupBy('action')
            ->orderBy('count', 'DESC')
            ->limit(10)
            ->findAll();
    }

    protected function getSuspiciousIPs(): array
    {
        $db = db_connect();

        // IPs com mais de 10 eventos de segurança na última hora
        $query = $db->query("
            SELECT
                JSON_EXTRACT(metadata, '$.ip_address') as ip_address,
                COUNT(*) as event_count,
                GROUP_CONCAT(DISTINCT action) as actions
            FROM audit_logs
            WHERE severity IN ('critical', 'alert', 'warning')
              AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
            GROUP BY ip_address
            HAVING event_count > 10
            ORDER BY event_count DESC
            LIMIT 20
        ");

        return $query->getResultArray();
    }

    protected function getBlockedIPsCount(): int
    {
        // Verificar Fail2Ban banned IPs
        exec('fail2ban-client status ponto-auth 2>/dev/null | grep "Currently banned" | awk \'{print $4}\'', $output);

        return isset($output[0]) ? (int)$output[0] : 0;
    }

    /**
     * API endpoint para dados do dashboard (AJAX)
     */
    public function apiStats()
    {
        if (!$this->request->isAJAX()) {
            return $this->failUnauthorized();
        }

        return $this->respond([
            'stats' => $this->getSecurityStats(),
            'recent_events' => $this->getRecentSecurityEvents(10),
        ]);
    }
}
```

**app/Views/admin/security_dashboard.php:**

```php
<!DOCTYPE html>
<html>
<head>
    <title><?= esc($title) ?></title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .stat-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            margin: 10px;
            display: inline-block;
            min-width: 200px;
        }
        .stat-card h3 { margin: 0; color: #333; }
        .stat-card .number { font-size: 36px; font-weight: bold; }
        .critical { color: #d32f2f; }
        .warning { color: #f57c00; }
        .info { color: #1976d2; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #f5f5f5; }
    </style>
</head>
<body>
    <h1>🔒 <?= esc($title) ?></h1>

    <div class="stats-container">
        <div class="stat-card critical">
            <h3>Logins Falhados Hoje</h3>
            <div class="number"><?= $stats['failed_logins_today'] ?></div>
        </div>

        <div class="stat-card warning">
            <h3>IPs Bloqueados</h3>
            <div class="number"><?= $stats['blocked_ips'] ?></div>
        </div>

        <div class="stat-card critical">
            <h3>Tentativas SQL Injection (7 dias)</h3>
            <div class="number"><?= $stats['sql_injection_attempts_week'] ?></div>
        </div>

        <div class="stat-card warning">
            <h3>Acessos Não Autorizados (7 dias)</h3>
            <div class="number"><?= $stats['unauthorized_access_week'] ?></div>
        </div>

        <div class="stat-card info">
            <h3>Total Eventos de Segurança (30 dias)</h3>
            <div class="number"><?= $stats['total_security_events_month'] ?></div>
        </div>
    </div>

    <h2>⚠️ Top Ameaças (Últimos 7 Dias)</h2>
    <canvas id="threatsChart" width="400" height="200"></canvas>

    <h2>🚨 Eventos Recentes</h2>
    <table>
        <thead>
            <tr>
                <th>Horário</th>
                <th>Evento</th>
                <th>Usuário</th>
                <th>IP</th>
                <th>Severidade</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($recentEvents as $event): ?>
                <tr>
                    <td><?= esc($event->created_at) ?></td>
                    <td><?= esc($event->action) ?></td>
                    <td><?= esc($event->user_id ?? 'N/A') ?></td>
                    <td><?= esc($event->metadata['ip_address'] ?? 'N/A') ?></td>
                    <td class="<?= esc($event->severity) ?>">
                        <?= strtoupper(esc($event->severity)) ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h2>🌐 IPs Suspeitos</h2>
    <table>
        <thead>
            <tr>
                <th>IP</th>
                <th>Eventos (Última Hora)</th>
                <th>Tipos de Ataque</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($suspiciousIPs as $ip): ?>
                <tr>
                    <td><?= esc($ip['ip_address']) ?></td>
                    <td><?= esc($ip['event_count']) ?></td>
                    <td><?= esc($ip['actions']) ?></td>
                    <td>
                        <button onclick="blockIP('<?= esc($ip['ip_address']) ?>')">Bloquear</button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <script>
        // Chart de top ameaças
        const ctx = document.getElementById('threatsChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_column($topThreats, 'action')) ?>,
                datasets: [{
                    label: 'Ocorrências',
                    data: <?= json_encode(array_column($topThreats, 'count')) ?>,
                    backgroundColor: 'rgba(211, 47, 47, 0.5)',
                    borderColor: 'rgba(211, 47, 47, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Auto-refresh a cada 30 segundos
        setInterval(() => {
            location.reload();
        }, 30000);

        function blockIP(ip) {
            if (confirm(`Bloquear IP ${ip} permanentemente?`)) {
                // AJAX call to block IP
                fetch('/admin/security/block-ip', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '<?= csrf_hash() ?>'
                    },
                    body: JSON.stringify({ ip: ip })
                })
                .then(response => response.json())
                .then(data => {
                    alert(data.message);
                    location.reload();
                });
            }
        }
    </script>
</body>
</html>
```

### 2. Métricas Exportadas para Prometheus

**app/Controllers/MetricsController.php:**

```php
<?php

namespace App\Controllers;

class MetricsController extends BaseController
{
    /**
     * Prometheus metrics endpoint
     */
    public function prometheus()
    {
        // Verificar autenticação (token, IP whitelist, etc.)
        if (!$this->isAuthorizedMonitoring()) {
            return $this->failUnauthorized();
        }

        $metrics = $this->generatePrometheusMetrics();

        return $this->response
            ->setContentType('text/plain; version=0.0.4')
            ->setBody($metrics);
    }

    protected function generatePrometheusMetrics(): string
    {
        $auditModel = new \App\Models\AuditLogModel();

        $metrics = [];

        // Failed logins
        $failedLogins = $auditModel
            ->where('action', 'LOGIN_FAILED')
            ->where('created_at >=', date('Y-m-d H:i:s', strtotime('-1 hour')))
            ->countAllResults();

        $metrics[] = '# HELP ponto_failed_logins_total Total failed login attempts';
        $metrics[] = '# TYPE ponto_failed_logins_total counter';
        $metrics[] = "ponto_failed_logins_total {$failedLogins}";

        // SQL Injection attempts
        $sqlInjections = $auditModel
            ->where('action', 'SQL_INJECTION_ATTEMPT')
            ->where('created_at >=', date('Y-m-d H:i:s', strtotime('-1 hour')))
            ->countAllResults();

        $metrics[] = '# HELP ponto_sql_injection_attempts_total SQL injection attempts detected';
        $metrics[] = '# TYPE ponto_sql_injection_attempts_total counter';
        $metrics[] = "ponto_sql_injection_attempts_total {$sqlInjections}";

        // Active sessions
        $activeSessions = $this->getActiveSessionsCount();

        $metrics[] = '# HELP ponto_active_sessions Active user sessions';
        $metrics[] = '# TYPE ponto_active_sessions gauge';
        $metrics[] = "ponto_active_sessions {$activeSessions}";

        return implode("\n", $metrics) . "\n";
    }

    protected function isAuthorizedMonitoring(): bool
    {
        // Verificar token de autenticação
        $token = $this->request->getHeaderLine('Authorization');

        return $token === 'Bearer ' . env('MONITORING_TOKEN');
    }

    protected function getActiveSessionsCount(): int
    {
        // Contar arquivos de sessão ativos
        $sessionPath = WRITEPATH . 'session/';
        $count = 0;

        if (is_dir($sessionPath)) {
            $files = glob($sessionPath . 'ci_session*');
            $count = count($files);
        }

        return $count;
    }
}
```

---

## 🔍 Queries e Relatórios

### Queries SQL Úteis

#### 1. Top IPs com Mais Tentativas de Login Falhadas

```sql
SELECT
    JSON_EXTRACT(metadata, '$.ip_address') as ip_address,
    COUNT(*) as failed_attempts,
    MIN(created_at) as first_attempt,
    MAX(created_at) as last_attempt
FROM audit_logs
WHERE action = 'LOGIN_FAILED'
  AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
GROUP BY ip_address
HAVING failed_attempts >= 5
ORDER BY failed_attempts DESC
LIMIT 20;
```

#### 2. Usuários com Mais Acessos Negados

```sql
SELECT
    user_id,
    e.name,
    e.email,
    COUNT(*) as denied_count,
    GROUP_CONCAT(DISTINCT action) as denied_actions
FROM audit_logs al
LEFT JOIN employees e ON e.id = al.user_id
WHERE al.action LIKE '%DENIED%'
  OR al.action = 'UNAUTHORIZED_ACCESS'
  AND al.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
GROUP BY user_id
ORDER BY denied_count DESC
LIMIT 20;
```

#### 3. Padrões de Ataque por Hora do Dia

```sql
SELECT
    HOUR(created_at) as hour,
    COUNT(*) as attack_count,
    GROUP_CONCAT(DISTINCT action) as attack_types
FROM audit_logs
WHERE severity IN ('critical', 'alert')
  AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
GROUP BY hour
ORDER BY attack_count DESC;
```

#### 4. Tempo Médio de Resposta por Endpoint (Slow Logs)

```sql
-- Analisar logs de acesso do Nginx
-- Precisa configurar log_format com $request_time

-- Exemplo de análise com awk:
```

```bash
awk '{print $7, $10}' /var/log/nginx/ponto-access.log | \
  awk '{sum[$1]+=$2; count[$1]++} END {for (url in sum) print url, sum[url]/count[url]}' | \
  sort -k2 -rn | head -20
```

#### 5. Usuários que Mudaram Permissões Recentemente

```sql
SELECT
    al.user_id as changed_by,
    e1.name as changed_by_name,
    al.record_id as affected_user_id,
    e2.name as affected_user_name,
    al.old_values,
    al.new_values,
    al.created_at
FROM audit_logs al
LEFT JOIN employees e1 ON e1.id = al.user_id
LEFT JOIN employees e2 ON e2.id = al.record_id
WHERE al.action = 'UPDATE'
  AND al.table_name = 'employees'
  AND (
      JSON_EXTRACT(al.new_values, '$.role') != JSON_EXTRACT(al.old_values, '$.role')
      OR JSON_EXTRACT(al.new_values, '$.active') != JSON_EXTRACT(al.old_values, '$.active')
  )
  AND al.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
ORDER BY al.created_at DESC;
```

---

## 🛠️ Manutenção e Tuning

### 1. Log Rotation

**logrotate configuration:**

**/etc/logrotate.d/ponto-eletronico:**

```
/var/www/ponto-eletronico/writable/logs/*.log {
    daily
    missingok
    rotate 90
    compress
    delaycompress
    notifempty
    create 0640 www-data www-data
    sharedscripts
    postrotate
        # Recarregar aplicação se necessário
    endscript
}

/var/www/ponto-eletronico/writable/logs/security/*.log {
    daily
    missingok
    rotate 365
    compress
    delaycompress
    notifempty
    create 0640 www-data www-data
}
```

### 2. Database Maintenance

**Limpeza de Logs Antigos (Cron Job):**

**scripts/cleanup_old_logs.php:**

```php
#!/usr/bin/env php
<?php

require __DIR__ . '/../vendor/autoload.php';

$db = \Config\Database::connect();

// Deletar audit logs mais antigos que 90 dias (exceto críticos)
$db->table('audit_logs')
    ->where('created_at <', date('Y-m-d', strtotime('-90 days')))
    ->whereNotIn('severity', ['critical', 'alert', 'emergency'])
    ->delete();

echo "Logs antigos deletados\n";

// Arquivar eventos críticos antigos (> 1 ano) para tabela de arquivo
$db->query("
    INSERT INTO audit_logs_archive
    SELECT * FROM audit_logs
    WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 YEAR)
      AND severity IN ('critical', 'alert', 'emergency')
");

$db->table('audit_logs')
    ->where('created_at <', date('Y-m-d', strtotime('-1 year')))
    ->whereIn('severity', ['critical', 'alert', 'emergency'])
    ->delete();

echo "Eventos críticos antigos arquivados\n";

// Otimizar tabela
$db->query('OPTIMIZE TABLE audit_logs');

echo "Tabela otimizada\n";
```

```bash
# Cron job - executar semanalmente
0 3 * * 0 /usr/bin/php /var/www/ponto-eletronico/scripts/cleanup_old_logs.php
```

### 3. Performance Tuning

#### Índices na Tabela audit_logs

```sql
-- Índices para queries comuns
CREATE INDEX idx_audit_action ON audit_logs(action);
CREATE INDEX idx_audit_severity ON audit_logs(severity);
CREATE INDEX idx_audit_created_at ON audit_logs(created_at);
CREATE INDEX idx_audit_user_id ON audit_logs(user_id);

-- Índice composto para queries de segurança
CREATE INDEX idx_audit_security ON audit_logs(severity, action, created_at);

-- Índice para metadata JSON (MySQL 5.7+)
ALTER TABLE audit_logs ADD INDEX idx_metadata_ip ((CAST(JSON_EXTRACT(metadata, '$.ip_address') AS CHAR(45))));
```

#### Particionamento por Data (MySQL)

```sql
-- Particionar audit_logs por mês
ALTER TABLE audit_logs
PARTITION BY RANGE (TO_DAYS(created_at)) (
    PARTITION p202401 VALUES LESS THAN (TO_DAYS('2024-02-01')),
    PARTITION p202402 VALUES LESS THAN (TO_DAYS('2024-03-01')),
    PARTITION p202403 VALUES LESS THAN (TO_DAYS('2024-04-01')),
    -- ...
    PARTITION pmax VALUES LESS THAN MAXVALUE
);
```

---

## 📚 Ferramentas Recomendadas

### Open Source / Gratuitas

| Ferramenta | Propósito | Link |
|------------|-----------|------|
| **Fail2Ban** | Bloqueio automático de IPs | https://www.fail2ban.org/ |
| **ELK Stack** | Log management | https://www.elastic.co/elastic-stack |
| **Grafana** | Dashboards | https://grafana.com/ |
| **Prometheus** | Metrics collection | https://prometheus.io/ |
| **Graylog** | Log management | https://www.graylog.org/ |
| **OSSEC** | HIDS (Host Intrusion Detection) | https://www.ossec.net/ |
| **Suricata** | IDS/IPS | https://suricata.io/ |

### Pagas / Enterprise

| Ferramenta | Propósito | Link |
|------------|-----------|------|
| **Splunk** | SIEM | https://www.splunk.com/ |
| **Datadog** | APM + Monitoring | https://www.datadoghq.com/ |
| **New Relic** | APM | https://newrelic.com/ |
| **Sentry** | Error tracking | https://sentry.io/ |
| **PagerDuty** | Incident management | https://www.pagerduty.com/ |
| **Cloudflare** | WAF + DDoS protection | https://www.cloudflare.com/ |

---

## 🎯 Checklist de Implementação

### Fase 1: Logging Básico (1-2 dias)
- [ ] Configurar Logger.php com níveis apropriados
- [ ] Criar helper security_log()
- [ ] Adicionar logs de segurança em controllers críticos
- [ ] Configurar log rotation
- [ ] Testar escrita de logs

### Fase 2: Alertas (2-3 dias)
- [ ] Instalar e configurar Fail2Ban
- [ ] Criar filtros personalizados
- [ ] Configurar email alerts
- [ ] Criar script de monitoramento (cron)
- [ ] Testar alertas com ataques simulados

### Fase 3: Dashboard (3-5 dias)
- [ ] Criar SecurityDashboardController
- [ ] Implementar views de dashboard
- [ ] Criar queries de análise
- [ ] Implementar gráficos (Chart.js)
- [ ] Testar e ajustar

### Fase 4: Monitoramento Avançado (5-10 dias - Opcional)
- [ ] Setup ELK Stack ou alternativa
- [ ] Configurar Filebeat/Logstash
- [ ] Criar dashboards no Kibana
- [ ] Configurar alertas avançados
- [ ] Integrar com PagerDuty/Slack

### Fase 5: Tuning e Manutenção (Contínuo)
- [ ] Ajustar thresholds de alertas
- [ ] Otimizar queries
- [ ] Criar scripts de cleanup
- [ ] Documentar runbooks
- [ ] Treinar equipe

---

## 📞 Runbook: Incident Response

### 1. Detecção de Brute Force Attack

**Sintomas:**
- Múltiplos alertas de LOGIN_FAILED
- IPs bloqueados pelo Fail2Ban

**Ações:**
1. Verificar dashboard de segurança
2. Identificar IPs atacantes
3. Confirmar bloqueio no Fail2Ban
4. Analisar padrões (horário, frequência)
5. Se persistir, considerar block permanente em firewall
6. Documentar incidente

```bash
# Verificar IPs bloqueados
fail2ban-client status ponto-auth

# Bloquear permanentemente no firewall
iptables -A INPUT -s 192.168.1.100 -j DROP
```

### 2. SQL Injection Attempt Detectado

**Sintomas:**
- Alerta crítico SQL_INJECTION_ATTEMPT
- Queries suspeitas nos logs

**Ações:**
1. **URGENTE:** Verificar se ataque foi bem-sucedido
   ```sql
   SELECT * FROM audit_logs
   WHERE action = 'SQL_INJECTION_ATTEMPT'
   ORDER BY created_at DESC LIMIT 10;
   ```
2. Bloquear IP imediatamente
3. Analisar código do endpoint atacado
4. Verificar se dados foram comprometidos
5. Se comprometido: iniciar procedimento de data breach
6. Corrigir vulnerabilidade
7. Deploy urgente
8. Notificar stakeholders

### 3. Acesso Não Autorizado em Massa

**Sintomas:**
- Múltiplos UNAUTHORIZED_ACCESS de mesmo IP/usuário
- Tentativa de acessar múltiplos recursos

**Ações:**
1. Bloquear IP/usuário imediatamente
2. Invalidar sessões do usuário
3. Investigar como autenticação foi obtida
4. Verificar se credenciais foram comprometidas
5. Forçar reset de senha se necessário
6. Notificar usuário afetado
7. Revisar logs de auditoria

---

**Última Atualização:** 18/11/2024
**Versão:** 1.0
**Status:** ✅ Pronto para implementação

---

**Lembre-se:** *"You can't secure what you can't see."* - Monitoramento é a base da segurança proativa! 🔍🛡️
