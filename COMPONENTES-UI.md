# Guia de Componentes UI

## Visão Geral

O sistema inclui uma biblioteca completa de componentes reutilizáveis para construir interfaces consistentes e modernas.

### Bibliotecas Disponíveis

- **ComponentBuilder** - Constrói componentes HTML completos
- **UIHelper** - Funções auxiliares para formatação e UI

---

## ComponentBuilder

Localização: `App\Libraries\UI\ComponentBuilder`

### Uso Básico

```php
use App\Libraries\UI\ComponentBuilder;

// Em qualquer view ou controller
$html = ComponentBuilder::card([
    'title' => 'Meu Card',
    'content' => 'Conteúdo aqui'
]);
```

---

## Componentes Disponíveis

### 1. Card

Cria um card com header, body e footer opcional.

```php
<?= ComponentBuilder::card([
    'title' => 'Título do Card',
    'icon' => 'fa-chart-line',          // Opcional
    'content' => '<p>Conteúdo</p>',
    'footer' => '<p>Rodapé</p>',        // Opcional
    'actions' => '<button>Ação</button>', // Opcional
    'class' => 'mb-4',                  // Classes CSS adicionais
    'id' => 'meuCard',                  // ID opcional
]) ?>
```

**Exemplo Prático:**
```php
<?= ComponentBuilder::card([
    'title' => 'Estatísticas',
    'icon' => 'fa-chart-bar',
    'content' => '
        <p>Total de usuários: 150</p>
        <p>Ativos hoje: 98</p>
    ',
    'actions' => ComponentBuilder::button([
        'text' => 'Ver Mais',
        'style' => 'outline-primary',
        'size' => 'sm'
    ])
]) ?>
```

### 2. Stat Card

Card especializado para exibir estatísticas.

```php
<?= ComponentBuilder::statCard([
    'value' => '1,234',
    'label' => 'Total de Registros',
    'icon' => 'fa-users',
    'color' => 'primary',  // primary, success, warning, danger, info
    'trend' => [           // Opcional
        'direction' => 'up',
        'value' => '+12%'
    ],
    'url' => base_url('employees')  // Opcional, torna clicável
]) ?>
```

**Grid de Stat Cards:**
```php
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: var(--spacing-lg);">
    <?= ComponentBuilder::statCard([
        'value' => '248',
        'label' => 'Funcionários',
        'icon' => 'fa-users',
        'color' => 'primary'
    ]) ?>

    <?= ComponentBuilder::statCard([
        'value' => '1,542',
        'label' => 'Registros Hoje',
        'icon' => 'fa-clock',
        'color' => 'success',
        'trend' => ['direction' => 'up', 'value' => '+8%']
    ]) ?>
</div>
```

### 3. Button

Botões estilizados e consistentes.

```php
<?= ComponentBuilder::button([
    'text' => 'Salvar',
    'icon' => 'fa-save',              // Opcional
    'type' => 'submit',               // button, submit, reset
    'style' => 'primary',             // primary, secondary, success, danger, etc
    'size' => '',                     // sm, lg
    'url' => '',                      // Se fornecido, cria <a> ao invés de <button>
    'onclick' => 'alert("Clique")',   // JavaScript onclick
    'disabled' => false,
    'class' => '',
    'id' => ''
]) ?>
```

**Exemplos:**
```php
// Botão normal
<?= ComponentBuilder::button(['text' => 'Enviar', 'style' => 'primary']) ?>

// Botão link
<?= ComponentBuilder::button([
    'text' => 'Ir para Dashboard',
    'url' => base_url('dashboard'),
    'style' => 'outline-primary'
]) ?>

// Botão com confirmação
<?= ComponentBuilder::button([
    'text' => 'Excluir',
    'icon' => 'fa-trash',
    'style' => 'danger',
    'onclick' => "return confirm('Tem certeza?')"
]) ?>
```

### 4. Badge

Badges para status, tags, etc.

```php
<?= ComponentBuilder::badge([
    'text' => 'Novo',
    'style' => 'success',  // primary, success, warning, danger, info
    'icon' => 'fa-star',   // Opcional
    'class' => ''          // Classes adicionais
]) ?>
```

### 5. Alert

Mensagens de alerta/notificação.

```php
<?= ComponentBuilder::alert([
    'message' => 'Operação realizada com sucesso!',
    'type' => 'success',      // success, danger, warning, info
    'dismissible' => true,     // Mostra botão X
    'icon' => 'fa-check-circle' // Opcional, detectado automaticamente
]) ?>
```

### 6. Table

Tabela moderna e responsiva.

