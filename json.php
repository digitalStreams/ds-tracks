<?php
/**
 * DS-Tracks - JSON API Handler
 * Provides session and track data with input validation and security
 */

// Start session
session_start();

// Configuration
define('MUSIC_BASE_DIR', __DIR__ . '/music/');
define('LABELS_FILE', MUSIC_BASE_DIR . 'session-labels.json');

// Load session labels
function getSessionLabels() {
    if (file_exists(LABELS_FILE)) {
        $data = json_decode(file_get_contents(LABELS_FILE), true);
        return is_array($data) ? $data : [];
    }
    return [];
}

// Save a session label
function saveSessionLabel($sessionId, $label) {
    $labels = getSessionLabels();
    $labels[$sessionId] = $label;
    file_put_contents(LABELS_FILE, json_encode($labels, JSON_PRETTY_PRINT));
}

// Error logging function
function logError($message) {
    error_log(date('[Y-m-d H:i:s] ') . $message . PHP_EOL, 3, __DIR__ . '/api_errors.log');
}

// Sanitize input function
function sanitizeInput($input, $allowDash = true) {
    if ($allowDash) {
        return preg_replace('/[^a-zA-Z0-9_-]/', '', $input);
    }
    return preg_replace('/[^a-zA-Z0-9_]/', '', $input);
}

// Validate directory path is within music directory
function isValidMusicPath($path) {
    $realPath = realpath($path);
    $baseDir = realpath(MUSIC_BASE_DIR);

    if ($realPath === false) {
        return false;
    }

    return strpos($realPath, $baseDir) === 0;
}

