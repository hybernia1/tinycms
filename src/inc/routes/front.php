<?php
declare(strict_types=1);
if (!defined('BASE_DIR')) {
    exit;
}

register_routes($router, $redirect, [
    ['method' => 'POST', 'path' => 'comments/{contentId}/add', 'controller' => $frontComments, 'action' => 'add', 'params' => ['contentId' => 'int']],
    ['method' => 'GET', 'path' => '', 'controller' => $front, 'action' => 'home', 'with_redirect' => false],
    ['method' => 'GET', 'path' => 'account', 'controller' => $front, 'action' => 'account'],
    ['method' => 'GET', 'path' => 'search', 'controller' => $front, 'action' => 'search', 'with_redirect' => false],
    ['method' => 'GET', 'path' => 'robots.txt', 'controller' => $front, 'action' => 'robotsTxt', 'with_redirect' => false],
    ['method' => 'GET', 'path' => 'feed', 'controller' => $front, 'action' => 'feed', 'with_redirect' => false],
    ['method' => 'GET', 'path' => 'sitemap.xml', 'controller' => $front, 'action' => 'sitemapIndex', 'with_redirect' => false],
    ['method' => 'GET', 'path' => 'sitemap-content{chunk}.xml', 'controller' => $front, 'action' => 'sitemapContent', 'with_redirect' => false, 'raw_params' => true],
    ['method' => 'GET', 'path' => 'sitemap-terms{chunk}.xml', 'controller' => $front, 'action' => 'sitemapTerms', 'with_redirect' => false, 'raw_params' => true],
    ['method' => 'GET', 'path' => 'term/{slug}', 'controller' => $front, 'action' => 'termArchive', 'raw_params' => true],
    ['method' => 'GET', 'path' => 'author/{slug}', 'controller' => $front, 'action' => 'authorArchive', 'raw_params' => true],
    ['method' => 'GET', 'path' => '{slug}', 'controller' => $front, 'action' => 'content', 'raw_params' => true],
]);
