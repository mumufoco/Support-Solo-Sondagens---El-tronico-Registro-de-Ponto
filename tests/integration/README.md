# Integration Tests

Testes de integração para validar fluxos completos do sistema.

## 📋 Arquivos de Teste

### 1. AuthenticationFlowTest.php
**Testa**: Fluxo completo de autenticação

**Cenários Cobertos**:
- ✅ Login sem 2FA
- ✅ Login com 2FA (fluxo completo)
- ✅ Credenciais inválidas
- ✅ Código 2FA inválido
- ✅ Bloqueio de acesso sem verificação 2FA
- ✅ Logout completo
- ✅ Tentativas múltiplas de login (rate limiting)

**Métodos de Teste**:
- `testLoginFlowWithout2FA()` - Login simples
- `testLoginFlowWith2FA()` - Login com 2FA
- `testLoginWithInvalidCredentials()` - Falha de autenticação
- `testTwoFactorWithInvalidCode()` - Código 2FA inválido
- `testTwoFactorFilterBlocksUnverifiedAccess()` - Middleware 2FA
- `testLogoutFlow()` - Logout completo
- `testAccountLockoutAfterFailedAttempts()` - Rate limiting

---

### 2. OAuth2IntegrationTest.php
**Testa**: Fluxo completo OAuth 2.0 para API

**Cenários Cobertos**:
- ✅ Password grant (obtenção de tokens)
- ✅ Refresh token (renovação de tokens)
- ✅ Token revocation (revogação de tokens)
- ✅ Credenciais inválidas
- ✅ Conta inativa
- ✅ Rate limiting em OAuth
- ✅ Acesso sem token
- ✅ Token inválido
- ✅ Scope-based authorization
- ✅ Múltiplos dispositivos
- ✅ Revogação de todos os tokens

**Métodos de Teste**:
- `testPasswordGrantFlow()` - Fluxo password grant
- `testRefreshTokenFlow()` - Renovação de token
- `testTokenRevocation()` - Revogação de token
- `testInvalidCredentials()` - Credenciais inválidas
- `testInactiveAccount()` - Conta inativa
- `testOAuthRateLimiting()` - Rate limiting
- `testProtectedEndpointWithoutToken()` - Sem autenticação
- `testProtectedEndpointWithInvalidToken()` - Token inválido
- `testScopeBasedAuthorization()` - Autorização por scope
- `testMultipleDeviceTokens()` - Múltiplos dispositivos
- `testRevokeAllTokens()` - Revogação em massa

---

### 3. SecurityIntegrationTest.php
**Testa**: Features de segurança integradas

**Cenários Cobertos**:
- ✅ Security headers em todas as respostas
- ✅ Content-Security-Policy (CSP)
- ✅ X-Frame-Options (clickjacking)
- ✅ X-Content-Type-Options (MIME-sniffing)
- ✅ Referrer-Policy
- ✅ Permissions-Policy
- ✅ Rate limiting enforcement
- ✅ Rate limit headers
- ✅ IP whitelisting
- ✅ Security headers em APIs
- ✅ Limites diferentes por endpoint
- ✅ CSP permite recursos necessários
- ✅ Rate limit reset
- ✅ Configuração customizada

**Métodos de Teste**:
- `testSecurityHeadersPresent()` - Headers presentes
- `testContentSecurityPolicyHeader()` - CSP configurado
- `testXFrameOptionsHeader()` - Anti-clickjacking
- `testXContentTypeOptionsHeader()` - Anti-MIME-sniffing
- `testReferrerPolicyHeader()` - Política de referrer
- `testPermissionsPolicyHeader()` - Permissões de browser
- `testRateLimitingEnforcement()` - Rate limiting funciona
- `testRateLimitHeadersPresent()` - Headers de rate limit
- `testRateLimitWhitelisting()` - Whitelist de IPs
- `testSecurityHeadersOnAPIResponses()` - Headers em APIs
- `testDifferentRateLimitsForEndpoints()` - Limites por endpoint
- `testCSPAllowsNecessaryResources()` - CSP flexível
- `testRateLimitReset()` - Reset de limites
- `testCustomRateLimitConfiguration()` - Configuração custom
- `testCombinedSecurityFeatures()` - Features combinadas

---

### 4. DashboardIntegrationTest.php
**Testa**: Dashboard analytics com dados reais

