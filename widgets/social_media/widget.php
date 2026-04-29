<?php
declare(strict_types=1);

if (!defined('BASE_DIR')) {
    exit;
}

$url = static function (string $url): string {
    $value = trim($url);
    if ($value === '') {
        return '';
    }

    if (str_starts_with($value, '//')) {
        return 'https:' . $value;
    }

    return preg_match('#^https?://#i', $value) === 1 ? $value : 'https://' . ltrim($value, '/');
};

$mail = static function (string $email): string {
    $value = trim($email);
    if ($value === '') {
        return '';
    }

    $value = preg_replace('#^mailto:#i', '', $value) ?? '';

    return filter_var($value, FILTER_VALIDATE_EMAIL) ? 'mailto:' . $value : '';
};

$services = [
    'facebook' => ['icon' => 'facebook', 'label' => 'Facebook', 'field' => t('widgets.social_media.facebook'), 'href' => $url],
    'instagram' => ['icon' => 'instagram', 'label' => 'Instagram', 'field' => t('widgets.social_media.instagram'), 'href' => $url],
    'x' => ['icon' => 'x', 'label' => 'X', 'field' => t('widgets.social_media.x'), 'href' => $url],
    'gmail' => ['icon' => 'gmail', 'label' => 'Gmail', 'field' => t('widgets.social_media.gmail'), 'href' => $mail],
];

$fields = [
    ['name' => 'title', 'type' => 'text', 'label' => t('widgets.social_media.title')],
];
foreach ($services as $name => $service) {
    $fields[] = ['name' => $name, 'type' => 'text', 'label' => $service['field']];
}

return [
    'name' => t('widgets.social_media.name'),
    'fields' => $fields,
    'render' => static function (array $data) use ($services): string {
        $title = trim((string)($data['title'] ?? ''));
        $links = [];

        foreach ($services as $name => $service) {
            $href = $service['href']((string)($data[$name] ?? ''));
            if ($href === '') {
                continue;
            }

            $label = (string)$service['label'];
            $target = str_starts_with($href, 'mailto:') ? '' : ' target="_blank" rel="noopener noreferrer"';
            $links[] = '<a class="social-link social-link-' . esc_attr($name) . '" href="' . esc_url($href) . '"' . $target . ' aria-label="' . esc_attr($label) . '" title="' . esc_attr($label) . '">' . icon((string)$service['icon']) . '<span>' . esc_html($label) . '</span></a>';
        }

        if ($title === '' && $links === []) {
            return '';
        }

        $html = $title !== '' ? '<h2 class="widget-title">' . esc_html($title) . '</h2>' : '';
        return $html . ($links !== [] ? '<div class="social-links">' . implode('', $links) . '</div>' : '');
    },
];
