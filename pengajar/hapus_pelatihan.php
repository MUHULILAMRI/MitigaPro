<?php
require $_SERVER['DOCUMENT_ROOT'] . '/MitigaPro/include/autoload.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
require_role('admin');

if (!isset($_GET['id'])) {
    header('Location: ' . BASE_URL . 'pengajar/dashboard.php'); exit;
}

$id = intval($_GET['id']);

// Ambil info pelatihan untuk tahu dinas_id
$stmt = $conn->prepare("SELECT dinas_id FROM identifikasi_pelatihan WHERE id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$d = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$d) { header('Location: ' . BASE_URL . 'pengajar/dashboard.php'); exit; }

$dinas_id = $d['dinas_id'];

// Hapus data
$stmt = $conn->prepare("DELETE FROM identifikasi_pelatihan WHERE id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$stmt->close();

if (isset($_GET['from']) && $_GET['from'] === 'list') {
    header('Location: daftar_pelatihan.php?success=deleted');
} else {
    header("Location: detail_dinas.php?id=" . $dinas_id);
}
exit;
