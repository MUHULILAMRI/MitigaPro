  <?php
require $_SERVER['DOCUMENT_ROOT'] . '/MitigaPro/include/autoload.php';

  if (!isset($_SESSION['role'])) {
      header("Location: login.php");
      exit;
  }

  $role = $_SESSION['role'] ?? null;

  require INCLUDE_PATH . 'sidebar_pengajar.php';

  // Fitur Search & Pagination
  $search = isset($_GET['search']) ? $_GET['search'] : '';
  $page = max(1, intval($_GET['page'] ?? 1));
  $per_page = 10;
  $offset = ($page - 1) * $per_page;

  // Count total
  $searchTerm = "%$search%";
  $count_stmt = $conn->prepare("SELECT COUNT(*) as total FROM pengajar WHERE nama_pengajar LIKE ? OR nip LIKE ?");
  $count_stmt->bind_param("ss", $searchTerm, $searchTerm);
  $count_stmt->execute();
  $total_filtered = $count_stmt->get_result()->fetch_assoc()['total'];
  $count_stmt->close();
  $total_pages = max(1, ceil($total_filtered / $per_page));

  // Fetch page
  $query = "SELECT * FROM pengajar WHERE nama_pengajar LIKE ? OR nip LIKE ? ORDER BY created_at DESC LIMIT ? OFFSET ?";
  $stmt = $conn->prepare($query);
  $stmt->bind_param("ssii", $searchTerm, $searchTerm, $per_page, $offset);
  $stmt->execute();
  $result = $stmt->get_result();

  // Statistik mini
  $total_query = $conn->query("SELECT COUNT(*) AS total FROM pengajar");
  $total_pengajar = $total_query->fetch_assoc()['total'] ?? 0;

  $aktif_query = $conn->query("SELECT COUNT(*) AS aktif FROM pengajar WHERE status='aktif'");
  $aktif_pengajar = $aktif_query->fetch_assoc()['aktif'] ?? 0;

  $nonaktif_query = $conn->query("SELECT COUNT(*) AS nonaktif FROM pengajar WHERE status='nonaktif'");
  $nonaktif_pengajar = $nonaktif_query->fetch_assoc()['nonaktif'] ?? 0;

  // menentukan foto
  $foto = (!empty($data['foto'])) ? $data['foto'] : 'default.jpg';
  ?>


  <!DOCTYPE html>
  <html lang="id">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Data Pengajar | Dashboard Pelatihan</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>1_css/sidebar_pengajar.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>1_css/footer.css">
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
/* =====================
   GLOBAL STYLE
====================== */
html, body {
    width: 100%;
    min-height: 100vh;
    margin: 0;
    padding: 0;
    background-color: #f4f7fb;
    font-family: 'Poppins', sans-serif;
    color: #2c3e50;
    box-sizing: border-box;
    overflow-x: hidden;
}

.wrapper {
    display: flex;
    flex-direction: column;
    min-height: 100vh;
}

.content {
    flex: 1;
    padding: 20px;
}


/* =====================
   CONTAINER
====================== */
.container {
    width: 95%;
    max-width: 1100px;
    margin: 30px auto;
    padding: clamp(20px, 3vw, 40px);
    background: white;
    border-radius: 16px;
    box-shadow: 0 6px 20px rgba(0,0,0,0.08);
    animation: fadeIn 0.6s ease;
}

/* =====================
   PAGE TITLE
====================== */
h2 {
    text-align: center;
    color: #225f9c;
    margin-bottom: 30px;
    font-weight: 600;
}

/* =====================
   HIGHLIGHT SECTION
====================== */
.highlight-section {
    display: flex;
    justify-content: center;
    flex-wrap: wrap;
    gap: 100px;
    margin: 30px 0 40px 0;
}

/* Highlight Card (modern version) */
.highlight-card {
    flex: 1 1 280px;
    max-width: 120px;
    background: linear-gradient(145deg, #ffffff, #f0f4fa);
    padding: 25px;
    border-radius: 16px;
    text-align: center;
    box-shadow: 0 6px 18px rgba(0,0,0,0.08);
    transition: 0.3s;
    position: relative;
}

.highlight-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 24px rgba(0,0,0,0.1);
}

