#!/bin/bash
# ============================================================
# KCR Tracks - Music Drive Setup Script
# ============================================================
#
# Formats and labels a USB drive as the KCR Tracks music storage.
# Run this on the Pi during build, with the target USB drive plugged in.
#
# Usage:
#   sudo bash setup-music-drive.sh [device]
#
# If no device is specified, the script lists available drives
# and asks you to choose.
#
# WARNING: This will ERASE ALL DATA on the selected drive!
# ============================================================

set -e

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

LABEL="KCR-MUSIC"
MOUNT_POINT="/mnt/kcr-music"

# Must run as root
if [ "$EUID" -ne 0 ]; then
    echo -e "${RED}This script must be run as root (use sudo)${NC}"
    exit 1
fi

echo ""
echo -e "${BLUE}============================================================${NC}"
echo -e "${BLUE} KCR Tracks - Music Drive Setup${NC}"
echo -e "${BLUE}============================================================${NC}"
echo ""

# If device specified on command line, use it
if [ -n "$1" ]; then
    TARGET_DEV="$1"
else
    # List available USB drives (exclude SD card / system disk)
    echo "Looking for USB drives..."
    echo ""

    ROOT_DEV=$(findmnt -n -o SOURCE / | sed 's/p\?[0-9]*$//')

    # Find USB block devices that aren't the root device
    FOUND=0
    while IFS= read -r line; do
        DEV=$(echo "$line" | awk '{print $1}')
        SIZE=$(echo "$line" | awk '{print $4}')
        MODEL=$(echo "$line" | awk '{$1=$2=$3=$4=""; print $0}' | xargs)

        # Skip the system disk
        if echo "$DEV" | grep -q "$(basename "$ROOT_DEV")"; then
            continue
        fi

        # Only show whole disks (not partitions)
        if echo "$DEV" | grep -qE '^[a-z]+$'; then
            echo -e "  ${GREEN}/dev/$DEV${NC} - $SIZE - $MODEL"
            FOUND=$((FOUND + 1))
            LAST_DEV="/dev/$DEV"
        fi
    done < <(lsblk -o NAME,TYPE,TRAN,SIZE,MODEL -n -l | grep -E "disk\s+usb")

    if [ "$FOUND" -eq 0 ]; then
        echo -e "${RED}No USB drives found.${NC}"
        echo "Please plug in the USB drive you want to use for music storage."
        exit 1
    fi

    echo ""

    if [ "$FOUND" -eq 1 ]; then
        TARGET_DEV="$LAST_DEV"
        echo -e "Found one USB drive: ${GREEN}$TARGET_DEV${NC}"
    else
        echo -n "Enter the device to use (e.g., /dev/sda): "
        read TARGET_DEV
    fi
fi

# Validate the device exists
if [ ! -b "$TARGET_DEV" ]; then
    echo -e "${RED}Device $TARGET_DEV does not exist${NC}"
    exit 1
fi

# Show what's on the drive
echo ""
echo "Drive details:"
lsblk -o NAME,SIZE,FSTYPE,LABEL,MOUNTPOINT "$TARGET_DEV" 2>/dev/null || true
echo ""

# Confirm
echo -e "${RED}╔══════════════════════════════════════════════════════════╗${NC}"
echo -e "${RED}║  WARNING: ALL DATA ON $TARGET_DEV WILL BE ERASED!  ║${NC}"
echo -e "${RED}╚══════════════════════════════════════════════════════════╝${NC}"
echo ""
echo -n "Type YES to continue: "
read CONFIRM

if [ "$CONFIRM" != "YES" ]; then
    echo "Cancelled."
    exit 0
fi

echo ""
echo -e "${BLUE}Setting up music drive...${NC}"

# Unmount any mounted partitions on this drive
for part in $(lsblk -n -o NAME "$TARGET_DEV" | tail -n +2); do
    umount "/dev/$part" 2>/dev/null || true
done

# Create a single partition using the full drive
echo "Creating partition table..."
parted -s "$TARGET_DEV" mklabel gpt
parted -s "$TARGET_DEV" mkpart primary ext4 0% 100%

# Wait for the kernel to detect the new partition
sleep 2
partprobe "$TARGET_DEV" 2>/dev/null || true
sleep 1

# Determine partition device name (sda1 or sda-part1 etc.)
PART_DEV="${TARGET_DEV}1"
if [ ! -b "$PART_DEV" ]; then
    PART_DEV="${TARGET_DEV}p1"
fi

if [ ! -b "$PART_DEV" ]; then
    echo -e "${RED}Could not find partition device after creating partition${NC}"
    exit 1
fi

# Format as ext4 with the label
echo "Formatting as ext4 with label '$LABEL'..."
mkfs.ext4 -L "$LABEL" -F "$PART_DEV"

# Create mount point
mkdir -p "$MOUNT_POINT"

# Mount it
echo "Mounting at $MOUNT_POINT..."
mount "$PART_DEV" "$MOUNT_POINT"

# Create the music directory structure
echo "Creating music directory..."
mkdir -p "$MOUNT_POINT/music"
chown www-data:www-data "$MOUNT_POINT/music"
chmod 755 "$MOUNT_POINT/music"

# Create a marker file so we know this is set up
echo "KCR-MUSIC drive configured on $(date)" > "$MOUNT_POINT/.kcr-music-drive"

# Unmount
umount "$MOUNT_POINT"

echo ""
echo -e "${GREEN}============================================================${NC}"
echo -e "${GREEN} Music drive setup complete!${NC}"
echo -e "${GREEN}============================================================${NC}"
echo ""
echo "  Drive: $TARGET_DEV"
echo "  Label: $LABEL"
echo "  Format: ext4"
echo ""
echo "The drive will auto-mount at $MOUNT_POINT when plugged in."
echo "The build script will create a symlink from the app's music"
echo "directory to this drive."
echo ""
