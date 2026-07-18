<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\SprawaRepository;

final class GmailSyncService
{
    public function __construct(
        private readonly SprawaRepository $repository,
        private readonly string $fixturePath = '',
    ) {
    }

    /**
     * @return array{created: int, skipped: int}
     */
    public function sync(): array
    {
        $threads = $this->fetchThreads();
        $created = 0;
        $skipped = 0;

        foreach ($threads as $thread) {
            $existing = $this->repository->findByGmailThreadId($thread['id']);
            if ($existing !== null) {
                $skipped++;
                continue;
            }

            $this->repository->createFromEmail(
                $thread['id'],
                $thread['subject'],
                $thread['from'],
                $thread['snippet'],
            );
            $created++;
        }

        return ['created' => $created, 'skipped' => $skipped];
    }

    /**
     * @return array<int, array{id: string, subject: string, from: string, snippet: string}>
     */
    private function fetchThreads(): array
    {
        // W testach: czytaj z pliku fixture
        if ($this->fixturePath !== '' && is_file($this->fixturePath)) {
            $data = json_decode(file_get_contents($this->fixturePath), true);
            return $data['threads'] ?? [];
        }

        // W produkcji: wywołaj Google API (na razie zwraca pustą listę)
        // TODO: zaimplementować pobieranie z Google Gmail API
        return [];
    }
}
