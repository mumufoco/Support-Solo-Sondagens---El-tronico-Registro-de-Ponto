<!DOCTYPE html>
<html lang="pt-BR" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">

    <title><?= esc($title ?? 'Dashboard') ?> - Sistema de Ponto Eletrônico</title>

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?= base_url('assets/modern/images/favicon.ico') ?>">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Design System CSS (Dynamic) -->
    <?php
    $designSystem = new \App\Libraries\DesignSystem();
    ?>
    <style>
        <?= $designSystem->generateCSS() ?>
    </style>

    <!-- Modern Dashboard CSS -->
    <link rel="stylesheet" href="<?= base_url('assets/modern/css/dashboard.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/modern/css/sidebar.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/modern/css/components.css') ?>">

    <!-- Additional Page CSS -->
    <?= $this->renderSection('css') ?>
</head>
<body>
    <div class="app-wrapper">
        <!-- Sidebar -->
        <?= view('layouts/partials/sidebar') ?>

        <!-- Main Content Area -->
        <div class="main-content">
            <!-- Header -->
            <?= view('layouts/partials/header') ?>

            <!-- Page Content -->
            <main class="page-content">
                <!-- Breadcrumbs -->
                <?php if (isset($breadcrumbs) && !empty($breadcrumbs)): ?>
                    <nav class="breadcrumb-nav" aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item">
                                <a href="<?= base_url('/') ?>">
                                    <i class="fas fa-home"></i>
                                </a>
                            </li>
                            <?php foreach ($breadcrumbs as $index => $crumb): ?>
                                <?php if ($index === array_key_last($breadcrumbs)): ?>
                                    <li class="breadcrumb-item active" aria-current="page">
                                        <?= esc($crumb['label']) ?>
                                    </li>
                                <?php else: ?>
                                    <li class="breadcrumb-item">
                                        <a href="<?= esc($crumb['url']) ?>">
                                            <?= esc($crumb['label']) ?>
                                        </a>
                                    </li>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </ol>
                    </nav>
                <?php endif; ?>

                <!-- Flash Messages -->
                <?php if (session()->has('success')): ?>
                    <div class="alert alert-success alert-dismissible">
                        <i class="fas fa-check-circle"></i>
                        <span><?= session('success') ?></span>
                        <button type="button" class="alert-close" data-dismiss="alert">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                <?php endif; ?>

                <?php if (session()->has('error')): ?>
                    <div class="alert alert-danger alert-dismissible">
                        <i class="fas fa-exclamation-circle"></i>
                        <span><?= session('error') ?></span>
                        <button type="button" class="alert-close" data-dismiss="alert">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                <?php endif; ?>

                <?php if (session()->has('warning')): ?>
                    <div class="alert alert-warning alert-dismissible">
                        <i class="fas fa-exclamation-triangle"></i>
                        <span><?= session('warning') ?></span>
                        <button type="button" class="alert-close" data-dismiss="alert">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                <?php endif; ?>

                <?php if (session()->has('info')): ?>
                    <div class="alert alert-info alert-dismissible">
                        <i class="fas fa-info-circle"></i>
                        <span><?= session('info') ?></span>
                        <button type="button" class="alert-close" data-dismiss="alert">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                <?php endif; ?>

                <!-- Page Content Section -->
                <?= $this->renderSection('content') ?>
            </main>

            <!-- Footer -->
            <?= view('layouts/partials/footer') ?>
        </div>
    </div>

    <!-- Backdrop for mobile sidebar -->
    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

    <!-- Core JavaScript -->
    <script src="<?= base_url('assets/modern/js/dashboard.js') ?>"></script>
    <script src="<?= base_url('assets/modern/js/sidebar.js') ?>"></script>
    <script src="<?= base_url('assets/modern/js/theme-switcher.js') ?>"></script>

    <!-- Additional Page JS -->
    <?= $this->renderSection('js') ?>
</body>
</html>
