<?php
require $_SERVER['DOCUMENT_ROOT'] . '/MitigaPro/include/autoload.php';

if (!isset($_SESSION['role'])) {
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}

require INCLUDE_PATH . 'sidebar_pengajar.php';

// Filter tahun
$tahun_filter = isset($_GET['tahun']) ? intval($_GET['tahun']) : 0;

// Ambil daftar tahun yang ada
$q_tahun = $conn->query("SELECT DISTINCT tahun FROM identifikasi_pelatihan ORDER BY tahun DESC");
$tahun_list = [];
while ($t = $q_tahun->fetch_assoc()) $tahun_list[] = $t['tahun'];

// Total pelatihan
$where = $tahun_filter ? " AND p.tahun = $tahun_filter" : "";

$total_pelatihan = $conn->query("SELECT COUNT(*) AS c FROM identifikasi_pelatihan p INNER JOIN dinas d ON p.dinas_id=d.id WHERE 1=1 $where")->fetch_assoc()['c'];
$total_dinas = $conn->query("SELECT COUNT(DISTINCT p.dinas_id) AS c FROM identifikasi_pelatihan p INNER JOIN dinas d ON p.dinas_id=d.id WHERE 1=1 $where")->fetch_assoc()['c'];
$total_wilayah = $conn->query("SELECT COUNT(DISTINCT d.wilayah_id) AS c FROM identifikasi_pelatihan p INNER JOIN dinas d ON p.dinas_id=d.id WHERE 1=1 $where")->fetch_assoc()['c'];
$total_jenis = $conn->query("SELECT COUNT(DISTINCT p.jenis_pelatihan) AS c FROM identifikasi_pelatihan p INNER JOIN dinas d ON p.dinas_id=d.id WHERE 1=1 $where")->fetch_assoc()['c'];

