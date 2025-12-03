# PLANO COMPLETO DE REFORMULAÇÃO DO DASHBOARD

## ✅ STATUS: EM ANDAMENTO - FASE 2 COMPLETA

---

## VISÃO GERAL

Este documento descreve o plano completo para reformulação do dashboard administrativo, incluindo:
- Design moderno com sidebar
- Sistema completo de customização
- Correção de todos os erros
- Otimização e performance
- Documentação técnica

**Estimativa total:** 8-12 semanas de desenvolvimento
**Prioridade:** Alta
**Complexidade:** Alta

---

## FASE 1: FUNDAÇÃO E DESIGN SYSTEM ✅ COMPLETO

### Objetivos
- [x] Criar biblioteca de Design System
- [x] Sistema de temas (claro/escuro)
- [x] Configuração centralizada
- [x] CSS dinâmico baseado em configurações

### Arquivos Criados
- ✅ `app/Libraries/DesignSystem.php` - Sistema completo de design

### Próximos Passos
→ Seguir para FASE 2 ✅

---

## FASE 2: LAYOUT BASE MODERNO ✅ COMPLETO

### Objetivos
- [x] Criar template base com sidebar
- [x] Header responsivo
- [x] Menu lateral expansível/colapsável
- [x] Breadcrumbs e navegação
- [x] Alertas e notificações

### Arquivos Criados
- ✅ `app/Views/layouts/modern.php` - Layout base principal
- ✅ `app/Views/layouts/partials/sidebar.php` - Menu lateral com navegação hierárquica
- ✅ `app/Views/layouts/partials/header.php` - Barra superior com busca e notificações
- ✅ `app/Views/layouts/partials/footer.php` - Rodapé com links úteis
- ✅ `public/assets/modern/css/dashboard.css` - Estilos principais do dashboard
- ✅ `public/assets/modern/css/sidebar.css` - Estilos do menu lateral
- ✅ `public/assets/modern/css/components.css` - Componentes reutilizáveis
- ✅ `public/assets/modern/js/dashboard.js` - Funcionalidades de dropdowns e alerts
- ✅ `public/assets/modern/js/sidebar.js` - Toggle, submenus e busca do sidebar
- ✅ `public/assets/modern/js/theme-switcher.js` - Sistema de troca de tema
- ✅ `public/assets/modern/images/logo.svg` - Logo placeholder
- ✅ `public/assets/modern/images/logo-icon.svg` - Ícone do logo
- ✅ `app/Views/dashboard/admin.php` - Exemplo de dashboard admin

### Funcionalidades do Sidebar
- Menu hierárquico com ícones
- Submenus expansíveis
- Indicador de página ativa
- Modo colapsado (ícones apenas)
- Responsivo (drawer em mobile)
- Busca de menu items

### Funcionalidades do Header
- Logo da empresa (customizável)
- Busca global
- Notificações
- Perfil do usuário
- Toggle de tema claro/escuro
- Breadcrumbs

### Próximos Passos
→ Seguir para FASE 3

---

## FASE 3: SISTEMA DE CONFIGURAÇÕES COMPLETO (PRÓXIMA)

### Objetivos
- [ ] Criar módulo de configurações
- [ ] Interface de customização visual
- [ ] Upload de logos e imagens
- [ ] Gerenciamento de certificado digital
- [ ] Configurações de segurança

### Estrutura de Configurações

#### 3.1 Aparência
```
- Paleta de cores (color picker)
- Logo principal
- Logo alternativa (tema escuro)
- Favicon
- Tema padrão (claro/escuro/auto)
- Fontes (Google Fonts integration)
```

#### 3.2 Login/Autenticação
```
- Imagem de fundo do login
- Logo no login
- Texto de boas-vindas
- Habilitar/desabilitar registro
- Autenticação de 2 fatores
- Tempo de sessão
```

