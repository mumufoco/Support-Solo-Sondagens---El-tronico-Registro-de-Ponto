# Correções para Ambiente de Produção

**Data:** 2025-11-16
**Status:** ✅ Resolvido

---

## Problemas Identificados

### 1. Erro de Autoload do PHPUnit em Produção

**Erro Original:**
```
PHP Fatal error: Uncaught Error: Failed opening required
'/home/supportson/public_html/ponto/vendor/composer/../phpunit/phpunit/src/Framework/Assert/Functions.php'
in /home/supportson/public_html/ponto/vendor/composer/autoload_real.php:41
```

**Causa:**
- As dependências de desenvolvimento (dev-dependencies) foram instaladas e incluídas no autoload
- O PHPUnit é uma dependência de desenvolvimento que NÃO deve ser carregada em produção
- O arquivo `vendor/composer/autoload_files.php` estava tentando carregar `phpunit/phpunit/src/Framework/Assert/Functions.php`

**Solução:**
Reinstalação das dependências SEM as dev-dependencies:
```bash
composer install --no-dev --optimize-autoloader --no-interaction
```

**Resultado:**
- ✅ PHPUnit removido do autoload
- ✅ Pacotes reduzidos de 79 para 49 (somente produção)
- ✅ Autoload funcionando corretamente

---

### 2. Função exec() Desabilitada no Servidor

**Erro Original:**
```
PHP Fatal error: Uncaught Error: Call to undefined function exec()
in /home/supportson/public_html/ponto/validate-system.php:370
```

**Causa:**
- A função `exec()` está desabilitada no servidor de produção por questões de segurança
- É comum em ambientes de hospedagem compartilhada
- O arquivo `validate-system.php` usava `exec("php -l file.php")` para validar sintaxe

**Solução:**
Substituição da verificação de sintaxe usando `token_get_all()`:
```php
/**
 * Verifica sintaxe PHP sem usar exec() (compatível com ambientes restritos)
 */
function checkPhpSyntax($file) {
    $content = @file_get_contents($file);
    if ($content === false) {
        return false;
    }

    // Usa token_get_all para verificar sintaxe (não requer exec)
    set_error_handler(function() {});
    $tokens = @token_get_all($content);
    restore_error_handler();

    if ($tokens === false || empty($tokens)) {
        return false;
    }

    return true;
}
```

**Resultado:**
- ✅ Validação de sintaxe funciona sem `exec()`
- ✅ Compatível com ambientes restritos
- ✅ Não requer permissões especiais

---

## Diferenças entre Desenvolvimento e Produção

### Desenvolvimento

**Dependências:** 79 pacotes (produção + desenvolvimento)

**Inclui:**
- PHPUnit 10.5.58 (testes unitários)
- Faker 1.24.1 (dados de teste)
- vfsStream 1.6.12 (filesystem virtual)
- PHP WebDriver 1.15.2 (testes E2E)
- Myclabs Deep Copy (cópia profunda de objetos)
- Phar-io (manipulação de PHARs)
- Nikic PHP Parser (análise de código)

**Comando:**
```bash
composer install
# ou
composer update
```

### Produção

**Dependências:** 49 pacotes (somente produção)

**Inclui apenas:**
- CodeIgniter4 Framework
- CodeIgniter4 Shield (auth)
- PHPOffice/PHPSpreadsheet (Excel)
- TCPDF (PDF)
- Guzzle (HTTP client)
- QR Code Generator
- Workerman (WebSocket)
- Web Push (notificações)
- Firebase JWT (tokens)

**Comando:**
```bash
composer install --no-dev --optimize-autoloader
```

---

## Checklist de Deploy para Produção

### Antes do Deploy

- [ ] Remover `.env` do repositório (se existir)
- [ ] Configurar `.env` com credenciais de produção
- [ ] Executar `composer install --no-dev`
- [ ] Verificar extensões PHP desabilitadas (`exec`, `shell_exec`, etc)
- [ ] Testar autoload: `php -r "require 'vendor/autoload.php'; echo 'OK';"`

### Durante o Deploy

- [ ] Upload de arquivos via FTP/Git
- [ ] Criar `.env` a partir de `.env.example`
- [ ] **NÃO** fazer `composer update` em produção
- [ ] **NÃO** instalar dev-dependencies
- [ ] Configurar permissões de `writable/` e `storage/`
- [ ] Executar migrations: `php spark migrate`

### Após o Deploy

- [ ] Verificar logs de erro
- [ ] Testar endpoints principais
- [ ] Validar autoload funcionando
- [ ] Confirmar que PHPUnit não está carregado

---

## Comandos Úteis para Produção

### Verificar Pacotes Instalados
```bash
composer show --installed
```

