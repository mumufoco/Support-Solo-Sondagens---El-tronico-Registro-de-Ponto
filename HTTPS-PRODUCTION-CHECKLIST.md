# ✅ Checklist de Configuração HTTPS para Produção

## 🌐 Ambiente
- **Tipo:** VPS (Virtual Private Server) - Servidor Cloud
- **Banco de Dados:** MySQL
- **Acesso:** Internet (domínio público)

## Status da Implementação
✅ Código implementado e testado
✅ HTTPS enforcement ativo em rotas biométricas
✅ Audit logging configurado
✅ Compatível com MySQL
⏳ Aguardando configuração do VPS

---

## 📋 Checklist de Configuração do Servidor

### 1. Certificado SSL/TLS (OBRIGATÓRIO)

**Ação:** Instalar certificado SSL válido no VPS

**Opções para VPS:**
- ✅ **Let's Encrypt** (gratuito, renovação automática) - **RECOMENDADO**
- ✅ Certificado comercial (DigiCert, Sectigo, etc.)
- ⚠️ Certificado auto-assinado (apenas para testes, não produção)

**IMPORTANTE:** Para VPS com acesso público, SEMPRE use Let's Encrypt ou certificado comercial válido.

#### **Let's Encrypt (RECOMENDADO - Gratuito e Automático)**

**Pré-requisitos:**
- Domínio apontando para o IP do VPS (ex: ponto.supportsondagens.com.br → IP.DO.VPS)
- Portas 80 e 443 abertas no firewall
- Servidor web (nginx ou Apache) instalado

```bash
# 1. Instalar Certbot (nginx)
sudo apt update
sudo apt install certbot python3-certbot-nginx -y

# 2. Obter e instalar certificado automaticamente
sudo certbot --nginx -d ponto.supportsondagens.com.br

# OU para Apache:
# sudo apt install certbot python3-certbot-apache -y
# sudo certbot --apache -d ponto.supportsondagens.com.br

# 3. Testar renovação automática
sudo certbot renew --dry-run
```

**Renovação Automática:**
Let's Encrypt configura renovação automática via cron/systemd timer. Verificar:
```bash
sudo systemctl status certbot.timer
```

**Verificação:**
```bash
# Testar se certificado está ativo
curl -I https://ponto.supportsondagens.com.br
# Deve retornar: HTTP/2 200 OK

# Verificar detalhes do certificado
openssl s_client -connect ponto.supportsondagens.com.br:443 -servername ponto.supportsondagens.com.br | grep -A2 "Verify return"
# Deve retornar: Verify return code: 0 (ok)
```

#### **Opção B: Certificado Auto-Assinado (APENAS para testes)**

**⚠️ NÃO RECOMENDADO para produção!** Apenas para ambiente de desenvolvimento/homologação.

```bash
# Criar certificado auto-assinado
sudo mkdir -p /etc/ssl/ponto
sudo openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
  -keyout /etc/ssl/ponto/server.key \
  -out /etc/ssl/ponto/server.crt \
  -subj "/C=BR/ST=SP/L=SaoPaulo/O=SupportSondagens/CN=ponto.local"

sudo chmod 600 /etc/ssl/ponto/server.key
sudo chmod 644 /etc/ssl/ponto/server.crt
```

---

### 2. Configuração do Servidor Web

#### **A) Nginx (se estiver usando Nginx como proxy reverso)**

**Arquivo:** `/etc/nginx/sites-available/ponto.supportsondagens.com.br`

```nginx
server {
    listen 80;
    server_name ponto.supportsondagens.com.br;

    # Redirecionar HTTP -> HTTPS
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name ponto.supportsondagens.com.br;

    # Certificado SSL
    ssl_certificate /etc/letsencrypt/live/ponto.supportsondagens.com.br/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/ponto.supportsondagens.com.br/privkey.pem;

    # Configurações SSL recomendadas
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;
    ssl_prefer_server_ciphers on;

    # CRÍTICO: Headers para proxy reverso
    location / {
        proxy_pass http://127.0.0.1:8080; # Porta do PHP-FPM ou Apache

        # IMPORTANTE: Passar informação de HTTPS para CodeIgniter
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header Host $host;
    }

    # Ou se estiver usando FastCGI diretamente:
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;

        # IMPORTANTE: Passar informação de HTTPS para CodeIgniter
        fastcgi_param HTTPS on;
        fastcgi_param X-Forwarded-Proto $scheme;
    }
}
```

**Reiniciar Nginx:**
```bash
sudo nginx -t  # Testar configuração
sudo systemctl reload nginx
```

