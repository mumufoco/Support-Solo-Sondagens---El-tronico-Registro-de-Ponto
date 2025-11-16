# Guia de Testes - Ponto Eletrônico

## 📊 Status Atual dos Testes

### Resumo Executivo
- **Total de Testes**: 160 testes unitários + 61 testes de integração = **221 testes**
- **Testes que Passam Sem BD**: 84 testes (52.5%)
- **Testes que Requerem BD**: 76 testes (47.5%)
- **Cobertura de Código**: Disponível com `--coverage-html`

### Último Resultado de Execução
```
Tests: 160, Assertions: 308
✅ Passing: 84 testes (sem dependência de BD)
⚠️  Errors: 74 testes (requerem MySQL)
❌ Failures: 2 testes
⚠️  Risky: 1 teste
```

---

## 🎯 Testes que Passam (Sem Banco de Dados)

### ✅ Encryption Service (17 testes)
- ✔ Encryption and decryption
- ✔ Key version management
- ✔ Error handling (empty strings, invalid data)
- ✔ Secure memory cleanup
- ✔ Multiple encryptions produce different ciphertexts

### ✅ Two-Factor Authentication (18 testes)
- ✔ Secret generation (16/32 chars)
- ✔ TOTP code generation (RFC 6238 compliant)
- ✔ Code verification with time drift
- ✔ OTP Auth URL generation
- ✔ QR Code data URI
- ✔ Backup codes (generation, hashing, verification)
- ✔ Google Authenticator compatibility
- ✔ Multiple codes in time window

### ✅ Rate Limiting Service (26 testes)
- ✔ Hit recording and checking
- ✔ Token bucket algorithm
- ✔ Multiple limit types (login, API, 2FA, etc.)
- ✔ IP whitelisting
- ✔ Proxy header support (X-Forwarded-For, etc.)
- ✔ Custom configurations
- ✔ Attempt reset

### ✅ Security Headers Filter (31 testes - 30 passando)
- ✔ Content-Security-Policy
- ✔ HTTP Strict Transport Security (HSTS)
- ✔ X-Frame-Options
- ✔ X-Content-Type-Options
- ✔ Referrer-Policy
- ✔ Permissions-Policy
- ✔ All headers present in production
- ⚠️ 1 risky test (HSTS not in development - sem assertions)

---

## 🔴 Testes que Requerem Banco de Dados (MySQL)

### ❌ Dashboard Integration Tests (61 testes)
- Todos os 5 arquivos de integração requerem BD
- `AuthenticationFlowTest.php` (7 testes)
- `OAuth2IntegrationTest.php` (13 testes)
- `SecurityIntegrationTest.php` (15 testes)
- `DashboardIntegrationTest.php` (19 testes)
- `EndToEndFlowTest.php` (7 testes)

### ❌ Unit Tests que Requerem BD (74 testes)
- `AuthServiceTest.php` - Login, brute force protection
- `FaceRecognitionServiceTest.php` - Face embeddings, verification
- `GeofenceServiceTest.php` - Location validation
- `TimePunchServiceTest.php` - Punch operations
- `EmployeeModelTest.php` - CRUD operations
- `DepartmentModelTest.php` - CRUD operations
- `TimePunchModelTest.php` - Punch records
- E outros testes de Models e Services

---

## 🚀 Como Executar os Testes

### 1. Testes Unitários (Sem Banco de Dados)
Executam sem necessidade de configuração adicional:

```bash
# Executar todos os testes unitários
vendor/bin/phpunit tests/unit/

# Executar apenas testes de serviços específicos
vendor/bin/phpunit tests/unit/Services/Security/EncryptionServiceTest.php
vendor/bin/phpunit tests/unit/Services/Security/TwoFactorAuthServiceTest.php
vendor/bin/phpunit tests/unit/Services/Security/RateLimitServiceTest.php

# Executar com formato testdox (mais legível)
vendor/bin/phpunit tests/unit/ --testdox

# Executar com filtro por nome
vendor/bin/phpunit --filter TwoFactor tests/unit/
```

### 2. Testes que Requerem Banco de Dados

