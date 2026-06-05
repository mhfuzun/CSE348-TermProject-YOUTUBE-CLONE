<?php

class FeedPage {
    private array $subscribedVideos;
    private array $topChannelVideos;
    private array $topChannels;

    public function __construct(
        array $subscribedVideos,
        array $topChannelVideos,
        array $topChannels = []
    ) {
        $this->subscribedVideos = $subscribedVideos;
        $this->topChannelVideos = $topChannelVideos;
        $this->topChannels = $topChannels;
    }

    public function getSubscribedVideos() : array {
        return $this->subscribedVideos;
    }

    public function getTopChannelVideos() : array {
        return $this->topChannelVideos;
    }

    public function getTopChannels() : array {
        return $this->topChannels;
    }
}
