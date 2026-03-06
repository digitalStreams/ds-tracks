<?php
/**
 * DS-Tracks - USB Music Export API (Admin Only)
 *
 * Copies music session folders from the application to a mounted USB drive.
 * Creates a DS-Tracks-Export/ directory on the USB containing session folders
 * and a session-info.json manifest with metadata.
 *
 * Requires admin session (same auth as admin_customize.php).
 *
 * POST Parameters (JSON body):
 *   sessions - array of session folder names to export (empty = export all)
 *
 * Response: {
 *   "success": true,
 *   "exported": 5,
 *   "skipped": 0,
 *   "total_files": 23,
 *   "errors": [],
 *   "export_path": "DS-Tracks-Export"
 * }
 */

header('Content-Type: application/json');

// Load configuration
require_once __DIR__ . '/config.php';

session_start();

// Admin authentication check
if (!isset($_SESSION['admin_logged_in'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Admin authentication required']);
    exit;
}

// Configuration
$usbMountPoint = '/media/kcr-usb';
$statusFile = '/run/kcr-usb-status.json';
$exportDirName = 'DS-Tracks-Export';

// Logging
function logExport($message) {
    error_log(date('[Y-m-d H:i:s] ') . $message . PHP_EOL, 3, __DIR__ . '/logs/export.log');
}

// Verify USB is mounted
if (!file_exists($statusFile) || !is_dir($usbMountPoint)) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'No USB drive detected. Please insert a USB drive.']);
    exit;
}

// Check USB is writable
if (!is_writable($usbMountPoint)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'USB drive is not writable. It may be read-only or full.']);
    exit;
}

// Read JSON request body
$input = json_decode(file_get_contents('php://input'), true);

// Get list of sessions to export
$requestedSessions = [];
if ($input && isset($input['sessions']) && is_array($input['sessions'])) {
    // Export specific sessions
    foreach ($input['sessions'] as $s) {
        $clean = preg_replace('/[^a-zA-Z0-9_-]/', '', $s);
        if (!empty($clean)) {
            $requestedSessions[] = $clean;
        }
    }
}

// Scan music directory for all sessions
$allDirs = array_filter(glob(MUSIC_BASE_DIR . '*'), 'is_dir');
$sessionsToExport = [];

foreach ($allDirs as $dir) {
    if (!isValidMusicPath($dir)) continue;
    $dirName = basename($dir);

    // If specific sessions requested, filter
    if (!empty($requestedSessions) && !in_array($dirName, $requestedSessions)) {
        continue;
    }

    $sessionsToExport[] = $dirName;
}

if (empty($sessionsToExport)) {
    echo json_encode(['success' => false, 'error' => 'No sessions found to export.']);
    exit;
}

// Load session labels
$labelsFile = MUSIC_BASE_DIR . 'session-labels.json';
$labels = [];
if (file_exists($labelsFile)) {
    $data = json_decode(file_get_contents($labelsFile), true);
    if (is_array($data)) $labels = $data;
}

// Create export directory on USB
$exportPath = $usbMountPoint . '/' . $exportDirName;

// If export dir already exists, add a timestamp suffix to avoid overwriting
if (is_dir($exportPath)) {
    $exportDirName = 'DS-Tracks-Export-' . date('ymd-His');
    $exportPath = $usbMountPoint . '/' . $exportDirName;
}

if (!mkdir($exportPath, 0755, true)) {
    logExport("Failed to create export directory: $exportPath");
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Could not create export folder on USB drive.']);
    exit;
}

// SECURITY: Verify export path is within USB mount
$realExportPath = realpath($exportPath);
$realMountPoint = realpath($usbMountPoint);
if ($realExportPath === false || strpos($realExportPath, $realMountPoint) !== 0) {
    logExport("Export path traversal attempt: $exportPath");
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Security error: invalid export path.']);
    exit;
}

$exported = 0;
$skipped = 0;
$totalFiles = 0;
$errors = [];
$manifest = [];

foreach ($sessionsToExport as $sessionName) {
    $sourceDir = MUSIC_BASE_DIR . $sessionName;

    if (!is_dir($sourceDir)) {
        $errors[] = "Session not found: $sessionName";
        $skipped++;
        continue;
    }

    // Create session directory on USB
    $destDir = $exportPath . '/' . $sessionName;
    if (!mkdir($destDir, 0755)) {
        $errors[] = "Could not create folder: $sessionName";
        $skipped++;
        logExport("Failed to create session dir: $destDir");
        continue;
    }

    // Copy all files in this session
    $files = array_diff(scandir($sourceDir), ['.', '..']);
    $sessionFiles = [];
    $sessionErrors = 0;

    foreach ($files as $file) {
        $sourcePath = $sourceDir . '/' . $file;
        $destPath = $destDir . '/' . $file;

        if (!is_file($sourcePath)) continue;

        if (copy($sourcePath, $destPath)) {
            $totalFiles++;
            $sessionFiles[] = $file;
        } else {
            $sessionErrors++;
            $errors[] = "Failed to copy: $sessionName/$file";
            logExport("Copy failed: $sourcePath -> $destPath");
        }
    }

    if (count($sessionFiles) > 0 || $sessionErrors === 0) {
        $exported++;
    } else {
        $skipped++;
    }

    // Parse session metadata from folder name (Username-YYMMDD-HHMMSS)
    $parts = explode('-', $sessionName);
    $username = $parts[0] ?? '';
    $dateStr = $parts[1] ?? '';
    $timeStr = $parts[2] ?? '';

    $formattedDate = '';
    if (strlen($dateStr) === 6) {
        $formattedDate = substr($dateStr, 0, 2) . '-' . substr($dateStr, 2, 2) . '-' . substr($dateStr, 4, 2);
    }
    $formattedTime = '';
    if (strlen($timeStr) >= 4) {
        $formattedTime = substr($timeStr, 0, 2) . ':' . substr($timeStr, 2, 2);
        if (strlen($timeStr) >= 6) {
            $formattedTime .= ':' . substr($timeStr, 4, 2);
        }
    }

    $manifest[] = [
        'session' => $sessionName,
        'username' => $username,
        'date' => $formattedDate,
        'time' => $formattedTime,
        'label' => $labels[$sessionName] ?? '',
        'tracks' => $sessionFiles,
        'track_count' => count($sessionFiles)
    ];
}

// Write manifest file
$manifestData = [
    'exported_at' => date('Y-m-d H:i:s'),
    'source' => 'DS-Tracks',
    'total_sessions' => $exported,
    'total_tracks' => $totalFiles,
    'sessions' => $manifest
];

file_put_contents(
    $exportPath . '/session-info.json',
    json_encode($manifestData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
);

logExport("Export complete: $exported sessions, $totalFiles files to $exportDirName");

echo json_encode([
    'success' => true,
    'exported' => $exported,
    'skipped' => $skipped,
    'total_files' => $totalFiles,
    'errors' => $errors,
    'export_path' => $exportDirName
]);
