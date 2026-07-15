<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\SprawaRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class SprawaController
{
    public function __construct(private readonly SprawaRepository $repository)
    {
    }

    public function list(Request $request, Response $response): Response
    {
        $includeArchived = ($request->getQueryParams()['archived'] ?? '0') === '1';

        $response->getBody()->write(
            json_encode($this->repository->findAll($includeArchived), JSON_THROW_ON_ERROR)
        );

        return $response->withHeader('Content-Type', 'application/json');
    }
}
