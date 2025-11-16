# Estratégia de Versionamento - .gitignore

## Resumo

Este documento explica a estratégia de versionamento de dependências adotada neste projeto, garantindo que todos os arquivos necessários para execução estejam incluídos no repositório.

## Objetivo

Permitir que o projeto seja clonado e executado imediatamente, sem necessidade de instalação de dependências, garantindo:
- ✅ Consistência de versões em todos os ambientes
- ✅ Deploy simplificado sem instalação de pacotes
- ✅ Portabilidade total do projeto
- ✅ Ambiente idêntico entre desenvolvimento e produção

## Dependências Versionadas

### PHP/Composer ✅
**Status: Completamente Versionado**

```
✓ vendor/ (70MB)
  - 76 pacotes instalados
  - CodeIgniter4, PHPOffice, TCPDF, Guzzle, QR Code, Workerman, etc

✓ composer.lock (191KB)
  - Versões exatas de todos os pacotes
  - Garante reprodutibilidade
```

**Vantagem:** Não é necessário executar `composer install` em produção.

### JavaScript/Node.js ✅
**Status: Pronto para Versionamento**

```
✓ node_modules/ - SERÁ versionado quando instalado
✓ package-lock.json - SERÁ versionado quando criado
✓ yarn.lock - SERÁ versionado se usado
```

**Nota:** Atualmente o projeto não possui dependências Node.js. Quando forem adicionadas, serão automaticamente versionadas.

### Python/DeepFace ✅
**Status: Parcialmente Versionado**

```
✓ deepface-api/venv/ - SERÁ versionado se criado
✓ requirements.txt - JÁ versionado
✓ requirements_deepface.txt - JÁ versionado
```

**Opções:**
1. **Versionar venv:** Incluir ambiente virtual completo (pode ser grande)
2. **Não versionar venv:** Recriar com `python -m venv venv && pip install -r requirements.txt`

Atualmente configurado para a **Opção 2** (venv não criado ainda).

## Arquivos Mantidos Ignorados (Segurança)

### 🔒 Credenciais e Configurações Sensíveis
```
.env                    - NUNCA versionar (credenciais de banco, chaves API)
storage/keys/*          - Chaves de criptografia
*.sql, *.backup        - Backups de banco de dados
```

**⚠️ IMPORTANTE:** O arquivo `.env` NUNCA deve ser versionado. Use `.env.example` como template.

### 📁 Dados de Usuários e Uploads
```
storage/faces/*                    - Dados biométricos sensíveis
storage/uploads/justifications/*   - Documentos enviados
storage/uploads/warnings/*         - Advertências
storage/uploads/temp/*             - Arquivos temporários
```

### 🗂️ Arquivos Temporários e Cache
```
writable/cache/*       - Cache da aplicação
writable/logs/*        - Logs de execução
writable/session/*     - Sessões de usuários
writable/uploads/*     - Uploads temporários
.deepface/            - Cache de modelos ML (grandes)
```

### 💻 Arquivos de IDE e OS
```
.idea/                 - PhpStorm
.vscode/               - Visual Studio Code
.DS_Store              - macOS
Thumbs.db              - Windows
```

### 🧪 Arquivos de Teste e Build
```
tests/coverage*        - Relatórios de cobertura
.phpunit.result.cache  - Cache do PHPUnit
deepface-api/__pycache__/ - Python compiled
```

## Estrutura de Diretórios Versionados

```
projeto/
├── vendor/                    ✅ VERSIONADO (70MB)
│   ├── codeigniter4/
│   ├── phpoffice/
│   ├── tecnickcom/
│   └── ... (76 pacotes)
│
├── composer.lock              ✅ VERSIONADO (191KB)
├── composer.json              ✅ VERSIONADO
│
├── requirements.txt           ✅ VERSIONADO
├── requirements_deepface.txt  ✅ VERSIONADO
│
├── node_modules/              ⏳ Será versionado quando criado
├── package-lock.json          ⏳ Será versionado quando criado
│
├── .env                       ❌ NUNCA versionar
├── .env.example               ✅ VERSIONADO (template)
│
└── storage/                   ⚠️ Estrutura versionada, conteúdo ignorado
    ├── .gitkeep              ✅ Mantém estrutura
    └── faces/                ❌ Conteúdo ignorado
```

