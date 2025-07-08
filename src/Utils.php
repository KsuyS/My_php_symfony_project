<?php
declare(strict_types=1);

namespace App;

class Utils
{
    private const MYSQL_DATETIME_FORMAT = 'Y-m-d H:i:s';
    private const FORM_DATETIME_FORMAT = 'Y-m-d';
    public static function parseDateTime(string $value, string $format): \DateTimeImmutable
    {
        $result = \DateTimeImmutable::createFromFormat($format, $value);
        if (!$result)
        {
            throw new \InvalidArgumentException("Invalid datetime value '$value'");
        }
        return $result;
    }

    public static function convertDateTimeToStringForm(\DateTimeImmutable $date): ?string
    {
        if ($date === null)
        {
            return null;
        }
        return $date->format(format:self::FORM_DATETIME_FORMAT);
    }

    public static function convertDateTimeToString(\DateTimeImmutable $date): ?string
    {
        if ($date === null)
        {
            return null;
        }
        return $date->format(format:self::MYSQL_DATETIME_FORMAT);
    }
}