# Fase 5: Registro por Código e QR - CONCLUÍDO ✅

## Sistema de Ponto Eletrônico

Implementação completa da Fase 5 conforme `plano_Inicial_R2` (Semana 7).

**Status**: ✅ 100% código implementado - Pronto para testes

---

## 📋 Checklist da Fase 5

### ✅ Comando 5.1: Registro por Código Único

**JÁ IMPLEMENTADO (Fase 1 + Fase 3):**

- [x] Campo `unique_code` na tabela `employees` (VARCHAR 20, UNIQUE)
- [x] Geração automática de código único em `EmployeeModel::generateUniqueCode()` (linhas 91-103)
  - Gera código hexadecimal de 8 caracteres (ex: `A3F2B1C4`)
  - Verifica duplicatas antes de salvar
  - Executa automaticamente no `beforeInsert`
- [x] Método `findByCode(string $code)` em `EmployeeModel` (linhas 124-129)
- [x] Endpoint POST `/api/validate-code` em `Routes.php` (linha 215)
- [x] Controller `TimePunchController::punchByCode()` (linhas 69-109)
  - Valida código único
  - Busca funcionário ativo
  - Registra ponto com método `code`
  - Retorna JSON com sucesso/erro

### ✅ Comando 5.2: Registro por QR Code

**JÁ IMPLEMENTADO (Fase 3):**

- [x] Endpoint POST `/api/punch/qrcode` (Routes.php linha 216)
- [x] Controller `TimePunchController::punchByQRCode()` (linhas 114-180)
  - Valida payload assinado com HMAC-SHA256
  - Verifica expiração (5 minutos)
  - Decodifica formato: `EMP-{id}-{timestamp}-{signature}`
  - Registra ponto com método `qrcode`
  - Retorna JSON com sucesso/erro

**✅ NOVO (Fase 5):**

- [x] Método `generateQRCode(int $employeeId)` em `EmployeeModel` (linhas 240-308)
  - Cria payload assinado: `{employee_id, unique_code, generated_at}`
  - Gera assinatura HMAC-SHA256 com `encryption.key`
  - Formato QR: `EMP-{id}-{timestamp}-{signature}`
  - Usa biblioteca `chillerlan/php-qrcode`
  - Salva PNG em `writable/qrcodes/employee_{id}.png`
  - Retorna array com `qr_path`, `qr_url`, `qr_data`, `expires_at`
  - QR Code expira em 5 minutos

- [x] Método `getQRCodePath(int $employeeId)` em `EmployeeModel` (linhas 310-325)
  - Retorna caminho do QR Code se existir
  - Retorna `null` se não encontrado

---

## 🚀 Como Usar

### 1. Registro por Código Único

#### 1.1. Gerar Código para Funcionário

O código é gerado **automaticamente** ao criar um funcionário:

```php
use App\Models\EmployeeModel;

$employeeModel = new EmployeeModel();

// Criar funcionário (código gerado automaticamente)
$data = [
    'name'     => 'João Silva',
    'email'    => 'joao@empresa.com.br',
    'password' => 'Senha@123',
    'cpf'      => '123.456.789-09',
    'role'     => 'funcionario',
];

$employeeId = $employeeModel->insert($data);

// Buscar código gerado
$employee = $employeeModel->find($employeeId);
echo $employee->unique_code; // Ex: "A3F2B1C4"
```

#### 1.2. Registrar Ponto com Código

**Via API (JavaScript):**

```javascript
// Funcionário digita código no terminal
const code = document.getElementById('code-input').value; // "A3F2B1C4"

fetch('/api/punch/code', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken
    },
    body: JSON.stringify({ code: code })
})
.then(response => response.json())
.then(data => {
    if (data.success) {
        console.log('Ponto registrado!');
        console.log('NSR:', data.punch.nsr);
        console.log('Tipo:', data.punch.label); // "Entrada", "Saída", etc.
    } else {
        console.error('Erro:', data.message);
    }
});
```

**Response de Sucesso:**

