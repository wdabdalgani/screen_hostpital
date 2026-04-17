<?php
declare(strict_types=1);

/**
 * SVG icons for department cards (stroke uses currentColor).
 *
 * @return non-empty-string
 */
function department_icon_svg(string $key): string
{
    $key = preg_replace('/[^a-z0-9_]/', '', strtolower($key)) ?: 'layers';
    $icons = [
        'layers' => '<path d="M12.83 2.18a2 2 0 0 0-1.66 0L2.6 6.08a1 1 0 0 0 0 1.83l8.58 3.91a2 2 0 0 0 1.66 0l8.58-3.9a1 1 0 0 0 0-1.83z"/><path d="M2 12a1 1 0 0 0 .74.97l8.57 3.91a2 2 0 0 0 1.66 0l8.57-3.91A1 1 0 0 0 22 12"/><path d="M2 17a1 1 0 0 0 .74.97l8.57 3.91a2 2 0 0 0 1.66 0l8.57-3.91A1 1 0 0 0 22 17"/>',
        'heart' => '<path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 3.78 3.4 6.86 8.55 11.54L12 21.35l1.45-1.32C18.6 15.36 22 12.28 22 8.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2Z"/>',
        'activity' => '<path d="M22 12h-4l-3 9L9 3l-3 9H2"/>',
        'bone' => '<path d="M17 10c.7-.7 1.2-1.7 1.5-2.5a6.7 6.7 0 0 0 .3-2.5 4 4 0 0 0-4-4 2 2 0 0 0-1.8.9 2 2 0 0 1-3.6 0A2 2 0 0 0 7 1a4 4 0 0 0-4 4c0 .7.1 1.7.4 2.5.3.8.8 1.8 1.5 2.5"/><path d="M7 14c-.7.7-1.2 1.7-1.5 2.5a6.7 6.7 0 0 0-.3 2.5 4 4 0 0 0 4 4 2 2 0 0 0 1.8-.9 2 2 0 0 1 3.6 0 2 2 0 0 0 1.8.9 4 4 0 0 0 4-4c0-.7-.1-1.7-.4-2.5-.3-.8-.8-1.8-1.5-2.5"/>',
        'brain' => '<circle cx="12" cy="12" r="3"/><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4"/>',
        'baby' => '<path d="M10 16c0-1.5 2-3 4-3"/><path d="M10 8h.01"/><path d="M14 8h.01"/><path d="M18.5 11c.5 1.5 1 3.5 1 5a7 7 0 1 1-14 0c0-1.5.5-3.5 1-5"/><path d="M7.5 11c.5-1.5 1.5-3 2.5-3s2 1.5 2.5 3"/>',
        'tooth' => '<path d="M7 10c0-2 1-4 5-4s5 2 5 4c0 2-1 4-2 5s-2 2-3 5c-1-3-2-4-3-5s-2-3-2-5z"/>',
        'eye' => '<path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/>',
        'stethoscope' => '<path d="M4.8 2.3A.25.25 0 0 1 5 2h14a.25.25 0 0 1 .2.3l-.74 2.45a.17.17 0 0 0 0 .11c.1.39.18.8.18 1.22a4.5 4.5 0 1 1-9 0c0-.41.07-.82.18-1.22a.17.17 0 0 0 0-.11Z"/><path d="M8 15v1a4 4 0 0 0 4 4"/><path d="M12 20v-4"/>',
        'hospital' => '<path d="M12 6v4"/><path d="M14 14h-4"/><path d="M14 18h-4"/><path d="M17 21v-8a1 1 0 0 0-1-1h-1"/><path d="M7 21v-8a1 1 0 0 1 1-1h1"/><path d="M17 21h2v-4h-2"/><path d="M5 21H3v-4h2"/><path d="M5 21V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16"/>',
        'pill' => '<path d="m10.5 20.5 10-10a4.95 4.95 0 1 0-7-7l-10 10a4.95 4.95 0 1 0 7 7Z"/><path d="m8.5 8.5 7 7"/>',
    ];
    $path = $icons[$key] ?? $icons['layers'];

    return '<svg class="dept-ic-svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.65" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $path . '</svg>';
}

/** @return array<int,array{value:string,label:string}> */
function department_icon_options(): array
{
    return [
        ['value' => 'layers', 'label' => 'عام'],
        ['value' => 'heart', 'label' => 'قلب'],
        ['value' => 'activity', 'label' => 'نشاط / عيادات'],
        ['value' => 'bone', 'label' => 'عظام'],
        ['value' => 'brain', 'label' => 'أعصاب'],
        ['value' => 'baby', 'label' => 'أطفال'],
        ['value' => 'tooth', 'label' => 'أسنان'],
        ['value' => 'eye', 'label' => 'عيون'],
        ['value' => 'stethoscope', 'label' => 'باطنية'],
        ['value' => 'hospital', 'label' => 'مستشفى'],
        ['value' => 'pill', 'label' => 'صيدلة'],
    ];
}
