<?php
declare(strict_types=1);

/**
 * One-time setup: create DB tables and default admin.
 * Remove or protect this file in production.
 */
require_once __DIR__ . '/includes/functions.php';

$cfg = app_config();
$d = $cfg['db'];
$error = '';
$done = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = trim((string) ($_POST['username'] ?? 'admin'));
    $pass = (string) ($_POST['password'] ?? '');
    if ($pass === '') {
        $error = 'أدخل كلمة مرور للمسؤول.';
    } else {
        try {
            $dsn = sprintf('mysql:host=%s;port=%d;charset=%s', $d['host'], $d['port'], $d['charset']);
            $pdo = new PDO($dsn, $d['user'], $d['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            $pdo->exec('CREATE DATABASE IF NOT EXISTS `' . str_replace('`', '``', $d['name']) . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
            $pdo->exec('USE `' . str_replace('`', '``', $d['name']) . '`');
            $sql = file_get_contents(__DIR__ . '/sql/schema.sql');
            if ($sql === false) {
                throw new RuntimeException('Cannot read sql/schema.sql');
            }
            $sql = preg_replace('/--.*$/m', '', $sql);
            foreach (preg_split('/;\s*[\r\n]+/', $sql) as $chunk) {
                $stmt = trim($chunk);
                if ($stmt === '') {
                    continue;
                }
                $pdo->exec($stmt);
            }
            $hash = password_hash($pass, PASSWORD_DEFAULT);
            $pdo->prepare('INSERT INTO admin_users (username, password_hash) VALUES (?, ?) ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash)')
                ->execute([$user, $hash]);
            foreach ([$cfg['upload_dir'], $cfg['upload_hospital_dir'], $cfg['upload_welcome_dir'], $cfg['upload_admin_dir']] as $upload) {
                if (!is_dir($upload)) {
                    mkdir($upload, 0755, true);
                }
            }
            $done = true;
        } catch (Throwable $e) {
            $error = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>تثبيت النظام</title>
  <link rel="stylesheet" href="assets/css/fonts.css">
  <style>
    body { font-family: var(--font-ar);
      max-width: 420px; margin: 2rem auto; padding: 1rem;
      background: linear-gradient(135deg, #2FAE66, #2C7FB8); min-height: 100vh; color: #fff; }
    label { display: block; margin: 0.75rem 0 0.25rem; }
    input { width: 100%; padding: 0.5rem; border: none; border-radius: 8px; box-sizing: border-box; }
    button { margin-top: 1rem; padding: 0.6rem 1.2rem; border: none; border-radius: 8px;
      background: #fff; color: #2C7FB8; font-weight: 700; cursor: pointer; }
    .err { background: rgba(255,255,255,0.2); padding: 0.5rem; border-radius: 8px; margin-bottom: 1rem; }
  </style>
</head>
<body>
  <h1>تثبيت قاعدة البيانات</h1>
  <?php if ($done): ?>
    <p>تم الإنشاء بنجاح. احذف ملف <code>install.php</code> أو احمِه.</p>
    <p><a href="admin/login.php" style="color:#fff;">دخول لوحة التحكم</a></p>
  <?php else: ?>
    <?php if ($error !== ''): ?><div class="err"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
    <form method="post">
      <label>اسم المستخدم</label>
      <input type="text" name="username" value="admin" required>
      <label>كلمة المرور</label>
      <input type="password" name="password" required>
      <button type="submit">تثبيت</button>
    </form>
  <?php endif; ?>
</body>
</html>
