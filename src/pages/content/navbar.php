<?php
/** @var User $user */
?>

<nav class="navbar bg-body-tertiary">
    <div class="container-fluid">
        <a class="navbar-brand" href="<?= Common::createLinkToSitePage('feed.php') ?>">
            <img src="favicon.ico" alt="Logo" width="30" height="24" class="d-inline-block align-text-top">
            MiniTube
        </a>
        <div class="d-flex align-items-center gap-2">
            <a class="btn btn-outline-secondary" href="/sql.php">SQL</a>
            <?php if ($user !== null) { ?>
            <div class="dropdown">
                <button class="btn btn-outline-success dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-person-circle"></i>
                    <?= View::e($user->getUsername()) ?>
                </button>
                <ul class="dropdown-menu dropdown-menu-lg-end">
                    <li><a class="dropdown-item" href="<?= Common::createLinkToSitePage('user.php') ?>">Profile</a></li>
                    <li><a class="dropdown-item" href="<?= Common::createLinkToSitePage('logout.php') ?>">Logout</a></li>
                </ul>
            </div>
            <?php } else { ?>
            <a class="btn btn-outline-success" href="/login.php">Login</a>
            <?php } ?>
        </div>
    </div>
</nav>
