<?php

namespace InstagramClone\Config;

class Config
{
    public static function init(): void
    {
        // Load environment variables
        if (file_exists(__DIR__ . '/../../.env')) {
            $dotenv = \Dotenv\Dotenv::createImmutable(__DIR__ . '/../..');
            $dotenv->load();
        }

        // Set timezone
        date_default_timezone_set('UTC');

        // Start session
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Set error reporting
        if ($_ENV['APP_DEBUG'] ?? false) {
            error_reporting(E_ALL);
            ini_set('display_errors', 1);
        } else {
            error_reporting(0);
            ini_set('display_errors', 0);
        }
    }

    public static function get(string $key, $default = null)
    {
        return $_ENV[$key] ?? $default;
    }

    public static function getUploadPath(): string
    {
        return rtrim(self::get('UPLOAD_PATH', 'uploads/'), '/') . '/';
    }

    public static function getMaxFileSize(): int
    {
        return (int) self::get('MAX_FILE_SIZE', 5242880); // 5MB default
    }

    public static function getAllowedExtensions(): array
    {
        $extensions = self::get('ALLOWED_EXTENSIONS', 'jpg,jpeg,png,gif');
        return explode(',', $extensions);
    }

    public static function getAppUrl(): string
    {
        return rtrim(self::get('APP_URL', 'http://localhost:8000'), '/');
    }
}