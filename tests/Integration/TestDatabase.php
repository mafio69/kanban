<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Support\Env;
use PDO;

// Polaczenie do bazy TESTOWEJ (nazwa DB + "_test"), zeby testy nie ruszaly danych roboczych.
final class TestDatabase
{
    public static function pdo(): PDO
    {
        $name = Env::get('DB_NAME_TEST') ?: (Env::get('DB_NAME', 'kanban_urzedowy') . '_test');
        $dsn = sprintf(
            'pgsql:host=%s;port=%s;dbname=%s',
            Env::get('DB_HOST', '127.0.0.1'),
            Env::get('DB_PORT', '5432'),
            $name
        );

        return new PDO($dsn, Env::get('DB_USER'), Env::get('DB_PASSWORD'), [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }
}
