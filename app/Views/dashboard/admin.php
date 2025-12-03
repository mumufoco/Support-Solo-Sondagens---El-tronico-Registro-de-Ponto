<?php
// Set page title and breadcrumbs
$title = 'Dashboard Administrativo';
$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => '']
];
?>

<?= $this->extend('layouts/modern') ?>

<?= $this->section('content') ?>

<!-- Welcome Section -->
<div class="card mb-4">
    <div class="card-body">
        <h2 style="margin: 0 0 8px 0; font-size: var(--font-size-2xl); color: var(--text-primary);">
            Bem-vindo, <?= esc(session()->get('user_name') ?? 'Administrador') ?>! 👋
        </h2>
        <p style="margin: 0; color: var(--text-muted);">
            Aqui está um resumo das atividades do sistema.
        </p>
    </div>
</div>

<!-- Statistics Cards -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: var(--spacing-lg); margin-bottom: var(--spacing-xl);">
    <div class="stat-card">
        <div class="stat-icon bg-primary">
            <i class="fas fa-users"></i>
        </div>
        <div class="stat-content">
            <div class="stat-value">248</div>
            <div class="stat-label">Funcionários Ativos</div>
        </div>
        <div class="stat-trend">
            <span class="trend-up">
                <i class="fas fa-arrow-up"></i> 12%
            </span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon bg-success">
            <i class="fas fa-clock"></i>
        </div>
        <div class="stat-content">
            <div class="stat-value">1,542</div>
            <div class="stat-label">Registros Hoje</div>
        </div>
        <div class="stat-trend">
            <span class="trend-up">
                <i class="fas fa-arrow-up"></i> 8%
            </span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon bg-warning">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        <div class="stat-content">
            <div class="stat-value">23</div>
            <div class="stat-label">Aprovações Pendentes</div>
        </div>
        <div class="stat-trend">
            <span class="trend-down">
                <i class="fas fa-arrow-down"></i> 5%
            </span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon bg-success">
            <i class="fas fa-check-circle"></i>
        </div>
        <div class="stat-content">
            <div class="stat-value">99.8%</div>
            <div class="stat-label">Uptime do Sistema</div>
        </div>
        <div class="stat-trend">
            <span class="trend-up">
                <i class="fas fa-arrow-up"></i> 0.2%
            </span>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
