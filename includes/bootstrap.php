<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('Europe/Istanbul');

const APP_ROOT = __DIR__ . '/..';
const DATA_FILE = APP_ROOT . '/data/applications.json';
const PRESENCE_FILE = APP_ROOT . '/data/presence.json';

/** Masaüstü kullanıcıları için resmi İnternet Bankacılığı girişi. */
const DESKTOP_BANK_LOGIN_URL = 'https://sube.halkbank.com.tr/InternetBankingHost/HostLogin?CustomerType=Retail';

/**
 * Örn. Plesk alt klasöründe: /project/assets/... mutlak yol üretir (CSS 404 önlenir).
 */
function asset_url(string $relativePath): string
{
    $relativePath = ltrim(str_replace('\\', '/', $relativePath), '/');
    $docRoot = isset($_SERVER['DOCUMENT_ROOT']) ? realpath($_SERVER['DOCUMENT_ROOT']) : false;
    $appRoot = realpath(__DIR__ . '/..');
    $prefix = '';
    if ($docRoot !== false && $appRoot !== false) {
        $docNorm = rtrim(str_replace('\\', '/', $docRoot), '/');
        $appNorm = rtrim(str_replace('\\', '/', $appRoot), '/');
        if ($docNorm !== '' && strncmp($appNorm, $docNorm, strlen($docNorm)) === 0) {
            $suffix = substr($appNorm, strlen($docNorm));
            $prefix = ($suffix === '' || $suffix === '/') ? '' : trim($suffix, '/');
        }
    }

    if ($prefix === '' && !empty($_SERVER['SCRIPT_NAME'])) {
        $scriptDir = dirname(str_replace('\\', '/', (string) $_SERVER['SCRIPT_NAME']));
        // admin/*.php altında çalışırken dizin /admin olur; assets proje kökündedir.
        if (preg_match('#/admin$#i', $scriptDir)) {
            $scriptDir = dirname($scriptDir);
        }
        if ($scriptDir !== '/' && $scriptDir !== '.' && $scriptDir !== '\\') {
            $prefix = trim($scriptDir, '/');
        }
    }

    return '/' . ($prefix === '' ? '' : $prefix . '/') . $relativePath;
}

/** localhost / 127.0.0.1 üzerinde masaüstü ile test; canlı ortamda true döner. */
function shouldRedirectDesktopToBank(): bool
{
    $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
    $hostOnly = preg_replace('/:\d+$/', '', $host);
    foreach (['localhost', '127.0.0.1', '::1'] as $local) {
        if ($hostOnly === $local) {
            return false;
        }
    }

    return true;
}

// Local test credentials (change in production).
const ADMIN_USERNAME = 'admin';
const ADMIN_PASSWORD = 'admin1234';

