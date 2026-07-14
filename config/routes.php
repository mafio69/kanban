<?php

declare(strict_types=1);

use App\Controller\HealthController;
use Slim\App;

return function (App $app): void {
    $app->get('/api/health', [HealthController::class, 'index']);
};
