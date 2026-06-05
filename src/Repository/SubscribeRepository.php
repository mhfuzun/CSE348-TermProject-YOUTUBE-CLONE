<?php

class SubscribeRepository extends Repository {
    private string $tableName;

    public function __construct(
        DatabaseAdapterInterface $db
    ) {
        parent::__construct($db);
        $this->tableName = database_cfg::$SUBSCRIPTION_TABLE_NAME;
    }

    public static function fromTableName(DatabaseAdapterInterface $db, string $tableName): SubscribeRepository {
        $subscribeRepository = new SubscribeRepository($db);
        $subscribeRepository->tableName = $tableName;

        return $subscribeRepository;
    }

    public function isSubscribe(User $user, Channel $channel) : bool {
        $rows = $this->getDb()->select(
            $this->tableName,
            ['channel_id' => $channel->getChannelId(), 'subscriber_id' => $user->getUserID()],
            ['channel_id', 'subscriber_id'],
            1
        );

        return !empty($rows);
    }

    public function subscribe(User $user, Channel $channel) : bool {
        if ($this->isSubscribe($user, $channel)) {
            return false;
        }

        $data = array(
            'channel_id' => $channel->getChannelId(),
            'subscriber_id' => $user->getUserID()
        );

        $this->getDb()->insert(
            $this->tableName,
            $data
        );

        return true;
    }

    public function unsubscribe(User $user, Channel $channel) : bool {
        if (!$this->isSubscribe($user, $channel)) {
            return false;
        }

        $conditions = array(
            'subscriber_id' => $user->getUserID(),
            'channel_id' => $channel->getChannelId()
        );

        $this->getDb()->delete(
            $this->tableName,
            $conditions
        );

        return true;
    }
}
