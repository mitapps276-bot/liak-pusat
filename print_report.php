<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['mothership_logged_in']) || $_SESSION['mothership_logged_in'] !== true) {
    echo "Unauthorized";
    exit;
}

if (!isset($_GET['mgmp_id'])) {
    echo "Invalid MGMP ID";
    exit;
}

$mgmp_id = mysqli_real_escape_string($conn, $_GET['mgmp_id']);
$query = mysqli_query($conn, "SELECT * FROM national_telemetry WHERE mgmp_id = '$mgmp_id'");

if (mysqli_num_rows($query) == 0) {
    echo "Data not found";
    exit;
}

$row = mysqli_fetch_assoc($query);

function generate_ai_insight($ksi, $ekspor, $impor, $internal) {
    $partisipasi_status = "";
    if ($ksi >= 10) {
        $partisipasi_status = "Sangat Sehat";
        $partisipasi_teks = "Partisipasi guru sangat aktif. Jadikan MGMP percontohan (Role Model).";
    } elseif ($ksi >= 4) {
        $partisipasi_status = "Sehat";
        $partisipasi_teks = "Partisipasi berjalan baik dan stabil. Pertahankan momentum kolaborasi.";
    } else {
        $partisipasi_status = "Perlu Perhatian";
        $partisipasi_teks = "Tingkat partisipasi minim. Perlu pendampingan intensif dari pengawas untuk memotivasi.";
    }

    $kolaborasi_teks = "";
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
        'status' => $partisipasi_status,
        'insight' => $partisipasi_teks . " " . $kolaborasi_teks
    ];
}

$ai_data = generate_ai_insight($row['ksi_score'], $row['cs_ekspor'], $row['cs_impor'], $row['cs_internal']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Kinerja - <?= htmlspecialchars($row['mgmp_name']) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            color: #111827;
            background: #fff;
            padding: 40px;
            max-width: 800px;
            margin: 0 auto;
            line-height: 1.6;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            color: #1f2937;
        }
        .header p {
            margin: 5px 0 0;
            color: #6b7280;
        }
        .report-section {
            margin-bottom: 30px;
        }
        .report-section h2 {
            font-size: 18px;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 10px;
            margin-bottom: 15px;
            color: #374151;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            text-align: left;
            padding: 12px;
            border-bottom: 1px solid #f3f4f6;
        }
        th {
            width: 40%;
            color: #6b7280;
            font-weight: 600;
        }
        .insight-box {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-left: 4px solid #3b82f6;
            padding: 20px;
            border-radius: 4px;
        }
        .insight-status {
            font-weight: 700;
            font-size: 16px;
            margin-bottom: 10px;
            color: #1e40af;
        }
        @media print {
            body { padding: 0; }
            button { display: none; }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="header">
        <h1>LAPORAN KINERJA MGMP</h1>
        <p>Mothership SI-LIAK - Sistem Informasi Learning Integration & Analitik Kinerja</p>
    </div>

    <div class="report-section">
        <h2>Identitas MGMP</h2>
        <table>
            <tr>
                <th>Nama MGMP</th>
                <td><strong><?= htmlspecialchars($row['mgmp_name']) ?></strong></td>
            </tr>
            <tr>
                <th>Telemetry Code (ID)</th>
                <td><?= htmlspecialchars($row['mgmp_id']) ?></td>
            </tr>
            <tr>
                <th>Domain / URL</th>
                <td><?= htmlspecialchars($row['domain']) ?></td>
            </tr>
            <tr>
                <th>Populasi Guru</th>
                <td><?= number_format($row['total_guru']) ?> Guru</td>
            </tr>
            <tr>
                <th>Terakhir Sinkronisasi</th>
                <td><?= htmlspecialchars(date('d M Y, H:i', strtotime($row['last_sync']))) ?></td>
            </tr>
        </table>
    </div>

    <div class="report-section">
        <h2>Metrik Kinerja & Interaksi</h2>
        <table>
            <tr>
                <th>Skor SPI (Aktivitas File)</th>
                <td><?= number_format($row['spi_score']) ?> Point</td>
            </tr>
            <tr>
                <th>Skor KSI (Partisipasi Kolaborasi)</th>
                <td><?= number_format($row['ksi_score'], 2) ?> Point</td>
            </tr>
            <tr>
                <th>Materi Diekspor (Dibagikan ke Luar)</th>
                <td><?= number_format($row['cs_ekspor']) ?> Berkas</td>
            </tr>
            <tr>
                <th>Materi Diimpor (Diambil dari Luar)</th>
                <td><?= number_format($row['cs_impor']) ?> Berkas</td>
            </tr>
            <tr>
                <th>Interaksi Internal (Satu Sekolah)</th>
                <td><?= number_format($row['cs_internal']) ?> Berkas</td>
            </tr>
        </table>
    </div>

    <div class="report-section">
        <h2>Kecerdasan Buatan SI-LIAK Menyatakan:</h2>
        <div class="insight-box">
            <div class="insight-status">Status: <?= htmlspecialchars($ai_data['status']) ?></div>
            <div class="insight-text"><?= htmlspecialchars($ai_data['insight']) ?></div>
        </div>
    </div>
    
    <div style="text-align: right; margin-top: 50px; color: #9ca3af; font-size: 12px;">
        Dicetak pada: <?= date('d M Y H:i:s') ?>
    </div>
</body>
</html>
