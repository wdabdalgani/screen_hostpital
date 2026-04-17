<?php
declare(strict_types=1);

/**
 * صفحة تشخيص النظام — Hospital Doctor Display System
 * ================================================
 * افتح هذه الصفحة بعد الرفع على الاستضافة لفحص كل شيء.
 * احذف هذا الملف فور الانتهاء من التشخيص لأسباب أمنية.
 */

// ── حماية بسيطة بكلمة مرور ──────────────────────────────────────────────────
define('CHECK_PASSWORD', 'check1234'); // غيّر هذه الكلمة قبل الرفع
$auth = false;
if (isset($_GET['pass']) && $_GET['pass'] === CHECK_PASSWORD) {
    $auth = true;
    setcookie('check_auth', CHECK_PASSWORD, time() + 3600, '/');
}
if (!$auth && isset($_COOKIE['check_auth']) && $_COOKIE['check_auth'] === CHECK_PASSWORD) {
    $auth = true;
}
if (!$auth) {
    ?>
    <!DOCTYPE html><html lang="ar" dir="rtl"><head><meta charset="UTF-8"><title>تشخيص النظام</title>
    <style>body{font-family:sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;background:#0f172a;color:#e2e8f0;margin:0}
    .box{background:#1e293b;padding:2rem;border-radius:16px;width:340px;text-align:center}
    input{width:100%;padding:.6rem;border-radius:8px;border:1px solid #334155;background:#0f172a;color:#e2e8f0;box-sizing:border-box;margin:.8rem 0}
    button{width:100%;padding:.7rem;background:#3b82f6;color:#fff;border:none;border-radius:8px;cursor:pointer;font-size:1rem}
    </style></head><body>
    <div class="box">
        <h2>🔒 صفحة التشخيص</h2>
        <p style="color:#94a3b8">أدخل كلمة المرور للدخول</p>
        <form method="get">
            <input type="password" name="pass" placeholder="كلمة المرور" autofocus>
            <button type="submit">دخول</button>
        </form>
    </div>
    </body></html>
    <?php
    exit;
}
// ─────────────────────────────────────────────────────────────────────────────

// جمع النتائج
$results = [];
$totalOk  = 0;
$totalWarn= 0;
$totalErr = 0;

function r(string $section, string $label, string $status, string $value, string $fix = ''): void
{
    global $results, $totalOk, $totalWarn, $totalErr;
    $results[] = compact('section', 'label', 'status', 'value', 'fix');
    if ($status === 'ok')   $totalOk++;
    if ($status === 'warn') $totalWarn++;
    if ($status === 'err')  $totalErr++;
}

$root = __DIR__;

// ═══════════════════════════════════════════════════════════════════
// 1. PHP — الإصدار والإعدادات
// ═══════════════════════════════════════════════════════════════════
$phpV = PHP_VERSION;
$phpOk = version_compare($phpV, '8.0.0', '>=');
r('PHP', 'إصدار PHP', $phpOk ? 'ok' : 'err', $phpV, 'يجب PHP 8.0 أو أحدث. غيّر الإصدار من لوحة الاستضافة.');

r('PHP', 'PDO Extension', extension_loaded('pdo') ? 'ok' : 'err',
    extension_loaded('pdo') ? 'مفعّل' : 'غير مفعّل',
    'فعّل pdo من إعدادات PHP في لوحة الاستضافة.');

r('PHP', 'PDO MySQL Driver', extension_loaded('pdo_mysql') ? 'ok' : 'err',
    extension_loaded('pdo_mysql') ? 'مفعّل' : 'غير مفعّل',
    'فعّل pdo_mysql من إعدادات PHP في لوحة الاستضافة.');

r('PHP', 'FileInfo Extension', extension_loaded('fileinfo') ? 'ok' : 'warn',
    extension_loaded('fileinfo') ? 'مفعّل' : 'غير مفعّل',
    'مطلوب للتحقق من نوع الملفات المرفوعة. فعّله من إعدادات PHP.');

r('PHP', 'GD / Imagick', (extension_loaded('gd') || extension_loaded('imagick')) ? 'ok' : 'warn',
    extension_loaded('gd') ? 'GD مفعّل' : (extension_loaded('imagick') ? 'Imagick مفعّل' : 'غير مفعّل'),
    'مستحسن لمعالجة الصور.');

r('PHP', 'mbstring', extension_loaded('mbstring') ? 'ok' : 'warn',
    extension_loaded('mbstring') ? 'مفعّل' : 'غير مفعّل',
    'مطلوب للنصوص العربية.');

r('PHP', 'session.use_cookies', (bool)ini_get('session.use_cookies') ? 'ok' : 'warn',
    ini_get('session.use_cookies') ?: '0', 'يجب أن يكون 1.');

$uploadMaxBytes = (int)ini_get('upload_max_filesize');
$uploadMaxVal   = ini_get('upload_max_filesize');
r('PHP', 'upload_max_filesize', $uploadMaxBytes >= 2 ? 'ok' : 'warn',
    $uploadMaxVal, 'يُفضّل 4M أو أكثر.');

$postMaxVal = ini_get('post_max_size');
r('PHP', 'post_max_size', 'ok', $postMaxVal);

r('PHP', 'display_errors', ini_get('display_errors') == '0' ? 'ok' : 'warn',
    ini_get('display_errors') ?: '0',
    'يُفضّل إيقافه في الإنتاج. أضف "display_errors = Off" في php.ini أو .htaccess.');

r('PHP', 'error_reporting', 'ok', (string)ini_get('error_reporting'));

// ═══════════════════════════════════════════════════════════════════
// 2. الملفات الأساسية
// ═══════════════════════════════════════════════════════════════════
$coreFiles = [
    'config/config.php'          => 'إعدادات التطبيق',
    'config/database.php'        => 'اتصال قاعدة البيانات',
    'includes/functions.php'     => 'الدوال المشتركة',
    'includes/auth.php'          => 'نظام المصادقة',
    'includes/migrations.php'    => 'ترحيل قاعدة البيانات',
    'includes/hospital.php'      => 'إعدادات المستشفى',
    'includes/display_styles.php'=> 'استايلات العرض',
    'includes/welcome_broadcast.php' => 'الترحيب البث',
    'includes/dashboard_stats.php'   => 'إحصائيات لوحة التحكم',
    'includes/department_icons.php'  => 'أيقونات الأقسام',
    'includes/admin/header.php'      => 'رأس لوحة التحكم',
    'includes/admin/footer.php'      => 'ذيل لوحة التحكم',
    'includes/admin/icons.php'       => 'أيقونات الإدارة',
    'includes/stats.php'             => 'الإحصائيات',
    'admin/login.php'            => 'صفحة تسجيل الدخول',
    'admin/dashboard.php'        => 'لوحة التحكم',
    'admin/doctors.php'          => 'إدارة الأطباء',
    'admin/screens.php'          => 'إدارة الشاشات',
    'admin/departments.php'      => 'إدارة الأقسام',
    'admin/hospital.php'         => 'بيانات المستشفى',
    'admin/display_styles.php'   => 'استايلات العرض',
    'admin/display_content.php'  => 'محتوى الشاشات',
    'admin/welcome_screen.php'   => 'شاشة الترحيب',
    'admin/account.php'          => 'حساب المشرف',
    'admin/doctor_edit.php'      => 'تعديل الطبيب',
    'admin/logout.php'           => 'تسجيل الخروج',
    'api/display.php'            => 'API العرض',
    'api/admin_doctor_actions.php' => 'API إجراءات الأطباء',
    'display.php'                => 'صفحة العرض',
    'display_manifest.php'       => 'PWA Manifest',
    'display-sw.js'              => 'Service Worker',
    'index.php'                  => 'الصفحة الرئيسية',
    'sql/schema.sql'             => 'مخطط قاعدة البيانات',
    'assets/css/admin.css'       => 'CSS الإدارة',
    'assets/css/display.css'     => 'CSS العرض',
    'assets/css/fonts.css'       => 'CSS الخطوط',
    'assets/js/admin.js'         => 'JS الإدارة',
    'assets/js/display.js'       => 'JS العرض',
    'theme/style.css'            => 'قالب الحريري CSS',
];

foreach ($coreFiles as $file => $label) {
    $path = $root . '/' . $file;
    $exists = file_exists($path);
    r('الملفات', $label . ' (' . $file . ')', $exists ? 'ok' : 'err',
        $exists ? 'موجود ✓' : 'مفقود ✗',
        'ارفع الملف "' . $file . '" من مجلد المشروع.');
}

// ═══════════════════════════════════════════════════════════════════
// 3. مجلدات الرفع
// ═══════════════════════════════════════════════════════════════════
$uploadDirs = [
    'uploads/doctors'     => 'صور الأطباء',
    'uploads/departments' => 'صور الأقسام',
    'uploads/content'     => 'محتوى الشاشات',
    'uploads/hospital'    => 'شعار المستشفى',
    'uploads/welcome'     => 'صورة الترحيب',
    'uploads/admin'       => 'صورة المشرف',
];

foreach ($uploadDirs as $dir => $label) {
    $path = $root . '/' . $dir;
    if (!is_dir($path)) {
        r('المجلدات', $label . ' (' . $dir . ')', 'err',
            'مفقود ✗', 'أنشئ المجلد "' . $dir . '" يدوياً أو شغّل install.php.');
    } elseif (!is_writable($path)) {
        r('المجلدات', $label . ' (' . $dir . ')', 'err',
            'موجود لكن غير قابل للكتابة ✗',
            'غيّر صلاحيات المجلد إلى 755 من File Manager أو FTP.');
    } else {
        r('المجلدات', $label . ' (' . $dir . ')', 'ok', 'موجود وقابل للكتابة ✓');
    }
}

// تحقق من .htaccess في مجلدات الرفع
foreach (['uploads/hospital', 'uploads/admin'] as $dir) {
    $htPath = $root . '/' . $dir . '/.htaccess';
    $exists = file_exists($htPath);
    r('المجلدات', '.htaccess في ' . $dir, $exists ? 'ok' : 'warn',
        $exists ? 'موجود ✓' : 'مفقود',
        'أضف ملف .htaccess في المجلد لمنع تنفيذ PHP داخله.');
}

// ═══════════════════════════════════════════════════════════════════
// 4. اتصال قاعدة البيانات
// ═══════════════════════════════════════════════════════════════════
$cfgPath = $root . '/config/config.php';
$cfg = null;
$dbOk = false;
$pdo  = null;
$dbError = '';
$adminUserExists = false;

if (file_exists($cfgPath)) {
    try {
        $cfg = require $cfgPath;
        $d = $cfg['db'];

        // اختبار الاتصال
        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $d['host'], $d['port'], $d['name'], $d['charset']);
        $pdo = new PDO($dsn, $d['user'], $d['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $dbOk = true;

        r('قاعدة البيانات', 'الاتصال بقاعدة البيانات', 'ok',
            'تم الاتصال بـ ' . $d['name'] . ' على ' . $d['host']);

        // إصدار MySQL
        $ver = $pdo->query('SELECT VERSION()')->fetchColumn();
        r('قاعدة البيانات', 'إصدار MySQL/MariaDB', 'ok', (string)$ver);

        // charset
        $cs = $pdo->query("SHOW VARIABLES LIKE 'character_set_database'")->fetch();
        $csVal = $cs ? (string)$cs['Value'] : '?';
        r('قاعدة البيانات', 'Charset قاعدة البيانات', str_starts_with($csVal, 'utf8') ? 'ok' : 'warn',
            $csVal, 'يُفضّل utf8mb4.');

        // الجداول المطلوبة
        $requiredTables = [
            'schema_migrations', 'departments', 'doctors', 'screens',
            'display_styles', 'content_groups', 'display_contents',
            'hospital_settings', 'admin_users', 'doctor_weekly_schedule',
            'dashboard_stat_snapshots', 'welcome_broadcast',
        ];
        foreach ($requiredTables as $tbl) {
            $exists = (int)$pdo->query(
                "SELECT COUNT(*) FROM information_schema.TABLES
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '{$tbl}'"
            )->fetchColumn() > 0;
            r('الجداول', 'جدول: ' . $tbl, $exists ? 'ok' : 'err',
                $exists ? 'موجود ✓' : 'مفقود ✗',
                'شغّل install.php لإنشاء الجداول تلقائياً.');
        }

        // آخر migration
        $migrExists = (int)$pdo->query(
            "SELECT COUNT(*) FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'schema_migrations'"
        )->fetchColumn() > 0;
        if ($migrExists) {
            $lastV = $pdo->query('SELECT MAX(version) FROM schema_migrations')->fetchColumn();
            $lastV = $lastV ?? 0;
            r('قاعدة البيانات', 'آخر Migration مطبّق', (int)$lastV >= 16 ? 'ok' : 'warn',
                'v' . $lastV, 'المطلوب v16 على الأقل. شغّل install.php أو افتح أي صفحة لتشغيل الترحيل تلقائياً.');
        }

        // حساب المشرف
        $adminTblExists = (int)$pdo->query(
            "SELECT COUNT(*) FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'admin_users'"
        )->fetchColumn() > 0;
        if ($adminTblExists) {
            $adminCount = (int)$pdo->query('SELECT COUNT(*) FROM admin_users')->fetchColumn();
            $adminUserExists = $adminCount > 0;
            r('قاعدة البيانات', 'حسابات المشرف', $adminUserExists ? 'ok' : 'err',
                $adminUserExists ? 'يوجد ' . $adminCount . ' حساب ✓' : 'لا يوجد حساب مشرف ✗',
                'شغّل install.php وأنشئ حساب المشرف.');
        }

    } catch (Throwable $e) {
        $dbError = $e->getMessage();
        r('قاعدة البيانات', 'الاتصال بقاعدة البيانات', 'err',
            'فشل الاتصال ✗',
            'الخطأ: ' . $dbError);

        // تشخيص محتمل للخطأ
        if (str_contains($dbError, 'Connection refused') || str_contains($dbError, '111')) {
            r('قاعدة البيانات', 'سبب محتمل', 'warn',
                'الخادم يرفض الاتصال', 'جرّب تغيير host من 127.0.0.1 إلى localhost في config/config.php');
        }
        if (str_contains($dbError, 'Access denied')) {
            r('قاعدة البيانات', 'سبب محتمل', 'warn',
                'رفض الوصول — بيانات الدخول خاطئة',
                'تحقق من اسم المستخدم وكلمة المرور واسم قاعدة البيانات في config/config.php');
        }
        if (str_contains($dbError, 'Unknown database')) {
            r('قاعدة البيانات', 'سبب محتمل', 'warn',
                'قاعدة البيانات غير موجودة',
                'أنشئ قاعدة البيانات أولاً من لوحة الاستضافة (hPanel → Databases)');
        }
        if (str_contains($dbError, 'getaddrinfo') || str_contains($dbError, 'php_network')) {
            r('قاعدة البيانات', 'سبب محتمل', 'warn',
                'لا يمكن الوصول للـ host',
                'جرّب تغيير host إلى localhost في config/config.php');
        }
    }
} else {
    r('قاعدة البيانات', 'ملف config/config.php', 'err',
        'مفقود ✗', 'ارفع ملف config/config.php وعدّل إعدادات قاعدة البيانات.');
}

// ═══════════════════════════════════════════════════════════════════
// 5. إعدادات التطبيق
// ═══════════════════════════════════════════════════════════════════
if ($cfg) {
    $baseUrl = (string)($cfg['base_url'] ?? '');
    r('الإعدادات', 'base_url', 'ok',
        $baseUrl === '' ? '(فارغ — كشف تلقائي)' : $baseUrl);

    // فحص base_url التلقائي
    $docRoot = realpath((string)($_SERVER['DOCUMENT_ROOT'] ?? ''));
    $projRoot = realpath(__DIR__);
    if ($docRoot && $projRoot) {
        $rel = str_replace('\\', '/', substr($projRoot, strlen($docRoot)));
        $detectedBase = $rel === '' ? '/' : $rel;
        r('الإعدادات', 'base_url المكتشف تلقائياً', 'ok', $detectedBase);
    }

    r('الإعدادات', 'session_name', 'ok', (string)($cfg['session_name'] ?? 'hariri_admin'));
    r('الإعدادات', 'upload_max_kb', 'ok', (string)($cfg['upload_max_kb'] ?? 4096) . ' KB');

    // فحص الـ host في config
    $dbHost = (string)($cfg['db']['host'] ?? '');
    if ($dbHost === '127.0.0.1' && !$dbOk) {
        r('الإعدادات', 'DB Host', 'warn', $dbHost,
            'الاتصال فشل مع 127.0.0.1 — جرّب تغييره إلى localhost في config/config.php');
    } else {
        r('الإعدادات', 'DB Host', 'ok', $dbHost);
    }
    r('الإعدادات', 'DB Name', 'ok', (string)($cfg['db']['name'] ?? ''));
    r('الإعدادات', 'DB User', 'ok', (string)($cfg['db']['user'] ?? ''));
}

// ═══════════════════════════════════════════════════════════════════
// 6. فحص install.php
// ═══════════════════════════════════════════════════════════════════
$installExists = file_exists($root . '/install.php');
if ($installExists && $dbOk && $adminUserExists) {
    r('الأمان', 'ملف install.php', 'warn',
        'موجود بعد التثبيت ✗',
        'احذف install.php فوراً من الاستضافة لأسباب أمنية.');
} elseif ($installExists && !$dbOk) {
    r('الأمان', 'ملف install.php', 'ok',
        'موجود — جاهز للتثبيت ✓',
        'افتح install.php في المتصفح لإنشاء الجداول وحساب المشرف.');
} elseif (!$installExists && !$adminUserExists) {
    r('الأمان', 'ملف install.php', 'err',
        'مفقود ولم يُنشأ حساب مشرف ✗',
        'ارفع install.php ثم شغّله لإنشاء قاعدة البيانات والحساب.');
} else {
    r('الأمان', 'ملف install.php', 'ok', 'تم حذفه ✓');
}

// فحص check.php نفسه
r('الأمان', 'ملف check.php (هذا الملف)', 'warn',
    'موجود ✗', 'احذف check.php بعد انتهاء التشخيص لأسباب أمنية.');

// ═══════════════════════════════════════════════════════════════════
// 7. معلومات الخادم
// ═══════════════════════════════════════════════════════════════════
r('الخادم', 'Server Software', 'ok', (string)($_SERVER['SERVER_SOFTWARE'] ?? 'غير معروف'));
r('الخادم', 'Document Root', 'ok', (string)($_SERVER['DOCUMENT_ROOT'] ?? 'غير معروف'));
r('الخادم', 'مسار المشروع', 'ok', $root);
r('الخادم', 'PHP SAPI', 'ok', PHP_SAPI);
r('الخادم', 'نظام التشغيل', 'ok', PHP_OS);
r('الخادم', 'الوقت الحالي للخادم', 'ok', date('Y-m-d H:i:s'));
r('الخادم', 'منطقة الوقت', 'ok', date_default_timezone_get());

// ─────────────────────────────────────────────────────────────────────────────
// عرض النتائج HTML
// ─────────────────────────────────────────────────────────────────────────────
$sections = array_unique(array_column($results, 'section'));
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>تشخيص النظام — Hospital Display</title>
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
:root {
    --bg: #0f172a; --surface: #1e293b; --border: #334155;
    --ok: #22c55e; --ok-bg: rgba(34,197,94,.12);
    --warn: #f59e0b; --warn-bg: rgba(245,158,11,.12);
    --err: #ef4444; --err-bg: rgba(239,68,68,.12);
    --text: #e2e8f0; --muted: #94a3b8; --accent: #3b82f6;
    --radius: 12px; --font: 'Segoe UI', Tahoma, sans-serif;
}
body { background: var(--bg); color: var(--text); font-family: var(--font); min-height: 100vh; padding: 1.5rem; }
h1 { font-size: 1.6rem; font-weight: 700; margin-bottom: .3rem; }
.sub { color: var(--muted); font-size: .92rem; margin-bottom: 1.5rem; }
.summary {
    display: flex; gap: 1rem; flex-wrap: wrap; margin-bottom: 2rem;
}
.summary__card {
    flex: 1; min-width: 130px; background: var(--surface);
    border-radius: var(--radius); padding: 1rem 1.2rem;
    display: flex; align-items: center; gap: .8rem; border: 1px solid var(--border);
}
.summary__card--ok   { border-color: var(--ok);   background: var(--ok-bg);   }
.summary__card--warn { border-color: var(--warn);  background: var(--warn-bg); }
.summary__card--err  { border-color: var(--err);   background: var(--err-bg);  }
.summary__num { font-size: 2rem; font-weight: 800; }
.summary__card--ok   .summary__num { color: var(--ok);   }
.summary__card--warn .summary__num { color: var(--warn);  }
.summary__card--err  .summary__num { color: var(--err);   }
.summary__lbl { color: var(--muted); font-size: .88rem; }
.nav { display: flex; gap: .5rem; flex-wrap: wrap; margin-bottom: 1.5rem; }
.nav a {
    padding: .4rem .9rem; border-radius: 20px; background: var(--surface);
    border: 1px solid var(--border); color: var(--muted); text-decoration: none;
    font-size: .88rem; transition: all .15s;
}
.nav a:hover { border-color: var(--accent); color: var(--text); }
.section { margin-bottom: 1.5rem; }
.section__title {
    font-size: 1rem; font-weight: 700; color: var(--muted);
    text-transform: uppercase; letter-spacing: .08em;
    margin-bottom: .7rem; padding-bottom: .4rem;
    border-bottom: 1px solid var(--border);
    display: flex; align-items: center; gap: .5rem;
}
.section__badge {
    font-size: .75rem; padding: .2rem .55rem; border-radius: 20px;
    font-weight: 600; letter-spacing: 0;
}
.badge-ok   { background: var(--ok-bg);   color: var(--ok);   }
.badge-warn { background: var(--warn-bg); color: var(--warn);  }
.badge-err  { background: var(--err-bg);  color: var(--err);   }
table { width: 100%; border-collapse: collapse; }
tr { border-bottom: 1px solid var(--border); }
tr:last-child { border-bottom: none; }
td { padding: .65rem .5rem; font-size: .9rem; vertical-align: top; }
td:first-child { color: var(--muted); width: 36%; white-space: nowrap; }
td:nth-child(2) { font-weight: 500; }
.status-dot {
    display: inline-block; width: 8px; height: 8px;
    border-radius: 50%; margin-left: .4rem; vertical-align: middle;
}
.dot-ok   { background: var(--ok); box-shadow: 0 0 6px var(--ok); }
.dot-warn { background: var(--warn); box-shadow: 0 0 6px var(--warn); }
.dot-err  { background: var(--err); box-shadow: 0 0 6px var(--err); }
.fix-btn {
    display: inline-block; margin-top: .3rem;
    background: var(--surface); border: 1px solid var(--border);
    border-radius: 6px; padding: .25rem .6rem; font-size: .8rem;
    color: var(--warn); cursor: pointer; text-align: right; line-height: 1.4;
}
.row-ok   { background: transparent; }
.row-warn { background: rgba(245,158,11,.04); }
.row-err  { background: rgba(239,68,68,.05); }
.logout { float: left; }
.logout a {
    padding: .4rem .9rem; border-radius: 8px;
    background: var(--err-bg); border: 1px solid var(--err);
    color: var(--err); text-decoration: none; font-size: .88rem;
}
@media (max-width: 640px) {
    td:first-child { white-space: normal; width: auto; }
    .summary { flex-direction: column; }
}
</style>
</head>
<body>

<div style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:1rem;margin-bottom:.5rem">
    <div>
        <h1>🏥 تشخيص النظام</h1>
        <p class="sub">Hospital Doctor Display System — فحص شامل لبيئة الاستضافة</p>
    </div>
    <div class="logout"><a href="?logout=1" onclick="document.cookie='check_auth=;expires=Thu,01 Jan 1970 00:00:00 GMT;path=/';location.reload();return false;">خروج</a></div>
</div>

<!-- ملخص -->
<div class="summary">
    <div class="summary__card summary__card--ok">
        <div class="summary__num"><?= $totalOk ?></div>
        <div class="summary__lbl">✓ سليم</div>
    </div>
    <div class="summary__card summary__card--warn">
        <div class="summary__num"><?= $totalWarn ?></div>
        <div class="summary__lbl">⚠ تحذير</div>
    </div>
    <div class="summary__card summary__card--err">
        <div class="summary__num"><?= $totalErr ?></div>
        <div class="summary__lbl">✗ خطأ</div>
    </div>
    <div class="summary__card" style="border-color:var(--accent)">
        <div class="summary__num" style="color:var(--accent)"><?= $totalOk + $totalWarn + $totalErr ?></div>
        <div class="summary__lbl">إجمالي الفحوصات</div>
    </div>
</div>

<!-- روابط الأقسام -->
<nav class="nav">
<?php foreach ($sections as $sec):
    $secItems = array_filter($results, fn($r) => $r['section'] === $sec);
    $hasErr = in_array('err', array_column(iterator_to_array((function() use ($secItems) { yield from $secItems; })(), false), 'status'));
    $hasWarn = in_array('warn', array_column(iterator_to_array((function() use ($secItems) { yield from $secItems; })(), false), 'status'));
    $cls = $hasErr ? 'err' : ($hasWarn ? 'warn' : 'ok');
?>
    <a href="#sec-<?= urlencode($sec) ?>">
        <?= htmlspecialchars($sec, ENT_QUOTES, 'UTF-8') ?>
        <span class="section__badge badge-<?= $cls ?>"><?= count($secItems) ?></span>
    </a>
<?php endforeach; ?>
</nav>

<!-- النتائج التفصيلية -->
<?php foreach ($sections as $sec):
    $secItems = array_filter($results, fn($r) => $r['section'] === $sec);
    $errs  = count(array_filter($secItems, fn($r) => $r['status'] === 'err'));
    $warns = count(array_filter($secItems, fn($r) => $r['status'] === 'warn'));
    $cls   = $errs ? 'err' : ($warns ? 'warn' : 'ok');
?>
<section class="section" id="sec-<?= urlencode($sec) ?>">
    <h2 class="section__title">
        <?= htmlspecialchars($sec, ENT_QUOTES, 'UTF-8') ?>
        <?php if ($errs): ?><span class="section__badge badge-err"><?= $errs ?> خطأ</span><?php endif; ?>
        <?php if ($warns): ?><span class="section__badge badge-warn"><?= $warns ?> تحذير</span><?php endif; ?>
        <?php if (!$errs && !$warns): ?><span class="section__badge badge-ok">✓ سليم</span><?php endif; ?>
    </h2>
    <div style="background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden">
    <table>
    <?php foreach ($secItems as $row): ?>
    <tr class="row-<?= $row['status'] ?>">
        <td><?= htmlspecialchars($row['label'], ENT_QUOTES, 'UTF-8') ?></td>
        <td>
            <span class="status-dot dot-<?= $row['status'] ?>"></span>
            <?= htmlspecialchars($row['value'], ENT_QUOTES, 'UTF-8') ?>
            <?php if ($row['fix'] !== '' && $row['status'] !== 'ok'): ?>
            <br><span class="fix-btn">🔧 <?= htmlspecialchars($row['fix'], ENT_QUOTES, 'UTF-8') ?></span>
            <?php endif; ?>
        </td>
    </tr>
    <?php endforeach; ?>
    </table>
    </div>
</section>
<?php endforeach; ?>

<!-- روابط سريعة -->
<div style="margin-top:2rem;display:flex;gap:.8rem;flex-wrap:wrap">
<?php if ($installExists): ?>
    <a href="install.php" target="_blank" style="padding:.5rem 1rem;background:rgba(59,130,246,.15);border:1px solid #3b82f6;color:#3b82f6;border-radius:8px;text-decoration:none;font-size:.9rem">
        🚀 فتح install.php
    </a>
<?php endif; ?>
<?php if ($dbOk && $adminUserExists): ?>
    <a href="admin/login.php" target="_blank" style="padding:.5rem 1rem;background:rgba(34,197,94,.15);border:1px solid #22c55e;color:#22c55e;border-radius:8px;text-decoration:none;font-size:.9rem">
        🔑 فتح صفحة تسجيل الدخول
    </a>
<?php endif; ?>
    <a href="?" style="padding:.5rem 1rem;background:var(--surface);border:1px solid var(--border);color:var(--muted);border-radius:8px;text-decoration:none;font-size:.9rem">
        🔄 إعادة فحص
    </a>
</div>

<p style="margin-top:2rem;color:var(--muted);font-size:.82rem;text-align:center">
    ⚠ احذف ملف <code>check.php</code> فور انتهاء التشخيص لأسباب أمنية.
</p>

</body>
</html>
