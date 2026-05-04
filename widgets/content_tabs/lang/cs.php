<?php

if (!defined('BASE_DIR')) {
    exit;
}

return [
    'widgets' => [
        'content_tabs' => [
            'name' => 'Obsah v tabech',
            'title' => 'Název',
            'limit' => 'Položek na tab',
            'hot_label' => 'Popisek hot tabu',
            'latest_label' => 'Popisek posledních příspěvků',
            'first_tab' => 'První tab',
            'show_thumbnail' => 'Zobrazit miniaturu',
            'show_excerpt' => 'Zobrazit perex',
            'hot_default' => 'Hot content',
            'latest_default' => 'Poslední příspěvky',
            'views_count' => 'Unikátních zobrazení: %d',
            'empty' => 'Žádný obsah nebyl nalezen.',
        ],
    ],
];
