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

$id = trim((string) ($_POST['id'] ?? ''));
$status = trim((string) ($_POST['status'] ?? ''));

if ($id === '' || !in_array($status, APPLICATION_STATUSES, true)) {
    if ($isAjax) {
        respondJson(['ok' => false, 'error' => 'Gecersiz durum guncelleme istegi.'], 422);
    }
    header('Location: index.php?type=error&msg=' . urlencode('Gecersiz durum guncelleme istegi.'));
    exit;
}

$ok = updateApplicationStatus($id, $status);
if (!$ok) {
    if ($isAjax) {
        respondJson(['ok' => false, 'error' => 'Basvuru bulunamadi veya guncellenemedi.'], 404);
    }
    header('Location: index.php?type=error&msg=' . urlencode('Basvuru bulunamadi veya guncellenemedi.'));
    exit;
}

if ($isAjax) {
    respondJson([
        'ok' => true,
        'id' => $id,
        'status' => $status,
        'status_label' => statusLabel($status),
        'message' => 'Durum basariyla guncellendi.',
    ]);
}

header('Location: index.php?type=success&msg=' . urlencode('Durum basariyla guncellendi: ' . statusLabel($status)));
exit;

