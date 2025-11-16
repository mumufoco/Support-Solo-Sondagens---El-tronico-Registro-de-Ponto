# Guia de Início Rápido - Sistema de Ponto Eletrônico

**Versão**: Fase 17+ Híbrida Completa
**Data**: 2024-11-16
**Status**: ✅ **Pronto para Produção**

---

## 🚀 Instalação em 3 Passos

### 1. Clone e Instale Dependências

```bash
git clone https://github.com/mumufoco/Support-Solo-Sondagens---El-tronico-Registro-de-Ponto.git
cd Support-Solo-Sondagens---El-tronico-Registro-de-Ponto
composer install
```

### 2. Execute a Instalação Automatizada

```bash
php install.php
```

O script irá guiá-lo através de:
- ✅ Validação de requisitos
- ✅ Configuração do .env
- ✅ Criação do banco de dados
- ✅ Execução de migrations
- ✅ Criação do usuário admin
- ✅ Validação da instalação

### 3. Inicie o Servidor

```bash
php spark serve
```

Acesse: http://localhost:8080

**Credenciais Padrão**:
- **Usuário**: admin@example.com
- **Senha**: Admin@123
- ⚠️ **ALTERE A SENHA IMEDIATAMENTE!**

---

## 🌐 Instalação Alternativa: Via Navegador Web

**Ideal para usuários não-técnicos ou instalação em servidores de hospedagem!**

### Pré-requisitos
- Servidor web (Apache/Nginx) com PHP 8.1+ configurado
- MySQL 8.0+ instalado
- Composer instalado e `composer install` executado

### Passos

1. **Acesse o instalador web**:
   ```
   http://seu-dominio.com/install.php
   ```

2. **Siga o assistente interativo** (5 etapas):
   - ✅ Verificação automática de requisitos
   - ✅ Configuração do banco de dados via formulário
   - ✅ Criação de usuário administrador personalizado
   - ✅ Execução automática de migrations e seeders
   - ✅ Confirmação e próximos passos

3. **DELETE o arquivo após instalação**:
   ```bash
   rm public/install.php
   ```

**Documentação completa**: [WEB_INSTALLER_GUIDE.md](docs/WEB_INSTALLER_GUIDE.md)

**Vantagens**:
- 🎯 Interface gráfica amigável
- ✅ Validação em tempo real
- 📊 Logs visuais de instalação
- 🔒 Proteção contra reinstalação
- 💡 Ideal para produção

---

## 📊 O Que Está Incluído

### ✅ Fases Implementadas (0-17+)

| Fase | Funcionalidade | Status |
|------|----------------|--------|
| 0-1 | Fundação & Ambiente | ✅ 100% |
| 2-3 | Models & Database | ✅ 100% |
| 4-5 | Geolocalização & Justificativas | ✅ 100% |
| 6-7 | Advertências & LGPD | ✅ 100% |
| 8-10 | Auditoria & Notificações | ✅ 100% |
| 11-13 | Settings & Relatórios | ✅ 100% |
| 14 | Chat & WebSocket | ✅ 100% |
| 15 | Push Web | ✅ 100% |
| 16 | Otimizações de Performance | ✅ 100% |
| **17+** | **Segurança Avançada** | ✅ **100%** |

### 🔐 Fase 17+ - Segurança Enterprise

- ✅ **Criptografia XChaCha20-Poly1305**: Dados sensíveis protegidos
- ✅ **Two-Factor Authentication (2FA)**: TOTP com Google Authenticator
- ✅ **OAuth 2.0**: API mobile com tokens JWT
- ✅ **Push Notifications**: FCM para Android/iOS/Web
- ✅ **Rate Limiting**: Proteção contra ataques
- ✅ **Security Headers**: OWASP compliant
- ✅ **Dashboard Analytics**: 7 KPIs + 3 gráficos

### 📱 4 Métodos de Registro de Ponto

1. **Código Único**: 8 caracteres alfanuméricos
2. **QR Code**: Com assinatura HMAC
3. **Reconhecimento Facial**: DeepFace AI (99.65% acurácia)
4. **Biometria Digital**: SourceAFIS

### 🛡️ Compliance Legal 100%

- ✅ **LGPD Lei 13.709/2018**: Proteção de dados pessoais
- ✅ **Portaria MTE 671/2021**: Registro eletrônico de ponto
- ✅ **CLT Art. 74**: Jornada de trabalho
- ✅ **ICP-Brasil**: Assinatura digital (opcional)

---

## 🧪 Validação & Testes

### Validar Sistema

```bash
php validate-system.php
```

**Resultado Esperado**: 120/120 testes (100%)

### Testar Instalação

```bash
php test-installation.php
```

**Resultado Esperado**: 54/54 testes (100%)

### Executar Testes Automatizados

```bash
# Testes unitários (sem BD)
vendor/bin/phpunit tests/unit/ --testdox

# Testes de integração (requer MySQL)
vendor/bin/phpunit tests/integration/ --testdox

# Todos os testes
vendor/bin/phpunit --testdox
```

**Total de Testes**: 221 (160 unit + 61 integration)

---

## 🐳 Instalação com Docker

### 1. Inicie os Serviços

```bash
docker-compose up -d mysql redis
```

### 2. Aguarde MySQL Ficar Pronto

```bash
docker-compose exec mysql mysqladmin ping -h localhost --silent
```

### 3. Execute a Instalação

```bash
php install.php
```

### 4. Inicie Todos os Serviços

