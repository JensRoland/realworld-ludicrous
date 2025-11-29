<div class="auth-page">
    <div class="container page">
        <div class="row">
            <div class="col-md-6 offset-md-3 col-xs-12">
                <h1 class="text-xs-center">Sign up</h1>
                <p class="text-xs-center">
                    <a href="/login">Have an account?</a>
                </p>

                <?php if (isset($error)): ?>
                    <ul class="error-messages">
                        <li><?= htmlspecialchars($error) ?></li>
                    </ul>
                <?php endif; ?>

                <form action="/register" method="POST" fx-boost="false">
                    <fieldset class="form-group">
                        <input class="form-control form-control-lg" type="text" name="username" placeholder="Username" autocomplete="username" required>
                    </fieldset>
                    <fieldset class="form-group">
                        <input class="form-control form-control-lg" type="email" name="email" placeholder="Email" autocomplete="email" required>
                    </fieldset>
                    <fieldset class="form-group">
                        <input class="form-control form-control-lg" type="password" name="password" placeholder="Password" autocomplete="current-password" required>
                    </fieldset>
                    <input type="hidden" name="csrf_token" value="<?= \App\Lib\Security::getToken() ?>">
                    <button class="btn btn-lg btn-primary pull-xs-right">
                        Sign up
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