// Pelatihan per wilayah (untuk bar chart)
$q_per_wilayah = $conn->query("
    SELECT w.nama_wilayah, COUNT(p.id) AS jumlah
    FROM identifikasi_pelatihan p
    INNER JOIN dinas d ON p.dinas_id = d.id
    INNER JOIN wilayah w ON d.wilayah_id = w.id
    WHERE 1=1 $where
    GROUP BY w.id, w.nama_wilayah
    ORDER BY jumlah DESC
");
$wil_labels = [];
$wil_data = [];
while ($r = $q_per_wilayah->fetch_assoc()) {
    $wil_labels[] = str_replace('Wilayah Kerja ', '', $r['nama_wilayah']);
    $wil_data[] = (int)$r['jumlah'];
}

// Top 10 jenis pelatihan (untuk horizontal bar)
$q_top_jenis = $conn->query("
    SELECT p.jenis_pelatihan, COUNT(*) AS jumlah
    FROM identifikasi_pelatihan p
    INNER JOIN dinas d ON p.dinas_id = d.id
    WHERE 1=1 $where
    GROUP BY p.jenis_pelatihan
    ORDER BY jumlah DESC
    LIMIT 10
");
$jenis_labels = [];
$jenis_data = [];
while ($r = $q_top_jenis->fetch_assoc()) {
    $label = $r['jenis_pelatihan'];
    $jenis_labels[] = mb_strlen($label) > 35 ? mb_substr($label, 0, 35) . '...' : $label;
    $jenis_data[] = (int)$r['jumlah'];
}

// Pelatihan per tahun (untuk line chart)
$q_per_tahun = $conn->query("
    SELECT tahun, COUNT(*) AS jumlah
    FROM identifikasi_pelatihan
    GROUP BY tahun
    ORDER BY tahun ASC
");
$thn_labels = [];
$thn_data = [];
while ($r = $q_per_tahun->fetch_assoc()) {
    $thn_labels[] = (string)$r['tahun'];
    $thn_data[] = (int)$r['jumlah'];
}

// Daftar pelatihan terbaru
$q_recent = $conn->query("
    SELECT p.jenis_pelatihan, p.tahun, p.created_at, d.nama_dinas, w.nama_wilayah
    FROM identifikasi_pelatihan p
    INNER JOIN dinas d ON p.dinas_id = d.id
    INNER JOIN wilayah w ON d.wilayah_id = w.id
    WHERE 1=1 $where
    ORDER BY p.created_at DESC
    LIMIT 10
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard Pelatihan | MitigaPro</title>
<link rel="stylesheet" href="<?= BASE_URL ?>1_css/sidebar_pengajar.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{--navy:#1a2744;--blue:#2c5282;--accent:#3b82f6;--green:#22c55e;--orange:#f59e0b;--purple:#8b5cf6;--cyan:#06b6d4;--bg:#f5f7fb;--white:#fff;--border:#e2e8f0;--muted:#64748b;--radius:12px}
body{font-family:'Poppins',sans-serif;background:var(--bg);color:var(--navy)}

.wrap{max-width:1100px;margin:0 auto;padding:30px 24px 60px}

/* Header */
.page-header{background:linear-gradient(135deg,var(--navy),var(--blue));border-radius:var(--radius);padding:28px 32px;color:#fff;margin-bottom:24px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px}
.page-header h1{font-size:20px;font-weight:700;display:flex;align-items:center;gap:10px}
.page-header p{font-size:12px;opacity:.7;margin-top:4px}
.filter-form{display:flex;align-items:center;gap:8px}
.filter-form select{padding:8px 14px;border-radius:8px;border:none;font-size:12px;font-family:'Poppins',sans-serif;font-weight:600;cursor:pointer}
.filter-form .btn-filter{padding:8px 16px;border-radius:8px;background:rgba(255,255,255,.2);color:#fff;border:1px solid rgba(255,255,255,.3);font-size:12px;font-weight:600;font-family:'Poppins',sans-serif;cursor:pointer;transition:background .2s}
.filter-form .btn-filter:hover{background:rgba(255,255,255,.3)}
.btn-export-header{display:inline-flex;align-items:center;gap:6px;padding:8px 18px;border-radius:8px;background:rgba(255,255,255,.2);color:#fff;border:1px solid rgba(255,255,255,.3);font-size:12px;font-weight:600;font-family:'Poppins',sans-serif;text-decoration:none;transition:background .2s}
.btn-export-header:hover{background:rgba(255,255,255,.35)}

/* Stat Cards */
.stats{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:24px}
@media(max-width:768px){.stats{grid-template-columns:repeat(2,1fr)}}
@media(max-width:480px){.stats{grid-template-columns:1fr}}
.stat-card{background:var(--white);border-radius:var(--radius);border:1px solid var(--border);padding:20px;display:flex;align-items:center;gap:16px}
.stat-icon{width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:20px;flex-shrink:0}
.stat-icon.blue{background:#eff6ff;color:var(--accent)}
.stat-icon.green{background:#ecfdf5;color:var(--green)}
.stat-icon.orange{background:#fffbeb;color:var(--orange)}
.stat-icon.purple{background:#f5f3ff;color:var(--purple)}
.stat-value{font-size:24px;font-weight:700;line-height:1}
.stat-label{font-size:11px;color:var(--muted);margin-top:2px}

/* Charts Grid */
.charts-grid{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:24px}
@media(max-width:768px){.charts-grid{grid-template-columns:1fr}}

.card{background:var(--white);border-radius:var(--radius);border:1px solid var(--border);padding:24px}
.card-title{font-size:14px;font-weight:700;color:var(--navy);margin-bottom:16px;padding-bottom:10px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:8px}
.card-title i{color:var(--accent);font-size:13px}
.card.full{grid-column:span 2}
@media(max-width:768px){.card.full{grid-column:span 1}}

/* Table */
.tbl{width:100%;border-collapse:collapse;font-size:12px}
.tbl th{background:var(--bg);padding:10px 12px;text-align:left;font-weight:600;font-size:11px;text-transform:uppercase;color:var(--muted);letter-spacing:.5px}
.tbl td{padding:10px 12px;border-top:1px solid var(--border)}
.tbl tr:hover td{background:#f8fafc}
.badge{display:inline-block;padding:2px 10px;border-radius:12px;font-size:10px;font-weight:600;background:#eff6ff;color:var(--accent)}

.empty-state{text-align:center;padding:40px;color:var(--muted)}
.empty-state i{font-size:32px;opacity:.3;display:block;margin-bottom:8px}
</style>
</head>
<body>

<div id="mainContent" class="main-content">
<div class="wrap">
    <?= breadcrumb([['label' => 'Pelatihan']]) ?>

    <!-- Header + Filter -->
    <div class="page-header">
        <div>
            <h1><i class="fas fa-graduation-cap"></i> Dashboard Pelatihan</h1>
            <p>Ringkasan identifikasi kebutuhan pelatihan seluruh wilayah</p>
        </div>
        <form class="filter-form" method="GET">
            <select name="tahun">
                <option value="0">Semua Tahun</option>
                <?php foreach ($tahun_list as $t): ?>
                <option value="<?= $t ?>" <?= $tahun_filter == $t ? 'selected' : '' ?>><?= $t ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn-filter"><i class="fas fa-filter"></i> Filter</button>
        </form>
        <a href="export_pelatihan.php?tahun=<?= $tahun_filter ?>" class="btn-export-header" title="Ekspor ke CSV">
            <i class="fas fa-file-csv"></i> Ekspor CSV
        </a>
    </div>

    <!-- Stat Cards -->
    <div class="stats">
        <div class="stat-card">
            <div class="stat-icon blue"><i class="fas fa-chalkboard-teacher"></i></div>
            <div>
                <div class="stat-value"><?= $total_pelatihan ?></div>
                <div class="stat-label">Total Pelatihan</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green"><i class="fas fa-building"></i></div>
            <div>
                <div class="stat-value"><?= $total_dinas ?></div>
                <div class="stat-label">Dinas Terlibat</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon orange"><i class="fas fa-map-marked-alt"></i></div>
            <div>
                <div class="stat-value"><?= $total_wilayah ?></div>
                <div class="stat-label">Wilayah Aktif</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon purple"><i class="fas fa-tags"></i></div>
            <div>
                <div class="stat-value"><?= $total_jenis ?></div>
                <div class="stat-label">Jenis Pelatihan</div>
            </div>
        </div>
    </div>

    <!-- Charts -->
    <div class="charts-grid">
        <!-- Pelatihan per Wilayah -->
        <div class="card">
            <div class="card-title"><i class="fas fa-chart-bar"></i> Pelatihan per Wilayah</div>
            <?php if (count($wil_labels) > 0): ?>
            <canvas id="chartWilayah" height="220"></canvas>
            <?php else: ?>
            <div class="empty-state"><i class="fas fa-chart-bar"></i><p>Belum ada data</p></div>
            <?php endif; ?>
        </div>

        <!-- Pelatihan per Tahun -->
        <div class="card">
            <div class="card-title"><i class="fas fa-chart-line"></i> Tren per Tahun</div>
            <?php if (count($thn_labels) > 0): ?>
            <canvas id="chartTahun" height="220"></canvas>
            <?php else: ?>
            <div class="empty-state"><i class="fas fa-chart-line"></i><p>Belum ada data</p></div>
            <?php endif; ?>
        </div>

        <!-- Top Jenis Pelatihan -->
        <div class="card full">
            <div class="card-title"><i class="fas fa-trophy"></i> Top 10 Jenis Pelatihan</div>
            <?php if (count($jenis_labels) > 0): ?>
            <canvas id="chartJenis" height="180"></canvas>
            <?php else: ?>
            <div class="empty-state"><i class="fas fa-trophy"></i><p>Belum ada data</p></div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Recent Table -->
    <div class="card">
        <div class="card-title"><i class="fas fa-clock"></i> Pelatihan Terbaru</div>
        <?php if ($q_recent->num_rows > 0): ?>
        <table class="tbl">
            <thead>
                <tr>
                    <th>Jenis Pelatihan</th>
                    <th>Dinas</th>
                    <th>Wilayah</th>
                    <th>Tahun</th>
                    <th>Ditambahkan</th>
                </tr>
            </thead>
            <tbody>
            <?php while ($r = $q_recent->fetch_assoc()): ?>
                <tr>
                    <td style="font-weight:600"><?= htmlspecialchars($r['jenis_pelatihan']) ?></td>
                    <td><?= htmlspecialchars($r['nama_dinas']) ?></td>
                    <td><?= htmlspecialchars(str_replace('Wilayah Kerja ', '', $r['nama_wilayah'])) ?></td>
                    <td><span class="badge"><?= $r['tahun'] ?></span></td>
                    <td style="color:var(--muted);font-size:11px"><?= date('d M Y', strtotime($r['created_at'])) ?></td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="empty-state"><i class="fas fa-inbox"></i><p>Belum ada data pelatihan<?= $tahun_filter ? " untuk tahun $tahun_filter" : '' ?>.</p></div>
        <?php endif; ?>
    </div>

</div>
</div>

<script>
const colors = {
    blue: '#3b82f6', navy: '#1a2744', green: '#22c55e',
    orange: '#f59e0b', purple: '#8b5cf6', cyan: '#06b6d4', red: '#ef4444',
    blueBg: 'rgba(59,130,246,0.15)', greenBg: 'rgba(34,197,94,0.15)'
};
const fontOpts = { family: 'Poppins', size: 11 };
const defaultOpts = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: {
        x: { ticks: { font: fontOpts }, grid: { display: false } },
        y: { ticks: { font: fontOpts, precision: 0 }, grid: { color: '#f1f5f9' } }
    }
};

<?php if (count($wil_labels) > 0): ?>
new Chart(document.getElementById('chartWilayah'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($wil_labels) ?>,
        datasets: [{
            data: <?= json_encode($wil_data) ?>,
            backgroundColor: [colors.blue, colors.green, colors.orange, colors.purple, colors.cyan, colors.red, colors.navy],
            borderRadius: 6,
            barThickness: 28
        }]
    },
    options: defaultOpts
});
<?php endif; ?>

<?php if (count($thn_labels) > 0): ?>
new Chart(document.getElementById('chartTahun'), {
    type: 'line',
    data: {
        labels: <?= json_encode($thn_labels) ?>,
        datasets: [{
            data: <?= json_encode($thn_data) ?>,
            borderColor: colors.blue,
            backgroundColor: colors.blueBg,
            fill: true,
            tension: 0.4,
            pointRadius: 5,
            pointBackgroundColor: colors.blue
        }]
    },
    options: defaultOpts
});
<?php endif; ?>

<?php if (count($jenis_labels) > 0): ?>
new Chart(document.getElementById('chartJenis'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($jenis_labels) ?>,
        datasets: [{
            data: <?= json_encode($jenis_data) ?>,
            backgroundColor: colors.blue,
            borderRadius: 4,
            barThickness: 18
        }]
    },
    options: {
        ...defaultOpts,
        indexAxis: 'y',
        scales: {
            x: { ticks: { font: fontOpts, precision: 0 }, grid: { color: '#f1f5f9' } },
            y: { ticks: { font: { ...fontOpts, size: 10 } }, grid: { display: false } }
        }
    }
});
<?php endif; ?>
</script>

</body>
</html>
