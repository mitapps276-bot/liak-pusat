<?php
session_start();
require_once 'config/database.php';

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

// Papan Peringkat MGMP (Berdasarkan SPI)
$leaderboard = mysqli_query($conn, "
    SELECT mgmp_id, domain, total_guru, spi_score, ksi_score, last_sync 
    FROM national_telemetry 
    ORDER BY spi_score DESC
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
            --glass-bg: rgba(30, 41, 59, 0.7);
            --glass-border: rgba(255, 255, 255, 0.1);
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
            padding: 40px 20px;
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

        .container {
            max-width: 1200px;
            margin: 0 auto;
            position: relative;
            z-index: 1;
        }

        /* Glassmorphism Header */
        .header {
            background: var(--glass-bg);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid var(--glass-border);
            padding: 30px 40px;
            border-radius: 20px;
            margin-bottom: 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            transition: transform 0.3s ease;
        }
        
        .header:hover {
            transform: translateY(-2px);
        }

        .header h1 {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 8px;
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
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
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

        /* Leaderboard Section */
        .section-title {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            color: var(--text-main);
        }

        .table-container {
            background: var(--glass-bg);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
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
            font-size: 16px;
            font-weight: 600;
            color: #fff;
            display: block;
            margin-bottom: 4px;
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
    </style>
</head>
<body>
    <!-- Background Blobs -->
    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>

    <div class="container">
        <div class="header">
            <div>
                <h1><i class="fa-solid fa-satellite-dish"></i> MOTHERSHIP SI-LIAK</h1>
                <p>Pusat Komando & Analitik Big Data MGMP Nasional</p>
            </div>
            <div style="display: flex; gap: 10px;">
                <button onclick="window.print()" class="btn-print">
                    <i class="fa-solid fa-print"></i> Cetak Laporan
                </button>
                <a href="?action=logout" class="btn-logout">
                    <i class="fa-solid fa-power-off"></i> Logout
                </a>
            </div>
        </div>

        <div class="grid-cards">
            <div class="card">
                <div class="card-icon"><i class="fa-solid fa-server"></i></div>
                <h3>Total MGMP</h3>
                <div class="value"><?= number_format($aggr['total_nodes'] ?: 0) ?></div>
            </div>
            <div class="card">
                <div class="card-icon"><i class="fa-solid fa-users"></i></div>
                <h3>Total Guru</h3>
                <div class="value"><?= number_format($aggr['nat_guru'] ?: 0) ?></div>
            </div>
            <div class="card">
                <div class="card-icon"><i class="fa-solid fa-file-arrow-up"></i></div>
                <h3>Total Materi</h3>
                <div class="value"><?= number_format($aggr['nat_upload'] ?: 0) ?></div>
            </div>
        </div>

        <h2 class="section-title"><i class="fa-solid fa-trophy" style="color: #fde047;"></i> Leaderboard Kinerja MGMP (SPI)</h2>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Peringkat</th>
                        <th>Domain</th>
                        <th>Jumlah Guru</th>
                        <th>Skor SPI</th>
                        <th>Skor KSI</th>
                        <th>Update Terakhir</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $rank = 1;
                    if(mysqli_num_rows($leaderboard) > 0) {
                        while($row = mysqli_fetch_assoc($leaderboard)): 
                    ?>
                    <tr>
                        <td class="rank">#<?= $rank++ ?></td>
                        <td>
                            <span class="mgmp-id"><?= htmlspecialchars($row['mgmp_id']) ?></span>
                            <a href="<?= htmlspecialchars($row['domain']) ?>" target="_blank" class="mgmp-domain" style="text-decoration:none;"><i class="fa-solid fa-link"></i> <?= htmlspecialchars(str_replace(['http://','https://'], '', $row['domain'])) ?></a>
                        </td>
                        <td><span class="badge"><i class="fa-solid fa-user-tie"></i> <?= number_format($row['total_guru']) ?> Guru</span></td>
                        <td class="score-highlight"><?= number_format($row['spi_score']) ?> <span style="font-size:12px; color:var(--text-muted); font-weight:400;">pts</span></td>
                        <td><?= number_format($row['ksi_score'], 2) ?></td>
                        <td><small style="color:var(--text-muted);"><i class="fa-regular fa-clock"></i> <?= htmlspecialchars(date('d M Y, H:i', strtotime($row['last_sync']))) ?></small></td>
                    </tr>
                    <?php 
                        endwhile; 
                    } else {
                        echo "<tr><td colspan='6'>
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
    </div>
</body>
</html>