#### 3.3 Certificado Digital
```
- Upload de certificado A1 (.pfx)
- Senha do certificado
- Configuração de certificado A3
- Validade e informações
- Teste de assinatura
```

#### 3.4 Sistema
```
- Nome da empresa
- CNPJ
- Endereço
- Contatos
- Fuso horário
- Idioma padrão
```

#### 3.5 Segurança
```
- Política de senhas
- Rate limiting
- IP whitelist/blacklist
- Logs de auditoria
- Backup automático
```

### Arquivos a Criar
```
app/Controllers/Admin/
├── SettingsController.php      # Controller principal
├── AppearanceController.php    # Customização visual
├── CertificateController.php   # Certificado digital
└── SecurityController.php      # Configurações de segurança

app/Views/admin/settings/
├── index.php                    # Dashboard de configurações
├── appearance.php               # Aparência
├── authentication.php           # Login/Auth
├── certificate.php              # Certificado digital
├── system.php                   # Sistema geral
└── security.php                 # Segurança

app/Models/
└── SystemSettingModel.php       # Model para configurações
```

---

## FASE 4: COMPONENTES REUTILIZÁVEIS

### Objetivos
- [ ] Criar biblioteca de componentes UI
- [ ] Cards modernos
- [ ] Tabelas responsivas
- [ ] Formulários estilizados
- [ ] Botões e badges
- [ ] Modais e tooltips

### Componentes a Desenvolver

#### Cards
```php
<!-- Card básico -->
<div class="card">
  <div class="card-header">
    <h3 class="card-title">Título</h3>
    <div class="card-actions">...</div>
  </div>
  <div class="card-body">...</div>
  <div class="card-footer">...</div>
</div>

<!-- Card com estatística -->
<div class="stat-card">
  <div class="stat-icon">
    <i class="icon-users"></i>
  </div>
  <div class="stat-content">
    <div class="stat-value">1,234</div>
    <div class="stat-label">Funcionários</div>
  </div>
  <div class="stat-trend">
    <span class="trend-up">+12%</span>
  </div>
</div>
```

#### Tabelas
```php
<!-- Tabela responsiva com ações -->
<div class="table-responsive">
  <table class="table table-modern">
    <thead>
      <tr>
        <th>Nome</th>
        <th>Email</th>
        <th>Status</th>
        <th class="text-end">Ações</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td>...</td>
        <td>...</td>
        <td><span class="badge badge-success">Ativo</span></td>
        <td class="text-end">
          <div class="btn-group">...</div>
        </td>
      </tr>
    </tbody>
  </table>
</div>
```

#### Formulários
```php
<!-- Form group moderno -->
<div class="form-group">
  <label for="input" class="form-label">
    Label
    <span class="label-required">*</span>
  </label>
  <input type="text" id="input" class="form-control">
  <div class="form-help">Texto de ajuda</div>
  <div class="form-error">Mensagem de erro</div>
</div>
```

---

## FASE 5: CORREÇÃO DE ERROS

### Objetivos
- [ ] Auditar todas as rotas
- [ ] Corrigir erros 404
- [ ] Corrigir erros 500
- [ ] Verificar todos os links
- [ ] Testar todas as funcionalidades

### Checklist de Erros

#### Rotas
- [ ] Verificar todas as rotas em `Routes.php`
- [ ] Testar cada rota individualmente
- [ ] Verificar filtros de autenticação
- [ ] Validar permissões por role

#### Controllers
- [ ] Verificar todos os métodos
- [ ] Validar retornos
- [ ] Tratar exceções
- [ ] Logs adequados

#### Views
- [ ] Verificar todas as views existem
- [ ] Validar includes/extends
- [ ] Testar com dados reais
- [ ] Responsividade

#### AJAX/API
- [ ] Testar todas as chamadas AJAX
- [ ] Validar respostas JSON
- [ ] Tratamento de erros
- [ ] Loading states

---

## FASE 6: DASHBOARDS POR ROLE

