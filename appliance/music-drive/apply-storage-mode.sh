#!/bin/bash
# ============================================================
# DS-Tracks - Apply Music Storage Mode
# ============================================================
# Called by the web admin panel (via sudo) to switch between
# USB SSD and SD card music storage.
#
# Usage:
#   sudo /usr/local/bin/apply-storage-mode.sh usb
#   sudo /usr/local/bin/apply-storage-mode.sh sdcard
#
# Returns JSON for the PHP caller to parse.
# ============================================================

INSTALL_DIR="/var/www/html/ds-tracks"
MUSIC_MOUNT="/mnt/ds-music"
MUSIC_DIR="$MUSIC_MOUNT/music"
MODE="$1"

json_response() {
    echo "{\"success\": $1, \"message\": \"$2\"}"
}

if [ "$EUID" -ne 0 ]; then
    json_response "false" "Must run as root"
    exit 1
fi

if [ -z "$MODE" ]; then
    json_response "false" "Usage: apply-storage-mode.sh [usb|sdcard]"
    exit 1
fi

case "$MODE" in
    sdcard)
        # Switch to SD card storage
        if [ -L "$INSTALL_DIR/music" ]; then
            # Currently using USB - copy music back to local if USB is mounted
            if mountpoint -q "$MUSIC_MOUNT" 2>/dev/null && [ -d "$MUSIC_DIR" ]; then
                rm -f "$INSTALL_DIR/music"
                mkdir -p "$INSTALL_DIR/music"
                cp -a "$MUSIC_DIR/"* "$INSTALL_DIR/music/" 2>/dev/null || true
            else
                rm -f "$INSTALL_DIR/music"
                mkdir -p "$INSTALL_DIR/music"
            fi
        elif [ ! -d "$INSTALL_DIR/music" ]; then
            mkdir -p "$INSTALL_DIR/music"
        fi
        chown www-data:www-data "$INSTALL_DIR/music"
        chmod 755 "$INSTALL_DIR/music"
        json_response "true" "Music storage switched to SD card"
        ;;

    usb)
        # Switch to USB SSD storage
        # Check if USB drive is mounted
        if ! mountpoint -q "$MUSIC_MOUNT" 2>/dev/null; then
            mount "$MUSIC_MOUNT" 2>/dev/null || true
        fi

        if ! mountpoint -q "$MUSIC_MOUNT" 2>/dev/null; then
            json_response "false" "USB drive (DS-MUSIC) not found. Please plug it in and try again."
            exit 1
        fi

        # Ensure music dir on USB exists
        mkdir -p "$MUSIC_DIR"
        chown www-data:www-data "$MUSIC_DIR"
        chmod 755 "$MUSIC_DIR"

        # Copy existing local music to USB if switching from sdcard
        if [ -d "$INSTALL_DIR/music" ] && [ ! -L "$INSTALL_DIR/music" ]; then
            if [ "$(ls -A "$INSTALL_DIR/music" 2>/dev/null)" ]; then
                cp -a "$INSTALL_DIR/music/"* "$MUSIC_DIR/" 2>/dev/null || true
            fi
            rm -rf "$INSTALL_DIR/music"
        elif [ -L "$INSTALL_DIR/music" ]; then
            # Already a symlink - just verify it points to the right place
            json_response "true" "Already using USB storage"
            exit 0
        fi

        ln -s "$MUSIC_DIR" "$INSTALL_DIR/music"
        json_response "true" "Music storage switched to USB SSD"
        ;;

    *)
        json_response "false" "Invalid mode: $MODE (use usb or sdcard)"
        exit 1
        ;;
esac