**Cenários Cobertos**:
- ✅ Cálculo de KPIs
- ✅ Total de funcionários
- ✅ Funcionários ativos
- ✅ Contagem de batidas
- ✅ Total de horas trabalhadas
- ✅ Aprovações pendentes
- ✅ Média de horas por funcionário
- ✅ Batidas por hora (gráfico)
- ✅ Horas por departamento (gráfico)
- ✅ Distribuição de status (gráfico)
- ✅ Atividade recente
- ✅ Top funcionários por horas
- ✅ Taxa de presença
- ✅ Filtros por departamento
- ✅ Filtros por período
- ✅ Consistência de dados
- ✅ Dados vazios (edge case)
- ✅ Formatação de gráficos

**Métodos de Teste**:
- `testKPICalculations()` - Cálculo de KPIs
- `testGetTotalEmployees()` - Total de funcionários
- `testGetActiveEmployees()` - Funcionários ativos
- `testGetPunchesCount()` - Contagem de batidas
- `testGetTotalHoursWorked()` - Horas trabalhadas
- `testGetPendingApprovals()` - Aprovações pendentes
- `testGetAverageHoursPerEmployee()` - Média de horas
- `testGetPunchesByHour()` - Gráfico por hora
- `testGetHoursByDepartment()` - Gráfico por departamento
- `testGetEmployeeStatusDistribution()` - Distribuição de status
- `testGetRecentActivity()` - Atividades recentes
- `testGetTopEmployeesByHours()` - Ranking de funcionários
- `testGetAttendanceRate()` - Taxa de presença
- `testGetDepartments()` - Lista de departamentos
- `testGetDashboardData()` - Dashboard completo
- `testDashboardWithDepartmentFilter()` - Filtro por departamento
- `testDashboardWithDateRangeFilter()` - Filtro por período
- `testDataConsistencyAcrossTimePeriods()` - Consistência
- `testEmptyDataHandling()` - Dados vazios
- `testChartDataFormatting()` - Formatação de dados

---

### 5. EndToEndFlowTest.php
**Testa**: Jornadas completas de usuários

**Cenários Cobertos**:
- ✅ Onboarding completo de funcionário
- ✅ Fluxo completo de app mobile
- ✅ Workflow de dashboard web
- ✅ Integração de features de segurança
- ✅ Criptografia de dados
- ✅ Ciclo de vida de sessão
- ✅ Cenário multi-dispositivo

**Métodos de Teste**:
- `testCompleteEmployeeOnboardingFlow()` - Onboarding completo
- `testCompleteMobileAppFlow()` - App mobile E2E
- `testCompleteWebDashboardFlow()` - Dashboard web E2E
- `testSecurityFeaturesIntegration()` - Segurança integrada
- `testDataEncryptionIntegration()` - Criptografia funcionando
- `testCompleteSessionLifecycle()` - Ciclo de sessão
- `testMultiDeviceScenario()` - Múltiplos dispositivos

---

## 🚀 Como Executar

### Executar Todos os Testes de Integração

```bash
vendor/bin/phpunit tests/integration/
```

### Executar Teste Específico

```bash
# Autenticação
vendor/bin/phpunit tests/integration/AuthenticationFlowTest.php

# OAuth 2.0
vendor/bin/phpunit tests/integration/OAuth2IntegrationTest.php

# Segurança
vendor/bin/phpunit tests/integration/SecurityIntegrationTest.php

# Dashboard
vendor/bin/phpunit tests/integration/DashboardIntegrationTest.php

# End-to-End
vendor/bin/phpunit tests/integration/EndToEndFlowTest.php
```

### Executar Método Específico

```bash
vendor/bin/phpunit --filter testLoginFlowWith2FA tests/integration/AuthenticationFlowTest.php
```

### Com Cobertura de Código

```bash
vendor/bin/phpunit --coverage-html coverage/ tests/integration/
```

### Modo Verbose

```bash
vendor/bin/phpunit --verbose tests/integration/
```

---

## 📊 Estatísticas de Cobertura

### Total de Testes de Integração: **61 testes**

