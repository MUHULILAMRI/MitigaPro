<?php
/**
 * sidebar_mitigapro.php
 * Sidebar navigasi untuk panel Admin MitigaPro.
 */

$_sidebar_username = $_SESSION['username'] ?? 'Admin';
$_current_page     = basename($_SERVER['PHP_SELF']);

function sb_link(string $href, string $icon, string $label, string $page, string $match): string {
    $active = (strpos($page, $match) !== false) ? 'active' : '';
    return "<li>
        <a href=\"$href\" class=\"$active\">
            <span class=\"sb-icon\">$icon</span>
            <span class=\"sb-label\">$label</span>
        </a>
    </li>";
}
?>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <img src="<?= BASE_URL ?>logo.png" alt="Logo" class="sidebar-logo-img" style="width:24px;height:24px">
        <span class="sidebar-title">MitigaPro</span>
        <button class="sidebar-toggle" id="sidebarToggle" title="Collapse">&#9776;</button>
    </div>

    <div class="sidebar-user">
        <div class="sidebar-avatar"><i class="fas fa-user-circle"></i></div>
        <div class="sidebar-user-info">
            <span class="sidebar-username"><?= htmlspecialchars($_sidebar_username) ?></span>
            <span class="sidebar-role">Admin</span>
        </div>
    </div>

    <nav class="sidebar-nav">
        <ul>
            <li class="nav-section">Menu Utama</li>
            <?= sb_link(BASE_URL . 'mitigapro/admin/db_mitigapro.php',  '<i class="fas fa-tachometer-alt"></i>', 'Dashboard',       $_current_page, 'db_mitigapro') ?>
            <?= sb_link(BASE_URL . 'mitigapro/admin/belanja_modal.php', '<i class="fas fa-wallet"></i>', 'Belanja Modal',    $_current_page, 'belanja_modal') ?>

            <li class="nav-section">Manajemen</li>
            <?= sb_link(BASE_URL . 'mitigapro/admin/manage_users.php',  '<i class="fas fa-users"></i>', 'Kelola User',      $_current_page, 'manage_users') ?>
            <?= sb_link(BASE_URL . 'pengajar/pengajar_add.php',         '<i class="fas fa-plus"></i>', 'Tambah Pengajar',  $_current_page, 'pengajar_add') ?>
            <?= sb_link(BASE_URL . 'pengajar/pengajar.php',             '<i class="fas fa-user-tie"></i>', 'Data Pengajar',    $_current_page, 'pengajar.php') ?>

            <li class="nav-section">Referensi</li>
            <?= sb_link(BASE_URL . 'pengajar/dashboard.php',            '<i class="fas fa-map"></i>', 'Peta Wilayah',     $_current_page, 'dashboard') ?>

            <li class="nav-section">Akun</li>
            <?= sb_link(BASE_URL . 'mitigapro/admin/change_password.php', '<i class="fas fa-key"></i>', 'Ganti Password',  $_current_page, 'change_password') ?>
            <li>
                <a href="<?= BASE_URL ?>logout.php" onclick="return confirm('Yakin ingin keluar?')">
                    <span class="sb-icon"><i class="fas fa-sign-out-alt"></i></span>
                    <span class="sb-label">Keluar</span>
                </a>
            </li>
        </ul>
    </nav>
</aside>

<!-- Loading overlay untuk navigasi -->
<div id="sidebarLoading" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(255,255,255,0.9);align-items:center;justify-content:center;flex-direction:column;gap:14px;font-family:'Poppins',sans-serif">
    <div style="width:44px;height:44px;border:4px solid #e2e8f0;border-top-color:#3b82f6;border-radius:50%;animation:sidebarSpin 1s linear infinite"></div>
    <div style="font-size:13px;color:#64748b;font-weight:500">Memuat halaman...</div>
</div>
<style>@keyframes sidebarSpin { to { transform: rotate(360deg); } }</style>

<script>
(function () {
    const toggle  = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    const main    = document.getElementById('mainContent');
    if (toggle) {
        toggle.addEventListener('click', function () {
            sidebar.classList.toggle('collapsed');
            if (main) main.classList.toggle('expanded');
        });
    }

    // Loading saat klik menu sidebar
    const loader = document.getElementById('sidebarLoading');
    document.querySelectorAll('.sidebar-nav a:not([onclick])').forEach(function(link) {
        if (link.classList.contains('active')) return;
        link.addEventListener('click', function(e) {
            e.preventDefault();
            loader.style.display = 'flex';
            setTimeout(function() {
                window.location.href = link.href;
            }, 600);
        });
    });
})();
</script>
