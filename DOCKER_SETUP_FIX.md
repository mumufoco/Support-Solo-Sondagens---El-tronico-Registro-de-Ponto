# Guia de Correção: Docker Compose V2 Migration

**Data:** 16 de Novembro de 2025
**Problema:** Falha de inicialização do Docker com erro `Not supported URL scheme http+docker`
**Causa:** Incompatibilidade entre docker-compose 1.29.2 (legado) e daemon Docker moderno
**Solução:** Migração para Docker Compose V2 (Plugin)

---

## 🔍 Diagnóstico

### Erro Identificado
```bash
urllib3.exceptions.URLSchemeUnknown: Not supported URL scheme http+docker
docker.errors.DockerException: Error while fetching server API version
```

### Causa Raiz
- **docker-compose v1.29.2** (standalone) está **deprecado**
- Docker moderno usa **Docker Compose V2** como plugin (`docker compose`)
- Incompatibilidade de comunicação com o daemon Docker

---

## ✅ Solução Recomendada: Migrar para Docker Compose V2

### Passo 1: Remover Docker Compose V1 (Legado)

```bash
# Remover versão standalone
sudo apt-get remove docker-compose

# Ou, se instalado via pip/curl
sudo rm /usr/local/bin/docker-compose
```

### Passo 2: Instalar Docker Compose V2 (Plugin)

```bash
# Atualizar repositórios
sudo apt-get update

# Instalar o plugin
sudo apt-get install docker-compose-plugin

# Verificar instalação
docker compose version
# Esperado: Docker Compose version v2.x.x
```

### Passo 3: Verificar Docker Daemon

```bash
# Verificar se Docker está rodando
sudo systemctl status docker

# Se não estiver rodando, iniciar
sudo systemctl start docker

# Habilitar para iniciar automaticamente
sudo systemctl enable docker
```

### Passo 4: Configurar Permissões (Se necessário)

```bash
# Adicionar usuário ao grupo docker
sudo usermod -aG docker $USER

# IMPORTANTE: Fazer logout e login novamente para aplicar
# Ou use: newgrp docker
```

### Passo 5: Testar Inicialização

```bash
# Navegar até o diretório do projeto
cd /caminho/para/Support-Solo-Sondagens---El-tronico-Registro-de-Ponto

# Limpar volumes e containers antigos (CUIDADO: Remove dados!)
docker compose down -v

# Inicializar com novo comando V2
docker compose up -d --build

# Verificar status
docker compose ps
```

---

## 🔄 Diferenças: V1 vs V2

| Aspecto | Docker Compose V1 | Docker Compose V2 |
|---------|------------------|-------------------|
| **Comando** | `docker-compose` | `docker compose` (sem hífen) |
| **Instalação** | Binário standalone | Plugin integrado do Docker |
| **Status** | ⚠️ Deprecado | ✅ Mantido ativamente |
| **Performance** | Mais lento | Mais rápido (Go nativo) |
| **Compatibilidade** | docker-compose.yml v2.x | docker-compose.yml v3.x+ |

---

## 🛠️ Solução Alternativa (Se V2 falhar)

### Opção A: Usar Docker Compose V1 com sudo

```bash
# Garantir que Docker está rodando
sudo systemctl start docker

# Usar sudo para evitar problemas de permissão
sudo docker-compose up -d --build
```

### Opção B: Reinstalar Docker Compose V1

```bash
# Baixar versão mais recente do V1
sudo curl -L "https://github.com/docker/compose/releases/download/1.29.2/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose

# Dar permissão de execução
sudo chmod +x /usr/local/bin/docker-compose

# Verificar
docker-compose --version
```

---

## 📋 Checklist de Verificação

Após aplicar a solução, verificar:

- [ ] `docker compose version` retorna versão 2.x
- [ ] `docker --version` retorna versão 20.x ou superior
- [ ] `sudo systemctl status docker` mostra serviço ativo
- [ ] `docker compose ps` lista containers sem erros
- [ ] Aplicativo acessível via browser (porta configurada)

---

## 🐛 Troubleshooting Comum

### Erro: "permission denied while trying to connect to Docker daemon"

**Solução:**
```bash
sudo usermod -aG docker $USER
newgrp docker
# Ou fazer logout/login
```

### Erro: "Cannot connect to the Docker daemon"

**Solução:**
```bash
sudo systemctl start docker
sudo systemctl enable docker
```

### Erro: "dpkg was interrupted"

**Solução:**
```bash
sudo dpkg --configure -a
sudo apt-get update
sudo apt-get install -f
```

### Containers não iniciam (Exit 1)

**Solução:**
```bash
# Ver logs de um container específico
docker compose logs <service-name>

# Ver todos os logs
docker compose logs

# Comum: problemas de .env ou permissões de arquivo
```

---

## 🚀 Próximos Passos Após Docker Funcionar

1. **Verificar Containers:**
   ```bash
   docker compose ps
   # Todos devem estar "Up" e "healthy"
   ```

2. **Acessar Aplicação:**
   - Web: http://localhost:8080 (ou porta configurada)
   - API DeepFace: http://localhost:5000
   - MySQL: localhost:3306
   - Redis: localhost:6379

3. **Executar Migrations:**
   ```bash
   docker compose exec app php spark migrate
   ```

4. **Criar Usuário Admin:**
   ```bash
   docker compose exec app php spark db:seed AdminSeeder
   ```

5. **Verificar Logs:**
   ```bash
   docker compose logs -f app
   ```

---

## 📝 Notas Importantes

⚠️ **IMPORTANTE:** As correções de segurança já foram aplicadas ao código:
- ✅ Validação de senha fortalecida (12 caracteres + complexidade)
- ✅ Remoção de file_path do banco de dados biométrico
- ✅ Todas as queries SQL usando prepared statements
- ✅ Rate limiting já implementado na API DeepFace

Após resolver o problema do Docker, o sistema estará pronto para:
- Testar as novas regras de senha
- Verificar cadastro biométrico com hash-based storage
- Executar testes de integração completos

---

## 🔗 Referências

- [Docker Compose V2 Documentation](https://docs.docker.com/compose/cli-command/)
- [Migrate to Compose V2](https://docs.docker.com/compose/migrate/)
- [Docker Engine Installation](https://docs.docker.com/engine/install/)

---

**Última Atualização:** 16/Nov/2025
**Status:** ✅ Documento completo - Pronto para implementação
