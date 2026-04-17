<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_admin();

$pdo = db();
$cfg = app_config();
$uploadDir = $cfg['upload_dir'];
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$screenGet = isset($_GET['screen']) ? (int) $_GET['screen'] : 0;
$row = null;
if ($id > 0) {
    $st = $pdo->prepare('SELECT * FROM doctors WHERE id = ? LIMIT 1');
    $st->execute([$id]);
    $row = $st->fetch();
    if (!$row) {
        header('Location: ' . url('admin/doctors.php'));
        exit;
    }
}

$screenId = $row ? (int) $row['screen_id'] : $screenGet;
$screens = $pdo->query('SELECT id, name FROM screens ORDER BY name ASC')->fetchAll();
if ($screenId <= 0 && count($screens)) {
    $screenId = (int) $screens[0]['id'];
}

$departments = $pdo->query('SELECT id, name FROM departments ORDER BY sort_order ASC, name ASC')->fetchAll();
$defaultDepId = (int) $pdo->query('SELECT id FROM departments WHERE name = \'عام\' LIMIT 1')->fetchColumn();
if ($defaultDepId <= 0 && count($departments)) {
    $defaultDepId = (int) $departments[0]['id'];
}

$weekdayLabels = [
    1 => 'الإثنين',
    2 => 'الثلاثاء',
    3 => 'الأربعاء',
    4 => 'الخميس',
    5 => 'الجمعة',
    6 => 'السبت',
    7 => 'الأحد',
];

/** @var array<int,array{on:bool,start:string,end:string}> */
$scheduleByDay = [];
for ($w = 1; $w <= 7; $w++) {
    $scheduleByDay[$w] = ['on' => false, 'start' => '08:00', 'end' => '14:00'];
}
if ($row) {
    $st = $pdo->prepare(
        'SELECT weekday, work_start, work_end FROM doctor_weekly_schedule WHERE doctor_id = ? ORDER BY weekday, sort_order, id'
    );
    $st->execute([(int) $row['id']]);
    $got = $st->fetchAll(PDO::FETCH_ASSOC);
    if (count($got) === 0) {
        $ds = substr((string) ($row['work_start'] ?? '08:00:00'), 0, 5);
        $de = substr((string) ($row['work_end'] ?? '14:00:00'), 0, 5);
        for ($w = 1; $w <= 7; $w++) {
            $scheduleByDay[$w] = ['on' => true, 'start' => $ds, 'end' => $de];
        }
    } else {
        foreach ($got as $gr) {
            $wd = (int) $gr['weekday'];
            if ($wd < 1 || $wd > 7) {
                continue;
            }
            $scheduleByDay[$wd] = [
                'on' => true,
                'start' => substr((string) $gr['work_start'], 0, 5),
                'end' => substr((string) $gr['work_end'], 0, 5),
            ];
        }
    }
} else {
    for ($w = 1; $w <= 7; $w++) {
        $scheduleByDay[$w] = ['on' => true, 'start' => '08:00', 'end' => '14:00'];
    }
}

