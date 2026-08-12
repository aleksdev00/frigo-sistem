<?php

declare(strict_types=1);

namespace App\Foundation;

use App\Http\Response;
use App\View\View;
use ErrorException;
use Throwable;

final readonly class ErrorHandler
{
    public function __construct(
        private Logger $logger,
        private bool $debug,
        private string $viewPath,
    ) {
    }

    public function register(): void
    {
        ini_set('display_errors', $this->debug ? '1' : '0');
        error_reporting(E_ALL);

        set_error_handler(static function (int $severity, string $message, string $file, int $line) {
            if (!(error_reporting() & $severity)) {
                return false;
            }
            throw new ErrorException($message, 0, $severity, $file, $line);
        });

        register_shutdown_function(function (): void {
            $error = error_get_last();
            if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
                $this->logger->error('Fatal PHP error', ['type' => $error['type'], 'message' => $error['message'], 'file' => $error['file'], 'line' => $error['line']]);
            }
        });
    }

    public function render(Throwable $exception): Response
    {
        $this->logger->error($exception::class . ': ' . $exception->getMessage(), [
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
        ]);

        if ($this->debug) {
            return Response::html('<h1>Application error</h1><pre>' . htmlspecialchars($exception->__toString(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</pre>', 500);
        }

        $view = new View($this->viewPath);
        return Response::html($view->render('errors/500', ['title' => 'Error', 'appName' => 'Frigo Sistem']), 500);
    }
}