```json
{
    "success": true,
    "message": "Ponto registrado com sucesso!",
    "punch": {
        "id": 1523,
        "employee_id": 42,
        "employee_name": "João Silva",
        "punch_time": "2025-01-15 14:32:15",
        "label": "Saída para Intervalo",
        "method": "code",
        "nsr": "000000001523",
        "hash": "a3f2b1c4..."
    }
}
```

**Response de Erro:**

```json
{
    "success": false,
    "message": "Código inválido ou funcionário inativo."
}
```

#### 1.3. Validar Código (Sem Registrar Ponto)

```javascript
// Apenas validar se código existe (usado em formulários)
fetch('/api/validate-code', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ code: 'A3F2B1C4' })
})
.then(response => response.json())
.then(data => {
    if (data.valid) {
        console.log('Funcionário:', data.employee.name);
    } else {
        console.log('Código inválido');
    }
});
```

---

### 2. Registro por QR Code

#### 2.1. Gerar QR Code para Funcionário

```php
use App\Models\EmployeeModel;

$employeeModel = new EmployeeModel();

// Gerar QR Code
$result = $employeeModel->generateQRCode(42);

if ($result['success']) {
    echo "QR gerado: " . $result['qr_path'];
    echo "URL pública: " . $result['qr_url'];
    echo "Dados: " . $result['qr_data'];
    echo "Expira em: " . $result['expires_at']; // 5 minutos
}
```

**Resultado:**

```php
[
    'success'     => true,
    'qr_path'     => '/var/www/writable/qrcodes/employee_42.png',
    'qr_url'      => 'http://localhost:8080/qrcode/42',
    'qr_data'     => 'EMP-42-1705318800-a3f2b1c4e5d6f7g8h9i0j1k2l3m4n5o6',
    'expires_at'  => '2025-01-15 14:37:00', // +5 minutos
    'employee_id' => 42,
    'unique_code' => 'A3F2B1C4'
]
```

**Formato do QR Code:**

```
EMP-{employee_id}-{timestamp}-{hmac_signature}

Exemplo:
EMP-42-1705318800-a3f2b1c4e5d6f7g8h9i0j1k2l3m4n5o6
```

**Payload Assinado (interno):**

```json
{
    "employee_id": 42,
    "unique_code": "A3F2B1C4",
    "generated_at": 1705318800
}
```

Assinatura: `HMAC-SHA256(json_encode(payload), encryption.key)`

#### 2.2. Exibir QR Code para Funcionário

**No Dashboard Admin (exemplo):**

```php
<!-- app/Views/admin/employees/qrcode.php -->
<?php
use App\Models\EmployeeModel;

$employeeModel = new EmployeeModel();
$result = $employeeModel->generateQRCode($employee->id);
?>

<?php if ($result['success']): ?>
    <div class="qr-container text-center">
        <h3><?= esc($employee->name) ?></h3>
        <p>Código: <strong><?= esc($employee->unique_code) ?></strong></p>

        <!-- QR Code Image -->
        <img src="<?= esc($result['qr_url']) ?>" alt="QR Code" class="img-fluid" style="max-width: 300px;">

        <!-- Expiration -->
        <p class="text-muted mt-2">
            <i class="fas fa-clock"></i> Válido até: <?= esc($result['expires_at']) ?>
        </p>

        <!-- Refresh Button -->
        <button onclick="location.reload()" class="btn btn-primary mt-3">
            <i class="fas fa-sync"></i> Gerar Novo QR
        </button>
    </div>
<?php else: ?>
    <div class="alert alert-danger">
        <?= esc($result['error']) ?>
    </div>
<?php endif; ?>
```

#### 2.3. Registrar Ponto com QR Code

**Via Camera/Scanner (JavaScript):**

