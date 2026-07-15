<?php

declare(strict_types=1);

use App\Support\Env;
use Psr\Container\ContainerInterface;

// PDO jako wspoldzielony singleton — parametry z .env. Kolejne serwisy/repozytoria
// PHP-DI autowiruje (wstrzykuje PDO w konstruktor), wiec nie trzeba ich tu wymieniac.
return [
    PDO::class => function (ContainerInterface $c): PDO {
        $dsn = sprintf(
            'pgsql:host=%s;port=%s;dbname=%s',
            Env::get('DB_HOST', '127.0.0.1'),
            Env::get('DB_PORT', '5432'),
            Env::get('DB_NAME', 'kanban_urzedowy')
        );

        return new PDO($dsn, Env::get('DB_USER'), Env::get('DB_PASSWORD'), [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    },
];
