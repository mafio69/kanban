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

    public function findByGmailThreadId(string $threadId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM sprawy WHERE gmail_thread_id = :thread_id LIMIT 1');
        $stmt->execute([':thread_id' => $threadId]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function findByEmailId(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM sprawy WHERE id = :id AND source = \'email\' LIMIT 1');
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM sprawy WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * @return array<string, mixed>
     */
    public function createFromEmail(string $threadId, string $subject, string $sender, string $snippet): array
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO sprawy (source, gmail_thread_id, subject, sender, snippet, stage) 
             VALUES (\'email\', :thread_id, :subject, :sender, :snippet, \'nowe\')
             RETURNING *'
        );
        $stmt->execute([
            ':thread_id' => $threadId,
            ':subject' => $subject,
            ':sender' => $sender,
            ':snippet' => $snippet,
        ]);
        return $stmt->fetch();
    }

    public function move(int $id, string $stage, int $position): void
    {
        // Przelicz position w kolumnie docelowej
        $stmt = $this->pdo->prepare(
            'UPDATE sprawy SET position = position + 1 WHERE stage = :stage AND position >= :position'
        );
        $stmt->execute([':stage' => $stage, ':position' => $position]);

        // Przenies sprawe
        $stmt = $this->pdo->prepare(
            'UPDATE sprawy SET stage = :stage, position = :position WHERE id = :id'
        );
        $stmt->execute([':stage' => $stage, ':position' => $position, ':id' => $id]);
    }
}
