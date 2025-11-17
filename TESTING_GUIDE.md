# 🧪 Guia de Testes - Sistema de Ponto Eletrônico

## 📋 Visão Geral

Este projeto utiliza **PHPUnit** para testes automatizados, divididos em:

- **Unit Tests** - Testes unitários de Models, Services, Libraries
- **Feature Tests** - Testes de integração de Controllers e rotas
- **Database Tests** - Testes com banco de dados (usa database de teste)

---

## 🚀 Executando Testes

### Todos os Testes

```bash
# Via composer
composer test

# Ou diretamente via PHPUnit
vendor/bin/phpunit
```

### Testes Específicos

```bash
# Apenas unit tests
vendor/bin/phpunit tests/unit

# Apenas feature tests
vendor/bin/phpunit tests/feature

# Teste específico
vendor/bin/phpunit tests/unit/Models/EmployeeModelTest.php

# Método específico
vendor/bin/phpunit tests/unit/Models/EmployeeModelTest.php --filter testCreateEmployeeWithValidData
```

### Com Coverage

```bash
# Gerar relatório de cobertura (requer Xdebug)
vendor/bin/phpunit --coverage-html coverage/

# Ver relatório
open coverage/index.html
```

---

## 📁 Estrutura de Testes

```
tests/
├── unit/                    # Testes unitários
│   ├── Models/             # Testes de Models
│   │   └── EmployeeModelTest.php
│   ├── Services/           # Testes de Services
│   └── Libraries/          # Testes de Libraries
├── feature/                # Testes de features
│   ├── LoginTest.php
│   └── PunchTest.php
├── _support/               # Classes de suporte
│   └── Database/
│       └── Seeds/
└── bootstrap.php           # Bootstrap dos testes
```

---

## ✍️ Escrevendo Testes

### Exemplo: Unit Test (Model)

```php
<?php

namespace Tests\Unit\Models;

use App\Models\EmployeeModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

class EmployeeModelTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $refresh = true;  // Reseta DB a cada teste
    protected EmployeeModel $model;

    protected function setUp(): void
    {
        parent::setUp();
        $this->model = new EmployeeModel();
    }

    public function testCreateEmployee()
    {
        $data = [
            'name' => 'Test User',
            'email' => 'test@empresa.com',
            'password' => 'SenhaForte123!@#',
            'cpf' => '123.456.789-00',
            'role' => 'funcionario',
        ];

        $id = $this->model->insert($data);

        $this->assertIsNumeric($id);
        $this->assertGreaterThan(0, $id);

        $employee = $this->model->find($id);
        $this->assertEquals('Test User', $employee->name);
    }
}
```

### Exemplo: Feature Test (Controller)

```php
<?php

namespace Tests\Feature;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use CodeIgniter\Test\DatabaseTestTrait;

class LoginTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    use DatabaseTestTrait;

    protected $refresh = true;

    public function testLoginPageLoads()
    {
        $result = $this->get('/auth/login');

        $result->assertOK();
        $result->assertSee('Login');
    }

    public function testSuccessfulLogin()
    {
        // Criar usuário de teste
        // ...

        $result = $this->post('/auth/authenticate', [
            'email' => 'test@empresa.com',
            'password' => 'password123',
        ]);

        $result->assertRedirect();
        $result->assertSessionHas('logged_in', true);
    }
}
```

---

## 🗄️ Testes com Banco de Dados

### Configuração

O arquivo `app/Config/Database.php` já está configurado com um database de teste:

```php
public array $tests = [
    'hostname' => 'localhost',
    'username' => 'root',
    'password' => '',
    'database' => 'ponto_eletronico_test',
    'DBDriver' => 'MySQLi',
];
```

### Criar Database de Teste

```bash
mysql -u root -p
CREATE DATABASE ponto_eletronico_test;
exit;
```

### Usar DatabaseTestTrait

```php
use CodeIgniter\Test\DatabaseTestTrait;

class MyTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $refresh = true;  // Reseta DB antes de cada teste
    protected $seed = 'TestSeeder';  // Seed para popular dados

    // Seu teste aqui
}
```

---

## 📊 Cobertura de Testes

### Meta de Cobertura

- **Mínimo:** 70%
- **Ideal:** 80%+
- **Crítico:** 100% para Models e Services de segurança

### Verificar Cobertura

```bash
# Gerar relatório
vendor/bin/phpunit --coverage-text

# Ou HTML
vendor/bin/phpunit --coverage-html coverage/
```

