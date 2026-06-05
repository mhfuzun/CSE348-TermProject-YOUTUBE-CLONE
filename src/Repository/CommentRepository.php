<?php

class CommentRepository extends Repository {
    private string $tableName;

    public function __construct(
        DatabaseAdapterInterface $db
    ) {
        parent::__construct($db);
        $this->tableName = database_cfg::$COMMENT_TABLE_NAME;
    }

    public static function fromTableName(DatabaseAdapterInterface $db, string $tableName): CommentRepository {
        $commentRepository = new CommentRepository($db);
        $commentRepository->tableName = $tableName;

        return $commentRepository;
    }

    public function deleteComment(int $comment_id, ?int $user_id = null) : bool {
        $conditions = array(
            'comment_id' => $comment_id
        );

        if ($user_id !== null) {
            $conditions['user_id'] = $user_id;
        }

        $commentRows = $this->getDb()->select(
            $this->tableName,
            $conditions,
            ['comment_id'],
            1
        );

        if (empty($commentRows)) {
            return false;
        }

        $this->getDb()->update(
            $this->tableName,
            ['parent_comment_id' => $comment_id],
            ['parent_comment_id' => null]
        );

        $rows = $this->getDb()->delete(
            $this->tableName,
            $conditions
        );

        return $rows > 0;
    }
    
    public function createComment(Comment $comment) : int {
        $data = array(
            'video_id' => $comment->getVideoId(),
            'user_id' => $comment->getUserId(),
            'parent_comment_id' => $comment->getParentCommentId(),
            'body' => $comment->getBody()
        );

        $last_id = $this->getDb()->insert(
            $this->tableName,
            $data
        );

        return $last_id;
    }

    public function getCommentsWithVideo(int $video_id) : array {
        $pdo = $this->getDb()->getPDO();

        $sql = "SELECT
            `child`.`comment_id`,
            `child`.`video_id`,
            `child`.`user_id`,
            `child`.`parent_comment_id`,
            `child`.`body`,
            `child`.`posted_at`,
            `USERS`.`username` AS `user_name`,
            `USERS`.`user_image`,
            `parent`.`comment_id` AS `parent_join_comment_id`
        FROM `COMMENTS` AS `child`
        LEFT JOIN `COMMENTS` AS `parent`
            ON `child`.`parent_comment_id` = `parent`.`comment_id`
        JOIN `USERS`
            ON `child`.`user_id` = `USERS`.`user_id`
        WHERE `child`.`video_id` = :VIDEOID
        ORDER BY
            IF(`child`.`parent_comment_id` IS NULL, 0, 1),
            `child`.`posted_at` DESC,
            `child`.`comment_id` DESC";

        $statement = $pdo->prepare($sql);
        $statement->bindValue(':VIDEOID', (int) $video_id, PDO::PARAM_INT);
        $statement->execute();

        $rows = $statement->fetchAll(PDO::FETCH_ASSOC);

        $comments = [];

        foreach ($rows as $row) {
            $comment = new CommentContent(
                new Comment(
                    $row['comment_id'],
                    $row['video_id'],
                    $row['user_id'],
                    $row['parent_comment_id'],
                    $row['body'],
                    $row['posted_at']
                ),
                $row['user_name'],
                $row['user_image']
            );

            $comments[] = $comment;
        }

        return $comments;
    }
}
