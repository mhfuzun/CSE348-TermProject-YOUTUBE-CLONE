<?php

class Comment {
    private int $comment_id;
    private int $video_id;
    private int $user_id;
    private ?int $parent_comment_id;
    private string $body;
    private string $posted_at;

    public function __construct(
        int $comment_id,
        int $video_id,
        int $user_id,
        ?int $parent_comment_id,
        string $body,
        string $posted_at
    ) {
        $this->comment_id = $comment_id;
        $this->video_id = $video_id;
        $this->user_id = $user_id;
        $this->parent_comment_id = $parent_comment_id;
        $this->body = $body;
        $this->posted_at = $posted_at;
    }

    public function getCommentId(): int {
        return $this->comment_id;
    }

    public function setCommentId(int $comment_id): void {
        $this->comment_id = $comment_id;
    }

    public function getVideoId(): int {
        return $this->video_id;
    }

    public function getUserId(): int {
        return $this->user_id;
    }

    public function getParentCommentId(): ?int {
        return $this->parent_comment_id;
    }

    public function getBody(): string {
        return $this->body;
    }

    public function getPostedAt(): string {
        return $this->posted_at;
    }
}
