<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/storage.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    header('Location: login.php');
    exit;
}

$token = (string) ($_POST['csrf'] ?? '');
if (!validateCsrf($token)) {
    $_SESSION['admin_login_error'] = 'Gecersiz oturum tokeni.';
    header('Location: login.php');
    exit;
}

$username = trim((string) ($_POST['username'] ?? ''));
$password = (string) ($_POST['password'] ?? '');

if (!loginAdmin($username, $password)) {
    $_SESSION['admin_login_error'] = 'Kullanici adi veya sifre hatali.';
    header('Location: login.php');
    exit;
}

header('Location: index.php');
exit;

