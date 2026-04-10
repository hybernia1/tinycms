<?php
declare(strict_types=1);
$type = trim((string)($contentType ?? 'text/plain; charset=utf-8'));
header('Content-Type: ' . ($type !== '' ? $type : 'text/plain; charset=utf-8'));
?>
<?= $content ?>
