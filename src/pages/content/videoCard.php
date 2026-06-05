<?php function videoCard(Video $video) { ?>
<a class="video-card" href="<?= Common::createLinkToSitePage('watch.php', ['video_id' => $video->getVideoId()]) ?>">
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

    <div class="video-card-info">
        <div class="video-card-title">
            <?= View::e($video->getTitle()) ?>
        </div>

        <div class="video-card-description">
            <?= View::e($video->getDescriptionShort()) ?>
        </div>

        <div class="video-card-meta">
            <span class="video-card-watch-count">
                <i class="bi bi-eye"></i>
                <?= View::e($video->getViewCount()) ?>
                views 
                <i class="bi bi-dot"></i>
                <?= View::e($video->getUploadedTimeText()) ?>
            </span>
        </div>
    </div>
</a>
<?php } ?>