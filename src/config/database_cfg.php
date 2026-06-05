<?php

class database_cfg {
    public static array $DATABASE_CFG = [
        'host' => 'localhost',
        'database' => 'MuhammetFurkanUZUN',
        'username' => 'furkan',
        'password' => 'root',
        'charset' => 'utf8mb4',
    ];

    public static $CHANNEL_TABLE_NAME = 'CHANNELS';
    public static $USER_TABLE_NAME = 'USERS';
    public static $VIDEO_TABLE_NAME = 'VIDEOS';
    public static $SUBSCRIPTION_TABLE_NAME = 'SUBSCRIPTIONS';
    public static $COMMENT_TABLE_NAME = 'COMMENTS';
}