```bash
docker-compose up -d
```

**Serviços Disponíveis**:
- **Aplicação**: http://localhost (Nginx)
- **PHPMyAdmin**: http://localhost:8080
- **Mailhog**: http://localhost:8025
- **DeepFace API**: http://localhost:5000

---

## ⚙️ Configurações Opcionais

### DeepFace API (Reconhecimento Facial)

```bash
cd deepface-api
python3 -m venv venv
source venv/bin/activate
pip install -r requirements.txt
python app.py
```

### WebSocket Server (Chat)

```bash
php websocket-server.php
```

### Push Notifications (FCM)

1. Crie projeto no [Firebase Console](https://console.firebase.google.com)
2. Obtenha FCM_SERVER_KEY e FCM_SENDER_ID
3. Adicione ao `.env`:

```ini
FCM_SERVER_KEY=your_server_key
FCM_SENDER_ID=your_sender_id
```

---

## 📈 Métricas do Sistema

### Código

- **Total de Arquivos PHP**: 5.326
- **Models**: 18
- **Controllers**: 31
- **Services**: 28
- **Filters**: 8
- **Migrations**: 21
- **Testes**: 221

### Documentação

- **README.md**: Documentação principal
- **SYSTEM_VALIDATION_REPORT_PHASES_0-17.md**: Relatório completo
- **TESTING_GUIDE.md**: Guia de testes
- **TEST_VALIDATION_REPORT.md**: Validação de testes
- **QUICK_START.md**: Este guia

**Total**: ~4.000+ linhas de documentação

---

## 🔧 Solução de Problemas

### Erro: "Unable to connect to database"

```bash
# Verificar se MySQL está rodando
sudo service mysql status

# Ou com Docker
docker-compose ps mysql
```

### Erro: "ENCRYPTION_KEY not set"

```bash
# Gerar nova chave
php spark encryption:generate-key

# Ou deixe install.php gerar automaticamente
```

### Erro: "Permission denied" em storage/

```bash
# Dar permissões de escrita
chmod -R 775 storage/
chown -R www-data:www-data storage/
```

### Migrations Falhando

```bash
# Verificar status
php spark migrate:status

# Reverter última migration
php spark migrate:rollback

# Executar novamente
php spark migrate
```

---

## 📚 Próximos Passos

### 1. Configuração Inicial

- [ ] Alterar senha do admin
- [ ] Configurar empresa (.env)
- [ ] Configurar DPO (LGPD)
- [ ] Configurar SMTP (email)

### 2. Dados Iniciais

- [ ] Criar departamentos
- [ ] Cadastrar funcionários
- [ ] Configurar geofences
- [ ] Definir escalas de trabalho

### 3. Segurança

- [ ] Habilitar 2FA para admins
- [ ] Configurar whitelist de IPs
- [ ] Revisar security headers
- [ ] Configurar backup automático

### 4. Produção

- [ ] Configurar HTTPS
- [ ] Configurar domínio
- [ ] Otimizar MySQL
- [ ] Configurar Redis cache
- [ ] Monitoramento (logs)

---

## 🆘 Suporte

### Documentação Completa

- `docs/SYSTEM_VALIDATION_REPORT_PHASES_0-17.md` - Validação completa
- `docs/TESTING_GUIDE.md` - Guia de testes
- `tests/integration/README.md` - Testes E2E

### Comandos Úteis

```bash
# Validar sistema
php validate-system.php

# Testar instalação
php test-installation.php

# Limpar cache
php spark cache:clear

# Ver logs
tail -f storage/logs/log-$(date +%Y-%m-%d).log

# Backup do banco
mysqldump -u root -p ponto_eletronico > backup.sql
```

### Executar Testes

```bash
# Testes rápidos (sem BD)
vendor/bin/phpunit tests/unit/Services/Security/

# Testes completos
vendor/bin/phpunit

# Com cobertura
vendor/bin/phpunit --coverage-html coverage/
```

---

## ✅ Checklist de Validação

Antes de ir para produção, certifique-se:

- [x] Instalação executada com sucesso
- [x] `php validate-system.php` = 100%
- [x] `php test-installation.php` = 100%
- [ ] Senha admin alterada
- [ ] HTTPS configurado
- [ ] Backup automático configurado
- [ ] ENCRYPTION_KEY segura
- [ ] Testes executados (221/221)
- [ ] Documentação revisada
- [ ] Equipe treinada

---

## 🎉 Conclusão

**Parabéns!** Você tem em mãos um sistema completo, seguro e em conformidade com a legislação brasileira.

### Recursos Principais

✅ 4 métodos de registro de ponto
✅ Reconhecimento facial AI
✅ Compliance LGPD 100%
✅ Segurança enterprise-grade
✅ 221 testes automatizados
✅ Documentação abrangente
✅ Pronto para produção

### Performance

- ⚡ 20+ índices otimizados
- ⚡ 5 views de banco de dados
- ⚡ Cache LRU para facial
- ⚡ Fila assíncrona de relatórios

### Segurança

- 🔐 Criptografia XChaCha20-Poly1305
- 🔐 Two-Factor Authentication
- 🔐 OAuth 2.0 + JWT
- 🔐 Rate Limiting
- 🔐 OWASP Security Headers

---

**Desenvolvido com ❤️ para atender 100% da legislação brasileira**

**Última Atualização**: 2024-11-16
**Versão**: Fase 17+ Híbrida Completa
**Status**: ✅ Pronto para Produção
