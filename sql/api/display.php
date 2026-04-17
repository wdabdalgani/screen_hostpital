<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/display_styles.php';
require_once dirname(__DIR__) . '/includes/welcome_broadcast.php';
require_once dirname(__DIR__) . '/config/database.php';

$token = isset($_GET['token']) ? preg_replace('/[^a-f0-9]/', '', strtolower((string) $_GET['token'])) : '';
if (strlen($token) !== 32) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'invalid_token'], JSON_UNESCAPED_UNICODE);
    exit;
}

$pdo = db();
$st = $pdo->prepare('SELECT id, name, slide_seconds, refresh_seconds, display_style, display_mode, content_group_id FROM screens WHERE token = ? LIMIT 1');
$st->execute([$token]);
$screen = $st->fetch();
if (!$screen) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'error' => 'not_found'], JSON_UNESCAPED_UNICODE);
    exit;
}

$out = [];
$contentOut = [];
$displayMode = (string) ($screen['display_mode'] ?? 'doctors');
if (!in_array($displayMode, ['doctors', 'content'], true)) {
    $displayMode = 'doctors';
}

if ($displayMode === 'content' && (int) ($screen['content_group_id'] ?? 0) > 0) {
    $cg = $pdo->prepare('SELECT id, name, loop_enabled FROM content_groups WHERE id = ? LIMIT 1');
    $cg->execute([(int) $screen['content_group_id']]);
    $group = $cg->fetch();
    if ($group) {
        $cs = $pdo->prepare(
            'SELECT c.*, d.name AS department_name, d.icon AS department_icon,
                    doc.name AS doctor_name, doc.work_start AS doctor_work_start, doc.work_end AS doctor_work_end,
                    doc.status_mode AS doctor_status_mode, doc.manual_status AS doctor_manual_status
             FROM display_contents c
             LEFT JOIN departments d ON d.id = c.department_id
             LEFT JOIN doctors doc ON doc.id = c.doctor_id
             WHERE c.group_id = ? AND c.is_active = 1
             ORDER BY c.sort_order ASC, c.id ASC'
        );
        $cs->execute([(int) $group['id']]);
        $contentRows = $cs->fetchAll();
        $docIds = [];
        foreach ($contentRows as $cr) {
            if (!empty($cr['doctor_id'])) {
                $docIds[] = (int) $cr['doctor_id'];
            }
        }
        $contentSchedMap = doctor_weekly_schedules_map($pdo, $docIds);
        foreach ($contentRows as $r) {
            $doctorStatus = null;
            if (!empty($r['doctor_name'])) {
                $did = (int) ($r['doctor_id'] ?? 0);
                $doctorStatus = doctor_effective_status([
                    'status_mode' => $r['doctor_status_mode'],
                    'manual_status' => $r['doctor_manual_status'],
                    'work_start' => $r['doctor_work_start'],
                    'work_end' => $r['doctor_work_end'],
                    'weekly_schedule' => $did > 0 ? ($contentSchedMap[$did] ?? []) : [],
                ]);
            }
            $contentOut[] = [
                'id' => (int) $r['id'],
                'name' => (string) $r['name'],
                'group_name' => (string) ($group['name'] ?? ''),
                'type' => (string) $r['content_type'],
                'url' => url('uploads/content/' . basename((string) $r['file_path'])),
                'duration_seconds' => max(5, min(15, (int) $r['duration_seconds'])),
                'department' => (string) ($r['department_name'] ?? ''),
                'department_icon' => (string) ($r['department_icon'] ?? 'layers'),
                'doctor_name' => (string) ($r['doctor_name'] ?? ''),
                'doctor_status' => $doctorStatus,
            ];
        }
        if (!count($contentOut)) {
            $displayMode = 'doctors';
        }
    } else {
        $displayMode = 'doctors';
    }
}

if ($displayMode === 'doctors') {
    $sql = 'SELECT d.id, d.name, d.specialty, d.image_path, d.work_start, d.work_end, d.status_mode, d.manual_status, d.sort_order,
            dep.name AS department_name, dep.icon AS department_icon, dep.banner_image_path AS department_banner_path
            FROM doctors d
            LEFT JOIN departments dep ON dep.id = d.department_id
            WHERE d.screen_id = ?';
    $st = $pdo->prepare($sql);
    $st->execute([(int) $screen['id']]);
    $rows = doctors_attach_weekly_schedule($pdo, $st->fetchAll());

    $rows = sort_doctors_display($rows);
    foreach ($rows as $r) {
        $eff = doctor_effective_status($r);
        $deptBanner = !empty($r['department_banner_path'])
            ? url('uploads/departments/' . basename((string) $r['department_banner_path']))
            : null;
        $weekly = $r['weekly_schedule'] ?? [];
        $timeDisplay = doctor_time_display_for_today($r, is_array($weekly) ? $weekly : []);
        $out[] = [
            'id' => (int) $r['id'],
            'name' => $r['name'],
            'specialty' => $r['specialty'],
            'department' => $r['department_name'] ?? '',
            'department_icon' => $r['department_icon'] ?? 'layers',
            'department_banner' => $deptBanner,
            'image' => $r['image_path'] ? url('uploads/doctors/' . basename($r['image_path'])) : null,
            'work_start' => substr($r['work_start'], 0, 5),
            'work_end' => substr($r['work_end'], 0, 5),
            'time_display' => $timeDisplay,
            'status' => $eff,
            'status_mode' => $r['status_mode'],
        ];
    }
}

$slide = max(5, min(10, (int) $screen['slide_seconds']));
$refresh = max(10, min(30, (int) $screen['refresh_seconds']));
$style = (string) ($screen['display_style'] ?? 'hero_medical');
$stStyle = $pdo->prepare('SELECT style_key, style_type, config_json, css_text, metadata_json FROM display_styles WHERE style_key = ? LIMIT 1');
$stStyle->execute([$style]);
$styleRow = $stStyle->fetch();
if (!$styleRow) {
    $style = 'hero_medical';
    $stStyle->execute([$style]);
    $styleRow = $stStyle->fetch();
}
$styleCfg = $styleRow ? (json_decode((string) ($styleRow['config_json'] ?? '{}'), true) ?: default_style_config('hero')) : default_style_config('hero');
$styleCss = $styleRow ? (string) ($styleRow['css_text'] ?? '') : '';
$styleMeta = $styleRow ? (json_decode((string) ($styleRow['metadata_json'] ?? '{}'), true) ?: []) : [];
$styleType = $styleRow ? (string) ($styleRow['style_type'] ?? 'hero') : 'hero';

echo json_encode([
    'ok' => true,
    'welcome' => welcome_broadcast_for_api($pdo),
    'screen' => [
        'name' => $screen['name'],
        'slide_seconds' => $slide,
        'refresh_seconds' => $refresh,
        'display_mode' => $displayMode,
        'content_group_id' => (int) ($screen['content_group_id'] ?? 0),
        'display_style' => $style,
        'style' => [
            'key' => $style,
            'type' => $styleType,
            'config' => $styleCfg,
            'css' => $styleCss,
            'metadata' => $styleMeta,
        ],
    ],
    'doctors' => $out,
    'contents' => $contentOut,
], JSON_UNESCAPED_UNICODE);
