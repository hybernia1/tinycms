<?php

if (!defined('BASE_DIR')) {
    exit;
}

?>
<?= $pagination(
    (array)($pagination ?? []),
    (string)($basePath ?? ''),
    [
        'prev' => $t('front.prev'),
        'next' => $t('front.next'),
    ],
) ?>
