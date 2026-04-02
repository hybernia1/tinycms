<?php
declare(strict_types=1);
?>
<h1>Admin dashboard</h1>
<p><?= htmlspecialchars((string)($user['name'] ?? 'Uživatel'), ENT_QUOTES, 'UTF-8') ?></p>
<a href="/admin/logout">Odhlásit</a>
