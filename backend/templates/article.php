<div class="article-page">
    <div class="banner">
        <div class="container">
            <h1><?= htmlspecialchars($article['title']) ?></h1>

            <div class="article-meta">
                <a href="/profile/<?= htmlspecialchars($article['author_username']) ?>">
                    <img src="<?= $article['author_image'] ?: '/img/smiley-cyrus.jpg' ?>" />
                </a>
                <div class="info">
                    <a href="/profile/<?= htmlspecialchars($article['author_username']) ?>" class="author"><?= htmlspecialchars($article['author_username']) ?></a>
                    <span class="date"><?= date('F jS', strtotime($article['created_at'])) ?></span>
                </div>

                <?php if (\App\Core\Auth::check() && \App\Core\Auth::userId() == $article['author_id']): ?>
                    <a class="btn btn-outline-secondary btn-sm" href="/editor/<?= $article['slug'] ?>">
                        <i class="ion-edit"></i> Edit Article
                    </a>
                    <button class="btn btn-outline-danger btn-sm" hx-post="/article/<?= $article['slug'] ?>/delete" hx-confirm="Are you sure you want to delete this article?">
                        <i class="ion-trash-a"></i> Delete Article
                    </button>
                <?php elseif (\App\Core\Auth::check()): ?>
                    <?php
                    $isFollowing = \App\Models\User::isFollowing(\App\Core\Auth::userId(), $article['author_id']);
                    ?>
                    <button class="btn btn-sm btn-outline-secondary"
                            hx-post="/profile/<?= htmlspecialchars($article['author_username']) ?>/<?= $isFollowing ? 'unfollow' : 'follow' ?>"
                            hx-target="this"
                            hx-swap="outerHTML">
                        <i class="ion-plus-round"></i>
                        &nbsp;
                        <?= $isFollowing ? 'Unfollow' : 'Follow' ?> <?= htmlspecialchars($article['author_username']) ?>
                    </button>
                    <button class="btn btn-sm <?= $isFavorited ? 'btn-primary' : 'btn-outline-primary' ?>"
                            hx-post="/article/<?= $article['slug'] ?>/<?= $isFavorited ? 'unfavorite' : 'favorite' ?>"
                            hx-target="this"
                            hx-swap="outerHTML">
                        <i class="ion-heart"></i>
                        &nbsp;
                        <?= $isFavorited ? 'Unfavorite' : 'Favorite' ?> Article <span class="counter">(<?= isset($article['favoritesCount']) ? (int)$article['favoritesCount'] : \App\Models\Article::favoritesCount($article['id']) ?>)</span>
                    </button>
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
                <a href="/profile/<?= htmlspecialchars($article['author_username']) ?>">
                    <img src="<?= $article['author_image'] ?: '/img/smiley-cyrus.jpg' ?>" />
                </a>
                <div class="info">
                    <a href="/profile/<?= htmlspecialchars($article['author_username']) ?>" class="author"><?= htmlspecialchars($article['author_username']) ?></a>
                    <span class="date"><?= date('F jS', strtotime($article['created_at'])) ?></span>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-xs-12 col-md-8 offset-md-2">
                <?php $currentUser = \App\Core\Auth::user(); ?>
                <?php if ($currentUser): ?>
                    <form class="card comment-form" hx-post="/article/<?= $article['slug'] ?>/comments" hx-target="#comment-list" hx-swap="afterbegin" hx-on::after-request="this.reset()">
                        <div class="card-block">
                            <textarea class="form-control" name="body" placeholder="Write a comment..." rows="3"></textarea>
                        </div>
                        <div class="card-footer">
                            <img src="<?= \App\Models\User::findById($currentUser['id'])['image'] ?? '/img/smiley-cyrus.jpg' ?>" class="comment-author-img" />
                            <input type="hidden" name="csrf_token" value="<?= \App\Core\Security::getToken() ?>">
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
                            <div class="card">
                                <div class="card-block">
                                    <p class="card-text"><?= htmlspecialchars($comment['body']) ?></p>
                                </div>
                                <div class="card-footer">
                                    <a href="/profile/<?= htmlspecialchars($comment['author_username']) ?>" class="comment-author">
                                        <img src="<?= $comment['author_image'] ?: '/img/smiley-cyrus.jpg' ?>" class="comment-author-img" />
                                    </a>
                                    &nbsp;
                                    <a href="/profile/<?= htmlspecialchars($comment['author_username']) ?>" class="comment-author"><?= htmlspecialchars($comment['author_username']) ?></a>
                                    <span class="date-posted"><?= date('F jS', strtotime($comment['created_at'])) ?></span>
                                    <?php if (\App\Core\Auth::check() && \App\Core\Auth::userId() == $comment['author_id']): ?>
                                        <span class="mod-options">
                                            <i class="ion-trash-a"
                                               hx-delete="/article/<?= $article['slug'] ?>/comment/<?= $comment['id'] ?>"
                                               hx-target="closest .card"
                                               hx-swap="outerHTML"></i>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
