<?php

declare(strict_types=1);

use App\Foundation\Config;
use App\Foundation\Database;
use App\Foundation\Environment;
use App\Repositories\AdminRepository;
use App\Services\AdminProvisioner;

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit(1);
}

$basePath = dirname(__DIR__);
require $basePath . '/vendor/autoload.php';
Environment::load($basePath . '/.env');

if (($argv[1] ?? null) !== 'create' || count($argv) > 3) {
    fwrite(STDERR, "Usage: php database/admin.php create [username]\n");
    exit(1);
}

$readLine = static function (string $prompt): string {
    fwrite(STDOUT, $prompt);
    $value = fgets(STDIN);
    if ($value === false) {
        throw new RuntimeException('Unable to read console input.');
    }
    return rtrim($value, "\r\n");
};
$readPassword = static function (string $prompt) use ($readLine): string {
    fwrite(STDOUT, $prompt);
    $hidden = DIRECTORY_SEPARATOR === '\\'
        ? trim((string) shell_exec('powershell -NoProfile -Command "$p=Read-Host -AsSecureString; $b=[Runtime.InteropServices.Marshal]::SecureStringToBSTR($p); try {[Runtime.InteropServices.Marshal]::PtrToStringBSTR($b)} finally {[Runtime.InteropServices.Marshal]::ZeroFreeBSTR($b)}"'))
        : null;
    if (DIRECTORY_SEPARATOR !== '\\' && function_exists('shell_exec')) {
        shell_exec('stty -echo 2>/dev/null');
        $hidden = fgets(STDIN);
        shell_exec('stty echo 2>/dev/null');
        fwrite(STDOUT, PHP_EOL);
        $hidden = $hidden === false ? null : rtrim($hidden, "\r\n");
    }
    if (!is_string($hidden) || $hidden === '') {
        fwrite(STDOUT, "\nWarning: hidden input is unavailable; input will be visible.\n");
        return $readLine('Password: ');
    }
    return $hidden;
};

try {
    $username = isset($argv[2]) ? (string) $argv[2] : $readLine('Username: ');
    $password = $readPassword('Password: ');
    $confirmation = $readPassword('Confirm password: ');
    $config = new Config(['database' => require $basePath . '/config/database.php']);
    $provisioner = new AdminProvisioner(new AdminRepository((new Database($config))->connect()));
    $created = $provisioner->provision($username, $password, $confirmation);
    unset($password, $confirmation);
    fwrite(STDOUT, $created ? "Administrator created successfully.\n" : "Administrator password replaced successfully.\n");
} catch (Throwable $exception) {
    fwrite(STDERR, 'Administrator provisioning failed: ' . $exception->getMessage() . PHP_EOL);
    exit(1);
}
