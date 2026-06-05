<?php

class VideoRepository extends Repository {
    private string $tableName;

    public function __construct(
        DatabaseAdapterInterface $db
    ) {
        parent::__construct($db);
        $this->tableName = database_cfg::$VIDEO_TABLE_NAME;
    }

    public static function fromTableName(DatabaseAdapterInterface $db, string $tableName): VideoRepository {
        $videoRepository = new VideoRepository($db);
        $videoRepository->tableName = $tableName;

        return $videoRepository;
    }

    public function getVideoByVideoId(int $videoId): ?VideoContent {
        $pdo = $this->getDb()->getPDO();

        $sql = "SELECT 
            `VIDEOS`.`video_id`,
            `VIDEOS`.`channel_id`,
            `VIDEOS`.`title`,
            `VIDEOS`.`description`,
            `VIDEOS`.`url`,
            `VIDEOS`.`duration_seconds`,
            `VIDEOS`.`uploaded_at`,
            `VIDEOS`.`view_count`,
            `VIDEOS`.`like_count`,
            IF(
                `VIDEOS`.`view_count` >= 1000,
                'Popular',
                IF(`VIDEOS`.`view_count` >= 100, 'Trending', 'New')
            ) AS `view_badge`,
            `CHANNELS`.`name` AS `channel_name`,
            `CHANNELS`.`channel_image`,
            `USERS`.`country` AS `uploader_country`
            FROM `VIDEOS` JOIN `CHANNELS`
                ON `CHANNELS`.`channel_id` = `VIDEOS`.`channel_id`
            JOIN `USERS`
                ON `USERS`.`user_id` = `CHANNELS`.`owner_id`
            WHERE `VIDEOS`.`video_id` = :VIDEOID
            LIMIT 1";

        $statement = $pdo->prepare($sql);
        $statement->bindValue(':VIDEOID', (int) $videoId, PDO::PARAM_INT);
        $statement->execute();

        $rows = $statement->fetchAll(PDO::FETCH_ASSOC);

        if (empty($rows)) {
            return null;
        }

        $row = $rows[0];

        return new VideoContent(
            new Video(
                $row['video_id'],
                $row['channel_id'],
                $row['title'],
                $row['description'],
                $row['url'],
                $row['duration_seconds'],
                $row['uploaded_at'],
                $row['view_count'],
                $row['like_count']),
            $row['channel_name'],
            $row['channel_image'],
            $row['view_badge'],
            $row['uploader_country']
        );
    }

    public function increaseViewCount(int $videoId): bool {
        $pdo = $this->getDb()->getPDO();

        $sql = "UPDATE `VIDEOS`
            SET `view_count` = `view_count` + 1
            WHERE `video_id` = :VIDEOID";

        $statement = $pdo->prepare($sql);
        $statement->bindValue(':VIDEOID', (int) $videoId, PDO::PARAM_INT);
        $statement->execute();

        return $statement->rowCount() > 0;
    }

    public function increaseLikeCount(int $videoId): bool {
        $pdo = $this->getDb()->getPDO();

        $sql = "UPDATE `VIDEOS`
            SET `like_count` = `like_count` + 1
            WHERE `video_id` = :VIDEOID";

        $statement = $pdo->prepare($sql);
        $statement->bindValue(':VIDEOID', (int) $videoId, PDO::PARAM_INT);
        $statement->execute();

        return $statement->rowCount() > 0;
    }

    public function getVideosByChannelId(Channel $channel): array {
        $channelId = $channel->getChannelId();

        $rows = $this->getDb()->select(
            $this->tableName,
            ['channel_id' => $channelId],
            ['video_id', 'channel_id', 'title', 'description', 'url',
'duration_seconds', 'uploaded_at', 'view_count', 'like_count'],
        );

        if (empty($rows)) {
            return [];
        }

        $videos = array();

        foreach ($rows as $row) {
            $videos[] = new Video(
                $row['video_id'],
                $row['channel_id'],
                $row['title'],
                $row['description'],
                $row['url'],
                $row['duration_seconds'],
                $row['uploaded_at'],
                $row['view_count'],
                $row['like_count']
            );
        }

        return $videos;
    }

