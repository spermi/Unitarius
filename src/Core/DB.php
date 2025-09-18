<?php
declare(strict_types=1);

namespace Core;

use PDO;
use PDOException;
use Throwable;

final class DB
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo === null) {
            $dsn = sprintf(
                'pgsql:host=%s;port=%s;dbname=%s',
                $_ENV['DB_HOST'] ?? 'localhost',
                $_ENV['DB_PORT'] ?? '5432',
                $_ENV['DB_NAME'] ?? ''
            );

            try {
                self::$pdo = new PDO(
                    $dsn,
                    $_ENV['DB_USER'] ?? '',
                    $_ENV['DB_PASS'] ?? '',
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false,
                    ]
                );
            } catch (PDOException $e) {
                // generic message to user; details go to log
                \Core\ErrorHandler::panic('DB connection failed', $e);
            } catch (Throwable $e) {
                \Core\ErrorHandler::panic('Unexpected DB error', $e);
            }
        }
        return self::$pdo;
    }
}
