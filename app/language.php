<?php
function languageSwitch(): string
{
    $query = $_GET;
    unset($query['language']);
    $links = [];
    foreach (['de'=>'Deutsch','en'=>'English'] as $code=>$label) {
        $links[] = '<a class="nav-link" lang="' . $code . '" href="' . h(appUrl() . '?' . http_build_query($query + ['language'=>$code])) . '">' . $label . '</a>';
    }
    return '<span class="languages">' . implode(' ', $links) . '</span>';
}
function t(string $text): string
{
    static $dictionary;
    $dictionary ??= require __DIR__ . '/translations.php';
    return ($GLOBALS['language'] ?? 'de') === 'en' ? ($dictionary[$text] ?? $text) : $text;
}