```php
<?= ComponentBuilder::table([
    'columns' => [
        ['label' => 'Nome', 'key' => 'name'],
        ['label' => 'Email', 'key' => 'email'],
        [
            'label' => 'Status',
            'key' => 'status',
            'formatter' => function($value) {
                return ComponentBuilder::badge([
                    'text' => $value,
                    'style' => $value === 'active' ? 'success' : 'danger'
                ]);
            }
        ],
        [
            'label' => 'Ações',
            'key' => 'id',
            'class' => 'text-end',
            'formatter' => function($value) {
                return '
                    <a href="' . base_url('edit/' . $value) . '" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-edit"></i>
                    </a>
                ';
            }
        ]
    ],
    'data' => $employees,  // Array de dados
    'responsive' => true,
    'class' => ''
]) ?>
```

### 7. Modal

Modal/dialog box.

```php
<?= ComponentBuilder::modal([
    'id' => 'confirmModal',
    'title' => 'Confirmar Ação',
    'content' => '<p>Tem certeza que deseja continuar?</p>',
    'footer' => '
        <button class="btn btn-outline-primary" onclick="closeModal(\'confirmModal\')">Cancelar</button>
        <button class="btn btn-primary">Confirmar</button>
    ',
    'size' => ''  // sm, lg
]) ?>

<!-- JavaScript para abrir -->
<script>
function openModal(modalId) {
    document.getElementById(modalId).classList.add('show');
    document.getElementById(modalId + 'Backdrop').classList.add('show');
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.remove('show');
    document.getElementById(modalId + 'Backdrop').classList.remove('show');
}
</script>
```

### 8. Form Group

Campo de formulário completo com label, input e validação.

```php
<?= ComponentBuilder::formGroup([
    'label' => 'Nome Completo',
    'name' => 'full_name',
    'type' => 'text',          // text, email, password, number, textarea
    'value' => old('full_name'),
    'placeholder' => 'Digite seu nome',
    'required' => true,
    'help' => 'Informe seu nome completo',
    'error' => session('errors.full_name'),  // Mensagem de erro de validação
    'class' => '',
    'attributes' => 'maxlength="100"'
]) ?>
```

### 9. Breadcrumb

Navegação breadcrumb.

```php
<?= ComponentBuilder::breadcrumb([
    ['label' => 'Configurações', 'url' => 'admin/settings'],
    ['label' => 'Aparência', 'url' => '']  // Último sem URL = ativo
]) ?>
```

### 10. Pagination

Paginação de resultados.

```php
<?= ComponentBuilder::pagination([
    'current' => 3,        // Página atual
    'total' => 10,         // Total de páginas
    'url' => base_url('employees'),
    'max_links' => 5       // Máximo de links visíveis
]) ?>
```

---

## UIHelper

Localização: `App\Libraries\UI\UIHelper`

### Funções de Formatação

```php
use App\Libraries\UI\UIHelper;

// Tamanho de arquivo
<?= UIHelper::formatFileSize(1024768) ?>  // 1 MB

// Número
<?= UIHelper::formatNumber(1234567) ?>     // 1.234.567

// Moeda
<?= UIHelper::formatCurrency(1234.56) ?>   // R$ 1.234,56

// Data
<?= UIHelper::formatDate('2025-12-05') ?>              // 05/12/2025
<?= UIHelper::formatDateTime('2025-12-05 14:30:00') ?> // 05/12/2025 14:30

// Tempo relativo
<?= UIHelper::timeAgo('2025-12-05 10:00:00') ?>  // 2 horas atrás

// Truncar texto
<?= UIHelper::truncate('Texto muito longo...', 50) ?>  // Texto muito lon...
```

### Funções de UI

```php
// Iniciais do nome
<?= UIHelper::getInitials('João Silva') ?>  // JS

// Avatar
<?= UIHelper::avatar('João Silva') ?>
<?= UIHelper::avatar('Maria', '/path/to/image.jpg', '50px') ?>

// Badge de status
<?= UIHelper::statusBadge('active') ?>    // Badge verde "Ativo"
<?= UIHelper::statusBadge('pending') ?>   // Badge amarelo "Pendente"

// Botão com confirmação
<?= UIHelper::confirmButton(
    'Excluir',
    base_url('delete/123'),
    'Tem certeza que deseja excluir?',
    'danger'
) ?>

// Ícone
<?= UIHelper::icon('user') ?>             // <i class="fas fa-user"></i>

// Spinner de loading
<?= UIHelper::spinner() ?>
<?= UIHelper::spinner('sm') ?>

// Estado vazio
<?= UIHelper::emptyState('Nenhum registro encontrado') ?>

// Grid layout
<?= UIHelper::grid([
    ComponentBuilder::statCard(...),
    ComponentBuilder::statCard(...),
    ComponentBuilder::statCard(...)
], 3, 'lg') ?>

// Flex layout
<?= UIHelper::flex([
    ComponentBuilder::button(...),
    ComponentBuilder::button(...),
], 'space-between', 'center', 'md') ?>
```

