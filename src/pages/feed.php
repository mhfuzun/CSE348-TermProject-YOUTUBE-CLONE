<?php
/** @var User $user */
/** @var FeedPage $feedPage */
?>

<div class="container py-4">
    <!-- page head -->
    <div class="page-head">
        <div class="login-handler">
            <?php if ($user !== null) { ?>
            <h3 class="welcome-title">
                Hello, <span style="color: #FFF; background-color: #F00">
                    <?= View::e($user->getFullName()) ?></span>!
            </h3>
            <?php } else { ?>
            <h3 class="welcome-title">
                Welcome to <span style="color: #FFF; background-color: #F00">
                    MiniTube</span>!
            </h3>
            <a class="btn btn-primary" href="/login.php" role="button">Login</a>
            <?php } ?>
        </div>
    </div>

    <!-- page body -->
    <div class="page-body-subscribed">
        <h3>Subscribed</h3>
        <div class="video-wrap-layout">
            <?php 
                foreach ($feedPage->getSubscribedVideos() as $video) {
                    echo videoBlock($video);
                }
            ?>
        </div>
    </div>

    <div class="page-body-top-channels">
        <h3>Top Channels</h3>
        <div class="top-channel-strip">
            <?php foreach ($feedPage->getTopChannels() as $topChannel) { ?>
                <?php
                    $channel = $topChannel['channel'];
                    $subscriberCount = $topChannel['subscriber_count'];
                ?>
                <a class="top-channel-card" href="<?= Common::createLinkToSitePage('channel.php', ['channel_id' => $channel->getChannelId()]) ?>">
                    <img
                        src="<?= View::e($channel->getChannelImage()) ?>"
                        alt="<?= View::e($channel->getName()) ?>"
                    >
                    <strong><?= View::e($channel->getName()) ?></strong>
                    <span><?= View::e($subscriberCount) ?> subscribers</span>
                </a>
            <?php } ?>
        </div>

        <div class="video-wrap-layout">
            <?php 
                foreach ($feedPage->getTopChannelVideos() as $video) {
                    echo videoBlock($video);
                }
            ?>
        </div>
    </div>

    <section class="feed-user-section">
        <h3>User Profile</h3>
        <?php if ($user !== null) { ?>
            <div class="feed-user-panel">
                <img
                    src="<?= View::e($user->getUserImage()) ?>"
                    alt="<?= View::e($user->getUsername()) ?>"
                    class="feed-user-image"
                >

                <div class="feed-user-info">
                    <div class="feed-user-name">
                        <?= View::e($user->getFullName()) ?>
                    </div>
                    <div class="feed-user-username">
                        <i class="bi bi-person"></i>
                        <?= View::e($user->getUsername()) ?>
                    </div>

                    <div class="feed-user-details">
                        <div>
                            <span>Country</span>
                            <strong><?= View::e($user->getCountry()) ?></strong>
                        </div>
                        <div>
                            <span>Join Date</span>
                            <strong><?= View::e($user->getJoinedOn()) ?></strong>
                        </div>
                        <div>
                            <span>Email</span>
                            <strong><?= View::e($user->getEmail()) ?></strong>
                        </div>
                    </div>

                    <p class="feed-user-bio">
                        <?= View::e($user->getBio()) ?>
                    </p>
                </div>
            </div>
        <?php } else { ?>
            <div class="alert alert-secondary mb-0">
                Login to see your profile information.
            </div>
        <?php } ?>
    </section>

</div>
