<?php
require $_SERVER['DOCUMENT_ROOT'] . '/MitigaPro/include/autoload.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}

$msg = '';
$msg_type = 'ok';
$edit_user = null;

/* ══════════════ HAPUS USER ══════════════ */
if (isset($_GET['delete'])) {
    $del_id = intval($_GET['delete']);
    if ($del_id === intval($_SESSION['user_id'])) {
        $msg = 'Tidak dapat menghapus akun Anda sendiri.';
        $msg_type = 'err';
    } else {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param('i', $del_id);
        $stmt->execute();
        $stmt->close();
        header('Location: manage_users.php?success=deleted');
        exit;
    }
}

/* ══════════════ TAMBAH / EDIT USER ══════════════ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $post_id   = intval($_POST['user_id'] ?? 0);
    $username  = trim($_POST['username'] ?? '');
    $role      = $_POST['role'] ?? 'pengajar';
    $password  = $_POST['password'] ?? '';

    if ($username === '') {
        $msg = 'Username tidak boleh kosong.'; $msg_type = 'err';
    } else {
        if ($post_id > 0) {
            // UPDATE
            if ($password !== '') {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET username=?, role=?, password=? WHERE id=?");
                $stmt->bind_param('sssi', $username, $role, $hash, $post_id);
            } else {
                $stmt = $conn->prepare("UPDATE users SET username=?, role=? WHERE id=?");
                $stmt->bind_param('ssi', $username, $role, $post_id);
            }
            $stmt->execute(); $stmt->close();
            header('Location: manage_users.php?success=updated'); exit;
        } else {
            // INSERT — password wajib
            if ($password === '') {
                $msg = 'Password wajib diisi untuk user baru.'; $msg_type = 'err';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?,?,?)");
                $stmt->bind_param('sss', $username, $hash, $role);
                if ($stmt->execute()) {
                    header('Location: manage_users.php?success=created'); exit;
                } else {
                    $msg = 'Username sudah dipakai, gunakan yang lain.'; $msg_type = 'err';
                }
                $stmt->close();
            }
        }
    }
}

/* ══════════════ LOAD EDIT ══════════════ */
if (isset($_GET['edit'])) {
    $eid = intval($_GET['edit']);
    $r = $conn->prepare("SELECT id, username, role FROM users WHERE id=?");
    $r->bind_param('i', $eid);
    $r->execute();
    $edit_user = $r->get_result()->fetch_assoc();
    $r->close();
}

/* ══════════════ DAFTAR SEMUA USER ══════════════ */
$search = trim($_GET['q'] ?? '');
if ($search !== '') {
    $sq = '%' . $search . '%';
    $list_q = $conn->prepare("SELECT * FROM users WHERE username LIKE ? ORDER BY created_at DESC");
    $list_q->bind_param('s', $sq);
    $list_q->execute();
    $users = $list_q->get_result();
} else {
    $users = $conn->query("SELECT * FROM users ORDER BY created_at DESC");
}

