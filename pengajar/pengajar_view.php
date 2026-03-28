<?php
require $_SERVER['DOCUMENT_ROOT'] . '/MitigaPro/include/autoload.php';

// pastikan user login
if (!isset($_SESSION['role'])) {
    header("Location: login.php");
    exit;
}

require INCLUDE_PATH . 'sidebar_pengajar.php';

$role = $_SESSION['role'];

// cek NIP
if (!isset($_GET['nip'])) {
    header("Location: pengajar.php");
    exit;
}

$nip = $_GET['nip'];

// ambil data pengajar
$stmt = $conn->prepare("SELECT * FROM pengajar WHERE nip = ?");
$stmt->bind_param("s", $nip);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();

if (!$data) {
    echo "<h2>Data tidak ditemukan.</h2>";
    exit;
}

// menentukan foto
$foto = (!empty($data['foto'])) ? $data['foto'] : 'default.jpg';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Pengajar | MitigaPro</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>1_css/pengajar_view.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>1_css/sidebar_pengajar.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<div id="mainContent" class="main-content">
<div class="profile-container">
    <?= breadcrumb([['label' => 'Data Pengajar', 'url' => 'pengajar.php'], ['label' => htmlspecialchars($data['nama_pengajar'])]]) ?>
    <div class="profile-header">
        <div class="photo-section">
            <img 
                src="<?= BASE_URL ?>uploads/pengajar/<?= htmlspecialchars($foto) ?>" 
                alt="Foto Pengajar <?= htmlspecialchars($data['nama_pengajar']); ?>">
        </div>


        <div class="info-section">
            <h2><?php echo htmlspecialchars($data['nama_pengajar']); ?></h2>
            <p><?php echo htmlspecialchars($data['jabatan']); ?> — <?php echo htmlspecialchars($data['unit_kerja']); ?></p>
            <span class="status <?php echo $data['status']; ?>">
                <?php echo ucfirst($data['status']); ?>
            </span>
        </div>
    </div>

    <div class="profile-content">
        <!-- Data Pribadi -->
        <div class="card">
            <h3><i class="fa-solid fa-id-card" style="color:#3b82f6;margin-right:6px"></i> Data Pribadi</h3>
            <div class="grid">
                <p><strong>NIP:</strong> <?= htmlspecialchars($data['nip']) ?></p>
                <p><strong>Jenis Kelamin:</strong> <?= htmlspecialchars($data['jenis_kelamin']) ?></p>
                <p><strong>Tempat, Tanggal Lahir:</strong> <?= htmlspecialchars($data['tempat_lahir']) . ', ' . date('d M Y', strtotime($data['tanggal_lahir'])) ?></p>
                <p><strong>Agama:</strong> <?= htmlspecialchars($data['agama']) ?></p>
                <p><strong>Pendidikan:</strong> <?= htmlspecialchars($data['pendidikan_terakhir']) ?></p>
                <p><strong>Golongan:</strong> <?= htmlspecialchars($data['golongan']) ?></p>
            </div>
        </div>

        <!-- Data Pekerjaan -->
        <div class="card">
            <h3><i class="fa-solid fa-briefcase" style="color:#3b82f6;margin-right:6px"></i> Data Pekerjaan</h3>
            <div class="grid">
                <p><strong>Instansi:</strong> <?= htmlspecialchars($data['instansi']) ?></p>
                <p><strong>Alamat Kantor:</strong> <?= htmlspecialchars($data['alamat_kantor']) ?></p>
                <p><strong>Email:</strong> <?= htmlspecialchars($data['email_pengajar']) ?></p>
                <p><strong>No. HP:</strong> <?= htmlspecialchars($data['no_hp']) ?></p>
            </div>
        </div>

        <!-- Data Lainnya -->
        <div class="card">
            <h3><i class="fa-solid fa-file-lines" style="color:#3b82f6;margin-right:6px"></i> Data Lain-Lain</h3>
            <div class="grid">
                <p><strong>Nomor NPWP:</strong> <?= htmlspecialchars($data['npwp']) ?></p>
            </div>
        </div>

        <div class="button-group">
            <?php if ($role === 'admin'): ?>
                <a href="pengajar_edit.php?nip=<?= htmlspecialchars($data['nip']) ?>" class="btn-primary"><i class="fa-solid fa-pen-to-square"></i> Edit</a>
            <?php endif; ?>
            <a href="pengajar.php" class="btn-secondary"><i class="fa-solid fa-arrow-left"></i> Kembali</a>
        </div>
    </div>

    <!-- Form Download PDF -->
    <div class="download-section">
        <h3><i class="fa-solid fa-file-pdf" style="color:#e74c3c;margin-right:6px"></i> Atur Informasi Pelatihan</h3>
        <form id="pdfForm" method="GET" action="download_pdf.php">
            <input type="hidden" name="nip" value="<?= $data['nip']; ?>">

            <div class="form-group">
                <label>Nama Pelatihan</label>
                <input type="text" name="nama_lengkap_pelatihan" placeholder="Contoh: PELATIHAN PERENCANAAN TEKNIS IRIGASI" required>
            </div>

            <div class="form-group">
                <label>Tanggal Pelatihan</label>
                <input type="text" name="tanggal" placeholder="Contoh: MAKASSAR, 29 JULI s.d 03 SEPTEMBER 2025" required>
            </div>

            <div class="form-group wide">
                <label>Instansi Penyelenggara</label>
                <input type="text" name="instansi" placeholder="Contoh: BALAI PENGEMBANGAN KOMPETENSI PU WILAYAH VIII MAKASSAR" required>
            </div>
            
            <button class="submit" style="float: right;"><i class="fa-solid fa-download"></i> Download PDF</button>
        </form>
    </div>
</div>  
</div><!-- /main-content -->
</body>
</html>