---

#### **B) Apache (se estiver usando Apache diretamente)**

**Arquivo:** `/etc/apache2/sites-available/ponto.supportsondagens.com.br.conf`

```apache
<VirtualHost *:80>
    ServerName ponto.supportsondagens.com.br

    # Redirecionar HTTP -> HTTPS
    Redirect permanent / https://ponto.supportsondagens.com.br/
</VirtualHost>

<VirtualHost *:443>
    ServerName ponto.supportsondagens.com.br
    DocumentRoot /var/www/ponto/public

    # SSL Configuration
    SSLEngine on
    SSLCertificateFile /etc/letsencrypt/live/ponto.supportsondagens.com.br/fullchain.pem
    SSLCertificateKeyFile /etc/letsencrypt/live/ponto.supportsondagens.com.br/privkey.pem

    # Protocolo SSL
    SSLProtocol all -SSLv3 -TLSv1 -TLSv1.1
    SSLCipherSuite HIGH:!aNULL:!MD5

    # CodeIgniter configuração
    <Directory /var/www/ponto/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted

        # Habilitar mod_rewrite
        RewriteEngine On
        RewriteCond %{REQUEST_FILENAME} !-f
        RewriteCond %{REQUEST_FILENAME} !-d
        RewriteRule ^(.*)$ index.php/$1 [L]
    </Directory>

    # IMPORTANTE: Variável HTTPS já é setada automaticamente pelo Apache
    # Não precisa de configuração adicional
</Directory>
</VirtualHost>
```

**Reiniciar Apache:**
```bash
# Habilitar módulos necessários
sudo a2enmod ssl
sudo a2enmod rewrite
sudo a2enmod headers

# Testar e reiniciar
sudo apachectl configtest
sudo systemctl reload apache2
```

---

### 3. Configuração da Aplicação (.env)

**Arquivo:** `/var/www/ponto/.env`

```env
#--------------------------------------------------------------------
# HTTPS Configuration
#--------------------------------------------------------------------

# Base URL DEVE usar https:// em produção
app.baseURL = 'https://ponto.supportsondagens.com.br/'

# NÃO forçar HTTPS global (nossa implementação é granular)
# Deixar false para permitir rotas públicas via HTTP se necessário
app.forceGlobalSecureRequests = false

#--------------------------------------------------------------------
# Proxy Configuration (apenas se usar nginx/Apache como proxy)
#--------------------------------------------------------------------

# Se usar proxy reverso, adicionar IP do proxy aqui
# Exemplo: app.proxyIPs = 127.0.0.1,::1
# app.proxyIPs =

#--------------------------------------------------------------------
# Security
#--------------------------------------------------------------------

# Cookie Secure: será setado automaticamente para true quando HTTPS ativo
# (configurado no App.php constructor)
```

---

### 4. Verificação de Proxy (se aplicável)

**Se estiver usando proxy reverso (nginx -> Apache/PHP-FPM):**

**Verificar se headers estão sendo passados:**
```bash
# Criar arquivo de teste: /var/www/ponto/public/test-https.php
<?php
header('Content-Type: text/plain');
echo "HTTPS: " . ($_SERVER['HTTPS'] ?? 'not set') . "\n";
echo "X-Forwarded-Proto: " . ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? 'not set') . "\n";
echo "Request Scheme: " . ($_SERVER['REQUEST_SCHEME'] ?? 'not set') . "\n";
echo "\n=== ALL HEADERS ===\n";
print_r(getallheaders());
?>
```

**Acessar via HTTPS:**
```bash
curl https://ponto.supportsondagens.com.br/test-https.php
```

**Resultado esperado:**
```
HTTPS: on
X-Forwarded-Proto: https
Request Scheme: https
```

**Se `X-Forwarded-Proto` estiver "not set":**
- Verificar configuração do nginx (proxy_set_header X-Forwarded-Proto $scheme;)
- Adicionar IP do proxy em app/Config/App.php: `public array $proxyIPs = ['127.0.0.1', '::1'];`

**IMPORTANTE:** Deletar `test-https.php` após verificação!

---

### 5. Testes de Segurança Biométrica

**Após configurar HTTPS, testar as rotas biométricas:**

#### **Teste 1: Verificar bloqueio de HTTP**
```bash
# Tentar cadastrar biometria via HTTP (deve falhar)
curl -X POST http://ponto.supportsondagens.com.br/biometric/face/enroll \
  -H "Content-Type: application/json" \
  -d '{"photo":"base64data"}'

# Resultado esperado: 403 Forbidden
# { "success": false, "message": "Biometric data must be transmitted over HTTPS." }
```

