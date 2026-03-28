<?php
// pages/mitigapro/admin/belanja_modal.php
require $_SERVER['DOCUMENT_ROOT'] . '/MitigaPro/include/autoload.php';
require INCLUDE_PATH . 'sidebar_mitigapro.php'; // sidebar include


// Ambil slug dari URL
$slug = $_GET['menu'] ?? 'beranda';

// Cari menu_id berdasarkan slug
$stmtMenu = $conn->prepare("SELECT id, title FROM mitigapro_menus WHERE slug = ?");
$stmtMenu->bind_param("s", $slug);
$stmtMenu->execute();
$menuData = $stmtMenu->get_result()->fetch_assoc();

if (!$menuData) {
    die("Menu tidak ditemukan!");
}

$menu_id = $menuData['id'];
$menu_title = $menuData['title'];


/* ========== HANDLE ADD HANDLER (ADD PEGAWAI) ========== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_handler') {
    $name = trim($_POST['name'] ?? '');
    $nip = trim($_POST['nip'] ?? '');
    $jabatan = trim($_POST['jabatan'] ?? '');
    $tugas = trim($_POST['tugas'] ?? '');

    // file upload
    $photo_path = null;
    if (!empty($_FILES['photo']['name'])) {
        $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/mitigapro/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
        $filename = 'h_' . time() . '_' . rand(100,999) . '.' . $ext;
        $target = $uploadDir . $filename;
        if (move_uploaded_file($_FILES['photo']['tmp_name'], $target)) {
            $photo_path = '/uploads/mitigapro/' . $filename;
        }
    }

    // insert handler
    $stmt = $conn->prepare("INSERT INTO mitigapro_handlers (name, nip, jabatan, tugas, photo) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param('sssss', $name, $nip, $jabatan, $tugas, $photo_path);
    $stmt->execute();
    $handler_id = $stmt->insert_id;
    $stmt->close();

    // map to menu
    $stmt2 = $conn->prepare("INSERT INTO mitigapro_handler_menu (handler_id, menu_id) VALUES (?, ?)");
    $stmt2->bind_param('ii', $handler_id, $menu_id);
    $stmt2->execute();
    $stmt2->close();

    header("Location: " . $_SERVER['REQUEST_URI']);
    exit;
}

/* ========== HANDLE ADD CONTENT ========== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_content') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $link = trim($_POST['link'] ?? '');
    $link_label = trim($_POST['link_label'] ?? '');
    $priority = intval($_POST['priority'] ?? 0);

    // upload image
    $image_path = null;
    if (!empty($_FILES['image']['name'])) {
        $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/mitigapro/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $filename = 'c_' . time() . '_' . rand(100,999) . '.' . $ext;
        $target = $uploadDir . $filename;
        if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
            $image_path = '/uploads/mitigapro/' . $filename;
        }
    }

    $stmt = $conn->prepare("INSERT INTO mitigapro_contents (menu_id, title, description, image, link, link_label, priority) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param('isssssi', $menu_id, $title, $description, $image_path, $link, $link_label, $priority);
    $stmt->execute();
    $stmt->close();

    header("Location: " . $_SERVER['REQUEST_URI']);
    exit;
}

/* ========== HANDLE EDIT HANDLER ========== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_handler') {
    $id = intval($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $nip = trim($_POST['nip'] ?? '');
    $jabatan = trim($_POST['jabatan'] ?? '');
    $tugas = trim($_POST['tugas'] ?? '');

    // upload foto baru jika ada
    $photo_path = null;
    if (!empty($_FILES['photo']['name'])) {
        $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/mitigapro/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
        $filename = 'h_edit_' . time() . '_' . rand(100,999) . '.' . $ext;
        $target = $uploadDir . $filename;

        if (move_uploaded_file($_FILES['photo']['tmp_name'], $target)) {
            $photo_path = '/uploads/mitigapro/' . $filename;
        }
    }

    // prepare update statement (dengan atau tanpa foto)
    if ($photo_path) {
        $stmt = $conn->prepare("UPDATE mitigapro_handlers SET name = ?, nip = ?, jabatan = ?, tugas = ?, photo = ? WHERE id = ?");
        $stmt->bind_param('sssssi', $name, $nip, $jabatan, $tugas, $photo_path, $id);
    } else {
        $stmt = $conn->prepare("UPDATE mitigapro_handlers SET name = ?, nip = ?, jabatan = ?, tugas = ? WHERE id = ?");
        $stmt->bind_param('ssssi', $name, $nip, $jabatan, $tugas, $id);
    }

    if (!$stmt) {
        // debugging ringan jika prepare gagal
        error_log("Prepare failed (edit_handler): " . $conn->error);
    } else {
        $stmt->execute();
        $stmt->close();
    }

    header("Location: " . $_SERVER['REQUEST_URI']);
    exit;
}

/* ========== HANDLE EDIT CONTENT ========== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_content') {
    $id = intval($_POST['id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $link = trim($_POST['link'] ?? '');
    $link_label = trim($_POST['link_label'] ?? '');
    $priority = intval($_POST['priority'] ?? 0);

    // upload gambar baru jika ada
    $image_path = null;
    if (!empty($_FILES['image']['name'])) {
        $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/mitigapro/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $filename = 'c_edit_' . time() . '_' . rand(100,999) . '.' . $ext;
        $target = $uploadDir . $filename;

        if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
            $image_path = '/uploads/mitigapro/' . $filename;
        }
    }

    // prepare update statement (dengan atau tanpa gambar)
    if ($image_path) {
        $stmt = $conn->prepare("
            UPDATE mitigapro_contents
            SET title = ?, description = ?, image = ?, link = ?, link_label = ?, priority = ?
            WHERE id = ?
        ");
        // title, description, image, link, link_label, priority (int), id (int)
        $stmt->bind_param('sssssii', $title, $description, $image_path, $link, $link_label, $priority, $id);
    } else {
        $stmt = $conn->prepare("
            UPDATE mitigapro_contents
            SET title = ?, description = ?, link = ?, link_label = ?, priority = ?
            WHERE id = ?
        ");
        // title, description, link, link_label, priority (int), id (int)
        $stmt->bind_param('ssssii', $title, $description, $link, $link_label, $priority, $id);
    }

    if (!$stmt) {
        error_log("Prepare failed (edit_content): " . $conn->error);
    } else {
        $stmt->execute();
        $stmt->close();
    }

    header("Location: " . $_SERVER['REQUEST_URI']);
    exit;
}

/* ========== FETCH DATA TO DISPLAY ========== */
// handlers for this menu
$handlers_q = $conn->prepare("
    SELECT h.* FROM mitigapro_handlers h
    JOIN mitigapro_handler_menu hm ON hm.handler_id = h.id
    WHERE hm.menu_id = ?
    ORDER BY h.id DESC
");
$handlers_q->bind_param('i', $menu_id);
$handlers_q->execute();
$handlers_res = $handlers_q->get_result();

// contents for this menu
$contents_q = $conn->prepare("SELECT * FROM mitigapro_contents WHERE menu_id = ? ORDER BY priority DESC, created_at DESC");
$contents_q->bind_param('i', $menu_id);
$contents_q->execute();
$contents_res = $contents_q->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title><?= $menu_title ?> - Admin MitigaPro</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>1_css/sidebar_mitigapro.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
<style>
/* ========= GLOBAL ========= */
body {
    font-family: 'Poppins', sans-serif;
    background: #eef3f9;
    margin: 0;
}

.page-wrap {
    padding: 40px;
}

/* ========= TITLES ========= */
h1 {
    color: #0d47a1;
    font-weight: 700;
    font-size: 28px;
    letter-spacing: .5px;
    margin-bottom: 25px;
}

/* ========= BUTTONS ========= */
.btn {
    padding: 10px 18px;
    border-radius: 12px;
    border: none;
    cursor: pointer;
    font-weight: 600;
    letter-spacing: .3px;
    transition: transform 0.25s ease, box-shadow 0.25s ease;
}

/* Primary Gradient Button */
.btn.primary {
    background: linear-gradient(135deg, #1976d2, #004ba0);
    color: #fff;
    box-shadow: 0 4px 14px rgba(25,118,210,0.3);
}

.btn.primary:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 18px rgba(25,118,210,0.35);
}

/* Secondary Button */
.btn.secondary {
    background: linear-gradient(135deg, #03a9f4, #0288d1);
    color: white;
    box-shadow: 0 4px 12px rgba(3,169,244,0.25);
}

.btn.secondary:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 16px rgba(3,169,244,0.32);
}

/* Delete Button */
.btn-delete {
    background: linear-gradient(135deg, #e53935, #b71c1c);
    color: #fff;
    padding: 8px 14px;
    border-radius: 12px;
    font-weight: 600;
    font-size: 14px;
    transition: transform 0.25s ease, box-shadow 0.25s ease;
}

.btn-delete:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 16px rgba(229,57,53,0.35);
}

/* ========= CARD GRID ========= */
.card-grid {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

/* ========= PROFILE CARD ========= */
.profile-card {
    display: flex;
    align-items: center;
    gap: 20px;
    background: rgba(255, 255, 255, 0.2); /* transparan tapi tetap berwarna */
    padding: 22px;
    border-radius: 14px;
    border: 1px solid rgba(0,0,0,0.05);
    box-shadow: 0 4px 14px rgba(0,0,0,0.06);
    transition: transform 0.25s ease, box-shadow 0.25s ease;
}

/* Hover: Biar kerasa luxury */
.profile-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 22px rgba(0,0,0,0.08);
}

/* ========= PROFILE IMAGE ========= */
.profile-photo {
    width: 200px;
    height: 270px;
    object-fit: cover;
    border-radius: 12px;
    border: 3px solid #2d64ac41;
    transition: 0.3s ease;
}

.profile-photo:hover {
    transform: scale(1.02);
}

/* ========= PROFILE TEXT ========= */
.profile-info {
    flex: 1;
}

.name {
    font-size: 20px;
    font-weight: 700;
    color: #0d47a1;
    margin-bottom: 6px;
}

.muted {
    font-size: 14px;
    color: #6b6b6b;
    margin-bottom: 4px;
    letter-spacing: .2px;
}

/* ========= CONTROL ROW ========= */
.action-row {
    margin-top: 14px;
    display: flex;
    gap: 12px;
}

/* Modern hyperlinks styled like buttons */
.btn-edit {
    background: linear-gradient(135deg, #1b66c9, #0d47a1);
    padding: 8px 16px;
    border-radius: 12px;
    color: #fff;
    font-size: 14px;
    text-decoration: none;
    transition: 0.25s ease;
}

.btn-edit:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 16px rgba(27,102,201,0.35);
}

.btn-delete {
    background: linear-gradient(135deg, #e74c3c, #c0392b);
    padding: 8px 16px;
    border-radius: 12px;
    color: #fff;
    font-size: 14px;
    text-decoration: none;
    transition: 0.25s ease;
}

.btn-delete:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 16px rgba(231,76,60,0.35);
}

.content-image {
    display: block;
    margin: 0 auto; /* center horizontal */
    max-width: 70%; /* atau 50% sesuai kebutuhan */
    height: auto;
    border-radius: 10px;
}


</style>


</head>
<body>

<div class="main-content" id="mainContent">
    <div class="page-wrap">
        <h1><?= $menu_title ?></h1>

        <div class="top-actions">
            <button class="btn primary" onclick="openModal('modalAddHandler')">+ Tambah Pegawai</button>
            <button class="btn secondary" onclick="openModal('modalAddContent')">+ Tambah Konten</button>
        </div>

        <h3>Daftar Pengendali Risiko</h3>

        <div class="card-grid">
            <?php while ($h = $handlers_res->fetch_assoc()): ?>
                <div class="profile-card">

                    <!-- FOTO PROFIL -->
                    <?php if ($h['photo']): ?>
                        <img class="profile-photo" src="<?= htmlspecialchars($h['photo']) ?>" alt="Foto Pegawai">
                    <?php else: ?>
                        <img class="profile-photo" src="<?= BASE_URL ?>uploads/mitigapro/" alt="Foto Default">
                    <?php endif; ?>

                    <!-- INFORMASI PEGAWAI -->
                    <div class="profile-info">
                        <div class="name"><?= htmlspecialchars($h['name']) ?></div>
                        <div class="muted">NIP: <?= htmlspecialchars($h['nip']) ?></div>
                        <div class="muted">Jabatan: <?= htmlspecialchars($h['jabatan']) ?></div>
                        <div class="muted">Tugas: <?= htmlspecialchars($h['tugas']) ?></div>

                        <!-- TOMBOL AKSI -->
                        <div class="action-row">
                            <a class="btn-edit" href="javascript:void(0)" 
                            onclick="openEditHandler(
                                '<?= $h['id'] ?>',
                                '<?= htmlspecialchars($h['name']) ?>',
                                '<?= htmlspecialchars($h['nip']) ?>',
                                '<?= htmlspecialchars($h['jabatan']) ?>',
                                '<?= htmlspecialchars($h['tugas']) ?>',
                                '<?= htmlspecialchars($h['photo']) ?>'
                            )">Edit Tampilan</a>
                            <a class="btn-delete" 
                            href="<?= BASE_URL ?>mitigapro/admin/delete.php?type=handler&id=<?= $h['id'] ?>&redirect=<?= urlencode($_SERVER['REQUEST_URI']) ?>"
                            onclick="return confirm('Yakin ingin menghapus pegawai ini?')"> Hapus</a>
                        </div>
                    </div>

        </div>
    <?php endwhile; ?>
