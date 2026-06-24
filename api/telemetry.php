<?php
require_once '../config/database.php';

// Terima input JSON dari node klien
$raw_data = file_get_contents('php://input');
$data = json_decode($raw_data, true);

// Validasi Payload
if (!$data || !isset($data['mgmp_id']) || !isset($data['metrics'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid payload']);
    exit;
}

// Validasi Kunci Rahasia
if (!isset($data['api_secret']) || $data['api_secret'] !== API_SECRET_KEY) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized: Invalid API Secret Key']);
    exit;
}

$mgmp_id = mysqli_real_escape_string($conn, $data['mgmp_id']);
$domain = mysqli_real_escape_string($conn, $data['domain']);
$sync_time = mysqli_real_escape_string($conn, $data['sync_time']);

$m = $data['metrics'];
$cs = $data['cross_school'];

$total_guru = (int)$m['total_guru'];
$total_asesor = (int)$m['total_asesor'];
$total_upload = (int)$m['total_upload'];
$total_download = (int)$m['total_download'];
$total_login = (int)$m['total_login'];
$spi_score = (int)$m['spi_score'];
$ksi_score = (float)$m['ksi_score'];

$cs_ekspor = (int)$cs['ekspor'];
$cs_impor = (int)$cs['impor'];
$cs_internal = (int)$cs['internal'];

// UPSERT Query
$query = "
    INSERT INTO national_telemetry (
        mgmp_id, domain, total_guru, total_asesor, total_upload, total_download, 
        total_login, spi_score, ksi_score, cs_ekspor, cs_impor, cs_internal, last_sync
    ) VALUES (
        '$mgmp_id', '$domain', $total_guru, $total_asesor, $total_upload, $total_download,
        $total_login, $spi_score, $ksi_score, $cs_ekspor, $cs_impor, $cs_internal, '$sync_time'
    )
    ON DUPLICATE KEY UPDATE
        domain = VALUES(domain),
        total_guru = VALUES(total_guru),
        total_asesor = VALUES(total_asesor),
        total_upload = VALUES(total_upload),
        total_download = VALUES(total_download),
        total_login = VALUES(total_login),
        spi_score = VALUES(spi_score),
        ksi_score = VALUES(ksi_score),
        cs_ekspor = VALUES(cs_ekspor),
        cs_impor = VALUES(cs_impor),
        cs_internal = VALUES(cs_internal),
        last_sync = VALUES(last_sync)
";

if (mysqli_query($conn, $query)) {
    echo json_encode(['status' => 'success', 'message' => 'Data recorded']);
} else {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => mysqli_error($conn)]);
}
?>
