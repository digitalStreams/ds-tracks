<?php
/**
 * DS-Tracks - USB File Import API
 *
 * Copies selected audio files from the mounted USB drive to the music directory.
 * Applies the same security validation as upload.php (extension, MIME type, filename sanitisation).
 *
 * POST Parameters (JSON body):
 *   username - user's name
 *   files    - array of relative file paths on USB to import
 *
 * Response: {
 *   "success": true,
 *   "session": "Peter-260122-143022",
 *   "copied": 4,
 *   "errors": [],
 *   "tracks": [ { "name": "...", "url": "..." } ]
 * }
 */

header('Content-Type: application/json');

// Configuration
$usbMountPoint = '/media/kcr-usb';
$musicBaseDir = __DIR__ . '/music/';
$statusFile = '/run/kcr-usb-status.json';
$maxFileSize = 50 * 1024 * 1024; // 50MB
$allowedExtensions = ['mp3', 'wav', 'ogg', 'flac', 'm4a'];
$allowedMimeTypes = [
    'audio/mpeg', 'audio/mp3', 'audio/wav', 'audio/wave', 'audio/x-wav',
    'audio/ogg', 'audio/flac', 'audio/x-m4a', 'audio/mp4'
];

// Error logging
function logImportError($message) {
    error_log(date('[Y-m-d H:i:s] ') . $message . PHP_EOL, 3, __DIR__ . '/logs/import_errors.log');
}

function logImportInfo($message) {
    error_log(date('[Y-m-d H:i:s] ') . $message . PHP_EOL, 3, __DIR__ . '/logs/import_info.log');
}

/**
 * Sanitise a filename for safe storage.
 * - Replaces special characters (commas, apostrophes, etc.) with underscores
 * - Collapses multiple spaces to single space
 * - Trims leading/trailing spaces
 * - If no extension, attempts to detect audio type from file content
 */
function sanitiseFilename($filename, $sourcePath = null) {
    $pathInfo = pathinfo($filename);
    $extension = strtolower($pathInfo['extension'] ?? '');
    $basename = $pathInfo['filename'] ?? '';

    // If no extension, try to detect from MIME type
    if (empty($extension) && $sourcePath && is_file($sourcePath)) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $sourcePath);
        finfo_close($finfo);
        $mimeToExt = [
            'audio/mpeg' => 'mp3', 'audio/mp3' => 'mp3',
            'audio/wav' => 'wav', 'audio/wave' => 'wav', 'audio/x-wav' => 'wav',
            'audio/ogg' => 'ogg', 'audio/flac' => 'flac',
            'audio/x-m4a' => 'm4a', 'audio/mp4' => 'm4a',
        ];
        $extension = $mimeToExt[$mime] ?? '';
        if (!empty($extension)) {
            logImportInfo("No extension on '$filename' - detected as .$extension from MIME type");
        }
    }

    // Remove special characters, keep safe ones (letters, numbers, underscore, hyphen, parens, brackets, space)
    $safeBasename = preg_replace('/[^a-zA-Z0-9_\-\(\)\[\] ]/', '_', $basename);
    // Collapse multiple spaces/underscores to single space
    $safeBasename = preg_replace('/\s+/', ' ', $safeBasename);
    $safeBasename = preg_replace('/_+/', '_', $safeBasename);
    $safeBasename = trim($safeBasename);
    $safeBasename = substr($safeBasename, 0, 200);

    if (empty($safeBasename)) {
        $safeBasename = 'track_' . time();
    }

    if (empty($extension)) {
        logImportInfo("No extension detected for '$filename' - skipping");
        return null; // Signal that this file should be flagged
    }

    return $safeBasename . '.' . $extension;
}

// Verify USB is mounted
if (!file_exists($statusFile) || !is_dir($usbMountPoint)) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'No USB drive detected']);
    exit;
}

// Read JSON request body
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['username']) || !isset($input['files']) || !is_array($input['files'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid request: username and files required']);
    exit;
}

// Sanitise username
$username = preg_replace('/[^a-zA-Z0-9_-]/', '', $input['username']);
if (empty($username) || strlen($username) < 3) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid username (3+ characters required)']);
    exit;
}

// Limit number of files per import
$files = $input['files'];
if (count($files) > 100) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Too many files selected (maximum 100)']);
    exit;
}

// Create or use existing session directory
$existingSession = isset($input['session']) ? preg_replace('/[^a-zA-Z0-9_-]/', '', $input['session']) : null;

