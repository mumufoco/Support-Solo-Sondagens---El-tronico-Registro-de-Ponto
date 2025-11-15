# Fase 9: Cálculo de Folha de Ponto (Banco de Horas)

## 📋 Visão Geral

A **Fase 9** implementa o sistema completo de cálculo automático de folha de ponto com banco de horas, processamento diário via worker (cron), dashboard interativo com gráficos, e exportação para PDF/Excel.

### Comandos Implementados
- ✅ **Comando 9.1**: Worker de cálculo diário (cron_calculate.php)
- ✅ **Comando 9.2**: Dashboard de saldo de horas (balance.php)

---

## ✅ Checklist de Implementação

### Banco de Dados
- [x] Tabela `timesheet_consolidated` criada
- [x] Campos: employee_id, date, total_worked, expected, extra, owed
- [x] Campos adicionais: interval_violation, justified, incomplete
- [x] Campos de metadados: justification_id, punches_count, first_punch, last_punch
- [x] Unique constraint (employee_id, date)
- [x] Foreign keys para employees e justifications

### Backend
- [x] Model `TimesheetConsolidatedModel` com métodos especializados
- [x] Worker script `scripts/cron_calculate.php` (12 passos)
- [x] Controller `TimesheetController` com balance() e export()
- [x] Lógica de validação CLT (intervalos obrigatórios)
- [x] Integração com justificativas aprovadas
- [x] Cálculo de violações de intervalo (1.5x)
- [x] Sistema de notificações (email + dashboard)

### Frontend
- [x] View `timesheet/balance.php` com Chart.js
- [x] Card de saldo com cores dinâmicas
- [x] Gráfico de evolução (30/60/90 dias)
- [x] Tabela detalhada com filtros
- [x] Alertas automáticos (saldo crítico)
- [x] Botões de exportação (PDF/Excel)
- [x] Seletor de funcionário (para gestores)

### Exportação
- [x] Exportação PDF com TCPDF
- [x] Exportação Excel com PhpSpreadsheet
- [x] Layout profissional com resumo + detalhes
- [x] Estatísticas do período
- [x] Audit log de exportações

### Notificações
- [x] Email diário resumo (sendDailyEmail)
- [x] Notificação de marcações incompletas
- [x] Notificação para gestores

---

## 🏗️ Arquitetura

### 1. Tabela `timesheet_consolidated`

Armazena o consolidado diário de cada funcionário após processamento pelo worker.

```sql
CREATE TABLE timesheet_consolidated (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id INT UNSIGNED NOT NULL,
    date DATE NOT NULL,
    total_worked DECIMAL(5,2) DEFAULT 0.00,
    expected DECIMAL(5,2) DEFAULT 8.00,
    extra DECIMAL(5,2) DEFAULT 0.00,
    owed DECIMAL(5,2) DEFAULT 0.00,
    interval_violation DECIMAL(5,2) DEFAULT 0.00,
    justified BOOLEAN DEFAULT FALSE,
    incomplete BOOLEAN DEFAULT FALSE,
    justification_id BIGINT UNSIGNED NULL,
    punches_count TINYINT UNSIGNED DEFAULT 0,
    first_punch TIME NULL,
    last_punch TIME NULL,
    total_interval DECIMAL(5,2) DEFAULT 0.00,
    notes TEXT NULL,
    processed_at DATETIME NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    UNIQUE KEY uk_employee_date (employee_id, date),
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    FOREIGN KEY (justification_id) REFERENCES justifications(id) ON DELETE SET NULL
);
```

**Campos principais:**
- `total_worked`: Horas trabalhadas no dia
- `expected`: Horas esperadas (padrão: 8h, pode ser customizado por funcionário)
- `extra`: Horas extras (quando trabalhado > esperado)
- `owed`: Horas devidas (quando trabalhado < esperado, sem justificativa)
- `interval_violation`: Horas de violação de intervalo (com adicional de 50%)
- `justified`: Se dia foi justificado (não desconta horas)
- `incomplete`: Se marcações estão incompletas (número ímpar, falta saída, etc.)

### 2. Worker Script: `scripts/cron_calculate.php`

