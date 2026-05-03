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
        'custom_css',
        'layout_width',
        'colors',
    ],
]);

register_theme_section('branding', t('theme.customizer_sections.branding'), ['brand_display', 'logo']);
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

register_widget_area('before_content', t('theme.widget_areas.before_content'));
register_widget_area('left', t('theme.widget_areas.left'));
register_widget_area('right', t('theme.widget_areas.right'));
register_widget_area('after_content', t('theme.widget_areas.after_content'));
