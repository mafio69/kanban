<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Application\AppFactory;
use PHPUnit\Framework\TestCase;
use Slim\Psr7\Factory\ServerRequestFactory;

// Realny blad aplikacji: /api/sprawy przy nieistniejacej bazie -> 500 -> log.
// Nie mockujemy niczego — PDO naprawde probuje sie polaczyc i naprawde rzuca.
final class BladBazyLogowanyTest extends TestCase
{
    private string|false $origDbName;

    protected function setUp(): void
    {
        $this->origDbName = getenv('DB_NAME');
    }

    protected function tearDown(): void
    {
        $this->origDbName === false ? putenv('DB_NAME') : putenv('DB_NAME=' . $this->origDbName);
    }

    public function testBladBazyDaje500ITrafiaDoLogu(): void
    {
        $marker = 'baza_nie_istnieje_' . bin2hex(random_bytes(4));
        putenv('DB_NAME=' . $marker);

        $logFile = dirname(__DIR__, 2) . '/var/log/php-error.log';
        @unlink($logFile);

        $app = AppFactory::create();
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/api/sprawy');

        $response = $app->handle($request);

        self::assertSame(500, $response->getStatusCode());
        self::assertStringContainsString($marker, (string) @file_get_contents($logFile));
    }
}
