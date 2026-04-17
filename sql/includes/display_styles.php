<?php
declare(strict_types=1);

/**
 * @return array<string,mixed>
 */
function default_style_config(string $layout): array
{
    if ($layout === 'minimal') {
        return [
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
    }

    if ($layout === 'card') {
        return [
            'layout' => 'card',
            'image_behavior' => [
                'doctor' => 'doctor_hero',
                'department' => 'department_background',
            ],
            'overlay' => [
                'gradient' => true,
                'blur' => 18,
                'opacity' => 0.65,
            ],
            'typography' => [
                'name_size' => 'clamp(1.55rem, 4.8vw, 3rem)',
                'spec_size' => 'clamp(1rem, 2.6vw, 1.28rem)',
                'time_size' => 'clamp(1rem, 2.8vw, 1.25rem)',
                'spacing' => 1.0,
            ],
            'colors' => [
                'primary' => '#2fae66',
                'secondary' => '#2c7fb8',
                'surface' => '#ffffff',
                'text' => '#0f172a',
            ],
            'animations' => [
                'type' => 'fade',
                'duration_ms' => 900,
            ],
        ];
    }

    return [
        'layout' => 'hero',
        'image_behavior' => [
            'doctor' => 'doctor_hero',
            'department' => 'department_background',
        ],
        'overlay' => [
            'gradient' => true,
            'blur' => 32,
            'opacity' => 0.42,
        ],
        'typography' => [
            'name_size' => 'clamp(1.65rem, 6vw, 3rem)',
            'spec_size' => 'clamp(0.92rem, 2.8vw, 1.2rem)',
            'time_size' => 'clamp(1.02rem, 3.2vw, 1.35rem)',
            'spacing' => 1.0,
        ],
        'colors' => [
            'primary' => '#2fae66',
            'secondary' => '#2c7fb8',
            'surface' => '#0a1628',
            'text' => '#ffffff',
        ],
        'animations' => [
            'type' => 'fade',
            'duration_ms' => 900,
        ],
    ];
}

/**
 * @param array<string,mixed> $in
 * @return array{ok:bool,error:?string,data?:array<string,mixed>}
 */
function validate_style_payload(array $in): array
{
    $name = trim((string) ($in['name'] ?? ''));
    $type = trim((string) ($in['type'] ?? 'custom'));
    $configIn = $in['config'] ?? [];
    if (!is_array($configIn)) {
        $configIn = [];
    }
    $layout = trim((string) ($in['layout'] ?? ($configIn['layout'] ?? 'hero')));
    if ($name === '') {
        return ['ok' => false, 'error' => 'name_required'];
    }
    if (!in_array($layout, ['hero', 'card', 'split', 'minimal'], true)) {
        return ['ok' => false, 'error' => 'layout_invalid'];
    }

    $cfg = $configIn;
    $baseLayout = $layout === 'split' ? 'card' : $layout;
    $cfg = array_replace_recursive(default_style_config($baseLayout), $cfg);
    $cfg['layout'] = $layout;
    if (!isset($cfg['image_behavior']) || !is_array($cfg['image_behavior'])) {
        return ['ok' => false, 'error' => 'image_behavior_invalid'];
    }
    if (!isset($cfg['overlay']) || !is_array($cfg['overlay'])) {
        return ['ok' => false, 'error' => 'overlay_invalid'];
    }
    if (!isset($cfg['typography']) || !is_array($cfg['typography'])) {
        return ['ok' => false, 'error' => 'typography_invalid'];
    }
    if (!isset($cfg['colors']) || !is_array($cfg['colors'])) {
        return ['ok' => false, 'error' => 'colors_invalid'];
    }
    if (!isset($cfg['animations']) || !is_array($cfg['animations'])) {
        return ['ok' => false, 'error' => 'animations_invalid'];
    }

    $css = (string) ($in['css'] ?? '');
    $meta = $in['metadata'] ?? [];
    if (!is_array($meta)) {
        $meta = [];
    }
    $meta['exported_at'] = $meta['exported_at'] ?? date('c');
    $meta['version'] = $meta['version'] ?? 1;

    return [
        'ok' => true,
        'error' => null,
        'data' => [
            'name' => $name,
            'type' => $type === '' ? 'custom' : $type,
            'config' => $cfg,
            'css' => $css,
            'metadata' => $meta,
        ],
    ];
}

function normalize_style_key(string $name): string
{
    $base = strtolower(trim($name));
    $base = preg_replace('/[^a-z0-9]+/', '_', $base) ?: 'style';
    $base = trim($base, '_');
    if ($base === '') {
        $base = 'style';
    }

    return $base;
}

