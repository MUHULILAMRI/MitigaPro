<?php
// File debug sementara — hapus setelah problem selesai
require $_SERVER['DOCUMENT_ROOT'] . '/MitigaPro/include/autoload.php';

echo "<pre style='font-family:monospace;font-size:14px;padding:20px'>";

// Test koneksi
echo "DB Connect: " . ($conn ? "OK\n" : "FAIL\n");

// Test tabel pengajar ada
$r = $conn->query("SHOW TABLES LIKE 'pengajar'");
echo "Tabel pengajar: " . ($r->num_rows > 0 ? "ADA\n" : "TIDAK ADA\n");

// Test kolom
$cols = $conn->query("DESCRIBE pengajar");
echo "\nKolom tabel pengajar:\n";
while ($c = $cols->fetch_assoc()) {
    echo "  {$c['Field']} | {$c['Type']} | Null:{$c['Null']} | Default:{$c['Default']}\n";
}

// Test INSERT langsung
$test_nip = 'TEST_DEBUG_999';
$conn->query("DELETE FROM pengajar WHERE nip='$test_nip'");

$stmt = $conn->prepare("INSERT INTO pengajar
    (nip,nama_pengajar,jenis_kelamin,agama,pendidikan_terakhir,golongan,
     tempat_lahir,tanggal_lahir,no_hp,email_pengajar,jabatan,unit_kerja,
     instansi,alamat_kantor,npwp,foto,status)
    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");

if (!$stmt) {
    echo "\nPREPARE ERROR: " . $conn->error . "\n";
} else {
    $g = null; $n = null; $f = null;
    $nama       = 'Test User';
    $jk         = 'Laki-laki';
    $agama      = 'ISLAM';
    $pend       = 'Sarjana S1';
    $tlahir     = 'Makassar';
    $tgl        = '1990-01-01';
    $hp         = '081234567890';
    $email      = 'test@test.com';
    $jabatan    = 'Pengajar';
    $unit       = 'Unit Test';
    $instansi   = 'PU';
    $alamat     = 'Jl. Test No.1';
    $status     = 'aktif';
    $stmt->bind_param('sssssssssssssssss',
        $test_nip,$nama,$jk,$agama,$pend,$g,
        $tlahir,$tgl,$hp,$email,
        $jabatan,$unit,$instansi,$alamat,$n,$f,$status);
    if ($stmt->execute()) {
        echo "\nINSERT TEST: BERHASIL ✅\n";
        $conn->query("DELETE FROM pengajar WHERE nip='$test_nip'");
    } else {
        echo "\nINSERT ERROR: " . $stmt->error . "\n";
        echo "ERRNO: " . $stmt->errno . "\n";
    }
    $stmt->close();
}

echo "</pre>";
?>
