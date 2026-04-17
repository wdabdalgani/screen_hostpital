<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_admin();

$pdo = db();
$cfg = app_config();
$uploadDir = $cfg['upload_content_dir'];
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$msg = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'group_add') {
        $name = trim((string) ($_POST['group_name'] ?? ''));
        $loop = isset($_POST['loop_enabled']) ? 1 : 0;
        if ($name === '') {
            $err = 'اسم المجموعة مطلوب.';
        } else {
            try {
                $pdo->prepare('INSERT INTO content_groups (name, loop_enabled) VALUES (?,?)')->execute([$name, $loop]);
                $msg = 'تم إنشاء المجموعة.';
            } catch (Throwable $e) {
                $err = 'اسم المجموعة مستخدم مسبقًا.';
            }
        }
    } elseif ($action === 'group_update') {
        $gid = (int) ($_POST['group_id'] ?? 0);
        $name = trim((string) ($_POST['group_name'] ?? ''));
        $loop = isset($_POST['loop_enabled']) ? 1 : 0;
        if ($gid <= 0 || $name === '') {
            $err = 'بيانات المجموعة غير صالحة.';
        } else {
            $pdo->prepare('UPDATE content_groups SET name = ?, loop_enabled = ? WHERE id = ?')->execute([$name, $loop, $gid]);
            $msg = 'تم تحديث المجموعة.';
        }
    } elseif ($action === 'group_delete') {
        $gid = (int) ($_POST['group_id'] ?? 0);
        if ($gid > 0) {
            $st = $pdo->prepare('SELECT file_path FROM display_contents WHERE group_id = ?');
            $st->execute([$gid]);
            foreach ($st->fetchAll() as $r) {
                unlink_uploaded_basename($uploadDir, (string) ($r['file_path'] ?? ''));
            }
            $pdo->prepare('DELETE FROM content_groups WHERE id = ?')->execute([$gid]);
            $msg = 'تم حذف المجموعة ومحتواها.';
        }
    } elseif ($action === 'content_add') {
        $gid = (int) ($_POST['group_id'] ?? 0);
        $name = trim((string) ($_POST['content_name'] ?? ''));
        $depId = (int) ($_POST['department_id'] ?? 0);
        $doctorId = (int) ($_POST['doctor_id'] ?? 0);
        $dur = max(5, min(15, (int) ($_POST['duration_seconds'] ?? 8)));
        $sort = (int) ($_POST['sort_order'] ?? 0);
        $active = isset($_POST['is_active']) ? 1 : 0;

        if ($gid <= 0 || $name === '') {
            $err = 'المجموعة واسم المحتوى مطلوبان.';
        } elseif (empty($_FILES['content_file']['tmp_name']) || !is_uploaded_file($_FILES['content_file']['tmp_name'])) {
            $err = 'اختر ملفًا للمحتوى.';
        } else {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = (string) $finfo->file($_FILES['content_file']['tmp_name']);
            if (!in_array($mime, $cfg['allowed_content_mimes'], true)) {
                $err = 'نوع الملف غير مدعوم.';
            } elseif ((int) ($_FILES['content_file']['size'] ?? 0) > $cfg['upload_max_kb'] * 1024) {
                $err = 'حجم الملف كبير جدًا.';
            } else {
                $type = str_starts_with($mime, 'video/') ? 'video' : ($mime === 'image/gif' ? 'gif' : 'image');
                $ext = strtolower((string) pathinfo((string) ($_FILES['content_file']['name'] ?? ''), PATHINFO_EXTENSION));
                if ($ext === '') {
                    $ext = $type === 'video' ? 'mp4' : ($type === 'gif' ? 'gif' : 'jpg');
                }
                $basename = random_token(12) . '.' . preg_replace('/[^a-z0-9]/', '', $ext);
                $dest = $uploadDir . DIRECTORY_SEPARATOR . $basename;
                if (!move_uploaded_file($_FILES['content_file']['tmp_name'], $dest)) {
                    $err = 'فشل رفع الملف.';
                } else {
                    $pdo->prepare(
                        'INSERT INTO display_contents (group_id, name, content_type, file_path, department_id, doctor_id, duration_seconds, sort_order, is_active) VALUES (?,?,?,?,?,?,?,?,?)'
                    )->execute([$gid, $name, $type, $basename, $depId > 0 ? $depId : null, $doctorId > 0 ? $doctorId : null, $dur, $sort, $active]);
                    $msg = 'تمت إضافة المحتوى.';
                }
            }
        }
    } elseif ($action === 'content_bulk_add') {
        $bulkMode = (string) ($_POST['bulk_group_mode'] ?? 'existing');
        $gid = (int) ($_POST['group_id'] ?? 0);
        $newGroupName = trim((string) ($_POST['bulk_group_name'] ?? ''));
        $loop = isset($_POST['bulk_loop_enabled']) ? 1 : 0;
        $dur = max(5, min(15, (int) ($_POST['bulk_duration_seconds'] ?? 8)));
        $sortBase = (int) ($_POST['bulk_sort_order_base'] ?? 0);
        $active = isset($_POST['bulk_is_active']) ? 1 : 0;

        if ($bulkMode === 'new') {
            if ($newGroupName === '') {
                $err = 'اسم المجموعة الجديدة مطلوب.';
            } else {
                try {
                    $pdo->prepare('INSERT INTO content_groups (name, loop_enabled) VALUES (?,?)')->execute([$newGroupName, $loop]);
                    $gid = (int) $pdo->lastInsertId();
                } catch (Throwable $e) {
                    $err = 'اسم المجموعة مستخدم مسبقًا.';
                }
            }
        } elseif ($gid <= 0) {
            $err = 'اختر مجموعة لرفع الملفات.';
        }

        if ($err === '') {
            $files = $_FILES['content_files'] ?? null;
            $tmpNames = is_array($files['tmp_name'] ?? null) ? $files['tmp_name'] : (($files['tmp_name'] ?? '') !== '' ? [$files['tmp_name']] : []);
            $origNames = is_array($files['name'] ?? null) ? $files['name'] : (($files['name'] ?? '') !== '' ? [$files['name']] : []);
            $sizes = is_array($files['size'] ?? null) ? $files['size'] : (isset($files['size']) ? [$files['size']] : []);

            if ($tmpNames === []) {
                $err = 'اختر ملفًا واحدًا على الأقل.';
            } else {
                $added = 0;
                $skipped = 0;
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $sortOrder = $sortBase;
                foreach ($tmpNames as $i => $tmp) {
                    if ($tmp === '' || !is_uploaded_file($tmp)) {
                        continue;
                    }
                    $mime = (string) $finfo->file($tmp);
                    if (!in_array($mime, $cfg['allowed_content_mimes'], true)) {
                        $skipped++;
                        continue;
                    }
                    $size = (int) ($sizes[$i] ?? 0);
                    if ($size > $cfg['upload_max_kb'] * 1024) {
                        $skipped++;
                        continue;
                    }
                    $type = str_starts_with($mime, 'video/') ? 'video' : ($mime === 'image/gif' ? 'gif' : 'image');
                    $origName = (string) ($origNames[$i] ?? '');
                    $ext = strtolower((string) pathinfo($origName, PATHINFO_EXTENSION));
                    if ($ext === '') {
                        $ext = $type === 'video' ? 'mp4' : ($type === 'gif' ? 'gif' : 'jpg');
                    }
                    $basename = random_token(12) . '.' . preg_replace('/[^a-z0-9]/', '', $ext);
                    $dest = $uploadDir . DIRECTORY_SEPARATOR . $basename;
                    if (!move_uploaded_file($tmp, $dest)) {
                        $skipped++;
                        continue;
                    }
                    $displayName = (string) pathinfo($origName, PATHINFO_FILENAME);
                    if ($displayName === '') {
                        $displayName = 'محتوى ' . (string) ($sortOrder + 1);
                    }
                    if (function_exists('mb_strlen')) {
                        if (mb_strlen($displayName, 'UTF-8') > 191) {
                            $displayName = mb_substr($displayName, 0, 188, 'UTF-8') . '…';
                        }
                    } elseif (strlen($displayName) > 191) {
                        $displayName = substr($displayName, 0, 188) . '…';
                    }
                    $pdo->prepare(
                        'INSERT INTO display_contents (group_id, name, content_type, file_path, department_id, doctor_id, duration_seconds, sort_order, is_active) VALUES (?,?,?,?,?,?,?,?,?)'
                    )->execute([$gid, $displayName, $type, $basename, null, null, $dur, $sortOrder, $active]);
                    $added++;
                    $sortOrder++;
                }
                if ($added === 0) {
                    $err = 'لم يُرفع أي ملف صالح. تحقق من النوع والحجم.';
                } else {
                    $msg = 'تم رفع ' . $added . ' ملفًا في المجموعة (بدون ربط قسم أو طبيب).';
                    if ($skipped > 0) {
                        $msg .= ' تم تخطي ' . $skipped . ' ملفًا.';
                    }
                }
            }
        }
    } elseif ($action === 'content_update') {
        $id = (int) ($_POST['content_id'] ?? 0);
        $name = trim((string) ($_POST['content_name'] ?? ''));
        $depId = (int) ($_POST['department_id'] ?? 0);
        $doctorId = (int) ($_POST['doctor_id'] ?? 0);
        $dur = max(5, min(15, (int) ($_POST['duration_seconds'] ?? 8)));
        $sort = (int) ($_POST['sort_order'] ?? 0);
        $active = isset($_POST['is_active']) ? 1 : 0;
        if ($id <= 0 || $name === '') {
            $err = 'بيانات المحتوى غير صالحة.';
        } else {
            $pdo->prepare(
                'UPDATE display_contents SET name=?, department_id=?, doctor_id=?, duration_seconds=?, sort_order=?, is_active=? WHERE id=?'
            )->execute([$name, $depId > 0 ? $depId : null, $doctorId > 0 ? $doctorId : null, $dur, $sort, $active, $id]);
            $msg = 'تم تحديث المحتوى.';
        }
    } elseif ($action === 'content_delete') {
        $id = (int) ($_POST['content_id'] ?? 0);
        if ($id > 0) {
            $st = $pdo->prepare('SELECT file_path FROM display_contents WHERE id = ?');
            $st->execute([$id]);
            $file = $st->fetchColumn();
            $pdo->prepare('DELETE FROM display_contents WHERE id = ?')->execute([$id]);
            unlink_uploaded_basename($uploadDir, $file !== false ? (string) $file : null);
            $msg = 'تم حذف المحتوى.';
        }
    }
}

