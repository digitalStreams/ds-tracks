<?php
/**
 * DS-Tracks - Central Configuration File
 * Version 2.0 - Security Hardened
 */

// Prevent direct browser access to this config file
if (basename($_SERVER['SCRIPT_FILENAME'] ?? '') === basename(__FILE__)) {
    http_response_code(403);
    die('Direct access not permitted');
}

// Base directory configuration
define('MUSIC_BASE_DIR', __DIR__ . '/music/');
define('LOG_DIR', __DIR__ . '/logs/');

// Ensure log directory exists
if (!file_exists(LOG_DIR)) {
    mkdir(LOG_DIR, 0755, true);
}

// File upload configuration
define('MAX_FILE_SIZE', 50 * 1024 * 1024); // 50MB
define('ALLOWED_AUDIO_EXTENSIONS', ['mp3', 'wav', 'ogg', 'flac', 'm4a']);
define('ALLOWED_MIME_TYPES', [
    'audio/mpeg',
    'audio/mp3',
    'audio/wav',
    'audio/wave',
    'audio/x-wav',
    'audio/ogg',
    'audio/flac',
    'audio/x-m4a',
    'audio/mp4'
]);

// Session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS
ini_set('session.cookie_samesite', 'Strict');

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', '0'); // Don't display errors to users
ini_set('log_errors', '1');
ini_set('error_log', LOG_DIR . 'php_errors.log');

// Security functions
class DSSecurity {

    /**
     * Sanitize username input
     */
    public static function sanitizeUsername($username) {
        return preg_replace('/[^a-zA-Z0-9_-]/', '', $username);
    }

    /**
     * Sanitize session/directory name input
     */
    public static function sanitizeSessionName($sessionName) {
        return preg_replace('/[^a-zA-Z0-9_-]/', '', $sessionName);
    }

    /**
     * Validate that a path is within the music directory
     */
    public static function isValidMusicPath($path) {
        $realPath = realpath($path);
        $baseDir = realpath(MUSIC_BASE_DIR);

        if ($realPath === false || $baseDir === false) {
            return false;
        }

        return strpos($realPath, $baseDir) === 0;
    }

    /**
     * Sanitize filename for safe storage
     */
    public static function sanitizeFilename($filename) {
        // Get file extension
        $pathInfo = pathinfo($filename);
        $extension = strtolower($pathInfo['extension'] ?? '');
        $basename = $pathInfo['filename'] ?? '';

        // Remove special characters from basename
        $safeBasename = preg_replace('/[^a-zA-Z0-9_\-\(\)\[\] ]/', '_', $basename);
        $safeBasename = substr($safeBasename, 0, 200);

        return $safeBasename . '.' . $extension;
    }

    /**
     * Log error messages
     */
    public static function logError($message, $logFile = 'app_errors.log') {
        $logPath = LOG_DIR . $logFile;
        $timestamp = date('[Y-m-d H:i:s]');
        $logMessage = $timestamp . ' ' . $message . PHP_EOL;
        error_log($logMessage, 3, $logPath);
    }

    /**
     * Log info messages
     */
    public static function logInfo($message, $logFile = 'app_info.log') {
        $logPath = LOG_DIR . $logFile;
        $timestamp = date('[Y-m-d H:i:s]');
        $logMessage = $timestamp . ' ' . $message . PHP_EOL;
        error_log($logMessage, 3, $logPath);
    }

    /**
     * Escape output for HTML display
     */
    public static function escapeHtml($string) {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Generate CSRF token
     */
    public static function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Validate CSRF token
     */
    public static function validateCSRFToken($token) {
        if (!isset($_SESSION['csrf_token'])) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }
}

// Helper functions for backward compatibility
function sanitizeInput($input, $allowDash = true) {
    return DSSecurity::sanitizeSessionName($input);
}

function isValidMusicPath($path) {
    return DSSecurity::isValidMusicPath($path);
}

function logError($message) {
    DSSecurity::logError($message);
}

function logInfo($message) {
    DSSecurity::logInfo($message);
}

?>
