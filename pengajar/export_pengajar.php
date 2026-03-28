<?php
require $_SERVER['DOCUMENT_ROOT'] . '/MitigaPro/include/autoload.php';

if (!isset($_SESSION['role'])) {
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}

$search = isset($_GET['search']) ? $_GET['search'] : '';
$searchTerm = "%$search%";

$query = "SELECT nip, nama_pengajar, jenis_kelamin, agama, pendidikan_terakhir, golongan,
                 tempat_lahir, tanggal_lahir, no_hp, email_pengajar, jabatan, unit_kerja,
                 instansi, alamat_kantor, npwp, status, created_at
          FROM pengajar
          WHERE nama_pengajar LIKE ? OR nip LIKE ?
          ORDER BY created_at DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("ss", $searchTerm, $searchTerm);
$stmt->execute();
$result = $stmt->get_result();

$filename = 'data_pengajar_' . date('Y-m-d_His') . '.csv';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');

// BOM for Excel UTF-8 compatibility
fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

// Header row
fputcsv($output, [
    'NIP', 'Nama Pengajar', 'Jenis Kelamin', 'Agama', 'Pendidikan Terakhir',
    'Golongan', 'Tempat Lahir', 'Tanggal Lahir', 'No HP', 'Email',
    'Jabatan', 'Unit Kerja', 'Instansi', 'Alamat Kantor', 'NPWP',
    'Status', 'Tanggal Ditambahkan'
]);

while ($row = $result->fetch_assoc()) {
    fputcsv($output, [
        $row['nip'],
        $row['nama_pengajar'],
        $row['jenis_kelamin'],
        $row['agama'],
        $row['pendidikan_terakhir'],
        $row['golongan'],
        $row['tempat_lahir'],
        $row['tanggal_lahir'],
        $row['no_hp'],
        $row['email_pengajar'],
        $row['jabatan'],
        $row['unit_kerja'],
        $row['instansi'],
        $row['alamat_kantor'],
        $row['npwp'],
        ucfirst($row['status']),
        date('d/m/Y', strtotime($row['created_at']))
    ]);
}

fclose($output);
$stmt->close();
exit;
