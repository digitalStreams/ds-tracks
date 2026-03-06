<?php
/**
 * DS-Tracks - USB File Browser API
 *
 * Returns the contents of a directory on the mounted USB drive.
 * Only exposes folders and supported audio files.
 *
 * POST Parameters:
 *   path - relative path within USB drive (default: "/")
 *
 * Response: {
 *   "current_path": "/folder",
 *   "parent_path": "/",
 *   "folders": [ { "name": "...", "audio_count": N, "path": "/..." } ],
 *   "files": [ { "name": "...", "size": N, "size_human": "...", "extension": "...", "path": "/..." } ]
 * }
 */

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

// Configuration
$usbMountPoint = '/media/kcr-usb';
$allowedExtensions = ['mp3', 'wav', 'ogg', 'flac', 'm4a'];
$statusFile = '/run/kcr-usb-status.json';

/**
 * Format bytes into human-readable size
 */
function formatSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 1) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 1) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 1) . ' KB';
    }
    return $bytes . ' B';
}

/**
 * Count audio files recursively in a directory
 */
function countAudioFiles($dir, $allowedExtensions) {
    $count = 0;
    if (!is_dir($dir) || !is_readable($dir)) {
        return 0;
    }

    $items = @scandir($dir);
    if ($items === false) return 0;

    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;

        $fullPath = $dir . '/' . $item;
        if (is_file($fullPath)) {
            $ext = strtolower(pathinfo($item, PATHINFO_EXTENSION));
            if (in_array($ext, $allowedExtensions)) {
                $count++;
            }
        }
    }
    return $count;
}

// Verify USB is mounted
if (!file_exists($statusFile) || !is_dir($usbMountPoint)) {
    http_response_code(404);
    echo json_encode(['error' => 'No USB drive detected']);
    exit;
}

// Get and sanitise requested path
$requestedPath = isset($_POST['path']) ? $_POST['path'] : '/';

// Remove any dangerous characters and normalise
$requestedPath = str_replace('\\', '/', $requestedPath);
$requestedPath = preg_replace('#/+#', '/', $requestedPath); // collapse multiple slashes
$requestedPath = '/' . trim($requestedPath, '/');

// Construct full filesystem path
$fullPath = $usbMountPoint . $requestedPath;

// SECURITY: Resolve to real path and verify it's within mount point
$realPath = realpath($fullPath);
$realMountPoint = realpath($usbMountPoint);

if ($realPath === false || $realMountPoint === false) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid path']);
    exit;
}

if (strpos($realPath, $realMountPoint) !== 0) {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied']);
    exit;
}

if (!is_dir($realPath)) {
    http_response_code(400);
    echo json_encode(['error' => 'Not a directory']);
    exit;
}

// Calculate parent path
$parentPath = dirname($requestedPath);
if ($parentPath === '.' || $parentPath === $requestedPath) {
    $parentPath = null; // We're at root
}

// Scan directory contents
$items = @scandir($realPath);
if ($items === false) {
    http_response_code(500);
    echo json_encode(['error' => 'Cannot read directory']);
    exit;
}

$folders = [];
$files = [];

foreach ($items as $item) {
    // Skip hidden files and navigation entries
    if ($item === '.' || $item === '..' || $item[0] === '.') continue;

    // Skip system files
    $lowerItem = strtolower($item);
    if (in_array($lowerItem, ['thumbs.db', 'desktop.ini', '.ds_store', 'system volume information'])) continue;

    $itemFullPath = $realPath . '/' . $item;
    $itemRelativePath = $requestedPath . '/' . $item;
    // Normalise double slashes
    $itemRelativePath = preg_replace('#/+#', '/', $itemRelativePath);

    if (is_dir($itemFullPath)) {
        // Count audio files in this folder (non-recursive, just immediate children)
        $audioCount = countAudioFiles($itemFullPath, $allowedExtensions);

        $folders[] = [
            'name' => $item,
            'audio_count' => $audioCount,
            'path' => $itemRelativePath
        ];
    } elseif (is_file($itemFullPath)) {
        $ext = strtolower(pathinfo($item, PATHINFO_EXTENSION));

        // Only show audio files
        if (in_array($ext, $allowedExtensions)) {
            $size = filesize($itemFullPath);
            $files[] = [
                'name' => $item,
                'size' => $size,
                'size_human' => formatSize($size),
                'extension' => $ext,
                'path' => $itemRelativePath
            ];
        }
    }
}

// Sort: folders alphabetically, files alphabetically
usort($folders, function($a, $b) { return strcasecmp($a['name'], $b['name']); });
usort($files, function($a, $b) { return strcasecmp($a['name'], $b['name']); });

echo json_encode([
    'current_path' => $requestedPath,
    'parent_path' => $parentPath,
    'folders' => $folders,
    'files' => $files
]);
