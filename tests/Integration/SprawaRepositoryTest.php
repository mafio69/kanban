<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Repository\SprawaRepository;
use PDO;
use PHPUnit\Framework\TestCase;

final class SprawaRepositoryTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        $this->pdo = TestDatabase::pdo();
        $this->pdo->exec('TRUNCATE sprawy RESTART IDENTITY');
    }

    public function testFindAllZwracaZaseedowanaSprawe(): void
    {
        // Losowy token per uruchomienie — musi realnie przejsc przez SQL, nie da sie zahardkodowac.
        $token = 'tok_' . bin2hex(random_bytes(8));
        $this->pdo->prepare(
            "INSERT INTO sprawy (source, subject, stage) VALUES ('reczne', :subject, 'nowe')"
        )->execute([':subject' => $token]);

        $rows = (new SprawaRepository($this->pdo))->findAll();

        self::assertContains($token, array_column($rows, 'subject'));
    }

    public function testFindAllDomyslniePomijaZarchiwizowane(): void
    {
        $this->pdo->exec(
            "INSERT INTO sprawy (source, subject, stage, archived) VALUES ('reczne', 'x', 'zrobione', true)"
        );

        $repo = new SprawaRepository($this->pdo);

        self::assertCount(0, $repo->findAll());
        self::assertCount(1, $repo->findAll(true));
    }

    public function testFindAllSortujePoEtapieIPozycji(): void
    {
        $this->pdo->exec(
            "INSERT INTO sprawy (source, subject, stage, position) VALUES
                ('reczne', 'druga', 'nowe', 1),
                ('reczne', 'pierwsza', 'nowe', 0)"
        );

        $rows = (new SprawaRepository($this->pdo))->findAll();

        self::assertSame(['pierwsza', 'druga'], array_column($rows, 'subject'));
    }
}
