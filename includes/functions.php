<?php
declare(strict_types=1);

function app_config(): array
{
    static $c = null;
    if ($c === null) {
        $c = require dirname(__DIR__) . '/config/config.php';
    }
    return $c;
}

function base_path(): string
{
    return dirname(__DIR__);
}

/**
 * Public URL path prefix (no trailing slash), e.g. '' or '/harri'.
 */
function base_url(): string
{
    $cfg = app_config();
    if ($cfg['base_url'] !== '') {
        return rtrim($cfg['base_url'], '/');
    }
    $docRoot = realpath((string) ($_SERVER['DOCUMENT_ROOT'] ?? ''));
    $projectRoot = realpath(dirname(__DIR__));
    if ($docRoot !== false && $projectRoot !== false && str_starts_with($projectRoot, $docRoot)) {
        $rel = str_replace('\\', '/', substr($projectRoot, strlen($docRoot)));
        return $rel === '' ? '' : rtrim($rel, '/');
    }
    $script = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
    $dir = str_replace('\\', '/', dirname($script));
    if ($dir === '/' || $dir === '.') {
        return '';
    }
    return rtrim($dir, '/');
}

function url(string $path = ''): string
{
    $b = base_url();
    $path = ltrim($path, '/');
    if ($b === '') {
        return '/' . $path;
    }
    return $b . '/' . $path;
}

function esc(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

function random_token(int $bytes = 16): string
{
    return bin2hex(random_bytes($bytes));
}

/**
 * Current time within work window (same day; supports overnight if end < start).
 */
function is_within_hours(string $workStart, string $workEnd, ?DateTimeInterface $now = null): bool
{
    $now = $now ?? new DateTimeImmutable('now');
    $ws = array_map('intval', explode(':', trim($workStart)));
    $we = array_map('intval', explode(':', trim($workEnd)));
    $startSec = ($ws[0] ?? 0) * 3600 + ($ws[1] ?? 0) * 60 + ($ws[2] ?? 0);
    $endSec = ($we[0] ?? 0) * 3600 + ($we[1] ?? 0) * 60 + ($we[2] ?? 0);
    $cur = (int) $now->format('H') * 3600 + (int) $now->format('i') * 60 + (int) $now->format('s');

    if ($endSec <= $startSec) {
        return $cur >= $startSec || $cur < $endSec;
    }
    return $cur >= $startSec && $cur < $endSec;
}

/**
 * ISO-8601 weekday: 1 = Monday … 7 = Sunday.
 */
function current_iso_weekday(?DateTimeInterface $now = null): int
{
    $now = $now ?? new DateTimeImmutable('now');

    return (int) $now->format('N');
}

/**
 * @param list<int> $doctorIds
 * @return array<int, list<array{weekday:int,work_start:string,work_end:string}>>
 */
function doctor_weekly_schedules_map(PDO $pdo, array $doctorIds): array
{
    $doctorIds = array_values(array_unique(array_filter(array_map('intval', $doctorIds), static function ($id) {
        return (int) $id > 0;
    })));
    if ($doctorIds === []) {
        return [];
    }
    $placeholders = implode(',', array_fill(0, count($doctorIds), '?'));
    $sql = 'SELECT doctor_id, weekday, work_start, work_end FROM doctor_weekly_schedule WHERE doctor_id IN (' . $placeholders . ') ORDER BY doctor_id, weekday, sort_order, id';
    $st = $pdo->prepare($sql);
    $st->execute($doctorIds);
    $map = [];
    foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $did = (int) $r['doctor_id'];
        if (!isset($map[$did])) {
            $map[$did] = [];
        }
        $map[$did][] = [
            'weekday' => (int) $r['weekday'],
            'work_start' => (string) $r['work_start'],
            'work_end' => (string) $r['work_end'],
        ];
    }

    return $map;
}

/**
 * Auto mode: within any slot for today if weekly rows exist; else legacy work_start/work_end every day.
 *
 * @param list<array{weekday:int,work_start:string,work_end:string}> $weekly
 */
