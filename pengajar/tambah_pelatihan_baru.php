<?php
require $_SERVER['DOCUMENT_ROOT'] . '/MitigaPro/include/autoload.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}
require_role('admin');

// Ambil wilayah & dinas untuk dropdown
$q_wilayah = $conn->query("SELECT id, nama_wilayah FROM wilayah ORDER BY nama_wilayah ASC");
$q_dinas   = $conn->query("SELECT d.id, d.nama_dinas, d.wilayah_id, w.nama_wilayah FROM dinas d INNER JOIN wilayah w ON d.wilayah_id = w.id ORDER BY w.nama_wilayah, d.nama_dinas");

// Build dinas data untuk JS filter
$dinas_list = [];
while ($d = $q_dinas->fetch_assoc()) {
    $dinas_list[] = $d;
}

if (isset($_POST['simpan'])) {
    if (!csrf_verify()) { header('Location: ' . $_SERVER['REQUEST_URI']); exit; }

    $dinas_id  = intval($_POST['dinas_id'] ?? 0);
    $jenis     = trim($_POST['jenis'] ?? '');
    $kebutuhan = trim($_POST['kebutuhan'] ?? '');
    $tahun     = intval($_POST['tahun'] ?? date('Y'));

    if ($dinas_id > 0 && $jenis !== '') {
        $stmt = $conn->prepare("INSERT INTO identifikasi_pelatihan (dinas_id, jenis_pelatihan, kebutuhan, tahun) VALUES (?, ?, ?, ?)");
        $stmt->bind_param('issi', $dinas_id, $jenis, $kebutuhan, $tahun);
        $stmt->execute();
        $stmt->close();

        header('Location: daftar_pelatihan.php?success=added');
        exit;
    }
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
body{font-family:'Poppins',sans-serif;background:var(--bg);color:var(--navy);min-height:100vh}

.form-card{background:var(--white);border-radius:var(--radius);border:1px solid var(--border);width:100%;max-width:620px;overflow:hidden;margin:0 auto}
.form-header{background:linear-gradient(135deg,var(--navy),var(--blue));padding:24px 28px;color:#fff}
.form-header h2{font-size:18px;font-weight:700;display:flex;align-items:center;gap:10px}
.form-header p{font-size:12px;opacity:.7;margin-top:4px}
.form-body{padding:24px 28px}

.fg{margin-bottom:16px}
.fg label{display:block;font-size:12px;font-weight:600;margin-bottom:5px;color:var(--navy)}
.fg label i{margin-right:4px;color:var(--accent);font-size:11px}
.fg input,.fg textarea,.fg select{width:100%;padding:10px 14px;border:1px solid var(--border);border-radius:8px;font-size:13px;font-family:'Poppins',sans-serif;transition:border-color .2s}
.fg input:focus,.fg textarea:focus,.fg select:focus{outline:none;border-color:var(--accent)}

.row-2{display:grid;grid-template-columns:1fr 1fr;gap:14px}
@media(max-width:500px){.row-2{grid-template-columns:1fr}}

.form-actions{display:flex;gap:10px;margin-top:20px}
.btn{padding:10px 24px;border-radius:8px;font-size:13px;font-weight:600;font-family:'Poppins',sans-serif;cursor:pointer;text-decoration:none;border:none;display:inline-flex;align-items:center;gap:6px;transition:opacity .2s}
.btn:hover{opacity:.85}
.btn-primary{background:var(--accent);color:#fff}
.btn-secondary{background:#f1f5f9;color:var(--navy);border:1px solid var(--border)}
.hint{font-size:11px;color:var(--muted);margin-top:4px}
</style>
</head>
<body>

<?php require INCLUDE_PATH . 'sidebar_pengajar.php'; ?>

<div id="mainContent" class="main-content" style="padding:30px 20px">
<?= breadcrumb([['label' => 'Daftar Pelatihan', 'url' => 'daftar_pelatihan.php'], ['label' => 'Tambah Pelatihan']]) ?>

<div class="form-card">
  <div class="form-header">
    <h2><i class="fas fa-graduation-cap"></i> Tambah Pelatihan</h2>
    <p>Tambahkan data identifikasi kebutuhan pelatihan baru</p>
  </div>
  <div class="form-body">
    <form method="POST">
      <?= csrf_field() ?>

      <div class="row-2">
        <div class="fg">
          <label><i class="fas fa-map"></i> Wilayah</label>
          <select id="selWilayah" required>
            <option value="" disabled selected>-- Pilih Wilayah --</option>
            <?php foreach ($q_wilayah->fetch_all(MYSQLI_ASSOC) as $w): ?>
              <option value="<?= $w['id'] ?>"><?= htmlspecialchars($w['nama_wilayah']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="fg">
          <label><i class="fas fa-building"></i> Dinas</label>
          <select name="dinas_id" id="selDinas" required>
            <option value="" disabled selected>-- Pilih Dinas --</option>
          </select>
          <div class="hint">Pilih wilayah terlebih dahulu</div>
        </div>
      </div>

      <div class="fg">
        <label><i class="fas fa-book"></i> Jenis Pelatihan</label>
        <input type="text" name="jenis" required placeholder="Masukkan jenis pelatihan" maxlength="200">
      </div>

      <div class="fg">
        <label><i class="fas fa-clipboard-list"></i> Kebutuhan</label>
        <textarea name="kebutuhan" rows="4" placeholder="Deskripsi kebutuhan pelatihan (opsional)"></textarea>
      </div>

      <div class="fg" style="max-width:200px">
        <label><i class="fas fa-calendar"></i> Tahun</label>
        <input type="number" name="tahun" value="<?= date('Y') ?>" min="2020" max="2035" required>
      </div>

      <div class="form-actions">
        <button type="submit" name="simpan" class="btn btn-primary"><i class="fas fa-save"></i> Simpan</button>
        <a href="daftar_pelatihan.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Kembali</a>
      </div>
    </form>
  </div>
</div>
</div>

<script>
(function(){
    var allDinas = <?= json_encode($dinas_list, JSON_HEX_TAG | JSON_HEX_AMP) ?>;
    var selW = document.getElementById('selWilayah');
    var selD = document.getElementById('selDinas');

    selW.addEventListener('change', function(){
        var wid = parseInt(this.value);
        selD.innerHTML = '<option value="" disabled selected>-- Pilih Dinas --</option>';
        allDinas.forEach(function(d){
            if (parseInt(d.wilayah_id) === wid) {
                var opt = document.createElement('option');
                opt.value = d.id;
                opt.textContent = d.nama_dinas;
                selD.appendChild(opt);
            }
        });
    });
})();
</script>

</body>
</html>
