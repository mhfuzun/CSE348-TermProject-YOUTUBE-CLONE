<?php

class CommentService extends Service {
    private CommentRepository $commentRepository;

    public function __construct(
        PdoDatabaseAdapter $db
    ) {
        parent::__construct($db);
        $this->commentRepository = new CommentRepository($db);
    }
    
    public function createComment(int $videoId, User $user, string $body, ?int $parentCommentId = null) : CommentContent {
        $comment = new Comment(
            0,
            $videoId,
            $user->getUserID(),
            $parentCommentId,
            $body,
            date('Y-m-d')
        );

        $last_id = $this->commentRepository->createComment($comment);
        $comment->setCommentId($last_id);

        return new CommentContent(
            $comment,
            $user->getUsername(),
            $user->getUserImage()
        );
    }

    public function deleteComment(int $comment_id, ?User $user = null) : bool {
        $userId = $user !== null ? $user->getUserID() : null;

        return $this->commentRepository->deleteComment($comment_id, $userId);
    }

    public function getCommentsByVideoId(Video $video) : array {
        $comments = $this->commentRepository->getCommentsWithVideo($video->getVideoId());
        $topComments = [];

        foreach ($comments as $comment) {
            if ($comment->getParentCommentId() === null) {
                $topComments[] = $comment;
            } else {
                foreach ($comments as $parentComment) {
                    if ($parentComment->getCommentId() === $comment->getParentCommentId()) {
                        $parentComment->addChild($comment);
                        break;
                    }
                }
            }
        }

        return $topComments;
    }
}
