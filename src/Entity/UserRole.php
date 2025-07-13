<?php
declare(strict_types=1);

namespace App\Entity;

class UserRole
{
    public const USER = 1;
    public const ADMIN = 2;

    public static function isValid(int $role): bool
    {
        return $role === self::USER || $role === self::ADMIN;
    }
}