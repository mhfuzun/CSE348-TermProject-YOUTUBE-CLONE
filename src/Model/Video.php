<?php

class Video {
    private int $video_id;
    private int $channel_id;
    private string $title;
    private string $description;
    private string $url;
    private int $duration_seconds;
    private string $uploaded_at;
    private int $view_count;
    private int $like_count;

    public function __construct(
        int $video_id,
        int $channel_id,
        string $title,
        string $description,
        string $url,
        int $duration_seconds,
        string $uploaded_at,
        int $view_count,
        int $like_count
    ) {
        $this->video_id = $video_id;
        $this->channel_id = $channel_id;
        $this->title = $title;
        $this->description = $description;
        $this->url = $url;
        $this->duration_seconds = $duration_seconds;
        $this->uploaded_at = $uploaded_at;
        $this->view_count = $view_count;
        $this->like_count = $like_count;
    }

    public function getVideoId(): int {
        return $this->video_id;
    }

    public function getChannelId(): int {
        return $this->channel_id;
    }

    public function getTitle(): string {
        return $this->title;
    }

    public function getDescription(): string {
        return $this->description;
    }

    public function getUrl(): string {
        return $this->url;
    }

    public function getDurationSeconds(): int {
        return $this->duration_seconds;
    }

    public function getUploadedAt(): string {
        return $this->uploaded_at;
    }

    public function getViewCount(): int {
        return $this->view_count;
    }

    public function getLikeCount(): int {
        return $this->like_count;
    }

    public function getEmbedId(): string {
        $parts = parse_url($this->getUrl());
        parse_str($parts["query"], $query);

        $videoId = $query["v"];

        return $videoId;
    }

    public function getDescriptionShort(int $length = 100) {
        $description = $this->getDescription();
        return substr($description, 0, $length) . "...";
    }

    public function getDurationText() : string {
        $hours = floor($this->getDurationSeconds() / 3600);
        $minutes = floor(($this->getDurationSeconds() % 3600) / 60);
        $seconds = $this->getDurationSeconds() % 60;

        if ($hours > 0) {
            return sprintf("%02d:%02d:%02d", $hours, $minutes, $seconds);
        } else {
            return sprintf("%02d:%02d", $minutes, $seconds);
        }
    }

    public function getUploadedTimeText() : string {
        $now = new DateTime();
        $uploadedAt = new DateTime($this->getUploadedAt());

        $days = $uploadedAt->diff($now)->days;

        if ($days == 0) {
            return "Today";
        } elseif ($days == 1) {
            return "Yesterday";
        } else {
            if ($days > 365) {
                $years = floor($days / 365);
                $days = $days % 365;
                return $years . " years ago";
            } elseif ($days > 30) {
                $months = floor($days / 30);
                $days = $days % 30;
                return $months . " months ago";
            } else {
                return $days . " days ago";
            }
        }
    }

}