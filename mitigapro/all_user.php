<?php
require $_SERVER['DOCUMENT_ROOT'] . '/MitigaPro/include/autoload.php';
require INCLUDE_PATH . 'sidebar_mitigapro.php'; // sidebar untuk semua user

// Ambil slug
$slug = $_GET['menu'] ?? 'beranda';

// Data menu
$stmtMenu = $conn->prepare("SELECT id, title FROM mitigapro_menus WHERE slug = ?");
$stmtMenu->bind_param("s", $slug);
$stmtMenu->execute();
$menuData = $stmtMenu->get_result()->fetch_assoc();
$stmtMenu->close();

if (!$menuData) {
    die("Menu tidak ditemukan!");
}

$menu_id = $menuData['id'];
$menu_title = $menuData['title'];

// Handler
$stmt = $conn->prepare("
    SELECT h.*
    FROM mitigapro_handlers h
    JOIN mitigapro_handler_menu hm ON hm.handler_id = h.id
    WHERE hm.menu_id = ?
");
$stmt->bind_param("i", $menu_id);
$stmt->execute();
$handlers = $stmt->get_result();
$stmt->close();

// Konten
$stmtC = $conn->prepare("SELECT * FROM mitigapro_contents WHERE menu_id = ? ORDER BY priority ASC");
$stmtC->bind_param("i", $menu_id);
$stmtC->execute();
$contents = $stmtC->get_result();
$stmtC->close();

?>
<!DOCTYPE html>
<html>
<head>
<title><?= htmlspecialchars($menu_title) ?> - MitigaPro</title>
<link rel="stylesheet" href="<?= BASE_URL ?>1_css/sidebar_mitigapro.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
/* =====================
    RESPONSIVE LAYOUT
===================== */
body {
    font-family: 'Poppins', sans-serif;
    background: #f2f4f8;
    margin: 0;
}

.main {
    margin-left: 280px;
    padding: 34px;
    max-width: 1100px;
    transition: margin-left 0.30s ease; /* SMOOTH */
}

/* Saat sidebar ditutup */
.main.expanded {
    margin-left: 80px !important; /* sesuaikan dengan lebar sidebar collapsed */
    transition: margin-left 0.30s ease; /* SMOOTH */
}

/* =====================
        TITLE
===================== */
.page-title {
    font-size: 32px;
    font-weight: 700;
    margin-bottom: 30px;
    color: #111;
    letter-spacing: -0.5px;
}

/* =====================
    HANDLER SECTION
===================== */
.section-label {
    font-size: 20px;
    font-weight: 600;
    margin-bottom: 18px;
    border-left: 5px solid #3a45ff;
    padding-left: 12px;
    color: #2e2e2e;
}

/* GRID BARU (lebih rapi, responsif) */
.handler-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 20px;
    width: 100%;
}

/* CARD BARU */
.handler-card {
    display: flex;
    align-items: flex-start;
    gap: 20px;
    width: 100%; /* <-- solusi utama */
    background: white;
    padding: 20px;
    border-radius: 14px;
    border: 1px solid #e4e4e4;
    box-shadow: 0 3px 10px rgba(0,0,0,0.06);
    transition: 0.25s ease;
}

.handler-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.09);
}

/* FOTO */
.profile-photo {
    width: 140px;
    height: 180px;
    border-radius: 12px;
    object-fit: cover;
}

/* INFO */
.profile-info {
    flex: 1;
}

.handler-name {
    font-size: 19px;
    font-weight: 700;
    margin-bottom: 6px;
    color: #0d47a1;
}

.handler-text {
    font-size: 14px;
    color: #555;
    margin-bottom: 5px;
}

/* =====================
    CONTENT SEPARATOR
===================== */
.content-separator {
    margin-top: 30px;
    padding: 14px 17px;
    font-size: 18px;
    font-weight: 600;
    border-left: 5px solid #3a45ff;
    border-radius: 6px;
}

/* =====================
        CONTENT CARD
===================== */
.content-card {
    margin-top: 30px;
    box-shadow: 0 3px 10px rgba(0,0,0,0.08);
    align-items: flex-start;
    gap: 20px;
    width: 100%; /* <-- solusi utama */
    background: white;
    padding: 20px;
    border-radius: 14px;
    border: 1px solid #e4e4e4;
    transition: 0.25s ease;
}

.content-image {
    max-width: 100%;
    margin: 16px 0;
    border-radius: 12px;
}

.link-btn {
    padding: 10px 18px;
    display: inline-block;
    background: #3a45ff;
    color: white;
    border-radius: 6px;
    text-decoration: none;
    font-size: 14px;
}
.link-btn:hover {
    background: #2a37c9;
}




</style>

</head>
<body>

<div class="main" id="mainContent" >

    <div class="page-title"><?= htmlspecialchars($menu_title) ?></div>

<!-- HANDLER SECTION -->
<div class="handler-section">
    <div class="section-label">Daftar Pengendali Risiko</div>

<div class="handler-grid">
    <?php while ($h = $handlers->fetch_assoc()): ?>
        <div class="handler-card">

            <img class="profile-photo"
                 src="<?= $h['photo'] ?: '/uploads/mitigapro/default.jpg' ?>">

            <div class="profile-info">
                <div class="handler-name"><?= htmlspecialchars($h['name']) ?></div>
                <div class="handler-text">NIP: <?= htmlspecialchars($h['nip']) ?></div>
                <div class="handler-text"><?= htmlspecialchars($h['jabatan']) ?></div>
                <div class="handler-text">Tugas: <?= htmlspecialchars($h['tugas']) ?></div>
            </div>

        <?php if ($handlers->num_rows == 0): ?>
            <p style="color:#777;">Belum ada pengendali risiko.</p>
        <?php endif; ?>
            </div>
        <?php endwhile; ?>
        </div>
    </div>

    <!-- CONTENT SECTION -->
    <?php while ($c = $contents->fetch_assoc()): ?>
        
        <div class="content-separator">
            <?= htmlspecialchars($c['title']) ?>
        </div>

        <div class="content-card">

            <?php if (!empty($c['description'])): ?>
                <p><?= nl2br(htmlspecialchars($c['description'])) ?></p>
            <?php endif; ?>

            <?php if (!empty($c['image'])): ?>
                <img class="content-image" src="<?= htmlspecialchars($c['image']) ?>">
            <?php endif; ?>

            <?php if (!empty($c['link'])): ?>
                <a href="<?= htmlspecialchars($c['link']) ?>" class="link-btn" target="_blank">
                    <?= htmlspecialchars($c['link_label'] ?: 'Kunjungi') ?>
                </a>
            <?php endif; ?>

        </div>

    <?php endwhile; ?>

</div>
<script>

// SIDEBAR TOGGLE: if you used previous toggle, this is safe
function toggleSidebar() {
const s = document.getElementById('sidebar');
const m = document.getElementById('mainContent');
s.classList.toggle('collapsed');
m.classList.toggle('expanded');
}

</script>
</body>
</html>
