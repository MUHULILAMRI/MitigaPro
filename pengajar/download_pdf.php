<?php
require $_SERVER['DOCUMENT_ROOT'] . '/MitigaPro/include/autoload.php';
require INCLUDE_PATH . 'fpdf.php';

// Validasi input
if (empty($_GET['nip'])) {
    header("Location: " . BASE_URL . "pengajar/pengajar.php");
    exit;
}

$nip                    = trim($_GET['nip']);
$nama_lengkap_pelatihan = trim($_GET['nama_lengkap_pelatihan'] ?? '');
$instansi_penyelenggara = trim($_GET['instansi'] ?? '');
$tanggal                = trim($_GET['tanggal'] ?? '');

// Ambil data pengajar (prepared statement — aman dari SQL injection)
$stmt = $conn->prepare("SELECT * FROM pengajar WHERE nip = ?");
$stmt->bind_param("s", $nip);
$stmt->execute();
$result = $stmt->get_result();
$data   = $result->fetch_assoc();
$stmt->close();

if (!$data) {
    die("Data pengajar tidak ditemukan.");
}

// Label tampilan yang rapi
$label_map = [
    'nip'               => 'NIP',
    'nama_pengajar'     => 'Nama Lengkap',
    'jenis_kelamin'     => 'Jenis Kelamin',
    'agama'             => 'Agama',
    'pendidikan_terakhir' => 'Pendidikan Terakhir',
    'golongan'          => 'Golongan / Ruang',
    'tempat_lahir'      => 'Tempat Lahir',
    'tanggal_lahir'     => 'Tanggal Lahir',
    'no_hp'             => 'Nomor HP',
    'email_pengajar'    => 'Email',
    'jabatan'           => 'Jabatan',
    'unit_kerja'        => 'Unit Kerja',
    'instansi'          => 'Instansi',
    'alamat_kantor'     => 'Alamat Kantor',
    'npwp'              => 'NPWP',
];

$skip_fields = ['foto', 'status', 'created_at', 'updated_at'];

// ── Mulai PDF ────────────────────────────────────────────────
$pdf = new FPDF('P', 'mm', 'A4');
$pdf->SetMargins(20, 20, 20);
$pdf->AddPage();
$pdf->SetAutoPageBreak(true, 20);

// ── FOTO (pojok kanan atas) ──────────────────────────────────
$fotoY = 20;
if (!empty($data['foto'])) {
    $fotoPath = ROOT_PATH . 'uploads/pengajar/' . basename($data['foto']);
    if (file_exists($fotoPath)) {
        $pdf->Image($fotoPath, 160, $fotoY, 30, 35);
    }
}

// ── HEADER ──────────────────────────────────────────────────
$pdf->SetFont('Helvetica', 'B', 13);
$pdf->Cell(0, 8, 'DAFTAR BIODATA', 0, 1, 'C');
$pdf->Cell(0, 8, 'WIDYAISWARA / NARASUMBER / PENCERAMAH', 0, 1, 'C');

if ($nama_lengkap_pelatihan) {
    $pdf->SetFont('Helvetica', 'B', 11);
    $pdf->Cell(0, 7, strtoupper($nama_lengkap_pelatihan), 0, 1, 'C');
}
if ($instansi_penyelenggara) {
    $pdf->SetFont('Helvetica', '', 10);
    $pdf->Cell(0, 6, strtoupper($instansi_penyelenggara), 0, 1, 'C');
}
if ($tanggal) {
    $pdf->SetFont('Helvetica', '', 10);
    $pdf->Cell(0, 6, strtoupper($tanggal), 0, 1, 'C');
}

$pdf->Ln(4);
// Garis pemisah
$pdf->SetDrawColor(80, 80, 80);
$pdf->SetLineWidth(0.5);
$pdf->Line(20, $pdf->GetY(), 190, $pdf->GetY());
$pdf->Ln(5);

// ── BIODATA ─────────────────────────────────────────────────
$colLabel = 65;
$colSep   = 5;
$colVal   = 0; // sisa lebar

foreach ($label_map as $key => $label) {
    if (in_array($key, $skip_fields) || !array_key_exists($key, $data)) continue;

    $value = (string)($data[$key] ?? '-');
    if ($value === '') $value = '-';

    // Format tanggal lahir
    if ($key === 'tanggal_lahir' && $value !== '-') {
        $ts = strtotime($value);
        if ($ts) $value = date('d F Y', $ts);
    }

    $pdf->SetFont('Helvetica', 'B', 10);
    $pdf->Cell($colLabel, 7, $label, 0, 0);
    $pdf->SetFont('Helvetica', '', 10);
    $pdf->Cell($colSep, 7, ':', 0, 0, 'C');
    $pdf->MultiCell(0, 7, $value, 0, 'L');
}

$pdf->Ln(3);
$pdf->Line(20, $pdf->GetY(), 190, $pdf->GetY());

// ── TANDA TANGAN ────────────────────────────────────────────
$pdf->Ln(5);
$pdf->SetFont('Helvetica', '', 10);
$pdf->Cell(0, 7, 'Makassar,  ................................', 0, 1, 'R');
$pdf->Ln(18);
$pdf->SetFont('Helvetica', 'B', 10);
$nama = strtoupper($data['nama_pengajar']);
$pdf->Cell(0, 7, $nama, 0, 1, 'R');
$pdf->SetFont('Helvetica', '', 9);
$pdf->Cell(0, 6, 'NIP. ' . $data['nip'], 0, 1, 'R');

// ── Output ─────────────────────────────────────────────────
$filename = 'Biodata_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', $data['nama_pengajar']) . '.pdf';
$pdf->Output('D', $filename);
?>
