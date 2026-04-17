<?php
declare(strict_types=1);
require_once __DIR__ . '/display_styles.php';

/**
 * Idempotent DB migrations (version 2: departments, hospital, admin profile).
 */
function run_migrations(PDO $pdo): void
{
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS schema_migrations (
      version INT UNSIGNED NOT NULL,
      applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (version)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $st = $pdo->query('SELECT version FROM schema_migrations');
    $done = $st ? $st->fetchAll(PDO::FETCH_COLUMN) : [];
    $done = array_map('intval', $done);

    if (!in_array(2, $done, true)) {
        migrate_v2($pdo);
        try {
            $pdo->prepare('INSERT INTO schema_migrations (version) VALUES (2)')->execute();
        } catch (Throwable $e) {
            // duplicate
        }
    }

    if (!in_array(3, $done, true)) {
        migrate_v3($pdo);
        try {
            $pdo->prepare('INSERT INTO schema_migrations (version) VALUES (3)')->execute();
        } catch (Throwable $e) {
            // duplicate
        }
    }

    if (!in_array(4, $done, true)) {
        migrate_v4($pdo);
        try {
            $pdo->prepare('INSERT INTO schema_migrations (version) VALUES (4)')->execute();
        } catch (Throwable $e) {
            // duplicate
        }
    }

    if (!in_array(5, $done, true)) {
        migrate_v5($pdo);
        try {
            $pdo->prepare('INSERT INTO schema_migrations (version) VALUES (5)')->execute();
        } catch (Throwable $e) {
            // duplicate
        }
    }

    if (!in_array(6, $done, true)) {
        migrate_v6($pdo);
        try {
            $pdo->prepare('INSERT INTO schema_migrations (version) VALUES (6)')->execute();
        } catch (Throwable $e) {
            // duplicate
        }
    }

    if (!in_array(7, $done, true)) {
        migrate_v7($pdo);
        try {
            $pdo->prepare('INSERT INTO schema_migrations (version) VALUES (7)')->execute();
        } catch (Throwable $e) {
            // duplicate
        }
    }

    if (!in_array(8, $done, true)) {
        migrate_v8($pdo);
        try {
            $pdo->prepare('INSERT INTO schema_migrations (version) VALUES (8)')->execute();
        } catch (Throwable $e) {
            // duplicate
        }
    }

    if (!in_array(9, $done, true)) {
        migrate_v9($pdo);
        try {
            $pdo->prepare('INSERT INTO schema_migrations (version) VALUES (9)')->execute();
        } catch (Throwable $e) {
            // duplicate
        }
    }

    if (!in_array(10, $done, true)) {
        migrate_v10($pdo);
        try {
            $pdo->prepare('INSERT INTO schema_migrations (version) VALUES (10)')->execute();
        } catch (Throwable $e) {
            // duplicate
        }
    }

    if (!in_array(11, $done, true)) {
        migrate_v11($pdo);
        try {
            $pdo->prepare('INSERT INTO schema_migrations (version) VALUES (11)')->execute();
        } catch (Throwable $e) {
            // duplicate
        }
    }

    if (!in_array(12, $done, true)) {
        migrate_v12($pdo);
        try {
            $pdo->prepare('INSERT INTO schema_migrations (version) VALUES (12)')->execute();
        } catch (Throwable $e) {
            // duplicate
        }
    }

    if (!in_array(13, $done, true)) {
        migrate_v13($pdo);
        try {
            $pdo->prepare('INSERT INTO schema_migrations (version) VALUES (13)')->execute();
        } catch (Throwable $e) {
            // duplicate
        }
    }

    if (!in_array(14, $done, true)) {
        migrate_v14($pdo);
        try {
            $pdo->prepare('INSERT INTO schema_migrations (version) VALUES (14)')->execute();
        } catch (Throwable $e) {
            // duplicate
        }
    }

    if (!in_array(15, $done, true)) {
        migrate_v15($pdo);
        try {
            $pdo->prepare('INSERT INTO schema_migrations (version) VALUES (15)')->execute();
        } catch (Throwable $e) {
            // duplicate
        }
    }

    if (!in_array(16, $done, true)) {
        migrate_v16($pdo);
        try {
            $pdo->prepare('INSERT INTO schema_migrations (version) VALUES (16)')->execute();
        } catch (Throwable $e) {
            // duplicate
        }
    }
}

function column_exists(PDO $pdo, string $table, string $column): bool
{
    $db = $pdo->query('SELECT DATABASE()')->fetchColumn();
    $st = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?'
    );
    $st->execute([$db, $table, $column]);

    return (int) $st->fetchColumn() > 0;
}

function table_exists(PDO $pdo, string $table): bool
{
    $db = $pdo->query('SELECT DATABASE()')->fetchColumn();
    $st = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?'
    );
    $st->execute([$db, $table]);

    return (int) $st->fetchColumn() > 0;
}

function fk_exists(PDO $pdo, string $table, string $constraintName): bool
{
    $db = $pdo->query('SELECT DATABASE()')->fetchColumn();
    $st = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND CONSTRAINT_NAME = ? AND CONSTRAINT_TYPE = ?'
    );
    $st->execute([$db, $table, $constraintName, 'FOREIGN KEY']);

    return (int) $st->fetchColumn() > 0;
}

