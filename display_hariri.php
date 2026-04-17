<?php
declare(strict_types=1);

/**
 * توافق مع الروابط القديمة: العرض الفعلي من display.php (نفس الصفحة، يفرع حسب الاستايل أو force_hariri).
 */
require_once __DIR__ . '/includes/functions.php';

$token = isset($_GET['token']) ? preg_replace('/[^a-f0-9]/', '', strtolower((string) $_GET['token'])) : '';
if (strlen($token) !== 32) {
    http_response_code(400);
    echo '<!DOCTYPE html><html lang="ar" dir="rtl"><meta charset="utf-8"><title>رابط غير صالح</title><body style="font-family:sans-serif;text-align:center;padding:2rem;background:linear-gradient(135deg,#2FAE66,#2C7FB8);color:#fff">رابط الشاشة غير صالح.</body></html>';
    exit;
}

$q = ['token' => $token, 'force_hariri' => '1'];
if (isset($_GET['offline_sync']) && (string) $_GET['offline_sync'] === '1') {
    $q['offline_sync'] = '1';
}
header('Location: ' . url('display.php?' . http_build_query($q)), true, 302);
exit;
