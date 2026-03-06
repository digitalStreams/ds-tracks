<?php
/**
 * DS-Tracks - Password Reset via USB
 *
 * Triggered when a USB drive contains DS-RESET-PASSWORD.txt
 * Resets the admin password to the file contents (or default if empty).
 *
 * POST only. Returns JSON.
 */

require_once __DIR__ . '/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'POST required']);
    exit;
}

$usbMountPoint = '/media/kcr-usb';
$resetFileName = 'DS-RESET-PASSWORD.txt';
$passwordFile = __DIR__ . '/admin_password.php';
$defaultPassword = 'changeme123';

// Verify USB is mounted and reset file exists
$resetFilePath = $usbMountPoint . '/' . $resetFileName;
if (!is_file($resetFilePath)) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'No password reset file found on USB']);
    exit;
}

// Read the file - if it contains text, use as new password; otherwise use default
$contents = trim(file_get_contents($resetFilePath));
$newPassword = !empty($contents) ? $contents : $defaultPassword;

// Validate: password must be at least 4 characters
if (strlen($newPassword) < 4) {
    echo json_encode(['success' => false, 'error' => 'Password must be at least 4 characters']);
    exit;
}

// Write the new password file
$phpContent = "<?php return " . var_export($newPassword, true) . "; ?>";
if (file_put_contents($passwordFile, $phpContent) === false) {
    error_log("DS-Tracks: Failed to write admin_password.php during USB reset");
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to write password file']);
    exit;
}

// Rename the reset file so it doesn't trigger again
$donePath = $usbMountPoint . '/' . 'DS-RESET-PASSWORD.done';
@rename($resetFilePath, $donePath);

// Log the reset
error_log(date('[Y-m-d H:i:s] ') . "Admin password reset via USB" . PHP_EOL, 3, __DIR__ . '/logs/import_info.log');

$isDefault = ($newPassword === $defaultPassword);
echo json_encode([
    'success' => true,
    'message' => $isDefault
        ? 'Admin password reset to default'
        : 'Admin password updated from USB file',
    'is_default' => $isDefault
]);
