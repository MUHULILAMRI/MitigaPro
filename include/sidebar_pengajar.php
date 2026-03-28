<?php
/**
 * sidebar_pengajar.php
 * Sidebar navigasi untuk panel Pengajar / User MitigaPro.
 */

$_sb_username = $_SESSION['username'] ?? 'Tamu';
$_sb_role     = $_SESSION['role']     ?? 'tamu';
$_sb_rolabel  = match ($_sb_role) {
    'admin'    => 'Admin',
    'pengajar' => 'Pengajar',
    default    => 'Pengunjung',
};
$_sb_page = basename($_SERVER['PHP_SELF']);

function sb_plink(string $href, string $icon, string $label, string $page, string $match): string {
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
        <img src="<?= BASE_URL ?>logo.png" alt="Logo" class="sidebar-logo-img">
        <span class="sidebar-title">MitigaPro</span>
        <button class="sidebar-toggle" id="sidebarToggle" title="Toggle">&#9776;</button>
    </div>

    <div class="sidebar-user">
        <div class="sidebar-avatar"><i class="fas fa-user-circle"></i></div>
        <div class="sidebar-user-info">
            <span class="sidebar-username"><?= htmlspecialchars($_sb_username) ?></span>
            <span class="sidebar-role"><?= htmlspecialchars($_sb_rolabel) ?></span>
        </div>
    </div>

    <nav class="sidebar-nav">
        <ul>
            <li class="nav-section">Overview</li>
            <?= sb_plink(BASE_URL . 'pengajar/dashboard.php',  '<i class="fas fa-home"></i>',         'Dashboard',       $_sb_page, 'dashboard') ?>

            <?php if ($_sb_role !== 'tamu'): ?>
            <li class="nav-section">Data</li>
            <?= sb_plink(BASE_URL . 'pengajar/pengajar.php',   '<i class="fas fa-user-tie"></i>',     'Data Pengajar',   $_sb_page, 'pengajar.php') ?>
            <?= sb_plink(BASE_URL . 'pengajar/dinas.php',       '<i class="fas fa-building"></i>',    'Data Dinas',      $_sb_page, 'dinas.php') ?>
            <?= sb_plink(BASE_URL . 'pengajar/pelatihan_dashboard.php', '<i class="fas fa-graduation-cap"></i>', 'Pelatihan', $_sb_page, 'pelatihan_dashboard') ?>
            <?= sb_plink(BASE_URL . 'pengajar/daftar_pelatihan.php', '<i class="fas fa-list-alt"></i>', 'Daftar Pelatihan', $_sb_page, 'daftar_pelatihan') ?>
            <?= sb_plink(BASE_URL . 'pengajar/berita_pelatihan.php', '<i class="fas fa-newspaper"></i>', 'Berita Pelatihan', $_sb_page, 'berita') ?>

            <?php if ($_sb_role === 'admin'): ?>
            <li class="nav-section">Admin</li>
            <?= sb_plink(BASE_URL . 'pengajar/pengajar_add.php','<i class="fas fa-user-plus"></i>',   'Tambah Pengajar', $_sb_page, 'pengajar_add') ?>
            <?= sb_plink(BASE_URL . 'pengajar/tambah_pelatihan_baru.php', '<i class="fas fa-plus-circle"></i>', 'Tambah Pelatihan', $_sb_page, 'tambah_pelatihan_baru') ?>
            <?= sb_plink(BASE_URL . 'pengajar/kelola_berita.php', '<i class="fas fa-pen-nib"></i>', 'Kelola Berita', $_sb_page, 'kelola_berita') ?>
            <?php endif; ?>

            <li class="nav-section">Referensi</li>
            <?= sb_plink(BASE_URL . 'pengajar/dashboard.php#wilayah', '<i class="fas fa-map-marked-alt"></i>', 'Peta Wilayah', $_sb_page, '__none__') ?>
            <?php endif; ?>

            <?php if ($_sb_role !== 'tamu'): ?>
            <li class="nav-section">Akun</li>
            <?= sb_plink(BASE_URL . 'pengajar/settings.php', '<i class="fas fa-cog"></i>', 'Pengaturan',    $_sb_page, 'settings') ?>
            <li>
                <a href="<?= BASE_URL ?>logout.php" onclick="return confirm('Yakin ingin keluar?')">
                    <span class="sb-icon"><i class="fas fa-sign-out-alt"></i></span>
                    <span class="sb-label">Keluar</span>
                </a>
            </li>
            <?php else: ?>
            <li class="nav-section">Akun</li>
            <li>
                <a href="<?= BASE_URL ?>login.php">
                    <span class="sb-icon"><i class="fas fa-sign-in-alt"></i></span>
                    <span class="sb-label">Masuk / Login</span>
                </a>
            </li>
            <?php endif; ?>
        </ul>
    </nav>
</aside>

<!-- Mobile overlay -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- Loading overlay -->
<div id="sidebarLoading" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(255,255,255,0.92);align-items:center;justify-content:center;flex-direction:column;gap:14px;font-family:'Poppins',sans-serif">
    <div style="width:44px;height:44px;border:4px solid #e2e8f0;border-top-color:#3b82f6;border-radius:50%;animation:sbSpin 1s linear infinite"></div>
    <div style="font-size:13px;color:#64748b;font-weight:500">Memuat halaman...</div>
</div>
<style>@keyframes sbSpin{to{transform:rotate(360deg)}}</style>

<script>
(function(){
    const toggle  = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const main    = document.getElementById('mainContent');

    if(toggle){
        toggle.addEventListener('click',function(){
            sidebar.classList.toggle('collapsed');
            sidebar.classList.toggle('mobile-open');
            if(main) main.classList.toggle('expanded');
            overlay.classList.toggle('show');
        });
    }
    if(overlay){
        overlay.addEventListener('click',function(){
            sidebar.classList.remove('mobile-open');
            overlay.classList.remove('show');
        });
    }

    // Loading on nav click
    const loader = document.getElementById('sidebarLoading');
    document.querySelectorAll('.sidebar-nav a:not([onclick])').forEach(function(link){
        if(link.classList.contains('active')) return;
        link.addEventListener('click',function(e){
            e.preventDefault();
            loader.style.display='flex';
            setTimeout(function(){ window.location.href=link.href; },400);
        });
    });
})();
</script>