.highlight-card h3 {
    font-size: 26px;
    font-weight: 700;
    color: #1b4d7a;
}

.highlight-card span {
    font-size: 15px;
    color: #4a4a4a;
}

/* =====================
   SEARCH BAR
====================== */
.search-bar {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    margin-bottom: 25px;
    justify-content: space-between;
}

.search-bar input {
    flex: 1;
    min-width: 220px;
    padding: 12px 15px;
    border-radius: 10px;
    border: 1.5px solid #cdd7e3;
    font-size: 15px;
    outline: none;
    transition: 0.3s;
}

.search-bar input:focus {
    border-color: #225f9c;
    box-shadow: 0 0 5px rgba(34,95,156,0.3);
}

.search-bar button,
.search-bar .btn-tambah {
    padding: 12px 22px;
    border: none;
    border-radius: 10px;
    background-color: #1b4d7a;
    color: white;
    font-weight: 600;
    cursor: pointer;
    transition: 0.3s;
}

.search-bar button:hover,
.search-bar .btn-tambah:hover {
    background-color: #153a5c;
}

.btn-tambah {
    background-color: #2e7d32;
}

.btn-export {
    padding: 12px 22px;
    border: none;
    border-radius: 10px;
    background-color: #2c5282;
    color: white;
    font-weight: 600;
    cursor: pointer;
    transition: 0.3s;
    text-decoration: none;
    font-size: 15px;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.btn-export:hover {
    background-color: #1a3a5c;
}

.btn-tambah:hover {
    background-color: #256928;
}

/* =====================
   TABLE
====================== */
.table-container {
    width: 100%;
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
    font-size: 15px;
    border-radius: 12px;
    overflow: hidden;
}

thead {
    background-color: #225f9c;
    color: white;
}

thead th {
    padding: 14px 10px;
    font-weight: 600;
}

tbody tr {
    border-bottom: 1px solid #e0e6ef;
    transition: 0.3s;
}

tbody tr:hover {
    background-color: #f5f9ff;
}

tbody td {
    padding: 12px 10px;
    vertical-align: middle;
}

.foto-mini {
    width: 45px;
    height: 45px;
    border-radius: 10px;
    object-fit: cover;
    border: 2px solid #e2eaf4;
}

/* =====================
   STATUS BADGE
====================== */
.status {
    display: inline-block;
    padding: 6px 12px;
    border-radius: 12px;
    font-size: 13px;
    font-weight: 600;
    text-transform: capitalize;
}

.status.aktif {
    background: #e6f4ea;
    color: #2e7d32;
}

.status.nonaktif {
    background: #fdecea;
    color: #c62828;
}

/* =====================
   TOMBOL AKSI
====================== */
td.aksi {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 8px;
    white-space: nowrap;
    flex-shrink: 0;
}

.btn-detail, .btn-edit, .btn-hapus {
    padding: 6px 12px;
    border-radius: 6px;
    text-decoration: none;
    color: #fff;
    font-size: 13px;
    font-weight: 600;
    transition: 0.3s;
}

.btn-detail { background-color: #3498db; }
.btn-edit   { background-color: #f1c40f; color: #000; }
.btn-hapus  { background-color: #e74c3c; }

.btn-detail:hover { background-color: #2176bd; }
.btn-edit:hover   { background-color: #d4ac0d; }
.btn-hapus:hover  { background-color: #c0392b; }

/* =====================
   RESPONSIVE
====================== */
@media (max-width: 768px) {
    .highlight-card {
        flex: 1 1 100%;
        max-width: 100%;
    }

    td.aksi {
        flex-direction: column;
        gap: 5px;
    }

    .btn-detail, .btn-edit, .btn-hapus {
        width: 100%;
        text-align: center;
    }
}

  </style>
  </head>
  <body>
      <div id="mainContent" class="main-content">
      <!-- Konten -->
      <div class="content">
        <div class="container">
          <?= breadcrumb([['label' => 'Data Pengajar']]) ?>
          <h2>Data Pengajar</h2>
          <p class="page-desc">Halaman ini menampilkan daftar pengajar pelatihan. Gunakan kolom pencarian untuk mencari berdasarkan NIP atau Nama Lengkap.</p>

          <div class="alert-info">
            <strong>Panduan Pengisian:</strong>
            <ul>
              <li>Gunakan NIP resmi untuk pegawai PNS.</li>
              <li>Pastikan foto profil berukuran persegi (1:1) dan maksimal 2MB.</li>
              <li>Status “Aktif” hanya untuk pengajar yang masih mengajar.</li>
            </ul>
          </div>

  <!-- Highlight modern dengan chart kecil -->
  <div class="highlight-section">
    <div class="highlight-card">
        <i class="fas fa-users"></i>
        <canvas id="chartTotal"></canvas>
        <h3><?= $total_pengajar; ?></h3>
        <span>Total Pengajar</span>
    </div>
    <div class="highlight-card">
        <i class="fas fa-user-check"></i>
        <canvas id="chartAktif"></canvas>
        <h3><?= $aktif_pengajar; ?></h3>
        <span>Pengajar Aktif</span>
    </div>
    <div class="highlight-card">
        <i class="fas fa-user-times"></i>
        <canvas id="chartNonaktif"></canvas>
        <h3><?= $nonaktif_pengajar; ?></h3>
        <span>Pengajar Nonaktif</span>
    </div>
  </div>

          <!-- Search Bar -->
          <form method="GET" class="search-bar">
            <input type="text" name="search" placeholder="Cari berdasarkan NIP atau Nama Lengkap Pengajar..." value="<?= htmlspecialchars($search) ?>">
            <button type="submit">Cari</button>
            <a href="export_pengajar.php?search=<?= urlencode($search) ?>" class="btn-export" title="Ekspor ke CSV">
              <i class="fas fa-file-csv"></i> Ekspor CSV
            </a>
            <?php if ($role === 'admin'): ?>
                <a href="pengajar_add.php" class="btn-tambah">+ Tambah Pengajar</a>
            <?php endif; ?>
          </form>

          <!-- Tabel Data -->
          <div class="table-container">
            <table>
              <thead>
                <tr>
                  <th>No</th>
                  <th>Foto</th>
                  <th>NIP</th>
                  <th>Nama Pengajar</th>
                  <th>Jabatan</th>
                  <th>Unit Kerja</th>
                  <th>Status</th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody>
                <?php if ($result->num_rows > 0): 
                  $no = 1;
                  while ($row = $result->fetch_assoc()): ?>
                  <tr>
                    <td><?= $no++; ?></td>
                    <td> <img src="<?= BASE_URL ?>uploads/pengajar/<?= $row['foto'] ?: 'default.png'; ?>" 
                              alt="Foto Pengajar <?= htmlspecialchars($row['nama_pengajar']); ?>" 
                              class="foto-mini">
                    </td>
                    <td><?= htmlspecialchars($row['nip']); ?></td>
                    <td><?= htmlspecialchars($row['nama_pengajar']); ?></td>
                    <td><?= htmlspecialchars($row['jabatan']); ?></td>
                    <td><?= htmlspecialchars($row['unit_kerja']); ?></td>
                    <td><span class="status <?= $row['status']; ?>"><?= ucfirst($row['status']); ?></span></td>
                    <td class="aksi">
                      <a href="pengajar_view.php?nip=<?= $row['nip']; ?>" class="btn-detail">Lihat</a>
                      <?php if ($role === 'admin'): ?>
                        <a href="pengajar_edit.php?nip=<?= $row['nip']; ?>" class="btn-edit">Edit</a>
                        <a href="pengajar_hapus.php?nip=<?= $row['nip']; ?>" class="btn-hapus" onclick="return confirm('Yakin ingin menghapus data ini?')">Hapus</a>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endwhile; else: ?>
                  <tr><td colspan="8" class="no-data">Tidak ada data ditemukan.</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>

          <!-- Pagination -->
          <?php if ($total_pages > 1): ?>
          <div style="display:flex;align-items:center;justify-content:space-between;padding:16px 20px;border-top:1px solid #e5e7eb;font-family:'Poppins',sans-serif">
            <span style="font-size:12px;color:#64748b">
              Halaman <?= $page ?> dari <?= $total_pages ?> (<?= $total_filtered ?> data)
            </span>
            <div style="display:flex;gap:4px">
              <?php if ($page > 1): ?>
                <a href="?search=<?= urlencode($search) ?>&page=<?= $page-1 ?>" style="padding:6px 12px;border-radius:6px;border:1px solid #e2e8f0;text-decoration:none;font-size:12px;color:#2c5282">&laquo; Prev</a>
              <?php endif; ?>
              <?php
              $start_p = max(1, $page - 2);
              $end_p = min($total_pages, $page + 2);
              for ($i = $start_p; $i <= $end_p; $i++): ?>
                <a href="?search=<?= urlencode($search) ?>&page=<?= $i ?>"
                   style="padding:6px 12px;border-radius:6px;text-decoration:none;font-size:12px;<?= $i===$page ? 'background:#2c5282;color:#fff;border:1px solid #2c5282' : 'border:1px solid #e2e8f0;color:#2c5282' ?>"><?= $i ?></a>
              <?php endfor; ?>
              <?php if ($page < $total_pages): ?>
                <a href="?search=<?= urlencode($search) ?>&page=<?= $page+1 ?>" style="padding:6px 12px;border-radius:6px;border:1px solid #e2e8f0;text-decoration:none;font-size:12px;color:#2c5282">Next &raquo;</a>
              <?php endif; ?>
            </div>
          </div>
          <?php endif; ?>

        </div>
      </div>  

    <script>
    // Chart Total Pengajar
    new Chart(document.getElementById('chartTotal'), {
      type: 'doughnut',
      data: {
        labels: ['Aktif', 'Nonaktif'],
        datasets: [{
          data: [<?= $aktif_pengajar; ?>, <?= $nonaktif_pengajar; ?>],
          backgroundColor: ['#1b4d7a', '#cfd8dc'],
          borderWidth: 0
        }]
      },
      options: { plugins: { legend: { display: false } }, cutout: '75%' }
    });

    // Chart Aktif
    new Chart(document.getElementById('chartAktif'), {
      type: 'doughnut',
      data: {
        labels: ['Aktif', 'Total'],
        datasets: [{
          data: [<?= $aktif_pengajar; ?>, <?= max($total_pengajar - $aktif_pengajar, 0); ?>],
          backgroundColor: ['#2e7d32', '#c8e6c9'],
          borderWidth: 0
        }]
      },
      options: { plugins: { legend: { display: false } }, cutout: '75%' }
    });
    
    // Chart Nonaktif
    new Chart(document.getElementById('chartNonaktif'), {
      type: 'doughnut',
      data: {
        labels: ['Nonaktif', 'Total'],
        datasets: [{
          data: [<?= $nonaktif_pengajar; ?>, <?= max($total_pengajar - $nonaktif_pengajar, 0); ?>],
          backgroundColor: ['#d32f2f', '#ffcdd2'],
          borderWidth: 0
        }]
      },
      options: { plugins: { legend: { display: false } }, cutout: '75%' }
    });

  // Ambil semua link dalam topbar
  const menuLinks = document.querySelectorAll('.topbar a');

  // Loop setiap link
  menuLinks.forEach(link => {
    link.addEventListener('click', () => {
      // Hapus class 'active' dari semua link
      menuLinks.forEach(item => item.classList.remove('active'));
      
      // Tambahkan class 'active' ke link yang diklik
      link.classList.add('active');
    });
  });
  </script>
  </div><!-- /main-content -->
  </body>
  </html>
