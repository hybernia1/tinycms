<?php

if (!defined('BASE_DIR')) {
    exit;
}

register_theme([
    'name' => 'Default',
    'version' => '1.0.0',
    'author' => 'TinyCMS',
    'description' => 'Clean default TinyCMS theme.',
    'features' => [
        'responsive',
        'widgets',
        'menu',
        'search',
        'logo',
        'favicon',
        'custom_css',
        'layout_width',
        'colors',
    ],
]);

register_theme_section('branding', 'Branding', ['brand_display', 'logo', 'favicon']);
register_theme_section('layout', 'Layout and features', [
    'layout_width',
    'enable_search',
    'enable_widgets',
    'footer_text',
]);
register_theme_section('colors', 'Colors', [
    'color_bg',
    'color_surface',
    'color_surface_alt',
    'color_text',
    'color_muted',
    'color_border',
    'color_accent',
    'color_accent_strong',
    'color_accent_soft',
]);
register_theme_section('advanced', 'Custom CSS', ['custom_css']);

register_theme_setting('brand_display', [
    'type' => 'select',
    'label' => 'Branding',
    'default' => 'both',
    'options' => [
        'both' => 'Logo and title',
        'logo' => 'Logo only',
        'title' => 'Title only',
        'none' => 'No branding',
    ],
]);
register_theme_setting('logo', ['type' => 'file', 'label' => 'Logo']);
register_theme_setting('favicon', ['type' => 'file', 'label' => 'Favicon']);
register_theme_setting('enable_widgets', ['type' => 'checkbox', 'label' => 'Enable widgets', 'default' => '1']);
register_theme_setting('enable_search', ['type' => 'checkbox', 'label' => 'Enable search bar', 'default' => '1']);
register_theme_setting('layout_width', [
    'type' => 'select',
    'label' => 'Layout width',
    'default' => 'default',
    'options' => [
        'narrow' => 'Narrow',
        'default' => 'Default',
        'wide' => 'Wide',
        'full' => 'Full',
    ],
]);
register_theme_setting('custom_css', ['type' => 'textarea', 'label' => 'Custom CSS', 'max' => 20000]);

foreach ([
    'color_bg' => ['Page background', '#f7f7f2'],
    'color_surface' => ['Surface', '#ffffff'],
    'color_surface_alt' => ['Muted surface', '#efefea'],
    'color_text' => ['Text', '#1f2328'],
    'color_muted' => ['Muted text', '#687076'],
    'color_border' => ['Border', '#c8ccc8'],
    'color_accent' => ['Accent', '#2457c5'],
    'color_accent_strong' => ['Accent strong', '#163f96'],
    'color_accent_soft' => ['Accent soft', '#edf2ff'],
] as $key => [$label, $default]) {
    register_theme_setting($key, ['type' => 'color', 'label' => $label, 'default' => $default]);
}

register_theme_setting('footer_text', ['type' => 'textarea', 'label' => 'Footer text']);

register_widget_area('before_content', 'Before content');
register_widget_area('left', 'Left sidebar');
register_widget_area('right', 'Right sidebar');
register_widget_area('after_content', 'After content');
