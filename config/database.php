<?php
// =====================================
// KEAMANAN: SEMBUNYIKAN ERROR DARI PUBLIK
// =====================================
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

// =====================================
// KEAMANAN: SESSION COOKIES STRICT
// =====================================
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.use_strict_mode', 1);

// =====================================
// DETEKSI OTOMATIS LOCALHOST VS HOSTING
// =====================================
$is_localhost = (
    $_SERVER['HTTP_HOST'] == 'localhost' ||
    $_SERVER['HTTP_HOST'] == '127.0.0.1' ||
    strpos($_SERVER['HTTP_HOST'], '192.168.') === 0
);

if ($is_localhost) {
    // Kredensial Database Lokal (XAMPP)
    $host = "localhost";
    $user = "root";
    $pass = "";
    $db   = "siliak_pusat_db";
} else {
    // Kredensial Database Hosting — Ubah sesuai server!
    $host = "localhost";
    $user = "db_user_hosting";
    $pass = "db_pass_hosting";
    $db   = "siliak_pusat_db";
}

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    // Log ke server, jangan tampilkan ke browser
    error_log("[SI-LIAK PUSAT] Koneksi DB gagal: " . mysqli_connect_error());
    die("Terjadi kesalahan sistem. Silakan hubungi administrator.");
}

// =====================================
// LOAD SECRETS (password & API key sensitif)
// File ini di-.gitignore dan tidak boleh dicommit
// =====================================
$_secrets_file = __DIR__ . '/secrets.php';
if (file_exists($_secrets_file)) {
    require_once $_secrets_file;
} else {
    // Fallback sementara jika secrets.php belum ada
    // Segera buat file secrets.php untuk keamanan lebih baik
    define('API_SECRET_KEY', 'LIAK-SYNC-2026-X9');
    define('MOTHERSHIP_USER', 'superadmin');
    define('MOTHERSHIP_PASS_HASH', password_hash('adminliak123', PASSWORD_BCRYPT, ['cost' => 12]));
}
unset($_secrets_file);
?>