#### Opção A: Usando Docker Compose (Recomendado)

```bash
# 1. Iniciar serviços (MySQL, Redis, etc.)
docker-compose up -d mysql redis

# 2. Aguardar MySQL estar pronto
docker-compose exec mysql mysqladmin ping -h localhost --silent

# 3. Criar banco de testes
docker-compose exec mysql mysql -u root -proot_password -e \
  "CREATE DATABASE IF NOT EXISTS ponto_eletronico_test;"

# 4. Executar migrations
php spark migrate --env testing

# 5. Executar todos os testes
vendor/bin/phpunit

# 6. Executar apenas testes de integração
vendor/bin/phpunit tests/integration/ --testdox

# 7. Parar serviços quando terminar
docker-compose down
```

#### Opção B: MySQL Local

```bash
# 1. Instalar MySQL 8.0
sudo apt-get install mysql-server-8.0  # Ubuntu/Debian
# ou
brew install mysql@8.0                  # macOS

# 2. Iniciar MySQL
sudo service mysql start

# 3. Criar banco de testes
mysql -u root -e "CREATE DATABASE ponto_eletronico_test;"

# 4. Configurar .env.testing
cp .env .env.testing

# Editar .env.testing:
CI_ENVIRONMENT = testing
database.tests.hostname = localhost
database.tests.database = ponto_eletronico_test
database.tests.username = root
database.tests.password = your_password
database.tests.DBDriver = MySQLi

# 5. Executar migrations
php spark migrate --env testing

# 6. Executar testes
vendor/bin/phpunit
```

---

## 🔧 Configuração do Ambiente de Testes

### Arquivo .env.testing (Criar se não existir)

```ini
#--------------------------------------------------------------------
# ENVIRONMENT
#--------------------------------------------------------------------

CI_ENVIRONMENT = testing

#--------------------------------------------------------------------
# DATABASE - Testing
#--------------------------------------------------------------------

database.tests.hostname = localhost
database.tests.database = ponto_eletronico_test
database.tests.username = root
database.tests.password =
database.tests.DBDriver = MySQLi
database.tests.DBPrefix =
database.tests.port = 3306

#--------------------------------------------------------------------
# ENCRYPTION
#--------------------------------------------------------------------

ENCRYPTION_KEY = test_encryption_key_32_bytes_long_minimum_here_12345
ENCRYPTION_KEY_VERSION = 1

#--------------------------------------------------------------------
# REDIS (Opcional - para testes de cache)
#--------------------------------------------------------------------

REDIS_HOST = localhost
REDIS_PORT = 6379
REDIS_PASSWORD =
REDIS_DATABASE = 1

#--------------------------------------------------------------------
# PUSH NOTIFICATIONS (Opcional)
#--------------------------------------------------------------------

# Deixe vazio para pular testes de notificação
FCM_SERVER_KEY =
FCM_SENDER_ID =

#--------------------------------------------------------------------
# RATE LIMITING
#--------------------------------------------------------------------

RATE_LIMIT_WHITELIST = 127.0.0.1,localhost
```

### phpunit.xml (Já Configurado)

O arquivo `phpunit.xml` na raiz do projeto já está configurado com:
- Suporte a `.env.testing`
- Isolamento de testes
- Cobertura de código
- Namespaces de teste

---

## 📈 Cobertura de Código

### Gerar Relatório de Cobertura

```bash
# Gerar relatório HTML
vendor/bin/phpunit --coverage-html coverage/

# Abrir no navegador
xdg-open coverage/index.html  # Linux
open coverage/index.html       # macOS

# Gerar relatório de texto
vendor/bin/phpunit --coverage-text

# Gerar relatório XML (para CI/CD)
vendor/bin/phpunit --coverage-clover coverage.xml
```

### Requisitos para Cobertura
- **Xdebug** ou **PCOV** instalado
- Para instalar PCOV:
  ```bash
  pecl install pcov
  echo "extension=pcov.so" >> /etc/php/8.4/cli/conf.d/20-pcov.ini
  ```

---

## 🐛 Troubleshooting

