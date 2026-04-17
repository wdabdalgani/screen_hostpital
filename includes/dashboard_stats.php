<?php
declare(strict_types=1);

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/stats.php';
require_once __DIR__ . '/hospital.php';

/**
 * Ensure time-series table exists and append a snapshot every 10 minutes.
 */
function dashboard_record_snapshot(PDO $pdo, int $available, int $unavailable, float $availabilityPct): void
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

    $last = $pdo->query('SELECT captured_at FROM dashboard_stat_snapshots ORDER BY id DESC LIMIT 1')->fetchColumn();
    if (is_string($last) && $last !== '') {
        $lastTs = strtotime($last);
        if ($lastTs !== false && (time() - $lastTs) < 600) {
            return;
        }
    }

    $st = $pdo->prepare(
        'INSERT INTO dashboard_stat_snapshots (available_count, unavailable_count, availability_pct) VALUES (?, ?, ?)'
    );
    $st->execute([$available, $unavailable, $availabilityPct]);
}

/**
 * Aggregates dashboard KPIs, alerts, chart datasets, and system health.
 *
 * @param array<string,mixed> $cfg app_config()
 * @return array<string,mixed>
 */
function compute_dashboard_bundle(PDO $pdo, array $cfg): array
{
    $stats = compute_global_stats($pdo);
    $screenCount = (int) $pdo->query('SELECT COUNT(*) FROM screens')->fetchColumn();
    $doctorCount = (int) $pdo->query('SELECT COUNT(*) FROM doctors')->fetchColumn();
    $departmentCount = (int) $pdo->query('SELECT COUNT(*) FROM departments')->fetchColumn();
    $contentGroupCount = (int) $pdo->query('SELECT COUNT(*) FROM content_groups')->fetchColumn();

    $stActiveContent = $pdo->query('SELECT COUNT(*) FROM display_contents WHERE is_active = 1');
    $contentActiveItems = (int) $stActiveContent->fetchColumn();
    $contentLinkedDoctor = (int) $pdo->query(
        'SELECT COUNT(*) FROM display_contents WHERE is_active = 1 AND doctor_id IS NOT NULL'
    )->fetchColumn();

    $doctorsNoImage = (int) $pdo->query(
        "SELECT COUNT(*) FROM doctors WHERE image_path IS NULL OR TRIM(image_path) = ''"
    )->fetchColumn();
    $doctorsNoDept = (int) $pdo->query(
        'SELECT COUNT(*) FROM doctors WHERE department_id IS NULL'
    )->fetchColumn();

    $total = $stats['available'] + $stats['unavailable'];
    $availabilityPct = $total > 0 ? round($stats['available'] * 100 / $total, 1) : 0.0;
    dashboard_record_snapshot($pdo, (int) $stats['available'], (int) $stats['unavailable'], (float) $availabilityPct);

    $styleNames = [];
    foreach ($pdo->query('SELECT style_key, name FROM display_styles') as $sr) {
        $styleNames[(string) $sr['style_key']] = (string) $sr['name'];
    }

    $screensEmptyDoctors = $pdo->query(
        "SELECT s.id, s.name FROM screens s
         WHERE s.display_mode = 'doctors'
         AND NOT EXISTS (SELECT 1 FROM doctors d WHERE d.screen_id = s.id)
         ORDER BY s.id ASC"
    )->fetchAll(PDO::FETCH_ASSOC);

    $screensContentNoGroup = $pdo->query(
        "SELECT s.id, s.name FROM screens s
         WHERE s.display_mode = 'content'
         AND (s.content_group_id IS NULL OR s.content_group_id = 0)
         ORDER BY s.id ASC"
    )->fetchAll(PDO::FETCH_ASSOC);

    $screensContentEmpty = $pdo->query(
        "SELECT s.id, s.name
         FROM screens s
         WHERE s.display_mode = 'content'
         AND s.content_group_id IS NOT NULL AND s.content_group_id > 0
         AND NOT EXISTS (
           SELECT 1 FROM display_contents c
           WHERE c.group_id = s.content_group_id AND c.is_active = 1
         )
         ORDER BY s.id ASC"
    )->fetchAll(PDO::FETCH_ASSOC);

    $modeDoctors = 0;
    $modeContent = 0;
    foreach ($pdo->query(
        "SELECT display_mode, COUNT(*) AS c FROM screens GROUP BY display_mode"
    ) as $mr) {
        if ($mr['display_mode'] === 'doctors') {
            $modeDoctors = (int) $mr['c'];
        } elseif ($mr['display_mode'] === 'content') {
            $modeContent = (int) $mr['c'];
        }
    }

    $styleUsage = [];
    foreach ($pdo->query(
        'SELECT display_style, COUNT(*) AS c FROM screens GROUP BY display_style ORDER BY c DESC'
    ) as $row) {
        $key = (string) $row['display_style'];
        $label = $styleNames[$key] ?? $key;
        $styleUsage[] = ['key' => $key, 'label' => $label, 'count' => (int) $row['c']];
    }

    $contentTypes = ['image' => 0, 'video' => 0, 'gif' => 0];
    foreach ($pdo->query(
        'SELECT content_type, COUNT(*) AS c FROM display_contents WHERE is_active = 1 GROUP BY content_type'
    ) as $row) {
        $t = (string) $row['content_type'];
        if (isset($contentTypes[$t])) {
            $contentTypes[$t] = (int) $row['c'];
        }
    }

    $modeAuto = 0;
    $modeManual = 0;
    foreach ($pdo->query(
        'SELECT status_mode, COUNT(*) AS c FROM doctors GROUP BY status_mode'
    ) as $sm) {
        if ($sm['status_mode'] === 'auto') {
            $modeAuto = (int) $sm['c'];
        } elseif ($sm['status_mode'] === 'manual') {
            $modeManual = (int) $sm['c'];
        }
    }

    $perScreen = $pdo->query(
        'SELECT s.id, COUNT(d.id) AS cnt FROM screens s
         LEFT JOIN doctors d ON d.screen_id = s.id
         GROUP BY s.id'
    )->fetchAll(PDO::FETCH_ASSOC);
    $buckets = ['0' => 0, '1' => 0, '2' => 0, '3' => 0, '4+' => 0];
    foreach ($perScreen as $ps) {
        $n = (int) $ps['cnt'];
        if ($n >= 4) {
            $buckets['4+']++;
        } else {
            $buckets[(string) $n]++;
        }
    }

    $topGroups = [];
    $gq = $pdo->query(
        'SELECT g.name AS group_name, COUNT(c.id) AS cnt
         FROM content_groups g
         LEFT JOIN display_contents c ON c.group_id = g.id AND c.is_active = 1
         GROUP BY g.id, g.name
         ORDER BY cnt DESC, g.name ASC
         LIMIT 8'
    );
    foreach ($gq->fetchAll(PDO::FETCH_ASSOC) as $gr) {
        $topGroups[] = [
            'name' => (string) $gr['group_name'],
            'count' => (int) $gr['cnt'],
        ];
    }

    $trendLabels = [];
    $trendAvailablePct = [];
    $trendAvailableCount = [];
    $trendUnavailableCount = [];
    $trendRows = $pdo->query(
        'SELECT captured_at, available_count, unavailable_count, availability_pct
         FROM dashboard_stat_snapshots
         WHERE captured_at >= DATE_SUB(NOW(), INTERVAL 48 HOUR)
         ORDER BY captured_at ASC'
    )->fetchAll(PDO::FETCH_ASSOC);
    foreach ($trendRows as $tr) {
        $trendLabels[] = date('m/d H:i', strtotime((string) $tr['captured_at']));
        $trendAvailablePct[] = (float) $tr['availability_pct'];
        $trendAvailableCount[] = (int) $tr['available_count'];
        $trendUnavailableCount[] = (int) $tr['unavailable_count'];
    }

    $heatCells = [];
    $heatRows = $pdo->query(
        'SELECT DAYOFWEEK(captured_at) AS dow, HOUR(captured_at) AS hr, AVG(availability_pct) AS avg_pct
         FROM dashboard_stat_snapshots
         WHERE captured_at >= DATE_SUB(NOW(), INTERVAL 21 DAY)
         GROUP BY DAYOFWEEK(captured_at), HOUR(captured_at)'
    )->fetchAll(PDO::FETCH_ASSOC);
    foreach ($heatRows as $hr) {
        $heatCells[] = [
            'dow' => (int) $hr['dow'],
            'hour' => (int) $hr['hr'],
            'pct' => round((float) $hr['avg_pct'], 1),
        ];
    }

    $withDeptCount = (int) $pdo->query('SELECT COUNT(*) FROM doctors WHERE department_id IS NOT NULL')->fetchColumn();
    $withImageCount = (int) $pdo->query(
        "SELECT COUNT(*) FROM doctors WHERE image_path IS NOT NULL AND TRIM(image_path) <> ''"
    )->fetchColumn();
    $withProfileCount = (int) $pdo->query(
        "SELECT COUNT(*) FROM doctors WHERE department_id IS NOT NULL AND image_path IS NOT NULL AND TRIM(image_path) <> ''"
    )->fetchColumn();
    $funnel = [
        'labels' => ['إجمالي الأطباء', 'بقسم', 'بصورة', 'بروفايل مكتمل', 'متاحون الآن (فعلي)'],
        'values' => [
            $doctorCount,
            $withDeptCount,
            $withImageCount,
            $withProfileCount,
            (int) $stats['available'],
        ],
    ];

    $depAvailUnavail = [];
    $depRowsForPareto = $pdo->query(
        'SELECT d.id, dep.name AS department_name, d.work_start, d.work_end, d.status_mode, d.manual_status
         FROM doctors d
         LEFT JOIN departments dep ON dep.id = d.department_id'
    )->fetchAll(PDO::FETCH_ASSOC);
    $depRowsForPareto = doctors_attach_weekly_schedule($pdo, $depRowsForPareto);
    foreach ($depRowsForPareto as $dr) {
        $depName = trim((string) ($dr['department_name'] ?? ''));
        if ($depName === '') {
            $depName = 'بدون قسم';
        }
        if (!isset($depAvailUnavail[$depName])) {
            $depAvailUnavail[$depName] = ['total' => 0, 'unavailable' => 0];
        }
        $depAvailUnavail[$depName]['total']++;
        if (doctor_effective_status($dr) !== 'available') {
            $depAvailUnavail[$depName]['unavailable']++;
        }
    }
    uasort($depAvailUnavail, static function (array $a, array $b): int {
        return $b['unavailable'] <=> $a['unavailable'];
    });
    $paretoLabels = [];
    $paretoUnavailable = [];
    $paretoCumPct = [];
    $unTotal = 0;
    foreach ($depAvailUnavail as $dv) {
        $unTotal += (int) $dv['unavailable'];
    }
    $run = 0;
    $pi = 0;
    foreach ($depAvailUnavail as $name => $dv) {
        $paretoLabels[] = $name;
        $cur = (int) $dv['unavailable'];
        $paretoUnavailable[] = $cur;
        $run += $cur;
        $paretoCumPct[] = $unTotal > 0 ? round($run * 100 / $unTotal, 1) : 0.0;
        if (++$pi >= 10) {
            break;
        }
    }

    $screensReady = 0;
    $screensRows = $pdo->query(
        'SELECT s.id, s.display_mode, s.content_group_id
         FROM screens s
         ORDER BY s.id ASC'
    )->fetchAll(PDO::FETCH_ASSOC);
    foreach ($screensRows as $sr) {
        $sid = (int) $sr['id'];
        $mode = (string) $sr['display_mode'];
        if ($mode === 'doctors') {
            $c = $pdo->prepare('SELECT COUNT(*) FROM doctors WHERE screen_id = ?');
            $c->execute([$sid]);
            if ((int) $c->fetchColumn() > 0) {
                $screensReady++;
            }
            continue;
        }
        if ($mode === 'content') {
            $gid = (int) ($sr['content_group_id'] ?? 0);
            if ($gid > 0) {
                $c = $pdo->prepare('SELECT COUNT(*) FROM display_contents WHERE group_id = ? AND is_active = 1');
                $c->execute([$gid]);
                if ((int) $c->fetchColumn() > 0) {
                    $screensReady++;
                }
            }
        }
    }
    $profileCompletionPct = $doctorCount > 0 ? round($withProfileCount * 100 / $doctorCount, 1) : 0.0;
    $contentLinkedPct = $contentActiveItems > 0 ? round($contentLinkedDoctor * 100 / $contentActiveItems, 1) : 0.0;
    $screenReadyPct = $screenCount > 0 ? round($screensReady * 100 / $screenCount, 1) : 0.0;
    $autoModePct = $doctorCount > 0 ? round($modeAuto * 100 / $doctorCount, 1) : 0.0;
    $sla = [
        'labels' => ['توفر الأطباء', 'اكتمال البروفايل', 'ربط المحتوى بطبيب', 'جاهزية الشاشات', 'تغطية الوضع التلقائي'],
        'actual' => [$availabilityPct, $profileCompletionPct, $contentLinkedPct, $screenReadyPct, $autoModePct],
        'target' => [85, 90, 60, 95, 70],
    ];

    $byDept = $stats['by_department'];
    $deptChartLabels = [];
    $deptChartValues = [];
    $i = 0;
    foreach ($byDept as $name => $n) {
        $deptChartLabels[] = $name;
        $deptChartValues[] = (int) $n;
        if (++$i >= 10) {
            break;
        }
    }

    $schemaVersion = (int) $pdo->query('SELECT COALESCE(MAX(version), 0) FROM schema_migrations')->fetchColumn();

    $uploads = [
        'doctors' => is_writable($cfg['upload_dir']),
        'departments' => is_writable($cfg['upload_departments_dir']),
        'content' => is_writable($cfg['upload_content_dir']),
        'hospital' => is_writable($cfg['upload_hospital_dir']),
        'admin' => is_writable($cfg['upload_admin_dir']),
    ];

    $hospitalRow = hospital_settings($pdo);
    $hospitalUpdated = isset($hospitalRow['updated_at']) ? (string) $hospitalRow['updated_at'] : '';

    $alerts = [];
    foreach ($screensEmptyDoctors as $s) {
        $alerts[] = [
            'severity' => 'warning',
            'text' => 'شاشة «' . $s['name'] . '» في وضع الأطباء ولا يوجد عليها أطباء.',
            'href' => url('admin/screens.php'),
        ];
    }
    foreach ($screensContentNoGroup as $s) {
        $alerts[] = [
            'severity' => 'danger',
            'text' => 'شاشة «' . $s['name'] . '» في وضع المحتوى دون مجموعة محتوى.',
            'href' => url('admin/screens.php'),
        ];
    }
    foreach ($screensContentEmpty as $s) {
        $alerts[] = [
            'severity' => 'warning',
            'text' => 'شاشة «' . $s['name'] . '» مرتبطة بمجموعة لا تحتوي عناصر نشطة.',
            'href' => url('admin/display_content.php'),
        ];
    }
    if ($doctorsNoImage > 0) {
        $alerts[] = [
            'severity' => 'info',
            'text' => (string) $doctorsNoImage . ' طبيباً بلا صورة عرض.',
            'href' => url('admin/doctors.php'),
        ];
    }
    if ($doctorsNoDept > 0) {
        $alerts[] = [
            'severity' => 'info',
            'text' => (string) $doctorsNoDept . ' طبيباً بلا قسم.',
            'href' => url('admin/doctors.php'),
        ];
    }
    foreach ($uploads as $label => $ok) {
        if (!$ok) {
            $alerts[] = [
                'severity' => 'danger',
                'text' => 'مجلد الرفع غير قابل للكتابة: ' . $label,
                'href' => null,
            ];
        }
    }

    $tips = [];
    if ($screenCount === 0) {
        $tips[] = 'ابدأ بإضافة شاشة عرض من قسم الشاشات وربطها بالأطباء أو المحتوى.';
    } elseif ($doctorCount === 0 && $modeDoctors > 0) {
        $tips[] = 'لا يوجد أطباء بعد — أضف أطباءً واربطهم بالشاشات.';
    } elseif ($contentGroupCount === 0 && $modeContent > 0) {
        $tips[] = 'أنشئ مجموعة محتوى وأضف عناصراً لشاشات وضع المحتوى.';
    }
    if ($availabilityPct < 40 && $doctorCount > 3) {
        $tips[] = 'نسبة التوفر منخفضة — راجع أوقات الدوام أو الحالة اليدوية للأطباء.';
    }

    $charts = [
        'availability' => [
            'labels' => ['متاح', 'غير متاح'],
            'values' => [$stats['available'], $stats['unavailable']],
        ],
        'departments' => [
            'labels' => $deptChartLabels,
            'values' => $deptChartValues,
        ],
        'screenModes' => [
            'labels' => ['أطباء', 'محتوى ديناميكي'],
            'values' => [$modeDoctors, $modeContent],
        ],
        'styles' => $styleUsage,
        'contentTypes' => [
            'labels' => ['صورة', 'فيديو', 'GIF'],
            'values' => [$contentTypes['image'], $contentTypes['video'], $contentTypes['gif']],
        ],
        'doctorBuckets' => [
            'labels' => ['0', '1', '2', '3', '4+'],
            'values' => [
                $buckets['0'],
                $buckets['1'],
                $buckets['2'],
                $buckets['3'],
                $buckets['4+'],
            ],
        ],
        'doctorStatusModes' => [
            'labels' => ['تلقائي (الدوام)', 'يدوي'],
            'values' => [$modeAuto, $modeManual],
        ],
        'contentGroups' => $topGroups,
        'advancedTrend' => [
            'labels' => $trendLabels,
            'availablePct' => $trendAvailablePct,
            'availableCount' => $trendAvailableCount,
            'unavailableCount' => $trendUnavailableCount,
        ],
        'advancedHeatmap' => [
            'days' => ['الأحد', 'الإثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت'],
            'cells' => $heatCells,
        ],
        'advancedFunnel' => $funnel,
        'advancedPareto' => [
            'labels' => $paretoLabels,
            'unavailable' => $paretoUnavailable,
            'cumulativePct' => $paretoCumPct,
        ],
        'advancedSla' => $sla,
    ];

    return [
        'stats' => $stats,
        'screen_count' => $screenCount,
        'doctor_count' => $doctorCount,
        'department_count' => $departmentCount,
        'content_group_count' => $contentGroupCount,
        'content_active_items' => $contentActiveItems,
        'content_linked_doctor' => $contentLinkedDoctor,
        'doctors_no_image' => $doctorsNoImage,
        'doctors_no_dept' => $doctorsNoDept,
        'availability_pct' => $availabilityPct,
        'top_departments' => top_departments_by_available($pdo, 8),
        'alerts' => $alerts,
        'tips' => $tips,
        'schema_version' => $schemaVersion,
        'uploads_health' => $uploads,
        'hospital_updated' => $hospitalUpdated,
        'charts' => $charts,
    ];
}
