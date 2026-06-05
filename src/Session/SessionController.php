<?php

class SessionController {
    public function __construct() {
        session_start();
    }

    public static function checkLoginAndGetUser(int $user_id) : ?User {
        if (!SessionController::isLogined()) {
            return null;
        }

        $user = $_SESSION['userData'] ?? null;

        if (!$user instanceof User) {
            return null;
        }

        if ($user->getUserID() !== $user_id) {
            return null;
        }

        return $user;
    }

    public static function getUser() : ?User {
        if (!SessionController::isLogined()) {
            return null;
        }

        $user = $_SESSION['userData'] ?? null;

        if (!$user instanceof User) {
            return null;
        }
        return $_SESSION['userData'];
    }

    public static function isLogined() : bool {
        return isset($_SESSION['loggined']) && $_SESSION['loggined'] === true;
    }

    public static function login(User $user) {
        $_SESSION['loggined'] = true;
        $_SESSION['userData'] = $user;
    }

    public static function logout() {
        $_SESSION['loggined'] = false;
        $_SESSION['userData'] = null;
    }
}
