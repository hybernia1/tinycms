<?php
$comments = is_array($comments ?? null) ? $comments : [];
$commentState = is_array($commentState ?? null) ? $commentState : [];
$isAuthenticated = ($commentState['isAuthenticated'] ?? false) === true;
?>
<article class="content-single">
    <h1><?= $e((string)($item['name'] ?? '')) ?></h1>
    <p><a href="<?= $e($contentUrl($item)) ?>"><?= $e($t('front.comments_back_to_content')) ?></a></p>
</article>

<?php $includePartial('comments', [
    'item' => $item,
    'comments' => $comments,
    'isAuthenticated' => $isAuthenticated,
]); ?>
