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

register_theme_section('branding', t('theme.customizer_sections.branding'), ['brand_display', 'logo', 'favicon']);
register_theme_section('layout', t('theme.customizer_sections.layout'), [
    'layout_width',
    'enable_search',
    'enable_widgets',
    'footer_text',
]);
register_theme_section('homepage', t('theme.customizer_sections.homepage'), ['front_home_content']);
register_theme_section('single_content', t('theme.customizer_sections.single_content'), [
    'single_show_thumbnail',
    'single_meta_date',
    'single_meta_author',
    'single_meta_comments',
    'single_show_terms',
]);
register_theme_section('archive', t('theme.customizer_sections.archive'), [
    'archive_show_thumbnail',
    'archive_meta_date',
    'archive_meta_author',
    'archive_meta_comments',
]);
register_theme_section('colors', t('theme.customizer_sections.colors'), [
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
register_theme_section('advanced', t('theme.customizer_sections.advanced'), ['custom_css']);

register_theme_setting('brand_display', [
    'type' => 'select',
    'label' => t('theme.customizer_fields.branding'),
    'default' => 'both',
    'options' => [
        'both' => t('theme.customizer_options.brand_both'),
        'logo' => t('theme.customizer_options.brand_logo'),
        'title' => t('theme.customizer_options.brand_title'),
        'none' => t('theme.customizer_options.brand_none'),
    ],
]);
register_theme_setting('logo', ['type' => 'file', 'label' => t('theme.customizer_fields.logo')]);
register_theme_setting('favicon', ['type' => 'file', 'label' => t('theme.customizer_fields.favicon')]);
register_theme_setting('enable_widgets', ['type' => 'checkbox', 'label' => t('theme.customizer_fields.enable_widgets'), 'default' => '1']);
register_theme_setting('enable_search', ['type' => 'checkbox', 'label' => t('theme.customizer_fields.enable_search'), 'default' => '1']);
register_theme_setting('front_home_content', [
    'type' => 'content_picker',
    'label' => t('theme.customizer_fields.homepage_content'),
    'default' => '',
    'default_setting' => 'front_home_content',
    'empty_label' => t('theme.customizer_fields.homepage_loop'),
]);
register_theme_setting('layout_width', [
    'type' => 'select',
    'label' => t('theme.customizer_fields.layout_width'),
    'default' => 'default',
    'options' => [
        'narrow' => t('theme.customizer_options.width_narrow'),
        'default' => t('theme.customizer_options.width_default'),
        'wide' => t('theme.customizer_options.width_wide'),
        'full' => t('theme.customizer_options.width_full'),
    ],
]);
register_theme_setting('custom_css', ['type' => 'textarea', 'label' => t('theme.customizer_fields.custom_css'), 'max' => 20000]);

register_theme_setting('single_show_thumbnail', ['type' => 'checkbox', 'label' => t('theme.customizer_fields.show_thumbnail'), 'default' => '1']);
register_theme_setting('single_meta_date', ['type' => 'checkbox', 'label' => t('theme.customizer_fields.meta_date'), 'default' => '1']);
register_theme_setting('single_meta_author', ['type' => 'checkbox', 'label' => t('theme.customizer_fields.meta_author'), 'default' => '1']);
register_theme_setting('single_meta_comments', ['type' => 'checkbox', 'label' => t('theme.customizer_fields.meta_comments'), 'default' => '0']);
register_theme_setting('single_show_terms', ['type' => 'checkbox', 'label' => t('theme.customizer_fields.show_terms'), 'default' => '1']);
register_theme_setting('archive_show_thumbnail', ['type' => 'checkbox', 'label' => t('theme.customizer_fields.show_thumbnail'), 'default' => '1']);
register_theme_setting('archive_meta_date', ['type' => 'checkbox', 'label' => t('theme.customizer_fields.meta_date'), 'default' => '1']);
register_theme_setting('archive_meta_author', ['type' => 'checkbox', 'label' => t('theme.customizer_fields.meta_author'), 'default' => '1']);
register_theme_setting('archive_meta_comments', ['type' => 'checkbox', 'label' => t('theme.customizer_fields.meta_comments'), 'default' => '1']);

foreach ([
    'color_bg' => [t('theme.customizer_fields.color_bg'), '#f7f7f2'],
    'color_surface' => [t('theme.customizer_fields.color_surface'), '#ffffff'],
    'color_surface_alt' => [t('theme.customizer_fields.color_surface_alt'), '#efefea'],
    'color_text' => [t('theme.customizer_fields.color_text'), '#1f2328'],
    'color_muted' => [t('theme.customizer_fields.color_muted'), '#687076'],
    'color_border' => [t('theme.customizer_fields.color_border'), '#c8ccc8'],
    'color_accent' => [t('theme.customizer_fields.color_accent'), '#2457c5'],
    'color_accent_strong' => [t('theme.customizer_fields.color_accent_strong'), '#163f96'],
    'color_accent_soft' => [t('theme.customizer_fields.color_accent_soft'), '#edf2ff'],
] as $key => [$label, $default]) {
    register_theme_setting($key, ['type' => 'color', 'label' => $label, 'default' => $default]);
}

register_theme_setting('footer_text', ['type' => 'textarea', 'label' => t('theme.customizer_fields.footer_text')]);

register_widget_area('before_content', t('theme.widget_areas.before_content'));
register_widget_area('left', t('theme.widget_areas.left'));
register_widget_area('right', t('theme.widget_areas.right'));
register_widget_area('after_content', t('theme.widget_areas.after_content'));
