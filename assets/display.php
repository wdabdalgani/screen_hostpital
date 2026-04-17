<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';

$token = isset($_GET['token']) ? preg_replace('/[^a-f0-9]/', '', strtolower((string) $_GET['token'])) : '';
$offlineSyncPrep = isset($_GET['offline_sync']) && (string) $_GET['offline_sync'] === '1';
if (strlen($token) !== 32) {
    http_response_code(400);
    echo '<!DOCTYPE html><html lang="ar" dir="rtl"><meta charset="utf-8"><title>رابط غير صالح</title><body style="font-family:sans-serif;text-align:center;padding:2rem;background:linear-gradient(135deg,#2FAE66,#2C7FB8);color:#fff">رابط الشاشة غير صالح.</body></html>';
    exit;
}
$api = esc(url('api/display.php?token=' . $token));

$host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
$https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || ((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
    || (isset($_SERVER['SERVER_PORT']) && (string) $_SERVER['SERVER_PORT'] === '443');
$scheme = $https ? 'https' : 'http';
$pageAbsUrl = $scheme . '://' . $host . url('display.php?token=' . $token);
$apiAbsUrl = $scheme . '://' . $host . url('api/display.php?token=' . $token);
$manifestAbsUrl = $scheme . '://' . $host . url('display_manifest.php?token=' . $token);
$assetCss = url('assets/css/display.css') . '?v=21';
$assetJs = url('assets/js/display.js') . '?v=21';
$swPath = url('display-sw.js');
$scopePath = url('');
?>
<!DOCTYPE html>
<!-- كشك ثابت (Windows): chrome.exe --kiosk "https://النطاق/.../display.php?token=TOKEN" -->
<!-- أو Edge: msedge.exe --kiosk "..." -->
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
  <meta name="theme-color" content="#0a1628">
  <meta name="mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
  <meta name="apple-mobile-web-app-title" content="عرض">
  <title>عرض المواعيد</title>
  <link rel="manifest" href="<?= esc(url('display_manifest.php?token=' . $token)) ?>">
  <link rel="stylesheet" href="<?= esc(url('assets/css/display.css')) ?>?v=21">
</head>
<body class="display-body">
  <div
    class="display-root"
    id="root"
    data-api="<?= $api ?>"
    data-api-abs="<?= esc($apiAbsUrl) ?>"
    data-display-page-abs="<?= esc($pageAbsUrl) ?>"
    data-manifest-abs="<?= esc($manifestAbsUrl) ?>"
    data-asset-css="<?= esc($assetCss) ?>"
    data-asset-js="<?= esc($assetJs) ?>"
    data-sw-url="<?= esc($swPath) ?>"
    data-sw-scope="<?= esc($scopePath) ?>"
    data-auto-offline-sync="<?= $offlineSyncPrep ? '1' : '0' ?>"
  >
    <div id="welcomeOverlay" class="welcome-overlay" hidden aria-hidden="true">
      <div class="welcome-overlay__media welcome-overlay__media--empty" id="welcomeBg" aria-hidden="true"></div>
      <div class="welcome-overlay__scrim" aria-hidden="true"></div>
      <div class="welcome-overlay__content">
        <img id="welcomeLogo" class="welcome-overlay__logo" src="" alt="" width="140" height="140" hidden decoding="async">
        <h1 id="welcomeTitle" class="welcome-overlay__title"></h1>
        <p id="welcomeSubtitle" class="welcome-overlay__subtitle"></p>
      </div>
    </div>
    <div class="display-viewport" id="slideBox">
      <div class="cinematic-slide slide slide--active" id="slideA" aria-live="polite"></div>
      <div class="cinematic-slide slide" id="slideB" hidden></div>
    </div>
    <?php if ($offlineSyncPrep): ?>
    <div id="displayOfflineSyncPanel" class="display-offline-sync" aria-live="polite">
      <div class="display-offline-sync__box">
        <p class="display-offline-sync__title">جاري تجهيز النسخة الأوفلاين على هذا المتصفح…</p>
        <p class="display-offline-sync__status" id="displayOfflineMsg"></p>
      </div>
    </div>
    <?php endif; ?>
  </div>
  <script src="<?= esc(url('assets/js/display.js')) ?>?v=21"></script>
</body>
</html>