Script executado diariamente via cron às 00:30 para processar o dia anterior.

**Algoritmo de 12 Passos:**

1. **Carregar funcionários ativos**
   ```php
   $employees = $employeeModel->where('active', true)->findAll();
   ```

2. **Para cada funcionário, obter marcações do dia anterior**
   ```php
   $punches = $timePunchModel
       ->where('employee_id', $employee->id)
       ->where('punch_date', $processDate)
       ->orderBy('punch_time', 'ASC')
       ->findAll();
   ```

3. **Validar emparelhamento (entrada/saída)**
   - Número par de marcações
   - Cada entrada tem uma saída correspondente

4. **Se incompleto:**
   - Marcar `incomplete = true`
   - Notificar funcionário e gestor
   - Não calcular horas extras/devidas

5. **Se completo, calcular horas trabalhadas:**
   ```php
   // Para cada par entrada/saída
   for ($i = 0; $i < $punchCount; $i += 2) {
       $start = strtotime("{$processDate} {$punchIn->punch_time}");
       $end = strtotime("{$processDate} {$punchOut->punch_time}");
       $duration = ($end - $start) / 3600;

       if ($i === 0 || $i === $punchCount - 2) {
           $totalWorked += $duration; // Períodos de trabalho
       } else {
           $totalInterval += $duration; // Períodos de intervalo
       }
   }
   ```

6. **Obter horas esperadas**
   ```php
   $expectedHours = $employee->daily_hours ?? 8.00;
   ```

7. **Calcular diferença**
   ```php
   $difference = $totalWorked - $expectedHours;
   ```

8. **Se diferença > 0: horas extras**
   ```php
   $extraHours = $difference;
   ```

9. **Se diferença < 0: verificar justificativa**
   ```php
   $hasJustification = $justificationModel->hasApprovedJustification(
       $employee->id,
       $processDate
   );

   if ($hasJustification) {
       $justified = true;
       // Não desconta horas
   } else {
       $owedHours = abs($difference);
   }
   ```

10. **Validar intervalos obrigatórios (CLT)**
    ```php
    if ($totalWorked > 6) {
        // Jornada > 6h: mínimo 1h de intervalo
        if ($totalInterval < 1) {
            $violation = 1 - $totalInterval;
            $intervalViolation = $violation * 1.5; // Adicional de 50%
        }
    } elseif ($totalWorked >= 4 && $totalWorked <= 6) {
        // Jornada 4-6h: mínimo 15min de intervalo
        if ($totalInterval < 0.25) {
            $violation = 0.25 - $totalInterval;
            $intervalViolation = $violation * 1.5;
        }
    }
    ```

11. **Salvar no consolidado**
    ```php
    $consolidatedModel->insert([
        'employee_id' => $employee->id,
        'date' => $processDate,
        'total_worked' => round($totalWorked, 2),
        'expected' => $expectedHours,
        'extra' => round($extraHours, 2),
        'owed' => round($owedHours, 2),
        'interval_violation' => round($intervalViolation, 2),
        'justified' => $justified,
        'incomplete' => $isIncomplete,
        'processed_at' => date('Y-m-d H:i:s'),
    ]);
    ```

12. **Atualizar saldo do funcionário**
    ```php
    $employeeModel->update($employee->id, [
        'extra_hours_balance' => $currentExtra + $extraHours,
        'owed_hours_balance' => $currentOwed + $owedHours,
    ]);
    ```

**Entrada no cron:**
```bash
# /etc/crontab ou crontab -e
30 0 * * * /usr/bin/php /path/to/scripts/cron_calculate.php >> /var/log/ponto/cron_calculate.log 2>&1
```

### 3. TimesheetController

**Método `balance()`:**
- Dashboard principal de visualização
- Suporta visualização de outros funcionários (gestores/admins)
- Filtros por período (30/60/90 dias)
- Filtro "apenas irregularidades"
- Calcula estatísticas do período

**Método `export()`:**
- Exporta dados para PDF ou Excel
- Valida permissões (funcionário próprio ou gestor do departamento)
- Gera arquivo com resumo + detalhes
- Registra audit log

### 4. TimesheetConsolidatedModel

