<?php

namespace App\Utils;

/**
 * Response Helper Class
 * Standard API response formatting
 */
class Response
{
    /**
     * Send JSON response
     */
    public static function json(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    /**
     * Send success response
     */
    public static function success(string $message = 'Success', array $data = [], int $statusCode = 200): void
    {
        self::json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], $statusCode);
    }

    /**
     * Send error response
     */
    public static function error(string $message = 'Error', array $errors = [], int $statusCode = 400): void
    {
        self::json([
            'success' => false,
            'message' => $message,
            'errors' => $errors
        ], $statusCode);
    }

    /**
     * Redirect to URL
     */
    public static function redirect(string $url, int $statusCode = 302): void
    {
        header("Location: $url", true, $statusCode);
        exit;
    }

    /**
     * Redirect back
     */
    public static function back(): void
    {
        $referrer = $_SERVER['HTTP_REFERER'] ?? '/';
        self::redirect($referrer);
    }
}
