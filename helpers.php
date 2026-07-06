<?php
// =====================================
// SI-LIAK PUSAT — Shared Helper Functions
// =====================================

/**
 * Generate AI insight berdasarkan metrik KSI dan pola kolaborasi.
 * (Dipindahkan dari index.php & print_report.php untuk menghindari duplikasi)
 */
if (!function_exists('generate_ai_insight')) {
    function generate_ai_insight($ksi, $ekspor, $impor, $internal) {
        // 1. Analisis Partisipasi
        if ($ksi >= 10) {
            $partisipasi_status = "Sangat Sehat";
            $partisipasi_teks   = "Partisipasi guru sangat aktif. Jadikan MGMP percontohan (Role Model).";
        } elseif ($ksi >= 4) {
            $partisipasi_status = "Sehat";
            $partisipasi_teks   = "Partisipasi berjalan baik dan stabil. Pertahankan momentum kolaborasi.";
        } else {
            $partisipasi_status = "Perlu Perhatian";
            $partisipasi_teks   = "Tingkat partisipasi minim. Perlu pendampingan intensif dari pengawas untuk memotivasi.";
        }

        // 2. Analisis Kolaborasi
        $total_luar = $ekspor + $impor;
        if ($ekspor == 0 && $impor == 0 && $internal == 0) {
            $kolaborasi_teks = "Tipe Pasif: Belum ada interaksi berbagi materi yang tercatat.";
        } elseif ($ekspor > $impor && $ekspor > $internal) {
            $kolaborasi_teks = "Tipe Produsen: Guru produktif membagikan materi ke sekolah lain. Berdayakan untuk menyusun modul standar kota.";
        } elseif ($impor > $ekspor && $impor > $internal) {
            $kolaborasi_teks = "Tipe Konsumen: Minat belajar tinggi (mengambil referensi luar), namun perlu distimulasi untuk produksi materi sendiri.";
        } elseif ($internal > $total_luar) {
            $kolaborasi_teks = "Tipe Terisolasi: Kolaborasi kuat, tapi hanya berputar di 1 sekolah. Gelar forum diskusi lintas sekolah.";
        } else {
            $kolaborasi_teks = "Tipe Seimbang: Pertukaran materi internal dan lintas sekolah berjalan seimbang.";
        }

        return [
            'status'  => $partisipasi_status,
            'insight' => $partisipasi_teks . " " . $kolaborasi_teks
        ];
    }
}

/**
 * Generate atau ambil CSRF token dari session.
 */
if (!function_exists('csrf_token')) {
    function csrf_token() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

/**
 * Verifikasi CSRF token dari POST request.
 * Menggunakan hash_equals() untuk mencegah timing attack.
 */
if (!function_exists('csrf_verify')) {
    function csrf_verify() {
        $token = $_POST['csrf_token'] ?? '';
        return !empty($token)
            && !empty($_SESSION['csrf_token'])
            && hash_equals($_SESSION['csrf_token'], $token);
    }
}

/**
 * Set security headers standar untuk semua halaman.
 */
if (!function_exists('set_security_headers')) {
    function set_security_headers() {
        header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' https://fonts.googleapis.com 'unsafe-inline'; font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com; img-src 'self' https: data:; object-src 'none'; frame-ancestors 'self';");
        header("X-Content-Type-Options: nosniff");
        header("X-Frame-Options: SAMEORIGIN");
        header("X-XSS-Protection: 1; mode=block");
        header("Referrer-Policy: strict-origin-when-cross-origin");
    }
}
?>
