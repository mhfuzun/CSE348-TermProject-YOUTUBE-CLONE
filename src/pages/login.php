<?php
    $error = Common::get('error');
    $reason = Common::get('reason');
?>

<div class="container auth-page">
    <div class="row justify-content-center">
        <div class="col-12 col-sm-10 col-md-6 col-lg-4">

            <div class="card shadow-sm">
                <div class="card-body p-4">

                    <h1 class="h3 fw-bold text-center mb-2">MiniTube</h1>
                    <p class="text-secondary text-center mb-4">
                        Sign in to your account
                    </p>

                    <?php if ($error === 'invalid') { ?>
                        <div class="alert alert-danger">
                            Login failed: <?= View::e($reason ?? 'unknown') ?>
                        </div>
                    <?php } else if ($error === 'valid') { ?>
                        <div class="alert alert-success">
                            <?= View::e($reason ?? 'success') ?>
                        </div>
                    <?php } ?>

                    <form action="login.php" method="post">
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input
                                type="text"
                                class="form-control"
                                id="username"
                                name="username"
                                autocomplete="username"
                                required
                            >
                        </div>

                        <div class="mb-4">
                            <label for="password" class="form-label">Password</label>
                            <input
                                type="password"
                                class="form-control"
                                id="password"
                                name="password"
                                autocomplete="current-password"
                                required
                            >
                        </div>

                        <div class="form-check mb-4">
                            <input
                                class="form-check-input"
                                type="checkbox"
                                value="1"
                                id="rememberme"
                                name="rememberme"
                            >
                            <label class="form-check-label" for="rememberme">
                                Remember me!
                            </label>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 py-2">
                            Login
                        </button>
                    </form>

                    <hr class="my-4">

                    <div class="text-center">
                        <p class="text-secondary mb-2">
                            Don't have an account?
                        </p>

                        <a href="<?= Common::createLinkToSitePage('register.php') ?>" class="btn btn-outline-primary w-100">
                            Register
                        </a>
                    </div>

                </div>
            </div>

        </div>
    </div>
</div>
