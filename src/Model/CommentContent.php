<?php

class CommentContent extends Comment {
    private string $user_name;
    private string $user_image;
    private array $children = [];

    public function __construct(
        Comment $comment,
        string $user_name,
        string $user_image
    ) {
        parent::__construct(
            $comment->getCommentId(),
            $comment->getVideoId(),
            $comment->getUserId(),
            $comment->getParentCommentId(),
            $comment->getBody(),
            $comment->getPostedAt()
        );
        $this->user_name = $user_name;
        $this->user_image = $user_image;
    }

    public function getUserName(): string {
        return $this->user_name;
    }

    public function getUserImage(): string {
        return $this->user_image;
    }

    public function addChild(CommentContent $comment): void {
        $this->children[] = $comment;
    }

    public function getChildren(): array {
        return $this->children;
    }
}
