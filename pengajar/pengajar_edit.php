<?php
require $_SERVER['DOCUMENT_ROOT'] . '/MitigaPro/include/autoload.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}
require_role('admin');

// Pastikan NIP dikirim lewat URL
if (!isset($_GET['nip'])) {
    header("Location: pengajar.php");
    exit;
}

require INCLUDE_PATH . 'sidebar_pengajar.php';

$nip = $_GET['nip'];

// Ambil data lama pengajar
$stmt = $conn->prepare("SELECT * FROM pengajar WHERE nip = ?");
$stmt->bind_param("s", $nip);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();

if (!$data) {
    echo "<h3>Data tidak ditemukan.</h3>";
    exit;
}

// Proses update data
if (isset($_POST['update'])) {
    if (!csrf_verify()) { header('Location: ' . $_SERVER['REQUEST_URI']); exit; }
    $nama_lengkap = $_POST['nama_pengajar'];
    $jk = $_POST['jenis_kelamin'];
    $agama = $_POST['agama'];
    $pendidikan = $_POST['pendidikan_terakhir'];
    $golongan = $_POST['golongan'];
    $tempat = $_POST['tempat_lahir'];
    $tanggal = $_POST['tanggal_lahir'];
    $nohp = $_POST['no_hp'];
    $email = $_POST['email_pengajar'];
    $jabatan = $_POST['jabatan'];
    $unit = $_POST['unit_kerja'];
    $instansi = $_POST['instansi'];
    $alamat = $_POST['alamat_kantor'];
    $npwp = $_POST['npwp'];
    $status = $_POST['status'];

    $foto = $data['foto']; // pakai foto lama dulu

    if (!empty($_FILES['foto']['name'])) {

        // path absolut
        $targetDir = ROOT_PATH . "uploads/pengajar/";

        if (!is_dir($targetDir)) mkdir($targetDir, 0755, true);

        $fileName = time() . "_" . basename($_FILES["foto"]["name"]);
        $targetFilePath = $targetDir . $fileName;

        if (move_uploaded_file($_FILES["foto"]["tmp_name"], $targetFilePath)) {
            $foto = $fileName; // simpan nama foto
        }
    }


    $stmt = $conn->prepare("UPDATE pengajar SET 
        nama_pengajar=?, jenis_kelamin=?, agama=?, pendidikan_terakhir=?, golongan=?, tempat_lahir=?, tanggal_lahir=?, 
        no_hp=?, email_pengajar=?, jabatan=?, unit_kerja=?, instansi=?, alamat_kantor=?, 
        npwp=?, foto=?, status=? 
        WHERE nip=?");

    $stmt->bind_param("sssssssssssssssss", 
        $nama_lengkap, $jk, $agama, $pendidikan, $golongan, $tempat, $tanggal, 
        $nohp, $email, $jabatan, $unit, $instansi, $alamat, 
        $npwp, $foto, $status, $nip
    );

    if ($stmt->execute()) {
        header("Location: pengajar_view.php?nip=$nip&updated=1");
        exit;
    } else {
        echo "<script>alert('Gagal memperbarui data');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Pengajar | MitigaPro</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>1_css/sidebar_pengajar.css">
    <style>
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
    :root{--navy:#1a2744;--blue:#2c5282;--accent:#3b82f6;--bg:#f5f7fb;--white:#fff;--border:#e2e8f0;--muted:#64748b;--radius:12px}
    body{font-family:'Poppins',sans-serif;background:var(--bg);color:var(--navy);padding:30px 20px}

    .container{max-width:900px;margin:0 auto}
    .page-header{background:linear-gradient(135deg,var(--navy),var(--blue));border-radius:var(--radius);padding:24px 28px;color:#fff;margin-bottom:24px}
    .page-header h2{font-size:20px;font-weight:700;display:flex;align-items:center;gap:10px;margin:0}
    .page-header p{font-size:12px;opacity:.7;margin-top:4px}

    .card{background:var(--white);border-radius:var(--radius);border:1px solid var(--border);padding:24px 28px;margin-bottom:20px}
    .card-title{font-size:14px;font-weight:700;color:var(--navy);margin-bottom:16px;padding-bottom:10px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:8px}
    .card-title i{color:var(--accent);font-size:13px}

    .form-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:14px 24px}
    @media(max-width:640px){.form-grid{grid-template-columns:1fr}}

    .fg{display:flex;flex-direction:column}
    .fg.wide{grid-column:span 2}
    .fg label{font-size:11px;font-weight:600;margin-bottom:4px;color:var(--navy)}
    .fg input,.fg select,.fg textarea{padding:9px 12px;border:1px solid var(--border);border-radius:8px;font-size:13px;font-family:'Poppins',sans-serif;transition:border-color .2s;width:100%}
    .fg input:focus,.fg select:focus,.fg textarea:focus{outline:none;border-color:var(--accent)}
    .fg input[readonly]{background:#f8fafc;color:var(--muted)}

    .preview-box{margin-top:8px}
    .preview-box img{width:120px;height:120px;border-radius:10px;object-fit:cover;border:2px solid var(--border)}

    .button-group{display:flex;gap:10px;justify-content:center;margin-top:24px}
    .btn{padding:10px 28px;border-radius:8px;font-size:13px;font-weight:600;font-family:'Poppins',sans-serif;cursor:pointer;text-decoration:none;border:none;display:inline-flex;align-items:center;gap:6px;transition:opacity .2s}
    .btn:hover{opacity:.85}
    .btn-primary{background:var(--accent);color:#fff}
    .btn-secondary{background:#f1f5f9;color:var(--navy);border:1px solid var(--border)}
    </style>
</head>
<body>
<div id="mainContent" class="main-content" style="padding:30px 20px">
<div class="container">
    <?= breadcrumb([['label' => 'Data Pengajar', 'url' => 'pengajar.php'], ['label' => htmlspecialchars($data['nama_pengajar']), 'url' => 'pengajar_view.php?nip=' . urlencode($nip)], ['label' => 'Edit']]) ?>
    <div class="page-header">
        <h2><i class="fa-solid fa-user-pen"></i> Edit Biodata Pengajar</h2>
        <p>Perbarui data profil pengajar</p>
    </div>

    <form action="" method="POST" enctype="multipart/form-data">
        <?= csrf_field() ?>

        <!-- Data Identitas -->
        <div class="card">
            <div class="card-title"><i class="fa-solid fa-id-card"></i> Data Identitas</div>
            <div class="form-grid">
                <div class="fg">
                    <label>NIP</label>
                    <input type="text" name="nip" maxlength="18" value="<?= htmlspecialchars($data['nip']) ?>" readonly>
                </div>
                <div class="fg">
                    <label>Nama Lengkap</label>
                    <input type="text" name="nama_pengajar" value="<?= htmlspecialchars($data['nama_pengajar']) ?>" required>
                </div>
                <div class="fg">
                    <label>Jenis Kelamin</label>
                    <select name="jenis_kelamin" required>
                        <option value="">-- Pilih --</option>
                        <option value="Laki-laki" <?php if($data['jenis_kelamin']=='Laki-laki') echo 'selected'; ?>>Laki-laki</option>
                        <option value="Perempuan" <?php if($data['jenis_kelamin']=='Perempuan') echo 'selected'; ?>>Perempuan</option>
                    </select>
                </div>
                <div class="fg">
                    <label>Agama</label>
                    <select name="agama" required>
                        <option value="ISLAM" <?php if($data['agama']=='ISLAM') echo 'selected'; ?>>ISLAM</option>
                        <option value="KRISTEN KATOLIK" <?php if($data['agama']=='KRISTEN KATOLIK') echo 'selected'; ?>>KRISTEN KATOLIK</option>
                        <option value="KRISTEN PROTESTAN" <?php if($data['agama']=='KRISTEN PROTESTAN') echo 'selected'; ?>>KRISTEN PROTESTAN</option>
                        <option value="HINDU" <?php if($data['agama']=='HINDU') echo 'selected'; ?>>HINDU</option>
                        <option value="BUDDHA" <?php if($data['agama']=='BUDDHA') echo 'selected'; ?>>BUDDHA</option>
                        <option value="KONGHUCU" <?php if($data['agama']=='KONGHUCU') echo 'selected'; ?>>KONGHUCU</option>
                    </select>
                </div>
                <div class="fg">
                    <label>Tempat Lahir</label>
                    <input type="text" name="tempat_lahir" value="<?= htmlspecialchars($data['tempat_lahir']) ?>">
                </div>
                <div class="fg">
                    <label>Tanggal Lahir</label>
                    <input type="date" name="tanggal_lahir" value="<?= htmlspecialchars($data['tanggal_lahir']) ?>">
                </div>
            </div>
        </div>

        <!-- Data Kepegawaian -->
        <div class="card">
            <div class="card-title"><i class="fa-solid fa-briefcase"></i> Data Kepegawaian</div>
            <div class="form-grid">
                <div class="fg">
                    <label>Pendidikan Terakhir</label>
                    <select name="pendidikan_terakhir" required>
                        <option value="SMA/SMK" <?php if($data['pendidikan_terakhir']=='SMA/SMK') echo 'selected'; ?>>SMA/SMK</option>
                        <option value="Diploma D1" <?php if($data['pendidikan_terakhir']=='Diploma D1') echo 'selected'; ?>>Diploma (D1)</option>
                        <option value="Diploma D2" <?php if($data['pendidikan_terakhir']=='Diploma D2') echo 'selected'; ?>>Diploma (D2)</option>
                        <option value="Diploma D3" <?php if($data['pendidikan_terakhir']=='Diploma D3') echo 'selected'; ?>>Diploma (D3)</option>
                        <option value="Sarjana Terapan D4" <?php if($data['pendidikan_terakhir']=='Sarjana Terapan D4') echo 'selected'; ?>>Sarjana Terapan (D4)</option>
                        <option value="Sarjana S1" <?php if($data['pendidikan_terakhir']=='Sarjana S1') echo 'selected'; ?>>Sarjana (S1)</option>
                        <option value="Magister S2" <?php if($data['pendidikan_terakhir']=='Magister S2') echo 'selected'; ?>>Magister (S2)</option>
                        <option value="Doctor S3" <?php if($data['pendidikan_terakhir']=='Doctor S3') echo 'selected'; ?>>Doctor (S3)</option>
                    </select>
                </div>
                <div class="fg">
                    <label>Golongan</label>
                    <select name="golongan" required>
                        <option value="III/A" <?php if($data['golongan']=='III/A') echo 'selected'; ?>>III/A</option>
                        <option value="III/B" <?php if($data['golongan']=='III/B') echo 'selected'; ?>>III/B</option>
                        <option value="III/C" <?php if($data['golongan']=='III/C') echo 'selected'; ?>>III/C</option>
                        <option value="III/D" <?php if($data['golongan']=='III/D') echo 'selected'; ?>>III/D</option>
                        <option value="IV/A" <?php if($data['golongan']=='IV/A') echo 'selected'; ?>>IV/A</option>
                        <option value="IV/B" <?php if($data['golongan']=='IV/B') echo 'selected'; ?>>IV/B</option>
                        <option value="IV/C" <?php if($data['golongan']=='IV/C') echo 'selected'; ?>>IV/C</option>
                        <option value="IV/D" <?php if($data['golongan']=='IV/D') echo 'selected'; ?>>IV/D</option>
                        <option value="IV/E" <?php if($data['golongan']=='IV/E') echo 'selected'; ?>>IV/E</option>
                    </select>
                </div>
                <div class="fg">
                    <label>Jabatan</label>
                    <input type="text" name="jabatan" value="<?= htmlspecialchars($data['jabatan']) ?>">
                </div>
                <div class="fg">
                    <label>Unit Kerja</label>
                    <input type="text" name="unit_kerja" value="<?= htmlspecialchars($data['unit_kerja']) ?>">
                </div>
                <div class="fg">
                    <label>Instansi</label>
                    <input type="text" name="instansi" value="<?= htmlspecialchars($data['instansi']) ?>">
                </div>
                <div class="fg">
                    <label>Status</label>
                    <select name="status">
                        <option value="aktif" <?php if($data['status']=='aktif') echo 'selected'; ?>>Aktif</option>
                        <option value="nonaktif" <?php if($data['status']=='nonaktif') echo 'selected'; ?>>Nonaktif</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Kontak & Lainnya -->
        <div class="card">
            <div class="card-title"><i class="fa-solid fa-address-book"></i> Kontak & Lainnya</div>
            <div class="form-grid">
                <div class="fg">
                    <label>No HP</label>
                    <input type="text" name="no_hp" value="<?= htmlspecialchars($data['no_hp']) ?>">
                </div>
                <div class="fg">
                    <label>Email</label>
                    <input type="email" name="email_pengajar" value="<?= htmlspecialchars($data['email_pengajar']) ?>">
                </div>
                <div class="fg">
                    <label>Nomor NPWP</label>
                    <input type="text" name="npwp" value="<?= htmlspecialchars($data['npwp']) ?>">
                </div>
                <div class="fg wide">
                    <label>Alamat Kantor</label>
                    <textarea name="alamat_kantor" rows="3"><?= htmlspecialchars($data['alamat_kantor']) ?></textarea>
                </div>
            </div>
        </div>

        <!-- Foto -->
        <div class="card">
            <div class="card-title"><i class="fa-solid fa-camera"></i> Foto Pengajar</div>
            <div class="fg">
                <input type="file" name="foto" accept="image/*" onchange="previewImage(event)">
                <div class="preview-box">
                    <img id="preview" src="<?= BASE_URL ?>uploads/pengajar/<?= htmlspecialchars($data['foto'] ?: 'default.png') ?>" alt="Preview">
                </div>
            </div>
        </div>

        <div class="button-group">
            <a href="pengajar_view.php?nip=<?= htmlspecialchars($nip) ?>" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Batal</a>
            <button type="submit" name="update" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Perbarui</button>
        </div>
    </form>
</div>

<script>
function previewImage(event) {
    const img = document.getElementById('preview');
    img.src = URL.createObjectURL(event.target.files[0]);
}
</script>
</div><!-- /container -->
</div><!-- /main-content -->
</body>
</html>
