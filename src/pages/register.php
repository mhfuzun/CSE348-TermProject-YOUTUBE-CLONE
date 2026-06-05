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
                        Create your account
                    </p>

                    <?php if ($error === 'invalid') { ?>
                        <div class="alert alert-danger">
                            Register failed: <?= View::e($reason ?? 'unknown') ?>
                        </div>
                    <?php } else if ($error === 'valid') { ?>
                        <div class="alert alert-success">
                            Register success: <?= View::e($reason ?? 'created') ?>
                        </div>
                    <?php } ?>

                    <form action="register.php" method="post">
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

                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input
                                type="email"
                                class="form-control"
                                id="email"
                                name="email"
                                autocomplete="email"
                                required
                            >
                        </div>

                        <div class="mb-3">
                            <label for="fullName" class="form-label">Full Name</label>
                            <input
                                type="text"
                                class="form-control"
                                id="fullName"
                                name="full_name"
                                autocomplete="name"
                                required
                            >
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input
                                type="password"
                                class="form-control"
                                id="password"
                                name="password"
                                autocomplete="new-password"
                                required
                            >
                        </div>

                        <div class="mb-4">
                            <label for="passwordConfirm" class="form-label">Confirm Password</label>
                            <input
                                type="password"
                                class="form-control"
                                id="passwordConfirm"
                                name="password_confirm"
                                autocomplete="new-password"
                                required
                            >
                        </div>

                        <button type="submit" class="btn btn-primary w-100 py-2">
                            Register
                        </button>
                    </form>

                    <hr class="my-4">

                    <div class="text-center">
                        <p class="text-secondary mb-2">
                            Already have an account?
                        </p>

                        <a href="<?= Common::createLinkToSitePage('login.php') ?>" class="btn btn-outline-primary w-100">
                            Login
                        </a>
                    </div>

                </div>
            </div>

        </div>
    </div>
</div>