</div>

        <hr style="margin:24px 0">

        <h3>Konten Tambahan</h3>
        <div class="card-grid">
            <?php while ($c = $contents_res->fetch_assoc()): ?>
                <div class="card">
                    <?php if ($c['image']):?>
                    <img src="<?= htmlspecialchars($c['image']) ?>" alt="img" class="content-image">
                    <?php endif; ?>
                    <div style="font-weight:600"><?= htmlspecialchars($c['title'] ?: '- Judul kosong -') ?></div>
                    <?php if ($c['description']): ?>
                        <div class="muted" style="margin:8px 0"><?= nl2br(htmlspecialchars($c['description'])) ?></div>
                    <?php endif; ?>
                    <?php if ($c['link']): ?>
                        <div style="margin-top:8px"><a href="<?= htmlspecialchars($c['link']) ?>" target="_blank" class="btn-edit"><?= htmlspecialchars($c['link_label'] ?: 'Buka Link') ?></a></div>
                    <?php endif; ?>
                    <div style="display:flex; gap:10px; margin-top:10px;">
                    <a class="btn-edit" href="javascript:void(0)"
                    onclick="openEditContent(
                            '<?= $c['id'] ?>',
                            `<?= htmlspecialchars($c['title']) ?>`,
                            `<?= htmlspecialchars($c['description']) ?>`,
                            '<?= htmlspecialchars($c['image']) ?>',
                            '<?= htmlspecialchars($c['link']) ?>',
                            '<?= htmlspecialchars($c['link_label']) ?>',
                            '<?= $c['priority'] ?>'
                    )">Edit Tampilan</a>
                    <a class="btn-delete" 
                    href="<?= BASE_URL ?>mitigapro/admin/delete.php?type=content&id=<?= $c['id'] ?>&redirect=<?= urlencode($_SERVER['REQUEST_URI']) ?>"
                    onclick="return confirm('Yakin ingin menghapus pegawai ini?')"> Hapus</a>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>