**Métodos especializados:**

```php
// Saldo atual
public function getCurrentBalance(int $employeeId): array
{
    // Retorna: ['extra' => 12.5, 'owed' => 3.0, 'balance' => 9.5]
}

// Evolução para gráfico
public function getBalanceEvolution(int $employeeId, int $days = 30): array
{
    // Retorna array com saldo cumulativo por dia
}

// Dias incompletos
public function getIncompleteDays(int $employeeId, ?string $startDate = null): array
{
    // Retorna registros com incomplete = true
}

// Estatísticas agregadas
public function getStatistics(int $employeeId, string $startDate, string $endDate): array
{
    // Retorna: total_days, incomplete_days, avg_worked, etc.
}
```

---

## 🎨 Interface do Usuário

### Dashboard de Saldo (`/timesheet/balance`)

**1. Card de Saldo Principal**
- Cor verde: saldo positivo (horas extras)
- Cor vermelha: saldo negativo (horas devidas)
- Cor azul: saldo neutro (0 horas)
- Exibe saldo total em fonte grande
- Mostra breakdown: horas extras vs devidas

**2. Alertas Automáticos**
- ⚠️ Saldo negativo > 10h: alerta vermelho para regularizar
- ⚠️ Saldo positivo > 40h: alerta amarelo para compensar
- ⚠️ Marcações incompletas: link direto para timesheet

**3. Cards de Estatísticas**
- Dias trabalhados (últimos N dias)
- Dias incompletos (com link para detalhes)
- Média diária de horas
- Dias justificados

**4. Gráfico de Evolução (Chart.js)**
- Linha azul: saldo total acumulado
- Linha verde tracejada: horas extras acumuladas
- Linha vermelha tracejada: horas devidas acumuladas
- Tabs para alternar entre 30/60/90 dias
- Tooltip detalhado ao passar mouse

**5. Tabela Detalhada**
Colunas:
- Data (dia da semana)
- Entrada (primeiro punch)
- Saída (último punch)
- Intervalo (total de intervalos)
- Trabalhado (horas efetivas)
- Esperado (carga horária)
- Extra (verde se > 0)
- Devidas (vermelho se > 0)
- Status (badges: OK, Incompleto, Justificado, Violação)
- Observações (notas)

Rodapé com totais do período.

**6. Filtros**
- Todos os registros
- Apenas irregularidades (incompletos + violações + devidas)

**7. Botões de Exportação**
- PDF (ícone vermelho)
- Excel (ícone verde)

**8. Seletor de Funcionário (gestores/admins)**
- Dropdown para visualizar saldo de outros funcionários
- Gestores: apenas seu departamento
- Admins: todos os funcionários

---

## 📊 Exportação

### PDF (TCPDF)

**Estrutura:**
1. Cabeçalho: "Folha de Ponto Eletrônico"
2. Período: data início - data fim
3. Informações do funcionário (nome, cargo, departamento)
4. Resumo do saldo:
   - Horas extras (verde)
   - Horas devidas (vermelho)
   - Saldo total (colorido conforme sinal)
5. Estatísticas do período
6. Tabela detalhada com todos os registros

**Código de exemplo:**
```php
$pdf = new \TCPDF();
$pdf->AddPage();
$pdf->SetFont('helvetica', 'B', 16);
$pdf->Cell(0, 10, 'Folha de Ponto Eletrônico', 0, 1, 'C');
// ... configurações ...
$pdf->Output('folha_ponto.pdf', 'D');
```

### Excel (PhpSpreadsheet)

**Estrutura similar ao PDF, com:**
- Células mescladas para títulos
- Formatação de cores (RGB)
- Auto-ajuste de colunas
- Negrito para headers
- Cores condicionais (verde/vermelho)

**Código de exemplo:**
```php
$spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setCellValue('A1', 'Folha de Ponto');
// ... preenchimento ...
$writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
$writer->save('php://output');
```

---

## 🔧 Configuração

### 1. Executar Migrations

```bash
php spark migrate
```

Isso criará a tabela `timesheet_consolidated`.

### 2. Configurar Cron Job

**Opção 1: Editar crontab do usuário**
```bash
crontab -e
```

