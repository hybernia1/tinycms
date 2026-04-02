<?php
declare(strict_types=1);
?>
<h1>Admin login</h1>
<?php if ($message !== ''): ?>
<p><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p>
<?php endif; ?>
<form method="post" action="/admin/login">
    <label>
        Email
        <input type="email" name="email" value="<?= htmlspecialchars((string)($old['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
    </label>
    <?php if (!empty($errors['email'])): ?>
    <small><?= htmlspecialchars((string)$errors['email'], ENT_QUOTES, 'UTF-8') ?></small>
    <?php endif; ?>
    <label>
        Heslo
        <input type="password" name="password" required>
    </label>
    <?php if (!empty($errors['password'])): ?>
    <small><?= htmlspecialchars((string)$errors['password'], ENT_QUOTES, 'UTF-8') ?></small>
    <?php endif; ?>
    <button type="submit">Přihlásit</button>
</form>
