<?php
/**
 * KCR Tracks - USB Status API
 *
 * Returns the current USB mount status as JSON.
 * Polled by the frontend every 2 seconds to detect USB insertion/removal.
 *
 * Response: { "mounted": true/false, "label": "...", "audio_count": N, ... }
 */

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

$statusFile = '/tmp/kcr-usb-status.json';

// Check if the status file exists (written by kcr-usb-mount.sh)
if (file_exists($statusFile)) {
    $status = file_get_contents($statusFile);
    $data = json_decode($status, true);

    // Verify the mount point actually exists and is accessible
    if ($data && isset($data['mountpoint']) && is_dir($data['mountpoint'])) {
        echo $status;
    } else {
        // Status file exists but mount point is gone - stale file
        echo json_encode(['mounted' => false]);
    }
} else {
    echo json_encode(['mounted' => false]);
}
