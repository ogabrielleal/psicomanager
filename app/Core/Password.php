<?php
declare(strict_types=1);

namespace App\Core;

final class Password
{
    public static function hash(string $password): string
    {
        $algo=defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_DEFAULT;
        return password_hash($password,$algo);
    }
    public static function needsRehash(string $hash): bool
    {
        $algo=defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_DEFAULT;
        return password_needs_rehash($hash,$algo);
    }
}