function doctor_auto_available_now(array $weekly, string $legacyStart, string $legacyEnd, ?DateTimeInterface $now = null): bool
{
    $now = $now ?? new DateTimeImmutable('now');
    if ($weekly === []) {
        return is_within_hours($legacyStart, $legacyEnd, $now);
    }
    $dow = current_iso_weekday($now);
    foreach ($weekly as $slot) {
        if ((int) ($slot['weekday'] ?? 0) !== $dow) {
            continue;
        }
        if (is_within_hours((string) $slot['work_start'], (string) $slot['work_end'], $now)) {
            return true;
        }
    }

    return false;
}

/**
 * Human-readable line for «today» (admin list + display API).
 *
 * @param list<array{weekday:int,work_start:string,work_end:string}> $weekly
 */
function doctor_time_display_for_today(array $row, array $weekly, ?DateTimeInterface $now = null): string
{
    $now = $now ?? new DateTimeImmutable('now');
    $dow = current_iso_weekday($now);
    $legacyS = substr((string) ($row['work_start'] ?? '08:00:00'), 0, 5);
    $legacyE = substr((string) ($row['work_end'] ?? '14:00:00'), 0, 5);

    if ($weekly === []) {
        return $legacyS . ' – ' . $legacyE;
    }

    $todaySlots = [];
    foreach ($weekly as $s) {
        if ((int) ($s['weekday'] ?? 0) === $dow) {
            $todaySlots[] = $s;
        }
    }
    if ($todaySlots === []) {
        return 'لا دوام اليوم';
    }
    $parts = [];
    foreach ($todaySlots as $s) {
        $parts[] = substr((string) $s['work_start'], 0, 5) . ' – ' . substr((string) $s['work_end'], 0, 5);
    }

    return implode('، ', $parts);
}

/**
 * First slot in Mon→Sun order for syncing doctors.work_start / work_end.
 *
 * @param list<array{weekday:int,work_start:string,work_end:string}> $weekly
 * @return array{0:string,1:string}
 */
function doctor_representative_hours(array $weekly, string $fallbackStart, string $fallbackEnd): array
{
    for ($w = 1; $w <= 7; $w++) {
        foreach ($weekly as $slot) {
            if ((int) ($slot['weekday'] ?? 0) === $w) {
                return [(string) $slot['work_start'], (string) $slot['work_end']];
            }
        }
    }

    return [$fallbackStart, $fallbackEnd];
}

function normalize_sql_time(string $t): string
{
    $t = trim($t);
    if (preg_match('/^(\d{1,2}):(\d{2})$/', $t, $m)) {
        return sprintf('%02d:%02d:00', (int) $m[1], (int) $m[2]);
    }
    if (preg_match('/^(\d{1,2}):(\d{2}):(\d{2})$/', $t, $m)) {
        return sprintf('%02d:%02d:%02d', (int) $m[1], (int) $m[2], (int) $m[3]);
    }

    return '08:00:00';
}

/**
 * @return list<array{weekday:int,work_start:string,work_end:string}>
 */
function parse_weekly_schedule_from_post(array $post): array
{
    $raw = $post['schedule'] ?? [];
    if (!is_array($raw)) {
        return [];
    }
    $out = [];
    for ($w = 1; $w <= 7; $w++) {
        $cell = $raw[(string) $w] ?? $raw[$w] ?? null;
        if (!is_array($cell)) {
            continue;
        }
        if (empty($cell['on'])) {
            continue;
        }
        $start = normalize_sql_time((string) ($cell['start'] ?? '08:00'));
        $end = normalize_sql_time((string) ($cell['end'] ?? '14:00'));
        $out[] = ['weekday' => $w, 'work_start' => $start, 'work_end' => $end];
    }

    return $out;
}

/**
 * @param list<array{weekday:int,work_start:string,work_end:string}> $weeklyRows
 */
