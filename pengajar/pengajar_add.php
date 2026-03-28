<?php
require $_SERVER['DOCUMENT_ROOT'] . '/MitigaPro/include/autoload.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "login.php");
    exit;
}
require_role('admin');

require INCLUDE_PATH . 'sidebar_pengajar.php';

$error_nip = "";
$errors    = [];

if (isset($_POST['simpan'])) {
    $nip        = trim($_POST['nip'] ?? '');
    $nama       = trim($_POST['nama_pengajar'] ?? '');
    $jk         = trim($_POST['jenis_kelamin'] ?? '');
    $agama      = trim($_POST['agama'] ?? '');
    $pendidikan = trim($_POST['pendidikan_terakhir'] ?? '');
    $golongan   = trim($_POST['golongan'] ?? '');
    $tempat     = trim($_POST['tempat_lahir'] ?? '');
    $tanggal    = trim($_POST['tanggal_lahir'] ?? '');
    $nohp       = trim($_POST['no_hp'] ?? '');
    $email      = trim($_POST['email_pengajar'] ?? '');
    $jabatan    = trim($_POST['jabatan'] ?? '');
    $unit       = trim($_POST['unit_kerja'] ?? '');
    $instansi   = trim($_POST['instansi'] ?? '');
    $alamat     = trim($_POST['alamat_kantor'] ?? '');
    $npwp       = trim($_POST['npwp'] ?? '') ?: null;
    $golongan   = $golongan ?: null;
    $status     = $_POST['status'] ?? 'aktif';

    // ── Validasi wajib ──────────────────────────────────────
    if ($nip === '')        $errors[] = "NIP wajib diisi.";
    if ($nama === '')       $errors[] = "Nama Lengkap wajib diisi.";
    if ($jk === '')         $errors[] = "Jenis Kelamin wajib dipilih.";
    if ($agama === '')      $errors[] = "Agama wajib dipilih.";
    if ($pendidikan === '') $errors[] = "Pendidikan Terakhir wajib dipilih.";
    if ($tempat === '')     $errors[] = "Tempat Lahir wajib diisi.";
    if ($tanggal === '')    $errors[] = "Tanggal Lahir wajib diisi.";
    if ($nohp === '')       $errors[] = "No HP wajib diisi.";
    if ($email === '')      $errors[] = "Email wajib diisi.";
    if ($jabatan === '')    $errors[] = "Jabatan wajib diisi.";
    if ($unit === '')       $errors[] = "Unit Kerja wajib diisi.";
    if ($instansi === '')   $errors[] = "Instansi wajib diisi.";
    if ($alamat === '')     $errors[] = "Alamat Kantor wajib diisi.";

    if (empty($errors)) {
        // Cek NIP duplikat
        $cek = $conn->prepare("SELECT COUNT(*) FROM pengajar WHERE nip = ?");
        $cek->bind_param("s", $nip);
        $cek->execute();
        $cek->bind_result($jumlah);
        $cek->fetch();
        $cek->close();

        if ($jumlah > 0) {
            $error_nip = "NIP sudah terdaftar. Gunakan NIP lain.";
        } else {
            // Upload foto
            $foto = null;
            if (!empty($_FILES['foto']['name'])) {
                $allowed = ['jpg','jpeg','png','webp'];
                $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
                if (in_array($ext, $allowed)) {
                    $targetDir = ROOT_PATH . "uploads/pengajar/";
                    if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
                    $fileName = time() . "_" . preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($_FILES["foto"]["name"]));
                    if (move_uploaded_file($_FILES["foto"]["tmp_name"], $targetDir . $fileName)) {
                        $foto = $fileName;
                    }
                } else {
                    $errors[] = "Format foto tidak didukung (JPG/PNG/WebP).";
                }
            }

            if (empty($errors)) {
                $stmt = $conn->prepare("INSERT INTO pengajar
                    (nip, nama_pengajar, jenis_kelamin, agama, pendidikan_terakhir, golongan,
                     tempat_lahir, tanggal_lahir, no_hp, email_pengajar, jabatan, unit_kerja,
                     instansi, alamat_kantor, npwp, foto, status)
                    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");

                if (!$stmt) {
                    $errors[] = "Prepare gagal: " . $conn->error;
                } else {
                    $stmt->bind_param("sssssssssssssssss",
                        $nip, $nama, $jk, $agama, $pendidikan, $golongan,
                        $tempat, $tanggal, $nohp, $email, $jabatan, $unit,
                        $instansi, $alamat, $npwp, $foto, $status);

                    if ($stmt->execute()) {
                        $stmt->close();
                        header("Location: pengajar.php?success=1");
                        exit;
                    } else {
                        $errors[] = "Gagal menyimpan: " . $stmt->error;
                        $stmt->close();
                    }
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Tambah Pengajar | MitigaPro</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="<?= BASE_URL ?>1_css/sidebar_pengajar.css">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

:root {
  --navy:   #0d1f4e;
  --blue:   #1e3c72;
  --mid:    #2a5298;
  --accent: #4f8ef7;
  --green:  #22c55e;
  --red:    #ef4444;
  --white:  #ffffff;
  --bg:     #f0f4ff;
  --card:   #ffffff;
  --muted:  #6b7fa3;
  --border: #dce6f5;
  --radius: 16px;
}

body {
  font-family: 'Poppins', sans-serif;
  background: linear-gradient(160deg, #e8efff 0%, #f4f8ff 60%, #eaf3ff 100%);
  min-height: 100vh;
  color: var(--navy);
  padding: 0 0 60px;
  animation: pageIn 0.5s ease both;
}
@keyframes pageIn {
  from { opacity: 0; transform: translateY(14px); }
  to   { opacity: 1; transform: translateY(0); }
}
body.leaving {
  animation: pageOut 0.3s ease both;
  pointer-events: none;
}
@keyframes pageOut {
  to { opacity: 0; transform: translateY(-10px); }
}

/* â•â•â• TOPBAR â•â•â• */
.page-topbar {
  background: linear-gradient(90deg, var(--navy), var(--blue));
  padding: 16px 40px;
  display: flex; align-items: center; justify-content: space-between;
  position: sticky; top: 0; z-index: 100;
  box-shadow: 0 4px 20px rgba(13,31,78,0.25);
}
.page-topbar .brand {
  display: flex; align-items: center; gap: 12px;
  color: #fff; font-weight: 700; font-size: 16px;
  text-decoration: none;
}
.topbar-actions { display: flex; gap: 10px; }
.tb-btn {
  display: inline-flex; align-items: center; gap: 7px;
  padding: 8px 18px; border-radius: 10px;
  font-size: 13px; font-weight: 600; font-family: 'Poppins', sans-serif;
  border: none; cursor: pointer; text-decoration: none;
  transition: transform 0.2s, opacity 0.2s;
}
.tb-btn:hover { transform: translateY(-1px); opacity: 0.9; }
.tb-back { background: rgba(255,255,255,0.15); color: #fff; }
.tb-exit { background: rgba(239,68,68,0.2); color: #fca5a5; }

/* â•â•â• WRAPPER â•â•â• */
.wrap {
  max-width: 1000px;
  margin: 40px auto;
  padding: 0 20px;
}

/* â•â•â• PAGE HEADER â•â•â• */
.page-header {
  display: flex; align-items: center; gap: 18px;
  background: var(--white);
  border-radius: var(--radius); padding: 24px 30px;
  box-shadow: 0 4px 20px rgba(30,60,114,0.09);
  margin-bottom: 28px;
  border-left: 5px solid var(--accent);
  animation: fadeUp 0.5s ease 0.05s both;
}
@keyframes fadeUp {
  from { opacity: 0; transform: translateY(16px); }
  to   { opacity: 1; transform: translateY(0); }
}
.page-header-icon {
  width: 56px; height: 56px; border-radius: 14px;
  background: linear-gradient(135deg, var(--blue), var(--accent));
  display: flex; align-items: center; justify-content: center;
  font-size: 24px; color: #fff; flex-shrink: 0;
}
.page-header h1 { font-size: 20px; font-weight: 700; color: var(--navy); }
.page-header p  { font-size: 13px; color: var(--muted); margin-top: 3px; }

/* â•â•â• CARD â•â•â• */
.card {
  background: var(--white);
  border-radius: var(--radius);
  box-shadow: 0 4px 20px rgba(30,60,114,0.08);
  margin-bottom: 24px;
  overflow: hidden;
  animation: fadeUp 0.5s ease both;
}
.card:nth-child(2) { animation-delay: 0.08s; }
.card:nth-child(3) { animation-delay: 0.13s; }
.card:nth-child(4) { animation-delay: 0.18s; }
.card:nth-child(5) { animation-delay: 0.23s; }

.card-header {
  display: flex; align-items: center; gap: 12px;
  padding: 18px 28px;
  border-bottom: 1.5px solid var(--border);
  background: linear-gradient(90deg, #f5f8ff, #fafbff);
}
.card-header-icon {
  width: 36px; height: 36px; border-radius: 10px;
  background: linear-gradient(135deg, var(--blue), var(--accent));
  display: flex; align-items: center; justify-content: center;
  font-size: 16px; color: #fff;
}
.card-header h2 { font-size: 15px; font-weight: 700; color: var(--navy); }
.card-body { padding: 28px; }

/* â•â•â• FORM GRID â•â•â• */
.form-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 20px 28px;
}
@media (max-width: 640px) { .form-grid { grid-template-columns: 1fr; } }
.col-full { grid-column: 1 / -1; }

/* â•â•â• FORM GROUP â•â•â• */
.fg { display: flex; flex-direction: column; gap: 6px; }

.fg label {
  font-size: 12.5px; font-weight: 700; color: #445;
  display: flex; align-items: center; gap: 6px;
}
.fg label i { color: var(--accent); font-size: 12px; }
.req { color: var(--red); font-size: 11px; }

.fg input[type=text],
.fg input[type=email],
.fg input[type=date],
.fg input[type=number],
.fg select,
.fg textarea {
  padding: 11px 14px;
  border: 1.8px solid var(--border);
  border-radius: 12px;
  font-size: 14px;
  font-family: 'Poppins', sans-serif;
  color: var(--navy);
  background: #f8fafd;
  outline: none;
  transition: border-color 0.25s, box-shadow 0.25s, background 0.25s;
}
.fg input:focus,
.fg select:focus,
.fg textarea:focus {
  border-color: var(--accent);
  background: #fff;
  box-shadow: 0 0 0 3px rgba(79,142,247,0.14);
}
.fg textarea { resize: vertical; min-height: 90px; }
.fg select { cursor: pointer; }

/* NIP feedback */
.nip-msg {
  font-size: 12px; font-weight: 600; padding: 4px 0; min-height: 20px;
  display: flex; align-items: center; gap: 5px;
}
.nip-ok   { color: var(--green); }
.nip-err  { color: var(--red); }
.nip-load { color: var(--muted); }

/* â•â•â• FOTO UPLOAD â•â•â• */
.foto-wrap {
  display: flex; align-items: flex-start; gap: 24px; flex-wrap: wrap;
}
.foto-preview-box {
  width: 120px; height: 120px; border-radius: 16px;
  border: 2.5px dashed var(--border);
  display: flex; align-items: center; justify-content: center;
  overflow: hidden; flex-shrink: 0; background: #f5f8ff;
  transition: border-color 0.3s;
}
.foto-preview-box img {
  width: 100%; height: 100%; object-fit: cover; border-radius: 14px;
}
.foto-placeholder { text-align: center; color: var(--muted); font-size: 12px; }
.foto-placeholder i { font-size: 28px; display: block; margin-bottom: 6px; color: var(--accent); opacity: 0.6; }
.foto-right { flex: 1; display: flex; flex-direction: column; gap: 10px; }
.foto-right label { font-size: 12.5px; font-weight: 700; color: #445; display: flex; align-items: center; gap: 6px; }
.foto-right label i { color: var(--accent); }

.custom-file-btn {
  display: inline-flex; align-items: center; gap: 8px;
  padding: 10px 20px; border-radius: 10px;
  background: linear-gradient(135deg, var(--blue), var(--accent));
  color: #fff; font-size: 13px; font-weight: 600;
  cursor: pointer; width: fit-content;
  transition: transform 0.2s, box-shadow 0.2s;
  box-shadow: 0 4px 12px rgba(42,82,152,0.3);
}
.custom-file-btn:hover { transform: translateY(-2px); box-shadow: 0 6px 18px rgba(42,82,152,0.4); }
#fotoInput { display: none; }
.file-name-label { font-size: 12px; color: var(--muted); font-style: italic; }
.foto-hint { font-size: 11.5px; color: var(--muted); line-height: 1.6; }

/* â•â•â• ALERT â•â•â• */
.alert {
  padding: 14px 18px; border-radius: 12px;
  font-size: 13.5px; font-weight: 500;
  display: flex; align-items: center; gap: 10px;
  margin-bottom: 20px;
  animation: fadeUp 0.4s ease both;
}
.alert-err { background: #fff5f5; border: 1.5px solid #fca5a5; color: #991b1b; }

/* â•â•â• ACTION BAR â•â•â• */
.action-bar {
  background: var(--white);
  border-radius: var(--radius);
  box-shadow: 0 4px 20px rgba(30,60,114,0.09);
  padding: 24px 28px;
  display: flex; align-items: center; justify-content: space-between;
  flex-wrap: wrap; gap: 14px;
  animation: fadeUp 0.5s ease 0.28s both;
  border-top: 3px solid var(--accent);
}
.action-left { display: flex; gap: 12px; flex-wrap: wrap; }
.action-right { display: flex; gap: 12px; flex-wrap: wrap; }

.btn {
  display: inline-flex; align-items: center; gap: 8px;
  padding: 12px 26px; border-radius: 12px;
  font-size: 14px; font-weight: 700; font-family: 'Poppins', sans-serif;
  border: none; cursor: pointer; text-decoration: none;
  transition: transform 0.2s, box-shadow 0.2s, background 0.2s;
}
.btn:hover { transform: translateY(-2px); }

.btn-save {
  background: linear-gradient(135deg, var(--blue), var(--accent));
  color: #fff;
  box-shadow: 0 6px 20px rgba(42,82,152,0.35);
}
.btn-save:hover { box-shadow: 0 8px 28px rgba(42,82,152,0.5); }

.btn-reset {
  background: #fef3cd; color: #92600a;
  border: 1.5px solid #fde68a;
}
.btn-reset:hover { background: #fde68a; }

.btn-back {
  background: #f0f4ff; color: var(--blue);
  border: 1.5px solid var(--border);
}
.btn-back:hover { background: #dce8ff; }

.btn-cancel {
  background: #fff0f0; color: #dc2626;
  border: 1.5px solid #fecaca;
}
.btn-cancel:hover { background: #ffe4e4; }

/* â•â•â• STEP INDICATOR â•â•â• */
.steps {
  display: flex; gap: 0; margin-bottom: 28px;
  animation: fadeUp 0.5s ease 0.02s both;
}
.step {
  flex: 1; display: flex; align-items: center;
}
.step-num {
  width: 32px; height: 32px; border-radius: 50%;
  font-size: 13px; font-weight: 700;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0; transition: all 0.3s;
  background: var(--border); color: var(--muted);
}
.step.active .step-num { background: linear-gradient(135deg, var(--blue), var(--accent)); color: #fff; box-shadow: 0 4px 12px rgba(42,82,152,0.35); }
.step-label { margin-left: 8px; font-size: 12px; font-weight: 600; color: var(--muted); }
.step.active .step-label { color: var(--navy); }
.step-line { flex: 1; height: 2px; background: var(--border); margin: 0 6px; }
</style>
</head>
<body>

<div id="mainContent" class="main-content">

<div class="wrap">
  <?= breadcrumb([['label' => 'Data Pengajar', 'url' => 'pengajar.php'], ['label' => 'Tambah Pengajar']]) ?>

  <!-- Page Header -->
  <div class="page-header">
    <div class="page-header-icon"><i class="fas fa-user-plus"></i></div>
    <div>
      <h1>Tambah Biodata Pengajar</h1>
      <p>Isi semua data dengan lengkap dan benar. Kolom bertanda <span style="color:var(--red)">*</span> wajib diisi.</p>
    </div>
  </div>

  <!-- Step Indicator -->
  <div class="steps">
    <div class="step active">
      <div class="step-num">1</div>
      <div class="step-label">Identitas</div>
    </div>
    <div class="step-line"></div>
    <div class="step active">
      <div class="step-num">2</div>
      <div class="step-label">Kontak & Jabatan</div>
    </div>
    <div class="step-line"></div>
    <div class="step active">
      <div class="step-num">3</div>
      <div class="step-label">Lainnya & Foto</div>
    </div>
  </div>

  <?php if (!empty($errors)): ?>
    <div class="alert alert-err">
      <i class="fas fa-exclamation-circle"></i>
      <div>
        <?php if (count($errors) === 1): ?>
          <?= htmlspecialchars($errors[0]) ?>
        <?php else: ?>
          <strong>Harap perbaiki kesalahan berikut:</strong>
          <ul style="margin:6px 0 0 16px;padding:0">
            <?php foreach ($errors as $e): ?>
              <li><?= htmlspecialchars($e) ?></li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>
    </div>
  <?php endif; ?>

  <form method="POST" action="" enctype="multipart/form-data" id="formPengajar" novalidate>
    <?= csrf_field() ?>
    <input type="hidden" name="simpan" value="1">

    <!-- â•â• CARD 1: Identitas Diri â•â• -->
    <div class="card">
      <div class="card-header">
        <div class="card-header-icon"><i class="fas fa-id-card"></i></div>
        <h2>Identitas Diri</h2>
      </div>
      <div class="card-body">
        <div class="form-grid">

          <div class="fg">
            <label><i class="fas fa-hashtag"></i> NIP <span class="req">*</span></label>
            <input type="text" name="nip" id="nipInput"
              maxlength="18"
              placeholder="Contoh: 197501012005011001"
              oninput="this.value=this.value.replace(/[^0-9]/g,'')"
              value="<?= htmlspecialchars($_POST['nip'] ?? '') ?>"
              autocomplete="off" required>
            <div class="nip-msg" id="nipMsg">
              <?php if ($error_nip): ?>
                <i class="fas fa-times-circle"></i> <span class="nip-err"><?= htmlspecialchars($error_nip) ?></span>
              <?php endif; ?>
            </div>
          </div>

          <div class="fg">
            <label><i class="fas fa-user"></i> Nama Lengkap <span class="req">*</span></label>
            <input type="text" name="nama_pengajar"
              placeholder="Nama sesuai dokumen resmi"
              value="<?= htmlspecialchars($_POST['nama_pengajar'] ?? '') ?>"
              required>
          </div>

          <div class="fg">
            <label><i class="fas fa-venus-mars"></i> Jenis Kelamin <span class="req">*</span></label>
            <select name="jenis_kelamin" required>
              <option value="">-- Pilih --</option>
              <option value="Laki-laki"  <?= (($_POST['jenis_kelamin'] ?? '') === 'Laki-laki')  ? 'selected' : '' ?>>Laki-laki</option>
              <option value="Perempuan" <?= (($_POST['jenis_kelamin'] ?? '') === 'Perempuan') ? 'selected' : '' ?>>Perempuan</option>
            </select>
          </div>

          <div class="fg">
            <label><i class="fas fa-moon"></i> Agama <span class="req">*</span></label>
            <select name="agama" required>
              <option value="">-- Pilih --</option>
              <?php foreach (['ISLAM','KRISTEN KATOLIK','KRISTEN PROTESTAN','HINDU','BUDDHA','KONGHUCU'] as $a): ?>
                <option value="<?= $a ?>" <?= (($_POST['agama'] ?? '') === $a) ? 'selected' : '' ?>><?= $a ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="fg">
            <label><i class="fas fa-graduation-cap"></i> Pendidikan Terakhir <span class="req">*</span></label>
            <select name="pendidikan_terakhir" required>
              <option value="">-- Pilih --</option>
              <?php foreach (['SMA/SMK','Diploma D1','Diploma D2','Diploma D3','Sarjana Terapan D4','Sarjana S1','Magister S2','Doctor S3'] as $p): ?>
                <option value="<?= $p ?>" <?= (($_POST['pendidikan_terakhir'] ?? '') === $p) ? 'selected' : '' ?>><?= $p ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="fg">
            <label><i class="fas fa-layer-group"></i> Golongan</label>
            <select name="golongan">
              <option value="">-- Pilih --</option>
              <?php foreach (['I/A','I/B','I/C','I/D','II/A','II/B','II/C','II/D','III','III/A','III/B','III/C','III/D','IV','IV/A','IV/B','IV/C','IV/D','IV/E'] as $g): ?>
                <option value="<?= $g ?>" <?= (($_POST['golongan'] ?? '') === $g) ? 'selected' : '' ?>><?= $g ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="fg">
            <label><i class="fas fa-map-pin"></i> Tempat Lahir</label>
            <input type="text" name="tempat_lahir"
              placeholder="Kota/Kabupaten"
              value="<?= htmlspecialchars($_POST['tempat_lahir'] ?? '') ?>">
          </div>

          <div class="fg">
            <label><i class="fas fa-calendar-alt"></i> Tanggal Lahir</label>
            <input type="date" name="tanggal_lahir"
              value="<?= htmlspecialchars($_POST['tanggal_lahir'] ?? '') ?>">
          </div>

        </div>
      </div>
    </div>

    <!-- â•â• CARD 2: Kontak & Jabatan â•â• -->
    <div class="card">
      <div class="card-header">
        <div class="card-header-icon"><i class="fas fa-briefcase"></i></div>
        <h2>Kontak &amp; Jabatan</h2>
      </div>
      <div class="card-body">
        <div class="form-grid">

          <div class="fg">
            <label><i class="fas fa-phone"></i> No HP</label>
            <input type="text" name="no_hp"
              placeholder="Contoh: 081234567890"
              oninput="this.value=this.value.replace(/[^0-9]/g,'')"
              maxlength="15"
              value="<?= htmlspecialchars($_POST['no_hp'] ?? '') ?>">
          </div>

          <div class="fg">
            <label><i class="fas fa-envelope"></i> Email</label>
            <input type="email" name="email_pengajar"
              placeholder="contoh@email.com"
              value="<?= htmlspecialchars($_POST['email_pengajar'] ?? '') ?>">
          </div>

          <div class="fg">
            <label><i class="fas fa-user-tie"></i> Jabatan</label>
            <input type="text" name="jabatan"
              placeholder="Jabatan saat ini"
              value="<?= htmlspecialchars($_POST['jabatan'] ?? '') ?>">
          </div>

          <div class="fg">
            <label><i class="fas fa-building"></i> Unit Kerja</label>
            <input type="text" name="unit_kerja"
              placeholder="Nama unit/divisi"
              value="<?= htmlspecialchars($_POST['unit_kerja'] ?? '') ?>">
          </div>

          <div class="fg">
            <label><i class="fas fa-landmark"></i> Instansi</label>
            <input type="text" name="instansi"
              placeholder="Nama instansi/lembaga"
              value="<?= htmlspecialchars($_POST['instansi'] ?? '') ?>">
          </div>

          <div class="fg">
            <label><i class="fas fa-toggle-on"></i> Status</label>
            <select name="status">
              <option value="aktif"    <?= (($_POST['status'] ?? 'aktif') === 'aktif')    ? 'selected' : '' ?>>Aktif</option>
              <option value="nonaktif" <?= (($_POST['status'] ?? '') === 'nonaktif') ? 'selected' : '' ?>>Nonaktif</option>
            </select>
          </div>

          <div class="fg col-full">
            <label><i class="fas fa-map-marker-alt"></i> Alamat Kantor</label>
            <textarea name="alamat_kantor" placeholder="Jl. ... No. ... Kota ..."><?= htmlspecialchars($_POST['alamat_kantor'] ?? '') ?></textarea>
          </div>

        </div>
      </div>
    </div>

    <!-- â•â• CARD 3: Data Tambahan & Foto â•â• -->
    <div class="card">
      <div class="card-header">
        <div class="card-header-icon"><i class="fas fa-camera"></i></div>
        <h2>Data Tambahan &amp; Foto</h2>
      </div>
      <div class="card-body">
        <div class="form-grid">

          <div class="fg">
            <label><i class="fas fa-file-invoice"></i> Nomor NPWP</label>
            <input type="text" name="npwp"
              placeholder="Contoh: 123456789012345"
              oninput="this.value=this.value.replace(/[^0-9]/g,'')"
              maxlength="16"
              value="<?= htmlspecialchars($_POST['npwp'] ?? '') ?>">
          </div>

          <div class="fg col-full" style="margin-top: 8px;">
            <label><i class="fas fa-image"></i> Foto Profil</label>
            <div class="foto-wrap" style="margin-top: 4px;">
              <div class="foto-preview-box" id="previewBox">
                <div class="foto-placeholder" id="fotoPlaceholder">
                  <i class="fas fa-user-circle"></i>
                  <span>Pratinjau</span>
                </div>
                <img id="fotoPreview" src="#" alt="Preview" style="display:none; width:100%; height:100%; object-fit:cover; border-radius:14px;">
              </div>
              <div class="foto-right">
                <label><i class="fas fa-upload"></i> Pilih Foto</label>
                <label for="fotoInput" class="custom-file-btn">
                  <i class="fas fa-folder-open"></i> Pilih File
                </label>
                <input type="file" name="foto" id="fotoInput" accept="image/jpeg,image/png,image/webp">
                <div class="file-name-label" id="fileNameLabel">Belum ada file dipilih</div>
                <div class="foto-hint">Format: JPG, PNG, WebP &middot; Ukuran maks 2MB &middot; Rasio 1:1 direkomendasikan</div>
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>

    <!-- â•â• ACTION BAR â•â• -->
    <div class="action-bar">
      <div class="action-left">
        <button type="button" class="btn btn-back" onclick="leavePage(null, 'pengajar.php')">
          <i class="fas fa-arrow-left"></i> Kembali
        </button>
        <button type="reset" class="btn btn-reset" onclick="resetForm()">
          <i class="fas fa-undo"></i> Reset
        </button>
      </div>
      <div class="action-right">
        <a href="pengajar.php" class="btn btn-cancel" onclick="leavePage(event, 'pengajar.php')">
          <i class="fas fa-times"></i> Batal
        </a>
        <button type="submit" name="simpan" class="btn btn-save" id="btnSimpan">
          <i class="fas fa-save"></i> Simpan Data
        </button>
      </div>
    </div>

  </form>
</div>

<script>
/* â”€â”€ Page Transition â”€â”€ */
function leavePage(e, href) {
  if (e) e.preventDefault();
  document.body.classList.add('leaving');
  setTimeout(() => { window.location.href = href; }, 280);
}

document.getElementById('btnKeluar').addEventListener('click', function(e) {
  e.preventDefault();
  if (confirm('Yakin ingin keluar dari sistem?')) leavePage(null, this.href);
});

/* â”€â”€ Foto Preview â”€â”€ */
document.getElementById('fotoInput').addEventListener('change', function () {
  const file = this.files[0];
  if (!file) return;
  document.getElementById('fileNameLabel').textContent = file.name;

  const preview = document.getElementById('fotoPreview');
  const placeholder = document.getElementById('fotoPlaceholder');
  preview.src = URL.createObjectURL(file);
  preview.style.display = 'block';
  placeholder.style.display = 'none';
  document.getElementById('previewBox').style.borderStyle = 'solid';
  document.getElementById('previewBox').style.borderColor = 'var(--accent)';
});

/* â”€â”€ Reset Form â”€â”€ */
function resetForm() {
  document.getElementById('fotoPreview').style.display = 'none';
  document.getElementById('fotoPlaceholder').style.display = 'flex';
  document.getElementById('fileNameLabel').textContent = 'Belum ada file dipilih';
  document.getElementById('previewBox').style.borderColor = 'var(--border)';
  document.getElementById('nipMsg').innerHTML = '';
}

/* â”€â”€ NIP Real-time Check â”€â”€ */
let nipTimer;
const nipInput = document.getElementById('nipInput');
const nipMsg   = document.getElementById('nipMsg');

nipInput.addEventListener('input', function () {
  clearTimeout(nipTimer);
  const nip = this.value.trim();

  if (nip.length < 5) {
    nipMsg.innerHTML = '';
    return;
  }

  nipMsg.innerHTML = '<span class="nip-load"><i class="fas fa-spinner fa-spin"></i> Memeriksa...</span>';

  nipTimer = setTimeout(() => {
    fetch('cek_nip.php?nip=' + encodeURIComponent(nip))
      .then(r => r.json())
      .then(data => {
        if (data.exists) {
          nipMsg.innerHTML = '<i class="fas fa-times-circle" style="color:var(--red)"></i> <span class="nip-err">NIP sudah terdaftar</span>';
        } else {
          nipMsg.innerHTML = '<i class="fas fa-check-circle" style="color:var(--green)"></i> <span class="nip-ok">NIP tersedia</span>';
        }
      })
      .catch(() => { nipMsg.innerHTML = ''; });
  }, 600);
});

/* ── Submit loading ── */
document.getElementById('formPengajar').addEventListener('submit', function () {
  const btn = document.getElementById('btnSimpan');
  // Delay disable so the button value is already included in POST
  setTimeout(() => {
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan...';
    btn.disabled = true;
  }, 10);
});
</script>
</div><!-- /main-content -->
</body>
</html>
