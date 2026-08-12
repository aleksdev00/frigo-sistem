<?php

declare(strict_types=1);

namespace App\View;

final readonly class View
{
    public function __construct(private string $basePath)
    {
    }

    public function render(string $template, array $data = [], string $layout = 'layouts/public'): string
    {
        $content = $this->capture($template, $data);
        return $this->capture($layout, [...$data, 'content' => $content]);
    }

    private function capture(string $template, array $data): string
    {
        if (!preg_match('#^[a-zA-Z0-9/_-]+$#', $template)) {
            throw new \InvalidArgumentException('Invalid view name.');
        }

        $path = $this->basePath . '/' . $template . '.php';
        if (!is_file($path)) {
            throw new \RuntimeException('View not found: ' . $template);
        }

        extract($data, EXTR_SKIP);
        ob_start();
        try {
            require $path;
            return (string) ob_get_clean();
        } catch (\Throwable $exception) {
            ob_end_clean();
            throw $exception;
        }
    }
}
