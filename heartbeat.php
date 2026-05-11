<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/storage.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$screen = (string) ($_POST['screen'] ?? $_GET['screen'] ?? 'index');
$applicationId = trim((string) ($_POST['application_id'] ?? $_GET['application_id'] ?? ''));

upsertPresence($screen, $applicationId !== '' ? $applicationId : null, false);
$summary = getOnlineSummary();

echo json_encode([
    'ok' => true,
    'online_count' => $summary['count'],
], JSON_UNESCAPED_UNICODE);

