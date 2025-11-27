<div class="home-page">
    <div class="banner">
        <div class="container">
            <h1 class="logo-font">conduit</h1>
            <p>A place to share your knowledge.</p>
        </div>
    </div>

    <div class="container page">
        <div class="row">
            <div class="col-md-9">
                <div class="feed-toggle">
                    <ul class="nav nav-pills outline-active">
                        <li class="nav-item">
                            <?php if (\App\Lib\Auth::check()): ?>
                                <a class="nav-link <?= $activeFeed === 'your' ? 'active' : '' ?>" href="/?feed=your">Your Feed</a>
                            <?php else: ?>
                                <a class="nav-link disabled" href="/login">Your Feed</a>
                            <?php endif; ?>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= ($activeFeed === 'global' && !$activeTag) ? 'active' : '' ?>" href="/">Global Feed</a>
                        </li>
                        <?php if ($activeTag): ?>
                            <li class="nav-item">
                                <a class="nav-link active" href="">#<?= htmlspecialchars($activeTag) ?></a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>

                <?php if (empty($articles)): ?>
                    <div class="article-preview">
                        No articles are here... yet.
                    </div>
                <?php else: ?>
                    <?php foreach ($articles as $article): ?>
                        <?php \App\Components\ArticlePreview\render($article); ?>
                    <?php endforeach; ?>

                    <!-- Pagination -->
                    <nav>
                        <ul class="pagination">
                            <?php if ($offset > 0): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?offset=<?= max(0, $offset - $limit) ?>&feed=<?= $activeFeed ?><?= $activeTag ? '&tag=' . $activeTag : '' ?>">Previous</a>
                                </li>
                            <?php endif; ?>
                            <?php if (count($articles) == $limit): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?offset=<?= $offset + $limit ?>&feed=<?= $activeFeed ?><?= $activeTag ? '&tag=' . $activeTag : '' ?>">Next</a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>

            <div class="col-md-3">
                <div class="sidebar">
                    <p>Popular Tags</p>
                    <div class="tag-list">
                        <?php foreach ($tags as $tag): ?>
                            <a href="/?tag=<?= htmlspecialchars($tag) ?>" class="tag-pill tag-default"><?= htmlspecialchars($tag) ?></a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
