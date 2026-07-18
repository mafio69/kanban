<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\SprawaRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class ArchiveController
{
    public function __construct(private readonly SprawaRepository $repository)
    {
    }

    public function archive(Request $request, Response $response, array $args): Response
    {
        $id = (int) $args['id'];
        $body = $request->getParsedBody();

        $archived = (bool) ($body['archived'] ?? false);

        $sprawa = $this->repository->findById($id);
        if ($sprawa === null) {
            $response->getBody()->write(json_encode(['error' => 'Not found'], JSON_THROW_ON_ERROR));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        $this->repository->setArchived($id, $archived);

        $updated = $this->repository->findById($id);
        $response->getBody()->write(json_encode($updated, JSON_THROW_ON_ERROR));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function delete(Request $request, Response $response, array $args): Response
    {
        $id = (int) $args['id'];

        $sprawa = $this->repository->findById($id);
        if ($sprawa === null) {
            $response->getBody()->write(json_encode(['error' => 'Not found'], JSON_THROW_ON_ERROR));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        $this->repository->delete($id);

        $response->getBody()->write(json_encode(['deleted' => true], JSON_THROW_ON_ERROR));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