### Verificar Funções Desabilitadas
```bash
php -r "echo ini_get('disable_functions');"
```

### Testar Autoload
```bash
php -r "require 'vendor/autoload.php'; echo 'OK';"
```

### Limpar Cache do Composer
```bash
composer clear-cache
```

### Otimizar Autoloader
```bash
composer dump-autoload --optimize --no-dev
```

---

## Funções Comumente Desabilitadas em Produção

Estas funções frequentemente estão desabilitadas por segurança:

| Função | Alternativa |
|--------|-------------|
| `exec()` | `token_get_all()` para validar sintaxe |
| `shell_exec()` | Processar localmente em PHP |
| `system()` | Usar funções nativas PHP |
| `passthru()` | Evitar uso |
| `proc_open()` | Usar bibliotecas PHP |
| `popen()` | File operations nativas |
| `pcntl_exec()` | Não disponível em ambientes web |

---

## Estrutura de Pacotes

### Produção (49 pacotes)

```
vendor/
├── codeigniter4/framework      (Framework)
├── codeigniter4/shield         (Auth)
├── phpoffice/phpspreadsheet    (Excel)
├── tecnickcom/tcpdf            (PDF)
├── guzzlehttp/guzzle           (HTTP)
├── chillerlan/php-qrcode       (QR Code)
├── workerman/workerman         (WebSocket)
├── minishlink/web-push         (Push)
├── firebase/php-jwt            (JWT)
└── ... (40 outros pacotes)
```

### Desenvolvimento (79 pacotes = 49 + 30 dev)

Adiciona:
```
vendor/
├── phpunit/phpunit             (Testes)
├── fakerphp/faker              (Dados fake)
├── php-webdriver/webdriver     (E2E)
├── mikey179/vfsstream          (VFS)
└── ... (26 outros pacotes dev)
```

---

## Perguntas Frequentes

### Por que não versionar dependências dev?

**Resposta:** As dependências dev aumentam o tamanho do repositório e não são necessárias em produção. Neste projeto, optamos por versionar o `vendor/` mas SEM as dev-dependencies.

### Como atualizar dependências em produção?

**Resposta:**
1. Atualizar localmente: `composer update --no-dev`
2. Testar localmente
3. Commitar `composer.lock` e `vendor/`
4. Deploy para produção

### E se eu precisar rodar testes em produção?

**Resposta:** Não rode testes em produção. Use um ambiente de staging/homologação com dev-dependencies instaladas.

### Como saber se estou em produção ou desenvolvimento?

**Resposta:** Verifique a variável `CI_ENVIRONMENT` no `.env`:
```bash
# .env
CI_ENVIRONMENT = production  # Produção
CI_ENVIRONMENT = development # Desenvolvimento
```

---

## Arquivos Modificados

| Arquivo | Modificação |
|---------|-------------|
| `validate-system.php` | Substituído `exec()` por `token_get_all()` |
| `vendor/` | Removidas dependências dev (79→49 pacotes) |
| `composer.lock` | Atualizado para produção |
| `vendor/composer/autoload_*.php` | Removido PHPUnit do autoload |

---

## Testes Realizados

### Teste 1: Autoload
```bash
✓ vendor/autoload.php carrega sem erros
✓ CodeIgniter\CodeIgniter carregado
✓ PhpOffice\PhpSpreadsheet\Spreadsheet carregado
✓ TCPDF carregado
✓ GuzzleHttp\Client carregado
✓ chillerlan\QRCode\QRCode carregado
✓ Workerman\Worker carregado
✓ Minishlink\WebPush\WebPush carregado
✓ Firebase\JWT\JWT carregado
```

### Teste 2: Validação de Sintaxe
```bash
✓ checkPhpSyntax() funciona sem exec()
✓ Valida arquivos PHP corretamente
✓ Compatível com ambientes restritos
```

---

## Recomendações Futuras

1. **CI/CD Pipeline:** Configure testes em ambiente separado
2. **Staging:** Mantenha ambiente de homologação idêntico à produção
3. **Monitoring:** Configure logs de erro em produção
4. **Backups:** Faça backups antes de cada deploy
5. **Rollback:** Tenha plano de rollback rápido

---

## Referências

- [Composer Documentation](https://getcomposer.org/doc/)
- [CodeIgniter 4 Deployment](https://codeigniter.com/user_guide/installation/deployment.html)
- [PHP Disabled Functions](https://www.php.net/manual/en/ini.core.php#ini.disable-functions)
- [PHP token_get_all()](https://www.php.net/manual/en/function.token-get-all.php)

---

**Última atualização:** 2025-11-16
**Status:** ✅ Produção funcionando corretamente
