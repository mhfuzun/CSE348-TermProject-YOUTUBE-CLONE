<?php

class ControlWatch {
    private Controller $controller;
    private WatchService $videoService;
    private CommentService $commentService;

    public function __construct(Controller $controller) {
        $this->controller = $controller;
        $this->videoService = new WatchService($this->controller->getDb());
        $this->commentService = new CommentService($this->controller->getDb());
    }

    public function getWatch() : ?View {
        $videoId = Common::get('video_id');

        if (!is_string($videoId) || !ctype_digit($videoId)) {
            http_response_code(404);
            return new View('404', '404');
        }

        $this->videoService->increaseViewCount((int) $videoId);

        $video = $this->videoService->getVideoByVideoId((int) $videoId);

        if ($video !== null) {
            $watchWindow = new WatchWindow(
                $video,
                $this->commentService->getCommentsByVideoId($video)
            );

            return new View(
                'watch',
                $video->getTitle(),
                ['watchWindow' => $watchWindow]
            );
        } else {
            http_response_code(404);
            return new View(
                '404',
                '404'
            );
        }
    }

    public function likeVideo() : ?View {
        $videoId = Common::get('video_id');

        if (!is_string($videoId) || !ctype_digit($videoId)) {
            http_response_code(404);
            return new View('404', '404');
        }

        $user = $this->controller->getSessionController()->getUser();

        if ($user === null) {
            http_response_code(404);
            return new View('404', '404');
        }

        $this->videoService->increaseLikeCount((int) $videoId);

        header('Location: ' . Common::createLinkToSitePage('watch.php', ['video_id' => (int) $videoId]));
        return null;
    }
}
