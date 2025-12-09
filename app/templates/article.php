<div class="article-page">
    <div class="banner">
        <div class="container">
            <h1 style="view-transition-name: <?= htmlspecialchars($article['slug']) ?>-title;">
                <?= htmlspecialchars($article['title']) ?>
            </h1>

            <div class="article-meta" style="view-transition-name: <?= htmlspecialchars($article['slug']) ?>-meta;">
                <?php \App\Components\ArticleMeta\render($article); ?>

                <?php if (\App\Lib\Auth::check() && \App\Lib\Auth::userId() == $article['author_id']): ?>
                    <a class="btn btn-outline-secondary btn-sm" href="/editor/<?= $article['slug'] ?>">
                        <i class="ion-edit"></i> Edit Article
                    </a>
                    <button class="btn btn-outline-danger btn-sm" fx-action="/article/<?= $article['slug'] ?>/delete" fx-method="POST" fx-confirm="Are you sure you want to delete this article?">
                        <i class="ion-trash-a"></i> Delete Article
                    </button>
                <?php elseif (\App\Lib\Auth::check()): ?>
                    <?php $isFollowing = \App\Models\User::isFollowing(\App\Lib\Auth::userId(), $article['author_id']); ?>
                    <?php \App\Components\FollowButton\render(['username' => $article['author_username']], $isFollowing); ?>
                    <?php \App\Components\FavoriteButton\render($article, $isFavorited); ?>
                <?php else: ?>
                    <a href="/login" class="btn btn-sm btn-outline-secondary">
                        <i class="ion-plus-round"></i>
                        &nbsp;
                        Follow <?= htmlspecialchars($article['author_username']) ?>
                    </a>
                    <a href="/login" class="btn btn-sm btn-outline-primary">
                        <i class="ion-heart"></i>
                        &nbsp;
                        Favorite Article <span class="counter">(<?= isset($article['favoritesCount']) ? (int)$article['favoritesCount'] : \App\Models\Article::favoritesCount($article['id']) ?>)</span>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="container page">
        <div class="row article-content">
            <div class="col-md-12">
                <?php
                $parsedown = new Parsedown();
                $parsedown->setSafeMode(true);
                echo $parsedown->text((string)$article['body']);
                ?>
                <?php if (!empty($article['tagList'])): ?>
                    <ul class="tag-list">
                        <?php foreach ($article['tagList'] as $tag): ?>
                            <li class="tag-default tag-pill tag-outline"><?= htmlspecialchars($tag) ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>

        <hr />

        <div class="article-actions">
            <div class="article-meta">
                <?php \App\Components\ArticleMeta\render($article); ?>
            </div>
        </div>

        <div class="row">
            <div class="col-xs-12 col-md-8 offset-md-2">
                <?php $currentUser = \App\Lib\Auth::user(); ?>
                <?php if ($currentUser): ?>
                    <form class="card comment-form" fx-action="/article/<?= $article['slug'] ?>/comments" fx-method="POST" fx-target="#comment-list" fx-swap="afterbegin" fx-reset>
                        <div class="card-block">
                            <textarea class="form-control" name="body" placeholder="Write a comment..." rows="3"></textarea>
                        </div>
                        <div class="card-footer">
                            <img src="<?= \App\Models\User::findById($currentUser['id'])['image'] ?? '/img/smiley-cyrus.avif' ?>" class="comment-author-img" />
                            <input type="hidden" name="csrf_token" value="<?= \App\Lib\Security::getToken() ?>">
                            <button class="btn btn-sm btn-primary">
                                Post Comment
                            </button>
                        </div>
                    </form>
                <?php else: ?>
                    <p>
                        <a href="/login">Sign in</a> or <a href="/register">sign up</a> to add comments on this article.
                    </p>
                <?php endif; ?>

                <div id="comment-list">
                    <?php if (!empty($comments)): ?>
                        <?php foreach ($comments as $comment): ?>
                            <?php \App\Components\Comment\render($comment, $article['slug']); ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
