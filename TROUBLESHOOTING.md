# 🔧 Guia de Solução de Problemas

Soluções rápidas para os problemas mais comuns durante instalação.

---

## 🚨 Erro 500 - Internal Server Error

**Causa**: Problema no carregamento do CodeIgniter

**Diagnóstico**:
```
Acesse: https://ponto.supportsondagens.com.br/diagnostico.php
```

**Soluções Comuns**:

### 1. PHPUnit/Autoloader Error
```
Error: Failed opening required phpunit/...
```

**Correção via navegador**:
```
https://ponto.supportsondagens.com.br/fix-autoload.php
```

**Correção via SSH**:
```bash
cd /home/supportson/public_html/ponto
composer dump-autoload --no-dev --optimize
```

### 2. Sistema Não Instalado
```
Error: .env not found
```

**Solução**: Execute o instalador completo
```
https://ponto.supportsondagens.com.br/install.php
```

### 3. Permissões Incorretas
```bash
chmod -R 755 writable/
chown -R www-data:www-data writable/
```

### 4. Conexão com Banco Falhou
- Verifique credenciais no arquivo `.env`
- Teste conexão MySQL manualmente
- Confirme que o banco de dados existe

---

## ⚠️ Warning: session.gc_divisor must be greater than 0

**Correção Automática**: Já implementada no código

**Se persistir**:
```bash
# Via SSH - editar php.ini
session.gc_divisor = 100
session.gc_probability = 1
```

**Alternativa**: O arquivo `.user.ini` já corrige isso automaticamente

---

## 🔴 Composer: PHP version >= 8.3.0 required

**Causa**: Platform check gerado em ambiente diferente

**Correção via navegador**:
```
https://ponto.supportsondagens.com.br/pre-install.php
```

**Correção via SSH**:
```bash
# Opção 1: Remover platform check
rm vendor/composer/platform_check.php

# Opção 2: Reinstalar ignorando plataforma
composer install --no-dev --optimize-autoloader --ignore-platform-reqs
```

---

## ❌ Column not found: 'unique_code' / 'position'

**Causa**: Tabela employees incompleta

**Solução**: Execute o instalador novamente
```
https://ponto.supportsondagens.com.br/install.php
```

O instalador detectará e adicionará colunas faltantes automaticamente.

---

## 📋 Ordem Recomendada de Execução

Para instalação limpa sem erros:

```
1. pre-install.php       ← Corrige configurações PHP e Composer
2. fix-autoload.php      ← Regenera autoloader para produção
3. install.php           ← Instala o sistema completo
4. diagnostico.php       ← Verifica se tudo está OK
```

**Via navegador**:
```
https://ponto.supportsondagens.com.br/pre-install.php
https://ponto.supportsondagens.com.br/fix-autoload.php
https://ponto.supportsondagens.com.br/install.php
https://ponto.supportsondagens.com.br/diagnostico.php
```

**Via SSH**:
```bash
cd /home/supportson/public_html/ponto
php pre-install.php
php fix-autoload.php
php install.php
php diagnostico.php
```

---

## 🔍 Scripts de Diagnóstico Disponíveis

| Script | Função | Quando Usar |
|--------|--------|-------------|
| `pre-install.php` | Verifica e corrige problemas antes da instalação | **Antes** de instalar |
| `fix-autoload.php` | Corrige problemas do Composer autoloader | Erro de PHPUnit/dev deps |
| `diagnostico.php` | Identifica causa do erro 500 | Após instalação, se erro 500 |
| `install.php` | Instalador completo do sistema | Instalação inicial |

---

## 📞 Ainda com Problemas?

1. **Execute o diagnóstico**:
   ```
   https://ponto.supportsondagens.com.br/diagnostico.php
   ```

2. **Copie a mensagem de erro completa**

3. **Verifique os logs**:
   ```bash
   tail -50 writable/logs/log-*.log
   tail -50 writable/logs/php-errors.log
   ```

4. **Informações úteis para reportar**:
   - Mensagem de erro exata
   - Saída do diagnostico.php
   - Versão do PHP (mostrada no diagnostico.php)
   - Últimas linhas dos logs

---

## ✅ Checklist de Instalação Bem-Sucedida

- [ ] PHP 8.1+ instalado
- [ ] Composer dependencies instaladas (`vendor/` existe)
- [ ] Arquivo `.env` criado pelo instalador
- [ ] Banco de dados criado e configurado
- [ ] Tabelas criadas (via migrations ou instalador)
- [ ] Usuário admin criado
- [ ] Diretório `writable/` com permissões corretas (755)
- [ ] Sem erros ao acessar URL raiz
- [ ] Login do admin funcionando

---

## 🎯 Comandos Rápidos de Diagnóstico

```bash
# Verificar versão PHP
php -v

# Verificar extensões PHP
php -m | grep -E "pdo|mysql|mbstring|json|curl|openssl"

# Verificar permissões
ls -la writable/

# Ver logs de erro
tail -50 writable/logs/log-$(date +%Y-%m-%d).log

# Verificar Composer
composer --version

# Regenerar autoloader
composer dump-autoload --no-dev --optimize

# Limpar cache do CodeIgniter
php spark cache:clear
```

---

**Última Atualização**: 2024-11-23
**Versão do Sistema**: 1.0 (Produção)
