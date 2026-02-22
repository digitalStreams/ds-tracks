<?php
/**
 * KCR Tracks - USB Safe Eject API
 *
 * Safely unmounts the USB drive so the user can remove it.
 *
 * POST Response: { "success": true/false, "message": "..." }
 */

header('Content-Type: application/json');

$mountPoint = '/media/kcr-usb';
$statusFile = '/tmp/kcr-usb-status.json';

// Check if USB is mounted
if (!file_exists($statusFile)) {
    echo json_encode(['success' => true, 'message' => 'No USB drive to eject']);
    exit;
}

// Attempt to unmount (requires www-data to have sudo permission for umount)
$output = [];
$returnCode = 0;
exec('sudo /bin/umount ' . escapeshellarg($mountPoint) . ' 2>&1', $output, $returnCode);

if ($returnCode === 0) {
    // Remove status file
    if (file_exists($statusFile)) {
        unlink($statusFile);
    }

    error_log(date('[Y-m-d H:i:s] ') . "USB ejected via web interface\n", 3, __DIR__ . '/logs/import_info.log');

    echo json_encode([
        'success' => true,
        'message' => 'USB drive safely ejected. You can remove it now.'
    ]);
} else {
    error_log(date('[Y-m-d H:i:s] ') . "USB eject failed: " . implode(' ', $output) . "\n", 3, __DIR__ . '/logs/import_errors.log');

    echo json_encode([
        'success' => false,
        'message' => 'Could not eject USB drive. It may be in use.'
    ]);
}
