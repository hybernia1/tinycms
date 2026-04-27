<?php
declare(strict_types=1);

define('BASE_DIR', __DIR__);
define('SRC_DIR', 'src/');
define('INC_DIR', SRC_DIR . 'inc/');
define('VIEW_DIR', SRC_DIR . 'view/');
define('ASSETS_DIR', SRC_DIR . 'assets/');
define('EXTENSIONS_DIR', 'extensions/');
define('THEMES_DIR', EXTENSIONS_DIR . 'themes/');

const APP_DEBUG = false;
const APP_VERSION = '0.9.0';
const APP_LANG = 'en';
const APP_POSTS_PER_PAGE = 10;

const DB_HOST = '127.0.0.1';
const DB_NAME = 'tinycms';
const DB_USER = 'root';
const DB_PASS = '';
const DB_PREFIX = '';
