<?php

if (!function_exists('connectToDatabase')) {
    function connectToDatabase(string $host, string $port, string $database, string $username, string $password, string $charset, array $options): PDO
    {
        $dsn = "mysql:host={$host};port={$port};dbname={$database};charset={$charset}";

        try {
            return new PDO($dsn, $username, $password, $options);
        } catch (PDOException $exception) {
            if (($exception->errorInfo[1] ?? null) === 1049) {
                createConfiguredDatabase($host, $port, $database, $username, $password, $charset, $options);

                return new PDO($dsn, $username, $password, $options);
            }

            throw $exception;
        }
    }
}

if (!function_exists('createConfiguredDatabase')) {
    function createConfiguredDatabase(string $host, string $port, string $database, string $username, string $password, string $charset, array $options): void
    {
        $serverDsn = "mysql:host={$host};port={$port};charset={$charset}";
        $server = new PDO($serverDsn, $username, $password, $options);
        $quotedDatabase = str_replace('`', '``', $database);
        $server->exec("CREATE DATABASE IF NOT EXISTS `{$quotedDatabase}` CHARACTER SET {$charset} COLLATE {$charset}_unicode_ci");
    }
}

return static function (): PDO {
    $configuredHost = getenv('DB_HOST');
    $host = $configuredHost ?: '127.0.0.1';
    $port = getenv('DB_PORT') ?: '3306';
    $database = getenv('DB_DATABASE') ?: 'fiesta_ochentera';
    $username = getenv('DB_USERNAME') ?: 'root';
    $password = getenv('DB_PASSWORD') ?: '';
    $charset = getenv('DB_CHARSET') ?: 'utf8mb4';

    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    $hosts = [$host];

    if ($configuredHost === false && $host === '127.0.0.1') {
        $hosts[] = 'localhost';
    }

    $lastException = null;

    foreach (array_unique($hosts) as $candidateHost) {
        try {
            return connectToDatabase($candidateHost, $port, $database, $username, $password, $charset, $options);
        } catch (PDOException $exception) {
            $lastException = $exception;
        }
    }

    throw $lastException;
};

if (!function_exists('connectToDatabase')) {
    function connectToDatabase(string $host, string $port, string $database, string $username, string $password, string $charset, array $options): PDO
    {
        $dsn = "mysql:host={$host};port={$port};dbname={$database};charset={$charset}";

        try {
            return new PDO($dsn, $username, $password, $options);
        } catch (PDOException $exception) {
            if (($exception->errorInfo[1] ?? null) === 1049) {
                createConfiguredDatabase($host, $port, $database, $username, $password, $charset, $options);

                return new PDO($dsn, $username, $password, $options);
            }

            throw $exception;
        }
    }
}

if (!function_exists('createConfiguredDatabase')) {
    function createConfiguredDatabase(string $host, string $port, string $database, string $username, string $password, string $charset, array $options): void
    {
        $serverDsn = "mysql:host={$host};port={$port};charset={$charset}";
        $server = new PDO($serverDsn, $username, $password, $options);
        $quotedDatabase = str_replace('`', '``', $database);
        $server->exec("CREATE DATABASE IF NOT EXISTS `{$quotedDatabase}` CHARACTER SET {$charset} COLLATE {$charset}_unicode_ci");
    }
}
