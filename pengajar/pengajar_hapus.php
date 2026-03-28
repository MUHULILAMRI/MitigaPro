<?php
require $_SERVER['DOCUMENT_ROOT'] . '/MitigaPro/include/autoload.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}
require_role('admin');

// Pastikan parameter NIP dikirim
if (!isset($_GET['nip'])) {
    header("Location: pengajar.php");
    exit;
}

$nip = $_GET['nip'];

// Jalankan query hapus
$stmt = $conn->prepare("DELETE FROM pengajar WHERE nip = ?");
$stmt->bind_param("s", $nip);

if ($stmt->execute()) {
    echo "<script>
            alert('Data berhasil dihapus!');
            window.location.href = 'pengajar.php';
          </script>";
} else {
    echo "<script>
            alert('Gagal menghapus data. Silakan coba lagi.');
            window.location.href = 'pengajar.php';
          </script>";
}
?>
