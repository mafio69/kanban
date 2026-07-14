<?php

declare(strict_types=1);

namespace App\Application;

use DI\ContainerBuilder;
use Dotenv\Dotenv;
use Slim\App;
use Slim\Factory\AppFactory as SlimAppFactory;

final class AppFactory
{
    public static function create(): App
    {
        self::loadEnv();

        $builder = new ContainerBuilder();
        $builder->addDefinitions(require __DIR__ . '/../../config/dependencies.php');
        $container = $builder->build();

        SlimAppFactory::setContainer($container);
        $app = SlimAppFactory::create();

        $app->addBodyParsingMiddleware();
        $app->addRoutingMiddleware();
        $app->addErrorMiddleware((bool) ($_ENV['APP_DEBUG'] ?? false), true, true);

        (require __DIR__ . '/../../config/routes.php')($app);

        return $app;
    }

    private static function loadEnv(): void
    {
        $root = dirname(__DIR__, 2);

        if (is_file($root . '/.env')) {
            Dotenv::createImmutable($root)->safeLoad();
        }
    }
}
