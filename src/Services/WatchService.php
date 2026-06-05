<?php

class WatchService extends Service {
    private VideoRepository $videoRepository;

    public function __construct(
        PdoDatabaseAdapter $db
    ) {
        parent::__construct($db);
        $this->videoRepository = new VideoRepository($db);
    }

    public function getVideoByVideoId(int $videoId): ?VideoContent {
        return $this->videoRepository->getVideoByVideoId($videoId);
    }

    public function increaseViewCount(int $videoId): bool {
        return $this->videoRepository->increaseViewCount($videoId);
    }

    public function increaseLikeCount(int $videoId): bool {
        return $this->videoRepository->increaseLikeCount($videoId);
    }
}
