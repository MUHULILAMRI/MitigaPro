<?php
require $_SERVER['DOCUMENT_ROOT'] . '/MitigaPro/include/autoload.php';

if (!isset($_GET['type']) || !isset($_GET['id'])) {
    header('Location: ' . BASE_URL . 'mitigapro/admin/belanja_modal.php'); exit;
}

$type = $_GET['type'];
$id   = intval($_GET['id']);

$redirect = isset($_GET['redirect']) ? $_GET['redirect'] : '/MitigaPro/mitigapro/admin/belanja_modal.php';

// HAPUS HANDLER (pegawai)
if ($type === "handler") {

    // ambil data untuk hapus foto
    $stmt = $conn->prepare("SELECT photo FROM mitigapro_handlers WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($res && !empty($res['photo'])) {
        $file = $_SERVER['DOCUMENT_ROOT'] . $res['photo'];
        if (file_exists($file)) unlink($file);
    }

    // hapus mapping menu
    $delMap = $conn->prepare("DELETE FROM mitigapro_handler_menu WHERE handler_id = ?");
    $delMap->bind_param('i', $id);
    $delMap->execute();
    $delMap->close();

    // hapus handler utama
    $del = $conn->prepare("DELETE FROM mitigapro_handlers WHERE id = ?");
    $del->bind_param('i', $id);
    $del->execute();
    $del->close();
}

/*HAPUS KONTEN*/
   
if ($type === "content") {

    // ambil data konten untuk hapus gambar
    $stmt = $conn->prepare("SELECT image FROM mitigapro_contents WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $stmt->close();

    // hapus file gambar jika ada
    if ($row && !empty($row['image'])) {
        $file_path = $_SERVER['DOCUMENT_ROOT'] . $row['image'];
        if (file_exists($file_path)) unlink($file_path);
    }

    // hapus row dari tabel
    $stmt2 = $conn->prepare("DELETE FROM mitigapro_contents WHERE id = ?");
    $stmt2->bind_param('i', $id);
    $stmt2->execute();
    $stmt2->close();

    header("Location: $redirect");
    exit;
}


// redirect kembali
header("Location: $redirect");
exit;
