<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="csrf-token" content="<?= \App\Lib\Security::getToken() ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Conduit</title>
    <link rel="stylesheet" href="/css/fonts.css">
    <link rel="stylesheet" href="/css/main.css">
    <link rel="stylesheet" href="/css/icons.css">
    <script src="/js/htmx.min.js"></script>
    <script>
        // Inject CSRF header for all HTMX requests
        document.addEventListener('htmx:configRequest', function (event) {
            var tokenMeta = document.querySelector('meta[name="csrf-token"]');
            if (tokenMeta) {
                event.detail.headers['X-CSRF-Token'] = tokenMeta.getAttribute('content') || '';
            }
        });
    </script>
</head>
<body hx-boost="true">
    <nav class="navbar navbar-light">
        <div class="container">
            <a class="navbar-brand" href="/">conduit</a>
            <ul class="nav navbar-nav pull-xs-right">
                <li class="nav-item">
                    <a class="nav-link<?= ($currentPage ?? '') === 'home' ? ' active' : '' ?>" href="/">Home</a>
                </li>
                <?php $currentUser = \App\Lib\Auth::user(); ?>
                <?php if ($currentUser): ?>
                    <li class="nav-item">
                        <a class="nav-link<?= ($currentPage ?? '') === 'editor' ? ' active' : '' ?>" href="/editor">
                            <i class="ion-compose"></i>&nbsp;New Article
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link<?= ($currentPage ?? '') === 'settings' ? ' active' : '' ?>" href="/settings">
                            <i class="ion-gear-a"></i>&nbsp;Settings
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link<?= ($currentPage ?? '') === 'profile' ? ' active' : '' ?>" href="/profile/<?= htmlspecialchars($currentUser['username']) ?>">
                            <?php if (!empty($currentUser['image'])): ?>
                                <img src="<?= htmlspecialchars($currentUser['image']) ?>" class="user-pic" alt="<?= htmlspecialchars($currentUser['username']) ?>">
                            <?php endif; ?>
                            <?= htmlspecialchars($currentUser['username']) ?>
                        </a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link<?= ($currentPage ?? '') === 'login' ? ' active' : '' ?>" href="/login">Sign in</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link<?= ($currentPage ?? '') === 'register' ? ' active' : '' ?>" href="/register">Sign up</a>
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
            <a href="/" class="logo-font">conduit</a>
            <span class="attribution">
                An interactive learning project from <a href="https://thinkster.io">Thinkster</a>. Code &amp; design licensed under MIT.
            </span>
        </div>
    </footer>
</body>
</html>
