<?php
declare(strict_types=1);

define('INC_DIR', 'inc/');

const APP_DEBUG = false;

const DB_HOST = '127.0.0.1';
const DB_NAME = 'tinycms';
const DB_USER = 'root';
const DB_PASS = '';

const MEDIA_THUMB_VARIANTS = [
    ['suffix' => '_100x100.webp', 'mode' => 'crop', 'width' => 100, 'height' => 100],
    ['suffix' => '_w768.webp', 'mode' => 'fit', 'width' => 768],
];
