<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="csrf-token" content="<?= \App\Lib\Security::getToken() ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Conduit</title>
    <meta name="description" content="A place to share your knowledge.">
    <link rel="preload" href="/fonts/source-sans-pro-regular.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="/fonts/source-sans-pro-v22-latin-300.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="/fonts/source-sans-pro-v22-latin-600.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="/fonts/titillium-web-v17-latin-700.woff2" as="font" type="font/woff2" crossorigin>
    <?php if (($currentPage ?? '') === 'article'): ?>
    <link rel="preload" href="/fonts/source-serif-pro-regular.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="/fonts/source-serif-pro-bold.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="/fonts/source-sans-pro-v22-latin-700.woff2" as="font" type="font/woff2" crossorigin>
    <?php endif; ?>
    <?= \App\Lib\Vite::assets() ?>
</head>
<body>
    <nav class="navbar navbar-light">
        <div class="container">
            <a fx-yolo-deep class="navbar-brand" href="/">conduit</a>
            <ul class="nav navbar-nav pull-xs-right">
                <li class="nav-item">
                    <a fx-yolo-deep class="nav-link<?= ($currentPage ?? '') === 'home' ? ' active' : '' ?>" href="/">Home</a>
                </li>
                <?php $currentUser = \App\Lib\Auth::user(); ?>
                <?php if ($currentUser): ?>
                    <li class="nav-item">
                        <a fx-yolo-deep class="nav-link<?= ($currentPage ?? '') === 'editor' ? ' active' : '' ?>" href="/editor">
                            <i class="ion-compose"></i>&nbsp;New Article
                        </a>
                    </li>
                    <li class="nav-item">
                        <a fx-yolo-deep class="nav-link<?= ($currentPage ?? '') === 'settings' ? ' active' : '' ?>" href="/settings">
                            <i class="ion-gear-a"></i>&nbsp;Settings
                        </a>
                    </li>
                    <li class="nav-item">
                        <a fx-yolo-deep class="nav-link<?= ($currentPage ?? '') === 'profile' ? ' active' : '' ?>" href="/profile/<?= htmlspecialchars($currentUser['username']) ?>" title="User Profile">
                            <?php if (!empty($currentUser['image'])): ?>
                                <img src="<?= htmlspecialchars($currentUser['image']) ?>" class="user-pic" alt="<?= htmlspecialchars($currentUser['username']) ?>" alt="author avatar">
                            <?php endif; ?>
                            <?= htmlspecialchars($currentUser['username']) ?>
                        </a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a fx-yolo-deep class="nav-link<?= ($currentPage ?? '') === 'login' ? ' active' : '' ?>" href="/login">Sign in</a>
                    </li>
                    <li class="nav-item">
                        <a fx-yolo-deep class="nav-link<?= ($currentPage ?? '') === 'register' ? ' active' : '' ?>" href="/register">Sign up</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>

    <?php if (isset($content_template)): ?>
        <?php \App\Lib\View::render($content_template, $data ?? []); ?>
    <?php endif; ?>

    <footer>
        <div class="container">
            <a fx-yolo-deep href="/" class="logo-font">conduit</a>
            <span class="attribution">
                An interactive learning project from <a href="https://thinkster.io">Thinkster</a>. Code &amp; design licensed under MIT.
            </span>
            <ul class="nav navbar-nav pull-xs-right">
                <li class="nav-item">
                    <a class="nav-link" href="https://github.com/JensRoland/realworld-ludicrous"><i class="ion-social-github"></i> Source code</a>
                </li>
            </ul>
        </div>
    </footer>
    <script>
        // Inject CSRF token into all boosti requests
        document.addEventListener('fx:config', (e) => {
            const token = document.querySelector('meta[name="csrf-token"]')?.content;
            if (token) e.detail.cfg.headers['X-CSRF-Token'] = token;
        });
    </script>
</body>
</html>
