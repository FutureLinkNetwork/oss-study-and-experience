<?php

declare(strict_types=1);

/**
 * Ensure the PHPUnit MySQL test database exists before Laravel boots (migrate / RefreshDatabase).
 */
$connection = getenv('DB_CONNECTION') ?: '';
if ($connection === 'mysql') {
    $host = getenv('DB_HOST') ?: '127.0.0.1';
    $port = (int) (getenv('DB_PORT') ?: '3306');
    $username = getenv('DB_USERNAME') ?: 'root';
    $password = getenv('DB_PASSWORD') ?: '';
    $database = getenv('DB_DATABASE') ?: 'fln_voucher_test';
    $database = preg_replace('/[^a-zA-Z0-9_]/', '', $database) ?: 'fln_voucher_test';

    $dsn = sprintf('mysql:host=%s;port=%d', $host, $port);
    try {
        $pdo = new PDO($dsn, $username, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $pdo->exec(sprintf(
            'CREATE DATABASE IF NOT EXISTS `%s` CHARACTER SET utf8mb4 COLLATE utf8mb4_bin',
            str_replace('`', '', $database)
        ));
    } catch (PDOException) {
        // DB unreachable (e.g. host-only run); Laravel will surface the error when needed.
    }
}

require dirname(__DIR__).'/vendor/autoload.php';
