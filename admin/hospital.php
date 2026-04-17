<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/hospital.php';
require_admin();

$pdo = db();
$cfg = app_config();
$uploadDir = $cfg['upload_hospital_dir'];
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$row = hospital_settings($pdo);
$msg = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim((string) ($_POST['name'] ?? ''));
    $phone = trim((string) ($_POST['phone'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $address = trim((string) ($_POST['address'] ?? ''));
    $website = trim((string) ($_POST['website'] ?? ''));
    $social_facebook = trim((string) ($_POST['social_facebook'] ?? ''));
    $social_instagram = trim((string) ($_POST['social_instagram'] ?? ''));
    $social_x = trim((string) ($_POST['social_x'] ?? ''));
    $social_youtube = trim((string) ($_POST['social_youtube'] ?? ''));

    $logoPath = $row['logo_path'] ?? null;
    if (!empty($_FILES['logo']['tmp_name']) && is_uploaded_file($_FILES['logo']['tmp_name'])) {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($_FILES['logo']['tmp_name']);
        if (!in_array($mime, $cfg['allowed_image_mimes'], true)) {
            $err = 'الشعار يجب أن يكون صورة (JPG / PNG / WebP).';
        } elseif ($_FILES['logo']['size'] > $cfg['upload_max_kb'] * 1024) {
            $err = 'حجم الملف كبير.';
        } else {
            $ext = $mime === 'image/png' ? 'png' : ($mime === 'image/webp' ? 'webp' : 'jpg');
            $newName = 'logo_' . random_token(8) . '.' . $ext;
            $dest = $uploadDir . DIRECTORY_SEPARATOR . $newName;
            if (move_uploaded_file($_FILES['logo']['tmp_name'], $dest)) {
                if ($logoPath) {
                    $oldp = $uploadDir . DIRECTORY_SEPARATOR . basename($logoPath);
                    if (is_file($oldp)) {
                        @unlink($oldp);
                    }
                }
                $logoPath = $newName;
            } else {
                $err = 'فشل رفع الشعار.';
            }
        }
    }

    if ($err === '') {
        $pdo->prepare(
            'UPDATE hospital_settings SET name=?, logo_path=?, phone=?, email=?, address=?, website=?, social_facebook=?, social_instagram=?, social_x=?, social_youtube=? WHERE id=1'
        )->execute([
            $name,
            $logoPath,
            $phone,
            $email,
            $address,
            $website,
            $social_facebook,
            $social_instagram,
            $social_x,
            $social_youtube,
        ]);
        $msg = 'تم حفظ بيانات المستشفى.';
        $row = hospital_settings($pdo);
    }
}

$adminPageTitle = 'بيانات المستشفى';
$adminNav = 'hospital';
require_once dirname(__DIR__) . '/includes/admin/header.php';
$logoUrl = !empty($row['logo_path']) ? url('uploads/hospital/' . basename((string) $row['logo_path'])) : null;
?>

    <?php if ($msg !== ''): ?><div class="alert alert--ok"><?= esc($msg) ?></div><?php endif; ?>
    <?php if ($err !== ''): ?><div class="alert alert--err"><?= esc($err) ?></div><?php endif; ?>

    <div class="saas-card">
      <div class="saas-card__head">
        <div>
          <h2 class="saas-card__title">الهوية والتواصل</h2>
          <p class="saas-card__sub">يظهر الاسم في الشريط الجانبي؛ الشعار للاستخدامات المستقبلية</p>
        </div>
      </div>
      <form method="post" enctype="multipart/form-data" class="form">
        <label class="form__label">اسم المستشفى</label>
        <input class="form__input" name="name" required value="<?= esc((string) ($row['name'] ?? '')) ?>">

        <label class="form__label">شعار المستشفى</label>
        <?php if ($logoUrl): ?>
          <div class="hospital-logo-preview"><img src="<?= esc($logoUrl) ?>" alt=""></div>
        <?php endif; ?>
        <input class="form__input" type="file" name="logo" accept="image/jpeg,image/png,image/webp">

        <div class="form__row">
          <div>
            <label class="form__label">الهاتف</label>
            <input class="form__input" name="phone" value="<?= esc((string) ($row['phone'] ?? '')) ?>" dir="ltr">
          </div>
          <div>
            <label class="form__label">البريد</label>
            <input class="form__input" type="email" name="email" value="<?= esc((string) ($row['email'] ?? '')) ?>" dir="ltr">
          </div>
        </div>

        <label class="form__label">العنوان</label>
        <textarea class="form__input" name="address" rows="3" style="resize:vertical"><?= esc((string) ($row['address'] ?? '')) ?></textarea>

        <label class="form__label">الموقع الإلكتروني</label>
        <input class="form__input" name="website" value="<?= esc((string) ($row['website'] ?? '')) ?>" dir="ltr" placeholder="https://">

        <p class="form__label" style="margin-top:0.5rem">وسائل التواصل</p>
        <div class="form__row">
          <div>
            <label class="form__label">Facebook</label>
            <input class="form__input" name="social_facebook" value="<?= esc((string) ($row['social_facebook'] ?? '')) ?>" dir="ltr">
          </div>
          <div>
            <label class="form__label">Instagram</label>
            <input class="form__input" name="social_instagram" value="<?= esc((string) ($row['social_instagram'] ?? '')) ?>" dir="ltr">
          </div>
        </div>
        <div class="form__row">
          <div>
            <label class="form__label">X / Twitter</label>
            <input class="form__input" name="social_x" value="<?= esc((string) ($row['social_x'] ?? '')) ?>" dir="ltr">
          </div>
          <div>
            <label class="form__label">YouTube</label>
            <input class="form__input" name="social_youtube" value="<?= esc((string) ($row['social_youtube'] ?? '')) ?>" dir="ltr">
          </div>
        </div>

        <button class="btn btn--primary" type="submit">حفظ</button>
      </form>
    </div>

<?php require_once dirname(__DIR__) . '/includes/admin/footer.php'; ?>
