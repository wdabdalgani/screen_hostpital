<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/hospital.php';
require_once dirname(__DIR__) . '/includes/display_styles.php';
require_admin();

$pdo = db();
$msg = '';
$err = '';
$styleRows = $pdo->query('SELECT style_key, name FROM display_styles ORDER BY is_system DESC, name ASC')->fetchAll();
$contentGroups = $pdo->query('SELECT id, name FROM content_groups ORDER BY name ASC')->fetchAll();
$styleOptions = [];
foreach ($styleRows as $sr) {
    $styleOptions[(string) $sr['style_key']] = (string) $sr['name'];
}
if (!count($styleOptions)) {
    $styleOptions = [
        'hero_medical' => 'Hero Medical Style',
        'card_social' => 'Card / Facebook-like Style',
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');
    if ($action === 'add') {
        $name = trim((string) ($_POST['name'] ?? ''));
        $slide = (int) ($_POST['slide_seconds'] ?? 8);
        $ref = (int) ($_POST['refresh_seconds'] ?? 20);
        $style = (string) ($_POST['display_style'] ?? 'hero_medical');
        $mode = (string) ($_POST['display_mode'] ?? 'doctors');
        $groupId = (int) ($_POST['content_group_id'] ?? 0);
        if (!in_array($mode, ['doctors', 'content'], true)) {
            $mode = 'doctors';
        }
        if (!isset($styleOptions[$style])) {
            $style = 'hero_medical';
        }
        if ($name === '') {
            $err = 'أدخل اسم الشاشة.';
        } else {
            $slide = max(5, min(60, $slide));
            $ref = max(10, min(30, $ref));
            $token = random_token(16);
            $pdo->prepare('INSERT INTO screens (name, token, slide_seconds, refresh_seconds, display_style, display_mode, content_group_id) VALUES (?,?,?,?,?,?,?)')
                ->execute([$name, $token, $slide, $ref, $style, $mode, $mode === 'content' && $groupId > 0 ? $groupId : null]);
            $msg = 'تمت إضافة الشاشة.';
        }
    } elseif ($action === 'edit') {
        $id = (int) ($_POST['id'] ?? 0);
        $name = trim((string) ($_POST['name'] ?? ''));
        $slide = (int) ($_POST['slide_seconds'] ?? 8);
        $ref = (int) ($_POST['refresh_seconds'] ?? 20);
        $style = (string) ($_POST['display_style'] ?? 'hero_medical');
        $mode = (string) ($_POST['display_mode'] ?? 'doctors');
        $groupId = (int) ($_POST['content_group_id'] ?? 0);
        if (!in_array($mode, ['doctors', 'content'], true)) {
            $mode = 'doctors';
        }
        if (!isset($styleOptions[$style])) {
            $style = 'hero_medical';
        }
        if ($name === '' || $id <= 0) {
            $err = 'بيانات غير صالحة.';
        } else {
            $slide = max(5, min(60, $slide));
            $ref = max(10, min(30, $ref));
            $pdo->prepare('UPDATE screens SET name=?, slide_seconds=?, refresh_seconds=?, display_style=?, display_mode=?, content_group_id=? WHERE id=?')
                ->execute([$name, $slide, $ref, $style, $mode, $mode === 'content' && $groupId > 0 ? $groupId : null, $id]);
            $msg = 'تم الحفظ.';
        }
    } elseif ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            $pdo->prepare('DELETE FROM screens WHERE id=?')->execute([$id]);
            $msg = 'تم الحذف.';
        }
    }
}

$screens = $pdo->query('SELECT id, name, token, slide_seconds, refresh_seconds, display_style, display_mode, content_group_id, created_at FROM screens ORDER BY id DESC')->fetchAll();

