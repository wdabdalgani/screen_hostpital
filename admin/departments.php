<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/department_icons.php';
require_admin();

$pdo = db();
$cfg = app_config();
$depUploadDir = $cfg['upload_departments_dir'];
if (!is_dir($depUploadDir)) {
    mkdir($depUploadDir, 0755, true);
}

$msg = '';
$err = '';

$defaultDepId = (int) $pdo->query('SELECT id FROM departments WHERE name = \'عام\' LIMIT 1')->fetchColumn();
if ($defaultDepId <= 0) {
    $defaultDepId = (int) $pdo->query('SELECT id FROM departments ORDER BY id ASC LIMIT 1')->fetchColumn();
}

$bannerErr = static function (string $code): string {
    return match ($code) {
        'type' => 'صورة القسم يجب أن تكون JPG أو PNG أو WebP.',
        'size' => 'حجم صورة القسم كبير جداً.',
        'move' => 'فشل رفع صورة القسم.',
        default => 'خطأ في رفع الصورة.',
    };
};

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');
    if ($action === 'add') {
        $name = trim((string) ($_POST['name'] ?? ''));
        $icon = preg_replace('/[^a-z0-9_]/', '', strtolower((string) ($_POST['icon'] ?? 'layers'))) ?: 'layers';
        $sort = (int) ($_POST['sort_order'] ?? 0);
        if ($name === '') {
            $err = 'أدخل اسم القسم.';
        } else {
            $up = save_uploaded_image_file($_FILES['banner'] ?? [], $depUploadDir, $cfg);
            if (!$up['ok']) {
                $err = $bannerErr($up['error'] ?? '');
            } else {
                try {
                    $pdo->prepare(
                        'INSERT INTO departments (name, icon, banner_image_path, sort_order) VALUES (?,?,?,?)'
                    )->execute([$name, $icon, $up['basename'], $sort]);
                    $msg = 'تمت إضافة القسم.';
                } catch (Throwable $e) {
                    if ($up['basename']) {
                        unlink_uploaded_basename($depUploadDir, $up['basename']);
                    }
                    $err = 'اسم القسم موجود مسبقاً أو خطأ في الحفظ.';
                }
            }
        }
    } elseif ($action === 'edit') {
        $id = (int) ($_POST['id'] ?? 0);
        $name = trim((string) ($_POST['name'] ?? ''));
        $icon = preg_replace('/[^a-z0-9_]/', '', strtolower((string) ($_POST['icon'] ?? 'layers'))) ?: 'layers';
        $sort = (int) ($_POST['sort_order'] ?? 0);
        $removeBanner = isset($_POST['remove_banner']);
        if ($name === '' || $id <= 0) {
            $err = 'بيانات غير صالحة.';
        } else {
            $st = $pdo->prepare('SELECT banner_image_path FROM departments WHERE id = ? LIMIT 1');
            $st->execute([$id]);
            $prev = $st->fetch();
            if (!$prev) {
                $err = 'القسم غير موجود.';
            } else {
                $prevPath = $prev['banner_image_path'] ?? null;
                $up = save_uploaded_image_file($_FILES['banner'] ?? [], $depUploadDir, $cfg);
                if (!$up['ok']) {
                    $err = $bannerErr($up['error'] ?? '');
                } else {
                    $newPath = $prevPath;
                    if (!empty($up['basename'])) {
                        $newPath = $up['basename'];
                    } elseif ($removeBanner) {
                        $newPath = null;
                    }
                }
                if ($err === '') {
                    try {
                        $pdo->prepare(
                            'UPDATE departments SET name=?, icon=?, banner_image_path=?, sort_order=? WHERE id=?'
                        )->execute([$name, $icon, $newPath, $sort, $id]);
                        if ($prevPath !== $newPath) {
                            unlink_uploaded_basename($depUploadDir, $prevPath);
                        }
                        $msg = 'تم الحفظ.';
                    } catch (Throwable $e) {
                        if (!empty($up['basename'])) {
                            unlink_uploaded_basename($depUploadDir, $up['basename']);
                        }
                        $err = 'تعذر الحفظ (ربما الاسم مكرر).';
                    }
                }
            }
        }
    } elseif ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0 && $id !== $defaultDepId) {
            $st = $pdo->prepare('SELECT banner_image_path FROM departments WHERE id = ?');
            $st->execute([$id]);
            $b = $st->fetchColumn();
            if ($defaultDepId > 0) {
                $pdo->prepare('UPDATE doctors SET department_id = ? WHERE department_id = ?')->execute([$defaultDepId, $id]);
            }
            $pdo->prepare('DELETE FROM departments WHERE id = ?')->execute([$id]);
            unlink_uploaded_basename($depUploadDir, $b !== false ? (string) $b : null);
            $msg = 'تم الحذف.';
        } else {
            $err = 'لا يمكن حذف القسم الافتراضي «عام».';
        }
    }
}

$departments = $pdo->query('SELECT * FROM departments ORDER BY sort_order ASC, name ASC')->fetchAll();
$iconOpts = department_icon_options();

