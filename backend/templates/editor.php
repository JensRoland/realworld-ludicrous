<div class="editor-page">
    <div class="container page">
        <div class="row">
            <div class="col-md-10 offset-md-1 col-xs-12">
                <?php if (isset($error)): ?>
                    <ul class="error-messages">
                        <li><?= htmlspecialchars($error) ?></li>
                    </ul>
                <?php endif; ?>

                <form action="<?= isset($article) ? '/editor/' . $article['slug'] : '/editor' ?>" method="POST">
                    <fieldset>
                        <fieldset class="form-group">
                            <input type="text" class="form-control form-control-lg" name="title" placeholder="Article Title" value="<?= htmlspecialchars($article['title'] ?? '') ?>" required>
                        </fieldset>
                        <fieldset class="form-group">
                            <input type="text" class="form-control" name="description" placeholder="What's this article about?" value="<?= htmlspecialchars($article['description'] ?? '') ?>" required>
                        </fieldset>
                        <fieldset class="form-group">
                            <textarea class="form-control" rows="8" name="body" placeholder="Write your article (in markdown)" required><?= htmlspecialchars($article['body'] ?? '') ?></textarea>
                        </fieldset>
                        <fieldset class="form-group">
                            <input type="text" class="form-control" name="tags" placeholder="Enter tags" value="<?= isset($article['tagList']) ? htmlspecialchars(implode(',', $article['tagList'])) : '' ?>"><div class="tag-list"></div>
                        </fieldset>
                        <input type="hidden" name="csrf_token" value="<?= \App\Core\Security::getToken() ?>">
                        <button class="btn btn-lg pull-xs-right btn-primary" type="submit">
                            Publish Article
                        </button>
                    </fieldset>
                </form>
            </div>
        </div>
    </div>
</div>