---

## Exemplos Completos

### Dashboard com Cards Estatísticos

```php
<?= $this->extend('layouts/modern') ?>
<?= $this->section('content') ?>

<?php
use App\Libraries\UI\ComponentBuilder;
use App\Libraries\UI\UIHelper;
?>

<!-- Stats Grid -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: var(--spacing-lg); margin-bottom: var(--spacing-xl);">

    <?= ComponentBuilder::statCard([
        'value' => UIHelper::formatNumber($totalUsers),
        'label' => 'Total de Usuários',
        'icon' => 'fa-users',
        'color' => 'primary',
        'url' => base_url('users')
    ]) ?>

    <?= ComponentBuilder::statCard([
        'value' => UIHelper::formatNumber($activeToday),
        'label' => 'Ativos Hoje',
        'icon' => 'fa-check-circle',
        'color' => 'success',
        'trend' => ['direction' => 'up', 'value' => '+12%']
    ]) ?>

</div>

<!-- Activity Card -->
<?= ComponentBuilder::card([
    'title' => 'Atividade Recente',
    'icon' => 'fa-list',
    'content' => ComponentBuilder::table([
        'columns' => [
            ['label' => 'Usuário', 'key' => 'user'],
            ['label' => 'Ação', 'key' => 'action'],
            ['label' => 'Data', 'key' => 'date', 'formatter' => fn($v) => UIHelper::timeAgo($v)]
        ],
        'data' => $activities
    ]),
    'actions' => ComponentBuilder::button([
        'text' => 'Ver Todas',
        'url' => base_url('activities'),
        'style' => 'outline-primary',
        'size' => 'sm'
    ])
]) ?>

<?= $this->endSection() ?>
```

### Formulário com Validação

```php
<form method="POST">
    <?= csrf_field() ?>

    <?= ComponentBuilder::formGroup([
        'label' => 'Nome',
        'name' => 'name',
        'value' => old('name'),
        'required' => true,
        'error' => session('errors.name')
    ]) ?>

    <?= ComponentBuilder::formGroup([
        'label' => 'Email',
        'name' => 'email',
        'type' => 'email',
        'value' => old('email'),
        'required' => true,
        'error' => session('errors.email')
    ]) ?>

    <?= ComponentBuilder::formGroup([
        'label' => 'Descrição',
        'name' => 'description',
        'type' => 'textarea',
        'value' => old('description'),
        'help' => 'Máximo 500 caracteres'
    ]) ?>

    <div style="display: flex; gap: var(--spacing-sm);">
        <?= ComponentBuilder::button(['text' => 'Cancelar', 'url' => base_url('back'), 'style' => 'outline-primary']) ?>
        <?= ComponentBuilder::button(['text' => 'Salvar', 'type' => 'submit', 'style' => 'primary', 'icon' => 'fa-save']) ?>
    </div>
</form>
```

---

## Dicas e Boas Práticas

1. **Consistência**: Use sempre os componentes da biblioteca para manter UI consistente
2. **Ícones**: Use Font Awesome 6.4.0 (já incluído no layout)
3. **Cores**: Prefira as cores do sistema (primary, success, warning, danger, info)
4. **Responsividade**: Os componentes já são responsivos por padrão
5. **Validação**: Use `session('errors.field')` para exibir erros de validação
6. **Loading**: Mostre spinners durante operações assíncronas
7. **Confirmações**: Use confirmações para ações destrutivas
8. **Empty States**: Sempre mostre mensagens quando não há dados

---

## CSS Custom Properties Disponíveis

Use essas variáveis CSS nos seus estilos:

```css
/* Cores */
--color-primary
--color-secondary
--color-success
--color-warning
--color-danger
--color-info

/* Background */
--bg-page
--bg-surface
--bg-hover

/* Texto */
--text-primary
--text-secondary
--text-muted

/* Espaçamento */
--spacing-xs   /* 4px */
--spacing-sm   /* 8px */
--spacing-md   /* 16px */
--spacing-lg   /* 24px */
--spacing-xl   /* 32px */

/* Bordas */
--border-color
--radius-sm
--radius-md
--radius-lg
--radius-full

/* Sombras */
--shadow-sm
--shadow-md
--shadow-lg
--shadow-xl
```

---

**Última atualização:** 2025-12-05
**Versão:** 1.0
