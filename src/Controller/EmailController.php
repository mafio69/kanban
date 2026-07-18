<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\SprawaRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class EmailController
{
    public function __construct(private readonly SprawaRepository $repository)
    {
    }

    public function show(Request $request, Response $response, array $args): Response
    {
        $id = (int) $args['id'];
        $sprawa = $this->repository->findByEmailId($id);

        if ($sprawa === null) {
            $response->getBody()->write(json_encode(['error' => 'Not found'], JSON_THROW_ON_ERROR));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        $response->getBody()->write(
            json_encode($sprawa, JSON_THROW_ON_ERROR)
        );

        return $response->withHeader('Content-Type', 'application/json');
    }
}