if ($existingSession && is_dir($musicBaseDir . $existingSession)) {
    // Add to existing session
    $sessionName = $existingSession;
    $sessionDir = $musicBaseDir . $sessionName;
    logImportInfo("Adding to existing session: $sessionName");
} else {
    // Create new session - use passed session name if provided, otherwise generate one
    if ($existingSession) {
        $sessionName = $existingSession;
    } else {
        $dateStamp = date('ymd-His');
        $sessionName = $username . '-' . $dateStamp;
    }
    $sessionDir = $musicBaseDir . $sessionName;

    if (!mkdir($sessionDir, 0755, true)) {
        logImportError("Failed to create session directory: $sessionDir");
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Could not create session directory']);
        exit;
    }
}

// Set cookie for compatibility with existing session system
setcookie('username', $sessionName, [
    'expires' => time() + (14 * 24 * 60 * 60),
    'path' => '/',
    'httponly' => false,  // JS reads this cookie via js.cookie
    'samesite' => 'Strict'
]);

$realMountPoint = realpath($usbMountPoint);
$realMusicBase = realpath($musicBaseDir);
$copied = 0;
$errors = [];
$tracks = [];

foreach ($files as $filePath) {
    // Sanitise the requested path
    $filePath = str_replace('\\', '/', $filePath);
    $filePath = preg_replace('#/+#', '/', $filePath);

    $sourceFullPath = $usbMountPoint . '/' . ltrim($filePath, '/');

    // SECURITY: Verify source is within USB mount point
    $realSource = realpath($sourceFullPath);
    if ($realSource === false || strpos($realSource, $realMountPoint) !== 0) {
        $errors[] = "Skipped (invalid path): " . basename($filePath);
        logImportError("Path traversal attempt: $filePath by user $username");
        continue;
    }

    // Verify it's a file
    if (!is_file($realSource)) {
        $errors[] = "Skipped (not a file): " . basename($filePath);
        continue;
    }

    // Check file size
    $fileSize = filesize($realSource);
    if ($fileSize > $maxFileSize) {
        $errors[] = "Skipped (too large): " . basename($filePath);
        logImportError("File too large: $filePath ($fileSize bytes) by user $username");
        continue;
    }

    // Validate extension
    $originalName = basename($filePath);
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    if (!in_array($extension, $allowedExtensions)) {
        $errors[] = "Skipped (not audio): $originalName";
        continue;
    }

    // Validate MIME type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $realSource);
    finfo_close($finfo);

    if (!in_array($mimeType, $allowedMimeTypes)) {
        $errors[] = "Skipped (invalid format): $originalName";
        logImportError("Invalid MIME type: $mimeType for $originalName by user $username");
        continue;
    }

    // Sanitise destination filename (pass source path for MIME detection of extensionless files)
    $safeFilename = sanitiseFilename($originalName, $realSource);
    if ($safeFilename === null) {
        $errors[] = "Skipped (no audio extension): $originalName";
        continue;
    }
    $destination = $sessionDir . '/' . $safeFilename;

    // Handle duplicate filenames
    if (file_exists($destination)) {
        $counter = 1;
        $baseName = pathinfo($safeFilename, PATHINFO_FILENAME);
        while (file_exists($sessionDir . '/' . $baseName . '_' . $counter . '.' . $extension)) {
            $counter++;
        }
        $safeFilename = $baseName . '_' . $counter . '.' . $extension;
        $destination = $sessionDir . '/' . $safeFilename;
    }

    // SECURITY: Verify destination is within music directory
    // We need to check the parent since the file doesn't exist yet
    $realSessionDir = realpath($sessionDir);
    if ($realSessionDir === false || strpos($realSessionDir, $realMusicBase) !== 0) {
        $errors[] = "Skipped (security): $originalName";
        logImportError("Destination traversal attempt: $destination by user $username");
        continue;
    }

    // Copy the file
    if (copy($realSource, $destination)) {
        chmod($destination, 0644);
        $copied++;

        $tracks[] = [
            'name' => $safeFilename,
            'url' => 'music/' . $sessionName . '/' . rawurlencode($safeFilename)
        ];

        logImportInfo("Imported: $originalName as $safeFilename for user $username");
    } else {
        $errors[] = "Failed to copy: $originalName";
        logImportError("Copy failed: $realSource to $destination for user $username");
    }
}

// If no files were copied, remove the empty session directory
if ($copied === 0) {
    rmdir($sessionDir);
    echo json_encode([
        'success' => false,
        'error' => 'No files could be imported',
        'errors' => $errors
    ]);
    exit;
}

logImportInfo("Import complete: $copied files for user $username in session $sessionName");

echo json_encode([
    'success' => true,
    'session' => $sessionName,
    'copied' => $copied,
    'errors' => $errors,
    'tracks' => $tracks
]);
