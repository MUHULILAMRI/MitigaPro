<?php
/**
 * topbar_pengajar.php
 * Header navigasi untuk bagian Pengajar.
 * Di-include SEBELUM <!DOCTYPE html> — browser modern menangani ini dengan baik.
 */

// Ambil nama user dari session untuk ditampilkan di topbar
$_topbar_username = $_SESSION['username'] ?? 'Pengguna';
$_topbar_role     = $_SESSION['role']     ?? 'pengajar';
$_topbar_rolabel  = match ($_topbar_role) {
    'admin'    => 'Admin',
    default    => 'Pengajar',
};
?>
<nav class="topbar">
    <div class="topbar-left">
        <a href="<?= BASE_URL ?>pengajar/dashboard.php" class="topbar-brand">
            <span class="brand-icon">🏛️</span>
            <span class="brand-text">Bapekom PU&nbsp;VIII Makassar</span>
        </a>
    </div>

    <div class="topbar-center">
        <a href="<?= BASE_URL ?>pengajar/dashboard.php">Dashboard</a>
        <a href="<?= BASE_URL ?>pengajar/pengajar.php">Data Pengajar</a>
    </div>

    <div class="topbar-right">
        <span class="topbar-user">
            <?= htmlspecialchars($_topbar_rolabel) ?>: <strong><?= htmlspecialchars($_topbar_username) ?></strong>
        </span>
        <a href="<?= BASE_URL ?>logout.php" class="btn-logout">Keluar</a>
    </div>
</nav>