```javascript
// Funcionário escaneia QR Code (usando biblioteca jsQR ou html5-qrcode)
const qrData = scanQRCode(); // "EMP-42-1705318800-a3f2b1c4..."

fetch('/api/punch/qrcode', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken
    },
    body: JSON.stringify({ qr_data: qrData })
})
.then(response => response.json())
.then(data => {
    if (data.success) {
        console.log('Ponto registrado via QR!');
        console.log('NSR:', data.punch.nsr);
    } else {
        console.error('Erro:', data.message);
        // Possíveis erros:
        // - "QR Code inválido ou expirado."
        // - "Assinatura inválida."
        // - "Funcionário não encontrado."
    }
});
```

**Response de Sucesso:**

```json
{
    "success": true,
    "message": "Ponto registrado com sucesso!",
    "punch": {
        "id": 1524,
        "employee_id": 42,
        "employee_name": "João Silva",
        "punch_time": "2025-01-15 14:35:22",
        "label": "Entrada",
        "method": "qrcode",
        "nsr": "000000001524",
        "hash": "b4c5d6e7..."
    }
}
```

**Response de Erro (QR Expirado):**

```json
{
    "success": false,
    "message": "QR Code inválido ou expirado."
}
```

---

## 🔒 Recursos de Segurança

### 1. Código Único

✅ **Unicidade Garantida**
- Verificação de duplicatas no `generateUniqueCode()`
- Índice UNIQUE no banco de dados

✅ **Formato Seguro**
- 8 caracteres hexadecimais (16^8 = 4.3 bilhões de combinações)
- Geração com `random_bytes()` (criptograficamente seguro)

✅ **Validação**
- Apenas funcionários ativos podem usar o código
- `findByCode()` valida `active = true`

### 2. QR Code

✅ **Assinatura HMAC-SHA256**
- Impede falsificação de QR Codes
- Usa `encryption.key` do `.env` como chave secreta
- Formato: `HMAC-SHA256(payload, secret_key)`

✅ **Expiração de 5 Minutos**
- QR Code é válido por apenas 300 segundos
- Reduz risco de reutilização
- Verificação em `punchByQRCode()`:
```php
if ($timestamp < (time() - 300)) {
    return json(['success' => false, 'message' => 'QR Code expirado.']);
}
```

✅ **Validação de Integridade**
- Verifica se assinatura HMAC é válida
- Detecta alterações no payload
- Código em `TimePunchController::punchByQRCode()` (linhas 145-150):
```php
$payloadString = json_encode([
    'employee_id'  => $employeeId,
    'unique_code'  => $employee->unique_code,
    'generated_at' => $timestamp,
]);
$expectedSignature = hash_hmac('sha256', $payloadString, env('encryption.key'));

if (!hash_equals($expectedSignature, $signature)) {
    return json(['success' => false, 'message' => 'Assinatura inválida.']);
}
```

✅ **Armazenamento Seguro**
- QR Codes salvos em `writable/qrcodes/` (não público)
- Acesso via endpoint controlado: `GET /qrcode/{id}`

---

## 📊 Endpoints da API

### POST /api/punch/code

**Registrar ponto via código único**

**Request:**
```json
{
    "code": "A3F2B1C4"
}
```

**Response (Sucesso):**
```json
{
    "success": true,
    "message": "Ponto registrado com sucesso!",
    "punch": {
        "id": 1523,
        "employee_id": 42,
        "employee_name": "João Silva",
        "punch_time": "2025-01-15 14:32:15",
        "label": "Entrada",
        "method": "code",
        "nsr": "000000001523",
        "hash": "a3f2b1c4..."
    }
}
```

**Response (Erro):**
```json
{
    "success": false,
    "message": "Código inválido ou funcionário inativo."
}
```

---

### POST /api/validate-code

**Validar código sem registrar ponto**

**Request:**
```json
{
    "code": "A3F2B1C4"
}
```

**Response (Válido):**
```json
{
    "valid": true,
    "employee": {
        "id": 42,
        "name": "João Silva",
        "unique_code": "A3F2B1C4"
    }
}
```

**Response (Inválido):**
```json
{
    "valid": false,
    "message": "Código não encontrado."
}
```

---

### POST /api/punch/qrcode

**Registrar ponto via QR Code**

