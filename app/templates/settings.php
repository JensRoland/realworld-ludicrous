<div class="settings-page">
    <div class="container page">
        <div class="row">
            <div class="col-md-6 offset-md-3 col-xs-12">
                <h1 class="text-xs-center">Your Settings</h1>

                <?php if (isset($error)): ?>
                    <ul class="error-messages">
                        <li><?= htmlspecialchars($error) ?></li>
                    </ul>
                <?php endif; ?>

                <form action="/settings" method="POST">
                    <fieldset>
                        <fieldset class="form-group">
                            <input class="form-control" type="text" name="image" placeholder="URL of profile picture" value="<?= htmlspecialchars($user['image'] ?? '') ?>">
                        </fieldset>
                        <fieldset class="form-group">
                            <input class="form-control form-control-lg" type="text" name="username" placeholder="Your Name" value="<?= htmlspecialchars($user['username'] ?? '') ?>">
                        </fieldset>
                        <fieldset class="form-group">
                            <textarea class="form-control form-control-lg" rows="8" name="bio" placeholder="Short bio about you"><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
                        </fieldset>
                        <fieldset class="form-group">
                            <input class="form-control form-control-lg" type="text" name="email" placeholder="Email" value="<?= htmlspecialchars($user['email'] ?? '') ?>">
                        </fieldset>
                        <fieldset class="form-group">
                            <input class="form-control form-control-lg" type="password" name="password" placeholder="Password">
                        </fieldset>
                        <input type="hidden" name="csrf_token" value="<?= \App\Lib\Security::getToken() ?>">
                        <button class="btn btn-lg btn-primary pull-xs-right">
                            Update Settings
                        </button>
                    </fieldset>
                </form>
                <hr>
                <a href="/logout" class="btn btn-outline-danger">
                    Or click here to logout.
                </a>
            </div>
        </div>
    </div>
</div>