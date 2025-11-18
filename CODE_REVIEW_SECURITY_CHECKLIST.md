# 🔍 Checklist de Code Review com Foco em Segurança
## Sistema de Registro de Ponto Eletrônico

**Versão:** 1.0
**Data:** 18/11/2024
**Objetivo:** Garantir que todo código novo/modificado atenda aos padrões de segurança

---

## 📋 Índice

1. [Como Usar Este Checklist](#como-usar-este-checklist)
2. [Checklist Geral](#checklist-geral)
3. [Autenticação e Autorização](#autenticação-e-autorização)
4. [Validação de Entrada](#validação-de-entrada)
5. [Proteção de Saída](#proteção-de-saída)
6. [Acesso a Dados](#acesso-a-dados)
7. [Criptografia](#criptografia)
8. [Gerenciamento de Sessões](#gerenciamento-de-sessões)
9. [Tratamento de Erros](#tratamento-de-erros)
10. [Logging e Auditoria](#logging-e-auditoria)
11. [APIs e Integrações](#apis-e-integrações)
12. [File Upload](#file-upload)
13. [Configurações e Deployment](#configurações-e-deployment)
14. [Performance e DoS](#performance-e-dos)
15. [LGPD e Privacy](#lgpd-e-privacy)
16. [Red Flags Críticos](#red-flags-críticos)

---

## 📖 Como Usar Este Checklist

### Para o Desenvolvedor (Antes de Criar PR)

1. **Auto-Review:** Passe por todos os itens aplicáveis ao seu código
2. **Marque itens:** Use ✅ para conformes, ❌ para não conformes, N/A para não aplicáveis
3. **Documente:** Justifique decisões de segurança no PR description
4. **Teste:** Execute testes de segurança relevantes

### Para o Reviewer

1. **Priorize segurança:** Itens de segurança têm prioridade sobre estilo
2. **Verifique todos os ✅:** Confirme que o desenvolvedor não marcou falsamente
3. **Bloqueie merges:** Se houver ❌ em itens críticos (marcados com 🔴)
4. **Eduque:** Explique o "porquê" das mudanças solicitadas

### Níveis de Severidade

- 🔴 **CRÍTICO:** Bloqueia merge imediatamente
- 🟠 **ALTO:** Deve ser corrigido antes do merge
- 🟡 **MÉDIO:** Deve ser corrigido ou justificado
- 🟢 **BAIXO:** Recomendação, pode ser tratado depois

---

## ✅ Checklist Geral

### Princípios Fundamentais

- [ ] **🔴 Least Privilege:** Código solicita apenas permissões necessárias
- [ ] **🔴 Defense in Depth:** Múltiplas camadas de proteção implementadas
- [ ] **🔴 Fail Secure:** Em caso de erro, sistema falha de forma segura (nega acesso)
- [ ] **🟠 Security by Design:** Segurança considerada desde o início, não adicionada depois
- [ ] **🟡 Separation of Concerns:** Lógica de negócio separada de lógica de segurança

### Code Quality

- [ ] **🟡 DRY:** Não há duplicação de código de segurança
- [ ] **🟡 SOLID:** Princípios SOLID aplicados (facilitam auditorias)
- [ ] **🟢 Clean Code:** Código legível e bem documentado
- [ ] **🟢 Comments:** Decisões de segurança documentadas em comentários

---

## 🔐 Autenticação e Autorização

### Autenticação

- [ ] **🔴 Senhas Fortes:** Requisitos mínimos aplicados (12+ chars, maiúscula, minúscula, número, especial)
  ```php
  // ✅ CORRETO
  $rules = ['password' => 'required|min_length[12]|regex_match[/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&#])/]'];

  // ❌ ERRADO
  $rules = ['password' => 'required|min_length[6]'];
  ```

- [ ] **🔴 Password Hashing:** Usa `password_hash()` com bcrypt (cost >= 12)
  ```php
  // ✅ CORRETO
  $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

  // ❌ ERRADO
  $hash = md5($password);
  $hash = sha1($password);
  $hash = hash('sha256', $password);
  ```

- [ ] **🔴 Brute Force Protection:** Limita tentativas de login (5 tentativas, 15 min bloqueio)
  ```php
  // ✅ CORRETO
  if ($this->isBruteForceBlocked($email)) {
      return $this->fail('Muitas tentativas. Tente em 15 minutos.');
  }
  ```

- [ ] **🟠 Session Regeneration:** Session ID regenerado após login/privilege change
  ```php
  // ✅ CORRETO
  $this->session->regenerate();

  // ❌ ERRADO - vulnerável a session fixation
  // (não regenerar sessão)
  ```

- [ ] **🟠 Remember Me:** Se implementado, usa padrão selector/verifier com hash
  ```php
  // ✅ CORRETO
  $selector = bin2hex(random_bytes(16));
  $verifier = bin2hex(random_bytes(32));
  $hash = hash('sha256', $verifier);
  // Salva $selector e $hash no banco

  // ❌ ERRADO
  setcookie('remember', $userId, time() + 3600*24*30);
  ```

### Autorização

- [ ] **🔴 IDOR Prevention:** Verifica propriedade do recurso antes de permitir acesso
  ```php
  // ✅ CORRETO
  $timesheet = $this->timesheetModel->find($id);
  if ($timesheet->employee_id !== session('user_id')) {
      return redirect()->back()->with('error', 'Acesso negado');
  }

  // ❌ ERRADO
  $timesheet = $this->timesheetModel->find($id);
  // Permite acesso sem verificar propriedade
  ```

- [ ] **🔴 Role-Based Access:** Verifica role do usuário antes de operações sensíveis
  ```php
  // ✅ CORRETO
  if (!in_array(session('user_role'), ['admin', 'gestor'])) {
      return $this->failUnauthorized('Permissão insuficiente');
  }

  // ❌ ERRADO
  if (session('user_role') != 'admin') {
      // Permite gestores sem verificar
  }
  ```

- [ ] **🔴 Authorization em TODAS as operações:** CREATE, READ, UPDATE, DELETE todas verificadas
  ```php
  // ✅ CORRETO - Verifica em todas as operações
  public function view($id) { $this->checkOwnership($id); }
  public function update($id) { $this->checkOwnership($id); }
  public function delete($id) { $this->checkOwnership($id); }

  // ❌ ERRADO - Esquece de verificar no update
  public function update($id) {
      $this->timesheetModel->update($id, $data); // SEM verificação
  }
  ```

- [ ] **🟠 Fail Closed:** Se verificação de permissão falhar, acesso é NEGADO (não permitido)
  ```php
  // ✅ CORRETO
  if (!$this->hasPermission($resource)) {
      return $this->failUnauthorized(); // NEGA por padrão
  }

  // ❌ ERRADO
  if ($this->hasPermission($resource)) {
      // Permite
  } else {
      log_message('warning', 'Sem permissão'); // Mas continua execução!
  }
  ```

### Multi-Factor Authentication (Se aplicável)

- [ ] **🟡 MFA Implementation:** 2FA implementado para admins/operações sensíveis
- [ ] **🟡 Backup Codes:** Códigos de recuperação gerados e armazenados com segurança

---

## 🛡️ Validação de Entrada

### Princípio: NUNCA confie em input do usuário

- [ ] **🔴 Whitelist over Blacklist:** Valida o que é permitido, não o que é proibido
  ```php
  // ✅ CORRETO (whitelist)
  $allowedRoles = ['admin', 'gestor', 'funcionario'];
  if (!in_array($role, $allowedRoles)) {
      return $this->fail('Role inválida');
  }

  // ❌ ERRADO (blacklist)
  $forbiddenRoles = ['root', 'superadmin'];
  if (in_array($role, $forbiddenRoles)) {
      return $this->fail('Role proibida');
  }
  ```

- [ ] **🔴 Tipo de Dados:** Valida tipo correto (int, string, email, date, etc.)
  ```php
  // ✅ CORRETO
  $id = (int) $this->request->getGet('id');
  if ($id <= 0) {
      return $this->fail('ID inválido');
  }

  // ❌ ERRADO
  $id = $this->request->getGet('id'); // Aceita qualquer tipo
  ```

- [ ] **🔴 Tamanho/Range:** Limita tamanho de strings e range de números
  ```php
  // ✅ CORRETO
  $rules = [
      'name' => 'required|min_length[3]|max_length[100]',
      'age'  => 'required|integer|greater_than[0]|less_than[150]',
  ];

  // ❌ ERRADO
  $rules = ['name' => 'required']; // Sem limite
  ```

- [ ] **🔴 Format Validation:** Valida formato (email, date, phone, CPF, etc.)
  ```php
  // ✅ CORRETO
  $rules = [
      'email' => 'required|valid_email',
      'date'  => 'required|valid_date[Y-m-d]',
      'cpf'   => 'required|exact_length[11]|numeric',
  ];
  ```

- [ ] **🟠 Business Logic Validation:** Valida regras de negócio
  ```php
  // ✅ CORRETO
  if ($endDate < $startDate) {
      return $this->fail('Data final deve ser maior que inicial');
  }

  if ($requestedHours > 24) {
      return $this->fail('Horas não podem exceder 24');
  }
  ```

- [ ] **🟠 File Upload Validation:** Valida tipo MIME, extensão e tamanho
  ```php
  // ✅ CORRETO
  $file = $this->request->getFile('upload');
  $finfo = finfo_open(FILEINFO_MIME_TYPE);
  $mimeType = finfo_file($finfo, $file->getTempName());

  $allowedMimes = ['image/jpeg', 'image/png', 'application/pdf'];
  if (!in_array($mimeType, $allowedMimes)) {
      return $this->fail('Tipo de arquivo não permitido');
  }

  // ❌ ERRADO
  if ($file->getExtension() != 'jpg') { // Confia na extensão
      return $this->fail('Apenas JPG');
  }
  ```

- [ ] **🟡 Sanitização:** Sanitiza input quando apropriado (mas não substitui validação!)
  ```php
  // ✅ CORRETO
  $name = strip_tags(trim($this->request->getPost('name')));

  // ❌ ERRADO
  $html = $this->request->getPost('content'); // HTML sem sanitização
  ```

---

## 🚫 Proteção de Saída

### XSS Prevention

- [ ] **🔴 Output Escaping:** SEMPRE escapa output em views
  ```php
  <!-- ✅ CORRETO -->
  <p><?= esc($userName) ?></p>
  <input value="<?= esc($userInput, 'attr') ?>">

  <!-- ❌ ERRADO -->
  <p><?= $userName ?></p>
  <p><?php echo $userName; ?></p>
  ```

- [ ] **🔴 Context-Aware Escaping:** Usa contexto correto (html, attr, js, url, css)
  ```php
  <!-- ✅ CORRETO -->
  <div data-name="<?= esc($name, 'attr') ?>"></div>
  <script>var name = <?= json_encode($name) ?>;</script>
  <a href="<?= esc($url, 'url') ?>">Link</a>

  <!-- ❌ ERRADO -->
  <script>var name = "<?= esc($name) ?>";</script> <!-- Contexto errado -->
  ```

- [ ] **🔴 Content-Type Headers:** Define Content-Type correto
  ```php
  // ✅ CORRETO
  return $this->response
      ->setContentType('application/json')
      ->setJSON($data);

  // ❌ ERRADO
  echo json_encode($data); // Sem Content-Type
  ```

- [ ] **🟠 Content Security Policy:** CSP headers configurados
  ```php
  // ✅ CORRETO (em SecurityHeadersFilter)
  $response->setHeader("Content-Security-Policy",
      "default-src 'self'; script-src 'self'; object-src 'none';"
  );
  ```

### Open Redirect Prevention

- [ ] **🔴 Redirect Validation:** Valida URLs de redirecionamento
  ```php
  // ✅ CORRETO
  $redirectUrl = $this->request->getGet('redirect');
  if (!$this->isValidRedirectUrl($redirectUrl)) {
      $redirectUrl = '/dashboard'; // Fallback seguro
  }
  return redirect()->to($redirectUrl);

  // ❌ ERRADO
  return redirect()->to($this->request->getGet('redirect')); // Confia cegamente
  ```

---

## 💾 Acesso a Dados

### SQL Injection Prevention

- [ ] **🔴 Prepared Statements:** SEMPRE usa prepared statements/query builder
  ```php
  // ✅ CORRETO
  $results = $this->db->table('employees')
      ->where('id', $id)
      ->get()
      ->getResult();

  // Ou
  $query = $this->db->query(
      "SELECT * FROM employees WHERE id = ?",
      [$id]
  );

  // ❌ ERRADO
  $query = $this->db->query("SELECT * FROM employees WHERE id = $id");
  $query = $this->db->query("SELECT * FROM employees WHERE name = '$name'");
  ```

- [ ] **🔴 Query Builder:** Prefere Query Builder sobre SQL raw
  ```php
  // ✅ CORRETO
  $this->db->table('timesheets')
      ->where('employee_id', $employeeId)
      ->where('date >=', $startDate)
      ->where('date <=', $endDate)
      ->get();

  // ❌ ERRADO
  $sql = "SELECT * FROM timesheets WHERE employee_id = ? AND date >= ? AND date <= ?";
  // Query Builder é mais seguro e legível
  ```

- [ ] **🔴 Dynamic Queries:** Se usar SQL dinâmico, valida TUDO
  ```php
  // ✅ CORRETO
  $allowedColumns = ['name', 'email', 'created_at'];
  $orderBy = $this->request->getGet('order_by');

  if (!in_array($orderBy, $allowedColumns)) {
      $orderBy = 'id'; // Fallback seguro
  }

  $this->db->table('employees')->orderBy($orderBy);

  // ❌ ERRADO
  $orderBy = $this->request->getGet('order_by');
  $this->db->query("SELECT * FROM employees ORDER BY $orderBy");
  ```

- [ ] **🟠 Escaping:** Se absolutamente necessário usar escape, usa método correto
  ```php
  // ✅ CORRETO (mas evite, use prepared statements)
  $escaped = $this->db->escape($userInput);

  // ❌ ERRADO
  $escaped = addslashes($userInput); // Não suficiente
  $escaped = mysqli_real_escape_string($userInput); // Não compatível com Query Builder
  ```

### Database Best Practices

- [ ] **🟠 Least Privilege:** Usuário do banco tem apenas permissões necessárias
  ```sql
  -- ✅ CORRETO
  GRANT SELECT, INSERT, UPDATE ON app_db.* TO 'app_user'@'localhost';

  -- ❌ ERRADO
  GRANT ALL PRIVILEGES ON *.* TO 'app_user'@'%';
  ```

- [ ] **🟠 Transações:** Operações relacionadas em transação
  ```php
  // ✅ CORRETO
  $this->db->transStart();
  try {
      $this->db->table('accounts')->where('id', $from)->decrement('balance', $amount);
      $this->db->table('accounts')->where('id', $to)->increment('balance', $amount);
      $this->db->transComplete();
  } catch (\Exception $e) {
      $this->db->transRollback();
      throw $e;
  }
  ```

- [ ] **🟡 Connection Pooling:** Não abre conexões desnecessárias

---

## 🔐 Criptografia

### Dados em Repouso

- [ ] **🔴 Sensitive Data Encryption:** Dados sensíveis criptografados (biometria, documentos, etc.)
  ```php
  // ✅ CORRETO
  $encrypted = encrypt_biometric_data($template, env('ENCRYPTION_KEY'));
  $this->db->table('biometric_templates')->insert([
      'template_data' => $encrypted,
  ]);

  // ❌ ERRADO
  $this->db->table('biometric_templates')->insert([
      'template_data' => json_encode($template), // Plaintext!
  ]);
  ```

- [ ] **🔴 Strong Algorithms:** Usa algoritmos fortes (AES-256-CBC ou superior)
  ```php
  // ✅ CORRETO
  openssl_encrypt($data, 'aes-256-cbc', $key, 0, $iv);

  // ❌ ERRADO
  openssl_encrypt($data, 'des', $key); // DES é fraco
  base64_encode($data); // NÃO é criptografia!
  ```

- [ ] **🔴 Key Management:** Chaves nunca hardcoded, sempre em .env
  ```php
  // ✅ CORRETO
  $key = env('ENCRYPTION_KEY');

  // ❌ ERRADO
  $key = 'my_secret_key_12345'; // Hardcoded!
  ```

- [ ] **🟠 Unique IVs:** IV (Initialization Vector) randômico por registro
  ```php
  // ✅ CORRETO
  $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
  $encrypted = openssl_encrypt($data, 'aes-256-cbc', $key, 0, $iv);
  // Salva IV junto com encrypted data

  // ❌ ERRADO
  $iv = '1234567890123456'; // IV fixo
  ```

- [ ] **🟠 HMAC/Signature:** Adiciona HMAC para integridade
  ```php
  // ✅ CORRETO
  $encrypted = encrypt($data);
  $hmac = hash_hmac('sha256', $encrypted, $key);
  $final = $encrypted . '::' . $hmac;

  // Na leitura, verifica HMAC antes de descriptografar
  ```

### Dados em Trânsito

- [ ] **🔴 HTTPS Only:** Força HTTPS em produção
  ```php
  // ✅ CORRETO (em App.php)
  public bool $forceGlobalSecureRequests = true;

  // ❌ ERRADO
  public bool $forceGlobalSecureRequests = false;
  ```

- [ ] **🔴 Secure Cookies:** Cookies com flag Secure em produção
  ```php
  // ✅ CORRETO
  public bool $cookieSecure = (ENVIRONMENT === 'production');

  // ❌ ERRADO
  public bool $cookieSecure = false;
  ```

- [ ] **🟠 TLS 1.2+:** Servidor configurado para TLS 1.2 ou superior (verificar config Apache/Nginx)

### Random Number Generation

- [ ] **🔴 Cryptographically Secure:** Usa funções criptograficamente seguras
  ```php
  // ✅ CORRETO
  $token = bin2hex(random_bytes(32));

  // ❌ ERRADO
  $token = md5(uniqid()); // NÃO é seguro
  $token = rand(1000, 9999); // NÃO é seguro
  ```

---

## 🍪 Gerenciamento de Sessões

### Session Security

- [ ] **🔴 Session Regeneration:** Regenera session ID após login/privilege change
  ```php
  // ✅ CORRETO
  $this->session->regenerate();
  ```

- [ ] **🔴 Session Timeout:** Timeout configurado (ex: 2 horas)
  ```php
  // ✅ CORRETO (em App.php)
  public int $sessionExpiration = 7200; // 2 horas
  ```

- [ ] **🔴 Secure Cookie Flags:** HttpOnly, Secure (prod), SameSite=Strict
  ```php
  // ✅ CORRETO (em App.php)
  public bool $cookieHTTPOnly = true;
  public bool $cookieSecure = (ENVIRONMENT === 'production');
  public ?string $cookieSameSite = 'Strict';
  ```

- [ ] **🟠 Session Storage:** Sessões armazenadas de forma segura
  ```php
  // ✅ CORRETO
  public string $sessionDriver = 'CodeIgniter\\Session\\Handlers\\DatabaseHandler';
  // Ou FileHandler com permissões corretas (700)

  // ❌ ERRADO - Evitar
  // Session em cache compartilhado sem proteção
  ```

- [ ] **🟠 Match IP:** Considera validar IP da sessão (cuidado com proxies legítimos)
  ```php
  // ✅ CORRETO (opcional, cuidado com proxies)
  public bool $sessionMatchIP = true;

  // 🟡 ALTERNATIVA
  // Validar mudanças suspeitas de IP e exigir re-autenticação
  ```

### CSRF Protection

- [ ] **🔴 CSRF Tokens:** Token CSRF em todos os formulários state-changing
  ```php
  <!-- ✅ CORRETO -->
  <form method="POST">
      <?= csrf_field() ?>
      <!-- campos -->
  </form>

  <!-- ❌ ERRADO -->
  <form method="POST">
      <!-- Sem token CSRF -->
  </form>
  ```

- [ ] **🔴 SameSite Cookies:** SameSite=Strict ou Lax
  ```php
  // ✅ CORRETO
  public ?string $cookieSameSite = 'Strict';
  ```

- [ ] **🟠 Verify CSRF:** Verifica token no servidor
  ```php
  // ✅ CORRETO (CodeIgniter faz automaticamente se CSRF filter ativo)
  // Mas se validar manualmente:
  if (!$this->request->getPost(csrf_token()) === csrf_hash()) {
      return $this->fail('Token CSRF inválido');
  }
  ```

---

## ⚠️ Tratamento de Erros

### Error Handling

- [ ] **🔴 Produção vs Desenvolvimento:** Comportamento diferente por ambiente
  ```php
  // ✅ CORRETO
  if (ENVIRONMENT === 'production') {
      // Mensagem genérica
      return $this->fail('Erro interno. Contate o suporte.');
  } else {
      // Detalhes para debug
      return $this->fail('Database error: ' . $e->getMessage());
  }
  ```

- [ ] **🔴 No Stack Traces em Produção:** Stack traces desabilitados
  ```php
  // ✅ CORRETO (em .env)
  CI_ENVIRONMENT = production

  // Em php.ini ou .htaccess
  display_errors = Off
  log_errors = On
  ```

- [ ] **🔴 Mensagens Genéricas:** Erros não revelam detalhes técnicos
  ```php
  // ✅ CORRETO
  return $this->fail('E-mail ou senha inválidos'); // Genérico

  // ❌ ERRADO
  return $this->fail('E-mail não encontrado'); // Revela que email não existe
  return $this->fail('Senha incorreta'); // Revela que email existe
  return $this->fail('Query failed: SELECT * FROM users WHERE...'); // SQL exposto
  ```

- [ ] **🟠 Sensitive Data in Exceptions:** Dados sensíveis em `sensitiveDataInTrace`
  ```php
  // ✅ CORRETO (em Exceptions.php)
  public array $sensitiveDataInTrace = [
      'password', 'token', 'api_key', 'biometric_data', ...
  ];
  ```

- [ ] **🟠 Error Logging:** Erros logados (mas não exibidos)
  ```php
  // ✅ CORRETO
  try {
      // operação
  } catch (\Exception $e) {
      log_message('error', $e->getMessage());
      return $this->fail('Erro ao processar requisição');
  }
  ```

### HTTP Status Codes

- [ ] **🟡 Códigos Corretos:** Usa status HTTP apropriado
  ```php
  // ✅ CORRETO
  return $this->respond($data, 200);           // Success
  return $this->failUnauthorized();             // 401
  return $this->failForbidden();                // 403
  return $this->failNotFound();                 // 404
  return $this->failValidationErrors($errors);  // 422
  return $this->failServerError();              // 500

  // ❌ ERRADO
  return $this->respond(['error' => 'Não autorizado'], 200); // Status errado
  ```

---

## 📊 Logging e Auditoria

### Audit Logging

- [ ] **🔴 Security Events:** Eventos de segurança são logados
  ```php
  // ✅ CORRETO - Eventos que DEVEM ser logados:
  // - Login bem-sucedido
  // - Login falhado
  // - Logout
  // - Mudanças de senha
  // - Mudanças de privilégios/roles
  // - Acesso negado (403)
  // - Criação/edição/exclusão de dados sensíveis
  // - Exceções de segurança

  $this->auditModel->log(
      $userId,
      'LOGIN',
      'employees',
      $userId,
      null,
      ['ip' => get_client_ip(), 'user_agent' => get_user_agent()],
      'Login bem-sucedido',
      'info'
  );
  ```

- [ ] **🔴 Sanitização de Logs:** Dados sensíveis NUNCA em logs
  ```php
  // ✅ CORRETO
  log_message('info', 'User logged in: ' . sanitize_for_log($email));

  // Ou use helper
  safe_log('info', 'Password changed for user ' . $userId);

  // ❌ ERRADO
  log_message('info', 'Login attempt: ' . $email . ' / ' . $password); // SENHA NO LOG!
  log_message('info', 'Biometric data: ' . json_encode($template)); // Dado sensível!
  ```

- [ ] **🟠 Sufficient Context:** Logs incluem contexto suficiente
  ```php
  // ✅ CORRETO
  log_message('warning', sprintf(
      'Failed login attempt for email %s from IP %s (attempt %d/5)',
      sanitize_for_log($email),
      get_client_ip(),
      $attemptCount
  ));

  // ❌ ERRADO
  log_message('warning', 'Login failed'); // Sem contexto
  ```

- [ ] **🟠 Log Injection Prevention:** Sanitiza newlines e caracteres especiais
  ```php
  // ✅ CORRETO
  function sanitize_for_log(string $data): string {
      return str_replace(["\n", "\r", "\0"], '', $data);
  }
  ```

### Log Storage

- [ ] **🟡 Log Rotation:** Logs são rotacionados/arquivados
- [ ] **🟡 Log Retention:** Política de retenção definida (ex: 90 dias)
- [ ] **🟡 Centralized Logging:** Logs centralizados (ELK, Sentry, etc.)

---

## 🔌 APIs e Integrações

### API Security

- [ ] **🔴 Authentication:** Todas as APIs exigem autenticação
  ```php
  // ✅ CORRETO
  if (!$this->authenticate()) {
      return $this->failUnauthorized('Token inválido ou ausente');
  }
  ```

- [ ] **🔴 Authorization:** Verifica permissões por endpoint
  ```php
  // ✅ CORRETO
  if (!$this->hasPermission('employees.read')) {
      return $this->failForbidden();
  }
  ```

- [ ] **🔴 Rate Limiting:** Limita requests por IP/usuário
  ```php
  // ✅ CORRETO
  if ($this->isRateLimited($userId)) {
      return $this->failTooManyRequests('Limite excedido. Tente em 60 segundos.');
  }
  ```

- [ ] **🟠 CORS Configuration:** CORS configurado corretamente (não '*' em produção)
  ```php
  // ✅ CORRETO
  $response->setHeader('Access-Control-Allow-Origin', 'https://app.example.com');

  // ❌ ERRADO
  $response->setHeader('Access-Control-Allow-Origin', '*'); // Muito permissivo
  ```

- [ ] **🟠 Input Validation:** Valida JSON/XML de entrada
  ```php
  // ✅ CORRETO
  $data = $this->request->getJSON();
  if (!$this->validate($rules)) {
      return $this->failValidationErrors($this->validator->getErrors());
  }
  ```

- [ ] **🟡 API Versioning:** API versionada (v1, v2, etc.)
  ```php
  // ✅ CORRETO
  Route::group('api/v1', function($routes) {
      // endpoints
  });
  ```

### External Integrations

- [ ] **🔴 Validate Responses:** Valida respostas de APIs externas
  ```php
  // ✅ CORRETO
  $response = $httpClient->post($url, $data);
  if ($response->getStatusCode() !== 200) {
      throw new \Exception('API externa falhou');
  }

  $body = json_decode($response->getBody(), true);
  if (!isset($body['data'])) {
      throw new \Exception('Resposta inválida da API');
  }
  ```

- [ ] **🔴 Timeouts:** Define timeouts para requests externos
  ```php
  // ✅ CORRETO
  $httpClient = \Config\Services::curlrequest([
      'timeout' => 10, // 10 segundos
  ]);
  ```

- [ ] **🟠 SSL Verification:** Verifica certificados SSL
  ```php
  // ✅ CORRETO
  $httpClient = \Config\Services::curlrequest([
      'verify' => true, // Verifica SSL
  ]);

  // ❌ ERRADO
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Perigoso!
  ```

---

## 📁 File Upload

### Upload Security

- [ ] **🔴 MIME Validation:** Valida MIME type com `finfo_file()`
  ```php
  // ✅ CORRETO
  $finfo = finfo_open(FILEINFO_MIME_TYPE);
  $mimeType = finfo_file($finfo, $file->getTempName());

  $allowedMimes = ['image/jpeg', 'image/png', 'application/pdf'];
  if (!in_array($mimeType, $allowedMimes)) {
      return $this->fail('Tipo de arquivo não permitido');
  }
  finfo_close($finfo);

  // ❌ ERRADO
  $extension = $file->getClientExtension(); // Confia no cliente
  if ($extension !== 'jpg') {
      return $this->fail('Apenas JPG');
  }
  ```

- [ ] **🔴 Extension Whitelist:** Verifica extensão contra whitelist
  ```php
  // ✅ CORRETO
  $allowedExtensions = ['jpg', 'jpeg', 'png', 'pdf'];
  $extension = strtolower($file->getClientExtension());

  if (!in_array($extension, $allowedExtensions)) {
      return $this->fail('Extensão não permitida');
  }
  ```

- [ ] **🔴 File Size Limit:** Limita tamanho do arquivo
  ```php
  // ✅ CORRETO
  $maxSize = 5 * 1024 * 1024; // 5MB
  if ($file->getSize() > $maxSize) {
      return $this->fail('Arquivo muito grande (máx 5MB)');
  }
  ```

- [ ] **🔴 Rename Files:** Renomeia arquivo com nome seguro
  ```php
  // ✅ CORRETO
  $newName = bin2hex(random_bytes(16)) . '.' . $extension;
  $file->move(WRITEPATH . 'uploads', $newName);

  // ❌ ERRADO
  $file->move(WRITEPATH . 'uploads', $file->getClientName()); // Nome do cliente!
  ```

- [ ] **🔴 Storage Outside Webroot:** Arquivos salvos fora do webroot
  ```php
  // ✅ CORRETO
  $uploadPath = WRITEPATH . 'uploads/'; // Fora do webroot

  // ❌ ERRADO
  $uploadPath = FCPATH . 'public/uploads/'; // Dentro do webroot (executável!)
  ```

- [ ] **🟠 Image Verification:** Para imagens, usa `getimagesize()`
  ```php
  // ✅ CORRETO
  if (strpos($mimeType, 'image/') === 0) {
      $imageInfo = getimagesize($file->getTempName());
      if ($imageInfo === false) {
          return $this->fail('Arquivo não é uma imagem válida');
      }
  }
  ```

- [ ] **🟠 Virus Scan:** Considera integração com antivírus (ClamAV)
  ```php
  // ✅ RECOMENDADO
  if (!$this->scanForVirus($file->getTempName())) {
      return $this->fail('Arquivo rejeitado pela verificação de segurança');
  }
  ```

---

## ⚙️ Configurações e Deployment

### Configuration

- [ ] **🔴 Secrets in .env:** NUNCA hardcode secrets
  ```php
  // ✅ CORRETO
  $apiKey = env('API_KEY');
  $dbPassword = env('database.default.password');

  // ❌ ERRADO
  $apiKey = 'sk_live_abc123'; // Hardcoded!
  ```

- [ ] **🔴 .env not in Git:** .env no .gitignore
  ```bash
  # ✅ CORRETO (.gitignore)
  .env
  .env.*
  !.env.example
  ```

- [ ] **🔴 Debug OFF em Produção:** Debug desabilitado
  ```php
  // ✅ CORRETO (em .env de produção)
  CI_ENVIRONMENT = production
  ```

- [ ] **🟠 Strong Encryption Key:** Chave de criptografia forte e única
  ```bash
  # ✅ CORRETO
  php spark key:generate

  # Ou
  openssl rand -hex 32
  ```

### File Permissions

- [ ] **🟠 Correct Permissions:** Permissões corretas de arquivos
  ```bash
  # ✅ CORRETO
  chmod 644 .env          # Apenas leitura para owner
  chmod 755 writable/     # Escrita para aplicação
  chmod 644 *.php         # Arquivos PHP não executáveis via shell

  # ❌ ERRADO
  chmod 777 writable/     # Muito permissivo
  chmod 666 .env          # Permite escrita para todos
  ```

### Headers

- [ ] **🟠 Security Headers:** Headers de segurança aplicados
  ```php
  // ✅ CORRETO (em SecurityHeadersFilter)
  $response->setHeader('X-Frame-Options', 'DENY');
  $response->setHeader('X-Content-Type-Options', 'nosniff');
  $response->setHeader('Referrer-Policy', 'no-referrer');
  $response->setHeader('Permissions-Policy', 'geolocation=(), microphone=()');
  ```

- [ ] **🟠 Remove Identifying Headers:** Remove headers que identificam tecnologia
  ```php
  // ✅ CORRETO
  $response->removeHeader('Server');
  $response->removeHeader('X-Powered-By');
  ```

---

## 🚦 Performance e DoS

### Denial of Service Prevention

- [ ] **🟠 Input Size Limits:** Limita tamanho de requisições
  ```php
  // ✅ CORRETO
  if ($this->request->getBody() > 1024 * 1024) { // 1MB
      return $this->fail('Requisição muito grande');
  }
  ```

- [ ] **🟠 Pagination:** Implementa paginação em listagens
  ```php
  // ✅ CORRETO
  $perPage = min((int)$this->request->getGet('per_page'), 100); // Máx 100
  $results = $this->model->paginate($perPage);

  // ❌ ERRADO
  $results = $this->model->findAll(); // Todos os registros sem limite
  ```

- [ ] **🟠 Query Complexity:** Limita complexidade de queries
  ```php
  // ✅ CORRETO
  if (count($filters) > 10) {
      return $this->fail('Muitos filtros aplicados');
  }

  // Limita JOINs
  if ($includeRelations && count($includeRelations) > 5) {
      return $this->fail('Máximo 5 relações podem ser incluídas');
  }
  ```

- [ ] **🟡 Caching:** Implementa cache onde apropriado
  ```php
  // ✅ CORRETO
  $cacheKey = 'report_' . md5(serialize($filters));
  if (!$report = cache($cacheKey)) {
      $report = $this->generateReport($filters);
      cache()->save($cacheKey, $report, 3600); // 1 hora
  }
  ```

### Resource Management

- [ ] **🟡 Connection Limits:** Não abre conexões desnecessárias
- [ ] **🟡 Memory Management:** Limpa recursos não utilizados
  ```php
  // ✅ CORRETO
  unset($largeArray); // Libera memória
  gc_collect_cycles(); // Force garbage collection se necessário
  ```

---

## 🔒 LGPD e Privacy

### Data Privacy

- [ ] **🔴 Data Minimization:** Coleta apenas dados necessários
  ```php
  // ✅ CORRETO
  $allowedFields = ['name', 'email', 'phone']; // Apenas o necessário

  // ❌ ERRADO
  // Coletar CPF, RG, endereço completo sem necessidade
  ```

- [ ] **🔴 Consent:** Obtém consentimento antes de coletar dados sensíveis
  ```php
  // ✅ CORRETO
  if (!$employee->biometric_consent) {
      return $this->fail('Consentimento para biometria não foi dado');
  }
  ```

- [ ] **🔴 Right to Erasure:** Implementa direito ao esquecimento
  ```php
  // ✅ CORRETO
  public function deleteMyData() {
      // Anonimiza ou deleta dados pessoais
      $this->employeeModel->anonymizeEmployee($userId);
      $this->biometricModel->deleteByEmployee($userId);
  }
  ```

- [ ] **🟠 Data Retention:** Deleta/arquiva dados após período
  ```php
  // ✅ CORRETO
  // Cron job para deletar logs antigos
  $this->auditModel->deleteOlderThan(90); // 90 dias
  ```

- [ ] **🟠 Encryption of PII:** Dados pessoais identificáveis criptografados
  ```php
  // ✅ CORRETO
  $this->biometricModel->insert([
      'template_data' => encrypt_biometric_data($template, $key),
  ]);
  ```

- [ ] **🟡 Privacy Policy:** Link para política de privacidade visível
- [ ] **🟡 Data Export:** Usuário pode exportar seus dados
  ```php
  // ✅ CORRETO
  public function exportMyData() {
      $data = $this->employeeModel->getAllDataFor($userId);
      return $this->response->download('my_data.json', json_encode($data));
  }
  ```

---

## 🚨 Red Flags Críticos

### ⛔ NUNCA Fazer

- [ ] **❌ Hardcoded Credentials:** Senhas, tokens, chaves em código
- [ ] **❌ SQL Concatenation:** Concatenar strings em SQL
- [ ] **❌ eval() / exec():** Executar código dinâmico
  ```php
  // ❌ NUNCA
  eval($userInput);
  exec($userInput);
  system($userInput);
  shell_exec($userInput);
  ```

- [ ] **❌ Unserialize User Input:** Deserializar dados não confiáveis
  ```php
  // ❌ NUNCA
  $data = unserialize($_GET['data']); // Object injection!

  // ✅ Use JSON
  $data = json_decode($this->request->getGet('data'), true);
  ```

- [ ] **❌ extract() em Input:** Usar extract() em dados do usuário
  ```php
  // ❌ NUNCA
  extract($_POST); // Pode sobrescrever variáveis!

  // ✅ Acesse diretamente
  $name = $this->request->getPost('name');
  ```

- [ ] **❌ Disable SSL Verification:** Desabilitar verificação SSL
  ```php
  // ❌ NUNCA
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
  ```

- [ ] **❌ Weak Hashing:** MD5, SHA1 para senhas
- [ ] **❌ `register_globals`:** Usar register_globals (obsoleto)

---

## 📝 Template de PR Description

Use este template ao criar Pull Requests:

```markdown
## Descrição
[Descreva o que foi implementado/corrigido]

## Tipo de Mudança
- [ ] Bug fix
- [ ] Nova feature
- [ ] Refatoração
- [ ] Atualização de documentação
- [ ] Correção de segurança

## Checklist de Segurança
- [ ] Código validado contra checklist de segurança
- [ ] Todos os inputs são validados
- [ ] Todos os outputs são escapados
- [ ] Prepared statements em queries
- [ ] Autorização verificada em endpoints
- [ ] Dados sensíveis não em logs
- [ ] Testes de segurança passaram
- [ ] Sem hardcoded secrets

## Testes Realizados
- [ ] Testes unitários
- [ ] Testes de integração
- [ ] Testes de segurança (especificar quais)
- [ ] Teste manual

## Screenshots (se aplicável)
[Adicionar screenshots]

## Notas Adicionais
[Qualquer informação adicional]
```

---

## 🎓 Recursos de Aprendizado

### Documentação Oficial
- **OWASP Top 10:** https://owasp.org/www-project-top-ten/
- **OWASP Cheat Sheets:** https://cheatsheetseries.owasp.org/
- **CodeIgniter Security:** https://codeigniter.com/user_guide/concepts/security.html
- **PHP Security:** https://www.php.net/manual/en/security.php

### Ferramentas de Análise
- **PHPStan:** Análise estática de código
- **Psalm:** Análise estática focada em tipos
- **RIPS:** Scanner de segurança PHP
- **SonarQube:** Análise de qualidade e segurança

### Treinamentos Recomendados
- **OWASP WebGoat:** Prática de vulnerabilidades
- **HackTheBox:** Desafios de segurança
- **PortSwigger Web Security Academy:** Treinamento gratuito

---

## 📊 Métricas de Qualidade

### Metas de Segurança

- **0** vulnerabilidades críticas
- **0** vulnerabilidades altas não justificadas
- **100%** de cobertura de autenticação/autorização
- **100%** de uso de prepared statements
- **100%** de output escaping em views
- **<5%** de dívida técnica de segurança

### Code Review KPIs

- **Tempo médio de review:** < 24 horas
- **Taxa de aprovação na primeira revisão:** > 70%
- **Bugs de segurança encontrados em prod:** 0/mês
- **Cobertura de testes de segurança:** > 80%

---

**Última Atualização:** 18/11/2024
**Versão:** 1.0
**Status:** ✅ Alinhado com correções de segurança Fase 1-8

---

**Lembre-se:** Segurança não é um checklist que você marca uma vez. É um processo contínuo de vigilância, educação e melhoria. 🛡️
