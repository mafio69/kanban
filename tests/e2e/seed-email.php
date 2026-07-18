<?php

// Seeder emaili dla testow e2e Gmail sync.
// Uzycie: php tests/e2e/seed-email.php <thread_id> <subject> <sender> <snippet>

declare(strict_types=1);

use App\Support\Env;
use Dotenv\Dotenv;

require __DIR__ . '/../../vendor/autoload.php';

$root = dirname(__DIR__, 2);

if (is_file($root . '/.env')) {
    Dotenv::createImmutable($root)->safeLoad();
}

$name = Env::get('DB_NAME', 'kanban_urzedowy');
$pdo = new PDO(
    sprintf('pgsql:host=%s;port=%s;dbname=%s', Env::get('DB_HOST', '127.0.0.1'), Env::get('DB_PORT', '5432'), $name),
    Env::get('DB_USER'),
    Env::get('DB_PASSWORD'),
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$threadId = $argv[1] ?? '';
$subject = $argv[2] ?? '';
$sender = $argv[3] ?? '';
$snippet = $argv[4] ?? '';

if (empty($threadId)) {
    echo "usage: seed-email.php <thread_id> <subject> <sender> <snippet>\n";
    exit(1);
}

// Wstaw sprawe ze zrodl=email, stage=nowe, z gmail_thread_id
$stmt = $pdo->prepare("INSERT INTO sprawy (source, subject, sender, snippet, stage, gmail_thread_id) VALUES ('email', :subject, :sender, :snippet, 'nowe', :thread_id)");
$stmt->execute([
    ':subject' => $subject,
    ':sender' => $sender,
    ':snippet' => $snippet,
    ':thread_id' => $threadId,
]);

echo "seeded email: $threadId\n";
