<?php
declare(strict_types=1);
namespace App;

class Config
{
    public static function getDatabaseDsn(): string
    {
        return 'mysql:host=localhost;dbname=php_course';
    }

    public static function getDatabaseUser(): string
    {
        return 'Ksenia22';
    }

    public static function getDatabasePassword(): string
    {
        return 'K220403s#';
    }
}