    public function getSubscribedChannelsVideosWithUser(User $user, int $videoLimit = 50): array {
        if ($user === null) {
            return [];
        }

        $pdo = $this->getDb()->getPDO();

        $sql = "SELECT 
            `VIDEOS`.`video_id`,
            `VIDEOS`.`channel_id`,
            `VIDEOS`.`title`,
            `VIDEOS`.`description`,
            `VIDEOS`.`url`,
            `VIDEOS`.`duration_seconds`,
            `VIDEOS`.`uploaded_at`,
            `VIDEOS`.`view_count`,
            `VIDEOS`.`like_count`,
            `CHANNELS`.`name` AS `channel_name`,
            `CHANNELS`.`channel_image`,
            `USERS`.`country` AS `uploader_country`
        FROM `VIDEOS`
        JOIN `SUBSCRIPTIONS`
            ON `VIDEOS`.`channel_id` = `SUBSCRIPTIONS`.`channel_id`
        JOIN `CHANNELS`
            ON `VIDEOS`.`channel_id` = `CHANNELS`.`channel_id`
        JOIN `USERS`
            ON `USERS`.`user_id` = `CHANNELS`.`owner_id`
        WHERE `SUBSCRIPTIONS`.`subscriber_id` = :USERID
        ORDER BY `VIDEOS`.`uploaded_at` DESC
        LIMIT :VIDEOLIMIT";

        $statement = $pdo->prepare($sql);
        $statement->bindValue(':USERID', (int) $user->getUserID(), PDO::PARAM_INT);
        $statement->bindValue(':VIDEOLIMIT', (int) $videoLimit, PDO::PARAM_INT);
        $statement->execute();

        $rows = $statement->fetchAll(PDO::FETCH_ASSOC);

        $videos = [];

        foreach ($rows as $row) {
            $video = new VideoContent(
                new Video(
                    $row['video_id'],
                    $row['channel_id'],
                    $row['title'],
                    $row['description'],
                    $row['url'],
                    $row['duration_seconds'],
                    $row['uploaded_at'],
                    $row['view_count'],
                    $row['like_count']
                ),
                $row['channel_name'],
                $row['channel_image'],
                'New',
                $row['uploader_country']
            );

            $videos[] = $video;
        }

        return $videos;
    }
    
    public function getMostSubscribedChannelVideos(int $channelLimit = 5, int $videoLimit = 50) : array {
        $pdo = $this->getDb()->getPDO();

        $sql = "SELECT 
            `VIDEOS`.`video_id`,
            `VIDEOS`.`channel_id`,
            `VIDEOS`.`title`,
            `VIDEOS`.`description`,
            `VIDEOS`.`url`,
            `VIDEOS`.`duration_seconds`,
            `VIDEOS`.`uploaded_at`,
            `VIDEOS`.`view_count`,
            `VIDEOS`.`like_count`,
            `CHANNELS`.`name` AS `channel_name`,
            `CHANNELS`.`channel_image`,
            `USERS`.`country` AS `uploader_country`
        FROM `VIDEOS`
        JOIN (
            SELECT 
                `SUBSCRIPTIONS`.`channel_id`
            FROM `SUBSCRIPTIONS`
            GROUP BY 
                `SUBSCRIPTIONS`.`channel_id`
            ORDER BY COUNT(*) DESC
            LIMIT :CHANNELLIMIT
        ) AS `top_channels`
            ON `VIDEOS`.`channel_id` = `top_channels`.`channel_id`
        JOIN `CHANNELS`
            ON `VIDEOS`.`channel_id` = `CHANNELS`.`channel_id`
        JOIN `USERS`
            ON `USERS`.`user_id` = `CHANNELS`.`owner_id`
        ORDER BY `VIDEOS`.`uploaded_at` DESC
        LIMIT :VIDEOLIMIT";

        $statement = $pdo->prepare($sql);
        $statement->bindValue(':CHANNELLIMIT', (int) $channelLimit, PDO::PARAM_INT);
        $statement->bindValue(':VIDEOLIMIT', (int) $videoLimit, PDO::PARAM_INT);
        $statement->execute();

        $rows = $statement->fetchAll(PDO::FETCH_ASSOC);

        $videos = [];

        foreach ($rows as $row) {
            $video = new VideoContent(
                new Video(
                    $row['video_id'],
                    $row['channel_id'],
                    $row['title'],
                    $row['description'],
                    $row['url'],
                    $row['duration_seconds'],
                    $row['uploaded_at'],
                    $row['view_count'],
                    $row['like_count']
                ),
                $row['channel_name'],
                $row['channel_image'],
                'New',
                $row['uploader_country']
            );

            $videos[] = $video;
        }

        return $videos;
    }
}