$groups = $pdo->query('SELECT * FROM content_groups ORDER BY name ASC')->fetchAll();
$departments = $pdo->query('SELECT id, name FROM departments ORDER BY sort_order ASC, name ASC')->fetchAll();
$doctors = $pdo->query('SELECT id, name FROM doctors ORDER BY name ASC')->fetchAll();

$search = trim((string) ($_GET['q'] ?? ''));
$typeFilter = trim((string) ($_GET['type'] ?? ''));
$depFilter = (int) ($_GET['department'] ?? 0);

$sql = 'SELECT c.*, g.name AS group_name, d.name AS department_name, doc.name AS doctor_name
        FROM display_contents c
        INNER JOIN content_groups g ON g.id = c.group_id
        LEFT JOIN departments d ON d.id = c.department_id
        LEFT JOIN doctors doc ON doc.id = c.doctor_id
        WHERE 1=1';
$params = [];
if ($search !== '') {
    $sql .= ' AND c.name LIKE ?';
    $params[] = '%' . $search . '%';
}
if (in_array($typeFilter, ['image', 'video', 'gif'], true)) {
    $sql .= ' AND c.content_type = ?';
    $params[] = $typeFilter;
}
if ($depFilter > 0) {
    $sql .= ' AND c.department_id = ?';
    $params[] = $depFilter;
}
$sql .= ' ORDER BY g.name ASC, c.sort_order ASC, c.id DESC';
$st = $pdo->prepare($sql);
$st->execute($params);
$contents = $st->fetchAll();

