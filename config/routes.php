<?php

declare(strict_types=1);

use App\Controller\EmailController;
use App\Controller\GmailSyncController;
use App\Controller\HealthController;
use App\Controller\SprawaController;
use Slim\App;

return function (App $app): void {
    $app->get('/api/health', [HealthController::class, 'index']);
    $app->get('/api/sprawy', [SprawaController::class, 'list']);
    $app->post('/api/sync/gmail', [GmailSyncController::class, 'sync']);
    $app->get('/api/sprawy/{id}/email', [EmailController::class, 'show']);
};
