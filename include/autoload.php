<?php
// ============================================================
//  MitigaPro — Auto Load (config, DB, constants)
// ============================================================

// ── Session (safe start) ─────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ── Database Configuration ───────────────────────────────────
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'mitigapro');

// ── Path Constants ───────────────────────────────────────────
//   $_SERVER['DOCUMENT_ROOT']  →  d:/truck/htdocs
//   Project root               →  d:/truck/htdocs/MitigaPro/
define('ROOT_PATH',    $_SERVER['DOCUMENT_ROOT'] . '/MitigaPro/');
define('INCLUDE_PATH', ROOT_PATH . 'include/');
define('UPLOAD_PATH',  ROOT_PATH . 'uploads/');

// ── URL Constant ─────────────────────────────────────────────
define('BASE_URL', '/MitigaPro/');

// ── Database Connection ──────────────────────────────────────
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die("
    <div style='font-family:\"Poppins\",sans-serif;padding:40px;max-width:600px;margin:60px auto;
                background:#fff5f5;border:2px solid #e53e3e;border-radius:12px;'>
      <h2 style='color:#e53e3e;margin:0 0 10px'>❌ Koneksi Database Gagal</h2>
      <p style='color:#555;margin:0 0 8px'>Error: <strong>" . htmlspecialchars($conn->connect_error) . "</strong></p>
      <p style='color:#555;margin:0 0 15px'>Pastikan:</p>
      <ul style='color:#555;margin:0;padding-left:20px'>
        <li>Server MySQL / MariaDB sedang berjalan</li>
        <li>Database <strong>" . DB_NAME . "</strong> sudah dibuat</li>
        <li>Username &amp; password di <code>include/autoload.php</code> sudah benar</li>
      </ul>
      <p style='margin-top:15px'>
        Jalankan file <a href='" . BASE_URL . "database_setup.sql' download style='color:#3182ce'>database_setup.sql</a>
        di phpMyAdmin untuk membuat semua tabel.
      </p>
    </div>
    ");
}

$conn->set_charset('utf8mb4');

// ── CSRF Helper ──────────────────────────────────────────────
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token()) . '">';
}

function csrf_verify(): bool {
    return isset($_POST['csrf_token']) && hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token']);
}

// ── Role Helper ──────────────────────────────────────────────
/**
 * Redirect away if current user doesn't have the required role.
 * Usage: require_role('pengajar');
 */
function require_role(string ...$allowed): void {
    $role = $_SESSION['role'] ?? '';
    if (!in_array($role, $allowed, true)) {
        header('Location: ' . BASE_URL . 'pengajar/dashboard.php');
        exit;
    }
}

// ── Breadcrumb Helper ────────────────────────────────────────
function breadcrumb(array $items): string {
    $base = '/MitigaPro/pengajar/';
    $html = '<nav class="breadcrumb"><a href="' . $base . 'dashboard.php"><i class="fas fa-home"></i></a>';
    $last = count($items) - 1;
    foreach ($items as $i => $item) {
        $html .= '<span class="bc-sep"><i class="fas fa-chevron-right"></i></span>';
        if ($i === $last || empty($item['url'])) {
            $html .= '<span class="bc-current">' . htmlspecialchars($item['label']) . '</span>';
        } else {
            $html .= '<a href="' . $item['url'] . '">' . htmlspecialchars($item['label']) . '</a>';
        }
    }
    return $html . '</nav>';
}
