<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "siliak_pusat_db";

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Koneksi Database Pusat Gagal: " . mysqli_connect_error());
}

// Keamanan Dasbor (Password Akses)
define('MOTHERSHIP_PASS', 'adminliak123');

// Keamanan API (Kunci Rahasia penolak bot/spam)
define('API_SECRET_KEY', 'LIAK-SYNC-2026-X9');
?>
