<?php

declare(strict_types=1);

namespace App\Foundation;

use App\Http\Request;
use App\Http\Response;
use App\Http\Router;
use Throwable;

final readonly class Application
{
    public function __construct(private Router $router, private ErrorHandler $errors)
    {
    }

    public function handle(Request $request): Response
    {
        try {
            return $this->router->dispatch($request);
        } catch (Throwable $exception) {
            return $this->errors->render($exception);
        }
    }
}