$msg = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? 'save');
    $postId = (int) ($_POST['id'] ?? 0);
    $screenId = (int) ($_POST['screen_id'] ?? 0);
    if ($action === 'delete' && $postId > 0) {
        $st = $pdo->prepare('SELECT image_path FROM doctors WHERE id=?');
        $st->execute([$postId]);
        $old = $st->fetch();
        $pdo->prepare('DELETE FROM doctors WHERE id=?')->execute([$postId]);
        if ($old && !empty($old['image_path'])) {
            $p = $uploadDir . DIRECTORY_SEPARATOR . basename($old['image_path']);
            if (is_file($p)) {
                @unlink($p);
            }
        }
        header('Location: ' . url('admin/doctors.php?screen=' . $screenId));
        exit;
    }

    $name = trim((string) ($_POST['name'] ?? ''));
    $spec = trim((string) ($_POST['specialty'] ?? ''));
    $departmentId = (int) ($_POST['department_id'] ?? 0);
    if ($departmentId <= 0) {
        $departmentId = $defaultDepId;
    }
    $weeklyParsed = parse_weekly_schedule_from_post($_POST);
    $statusMode = ($_POST['status_mode'] ?? 'auto') === 'manual' ? 'manual' : 'auto';
    $manual = ($_POST['manual_status'] ?? 'available') === 'unavailable' ? 'unavailable' : 'available';
    $sort = (int) ($_POST['sort_order'] ?? 0);

    if ($name === '' || $screenId <= 0) {
        $err = 'الاسم والشاشة مطلوبان.';
    } elseif ($weeklyParsed === []) {
        $err = 'فعّل يوماً واحداً على الأقل وأدخل أوقات الدوام.';
    } else {
        [$repS, $repE] = doctor_representative_hours($weeklyParsed, '08:00:00', '14:00:00');
        $ws = $repS;
        $we = $repE;
        $imgPath = $row['image_path'] ?? null;
        if (!empty($_FILES['image']['tmp_name']) && is_uploaded_file($_FILES['image']['tmp_name'])) {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($_FILES['image']['tmp_name']);
            if (!in_array($mime, $cfg['allowed_image_mimes'], true)) {
                $err = 'الصورة يجب أن تكون JPG أو PNG أو WebP.';
            } elseif ($_FILES['image']['size'] > $cfg['upload_max_kb'] * 1024) {
                $err = 'حجم الصورة كبير جداً.';
            } else {
                $ext = $mime === 'image/png' ? 'png' : ($mime === 'image/webp' ? 'webp' : 'jpg');
                $newName = random_token(12) . '.' . $ext;
                $dest = $uploadDir . DIRECTORY_SEPARATOR . $newName;
                if (move_uploaded_file($_FILES['image']['tmp_name'], $dest)) {
                    if ($imgPath) {
                        $oldp = $uploadDir . DIRECTORY_SEPARATOR . basename($imgPath);
                        if (is_file($oldp)) {
                            @unlink($oldp);
                        }
                    }
                    $imgPath = $newName;
                } else {
                    $err = 'فشل رفع الصورة.';
                }
            }
        }

        if ($err === '') {
            if ($postId > 0) {
                try {
                    $pdo->beginTransaction();
                    $pdo->prepare(
                        'UPDATE doctors SET screen_id=?, name=?, specialty=?, department_id=?, image_path=?, work_start=?, work_end=?, status_mode=?, manual_status=?, sort_order=? WHERE id=?'
                    )->execute([$screenId, $name, $spec, $departmentId, $imgPath, $ws, $we, $statusMode, $manual, $sort, $postId]);
                    doctor_save_weekly_schedule($pdo, $postId, $weeklyParsed);
                    $pdo->commit();
                } catch (Throwable $e) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    $err = 'تعذر حفظ الجدول. حاول مرة أخرى.';
                }
                if ($err === '') {
                    $msg = 'تم الحفظ.';
                    $st = $pdo->prepare('SELECT * FROM doctors WHERE id=?');
                    $st->execute([$postId]);
                    $row = $st->fetch();
                    $scheduleByDay = [];
                    for ($w = 1; $w <= 7; $w++) {
                        $scheduleByDay[$w] = ['on' => false, 'start' => '08:00', 'end' => '14:00'];
                    }
                    $st2 = $pdo->prepare(
                        'SELECT weekday, work_start, work_end FROM doctor_weekly_schedule WHERE doctor_id = ? ORDER BY weekday, sort_order, id'
                    );
                    $st2->execute([$postId]);
                    foreach ($st2->fetchAll(PDO::FETCH_ASSOC) as $gr) {
                        $wd = (int) $gr['weekday'];
                        if ($wd >= 1 && $wd <= 7) {
                            $scheduleByDay[$wd] = [
                                'on' => true,
                                'start' => substr((string) $gr['work_start'], 0, 5),
                                'end' => substr((string) $gr['work_end'], 0, 5),
                            ];
                        }
                    }
                }
            } else {
                try {
                    $pdo->beginTransaction();
                    $pdo->prepare(
                        'INSERT INTO doctors (screen_id, name, specialty, department_id, image_path, work_start, work_end, status_mode, manual_status, sort_order) VALUES (?,?,?,?,?,?,?,?,?,?)'
                    )->execute([$screenId, $name, $spec, $departmentId, $imgPath, $ws, $we, $statusMode, $manual, $sort]);
                    $newId = (int) $pdo->lastInsertId();
                    doctor_save_weekly_schedule($pdo, $newId, $weeklyParsed);
                    $pdo->commit();
                    header('Location: ' . url('admin/doctor_edit.php?id=' . $newId . '&screen=' . $screenId . '&saved=1'));
                    exit;
                } catch (Throwable $e) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    $err = 'تعذر حفظ الطبيب أو الجدول. حاول مرة أخرى.';
                }
            }
        }
    }

    if ($err !== '') {
        $rawSch = $_POST['schedule'] ?? [];
        if (is_array($rawSch)) {
            for ($w = 1; $w <= 7; $w++) {
                $cell = $rawSch[(string) $w] ?? $rawSch[$w] ?? [];
                $scheduleByDay[$w] = [
                    'on' => is_array($cell) && !empty($cell['on']),
                    'start' => is_array($cell) ? substr(normalize_sql_time((string) ($cell['start'] ?? '08:00')), 0, 5) : '08:00',
                    'end' => is_array($cell) ? substr(normalize_sql_time((string) ($cell['end'] ?? '14:00')), 0, 5) : '14:00',
                ];
            }
        }
    }
}