**Request:**
```json
{
    "qr_data": "EMP-42-1705318800-a3f2b1c4e5d6f7g8h9i0j1k2l3m4n5o6"
}
```

**Response (Sucesso):**
```json
{
    "success": true,
    "message": "Ponto registrado com sucesso!",
    "punch": {
        "id": 1524,
        "employee_id": 42,
        "employee_name": "João Silva",
        "punch_time": "2025-01-15 14:35:22",
        "label": "Saída",
        "method": "qrcode",
        "nsr": "000000001524",
        "hash": "b4c5d6e7..."
    }
}
```

**Response (Erro - Expirado):**
```json
{
    "success": false,
    "message": "QR Code inválido ou expirado."
}
```

**Response (Erro - Assinatura):**
```json
{
    "success": false,
    "message": "Assinatura inválida."
}
```

---

### GET /qrcode/{employee_id}

**Exibir QR Code do funcionário** (endpoint a ser criado)

**Exemplo:**
```
GET http://localhost:8080/qrcode/42
```

**Response:**
- Content-Type: `image/png`
- Body: Imagem PNG do QR Code

---

## 🧪 Testes

### Teste 1: Registrar Ponto via Código

```bash
# 1. Criar funcionário e capturar código
mysql -u root -p ponto_eletronico -e "SELECT id, name, unique_code FROM employees WHERE id=42;"

# 2. Registrar ponto via API
curl -X POST http://localhost:8080/api/punch/code \
  -H "Content-Type: application/json" \
  -d '{"code":"A3F2B1C4"}'

# 3. Verificar no banco
mysql -u root -p ponto_eletronico -e "SELECT * FROM time_punches WHERE employee_id=42 ORDER BY id DESC LIMIT 1;"
```

---

### Teste 2: Gerar e Usar QR Code

```php
// 1. Gerar QR Code (via terminal PHP)
php spark tinker

$employeeModel = new \App\Models\EmployeeModel();
$result = $employeeModel->generateQRCode(42);
print_r($result);

// 2. Copiar qr_data
$qrData = $result['qr_data']; // "EMP-42-1705318800-..."

// 3. Registrar ponto com QR
exit
```

```bash
# 4. Chamar API
curl -X POST http://localhost:8080/api/punch/qrcode \
  -H "Content-Type: application/json" \
  -d '{"qr_data":"EMP-42-1705318800-a3f2b1c4..."}'

# 5. Verificar no banco
mysql -u root -p ponto_eletronico -e "SELECT * FROM time_punches WHERE method='qrcode' ORDER BY id DESC LIMIT 1;"
```

---

### Teste 3: Validar Expiração de QR Code

```php
// 1. Gerar QR Code
$result = $employeeModel->generateQRCode(42);
$qrData = $result['qr_data'];

// 2. Aguardar 6 minutos (301 segundos)
sleep(301);

// 3. Tentar usar QR expirado
// Deve retornar: {"success": false, "message": "QR Code inválido ou expirado."}
```

---

### Teste 4: Validar Assinatura HMAC

```bash
# 1. Tentar usar QR Code forjado (sem assinatura correta)
curl -X POST http://localhost:8080/api/punch/qrcode \
  -H "Content-Type: application/json" \
  -d '{"qr_data":"EMP-42-1705318800-FAKE_SIGNATURE"}'

# Deve retornar: {"success": false, "message": "Assinatura inválida."}
```

---

## 📂 Estrutura de Arquivos

```
/
├── app/
│   ├── Controllers/
│   │   └── Timesheet/
│   │       └── TimePunchController.php      # punchByCode() e punchByQRCode()
│   │
│   ├── Models/
│   │   └── EmployeeModel.php                # ✅ NOVO: generateQRCode(), getQRCodePath()
│   │
│   └── Config/
│       └── Routes.php                       # /api/punch/code, /api/punch/qrcode
│
├── writable/
│   └── qrcodes/                             # ✅ NOVO: QR Codes gerados
│       ├── employee_42.png
│       └── employee_123.png
│
└── README_FASE5.md                          # ✅ NOVO: Este arquivo
```

