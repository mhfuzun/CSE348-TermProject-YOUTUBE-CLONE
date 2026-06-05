<?php

class Password {
    public static function generatePasswordHash(string $password): string {
        return hash('sha256', $password);
    }

    public static function verifyPassword(string $passwordPlain, string $passwordHash): bool {
        return hash_equals($passwordHash, Password::generatePasswordHash($passwordPlain))
            || hash_equals($passwordHash, $passwordPlain);
    }
}
