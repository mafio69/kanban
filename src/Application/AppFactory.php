<?php

declare(strict_types=1);

namespace App\Application;

use App\Support\Env;
use DI\ContainerBuilder;
use Dotenv\Dotenv;
use Slim\App;
use Slim\Factory\AppFactory as SlimAppFactory;

final class AppFactory
{
    public static function create(): App
    {
        self::loadEnv();
        self::configureErrorLog();

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

    // Bledy (500) trafiaja do var/log/php-error.log przez error_log PHP — Slim loguje tam,
    // bo addErrorMiddleware ma wlaczone logErrors. Bez dodatkowej biblioteki.
    private static function configureErrorLog(): void
    {
        // LOG_DIR pozwala wskazac wolumin w kontenerze (np. /var/log/app); domyslnie var/log w repo.
        $dir = Env::get('LOG_DIR') ?: dirname(__DIR__, 2) . '/var/log';

        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        ini_set('log_errors', '1');
        ini_set('error_log', $dir . '/php-error.log');
    }
}
