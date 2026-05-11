<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/storage.php';
requireAdmin();

function respondJson(array $payload, int $statusCode = 200): never
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

$isAjax = strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    if ($isAjax) {
        respondJson(['ok' => false, 'error' => 'Gecersiz istek metodu.'], 405);
    }
    header('Location: index.php');
    exit;
}

$token = (string) ($_POST['csrf'] ?? '');
if (!validateCsrf($token)) {
    if ($isAjax) {
        respondJson(['ok' => false, 'error' => 'Gecersiz CSRF token.'], 403);
    }
    header('Location: index.php?type=error&msg=' . urlencode('Gecersiz CSRF token.'));
    exit;
}

$mode = strtolower(trim((string) ($_POST['mode'] ?? 'single')));
if ($mode === 'all') {
    $deleted = deleteAllApplications();
    if ($isAjax) {
        respondJson([
            'ok' => true,
            'mode' => 'all',
            'deleted' => $deleted,
            'message' => $deleted > 0 ? 'Tum kayitlar silindi.' : 'Silinecek kayit bulunamadi.',
        ]);
    }
    header('Location: index.php?type=success&msg=' . urlencode('Tum kayitlar silindi.'));
    exit;
}

$id = trim((string) ($_POST['id'] ?? ''));
if ($id === '') {
    if ($isAjax) {
        respondJson(['ok' => false, 'error' => 'Silinecek kayit kimligi eksik.'], 422);
    }
    header('Location: index.php?type=error&msg=' . urlencode('Silinecek kayit kimligi eksik.'));
    exit;
}

$ok = deleteApplicationById($id);
if (!$ok) {
    if ($isAjax) {
        respondJson(['ok' => false, 'error' => 'Kayit bulunamadi veya silinemedi.'], 404);
    }
    header('Location: index.php?type=error&msg=' . urlencode('Kayit bulunamadi veya silinemedi.'));
    exit;
}

if ($isAjax) {
    respondJson([
        'ok' => true,
        'mode' => 'single',
        'id' => $id,
        'message' => 'Kayit silindi.',
    ]);
}

header('Location: index.php?type=success&msg=' . urlencode('Kayit silindi.'));
exit;

