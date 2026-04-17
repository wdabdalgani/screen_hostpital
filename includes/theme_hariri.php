<?php
declare(strict_types=1);

/**
 * Loads /theme/index.html without modifying the file on disk; rewrites asset src to absolute URLs.
 *
 * @return array{body_inner: string, head_inline_css: string}
 */
function hariri_theme_embed_parts(): array
{
    $path = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'theme' . DIRECTORY_SEPARATOR . 'index.html';
    if (!is_readable($path)) {
        return ['body_inner' => '<p>تعذر تحميل القالب.</p>', 'head_inline_css' => ''];
    }
    $html = (string) file_get_contents($path);
    if (preg_match('~<body[^>]*>(.*)</body>~si', $html, $bm)) {
        $bodyInner = $bm[1];
    } else {
        $bodyInner = '';
    }
    $headCss = '';
    if (preg_match('~<style[^>]*>(.*?)</style>~si', $html, $sm)) {
        $headCss = $sm[1];
    }

    $bodyInner = hariri_resolve_theme_asset_paths($bodyInner);

    return ['body_inner' => $bodyInner, 'head_inline_css' => $headCss];
}

function hariri_resolve_theme_asset_paths(string $html): string
{
    $assets = ['logo.png', 'doctor.png', 'part.png', 'icon_time.png', 'line_one.png'];
    foreach ($assets as $f) {
        $html = str_replace('src="' . $f . '"', 'src="' . esc(url('theme/' . $f)) . '"', $html);
    }

    return $html;
}