#### **Teste 2: Verificar funcionamento com HTTPS**
```bash
# Tentar cadastrar biometria via HTTPS (deve permitir se autenticado)
curl -X POST https://ponto.supportsondagens.com.br/biometric/face/enroll \
  -H "Content-Type: application/json" \
  -d '{"photo":"base64data"}'

# Deve retornar erro de autenticação (normal) ou processar a requisição
```

#### **Teste 3: Verificar audit logs**
```bash
# Verificar se violações HTTP foram logadas
mysql -u root -p sistema_ponto_eletronico
SELECT * FROM audit_logs WHERE action = 'HTTPS_VIOLATION' ORDER BY created_at DESC LIMIT 5;
```

---

### 6. Monitoramento e Alertas

**Configurar monitoramento:**

1. **Certificado SSL expirando:**
   - Let's Encrypt renova automaticamente
   - Verificar: `sudo certbot renew --dry-run`

2. **Violações de HTTPS:**
   ```sql
   -- Criar alerta para múltiplas violações
   SELECT
       DATE(created_at) as data,
       COUNT(*) as total_violacoes,
       COUNT(DISTINCT user_id) as usuarios_afetados
   FROM audit_logs
   WHERE action = 'HTTPS_VIOLATION'
   GROUP BY DATE(created_at)
   HAVING COUNT(*) > 10;
   ```

3. **SSL Labs Test:**
   ```
   https://www.ssllabs.com/ssltest/analyze.html?d=ponto.supportsondagens.com.br

   Alvo: Nota A ou A+
   ```

---

## 🔐 Rotas Protegidas por HTTPS

As seguintes rotas **EXIGEM HTTPS** (retornam 403 se acessadas via HTTP):

### FaceRecognitionController
- `POST /biometric/face/enroll` - Cadastro de biometria facial
- `POST /biometric/face/test` - Teste de reconhecimento facial

### FingerprintController
- `POST /fingerprint/enroll` - Cadastro de impressão digital
- `POST /fingerprint/test` - Teste de reconhecimento de impressão digital

**Todas as outras rotas continuam funcionando com HTTP/HTTPS.**

---

## 📊 Verificação Final

### Checklist de Produção

- [ ] Certificado SSL instalado e válido
- [ ] Redirecionamento HTTP → HTTPS configurado no servidor web
- [ ] Headers de proxy configurados (se aplicável)
- [ ] `.env` com `app.baseURL = 'https://...'`
- [ ] Teste manual de rota biométrica via HTTP (deve bloquear)
- [ ] Teste manual de rota biométrica via HTTPS (deve funcionar)
- [ ] Audit logs registrando violações HTTPS
- [ ] SSL Labs test com nota A/A+
- [ ] Renovação automática de certificado configurada

---

## 🚨 Troubleshooting

### Problema: "requireHttps() não está bloqueando HTTP"

**Causa:** Servidor não está passando informação de HTTPS corretamente

**Solução:**
```bash
# Verificar se $request->isSecure() detecta HTTPS
# Adicionar em BaseController.php temporariamente:
log_message('debug', 'HTTPS Detection: ' . ($this->request->isSecure() ? 'YES' : 'NO'));
log_message('debug', '$_SERVER[HTTPS]: ' . ($_SERVER['HTTPS'] ?? 'not set'));
log_message('debug', 'X-Forwarded-Proto: ' . ($this->request->getHeaderLine('X-Forwarded-Proto') ?? 'not set'));

# Verificar logs
tail -f /var/www/ponto/writable/logs/log-*.log
```

### Problema: "Todos os requests estão sendo bloqueados, mesmo HTTPS"

**Causa:** Proxy não está passando headers corretos

**Solução:**
1. Adicionar IP do proxy em `app/Config/App.php`:
   ```php
   public array $proxyIPs = ['127.0.0.1', '::1', '10.0.0.1']; // IPs do seu proxy
   ```

2. Verificar nginx/Apache está enviando headers

---

## 📞 Suporte

Se encontrar problemas após seguir este checklist:

1. Verificar logs da aplicação: `/var/www/ponto/writable/logs/`
2. Verificar logs do servidor web: `/var/log/nginx/` ou `/var/log/apache2/`
3. Consultar audit_logs no banco de dados
4. Criar issue com detalhes do erro

---

**Última atualização:** 2025-12-06
**Responsável:** Sistema de Segurança Biométrica
**Prioridade:** 🔴 CRÍTICA (Requisito de produção)
