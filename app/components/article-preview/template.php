<div class="article-preview">
    <div class="article-meta">
        <a href="/profile/<?= htmlspecialchars($authorUsername) ?>">
            <img src="<?= htmlspecialchars($authorImage) ?>" />
        </a>
        <div class="info">
            <a href="/profile/<?= htmlspecialchars($authorUsername) ?>" class="author"><?= htmlspecialchars($authorUsername) ?></a>
            <span class="date"><?= $date ?></span>
        </div>
        <?php \App\Components\FavoriteButton\render($article, $isFavorited, true, true); ?>
    </div>
    <a href="/article/<?= htmlspecialchars($slug) ?>" class="preview-link">
        <h1><?= htmlspecialchars($title) ?></h1>
        <p><?= htmlspecialchars($description) ?></p>
        <span>Read more...</span>
        <ul class="tag-list">
            <?php foreach ($tagList as $tag): ?>
                <li class="tag-default tag-pill tag-outline"><?= htmlspecialchars($tag) ?></li>
            <?php endforeach; ?>
        </ul>
    </a>
</div>
