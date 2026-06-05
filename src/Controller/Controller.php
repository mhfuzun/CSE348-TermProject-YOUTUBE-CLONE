<?php

class Controller {
    private PdoDatabaseAdapter $db;
    private SessionController $sessionController;
    private ?User $user;

    public function __construct(
        PdoDatabaseAdapter $db,
        SessionController $sessionController
    ) {
        $this->db = $db;
        $this->sessionController = $sessionController;

        $this->user = $this->checkSession();
    }

    public function getDb() : PdoDatabaseAdapter {
        return $this->db;
    }

    public function getSessionController() : SessionController {
        return $this->sessionController;
    }

    private function checkSession() : ?User {
        $user_id = Common::get('user_id');

        if (!is_string($user_id) || !ctype_digit($user_id)) {
            return null;
        }

        if ($this->sessionController->checkLoginAndGetUser($user_id) !== null)
            return $this->sessionController->checkLoginAndGetUser($user_id);

        return null;
    }

    public function getUser() : ?User {
        return $this->user;
    }

    public function isLogined() : bool {
        return $this->sessionController->isLogined();
    }

    public function logout() {
        // $this->sessionController->logout();
        // $this->user = null;
    }
}
