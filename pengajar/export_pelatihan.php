<?php
require $_SERVER['DOCUMENT_ROOT'] . '/MitigaPro/include/autoload.php';

if (!isset($_SESSION['role'])) {
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}

$tahun_filter = isset($_GET['tahun']) ? intval($_GET['tahun']) : 0;
$where = $tahun_filter ? " AND p.tahun = " . intval($tahun_filter) : "";

$query = "SELECT p.jenis_pelatihan, p.kebutuhan, p.tahun, d.nama_dinas, w.nama_wilayah, p.created_at
          FROM identifikasi_pelatihan p
          INNER JOIN dinas d ON p.dinas_id = d.id
          INNER JOIN wilayah w ON d.wilayah_id = w.id
          WHERE 1=1 $where
          ORDER BY w.nama_wilayah, d.nama_dinas, p.tahun DESC";
$result = $conn->query($query);

$suffix = $tahun_filter ? "_tahun_{$tahun_filter}" : '';
$filename = 'data_pelatihan' . $suffix . '_' . date('Y-m-d_His') . '.csv';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');

// BOM for Excel UTF-8 compatibility
fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

// Header row
fputcsv($output, [
    'No', 'Jenis Pelatihan', 'Kebutuhan', 'Tahun', 'Nama Dinas',
    'Wilayah', 'Tanggal Ditambahkan'
]);

$no = 1;
while ($row = $result->fetch_assoc()) {
    fputcsv($output, [
        $no++,
        $row['jenis_pelatihan'],
        $row['kebutuhan'],
        $row['tahun'],
        $row['nama_dinas'],
        $row['nama_wilayah'],
        date('d/m/Y', strtotime($row['created_at']))
    ]);
}

fclose($output);
exit;