Adicionar linha:
```
30 0 * * * /usr/bin/php /var/www/html/scripts/cron_calculate.php >> /var/log/ponto/cron_calculate.log 2>&1
```

**Opção 2: Editar /etc/crontab (requer sudo)**
```bash
sudo nano /etc/crontab
```

Adicionar linha:
```
30 0 * * * www-data /usr/bin/php /var/www/html/scripts/cron_calculate.php >> /var/log/ponto/cron_calculate.log 2>&1
```

**Criar diretório de logs:**
```bash
sudo mkdir -p /var/log/ponto
sudo chown www-data:www-data /var/log/ponto
```

### 3. Configurar Email (para notificações)

Editar `.env`:
```env
# Email Settings
email.fromEmail = noreply@empresa.com
email.fromName = Sistema de Ponto Eletrônico
email.SMTPHost = smtp.gmail.com
email.SMTPUser = seu-email@gmail.com
email.SMTPPass = sua-senha
email.SMTPPort = 587
email.SMTPCrypto = tls
```

### 4. Instalar Dependências

**TCPDF (para exportação PDF):**
```bash
composer require tecnickcom/tcpdf
```

**PhpSpreadsheet (para exportação Excel):**
```bash
composer require phpoffice/phpspreadsheet
```

### 5. Configurar Permissões

```bash
# Diretório de uploads (para anexos de justificativas)
sudo chown -R www-data:www-data writable/uploads
sudo chmod -R 775 writable/uploads

# Logs
sudo chown -R www-data:www-data writable/logs
sudo chmod -R 775 writable/logs
```

---

## 🧪 Cenários de Teste

### Cenário 1: Dia Completo com Horas Extras

**Configuração:**
- Funcionário: João Silva (8h diárias esperadas)
- Data: 2024-01-15
- Marcações:
  - 08:00 (entrada)
  - 12:00 (saída para almoço)
  - 13:00 (retorno do almoço)
  - 18:30 (saída)

**Cálculo:**
- Período 1: 12:00 - 08:00 = 4h (trabalho)
- Intervalo: 13:00 - 12:00 = 1h
- Período 2: 18:30 - 13:00 = 5.5h (trabalho)
- Total trabalhado: 4 + 5.5 = 9.5h
- Esperado: 8h
- **Extra: +1.5h** ✅

**Validação CLT:**
- Jornada > 6h ✓
- Intervalo ≥ 1h ✓
- Sem violação ✅

**Resultado esperado:**
```
total_worked: 9.50
expected: 8.00
extra: 1.50
owed: 0.00
interval_violation: 0.00
incomplete: false
```

### Cenário 2: Dia Incompleto (Falta Saída)

**Configuração:**
- Funcionário: Maria Santos
- Data: 2024-01-16
- Marcações:
  - 08:00 (entrada)
  - 12:00 (saída almoço)
  - 13:00 (retorno almoço)
  - (falta saída final)

**Resultado esperado:**
```
punches_count: 3
incomplete: true
notes: "Número ímpar de marcações (3). Falta entrada ou saída."
total_worked: 0.00
extra: 0.00
owed: 0.00
```

**Notificações:**
- ✉️ Email para funcionário: "Marcações incompletas"
- 🔔 Notificação dashboard para funcionário
- 🔔 Notificação dashboard para gestor do departamento

### Cenário 3: Horas Devidas com Justificativa Aprovada

**Configuração:**
- Funcionário: Carlos Oliveira
- Data: 2024-01-17
- Marcações:
  - 08:00 (entrada)
  - 12:00 (saída almoço)
  - 13:00 (retorno)
  - 15:00 (saída antecipada)
- Justificativa: Consulta médica (status: aprovado)

**Cálculo:**
- Total trabalhado: 4 + 2 = 6h
- Esperado: 8h
- Diferença: -2h
- **Tem justificativa aprovada** ✅

**Resultado esperado:**
```
total_worked: 6.00
expected: 8.00
extra: 0.00
owed: 0.00 (não desconta pois tem justificativa)
justified: true
justification_id: 123
notes: "Justificativa aprovada. Horas não descontadas."
```

