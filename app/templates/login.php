<div class="auth-page">
    <div class="container page">
        <div class="row">
            <div class="col-md-6 offset-md-3 col-xs-12">
                <h1 class="text-xs-center">Sign in</h1>
                <p class="text-xs-center">
                    <a href="/register">Need an account?</a>
                </p>

                <?php if (isset($error)): ?>
                    <ul class="error-messages">
                        <li><?= htmlspecialchars($error) ?></li>
                    </ul>
                <?php endif; ?>

                <form action="/login" method="POST" hx-boost="false">
                    <fieldset class="form-group">
                        <input class="form-control form-control-lg" type="email" name="email" placeholder="Email" required>
                    </fieldset>
                    <fieldset class="form-group">
                        <input class="form-control form-control-lg" type="password" name="password" placeholder="Password" required>
                    </fieldset>
                    <input type="hidden" name="csrf_token" value="<?= \App\Lib\Security::getToken() ?>">
                    <button class="btn btn-lg btn-primary pull-xs-right">
                        Sign in
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
