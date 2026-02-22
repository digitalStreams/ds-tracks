#!/bin/bash
# KCR Tracks - USB Auto-Unmount Script
# Install to: /usr/local/bin/kcr-usb-unmount.sh
#
# Called by udev when a USB storage partition is removed.
# Unmounts the device and removes the status file.

DEVICE="/dev/$1"
MOUNT_POINT="/media/kcr-usb"
STATUS_FILE="/tmp/kcr-usb-status.json"
LOG_FILE="/var/log/kcr-usb.log"

log() {
    echo "$(date '+%Y-%m-%d %H:%M:%S') UNMOUNT: $1" >> "$LOG_FILE"
}

log "USB device removed: $DEVICE"

# Unmount if mounted
if mountpoint -q "$MOUNT_POINT" 2>/dev/null; then
    umount "$MOUNT_POINT" 2>/dev/null
    log "Unmounted $MOUNT_POINT"
fi

# Remove status file
if [ -f "$STATUS_FILE" ]; then
    rm -f "$STATUS_FILE"
    log "Status file removed"
fi

log "Cleanup complete"
