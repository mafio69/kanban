<?php

// Seeder bazy TESTOWEJ dla e2e. Uzycie:
//   php tests/e2e/seed.php __truncate__          — czysci tabele sprawy
//   php tests/e2e/seed.php "temat" nowe          — wstawia sprawe z danym tematem i etapem

declare(strict_types=1);

use App\Support\Env;
use Dotenv\Dotenv;

require __DIR__ . '/../../vendor/autoload.php';

$root = dirname(__DIR__, 2);

if (is_file($root . '/.env')) {
    Dotenv::createImmutable($root)->safeLoad();
}

// W e2e DB_NAME wskazuje wprost baze testowa (tak samo jak czyta ja aplikacja) — bez doklejania.
$name = Env::get('DB_NAME', 'kanban_urzedowy');
$pdo = new PDO(
    sprintf('pgsql:host=%s;port=%s;dbname=%s', Env::get('DB_HOST', '127.0.0.1'), Env::get('DB_PORT', '5432'), $name),
    Env::get('DB_USER'),
    Env::get('DB_PASSWORD'),
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$subject = $argv[1] ?? '';
$stage = $argv[2] ?? 'nowe';

if ($subject === '__truncate__') {
    $pdo->exec('TRUNCATE sprawy RESTART IDENTITY');
    echo "truncated\n";
    exit;
}

$pdo->prepare("INSERT INTO sprawy (source, subject, stage) VALUES ('reczne', :s, :st)")
    ->execute([':s' => $subject, ':st' => $stage]);

echo "seeded\n";
