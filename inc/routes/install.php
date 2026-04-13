<?php
declare(strict_types=1);

register_routes($router, $redirect, [
    ['method' => 'GET', 'path' => 'install', 'controller' => $install, 'action' => 'formLanguage', 'with_redirect' => false],
    ['method' => 'POST', 'path' => 'install', 'controller' => $install, 'action' => 'submitLanguage'],

    ['method' => 'GET', 'path' => 'install/db', 'controller' => $install, 'action' => 'formDb', 'with_redirect' => false],
    ['method' => 'POST', 'path' => 'install/db', 'controller' => $install, 'action' => 'submitDb'],

    ['method' => 'GET', 'path' => 'install/admin', 'controller' => $install, 'action' => 'formAdmin'],
    ['method' => 'POST', 'path' => 'install/admin', 'controller' => $install, 'action' => 'submitAdmin'],

    ['method' => 'GET', 'path' => 'install/done', 'controller' => $install, 'action' => 'done'],
]);
