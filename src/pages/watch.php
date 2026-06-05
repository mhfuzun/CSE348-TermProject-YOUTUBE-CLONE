<?php
/** @var WatchWindow $watchWindow */
/** @var ?User $user */

    $video = $watchWindow->getVideo();
    $comments = $watchWindow->getComments();
?>

<div class="container py-4">
    <div class="mb-4">
        <h1 class="h3 mb-2"><?= View::e($video->getTitle()) ?></h1>
    </div>

    <div class="ratio ratio-16x9 mb-4 bg-body-tertiary border rounded">
        <iframe
            src="https://www.youtube.com/embed/<?= View::e($video->getEmbedId()) ?>"
            title="<?= View::e($video->getTitle()) ?>"
            allowfullscreen
        ></iframe>
    </div>

    <div class="watch-video-tabs mb-4">
        <ul class="nav nav-tabs" id="watchVideoTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="stats-tab" data-bs-toggle="tab" data-bs-target="#stats-pane" type="button" role="tab" aria-controls="stats-pane" aria-selected="true">
                    <i class="bi bi-bar-chart"></i>
                    Stats
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="like-tab" data-bs-toggle="tab" data-bs-target="#like-pane" type="button" role="tab" aria-controls="like-pane" aria-selected="false">
                    <i class="bi bi-hand-thumbs-up"></i>
                    Like
                </button>
            </li>
        </ul>
        <div class="tab-content watch-video-tab-content">
            <div class="tab-pane fade show active" id="stats-pane" role="tabpanel" aria-labelledby="stats-tab" tabindex="0">
                <span class="badge text-bg-primary">
                    <?= View::e($video->getViewBadge()) ?>
                </span>
                <span>
                    <i class="bi bi-eye"></i>
                    <?= View::e($video->getViewCount()) ?> views
                </span>
                <span>
                    <i class="bi bi-hand-thumbs-up"></i>
                    <?= View::e($video->getLikeCount()) ?> likes
                </span>
            </div>
            <div class="tab-pane fade" id="like-pane" role="tabpanel" aria-labelledby="like-tab" tabindex="0">
                <?php if ($user !== null) { ?>
                    <a class="btn btn-primary btn-sm" href="<?= Common::createLinkToSitePage('api.php/likevideo', ['video_id' => $video->getVideoId()]) ?>">
                        <i class="bi bi-hand-thumbs-up"></i>
                        Like this video
                    </a>
                <?php } else { ?>
                    <span class="text-secondary">Login to like this video.</span>
                    <a class="btn btn-outline-primary btn-sm" href="<?= Common::createLinkToSitePage('login.php') ?>">
                        Login
                    </a>
                <?php } ?>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-12 col-lg-4">
            <div class="border rounded p-3">
                <div class="row row-cols-2 row-cols-md-1 g-4">
                    <div class="col">
                        <figure class="watch-channel-photo">
                            <img
                                src="<?= View::e($video->getChannelImage()) ?>"
                                alt="<?= View::e($video->getChannelName()) ?>"
                                class="watch-channel-avatar"
                            >
                        </figure>
                    </div>
                    <div class="col">
                        <dl class="mb-0">
                            <dt>Uploaded By</dt>
                            <dd>
                                <p><a href="<?= View::e($video->getChannelUrl()) ?>" class="link-underline-primary">
                                <i class="bi bi-at"></i><?= View::e($video->getChannelName()) ?>
                                </a></p>
                            </dd>

                            <dt>Duration</dt>
                            <dd>
                                <i class="bi bi-stopwatch"></i>
                                <?= View::e($video->getDurationText()) ?>
                            </dd>

                            <dt>Uploaded At</dt>
                            <dd>
                                <i class="bi bi-calendar"></i>
                                <?= View::e($video->getUploadedAt()) ?>
                            </dd>

                            <dt>Uploader Country</dt>
                            <dd>
                                <i class="bi bi-geo-alt"></i>
                                <?= View::e($video->getUploaderCountry()) ?>
                            </dd>

                            <dt>Views</dt>
                            <dd>
                                <i class="bi bi-eye"></i>
                                <?= View::e($video->getViewCount()) ?>
                            </dd>

                            <dt>Likes</dt>
                            <dd class="mb-0">
                                <i class="bi bi-hand-thumbs-up"></i>
                                <?= View::e($video->getLikeCount()) ?>
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-8">
            <div class="border rounded p-3">
                <h2 class="h5">
                    <i class="bi bi-card-text"></i>
                    Description
                </h2>
                <!-- nl2br: php fonksiyonu ve \n -> <br> dönüşümünü yapar. -->
                <p class="mb-0"><?= nl2br(View::e($video->getDescription())) ?></p>
            </div>
            <div class="border rounded p-3 mt-4">
                <h2 class="h5">
                    <i class="bi bi-chat"></i>
                    Comments
                    <?php if (count($comments) > 0) { ?>
                    <i class="bi bi-dot"></i>
                    <?= count($comments) ?>
                    <?php } ?>
                </h2>
                <?= addCommentBar($video->getVideoId(), $user ?? null) ?>
                <?php if (count($comments) > 0) { ?>
                <div class="comment-contents">
                    <?php
                        foreach ($comments as $comment) {
                            echo commentCard($comment, $user ?? null);
                        }
                    ?>
                </div>
                <?php } else { ?>
                    <div class="alert alert-warning" role="alert">
                        There are no comments yet!
                    </div>
                <?php } ?>
            </div>
        </div>
    </div>
</div>
