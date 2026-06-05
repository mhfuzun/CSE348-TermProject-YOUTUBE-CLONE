<?php

class AuthService extends Service {
    private UserRepository $userRepository;

    public function __construct(
        PdoDatabaseAdapter $db
    ) {
        parent::__construct($db);
        $this->userRepository = new UserRepository($db);
    }

    public function loginWithRemembermeToken(string $token) : ?User {
        throw new NotImplementedException();
    }

    public function loginWithUsernameAndPassword(string $username, string $passwordPlain): ?User {
        $userAccount = $this->userRepository->findByUsername($username);

        if ($userAccount === null) {
            return null;
        }

        if (!Password::verifyPassword($passwordPlain, $userAccount->getPassword())) {
            return null;
        }

        return $userAccount;
    }

    public function registerWithValues(
        string $username,
        string $passwordPlain,
        string $userImage,
        string $fullName,
        string $email,
        string $country,
        string $bio
    ) : User {
        $userAccount = UserAccount::fromUser(
            new User(0, $username, $userImage, $email),
            Password::generatePasswordHash($passwordPlain),
            $fullName,
            $country,
            '',
            $bio
        );

        $this->registerWithUserAccount($userAccount);

        return $userAccount;
    }

    public function registerWithUserAccount(UserAccount $userAccount): int {
        return $this->userRepository->createUserAccount($userAccount);
    }
}
