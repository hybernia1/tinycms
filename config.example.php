<?php
declare(strict_types=1);

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

const MEDIA_THUMB_VARIANTS = [
    ['name' => 'medium', 'suffix' => '_w768.webp', 'mode' => 'fit', 'width' => 768],
];
