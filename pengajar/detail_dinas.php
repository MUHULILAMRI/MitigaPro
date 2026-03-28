<?php
require $_SERVER['DOCUMENT_ROOT'] . '/MitigaPro/include/autoload.php';

if (!isset($_SESSION['role'])) {
    header("Location: login.php");
    exit;
}

require INCLUDE_PATH . 'sidebar_pengajar.php';

$role = $_SESSION['role'] ?? 'pengajar';

if (!isset($_GET['id'])) {
    header('Location: ' . BASE_URL . 'pengajar/dashboard.php'); exit;
}

$dinas_id = intval($_GET['id']);

// Ambil info dinas
$stmt = $conn->prepare("SELECT * FROM dinas WHERE id = ?");
$stmt->bind_param('i', $dinas_id);
$stmt->execute();
$dinas = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$dinas) { header('Location: ' . BASE_URL . 'pengajar/dashboard.php'); exit; }

// Ambil daftar pelatihan + ID
$stmt2 = $conn->prepare("SELECT id, jenis_pelatihan FROM identifikasi_pelatihan WHERE dinas_id = ? GROUP BY jenis_pelatihan ORDER BY jenis_pelatihan ASC");
$stmt2->bind_param('i', $dinas_id);
$stmt2->execute();
$data = $stmt2->get_result();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<link rel="stylesheet" href="<?= BASE_URL ?>1_css/sidebar_pengajar.css">
<link rel="stylesheet" href="<?= BASE_URL ?>1_css/footer.css">
<title>Pelatihan - <?= $dinas['nama_dinas'] ?></title>

<style>
body {
    background: #f1f5fb;
    font-family: 'Poppins', sans-serif;
    margin: 0;
}

.container {
    padding: 30px;
    max-width: 1000px;
    margin: 0 auto;
}

/* Judul */
.page-title {
    font-size: 30px;
    color: #1e3c72;
    font-weight: 700;
    margin-bottom: 18px;
}

/* Tombol tambah */
.add-btn {
    background: #1e3c72;
    padding: 10px 18px;
    color: #fff;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 600;
    text-decoration: none;
    transition: 0.3s ease;
    display: inline-block;
    margin-bottom: 25px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.12);
}
.add-btn:hover {
    background: #2a5298;
    transform: translateY(-2px);
}

/* Card wrapper */
.card {
    background: #fff;
    border-radius: 18px;
    padding: 25px 30px;
    box-shadow: 0 8px 25px rgba(0,0,0,0.08);
}

/* Item pelatihan */
.pelatihan-item {
    background: #f8fafc;
    padding: 14px 20px;
    border-radius: 12px;
    font-size: 16px;
    font-weight: 600;
    color: #1e3c72;
    margin-bottom: 12px;
    transition: 0.25s ease;
    border: 1px solid #e5e7eb;
    cursor: pointer;
}

.pelatihan-item:hover {
    background: #edf2ff;
    border-color: #cdd6ff;
    transform: translateX(5px);
}

.menu-popup {
    position: absolute;
    background: #fff;
    border-radius: 12px;
    padding: 10px 0;
    width: 160px;
    box-shadow: 0 8px 20px rgba(0,0,0,0.15);
    display: none;
    z-index: 100;
}

.menu-popup a {
    display: block;
    padding: 10px 16px;
    font-size: 14px;
    color: #1e3c72;
    text-decoration: none;
    transition: 0.2s;
}

.menu-popup a:hover {
    background: #f1f5ff;
}
</style>
</head>

<body>

<div id="mainContent" class="main-content">
<div class="container">
    <?= breadcrumb([['label' => 'Data Dinas', 'url' => 'dinas.php'], ['label' => htmlspecialchars($dinas['nama_dinas'])]]) ?>
    <h2 class="page-title">
        Daftar Pelatihan — <?= $dinas['nama_dinas'] ?>
    </h2>

    <?php if ($role === 'admin'): ?>
    <a href="tambah_pelatihan.php?id=<?= $dinas_id ?>" class="add-btn">+ Tambah Pelatihan</a>
    <?php endif; ?>

    <div class="card">
        <?php while ($r = mysqli_fetch_assoc($data)): ?>
            <div class="pelatihan-item"
                 onclick="openMenu(this, <?= $r['id'] ?>)">
                <?= $r['jenis_pelatihan'] ?>
            </div>
        <?php endwhile; ?>
    </div>
</div>

<!-- Popup Menu -->
<?php if ($role === 'admin'): ?>
<div id="actionMenu" class="menu-popup">
    <a id="editLink" href="edit_pelatihan.php">Edit</a>
    <a id="deleteLink" href="hapus_pelatihan.php" onclick="return confirm('Yakin ingin menghapus pelatihan ini?')">Hapus</a>
</div>
<?php endif; ?>

<script>
function openMenu(element, id) {
    const menu = document.getElementById("actionMenu");
    const rect = element.getBoundingClientRect();

    // Posisi menu muncul tepat di bawah item
    menu.style.top = (window.scrollY + rect.bottom + 5) + "px";
    menu.style.left = (rect.left) + "px";
    menu.style.display = "block";

    // Set link dinamis
    document.getElementById("editLink").href = "edit_pelatihan.php?id=" + id;
    document.getElementById("deleteLink").href = "hapus_pelatihan.php?id=" + id;
}

// Klik di luar menu → tutup popup
document.addEventListener("click", function(e) {
    const menu = document.getElementById("actionMenu");

    if (!e.target.closest(".pelatihan-item")) {
        menu.style.display = "none";
    }
});
</script>
</div><!-- /main-content -->
</body>
</html>
