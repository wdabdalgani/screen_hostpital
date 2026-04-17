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
    ];
}
