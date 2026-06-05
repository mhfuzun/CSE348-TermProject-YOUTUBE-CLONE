<?php

class UserRepository extends Repository {
    private string $tableName;

    public function __construct(
        DatabaseAdapterInterface $db
    ) {
        parent::__construct($db);
        $this->tableName = database_cfg::$USER_TABLE_NAME;
    }

    public static function fromTableName(DatabaseAdapterInterface $db, string $tableName): UserRepository {
        $userRepository = new UserRepository($db);
        $userRepository->tableName = $tableName;

        return $userRepository;
    }

    public function createUserAccount(UserAccount $userAccount): int {
        $userId = $this->getDb()->insert($this->tableName, [
            'username' => $userAccount->getUsername(),
            'password' => $userAccount->getPassword(),
            'user_image' => $userAccount->getUserImage(),
            'full_name' => $userAccount->getFullName(),
            'email' => $userAccount->getEmail(),
            'country' => $userAccount->getCountry(),
            'bio' => $userAccount->getBio(),
            // 'joined_on' => $userAccount->getJoinedOn(),
        ]);

        $userAccount->setUserId($userId);

        return $userId;
    }

    public function findByUsername(string $username): ?UserAccount {
        $rows = $this->getDb()->select(
            $this->tableName,
            ['username' => $username],
            ['user_id', 'username', 'password', 'user_image', 'full_name', 'email', 'country', 'bio', 'joined_on'],
            1
        );

        if (empty($rows)) {
            return null;
        }

        $row = $rows[0];

        return UserAccount::fromUser(
            new User(
                $row['user_id'],
                $row['username'],
                $row['user_image'],
                $row['email']
            ),
            $row['password'],
            $row['full_name'],
            $row['country'],
            $row['joined_on'],
            $row['bio']
        );
    }

    public function findByEmail(string $email): ?array {
        $rows = $this->getDb()->select(
            $this->tableName,
            ['email' => $email],
            ['user_id', 'username', 'user_image', 'full_name', 'email', 'country', 'bio', 'joined_on'],
            1
        );

        return $rows[0] ?? null;
    }

    public function updateBio(int $userId, string $bio): int {
        return $this->getDb()->update(
            $this->tableName,
            ['user_id' => $userId],
            ['bio' => $bio],
            1
        );
    }
}
