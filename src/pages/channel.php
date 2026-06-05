<?php
/** @var User $user */
/** @var ChannelPage $channelPage */

$videos = $channelPage->getVideos();

$lastUploadedVideos = $videos;
usort($lastUploadedVideos, function ($a, $b) {
    return strcmp($b->getUploadedAt(), $a->getUploadedAt());
});

$firstUploadedVideos = $videos;
usort($firstUploadedVideos, function ($a, $b) {
    return strcmp($a->getUploadedAt(), $b->getUploadedAt());
});

$mostViewedVideos = $videos;
usort($mostViewedVideos, function ($a, $b) {
    return $b->getViewCount() <=> $a->getViewCount();
});

$mostPopularVideos = $videos;
usort($mostPopularVideos, function ($a, $b) {
    return $b->getLikeCount() <=> $a->getLikeCount();
});

function channelVideoList(array $videos) {
    if (count($videos) === 0) {
        echo '<div class="alert alert-warning mb-0">No videos in this channel.</div>';
        return;
    }

    echo '<div class="video-cards">';
    foreach ($videos as $video) {
        echo videoCard($video);
    }
    echo '</div>';
}
?>

<div class="container py-4">
    <style>
        .channel-page {
            display: flex;
            flex: 1;
            flex-direction: column;
        }

        .channel-header {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .channel-info {
            display: flex;
            flex-direction: row;
            align-items: center;
            /*justify-content: space-between;*/
            margin-bottom: 20px;
        }

        .channel-image {
            border-radius: 50%;
            overflow: hidden;
            margin-right: 20px;
        }

        .channel-attr {
            display: flex;
            flex-direction: column;
            margin-left: 58px;
        }

        .channel-title {
            font-size: 45px;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .channel-description {
            font-size: 20px;
        }

        .channel-createdOn {
            font-size: 20px;
            font-style: italic;
        }

        .channel-category {
            font-size: 20px;
        }
    </style>
    <div class="channel-page">
        <div class="channel-header">
            <div class="channel-info">
                <div class="channel-image">
                    <div class="pp-image">
                        <img src="<?= View::e($channelPage->getChannel()->getChannelImage()) ?>" alt="Avatar" class="avatar">
                    </div>
                </div>
                <div class="channel-attr">
                    <div class="channel-title"><?= View::e($channelPage->getChannel()->getName()) ?></div>
                    <div class="channel-subscribers">
                        <i class="bi bi-people"></i>
                        <?= View::e($channelPage->getSubcribersCount()) ?> subscribers
                    </div>
                    <div class="channel-owner">
                        Owner:
                        <?= View::e($channelPage->getOwnerFullName()) ?>
                        <?php if ($channelPage->getOwnerCountry() !== '') { ?>
                            (<?= View::e($channelPage->getOwnerCountry()) ?>)
                        <?php } ?>
                    </div>
                    <div class="channel-description"><?= View::e($channelPage->getChannel()->getDescription()) ?></div>
                    <hr class="my-2">
                    <div class="channel-createdOn"><?= View::e($channelPage->getChannel()->getCreatedOn()) ?></div>
                    <div class="channel-category">
                        <span class="badge text-bg-success">
                            #<?= View::e($channelPage->getChannel()->getCategory()) ?>
                        </span>
                    </div>

                    <?php if ($user !== null && $channelPage->getIsSubscribe() === true) { ?>
                    <div class="subscribe-button mt-4">
                        <a class="btn btn-primary" href="<?= Common::createLinkToSitePage('api.php/unsubscribe', ['channel_id' => $channelPage->getChannel()->getChannelId()]) ?>" role="button">
                            <i class="bi bi-bell-slash"></i>
                            Unsubscribe
                        </a>
                    </div>
                    <?php } else if ($user !== null && $channelPage->getIsSubscribe() === false) { ?>
                    <div class="subscribe-button mt-4">
                        <a class="btn btn-primary" href="<?= Common::createLinkToSitePage('api.php/subscribe', ['channel_id' => $channelPage->getChannel()->getChannelId()]) ?>" role="button">
                            <i class="bi bi-bell"></i>
                            Subscribe
                        </a>
                    </div>
                    <?php } else { ?>
                    <div class="subscribe-button mt-4">
                        <a class="btn btn-primary" href="<?= Common::createLinkToSitePage('login.php') ?>" role="button">
                            <i class="bi bi-bell-slash"></i>
                            Login&nbsp;to&nbsp;subscribe
                        </a>
                    </div>
                    <?php } ?>
                </div>
            </div>
        </div>
        <div class="hot-contents">
        </div>
        <div class="channel-videos">
            <ul class="nav nav-tabs">
                <li class="nav-item">
                    <button class="nav-link active" data-bs-toggle="tab" type="button" role="tab" data-bs-target="#lastUploaded">Last Uploaded</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" type="button" role="tab" data-bs-target="#mostPolular">Most Popular</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" type="button" role="tab" data-bs-target="#firstUploaded">First Uploaded</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" type="button" role="tab" data-bs-target="#mostViewed">Most Viewed</button>
                </li>
            </ul>
            <div class="tab-content mt-3" id="myTabContent">
                <div 
                    class="tab-pane fade show active" 
                    id="lastUploaded" 
                    role="tabpanel">
                    <?php channelVideoList($lastUploadedVideos); ?>
                </div>
                <div 
                    class="tab-pane fade" 
                    id="mostPolular" 
                    role="tabpanel">
                    <?php channelVideoList($mostPopularVideos); ?>
                </div>
                <div 
                    class="tab-pane fade" 
                    id="firstUploaded" 
                    role="tabpanel">
                    <?php channelVideoList($firstUploadedVideos); ?>
                </div>
                <div 
                    class="tab-pane fade" 
                    id="mostViewed" 
                    role="tabpanel">
                    <?php channelVideoList($mostViewedVideos); ?>
                </div>
            </div>
        </div>
    </div>
</div>
