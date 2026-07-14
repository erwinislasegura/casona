<?php

return static function (): PDO {
    $host = getenv('DB_HOST') ?: '127.0.0.1';
    $port = getenv('DB_PORT') ?: '3306';
    $database = getenv('DB_DATABASE') ?: 'fiesta_ochentera';
    $username = getenv('DB_USERNAME') ?: 'root';
    $password = getenv('DB_PASSWORD') ?: '';
    $charset = getenv('DB_CHARSET') ?: 'utf8mb4';

    $dsn = "mysql:host={$host};port={$port};dbname={$database};charset={$charset}";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    try {
        return new PDO($dsn, $username, $password, $options);
    } catch (PDOException $exception) {
        if (($exception->errorInfo[1] ?? null) !== 1049) {
            throw $exception;
        }

        $serverDsn = "mysql:host={$host};port={$port};charset={$charset}";
        $server = new PDO($serverDsn, $username, $password, $options);
        $quotedDatabase = str_replace('`', '``', $database);
        $server->exec("CREATE DATABASE IF NOT EXISTS `{$quotedDatabase}` CHARACTER SET {$charset} COLLATE {$charset}_unicode_ci");

        return new PDO($dsn, $username, $password, $options);
    }
};
