<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/hospital.php';
require_once dirname(__DIR__) . '/includes/welcome_broadcast.php';
require_admin();

$pdo = db();
$cfg = app_config();
$uploadDir = $cfg['upload_welcome_dir'];
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$row = welcome_broadcast_row($pdo);
$hs = hospital_settings($pdo);
$msg = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? 'save');
    if ($action === 'clear_image') {
        $imgPath = $row['image_path'] ?? null;
        if ($imgPath) {
            unlink_uploaded_basename($uploadDir, (string) $imgPath);
            $pdo->prepare('UPDATE welcome_broadcast SET image_path = NULL WHERE id = 1')->execute();
            $msg = 'تمت إزالة صورة الخلفية.';
        }
        $row = welcome_broadcast_row($pdo);
    } elseif ($action === 'save') {
        $title = trim((string) ($_POST['title'] ?? ''));
        $subtitle = trim((string) ($_POST['subtitle'] ?? ''));
        if (strlen($subtitle) > 512) {
            $subtitle = substr($subtitle, 0, 512);
        }
        if (strlen($title) > 255) {
            $title = substr($title, 0, 255);
        }
        $isEnabled = isset($_POST['is_enabled']) ? 1 : 0;
        $showLogo = isset($_POST['show_logo']) ? 1 : 0;
        $imagePath = $row['image_path'] ?? null;

        if (!empty($_FILES['welcome_image']['tmp_name']) && is_uploaded_file($_FILES['welcome_image']['tmp_name'])) {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = (string) $finfo->file($_FILES['welcome_image']['tmp_name']);
            if (!in_array($mime, $cfg['allowed_image_mimes'], true)) {
                $err = 'الصورة يجب أن تكون JPG أو PNG أو WebP.';
            } elseif ((int) ($_FILES['welcome_image']['size'] ?? 0) > $cfg['upload_max_kb'] * 1024) {
                $err = 'حجم الصورة كبير جدًا.';
            } else {
                $ext = $mime === 'image/png' ? 'png' : ($mime === 'image/webp' ? 'webp' : 'jpg');
                $newName = 'welcome_' . random_token(10) . '.' . $ext;
                $dest = $uploadDir . DIRECTORY_SEPARATOR . $newName;
                if (move_uploaded_file($_FILES['welcome_image']['tmp_name'], $dest)) {
                    if ($imagePath) {
                        unlink_uploaded_basename($uploadDir, (string) $imagePath);
                    }
                    $imagePath = $newName;
                } else {
                    $err = 'فشل رفع الصورة.';
                }
            }
        }

        if ($err === '') {
            $pdo->prepare(
                'UPDATE welcome_broadcast SET is_enabled=?, show_logo=?, title=?, subtitle=?, image_path=? WHERE id=1'
            )->execute([$isEnabled, $showLogo, $title, $subtitle, $imagePath]);
            $msg = 'تم حفظ شاشة الترحيب.';
            $row = welcome_broadcast_row($pdo);
        }
    }
}

$imageUrl = !empty($row['image_path'])
    ? url('uploads/welcome/' . basename((string) $row['image_path']))
    : null;
$logoUrl = !empty($hs['logo_path'])
    ? url('uploads/hospital/' . basename((string) $hs['logo_path']))
    : null;

$adminPageTitle = 'شاشة الترحيب';
$adminNav = 'welcome_screen';
require_once dirname(__DIR__) . '/includes/admin/header.php';
?>

    <?php if ($msg !== ''): ?><div class="alert alert--ok"><?= esc($msg) ?></div><?php endif; ?>
    <?php if ($err !== ''): ?><div class="alert alert--err"><?= esc($err) ?></div><?php endif; ?>

    <section class="saas-card section">
      <div class="saas-card__head">
        <div>
          <h2 class="saas-card__title">ترحيب طاغٍ على كل الشاشات</h2>
          <p class="saas-card__sub">عند التفعيل، تظهر هذه الشاشة فوق عرض الأطباء أو المحتوى على جميع روابط العرض فور تحديث البيانات.</p>
        </div>
      </div>
      <form method="post" enctype="multipart/form-data" class="form">
        <input type="hidden" name="action" value="save">
        <label class="form__check" style="margin-bottom:1rem;display:block">
          <input type="checkbox" name="is_enabled" value="1" <?= (int) ($row['is_enabled'] ?? 0) === 1 ? 'checked' : '' ?>>
          تفعيل شاشة الترحيب الآن
        </label>
        <label class="form__check" style="margin-bottom:1rem;display:block">
          <input type="checkbox" name="show_logo" value="1" <?= (int) ($row['show_logo'] ?? 1) === 1 ? 'checked' : '' ?>>
          إظهار شعار المستشفى (من بيانات المستشفى)
        </label>
        <?php if (!$logoUrl): ?>
          <p class="muted" style="margin:0 0 1rem">لا يوجد شعار مرفوع — ارفع شعارًا من «بيانات المستشفى».</p>
        <?php endif; ?>
        <div class="form__group">
          <label class="form__label">العنوان (كبير)</label>
          <input class="form__input" name="title" value="<?= esc((string) ($row['title'] ?? '')) ?>" placeholder="مثال: أهلاً بكم في مستشفى …" maxlength="255">
        </div>
        <div class="form__group">
          <label class="form__label">نص مصغّر</label>
          <textarea class="form__input" name="subtitle" rows="3" placeholder="رسالة ترحيبية أو تعليمات قصيرة" maxlength="512"><?= esc((string) ($row['subtitle'] ?? '')) ?></textarea>
        </div>
        <div class="form__group">
          <label class="form__label">صورة الخلفية (اختياري)</label>
          <input class="form__input" type="file" name="welcome_image" accept="image/jpeg,image/png,image/webp">
          <?php if ($imageUrl): ?>
            <p class="muted" style="margin-top:.5rem">الصورة الحالية:</p>
            <div class="thumb-preview thumb-preview--wide" style="max-width:420px;margin-top:.35rem">
              <img src="<?= esc($imageUrl) ?>" alt="" style="max-height:200px;object-fit:cover;width:100%;border-radius:12px">
            </div>
          <?php endif; ?>
        </div>
        <div class="form__actions">
          <button class="btn btn--primary" type="submit">حفظ</button>
        </div>
      </form>
      <?php if ($imageUrl): ?>
        <form method="post" class="form" style="margin-top:.75rem" onsubmit="return confirm('إزالة صورة الخلفية؟');">
          <input type="hidden" name="action" value="clear_image">
          <button class="btn btn--ghost" type="submit">إزالة الصورة</button>
        </form>
      <?php endif; ?>
    </section>

<?php require_once dirname(__DIR__) . '/includes/admin/footer.php'; ?>