### Exemplo de Saída

```
Code Coverage Report:
  2024-11-17 15:30:00

 Summary:
  Classes: 85.00% (17/20)
  Methods: 78.50% (157/200)
  Lines:   82.30% (1234/1500)

  App\Models:
    EmployeeModel          95.50% (64/67)
    TimePunchModel         88.20% (45/51)

  App\Services:
    AuthService            92.00% (46/50)
```

---

## 🎯 Boas Práticas

### 1. Nomenclatura

- Testes começam com `test`
- Nome descritivo: `testCreateEmployeeWithValidData()`
- Evite: `test1()`, `test2()`

### 2. Arrange-Act-Assert (AAA)

```php
public function testExample()
{
    // Arrange - Preparar dados
    $data = ['name' => 'Test'];

    // Act - Executar ação
    $result = $this->model->create($data);

    // Assert - Verificar resultado
    $this->assertTrue($result);
}
```

### 3. Um Assert por Conceito

```php
// ✅ BOM
public function testUserIsCreated()
{
    $id = $this->model->insert($data);
    $this->assertIsNumeric($id);
}

public function testUserHasCorrectName()
{
    $user = $this->model->find($id);
    $this->assertEquals('John', $user->name);
}

// ❌ EVITE
public function testUser()
{
    $id = $this->model->insert($data);
    $this->assertIsNumeric($id);
    $user = $this->model->find($id);
    $this->assertEquals('John', $user->name);
    $this->assertTrue($user->active);
}
```

### 4. Dados de Teste

```php
// Use Seeders para dados complexos
protected $seed = 'TestSeeder';

// Ou crie dados no setUp()
protected function setUp(): void
{
    parent::setUp();
    $this->createTestUser();
}
```

### 5. Cleanup

```php
// DatabaseTestTrait cuida automaticamente quando $refresh = true

// Para cleanup manual:
protected function tearDown(): void
{
    // Limpar dados
    parent::tearDown();
}
```

---

## 🔍 Assertions Úteis

### Assertions Básicas

```php
$this->assertTrue($condition);
$this->assertFalse($condition);
$this->assertEquals($expected, $actual);
$this->assertNotEquals($expected, $actual);
$this->assertNull($value);
$this->assertNotNull($value);
$this->assertEmpty($value);
$this->assertNotEmpty($value);
```

### Assertions de Tipo

```php
$this->assertIsInt($value);
$this->assertIsString($value);
$this->assertIsArray($value);
$this->assertIsBool($value);
$this->assertIsNumeric($value);
```

### Assertions de Comparação

```php
$this->assertGreaterThan($expected, $actual);
$this->assertLessThan($expected, $actual);
$this->assertContains($needle, $haystack);
$this->assertStringContainsString($needle, $haystack);
```

### Assertions de HTTP (FeatureTest)

```php
$result->assertOK();                     // 200
$result->assertRedirect();                // 30x
$result->assertStatus(201);               // Status específico
$result->assertSee('text');               // Texto na resposta
$result->assertDontSee('text');
$result->assertSeeElement('#id');         // Elemento HTML
$result->assertSessionHas('key', 'value');
$result->assertHeader('header', 'value');
```

---

## 🐛 Debugging Testes

### Ver Output

```bash
# Modo verbose
vendor/bin/phpunit --verbose

# Mostrar echo/var_dump
vendor/bin/phpunit --debug
```

### Parar no Primeiro Erro

```bash
vendor/bin/phpunit --stop-on-failure
```

### Testar Apenas Falhos Anteriores

```bash
vendor/bin/phpunit --testdox
```

---

## 🚦 CI/CD Integration

### GitHub Actions

```yaml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.1'
      - name: Install Dependencies
        run: composer install
      - name: Run Tests
        run: vendor/bin/phpunit
```

---

## 📚 Recursos

- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [CodeIgniter Testing Guide](https://codeigniter4.github.io/userguide/testing/index.html)
- [Test Driven Development](https://martinfowler.com/bliki/TestDrivenDevelopment.html)

---

## ✅ Checklist de Testes

Antes de fazer commit/deploy:

- [ ] Todos os testes passam (`composer test`)
- [ ] Cobertura >= 70%
- [ ] Novos features têm testes
- [ ] Bugs corrigidos têm testes de regressão
- [ ] Testes de integração para APIs
- [ ] Testes de segurança para autenticação

---

**Mantenha os testes atualizados! Um código bem testado é um código confiável.** ✨