### Erro: "Unable to connect to the database"

**Causa**: MySQL não está rodando ou configuração incorreta

**Solução**:
```bash
# Verificar se MySQL está rodando
sudo service mysql status

# Ou com Docker
docker-compose ps

# Verificar conectividade
mysql -u root -p -h localhost ponto_eletronico_test
```

### Erro: "Failed to decrypt setting"

**Causa**: ENCRYPTION_KEY não configurada ou incompatível

**Solução**:
```bash
# Gerar nova chave de criptografia
php spark encryption:generatekey

# Copiar para .env.testing
```

### Erro: "PHPUnit test runner warning: No code coverage driver"

**Causa**: Xdebug ou PCOV não instalado

**Solução**:
```bash
# Instalar PCOV (mais rápido que Xdebug)
pecl install pcov

# Verificar
php -m | grep pcov
```

### Testes de Rate Limiting Falhando

**Causa**: Cache não está limpo ou localhost não whitelisted

**Solução**:
```bash
# Limpar cache
php spark cache:clear

# Verificar whitelist no .env.testing
RATE_LIMIT_WHITELIST = 127.0.0.1,localhost,::1
```

### Testes de Push Notification Falhando

**Causa**: FCM_SERVER_KEY não configurada

**Solução**:
- Isso é esperado se FCM não está configurado
- Testes podem ser pulados com:
  ```bash
  vendor/bin/phpunit --exclude-group notifications
  ```

---

## 🎬 CI/CD Integration

### GitHub Actions Example

```yaml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest

    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_ROOT_PASSWORD: root
          MYSQL_DATABASE: ponto_eletronico_test
        ports:
          - 3306:3306
        options: --health-cmd="mysqladmin ping" --health-interval=10s

      redis:
        image: redis:7-alpine
        ports:
          - 6379:6379

    steps:
      - uses: actions/checkout@v3

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.4'
          extensions: mysqli, pdo_mysql, redis, sodium, gd
          coverage: pcov

      - name: Install Dependencies
        run: composer install --no-interaction

      - name: Run Migrations
        run: php spark migrate --env testing

      - name: Run Tests
        run: vendor/bin/phpunit --coverage-clover coverage.xml

      - name: Upload Coverage
        uses: codecov/codecov-action@v3
        with:
          file: ./coverage.xml
```

---

## 📝 Escrevendo Novos Testes

### Estrutura de Teste Unitário

```php
<?php

namespace Tests\Unit\Services;

use CodeIgniter\Test\CIUnitTestCase;
use App\Services\MyService;

class MyServiceTest extends CIUnitTestCase
{
    protected MyService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new MyService();
    }

    public function testMyFeature()
    {
        // Arrange
        $input = 'test';

        // Act
        $result = $this->service->doSomething($input);

        // Assert
        $this->assertEquals('expected', $result);
    }
}
```

### Estrutura de Teste de Integração

```php
<?php

namespace Tests\Integration;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

class MyIntegrationTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected function setUp(): void
    {
        parent::setUp();
        // Criar dados de teste
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        // Limpar dados de teste
    }

    public function testCompleteFlow()
    {
        // Teste de fluxo completo
    }
}
```

---

## 📚 Referências

- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [CodeIgniter Testing Guide](https://codeigniter.com/user_guide/testing/index.html)
- [Database Testing in CodeIgniter](https://codeigniter.com/user_guide/testing/database.html)
- [Integration Tests README](../tests/integration/README.md)

---

## ✅ Checklist de Validação

Antes de fazer commit/push, certifique-se:

- [ ] Todos os testes unitários passam
- [ ] Testes de integração passam (com MySQL rodando)
- [ ] Não há warnings do PHPUnit
- [ ] Cobertura de código > 80% (alvo)
- [ ] Novos recursos têm testes correspondentes
- [ ] Testes são isolados e podem rodar em qualquer ordem
- [ ] Dados de teste são criados e limpos corretamente

---

**Última Atualização**: 2024-11-16
**Versão**: 1.0.0
**Fase do Projeto**: 17+ Híbrida Completa