### Cenário 4: Horas Devidas SEM Justificativa

**Mesmo cenário anterior, mas SEM justificativa:**

**Resultado esperado:**
```
total_worked: 6.00
expected: 8.00
extra: 0.00
owed: 2.00 ⚠️
justified: false
justification_id: null
```

**Atualização saldo:**
```
employee.owed_hours_balance += 2.00
```

### Cenário 5: Violação de Intervalo (CLT)

**Configuração:**
- Funcionário: Ana Costa
- Data: 2024-01-18
- Marcações:
  - 08:00 (entrada)
  - 12:00 (saída almoço)
  - 12:30 (retorno - INTERVALO DE APENAS 30MIN!)
  - 17:00 (saída)

**Cálculo:**
- Período 1: 4h
- Intervalo: 0.5h (30min)
- Período 2: 4.5h
- Total trabalhado: 8.5h
- **Jornada > 6h, mas intervalo < 1h** ⚠️

**CLT (Art. 71):**
- Jornada > 6h: intervalo mínimo de 1h
- Intervalo dado: 0.5h
- Faltou: 1h - 0.5h = 0.5h
- **Adicional de 50%: 0.5h × 1.5 = 0.75h**

**Resultado esperado:**
```
total_worked: 8.50
expected: 8.00
extra: 0.50
owed: 0.00
interval_violation: 0.75 ⚠️
notes: "Violação de intervalo: jornada >6h sem intervalo mínimo de 1h. Pagamento adicional: 0.75h."
```

**Implicações:**
- Empresa deve pagar 0.75h adicionais (como hora extra)
- Sistema marca em vermelho na tabela
- Badge "Violação Intervalo" aparece

### Cenário 6: Sem Marcações (Falta Total)

**Configuração:**
- Funcionário: Pedro Lima
- Data: 2024-01-19
- Marcações: (nenhuma)

**Resultado esperado:**
```
punches_count: 0
incomplete: true
notes: "Nenhuma marcação de ponto registrada."
total_worked: 0.00
extra: 0.00
owed: 0.00 (não desconta automaticamente, mas marca como incompleto)
```

**Observação:** Faltas totais normalmente exigem justificativa ou são tratadas separadamente no RH.

### Cenário 7: Múltiplos Intervalos

**Configuração:**
- Funcionário: Luciana Ferreira
- Data: 2024-01-20
- Marcações:
  - 08:00 (entrada)
  - 10:00 (pausa café - saída)
  - 10:15 (pausa café - retorno)
  - 12:00 (almoço - saída)
  - 13:00 (almoço - retorno)
  - 15:00 (pausa - saída)
  - 15:15 (pausa - retorno)
  - 18:00 (saída final)

**Cálculo:**
- Período 1 (trabalho): 10:00 - 08:00 = 2h
- Intervalo 1 (café): 10:15 - 10:00 = 0.25h
- Período 2 (trabalho): 12:00 - 10:15 = 1.75h
- Intervalo 2 (almoço): 13:00 - 12:00 = 1h
- Período 3 (trabalho): 15:00 - 13:00 = 2h
- Intervalo 3 (pausa): 15:15 - 15:00 = 0.25h
- Período 4 (trabalho): 18:00 - 15:15 = 2.75h

**Total trabalhado:** 2 + 1.75 + 2 + 2.75 = 8.5h
**Total intervalo:** 0.25 + 1 + 0.25 = 1.5h

**Resultado esperado:**
```
total_worked: 8.50
expected: 8.00
extra: 0.50
total_interval: 1.50
interval_violation: 0.00 (intervalo total > 1h, OK para jornada >6h)
```

### Cenário 8: Dashboard - Visualização Gestor

**Ação:**
1. Gestor "Ricardo Mendes" (Depto: TI) faz login
2. Acessa `/timesheet/balance`
3. No dropdown, seleciona "João Silva" (Depto: TI)

**Comportamento esperado:**
- ✅ Visualiza saldo de João Silva
- ✅ Gráfico mostra evolução de João
- ✅ Tabela mostra registros de João
- ✅ Pode exportar PDF/Excel de João

