<?php

declare(strict_types=1);

use App\Controller\HealthController;
use App\Controller\SprawaController;
use Slim\App;

return function (App $app): void {
    $app->get('/api/health', [HealthController::class, 'index']);
    $app->get('/api/sprawy', [SprawaController::class, 'list']);
};
