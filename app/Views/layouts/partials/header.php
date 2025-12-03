<header class="app-header">
    <div class="header-left">
        <!-- Mobile Menu Toggle -->
        <button type="button" class="mobile-menu-toggle" id="mobileMenuToggle">
            <i class="fas fa-bars"></i>
        </button>

        <!-- Page Title -->
        <h1 class="page-title"><?= esc($title ?? 'Dashboard') ?></h1>
    </div>

    <div class="header-right">
        <!-- Global Search -->
        <div class="header-search">
            <div class="search-wrapper">
                <i class="fas fa-search"></i>
                <input type="text" class="search-input" placeholder="Buscar..." id="globalSearch">
            </div>
        </div>

        <!-- Theme Switcher -->
        <div class="header-action">
            <button type="button" class="theme-toggle" id="themeToggle" title="Alternar tema">
                <i class="fas fa-moon theme-icon-dark"></i>
                <i class="fas fa-sun theme-icon-light"></i>
            </button>
        </div>

        <!-- Notifications -->
        <div class="header-action dropdown">
            <button type="button" class="notification-toggle" id="notificationToggle" data-toggle="dropdown">
                <i class="fas fa-bell"></i>
                <span class="notification-badge">3</span>
            </button>
            <div class="dropdown-menu dropdown-menu-right notification-dropdown" id="notificationDropdown">
                <div class="dropdown-header">
                    <h4>Notificações</h4>
                    <a href="<?= base_url('notifications/all') ?>" class="view-all">Ver todas</a>
                </div>
                <div class="notification-list">
                    <a href="#" class="notification-item unread">
                        <div class="notification-icon bg-primary">
                            <i class="fas fa-info-circle"></i>
                        </div>
                        <div class="notification-content">
                            <div class="notification-title">Nova atualização disponível</div>
                            <div class="notification-time">Há 5 minutos</div>
                        </div>
                    </a>
                    <a href="#" class="notification-item unread">
                        <div class="notification-icon bg-success">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="notification-content">
                            <div class="notification-title">Ponto aprovado</div>
                            <div class="notification-time">Há 1 hora</div>
                        </div>
                    </a>
                    <a href="#" class="notification-item">
                        <div class="notification-icon bg-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <div class="notification-content">
                            <div class="notification-title">Ajuste de ponto pendente</div>
                            <div class="notification-time">Há 2 horas</div>
                        </div>
                    </a>
                </div>
                <div class="dropdown-footer">
                    <a href="<?= base_url('notifications/all') ?>">Ver todas as notificações</a>
                </div>
            </div>
        </div>

        <!-- User Menu -->
        <div class="header-action dropdown">
            <button type="button" class="user-toggle" id="userToggle" data-toggle="dropdown">
                <div class="user-avatar">
                    <i class="fas fa-user"></i>
                </div>
                <span class="user-name"><?= esc(session()->get('user_name') ?? 'Usuário') ?></span>
                <i class="fas fa-chevron-down"></i>
            </button>
            <div class="dropdown-menu dropdown-menu-right user-dropdown" id="userDropdown">
                <div class="dropdown-header">
                    <div class="user-info">
                        <div class="user-name"><?= esc(session()->get('user_name') ?? 'Usuário') ?></div>
                        <div class="user-email"><?= esc(session()->get('user_email') ?? '') ?></div>
                    </div>
                </div>
                <div class="dropdown-divider"></div>
                <a href="<?= base_url('profile') ?>" class="dropdown-item">
                    <i class="fas fa-user"></i>
                    <span>Meu Perfil</span>
                </a>
                <a href="<?= base_url('profile/settings') ?>" class="dropdown-item">
                    <i class="fas fa-cog"></i>
                    <span>Configurações</span>
                </a>
                <?php if (session()->get('user_role') === 'admin'): ?>
                    <a href="<?= base_url('admin/settings') ?>" class="dropdown-item">
                        <i class="fas fa-tools"></i>
                        <span>Configurações do Sistema</span>
                    </a>
                <?php endif; ?>
                <div class="dropdown-divider"></div>
                <a href="<?= base_url('help') ?>" class="dropdown-item">
                    <i class="fas fa-question-circle"></i>
                    <span>Ajuda</span>
                </a>
                <div class="dropdown-divider"></div>
                <a href="<?= base_url('logout') ?>" class="dropdown-item text-danger">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Sair</span>
                </a>
            </div>
        </div>
    </div>
</header>
