<?php
$item = is_array($item ?? null) ? $item : [];
$comments = is_array($comments ?? null) ? $comments : [];
$isAuthenticated = ($isAuthenticated ?? false) === true;
$contentId = (int)($item['id'] ?? 0);
$contentSlug = trim((string)($item['name'] ?? '')) !== '' && $contentId > 0 ? trim((string)parse_url($contentUrl($item), PHP_URL_PATH), '/') : '';
?>
<section class="comments-block">
    <h2><?= $e($t('front.comments_heading')) ?></h2>

    <?php if (!$isAuthenticated): ?>
        <p class="text-muted"><?= $e($t('front.comments_login_required')) ?> <a href="<?= $e($url('auth/login')) ?>"><?= $e($t('front.comments_login_link')) ?></a></p>
    <?php else: ?>
        <p class="text-danger" data-api-form-message hidden></p>
        <form method="post" action="<?= $e($url('admin/api/v1/comments/add')) ?>" data-api-submit data-stay-on-page>
            <?= $csrfField() ?>
            <input type="hidden" name="content_id" value="<?= $e((string)$contentId) ?>">
            <input type="hidden" name="content_slug" value="<?= $e($contentSlug) ?>">
            <input type="hidden" name="reply_to" value="0">
            <label for="comment-body"><?= $e($t('front.comments_form_label')) ?></label>
            <textarea id="comment-body" name="body" rows="5" required></textarea>
            <button type="submit"><?= $e($t('front.comments_submit')) ?></button>
        </form>
    <?php endif; ?>

    <?php if ($comments === []): ?>
        <p class="text-muted"><?= $e($t('front.comments_empty')) ?></p>
    <?php else: ?>
        <div class="comments-list">
            <?php foreach ($comments as $parent): ?>
                <?php $parentId = (int)($parent['id'] ?? 0); ?>
                <article class="comment-item">
                    <header>
                        <strong><?= $e((string)($parent['author_name'] ?? '')) ?></strong>
                        <small class="text-muted"><?= $e($contentDate($parent)) ?></small>
                    </header>
                    <p><?= nl2br($e((string)($parent['body'] ?? ''))) ?></p>

                    <?php if ($isAuthenticated && $parentId > 0): ?>
                        <details>
                            <summary><?= $e($t('front.comments_reply')) ?></summary>
                            <p class="text-danger" data-api-form-message hidden></p>
                            <form method="post" action="<?= $e($url('admin/api/v1/comments/add')) ?>" data-api-submit data-stay-on-page>
                                <?= $csrfField() ?>
                                <input type="hidden" name="content_id" value="<?= $e((string)$contentId) ?>">
                                <input type="hidden" name="content_slug" value="<?= $e($contentSlug) ?>">
                                <input type="hidden" name="reply_to" value="<?= $e((string)$parentId) ?>">
                                <label><?= $e($t('front.comments_form_label')) ?></label>
                                <textarea name="body" rows="4" required></textarea>
                                <button type="submit"><?= $e($t('front.comments_submit_reply')) ?></button>
                            </form>
                        </details>
                    <?php endif; ?>

                    <?php $children = is_array($parent['children'] ?? null) ? $parent['children'] : []; ?>
                    <?php if ($children !== []): ?>
                        <div class="comment-children">
                            <?php foreach ($children as $child): ?>
                                <?php $childId = (int)($child['id'] ?? 0); ?>
                                <article class="comment-child">
                                    <header>
                                        <strong><?= $e((string)($child['author_name'] ?? '')) ?></strong>
                                        <small class="text-muted"><?= $e($contentDate($child)) ?></small>
                                    </header>
                                    <?php $replyAuthor = trim((string)($child['reply_author_name'] ?? '')); ?>
                                    <?php if ($replyAuthor !== '' && (int)($child['reply_to'] ?? 0) > 0): ?>
                                        <p class="text-muted small">@<?= $e($replyAuthor) ?></p>
                                    <?php endif; ?>
                                    <p><?= nl2br($e((string)($child['body'] ?? ''))) ?></p>

                                    <?php if ($isAuthenticated && $childId > 0): ?>
                                        <details>
                                            <summary><?= $e($t('front.comments_reply')) ?></summary>
                                            <p class="text-danger" data-api-form-message hidden></p>
                                            <form method="post" action="<?= $e($url('admin/api/v1/comments/add')) ?>" data-api-submit data-stay-on-page>
                                                <?= $csrfField() ?>
                                                <input type="hidden" name="content_id" value="<?= $e((string)$contentId) ?>">
                                                <input type="hidden" name="content_slug" value="<?= $e($contentSlug) ?>">
                                                <input type="hidden" name="reply_to" value="<?= $e((string)$childId) ?>">
                                                <label><?= $e($t('front.comments_form_label')) ?></label>
                                                <textarea name="body" rows="3" required></textarea>
                                                <button type="submit"><?= $e($t('front.comments_submit_reply')) ?></button>
                                            </form>
                                        </details>
                                    <?php endif; ?>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
