<div class="card" id="comment-<?= $id ?>">
    <div class="card-block">
        <p class="card-text"><?= htmlspecialchars($body) ?></p>
    </div>
    <div class="card-footer">
        <a href="/profile/<?= htmlspecialchars($authorUsername) ?>" class="comment-author">
            <img src="<?= htmlspecialchars($authorImage) ?>" class="comment-author-img" />
        </a>
        &nbsp;
        <a href="/profile/<?= htmlspecialchars($authorUsername) ?>" class="comment-author">
            <?= htmlspecialchars($authorUsername) ?>
        </a>
        <span class="date-posted"><?= $date ?></span>
        <?php if ($canDelete): ?>
            <span class="mod-options">
                <i class="ion-trash-a"
                   fx-action="/article/<?= htmlspecialchars($articleSlug) ?>/comment/<?= $id ?>"
                   fx-method="DELETE"
                   fx-target="#comment-<?= $id ?>"
                   fx-swap="outerHTML"></i>
            </span>
        <?php endif; ?>
    </div>
</div>
