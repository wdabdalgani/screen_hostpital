<?php
declare(strict_types=1);

require_once __DIR__ . '/hospital.php';

/**
 * @return array<string, mixed>
 */
function welcome_broadcast_row(PDO $pdo): array
{
    try {
        $st = $pdo->query('SELECT * FROM welcome_broadcast WHERE id = 1 LIMIT 1');
        $row = $st ? $st->fetch(PDO::FETCH_ASSOC) : false;
        if (is_array($row)) {
            return $row;
        }
    } catch (Throwable $e) {
        // table missing until migration
    }

    return welcome_broadcast_defaults();
}

/**
 * @return array<string, mixed>
 */
function welcome_broadcast_defaults(): array
{
    return [
        'id' => 1,
        'is_enabled' => 0,
        'show_logo' => 1,
        'title' => '',
        'subtitle' => '',
        'image_path' => null,
    ];
}

/**
 * Build API payload for display clients.
 *
 * @return array<string, mixed>
 */
function welcome_broadcast_for_api(PDO $pdo): array
{
    $row = welcome_broadcast_row($pdo);
    $enabled = (int) ($row['is_enabled'] ?? 0) === 1;
    $hs = hospital_settings($pdo);
    $showLogo = (int) ($row['show_logo'] ?? 1) === 1;
    $logoPath = $hs['logo_path'] ?? null;
    $logoUrl = ($showLogo && !empty($logoPath))
        ? url('uploads/hospital/' . basename((string) $logoPath))
        : null;
    $imgPath = $row['image_path'] ?? null;
    $imageUrl = !empty($imgPath)
        ? url('uploads/welcome/' . basename((string) $imgPath))
        : null;

    return [
        'active' => $enabled,
        'title' => (string) ($row['title'] ?? ''),
        'subtitle' => (string) ($row['subtitle'] ?? ''),
        'image_url' => $imageUrl,
        'logo_url' => $logoUrl,
    ];
}