$adminPageTitle = 'محتوى الشاشات';
$adminNav = 'display_content';
require_once dirname(__DIR__) . '/includes/admin/header.php';
?>
    <?php if ($msg !== ''): ?><div class="alert alert--ok"><?= esc($msg) ?></div><?php endif; ?>
    <?php if ($err !== ''): ?><div class="alert alert--err"><?= esc($err) ?></div><?php endif; ?>

    <section class="saas-card section">
      <div class="saas-card__head">
        <div>
          <h2 class="saas-card__title">مجموعة جديدة</h2>
          <p class="saas-card__sub">أنشئ مجموعات مثل: استقبال / إعلانات / توجيه المرضى</p>
        </div>
      </div>
      <form method="post" class="form form--row">
        <input type="hidden" name="action" value="group_add">
        <div class="form__group">
          <label class="form__label">اسم المجموعة</label>
          <input class="form__input" name="group_name" required>
        </div>
        <label class="form__group form__check"><input type="checkbox" name="loop_enabled" checked> Loop</label>
        <button class="btn btn--primary" type="submit" style="align-self:flex-end">إضافة المجموعة</button>
      </form>
    </section>

    <section class="saas-card section">
      <div class="saas-card__head">
        <div>
          <h2 class="saas-card__title">إضافة محتوى</h2>
          <p class="saas-card__sub">يدعم صورة، فيديو، GIF مع مدة عرض لكل عنصر</p>
        </div>
      </div>
      <form method="post" enctype="multipart/form-data" class="form form--row">
        <input type="hidden" name="action" value="content_add">
        <div class="form__group">
          <label class="form__label">المجموعة</label>
          <select class="form__input" name="group_id" required>
            <option value="">— اختر —</option>
            <?php foreach ($groups as $g): ?>
              <option value="<?= (int) $g['id'] ?>"><?= esc((string) $g['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form__group">
          <label class="form__label">اسم التصميم</label>
          <input class="form__input" name="content_name" required>
        </div>
        <div class="form__group">
          <label class="form__label">الملف</label>
          <input class="form__input" type="file" name="content_file" accept="image/jpeg,image/png,image/webp,image/gif,video/mp4,video/webm" required>
        </div>
        <div class="form__group">
          <label class="form__label">القسم المرتبط</label>
          <select class="form__input" name="department_id">
            <option value="0">بدون</option>
            <?php foreach ($departments as $d): ?>
              <option value="<?= (int) $d['id'] ?>"><?= esc((string) $d['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form__group">
          <label class="form__label">ربط بطبيب</label>
          <select class="form__input" name="doctor_id">
            <option value="0">None (بدون ربط)</option>
            <?php foreach ($doctors as $dr): ?>
              <option value="<?= (int) $dr['id'] ?>"><?= esc((string) $dr['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form__group">
          <label class="form__label">المدة (5-15 ث)</label>
          <input class="form__input form__input--sm" type="number" min="5" max="15" value="8" name="duration_seconds">
        </div>
        <div class="form__group">
          <label class="form__label">الترتيب</label>
          <input class="form__input form__input--sm" type="number" value="0" name="sort_order">
        </div>
        <label class="form__group form__check"><input type="checkbox" name="is_active" checked> مفعّل</label>
        <button class="btn btn--primary" type="submit" style="align-self:flex-end">رفع المحتوى</button>
      </form>
    </section>

    <section class="saas-card section">
      <div class="saas-card__head">
        <div>
          <h2 class="saas-card__title">رفع مجموعة ملفات دفعة واحدة</h2>
          <p class="saas-card__sub">صور أو فيديو أو GIF — بدون ربط بقسم أو طبيب. يمكن اختيار مجموعة موجودة أو إنشاء مجموعة جديدة مع الرفع.</p>
        </div>
      </div>
      <form method="post" enctype="multipart/form-data" class="form form--row">
        <input type="hidden" name="action" value="content_bulk_add">
        <div class="form__group" style="grid-column:1/-1">
          <span class="form__label">المجموعة</span>
          <div style="display:flex;flex-wrap:wrap;gap:1rem;align-items:center;margin-top:.35rem">
            <label class="form__check">
              <input type="radio" name="bulk_group_mode" value="existing" checked> مجموعة موجودة
            </label>
            <label class="form__check">
              <input type="radio" name="bulk_group_mode" value="new"> مجموعة جديدة
            </label>
          </div>
        </div>
        <div class="form__group" data-bulk-existing>
          <label class="form__label">اختر المجموعة</label>
          <select class="form__input" name="group_id">
            <option value="0">— اختر —</option>
            <?php foreach ($groups as $g): ?>
              <option value="<?= (int) $g['id'] ?>"><?= esc((string) $g['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form__group" data-bulk-new style="display:none">
          <label class="form__label">اسم المجموعة الجديدة</label>
          <input class="form__input" name="bulk_group_name" placeholder="مثال: إعلانات الاستقبال">
        </div>
        <label class="form__group form__check" data-bulk-new style="display:none;align-self:flex-end"><input type="checkbox" name="bulk_loop_enabled" checked> Loop للمجموعة الجديدة</label>
        <div class="form__group" style="grid-column:1/-1">
          <label class="form__label">الملفات (متعدد)</label>
          <input class="form__input" type="file" name="content_files[]" accept="image/jpeg,image/png,image/webp,image/gif,video/mp4,video/webm" multiple required>
        </div>
        <div class="form__group">
          <label class="form__label">المدة لكل عنصر (5–15 ث)</label>
          <input class="form__input form__input--sm" type="number" min="5" max="15" value="8" name="bulk_duration_seconds">
        </div>
        <div class="form__group">
          <label class="form__label">ترتيب البداية</label>
          <input class="form__input form__input--sm" type="number" value="0" name="bulk_sort_order_base">
        </div>
        <label class="form__group form__check"><input type="checkbox" name="bulk_is_active" checked> مفعّل</label>
        <button class="btn btn--primary" type="submit" style="align-self:flex-end">رفع المجموعة</button>
      </form>
      <script>
        (function () {
          var trigger = document.querySelector('input[name="action"][value="content_bulk_add"]');
          var form = trigger ? trigger.closest('form') : null;
          if (!form) return;
          var radios = form.querySelectorAll('input[name="bulk_group_mode"]');
          var existing = form.querySelectorAll('[data-bulk-existing]');
          var neu = form.querySelectorAll('[data-bulk-new]');
          function sync() {
            var isNew = form.querySelector('input[name="bulk_group_mode"][value="new"]').checked;
            existing.forEach(function (el) { el.style.display = isNew ? 'none' : ''; });
            neu.forEach(function (el) { el.style.display = isNew ? '' : 'none'; });
          }
          radios.forEach(function (r) { r.addEventListener('change', sync); });
          sync();
        })();
      </script>
    </section>

    <section class="section">
      <h2 class="section__title">بحث وفلترة</h2>
      <form method="get" class="form form--row" style="margin-bottom:.8rem">
        <div class="form__group">
          <label class="form__label">بحث بالاسم</label>
          <input class="form__input" type="search" name="q" value="<?= esc($search) ?>">
        </div>
        <div class="form__group">
          <label class="form__label">النوع</label>
          <select class="form__input" name="type">
            <option value="">الكل</option>
            <option value="image" <?= $typeFilter === 'image' ? 'selected' : '' ?>>صورة</option>
            <option value="video" <?= $typeFilter === 'video' ? 'selected' : '' ?>>فيديو</option>
            <option value="gif" <?= $typeFilter === 'gif' ? 'selected' : '' ?>>GIF</option>
          </select>
        </div>
        <div class="form__group">
          <label class="form__label">القسم</label>
          <select class="form__input" name="department">
            <option value="0">الكل</option>
            <?php foreach ($departments as $d): ?>
              <option value="<?= (int) $d['id'] ?>" <?= $depFilter === (int) $d['id'] ? 'selected' : '' ?>><?= esc((string) $d['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <button class="btn btn--ghost" type="submit" style="align-self:flex-end">تطبيق</button>
      </form>

      <?php foreach ($groups as $g): ?>
        <div class="screen-card" style="margin-bottom:.9rem">
          <form method="post" class="screen-card__row">
            <input type="hidden" name="action" value="group_update">
            <input type="hidden" name="group_id" value="<?= (int) $g['id'] ?>">
            <label class="form__label">المجموعة</label>
            <input class="form__input" name="group_name" value="<?= esc((string) $g['name']) ?>" required>
            <label class="form__check"><input type="checkbox" name="loop_enabled" <?= (int) $g['loop_enabled'] === 1 ? 'checked' : '' ?>> Loop</label>
            <button class="btn btn--primary" type="submit">حفظ</button>
          </form>
          <form method="post" class="screen-card__del" onsubmit="return confirm('حذف المجموعة ومحتواها؟');">
            <input type="hidden" name="action" value="group_delete">
            <input type="hidden" name="group_id" value="<?= (int) $g['id'] ?>">
            <button class="btn btn--danger" type="submit">حذف المجموعة</button>
          </form>
        </div>
      <?php endforeach; ?>

      <div class="doctor-cards-grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(270px,1fr));gap:1rem">
        <?php foreach ($contents as $c): ?>
          <?php
            $url = url('uploads/content/' . basename((string) $c['file_path']));
            $type = (string) $c['content_type'];
            $icon = $type === 'video' ? '🎬' : ($type === 'gif' ? '🔁' : '🖼️');
          ?>
          <article class="saas-card" style="padding:.8rem">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.4rem">
              <strong><?= esc((string) $c['name']) ?></strong>
              <span><?= $icon ?> <?= esc($type) ?></span>
            </div>
            <div class="thumb-preview thumb-preview--wide" style="margin-bottom:.45rem">
              <?php if ($type === 'video'): ?>
                <video src="<?= esc($url) ?>" controls preload="metadata" style="width:100%;max-height:200px;border-radius:12px"></video>
              <?php else: ?>
                <img src="<?= esc($url) ?>" alt="">
              <?php endif; ?>
            </div>
            <p class="muted" style="margin:0 0 .35rem">المجموعة: <?= esc((string) $c['group_name']) ?> · القسم: <?= esc((string) ($c['department_name'] ?? 'بدون')) ?> · الطبيب: <?= esc((string) ($c['doctor_name'] ?? 'None')) ?></p>

            <form method="post" class="form">
              <input type="hidden" name="action" value="content_update">
              <input type="hidden" name="content_id" value="<?= (int) $c['id'] ?>">
              <label class="form__label">الاسم</label>
              <input class="form__input" name="content_name" value="<?= esc((string) $c['name']) ?>" required>
              <div class="form__row">
                <div>
                  <label class="form__label">المدة</label>
                  <input class="form__input form__input--sm" type="number" name="duration_seconds" min="5" max="15" value="<?= (int) $c['duration_seconds'] ?>">
                </div>
                <div>
                  <label class="form__label">الترتيب</label>
                  <input class="form__input form__input--sm" type="number" name="sort_order" value="<?= (int) $c['sort_order'] ?>">
                </div>
              </div>
              <label class="form__label">القسم</label>
              <select class="form__input" name="department_id">
                <option value="0">بدون</option>
                <?php foreach ($departments as $d): ?>
                  <option value="<?= (int) $d['id'] ?>" <?= (int) $c['department_id'] === (int) $d['id'] ? 'selected' : '' ?>><?= esc((string) $d['name']) ?></option>
                <?php endforeach; ?>
              </select>
              <label class="form__label">ربط بطبيب</label>
              <select class="form__input" name="doctor_id">
                <option value="0">None</option>
                <?php foreach ($doctors as $dr): ?>
                  <option value="<?= (int) $dr['id'] ?>" <?= (int) $c['doctor_id'] === (int) $dr['id'] ? 'selected' : '' ?>><?= esc((string) $dr['name']) ?></option>
                <?php endforeach; ?>
              </select>
              <label class="form__check"><input type="checkbox" name="is_active" <?= (int) $c['is_active'] === 1 ? 'checked' : '' ?>> مفعّل</label>
              <div class="form__actions">
                <button class="btn btn--primary" type="submit">حفظ</button>
                <button class="btn btn--danger" type="submit" formaction="<?= esc(url('admin/display_content.php')) ?>" name="action" value="content_delete" onclick="return confirm('حذف هذا المحتوى؟');">حذف</button>
              </div>
            </form>
          </article>
        <?php endforeach; ?>
      </div>
    </section>

<?php require_once dirname(__DIR__) . '/includes/admin/footer.php'; ?>