### 6.1 Dashboard Admin
```
- Visão geral completa
- Estatísticas gerais
- Gráficos de uso
- Atividades recentes
- Alertas do sistema
- Atalhos administrativos
```

### 6.2 Dashboard Gestor
```
- Equipe sob gestão
- Aprovações pendentes
- Relatórios da equipe
- Estatísticas de presença
- Horas extras da equipe
```

### 6.3 Dashboard Funcionário
```
- Registro de ponto rápido
- Pontos do dia/semana
- Banco de horas
- Justificativas pendentes
- Próximos eventos
```

---

## FASE 7: MÓDULOS ESPECÍFICOS

### 7.1 Registro de Ponto
- [ ] Interface moderna de punch
- [ ] Visualização em tempo real
- [ ] Histórico com filtros
- [ ] Edição (para gestores)

### 7.2 Gestão de Funcionários
- [ ] Listagem com busca avançada
- [ ] Perfil completo
- [ ] Documentos
- [ ] Histórico

### 7.3 Relatórios
- [ ] Gerador de relatórios
- [ ] Exportação (PDF, Excel, CSV)
- [ ] Relatórios customizados
- [ ] Agendamento de relatórios

### 7.4 LGPD
- [ ] Portal de consentimentos
- [ ] Gerenciamento de dados
- [ ] Logs de acesso
- [ ] Solicitações LGPD

---

## FASE 8: OTIMIZAÇÃO E PERFORMANCE

### Objetivos
- [ ] Otimizar queries do banco
- [ ] Implementar cache
- [ ] Lazy loading de imagens
- [ ] Minificar CSS/JS
- [ ] Compression
- [ ] CDN para assets estáticos

### Métricas a Melhorar
```
- Tempo de carregamento inicial < 2s
- First Contentful Paint < 1.5s
- Time to Interactive < 3s
- Lighthouse Score > 90
```

---

## FASE 9: ACESSIBILIDADE

### Objetivos
- [ ] Navegação por teclado
- [ ] ARIA labels
- [ ] Contraste adequado (WCAG AA)
- [ ] Screen reader support
- [ ] Skip links
- [ ] Focus indicators

---

## FASE 10: TESTES E QA

### Tipos de Teste
- [ ] Testes unitários (PHPUnit)
- [ ] Testes de integração
- [ ] Testes E2E (Cypress/Playwright)
- [ ] Testes de acessibilidade
- [ ] Testes de performance
- [ ] Testes de segurança

---

## FASE 11: DOCUMENTAÇÃO

### Documentação a Criar
- [ ] Manual do usuário (admin)
- [ ] Manual do usuário (funcionário)
- [ ] Documentação técnica
- [ ] Guia de customização
- [ ] API documentation
- [ ] Troubleshooting guide

---

## TECNOLOGIAS E BIBLIOTECAS

### Front-end
```
- TailwindCSS ou Bootstrap 5 (decidir)
- Alpine.js para interatividade leve
- Chart.js para gráficos
- Select2 para selects avançados
- Flatpickr para date pickers
- Font Awesome ou Heroicons para ícones
```

### Back-end
```
- CodeIgniter 4
- PHP 8.4
- MySQL/MariaDB
- Redis para cache (opcional)
```

### Build Tools
```
- Vite para bundling
- PostCSS
- Autoprefixer
- PurgeCSS
```

---

## ESTRUTURA DE DIRETÓRIOS PROPOSTA

```
public/assets/
├── modern/                 # Assets do novo dashboard
│   ├── css/
│   │   ├── dashboard.css
│   │   ├── components.css
│   │   ├── sidebar.css
│   │   └── themes.css
│   ├── js/
│   │   ├── dashboard.js
│   │   ├── components.js
│   │   └── theme-switcher.js
│   ├── images/
│   │   ├── logo-light.svg
│   │   ├── logo-dark.svg
│   │   └── placeholders/
│   └── fonts/
│       └── Inter/
│
app/Views/
├── layouts/
│   ├── modern.php         # Layout principal
│   └── partials/
│       ├── sidebar.php
│       ├── header.php
│       └── footer.php
├── dashboard/
│   ├── admin.php
│   ├── manager.php
│   └── employee.php
└── admin/
    └── settings/
        ├── index.php
        ├── appearance.php
        ├── system.php
        └── security.php
```

