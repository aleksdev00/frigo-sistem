<?php

declare(strict_types=1);

namespace App\Services;

use App\Foundation\Config;

final readonly class SeoService
{
    private string $baseUrl;

    public function __construct(private Config $config)
    {
        $url = rtrim((string) $config->get('app.url', ''), '/');
        if (!$this->validBaseUrl($url)) {
            throw new \RuntimeException('APP_URL must be an absolute HTTP(S) URL without a path, query, or fragment.');
        }
        $this->baseUrl = $url;
    }

    public function url(string $path = '/'): string
    {
        if ($path === '/') return $this->baseUrl . '/';
        if (!str_starts_with($path, '/') || str_contains($path, '?') || str_contains($path, '#')) {
            throw new \InvalidArgumentException('SEO URLs require a known application path.');
        }
        return $this->baseUrl . '/' . ltrim($path, '/');
    }

    public function page(string $path, string $title, string $description, bool $index = true, string $type = 'website', ?string $image = null): array
    {
        $canonical = $this->url($path);
        $robots = $index ? 'index, follow' : 'noindex, follow';
        if (!$this->isProduction()) $robots = 'noindex, nofollow';
        $openGraph = [
            'type' => $type, 'title' => $title, 'description' => $description,
            'url' => $canonical, 'site_name' => (string) $this->config->get('app.name', 'Frigo Sistem'),
        ];
        if ($image !== null && $image !== '') $openGraph['image'] = $this->url('/' . ltrim($image, '/'));
        return compact('title', 'description', 'canonical', 'robots') + ['metaDescription' => $description, 'openGraph' => $openGraph];
    }

    public function breadcrumbs(array $items): array
    {
        return ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => array_map(
            fn (array $item, int $position): array => ['@type' => 'ListItem', 'position' => $position + 1, 'name' => $item['name'], 'item' => $this->url($item['path'])],
            $items,
            array_keys($items),
        )];
    }

    public function isProduction(): bool
    {
        return strtolower((string) $this->config->get('app.env', 'production')) === 'production';
    }

    private function validBaseUrl(string $url): bool
    {
        $parts = parse_url($url);
        return is_array($parts) && in_array($parts['scheme'] ?? '', ['http', 'https'], true)
            && isset($parts['host']) && !isset($parts['user'], $parts['pass'], $parts['query'], $parts['fragment'])
            && (($parts['path'] ?? '') === '' || ($parts['path'] ?? '') === '/');
    }
}
