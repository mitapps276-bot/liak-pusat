CREATE DATABASE IF NOT EXISTS siliak_pusat_db;
USE siliak_pusat_db;

CREATE TABLE IF NOT EXISTS national_telemetry (
    id INT AUTO_INCREMENT PRIMARY KEY,
    mgmp_id VARCHAR(50) NOT NULL UNIQUE,
    domain VARCHAR(100) NOT NULL,
    total_guru INT DEFAULT 0,
    total_asesor INT DEFAULT 0,
    total_upload INT DEFAULT 0,
    total_download INT DEFAULT 0,
    total_login INT DEFAULT 0,
    spi_score INT DEFAULT 0,
    ksi_score FLOAT DEFAULT 0,
    cs_ekspor INT DEFAULT 0,
    cs_impor INT DEFAULT 0,
    cs_internal INT DEFAULT 0,
    last_sync DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
