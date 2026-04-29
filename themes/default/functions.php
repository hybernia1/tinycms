<?php

if (!defined('BASE_DIR')) {
    exit;
}

register_widget_area('before_content', 'Before content');
register_widget_area('left', 'Left sidebar');
register_widget_area('right', 'Right sidebar');
register_widget_area('after_content', 'After content');
