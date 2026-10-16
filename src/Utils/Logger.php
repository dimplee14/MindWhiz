<?php

namespace App\Utils;

/**
 * Logger Utility Class
 * Centralized logging functionality
 */
class Logger
{
    private static string $logFile = '';

    /**
     * Initialize logger
     */
    private static function init(): void
    {
        if (empty(self::$logFile)) {
            self::$logFile = STORAGE_PATH . '/logs/app.log';
            
            // Create logs directory if it doesn't exist
            $logDir = dirname(self::$logFile);
            if (!is_dir($logDir)) {
                mkdir($logDir, 0755, true);
            }
        }
    }

    /**
     * Log info message
     */
    public static function info(string $message, array $context = []): void
    {
        self::log('INFO', $message, $context);
    }

    /**
     * Log error message
     */
    public static function error(string $message, array $context = []): void
    {
        self::log('ERROR', $message, $context);
    }

    /**
     * Log warning message
     */
    public static function warning(string $message, array $context = []): void
    {
        self::log('WARNING', $message, $context);
    }

    /**
     * Log debug message
     */
    public static function debug(string $message, array $context = []): void
    {
        if (env('APP_DEBUG', false)) {
            self::log('DEBUG', $message, $context);
        }
    }

    /**
     * Write log entry
     */
    private static function log(string $level, string $message, array $context = []): void
    {
        self::init();

        $timestamp = date('Y-m-d H:i:s');
        $contextStr = empty($context) ? '' : ' ' . json_encode($context);
        $logMessage = "[$timestamp] [$level] $message$contextStr\n";

        error_log($logMessage, 3, self::$logFile);
    }

    /**
     * Clear log file
     */
    public static function clear(): void
    {
        self::init();
        if (file_exists(self::$logFile)) {
            file_put_contents(self::$logFile, '');
        }
    }
}