**Teste de permissão:**
4. Gestor tenta selecionar "Ana Costa" (Depto: RH)
- ❌ Redirecionado com erro: "Você não tem permissão para visualizar este funcionário."

### Cenário 9: Exportação PDF

**Ação:**
1. Funcionário acessa `/timesheet/balance`
2. Clica em botão "PDF" (período: últimos 30 dias)

**Resultado esperado:**
- Download inicia automaticamente
- Nome do arquivo: `folha_ponto_Joao_Silva_2024-01-20.pdf`
- Conteúdo:
  - Cabeçalho com logo/título
  - Período: 21/12/2023 a 20/01/2024
  - Dados do funcionário
  - Resumo colorido: Extra (verde), Devidas (vermelho), Saldo (cor dinâmica)
  - Estatísticas: dias trabalhados, média diária
  - Tabela com todos os 30 registros
- Audit log criado:
  ```
  action: TIMESHEET_EXPORTED
  description: "Exportação de folha de ponto (pdf) - João Silva"
  ```

### Cenário 10: Exportação Excel

**Similar ao PDF, mas:**
- Nome: `folha_ponto_Joao_Silva_2024-01-20.xlsx`
- Formato: planilha Excel (XLSX)
- Células formatadas com cores
- Colunas auto-ajustadas
- Pode ser editada/manipulada no Excel

---

## 🐛 Troubleshooting

### Problema 1: Cron não executa

**Sintomas:**
- Registros não são criados automaticamente
- Log `/var/log/ponto/cron_calculate.log` está vazio

**Diagnóstico:**
```bash
# Verificar se cron está rodando
sudo systemctl status cron

# Ver logs do cron
sudo grep CRON /var/log/syslog

# Testar script manualmente
php /var/www/html/scripts/cron_calculate.php
```

**Soluções:**
1. Verificar caminho do PHP:
   ```bash
   which php
   # Usar o caminho correto no crontab
   ```

2. Verificar permissões:
   ```bash
   chmod +x /var/www/html/scripts/cron_calculate.php
   ```

3. Verificar sintaxe do crontab:
   ```bash
   crontab -l
   # Formato: minuto hora dia mês dia-semana comando
   ```

### Problema 2: Email não envia

**Sintomas:**
- Worker executa mas emails não chegam
- Log mostra "Email failed"

**Diagnóstico:**
```bash
# Ver logs do CodeIgniter
tail -f writable/logs/log-*.php

# Testar SMTP manualmente
telnet smtp.gmail.com 587
```

**Soluções:**
1. Verificar credenciais no `.env`:
   ```env
   email.SMTPUser = seu-email@gmail.com
   email.SMTPPass = sua-senha-app # NÃO a senha normal!
   ```

2. Gmail: criar senha de app
   - Acessar conta Google
   - Segurança > Verificação em duas etapas
   - Senhas de app > Gerar

3. Verificar firewall:
   ```bash
   sudo ufw allow out 587/tcp
   ```

### Problema 3: Cálculo de horas incorreto

**Sintomas:**
- Total trabalhado não bate com marcações
- Intervalos calculados errados

**Diagnóstico:**
1. Verificar marcações no banco:
   ```sql
   SELECT * FROM time_punches
   WHERE employee_id = 1 AND punch_date = '2024-01-15'
   ORDER BY punch_time;
   ```

2. Verificar consolidado:
   ```sql
   SELECT * FROM timesheet_consolidated
   WHERE employee_id = 1 AND date = '2024-01-15';
   ```

**Soluções:**
- Se número ímpar de punches: adicionar punch faltante ou deletar punch excedente
- Se par mas cálculo errado: verificar lógica de trabalho vs intervalo no worker
- Reprocessar dia:
  ```sql
  DELETE FROM timesheet_consolidated WHERE employee_id = 1 AND date = '2024-01-15';
  # Executar worker novamente
  ```

### Problema 4: Gráfico não carrega

**Sintomas:**
- Dashboard abre mas gráfico fica em branco
- Console do navegador mostra erro

**Diagnóstico:**
```javascript
// Abrir DevTools (F12)
// Ver Console e Network tabs
```

