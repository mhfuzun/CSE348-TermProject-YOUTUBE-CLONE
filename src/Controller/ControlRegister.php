<?php

class ControlRegister {
    private Controller $controller;
    private AuthService $authService;

    public function __construct(Controller $controller) {
        $this->controller = $controller;
        $this->authService = new AuthService($this->controller->getDb());
    }

    public function getRegister() : ?View {
        $v = new View(
            'register',
            'Register',
            []
        );

        return $v;
    }

    public function postRegister() {
        $username = Common::post('username');
        $email = Common::post('email');
        $full_name = Common::post('full_name');
        $password = Common::post('password');
        $passwordConfirm = Common::post('password_confirm');

        if ($username === null || $email === null || $full_name === null || $password === null || $passwordConfirm === null) {
            header("Location: /register.php?error=invalid&reason=missing_fields");
            exit;
        }

        if ($password !== $passwordConfirm) {
            header("Location: /register.php?error=invalid&reason=passwords_not_match");
            exit;
        }

        try {
            $user = $this->authService->registerWithValues(
                $username,
                $password,
                'default',
                $full_name,
                $email,
                '',
                ''
            );
        } catch (Throwable $e) {
            header("Location: /register.php?error=invalid&reason=register_failed");
            exit;
        }

        // redirect login page
        if ($user !== null) {
            header("Location: /login.php?error=valid&reason=register_success");
            exit;
        }

        // error display
        // ...
        header("Location: /register.php?error=invalid&reason=register_failed");
        exit;
    }
}