</div>

<!-- ========== MODAL: Add Handler (Pegawai) ========== -->
<div id="modalAddHandler" class="modal" style="display:none;">
    <div class="modal-content" style="max-width:700px;padding:18px;border-radius:12px;background:#fff;">
        <h2>Tambah Pegawai Pengendali Risiko</h2>
        <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="action" value="add_handler">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
                <input name="name" placeholder="Nama lengkap" required>
                <input name="nip" placeholder="NIP">
                <input name="jabatan" placeholder="Jabatan">
                <input name="tugas" placeholder="Tugas">
            </div>
            <div style="margin-top:10px">
                <label>Foto (jpg/png)</label><br>
                <input type="file" name="photo" id="handlerPhoto" accept="image/*">
                <div id="handlerPhotoPreview" style="margin-top:8px"></div>
            </div>

            <div style="margin-top:12px">
                <button type="submit" class="btn primary">Simpan</button>
                <button type="button" class="btn" onclick="closeModal('modalAddHandler')">Batal</button>
            </div>
        </form>
    </div>
</div>

<!-- ========== MODAL: Edit Handler (Pegawai) ========== -->
<div id="modalEditHandler" class="modal" style="display:none;">
    <div class="modal-content" style="max-width:700px;padding:18px;border-radius:12px;background:#fff;">
        <h2>Edit Pegawai Pengendali Risiko</h2>
        <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="action" value="edit_handler">
            <input type="hidden" name="id" id="editHandlerId">

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
                <input name="name" id="editHandlerName" placeholder="Nama lengkap" required>
                <input name="nip" id="editHandlerNip" placeholder="NIP">
                <input name="jabatan" id="editHandlerJabatan" placeholder="Jabatan">
                <input name="tugas" id="editHandlerTugas" placeholder="Tugas">
            </div>

            <div style="margin-top:10px">
                <label>Ganti Foto (opsional)</label><br>
                <input type="file" name="photo" accept="image/*">
                <div id="editHandlerPhotoPreview" style="margin-top:8px"></div>
            </div>

            <div style="margin-top:12px">
                <button type="submit" class="btn primary">Update Pegawai</button>
                <button type="button" class="btn" onclick="closeModal('modalEditHandler')">Batal</button>
            </div>
        </form>
    </div>
