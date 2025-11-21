<div class="article-preview">
    <div class="article-meta">
        <a href="/profile/<?= htmlspecialchars($article['author_username']) ?>">
            <img src="<?= $article['author_image'] ?: '/img/smiley-cyrus.jpg' ?>" />
        </a>
        <div class="info">
            <a href="/profile/<?= htmlspecialchars($article['author_username']) ?>" class="author"><?= htmlspecialchars($article['author_username']) ?></a>
            <span class="date"><?= date('F jS', strtotime($article['created_at'])) ?></span>
        </div>
        <?php
        $isFavorited = false;
        if (\App\Core\Auth::check()) {
            $isFavorited = \App\Models\Article::isFavorited(\App\Core\Auth::userId(), $article['id']);
        }
        ?>
        <button class="btn btn-sm pull-xs-right <?= $isFavorited ? 'btn-primary' : 'btn-outline-primary' ?>"
                hx-post="/article/<?= $article['slug'] ?>/<?= $isFavorited ? 'unfavorite' : 'favorite' ?>?align=right&variant=compact"
                hx-target="this"
                hx-swap="outerHTML">
            <i class="ion-heart"></i> <?= $article['favoritesCount'] ?>
        </button>
    </div>
    <a href="/article/<?= htmlspecialchars($article['slug']) ?>" class="preview-link">
        <h1><?= htmlspecialchars($article['title']) ?></h1>
        <p><?= htmlspecialchars($article['description']) ?></p>
        <span>Read more...</span>
        <ul class="tag-list">
            <?php foreach ($article['tagList'] as $tag): ?>
                <li class="tag-default tag-pill tag-outline"><?= htmlspecialchars($tag) ?></li>
            <?php endforeach; ?>
        </ul>
    </a>
</div>
