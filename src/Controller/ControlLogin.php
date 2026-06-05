<?php

class ControlLogin {
    private Controller $controller;
    private AuthService $authService;

    public function __construct(Controller $controller) {
        $this->controller = $controller;
        $this->authService = new AuthService($this->controller->getDb());
    }

    public function getLogin() : ?View {
        $v = new View(
            'login',
            'Login',
            []
        );

        return $v;
    }

    public function postLogin() {
        $username = Common::post('username');
        $password = Common::post('password');
        $rememberme = Common::post('rememberme') === '1';

        if ($username === null || $password === null) {
            header('Location: login.php?error=invalid&reason=missing_fields');
            exit;
        }

        // to services
        $user = $this->authService->loginWithUsernameAndPassword(
            $username,
            $password
        );

        // create session
        if ($user !== null) {
            SessionController::login($user);
            // create readme if it's enable.

            // redirect homepage(feed.php)
            header('Location: feed.php?user_id=' . $user->getUserID());
            exit;
        }

        // error display
        // ...
        header('Location: login.php?error=invalid&reason=login_failed');
        exit;
    }

    public function getLogout() {
        SessionController::logout();
        header('Location: login.php?error=valid&reason=logged_out');
        exit;
    }
}
