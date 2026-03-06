<?php
/**
 * DS-Tracks - Secure File Upload Handler
 * https://www.theserverside.com/blog/Coffee-Talk-Java-News-Stories-and-Opinions/Ajax-JavaScript-file-upload-example
 */

// Start session for better security than cookies alone
session_start();

// Load central configuration (provides constants, sanitizeInput, isValidMusicPath, logError)
require_once __DIR__ . '/config.php';

// Local aliases for config constants
define('ALLOWED_EXTENSIONS', ALLOWED_AUDIO_EXTENSIONS);
define('UPLOAD_BASE_DIR', MUSIC_BASE_DIR);

// Validate and sanitize input
if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    logError('Upload error: ' . ($_FILES['file']['error'] ?? 'No file uploaded'));
    die('Upload failed: Invalid file upload');
}

if (!isset($_COOKIE['username']) || empty($_COOKIE['username'])) {
    logError('Upload attempt without valid username cookie');
    die('Upload failed: Invalid session');
}

// Sanitize username - only allow alphanumeric, dash, and underscore
$username = preg_replace('/[^a-zA-Z0-9_-]/', '', $_COOKIE['username']);
if (empty($username) || strlen($username) < 3) {
    logError('Invalid username format: ' . $_COOKIE['username']);
    die('Upload failed: Invalid username');
}

// Validate file size
if ($_FILES['file']['size'] > MAX_FILE_SIZE) {
    logError('File too large: ' . $_FILES['file']['size'] . ' bytes from user: ' . $username);
    die('Upload failed: File size exceeds maximum allowed (50MB)');
}

// Get original filename and sanitize it
$originalFilename = basename($_FILES['file']['name']);
$pathInfo = pathinfo($originalFilename);
$extension = strtolower($pathInfo['extension'] ?? '');

// Validate file extension
if (!in_array($extension, ALLOWED_EXTENSIONS)) {
    logError('Invalid file type: ' . $extension . ' from user: ' . $username);
    die('Upload failed: Only audio files (mp3, wav, ogg, flac, m4a) are allowed');
}

// Validate MIME type as additional security
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $_FILES['file']['tmp_name']);
finfo_close($finfo);

$allowedMimeTypes = [
    'audio/mpeg',
    'audio/mp3',
    'audio/wav',
    'audio/wave',
    'audio/x-wav',
    'audio/ogg',
    'audio/flac',
    'audio/x-m4a',
    'audio/mp4'
];

if (!in_array($mimeType, $allowedMimeTypes)) {
    logError('Invalid MIME type: ' . $mimeType . ' for file: ' . $originalFilename . ' from user: ' . $username);
    die('Upload failed: File does not appear to be a valid audio file');
}

// Sanitize filename - remove special characters, keep only safe ones
$safeBasename = preg_replace('/[^a-zA-Z0-9_\-\(\)\[\] ]/', '_', $pathInfo['filename']);
$safeBasename = substr($safeBasename, 0, 200); // Limit filename length
$filename = $safeBasename . '.' . $extension;

// Construct safe path
$userDir = UPLOAD_BASE_DIR . $username;

// Prevent directory traversal - ensure the resolved path is within the base directory
$realUserDir = realpath(dirname($userDir));
$realBaseDir = realpath(UPLOAD_BASE_DIR);

// If directory doesn't exist yet, we need to create it first
if (!file_exists($userDir)) {
    if (!mkdir($userDir, 0755, true)) {
        logError('Failed to create directory: ' . $userDir);
        die('Upload failed: Could not create user directory');
    }
}

// Now verify the path is safe
$realUserDir = realpath($userDir);
if ($realUserDir === false || strpos($realUserDir, $realBaseDir) !== 0) {
    logError('Directory traversal attempt detected for user: ' . $username);
    die('Upload failed: Invalid directory path');
}

$destination = $userDir . '/' . $filename;

// Check if file already exists and create unique name if needed
if (file_exists($destination)) {
    $counter = 1;
    while (file_exists($userDir . '/' . $safeBasename . '_' . $counter . '.' . $extension)) {
        $counter++;
    }
    $filename = $safeBasename . '_' . $counter . '.' . $extension;
    $destination = $userDir . '/' . $filename;
}

// Move the uploaded file
if (move_uploaded_file($_FILES['file']['tmp_name'], $destination)) {
    // Set appropriate permissions
    chmod($destination, 0644);

    logError('Successful upload: ' . $filename . ' by user: ' . $username);
    echo 'Success';
} else {
    logError('Failed to move uploaded file: ' . $filename . ' for user: ' . $username);
    echo 'Failure to upload - could not save file';
}

?>

