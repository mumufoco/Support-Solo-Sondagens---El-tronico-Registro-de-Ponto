# Testes E2E (End-to-End) com Selenium

## Pré-requisitos

```bash
# Instalar ChromeDriver
wget https://chromedriver.storage.googleapis.com/LATEST_RELEASE
# Baixar ChromeDriver compatível com sua versão do Chrome

# php-webdriver já instalado via Composer
```

## Estrutura de Testes E2E

### 1. LoginE2ETest
```php
- testLoginFlow(): Abrir /login, preencher, clicar, verificar redirect
- testLoginInvalidCredentials(): Testar mensagem de erro
- testRememberMe(): Checkbox "Lembrar-me"
```

### 2. PunchE2ETest
```php
- testClockInByCode(): Bater ponto via código
- testClockInByQRCode(): Scanner QR Code
- testClockInByFace(): Webcam + reconhecimento facial
```

### 3. JustificationE2ETest
```php
- testCreateJustification(): Formulário completo com anexo
- testApproveJustification(): Gestor aprova
- testRejectJustification(): Gestor rejeita
```

## Executar

```bash
# Modo headless (CI/CD)
php vendor/bin/phpunit --testsuite E2E --headless

# Modo visual (debug)
php vendor/bin/phpunit --testsuite E2E

# Com screenshots em caso de falha
php vendor/bin/phpunit --testsuite E2E --screenshots
```

## Exemplo de Teste

```php
<?php
use Facebook\WebDriver\Chrome\ChromeDriver;
use Facebook\WebDriver\WebDriverBy;

public function testLoginFlow()
{
    $driver = ChromeDriver::start();
    $driver->get('http://localhost:8080/login');

    $driver->findElement(WebDriverBy::id('email'))
        ->sendKeys('admin@ponto.com.br');

    $driver->findElement(WebDriverBy::id('password'))
        ->sendKeys('Admin@123');

    $driver->findElement(WebDriverBy::cssSelector('button[type="submit"]'))
        ->click();

    // Aguardar redirect
    $driver->wait(10)->until(
        WebDriverExpectedCondition::urlContains('/dashboard')
    );

    $this->assertStringContainsString('/dashboard', $driver->getCurrentURL());

    $driver->quit();
}
```

## CI/CD (GitHub Actions)

Testes E2E devem rodar apenas em staging/pre-deploy devido à lentidão.

```yaml
- name: E2E Tests
  run: |
    Xvfb :99 -ac &
    export DISPLAY=:99
    php vendor/bin/phpunit --testsuite E2E
```
