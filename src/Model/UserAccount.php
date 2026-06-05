<?php

class UserAccount extends User {
    private string $password;
    private string $full_name;
    private string $country;
    private string $joined_on;
    private string $bio;

    public function __construct(
        int $user_id,
        string $username,
        string $user_image,
        string $email,
        string $password,
        string $full_name,
        string $country,
        string $joined_on,
        string $bio
    ) {
        parent::__construct($user_id, $username, $user_image, $email);

        $this->password = $password;
        $this->full_name = $full_name;
        $this->country = $country;
        $this->joined_on = $joined_on;
        $this->bio = $bio;
    }

    public static function fromUser(
        User $user,
        string $password,
        string $full_name,
        string $country,
        string $joined_on,
        string $bio
    ): UserAccount {
        return new UserAccount(
            $user->getUserId(),
            $user->getUsername(),
            $user->getUserImage(),
            $user->getEmail(),
            $password,
            $full_name,
            $country,
            $joined_on,
            $bio
        );
    }

    public function getPassword(): string {
        return $this->password;
    }

    public function getFullName(): string {
        return $this->full_name;
    }

    public function getCountry(): string {
        return $this->country;
    }

    public function getJoinedOn(): string {
        return $this->joined_on;
    }

    public function getBio(): string {
        return $this->bio;
    }
}
