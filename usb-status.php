<?php
/**
 * DS-Tracks - USB Status API
 *
 * Returns the current USB mount status as JSON.
 * Polled by the frontend every 2 seconds to detect USB insertion/removal.
 *
 * Response: { "mounted": true/false, "label": "...", "audio_count": N, ... }
 */

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

$statusFile = '/run/kcr-usb-status.json';

// Check if the status file exists (written by ds-usb-mount.sh)
if (file_exists($statusFile)) {
    $status = file_get_contents($statusFile);
    $data = json_decode($status, true);

    if ($data) {
        echo $status;
    } else {
        echo json_encode(['mounted' => false]);
    }
} else {
    echo json_encode(['mounted' => false]);
}
