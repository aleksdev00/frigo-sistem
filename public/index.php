<?php

declare(strict_types=1);

use App\Foundation\Application;
use App\Http\Request;

try {
    /** @var Application $app */
    $app = require dirname(__DIR__) . '/bootstrap/app.php';
    $app->handle(Request::fromGlobals())->send();
} catch (Throwable $exception) {
    error_log($exception->__toString());
    http_response_code(500);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'The application could not start.';
}
