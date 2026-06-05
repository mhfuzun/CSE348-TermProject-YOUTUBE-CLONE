<?php function videoBlock(VideoContent $video) { ?>
    <?php $watchLink = Common::createLinkToSitePage('watch.php', ['video_id' => $video->getVideoId()]); ?>
    <?php $channelLink = Common::createLinkToSitePage('channel.php', ['channel_id' => $video->getChannelId()]); ?>

    <article class="card video-block">
        <a class="video-block-media" href="<?= $watchLink ?>" aria-label="<?= View::e($video->getTitle()) ?>">
            <div class="video-card-image">
                <img
                    src="https://img.youtube.com/vi/<?= View::e($video->getEmbedId()) ?>/hqdefault.jpg"
                    alt="<?= View::e($video->getTitle()) ?>"
                >

                <div class="video-card-play">
                    <i class="bi bi-play-fill"></i>
                </div>

                <div class="video-card-duration">
                    <span class="badge rounded-pill text-bg-primary">
                        <i class="bi bi-stopwatch"></i>
                        <?= View::e($video->getDurationText()) ?>
                    </span>
                </div>
            </div>
        </a>

        <div class="card-body video-block-body">
            <h5 class="card-title video-block-title">
                <a href="<?= $watchLink ?>">
                    <?= View::e($video->getTitle()) ?>
                </a>
            </h5>

            <div class="channel-info">
                <a class="channel-image-small" href="<?= $channelLink ?>">
                    <img
                        src="<?= View::e($video->getChannelImage()) ?>"
                        alt="<?= View::e($video->getChannelName()) ?>"
                        class="avatar"
                    >
                </a>

                <div class="channel-meta">
                    <a class="channel-name" href="<?= $channelLink ?>">
                        <?= View::e($video->getChannelName()) ?>
                    </a>
                </div>
            </div>

            <div class="video-block-meta">
                <span>
                    <i class="bi bi-geo-alt"></i>
                    <?= View::e($video->getUploaderCountry()) ?>
                </span>
                <span>
                    <i class="bi bi-clock"></i>
                    <?php
                        $uploadedAt = new DateTime($video->getUploadedAt());
                        $today = new DateTime();
                        echo View::e($uploadedAt->diff($today)->days);
                    ?>
                    days ago
                </span>
            </div>

            <p class="card-text video-block-description">
                <?= View::e($video->getDescriptionShort(120)) ?>
            </p>
        </div>
    </article>
<?php } ?>
