<?php

class ControlComment {
    private Controller $controller;
    private CommentService $commentService;

    public function __construct(Controller $controller) {
        $this->controller = $controller;
        $this->commentService = new CommentService($this->controller->getDb());
    }

    public function deleteComment() : ?View {
        $commentId = Common::get('comment_id');
        $videoId = Common::get('video_id');

        if (!is_string($commentId) || !ctype_digit($commentId)) {
            http_response_code(404);
            return new View('404', '404');
        }

        if (!is_string($videoId) || !ctype_digit($videoId)) {
            http_response_code(404);
            return new View('404', '404');
        }

        $user = $this->controller->getSessionController()->getUser();

        if ($user === null) {
            http_response_code(404);
            return new View('404', '404');
        }

        if ($this->commentService->deleteComment((int) $commentId, $user)) {
            header('Location: ' . Common::createLinkToSitePage('watch.php', ['video_id' => (int) $videoId]));
            return null;
        } else {
            http_response_code(404);
            return new View('404', '404');
        }
    }

    public function createComment() : ?View {
        $videoId = Common::post('video_id');
        $body = Common::post('body');
        $parentCommentId = Common::post('parent_comment_id');

        $user = $this->controller->getSessionController()->getUser();

        if ($user === null) {
            http_response_code(401);
            return new View('404', '401');
        }

        if (!is_string($videoId) || !ctype_digit($videoId)) {
            http_response_code(404);
            return new View('404', '404');
        }

        if (!is_string($body) || trim($body) === '') {
            http_response_code(404);
            return new View('404', '404');
        }

        if (strlen($body) > 250) {
            $body = substr($body, 0, 250);
        }

        if ($parentCommentId === '' || $parentCommentId === null) {
            $parentCommentId = null;
        } elseif (is_string($parentCommentId) && ctype_digit($parentCommentId)) {
            $parentCommentId = (int) $parentCommentId;
        } else {
            http_response_code(404);
            return new View('404', '404');
        }

        $this->commentService->createComment(
            (int) $videoId,
            $user,
            $body,
            $parentCommentId
        );

        header('Location: ' . Common::createLinkToSitePage('watch.php', ['video_id' => (int) $videoId]));
        return null;
    }

}
