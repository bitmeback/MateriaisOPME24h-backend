<?php
declare(strict_types=1);

namespace MateriaisOpme\App\Support;

final class Cnpj
{
    public static function normalize(string $value): string
    {
        return preg_replace('/\D+/', '', $value) ?? '';
    }

    public static function format(string $value): string
    {
        $digits = self::normalize($value);
        if (strlen($digits) !== 14) {
            return $value;
        }

        return sprintf(
            '%s.%s.%s/%s-%s',
            substr($digits, 0, 2),
            substr($digits, 2, 3),
            substr($digits, 5, 3),
            substr($digits, 8, 4),
            substr($digits, 12, 2)
        );
    }
}
