<?php

declare(strict_types=1);

namespace App\Foundation;

use PDO;

final readonly class Database
{
    public function __construct(private Config $config)
    {
    }

    public function connect(): PDO
    {
        $host = (string) $this->config->get('database.host');
        $port = (int) $this->config->get('database.port', 3306);
        $database = (string) $this->config->get('database.database');
        $charset = (string) $this->config->get('database.charset', 'utf8mb4');

        return new PDO(
            sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', $host, $port, $database, $charset),
            (string) $this->config->get('database.username'),
            (string) $this->config->get('database.password'),
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ],
        );
    }
}
