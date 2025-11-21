<div class="profile-page">
    <div class="user-info">
        <div class="container">
            <div class="row">
                <div class="col-xs-12 col-md-10 offset-md-1">
                    <img src="<?= $profile['image'] ?: '/img/smiley-cyrus.jpg' ?>" class="user-img" />
                    <h4><?= htmlspecialchars($profile['username']) ?></h4>
                    <p><?= htmlspecialchars($profile['bio'] ?? '') ?></p>

                    <?php if (\App\Core\Auth::check() && \App\Core\Auth::userId() == $profile['id']): ?>
                        <a href="/settings" class="btn btn-sm btn-outline-secondary action-btn">
                            <i class="ion-gear-a"></i>
                            &nbsp;
                            Edit Profile Settings
                        </a>
                    <?php elseif (\App\Core\Auth::check()): ?>
                        <button class="btn btn-sm btn-outline-secondary action-btn"
                                hx-post="/profile/<?= htmlspecialchars($profile['username']) ?>/<?= $isFollowing ? 'unfollow' : 'follow' ?>"
                                hx-target="this"
                                hx-swap="outerHTML">
                            <i class="ion-plus-round"></i>
                            &nbsp;
                            <?= $isFollowing ? 'Unfollow' : 'Follow' ?> <?= htmlspecialchars($profile['username']) ?>
                        </button>
                    <?php else: ?>
                        <a href="/login" class="btn btn-sm btn-outline-secondary action-btn">
                            <i class="ion-plus-round"></i>
                            &nbsp;
                            Follow <?= htmlspecialchars($profile['username']) ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="row">
            <div class="col-xs-12 col-md-10 offset-md-1">
                <div class="articles-toggle">
                    <ul class="nav nav-pills outline-active">
                        <li class="nav-item">
                            <a class="nav-link <?= (($activeTab ?? 'my') === 'my') ? 'active' : '' ?>" href="/profile/<?= htmlspecialchars($profile['username']) ?>?tab=my">My Articles</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= (($activeTab ?? 'my') === 'favorites') ? 'active' : '' ?>" href="/profile/<?= htmlspecialchars($profile['username']) ?>?tab=favorites">Favorited Articles</a>
                        </li>
                    </ul>
                </div>

                <?php if (empty($articles)): ?>
                    <div class="article-preview">
                        No articles are here... yet.
                    </div>
                <?php else: ?>
                    <?php foreach ($articles as $article): ?>
                        <?php include __DIR__ . '/partials/article_preview.php'; ?>
                    <?php endforeach; ?>

                    <!-- Pagination -->
                    <nav>
                        <ul class="pagination">
                            <?php if (($offset ?? 0) > 0): ?>
                                <li class="page-item">
                                    <a class="page-link"
                                       href="/profile/<?= htmlspecialchars($profile['username']) ?>?tab=<?= htmlspecialchars($activeTab ?? 'my') ?>&offset=<?= max(0, ($offset ?? 0) - ($limit ?? 20)) ?>">
                                        Previous
                                    </a>
                                </li>
                            <?php endif; ?>
                            <?php if (count($articles) == ($limit ?? 20)): ?>
                                <li class="page-item">
                                    <a class="page-link"
                                       href="/profile/<?= htmlspecialchars($profile['username']) ?>?tab=<?= htmlspecialchars($activeTab ?? 'my') ?>&offset=<?= ($offset ?? 0) + ($limit ?? 20) ?>">
                                        Next
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>