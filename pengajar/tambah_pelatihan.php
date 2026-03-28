<?php
require $_SERVER['DOCUMENT_ROOT'] . '/MitigaPro/include/autoload.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}
require_role('admin');

require INCLUDE_PATH . 'sidebar_pengajar.php';

if (!isset($_GET['id'])) { header('Location: ' . BASE_URL . 'pengajar/dashboard.php'); exit; }

$dinas_id = intval($_GET['id']);

$stmt = $conn->prepare("SELECT * FROM dinas WHERE id = ?");
$stmt->bind_param('i', $dinas_id);
$stmt->execute();
$dinas = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$dinas) { header('Location: ' . BASE_URL . 'pengajar/dashboard.php'); exit; }

if (isset($_POST['simpan'])) {
    if (!csrf_verify()) { header('Location: ' . $_SERVER['REQUEST_URI']); exit; }
    $jenis = trim($_POST['jenis'] ?? '');
    $kebutuhan = trim($_POST['kebutuhan'] ?? '');
    $tahun = intval($_POST['tahun']);

    $stmt = $conn->prepare("INSERT INTO identifikasi_pelatihan (dinas_id, jenis_pelatihan, kebutuhan, tahun) VALUES (?, ?, ?, ?)");
    $stmt->bind_param('issi', $dinas_id, $jenis, $kebutuhan, $tahun);
    $stmt->execute();
    $stmt->close();

    header("Location: detail_dinas.php?id=$dinas_id");
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Tambah Pelatihan | MitigaPro</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="<?= BASE_URL ?>1_css/sidebar_pengajar.css">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{--navy:#1a2744;--blue:#2c5282;--accent:#3b82f6;--green:#22c55e;--bg:#f5f7fb;--white:#fff;--border:#e2e8f0;--muted:#64748b;--radius:12px}
body{font-family:'Poppins',sans-serif;background:var(--bg);color:var(--navy);min-height:100vh;padding:40px 20px}

.form-card{background:var(--white);border-radius:var(--radius);border:1px solid var(--border);width:100%;max-width:560px;overflow:hidden;margin:0 auto}
.form-header{background:linear-gradient(135deg,var(--navy),var(--blue));padding:24px 28px;color:#fff}
.form-header h2{font-size:18px;font-weight:700;display:flex;align-items:center;gap:10px}
.form-header p{font-size:12px;opacity:.7;margin-top:4px}
.form-body{padding:24px 28px}

.fg{margin-bottom:16px}
.fg label{display:block;font-size:12px;font-weight:600;margin-bottom:5px;color:var(--navy)}
.fg label i{margin-right:4px;color:var(--accent);font-size:11px}
.fg input,.fg textarea{width:100%;padding:10px 14px;border:1px solid var(--border);border-radius:8px;font-size:13px;font-family:'Poppins',sans-serif;transition:border-color .2s}
.fg input:focus,.fg textarea:focus{outline:none;border-color:var(--accent)}

.form-actions{display:flex;gap:10px;margin-top:20px}
.btn{padding:10px 24px;border-radius:8px;font-size:13px;font-weight:600;font-family:'Poppins',sans-serif;cursor:pointer;text-decoration:none;border:none;display:inline-flex;align-items:center;gap:6px;transition:opacity .2s}
.btn:hover{opacity:.85}
.btn-primary{background:var(--accent);color:#fff}
.btn-secondary{background:#f1f5f9;color:var(--navy);border:1px solid var(--border)}

.toast{position:fixed;top:20px;right:20px;padding:12px 20px;background:var(--green);color:#fff;border-radius:8px;font-size:13px;font-weight:600;z-index:9999;animation:slideIn .3s ease,fadeOut .3s ease 2.5s forwards}
@keyframes slideIn{from{transform:translateX(100px);opacity:0}to{transform:translateX(0);opacity:1}}
@keyframes fadeOut{to{opacity:0;transform:translateY(-10px)}}
</style>
</head>
<body>

<div id="mainContent" class="main-content" style="padding:30px 20px">
<?= breadcrumb([['label' => 'Data Dinas', 'url' => 'dinas.php'], ['label' => 'Tambah Pelatihan']]) ?>
<?php if (isset($_GET['success'])): ?>
<div class="toast"><i class="fas fa-check-circle"></i> Pelatihan berhasil ditambahkan!</div>
<?php endif; ?>

<div class="form-card">
  <div class="form-header">
    <h2><i class="fas fa-graduation-cap"></i> Tambah Pelatihan</h2>
    <p>Untuk dinas: <?= htmlspecialchars($dinas['nama_dinas']) ?></p>
  </div>
  <div class="form-body">
    <form method="POST">
      <?= csrf_field() ?>

      <div class="fg">
        <label><i class="fas fa-book"></i> Jenis Pelatihan</label>
        <input type="text" name="jenis" required placeholder="Masukkan jenis pelatihan">
      </div>

      <div class="fg">
        <label><i class="fas fa-clipboard-list"></i> Kebutuhan</label>
        <textarea name="kebutuhan" rows="4" placeholder="Deskripsi kebutuhan pelatihan"></textarea>
      </div>

      <div class="fg">
        <label><i class="fas fa-calendar"></i> Tahun Pelatihan</label>
        <input type="number" name="tahun" placeholder="2025" required>
      </div>

      <div class="form-actions">
        <button type="submit" name="simpan" class="btn btn-primary"><i class="fas fa-save"></i> Simpan</button>
        <a href="detail_dinas.php?id=<?= $dinas_id ?>" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Kembali</a>
      </div>
    </form>
  </div>
</div>
</div><!-- /main-content -->
</body>
</html>
