<?php
declare(strict_types=1);

require_once __DIR__ . '/functions.php';

/**
 * @return array{available:int,unavailable:int,by_department:array<string,int>}
 */
function compute_global_stats(PDO $pdo): array
{
    $sql = 'SELECT doctors.id, doctors.work_start, doctors.work_end, doctors.status_mode, doctors.manual_status,
            dep.name AS department_name
            FROM doctors
            LEFT JOIN departments dep ON dep.id = doctors.department_id';
    $st = $pdo->query($sql);
    $rows = doctors_attach_weekly_schedule($pdo, $st->fetchAll());
    $av = 0;
    $un = 0;
    $by = [];
    foreach ($rows as $r) {
        $eff = doctor_effective_status($r);
        if ($eff === 'available') {
            $av++;
        } else {
            $un++;
        }
        $dep = trim((string) ($r['department_name'] ?? ''));
        if ($dep === '') {
            $dep = 'بدون قسم';
        }
        if (!isset($by[$dep])) {
            $by[$dep] = 0;
        }
        $by[$dep]++;
    }
    arsort($by);

    return ['available' => $av, 'unavailable' => $un, 'by_department' => $by];
}

/**
 * Departments with most available doctors first (only departments that have doctors).
 *
 * @return list<array{name:string,icon:string,available:int}>
 */
function top_departments_by_available(PDO $pdo, int $limit = 8): array
{
    $sql = 'SELECT doctors.id, doctors.work_start, doctors.work_end, doctors.status_mode, doctors.manual_status,
            doctors.department_id, dep.name AS dep_name, dep.icon AS dep_icon
            FROM doctors
            INNER JOIN departments dep ON dep.id = doctors.department_id';
    $st = $pdo->query($sql);
    $rows = doctors_attach_weekly_schedule($pdo, $st->fetchAll());
    /** @var array<int,array{name:string,icon:string,available:int}> $agg */
    $agg = [];
    foreach ($rows as $r) {
        $did = (int) ($r['department_id'] ?? 0);
        if ($did <= 0) {
            continue;
        }
        if (!isset($agg[$did])) {
            $agg[$did] = [
                'name' => (string) $r['dep_name'],
                'icon' => (string) ($r['dep_icon'] ?: 'layers'),
                'available' => 0,
            ];
        }
        if (doctor_effective_status($r) === 'available') {
            $agg[$did]['available']++;
        }
    }
    usort($agg, static function (array $a, array $b): int {
        return $b['available'] <=> $a['available'];
    });

    return array_slice($agg, 0, max(1, $limit));
}
