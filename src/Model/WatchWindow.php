<?php

class WatchWindow {
    private VideoContent $video;
    private array $comments;

    public function __construct(VideoContent $video, array $comments) {
        $this->video = $video;
        $this->comments = $comments;
    }

    public function getVideo(): VideoContent {
        return $this->video;
    }

    public function getComments(): array {
        return $this->comments;
    }
}