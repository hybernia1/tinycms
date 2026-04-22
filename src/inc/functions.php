<?php
declare(strict_types=1);

use App\Service\Support\Escape;
use App\Service\Support\I18n;
use App\Service\Support\RequestContext;

function t(string $key, ?string $fallback = null): string
{
    return I18n::t($key, $fallback);
}

function icon(string $name, string $classes = 'icon'): string
{
    $icon = trim($name);
    if (preg_match('/^[a-z0-9_-]+$/i', $icon) !== 1) {
        return '';
    }

    $class = trim($classes);
    $href = esc_url(icon_sprite() . '#icon-' . $icon);
    return '<svg class="' . esc_attr($class !== '' ? $class : 'icon') . '" aria-hidden="true" focusable="false"><use href="' . $href . '"></use></svg>';
}

function icon_sprite(): string
{
    return RequestContext::path(ASSETS_DIR . 'svg/icons.svg');
}

function esc_html(mixed $value): string
{
    return Escape::html($value);
}

function esc_attr(mixed $value): string
{
    return Escape::attr($value);
}

function esc_url(mixed $value): string
{
    return Escape::url($value);
}

function esc_js(mixed $value): string
{
    return Escape::js($value);
}

function esc_json(mixed $value): string
{
    return Escape::json($value);
}

function esc_content(mixed $value): string
{
    return Escape::content($value);
}

function esc_xml(mixed $value): string
{
    return Escape::xml($value);
}