// Save a session label
if (isset($_POST['save_label']) && isset($_POST['session_id'])) {
    $sessionId = sanitizeInput($_POST['session_id']);
    $label = trim(substr($_POST['save_label'], 0, 100)); // Max 100 chars
    $label = htmlspecialchars($label, ENT_QUOTES, 'UTF-8');
    if (!empty($sessionId) && !empty($label)) {
        saveSessionLabel($sessionId, $label);
        echo json_encode(['status' => 'ok']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Missing data']);
    }
    exit;
}

// DELETE ACTIONS
if (isset($_POST['delete_action'])) {
    $action = sanitizeInput($_POST['delete_action']);
    header('Content-Type: application/json');

    // DELETE TRACK: remove a single file from a session
    if ($action === 'track' && isset($_POST['session']) && isset($_POST['track'])) {
        $session = sanitizeInput($_POST['session']);
        $track = basename($_POST['track']);
        $filePath = MUSIC_BASE_DIR . $session . '/' . $track;

        if (!isValidMusicPath(MUSIC_BASE_DIR . $session) || !file_exists($filePath)) {
            echo json_encode(['status' => 'error', 'message' => 'File not found']);
            exit;
        }

        if (unlink($filePath)) {
            $remaining = array_diff(scandir(MUSIC_BASE_DIR . $session), ['.', '..']);
            if (count($remaining) === 0) {
                rmdir(MUSIC_BASE_DIR . $session);
                $labels = getSessionLabels();
                if (isset($labels[$session])) {
                    unset($labels[$session]);
                    file_put_contents(LABELS_FILE, json_encode($labels, JSON_PRETTY_PRINT));
                }
            }
            echo json_encode(['status' => 'ok', 'remaining' => count($remaining)]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Could not delete file']);
        }
        exit;
    }

    // DELETE SESSION: remove entire session directory and all tracks
    if ($action === 'session' && isset($_POST['session'])) {
        $session = sanitizeInput($_POST['session']);
        $sessionDir = MUSIC_BASE_DIR . $session;

        if (!is_dir($sessionDir) || !isValidMusicPath($sessionDir)) {
            echo json_encode(['status' => 'error', 'message' => 'Session not found']);
            exit;
        }

        $files = array_diff(scandir($sessionDir), ['.', '..']);
        foreach ($files as $file) {
            unlink($sessionDir . '/' . $file);
        }
        rmdir($sessionDir);

        $labels = getSessionLabels();
        if (isset($labels[$session])) {
            unset($labels[$session]);
            file_put_contents(LABELS_FILE, json_encode($labels, JSON_PRETTY_PRINT));
        }

        echo json_encode(['status' => 'ok']);
        exit;
    }

    // DELETE USER: remove all sessions for a username
    if ($action === 'user' && isset($_POST['username'])) {
        $targetUser = sanitizeInput($_POST['username'], false);
        $dirs = array_filter(glob(MUSIC_BASE_DIR . '*'), 'is_dir');
        $deleted = 0;
        $labels = getSessionLabels();
        $labelsChanged = false;

        foreach ($dirs as $dir) {
            if (!isValidMusicPath($dir)) continue;
            $dirName = basename($dir);
            $nameParts = explode('-', $dirName);
            if ($nameParts[0] === $targetUser) {
                $files = array_diff(scandir($dir), ['.', '..']);
                foreach ($files as $file) {
                    unlink($dir . '/' . $file);
                }
                rmdir($dir);
                $deleted++;

                if (isset($labels[$dirName])) {
                    unset($labels[$dirName]);
                    $labelsChanged = true;
                }
            }
        }

        if ($labelsChanged) {
            file_put_contents(LABELS_FILE, json_encode($labels, JSON_PRETTY_PRINT));
        }

        echo json_encode(['status' => 'ok', 'deleted_sessions' => $deleted]);
        exit;
    }

    echo json_encode(['status' => 'error', 'message' => 'Invalid delete action']);
    exit;
}

// Get sessions for a specific user
if(isset($_POST['u_name'])){
    $user_name = sanitizeInput($_POST['u_name']);

    // Validate username
    if (empty($user_name) || strlen($user_name) < 3) {
        logError('Invalid username in u_name request: ' . $_POST['u_name']);
        echo json_encode(['error' => 'Invalid username']);
        exit;
    }

    $dirs = array_filter(glob(MUSIC_BASE_DIR . '*'), 'is_dir');
    $arr = [];
    $my_music = [];
    $labels = getSessionLabels();

    foreach ($dirs as $key => $value) {
        // Validate path before processing
        if (!isValidMusicPath($value)) {
            logError('Invalid path detected: ' . $value);
            continue;
        }

        $dirName = basename($value);
        $nameParts = explode("-", $dirName);
        $onlyname = $nameParts[0];

        if($onlyname == $user_name){
            $files = scandir($value);
            array_splice($files, 0, 2); // Remove . and ..

            // Sanitize file names before adding to response
            $sanitizedFiles = array_map('basename', $files);

            $session = array('name' => $dirName, 'music' => $sanitizedFiles);
            if (isset($labels[$dirName])) {
                $session['label'] = $labels[$dirName];
            }
            $arr[] = $session;
        }
    }

    $data = json_encode($arr, JSON_PRETTY_PRINT);
    echo $data;


}else{

    if(isset($_POST['option'])){
        $option = sanitizeInput($_POST['option']);

        // Get list of all users
        if($option == 'users'){
            $dirs = array_filter(glob(MUSIC_BASE_DIR . '*'), 'is_dir');
            $arr = [];
            foreach ($dirs as $key => $value) {
                // Validate path
                if (!isValidMusicPath($value)) {
                    continue;
                }
                $dirName = basename($value);
                $arr[] = array('name' => $dirName);
            }
            $data = json_encode($arr, JSON_PRETTY_PRINT);
            echo $data;

        // Get users dropdown (deprecated - but keeping for compatibility)
        } else if ($option == 'by_users'){
            echo '<label for="Music Users">Choose a User:</label>
            <select name="Music" id="Users_id">';

            $scan = scandir(MUSIC_BASE_DIR);
            foreach($scan as $file) {
                if ($file === '.' || $file === '..') continue;

                $fullPath = MUSIC_BASE_DIR . $file;
                if (!is_dir($fullPath) || !isValidMusicPath($fullPath)) {
                    continue;
                }

                $safeFile = htmlspecialchars($file, ENT_QUOTES, 'UTF-8');
                echo "<option value=\"$safeFile\">$safeFile</option>";
            }
            echo '</select>';

        // Get session details for a specific path
        } else if(!empty($option)){
            $sessionPath = sanitizeInput($option);
            $fullPath = MUSIC_BASE_DIR . $sessionPath;

            // Validate path exists and is within music directory
            if (!is_dir($fullPath) || !isValidMusicPath($fullPath)) {
                logError('Invalid session path requested: ' . $option);
                echo json_encode(['error' => 'Invalid session']);
                exit;
            }

            $files = scandir($fullPath);
            $myarr = [];

            // Parse session name for metadata
            $nameParts = explode("-", $sessionPath);
            $username = $nameParts[0];
            $dateTime = isset($nameParts[1]) && isset($nameParts[2])
                        ? $nameParts[1] . '-' . $nameParts[2]
                        : '';

            // Format date/time
            if (!empty($dateTime)) {
                $parts = explode('-', $dateTime);
                $date = $parts[0] ?? '';
                $time = $parts[1] ?? '';

                // Format time with colons
                $timeFormatted = '';
                if (strlen($time) >= 4) {
                    $end = substr($time, -4);
                    $chunks = str_split($end, 2);
                    $timeFormatted = implode(':', $chunks);
                }

                // Format date
                $dateFormatted = '';
                if (strlen($date) >= 6) {
                    $chunks = str_split($date, 2);
                    $dateFormatted = implode('-', $chunks);
                }

                $dateCreation = $dateFormatted . " " . $timeFormatted;
            } else {
                $dateCreation = 'Unknown';
            }

            foreach($files as $file_sub) {
                if ($file_sub === '.' || $file_sub === '..') continue;

                $myarr[] = array(
                    "id" => substr($time ?? '', -4),
                    "name" => $username,
                    "uniqueName" => $sessionPath,
                    "createdDate" => $dateCreation
                );
            }
            echo json_encode($myarr, JSON_PRETTY_PRINT);
        }else{
            echo json_encode(['error' => 'Invalid request']);
        }




    }else{

        // Get tracks for a specific session
        if(isset($_POST['t_name'])){
            $sessionName = sanitizeInput($_POST['t_name']);
            $fullPath = MUSIC_BASE_DIR . $sessionName;

            // Validate path
            if (!is_dir($fullPath) || !isValidMusicPath($fullPath)) {
                logError('Invalid session path in t_name request: ' . $_POST['t_name']);
                echo json_encode(['error' => 'Invalid session']);
                exit;
            }

            $myarr = [];
            $files = scandir($fullPath);

            // Parse session name
            $nameParts = explode("-", $sessionName);
            $username = $nameParts[0];
            $dateTime = isset($nameParts[1]) && isset($nameParts[2])
                        ? $nameParts[1] . '-' . $nameParts[2]
                        : '';

            $date = explode("-", $dateTime);
            $sessionId = ($date[0] ?? '') . "-" . ($date[1] ?? '');
            $end = substr($dateTime, -4);

            foreach($files as $file_sub) {
                if ($file_sub === '.' || $file_sub === '..') continue;

                $relativePath = "music/" . $sessionName . "/" . basename($file_sub);

                $myarr[] = array(
                    "id" => $sessionId,
                    "name" => basename($file_sub),
                    "path" => $relativePath,
                    $sessionName => $end
                );
            }

            echo json_encode($myarr, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        // Get all tracks from all sessions
        } else if (isset($_POST['all_track'])){
            $dirs = array_filter(glob(MUSIC_BASE_DIR . '*'), 'is_dir');
            $myarr = [];

            foreach ($dirs as $key => $value) {
                // Validate path
                if (!isValidMusicPath($value)) {
                    continue;
                }

                $dirName = basename($value);
                $files = scandir($value);
                array_splice($files, 0, 2); // Remove . and ..

                foreach($files as $file_sub) {
                    $safeName = basename($file_sub);
                    $myarr[] = array(
                        "name" => $safeName,
                        "path" => 'music/' . $dirName . '/' . $safeName
                    );
                }
            }

            $data = json_encode($myarr, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            echo $data;

        } else {
            echo json_encode(array("Status" => "Invalid request"));
        }
    }
}
?>
