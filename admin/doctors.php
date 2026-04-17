<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_admin();

$pdo = db();

$screens = $pdo->query('SELECT id, name FROM screens ORDER BY name ASC')->fetchAll();
$departments = $pdo->query('SELECT id, name FROM departments ORDER BY sort_order ASC, name ASC')->fetchAll();
$firstScreenId = count($screens) ? (int) $screens[0]['id'] : 0;

$sql = 'SELECT d.*, s.name AS screen_name, dep.name AS department_name, dep.banner_image_path AS department_banner_path
        FROM doctors d
        INNER JOIN screens s ON s.id = d.screen_id
        LEFT JOIN departments dep ON dep.id = d.department_id
        ORDER BY s.name ASC, d.sort_order ASC, d.name ASC';
$doctors = count($screens) ? doctors_attach_weekly_schedule($pdo, $pdo->query($sql)->fetchAll()) : [];

$normBlob = static function (string $name, string $spec): string {
    $s = $name . ' ' . $spec;
    if (function_exists('mb_strtolower')) {
        return mb_strtolower($s, 'UTF-8');
    }

    return strtolower($s);
};

$adminPageTitle = 'إدارة الأطباء';
$adminNav = 'doctors';
require_once dirname(__DIR__) . '/includes/admin/header.php';
?>
<link rel="stylesheet" href="<?= esc(url('assets/css/doctors-cards.css')) ?>?v=1">

    <?php if (!count($screens)): ?>
      <div class="alert alert--err">أضف شاشة عرض أولاً من «الشاشات».</div>
    <?php else: ?>

      <div class="saas-card" style="margin-bottom:1rem">
        <div class="saas-card__head">
          <div>
            <h2 class="saas-card__title" style="font-size:1.05rem">إدارة الأطباء</h2>
            <p class="saas-card__sub">بطاقات تفاعلية — بحث، فلترة، وتغيير الحالة فوراً</p>
          </div>
          <a class="btn btn--primary" href="<?= esc(url('admin/doctor_edit.php?screen=' . $firstScreenId)) ?>">إضافة طبيب</a>
        </div>

        <div class="doctors-manager__toolbar">
          <p class="doctors-manager__count" id="doctorsFilteredCount" aria-live="polite"></p>
          <div class="form__group">
            <label class="form__label" for="doctorsSearch">بحث (اسم أو تخصص)</label>
            <input class="form__input" type="search" id="doctorsSearch" placeholder="ابحث…" autocomplete="off">
          </div>
          <div class="form__group">
            <label class="form__label" for="filterScreen">الشاشة</label>
            <select class="form__input" id="filterScreen">
              <option value="">الكل</option>
              <?php foreach ($screens as $s): ?>
                <option value="<?= (int) $s['id'] ?>"><?= esc($s['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form__group">
            <label class="form__label" for="filterDepartment">القسم</label>
            <select class="form__input" id="filterDepartment">
              <option value="">الكل</option>
              <?php foreach ($departments as $dep): ?>
                <option value="<?= (int) $dep['id'] ?>"><?= esc($dep['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form__group">
            <label class="form__label" for="filterStatus">الحالة</label>
            <select class="form__input" id="filterStatus">
              <option value="">الكل</option>
              <option value="available">متاح</option>
              <option value="unavailable">غير متاح</option>
            </select>
          </div>
        </div>
      </div>

      <?php if (!count($doctors)): ?>
        <div class="saas-card doctors-manager__empty">
          <p>لا يوجد أطباء بعد. أضف أول طبيب للبدء.</p>
          <a class="btn btn--primary" href="<?= esc(url('admin/doctor_edit.php?screen=' . $firstScreenId)) ?>">إضافة طبيب</a>
        </div>
      <?php else: ?>
        <div
          class="doctor-cards-grid"
          id="doctorCardsGrid"
          data-api="<?= esc(url('api/admin_doctor_actions.php')) ?>"
        >
          <?php foreach ($doctors as $d): ?>
            <?php
              $eff = doctor_effective_status($d);
              $deptId = (int) ($d['department_id'] ?? 0);
              $bannerPath = $d['department_banner_path'] ?? null;
              $bannerUrl = $bannerPath ? url('uploads/departments/' . basename((string) $bannerPath)) : null;
              $photoUrl = !empty($d['image_path']) ? url('uploads/doctors/' . basename((string) $d['image_path'])) : null;
              $did = (int) $d['id'];
              $sid = (int) $d['screen_id'];
              $searchAttr = $normBlob((string) $d['name'], (string) $d['specialty']);
            ?>
            <article
              class="doctor-card"
              data-search="<?= esc($searchAttr) ?>"
              data-dept-id="<?= $deptId ?>"
              data-screen-id="<?= $sid ?>"
              data-status="<?= esc($eff) ?>"
            >
              <div class="doctor-card__visual">
                <div class="doctor-card__ambient" aria-hidden="true">
                  <?php if ($bannerUrl): ?>
                    <img class="doctor-card__ambient-img" src="<?= esc($bannerUrl) ?>" alt="">
                  <?php endif; ?>
                  <div class="doctor-card__ambient-fade"></div>
                </div>
                <span class="doctor-card__mode"><?= ($d['status_mode'] ?? 'auto') === 'manual' ? 'يدوي' : 'تلقائي' ?></span>
                <div class="doctor-card__actions">
                  <button
                    type="button"
                    class="doctor-card__action doctor-card__action--toggle"
                    data-action="toggle"
                    data-doctor-id="<?= $did ?>"
                    title="تغيير الحالة"
                    aria-label="تغيير الحالة"
                  ><?= ic_toggle_status() ?></button>
                  <a
                    class="doctor-card__action doctor-card__action--edit"
                    href="<?= esc(url('admin/doctor_edit.php?id=' . $did . '&screen=' . $sid)) ?>"
                    title="تعديل"
                    aria-label="تعديل"
                  ><?= ic_edit() ?></a>
                  <button
                    type="button"
                    class="doctor-card__action doctor-card__action--delete"
                    data-action="delete"
                    data-doctor-id="<?= $did ?>"
                    title="حذف"
                    aria-label="حذف"
                  ><?= ic_trash() ?></button>
                </div>
                <div class="doctor-card__photo-wrap">
                  <?php if ($photoUrl): ?>
                    <img class="doctor-card__photo" src="<?= esc($photoUrl) ?>" alt="">
                  <?php else: ?>
                    <div class="doctor-card__photo doctor-card__photo--empty" aria-hidden="true"><?= ic_user_circle() ?></div>
                  <?php endif; ?>
                </div>
                <span class="doctor-card__badge <?= $eff === 'available' ? 'doctor-card__badge--ok' : 'doctor-card__badge--no' ?>"><?= $eff === 'available' ? 'متاح' : 'غير متاح' ?></span>
              </div>
              <div class="doctor-card__body">
                <h3 class="doctor-card__name"><?= esc((string) $d['name']) ?></h3>
                <p class="doctor-card__spec"><?= esc((string) ($d['specialty'] !== '' ? $d['specialty'] : '—')) ?></p>
                <p class="doctor-card__time"><?= esc(doctor_time_display_for_today($d, is_array($d['weekly_schedule'] ?? null) ? $d['weekly_schedule'] : [])) ?></p>
                <p class="doctor-card__meta"><?= esc((string) ($d['screen_name'] ?? '')) ?> · <?= esc((string) ($d['department_name'] ?? '—')) ?></p>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

    <?php endif; ?>

    <script src="<?= esc(url('assets/js/doctors-cards.js')) ?>?v=1" defer></script>
<?php require_once dirname(__DIR__) . '/includes/admin/footer.php'; ?>