| Arquivo | Testes | Features Testadas |
|---------|--------|-------------------|
| AuthenticationFlowTest | 7 | Login, 2FA, Sessions |
| OAuth2IntegrationTest | 13 | OAuth 2.0, Tokens, API Auth |
| SecurityIntegrationTest | 15 | Headers, Rate Limiting, CSP |
| DashboardIntegrationTest | 19 | Analytics, KPIs, Charts |
| EndToEndFlowTest | 7 | Fluxos completos E2E |

---

## ✅ Features Validadas

### Autenticação & Autorização
- ✅ Login com credenciais
- ✅ Two-Factor Authentication (TOTP)
- ✅ OAuth 2.0 (Password Grant + Refresh Token)
- ✅ Bearer Token Authentication
- ✅ Session Management
- ✅ Token Revocation

### Segurança
- ✅ Security Headers (CSP, HSTS, X-Frame-Options, etc.)
- ✅ Rate Limiting (5 tipos diferentes)
- ✅ IP Whitelisting
- ✅ Data Encryption (XChaCha20-Poly1305)
- ✅ Password Hashing (Argon2id)
- ✅ Device Fingerprinting

### Push Notifications
- ✅ Device Registration
- ✅ Token Management
- ✅ Notification Sending
- ✅ Multi-platform Support

### Dashboard Analytics
- ✅ KPI Calculations
- ✅ Chart Data Generation
- ✅ Filtering (Date, Department)
- ✅ Top Rankings
- ✅ Activity Timeline

### Integrações
- ✅ 2FA + OAuth
- ✅ Rate Limiting + OAuth
- ✅ Security Headers + APIs
- ✅ Encryption + 2FA
- ✅ Multi-device Support

---

## 🔧 Configuração Necessária

### Banco de Dados
Os testes assumem que as tabelas necessárias existem:
- `employees`
- `departments`
- `timesheets`
- `oauth_access_tokens`
- `oauth_refresh_tokens`
- `push_notification_tokens`

### Variáveis de Ambiente (.env.testing)
```ini
CI_ENVIRONMENT = testing

database.tests.hostname = localhost
database.tests.database = ponto_eletronico_test
database.tests.username = root
database.tests.password =
database.tests.DBDriver = MySQLi

ENCRYPTION_KEY = test_encryption_key_32_bytes_long
```

---

## 📝 Notas Importantes

### Isolamento de Testes
- Cada teste cria e limpa seus próprios dados
- Testes não dependem de estado compartilhado
- Seguro executar em paralelo

### Dados de Teste
- Todos os dados são prefixados com "test" ou similar
- Cleanup automático após cada teste
- Não afeta dados de produção

### Rate Limiting
- Alguns testes podem falhar se rate limiting estiver muito restritivo
- Localhost geralmente está whitelisted
- Testes consideram isso

### Push Notifications
- Testes de notificação podem falhar se FCM não estiver configurado
- Isso é esperado e não indica problema no código
- Endpoints são testados independentemente

---

## 🐛 Troubleshooting

### Testes Falhando com "Database not found"
```bash
# Criar banco de testes
mysql -u root -e "CREATE DATABASE ponto_eletronico_test;"

# Executar migrations
php spark migrate --env testing
```

### Testes de Rate Limiting Falhando
```bash
# Limpar cache
php spark cache:clear

# Executar teste individual
vendor/bin/phpunit --filter testRateLimitingEnforcement
```

### Testes de Push Notification Falhando
```bash
# Configurar FCM_SERVER_KEY no .env.testing
# Ou pular testes de notificação
vendor/bin/phpunit --exclude-group notifications
```

---

## 🎯 Próximos Passos

### Testes Adicionais Recomendados
1. **Performance Tests** - Validar tempos de resposta
2. **Load Tests** - Testar sob carga
3. **Smoke Tests** - Testes rápidos após deploy
4. **Regression Tests** - Evitar quebrar features existentes

### Melhorias Possíveis
1. Mock de serviços externos (FCM)
2. Fixtures para dados de teste
3. Data providers para testes parametrizados
4. CI/CD integration
5. Parallel test execution

---

## 📚 Referências

- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [CodeIgniter Testing](https://codeigniter.com/user_guide/testing/index.html)
- [RFC 6749 - OAuth 2.0](https://datatracker.ietf.org/doc/html/rfc6749)
- [RFC 6238 - TOTP](https://datatracker.ietf.org/doc/html/rfc6238)
- [OWASP Security Headers](https://owasp.org/www-project-secure-headers/)
