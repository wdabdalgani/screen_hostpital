<?php
declare(strict_types=1);

/**
 * @return array<string,mixed>
 */
function hospital_settings(PDO $pdo): array
{
    $st = $pdo->query('SELECT * FROM hospital_settings WHERE id = 1 LIMIT 1');
    $row = $st->fetch();
    if ($row) {
        return $row;
    }

    return [
        'id' => 1,
        'name' => 'المستشفى',
        'logo_path' => null,
        'phone' => '',
        'email' => '',
        'address' => '',
        'website' => '',
        'social_facebook' => '',
        'social_instagram' => '',
        'social_x' => '',
        'social_youtube' => '',
        'default_display_shell' => 'classic',
    ];
}

/**
 * Primary/secondary public URLs for a screen token (classic slideshow vs قالب الحريري).
 *
 * @return array{primary: string, secondary: string, primary_kind: string, secondary_kind: string}
 */
function screen_display_urls(PDO $pdo, string $token): array
{
    $natural = url('display.php?token=' . $token);
    $forcedClassic = url('display.php?token=' . $token . '&force_classic=1');
    $forcedHariri = url('display.php?token=' . $token . '&force_hariri=1');
    $hs = hospital_settings($pdo);
    $shell = (string) ($hs['default_display_shell'] ?? 'classic');
    if ($shell === 'hariri') {
        return [
            'primary' => $forcedHariri,
            'secondary' => $forcedClassic,
            'primary_kind' => 'hariri',
            'secondary_kind' => 'classic',
        ];
    }

    return [
        'primary' => $natural,
        'secondary' => $forcedHariri,
        'primary_kind' => 'classic',
        'secondary_kind' => 'hariri',
    ];
}