if (isset($_GET['saved'])) {
    $msg = 'تمت الإضافة.';
}

$adminPageTitle = $row ? 'تعديل طبيب' : 'طبيب جديد';
$adminNav = 'doctors';
$adminMainClass = 'saas-main main--narrow';
require_once dirname(__DIR__) . '/includes/admin/header.php';
?>

    <?php if ($msg !== ''): ?><div class="alert alert--ok"><?= esc($msg) ?></div><?php endif; ?>
    <?php if ($err !== ''): ?><div class="alert alert--err"><?= esc($err) ?></div><?php endif; ?>
    <?php if (!count($departments)): ?>
      <div class="alert alert--err">لا توجد أقسام في النظام. أضف قسمًا من «الأقسام» ثم عد هنا.</div>
    <?php endif; ?>

    <div class="saas-card">
      <div class="saas-card__head">
        <div>
          <h2 class="saas-card__title"><?= $row ? 'تعديل البيانات' : 'بيانات الطبيب' ?></h2>
          <p class="saas-card__sub">الحقول الأساسية والصورة وجدول الدوام الأسبوعي (يُحسب التوفر التلقائي حسب يوم اليوم)</p>
        </div>
      </div>

      <form method="post" enctype="multipart/form-data" class="form">
        <input type="hidden" name="id" value="<?= $row ? (int) $row['id'] : 0 ?>">

        <label class="form__label">الشاشة</label>
        <select class="form__input" name="screen_id" required>
          <?php foreach ($screens as $s): ?>
            <option value="<?= (int) $s['id'] ?>" <?= $screenId === (int) $s['id'] ? 'selected' : '' ?>><?= esc($s['name']) ?></option>
          <?php endforeach; ?>
        </select>

        <label class="form__label">الاسم</label>
        <input class="form__input" name="name" required value="<?= esc($row['name'] ?? '') ?>">

        <label class="form__label">التخصص</label>
        <input class="form__input" name="specialty" value="<?= esc($row['specialty'] ?? '') ?>">

        <label class="form__label">القسم</label>
        <select class="form__input" name="department_id" required>
          <?php
            $curDep = (int) ($row['department_id'] ?? $defaultDepId);
          ?>
          <?php foreach ($departments as $dep): ?>
            <option value="<?= (int) $dep['id'] ?>" <?= $curDep === (int) $dep['id'] ? 'selected' : '' ?>><?= esc($dep['name']) ?></option>
          <?php endforeach; ?>
        </select>

        <label class="form__label">صورة الطبيب</label>
        <?php if ($row && !empty($row['image_path'])): ?>
          <div class="thumb-preview"><img src="<?= esc(url('uploads/doctors/' . basename($row['image_path']))) ?>" alt=""></div>
        <?php endif; ?>
        <input class="form__input" type="file" name="image" accept="image/jpeg,image/png,image/webp">

        <label class="form__label">جدول الدوام حسب أيام الأسبوع</label>
        <p class="form__hint" style="margin:-0.35rem 0 0.75rem;font-size:0.9rem;opacity:0.85">فعّل الأيام التي يحضر فيها الطبيب وحدد بداية ونهاية كل يوم. يدعم الدوام الليلي إذا كانت النهاية قبل البداية (مثال 22:00–06:00).</p>
        <div class="weekly-schedule-table-wrap" style="overflow-x:auto;margin-bottom:1rem">
          <table class="weekly-schedule-table" style="width:100%;border-collapse:collapse;font-size:0.95rem">
            <thead>
              <tr style="text-align:right;border-bottom:1px solid var(--saas-border,#ddd)">
                <th style="padding:0.5rem 0.25rem">يوم</th>
                <th style="padding:0.5rem 0.25rem;width:2.5rem">دوام</th>
                <th style="padding:0.5rem 0.25rem">من</th>
                <th style="padding:0.5rem 0.25rem">إلى</th>
              </tr>
            </thead>
            <tbody>
              <?php for ($w = 1; $w <= 7; $w++):
                  $sd = $scheduleByDay[$w] ?? ['on' => false, 'start' => '08:00', 'end' => '14:00'];
                  $lab = $weekdayLabels[$w] ?? (string) $w;
                  ?>
                <tr style="border-bottom:1px solid var(--saas-border,#eee)">
                  <td style="padding:0.45rem 0.25rem"><?= esc($lab) ?></td>
                  <td style="padding:0.45rem 0.25rem;text-align:center">
                    <input type="checkbox" name="schedule[<?= $w ?>][on]" value="1" <?= !empty($sd['on']) ? 'checked' : '' ?> aria-label="<?= esc($lab) ?> — دوام">
                  </td>
                  <td style="padding:0.35rem 0.25rem">
                    <input class="form__input" style="min-width:7rem" type="time" name="schedule[<?= $w ?>][start]" value="<?= esc($sd['start']) ?>">
                  </td>
                  <td style="padding:0.35rem 0.25rem">
                    <input class="form__input" style="min-width:7rem" type="time" name="schedule[<?= $w ?>][end]" value="<?= esc($sd['end']) ?>">
                  </td>
                </tr>
              <?php endfor; ?>
            </tbody>
          </table>
        </div>
        <p style="margin:0 0 1rem">
          <button type="button" class="btn btn--ghost" id="copyMonToAll" style="font-size:0.9rem">نسخ أوقات الإثنين إلى كل الأيام المفعّلة</button>
        </p>

        <label class="form__label">وضع الحالة</label>
        <select class="form__input" name="status_mode" id="status_mode">
          <option value="auto" <?= ($row['status_mode'] ?? 'auto') === 'auto' ? 'selected' : '' ?>>تلقائي حسب وقت الدوام</option>
          <option value="manual" <?= ($row['status_mode'] ?? '') === 'manual' ? 'selected' : '' ?>>يدوي</option>
        </select>

        <div id="manual_wrap" style="display:none">
          <label class="form__label">الحالة اليدوية</label>
          <select class="form__input" name="manual_status">
            <option value="available" <?= ($row['manual_status'] ?? 'available') === 'available' ? 'selected' : '' ?>>متاح</option>
            <option value="unavailable" <?= ($row['manual_status'] ?? '') === 'unavailable' ? 'selected' : '' ?>>غير متاح</option>
          </select>
        </div>

        <label class="form__label">ترتيب العرض (داخلي)</label>
        <input class="form__input form__input--sm" type="number" name="sort_order" value="<?= (int) ($row['sort_order'] ?? 0) ?>">

        <div class="form__actions">
          <button class="btn btn--primary" type="submit" name="action" value="save">حفظ</button>
          <a class="btn btn--ghost" href="<?= esc(url('admin/doctors.php?screen=' . $screenId)) ?>">رجوع</a>
          <?php if ($row): ?>
            <button class="btn btn--danger" type="submit" name="action" value="delete" onclick="return confirm('حذف هذا الطبيب؟');">حذف</button>
          <?php endif; ?>
        </div>
      </form>
    </div>

    <script>
      (function () {
        var m = document.getElementById('status_mode');
        var w = document.getElementById('manual_wrap');
        if (!m || !w) return;
        function sync() {
          w.style.display = m.value === 'manual' ? 'block' : 'none';
        }
        m.addEventListener('change', sync);
        sync();
      })();
      (function () {
        var btn = document.getElementById('copyMonToAll');
        if (!btn) return;
        btn.addEventListener('click', function () {
          var s1 = document.querySelector('input[name="schedule[1][start]"]');
          var e1 = document.querySelector('input[name="schedule[1][end]"]');
          if (!s1 || !e1) return;
          for (var d = 1; d <= 7; d++) {
            var on = document.querySelector('input[name="schedule[' + d + '][on]"]');
            if (!on || !on.checked) continue;
            var s = document.querySelector('input[name="schedule[' + d + '][start]"]');
            var e = document.querySelector('input[name="schedule[' + d + '][end]"]');
            if (s) s.value = s1.value;
            if (e) e.value = e1.value;
          }
        });
      })();
    </script>

<?php require_once dirname(__DIR__) . '/includes/admin/footer.php'; ?>
