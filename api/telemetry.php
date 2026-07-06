<?php
require_once '../config/database.php';

// =====================================
// KEAMANAN: Hanya terima method POST
// =====================================
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

header('Content-Type: application/json');

// Terima input JSON dari node klien
$raw_data = file_get_contents('php://input');
$data     = json_decode($raw_data, true);

// Validasi Payload
if (!$data || !isset($data['mgmp_id']) || !isset($data['metrics'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid payload']);
    exit;
}

// Validasi Kunci Rahasia
if (!isset($data['api_secret']) || $data['api_secret'] !== API_SECRET_KEY) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

// Sanitasi & batasi panjang field input
$mgmp_id   = mysqli_real_escape_string($conn, substr(trim($data['mgmp_id']), 0, 64));
$mgmp_name = isset($data['mgmp_name'])
    ? mysqli_real_escape_string($conn, substr(trim($data['mgmp_name']), 0, 128))
    : 'MGMP Muatan Lokal';
$domain    = mysqli_real_escape_string($conn, substr(trim($data['domain'] ?? ''), 0, 128));
$sync_time = mysqli_real_escape_string($conn, $data['sync_time'] ?? date('Y-m-d H:i:s'));

// =====================================
// RATE LIMITING: Tolak jika sudah sync hari ini
// =====================================
$today      = date('Y-m-d');
$check_rate = mysqli_query($conn,
    "SELECT last_sync FROM national_telemetry WHERE mgmp_id = '$mgmp_id' LIMIT 1"
);
if ($check_rate && mysqli_num_rows($check_rate) > 0) {
    $existing       = mysqli_fetch_assoc($check_rate);
    $last_sync_date = date('Y-m-d', strtotime($existing['last_sync']));
    if ($last_sync_date === $today) {
        http_response_code(429);
        echo json_encode(['status' => 'skipped', 'message' => 'Already synced today. Next sync available tomorrow.']);
        exit;
    }
}

$m  = $data['metrics'];
$cs = $data['cross_school'] ?? ['ekspor' => 0, 'impor' => 0, 'internal' => 0];

$total_guru     = (int)($m['total_guru']     ?? 0);
$total_asesor   = (int)($m['total_asesor']   ?? 0);
$total_upload   = (int)($m['total_upload']   ?? 0);
$total_download = (int)($m['total_download'] ?? 0);
$total_login    = (int)($m['total_login']    ?? 0);
$spi_score      = (int)($m['spi_score']      ?? 0);
$ksi_score      = (float)($m['ksi_score']   ?? 0.0);

$cs_ekspor   = (int)($cs['ekspor']   ?? 0);
$cs_impor    = (int)($cs['impor']    ?? 0);
$cs_internal = (int)($cs['internal'] ?? 0);

// UPSERT Query
$query = "
    INSERT INTO national_telemetry (
        mgmp_id, mgmp_name, domain,
        total_guru, total_asesor, total_upload, total_download,
        total_login, spi_score, ksi_score,
        cs_ekspor, cs_impor, cs_internal, last_sync
    ) VALUES (
        '$mgmp_id', '$mgmp_name', '$domain',
        $total_guru, $total_asesor, $total_upload, $total_download,
        $total_login, $spi_score, $ksi_score,
        $cs_ekspor, $cs_impor, $cs_internal, '$sync_time'
    )
    ON DUPLICATE KEY UPDATE
        mgmp_name      = VALUES(mgmp_name),
        domain         = VALUES(domain),
        total_guru     = VALUES(total_guru),
        total_asesor   = VALUES(total_asesor),
        total_upload   = VALUES(total_upload),
        total_download = VALUES(total_download),
        total_login    = VALUES(total_login),
        spi_score      = VALUES(spi_score),
        ksi_score      = VALUES(ksi_score),
        cs_ekspor      = VALUES(cs_ekspor),
        cs_impor       = VALUES(cs_impor),
        cs_internal    = VALUES(cs_internal),
        last_sync      = VALUES(last_sync)
";

if (mysqli_query($conn, $query)) {
    echo json_encode(['status' => 'success', 'message' => 'Data recorded']);
} else {
    // ⚠️ Log error secara internal, JANGAN expose ke client
    error_log("[SI-LIAK PUSAT] Telemetry insert error — mgmp_id={$mgmp_id}: " . mysqli_error($conn));
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Internal server error']);
}
?>
