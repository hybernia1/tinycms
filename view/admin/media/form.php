<div class="card p-5">
    <?php
    $previewPath = trim((string)($item['path_webp'] ?? ''));
    if ($previewPath === '') {
        $previewPath = trim((string)($item['path'] ?? ''));
    }
    $previewUrl = $previewPath !== '' ? $url($previewPath) : '';
    $authUser = $_SESSION['auth'] ?? [];
    $isEditor = (string)($authUser['role'] ?? '') === 'editor';
    $currentUserId = (int)($authUser['id'] ?? 0);
    ?>
    <form method="post" enctype="multipart/form-data" action="<?= htmlspecialchars($mode === 'add' ? $url('admin/media/add') : $url('admin/media/edit?id=' . (int)($item['id'] ?? 0)), ENT_QUOTES, 'UTF-8') ?>">
        <?= $csrfField() ?>
        <div class="mb-3">
            <label><?= htmlspecialchars($t('common.name', 'Name'), ENT_QUOTES, 'UTF-8') ?></label>
            <input type="text" name="name" value="<?= htmlspecialchars((string)($item['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
            <?php if (!empty($errors['name'])): ?><small class="text-danger"><?= htmlspecialchars((string)$errors['name'], ENT_QUOTES, 'UTF-8') ?></small><?php endif; ?>
        </div>
        <div class="mb-3">
            <label><?= htmlspecialchars($t('media.file', 'File'), ENT_QUOTES, 'UTF-8') ?> <?= $mode === 'add' ? '' : '(' . htmlspecialchars($t('media.file_optional_replace', 'optional, replaces current'), ENT_QUOTES, 'UTF-8') . ')' ?></label>
            <input type="file" name="file" accept=".jpg,.jpeg,.png,.webp,.gif,image/jpeg,image/png,image/webp,image/gif" <?= $mode === 'add' ? 'required' : '' ?>>
            <?php if (!empty($errors['file'])): ?><small class="text-danger"><?= htmlspecialchars((string)$errors['file'], ENT_QUOTES, 'UTF-8') ?></small><?php endif; ?>
        </div>
        <?php if ($mode === 'edit'): ?>
            <div class="mb-3">
                <label>Path</label>
                <div class="text-muted"><?= htmlspecialchars((string)($item['path'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
            </div>
            <div class="mb-3">
                <label>Path webp</label>
                <div class="text-muted"><?= htmlspecialchars((string)($item['path_webp'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
            </div>
            <?php if ($previewUrl !== ''): ?>
                <div class="content-thumbnail-preview mb-3">
                    <img src="<?= htmlspecialchars($previewUrl, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars((string)($item['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                </div>
            <?php endif; ?>
            <div class="mb-3">
                <label><?= htmlspecialchars($t('common.created', 'Created'), ENT_QUOTES, 'UTF-8') ?></label>
                <div class="text-muted"><?= htmlspecialchars($formatDateTime((string)($item['created'] ?? '')), ENT_QUOTES, 'UTF-8') ?></div>
            </div>
            <div class="mb-3">
                <label><?= htmlspecialchars($t('common.updated', 'Updated'), ENT_QUOTES, 'UTF-8') ?></label>
                <div class="text-muted"><?= htmlspecialchars($formatDateTime((string)($item['updated'] ?? ''), '—'), ENT_QUOTES, 'UTF-8') ?></div>
            </div>
        <?php endif; ?>
        <?php if ($isEditor): ?>
            <input type="hidden" name="author" value="<?= $currentUserId > 0 ? $currentUserId : '' ?>">
        <?php else: ?>
            <div class="mb-4">
                <label><?= htmlspecialchars($t('common.author', 'Author'), ENT_QUOTES, 'UTF-8') ?></label>
                <select name="author">
                    <option value=""><?= htmlspecialchars($t('common.no_author', 'No author'), ENT_QUOTES, 'UTF-8') ?></option>
                    <?php foreach ($authors as $author): ?>
                        <?php $authorId = (int)($author['ID'] ?? 0); ?>
                        <option value="<?= $authorId ?>" <?= (int)($item['author'] ?? 0) === $authorId ? 'selected' : '' ?>>
                            <?= htmlspecialchars((string)($author['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars((string)($author['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (!empty($errors['author'])): ?><small class="text-danger"><?= htmlspecialchars((string)$errors['author'], ENT_QUOTES, 'UTF-8') ?></small><?php endif; ?>
            </div>
        <?php endif; ?>
        <button class="btn btn-primary" type="submit"><?= htmlspecialchars($t('common.save', 'Save'), ENT_QUOTES, 'UTF-8') ?></button>
        <a class="btn btn-light" href="<?= htmlspecialchars($url('admin/media'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($t('common.back', 'Back'), ENT_QUOTES, 'UTF-8') ?></a>
    </form>

    <?php if ($mode === 'edit'): ?>
        <hr>
        <h3 class="mb-3"><?= htmlspecialchars($t('media.used_as_thumbnail', 'Used as thumbnail'), ENT_QUOTES, 'UTF-8') ?></h3>
        <?php if (($usages ?? []) === []): ?>
            <p class="text-muted m-0"><?= htmlspecialchars($t('media.no_thumbnail_usage', 'Media is not used as a thumbnail in any post.'), ENT_QUOTES, 'UTF-8') ?></p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                    <tr>
                        <th><?= htmlspecialchars($t('content.post', 'Post'), ENT_QUOTES, 'UTF-8') ?></th><th><?= htmlspecialchars($t('content.status', 'Status'), ENT_QUOTES, 'UTF-8') ?></th><th><?= htmlspecialchars($t('common.created', 'Created'), ENT_QUOTES, 'UTF-8') ?></th><th><?= htmlspecialchars($t('common.updated', 'Updated'), ENT_QUOTES, 'UTF-8') ?></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($usages as $usage): ?>
                        <tr>
                            <td>
                                <a href="<?= htmlspecialchars($url('admin/content/edit?id=' . (int)($usage['id'] ?? 0)), ENT_QUOTES, 'UTF-8') ?>">
                                    <?= htmlspecialchars((string)($usage['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                </a>
                            </td>
                            <td><?= htmlspecialchars((string)($usage['status'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($formatDateTime((string)($usage['created'] ?? '')), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($formatDateTime((string)($usage['updated'] ?? ''), '—'), ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
