<?php

if (!defined('BASE_DIR')) {
    exit;
}

?>
<?= $renderPagination(
    (array)($pagination ?? []),
    (string)($basePath ?? ''),
    [
        'prev' => $t('front.prev'),
        'next' => $t('front.next'),
    ],
) ?>
