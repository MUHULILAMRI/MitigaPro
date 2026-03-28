<?php
require $_SERVER['DOCUMENT_ROOT'] . '/MitigaPro/include/autoload.php';


header('Content-Type: application/json');

$nip = $_GET['nip'] ?? '';

if (empty($nip)) {
    echo json_encode(['exists' => false]);
    exit;
}

$stmt = $conn->prepare("SELECT COUNT(*) FROM pengajar WHERE nip = ?");
$stmt->bind_param("s", $nip);
$stmt->execute();
$stmt->bind_result($jumlah);
$stmt->fetch();
$stmt->close();

echo json_encode(['exists' => $jumlah > 0]);
?>
