<?php

class FeedService extends Service {
    private AuthService $authService;
    private ChannelRepository $channelRepository;
    private VideoRepository $videoRepository;

    public function __construct(
        PdoDatabaseAdapter $db
    ) {
        parent::__construct($db);
        $this->authService = new AuthService($db);
        $this->channelRepository = new ChannelRepository($db);
        $this->videoRepository = new VideoRepository($db);
    }

    public function createFeedVideoList_Subscribed() : array {
        if (!SessionController::isLogined()) {
            return [];
        }

        return $this->videoRepository->getSubscribedChannelsVideosWithUser(
            SessionController::getUser()
        );
    }

    public function createFeedVideoList_Top(int $channelLimit = 10) : array {
        return $this->videoRepository->getMostSubscribedChannelVideos($channelLimit);
    }

    public function createTopChannelList(int $channelLimit = 5) : array {
        return $this->channelRepository->getTopChannelsWithSubscriberCount($channelLimit);
    }
}