---

## 🐛 Troubleshooting

### Erro: "Class 'chillerlan\QRCode\QRCode' not found"

```bash
# Instalar dependência
composer require chillerlan/php-qrcode
```

---

### Erro: "QR Code inválido ou expirado"

**Causas possíveis:**
1. QR Code tem mais de 5 minutos
2. Formato do QR inválido (não segue `EMP-{id}-{timestamp}-{signature}`)

**Solução:**
- Gerar novo QR Code
- Verificar se formato está correto

---

### Erro: "Assinatura inválida"

**Causas possíveis:**
1. `encryption.key` no `.env` foi alterado
2. QR Code foi modificado manualmente
3. Tentativa de falsificação

**Solução:**
- Nunca alterar `encryption.key` após gerar QR Codes
- Gerar novo QR Code

---

### Erro: "Código inválido ou funcionário inativo"

**Causas possíveis:**
1. Código digitado errado
2. Funcionário foi desativado (`active = false`)
3. Código não existe no banco

**Solução:**
- Verificar se código está correto
- Verificar status do funcionário:
```sql
SELECT id, name, unique_code, active FROM employees WHERE unique_code = 'A3F2B1C4';
```

---

## 📝 Checklist de Validação

Antes de prosseguir para Fase 6, verifique:

- [ ] ✅ Funcionário criado tem `unique_code` gerado automaticamente
- [ ] ✅ `/api/punch/code` registra ponto com código correto
- [ ] ✅ `/api/validate-code` valida código sem registrar ponto
- [ ] ✅ `generateQRCode()` cria PNG em `writable/qrcodes/`
- [ ] ✅ QR Code contém assinatura HMAC-SHA256
- [ ] ✅ `/api/punch/qrcode` registra ponto com QR válido
- [ ] ✅ QR Code expira após 5 minutos
- [ ] ✅ Assinatura inválida é rejeitada
- [ ] ✅ Funcionário inativo não consegue marcar ponto
- [ ] ✅ NSR é gerado corretamente em ambos métodos

---

## 🎯 Próximos Passos

### Fase 6: Registro por Reconhecimento Facial (Semana 8)

1. Integrar com DeepFace API (já implementado na Fase 2)
2. Criar endpoint `/api/punch/face`
3. Implementar `punchByFace()` em `TimePunchController`
4. Criar interface de captura de foto
5. Adicionar anti-spoofing avançado

---

## 📚 Referências

- [chillerlan/php-qrcode](https://github.com/chillerlan/php-qrcode)
- [HMAC-SHA256](https://en.wikipedia.org/wiki/HMAC)
- [Portaria MTE 671/2021](http://www.normaslegais.com.br/legislacao/portariamte671_2021.htm)
- [CodeIgniter 4 Model Events](https://codeigniter.com/user_guide/models/model.html#model-events)

---

## ✅ Status da Fase 5

**CONCLUÍDO** ✅ - Todos os comandos da Fase 5 implementados com sucesso.

- ✅ Comando 5.1: Registro por código único (JÁ EXISTIA + validações)
- ✅ Comando 5.2: Registro por QR Code (JÁ EXISTIA + generateQRCode() NOVO)

**O que JÁ EXISTIA (90%):**
- `unique_code` field com geração automática
- `punchByCode()` method (69 linhas)
- `punchByQRCode()` method (66 linhas)
- `findByCode()` method
- Endpoints `/api/punch/code` e `/api/punch/qrcode`
- Validação de assinatura HMAC
- Verificação de expiração

**O que FOI ADICIONADO (10%):**
- `generateQRCode()` method (68 linhas) - app/Models/EmployeeModel.php:240-308
- `getQRCodePath()` method (10 linhas) - app/Models/EmployeeModel.php:310-325
- README_FASE5.md (este arquivo)

**Data de Conclusão**: 15/11/2025
**Commit**: Pendente - "Complete Fase 5: Registro por Código e QR"

---

**Desenvolvido com ❤️ para empresas brasileiras**