</div>


<!-- ========== MODAL: Add Content ========== -->
<div id="modalAddContent" class="modal" style="display:none;">
    <div class="modal-content" style="max-width:700px;padding:18px;border-radius:12px;background:#fff;">
        <h2>Tambah Konten</h2>
        <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="action" value="add_content">
            <div style="margin-bottom:8px">
                <input name="title" placeholder="Judul konten">
            </div>
            <div style="margin-bottom:8px">
                <textarea name="description" placeholder="Deskripsi (opsional)" rows="4" style="width:100%"></textarea>
            </div>
            <div style="display:flex;gap:8px">
                <div>
                    <label>Gambar (opsional)</label><br>
                    <input type="file" name="image" id="contentImage" accept="image/*">
                    <div id="contentImagePreview" style="margin-top:8px"></div>
                </div>
                <div style="flex:1">
                    <input name="link" placeholder="Link (opsional)">
                    <input name="link_label" placeholder="Label link (opsional)">
                    <input name="priority" type="number" placeholder="Prioritas (angka, default 0)" value="0">
                </div>
            </div>

            <div style="margin-top:12px">
                <button type="submit" class="btn primary">Simpan Konten</button>
                <button type="button" class="btn" onclick="closeModal('modalAddContent')">Batal</button>
            </div>
        </form>
    </div>
