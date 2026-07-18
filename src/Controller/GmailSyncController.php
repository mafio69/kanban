<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\GmailSyncService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class GmailSyncController
{
    public function __construct(private readonly GmailSyncService $service)
    {
    }

    public function sync(Request $request, Response $response): Response
    {
        $result = $this->service->sync();

        $response->getBody()->write(
            json_encode($result, JSON_THROW_ON_ERROR)
        );

        return $response->withHeader('Content-Type', 'application/json');
    }
}
