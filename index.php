<?php
// index.php — Entry point MitigaPro
// Redirect ke halaman yang sesuai berdasarkan role

session_start();

if (!isset($_SESSION['role'])) {
    header('Location: /MitigaPro/login.php');
    exit;
}

$role = $_SESSION['role'] ?? 'pengajar';

switch ($role) {
    case 'admin':
        header('Location: /MitigaPro/mitigapro/admin/db_mitigapro.php');
        break;
    case 'tamu':
        header('Location: /MitigaPro/pengajar/dashboard_tamu.php');
        break;
    case 'pengajar':
    default:
        header('Location: /MitigaPro/pengajar/dashboard.php');
        break;
}
exit;
