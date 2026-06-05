<?php

class ChannelPage {
    private Channel $channel;
    private int $subcribersCount;
    private array $videos;
    private bool $isSubscribe;
    private string $ownerFullName;
    private string $ownerCountry;

    public function __construct(
        Channel $channel,
        int $subcribersCount,
        array $videos,
        bool $isSubscribe,
        string $ownerFullName = '',
        string $ownerCountry = ''
    ) {
        $this->channel = $channel;
        $this->subcribersCount = $subcribersCount;
        $this->videos = $videos;
        $this->isSubscribe = $isSubscribe;
        $this->ownerFullName = $ownerFullName;
        $this->ownerCountry = $ownerCountry;
    }

    public function getChannel(): Channel {
        return $this->channel;
    }

    public function getSubcribersCount(): int {
        return $this->subcribersCount;
    }

    public function getVideos(): array {
        return $this->videos;
    }

    public function getIsSubscribe(): bool {
        return $this->isSubscribe;
    }

    public function getOwnerFullName(): string {
        return $this->ownerFullName;
    }

    public function getOwnerCountry(): string {
        return $this->ownerCountry;
    }

}
