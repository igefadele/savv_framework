<?php
namespace Savv\Utils;

/**
 * Writes simple structured application logs to daily files in `storage/logs`.
 */
class Log
{
    /**
     * Append a formatted log entry to the current day's log file.
     *
     * @param string $level Log severity level.
     * @param string $message Primary log message.
     * @param array<string, mixed> $context Extra structured context to encode as JSON.
     * @return void
     */
    protected static function write(string $level, string $message, array $context = []): void
    {
        $logDir = ROOT_PATH . '/storage/logs';
        
        // 1. Ensure the directory exists
        if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }

        // 2. Define the filename based on today's date
        $filePath = $logDir . '/' . date('Y-m-d') . '.log';

        // 3. Format the log entry
        // [2026-04-15 14:30:01] INFO: User logged in. {"user_id": 5}
        $timestamp = date('Y-m-d H:i:s');
        $contextString = !empty($context) ? ' ' . json_encode($context) : '';
        $formattedMessage = "[$timestamp] " . strtoupper($level) . ": {$message}{$contextString}" . PHP_EOL;

        // 4. Append to file (FILE_APPEND) and lock it while writing (LOCK_EX)
        file_put_contents($filePath, $formattedMessage, FILE_APPEND | LOCK_EX);
    }

    /**
     * Write an informational log entry.
     *
     * @param string $message Primary log message.
     * @param array<string, mixed> $context Extra structured context.
     * @return void
     */
    public static function info(string $message, array $context = []): void
    {
        self::write('info', $message, $context);
    }

    /**
     * Write an error log entry.
     *
     * @param string $message Primary log message.
     * @param array<string, mixed> $context Extra structured context.
     * @return void
     */
    public static function error(string $message, array $context = []): void
    {
        self::write('error', $message, $context);
    }

    /**
     * Write a warning log entry.
     *
     * @param string $message Primary log message.
     * @param array<string, mixed> $context Extra structured context.
     * @return void
     */
    public static function warning(string $message, array $context = []): void
    {
        self::write('warning', $message, $context);
    }

    /**
     * Write a debug log entry.
     *
     * @param string $message Primary log message.
     * @param array<string, mixed> $context Extra structured context.
     * @return void
     */
    public static function debug(string $message, array $context = []): void
    {
        self::write('debug', $message, $context);
    }
}
