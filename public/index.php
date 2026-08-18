<?php

declare(strict_types=1);

use App\Foundation\Application;
use App\Http\Request;

try {
    /** @var Application $app */
    $app = require dirname(__DIR__) . '/bootstrap/app.php';
    $app->handle(Request::fromGlobals())->send();
} catch (Throwable $exception) {
    $debug = filter_var($_ENV['APP_DEBUG'] ?? $_SERVER['APP_DEBUG'] ?? getenv('APP_DEBUG') ?: false, FILTER_VALIDATE_BOOL);
    error_log('Application startup failed [' . $exception::class . '].');
    http_response_code(500);
    header('Content-Type: text/plain; charset=UTF-8');
    echo $debug ? 'The application could not start: ' . $exception->getMessage() : 'The application could not start.';
}
