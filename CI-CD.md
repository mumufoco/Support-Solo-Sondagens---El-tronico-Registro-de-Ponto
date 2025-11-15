# CI/CD Pipeline - Sistema de Ponto Eletrônico

Documentação completa do pipeline CI/CD com GitHub Actions, testes automatizados e deployment contínuo.

## 📋 Índice

1. [Visão Geral](#visão-geral)
2. [Pipeline CI (Continuous Integration)](#pipeline-ci)
3. [Pipeline CD (Continuous Deployment)](#pipeline-cd)
4. [Configuração](#configuração)
5. [Testes Automatizados](#testes-automatizados)
6. [Qualidade de Código](#qualidade-de-código)
7. [Deployment](#deployment)
8. [Monitoramento](#monitoramento)
9. [Troubleshooting](#troubleshooting)

---

## Visão Geral

### Arquitetura do Pipeline

```
┌─────────────┐     ┌──────────────┐     ┌──────────────┐
│   Git Push  │ --> │   CI Pipeline│ --> │  CD Pipeline │
└─────────────┘     └──────────────┘     └──────────────┘
                           │                      │
                           ├─ Syntax Check        ├─ Build Images
                           ├─ Unit Tests          ├─ Deploy Staging
                           ├─ PHPStan             ├─ Deploy Production
                           ├─ PHP CS Fixer        └─ Create Release
                           ├─ Security Audit
                           └─ Docker Build
```

### Fluxo de Trabalho

1. **Desenvolvimento**: Push para branch `feature/*`, `bugfix/*` ou `claude/*`
2. **CI Pipeline**: Executa testes, análise estática e verificações de qualidade
3. **Code Review**: Pull Request para `develop` ou `main`
4. **CD Pipeline**: Deploy automático para staging ou production
5. **Verificação**: Health checks e testes de integração
6. **Release**: Criação automática de release no GitHub

---

## Pipeline CI

### Workflows

Arquivo: `.github/workflows/ci.yml`

### Jobs

#### 1. PHP Syntax Check
Verifica sintaxe de todos os arquivos PHP.

```yaml
php-syntax:
  - Checkout code
  - Setup PHP 8.2
  - Check syntax: find app/ -name "*.php" -exec php -l {} \;
```

#### 2. Composer Dependencies
Valida e instala dependências.

```yaml
composer:
  - Validate composer.json
  - Cache dependencies
  - Install dependencies
```

#### 3. PHPStan Static Analysis
Análise estática de código (Level 6).

```yaml
phpstan:
  - Run PHPStan
  - Memory limit: 2GB
  - Paths: Controllers, Models, Services, Filters, Helpers
```

#### 4. PHP CS Fixer
Verifica padrões de código PSR-12.

```yaml
php-cs-fixer:
  - Run PHP CS Fixer (dry-run)
  - Show diff
  - Fail on violations
```

#### 5. PHPUnit Tests
Executa testes unitários e de feature com cobertura.

```yaml
phpunit:
  - Setup MySQL + Redis
  - Run migrations
  - Execute tests
  - Upload coverage to Codecov
```

#### 6. Security Check
Verifica vulnerabilidades nas dependências.

```yaml
security:
  - Run composer audit
  - Check for known vulnerabilities
```

#### 7. Docker Build Test
Testa construção de imagens Docker.

```yaml
docker-build:
  - Build all images
  - Validate docker-compose.yml
```

### Triggers

```yaml
on:
  push:
    branches: [main, develop, feature/**, bugfix/**, claude/**]
  pull_request:
    branches: [main, develop]
```

### Status Badges

Adicione ao README.md:

```markdown
[![CI Pipeline](https://github.com/your-org/ponto-eletronico/workflows/CI%20Pipeline/badge.svg)](https://github.com/your-org/ponto-eletronico/actions)
[![codecov](https://codecov.io/gh/your-org/ponto-eletronico/branch/main/graph/badge.svg)](https://codecov.io/gh/your-org/ponto-eletronico)
```

---

## Pipeline CD

### Workflows

Arquivo: `.github/workflows/cd.yml`

### Jobs

#### 1. Build and Push Docker Images
Constrói e publica imagens no GitHub Container Registry.

```yaml
build-and-push:
  - Build PHP image
  - Build Nginx image
  - Build DeepFace image
  - Push to ghcr.io
  - Tag: sha, latest, version
```

#### 2. Deploy to Staging
Deploy automático para ambiente de staging.

```yaml
deploy-staging:
  - Enable maintenance mode
  - Pull latest code
  - Pull Docker images
  - Run migrations
  - Restart services
  - Health check
```

**Trigger**: Push para branch `develop` ou workflow manual

**URL**: https://staging.pontoeletronico.com.br

#### 3. Deploy to Production
Deploy automático para ambiente de produção.

```yaml
deploy-production:
  - Create backup
  - Enable maintenance mode
  - Pull latest code
  - Pull Docker images
  - Run migrations
  - Restart services (zero-downtime)
  - Health check
  - Rollback on failure
```

**Trigger**: Push de tag `v*.*.*` ou workflow manual

**URL**: https://pontoeletronico.com.br

#### 4. Create GitHub Release
Cria release automática no GitHub.

```yaml
create-release:
  - Generate changelog
  - Create release notes
  - Attach Docker image tags
  - Publish release
```

### Triggers

```yaml
on:
  push:
    branches: [main]
    tags: ['v*.*.*']
  workflow_dispatch:
    inputs:
      environment: [staging, production]
```

---

## Configuração

### 1. GitHub Secrets

Configure os seguintes secrets no repositório:

#### Staging Environment

```bash
STAGING_HOST=staging.pontoeletronico.com.br
STAGING_USER=deploy
STAGING_SSH_KEY=<private-ssh-key>
STAGING_PATH=/var/www/ponto-eletronico
```

#### Production Environment

```bash
PRODUCTION_HOST=pontoeletronico.com.br
PRODUCTION_USER=deploy
PRODUCTION_SSH_KEY=<private-ssh-key>
PRODUCTION_PATH=/var/www/ponto-eletronico
```

### 2. SSH Keys

Gere par de chaves SSH para deployment:

```bash
# No servidor de CI/CD
ssh-keygen -t ed25519 -C "deploy@pontoeletronico.com.br" -f deploy_key

# Adicione a chave pública ao servidor
ssh-copy-id -i deploy_key.pub deploy@pontoeletronico.com.br

# Adicione a chave privada aos GitHub Secrets
cat deploy_key # Copie e adicione como PRODUCTION_SSH_KEY
```

### 3. Deploy User

Crie usuário de deployment no servidor:

```bash
# No servidor
sudo useradd -m -s /bin/bash deploy
sudo usermod -aG docker deploy
sudo mkdir -p /var/www/ponto-eletronico
sudo chown deploy:deploy /var/www/ponto-eletronico

# Configure sudoers para docker-compose
sudo visudo
# Adicione:
deploy ALL=(ALL) NOPASSWD: /usr/local/bin/docker-compose
```

### 4. Container Registry

Configure acesso ao GitHub Container Registry:

```bash
# Login
echo $GITHUB_TOKEN | docker login ghcr.io -u USERNAME --password-stdin

# Pull images
docker pull ghcr.io/your-org/ponto-eletronico/php:latest
```

---

## Testes Automatizados

### Estrutura de Testes

```
tests/
├── unit/               # Testes unitários
│   ├── Models/
│   ├── Services/
│   └── Helpers/
└── feature/            # Testes de feature
    ├── Controllers/
    └── API/
```

### PHPUnit

#### Executar Testes

```bash
# Todos os testes
vendor/bin/phpunit

# Testes unitários
vendor/bin/phpunit --testsuite=Unit

# Testes de feature
vendor/bin/phpunit --testsuite=Feature

# Com cobertura
vendor/bin/phpunit --coverage-html build/coverage
```

#### Configuração

Arquivo: `phpunit.xml`

```xml
<phpunit bootstrap="vendor/codeigniter4/framework/system/Test/bootstrap.php">
  <testsuites>
    <testsuite name="Unit">
      <directory>./tests/unit</directory>
    </testsuite>
    <testsuite name="Feature">
      <directory>./tests/feature</directory>
    </testsuite>
  </testsuites>
</phpunit>
```

### Ambiente de Teste

Arquivo: `env.testing`

```ini
CI_ENVIRONMENT = testing
database.default.database = ponto_eletronico_test
session.driver = ArrayHandler
email.protocol = null
```

### Cobertura de Código

**Meta**: 80% de cobertura mínima

```bash
# Gerar relatório de cobertura
vendor/bin/phpunit --coverage-text

# Visualizar em HTML
vendor/bin/phpunit --coverage-html build/coverage
open build/coverage/index.html
```

---

## Qualidade de Código

### PHPStan

#### Configuração

Arquivo: `phpstan.neon`

```neon
parameters:
  level: 6
  paths:
    - app/Controllers
    - app/Models
    - app/Services
```

#### Executar

```bash
# Análise completa
vendor/bin/phpstan analyse

# Com mais memória
vendor/bin/phpstan analyse --memory-limit=2G

# Gerar baseline (primeira vez)
vendor/bin/phpstan analyse --generate-baseline
```

### PHP CS Fixer

#### Configuração

Arquivo: `.php-cs-fixer.php`

```php
return $config->setRules([
    '@PSR12' => true,
    '@PHP82Migration' => true,
    'array_syntax' => ['syntax' => 'short'],
    // ... mais regras
]);
```

#### Executar

```bash
# Verificar problemas
vendor/bin/php-cs-fixer fix --dry-run --diff

# Corrigir automaticamente
vendor/bin/php-cs-fixer fix

# Verificar arquivo específico
vendor/bin/php-cs-fixer fix app/Controllers/AuthController.php --dry-run
```

### Composer Audit

```bash
# Verificar vulnerabilidades
composer audit

# Com detalhes
composer audit --format=json
```

---

## Deployment

### Estratégia de Deployment

#### 1. Staging (Automático)

- **Trigger**: Push para `develop`
- **Ambiente**: https://staging.pontoeletronico.com.br
- **Propósito**: Testes de integração e QA

```bash
# Deploy manual para staging
gh workflow run cd.yml -f environment=staging
```

#### 2. Production (Controlado)

- **Trigger**: Tag `v*.*.*` ou manual
- **Ambiente**: https://pontoeletronico.com.br
- **Propósito**: Produção

```bash
# Create release tag
git tag -a v1.2.3 -m "Release version 1.2.3"
git push origin v1.2.3

# Deploy manual para production
gh workflow run cd.yml -f environment=production
```

### Versionamento Semântico

```
v<major>.<minor>.<patch>

Exemplo:
v1.0.0  - Release inicial
v1.0.1  - Bugfix
v1.1.0  - Nova feature
v2.0.0  - Breaking change
```

### Zero-Downtime Deployment

```bash
# 1. Enable maintenance mode (apenas para usuarios não-autenticados)
touch writable/maintenance.lock

# 2. Pull latest code
git pull origin main

# 3. Update containers (rolling update)
docker-compose up -d --no-deps --build

# 4. Run migrations
docker-compose exec php php spark migrate --all

# 5. Clear cache
docker-compose exec php php spark cache:clear

# 6. Disable maintenance mode
rm writable/maintenance.lock
```

### Rollback

#### Automático

Pipeline CD executa rollback automático em caso de falha:

```bash
# Restore from latest backup
tar -xzf backups/latest.tar.gz
docker-compose exec mysql mysql < database.sql
docker-compose restart
```

#### Manual

```bash
# 1. Checkout previous version
git checkout v1.2.2

# 2. Rebuild containers
docker-compose up -d --build

# 3. Restore database (if needed)
./scripts/restore-backup.sh backups/2024-01-15_backup.tar.gz
```

---

## Monitoramento

### Health Checks

#### Script Automático

```bash
# Executar health check
./scripts/health-check.sh

# Output:
# ✓ Docker is running
# ✓ All containers are healthy
# ✓ Database connection working
# ✓ Redis is accessible
```

#### Endpoints

```bash
# Application health
curl https://pontoeletronico.com.br/health

# API health
curl https://pontoeletronico.com.br/api/health

# Database health
docker-compose exec mysql mysqladmin ping
```

### Deployment Verification

```bash
# Verificar deployment
./scripts/verify-deployment.sh production

# Output:
# ✓ Homepage accessible
# ✓ SSL certificate valid
# ✓ All services running
# ✓ Database migrations up to date
```

### Logs

```bash
# Ver logs em tempo real
docker-compose logs -f

# Ver logs de serviço específico
docker-compose logs -f php
docker-compose logs -f nginx

# Ver logs de deployment
tail -f /var/log/deployment.log
```

### Métricas

```bash
# Container stats
docker stats

# Disk usage
docker system df

# Database size
docker-compose exec mysql mysql -e "SELECT table_schema AS 'Database', ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS 'Size (MB)' FROM information_schema.tables GROUP BY table_schema;"
```

---

## Troubleshooting

### CI Pipeline Falhou

#### Problema: Testes falhando

```bash
# Executar testes localmente
docker-compose exec php vendor/bin/phpunit

# Ver logs detalhados
docker-compose exec php vendor/bin/phpunit --debug

# Verificar ambiente de teste
docker-compose exec php php spark env
```

#### Problema: PHPStan erros

```bash
# Executar localmente
docker-compose exec php vendor/bin/phpstan analyse

# Gerar baseline para ignorar erros existentes
docker-compose exec php vendor/bin/phpstan analyse --generate-baseline

# Atualizar nivel gradualmente
# phpstan.neon: level: 5 -> level: 6
```

#### Problema: PHP CS Fixer violations

```bash
# Ver problemas
docker-compose exec php vendor/bin/php-cs-fixer fix --dry-run --diff

# Corrigir automaticamente
docker-compose exec php vendor/bin/php-cs-fixer fix

# Commit fixes
git add .
git commit -m "fix: code style violations"
```

### CD Pipeline Falhou

#### Problema: Deploy failed

```bash
# Check logs
ssh deploy@pontoeletronico.com.br
cd /var/www/ponto-eletronico
docker-compose logs --tail=100

# Verify services
docker-compose ps

# Manual rollback
git checkout v1.2.2
docker-compose up -d --build
```

#### Problema: Database migration failed

```bash
# Check migration status
docker-compose exec php php spark migrate:status

# Rollback last migration
docker-compose exec php php spark migrate:rollback

# Re-run migrations
docker-compose exec php php spark migrate

# Restore from backup (if needed)
./scripts/restore-backup.sh backups/latest.tar.gz
```

#### Problema: Services not starting

```bash
# Check container logs
docker-compose logs mysql
docker-compose logs redis
docker-compose logs php

# Restart services
docker-compose restart

# Rebuild if needed
docker-compose down
docker-compose up -d --build
```

### Health Check Failed

#### Problema: Database connection failed

```bash
# Check MySQL
docker-compose exec mysql mysqladmin ping

# Check credentials
docker-compose exec php php spark db:connect

# Restart MySQL
docker-compose restart mysql
```

#### Problema: Redis connection failed

```bash
# Check Redis
docker-compose exec redis redis-cli ping

# Flush Redis
docker-compose exec redis redis-cli FLUSHDB

# Restart Redis
docker-compose restart redis
```

#### Problema: Application not accessible

```bash
# Check Nginx
docker-compose exec nginx nginx -t

# Check PHP-FPM
docker-compose exec php php -v

# Check logs
docker-compose logs nginx
docker-compose logs php

# Restart services
docker-compose restart nginx php
```

---

## Comandos Úteis

### CI/CD

```bash
# Ver status dos workflows
gh workflow list

# Ver execuções recentes
gh run list

# Ver logs de execução
gh run view <run-id> --log

# Re-executar workflow
gh run rerun <run-id>

# Cancelar workflow
gh run cancel <run-id>
```

### Deployment

```bash
# Deploy manual
gh workflow run cd.yml -f environment=production

# Criar release
git tag -a v1.2.3 -m "Release 1.2.3"
git push origin v1.2.3

# Listar releases
gh release list

# Ver detalhes de release
gh release view v1.2.3
```

### Testes

```bash
# Executar testes
vendor/bin/phpunit

# Com cobertura
vendor/bin/phpunit --coverage-html build/coverage

# Teste específico
vendor/bin/phpunit tests/unit/Models/EmployeeModelTest.php

# Filtrar por método
vendor/bin/phpunit --filter testEmployeeCanBeCreated
```

### Qualidade

```bash
# PHPStan
vendor/bin/phpstan analyse

# PHP CS Fixer
vendor/bin/php-cs-fixer fix --dry-run

# Composer audit
composer audit
```

---

## Melhores Práticas

### 1. Commits

- Use Conventional Commits: `feat:`, `fix:`, `docs:`, `refactor:`
- Commits pequenos e atômicos
- Mensagens descritivas em português

```bash
git commit -m "feat: adicionar autenticação por reconhecimento facial"
git commit -m "fix: corrigir cálculo de horas extras"
git commit -m "docs: atualizar documentação do CI/CD"
```

### 2. Branches

- `main` - Produção
- `develop` - Desenvolvimento
- `feature/*` - Novas features
- `bugfix/*` - Correções de bugs
- `hotfix/*` - Correções urgentes para produção

### 3. Pull Requests

- Sempre criar PR para `main` e `develop`
- Aguardar aprovação do CI
- Code review obrigatório
- Squash commits antes do merge

### 4. Versionamento

- Seguir Semantic Versioning
- Criar tag para cada release
- Gerar changelog automático
- Documentar breaking changes

### 5. Testes

- Escrever testes para novas features
- Manter cobertura > 80%
- Executar testes localmente antes do push
- Mockar dependências externas

### 6. Deployment

- Sempre fazer backup antes
- Testar em staging primeiro
- Deploy em horários de baixo tráfego
- Monitorar logs após deployment
- Planejar rollback

---

## Referências

- [GitHub Actions Documentation](https://docs.github.com/en/actions)
- [Docker Documentation](https://docs.docker.com/)
- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [PHPStan Documentation](https://phpstan.org/user-guide/getting-started)
- [PHP CS Fixer Documentation](https://github.com/FriendsOfPHP/PHP-CS-Fixer)
- [Semantic Versioning](https://semver.org/)
- [Conventional Commits](https://www.conventionalcommits.org/)

---

## Suporte

Para dúvidas ou problemas com o CI/CD:

1. Verifique os logs no GitHub Actions
2. Consulte esta documentação
3. Execute scripts de diagnóstico localmente
4. Contate a equipe de DevOps

**Desenvolvido para Sistema de Ponto Eletrônico**
**Compliance**: LGPD (Lei 13.709/2018) | MTE 671/2021