</div>

<!-- ========== MODAL: Edit Content ========== -->
<div id="modalEditContent" class="modal" style="display:none;">
    <div class="modal-content" style="max-width:700px;padding:18px;border-radius:12px;background:#fff;">
        <h2>Edit Konten</h2>

        <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="action" value="edit_content">
            <input type="hidden" name="id" id="editContentId">

            <input name="title" id="editContentTitle" placeholder="Judul konten">

            <textarea name="description" id="editContentDescription" rows="4" style="width:100%" placeholder="Deskripsi (opsional)"></textarea>

            <div style="margin-top:8px">
                <label>Ganti gambar (opsional)</label>
                <input type="file" name="image" accept="image/*">
                <div id="editContentImagePreview" style="margin-top:8px"></div>
            </div>

            <input name="link" id="editContentLink" placeholder="Link (opsional)">
            <input name="link_label" id="editContentLinkLabel" placeholder="Label Link">
            <input type="number" name="priority" id="editContentPriority" placeholder="Prioritas">

            <div style="margin-top:12px">
                <button type="submit" class="btn primary">Update Konten</button>
                <button type="button" class="btn" onclick="closeModal('modalEditContent')">Batal</button>
            </div>
        </form>
    </div>
</div>

<!-- modal CSS & JS -->
<style>
/* ===== MODAL ===== */
.modal {
    position: fixed;
    inset:0;
    background: rgba(0,0,0,0.45);
    backdrop-filter: blur(3px);
    display:flex;
    align-items:center;
    justify-content:center;
    z-index:3000;
}