$adminPageTitle = 'الأقسام';
$adminNav = 'departments';
require_once dirname(__DIR__) . '/includes/admin/header.php';
?>

    <?php if ($msg !== ''): ?><div class="alert alert--ok"><?= esc($msg) ?></div><?php endif; ?>
    <?php if ($err !== ''): ?><div class="alert alert--err"><?= esc($err) ?></div><?php endif; ?>

    <section class="saas-card section">
      <div class="saas-card__head">
        <div>
          <h2 class="saas-card__title">قسم جديد</h2>
          <p class="saas-card__sub">اسم، أيقونة، وصورة خلفية رمزية لشاشة العرض</p>
        </div>
      </div>
      <form method="post" enctype="multipart/form-data" class="form form--row">
        <input type="hidden" name="action" value="add">
        <div class="form__group">
          <label class="form__label">اسم القسم</label>
          <input class="form__input" name="name" required placeholder="مثال: الباطنية">
        </div>
        <div class="form__group">
          <label class="form__label">الأيقونة</label>
          <select class="form__input" name="icon">
            <?php foreach ($iconOpts as $opt): ?>
              <option value="<?= esc($opt['value']) ?>"><?= esc($opt['label']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form__group">
          <label class="form__label">الترتيب</label>
          <input class="form__input form__input--sm" type="number" name="sort_order" value="0">
        </div>
        <div class="form__group" style="grid-column: 1 / -1">
          <label class="form__label">صورة القسم (خلفية شاشة العرض)</label>
          <input class="form__input" type="file" name="banner" accept="image/jpeg,image/png,image/webp" data-banner-preview="banner_preview_add">
          <div class="thumb-preview thumb-preview--wide" id="banner_preview_add" hidden></div>
          <p class="muted" style="margin:0.35rem 0 0;font-size:0.85rem">اختياري — تُعرض بشكل ضبابي خلف صورة الطبيب</p>
        </div>
        <button class="btn btn--primary" type="submit" style="align-self:flex-end">إضافة</button>
      </form>
    </section>

    <section class="section">
      <h2 class="section__title">القائمة</h2>
      <?php if (!count($departments)): ?>
        <div class="saas-card"><p class="muted" style="margin:0">لا توجد أقسام.</p></div>
      <?php endif; ?>
      <?php foreach ($departments as $d): ?>
        <?php
          $stc = $pdo->prepare('SELECT COUNT(*) FROM doctors WHERE department_id = ?');
        $stc->execute([(int) $d['id']]);
        $docCount = (int) $stc->fetchColumn();
        $bid = (int) $d['id'];
        $hasBanner = !empty($d['banner_image_path']);
        ?>
        <div class="screen-card">
          <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" value="<?= $bid ?>">
            <div class="screen-card__head">
              <span class="dept-ic-preview" aria-hidden="true"><?= department_icon_svg((string) $d['icon']) ?></span>
              <input class="form__input" name="name" value="<?= esc($d['name']) ?>" required>
              <button class="btn btn--primary" type="submit">حفظ</button>
            </div>
            <div class="screen-card__row">
              <label class="form__label">أيقونة</label>
              <select class="form__input" name="icon" style="max-width:220px">
                <?php foreach ($iconOpts as $opt): ?>
                  <option value="<?= esc($opt['value']) ?>" <?= $d['icon'] === $opt['value'] ? 'selected' : '' ?>><?= esc($opt['label']) ?></option>
                <?php endforeach; ?>
              </select>
              <label class="form__label">ترتيب</label>
              <input class="form__input form__input--sm" type="number" name="sort_order" value="<?= (int) $d['sort_order'] ?>">
              <span class="muted" style="margin-inline-start:0.5rem">أطباء: <?= $docCount ?></span>
            </div>
            <div class="screen-card__row screen-card__row--stack">
              <label class="form__label">صورة القسم</label>
              <?php if ($hasBanner): ?>
                <div class="thumb-preview thumb-preview--wide">
                  <img src="<?= esc(url('uploads/departments/' . basename((string) $d['banner_image_path']))) ?>" alt="">
                </div>
              <?php endif; ?>
              <input class="form__input" type="file" name="banner" accept="image/jpeg,image/png,image/webp" data-banner-preview="banner_preview_<?= $bid ?>">
              <div class="thumb-preview thumb-preview--wide" id="banner_preview_<?= $bid ?>" hidden></div>
              <?php if ($hasBanner): ?>
                <label class="form__check"><input type="checkbox" name="remove_banner" value="1"> حذف صورة القسم الحالية</label>
              <?php endif; ?>
            </div>
          </form>
          <?php if ((int) $d['id'] !== $defaultDepId): ?>
            <form method="post" class="screen-card__del" onsubmit="return confirm('حذف القسم؟ سيتم نقل أطبائه إلى «عام».');">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?= (int) $d['id'] ?>">
              <button class="btn btn--danger" type="submit">حذف</button>
            </form>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </section>

    <script>
      (function () {
        document.querySelectorAll('input[data-banner-preview]').forEach(function (input) {
          var id = input.getAttribute('data-banner-preview');
          var box = id ? document.getElementById(id) : null;
          if (!box) return;
          input.addEventListener('change', function () {
            var f = input.files && input.files[0];
            box.innerHTML = '';
            if (!f) {
              box.hidden = true;
              return;
            }
            var img = document.createElement('img');
            img.alt = '';
            img.src = URL.createObjectURL(f);
            box.appendChild(img);
            box.hidden = false;
          });
        });
      })();
    </script>

<?php require_once dirname(__DIR__) . '/includes/admin/footer.php'; ?>
