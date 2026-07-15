<?php

declare(strict_types=1);

namespace App\Support;

// Odczyt zmiennej srodowiskowej: najpierw realne srodowisko (getenv — lapie nadpisanie
// z linii polecen, np. DB_HOST=127.0.0.1), potem $_ENV z .env, na koncu domyslna.
final class Env
{
    public static function get(string $key, string $default = ''): string
    {
        $value = getenv($key);

        if ($value === false || $value === '') {
            $value = $_ENV[$key] ?? $default;
        }

        return (string) $value;
    }
}
