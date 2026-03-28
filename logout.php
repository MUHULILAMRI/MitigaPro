<?php
// logout.php — Hapus sesi & arahkan ke login
require $_SERVER['DOCUMENT_ROOT'] . '/MitigaPro/include/autoload.php';
session_unset();
session_destroy();
header('Location: ' . BASE_URL . 'login.php');
exit;
