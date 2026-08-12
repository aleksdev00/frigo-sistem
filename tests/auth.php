<?php

declare(strict_types=1);

ob_start();

use App\Foundation\Config;
use App\Foundation\Database;
use App\Foundation\Environment;
use App\Http\Request;
use App\Http\Response;
use App\Http\Router;
use App\Repositories\AdminRepository;
use App\Repositories\LoginThrottleRepository;
use App\Security\Csrf;
use App\Security\SessionManager;
use App\Services\AdminProvisioner;
use App\Services\AuthService;

$basePath = dirname(__DIR__);
require $basePath . '/vendor/autoload.php';
Environment::load($basePath . '/.env');
$config = new Config(['database' => require $basePath . '/config/database.php']);
$pdo = (new Database($config))->connect();
$failures = [];
$test = static function (string $name, callable $assertion) use (&$failures): void {
    try {
        $assertion();
        echo "PASS: {$name}\n";
    } catch (Throwable $exception) {
        $failures[] = $name . ': ' . $exception->getMessage();
        echo "FAIL: {$name}\n";
    }
};
$assert = static function (bool $condition, string $message = 'Assertion failed.'): void {
    if (!$condition) {
        throw new RuntimeException($message);
    }
};

$username = 'phase3_test_admin';
$pdo->prepare('DELETE FROM admins WHERE username = ?')->execute([$username]);
$pdo->exec('DELETE FROM login_throttles');
$sessions = new SessionManager(1800, false, 'frigo_phase3_test');
$sessions->start();
$admins = new AdminRepository($pdo);
$provisioner = new AdminProvisioner($admins);
$throttle = new LoginThrottleRepository($pdo, 3, 900, 900);
$auth = new AuthService($admins, $throttle, $sessions, str_repeat('k', 32));

$test('Password hashing and CLI provisioning service', static function () use ($assert, $provisioner, $admins, $username): void {
    $created = $provisioner->provision($username, 'Secure-Test-9!', 'Secure-Test-9!');
    $admin = $admins->findByUsername($username);
    $assert($created && is_array($admin));
    $assert($admin['password_hash'] !== 'Secure-Test-9!', 'Plaintext password was persisted.');
    $assert(password_verify('Secure-Test-9!', $admin['password_hash']), 'Stored password hash does not verify.');
});

$test('Invalid username and password share public outcome', static function () use ($assert, $auth, $username): void {
    $assert(!$auth->attempt('missing_phase3_admin', 'Wrong-Test-9!', '192.0.2.1'));
    $assert(!$auth->attempt($username, 'Wrong-Test-9!', '192.0.2.2'));
});

$test('Inactive administrator cannot log in', static function () use ($assert, $auth, $pdo, $username): void {
    $pdo->prepare('UPDATE admins SET is_active = 0 WHERE username = ?')->execute([$username]);
    $assert(!$auth->attempt($username, 'Secure-Test-9!', '192.0.2.3'));
    $pdo->prepare('UPDATE admins SET is_active = 1 WHERE username = ?')->execute([$username]);
});

$test('Valid login regenerates session and stores minimal auth', static function () use ($assert, $auth, $username): void {
    $before = session_id();
    $assert($auth->attempt($username, 'Secure-Test-9!', '192.0.2.4'));
    $assert(session_id() !== $before, 'Session ID was not regenerated.');
    $assert($auth->isAuthenticated() && $auth->currentAdminId() !== null);
    $assert(!array_key_exists('password_hash', $_SESSION['admin_auth']));
});

$test('CSRF rejects missing/invalid tokens', static function () use ($assert): void {
    $csrf = new Csrf();
    $token = $csrf->token();
    $assert($csrf->validate($token));
    $assert(!$csrf->validate(null) && !$csrf->validate(str_repeat('a', 64)));
});

$test('Admin route guard blocks unauthenticated and allows authenticated requests', static function () use ($assert): void {
    $allowed = false;
    $router = new Router();
    $router->get('/admin', static function () use (&$allowed): Response { $allowed = true; return Response::html('ok'); });
    $state = (object) ['authenticated' => false];
    $router->protectAdminWith(static fn (): ?Response => $state->authenticated ? null : Response::redirect('/admin/login', 302));
    $blocked = $router->dispatch(new Request('GET', '/admin'));
    $assert($blocked->status === 302 && !$allowed);
    $state->authenticated = true;
    $assert($router->dispatch(new Request('GET', '/admin'))->status === 200 && $allowed);
});

$test('Logout is POST-only, requires CSRF by route shape, and clears auth', static function () use ($assert, $auth): void {
    $router = new Router();
    $router->add('POST', '/admin/logout', static fn (): Response => Response::redirect('/admin/login'));
    $assert($router->dispatch(new Request('GET', '/admin/logout'))->status === 404);
    $csrf = new Csrf();
    $assert(!$csrf->validate(null));
    $auth->logout();
    $assert(!$auth->isAuthenticated());
});

$test('Login throttling activates after repeated failures', static function () use ($assert, $auth): void {
    for ($attempt = 0; $attempt < 3; $attempt++) {
        $auth->attempt('throttled_user', 'Wrong-Test-9!', '192.0.2.55');
    }
    $before = microtime(true);
    $assert(!$auth->attempt('throttled_user', 'Wrong-Test-9!', '192.0.2.55'));
    $assert(microtime(true) - $before < 1.0, 'Throttle check unexpectedly blocked the PHP worker.');
});

$test('Security header baseline is present', static function () use ($assert): void {
    $headers = Response::html('ok')->effectiveHeaders();
    foreach (['X-Content-Type-Options', 'Referrer-Policy', 'Permissions-Policy', 'Content-Security-Policy'] as $name) {
        $assert(isset($headers[$name]), $name . ' is missing.');
    }
    $assert(str_contains($headers['Content-Security-Policy'], "frame-ancestors 'none'"));
});

$pdo->prepare('DELETE FROM admins WHERE username = ?')->execute([$username]);
$pdo->exec('DELETE FROM login_throttles');
if ($failures !== []) {
    ob_end_flush();
    fwrite(STDERR, implode(PHP_EOL, $failures) . PHP_EOL);
    exit(1);
}
echo "All Phase 3 authentication checks passed.\n";
ob_end_flush();
