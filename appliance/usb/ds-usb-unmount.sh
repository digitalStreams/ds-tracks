#!/bin/bash
# DS-Tracks - USB Auto-Unmount Script
# Install to: /usr/local/bin/ds-usb-unmount.sh
#
# Called by udev when a USB storage partition is removed.
# Unmounts the device and removes the status file.

DEVICE="/dev/$1"
MOUNT_POINT="/media/ds-usb"
STATUS_FILE="/tmp/ds-usb-status.json"
LOG_FILE="/var/log/ds-usb.log"

log() {
    echo "$(date '+%Y-%m-%d %H:%M:%S') UNMOUNT: $1" >> "$LOG_FILE"
}

log "USB device removed: $DEVICE"

# Don't touch the music drive - it's managed by fstab
MUSIC_MOUNT="/mnt/ds-music"
if mountpoint -q "$MUSIC_MOUNT" 2>/dev/null; then
    # Check if this device is the music drive
    MUSIC_DEV=$(findmnt -n -o SOURCE "$MUSIC_MOUNT" 2>/dev/null)
    if [ "$DEVICE" = "$MUSIC_DEV" ]; then
        log "Ignoring removal event for music drive ($DEVICE) - managed by fstab"
        exit 0
    fi
fi

# Unmount presenter USB if mounted
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
