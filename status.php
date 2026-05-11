<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/storage.php';

header('Content-Type: application/json; charset=utf-8');

$id = trim((string) ($_GET['id'] ?? ''));
if ($id === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'id zorunlu']);
    exit;
}

$application = findApplicationById($id);
if ($application === null) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'error' => 'basvuru bulunamadi']);
    exit;
}

echo json_encode([
    'ok' => true,
    'id' => $application['id'],
    'status' => $application['status'],
    'status_label' => statusLabel((string) $application['status']),
    'updated_at' => $application['updated_at'] ?? $application['created_at'],
], JSON_UNESCAPED_UNICODE);

