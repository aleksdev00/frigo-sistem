<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\AnalyticsRepository;
use DateTimeImmutable;
use InvalidArgumentException;

final readonly class ProductViewService
{
    public function __construct(private AnalyticsRepository $analytics, private string $appKey, private int $cooldownSeconds = 1800) {}

    public function recordView(int $productId, array $visitorContext, ?DateTimeImmutable $now = null): bool
    {
        if ($productId < 1 || !$this->analytics->productExists($productId)) {
            throw new InvalidArgumentException('A valid existing product is required.');
        }
        $visitorToken = trim((string) ($visitorContext['visitor_token'] ?? ''));
        if ($visitorToken === '') {
            throw new InvalidArgumentException('A visitor token is required.');
        }
        $now ??= new DateTimeImmutable();
        $hash = hash_hmac('sha256', $visitorToken, $this->appKey);
        $since = $now->modify('-' . max(1, $this->cooldownSeconds) . ' seconds');
        if ($this->analytics->hasRecentView($productId, $hash, $since)) {
            return false;
        }
        $this->analytics->insertView($productId, $hash, $now);
        return true;
    }
}