## Vantagens desta Abordagem

### 1. Deploy Simplificado
```bash
# Clone e execute - pronto!
git clone [repo]
cd projeto
cp .env.example .env
# Edite .env com suas credenciais
php spark serve
```

**Sem necessidade de:**
- ✗ Instalar Composer
- ✗ Executar composer install
- ✗ Configurar versões de pacotes
- ✗ Resolver conflitos de dependências

### 2. Garantia de Consistência
- Todos os desenvolvedores usam exatamente as mesmas versões
- Ambiente de produção idêntico ao desenvolvimento
- Elimina problemas "funciona na minha máquina"

### 3. Controle de Versão Total
- Histórico completo de mudanças em dependências
- Possibilidade de reverter para versões anteriores
- Auditoria completa do código em produção

### 4. Deploy Atômico
- Um único commit contém código + dependências
- Rollback instantâneo se necessário
- Sem período de instalação de pacotes

## Desvantagens e Mitigações

### 1. Repositório Maior
**Problema:** Repositório aumenta de tamanho (vendor/ = 70MB)
**Mitigação:**
- Git usa compressão eficiente
- Clones shallow: `git clone --depth 1`
- Benefício vale o custo em projetos de produção

### 2. Conflitos em Merges
**Problema:** Merge de branches pode gerar conflitos em vendor/
**Mitigação:**
- Usar merge strategy: `git merge -X ours` ou `-X theirs`
- Resolver no composer.json e executar install localmente

### 3. Atualizações de Segurança
**Problema:** Pacotes com vulnerabilidades não são atualizados automaticamente
**Mitigação:**
- Monitorar com `composer audit`
- Revisar dependências periodicamente
- GitHub Dependabot (se disponível)

## Quando Adicionar Dependências

### Node.js
```bash
# Se instalar dependências Node.js
npm install
# ou
yarn install

# Automaticamente serão versionadas
git add node_modules/ package-lock.json
git commit -m "Add Node.js dependencies"
```

### Python venv
```bash
# Se quiser versionar ambiente virtual Python
cd deepface-api
python -m venv venv
source venv/bin/activate  # Linux/Mac
# ou
venv\Scripts\activate  # Windows

pip install -r requirements.txt

# Versionar venv (OPCIONAL - pode ser grande)
git add venv/
git commit -m "Add Python virtual environment"
```

**Recomendação:** Não versionar venv, apenas requirements.txt

## Comandos Úteis

### Verificar o que está ignorado
```bash
git status --ignored
```

### Ver tamanho do repositório
```bash
du -sh .git/
```

### Limpar cache do Git (se muito grande)
```bash
git gc --aggressive --prune=now
```

### Verificar dependências desatualizadas
```bash
composer outdated
npm outdated  # Se houver package.json
```

### Auditoria de segurança
```bash
composer audit
npm audit  # Se houver package.json
```

## Segurança - Checklist

Antes de fazer commit, SEMPRE verifique:

- [ ] `.env` NÃO está sendo versionado
- [ ] Arquivos `*.sql` NÃO estão sendo versionados
- [ ] `storage/keys/*` NÃO está sendo versionado
- [ ] Dados biométricos em `storage/faces/*` NÃO estão sendo versionados
- [ ] Credenciais hardcoded foram removidas do código
- [ ] `.env.example` não contém valores reais, apenas placeholders

## Conclusão

Esta estratégia garante que:
1. ✅ Todas as dependências necessárias estão versionadas
2. ✅ Arquivos sensíveis permanecem protegidos
3. ✅ Deploy é simples e confiável
4. ✅ Ambiente é consistente em todos os lugares

Para dúvidas ou sugestões de melhoria, consulte a equipe de desenvolvimento.

---

**Última atualização:** 2025-11-16
**Branch:** claude/fix-composer-setup-warning-01NedH4Ms8iQLTecqkxf9rPx
**Commits relacionados:**
- `5865b48` - Add vendor directory and composer.lock to repository
- `d50e6ae` - Update .gitignore to version control all necessary dependencies
