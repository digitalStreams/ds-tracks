# KCR Tracks Raspberry Pi Appliance Build Specification

**Version:** 1.0
**Date:** January 2026
**Status:** Ready for Implementation
**Estimated Implementation Time:** 1 day

---

## Executive Summary

This specification documents how to create a pre-configured Raspberry Pi disk image for KCR Tracks that can be distributed to radio stations. The goal is a "flash and boot" appliance requiring no Linux knowledge from end users.

### Codebase Assessment Result: APPROVED

The current KCR-Tracks2 v2.0 codebase has been reviewed and is **highly suitable** for appliance deployment with only minor enhancements needed.

| Component | Readiness | Action Required |
|-----------|-----------|-----------------|
| Installation scripts | 9.5/10 | None |
| Configuration system | 10/10 | None |
| Application code | 9.5/10 | Minor (see Section 7) |
| Security | 9/10 | None |
| Documentation | 9.5/10 | None |

---

## Table of Contents

1. [Architecture Overview](#1-architecture-overview)
2. [Hardware Requirements](#2-hardware-requirements)
3. [Disk Partition Scheme](#3-disk-partition-scheme)
4. [Build Process Overview](#4-build-process-overview)
5. [First-Boot Configuration System](#5-first-boot-configuration-system)
6. [Kiosk Mode Setup](#6-kiosk-mode-setup)
7. [Codebase Modifications Required](#7-codebase-modifications-required)
8. [Image Creation Process](#8-image-creation-process)
9. [Distribution Package Contents](#9-distribution-package-contents)
10. [Testing Checklist](#10-testing-checklist)
11. [Support Documentation Required](#11-support-documentation-required)

---

## 1. Architecture Overview

### 1.1 Appliance Concept

```
┌─────────────────────────────────────────────────────────────┐
│                    END USER EXPERIENCE                       │
├─────────────────────────────────────────────────────────────┤
│  1. Download disk image                                      │
│  2. Flash to SSD using Raspberry Pi Imager                  │
│  3. (Optional) Edit kcr-config.txt on boot partition        │
│  4. Insert SSD, connect screen, power on                    │
│  5. KCR Tracks loads automatically in kiosk mode            │
│  6. Ready to use - no configuration required                │
└─────────────────────────────────────────────────────────────┘
```

### 1.2 System Components

```
┌─────────────────────────────────────────────────────────────┐
│                     RASPBERRY PI 4/5                         │
├─────────────────────────────────────────────────────────────┤
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────────────┐  │
│  │ Raspberry   │  │   Apache    │  │    Chromium         │  │
│  │ Pi OS Lite  │  │   + PHP     │  │    Kiosk Mode       │  │
│  │  (Headless) │  │             │  │                     │  │
│  └─────────────┘  └─────────────┘  └─────────────────────┘  │
│         │               │                    │               │
│         └───────────────┴────────────────────┘               │
│                         │                                    │
│              ┌──────────┴──────────┐                        │
│              │    KCR Tracks v2    │                        │
│              │  /var/www/html/     │                        │
│              │    kcr-tracks/      │                        │
│              └─────────────────────┘                        │
│                         │                                    │
│              ┌──────────┴──────────┐                        │
│              │   /music partition  │                        │
│              │  (auto-expands to   │                        │
│              │   fill SSD)         │                        │
│              └─────────────────────┘                        │
└─────────────────────────────────────────────────────────────┘
```

### 1.3 User Access Levels

| Level | Access Method | Capabilities |
|-------|---------------|--------------|
| **Presenter** | Touchscreen | Upload tracks, play music, manage sessions |
| **Station Admin** | Web browser (any device) | Branding, colours, logos via admin panel |
| **Technical Admin** | SSH (disabled by default) | Full system access, troubleshooting |

---

## 2. Hardware Requirements

### 2.1 Supported Hardware

| Component | Minimum | Recommended |
|-----------|---------|-------------|
| **Raspberry Pi** | Pi 3 B+ (2GB) | Pi 4 (4GB) or Pi 5 (4GB) |
| **Storage** | 32GB microSD | 128GB+ USB SSD |
| **Display** | Any HDMI display | Official 7" touchscreen |
| **Audio** | 3.5mm jack | USB audio interface |
| **Power** | 15W USB-C | Official Pi power supply |

### 2.2 Recommended SSD Options

For appliance deployment, USB SSD is strongly recommended over microSD:

| Capacity | Use Case | Approx. Track Storage |
|----------|----------|----------------------|
| 128GB | Small station | ~2,500 tracks (5MB avg) |
| 256GB | Medium station | ~5,000 tracks |
| 512GB | Large station | ~10,000 tracks |
| 1TB | Archive station | ~20,000 tracks |

**Note:** The music partition auto-expands on first boot to use all available space.

---

## 3. Disk Partition Scheme

### 3.1 Partition Layout

```
┌────────────────────────────────────────────────────────────┐
│                        SSD/SD CARD                          │
├──────────┬──────────────┬──────────────────────────────────┤
│  boot    │    rootfs    │           music                  │
│  (FAT32) │    (ext4)    │           (ext4)                 │
│  512MB   │    8GB       │    [Remaining Space]             │
│          │              │    Auto-expands on first boot    │
├──────────┼──────────────┼──────────────────────────────────┤
│ Config   │ OS + App     │ /var/www/html/kcr-tracks/music/  │
│ files    │              │ Symlinked from app directory     │
│ editable │ Read-mostly  │ User data - preserve on updates  │
│ on Win   │              │                                  │
└──────────┴──────────────┴──────────────────────────────────┘
```

### 3.2 Boot Partition Contents (User-Editable)

The FAT32 boot partition is readable/writable on Windows, allowing pre-boot configuration:

```
/boot/
├── config.txt              # Standard Pi config
├── cmdline.txt             # Boot parameters
├── kcr-config.txt          # KCR Tracks configuration (NEW)
└── ssh                     # Empty file enables SSH (optional)
```

### 3.3 kcr-config.txt Format

```ini
# KCR Tracks Appliance Configuration
# Edit this file before first boot, or via admin panel after boot

# Station Information
STATION_NAME=Your Radio Station
STATION_SHORT_NAME=YRS
STATION_WEBSITE=https://yourstation.com

# Network (optional - defaults to DHCP)
# WIFI_SSID=YourNetwork
# WIFI_PASSWORD=YourPassword
# STATIC_IP=192.168.1.100
# GATEWAY=192.168.1.1

# Display Settings
SCREEN_ROTATION=0           # 0, 90, 180, or 270
HIDE_CURSOR=true            # Hide mouse cursor in kiosk mode

# Advanced (leave defaults unless needed)
ENABLE_SSH=false            # Set true for remote access
SSH_PORT=22
TIMEZONE=Australia/Sydney
```

---

## 4. Build Process Overview

### 4.1 Build Environment Requirements

| Requirement | Purpose |
|-------------|---------|
| Raspberry Pi 4/5 | Build machine (or Pi emulator on Linux) |
| 64GB+ SD card | Working space for build |
| Internet connection | Download packages |
| ~2 hours | Initial build time |

### 4.2 Build Phases

```
Phase 1: Base System (30 mins)
├── Download Raspberry Pi OS Lite (64-bit)
├── Flash to build SD card
├── Boot and update system
└── Install required packages

Phase 2: Application Install (15 mins)
├── Run install-raspberry-pi.sh
├── Verify installation
└── Test functionality

Phase 3: Kiosk Configuration (30 mins)
├── Install X11 and Chromium
├── Configure auto-login
├── Configure kiosk mode
└── Disable screen blanking

Phase 4: First-Boot System (30 mins)
├── Create first-boot service
├── Create partition expansion script
├── Create config reader script
└── Test first-boot sequence

Phase 5: Image Creation (30 mins)
├── Clean up temporary files
├── Zero free space (for compression)
├── Create disk image
└── Compress image
```

---

## 5. First-Boot Configuration System

### 5.1 First-Boot Service

Create `/etc/systemd/system/kcr-first-boot.service`:

```ini
[Unit]
Description=KCR Tracks First Boot Configuration
After=network.target
ConditionPathExists=/boot/kcr-first-boot-pending

[Service]
Type=oneshot
ExecStart=/usr/local/bin/kcr-first-boot.sh
ExecStartPost=/bin/rm -f /boot/kcr-first-boot-pending
RemainAfterExit=yes

[Install]
WantedBy=multi-user.target
```

### 5.2 First-Boot Script Functions

The `/usr/local/bin/kcr-first-boot.sh` script should:

1. **Expand music partition** to fill available space
2. **Read kcr-config.txt** and apply settings
3. **Configure WiFi** if credentials provided
4. **Set timezone**
5. **Update branding.php** with station name
6. **Enable/disable SSH** based on config
7. **Set hostname** to station short name
8. **Generate unique instance ID** for support
9. **Log completion** and reboot if needed

### 5.3 Partition Expansion Logic

```bash
#!/bin/bash
# Expand music partition on first boot

DEVICE=$(findmnt -n -o SOURCE /)
DEVICE=${DEVICE%p*}  # Remove partition number

# Get the partition number for music (partition 3)
MUSIC_PART="${DEVICE}p3"

# Expand partition to fill disk
parted -s $DEVICE resizepart 3 100%

# Resize filesystem
resize2fs $MUSIC_PART

# Create symlink if not exists
ln -sf /mnt/music /var/www/html/kcr-tracks/music
```

---

## 6. Kiosk Mode Setup

### 6.1 Required Packages

```bash
apt install --no-install-recommends \
    xserver-xorg \
    x11-xserver-utils \
    xinit \
    openbox \
    chromium-browser \
    unclutter
```

### 6.2 Auto-Login Configuration

Create `/etc/systemd/system/getty@tty1.service.d/autologin.conf`:

```ini
[Service]
ExecStart=
ExecStart=-/sbin/agetty --autologin pi --noclear %I $TERM
```

### 6.3 Kiosk Startup Script

Create `/home/pi/.bash_profile`:

```bash
if [ -z "$DISPLAY" ] && [ "$(tty)" = "/dev/tty1" ]; then
    startx -- -nocursor
fi
```

Create `/home/pi/.xinitrc`:

```bash
#!/bin/bash

# Disable screen blanking
xset s off
xset s noblank
xset -dpms

# Hide cursor after 0.5 seconds of inactivity
unclutter -idle 0.5 -root &

# Start window manager
openbox-session &

# Wait for network
sleep 5

# Launch Chromium in kiosk mode
chromium-browser \
    --kiosk \
    --noerrdialogs \
    --disable-infobars \
    --disable-session-crashed-bubble \
    --disable-restore-session-state \
    --disable-translate \
    --no-first-run \
    --start-fullscreen \
    --autoplay-policy=no-user-gesture-required \
    http://localhost/kcr-tracks/login.php
```

### 6.4 Touchscreen Calibration

For official 7" display, add to `/boot/config.txt`:

```ini
# Official 7" touchscreen
lcd_rotate=2
dtoverlay=vc4-kms-v3d
```

---

## 7. Codebase Modifications Required

### 7.1 Issues Identified

Based on the codebase review, the following modifications are recommended but **not required** for basic appliance functionality:

#### Issue 1: Hardcoded Path in login.php (LOW PRIORITY)

**Location:** `login.php` lines 135, 188, 205

**Current:**
```javascript
var base_url = window.location.origin + "/kcr-tracks/";
```

**Issue:** Assumes installation at `/kcr-tracks/` path

**Impact:** None for standard appliance (uses default path)

**Recommended Fix (Optional):**
```javascript
var base_url = window.location.pathname.replace(/\/[^\/]*$/, '') + '/';
```

**Action:** No change required for appliance build. Document that installation path is fixed.

---

#### Issue 2: Interactive Prompts in security-hardening.sh (LOW PRIORITY)

**Location:** `security-hardening.sh` lines 80, 118, 193, 404

**Issue:** Requires manual input for some security options

**Impact:** Cannot run fully unattended

**Recommended Fix (Optional):**
Add environment variable support:
```bash
# At top of script
AUTO_CONFIRM=${AUTO_CONFIRM:-false}

# Replace interactive prompts
if [ "$AUTO_CONFIRM" = "true" ]; then
    REPLY="y"
else
    read -p "Continue? (y/n): " REPLY
fi
```

**Action:** For appliance builds, security hardening is pre-applied during image creation. No runtime hardening needed.

---

### 7.2 New Files Required

#### File 1: /usr/local/bin/kcr-first-boot.sh

Purpose: First-boot configuration script

```bash
#!/bin/bash
# KCR Tracks First Boot Configuration
# This script runs once on first boot

set -e
LOG="/var/log/kcr-first-boot.log"

log() {
    echo "$(date '+%Y-%m-%d %H:%M:%S') $1" | tee -a "$LOG"
}

log "Starting KCR Tracks first boot configuration..."

# 1. Expand music partition
log "Expanding music partition..."
DEVICE=$(findmnt -n -o SOURCE / | sed 's/p[0-9]*$//')
if [ -b "${DEVICE}p3" ]; then
    parted -s "$DEVICE" resizepart 3 100% || true
    resize2fs "${DEVICE}p3" || true
    log "Music partition expanded"
else
    log "Music partition not found, skipping expansion"
fi

# 2. Read configuration from boot partition
CONFIG_FILE="/boot/kcr-config.txt"
if [ -f "$CONFIG_FILE" ]; then
    log "Reading configuration from $CONFIG_FILE"
    source "$CONFIG_FILE"

    # Apply station name to branding
    if [ -n "$STATION_NAME" ]; then
        log "Setting station name: $STATION_NAME"
        sed -i "s/stationName = \".*\"/stationName = \"$STATION_NAME\"/" \
            /var/www/html/kcr-tracks/branding.php
    fi

    if [ -n "$STATION_SHORT_NAME" ]; then
        log "Setting short name: $STATION_SHORT_NAME"
        sed -i "s/stationShortName = \".*\"/stationShortName = \"$STATION_SHORT_NAME\"/" \
            /var/www/html/kcr-tracks/branding.php
        hostnamectl set-hostname "kcr-$STATION_SHORT_NAME"
    fi

    if [ -n "$STATION_WEBSITE" ]; then
        log "Setting website: $STATION_WEBSITE"
        sed -i "s|stationWebsite = \".*\"|stationWebsite = \"$STATION_WEBSITE\"|" \
            /var/www/html/kcr-tracks/branding.php
    fi

    # Configure WiFi if provided
    if [ -n "$WIFI_SSID" ] && [ -n "$WIFI_PASSWORD" ]; then
        log "Configuring WiFi: $WIFI_SSID"
        cat > /etc/wpa_supplicant/wpa_supplicant.conf << EOF
country=AU
ctrl_interface=DIR=/var/run/wpa_supplicant GROUP=netdev
update_config=1

network={
    ssid="$WIFI_SSID"
    psk="$WIFI_PASSWORD"
}
EOF
        wpa_cli -i wlan0 reconfigure || true
    fi

    # Set timezone
    if [ -n "$TIMEZONE" ]; then
        log "Setting timezone: $TIMEZONE"
        timedatectl set-timezone "$TIMEZONE"
    fi

    # Enable/disable SSH
    if [ "$ENABLE_SSH" = "true" ]; then
        log "Enabling SSH"
        systemctl enable ssh
        systemctl start ssh
    else
        log "SSH disabled (default)"
        systemctl disable ssh || true
        systemctl stop ssh || true
    fi
fi

# 3. Generate instance ID for support
INSTANCE_ID=$(cat /proc/sys/kernel/random/uuid | cut -d'-' -f1)
echo "$INSTANCE_ID" > /var/www/html/kcr-tracks/.instance-id
log "Instance ID: $INSTANCE_ID"

# 4. Set correct permissions
log "Setting permissions..."
chown -R www-data:www-data /var/www/html/kcr-tracks/
chmod -R 755 /var/www/html/kcr-tracks/
chmod -R 644 /var/www/html/kcr-tracks/*.php
chmod 755 /var/www/html/kcr-tracks/music
chmod 755 /var/www/html/kcr-tracks/logs

# 5. Clear config file passwords (security)
if [ -f "$CONFIG_FILE" ]; then
    sed -i 's/WIFI_PASSWORD=.*/WIFI_PASSWORD=***CONFIGURED***/' "$CONFIG_FILE"
fi

log "First boot configuration complete!"

# Reboot to apply all changes
log "Rebooting in 5 seconds..."
sleep 5
reboot
```

---

#### File 2: /boot/kcr-config.txt (Template)

```ini
# ============================================================
# KCR Tracks Appliance Configuration
# ============================================================
# Edit this file BEFORE first boot to pre-configure your station.
# You can also change these settings later via the admin panel.
#
# After editing, safely eject the drive and insert into your Pi.
# ============================================================

# ------------------------------------------------------------
# STATION INFORMATION
# ------------------------------------------------------------
# Your station's full name (displayed in header)
STATION_NAME=My Community Radio

# Short name for compact displays and hostname (no spaces)
STATION_SHORT_NAME=MCR

# Your station's website URL
STATION_WEBSITE=https://example.com

# ------------------------------------------------------------
# NETWORK CONFIGURATION
# ------------------------------------------------------------
# Leave commented for wired ethernet with DHCP (recommended)
# Uncomment and fill in for WiFi:

# WIFI_SSID=YourNetworkName
# WIFI_PASSWORD=YourPassword

# For static IP (optional, usually not needed):
# STATIC_IP=192.168.1.100
# GATEWAY=192.168.1.1
# DNS=8.8.8.8

# ------------------------------------------------------------
# DISPLAY SETTINGS
# ------------------------------------------------------------
# Screen rotation: 0, 90, 180, or 270 degrees
SCREEN_ROTATION=0

# Hide mouse cursor (recommended for touchscreen)
HIDE_CURSOR=true

# ------------------------------------------------------------
# TIMEZONE
# ------------------------------------------------------------
# See: https://en.wikipedia.org/wiki/List_of_tz_database_time_zones
TIMEZONE=Australia/Sydney

# ------------------------------------------------------------
# ADVANCED OPTIONS (leave defaults unless needed)
# ------------------------------------------------------------
# Enable SSH for remote access (security risk if exposed)
ENABLE_SSH=false

# SSH port (only if ENABLE_SSH=true)
SSH_PORT=22

# Admin panel password (change this!)
# ADMIN_PASSWORD=changeme

# ============================================================
# END OF CONFIGURATION
# ============================================================
```

---

## 8. Image Creation Process

### 8.1 Build Steps (Detailed)

#### Step 1: Prepare Base System

```bash
# Download Raspberry Pi OS Lite (64-bit)
# Use Raspberry Pi Imager to flash to SD card

# Boot Pi and connect via SSH
ssh pi@raspberrypi.local

# Update system
sudo apt update && sudo apt full-upgrade -y

# Set locale and timezone
sudo raspi-config nonint do_change_locale en_AU.UTF-8
sudo raspi-config nonint do_change_timezone Australia/Sydney
```

#### Step 2: Install KCR Tracks

```bash
# Copy application files to Pi
scp -r /path/to/KCR-Tracks2 pi@raspberrypi.local:/tmp/

# Run installer
cd /tmp/KCR-Tracks2
sudo ./install-raspberry-pi.sh

# Verify installation
curl -I http://localhost/kcr-tracks/
```

#### Step 3: Install Kiosk Components

```bash
# Install display packages
sudo apt install --no-install-recommends -y \
    xserver-xorg \
    x11-xserver-utils \
    xinit \
    openbox \
    chromium-browser \
    unclutter

# Configure auto-login
sudo mkdir -p /etc/systemd/system/getty@tty1.service.d/
sudo tee /etc/systemd/system/getty@tty1.service.d/autologin.conf << 'EOF'
[Service]
ExecStart=
ExecStart=-/sbin/agetty --autologin pi --noclear %I $TERM
EOF

# Create kiosk startup scripts
# (See Section 6.3 for file contents)
```

#### Step 4: Configure First-Boot System

```bash
# Install first-boot script
sudo cp kcr-first-boot.sh /usr/local/bin/
sudo chmod +x /usr/local/bin/kcr-first-boot.sh

# Install first-boot service
sudo cp kcr-first-boot.service /etc/systemd/system/
sudo systemctl enable kcr-first-boot.service

# Create first-boot trigger file
sudo touch /boot/kcr-first-boot-pending

# Install config template
sudo cp kcr-config.txt /boot/
```

#### Step 5: Apply Security Hardening

```bash
# Run security hardening (answer prompts as needed)
sudo ./security-hardening.sh

# Or apply specific hardening manually:
sudo apt install -y ufw fail2ban
sudo ufw default deny incoming
sudo ufw default allow outgoing
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw --force enable
```

#### Step 6: Clean Up for Imaging

```bash
# Remove temporary files
sudo apt clean
sudo rm -rf /tmp/*
sudo rm -rf /var/tmp/*
sudo rm -rf /home/pi/.cache/*

# Clear logs (optional - keeps structure)
sudo truncate -s 0 /var/log/*.log
sudo truncate -s 0 /var/log/**/*.log

# Clear bash history
history -c
rm -f ~/.bash_history

# Zero free space for better compression
sudo dd if=/dev/zero of=/zero.fill bs=1M || true
sudo rm -f /zero.fill

# Shutdown for imaging
sudo shutdown -h now
```

### 8.2 Create Disk Image

On another Linux machine:

```bash
# Insert SD card and identify device
lsblk

# Create image (replace sdX with actual device)
sudo dd if=/dev/sdX of=kcr-tracks-appliance-v2.0.img bs=4M status=progress

# Shrink image (optional but recommended)
# Use PiShrink: https://github.com/Drewsif/PiShrink
wget https://raw.githubusercontent.com/Drewsif/PiShrink/master/pishrink.sh
chmod +x pishrink.sh
sudo ./pishrink.sh kcr-tracks-appliance-v2.0.img

# Compress
xz -9 -T0 kcr-tracks-appliance-v2.0.img
# Creates: kcr-tracks-appliance-v2.0.img.xz
```

### 8.3 Image Distribution

Final image size estimates:

| Stage | Size |
|-------|------|
| Raw image | ~8 GB |
| After PiShrink | ~3 GB |
| After xz compression | ~1 GB |

---

## 9. Distribution Package Contents

### 9.1 Download Package Structure

```
KCR-Tracks-Appliance-v2.0/
├── kcr-tracks-appliance-v2.0.img.xz     # Compressed disk image (~1GB)
├── README.txt                            # Quick start instructions
├── kcr-config-example.txt               # Example configuration file
├── FLASHING-GUIDE.pdf                   # Visual guide with screenshots
└── checksums.txt                         # SHA256 checksums for verification
```

### 9.2 README.txt Contents

```
KCR TRACKS APPLIANCE v2.0
=========================

Quick Start:
1. Download and install Raspberry Pi Imager from https://www.raspberrypi.com/software/
2. Extract kcr-tracks-appliance-v2.0.img.xz
3. Flash the .img file to your SSD/SD card using Raspberry Pi Imager
4. (Optional) Open the boot drive on your computer and edit kcr-config.txt
5. Insert the SSD/SD card into your Raspberry Pi
6. Connect the display and power on
7. KCR Tracks will start automatically

For detailed instructions, see FLASHING-GUIDE.pdf

Support: https://github.com/your-repo/kcr-tracks/issues

Verify your download:
  Windows: certutil -hashfile kcr-tracks-appliance-v2.0.img SHA256
  Mac/Linux: sha256sum kcr-tracks-appliance-v2.0.img
  Compare with checksums.txt
```

---

## 10. Testing Checklist

### 10.1 Pre-Release Testing

#### Fresh Boot Tests

| Test | Expected Result | Pass |
|------|-----------------|------|
| Flash image to new SSD | Completes without error | ☐ |
| First boot (no config) | Boots to KCR Tracks kiosk | ☐ |
| First boot (with config) | Applies station name from config | ☐ |
| Partition expansion | Music partition fills SSD | ☐ |
| Screen rotation | Applies SCREEN_ROTATION setting | ☐ |
| WiFi connection | Connects to configured network | ☐ |

#### Functionality Tests

| Test | Expected Result | Pass |
|------|-----------------|------|
| Create new user | Session created successfully | ☐ |
| Upload MP3 file | File uploads and appears in list | ☐ |
| Upload WAV file | File uploads and appears in list | ☐ |
| Play track | Audio plays through output | ☐ |
| Auto-play mode | Tracks play in sequence | ☐ |
| Reorder tracks | Drag and drop works | ☐ |
| Delete session | Session removed | ☐ |
| Reports page | Displays user/track data | ☐ |
| Admin panel | Accessible and functional | ☐ |
| Branding changes | Applied after save | ☐ |

#### Recovery Tests

| Test | Expected Result | Pass |
|------|-----------------|------|
| Power loss during playback | Recovers on restart | ☐ |
| Browser crash | Auto-restarts to kiosk | ☐ |
| Full disk | Graceful error message | ☐ |
| Network loss | Continues to function | ☐ |

#### Hardware Compatibility

| Hardware | Tested | Notes |
|----------|--------|-------|
| Raspberry Pi 4 (4GB) | ☐ | Primary target |
| Raspberry Pi 4 (8GB) | ☐ | |
| Raspberry Pi 5 (4GB) | ☐ | |
| Official 7" touchscreen | ☐ | |
| Generic HDMI display | ☐ | |
| USB SSD (various) | ☐ | |
| USB audio interface | ☐ | |

---

## 11. Support Documentation Required

### 11.1 Documents to Create

| Document | Audience | Purpose |
|----------|----------|---------|
| FLASHING-GUIDE.pdf | End users | Visual step-by-step flashing instructions |
| TROUBLESHOOTING.md | Station admins | Common issues and solutions |
| ADMIN-GUIDE.md | Station admins | Branding, user management, maintenance |
| TECHNICAL-REFERENCE.md | IT support | SSH access, logs, advanced configuration |

### 11.2 Flashing Guide Outline

1. **Introduction** - What's in the box (metaphorically)
2. **Requirements** - Hardware needed, Raspberry Pi Imager download
3. **Extracting the Image** - How to decompress .xz file
4. **Flashing** - Step-by-step with screenshots
5. **Pre-Configuration** - Editing kcr-config.txt (optional)
6. **First Boot** - What to expect
7. **Verification** - Confirming it works
8. **Next Steps** - Admin panel access, customisation

---

## 12. Future Enhancements (Out of Scope)

The following are noted for potential future versions:

| Enhancement | Benefit | Complexity |
|-------------|---------|------------|
| OTA updates | Remote update capability | High |
| Web-based initial setup | No config file editing | Medium |
| Automatic backup to USB | Data protection | Medium |
| Multiple station profiles | Shared hardware | Medium |
| HTTPS auto-configuration | Security | Medium |
| Read-only root filesystem | Corruption resistance | High |

---

## Appendix A: File Inventory

### Files to Create

| File | Location | Purpose |
|------|----------|---------|
| kcr-first-boot.sh | /usr/local/bin/ | First-boot configuration |
| kcr-first-boot.service | /etc/systemd/system/ | Systemd service |
| kcr-config.txt | /boot/ | User configuration template |
| autologin.conf | /etc/systemd/system/getty@tty1.service.d/ | Auto-login |
| .bash_profile | /home/pi/ | Start X on login |
| .xinitrc | /home/pi/ | Kiosk mode startup |

### Files Modified

| File | Modification |
|------|--------------|
| /boot/config.txt | Display settings |
| /etc/hostname | Set from config |
| branding.php | Station name from config |

---

## Appendix B: Estimated Costs

### Hardware Cost (AUD, Approximate)

| Component | Budget | Recommended |
|-----------|--------|-------------|
| Raspberry Pi 4 (4GB) | $90 | $90 |
| Official 7" touchscreen | $120 | $120 |
| USB-C power supply | $25 | $25 |
| Case with cooling | $20 | $40 |
| 128GB USB SSD | $30 | - |
| 256GB USB SSD | - | $50 |
| USB audio interface | $0 (use 3.5mm) | $30 |
| **Total** | **~$285** | **~$355** |

---

## Appendix C: Commands Reference

### Useful Commands for Support

```bash
# Check KCR Tracks status
systemctl status apache2

# View application logs
tail -f /var/www/html/kcr-tracks/logs/app_errors.log

# Check disk space
df -h

# View first-boot log
cat /var/log/kcr-first-boot.log

# Restart kiosk
sudo systemctl restart getty@tty1

# Check instance ID
cat /var/www/html/kcr-tracks/.instance-id

# Manual partition expansion
sudo parted /dev/sda resizepart 3 100%
sudo resize2fs /dev/sda3
```

---

**End of Specification**

*Document Version: 1.0*
*Last Updated: January 2026*
