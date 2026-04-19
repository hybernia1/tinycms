<?php
declare(strict_types=1);
if (!defined('BASE_DIR')) {
    exit;
}

register_routes($router, $redirect, [
    ['method' => 'GET', 'path' => '', 'controller' => $front, 'action' => 'home', 'with_redirect' => false],
    ['method' => 'GET', 'path' => 'account', 'controller' => $front, 'action' => 'account'],
    ['method' => 'GET', 'path' => 'search', 'controller' => $front, 'action' => 'search', 'with_redirect' => false],
    ['method' => 'GET', 'path' => 'robots.txt', 'controller' => $front, 'action' => 'robotsTxt', 'with_redirect' => false],
    ['method' => 'GET', 'path' => 'sitemap.xml', 'controller' => $front, 'action' => 'sitemapIndex', 'with_redirect' => false],
    ['method' => 'GET', 'path' => 'sitemap-content{chunk}.xml', 'controller' => $front, 'action' => 'sitemapContent', 'with_redirect' => false, 'raw_params' => true],
    ['method' => 'GET', 'path' => 'sitemap-terms{chunk}.xml', 'controller' => $front, 'action' => 'sitemapTerms', 'with_redirect' => false, 'raw_params' => true],
    ['method' => 'GET', 'path' => 'content/{id}', 'controller' => $front, 'action' => 'contentLegacy', 'raw_params' => true],
    ['method' => 'GET', 'path' => 'term/{slug}', 'controller' => $front, 'action' => 'termArchive', 'raw_params' => true],
    ['method' => 'GET', 'path' => '{slug}', 'controller' => $front, 'action' => 'content', 'raw_params' => true],
]);
