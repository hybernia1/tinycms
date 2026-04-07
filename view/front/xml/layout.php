<?php
declare(strict_types=1);
$type = trim((string)($contentType ?? 'application/xml; charset=utf-8'));
header('Content-Type: ' . ($type !== '' ? $type : 'application/xml; charset=utf-8'));
echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<?= $content ?>
