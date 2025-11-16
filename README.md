# Sistema de Ponto Eletrônico Brasileiro

[![PHP Version](https://img.shields.io/badge/PHP-8.1+-blue.svg)](https://www.php.net/)
[![CodeIgniter](https://img.shields.io/badge/CodeIgniter-4.4+-red.svg)](https://codeigniter.com/)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)
[![LGPD](https://img.shields.io/badge/LGPD-Compliant-success.svg)](https://www.gov.br/lgpd/)

Sistema completo de registro de ponto eletrônico para empresas brasileiras, com conformidade total à **Portaria MTE 671/2021**, **CLT Art. 74** e **LGPD**.

## 🚀 Funcionalidades

### ✅ Registro de Ponto (4 Métodos)
- **Código Único** - 8 caracteres alfanuméricos
- **QR Code** - Com assinatura HMAC e expiração
- **Reconhecimento Facial** - DeepFace com anti-spoofing
- **Biometria Digital** - SourceAFIS (opcional)

### 📍 Geolocalização
- Captura automática de coordenadas GPS
- Sistema de cerca virtual (geofencing)
- Alertas para registros fora da área permitida

### 📊 Gestão Completa
- Cálculo automático de jornada de trabalho
- Banco de horas (positivo/negativo)
- Folha de ponto digital com NSR e Hash SHA-256
- Comprovante eletrônico em PDF
- Relatórios completos (PDF, Excel, CSV)

### 💬 Comunicação
- Chat interno em tempo real (WebSocket)
- Notificações por e-mail/push
- Sistema de justificativas de ausências

### 🔐 Conformidade Legal
- **Portaria MTE 671/2021** - Registro eletrônico de ponto
- **CLT Art. 74** - Jornada de trabalho
- **LGPD Lei 13.709/2018** - Proteção de dados
- Assinatura digital ICP-Brasil (opcional)
- Sistema de advertências com assinatura

### 🛡️ Proteção de Dados (LGPD)
- Portal de consentimentos
- Direito de portabilidade
- Exportação completa de dados
- Auditoria de 10 anos
- DPO configurável

## 🏗️ Stack Tecnológica

### Backend
- **PHP 8.1+**
- **CodeIgniter 4**
- **MySQL 8.0+**

### Frontend
- **HTML5, JavaScript ES6+**
- **Bootstrap 5**
- **Leaflet.js** (mapas)
- **Chart.js** (gráficos)

### Biometria e IA
- **DeepFace** (Python + Flask) - Reconhecimento facial
- **Modelo:** VGG-Face (99.65% acurácia)
- **Anti-spoofing** integrado

### Infraestrutura
- **VPS Ubuntu 22.04** (4GB RAM)
- **WebSocket** (Workerman)
- **OpenStreetMap + Nominatim**

## 📋 Requisitos

### Servidor
- **PHP 8.1+** com extensões:
  - mbstring, intl, gd, curl, mysqli, sodium, zip
- **MySQL 8.0+** ou MariaDB 10.6+
- **Python 3.8+** para DeepFace
- **Apache 2.4+** ou Nginx 1.18+
- **4GB RAM** mínimo
- **20GB** espaço em disco

### Desenvolvimento
- **Composer** 2.x
- **Git**
- **Node.js 16+** (opcional, para build de assets)

## 🔧 Instalação

### 1. Clonar o Repositório

```bash
git clone https://github.com/mumufoco/Support-Solo-Sondagens---El-tronico-Registro-de-Ponto.git
cd Support-Solo-Sondagens---El-tronico-Registro-de-Ponto
```

### 2. Instalar Dependências PHP

```bash
composer install
```

### 3. Configurar Ambiente

```bash
cp .env.example .env
php spark key:generate
```

Edite o arquivo `.env` com suas configurações:
- Banco de dados
- DeepFace API URL
- Configurações de e-mail
- Informações da empresa

### 4. Criar Banco de Dados

```bash
mysql -u root -p
```

```sql
CREATE DATABASE ponto_eletronico CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
exit;
```

### 5. Executar Migrations

```bash
php spark migrate
php spark db:seed AdminUserSeeder
php spark db:seed SettingsSeeder
```

### 6. Configurar DeepFace API

```bash
cd deepface-api
python3 -m venv venv
source venv/bin/activate  # Linux/Mac
# ou
venv\Scripts\activate     # Windows

pip install -r requirements.txt
```

### 7. Iniciar Serviços

```bash
# Terminal 1 - Aplicação principal
php spark serve --port=8000

# Terminal 2 - DeepFace API
cd deepface-api
source venv/bin/activate
python app.py

# Terminal 3 - WebSocket (Chat)
php scripts/websocket_server.php
```

### 8. Acessar o Sistema

Abra o navegador em: `http://localhost:8000`

**Login padrão:**
- **E-mail:** admin@ponto.com.br
- **Senha:** Admin@123

⚠️ **IMPORTANTE:** Altere a senha padrão após o primeiro login!

## 📚 Documentação

- [Guia de Instalação Completo](docs/INSTALL.md)
- [Documentação da API](docs/API.md)
- [Conformidade LGPD](docs/LGPD.md)
- [Resolução de Problemas](docs/TROUBLESHOOTING.md)
- [Changelog](CHANGELOG.md)

## 🧪 Testes

### Executar Testes Unitários

```bash
./vendor/bin/phpunit
```

### Executar com Coverage

```bash
./vendor/bin/phpunit --coverage-html coverage/
```

### Verificar Health da API DeepFace

```bash
curl http://localhost:5000/health
```

## 📦 Deploy em Produção

```bash
./scripts/deploy.sh --production
```

Consulte [docs/DEPLOY.md](docs/DEPLOY.md) para instruções detalhadas.

## 💰 Custos Estimados

### Desenvolvimento
- **R$ 36.000 - 84.000** (450-700 horas)

### Infraestrutura Anual
- **VPS:** €59.88/ano (~R$ 360/ano)
- **Domínio:** R$ 40/ano
- **ICP-Brasil:** R$ 200-400/ano (opcional)
- **Total:** R$ 600-800/ano

## 🔐 Segurança

- Senha hash Argon2id
- Proteção contra brute force
- CSRF tokens
- Rate limiting
- HTTPS obrigatório em produção
- Criptografia AES-256 para dados biométricos
- Anti-spoofing facial
- Auditoria completa de ações

## 📄 Licença

Este projeto está licenciado sob a [Licença MIT](LICENSE).

## 👥 Autores

- **Mumufoco Team** - [GitHub](https://github.com/mumufoco)

## 🤝 Contribuindo

Contribuições são bem-vindas! Por favor, leia [CONTRIBUTING.md](CONTRIBUTING.md) para detalhes.

## 📞 Suporte

- **Issues:** [GitHub Issues](https://github.com/mumufoco/Support-Solo-Sondagens---El-tronico-Registro-de-Ponto/issues)
- **E-mail:** suporte@pontoeletronico.com.br
- **Documentação:** [Wiki](https://github.com/mumufoco/Support-Solo-Sondagens---El-tronico-Registro-de-Ponto/wiki)

## 🙏 Agradecimentos

- CodeIgniter Framework
- DeepFace (Serengil)
- OpenStreetMap
- Comunidade PHP Brasil

---

**Desenvolvido com ❤️ para empresas brasileiras**

🎯 **Conformidade:** MTE 671/2021 | CLT Art. 74 | LGPD
