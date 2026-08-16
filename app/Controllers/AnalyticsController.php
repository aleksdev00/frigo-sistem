<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Http\Request;
use App\Http\Response;
use App\Repositories\AnalyticsRepository;
use App\Support\AdminPage;
use DateTimeImmutable;

final readonly class AnalyticsController
{
    public function __construct(private AnalyticsRepository $analytics, private AdminPage $page) {}

    public function index(Request $request): Response
    {
        $requested = is_string($request->query['range'] ?? null) ? $request->query['range'] : '30';
        $range = in_array($requested, ['7', '30', 'all'], true) ? $requested : '30';
        $since = $range === 'all' ? null : (new DateTimeImmutable('today'))->modify('-' . ((int) $range - 1) . ' days');
        $dailyViews = $this->analytics->dailyViews($since);
        if ($since !== null) {
            $dailyViews = $this->fillDailyRange($dailyViews, $since, new DateTimeImmutable('today'));
        }
        return $this->page->render('admin/analytics', 'Analytics', [
            'range' => $range,
            'summary' => $this->analytics->summary($since),
            'topProducts' => $this->analytics->topProducts($since),
            'topCategories' => $this->analytics->topCategories($since),
            'dailyViews' => $dailyViews,
        ]);
    }

    private function fillDailyRange(array $rows, DateTimeImmutable $start, DateTimeImmutable $end): array
    {
        $counts = [];
        foreach ($rows as $row) {
            $counts[(string) $row['view_date']] = (int) $row['views'];
        }
        $result = [];
        for ($date = $start; $date <= $end; $date = $date->modify('+1 day')) {
            $key = $date->format('Y-m-d');
            $result[] = ['view_date' => $key, 'views' => $counts[$key] ?? 0];
        }
        return $result;
    }
}
