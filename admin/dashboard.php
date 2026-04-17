<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/dashboard_stats.php';
require_once dirname(__DIR__) . '/includes/department_icons.php';
require_once dirname(__DIR__) . '/includes/hospital.php';
require_admin();

$pdo = db();
$cfg = app_config();
$bundle = compute_dashboard_bundle($pdo, $cfg);
$hs = hospital_settings($pdo);
$hospitalName = trim((string) ($hs['name'] ?? '')) !== '' ? (string) $hs['name'] : 'المستشفى';

$stats = $bundle['stats'];
$screenCount = $bundle['screen_count'];
$doctorCount = $bundle['doctor_count'];
$departmentCount = $bundle['department_count'];
$contentGroupCount = $bundle['content_group_count'];
$contentActiveItems = $bundle['content_active_items'];
$topDepartments = $bundle['top_departments'];
$availabilityPct = $bundle['availability_pct'];

$chartsJson = json_encode(
    $bundle['charts'],
    JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS
);

$adminPageTitle = 'الرئيسية';
$adminNav = 'dashboard';
$adminMainClass = 'saas-main saas-main--dashboard';
$adminFooterScripts = [
    'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js',
    url('assets/js/dashboard-charts.js') . '?v=2',
];
require_once dirname(__DIR__) . '/includes/admin/header.php';
?>

    <section class="dashboard-hero saas-card section">
      <div class="dashboard-hero__row">
        <div>
          <p class="dashboard-hero__hello">مرحباً، <span class="dashboard-hero__name"><?= esc($hospitalName) ?></span></p>
          <p class="dashboard-hero__sub">لوحة تحكم موحّدة — صحة الإعداد، الإحصائيات، والرسوم البيانية.</p>
        </div>
        <div class="dashboard-hero__kpi">
          <span class="dashboard-pill dashboard-pill--accent">نسبة التوفر <strong><?= esc((string) $availabilityPct) ?>٪</strong></span>
          <?php if ($bundle['hospital_updated'] !== ''): ?>
            <span class="dashboard-pill" title="آخر تحديث لبيانات المستشفى">تحديث البيانات: <?= esc(substr($bundle['hospital_updated'], 0, 16)) ?></span>
          <?php endif; ?>
        </div>
      </div>
    </section>

    <?php if (count($bundle['alerts'])): ?>
    <section class="section dashboard-alerts" aria-label="تنبيهات">
      <?php foreach ($bundle['alerts'] as $a): ?>
        <div class="dashboard-alert dashboard-alert--<?= esc((string) $a['severity']) ?>">
          <span class="dashboard-alert__text"><?= esc((string) $a['text']) ?></span>
          <?php if (!empty($a['href'])): ?>
            <a class="dashboard-alert__link" href="<?= esc((string) $a['href']) ?>">إصلاح</a>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </section>
    <?php endif; ?>

    <?php if (count($bundle['tips'])): ?>
    <section class="section dashboard-tips" aria-label="نصائح">
      <?php foreach ($bundle['tips'] as $tip): ?>
        <p class="dashboard-tip"><?= esc($tip) ?></p>
      <?php endforeach; ?>
    </section>
    <?php endif; ?>

    <section class="dashboard-quick section" aria-label="إجراءات سريعة">
      <h2 class="section__title">إجراءات سريعة</h2>
      <div class="dashboard-quick__grid">
        <a class="dashboard-qbtn" href="<?= esc(url('admin/screens.php')) ?>"><?= ic_monitor() ?><span>شاشات العرض</span></a>
        <a class="dashboard-qbtn" href="<?= esc(url('admin/doctors.php')) ?>"><?= ic_users() ?><span>إدارة الأطباء</span></a>
        <a class="dashboard-qbtn" href="<?= esc(url('admin/display_content.php')) ?>"><?= ic_media() ?><span>محتوى الشاشات</span></a>
        <a class="dashboard-qbtn" href="<?= esc(url('admin/display_styles.php')) ?>"><?= ic_spark() ?><span>استايلات العرض</span></a>
        <a class="dashboard-qbtn" href="<?= esc(url('admin/departments.php')) ?>"><?= ic_folder_dept() ?><span>الأقسام</span></a>
        <a class="dashboard-qbtn" href="<?= esc(url('admin/hospital.php')) ?>"><?= ic_building() ?><span>بيانات المستشفى</span></a>
      </div>
    </section>

    <div class="saas-stat-slider saas-stat-slider--gridwide saas-stat-slider--six" data-stat-slider data-autoplay="6000">
      <div class="saas-stat-slider__controls">
        <div>
          <p class="saas-stat-slider__label">نظرة عامة موسّعة</p>
          <p class="saas-card__sub" style="margin:0">مؤشرات فورية للنظام</p>
        </div>
        <div class="saas-stat-slider__arrows">
          <button type="button" class="saas-stat-arrow" data-stat-prev aria-label="السابق"><?= ic_arrow_prev() ?></button>
          <button type="button" class="saas-stat-arrow" data-stat-next aria-label="التالي"><?= ic_arrow_next() ?></button>
        </div>
      </div>
      <div class="saas-stat-slider__viewport">
        <div class="saas-stat-slider__track" data-stat-track>
          <div class="saas-stat-slider__slide" data-stat-slide>
            <div class="stat-card">
              <span class="stat-card__label">شاشات العرض</span>
              <span class="stat-card__value"><?= (int) $screenCount ?></span>
            </div>
          </div>
          <div class="saas-stat-slider__slide" data-stat-slide>
            <div class="stat-card stat-card--green">
              <span class="stat-card__label">متاح (حسب الدوام / الإعداد)</span>
              <span class="stat-card__value"><?= (int) $stats['available'] ?></span>
            </div>
          </div>
          <div class="saas-stat-slider__slide" data-stat-slide>
            <div class="stat-card stat-card--blue">
              <span class="stat-card__label">غير متاح</span>
              <span class="stat-card__value"><?= (int) $stats['unavailable'] ?></span>
            </div>
          </div>
          <div class="saas-stat-slider__slide" data-stat-slide>
            <div class="stat-card">
              <span class="stat-card__label">إجمالي الأطباء</span>
              <span class="stat-card__value"><?= (int) $doctorCount ?></span>
            </div>
          </div>
          <div class="saas-stat-slider__slide" data-stat-slide>
            <div class="stat-card">
              <span class="stat-card__label">الأقسام</span>
              <span class="stat-card__value"><?= (int) $departmentCount ?></span>
            </div>
          </div>
          <div class="saas-stat-slider__slide" data-stat-slide>
            <div class="stat-card stat-card--green">
              <span class="stat-card__label">محتوى نشط · مجموعات</span>
              <span class="stat-card__value"><?= (int) $contentActiveItems ?><span class="stat-card__pair"> / <?= (int) $contentGroupCount ?></span></span>
            </div>
          </div>
        </div>
      </div>
      <div class="saas-stat-dots" data-stat-dots></div>
    </div>

    <section class="dashboard-charts section" aria-label="الرسوم البيانية — 13 مؤشر">
      <div class="dashboard-charts__head">
        <h2 class="dashboard-charts__title">الإحصائيات والرسوم البيانية</h2>
        <p class="dashboard-charts__sub">13 مؤشراً تفاعلياً: 8 مؤشرات تشغيلية أساسية + 5 تحليلات متقدمة (Trend, Heatmap, Funnel, Pareto, SLA).</p>
      </div>
      <div class="dashboard-charts__grid">
        <article class="chart-card"><h3 class="chart-card__title">1 — توزيع التوفر</h3><div class="chart-card__canvas"><canvas id="dashChart1" aria-label="رسم توزيع التوفر"></canvas></div></article>
        <article class="chart-card"><h3 class="chart-card__title">2 — الأطباء حسب القسم</h3><div class="chart-card__canvas"><canvas id="dashChart2" aria-label="رسم الأطباء حسب القسم"></canvas></div></article>
        <article class="chart-card"><h3 class="chart-card__title">3 — وضع الشاشات</h3><div class="chart-card__canvas"><canvas id="dashChart3" aria-label="رسم وضع الشاشات"></canvas></div></article>
        <article class="chart-card"><h3 class="chart-card__title">4 — استايلات العرض</h3><div class="chart-card__canvas"><canvas id="dashChart4" aria-label="رسم استايلات العرض"></canvas></div></article>
        <article class="chart-card"><h3 class="chart-card__title">5 — أنواع المحتوى النشط</h3><div class="chart-card__canvas"><canvas id="dashChart5" aria-label="رسم أنواع المحتوى"></canvas></div></article>
        <article class="chart-card"><h3 class="chart-card__title">6 — عدد الأطباء لكل شاشة</h3><div class="chart-card__canvas"><canvas id="dashChart6" aria-label="رسم توزيع الأطباء على الشاشات"></canvas></div></article>
        <article class="chart-card"><h3 class="chart-card__title">7 — وضع حالة الطبيب</h3><div class="chart-card__canvas"><canvas id="dashChart7" aria-label="رسم وضع الحالة"></canvas></div></article>
        <article class="chart-card"><h3 class="chart-card__title">8 — المجموعات — عناصر نشطة</h3><div class="chart-card__canvas"><canvas id="dashChart8" aria-label="رسم المجموعات"></canvas></div></article>
        <article class="chart-card chart-card--wide"><h3 class="chart-card__title">9 — اتجاه التوفر (آخر 48 ساعة)</h3><div class="chart-card__canvas"><canvas id="dashChart9" aria-label="رسم اتجاه التوفر الزمني"></canvas></div></article>
        <article class="chart-card chart-card--wide"><h3 class="chart-card__title">10 — Heatmap التوفر (اليوم × الساعة)</h3><div class="chart-card__canvas"><canvas id="dashChart10" aria-label="رسم Heatmap للتوفر"></canvas></div></article>
        <article class="chart-card"><h3 class="chart-card__title">11 — Funnel جودة البيانات</h3><div class="chart-card__canvas"><canvas id="dashChart11" aria-label="رسم Funnel لجودة البيانات"></canvas></div></article>
        <article class="chart-card chart-card--wide"><h3 class="chart-card__title">12 — Pareto عدم التوفر حسب القسم</h3><div class="chart-card__canvas"><canvas id="dashChart12" aria-label="رسم Pareto لعدم التوفر"></canvas></div></article>
        <article class="chart-card"><h3 class="chart-card__title">13 — أداء KPI مقابل الهدف (SLA)</h3><div class="chart-card__canvas"><canvas id="dashChart13" aria-label="رسم SLA"></canvas></div></article>
      </div>
    </section>

    <script type="application/json" id="dashboard-chart-data"><?= $chartsJson ?></script>

    <section class="top-dept-section section">
      <div class="top-dept-section__head">
        <div>
          <h2 class="top-dept-section__title">أفضل الأقسام</h2>
          <p class="top-dept-section__sub">الأكثر توفرًا للأطباء (حسب الدوام والإعدادات)</p>
        </div>
      </div>
      <?php if (!count($topDepartments)): ?>
        <div class="saas-card"><p class="muted" style="margin:0">لا توجد بيانات أقسام أو أطباء بعد.</p></div>
      <?php else: ?>
        <div class="top-dept-grid">
          <?php foreach ($topDepartments as $td): ?>
            <div class="dept-top-card">
              <span class="dept-top-card__icon" aria-hidden="true"><?= department_icon_svg((string) $td['icon']) ?></span>
              <span class="dept-top-card__name"><?= esc((string) $td['name']) ?></span>
              <span class="dept-top-card__metric">
                <span class="dept-top-card__n"><?= (int) $td['available'] ?></span>
                <span class="dept-top-card__lbl">متاح</span>
              </span>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>

    <section class="saas-card section">
      <div class="saas-card__head">
        <div>
          <h2 class="saas-card__title">توزيع الأقسام</h2>
          <p class="saas-card__sub">عدد الأطباء حسب القسم</p>
        </div>
      </div>
      <?php if (empty($stats['by_department'])): ?>
        <p class="muted" style="margin:0">لا توجد بيانات بعد.</p>
      <?php else: ?>
        <ul class="dep-list">
          <?php foreach ($stats['by_department'] as $dep => $n): ?>
            <li>
              <span class="dep-list__name"><?= esc($dep) ?></span>
              <span class="dep-list__bar-wrap"><span class="dep-list__bar" style="--p: <?= min(100, (int) round($n / max(1, $doctorCount) * 100)) ?>%"></span></span>
              <span class="dep-list__n"><?= (int) $n ?></span>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </section>

    <section class="saas-card section dashboard-system">
      <div class="saas-card__head">
        <div>
          <h2 class="saas-card__title">النظام والصيانة</h2>
          <p class="saas-card__sub">إصدار قاعدة البيانات وصلاحية مجلدات الرفع</p>
        </div>
      </div>
      <ul class="dashboard-system__list">
        <li><span>آخر ترحيل مخطط</span><strong>v<?= (int) $bundle['schema_version'] ?></strong></li>
        <li><span>محتوى مرتبط بطبيب</span><strong><?= (int) $bundle['content_linked_doctor'] ?></strong></li>
        <li><span>أطباء بلا صورة</span><strong><?= (int) $bundle['doctors_no_image'] ?></strong></li>
        <li><span>أطباء بلا قسم</span><strong><?= (int) $bundle['doctors_no_dept'] ?></strong></li>
      </ul>
      <div class="dashboard-system__uploads">
        <?php foreach ($bundle['uploads_health'] as $label => $ok): ?>
          <span class="dashboard-upload-pill <?= $ok ? 'is-ok' : 'is-bad' ?>"><?= esc($label) ?>: <?= $ok ? 'جاهز' : 'خطأ' ?></span>
        <?php endforeach; ?>
      </div>
    </section>

    <p class="muted" style="margin-top:1.5rem">
      <a href="<?= esc(url('admin/screens.php')) ?>">الشاشات</a>
      —
      <a href="<?= esc(url('admin/doctors.php')) ?>">إدارة الأطباء</a>
      —
      <a href="<?= esc(url('admin/departments.php')) ?>">الأقسام</a>
      —
      <a href="<?= esc(url('admin/hospital.php')) ?>">المستشفى</a>
    </p>

<?php require_once dirname(__DIR__) . '/includes/admin/footer.php'; ?>
