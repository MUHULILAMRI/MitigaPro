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

// Ambil wilayah_id sebelum dihapus
$stmt = $conn->prepare("SELECT wilayah_id FROM dinas WHERE id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$d = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$d) { header('Location: ' . BASE_URL . 'pengajar/dashboard.php'); exit; }

$wilayah_id = $d['wilayah_id'];

// Hapus dinas
$stmt = $conn->prepare("DELETE FROM dinas WHERE id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$stmt->close();

// Redirect kembali ke halaman wilayah
header("Location: wilayah.php?id=" . $wilayah_id);
exit;
