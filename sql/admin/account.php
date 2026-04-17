<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_admin();

$pdo = db();
$cfg = app_config();
$uploadDir = $cfg['upload_admin_dir'];
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$id = (int) ($_SESSION['admin_id'] ?? 0);
$st = $pdo->prepare('SELECT * FROM admin_users WHERE id = ? LIMIT 1');
$st->execute([$id]);
$row = $st->fetch();
if (!$row) {
    header('Location: ' . url('admin/logout.php'));
    exit;
}

$msg = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $section = (string) ($_POST['section'] ?? 'profile');

    if ($section === 'password') {
        $old = (string) ($_POST['old_password'] ?? '');
        $new = (string) ($_POST['new_password'] ?? '');
        $new2 = (string) ($_POST['new_password2'] ?? '');
        if ($old === '' || $new === '') {
            $err = 'أدخل كلمة المرور الحالية والجديدة.';
        } elseif ($new !== $new2) {
            $err = 'تأكيد كلمة المرور غير متطابق.';
        } elseif (strlen($new) < 6) {
            $err = 'كلمة المرور الجديدة قصيرة جداً (6 أحرف على الأقل).';
        } elseif (!password_verify($old, (string) $row['password_hash'])) {
            $err = 'كلمة المرور الحالية غير صحيحة.';
        } else {
            $hash = password_hash($new, PASSWORD_DEFAULT);
            $pdo->prepare('UPDATE admin_users SET password_hash = ? WHERE id = ?')->execute([$hash, $id]);
            $msg = 'تم تغيير كلمة المرور.';
            $st = $pdo->prepare('SELECT * FROM admin_users WHERE id = ? LIMIT 1');
            $st->execute([$id]);
            $row = $st->fetch();
        }
    } else {
        $fullName = trim((string) ($_POST['full_name'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $phone = trim((string) ($_POST['phone'] ?? ''));
        $photoPath = $row['photo_path'] ?? null;

        if (!empty($_FILES['photo']['tmp_name']) && is_uploaded_file($_FILES['photo']['tmp_name'])) {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($_FILES['photo']['tmp_name']);
            if (!in_array($mime, $cfg['allowed_image_mimes'], true)) {
                $err = 'الصورة يجب أن تكون JPG أو PNG أو WebP.';
            } elseif ($_FILES['photo']['size'] > $cfg['upload_max_kb'] * 1024) {
                $err = 'حجم الصورة كبير.';
            } else {
                $ext = $mime === 'image/png' ? 'png' : ($mime === 'image/webp' ? 'webp' : 'jpg');
                $newName = 'admin_' . random_token(10) . '.' . $ext;
                $dest = $uploadDir . DIRECTORY_SEPARATOR . $newName;
                if (move_uploaded_file($_FILES['photo']['tmp_name'], $dest)) {
                    if ($photoPath) {
                        $oldp = $uploadDir . DIRECTORY_SEPARATOR . basename((string) $photoPath);
                        if (is_file($oldp)) {
                            @unlink($oldp);
                        }
                    }
                    $photoPath = $newName;
                } else {
                    $err = 'فشل رفع الصورة.';
                }
            }
        }

        if ($err === '') {
            $pdo->prepare('UPDATE admin_users SET full_name=?, email=?, phone=?, photo_path=? WHERE id=?')->execute([
                $fullName !== '' ? $fullName : null,
                $email !== '' ? $email : null,
                $phone !== '' ? $phone : null,
                $photoPath,
                $id,
            ]);
            $msg = 'تم حفظ الملف الشخصي.';
            $st->execute([$id]);
            $row = $st->fetch();
        }
    }
}

$adminPageTitle = 'حسابي';
$adminNav = 'account';
require_once dirname(__DIR__) . '/includes/admin/header.php';
$photoUrl = !empty($row['photo_path']) ? url('uploads/admin/' . basename((string) $row['photo_path'])) : null;
?>

    <?php if ($msg !== ''): ?><div class="alert alert--ok"><?= esc($msg) ?></div><?php endif; ?>
    <?php if ($err !== ''): ?><div class="alert alert--err"><?= esc($err) ?></div><?php endif; ?>

    <div class="saas-card section">
      <div class="saas-card__head">
        <div>
          <h2 class="saas-card__title">الملف الشخصي</h2>
          <p class="saas-card__sub">الاسم الظاهر في الواجهة وبيانات التواصل</p>
        </div>
      </div>
      <form method="post" enctype="multipart/form-data" class="form">
        <input type="hidden" name="section" value="profile">
        <p class="form__label">اسم المستخدم (ثابت)</p>
        <input class="form__input" value="<?= esc((string) $row['username']) ?>" disabled>

        <label class="form__label">الاسم الكامل</label>
        <input class="form__input" name="full_name" value="<?= esc((string) ($row['full_name'] ?? '')) ?>">

        <div class="form__row">
          <div>
            <label class="form__label">البريد</label>
            <input class="form__input" type="email" name="email" value="<?= esc((string) ($row['email'] ?? '')) ?>" dir="ltr">
          </div>
          <div>
            <label class="form__label">الهاتف</label>
            <input class="form__input" name="phone" value="<?= esc((string) ($row['phone'] ?? '')) ?>" dir="ltr">
          </div>
        </div>

        <label class="form__label">صورة الحساب</label>
        <?php if ($photoUrl): ?>
          <div class="account-photo-preview"><img src="<?= esc($photoUrl) ?>" alt=""></div>
        <?php endif; ?>
        <input class="form__input" type="file" name="photo" accept="image/jpeg,image/png,image/webp">

        <button class="btn btn--primary" type="submit">حفظ الملف</button>
      </form>
    </div>

    <div class="saas-card section">
      <div class="saas-card__head">
        <div>
          <h2 class="saas-card__title">كلمة المرور</h2>
          <p class="saas-card__sub">تغيير آمن للدخول إلى لوحة التحكم</p>
        </div>
      </div>
      <form method="post" class="form">
        <input type="hidden" name="section" value="password">
        <label class="form__label">كلمة المرور الحالية</label>
        <input class="form__input" type="password" name="old_password" autocomplete="current-password">

        <label class="form__label">كلمة المرور الجديدة</label>
        <input class="form__input" type="password" name="new_password" autocomplete="new-password">

        <label class="form__label">تأكيد الجديدة</label>
        <input class="form__input" type="password" name="new_password2" autocomplete="new-password">

        <button class="btn btn--primary" type="submit">تحديث كلمة المرور</button>
      </form>
    </div>

<?php require_once dirname(__DIR__) . '/includes/admin/footer.php'; ?>
