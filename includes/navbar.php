<?php
$current_page = basename($_SERVER['PHP_SELF']);
$menu_items = [
    ['icon' => 'bi-speedometer2', 'label' => 'Dashboard', 'file' => 'dashboard.php', 'url' => BASE_URL.'/dashboard.php'],
    ['icon' => 'bi-people-fill', 'label' => 'Inquilinos', 'file' => 'inquilinos.php', 'url' => BASE_URL.'/pages/inquilinos.php'],
    ['icon' => 'bi-person-badge-fill', 'label' => 'Proprietários', 'file' => 'proprietarios.php', 'url' => BASE_URL.'/pages/proprietarios.php'],
    ['icon' => 'bi-house-fill', 'label' => 'Imóveis', 'file' => 'imoveis.php', 'url' => BASE_URL.'/pages/imoveis.php'],
    ['icon' => 'bi-file-earmark-text-fill', 'label' => 'Contratos', 'file' => 'contratos.php', 'url' => BASE_URL.'/pages/contratos.php'],
    ['icon' => 'bi-cash-stack', 'label' => 'Contas a Receber', 'file' => 'contas.php', 'url' => BASE_URL.'/pages/contas.php'],
    ['icon' => 'bi-receipt', 'label' => 'Recibos', 'file' => 'recibos.php', 'url' => BASE_URL.'/pages/recibos.php'],
    ['icon' => 'bi-tools', 'label' => 'Manutenções', 'file' => 'manutencoes.php', 'url' => BASE_URL.'/pages/manutencoes.php'],
    ['icon' => 'bi-bar-chart-fill', 'label' => 'Relatórios', 'file' => 'relatorios.php', 'url' => BASE_URL.'/pages/relatorios.php'],
];
if (is_admin()) {
    $menu_items[] = ['icon' => 'bi-people', 'label' => 'Usuários', 'file' => 'usuarios.php', 'url' => BASE_URL.'/pages/usuarios.php'];
}
?>
<!-- Sidebar -->
<nav id="sidebar" class="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo">
            <i class="bi bi-building-fill-check"></i>
            <span class="sidebar-title">AluguelPRO</span>
        </div>
        <button id="sidebarToggle" class="btn btn-sm btn-link text-white d-lg-none">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>

    <div class="sidebar-user">
        <div class="user-avatar">
            <i class="bi bi-person-circle"></i>
        </div>
        <div class="user-info">
            <div class="user-name"><?= htmlspecialchars($_SESSION['usuario_nome'] ?? 'Usuário') ?></div>
            <div class="user-role"><?= ucfirst($_SESSION['usuario_nivel'] ?? 'operador') ?></div>
        </div>
    </div>

    <ul class="nav flex-column sidebar-nav">
        <?php foreach ($menu_items as $item): ?>
        <?php $is_active = ($current_page === $item['file']); ?>
        <li class="nav-item">
            <a href="<?= $item['url'] ?>" class="nav-link <?= $is_active ? 'active' : '' ?>">
                <i class="bi <?= $item['icon'] ?>"></i>
                <span><?= $item['label'] ?></span>
            </a>
        </li>
        <?php endforeach; ?>
    </ul>

    <div class="sidebar-footer">
        <a href="<?= BASE_URL ?>/logout.php" class="nav-link text-danger">
            <i class="bi bi-box-arrow-left"></i>
            <span>Sair</span>
        </a>
    </div>
</nav>

<!-- Overlay para mobile -->
<div id="sidebarOverlay" class="sidebar-overlay"></div>

<!-- Main Content Wrapper -->
<div id="mainContent" class="main-content">
    <!-- Top Bar -->
    <header class="topbar">
        <button id="sidebarToggleMain" class="btn btn-link topbar-toggle">
            <i class="bi bi-list fs-4"></i>
        </button>
        <div class="topbar-title">
            <?= isset($page_title) ? htmlspecialchars($page_title) : 'Dashboard' ?>
        </div>
        <div class="topbar-actions">
            <span class="text-muted small d-none d-md-inline">
                <?= date('d/m/Y H:i') ?>
            </span>
            <button type="button" class="btn-tema" onclick="alternarTema(this)" aria-label="Alternar tema"><i class="bi bi-moon-stars"></i></button>
        </div>
    </header>

    <!-- Page Content -->
    <main class="page-content">
    <script>window.BASE_URL = '<?= BASE_URL ?>'; window.CSRF = '<?= csrf_token() ?>';</script>