function migrate_v2(PDO $pdo): void
{
    $pdo->exec('SET FOREIGN_KEY_CHECKS=0');

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS departments (
      id INT UNSIGNED NOT NULL AUTO_INCREMENT,
      name VARCHAR(191) NOT NULL,
      icon VARCHAR(32) NOT NULL DEFAULT \'layers\',
      banner_image_path VARCHAR(512) NULL,
      sort_order INT NOT NULL DEFAULT 0,
      created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (id),
      UNIQUE KEY uk_dep_name (name)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS hospital_settings (
      id TINYINT UNSIGNED NOT NULL DEFAULT 1,
      name VARCHAR(255) NOT NULL DEFAULT \'\',
      logo_path VARCHAR(512) DEFAULT NULL,
      phone VARCHAR(64) NOT NULL DEFAULT \'\',
      email VARCHAR(191) NOT NULL DEFAULT \'\',
      address TEXT,
      website VARCHAR(255) NOT NULL DEFAULT \'\',
      social_facebook VARCHAR(255) NOT NULL DEFAULT \'\',
      social_instagram VARCHAR(255) NOT NULL DEFAULT \'\',
      social_x VARCHAR(255) NOT NULL DEFAULT \'\',
      social_youtube VARCHAR(255) NOT NULL DEFAULT \'\',
      updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $pdo->exec('INSERT IGNORE INTO hospital_settings (id, name) VALUES (1, \'المستشفى\')');
    $pdo->exec('INSERT IGNORE INTO departments (name, icon, sort_order) VALUES (\'عام\', \'layers\', 0)');

    $adminCols = [
        'full_name' => 'VARCHAR(191) NULL',
        'email' => 'VARCHAR(191) NULL',
        'phone' => 'VARCHAR(64) NULL',
        'photo_path' => 'VARCHAR(512) NULL',
    ];
    foreach ($adminCols as $col => $def) {
        if (table_exists($pdo, 'admin_users') && !column_exists($pdo, 'admin_users', $col)) {
            $pdo->exec('ALTER TABLE admin_users ADD COLUMN ' . $col . ' ' . $def);
        }
    }

    if (!table_exists($pdo, 'doctors')) {
        $pdo->exec('SET FOREIGN_KEY_CHECKS=1');

        return;
    }

    if (!column_exists($pdo, 'doctors', 'department_id')) {
        $pdo->exec('ALTER TABLE doctors ADD COLUMN department_id INT UNSIGNED NULL AFTER specialty');
    }

    $defaultDepId = ensure_default_department($pdo);

    if (column_exists($pdo, 'doctors', 'department')) {
        $dist = $pdo->query('SELECT DISTINCT TRIM(department) AS d FROM doctors WHERE TRIM(department) <> \'\'')->fetchAll();
        foreach ($dist as $row) {
            $name = (string) $row['d'];
            if ($name === '') {
                continue;
            }
            $ins = $pdo->prepare('INSERT IGNORE INTO departments (name, icon, sort_order) VALUES (?, \'layers\', 0)');
            $ins->execute([$name]);
        }

        $pdo->exec(
            'UPDATE doctors doc
            INNER JOIN departments dep ON dep.name = doc.department
            SET doc.department_id = dep.id
            WHERE doc.department_id IS NULL AND TRIM(doc.department) <> \'\''
        );

        $pdo->prepare('UPDATE doctors SET department_id = ? WHERE department_id IS NULL')->execute([$defaultDepId]);

        try {
            $pdo->exec('ALTER TABLE doctors DROP COLUMN department');
        } catch (Throwable $e) {
            // ignore
        }
    } else {
        $pdo->prepare('UPDATE doctors SET department_id = ? WHERE department_id IS NULL')->execute([$defaultDepId]);
    }

    if (!fk_exists($pdo, 'doctors', 'fk_doctors_department')) {
        try {
            $pdo->exec(
                'ALTER TABLE doctors ADD CONSTRAINT fk_doctors_department FOREIGN KEY (department_id) REFERENCES departments (id) ON DELETE SET NULL'
            );
        } catch (Throwable $e) {
            // ignore
        }
    }

    $pdo->exec('SET FOREIGN_KEY_CHECKS=1');

    $root = dirname(__DIR__);
    foreach (['uploads' . DIRECTORY_SEPARATOR . 'hospital', 'uploads' . DIRECTORY_SEPARATOR . 'admin'] as $rel) {
        $dir = $root . DIRECTORY_SEPARATOR . $rel;
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }
}

function migrate_v3(PDO $pdo): void
{
    if (table_exists($pdo, 'departments') && !column_exists($pdo, 'departments', 'banner_image_path')) {
        $pdo->exec('ALTER TABLE departments ADD COLUMN banner_image_path VARCHAR(512) NULL AFTER icon');
    }

    $root = dirname(__DIR__);
    $dir = $root . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'departments';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

function migrate_v4(PDO $pdo): void
{
    if (table_exists($pdo, 'screens') && !column_exists($pdo, 'screens', 'display_style')) {
        $pdo->exec("ALTER TABLE screens ADD COLUMN display_style VARCHAR(32) NOT NULL DEFAULT 'hero_medical' AFTER refresh_seconds");
    }
}

function migrate_v5(PDO $pdo): void
{
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS display_styles (
      id INT UNSIGNED NOT NULL AUTO_INCREMENT,
      style_key VARCHAR(64) NOT NULL,
      name VARCHAR(191) NOT NULL,
      style_type VARCHAR(32) NOT NULL DEFAULT \'custom\',
      config_json LONGTEXT NOT NULL,
      css_text LONGTEXT,
      metadata_json LONGTEXT,
      is_system TINYINT(1) NOT NULL DEFAULT 0,
      created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (id),
      UNIQUE KEY uk_display_style_key (style_key)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $heroCfg = json_encode(default_style_config('hero'), JSON_UNESCAPED_UNICODE);
    $cardCfg = json_encode(default_style_config('card'), JSON_UNESCAPED_UNICODE);
    if ($heroCfg === false || $cardCfg === false) {
        $heroCfg = '{}';
        $cardCfg = '{}';
    }

    $metaHero = json_encode(['version' => 1, 'source' => 'system'], JSON_UNESCAPED_UNICODE) ?: '{}';
    $metaCard = json_encode(['version' => 1, 'source' => 'system'], JSON_UNESCAPED_UNICODE) ?: '{}';

    $ins = $pdo->prepare(
        'INSERT IGNORE INTO display_styles (style_key, name, style_type, config_json, css_text, metadata_json, is_system) VALUES (?,?,?,?,?,?,1)'
    );
    $ins->execute(['hero_medical', 'Hero Medical Style', 'hero', $heroCfg, '', $metaHero]);
    $ins->execute(['card_social', 'Card / Facebook-like Style', 'card', $cardCfg, '', $metaCard]);
}

function migrate_v6(PDO $pdo): void
{
    $cfg = [
        'layout' => 'hero',
        'image_behavior' => [
            'doctor' => 'doctor_hero',
            'department' => 'department_background',
        ],
        'overlay' => [
            'gradient' => true,
            'blur' => 30,
            'opacity' => 0.48,
        ],
        'typography' => [
            'name_size' => 'clamp(1.9rem, 6.2vw, 4rem)',
            'spec_size' => 'clamp(1rem, 2.7vw, 1.45rem)',
            'time_size' => 'clamp(1.05rem, 3.1vw, 1.55rem)',
            'spacing' => 1.05,
        ],
        'colors' => [
            'primary' => '#2fae66',
            'secondary' => '#2c7fb8',
            'surface' => '#0a1628',
            'text' => '#ffffff',
        ],
        'animations' => [
            'type' => 'fade',
            'duration_ms' => 950,
        ],
    ];
    $css = <<<'CSS'
.cinematic-stack__ambient-img {
  filter: blur(34px) saturate(1.12);
  opacity: .44;
}
.cinematic-stack__photo {
  object-position: center 16%;
  filter: saturate(1.05) contrast(1.03);
}
.cinematic-name {
  letter-spacing: .01em;
  text-shadow: 0 3px 24px rgba(0, 0, 0, .62);
}
.cinematic-row--time {
  background: rgba(8, 16, 28, .52);
  border: 1px solid rgba(255, 255, 255, .28);
}
.cinematic-stack--ok .cinematic-status-line {
  background: linear-gradient(90deg, #2fae66, rgba(47, 174, 102, .55));
}
.cinematic-stack--no .cinematic-status-line {
  background: linear-gradient(90deg, #334155, rgba(71, 85, 105, .72));
}
CSS;
    $cfgJson = json_encode($cfg, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}';
    $meta = json_encode(['version' => 1, 'source' => 'system', 'preset' => 'immersive_signature'], JSON_UNESCAPED_UNICODE) ?: '{}';

    $pdo->prepare(
        'INSERT IGNORE INTO display_styles (style_key, name, style_type, config_json, css_text, metadata_json, is_system) VALUES (?,?,?,?,?,?,1)'
    )->execute([
        'immersive_signature',
        'Immersive Medical Signature',
        'hero',
        $cfgJson,
        $css,
        $meta,
    ]);
}

function migrate_v7(PDO $pdo): void
{
    $cfg = [
        'layout' => 'split',
        'image_behavior' => [
            'doctor' => 'doctor_hero',
            'department' => 'department_background',
        ],
        'overlay' => [
            'gradient' => true,
            'blur' => 20,
            'opacity' => 0.5,
        ],
        'typography' => [
            'name_size' => 'clamp(1.8rem, 5.5vw, 3.8rem)',
            'spec_size' => 'clamp(1rem, 2.5vw, 1.35rem)',
            'time_size' => 'clamp(1.05rem, 2.9vw, 1.45rem)',
            'spacing' => 1.08,
        ],
        'colors' => [
            'primary' => '#2fae66',
            'secondary' => '#2c7fb8',
            'surface' => '#081425',
            'text' => '#eaf3ff',
        ],
        'animations' => [
            'type' => 'fade',
            'duration_ms' => 1000,
        ],
    ];
    $css = <<<'CSS'
/* Aurora Split Signature — out-of-the-box premium split */
.card-display {
  background:
    radial-gradient(100% 80% at 0% 0%, rgba(47, 174, 102, .18), transparent 62%),
    radial-gradient(90% 80% at 100% 100%, rgba(44, 127, 184, .23), transparent 60%),
    #050b15;
}
.card-display__card {
  width: min(98vw, 1500px);
  min-height: min(92vh, 980px);
  border-radius: 30px;
  background: linear-gradient(120deg, rgba(8, 20, 38, .92), rgba(11, 28, 50, .92));
  border: 1px solid rgba(170, 210, 255, .18);
  box-shadow:
    0 32px 90px rgba(2, 6, 23, .58),
    inset 0 0 0 1px rgba(255, 255, 255, .04);
}
.card-display__banner {
  position: absolute;
  inset: 0;
  height: auto;
  z-index: 0;
}
.card-display__banner-img {
  filter: blur(20px) saturate(1.08);
  opacity: .34;
  transform: scale(1.08);
}
.card-display__banner-overlay {
  background:
    linear-gradient(130deg, rgba(5, 11, 21, .78), rgba(5, 11, 21, .55)),
    linear-gradient(130deg, rgba(47, 174, 102, .15), rgba(44, 127, 184, .22));
}
.card-display__body {
  position: relative;
  z-index: 1;
  grid-template-columns: minmax(350px, 520px) 1fr;
  gap: clamp(1rem, 2.8vw, 2.4rem);
  min-height: min(92vh, 980px);
}
.card-display__hero {
  justify-content: flex-start;
}
.card-display__hero-img,
.card-display__hero-empty {
  width: min(100%, 500px);
  height: clamp(44vh, 52vh, 58vh);
  border-radius: 26px;
  border: 1px solid rgba(174, 228, 255, .35);
  box-shadow:
    0 24px 62px rgba(2, 6, 23, .52),
    0 0 32px rgba(44, 127, 184, .18);
}
.card-display__hero-fade {
  border-radius: 26px;
  background: linear-gradient(to top, rgba(7, 14, 27, .26), transparent 44%);
}
.card-display__info {
  color: #eaf3ff;
  align-items: flex-start;
  text-align: right;
}
.card-display__name {
  color: #f6fbff;
  text-shadow: 0 2px 24px rgba(0, 0, 0, .45);
}
.card-display__spec {
  color: rgba(226, 239, 255, .88);
}
.card-display__time {
  background: rgba(9, 22, 38, .62);
  color: #eaf3ff;
  box-shadow: inset 0 0 0 1px rgba(166, 205, 255, .22);
}
.card-display__badge--ok {
  background: linear-gradient(90deg, #2fae66, #24c877);
}
.card-display__badge--no {
  background: linear-gradient(90deg, #475569, #334155);
}
.card-display__dept-badge {
  background: rgba(8, 20, 38, .48);
  border-color: rgba(174, 228, 255, .3);
}
@media (max-width: 1100px) {
  .card-display__body {
    grid-template-columns: 1fr;
    justify-items: center;
  }
  .card-display__hero {
    justify-content: center;
  }
  .card-display__info {
    align-items: center;
    text-align: center;
  }
}
CSS;

    $cfgJson = json_encode($cfg, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}';
    $meta = json_encode(['version' => 1, 'source' => 'system', 'preset' => 'aurora_split_signature'], JSON_UNESCAPED_UNICODE) ?: '{}';
    $pdo->prepare(
        'INSERT IGNORE INTO display_styles (style_key, name, style_type, config_json, css_text, metadata_json, is_system) VALUES (?,?,?,?,?,?,1)'
    )->execute([
        'aurora_split_signature',
        'Aurora Split Signature',
        'split',
        $cfgJson,
        $css,
        $meta,
    ]);
}

function migrate_v8(PDO $pdo): void
{
    $cfg = [
        'layout' => 'card',
        'image_behavior' => [
            'doctor' => 'doctor_hero',
            'department' => 'department_background',
        ],
        'overlay' => [
            'gradient' => true,
            'blur' => 12,
            'opacity' => 0.26,
        ],
        'typography' => [
            'name_size' => 'clamp(1.4rem, 4.2vw, 2.4rem)',
            'spec_size' => 'clamp(0.95rem, 2.2vw, 1.2rem)',
            'time_size' => 'clamp(1rem, 2.5vw, 1.25rem)',
            'spacing' => 1.0,
        ],
        'colors' => [
            'primary' => '#2fae66',
            'secondary' => '#2c7fb8',
            'surface' => '#e7edf5',
            'text' => '#1f2937',
        ],
        'animations' => [
            'type' => 'fade',
            'duration_ms' => 850,
        ],
    ];
    $css = <<<'CSS'
/* Soft Card Medical — Neumorphism */
.card-display {
  background:
    radial-gradient(80% 60% at 0% 0%, rgba(47, 174, 102, .16), transparent 58%),
    radial-gradient(85% 65% at 100% 100%, rgba(44, 127, 184, .16), transparent 62%),
    linear-gradient(160deg, #edf3fb 0%, #dbe7f4 100%);
}
.card-display__card {
  width: min(92vw, 640px);
  min-height: min(86vh, 900px);
  border-radius: 34px;
  background: linear-gradient(160deg, #eaf1f9, #dde8f5);
  border: 1px solid rgba(255, 255, 255, .65);
  box-shadow:
    16px 16px 34px rgba(158, 176, 198, .42),
    -14px -14px 30px rgba(255, 255, 255, .95),
    inset 0 1px 0 rgba(255, 255, 255, .7);
  overflow: hidden;
}
.card-display__banner {
  position: absolute;
  inset: 0;
  height: auto;
  z-index: 0;
}
.card-display__banner-img {
  filter: blur(18px) saturate(1.02);
  opacity: .22;
  transform: scale(1.08);
}
.card-display__banner-overlay {
  background: linear-gradient(180deg, rgba(255, 255, 255, .24), rgba(219, 231, 244, .5));
}
.card-display__body {
  position: relative;
  z-index: 1;
  grid-template-columns: 1fr;
  justify-items: center;
  align-items: start;
  gap: .85rem;
  min-height: auto;
  padding: 1.2rem 1.15rem 1.35rem;
}
.card-display__hero {
  justify-content: center;
}
.card-display__hero-img,
.card-display__hero-empty {
  width: min(100%, 420px);
  height: clamp(34vh, 42vh, 50vh);
  border-radius: 28px;
  border: 1px solid rgba(255, 255, 255, .75);
  box-shadow:
    10px 10px 24px rgba(152, 172, 195, .35),
    -8px -8px 18px rgba(255, 255, 255, .82);
}
.card-display__hero-fade {
  border-radius: 28px;
  background: linear-gradient(to top, rgba(15, 23, 42, .14), transparent 50%);
}
.card-display__info {
  width: 100%;
  align-items: center;
  text-align: center;
  color: #223041;
}
.card-display__name {
  color: #1e2a39;
}
.card-display__spec {
  color: #526478;
}
.card-display__time {
  background: #e9f0f8;
  color: #1f2f42;
  border: 1px solid rgba(255, 255, 255, .72);
  box-shadow:
    inset 2px 2px 6px rgba(159, 177, 198, .2),
    inset -2px -2px 6px rgba(255, 255, 255, .75);
}
.card-display__badge {
  animation: soft-pulse 2.1s ease-in-out infinite;
}
.card-display__badge--ok {
  background: linear-gradient(160deg, #33b970, #24a35f);
}
.card-display__badge--no {
  background: linear-gradient(160deg, #d45353, #ba3f3f);
}
.card-display__dept-badge {
  background: rgba(234, 242, 251, .82);
  color: #264257;
  border-color: rgba(255, 255, 255, .78);
  box-shadow:
    6px 6px 14px rgba(160, 178, 200, .28),
    -6px -6px 12px rgba(255, 255, 255, .9);
}
@keyframes soft-pulse {
  0%, 100% { transform: scale(1); }
  50% { transform: scale(1.04); }
}
CSS;

    $cfgJson = json_encode($cfg, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}';
    $meta = json_encode(['version' => 1, 'source' => 'system', 'preset' => 'soft_card_medical'], JSON_UNESCAPED_UNICODE) ?: '{}';
    $pdo->prepare(
        'INSERT IGNORE INTO display_styles (style_key, name, style_type, config_json, css_text, metadata_json, is_system) VALUES (?,?,?,?,?,?,1)'
    )->execute([
        'soft_card_medical',
        'Soft Card Medical (Neumorphism)',
        'card',
        $cfgJson,
        $css,
        $meta,
    ]);
}

function migrate_v9(PDO $pdo): void
{
    $cfg = [
        'layout' => 'card',
        'image_behavior' => [
            'doctor' => 'doctor_hero',
            'department' => 'department_background',
        ],
        'overlay' => [
            'gradient' => true,
            'blur' => 12,
            'opacity' => 0.26,
        ],
        'typography' => [
            'name_size' => 'clamp(1.48rem, 4.5vw, 2.6rem)',
            'spec_size' => 'clamp(0.98rem, 2.3vw, 1.22rem)',
            'time_size' => 'clamp(1.02rem, 2.6vw, 1.3rem)',
            'spacing' => 1.02,
        ],
        'colors' => [
            'primary' => '#2fae66',
            'secondary' => '#2c7fb8',
            'surface' => '#e7edf5',
            'text' => '#1f2937',
        ],
        'animations' => [
            'type' => 'hero_intro',
            'duration_ms' => 1300,
        ],
    ];
    $css = <<<'CSS'
/* Soft Card Medical v2 — hero intro (100% -> 70%), delayed text */
.card-display {
  background:
    radial-gradient(80% 60% at 0% 0%, rgba(47, 174, 102, .16), transparent 58%),
    radial-gradient(85% 65% at 100% 100%, rgba(44, 127, 184, .16), transparent 62%),
    linear-gradient(160deg, #edf3fb 0%, #dbe7f4 100%);
}
.card-display__card {
  width: min(92vw, 700px);
  min-height: min(90vh, 960px);
  border-radius: 36px;
  background: linear-gradient(160deg, #eaf1f9, #dde8f5);
  border: 1px solid rgba(255, 255, 255, .68);
  box-shadow:
    18px 18px 36px rgba(158, 176, 198, .42),
    -14px -14px 30px rgba(255, 255, 255, .95),
    inset 0 1px 0 rgba(255, 255, 255, .72);
}
.card-display__banner {
  position: absolute;
  inset: 0;
  height: auto;
  z-index: 0;
}
.card-display__banner-img {
  filter: blur(18px) saturate(1.02);
  opacity: .22;
  transform: scale(1.08);
}
.card-display__banner-overlay {
  background: linear-gradient(180deg, rgba(255, 255, 255, .24), rgba(219, 231, 244, .5));
}
.card-display__body {
  position: relative;
  z-index: 1;
  grid-template-columns: 1fr;
  justify-items: center;
  align-items: start;
  gap: .85rem;
  min-height: auto;
  padding: 1.2rem 1.15rem 1.35rem;
}
.card-display__hero {
  justify-content: center;
  width: 100%;
}
.card-display__hero-img,
.card-display__hero-empty {
  width: min(100%, 460px);
  height: clamp(38vh, 46vh, 54vh);
  border-radius: 30px;
  border: 1px solid rgba(255, 255, 255, .78);
  box-shadow:
    10px 10px 24px rgba(152, 172, 195, .35),
    -8px -8px 18px rgba(255, 255, 255, .82);
  transform-origin: top center;
  animation: soft-hero-intro 1.35s cubic-bezier(.2,.75,.18,1) forwards;
}
.card-display__hero-fade {
  border-radius: 30px;
  background: linear-gradient(to top, rgba(15, 23, 42, .14), transparent 50%);
}
.card-display__info {
  width: 100%;
  align-items: center;
  text-align: center;
  color: #223041;
  opacity: 0;
  transform: translateY(18px);
  animation: soft-info-reveal .65s ease .95s forwards;
}
.card-display__name {
  color: #1e2a39;
}
.card-display__spec {
  color: #526478;
}
.card-display__time {
  background: #e9f0f8;
  color: #1f2f42;
  border: 1px solid rgba(255, 255, 255, .72);
  box-shadow:
    inset 2px 2px 6px rgba(159, 177, 198, .2),
    inset -2px -2px 6px rgba(255, 255, 255, .75);
}
.card-display__badge {
  animation: soft-pulse 2.1s ease-in-out infinite;
  padding: .56rem .95rem;
  font-size: clamp(.95rem, 2.1vw, 1.15rem);
}
.card-display__badge svg {
  width: 2.5em;
  height: 2.5em;
}
.card-display__badge--ok {
  background: linear-gradient(160deg, #33b970, #24a35f);
}
.card-display__badge--no {
  background: linear-gradient(160deg, #d45353, #ba3f3f);
}
.card-display__dept-badge {
  background: rgba(234, 242, 251, .82);
  color: #264257;
  border-color: rgba(255, 255, 255, .78);
  box-shadow:
    6px 6px 14px rgba(160, 178, 200, .28),
    -6px -6px 12px rgba(255, 255, 255, .9);
}
@keyframes soft-hero-intro {
  0% { transform: scale(1); }
  100% { transform: scale(.7); }
}
@keyframes soft-info-reveal {
  to { opacity: 1; transform: translateY(0); }
}
@keyframes soft-pulse {
  0%, 100% { transform: scale(1); }
  50% { transform: scale(1.04); }
}
CSS;

    $cfgJson = json_encode($cfg, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}';
    $meta = json_encode(['version' => 2, 'source' => 'system', 'preset' => 'soft_card_medical'], JSON_UNESCAPED_UNICODE) ?: '{}';
    $pdo->prepare(
        'INSERT INTO display_styles (style_key, name, style_type, config_json, css_text, metadata_json, is_system)
         VALUES (?,?,?,?,?,?,1)
         ON DUPLICATE KEY UPDATE
           name = VALUES(name),
           style_type = VALUES(style_type),
           config_json = VALUES(config_json),
           css_text = VALUES(css_text),
           metadata_json = VALUES(metadata_json)'
    )->execute([
        'soft_card_medical',
        'Soft Card Medical (Neumorphism)',
        'card',
        $cfgJson,
        $css,
        $meta,
    ]);
}

function migrate_v10(PDO $pdo): void
{
    $cfg = [
        'layout' => 'card',
        'image_behavior' => [
            'doctor' => 'doctor_hero',
            'department' => 'department_background',
        ],
        'overlay' => [
            'gradient' => true,
            'blur' => 12,
            'opacity' => 0.26,
        ],
        'typography' => [
            'name_size' => 'clamp(1.48rem, 4.5vw, 2.6rem)',
            'spec_size' => 'clamp(0.98rem, 2.3vw, 1.22rem)',
            'time_size' => 'clamp(1.02rem, 2.6vw, 1.3rem)',
            'spacing' => 1.02,
        ],
        'colors' => [
            'primary' => '#2fae66',
            'secondary' => '#2c7fb8',
            'surface' => '#e7edf5',
            'text' => '#1f2937',
        ],
        'animations' => [
            'type' => 'hero_intro',
            'duration_ms' => 1300,
        ],
    ];
    $css = <<<'CSS'
/* Soft Card Medical v3 — portrait/small screens: doctor image fixed 70vh */
.card-display {
  background:
    radial-gradient(80% 60% at 0% 0%, rgba(47, 174, 102, .16), transparent 58%),
    radial-gradient(85% 65% at 100% 100%, rgba(44, 127, 184, .16), transparent 62%),
    linear-gradient(160deg, #edf3fb 0%, #dbe7f4 100%);
}
.card-display__card {
  width: min(92vw, 700px);
  min-height: min(90vh, 960px);
  border-radius: 36px;
  background: linear-gradient(160deg, #eaf1f9, #dde8f5);
  border: 1px solid rgba(255, 255, 255, .68);
}
.card-display__hero {
  justify-content: center;
  width: 100%;
}
.card-display__hero-img,
.card-display__hero-empty {
  width: min(100%, 460px);
  height: clamp(38vh, 46vh, 54vh);
  border-radius: 30px;
  transform-origin: top center;
  animation: soft-hero-intro 1.35s cubic-bezier(.2,.75,.18,1) forwards;
}
.card-display__info {
  opacity: 0;
  transform: translateY(18px);
  animation: soft-info-reveal .65s ease .95s forwards;
}
.card-display__badge svg {
  width: 2.5em;
  height: 2.5em;
}
@media (max-width: 900px), (orientation: portrait) {
  .card-display__hero-img,
  .card-display__hero-empty {
    width: 100%;
    max-width: none;
    height: 70vh;
    animation: none !important;
    transform: none !important;
  }
}
@keyframes soft-hero-intro {
  0% { transform: scale(1); }
  100% { transform: scale(.7); }
}
@keyframes soft-info-reveal {
  to { opacity: 1; transform: translateY(0); }
}
CSS;

    $cfgJson = json_encode($cfg, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}';
    $meta = json_encode(['version' => 3, 'source' => 'system', 'preset' => 'soft_card_medical'], JSON_UNESCAPED_UNICODE) ?: '{}';
    $pdo->prepare(
        'INSERT INTO display_styles (style_key, name, style_type, config_json, css_text, metadata_json, is_system)
         VALUES (?,?,?,?,?,?,1)
         ON DUPLICATE KEY UPDATE
           name = VALUES(name),
           style_type = VALUES(style_type),
           config_json = VALUES(config_json),
           css_text = VALUES(css_text),
           metadata_json = VALUES(metadata_json)'
    )->execute([
        'soft_card_medical',
        'Soft Card Medical (Neumorphism)',
        'card',
        $cfgJson,
        $css,
        $meta,
    ]);
}

function migrate_v11(PDO $pdo): void
{
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS content_groups (
      id INT UNSIGNED NOT NULL AUTO_INCREMENT,
      name VARCHAR(191) NOT NULL,
      loop_enabled TINYINT(1) NOT NULL DEFAULT 1,
      created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (id),
      UNIQUE KEY uk_content_group_name (name)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS display_contents (
      id INT UNSIGNED NOT NULL AUTO_INCREMENT,
      group_id INT UNSIGNED NOT NULL,
      name VARCHAR(191) NOT NULL,
      content_type ENUM(\'image\',\'video\',\'gif\') NOT NULL DEFAULT \'image\',
      file_path VARCHAR(512) NOT NULL,
      department_id INT UNSIGNED DEFAULT NULL,
      duration_seconds TINYINT UNSIGNED NOT NULL DEFAULT 8,
      sort_order INT NOT NULL DEFAULT 0,
      is_active TINYINT(1) NOT NULL DEFAULT 1,
      created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (id),
      KEY idx_content_group (group_id),
      KEY idx_content_department (department_id),
      CONSTRAINT fk_content_group FOREIGN KEY (group_id) REFERENCES content_groups (id) ON DELETE CASCADE,
      CONSTRAINT fk_content_department FOREIGN KEY (department_id) REFERENCES departments (id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    if (table_exists($pdo, 'screens') && !column_exists($pdo, 'screens', 'display_mode')) {
        $pdo->exec("ALTER TABLE screens ADD COLUMN display_mode ENUM('doctors','content') NOT NULL DEFAULT 'doctors' AFTER display_style");
    }
    if (table_exists($pdo, 'screens') && !column_exists($pdo, 'screens', 'content_group_id')) {
        $pdo->exec('ALTER TABLE screens ADD COLUMN content_group_id INT UNSIGNED NULL AFTER display_mode');
        try {
            $pdo->exec('ALTER TABLE screens ADD CONSTRAINT fk_screens_content_group FOREIGN KEY (content_group_id) REFERENCES content_groups (id) ON DELETE SET NULL');
        } catch (Throwable $e) {
            // ignore if already exists
        }
    }

    $root = dirname(__DIR__);
    $dir = $root . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'content';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

function migrate_v12(PDO $pdo): void
{
    if (table_exists($pdo, 'display_contents') && !column_exists($pdo, 'display_contents', 'doctor_id')) {
        $pdo->exec('ALTER TABLE display_contents ADD COLUMN doctor_id INT UNSIGNED NULL AFTER department_id');
        try {
            $pdo->exec('ALTER TABLE display_contents ADD KEY idx_content_doctor (doctor_id)');
        } catch (Throwable $e) {
            // ignore
        }
        try {
            $pdo->exec('ALTER TABLE display_contents ADD CONSTRAINT fk_content_doctor FOREIGN KEY (doctor_id) REFERENCES doctors (id) ON DELETE SET NULL');
        } catch (Throwable $e) {
            // ignore
        }
    }
}

function migrate_v13(PDO $pdo): void
{
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS dashboard_stat_snapshots (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            captured_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            available_count INT UNSIGNED NOT NULL DEFAULT 0,
            unavailable_count INT UNSIGNED NOT NULL DEFAULT 0,
            availability_pct DECIMAL(5,2) NOT NULL DEFAULT 0.00,
            PRIMARY KEY (id),
            KEY idx_snapshot_captured_at (captured_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
}

function migrate_v15(PDO $pdo): void
{
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS welcome_broadcast (
            id TINYINT UNSIGNED NOT NULL DEFAULT 1,
            is_enabled TINYINT(1) NOT NULL DEFAULT 0,
            show_logo TINYINT(1) NOT NULL DEFAULT 1,
            title VARCHAR(255) NOT NULL DEFAULT \'\',
            subtitle VARCHAR(512) NOT NULL DEFAULT \'\',
            image_path VARCHAR(512) DEFAULT NULL,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
    $pdo->exec('INSERT IGNORE INTO welcome_broadcast (id) VALUES (1)');
}

function migrate_v16(PDO $pdo): void
{
    if (!table_exists($pdo, 'display_styles')) {
        return;
    }
    $cfg = [
        'layout' => 'minimal',
        'image_behavior' => [
            'doctor' => 'contain',
            'department' => 'none',
        ],
        'overlay' => [
            'gradient' => false,
            'blur' => 0,
            'opacity' => 0,
        ],
        'typography' => [
            'name_size' => 'clamp(1.8rem, 5.8vw, 3.1rem)',
            'spec_size' => 'clamp(1.08rem, 3.1vw, 1.55rem)',
            'time_size' => 'clamp(1rem, 2.8vw, 1.2rem)',
            'spacing' => 1.08,
        ],
        'colors' => [
            'primary' => '#22c55e',
            'secondary' => '#f87171',
            'surface' => '#0f172a',
            'text' => '#f8fafc',
        ],
        'animations' => [
            'type' => 'fade',
            'duration_ms' => 850,
        ],
    ];
    $cfgJson = json_encode($cfg, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}';
    $meta = json_encode(['version' => 1, 'source' => 'system', 'preset' => 'minimal_clear'], JSON_UNESCAPED_UNICODE) ?: '{}';
    $pdo->prepare(
        'INSERT IGNORE INTO display_styles (style_key, name, style_type, config_json, css_text, metadata_json, is_system) VALUES (?,?,?,?,?,?,1)'
    )->execute([
        'minimal_clear',
        'بسيط وواضح (صورة كاملة + متاح/غير متاح)',
        'minimal',
        $cfgJson,
        '',
        $meta,
    ]);
}

function migrate_v14(PDO $pdo): void
{
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS doctor_weekly_schedule (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            doctor_id INT UNSIGNED NOT NULL,
            weekday TINYINT UNSIGNED NOT NULL COMMENT \'1=Mon..7=Sun ISO-8601\',
            work_start TIME NOT NULL,
            work_end TIME NOT NULL,
            sort_order SMALLINT NOT NULL DEFAULT 0,
            PRIMARY KEY (id),
            KEY idx_dws_doctor_weekday (doctor_id, weekday),
            CONSTRAINT fk_dws_doctor FOREIGN KEY (doctor_id) REFERENCES doctors (id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    if (!table_exists($pdo, 'doctors')) {
        return;
    }

    $st = $pdo->query('SELECT id, work_start, work_end FROM doctors');
    $doctors = $st ? $st->fetchAll(PDO::FETCH_ASSOC) : [];
    $ins = $pdo->prepare(
        'INSERT INTO doctor_weekly_schedule (doctor_id, weekday, work_start, work_end, sort_order) VALUES (?,?,?,?,0)'
    );
    $chk = $pdo->prepare('SELECT 1 FROM doctor_weekly_schedule WHERE doctor_id = ? LIMIT 1');

    foreach ($doctors as $d) {
        $did = (int) $d['id'];
        $chk->execute([$did]);
        if ($chk->fetch()) {
            continue;
        }
        for ($w = 1; $w <= 7; $w++) {
            $ins->execute([$did, $w, $d['work_start'], $d['work_end']]);
        }
    }
}

function ensure_default_department(PDO $pdo): int
{
    $st = $pdo->prepare('SELECT id FROM departments WHERE name = ? LIMIT 1');
    $st->execute(['عام']);
    $row = $st->fetch();
    if ($row) {
        return (int) $row['id'];
    }
    $pdo->prepare('INSERT INTO departments (name, icon, sort_order) VALUES (?, \'layers\', 0)')->execute(['عام']);

    return (int) $pdo->lastInsertId();
}
