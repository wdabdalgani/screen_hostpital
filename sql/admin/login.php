<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/hospital.php';

if (admin_logged_in()) {
    header('Location: ' . url('admin/dashboard.php'));
    exit;
}

$pdo = db();
$hs = hospital_settings($pdo);
$hospitalName = trim((string) ($hs['name'] ?? '')) !== '' ? (string) $hs['name'] : 'المستشفى';
$logoUrl = !empty($hs['logo_path']) ? url('uploads/hospital/' . basename((string) $hs['logo_path'])) : null;

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $u = trim((string) ($_POST['username'] ?? ''));
    $p = (string) ($_POST['password'] ?? '');
    if ($u === '' || $p === '') {
        $error = 'أدخل اسم المستخدم وكلمة المرور.';
    } elseif (!admin_login($u, $p)) {
        $error = 'بيانات الدخول غير صحيحة.';
    } else {
        header('Location: ' . url('admin/dashboard.php'));
        exit;
    }
}
$heroUrl = url('assets/images/login-hero.svg');
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>دخول — <?= esc($hospitalName) ?></title>
  <link rel="stylesheet" href="<?= esc(url('assets/css/admin.css')) ?>?v=7">
</head>
<body class="admin-body admin-body--login">
  <div class="login-split">
    <main class="login-panel">
      <div class="login-card">
        <header class="login-card__head">
          <?php if ($logoUrl): ?>
            <img class="login-card__logo" src="<?= esc($logoUrl) ?>" alt="" width="72" height="72" loading="eager" decoding="async">
          <?php else: ?>
            <span class="login-card__mark" aria-hidden="true">
              <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><rect x="3" y="3" width="7" height="9" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="12" width="7" height="9" rx="1.5"/><rect x="3" y="16" width="7" height="5" rx="1.5"/></svg>
            </span>
          <?php endif; ?>
          <div class="login-card__titles">
            <h2 class="login-card__title"><?= esc($hospitalName) ?></h2>
            <p class="login-card__welcome">مرحباً بك في لوحة الإدارة</p>
            <p class="login-card__sub">سجّل الدخول للمتابعة وإدارة شاشات العرض والمحتوى.</p>
          </div>
        </header>

        <?php if ($error !== ''): ?>
          <div class="login-alert" role="alert"><?= esc($error) ?></div>
        <?php endif; ?>

        <form method="post" class="login-form" autocomplete="on" novalidate>
          <div class="login-field">
            <label class="login-field__label" for="login-user">اسم المستخدم</label>
            <div class="login-field__wrap">
              <span class="login-field__icon" aria-hidden="true">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
              </span>
              <input class="login-field__input" id="login-user" type="text" name="username" required autocomplete="username" placeholder="أدخل اسم المستخدم" value="<?= isset($_POST['username']) ? esc((string) $_POST['username']) : '' ?>">
            </div>
          </div>

          <div class="login-field">
            <label class="login-field__label" for="login-pass">كلمة المرور</label>
            <div class="login-field__wrap">
              <span class="login-field__icon" aria-hidden="true">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
              </span>
              <input class="login-field__input" id="login-pass" type="password" name="password" required autocomplete="current-password" placeholder="••••••••">
              <button type="button" class="login-field__toggle" id="login-pass-toggle" aria-label="إظهار كلمة المرور" title="إظهار / إخفاء">
                <svg class="login-field__eye-open" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                <svg class="login-field__eye-off" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" hidden><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
              </button>
            </div>
          </div>

          <button class="login-submit" type="submit">
            <span>تسجيل الدخول</span>
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><polyline points="15 18 9 12 15 6"/></svg>
          </button>
        </form>

        <p class="login-card__foot">جلسة آمنة — لا تشارك بيانات الدخول مع أحد.</p>
      </div>
    </main>

    <aside class="login-hero" aria-label="هوية بصرية">
      <div class="login-hero__bg" style="--login-hero:url('<?= esc($heroUrl) ?>')"></div>
      <div class="login-hero__scrim"></div>
      <div class="login-hero__content">
        <?php if ($logoUrl): ?>
          <img class="login-hero__logo" src="<?= esc($logoUrl) ?>" alt="" width="120" height="120" loading="eager" decoding="async">
        <?php else: ?>
          <span class="login-hero__mark" aria-hidden="true">
            <svg width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/><circle cx="12" cy="12" r="3"/></svg>
          </span>
        <?php endif; ?>
        <p class="login-hero__eyebrow">لوحة إدارة مواعيد الأطباء</p>
        <h1 class="login-hero__title"><?= esc($hospitalName) ?></h1>
        <p class="login-hero__lead">إدارة الشاشات والأطباء والمحتوى الديناميكي — بواجهة واحدة آمنة وسريعة.</p>
      </div>
    </aside>
  </div>
  <script>
    (function () {
      var pass = document.getElementById('login-pass');
      var btn = document.getElementById('login-pass-toggle');
      if (!pass || !btn) return;
      var eyeOpen = btn.querySelector('.login-field__eye-open');
      var eyeOff = btn.querySelector('.login-field__eye-off');
      btn.addEventListener('click', function () {
        var show = pass.getAttribute('type') === 'password';
        pass.setAttribute('type', show ? 'text' : 'password');
        btn.setAttribute('aria-label', show ? 'إخفاء كلمة المرور' : 'إظهار كلمة المرور');
        if (eyeOpen && eyeOff) {
          eyeOpen.hidden = show;
          eyeOff.hidden = !show;
        }
      });
    })();
  </script>
</body>
</html>
