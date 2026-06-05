<?php

class Channel {
    private int $channel_id;
    private int $owner_id;
    private string $channel_image;
    private string $name;
    private string $description;

    private string $created_on;
    private string $category;

    public function __construct(
        int $channel_id,
        int $owner_id,
        string $channel_image,
        string $name,
        string $description,
        string $created_on,
        string $category
    ) {
        $this->channel_id = $channel_id;
        $this->owner_id = $owner_id;
        $this->channel_image = $channel_image;
        $this->name = $name;
        $this->description = $description;
        $this->created_on = $created_on;
        $this->category = $category;
    }

    public function getChannelId(): int {
        return $this->channel_id;
    }

    public function getOwnerId(): int {
        return $this->owner_id;
    }

    public function getChannelImage(): string {
        return $this->channel_image;
    }

    public function getName(): string {
        return $this->name;
    }

    public function getDescription(): string {
        if (!isset($this->description) ||
            empty($this->description)) {
            return '(no description)';
        }

        return $this->description;
    }

    public function getCreatedOn(): string {
        return $this->created_on;
    }

    public function getCategory(): string {
        return $this->category;
    }

}