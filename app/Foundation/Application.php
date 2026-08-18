<?php

declare(strict_types=1);

namespace App\Foundation;

use App\Http\Request;
use App\Http\Response;
use App\Http\Router;
use Throwable;

final readonly class Application
{
    public function __construct(private Router $router, private ErrorHandler $errors, private bool $production = false)
    {
    }

    public function handle(Request $request): Response
    {
        try {
            $response = $this->router->dispatch($request);
            if ($this->production && $request->secure) {
                $response = $response->withHeaders(['Strict-Transport-Security' => 'max-age=31536000']);
            }
            return $response;
        } catch (Throwable $exception) {
            return $this->errors->render($exception);
        }
    }
}
