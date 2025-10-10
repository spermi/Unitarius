<?php
declare(strict_types=1);

namespace Core;

use Throwable;

final class ErrorHandler
{
    public static function register(string $env = 'production'): void
    {
        ini_set('display_errors', $env === 'local' ? '1' : '0');
        error_reporting(E_ALL);

        set_error_handler(function ($severity, $message, $file, $line) {
            throw new \ErrorException($message, 0, $severity, $file, $line);
        });

        set_exception_handler(function (Throwable $e) use ($env) {
            self::log($e);
            http_response_code(500);

            if ($env === 'local') {
                echo self::renderDev($e);
                return;
            }

            try {
                echo View::render('errors/500', [
                    'title'   => 'Server Error',
                    'message' => $e->getMessage(),
                ], null);
            } catch (Throwable) {
                echo '500 Internal Server Error';
            }
        });
    }

    public static function panic(string $msg, Throwable $e): void
    {
        self::log($e);
        http_response_code(500);

        try {
            echo View::render('errors/500', [
                'title'   => 'Server Error',
                'message' => $msg,
            ], null);
        } catch (Throwable) {
            echo '500 Internal Server Error';
        }
        exit;
    }

    private static function log(Throwable $e): void
    {
        $dir = dirname(__DIR__, 2) . '/storage/logs';
        if (!is_dir($dir)) { @mkdir($dir, 0777, true); }
        $line = sprintf("[%s] %s in %s:%d\nStack:\n%s\n\n",
            date('c'), $e->getMessage(), $e->getFile(), $e->getLine(), $e->getTraceAsString()
        );
        @file_put_contents($dir . '/app.log', $line, FILE_APPEND);
    }

    private static function renderDev(Throwable $e): string
    {
        $msg  = htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        $file = htmlspecialchars($e->getFile(), ENT_QUOTES, 'UTF-8');
        $line = (int)$e->getLine();
        $trace = htmlspecialchars($e->getTraceAsString(), ENT_QUOTES, 'UTF-8');

        return "<pre style='font:14px/1.4 monospace'>
<b>{$msg}</b>
{$file}:{$line}

{$trace}
</pre>";
    }
}
