<?php

class VideoContent extends Video {
    private string $channelName;
    private string $channelImage;
    private string $viewBadge;
    private string $uploaderCountry;
    
    public function __construct(
        Video $video,
        string $channelName,
        string $channelImage,
        string $viewBadge = 'New',
        string $uploaderCountry = ''
    ) {
        parent::__construct(
            $video->getVideoId(),
            $video->getChannelId(),
            $video->getTitle(),
            $video->getDescription(),
            $video->getUrl(),
            $video->getDurationSeconds(),
            $video->getUploadedAt(),
            $video->getViewCount(),
            $video->getLikeCount()
        );

        $this->channelName = $channelName;
        $this->channelImage = $channelImage;
        $this->viewBadge = $viewBadge;
        $this->uploaderCountry = $uploaderCountry;
    }

    public function getChannelName(): string {
        return $this->channelName;
    }

    public function getChannelImage(): string {
        return $this->channelImage;
    }

    public function getChannelUrl() : string {
        return Common::createLinkToSitePage('channel.php', ['channel_id' => $this->getChannelId()]);
    }

    public function getViewBadge(): string {
        return $this->viewBadge;
    }

    public function getUploaderCountry(): string {
        return $this->uploaderCountry;
    }
    
}