---

## PALETA DE CORES

### Cores Principais (Baseadas na Logo - A DEFINIR)
```css
--primary: #3B82F6;      /* Azul principal */
--secondary: #8B5CF6;    /* Roxo/Secundário */
--accent: #06B6D4;       /* Destaque */
```

### Cores Funcionais
```css
--success: #10B981;      /* Verde - sucesso */
--warning: #F59E0B;      /* Amarelo - aviso */
--danger: #EF4444;       /* Vermelho - erro */
--info: #06B6D4;         /* Ciano - informação */
```

### Cores Neutras
```css
--gray-50: #F9FAFB;
--gray-100: #F3F4F6;
--gray-200: #E5E7EB;
--gray-300: #D1D5DB;
--gray-400: #9CA3AF;
--gray-500: #6B7280;
--gray-600: #4B5563;
--gray-700: #374151;
--gray-800: #1F2937;
--gray-900: #111827;
```

**NOTA:** As cores serão ajustadas assim que a logo for fornecida.

---

## CRONOGRAMA ESTIMADO

| Fase | Descrição | Tempo Estimado | Status |
|------|-----------|----------------|--------|
| 1 | Fundação e Design System | 3-5 dias | ✅ COMPLETO |
| 2 | Layout Base | 5-7 dias | ✅ COMPLETO |
| 3 | Sistema de Configurações | 7-10 dias | 🔄 PRÓXIMO |
| 4 | Componentes Reutilizáveis | 5-7 dias | ⏳ PENDENTE |
| 5 | Correção de Erros | 3-5 dias | ⏳ PENDENTE |
| 6 | Dashboards por Role | 7-10 dias | ⏳ PENDENTE |
| 7 | Módulos Específicos | 10-14 dias | ⏳ PENDENTE |
| 8 | Otimização | 3-5 dias | ⏳ PENDENTE |
| 9 | Acessibilidade | 3-5 dias | ⏳ PENDENTE |
| 10 | Testes e QA | 5-7 dias | ⏳ PENDENTE |
| 11 | Documentação | 5-7 dias | ⏳ PENDENTE |

**Total:** 8-12 semanas

---

## PRÓXIMOS PASSOS IMEDIATOS

1. ✅ **Design System criado** - Base para todo o sistema
2. ✅ **Layout base criado** - Sidebar + Header + Footer + Exemplo
3. 🔄 **Implementar página de configurações básica** - Sistema completo de customização
4. ⏳ **Corrigir erros críticos (404, 500)**
5. ⏳ **Aplicar paleta de cores da logo**

---

## NOTAS IMPORTANTES

- Este é um projeto de grande escala que requer dedicação contínua
- Cada fase deve ser testada antes de avançar para a próxima
- Commits frequentes e incrementais são essenciais
- Feedback contínuo do cliente é crucial
- Backup do sistema atual antes de grandes mudanças
- Manter documentação atualizada durante todo o processo

---

## PERGUNTAS PENDENTES

1. ❓ **Logo da empresa** - Aguardando arquivo para extrair cores
2. ❓ **Certificado Digital** - Tipo (A1/A3)? Provider específico?
3. ❓ **Idiomas** - Implementar multilíngue? Quais idiomas?
4. ❓ **Integrações** - API externa? ERPs? Folha de pagamento?
5. ❓ **Hospedagem** - VPS próprio? Shared hosting? Cloud?

---

**Documento criado em:** 2025-12-03
**Última atualização:** 2025-12-03
**Responsável:** Claude AI
**Status:** EM ANDAMENTO - FASE 2 COMPLETA
