<?php
require $_SERVER['DOCUMENT_ROOT'] . '/MitigaPro/include/autoload.php';

if (!isset($_SESSION['role'])) {
    header("Location: " . BASE_URL . "login.php");
    exit;
}

// Tamu diarahkan ke dashboard khusus (full screen)
if (($_SESSION['role'] ?? '') === 'tamu') {
    header("Location: " . BASE_URL . "pengajar/dashboard_tamu.php");
    exit;
}

/* ── Simpan catatan cepat ── */
$save_msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['catatan_wilayah_id']) && ($_SESSION['role'] ?? '') !== 'tamu') {
    $wid     = intval($_POST['catatan_wilayah_id']);
    $catatan = trim($_POST['catatan'] ?? '');
    $user_id = intval($_SESSION['user_id']);

    if ($catatan !== '') {
        $stmt = $conn->prepare(
            "INSERT INTO catatan_wilayah (wilayah_id, user_id, catatan, created_at)
             VALUES (?, ?, ?, NOW())
             ON DUPLICATE KEY UPDATE catatan = VALUES(catatan), created_at = NOW()"
        );
        if ($stmt) {
            $stmt->bind_param('iis', $wid, $user_id, $catatan);
            $stmt->execute();
            $stmt->close();
            $save_msg = 'ok';
        }
    }
    header("Location: " . $_SERVER['PHP_SELF'] . "?saved=" . ($save_msg === 'ok' ? '1' : '0'));
    exit;
}

require INCLUDE_PATH . 'sidebar_pengajar.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="<?= BASE_URL ?>1_css/sidebar_pengajar.css">
<link rel="stylesheet" href="<?= BASE_URL ?>1_css/footer.css">
<title>Dashboard | Wilayah Kerja Bapekom PU VIII Makassar</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

:root {
  --navy:   #1a2744;
  --blue:   #2c5282;
  --accent: #3b82f6;
  --bg:     #f5f7fb;
  --white:  #ffffff;
  --text:   #334155;
  --muted:  #94a3b8;
  --border: #e2e8f0;
  --radius: 12px;
}

body {
  font-family: 'Poppins', sans-serif;
  background: var(--bg);
  color: var(--text);
  min-height: 100vh;
}

/* ── Layout ── */
.main {
  max-width: 1100px;
  margin: 0 auto;
  padding: 36px 32px 60px;
}
@media (max-width: 768px) { .main { padding: 24px 16px 40px; } }

