<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/hospital.php';
require_admin();

$pdo = db();
$msg = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $shell = (string) ($_POST['default_display_shell'] ?? 'classic');
    if (!in_array($shell, ['classic', 'hariri'], true)) {
        $shell = 'classic';
    }
    try {
        $pdo->prepare('UPDATE hospital_settings SET default_display_shell = ? WHERE id = 1')->execute([$shell]);
        $msg = 'تم حفظ وضع الشاشة الأولى (الرابط الافتراضي من لوحة الشاشات).';
    } catch (Throwable $e) {
        $err = 'تعذر الحفظ.';
    }
}

$hs = hospital_settings($pdo);
$currentShell = (string) ($hs['default_display_shell'] ?? 'classic');
$screens = $pdo->query('SELECT id, name, token, display_style FROM screens ORDER BY id DESC')->fetchAll();

$adminPageTitle = 'قوالب جاهزة للاستخدام';
$adminNav = 'ready_templates';
require_once dirname(__DIR__) . '/includes/admin/header.php';
?>

    <?php if ($msg !== ''): ?><div class="alert alert--ok"><?= esc($msg) ?></div><?php endif; ?>
    <?php if ($err !== ''): ?><div class="alert alert--err"><?= esc($err) ?></div><?php endif; ?>

    <section class="saas-card section">
      <div class="saas-card__head">
        <div>
          <h2 class="saas-card__title">وضع الشاشة الأولى</h2>
          <p class="saas-card__sub">يحدد أي رابط يُعرض افتراضياً في «الشاشات» ويُنسخ كرابط رئيسي: العرض الكلاسيكي أو قالب الحريري الثابت من مجلد القالب.</p>
        </div>
      </div>
      <form method="post" class="form">
        <label class="form__label">الرابط الافتراضي لشاشات العرض</label>
        <select class="form__input" name="default_display_shell" style="max-width:420px">
          <option value="classic" <?= $currentShell === 'classic' ? 'selected' : '' ?>>كلاسيكي — display.php (شرائح النظام)</option>
          <option value="hariri" <?= $currentShell === 'hariri' ? 'selected' : '' ?>>قالب الحريري — display.php (قالب theme/ دون تعديل الملفات)</option>
        </select>
        <button class="btn btn--primary" type="submit" style="margin-top:0.75rem">حفظ</button>
      </form>
    </section>

    <section class="saas-card section">
      <div class="saas-card__head">
        <div>
          <h2 class="saas-card__title">قالب الحريري — البطاقة الثابتة</h2>
          <p class="saas-card__sub">يعرض الاسم والتخصص والصورة وجدول المواعيد من بيانات الأطباء. ملفات <code>theme/index.html</code> و <code>theme/style.css</code> تُستخدم كما هي؛ التعبئة ديناميكية فقط.</p>
        </div>
      </div>
      <ol style="margin:0 1.25rem 1rem;line-height:1.7">
        <li>في «الشاشات» اختر الاستايل <strong>قالب الحريري — البطاقة الثابتة</strong> للشاشة المطلوبة.</li>
        <li>افتح رابط العرض (أو استخدم «فتح الشاشة» من القائمة؛ يوجد زر للبديل).</li>
        <li>الشاشة تُعرَض من <code>display.php</code> فقط: إن كان استايل الشاشة «قالب الحريري» يُحمَّل قالب <code>theme/</code>؛ ويمكن فرض الوضع الكلاسيكي بـ <code>?force_classic=1</code> أو قالب الحريري بـ <code>?force_hariri=1</code>.</li>
      </ol>
      <p class="muted" style="margin:0">هاتف التذييل والتذييل السفلي يأخذان الاسم ورقم الهاتف من «بيانات المستشفى».</p>
    </section>

    <section class="section">
      <h2 class="section__title">روابط سريعة حسب الشاشات</h2>
      <?php if (!count($screens)): ?>
        <div class="saas-card"><p class="muted" style="margin:0">لا توجد شاشات بعد. أضف شاشة من «الشاشات».</p></div>
      <?php else: ?>
        <?php foreach ($screens as $s): ?>
          <?php $urls = screen_display_urls($pdo, (string) $s['token']); ?>
          <div class="saas-card" style="margin-bottom:0.75rem">
            <p style="margin:0 0 0.5rem;font-weight:600"><?= esc((string) $s['name']) ?></p>
            <p class="muted" style="margin:0 0 0.35rem;font-size:0.9rem">استايل الشاشة: <?= esc((string) ($s['display_style'] ?? '')) ?></p>
            <div class="form__row" style="flex-wrap:wrap;gap:0.5rem">
              <input type="text" readonly class="form__input form__input--mono" style="flex:1;min-width:220px" value="<?= esc($urls['primary']) ?>" onclick="this.select()" title="رئيسي">
              <input type="text" readonly class="form__input form__input--mono" style="flex:1;min-width:220px" value="<?= esc($urls['secondary']) ?>" onclick="this.select()" title="بديل">
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </section>

<?php require_once dirname(__DIR__) . '/includes/admin/footer.php'; ?>
