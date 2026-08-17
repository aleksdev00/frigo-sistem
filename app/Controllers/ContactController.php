<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Foundation\Config;
use App\Foundation\Logger;
use App\Http\Request;
use App\Http\Response;
use App\Repositories\PublicProductRepository;
use App\Security\Csrf;
use App\Services\ContactAntiSpam;
use App\Services\ContactRateLimiter;
use App\Services\ContactService;
use App\View\View;
use Throwable;

final readonly class ContactController
{
    public function __construct(
        private PublicProductRepository $products,
        private ContactService $contact,
        private ContactAntiSpam $antiSpam,
        private ContactRateLimiter $rateLimiter,
        private Csrf $csrf,
        private View $view,
        private Config $config,
        private Logger $logger,
    ) {}

    public function show(Request $request): Response
    {
        $product = $this->resolveProduct($request->query['product'] ?? null);
        return $this->page($request, product: $product, sent: ($request->query['sent'] ?? null) === '1');
    }

    public function submit(Request $request): Response
    {
        $product = $this->resolveProduct($request->input['product'] ?? null);
        if (!$this->csrf->validate($request->input['_csrf'] ?? null)) {
            return $this->page($request, product: $product, values: $this->values($request->input), globalError: 'Zahtev nije mogao biti potvrđen. Osvežite stranicu i pokušajte ponovo.', status: 419);
        }
        if (!$this->antiSpam->accepts($request->input)) {
            return $this->page($request, product: $product, values: $this->values($request->input), globalError: 'Poruka nije poslata. Osvežite stranicu i pokušajte ponovo.', status: 422);
        }

        $validation = $this->contact->validate($request->input);
        if (!$validation->isValid()) return $this->page($request, product: $product, values: $validation->values, errors: $validation->errors, status: 422);

        try {
            if (!$this->rateLimiter->consume($request->clientIp)) {
                return $this->page($request, product: $product, values: $validation->values, globalError: 'Poslato je previše poruka. Sačekajte i pokušajte ponovo kasnije.', status: 429);
            }
            $this->contact->deliver($validation->values, $product);
        } catch (Throwable $exception) {
            $this->logger->error('Contact delivery failed.', ['exception' => $exception::class]);
            return $this->page($request, product: $product, values: $validation->values, globalError: 'Došlo je do problema prilikom slanja poruke. Pokušajte ponovo.', status: 503);
        }

        $this->csrf->rotate();
        return Response::redirect('/kontakt?sent=1');
    }

    private function page(Request $request, ?array $product = null, array $values = [], array $errors = [], ?string $globalError = null, bool $sent = false, int $status = 200): Response
    {
        $baseUrl = rtrim((string) $this->config->get('app.url', 'http://localhost'), '/');
        return new Response($this->view->render('contact/index', [
            'title' => 'Kontakt | Frigo Sistem Niš',
            'metaDescription' => 'Kontaktirajte Frigo Sistem za informacije o klima uređajima, ugradnji i servisu.',
            'canonical' => $baseUrl . '/kontakt',
            'robots' => $request->query === [] ? 'index, follow' : 'noindex, follow',
            'appName' => (string) $this->config->get('app.name', 'Frigo Sistem'),
            'csrfToken' => $this->csrf->token(),
            'antiSpam' => $this->antiSpam->fields(),
            'product' => $product,
            'values' => $values + ['name' => '', 'email' => '', 'phone' => '', 'message' => ''],
            'errors' => $errors,
            'globalError' => $globalError,
            'sent' => $sent,
        ]), $status, ['Content-Type' => 'text/html; charset=UTF-8', 'Cache-Control' => 'no-store']);
    }

    private function resolveProduct(mixed $reference): ?array
    {
        if (!is_string($reference)) return null;
        $reference = strtolower(trim($reference));
        if (preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/D', $reference) !== 1 || strlen($reference) > 190) return null;
        return $this->products->findBySlug($reference);
    }

    private function values(array $input): array
    {
        $value = static fn (string $key): string => is_string($input[$key] ?? null) ? trim((string) $input[$key]) : '';
        return ['name' => $value('name'), 'email' => $value('email'), 'phone' => $value('phone'), 'message' => $value('message')];
    }
}
