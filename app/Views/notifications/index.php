<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>Notificações<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="mb-1">
                <i class="fas fa-bell me-2"></i>Notificações
            </h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?= base_url('dashboard') ?>">Dashboard</a></li>
                    <li class="breadcrumb-item active">Notificações</li>
                </ol>
            </nav>
        </div>
    </div>

    <!-- Actions Bar -->
    <div class="row mb-4">
        <div class="col-md-8">
            <div class="btn-group" role="group">
                <a href="<?= base_url('notifications?filter=all') ?>"
                   class="btn btn-outline-primary <?= ($filter ?? 'all') === 'all' ? 'active' : '' ?>">
                    Todas (<?= $counts['all'] ?? 0 ?>)
                </a>
                <a href="<?= base_url('notifications?filter=unread') ?>"
                   class="btn btn-outline-warning <?= ($filter ?? 'all') === 'unread' ? 'active' : '' ?>">
                    Não Lidas (<?= $counts['unread'] ?? 0 ?>)
                </a>
                <a href="<?= base_url('notifications?filter=read') ?>"
                   class="btn btn-outline-success <?= ($filter ?? 'all') === 'read' ? 'active' : '' ?>">
                    Lidas (<?= $counts['read'] ?? 0 ?>)
                </a>
            </div>
        </div>
        <div class="col-md-4 text-md-end mt-3 mt-md-0">
            <?php if (!empty($notifications)): ?>
                <form action="<?= base_url('notifications/mark-all-read') ?>" method="POST" class="d-inline">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-outline-success">
                        <i class="fas fa-check-double me-2"></i>Marcar Todas como Lidas
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- Notifications List -->
    <div class="row">
        <div class="col-12">
            <?php if (empty($notifications)): ?>
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-bell-slash fa-4x text-muted mb-3 opacity-50"></i>
                        <h5 class="text-muted">Nenhuma notificação encontrada</h5>
                        <p class="text-muted">Você está em dia com suas notificações!</p>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($notifications as $notification): ?>
                    <div class="card mb-3 <?= !$notification['read'] ? 'border-primary' : '' ?>">
                        <div class="card-body">
                            <div class="row align-items-start">
                                <div class="col-auto">
                                    <div class="notification-icon">
                                        <i class="fas fa-<?=
                                            $notification['type'] === 'success' ? 'check-circle text-success' :
                                            ($notification['type'] === 'warning' ? 'exclamation-triangle text-warning' :
                                            ($notification['type'] === 'danger' ? 'exclamation-circle text-danger' :
                                            'info-circle text-info'))
                                        ?> fa-2x"></i>
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h5 class="mb-0 <?= !$notification['read'] ? 'fw-bold' : '' ?>">
                                            <?= esc($notification['title']) ?>
                                            <?php if (!$notification['read']): ?>
                                                <span class="badge bg-primary ms-2">Nova</span>
                                            <?php endif; ?>
                                        </h5>
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-link text-muted" type="button"
                                                    data-bs-toggle="dropdown">
                                                <i class="fas fa-ellipsis-v"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <?php if (!$notification['read']): ?>
                                                    <li>
                                                        <a class="dropdown-item" href="<?= base_url("notifications/{$notification['id']}/mark-read") ?>">
                                                            <i class="fas fa-check me-2"></i>Marcar como Lida
                                                        </a>
                                                    </li>
                                                <?php endif; ?>
                                                <li>
                                                    <a class="dropdown-item text-danger"
                                                       href="<?= base_url("notifications/{$notification['id']}/delete") ?>"
                                                       data-confirm="Tem certeza que deseja excluir esta notificação?">
                                                        <i class="fas fa-trash me-2"></i>Excluir
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>

                                    <p class="mb-2"><?= esc($notification['message']) ?></p>

                                    <?php if ($notification['link']): ?>
                                        <a href="<?= esc($notification['link']) ?>" class="btn btn-sm btn-outline-primary">
                                            Ver Detalhes <i class="fas fa-arrow-right ms-1"></i>
                                        </a>
                                    <?php endif; ?>

                                    <div class="mt-2">
                                        <small class="text-muted">
                                            <i class="fas fa-clock me-1"></i>
                                            <?= esc($notification['time_ago']) ?>
                                            <?php if ($notification['read'] && $notification['read_at']): ?>
                                                • Lida em <?= format_datetime_br($notification['read_at']) ?>
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>

                <!-- Pagination -->
                <?php if ($pager): ?>
                    <div class="mt-4">
                        <?= $pager->links() ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
