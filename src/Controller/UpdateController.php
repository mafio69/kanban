<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\SprawaRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class UpdateController
{
    private const ALLOWED_FIELDS = ['subject', 'sender', 'notes', 'ocr_text'];

    public function __construct(private readonly SprawaRepository $repository)
    {
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        $id = (int) $args['id'];
        $body = $request->getParsedBody();

        $sprawa = $this->repository->findById($id);
        if ($sprawa === null) {
            $response->getBody()->write(json_encode(['error' => 'Not found'], JSON_THROW_ON_ERROR));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        $fields = array_intersect_key($body, array_flip(self::ALLOWED_FIELDS));

        if (empty($fields)) {
            $response->getBody()->write(json_encode(['error' => 'No valid fields'], JSON_THROW_ON_ERROR));
            return $response->withStatus(422)->withHeader('Content-Type', 'application/json');
        }

        $this->repository->updateFields($id, $fields);

        $updated = $this->repository->findById($id);
        $response->getBody()->write(json_encode($updated, JSON_THROW_ON_ERROR));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
