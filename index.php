<?php
session_start();
require_once 'config/database.php';

// AI Rule-Based Helper
function generate_ai_insight($ksi, $ekspor, $impor, $internal) {
    // 1. Analisis Partisipasi
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

    // 2. Analisis Kolaborasi
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

// Cek Keamanan Akses Dasbor
if (!isset($_SESSION['mothership_logged_in']) || $_SESSION['mothership_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

// Proses Logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_destroy();
    header("Location: login.php");
    exit;
}

// Proses Reset Data Telemetri
if (isset($_GET['action']) && $_GET['action'] === 'reset') {
    mysqli_query($conn, "TRUNCATE TABLE national_telemetry");
    header("Location: index.php");
    exit;
}

// Agregasi Nasional
$aggr_query = mysqli_query($conn, "
    SELECT 
        COUNT(id) AS total_nodes,
        SUM(total_guru) AS nat_guru,
        SUM(total_upload) AS nat_upload,
        SUM(total_download) AS nat_download,
        SUM(cs_ekspor) AS nat_ekspor,
        SUM(cs_impor) AS nat_impor
    FROM national_telemetry
");
$aggr = mysqli_fetch_assoc($aggr_query);

// Pagination Logic
$items_per_page = 3;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($current_page < 1) $current_page = 1;
$offset = ($current_page - 1) * $items_per_page;

$count_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM national_telemetry");
$total_items = $count_query ? mysqli_fetch_assoc($count_query)['total'] : 0;
$total_pages = ceil($total_items / $items_per_page);
if ($total_pages < 1) $total_pages = 1;

// Papan Peringkat MGMP (Berdasarkan SPI)
$leaderboard = mysqli_query($conn, "
    SELECT mgmp_id, mgmp_name, domain, total_guru, spi_score, ksi_score, cs_ekspor, cs_impor, cs_internal, last_sync 
    FROM national_telemetry 
    ORDER BY spi_score DESC
    LIMIT $items_per_page OFFSET $offset
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dasbor Nasional SI-LIAK</title>
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --bg-color: #0f172a;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --glass-bg: rgba(0, 0, 0, 0.05); /* Super transparan ala HUD sexy */
            --glass-border: rgba(255, 255, 255, 0.15);
            --accent-blue: #3b82f6;
            --accent-purple: #8b5cf6;
            --accent-green: #10b981;
            --accent-pink: #ec4899;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background: var(--bg-color);
            color: var(--text-main);
            min-height: 100vh;
            position: relative;
            overflow-x: hidden;
            padding: 160px 20px 40px 20px; /* Padding atas ditambah agar tidak tertutup header */
        }

        /* Animated background blobs */
        .blob {
            position: absolute;
            filter: blur(80px);
            z-index: -1;
            opacity: 0.5;
            animation: float 10s ease-in-out infinite;
        }
        .blob-1 { top: -10%; left: -10%; width: 50vw; height: 50vw; background: radial-gradient(circle, rgba(59,130,246,0.4) 0%, transparent 70%); }
        .blob-2 { bottom: -10%; right: -10%; width: 60vw; height: 60vw; background: radial-gradient(circle, rgba(139,92,246,0.3) 0%, transparent 70%); animation-delay: -5s; }

        @keyframes float {
            0%, 100% { transform: translateY(0) scale(1); }
            50% { transform: translateY(-20px) scale(1.05); }
        }

        @keyframes subtlePan {
            0% { transform: scale(1) translate(0, 0); }
            25% { transform: scale(1.05) translate(-1%, -1%); }
            50% { transform: scale(1.1) translate(1%, -1%); }
            75% { transform: scale(1.05) translate(-1%, 1%); }
            100% { transform: scale(1) translate(0, 0); }
        }

        .bg-logo {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            object-fit: fill; /* Memaksa gambar ditarik memenuhi layar tanpa memotong */
            opacity: 0.25;
            z-index: 0;
            pointer-events: none;
            animation: subtlePan 12s ease-in-out infinite;
        }

        .floating-logo {
            position: fixed;
            top: 20px;
            left: 30px;
            height: 350px;
            z-index: 1001;
            filter: drop-shadow(0 10px 20px rgba(0,0,0,0.5));
            pointer-events: none;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            position: relative;
            z-index: 1;
        }

        /* Glassmorphism Header */
        .header {
            background: var(--glass-bg);
            backdrop-filter: blur(2px);
            -webkit-backdrop-filter: blur(2px);
            border: 1px solid var(--glass-border);
            padding: 25px 40px;
            border-radius: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            transition: transform 0.3s ease;
            position: fixed;
            top: 25px;
            right: 20px;
            width: auto;
            gap: 40px;
            z-index: 1000;
        }
        
        .header:hover {
            transform: translateY(-2px);
        }

        .header h1 {
            font-size: 32px;
            font-weight: 700;
            background: linear-gradient(135deg, #60a5fa, #c084fc);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .header p {
            color: var(--text-muted);
            font-weight: 300;
            font-size: 16px;
            letter-spacing: 0.5px;
        }

        .btn-print {
            color: white;
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            padding: 10px 20px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);
            display: flex;
            align-items: center;
            gap: 8px;
            font-family: inherit;
        }

        .btn-print:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(59, 130, 246, 0.5);
            background: linear-gradient(135deg, #60a5fa, #3b82f6);
        }

        .btn-logout {
            color: white;
            text-decoration: none;
            background: linear-gradient(135deg, #ef4444, #b91c1c);
            padding: 10px 20px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(239, 68, 68, 0.3);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-logout:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(239, 68, 68, 0.5);
            background: linear-gradient(135deg, #f87171, #dc2626);
        }

        /* Grid Cards */
        .grid-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 24px;
            margin-bottom: 40px;
        }

        .card {
            background: var(--glass-bg);
            backdrop-filter: blur(2px);
            -webkit-backdrop-filter: blur(2px);
            border: 1px solid var(--glass-border);
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 10px 30px -10px rgba(0, 0, 0, 0.3);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
            overflow: hidden;
        }

        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
        }
        
        .card:nth-child(1)::before { background: var(--accent-blue); }
        .card:nth-child(2)::before { background: var(--accent-purple); }
        .card:nth-child(3)::before { background: var(--accent-green); }
        .card:nth-child(4)::before { background: var(--accent-pink); }

        .card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.5);
            border-color: rgba(255, 255, 255, 0.2);
        }

        .card-icon {
            font-size: 32px;
            margin-bottom: 15px;
            opacity: 0.8;
        }
        .card:nth-child(1) .card-icon { color: var(--accent-blue); }
        .card:nth-child(2) .card-icon { color: var(--accent-purple); }
        .card:nth-child(3) .card-icon { color: var(--accent-green); }
        .card:nth-child(4) .card-icon { color: var(--accent-pink); }

        .card h3 {
            font-size: 14px;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 1.5px;
            margin-bottom: 15px;
            font-weight: 600;
        }

        .card .value {
            font-size: 42px;
            font-weight: 700;
            color: var(--text-main);
            text-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }

        .leaderboard-wrapper {
            position: fixed;
            bottom: 25px;
            right: 20px;
            z-index: 100;
        }

        .section-title {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            color: var(--text-main);
            text-shadow: 0 4px 15px rgba(0,0,0,0.8);
        }

        .table-container {
            background: var(--glass-bg);
            backdrop-filter: blur(2px);
            -webkit-backdrop-filter: blur(2px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }

        .btn-page {
            color: white;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid var(--glass-border);
            padding: 8px 16px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 13px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
        }
        .btn-page:hover:not(.disabled) {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }
        .btn-page.disabled {
            opacity: 0.3;
            cursor: not-allowed;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 20px 25px;
            text-align: left;
        }

        th {
            background: rgba(0, 0, 0, 0.2);
            color: var(--text-muted);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 13px;
            letter-spacing: 1px;
            border-bottom: 1px solid var(--glass-border);
        }

        td {
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            color: #e2e8f0;
            transition: background 0.3s ease;
        }

        tr:last-child td {
            border-bottom: none;
        }

        tr:hover td {
            background: rgba(255, 255, 255, 0.03);
        }

        .rank {
            font-weight: 700;
            font-size: 18px;
            background: linear-gradient(135deg, #fde047, #f59e0b);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .mgmp-id {
            font-size: 13px;
            font-weight: 600;
            color: #60a5fa;
            background: rgba(96, 165, 250, 0.1);
            padding: 5px 10px;
            border-radius: 6px;
            border: 1px solid rgba(96, 165, 250, 0.2);
            font-family: 'Courier New', Courier, monospace;
            display: inline-block;
            letter-spacing: 0.5px;
        }

        .mgmp-domain {
            font-size: 13px;
            color: var(--accent-blue);
        }

        .score-highlight {
            font-size: 18px;
            font-weight: 700;
            color: var(--accent-green);
        }

        .badge {
            background: rgba(16, 185, 129, 0.1);
            color: var(--accent-green);
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            border: 1px solid rgba(16, 185, 129, 0.2);
        }
        
        .empty-state {
            text-align: center;
            padding: 50px 20px;
            color: var(--text-muted);
        }
        .empty-state i {
            font-size: 48px;
            margin-bottom: 15px;
            opacity: 0.5;
        }

        @media print {
            body { background: white; color: black; padding: 0; }
            .blob, .btn-logout, .btn-print { display: none !important; }
            .header, .card, .table-container { 
                background: transparent; 
                box-shadow: none; 
                border: 1px solid #ddd; 
                backdrop-filter: none;
                -webkit-backdrop-filter: none;
                color: black;
            }
            .header h1 { background: none; -webkit-text-fill-color: black; color: black; }
            .header p, .card h3, .card .value, .section-title, .mgmp-id, td { color: black !important; }
            th { background: #f1f5f9; color: black !important; border-bottom: 2px solid #ddd; }
            td { border-bottom: 1px solid #ddd; }
            .rank { background: none; -webkit-text-fill-color: black; color: black; font-weight: bold; }
            .badge { border: 1px solid #000; color: black; background: transparent; }
            .score-highlight { color: black !important; }
            .card::before { display: none; }
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                text-align: center;
                gap: 20px;
            }
            .table-container {
                overflow-x: auto;
            }
        }
        /* Modal AI Insight */
        .ai-modal-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.7);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            display: none; justify-content: center; align-items: center;
            z-index: 9999; opacity: 0; transition: opacity 0.3s;
        }
        .ai-modal-overlay.active { display: flex; opacity: 1; }
        .ai-modal {
            background: rgba(15, 23, 42, 0.85);
            border: 1px solid var(--glass-border);
            border-radius: 15px; padding: 30px;
            max-width: 500px; width: 90%;
            box-shadow: 0 10px 40px rgba(0,0,0,0.5);
            position: relative;
            transform: translateY(20px); transition: transform 0.3s;
        }
        .ai-modal-overlay.active .ai-modal { transform: translateY(0); }
        .ai-modal h3 { color: var(--accent-purple); margin-bottom: 15px; display: flex; align-items: center; gap: 10px; }
        .ai-status-badge { display: inline-block; padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; margin-bottom: 15px; }
        .ai-status-sangat-sehat { background: rgba(16, 185, 129, 0.2); color: #34d399; border: 1px solid #10b981; }
        .ai-status-sehat { background: rgba(59, 130, 246, 0.2); color: #60a5fa; border: 1px solid #3b82f6; }
        .ai-status-perlu-perhatian { background: rgba(244, 63, 94, 0.2); color: #fb7185; border: 1px solid #f43f5e; }
        .ai-close {
            position: absolute; top: 20px; right: 20px;
            background: none; border: none; color: var(--text-muted);
            font-size: 20px; cursor: pointer; transition: 0.2s;
        }
        .ai-close:hover { color: #fff; }
        .btn-ai {
            background: linear-gradient(135deg, #8b5cf6, #d946ef);
            color: white; border: none; padding: 6px 12px; border-radius: 6px;
            font-family: inherit; font-size: 12px; cursor: pointer;
            box-shadow: 0 4px 15px rgba(139, 92, 246, 0.3); transition: 0.3s;
            display: inline-flex; align-items: center; gap: 5px;
        }
        .btn-ai:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(139, 92, 246, 0.5); }
    </style>
</head>
<body>
    <!-- Background Wallpaper -->
    <img src="LIAK.jpg" alt="Background SI-LIAK" class="bg-logo">
    
    <!-- Background Blobs -->
    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>

    <!-- Floating Logo di Pojok Kiri Atas -->
    <img src="Logo%20SI-LIAK.png" alt="Logo SI-LIAK" class="floating-logo">

    <div class="container">
        <div class="header">
            <div>
                <h1 style="margin-bottom: 5px;">MOTHERSHIP SI-LIAK</h1>
                <p>Sistem Informasi Learning Integration & Analitik Kinerja</p>
            </div>
            <div style="display: flex; gap: 10px;">
                <button onclick="window.print()" class="btn-print">
                    <i class="fa-solid fa-print"></i> Cetak Laporan
                </button>
                <button onclick="if(confirm('YAKIN RESET SEMUA DATA TELEMETRI? Data akan terhapus dari server pusat dan kembali menjadi nol!')) window.location.href='?action=reset'" class="btn-logout" style="background: linear-gradient(135deg, #f59e0b, #d97706); box-shadow: 0 4px 15px rgba(245, 158, 11, 0.3);">
                    <i class="fa-solid fa-trash-can"></i> Reset Data
                </button>
                <a href="?action=logout" class="btn-logout">
                    <i class="fa-solid fa-power-off"></i> Logout
                </a>
            </div>
        </div>

        <div class="leaderboard-wrapper">
            <h2 class="section-title">LEADERBOARD KINERJA MGMP</h2>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Peringkat</th>
                            <th>Telemetry Code</th>
                            <th>MGMP / Domain</th>
                            <th>Jumlah Guru</th>
                            <th>Skor SPI</th>
                            <th>Skor KSI</th>
                            <th>Interaksi Lintas</th>
                            <th>Update Terakhir</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $rank = $offset + 1;
                        if(mysqli_num_rows($leaderboard) > 0) {
                            while($row = mysqli_fetch_assoc($leaderboard)): 
                                $ai_data = generate_ai_insight($row['ksi_score'], $row['cs_ekspor'], $row['cs_impor'], $row['cs_internal']);
                                $status_class = strtolower(str_replace(' ', '-', $ai_data['status']));
                        ?>
                        <tr>
                            <td class="rank">#<?= $rank++ ?></td>
                            <td>
                                <span class="mgmp-id"><i class="fa-solid fa-barcode"></i> <?= htmlspecialchars($row['mgmp_id']) ?></span>
                            </td>
                            <td>
                                <div style="font-weight: 700; margin-bottom: 6px; color: #fff; font-size: 15px; text-transform: uppercase; letter-spacing: 0.5px;"><?= htmlspecialchars($row['mgmp_name']) ?></div>
                                <?php 
                                    $raw_domain = $row['domain'];
                                    $link_url = (strpos($raw_domain, 'http') === 0) ? $raw_domain : 'http://' . $raw_domain;
                                    $display_domain = str_replace(['http://','https://'], '', $raw_domain);
                                ?>
                                <a href="<?= htmlspecialchars($link_url) ?>" target="_blank" class="mgmp-domain" style="text-decoration:none;"><i class="fa-solid fa-link"></i> <?= htmlspecialchars($display_domain) ?></a>
                            </td>
                            <td><span class="badge"><i class="fa-solid fa-user-tie"></i> <?= number_format($row['total_guru']) ?> Guru</span></td>
                            <td class="score-highlight"><?= number_format($row['spi_score']) ?> <span style="font-size:12px; color:var(--text-muted); font-weight:400;">Point</span></td>
                            <td><?= number_format($row['ksi_score'], 2) ?></td>
                            <td style="font-size: 13px; line-height: 1.6; text-align: left; min-width: 120px;">
                                <span style="color:var(--accent-green);"><i class="fa-solid fa-arrow-up-right-from-square" style="width: 14px;"></i> Ekspor: <?= number_format($row['cs_ekspor']) ?></span><br>
                                <span style="color:var(--accent-blue);"><i class="fa-solid fa-arrow-down-to-bracket" style="width: 14px;"></i> Impor: <?= number_format($row['cs_impor']) ?></span><br>
                                <span style="color:var(--text-muted);"><i class="fa-solid fa-rotate" style="width: 14px;"></i> Internal: <?= number_format($row['cs_internal']) ?></span>
                            </td>
                            <td><small style="color:var(--text-muted);"><i class="fa-regular fa-clock"></i> <?= htmlspecialchars(date('d M Y, H:i', strtotime($row['last_sync']))) ?></small></td>
                            <td>
                                <button class="btn-ai" onclick="showAiModal('<?= htmlspecialchars($row['mgmp_name'], ENT_QUOTES) ?>', '<?= htmlspecialchars($ai_data['status'], ENT_QUOTES) ?>', '<?= htmlspecialchars($ai_data['insight'], ENT_QUOTES) ?>', '<?= $status_class ?>')">
                                    <i class="fa-solid fa-wand-magic-sparkles"></i> SI-LIAK Insight
                                </button>
                            </td>
                        </tr>
                        <?php 
                            endwhile; 
                        } else {
                            echo "<tr><td colspan='7'>
                                <div class='empty-state'>
                                    <i class='fa-solid fa-satellite'></i>
                                    <h3>Menunggu Sinyal Telemetri...</h3>
                                    <p>Belum ada data dari MGMP klien yang masuk ke Mothership.</p>
                                </div>
                            </td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>

            <?php if ($total_pages > 1 || $total_items > $items_per_page): ?>
            <div class="pagination" style="display:flex; justify-content:center; gap: 15px; margin-top: 15px;">
                <?php if ($current_page > 1): ?>
                    <a href="?page=<?= $current_page - 1 ?>" class="btn-page"><i class="fa-solid fa-chevron-left"></i> Sebelumnya</a>
                <?php else: ?>
                    <button class="btn-page disabled" disabled><i class="fa-solid fa-chevron-left"></i> Sebelumnya</button>
                <?php endif; ?>
                
                <span style="color: var(--text-muted); align-self: center; font-size: 14px; background: rgba(0,0,0,0.3); padding: 5px 15px; border-radius: 20px;">
                    Hal <?= $current_page ?> / <?= $total_pages ?>
                </span>
                
                <?php if ($current_page < $total_pages): ?>
                    <a href="?page=<?= $current_page + 1 ?>" class="btn-page">Selanjutnya <i class="fa-solid fa-chevron-right"></i></a>
                <?php else: ?>
                    <button class="btn-page disabled" disabled>Selanjutnya <i class="fa-solid fa-chevron-right"></i></button>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- AI Modal -->
    <div class="ai-modal-overlay" id="aiModal" onclick="closeAiModal(event)">
        <div class="ai-modal" onclick="event.stopPropagation()">
            <button class="ai-close" onclick="closeAiModal()"><i class="fa-solid fa-xmark"></i></button>
            <h3><img src="Logo%20SI-LIAK.png" alt="Logo SI-LIAK" style="height: 24px; width: 24px; vertical-align: middle;"> Kecerdasan Buatan SI-LIAK Menyatakan <span id="aiMgmpName"></span></h3>
            <div id="aiStatus" class="ai-status-badge"></div>
            <p id="aiInsightText" style="line-height: 1.6; color: var(--text-main); font-size: 15px;"></p>
        </div>
    </div>

    <script>
        function showAiModal(name, status, insight, statusClass) {
            document.getElementById('aiMgmpName').innerText = name;
            const statusEl = document.getElementById('aiStatus');
            statusEl.innerText = status;
            statusEl.className = 'ai-status-badge ai-status-' + statusClass;
            document.getElementById('aiInsightText').innerText = insight;
            document.getElementById('aiModal').classList.add('active');
        }
        function closeAiModal(e) {
            document.getElementById('aiModal').classList.remove('active');
        }
    </script>
</body>
</html>