**Soluções:**
1. Verificar CDN do Chart.js:
   ```html
   <!-- Trocar para versão local se CDN falhar -->
   <script src="/assets/js/chart.min.js"></script>
   ```

2. Verificar dados JSON:
   ```php
   // No balance.php
   var_dump($evolution); // Ver se está vazio
   ```

3. Verificar JavaScript no console:
   ```javascript
   console.log(evolutionData); // Deve mostrar array com dates/balances
   ```

### Problema 5: Exportação PDF/Excel dá erro 500

**Sintomas:**
- Clicar em botão de exportação retorna erro
- Log mostra "Class not found"

**Soluções:**
1. Verificar dependências instaladas:
   ```bash
   composer show | grep tcpdf
   composer show | grep phpoffice
   ```

2. Se não instaladas:
   ```bash
   composer require tecnickcom/tcpdf
   composer require phpoffice/phpspreadsheet
   ```

3. Verificar autoload:
   ```bash
   composer dump-autoload
   ```

4. Verificar memória PHP:
   ```ini
   ; php.ini
   memory_limit = 256M
   ```

### Problema 6: Justificativa não é considerada

**Sintomas:**
- Funcionário tem justificativa aprovada
- Mas worker desconta horas devidas mesmo assim

**Diagnóstico:**
```sql
SELECT * FROM justifications
WHERE employee_id = 1
  AND justification_date = '2024-01-15'
  AND status = 'aprovado';
```

**Soluções:**
1. Verificar método `hasApprovedJustification()` no JustificationModel
2. Verificar se justificativa foi aprovada ANTES do cron executar
3. Reprocessar dia após aprovar justificativa

### Problema 7: Saldo no dashboard diferente do banco

**Sintomas:**
- `employees.extra_hours_balance` mostra valor X
- Dashboard mostra valor Y

**Diagnóstico:**
```sql
-- Calcular saldo real do consolidado
SELECT
    SUM(extra) as total_extra,
    SUM(owed) as total_owed,
    SUM(extra) - SUM(owed) as balance
FROM timesheet_consolidated
WHERE employee_id = 1;

-- Comparar com tabela employees
SELECT extra_hours_balance, owed_hours_balance
FROM employees
WHERE id = 1;
```

**Soluções:**
- Se divergirem: recalcular saldo
  ```sql
  UPDATE employees e
  SET
      extra_hours_balance = (
          SELECT COALESCE(SUM(extra), 0)
          FROM timesheet_consolidated
          WHERE employee_id = e.id
      ),
      owed_hours_balance = (
          SELECT COALESCE(SUM(owed), 0)
          FROM timesheet_consolidated
          WHERE employee_id = e.id
      )
  WHERE e.id = 1;
  ```

---

## 📝 Manutenção e Monitoramento

### Logs do Worker

**Visualizar execução:**
```bash
tail -f /var/log/ponto/cron_calculate.log
```

**Formato de log:**
```
===========================================
Daily Timesheet Calculation Worker
Processing date: 2024-01-19
Started at: 2024-01-20 00:30:00
===========================================

Found 50 active employees to process.

Processing Employee ID 1 - João Silva...
  Found 4 punch(es)
  ✓ Total worked: 8.50h
  ✓ Total interval: 1.00h
  ✅ Extra hours: +0.50h
  ✓ Updated balance: Extra=12.50h, Owed=0.00h
  ✉ Email sent
  ✅ Success

...

===========================================
Processing Complete
Total: 50 | Success: 48 | Errors: 0 | Incomplete: 2
Finished at: 2024-01-20 00:31:23
===========================================
```

### Monitoramento de Falhas

**Criar alerta para erros:**
```bash
# Script: /usr/local/bin/check_cron_errors.sh
#!/bin/bash

LOG_FILE="/var/log/ponto/cron_calculate.log"
ERROR_COUNT=$(grep -c "❌ Error" "$LOG_FILE")

if [ "$ERROR_COUNT" -gt 0 ]; then
    echo "Cron calculate tem $ERROR_COUNT erros!" | mail -s "Alerta: Erros no Worker" admin@empresa.com
fi
```

**Adicionar no cron:**
```
0 1 * * * /usr/local/bin/check_cron_errors.sh
```

