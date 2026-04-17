<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config/database.php';

$token = isset($_GET['token']) ? preg_replace('/[^a-f0-9]/', '', strtolower((string) $_GET['token'])) : '';
$offlineSyncPrep = isset($_GET['offline_sync']) && (string) $_GET['offline_sync'] === '1';
if (strlen($token) !== 32) {
    http_response_code(400);
    echo '<!DOCTYPE html><html lang="ar" dir="rtl"><meta charset="utf-8"><title>رابط غير صالح</title><body style="font-family:sans-serif;text-align:center;padding:2rem;background:linear-gradient(135deg,#2FAE66,#2C7FB8);color:#fff">رابط الشاشة غير صالح.</body></html>';
    exit;
}

$pdo = db();
$st = $pdo->prepare('SELECT display_style FROM screens WHERE token = ? LIMIT 1');
$st->execute([$token]);
$row = $st->fetch();
$style = $row ? (string) ($row['display_style'] ?? '') : '';

$wantClassic = isset($_GET['force_classic']);
$wantHariri = isset($_GET['force_hariri']);
if ($wantClassic) {
    $useHariri = false;
} elseif ($wantHariri) {
    $useHariri = true;
} else {
    $useHariri = ($style === 'hariri_template');
}

$api = esc(url('api/display.php?token=' . $token));

$host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
$https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || ((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
    || (isset($_SERVER['SERVER_PORT']) && (string) $_SERVER['SERVER_PORT'] === '443');
$scheme = $https ? 'https' : 'http';
$pagePath = 'display.php?token=' . $token . ($offlineSyncPrep ? '&offline_sync=1' : '');
$pageAbsUrl = $scheme . '://' . $host . url($pagePath);
$apiAbsUrl = $scheme . '://' . $host . url('api/display.php?token=' . $token);
$manifestAbsUrl = $scheme . '://' . $host . url('display_manifest.php?token=' . $token);
$swPath = url('display-sw.js');
$scopePath = url('');

if ($useHariri) {
    require_once __DIR__ . '/includes/theme_hariri.php';
    $parts = hariri_theme_embed_parts();
    $assetCss = url('assets/css/display_hariri_shell.css') . '?v=3';
    $assetJs = url('assets/js/display_hariri.js') . '?v=8';
    ?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
  <meta name="theme-color" content="#3498db">
  <meta name="mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <title>عرض المواعيد — قالب الحريري</title>
  <link rel="manifest" href="<?= esc(url('display_manifest.php?token=' . $token)) ?>">
  <link rel="stylesheet" href="<?= esc(url('theme/style.css')) ?>">
  <link rel="stylesheet" href="<?= esc(url('assets/css/display_hariri_shell.css')) ?>?v=3">
  <style><?= $parts['head_inline_css'] ?></style>
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
  <template id="hariri-tpl"><?= $parts['body_inner'] ?></template>
  <script src="<?= esc(url('assets/js/display_hariri.js')) ?>?v=8"></script>
</body>
</html>
<?php
    exit;
}

$assetCss = url('assets/css/display.css') . '?v=21';
$assetJs = url('assets/js/display.js') . '?v=21';
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