if (isset($_GET['success'])) {
    $msgs = ['created'=>'User berhasil ditambahkan!','updated'=>'User berhasil diperbarui!','deleted'=>'User berhasil dihapus!'];
    $msg = $msgs[$_GET['success']] ?? '';
    $msg_type = 'ok';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manajemen User | Admin MitigaPro</title>
<link rel="stylesheet" href="<?= BASE_URL ?>1_css/sidebar_mitigapro.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
:root {
  --navy:#0d1f4e; --blue:#1e3c72; --mid:#2a5298; --accent:#4f8ef7;
  --green:#22c55e; --red:#ef4444; --orange:#f59e0b; --purple:#8b5cf6;
  --white:#fff; --bg:#f0f4ff; --border:#dce6f5; --muted:#6b7fa3; --radius:16px;
}
body {
  font-family:'Poppins',sans-serif;
  background:linear-gradient(160deg,#eaf0ff,#f5f8ff 60%,#e8f3ff);
  min-height:100vh; display:flex; color:var(--navy);
  animation:pageIn 0.5s ease both;
}
@keyframes pageIn { from{opacity:0;transform:translateY(10px)} to{opacity:1;transform:none} }

.main-content { margin-left:260px; flex:1; padding:36px 40px 60px; transition:margin-left 0.3s; }
.main-content.expanded { margin-left:72px; }

/* ── Page Header ── */
.page-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:28px; flex-wrap:wrap; gap:14px; animation:fadeUp 0.5s ease 0.05s both; }
@keyframes fadeUp { from{opacity:0;transform:translateY(16px)} to{opacity:1;transform:none} }
.page-header h1 { font-size:22px; font-weight:800; color:var(--navy); display:flex; align-items:center; gap:10px; }
.page-header p  { font-size:13px; color:var(--muted); margin-top:4px; }
.breadcrumb { font-size:12px; color:var(--muted); display:flex; align-items:center; gap:6px; margin-bottom:6px; }
.breadcrumb a { color:var(--accent); text-decoration:none; }

/* ── Alert ── */
.alert { display:flex; align-items:center; gap:10px; padding:14px 18px; border-radius:12px; font-size:13.5px; font-weight:500; margin-bottom:22px; animation:fadeUp 0.4s ease both; }
.alert-ok  { background:#f0fdf4; border:1.5px solid #86efac; color:#15803d; }
.alert-err { background:#fff5f5; border:1.5px solid #fca5a5; color:#991b1b; }

/* ── Layout ── */
.layout { display:grid; grid-template-columns:1fr 380px; gap:24px; align-items:start; }
@media(max-width:1100px){ .layout{grid-template-columns:1fr;} }

/* ── Panel ── */
.panel { background:var(--white); border-radius:var(--radius); box-shadow:0 4px 18px rgba(30,60,114,0.08); border:1.5px solid var(--border); overflow:hidden; animation:fadeUp 0.5s ease both; }
.panel:nth-child(1){animation-delay:0.1s}
.panel:nth-child(2){animation-delay:0.18s}

.panel-header { display:flex; align-items:center; justify-content:space-between; padding:18px 22px; border-bottom:1.5px solid var(--border); background:linear-gradient(90deg,#f7f9ff,#fafbff); }
.panel-title  { display:flex; align-items:center; gap:10px; font-size:14.5px; font-weight:700; color:var(--navy); }
.panel-title i { color:var(--accent); }

/* ── Search bar ── */
.search-bar { display:flex; gap:10px; padding:16px 22px; border-bottom:1.5px solid var(--border); }
.search-bar input {
  flex:1; padding:10px 16px; border:1.8px solid var(--border); border-radius:10px;
  font-family:'Poppins',sans-serif; font-size:13.5px; color:var(--navy); outline:none;
  background:#f8fafd; transition:border-color 0.25s,box-shadow 0.25s;
}
.search-bar input:focus { border-color:var(--accent); box-shadow:0 0 0 3px rgba(79,142,247,0.12); background:#fff; }
.btn-search { display:inline-flex; align-items:center; gap:6px; padding:10px 20px; background:linear-gradient(135deg,var(--blue),var(--accent)); color:#fff; border:none; border-radius:10px; font-size:13px; font-weight:600; font-family:'Poppins',sans-serif; cursor:pointer; transition:transform 0.2s; }
.btn-search:hover { transform:translateY(-1px); }

/* ── Table ── */
.data-table { width:100%; border-collapse:collapse; font-size:13px; }
.data-table th { padding:10px 18px; text-align:left; background:#f5f8ff; color:var(--muted); font-size:11px; font-weight:700; letter-spacing:0.4px; text-transform:uppercase; border-bottom:1.5px solid var(--border); }
.data-table td { padding:13px 18px; border-bottom:1px solid #f0f4fb; vertical-align:middle; }
.data-table tr:last-child td { border-bottom:none; }
.data-table tr:hover td { background:#f7f9ff; }

.td-avatar { width:34px; height:34px; border-radius:50%; background:linear-gradient(135deg,var(--blue),var(--accent)); display:inline-flex; align-items:center; justify-content:center; color:#fff; font-size:14px; font-weight:700; flex-shrink:0; }
.td-name { display:flex; align-items:center; gap:10px; }
.td-sub  { font-size:11px; color:var(--muted); }

.badge { display:inline-flex; align-items:center; padding:3px 10px; border-radius:100px; font-size:11px; font-weight:700; }
.badge-admin    { background:#ede9fe; color:#6c3fd3; }
.badge-pengajar { background:#dbeafe; color:#1e40af; }
.badge-user     { background:#dcfce7; color:#15803d; }

.tbl-actions { display:flex; gap:6px; }
.tbl-btn { display:inline-flex; align-items:center; gap:5px; padding:5px 12px; border-radius:8px; font-size:11.5px; font-weight:600; font-family:'Poppins',sans-serif; border:none; cursor:pointer; text-decoration:none; transition:transform 0.2s,opacity 0.2s; }
.tbl-btn:hover { transform:translateY(-1px); opacity:0.85; }
.btn-edit   { background:#eff6ff; color:var(--accent); }
.btn-delete { background:#fff1f2; color:var(--red); }

.empty-state { text-align:center; padding:50px 20px; color:var(--muted); }
.empty-state i { font-size:40px; opacity:0.4; display:block; margin-bottom:12px; }
.empty-state p { font-size:13px; }

/* ── Form Panel ── */
.form-title { font-size:15px; font-weight:700; color:var(--navy); display:flex; align-items:center; gap:8px; }
.form-title i { color:var(--accent); }

.fg { display:flex; flex-direction:column; gap:6px; margin-bottom:18px; }
.fg label { font-size:12.5px; font-weight:700; color:#445; display:flex; align-items:center; gap:6px; }
.fg label i { font-size:12px; color:var(--accent); }
.fg input, .fg select {
  padding:11px 14px; border:1.8px solid var(--border); border-radius:12px;
  font-size:14px; font-family:'Poppins',sans-serif; color:var(--navy);
  background:#f8fafd; outline:none;
  transition:border-color 0.25s,box-shadow 0.25s,background 0.25s;
}
.fg input:focus, .fg select:focus { border-color:var(--accent); background:#fff; box-shadow:0 0 0 3px rgba(79,142,247,0.14); }
.fg .hint { font-size:11.5px; color:var(--muted); }

.form-body { padding:24px; }

.btn { display:inline-flex; align-items:center; gap:8px; padding:12px 24px; border-radius:12px; font-size:14px; font-weight:700; font-family:'Poppins',sans-serif; border:none; cursor:pointer; text-decoration:none; transition:transform 0.2s,box-shadow 0.2s; width:100%; justify-content:center; }
.btn:hover { transform:translateY(-2px); }
.btn-save   { background:linear-gradient(135deg,var(--blue),var(--accent)); color:#fff; box-shadow:0 6px 20px rgba(42,82,152,0.3); }
.btn-cancel { background:#f0f4ff; color:var(--blue); border:1.5px solid var(--border); margin-top:10px; }

/* ── Mode indicator ── */
.mode-badge { display:inline-flex; align-items:center; gap:6px; padding:4px 12px; border-radius:100px; font-size:12px; font-weight:700; }
.mode-add  { background:#dcfce7; color:#15803d; }
.mode-edit { background:#fef3c7; color:#92600a; }

/* Pagination */
.pag { display:flex; justify-content:flex-end; align-items:center; gap:8px; padding:14px 22px; border-top:1.5px solid var(--border); font-size:12.5px; color:var(--muted); }
</style>
</head>
<body>
<?php require INCLUDE_PATH . 'sidebar_mitigapro.php'; ?>

<div class="main-content" id="mainContent">

  <!-- Breadcrumb -->
  <div class="breadcrumb">
    <a href="db_mitigapro.php"><i class="fas fa-home"></i> Dashboard</a>
    <i class="fas fa-chevron-right" style="font-size:10px"></i>
    <span>Manajemen User</span>
  </div>

  <!-- Page Header -->
  <div class="page-header">
    <div>
      <h1><i class="fas fa-users-gear" style="color:var(--accent)"></i> Manajemen User</h1>
      <p>Tambah, edit, dan hapus akun pengguna sistem MitigaPro</p>
    </div>
    <a href="db_mitigapro.php" class="tbl-btn btn-edit" style="padding:10px 18px;font-size:13px">
      <i class="fas fa-arrow-left"></i> Kembali
    </a>
  </div>

  <?php if ($msg): ?>
    <div class="alert alert-<?= $msg_type ?>">
      <i class="fas fa-<?= $msg_type==='ok' ? 'check-circle' : 'exclamation-circle' ?>"></i>
      <?= htmlspecialchars($msg) ?>
    </div>
  <?php endif; ?>

  <div class="layout">

    <!-- ═══ TABEL USER ═══ -->
    <div class="panel">
      <div class="panel-header">
        <div class="panel-title"><i class="fas fa-table"></i> Daftar User</div>
        <span style="font-size:12px;color:var(--muted)"><?= $users->num_rows ?> user ditemukan</span>
      </div>

      <!-- Search -->
      <form method="GET" class="search-bar">
        <input type="text" name="q" placeholder="Cari username..." value="<?= htmlspecialchars($search) ?>">
        <button type="submit" class="btn-search"><i class="fas fa-search"></i> Cari</button>
        <?php if ($search): ?>
          <a href="manage_users.php" class="tbl-btn btn-edit" style="padding:10px 14px">✕ Reset</a>
        <?php endif; ?>
      </form>

      <?php if ($users->num_rows > 0): ?>
      <table class="data-table">
        <thead>
          <tr>
            <th>#</th>
            <th>Username</th>
            <th>Role</th>
            <th>Dibuat</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
        <?php $no = 1; while ($u = $users->fetch_assoc()): ?>
          <tr>
            <td style="color:var(--muted);font-size:12px"><?= $no++ ?></td>
            <td>
              <div class="td-name">
                <div class="td-avatar"><?= strtoupper(substr($u['username'],0,1)) ?></div>
                <div>
                  <div style="font-weight:600"><?= htmlspecialchars($u['username']) ?></div>
                  <?php if ((int)$u['id'] === (int)$_SESSION['user_id']): ?>
                    <div class="td-sub" style="color:var(--accent)">← Akun Anda</div>
                  <?php endif; ?>
                </div>
              </div>
            </td>
            <td><span class="badge badge-<?= $u['role'] ?>"><?= ucfirst($u['role']) ?></span></td>
            <td style="font-size:12px;color:var(--muted)"><?= date('d M Y', strtotime($u['created_at'])) ?></td>
            <td>
              <div class="tbl-actions">
                <a href="?edit=<?= $u['id'] ?><?= $search ? '&q='.urlencode($search) : '' ?>" class="tbl-btn btn-edit">
                  <i class="fas fa-pen"></i> Edit
                </a>
                <?php if ((int)$u['id'] !== (int)$_SESSION['user_id']): ?>
                  <a href="?delete=<?= $u['id'] ?>" class="tbl-btn btn-delete" onclick="return confirm('Hapus user \'<?= htmlspecialchars(addslashes($u['username'])) ?>\'?')">
                    <i class="fas fa-trash"></i>
                  </a>
                <?php endif; ?>
              </div>
            </td>
          </tr>
        <?php endwhile; ?>
        </tbody>
      </table>
      <div class="pag">Total: <?= $no-1 ?> user</div>
      <?php else: ?>
        <div class="empty-state"><i class="fas fa-users"></i><p>Tidak ada user ditemukan.</p></div>
      <?php endif; ?>
    </div>

    <!-- ═══ FORM TAMBAH / EDIT ═══ -->
    <div class="panel">
      <div class="panel-header">
        <div class="panel-title">
          <i class="fas fa-<?= $edit_user ? 'user-pen' : 'user-plus' ?>"></i>
          <?= $edit_user ? 'Edit User' : 'Tambah User Baru' ?>
        </div>
        <span class="mode-badge mode-<?= $edit_user ? 'edit' : 'add' ?>">
          <?= $edit_user ? '✏️ Edit Mode' : '➕ Tambah' ?>
        </span>
      </div>
      <div class="form-body">
        <form method="POST" action="">
          <?= csrf_field() ?>
          <input type="hidden" name="user_id" value="<?= $edit_user ? $edit_user['id'] : 0 ?>">

          <div class="fg">
            <label><i class="fas fa-user"></i> Username <span style="color:red;font-size:11px">*</span></label>
            <input type="text" name="username"
              placeholder="Nama pengguna (unik)"
              value="<?= htmlspecialchars($edit_user['username'] ?? $_POST['username'] ?? '') ?>"
              autocomplete="off" required>
          </div>

          <div class="fg">
            <label><i class="fas fa-lock"></i> Password <?= $edit_user ? '<span class="hint">(kosongkan jika tidak diubah)</span>' : '<span style="color:red;font-size:11px">*</span>' ?></label>
            <input type="password" name="password"
              placeholder="<?= $edit_user ? 'Kosongkan jika tidak ingin diubah' : 'Minimal 6 karakter' ?>"
              autocomplete="new-password"
              <?= !$edit_user ? 'required' : '' ?>>
          </div>

          <div class="fg">
            <label><i class="fas fa-shield-halved"></i> Role <span style="color:red;font-size:11px">*</span></label>
            <select name="role" required>
              <option value="pengajar" <?= (($edit_user['role'] ?? 'pengajar') === 'pengajar') ? 'selected' : '' ?>>Pengajar (Lihat Data)</option>
              <option value="admin"    <?= (($edit_user['role'] ?? '') === 'admin')    ? 'selected' : '' ?>>Admin (Kelola Semua)</option>
            </select>
            <div class="hint"><i class="fas fa-info-circle"></i> Admin: akses penuh, kelola semua data | Pengajar: hanya melihat data & berita</div>
          </div>

          <button type="submit" class="btn btn-save">
            <i class="fas fa-<?= $edit_user ? 'save' : 'user-plus' ?>"></i>
            <?= $edit_user ? 'Simpan Perubahan' : 'Tambah User' ?>
          </button>

          <?php if ($edit_user): ?>
            <a href="manage_users.php" class="btn btn-cancel">
              <i class="fas fa-times"></i> Batal Edit
            </a>
          <?php endif; ?>
        </form>
      </div>
    </div>

  </div>
</div>
</body>
</html>
