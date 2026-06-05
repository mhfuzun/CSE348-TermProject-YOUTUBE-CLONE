<?php

class ControlFeed {
    private Controller $controller;
    private FeedService $feedService;

    public function __construct(Controller $controller) {
        $this->controller = $controller;
        $this->feedService = new FeedService($this->controller->getDb());
    }

    public function getFeed() : ?View {
        $subscribedVideos = [];
        $topChannelVideos = [];
        $topChannels = [];

        if (SessionController::isLogined()) {
            $subscribedVideos = $this->feedService->createFeedVideoList_Subscribed();
        }

        $topChannelVideos = $this->feedService->createFeedVideoList_Top();
        $topChannels = $this->feedService->createTopChannelList(5);

        $feedPage = new FeedPage(
            $subscribedVideos,
            $topChannelVideos,
            $topChannels
        );

        return new View(
            'feed',
            'Feed',
            [
                'feedPage' => $feedPage
            ]
        );
    }
}