.modal .modal-content {
    background:#ffffff;
    border-radius:16px;
    box-shadow:0 8px 30px rgba(0,0,0,0.15);
    padding:24px;
    width:90%;
    max-width:720px;
    animation: fadeIn .2s ease-out;
}

/* Inputs */
.modal input,
.modal textarea {
    width:100%;
    padding:10px;
    margin-top:5px;
    border:1px solid #cfd8dc;
    border-radius:8px;
    font-size:14px;
}

.modal h2 {
    color:#0d47a1;
    font-weight:700;
    margin-bottom:15px;
}

@keyframes fadeIn {
    from { opacity:0; transform: translateY(6px); }
    to { opacity:1; transform: translateY(0); }
}
</style>

<script>

function openEditHandler(id, name, nip, jabatan, tugas, photo){
    document.getElementById('editHandlerId').value = id;
    document.getElementById('editHandlerName').value = name;
    document.getElementById('editHandlerNip').value = nip;
    document.getElementById('editHandlerJabatan').value = jabatan;
    document.getElementById('editHandlerTugas').value = tugas;

    let prev = document.getElementById('editHandlerPhotoPreview');
    prev.innerHTML = photo ? `<img src="${photo}" style="max-width:180px;border-radius:8px;">` : '';

    openModal('modalEditHandler');
}

function openEditContent(id, title, description, image, link, link_label, priority){
    document.getElementById('editContentId').value = id;
    document.getElementById('editContentTitle').value = title;
    document.getElementById('editContentDescription').value = description;
    document.getElementById('editContentLink').value = link;
    document.getElementById('editContentLinkLabel').value = link_label;
    document.getElementById('editContentPriority').value = priority;

    let prev = document.getElementById('editContentImagePreview');
    prev.innerHTML = image ? `<img src="${image}" style="max-width:180px;border-radius:8px;">` : '';

    openModal('modalEditContent');
}


// SIDEBAR TOGGLE: if you used previous toggle, this is safe
function toggleSidebar() {
    const s = document.getElementById('sidebar');
    const m = document.getElementById('mainContent');
    s.classList.toggle('collapsed');
    m.classList.toggle('expanded');
}

// MODAL functions
function openModal(id){ document.getElementById(id).style.display='flex'; }
function closeModal(id){ document.getElementById(id).style.display='none'; }

// image preview handler
document.getElementById('handlerPhoto')?.addEventListener('change', function(e){
    const p = document.getElementById('handlerPhotoPreview');
    p.innerHTML = '';
    const f = e.target.files[0];
    if (!f) return;
    const img = document.createElement('img');
    img.style.maxWidth='180px'; img.style.borderRadius='8px';
    img.src = URL.createObjectURL(f);
    p.appendChild(img);
});

document.getElementById('contentImage')?.addEventListener('change', function(e){
    const p = document.getElementById('contentImagePreview');
    p.innerHTML = '';
    const f = e.target.files[0];
    if (!f) return;
    const img = document.createElement('img');
    img.style.maxWidth='100px'; img.style.borderRadius='8px';
    img.src = URL.createObjectURL(f);
    p.appendChild(img);
});
</script>

<script>
/* Real-time preview foto Pegawai */
document.getElementById("handlerPhoto").addEventListener("change", function(e) {
    const preview = document.getElementById("handlerPhotoPreview");
    preview.innerHTML = ""; // reset preview

    const file = e.target.files[0];
    if (!file) return;

    const img = document.createElement("img");
    img.src = URL.createObjectURL(file);
    img.style.width = "150px";
    img.style.height = "200px";
    img.style.objectFit = "cover";
    img.style.border = "2px solid #1b66c9";
    img.style.borderRadius = "4px";

    preview.appendChild(img);
});
</script>

<script>
document.getElementById("contentImage")?.addEventListener("change", function(e) {
    const preview = document.getElementById("contentImagePreview");
    preview.innerHTML = "";

    const file = e.target.files[0];
    if (!file) return;

    const img = document.createElement("img");
    img.src = URL.createObjectURL(file);
    img.style.width = "70%";
    img.style.maxWidth = "70px";
    img.style.borderRadius = "6px";

    preview.appendChild(img);
});
</script>


</body>
</html>