$adminPageTitle = 'شاشات العرض';
$adminNav = 'screens';
require_once dirname(__DIR__) . '/includes/admin/header.php';
?>

    <?php if ($msg !== ''): ?><div class="alert alert--ok"><?= esc($msg) ?></div><?php endif; ?>
    <?php if ($err !== ''): ?><div class="alert alert--err"><?= esc($err) ?></div><?php endif; ?>

    <section class="saas-card section">
      <div class="saas-card__head">
        <div>
          <h2 class="saas-card__title">شاشة جديدة</h2>
          <p class="saas-card__sub">أضف شاشة وستُنشأ تلقائياً رابط عرض فريد</p>
        </div>
        <a class="btn btn--ghost" href="<?= esc(url('admin/display_styles.php')) ?>">إدارة الاستايلات</a>
      </div>
      <form method="post" class="form form--row">
        <input type="hidden" name="action" value="add">
        <div class="form__group">
          <label class="form__label">الاسم</label>
          <input class="form__input" name="name" required placeholder="مثال: عيادة الباطنية">
        </div>
        <div class="form__group">
          <label class="form__label">تبديل الطبيب (5–10 ث)</label>
          <input class="form__input form__input--sm" type="number" name="slide_seconds" min="5" max="60" value="15">
        </div>
        <div class="form__group">
          <label class="form__label">تحديث البيانات (10–30 ث)</label>
          <input class="form__input form__input--sm" type="number" name="refresh_seconds" min="10" max="30" value="20">
        </div>
        <div class="form__group">
          <label class="form__label">الاستايل</label>
          <select class="form__input" name="display_style">
            <?php foreach ($styleOptions as $key => $label): ?>
              <option value="<?= esc($key) ?>"><?= esc($label) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form__group">
          <label class="form__label">نوع العرض</label>
          <select class="form__input" name="display_mode">
            <option value="doctors">عرض الأطباء</option>
            <option value="content">عرض مجموعة محتوى</option>
          </select>
        </div>
        <div class="form__group">
          <label class="form__label">مجموعة المحتوى</label>
          <select class="form__input" name="content_group_id">
            <option value="0">— لا شيء —</option>
            <?php foreach ($contentGroups as $g): ?>
              <option value="<?= (int) $g['id'] ?>"><?= esc((string) $g['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <button class="btn btn--primary" type="submit" style="align-self:flex-end">إضافة</button>
      </form>
    </section>

    <section class="section">
      <h2 class="section__title">القائمة</h2>
      <?php if (!count($screens)): ?>
        <div class="saas-card"><p class="muted" style="margin:0">لا توجد شاشات بعد.</p></div>
      <?php endif; ?>
      <?php foreach ($screens as $s): ?>
        <?php
        $urls = screen_display_urls($pdo, (string) $s['token']);
        $link = $urls['primary'];
        $altLink = $urls['secondary'];
        $offlinePrepLink = url('display.php?token=' . $s['token'] . '&offline_sync=1&force_classic=1');
        $offlinePrepHariri = url('display.php?token=' . $s['token'] . '&offline_sync=1&force_hariri=1');
        ?>
        <div class="screen-card">
          <form method="post" class="screen-card__form">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
            <div class="screen-card__head">
              <input class="form__input" name="name" value="<?= esc($s['name']) ?>" required>
              <button class="btn btn--primary" type="submit">حفظ</button>
            </div>
            <div class="screen-card__row">
              <label class="form__label">تبديل (ث)</label>
              <input class="form__input form__input--sm" type="number" name="slide_seconds" min="5" max="60" value="<?= (int) $s['slide_seconds'] ?>">
              <label class="form__label">تحديث (ث)</label>
              <input class="form__input form__input--sm" type="number" name="refresh_seconds" min="10" max="30" value="<?= (int) $s['refresh_seconds'] ?>">
              <label class="form__label">الاستايل</label>
              <select class="form__input" name="display_style" style="max-width:260px">
                <?php foreach ($styleOptions as $key => $label): ?>
                  <option value="<?= esc($key) ?>" <?= ($s['display_style'] ?? 'hero_medical') === $key ? 'selected' : '' ?>><?= esc($label) ?></option>
                <?php endforeach; ?>
              </select>
              <label class="form__label">نوع العرض</label>
              <select class="form__input" name="display_mode" style="max-width:220px">
                <option value="doctors" <?= ($s['display_mode'] ?? 'doctors') === 'doctors' ? 'selected' : '' ?>>أطباء</option>
                <option value="content" <?= ($s['display_mode'] ?? '') === 'content' ? 'selected' : '' ?>>محتوى</option>
              </select>
              <label class="form__label">مجموعة المحتوى</label>
              <select class="form__input" name="content_group_id" style="max-width:220px">
                <option value="0">—</option>
                <?php foreach ($contentGroups as $g): ?>
                  <option value="<?= (int) $g['id'] ?>" <?= (int) ($s['content_group_id'] ?? 0) === (int) $g['id'] ? 'selected' : '' ?>><?= esc((string) $g['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </form>
          <div class="screen-card__link">
            <a class="btn btn--ghost" href="<?= esc($link) ?>" target="_blank" rel="noopener">فتح الشاشة (<?= $urls['primary_kind'] === 'hariri' ? 'قالب الحريري' : 'كلاسيكي' ?>)</a>
            <a class="btn btn--ghost" href="<?= esc($altLink) ?>" target="_blank" rel="noopener">البديل (<?= $urls['secondary_kind'] === 'hariri' ? 'قالب الحريري' : 'كلاسيكي' ?>)</a>
            <a
              class="btn btn--ghost"
              href="<?= esc($offlinePrepLink) ?>"
              target="_blank"
              rel="noopener"
              title="يفتح الشاشة ويحمّل الصفحة والبيانات والوسائط للعمل دون إنترنت على هذا المتصفح (يفضّل Chrome/Edge وHTTPS)"
            >تجهيز أوفلاين (كلاسيكي)</a>
            <a
              class="btn btn--ghost"
              href="<?= esc($offlinePrepHariri) ?>"
              target="_blank"
              rel="noopener"
              title="تجهيز أوفلاين لقالب الحريري"
            >تجهيز أوفلاين (قالب)</a>
            <input type="text" readonly class="form__input form__input--mono" value="<?= esc($link) ?>" onclick="this.select()" title="الرابط الافتراضي حسب إعداد وضع الشاشة الأولى">
          </div>
          <form method="post" class="screen-card__del" onsubmit="return confirm('حذف الشاشة وجميع أطبائها؟');">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
            <button class="btn btn--danger" type="submit">حذف الشاشة</button>
          </form>
        </div>
      <?php endforeach; ?>
    </section>

<?php require_once dirname(__DIR__) . '/includes/admin/footer.php'; ?>
