<?php

class ChannelService extends Service {
    private ChannelRepository $channelRepository;
    private VideoRepository $videoRepository;
    private SubscribeRepository $subscribeRepository;

    public function __construct(
        PdoDatabaseAdapter $db
    ) {
        parent::__construct($db);
        $this->channelRepository = new ChannelRepository($db);
        $this->videoRepository = new VideoRepository($db);
        $this->subscribeRepository = new SubscribeRepository($db);
    }

    public function getChannelByChannelId(int $channelId): ?Channel {
        return $this->channelRepository->getChannelByChannelId($channelId);
    }

    public function getVideosByChannelId(Channel $channel): array {
        return $this->videoRepository->getVideosByChannelId($channel);
    }

    public function createChannelPageFromChannelId(int $channelId): ?ChannelPage {
        $channel = $this->getChannelByChannelId((int) $channelId);
        if ($channel === null) {
            return null;
        }
        return $this->createChannelPage($channel);
    }

    public function createChannelPage(Channel $channel) : ?ChannelPage {
        $channelSubcribersCount = $this->channelRepository->getSubcribersCount($channel);
        $videos = $this->getVideosByChannelId($channel);
        $isSubscribe = $this->isSubscribeAChannelWithUser(SessionController::getUser(), $channel);
        $ownerInfo = $this->channelRepository->getChannelOwnerInfo($channel);

        return new ChannelPage(
            $channel,
            $channelSubcribersCount,
            $videos,
            $isSubscribe,
            $ownerInfo['full_name'],
            $ownerInfo['country']
        );
    }

    public function isSubscribeAChannelWithUser(User $user, Channel $channel) : bool {
        if ($user === null) {
            return false;
        }

        return $this->subscribeRepository->isSubscribe($user, $channel);
    }

    public function unsubscribeAChannelWithUser(User $user, Channel $channel) : bool {
        if ($user === null) {
            return false;
        }

        $this->subscribeRepository->unsubscribe($user, $channel);
        return true;
    }

    public function subscribeAChannelWithUser(User $user, Channel $channel) : bool {
        if ($user === null) {
            return false;
        }

        $this->subscribeRepository->subscribe($user, $channel);
        return true;
    }
}
