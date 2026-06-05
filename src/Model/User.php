<?php

class User {
    private int $user_id;
    private string $username;
    private string $user_image; 
    private string $email;

    public function __construct(int $user_id, string $username, string $user_image, string $email) {
        $this->user_id = $user_id;
        $this->username = $username;
        $this->user_image = $user_image;
        $this->email = $email;
    }

    public function setUserId(int $user_id) {
        $this->user_id = $user_id;
    }

    public function getUserID() : int {
        return $this->user_id;
    }

    public function getUsername() : string {
        return $this->username;
    }

    public function getUserImage() : string {
        return $this->user_image;
    }

    public function getEmail() : string {
        return $this->email;
    }
}