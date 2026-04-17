<?php
declare(strict_types=1);

/**
 * Web App Manifest for display.php — enables standalone/fullscreen when added to home screen.
 */
require_once __DIR__ . '/includes/functions.php';

$token = isset($_GET['token']) ? preg_replace('/[^a-f0-9]/', '', strtolower((string) $_GET['token'])) : '';
if (strlen($token) !== 32) {
    http_response_code(400);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'error' => 'invalid_token'], JSON_UNESCAPED_UNICODE);
    exit;
}

$path = url('display.php?token=' . $token);
$host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
$https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || ((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
    || (isset($_SERVER['SERVER_PORT']) && (string) $_SERVER['SERVER_PORT'] === '443');
$scheme = $https ? 'https' : 'http';
$startUrl = $scheme . '://' . $host . $path;

$b = base_url();
$scopePath = $b === '' ? '/' : $b . '/';
$scope = $scheme . '://' . $host . $scopePath;

$iconPath = url('assets/images/login-hero.svg');
$iconUrl = $scheme . '://' . $host . $iconPath;

$manifest = [
    'name' => 'عرض الشاشة',
    'short_name' => 'العرض',
    'description' => 'شاشة عرض مواعيد الأطباء',
    'start_url' => $startUrl,
    'scope' => $scope,
    'display' => 'fullscreen',
    'orientation' => 'portrait-primary',
    'background_color' => '#050a12',
    'theme_color' => '#0a1628',
    'dir' => 'rtl',
    'lang' => 'ar',
    'icons' => [
        [
            'src' => $iconUrl,
            'sizes' => 'any',
            'type' => 'image/svg+xml',
            'purpose' => 'any',
        ],
    ],
];

header('Content-Type: application/manifest+json; charset=utf-8');
header('Cache-Control: no-store');
echo json_encode($manifest, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
