<?php
declare(strict_types=1);

if (!defined('BASE_DIR')) {
    define('BASE_DIR', __DIR__);
}
define('SRC_DIR', 'src/');
define('INC_DIR', SRC_DIR . 'inc/');
define('VIEW_DIR', SRC_DIR . 'view/');
define('ASSETS_DIR', SRC_DIR . 'assets/');

const APP_DEBUG = false;
const APP_VERSION = '0.9.0';
const APP_LANG = 'en';
const APP_DATE_FORMAT = 'd.m.Y';
const APP_DATETIME_FORMAT = 'd.m.Y H:i';
const APP_POSTS_PER_PAGE = 10;

const DB_HOST = '127.0.0.1';
const DB_NAME = 'tinycms';
const DB_USER = 'root';
const DB_PASS = '';
const DB_PREFIX = '';

const MEDIA_SMALL_WIDTH = 300;
const MEDIA_SMALL_HEIGHT = 300;
const MEDIA_MEDIUM_WIDTH = 768;
