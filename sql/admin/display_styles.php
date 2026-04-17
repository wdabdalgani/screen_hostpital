<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/display_styles.php';
require_admin();

$pdo = db();
$msg = '';
$err = '';

$styleErrText = [
    'name_required' => 'اسم الاستايل مطلوب.',
    'layout_invalid' => 'قيمة layout غير مدعومة.',
    'image_behavior_invalid' => 'image_behavior غير صالح.',
    'overlay_invalid' => 'overlay غير صالح.',
    'typography_invalid' => 'typography غير صالح.',
    'colors_invalid' => 'colors غير صالح.',
    'animations_invalid' => 'animations غير صالح.',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'apply') {
        $screenId = (int) ($_POST['screen_id'] ?? 0);
        $styleKey = (string) ($_POST['style_key'] ?? '');
        if ($screenId <= 0 || $styleKey === '') {
            $err = 'بيانات غير صالحة.';
        } else {
            $st = $pdo->prepare('SELECT id FROM display_styles WHERE style_key = ? LIMIT 1');
            $st->execute([$styleKey]);
            if (!$st->fetch()) {
                $err = 'الاستايل غير موجود.';
            } else {
                $pdo->prepare('UPDATE screens SET display_style = ? WHERE id = ?')->execute([$styleKey, $screenId]);
                $msg = 'تم تطبيق الاستايل على الشاشة.';
            }
        }
    } elseif ($action === 'save') {
        $id = (int) ($_POST['style_id'] ?? 0);
        $name = trim((string) ($_POST['name'] ?? ''));
        $styleType = trim((string) ($_POST['style_type'] ?? 'custom'));
        $configRaw = trim((string) ($_POST['config_json'] ?? ''));
        $cssText = (string) ($_POST['css_text'] ?? '');
        $metaRaw = trim((string) ($_POST['metadata_json'] ?? ''));

        if ($id <= 0) {
            $err = 'المعرف غير صالح.';
        } else {
            $config = json_decode($configRaw, true);
            if (!is_array($config)) {
                $err = 'Config JSON غير صالح.';
            } else {
                $meta = json_decode($metaRaw !== '' ? $metaRaw : '{}', true);
                if (!is_array($meta)) {
                    $meta = [];
                }
                $payload = validate_style_payload([
                    'name' => $name,
                    'type' => $styleType,
                    'config' => $config,
                    'css' => $cssText,
                    'metadata' => $meta,
                ]);
                if (!$payload['ok']) {
                    $key = (string) ($payload['error'] ?? 'invalid');
                    $err = $styleErrText[$key] ?? 'بيانات الاستايل غير صالحة.';
                } else {
                    $d = $payload['data'];
                    $pdo->prepare('UPDATE display_styles SET name = ?, style_type = ?, config_json = ?, css_text = ?, metadata_json = ? WHERE id = ?')
                        ->execute([
                            $d['name'],
                            $d['type'],
                            json_encode($d['config'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                            $d['css'],
                            json_encode($d['metadata'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                            $id,
                        ]);
                    $msg = 'تم حفظ الاستايل.';
                }
            }
        }
    } elseif ($action === 'import') {
        $raw = '';
        $f = $_FILES['style_file'] ?? null;
        if (!$f || empty($f['tmp_name']) || !is_uploaded_file($f['tmp_name'])) {
            $err = 'اختر ملف استايل أولاً.';
        } else {
            $ext = strtolower(pathinfo((string) ($f['name'] ?? ''), PATHINFO_EXTENSION));
            if ($ext === 'zip') {
                if (!class_exists('ZipArchive')) {
                    $err = 'امتداد ZIP غير متاح على الخادم.';
                } else {
                    $zip = new ZipArchive();
                    if ($zip->open($f['tmp_name']) === true) {
                        for ($i = 0; $i < $zip->numFiles; $i++) {
                            $name = $zip->getNameIndex($i);
                            if ($name && strtolower(pathinfo($name, PATHINFO_EXTENSION)) === 'json') {
                                $raw = (string) $zip->getFromIndex($i);
                                break;
                            }
                        }
                        $zip->close();
                        if ($raw === '') {
                            $err = 'لم يتم العثور على ملف JSON داخل ZIP.';
                        }
                    } else {
                        $err = 'تعذر قراءة ZIP.';
                    }
                }
            } else {
                $raw = (string) file_get_contents($f['tmp_name']);
                if ($raw === '') {
                    $err = 'الملف فارغ.';
                }
            }

            if ($err === '') {
                $data = json_decode($raw, true);
                if (!is_array($data)) {
                    $err = 'JSON غير صالح.';
                } else {
                    $payload = validate_style_payload($data);
                    if (!$payload['ok']) {
                        $key = (string) ($payload['error'] ?? 'invalid');
                        $err = $styleErrText[$key] ?? 'هيكل الاستايل غير صالح.';
                    } else {
                        $d = $payload['data'];
                        $baseKey = normalize_style_key((string) $d['name']);
                        $styleKey = $baseKey;
                        $n = 1;
                        $chk = $pdo->prepare('SELECT id FROM display_styles WHERE style_key = ? LIMIT 1');
                        while (true) {
                            $chk->execute([$styleKey]);
                            if (!$chk->fetch()) {
                                break;
                            }
                            $n++;
                            $styleKey = $baseKey . '_' . $n;
                        }
                        $pdo->prepare('INSERT INTO display_styles (style_key, name, style_type, config_json, css_text, metadata_json, is_system) VALUES (?,?,?,?,?,?,0)')
                            ->execute([
                                $styleKey,
                                $d['name'],
                                $d['type'],
                                json_encode($d['config'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                                $d['css'],
                                json_encode($d['metadata'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                            ]);
                        $msg = 'تم استيراد الاستايل بنجاح.';
                    }
                }
            }
        }
    }
}

if (isset($_GET['export']) && (int) $_GET['export'] > 0) {
    $id = (int) $_GET['export'];
    $st = $pdo->prepare('SELECT * FROM display_styles WHERE id = ? LIMIT 1');
    $st->execute([$id]);
    $row = $st->fetch();
    if ($row) {
        $cfg = json_decode((string) ($row['config_json'] ?? '{}'), true);
        $meta = json_decode((string) ($row['metadata_json'] ?? '{}'), true);
        $payload = [
            'name' => (string) $row['name'],
            'type' => (string) $row['style_type'],
            'layout' => is_array($cfg) ? ((string) ($cfg['layout'] ?? 'hero')) : 'hero',
            'config' => is_array($cfg) ? $cfg : [],
            'css' => (string) ($row['css_text'] ?? ''),
            'metadata' => is_array($meta) ? $meta : [],
        ];
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . normalize_style_key((string) $row['name']) . '.json"');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        exit;
    }
}

$styles = $pdo->query('SELECT * FROM display_styles ORDER BY is_system DESC, updated_at DESC, id DESC')->fetchAll();
$screens = $pdo->query('SELECT id, name, display_style FROM screens ORDER BY name ASC')->fetchAll();
$styleMap = [];
foreach ($styles as $s) {
    $styleMap[(string) $s['style_key']] = (string) $s['name'];
}

$adminPageTitle = 'إدارة استايلات الشاشات';
$adminNav = 'display_styles';
require_once dirname(__DIR__) . '/includes/admin/header.php';
?>
    <?php if ($msg !== ''): ?><div class="alert alert--ok"><?= esc($msg) ?></div><?php endif; ?>
    <?php if ($err !== ''): ?><div class="alert alert--err"><?= esc($err) ?></div><?php endif; ?>

    <section class="saas-card section">
      <div class="saas-card__head">
        <div>
          <h2 class="saas-card__title">Style Manager</h2>
          <p class="saas-card__sub">تصدير / استيراد / تعديل / Apply للاستايلات بشكل مستقل لكل شاشة.</p>
        </div>
      </div>
      <form method="post" class="form form--row" style="margin-bottom:.8rem">
        <input type="hidden" name="action" value="apply">
        <div class="form__group">
          <label class="form__label">الشاشة</label>
          <select class="form__input" name="screen_id">
            <?php foreach ($screens as $sc): ?>
              <option value="<?= (int) $sc['id'] ?>"><?= esc((string) $sc['name']) ?> — <?= esc($styleMap[(string) ($sc['display_style'] ?? '')] ?? 'بدون') ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form__group">
          <label class="form__label">الاستايل</label>
          <select class="form__input" name="style_key">
            <?php foreach ($styles as $s): ?>
              <option value="<?= esc((string) $s['style_key']) ?>"><?= esc((string) $s['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <button class="btn btn--primary" type="submit" style="align-self:flex-end">Apply</button>
      </form>
      <form method="post" enctype="multipart/form-data" class="form form--row">
        <input type="hidden" name="action" value="import">
        <div class="form__group" style="min-width:260px">
          <label class="form__label">Import (JSON / ZIP)</label>
          <input class="form__input" type="file" name="style_file" accept=".json,.zip" required>
        </div>
        <button class="btn btn--ghost" type="submit" style="align-self:flex-end">Import</button>
      </form>
    </section>

    <section class="section">
      <h2 class="section__title">الاستايلات</h2>
      <?php foreach ($styles as $s): ?>
        <?php
          $cfgPretty = json_encode(json_decode((string) $s['config_json'], true) ?: [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
          $metaPretty = json_encode(json_decode((string) ($s['metadata_json'] ?? '{}'), true) ?: [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        ?>
        <div class="screen-card">
          <div class="screen-card__head" style="margin-bottom:.45rem">
            <div>
              <strong><?= esc((string) $s['name']) ?></strong>
              <div class="muted" style="font-size:.84rem">key: <?= esc((string) $s['style_key']) ?> — type: <?= esc((string) $s['style_type']) ?> <?= (int) $s['is_system'] === 1 ? '(system)' : '' ?></div>
            </div>
            <a class="btn btn--ghost" href="<?= esc(url('admin/display_styles.php?export=' . (int) $s['id'])) ?>">Export</a>
          </div>

          <form method="post">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="style_id" value="<?= (int) $s['id'] ?>">
            <div class="screen-card__row">
              <label class="form__label">Name</label>
              <input class="form__input" name="name" value="<?= esc((string) $s['name']) ?>" required>
              <label class="form__label">Type</label>
              <input class="form__input form__input--sm" name="style_type" value="<?= esc((string) $s['style_type']) ?>">
            </div>
            <label class="form__label">Config JSON</label>
            <textarea class="form__input" name="config_json" rows="12" style="font-family:ui-monospace,Consolas,monospace"><?= esc((string) $cfgPretty) ?></textarea>
            <label class="form__label">CSS</label>
            <textarea class="form__input" name="css_text" rows="6" style="font-family:ui-monospace,Consolas,monospace"><?= esc((string) ($s['css_text'] ?? '')) ?></textarea>
            <label class="form__label">Metadata JSON</label>
            <textarea class="form__input" name="metadata_json" rows="4" style="font-family:ui-monospace,Consolas,monospace"><?= esc((string) $metaPretty) ?></textarea>
            <div class="form__actions">
              <button class="btn btn--primary" type="submit">Save</button>
            </div>
          </form>
        </div>
      <?php endforeach; ?>
    </section>

<?php require_once dirname(__DIR__) . '/includes/admin/footer.php'; ?>
