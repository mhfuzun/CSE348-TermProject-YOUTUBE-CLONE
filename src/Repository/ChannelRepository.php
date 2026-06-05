<?php

class ChannelRepository extends Repository {
    private string $tableName;

    public function __construct(
        DatabaseAdapterInterface $db
    ) {
        parent::__construct($db);
        $this->tableName = database_cfg::$CHANNEL_TABLE_NAME;
    }

    public static function fromTableName(DatabaseAdapterInterface $db, string $tableName): ChannelRepository {
        $channelRepository = new ChannelRepository($db);
        $channelRepository->tableName = $tableName;

        return $channelRepository;
    }

    public function getChannelByChannelId(int $channelId): ?Channel {
        $rows = $this->getDb()->select(
            $this->tableName,
            ['channel_id' => $channelId],
            ['channel_id', 'owner_id', 'channel_image',
            'name', 'description', 'created_on', 'category'],
            1
        );

        if (empty($rows)) {
            return null;
        }

        $row = $rows[0];
        return new Channel(
            $row['channel_id'],
            $row['owner_id'],
            $row['channel_image'],
            $row['name'],
            $row['description'],
            $row['created_on'],
            $row['category']
        );
    }

    public function getSubcribersCount(Channel $channel): int {
        return $this->getDb()->count(
            database_cfg::$SUBSCRIPTION_TABLE_NAME,
            ['channel_id' => $channel->getChannelId()]
        );
    }

    public function getChannelOwnerInfo(Channel $channel): array {
        $pdo = $this->getDb()->getPDO();

        $sql = "SELECT
            `USERS`.`full_name`,
            `USERS`.`country`
        FROM `USERS`
        WHERE `USERS`.`user_id` = :OWNERID
        LIMIT 1";

        $statement = $pdo->prepare($sql);
        $statement->bindValue(':OWNERID', (int) $channel->getOwnerId(), PDO::PARAM_INT);
        $statement->execute();

        $row = $statement->fetch(PDO::FETCH_ASSOC);

        if ($row === false) {
            return [
                'full_name' => '',
                'country' => '',
            ];
        }

        return [
            'full_name' => $row['full_name'],
            'country' => $row['country'],
        ];
    }

    public function getTopChannelsWithSubscriberCount(int $limit = 5): array {
        $pdo = $this->getDb()->getPDO();

        $sql = "SELECT
            `CHANNELS`.`channel_id`,
            `CHANNELS`.`owner_id`,
            `CHANNELS`.`channel_image`,
            `CHANNELS`.`name`,
            `CHANNELS`.`description`,
            `CHANNELS`.`created_on`,
            `CHANNELS`.`category`,
            COUNT(`SUBSCRIPTIONS`.`subscription_id`) AS `subscriber_count`
        FROM `CHANNELS`
        LEFT JOIN `SUBSCRIPTIONS`
            ON `SUBSCRIPTIONS`.`channel_id` = `CHANNELS`.`channel_id`
        GROUP BY
            `CHANNELS`.`channel_id`,
            `CHANNELS`.`owner_id`,
            `CHANNELS`.`channel_image`,
            `CHANNELS`.`name`,
            `CHANNELS`.`description`,
            `CHANNELS`.`created_on`,
            `CHANNELS`.`category`
        ORDER BY `subscriber_count` DESC
        LIMIT :LIMITCOUNT";

        $statement = $pdo->prepare($sql);
        $statement->bindValue(':LIMITCOUNT', (int) $limit, PDO::PARAM_INT);
        $statement->execute();

        $rows = $statement->fetchAll(PDO::FETCH_ASSOC);
        $channels = [];

        foreach ($rows as $row) {
            $channels[] = [
                'channel' => new Channel(
                    $row['channel_id'],
                    $row['owner_id'],
                    $row['channel_image'],
                    $row['name'],
                    $row['description'],
                    $row['created_on'],
                    $row['category']
                ),
                'subscriber_count' => $row['subscriber_count']
            ];
        }

        return $channels;
    }
}