/* ── Identity Banner ── */
.identity-banner {
  background: linear-gradient(135deg, #1a2744, #2c5282);
  border-radius: 16px;
  padding: 32px 36px;
  margin-bottom: 32px;
  display: flex;
  align-items: center;
  gap: 28px;
  color: #fff;
}
.id-logo {
  width: 52px; height: 52px;
  flex-shrink: 0;
}
.id-logo img {
  width: 100%; height: 100%;
  object-fit: contain;
}
.id-text { flex: 1; }
.id-text .id-subtitle {
  font-size: 11px; font-weight: 600;
  letter-spacing: 1px; text-transform: uppercase;
  color: rgba(255,255,255,0.6);
  margin-bottom: 6px;
  display: flex; align-items: center; gap: 6px;
}
.id-text h2 {
  font-size: 1.25rem; font-weight: 700;
  line-height: 1.4; margin: 0 0 4px;
}
.id-text .id-location {
  font-size: 12.5px; color: rgba(255,255,255,0.5);
  display: flex; align-items: center; gap: 5px;
}
.id-stats {
  display: flex; gap: 10px; flex-shrink: 0;
}
.id-stat-box {
  background: rgba(255,255,255,0.1);
  border-radius: 10px;
  padding: 14px 18px;
  text-align: center;
  min-width: 80px;
}
.id-stat-num { font-size: 1.5rem; font-weight: 800; }
.id-stat-label { font-size: 10px; color: rgba(255,255,255,0.55); margin-top: 2px; }
@media (max-width: 768px) {
  .identity-banner { flex-direction: column; text-align: center; padding: 24px 20px; gap: 16px; }
  .id-text .id-location { justify-content: center; }
  .id-stats { justify-content: center; }
}

/* ── Quick Nav ── */
.quick-nav {
  display: flex; gap: 10px; flex-wrap: wrap;
  margin-bottom: 32px;
}
.qnav-btn {
  display: inline-flex; align-items: center; gap: 6px;
  padding: 9px 18px; border-radius: 8px;
  font-size: 13px; font-weight: 600;
  font-family: 'Poppins', sans-serif;
  cursor: pointer; border: none; text-decoration: none;
  transition: background 0.2s, box-shadow 0.2s;
}
.qnav-primary {
  background: var(--accent); color: #fff;
}
.qnav-primary:hover { background: #2563eb; }
.qnav-outline {
  background: var(--white); color: var(--blue);
  border: 1px solid var(--border);
}
.qnav-outline:hover { background: #f0f4ff; }
.qnav-danger {
  background: var(--white); color: #dc2626;
  border: 1px solid #fecaca;
}
.qnav-danger:hover { background: #fef2f2; }

/* ── Section Title ── */
.section-title {
  font-size: 16px; font-weight: 700; color: var(--navy);
  margin-bottom: 20px;
  display: flex; align-items: center; gap: 8px;
}

/* ── Wilayah Grid ── */
.wilayah-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
  gap: 16px;
  margin-bottom: 40px;
}

/* ── Wilayah Card ── */
.wcard {
  background: var(--white);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 24px 18px 18px;
  display: flex; flex-direction: column; align-items: center; gap: 12px;
  transition: box-shadow 0.2s, transform 0.2s;
  text-decoration: none; color: inherit;
}
.wcard:hover {
  box-shadow: 0 8px 24px rgba(0,0,0,0.08);
  transform: translateY(-3px);
}
.wcard-icon-wrap {
  width: 48px; height: 48px; border-radius: 10px;
  background: #eff6ff;
  display: flex; align-items: center; justify-content: center;
  font-size: 22px; color: var(--accent);
}
.wcard-name {
  font-size: 12.5px; font-weight: 700; color: var(--navy);
  text-align: center; line-height: 1.4;
}
.wcard-desc {
  font-size: 11px; color: var(--muted); text-align: center; line-height: 1.5;
}
.wcard-actions {
  display: flex; gap: 8px; width: 100%; margin-top: auto;
}
.wcard-btn {
  flex: 1; padding: 7px 0;
  border-radius: 8px; font-size: 11.5px; font-weight: 600;
  font-family: 'Poppins', sans-serif;
  border: none; cursor: pointer;
  display: flex; align-items: center; justify-content: center; gap: 4px;
  text-decoration: none;
  transition: background 0.2s;
}
.wcard-btn.primary {
  background: var(--accent); color: #fff;
}
.wcard-btn.primary:hover { background: #2563eb; }
.wcard-btn.secondary {
  background: #f1f5f9; color: var(--blue);
}
.wcard-btn.secondary:hover { background: #e2e8f0; }

/* ── Info Banner ── */
.info-banner {
  background: var(--white);
  border: 1px solid var(--border);
  border-left: 4px solid var(--accent);
  border-radius: var(--radius);
  padding: 20px 24px;
  display: flex; align-items: flex-start; gap: 14px;
}
.info-banner-icon { font-size: 20px; color: var(--accent); flex-shrink: 0; margin-top: 2px; }
.info-banner h4 { font-size: 14px; font-weight: 700; color: var(--navy); margin-bottom: 4px; }
.info-banner p  { font-size: 12.5px; color: var(--muted); line-height: 1.6; }

/* ── Toast ── */
.toast {
  position: fixed; bottom: 24px; right: 24px; z-index: 9999;
  background: #16a34a; color: #fff;
  padding: 12px 20px; border-radius: 10px;
  font-size: 13px; font-weight: 600;
  box-shadow: 0 4px 16px rgba(0,0,0,0.15);
  display: flex; align-items: center; gap: 8px;
  animation: toastIn 0.3s ease both;
}
.toast.hide { animation: toastOut 0.25s ease forwards; }
@keyframes toastIn  { from { opacity:0; transform: translateY(12px); } to { opacity:1; transform: none; } }
@keyframes toastOut { to   { opacity:0; transform: translateY(12px); } }

/* ── Modal ── */
.modal-overlay {
  position: fixed; inset: 0; z-index: 1000;
  background: rgba(0,0,0,0.4);
  backdrop-filter: blur(4px);
  display: flex; align-items: center; justify-content: center;
  padding: 20px;
  opacity: 0; pointer-events: none;
  transition: opacity 0.2s;
}
.modal-overlay.open { opacity: 1; pointer-events: all; }
.modal {
  background: var(--white);
  border-radius: 16px;
  padding: 32px 28px;
  width: 100%; max-width: 440px;
  box-shadow: 0 20px 60px rgba(0,0,0,0.2);
  transform: translateY(16px);
  transition: transform 0.25s ease;
}
.modal-overlay.open .modal { transform: none; }
.modal-header {
  display: flex; align-items: center; justify-content: space-between;
  margin-bottom: 20px;
}
.modal-title {
  font-size: 16px; font-weight: 700; color: var(--navy);
  display: flex; align-items: center; gap: 8px;
}
.modal-close {
  background: #f1f5f9; border: none; width: 32px; height: 32px;
  border-radius: 8px; cursor: pointer; font-size: 16px; color: var(--muted);
  display: flex; align-items: center; justify-content: center;
  transition: background 0.2s;
}
.modal-close:hover { background: #fee2e2; color: #dc2626; }
.modal-body label {
  display: block; font-size: 12px; font-weight: 600;
  color: #64748b; margin-bottom: 5px;
}
.modal-body input[type=text],
.modal-body textarea,
.modal-body select {
  width: 100%; padding: 10px 12px;
  border: 1px solid var(--border); border-radius: 8px;
  font-size: 13px; font-family: 'Poppins', sans-serif; color: var(--text);
  outline: none; transition: border-color 0.2s;
  margin-bottom: 16px; background: #fafbfc;
}
.modal-body input[type=text]:focus,
.modal-body textarea:focus {
  border-color: var(--accent);
  background: #fff;
}
.modal-body textarea { resize: vertical; min-height: 90px; }
.modal-footer { display: flex; gap: 8px; margin-top: 4px; }
.btn-modal {
  flex: 1; padding: 10px;
  border: none; border-radius: 8px;
  font-size: 13px; font-weight: 700; font-family: 'Poppins', sans-serif;
  cursor: pointer;
  display: flex; align-items: center; justify-content: center; gap: 6px;
  transition: background 0.2s;
}
.btn-save { background: var(--accent); color: #fff; }
.btn-save:hover { background: #2563eb; }
.btn-cancel { background: #f1f5f9; color: var(--muted); }
.btn-cancel:hover { background: #e2e8f0; }

/* ── Loading Bar ── */
.loading-bar {
  position: fixed; top: 0; left: 0; z-index: 9999;
  width: 0; height: 3px;
  background: var(--accent);
  transition: width 0.3s ease;
}
.loading-bar.active { width: 70%; transition: width 1.5s ease; }
.loading-bar.done { width: 100%; transition: width 0.2s ease; }
</style>
</head>
<body>

<div id="mainContent" class="main-content">
<!-- Loading Bar -->
<div class="loading-bar" id="loadingBar"></div>

<!-- Content -->
<div class="main">
<?= breadcrumb([['label' => 'Dashboard']]) ?>

  <!-- Identity Banner -->
  <div class="identity-banner">
    <div class="id-logo">
      <img src="<?= BASE_URL ?>logo.png" alt="Logo">
    </div>
    <div class="id-text">
      <div class="id-subtitle"><i class="fas fa-landmark"></i> Kementerian PU</div>
      <h2>Balai Pengembangan Kompetensi PU<br>Wilayah VIII Makassar</h2>
      <div class="id-location">
        <i class="fas fa-map-marker-alt"></i> Sulawesi &bull; Gorontalo &bull; Maluku Utara
      </div>
    </div>
    <div class="id-stats">
      <div class="id-stat-box">
        <div class="id-stat-num">7</div>
        <div class="id-stat-label">Wilayah Kerja</div>
      </div>
      <div class="id-stat-box">
        <div class="id-stat-num">VIII</div>
        <div class="id-stat-label">Balai Wilayah</div>
      </div>
    </div>
  </div>

  <!-- Quick Nav -->
  <?php if (($_SESSION['role'] ?? '') !== 'tamu'): ?>
  <div class="quick-nav">
    <a href="<?= BASE_URL ?>pengajar/pengajar.php" class="qnav-btn qnav-primary">
      <i class="fas fa-chalkboard-teacher"></i> Data Pengajar
    </a>
    <a href="<?= BASE_URL ?>pengajar/dashboard.php" class="qnav-btn qnav-outline">
      <i class="fas fa-home"></i> Beranda
    </a>
    <button onclick="history.back()" class="qnav-btn qnav-outline">
      <i class="fas fa-arrow-left"></i> Kembali
    </button>
    <a href="<?= BASE_URL ?>logout.php" class="qnav-btn qnav-danger" id="btnKeluar">
      <i class="fas fa-sign-out-alt"></i> Keluar
    </a>
  </div>
  <?php endif; ?>

  <!-- Wilayah Grid -->
  <div class="section-title"><i class="fas fa-layer-group"></i> Pilih Wilayah</div>

  <div class="wilayah-grid">
    <?php
    $wilayah_list = [
      [1, "Sulawesi Selatan",  "fa-location-dot"],
      [2, "Sulawesi Barat",    "fa-location-dot"],
      [3, "Sulawesi Tengah",   "fa-location-dot"],
      [4, "Sulawesi Utara",    "fa-location-dot"],
      [5, "Sulawesi Tenggara", "fa-location-dot"],
      [6, "Gorontalo",         "fa-location-dot"],
      [7, "Maluku Utara",      "fa-location-dot"],
    ];
    foreach ($wilayah_list as [$id, $nama, $icon]):
    ?>
    <div class="wcard">
      <div class="wcard-icon-wrap"><i class="fas <?= $icon ?>"></i></div>
      <div class="wcard-name">WILAYAH KERJA<br><?= strtoupper($nama) ?></div>
      <div class="wcard-desc">Data dinas &amp; identifikasi pelatihan di wilayah ini.</div>
      <div class="wcard-actions">
        <a href="wilayah.php?id=<?= $id ?>" class="wcard-btn primary" onclick="leavePage(event, this.href)">
          <i class="fas fa-eye"></i> Lihat
        </a>
        <?php if (($_SESSION['role'] ?? '') !== 'tamu'): ?>
        <button class="wcard-btn secondary" onclick="openModal(<?= $id ?>, '<?= htmlspecialchars($nama) ?>')">
          <i class="fas fa-pen"></i> Catat
        </button>
        <?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Info Banner -->
  <div class="info-banner">
    <div class="info-banner-icon"><i class="fas fa-bullhorn"></i></div>
    <div>
      <h4>Informasi Sistem</h4>
      <p>Pastikan data dinas dan pelatihan selalu diperbarui agar proses penyusunan program berjalan optimal. Gunakan tombol <strong>Catat</strong> untuk menambahkan catatan wilayah secara langsung.</p>
    </div>
  </div>

</div>

<!-- ═══ MODAL CATATAN ═══ -->
<div class="modal-overlay" id="modalOverlay">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">
        <i class="fas fa-pen-to-square" style="color:var(--blue)"></i>
        <span id="modalWilayahLabel">Tambah Catatan</span>
      </div>
      <button class="modal-close" onclick="closeModal()" title="Tutup">&times;</button>
    </div>
    <form method="POST" action="" id="catatanForm">
      <?= csrf_field() ?>
      <input type="hidden" name="catatan_wilayah_id" id="catatanWilayahId">
      <div class="modal-body">
        <label for="catatanText"><i class="fas fa-file-alt"></i> Catatan untuk wilayah ini</label>
        <textarea name="catatan" id="catatanText" placeholder="Tuliskan catatan, temuan lapangan, atau hal penting lainnya..." required></textarea>

        <label><i class="fas fa-user"></i> Dicatat oleh</label>
        <input type="text" value="<?= htmlspecialchars($_SESSION['username'] ?? '') ?>" disabled style="opacity:0.6; cursor:default;">
      </div>
      <div class="modal-footer">
        <button type="button" class="btn-modal btn-cancel" onclick="closeModal()">
          <i class="fas fa-times"></i> Batal
        </button>
        <button type="submit" class="btn-modal btn-save">
          <i class="fas fa-save"></i> Simpan
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Toast -->
<?php if (isset($_GET['saved']) && $_GET['saved'] === '1'): ?>
<div class="toast" id="toast">
  <i class="fas fa-check-circle"></i> Catatan berhasil disimpan!
</div>
<?php endif; ?>

<script>
const loadBar = document.getElementById('loadingBar');

/* ── Page navigation with loading bar ── */
function leavePage(e, href) {
  e.preventDefault();
  loadBar.classList.add('active');
  setTimeout(() => {
    loadBar.classList.add('done');
    setTimeout(() => { window.location.href = href; }, 200);
  }, 400);
}

document.querySelectorAll('a.qnav-btn:not(.qnav-danger)').forEach(a => {
  a.addEventListener('click', function(e) { leavePage(e, this.href); });
});

/* ── Modal ── */
const overlay = document.getElementById('modalOverlay');

function openModal(id, nama) {
  document.getElementById('catatanWilayahId').value = id;
  document.getElementById('modalWilayahLabel').textContent = 'Catatan — Wilayah ' + nama;
  document.getElementById('catatanText').value = '';
  overlay.classList.add('open');
}

function closeModal() {
  overlay.classList.remove('open');
}

overlay.addEventListener('click', function(e) {
  if (e.target === overlay) closeModal();
});

document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') closeModal();
});

/* ── Toast auto-hide ── */
const toast = document.getElementById('toast');
if (toast) {
  setTimeout(() => {
    toast.classList.add('hide');
    setTimeout(() => toast.remove(), 350);
  }, 3500);
}

/* ── Konfirmasi logout ── */
var btnKeluar = document.getElementById('btnKeluar');
if (btnKeluar) btnKeluar.addEventListener('click', function(e) {
  e.preventDefault();
  if (confirm('Yakin ingin keluar dari sistem?')) {
    loadBar.classList.add('active');
    setTimeout(() => {
      loadBar.classList.add('done');
      setTimeout(() => { window.location.href = this.href; }, 200);
    }, 400);
  }
});
</script>
</div><!-- /main-content -->
</body>
</html>
