#!/bin/bash
# KCR Tracks - USB Auto-Mount Script
# Install to: /usr/local/bin/kcr-usb-mount.sh
#
# Called by udev when a USB storage partition is inserted.
# Mounts the device read-only and writes a status file for the web app.

DEVICE="/dev/$1"
MOUNT_POINT="/media/kcr-usb"
STATUS_FILE="/tmp/kcr-usb-status.json"
LOG_FILE="/var/log/kcr-usb.log"

log() {
    echo "$(date '+%Y-%m-%d %H:%M:%S') MOUNT: $1" >> "$LOG_FILE"
}

log "USB device detected: $DEVICE"

# Check if this is the permanent music storage drive (labelled KCR-MUSIC)
LABEL=$(blkid -o value -s LABEL "$DEVICE" 2>/dev/null)
if [ "$LABEL" = "KCR-MUSIC" ]; then
    log "Skipping KCR-MUSIC drive (permanent music storage) - mounted via fstab"
    exit 0
fi

# Create mount point if it doesn't exist
mkdir -p "$MOUNT_POINT"

# Detect filesystem type
FSTYPE=$(blkid -o value -s TYPE "$DEVICE" 2>/dev/null)
log "Filesystem type: $FSTYPE"

# Only mount supported filesystem types
case "$FSTYPE" in
    vfat|ntfs|exfat|ext4|ext3|ext2)
        ;;
    *)
        log "Unsupported filesystem type: $FSTYPE - skipping"
        exit 0
        ;;
esac

# Mount options: read-only, no execution, no suid, no device files
MOUNT_OPTS="ro,noexec,nosuid,nodev"

# Add filesystem-specific options
case "$FSTYPE" in
    vfat)
        MOUNT_OPTS="$MOUNT_OPTS,utf8,umask=0022"
        ;;
    ntfs)
        MOUNT_OPTS="$MOUNT_OPTS,utf8,umask=0022"
        ;;
    exfat)
        MOUNT_OPTS="$MOUNT_OPTS,utf8,umask=0022"
        ;;
esac

# Unmount if something is already mounted there
if mountpoint -q "$MOUNT_POINT" 2>/dev/null; then
    log "Unmounting existing mount at $MOUNT_POINT"
    umount "$MOUNT_POINT" 2>/dev/null
fi

# Mount the device
if mount -t "$FSTYPE" -o "$MOUNT_OPTS" "$DEVICE" "$MOUNT_POINT"; then
    log "Mounted $DEVICE at $MOUNT_POINT"

    # Get volume label
    LABEL=$(blkid -o value -s LABEL "$DEVICE" 2>/dev/null)
    [ -z "$LABEL" ] && LABEL="USB Drive"

    # Get device size
    SIZE=$(lsblk -o SIZE -n -d "$DEVICE" 2>/dev/null | tr -d ' ')

    # Count audio files
    AUDIO_COUNT=$(find "$MOUNT_POINT" -type f \( -iname "*.mp3" -o -iname "*.wav" -o -iname "*.ogg" -o -iname "*.flac" -o -iname "*.m4a" \) 2>/dev/null | wc -l)

    # Write status file (readable by www-data)
    cat > "$STATUS_FILE" << EOF
{
    "mounted": true,
    "label": "$LABEL",
    "device": "$DEVICE",
    "filesystem": "$FSTYPE",
    "size": "$SIZE",
    "audio_count": $AUDIO_COUNT,
    "mountpoint": "$MOUNT_POINT",
    "timestamp": "$(date -Iseconds)"
}
EOF
    chmod 644 "$STATUS_FILE"
    log "Status file written: $AUDIO_COUNT audio files found"
else
    log "Failed to mount $DEVICE"
    exit 1
fi
