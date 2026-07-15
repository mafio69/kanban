<?php

declare(strict_types=1);

namespace App\Repository;

use PDO;

final class SprawaRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findAll(bool $includeArchived = false): array
    {
        $sql = 'SELECT * FROM sprawy';

        if (!$includeArchived) {
            $sql .= ' WHERE archived = false';
        }

        $sql .= ' ORDER BY stage, position, id';

        return $this->pdo->query($sql)->fetchAll();
    }
}