function doctor_save_weekly_schedule(PDO $pdo, int $doctorId, array $weeklyRows): void
{
    $pdo->prepare('DELETE FROM doctor_weekly_schedule WHERE doctor_id = ?')->execute([$doctorId]);
    if ($weeklyRows === []) {
        return;
    }
    $ins = $pdo->prepare(
        'INSERT INTO doctor_weekly_schedule (doctor_id, weekday, work_start, work_end, sort_order) VALUES (?,?,?,?,0)'
    );
    foreach ($weeklyRows as $slot) {
        $ins->execute([
            $doctorId,
            (int) $slot['weekday'],
            $slot['work_start'],
            $slot['work_end'],
        ]);
    }
}

/**
 * @param array<string,mixed> $row optional key weekly_schedule: list<array{weekday:int,work_start:string,work_end:string}>
 * @return 'available'|'unavailable'
 */
function doctor_effective_status(array $row): string
{
    if (($row['status_mode'] ?? 'auto') === 'manual') {
        return $row['manual_status'] === 'unavailable' ? 'unavailable' : 'available';
    }
    $weekly = $row['weekly_schedule'] ?? [];
    if (!is_array($weekly)) {
        $weekly = [];
    }

    return doctor_auto_available_now(
        $weekly,
        (string) ($row['work_start'] ?? '08:00:00'),
        (string) ($row['work_end'] ?? '17:00:00')
    ) ? 'available' : 'unavailable';
}

/**
 * @param array<int,array<string,mixed>> $rows rows with integer id
 * @return array<int,array<string,mixed>>
 */
function doctors_attach_weekly_schedule(PDO $pdo, array $rows): array
{
    $ids = array_map('intval', array_column($rows, 'id'));
    $map = doctor_weekly_schedules_map($pdo, $ids);
    foreach ($rows as &$r) {
        $did = (int) ($r['id'] ?? 0);
        $r['weekly_schedule'] = $map[$did] ?? [];
    }
    unset($r);

    return $rows;
}

/**
 * Sort: available first, then sort_order, then name.
 *
 * @param array<int,array<string,mixed>> $rows
 * @return array<int,array<string,mixed>>
 */
function sort_doctors_display(array $rows): array
{
    usort($rows, static function (array $a, array $b): int {
        $sa = doctor_effective_status($a) === 'available' ? 0 : 1;
        $sb = doctor_effective_status($b) === 'available' ? 0 : 1;
        if ($sa !== $sb) {
            return $sa <=> $sb;
        }
        $oa = (int) ($a['sort_order'] ?? 0);
        $ob = (int) ($b['sort_order'] ?? 0);
        if ($oa !== $ob) {
            return $oa <=> $ob;
        }
        return strcmp((string) ($a['name'] ?? ''), (string) ($b['name'] ?? ''));
    });
    return $rows;
}

/**
 * Save one image upload (JPG/PNG/WebP) under $destDir. Empty field → basename null.
 *
 * @return array{ok: bool, basename: ?string, error: ?string}
 */
function save_uploaded_image_file(array $file, string $destDir, array $cfg): array
{
    if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        return ['ok' => true, 'basename' => null, 'error' => null];
    }
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    if (!in_array($mime, $cfg['allowed_image_mimes'], true)) {
        return ['ok' => false, 'basename' => null, 'error' => 'type'];
    }
    if (($file['size'] ?? 0) > $cfg['upload_max_kb'] * 1024) {
        return ['ok' => false, 'basename' => null, 'error' => 'size'];
    }
    if (!is_dir($destDir)) {
        mkdir($destDir, 0755, true);
    }
    $ext = $mime === 'image/png' ? 'png' : ($mime === 'image/webp' ? 'webp' : 'jpg');
    $newName = random_token(12) . '.' . $ext;
    $dest = $destDir . DIRECTORY_SEPARATOR . $newName;
    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        return ['ok' => false, 'basename' => null, 'error' => 'move'];
    }

    return ['ok' => true, 'basename' => $newName, 'error' => null];
}

function unlink_uploaded_basename(string $dir, ?string $basename): void
{
    if ($basename === null || $basename === '') {
        return;
    }
    $p = $dir . DIRECTORY_SEPARATOR . basename($basename);
    if (is_file($p)) {
        @unlink($p);
    }
}