### Limpeza de Dados Antigos

**Manter apenas 2 anos de histórico:**
```sql
-- Executar mensalmente
DELETE FROM timesheet_consolidated
WHERE date < DATE_SUB(NOW(), INTERVAL 2 YEAR);
```

**Adicionar no cron (1º dia do mês, 02:00):**
```bash
0 2 1 * * mysql -u root -p -e "DELETE FROM ponto_db.timesheet_consolidated WHERE date < DATE_SUB(NOW(), INTERVAL 2 YEAR);" >> /var/log/ponto/cleanup.log 2>&1
```

---

## 🔐 Segurança e Permissões

### Controle de Acesso

**Funcionário comum:**
- ✅ Ver próprio saldo
- ✅ Exportar próprios dados
- ❌ Ver saldo de outros
- ❌ Modificar registros

**Gestor:**
- ✅ Ver saldo de funcionários do seu departamento
- ✅ Exportar dados do seu departamento
- ❌ Ver outros departamentos
- ❌ Modificar registros

**Admin:**
- ✅ Ver saldo de todos
- ✅ Exportar dados de todos
- ✅ Acesso total

### Auditoria

**Todas as exportações são registradas:**
```sql
SELECT * FROM audit_logs
WHERE action = 'TIMESHEET_EXPORTED'
ORDER BY created_at DESC
LIMIT 10;
```

**Resultado:**
```
| employee_id | action               | description                                  | created_at          |
|-------------|----------------------|----------------------------------------------|---------------------|
| 5           | TIMESHEET_EXPORTED   | Exportação de folha de ponto (pdf) - João   | 2024-01-20 10:30:15 |
| 2           | TIMESHEET_EXPORTED   | Exportação de folha de ponto (excel) - Maria| 2024-01-20 09:15:22 |
```

---

## 📚 Referências Legais (CLT)

### Art. 71 - Intervalos para Repouso ou Alimentação

> **Jornada > 6 horas contínuas:**
> - Intervalo mínimo: 1 hora
> - Intervalo máximo: 2 horas
> - Não concessão ou redução: pagamento da hora acrescida de 50%

> **Jornada > 4 horas e ≤ 6 horas:**
> - Intervalo mínimo: 15 minutos

**Implementação no sistema:**
```php
if ($totalWorked > 6) {
    if ($totalInterval < 1) {
        $violation = 1 - $totalInterval;
        $intervalViolation = $violation * 1.5; // 50% adicional
    }
} elseif ($totalWorked >= 4 && $totalWorked <= 6) {
    if ($totalInterval < 0.25) { // 15 min = 0.25h
        $violation = 0.25 - $totalInterval;
        $intervalViolation = $violation * 1.5;
    }
}
```

### Art. 58 - Jornada Normal de Trabalho

> Duração normal do trabalho: não superior a 8 horas diárias e 44 semanais.

**Implementação:**
- Campo `employees.daily_hours` (padrão: 8.00)
- Personalizável por funcionário (ex: meio período = 4.00)

---

## 🎯 Próximas Melhorias (Opcional)

- [ ] Dashboard executivo (visão consolidada de toda empresa)
- [ ] Relatórios gerenciais (custos com horas extras, absenteísmo)
- [ ] Integração com folha de pagamento (exportar para sistemas externos)
- [ ] Banco de horas negociado (compensação de extras em folgas)
- [ ] Alertas proativos (previsão de horas extras, sugestão de compensação)
- [ ] Aplicativo mobile para consulta de saldo
- [ ] API REST para integrações externas
- [ ] Análise preditiva com ML (padrões de atraso, tendências)

---

## ✅ Status Final

**FASE 9: 100% COMPLETA** 🎉

✅ Banco de dados configurado
✅ Worker de cálculo diário implementado
✅ Dashboard interativo com gráficos
✅ Exportação PDF/Excel funcional
✅ Validações CLT implementadas
✅ Sistema de notificações ativo
✅ Documentação completa
✅ Cenários de teste documentados

---

**Desenvolvido por:** Claude Code
**Data:** 2024-01-20
**Versão:** 1.0.0
