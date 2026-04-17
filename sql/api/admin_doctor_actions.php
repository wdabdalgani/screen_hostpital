<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'method'], JSON_UNESCAPED_UNICODE);
    exit;
}

$pdo = db();
$cfg = app_config();
$uploadDir = $cfg['upload_dir'];

$raw = (string) file_get_contents('php://input');
$input = json_decode($raw, true);
if (!is_array($input)) {
    $input = [];
}
$action = (string) ($input['action'] ?? $_POST['action'] ?? '');
$id = (int) ($input['doctor_id'] ?? $_POST['doctor_id'] ?? 0);

if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'invalid_id'], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($action === 'toggle_status') {
    $st = $pdo->prepare('SELECT * FROM doctors WHERE id = ? LIMIT 1');
    $st->execute([$id]);
    $row = $st->fetch();
    if (!$row) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'error' => 'not_found'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $schedMap = doctor_weekly_schedules_map($pdo, [$id]);
    $row['weekly_schedule'] = $schedMap[$id] ?? [];
    $eff = doctor_effective_status($row);
    $newManual = $eff === 'available' ? 'unavailable' : 'available';
    $pdo->prepare('UPDATE doctors SET status_mode = ?, manual_status = ? WHERE id = ?')->execute(['manual', $newManual, $id]);

    $st->execute([$id]);
    $row = $st->fetch();
    if (!$row) {
        echo json_encode(['ok' => false, 'error' => 'not_found'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $schedMap = doctor_weekly_schedules_map($pdo, [$id]);
    $row['weekly_schedule'] = $schedMap[$id] ?? [];
    $effective = doctor_effective_status($row);
    echo json_encode([
        'ok' => true,
        'effective_status' => $effective,
        'status_mode' => $row['status_mode'],
        'manual_status' => $row['manual_status'],
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($action === 'delete') {
    $st = $pdo->prepare('SELECT id, image_path FROM doctors WHERE id = ? LIMIT 1');
    $st->execute([$id]);
    $row = $st->fetch();
    if (!$row) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'error' => 'not_found'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $pdo->prepare('DELETE FROM doctors WHERE id = ?')->execute([$id]);
    if (!empty($row['image_path'])) {
        $p = $uploadDir . DIRECTORY_SEPARATOR . basename((string) $row['image_path']);
        if (is_file($p)) {
            @unlink($p);
        }
    }
    echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
    exit;
}

http_response_code(400);
echo json_encode(['ok' => false, 'error' => 'unknown_action'], JSON_UNESCAPED_UNICODE);
