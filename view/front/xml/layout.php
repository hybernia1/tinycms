<?php
declare(strict_types=1);
header((string)($contentType ?? 'application/xml; charset=utf-8'));
echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<?= $content ?>
