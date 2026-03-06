<?php
/**
 * DS-Tracks - Music Player Display
 * Displays audio files for a specific user session
 */

// Configuration
define('MUSIC_BASE_DIR', __DIR__ . '/music/');
define('BASE_URL', ''); // Use relative URLs

// Error logging function
function logError($message) {
    error_log(date('[Y-m-d H:i:s] ') . $message . PHP_EOL, 3, __DIR__ . '/music_errors.log');
}

// Sanitize input
function sanitizeInput($input) {
    return preg_replace('/[^a-zA-Z0-9_-]/', '', $input);
}

// Validate path is within music directory
function isValidMusicPath($path) {
    $realPath = realpath($path);
    $baseDir = realpath(MUSIC_BASE_DIR);

    if ($realPath === false || $baseDir === false) {
        return false;
    }

    return strpos($realPath, $baseDir) === 0;
}

Get();

function Get(){
    if (!isset($_REQUEST['option'])) {
        echo "<p>No session selected</p>";
        return;
    }

    $sessionPath = sanitizeInput($_REQUEST['option']);
    $fullPath = MUSIC_BASE_DIR . $sessionPath;

    // Validate path
    if (!is_dir($fullPath) || !isValidMusicPath($fullPath)) {
        logError('Invalid session path requested: ' . $_REQUEST['option']);
        echo "<p>Invalid session</p>";
        return;
    }

    $files = scandir($fullPath);

    echo "<thead><tr><th>Users Name</th><th>Users Music</th><th>Save Track</th></tr></thead><tbody>";

    foreach($files as $file_sub) {
        if ($file_sub === '.' || $file_sub === '..') {
            continue;
        }

        // Sanitize output to prevent XSS
        $safeSessionPath = htmlspecialchars($sessionPath, ENT_QUOTES, 'UTF-8');
        $safeFileName = htmlspecialchars($file_sub, ENT_QUOTES, 'UTF-8');

        // Use relative URL path
        $audioPath = 'music/' . urlencode($sessionPath) . '/' . urlencode($file_sub);

        echo "<tr><td>" . $safeSessionPath . "</td>
        <td><audio controls>
            <source src='" . htmlspecialchars($audioPath, ENT_QUOTES, 'UTF-8') . "' type='audio/mpeg'>
            Your browser does not support the audio element.
        </audio></td>
        <td><a class='button button4' role='button' href='" . htmlspecialchars($audioPath, ENT_QUOTES, 'UTF-8') . "' download='" . $safeFileName . "'>
            Download
        </a></td>
        </tr>";
    }

    echo "</tbody>";
}
