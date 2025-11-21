<div class="error-page">
    <div class="container page">
        <div class="row">
            <div class="col-md-12">
                <h1>404 - Not Found</h1>
                <?php if (!empty($path)): ?>
                    <p>The requested path "<?= htmlspecialchars($path) ?>" could not be found.</p>
                <?php else: ?>
                    <p>The page you are looking for could not be found.</p>
                <?php endif; ?>
                <p><a href="/">Return to the home page</a></p>
            </div>
        </div>
    </div>
</div>