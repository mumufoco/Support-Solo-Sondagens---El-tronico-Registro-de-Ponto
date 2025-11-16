# 🌐 Guia do Instalador Web

## Visão Geral

O Sistema de Ponto Eletrônico inclui um **instalador web interativo** que permite configurar todo o sistema através do navegador, sem necessidade de linha de comando.

## 📋 Pré-requisitos

Antes de usar o instalador web, certifique-se de que:

1. **Servidor Web configurado** (Apache/Nginx com PHP-FPM)
2. **PHP 8.1+** instalado com extensões:
   - `intl`, `mbstring`, `json`, `mysqlnd`, `gd`, `curl`, `sodium`
3. **MySQL 8.0+** instalado e rodando
4. **Composer** instalado e `composer install` executado
5. **Permissões de escrita** nas pastas `writable/` e raiz do projeto

## 🚀 Como Usar

### Passo 1: Acessar o Instalador

Abra seu navegador e acesse:

```
http://seu-dominio.com/install.php
```

Ou em ambiente local:

```
http://localhost:8080/install.php
```

### Passo 2: Verificação de Requisitos

O instalador automaticamente verificará:

- ✅ Versão do PHP (mínimo 8.1.0)
- ✅ Extensões PHP necessárias
- ✅ Permissões de escrita em diretórios
- ✅ Disponibilidade do MySQL

**Possíveis Problemas:**

- ❌ **Extensão faltando**: Instale via `apt install php-extensao` ou `yum install php-extensao`
- ❌ **Permissão negada**: Execute `chmod -R 775 writable/` e `chown -R www-data:www-data writable/`
- ❌ **PHP antigo**: Atualize para PHP 8.1+ ou superior

### Passo 3: Configurar Banco de Dados

Preencha os campos:

| Campo | Descrição | Exemplo |
|-------|-----------|---------|
| **Host** | Endereço do servidor MySQL | `localhost` ou `127.0.0.1` |
| **Porta** | Porta do MySQL | `3306` (padrão) |
| **Nome do Banco** | Nome do banco de dados | `ponto_eletronico` |
| **Usuário** | Usuário MySQL | `root` ou usuário criado |
| **Senha** | Senha do MySQL | Sua senha MySQL |

**Importante:**
- O banco de dados será **criado automaticamente** se não existir
- O usuário deve ter permissões `CREATE DATABASE` e `CREATE TABLE`
- Use charset `utf8mb4` (feito automaticamente)

**Teste de Conexão:**

Clique em **"Testar Conexão"** para verificar:
- ✅ Conexão com MySQL estabelecida
- ✅ Permissões adequadas
- ✅ Banco de dados criado (se não existia)

### Passo 4: Criar Usuário Administrador

Configure o primeiro usuário administrador:

| Campo | Descrição | Exemplo |
|-------|-----------|---------|
| **Nome da Empresa** | Razão social | `Empresa LTDA` |
| **CNPJ** | CNPJ da empresa | `00.000.000/0000-00` |
| **Nome do Admin** | Nome completo | `João da Silva` |
| **Email** | Email de acesso | `admin@empresa.com.br` |
| **Senha** | Senha segura (min 8 caracteres) | `Admin@2024!` |

**Importante:**
- Use uma **senha forte** (mínimo 8 caracteres, letras, números e símbolos)
- A senha será criptografada com **Argon2id** (mais seguro que bcrypt)
- **Altere a senha** após o primeiro login!

### Passo 5: Executar Instalação

Clique em **"Instalar Sistema"** e aguarde:

**O que acontece durante a instalação:**

```
1. Criando arquivo .env...
   ✓ Arquivo .env criado

2. Executando migrations do banco de dados...
   ✓ Migrations executadas com sucesso

3. Criando usuário administrador...
   ✓ Usuário administrador criado

4. Executando seeders (configurações iniciais)...
   ✓ Seeders executados

5. Finalizando instalação...
   ✓ Arquivo de proteção criado
```

**Duração:** 30-60 segundos (depende do servidor)

**Em caso de erro:**
- Logs detalhados serão exibidos na tela
- Você pode clicar em **"Tentar Novamente"** após corrigir
- Veja **"Ver detalhes das migrations"** para diagnóstico

### Passo 6: Conclusão

Após instalação bem-sucedida, você verá:

✅ **Resumo da Instalação**
- Banco de dados criado
- 21+ tabelas criadas
- Usuário admin criado
- Configurações inicializadas

🔑 **Credenciais de Acesso**
- Email e senha configurados

⚠️ **Ações de Segurança Obrigatórias**
- **DELETE `public/install.php` IMEDIATAMENTE!**
- Altere a senha após primeiro login
- Configure HTTPS em produção

## 🔒 Segurança

### Proteção Contra Reinstalação

Após instalação bem-sucedida:
- Arquivo `writable/installed.lock` é criado
- Tentativas de acessar `install.php` serão bloqueadas
- Mensagem de aviso será exibida

### Deletar o Instalador

**CRÍTICO:** Delete o arquivo após instalação:

```bash
rm public/install.php
```

Ou via FTP/painel de controle do servidor.

**Por quê?**
- Evita reinstalação acidental
- Previne acesso não autorizado
- Elimina vetor de ataque

## 🛠️ Troubleshooting

### Erro: "Extensão X não encontrada"

```bash
# Ubuntu/Debian
sudo apt install php8.1-intl php8.1-mbstring php8.1-mysql php8.1-gd php8.1-curl
sudo systemctl restart apache2

# CentOS/RHEL
sudo yum install php81-intl php81-mbstring php81-mysqlnd php81-gd php81-curl
sudo systemctl restart httpd
```

### Erro: "Permission denied em writable/"

```bash
# Dar permissões corretas
sudo chown -R www-data:www-data writable/
sudo chmod -R 775 writable/

# Verificar permissões
ls -la writable/
```

### Erro: "SQLSTATE[42000]: Access denied"

**Problema:** Usuário MySQL sem permissões

**Solução:**

```sql
-- Conectar como root
mysql -u root -p

-- Criar usuário e dar permissões
CREATE USER 'ponto_user'@'localhost' IDENTIFIED BY 'senha_segura';
GRANT ALL PRIVILEGES ON ponto_eletronico.* TO 'ponto_user'@'localhost';
FLUSH PRIVILEGES;
```

### Erro: "Table 'employees' doesn't exist"

**Problema:** Migrations não executaram completamente

**Solução 1:** Tentar novamente pelo instalador web

**Solução 2:** Executar script de correção:

```bash
php fix-installation.php
```

**Solução 3:** Executar manualmente:

```bash
php spark migrate --all
php spark db:seed AdminUserSeeder
php spark db:seed SettingsSeeder
```

### Erro: "Arquivo spark não encontrado"

**Problema:** Composer não instalado ou incompleto

**Solução:**

```bash
# Instalar dependências
composer install

# Copiar spark para raiz
cp vendor/codeigniter4/framework/spark .
chmod +x spark
```

### Instalador não abre (tela branca)

**Problema:** Erro de sintaxe PHP ou configuração

**Solução:**

```bash
# Ver logs de erro
tail -f /var/log/apache2/error.log
# ou
tail -f /var/log/php-fpm/error.log

# Verificar sintaxe
php -l public/install.php

# Habilitar display_errors temporariamente
echo "display_errors = On" >> /etc/php/8.1/apache2/php.ini
sudo systemctl restart apache2
```

## 📊 O Que É Criado

### Arquivo .env

```env
CI_ENVIRONMENT = production
app.baseURL = 'http://seu-dominio.com'
encryption.key = base64:xxxx...  # 32 bytes para XChaCha20-Poly1305

database.default.hostname = localhost
database.default.database = ponto_eletronico
database.default.username = seu_usuario
database.default.password = sua_senha
database.default.port = 3306
```

### Banco de Dados (21+ Tabelas)

**Principais tabelas criadas:**

1. `employees` - Funcionários
2. `time_punches` - Registros de ponto
3. `biometric_templates` - Dados biométricos
4. `justifications` - Justificativas de ausências
5. `warnings` - Advertências
6. `settings` - Configurações do sistema
7. `two_factor_auth` - Autenticação 2FA
8. `oauth_tokens` - Tokens OAuth 2.0
9. `push_notification_tokens` - Tokens FCM
10. `rate_limits` - Controle de taxa
11. `migrations` - Histórico de migrations

### Usuário Administrador

- Email: (configurado por você)
- Senha: Criptografada com **Argon2id**
- Role: `admin`
- Código único: `ADM000001`
- Status: Ativo

### Configurações Iniciais

Inseridas via `SettingsSeeder`:

- Nome da empresa
- CNPJ
- Configurações de horário
- Tolerância de atrasos
- Regras de banco de horas
- Configurações de notificação

## 🎯 Próximos Passos

Após instalação bem-sucedida:

1. **Delete `public/install.php`** ⚠️
2. Acesse o sistema via navegador
3. Faça login com as credenciais criadas
4. **Altere a senha imediatamente**
5. Configure informações da empresa
6. Cadastre departamentos e cargos
7. Cadastre funcionários
8. Configure biometria/reconhecimento facial
9. Configure notificações (opcional)
10. Configure HTTPS em produção

## 🆚 Instalador Web vs CLI

| Recurso | Web Installer | CLI (install.php) |
|---------|---------------|-------------------|
| **Interface** | Gráfica (navegador) | Terminal/linha de comando |
| **Facilidade** | ⭐⭐⭐⭐⭐ Muito fácil | ⭐⭐⭐ Médio |
| **Ideal para** | Usuários não-técnicos | Desenvolvedores/DevOps |
| **Customização** | Limitada | Total |
| **Debugging** | Logs visuais | Output completo |
| **Automação** | Não | Sim (scripts) |
| **Segurança** | Deve deletar após uso | Pode manter |

**Recomendação:**
- **Produção/Usuários finais**: Use Web Installer
- **Desenvolvimento/CI/CD**: Use CLI

## 📚 Referências

- [QUICK_START.md](QUICK_START.md) - Guia de início rápido
- [SYSTEM_VALIDATION_REPORT.md](SYSTEM_VALIDATION_REPORT_PHASES_0-17.md) - Relatório de validação
- [Documentação CodeIgniter 4](https://codeigniter.com/user_guide/)
- [PHP Manual](https://www.php.net/manual/pt_BR/)

## 🆘 Suporte

Se encontrar problemas:

1. Verifique os logs de erro
2. Execute script de diagnóstico: `php fix-installation.php`
3. Verifique requisitos mínimos
4. Consulte seção Troubleshooting acima
5. Verifique documentação do CodeIgniter 4

---

**Sistema de Ponto Eletrônico** © 2024
Conforme Portaria MTE 671/2021 e LGPD Lei 13.709/2018
