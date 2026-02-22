#!/bin/bash
# ============================================================
# KCR Tracks Appliance Build Script
# ============================================================
#
# This script configures a fresh Raspberry Pi OS installation
# into a KCR Tracks appliance ready for imaging.
#
# Prerequisites:
# - Fresh Raspberry Pi OS Lite (64-bit) installation
# - Internet connection
# - KCR Tracks source files in /tmp/KCR-Tracks2/
#
# Usage:
#   sudo ./build-appliance.sh
#
# Version: 2.0
# ============================================================

set -e

# Configuration
KCR_SOURCE_DIR="/tmp/KCR-Tracks2"
KCR_INSTALL_DIR="/var/www/html/kcr-tracks"
APPLIANCE_DIR="$KCR_SOURCE_DIR/appliance"
LOG_FILE="/var/log/kcr-build.log"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# ============================================================
# Helper Functions
# ============================================================

log() {
    echo -e "${BLUE}[$(date '+%H:%M:%S')]${NC} $1" | tee -a "$LOG_FILE"
}

log_success() {
    echo -e "${GREEN}[$(date '+%H:%M:%S')] ✓ $1${NC}" | tee -a "$LOG_FILE"
}

log_error() {
    echo -e "${RED}[$(date '+%H:%M:%S')] ✗ $1${NC}" | tee -a "$LOG_FILE"
}

log_warn() {
    echo -e "${YELLOW}[$(date '+%H:%M:%S')] ! $1${NC}" | tee -a "$LOG_FILE"
}

log_header() {
    echo "" | tee -a "$LOG_FILE"
    echo -e "${BLUE}============================================================${NC}" | tee -a "$LOG_FILE"
    echo -e "${BLUE} $1${NC}" | tee -a "$LOG_FILE"
    echo -e "${BLUE}============================================================${NC}" | tee -a "$LOG_FILE"
    echo "" | tee -a "$LOG_FILE"
}

check_root() {
    if [ "$EUID" -ne 0 ]; then
        log_error "This script must be run as root (use sudo)"
        exit 1
    fi
}

check_prerequisites() {
    log "Checking prerequisites..."

    if [ ! -d "$KCR_SOURCE_DIR" ]; then
        log_error "KCR Tracks source not found at $KCR_SOURCE_DIR"
        log_error "Please copy the KCR-Tracks2 folder to /tmp/"
        exit 1
    fi

    if [ ! -f "$KCR_SOURCE_DIR/install-raspberry-pi.sh" ]; then
        log_error "install-raspberry-pi.sh not found in source directory"
        exit 1
    fi

    log_success "Prerequisites check passed"
}

# ============================================================
# Build Phases
# ============================================================

phase1_system_update() {
    log_header "Phase 1: System Update"

    log "Updating package lists..."
    apt update

    log "Upgrading system packages..."
    apt full-upgrade -y

    log "Installing essential packages..."
    apt install -y \
        git \
        wget \
        curl \
        vim \
        htop \
        parted \
        cloud-guest-utils

    log_success "System update complete"
}

phase2_install_kcr_tracks() {
    log_header "Phase 2: Install KCR Tracks"

    cd "$KCR_SOURCE_DIR"

    log "Running KCR Tracks installer..."
    chmod +x install-raspberry-pi.sh
    ./install-raspberry-pi.sh

    log "Verifying installation..."
    if curl -s -o /dev/null -w "%{http_code}" http://localhost/kcr-tracks/ | grep -q "200\|302"; then
        log_success "KCR Tracks installation verified"
    else
        log_warn "Could not verify installation - Apache may need restart"
        systemctl restart apache2
    fi

    log_success "KCR Tracks installed"
}

phase3_install_kiosk() {
    log_header "Phase 3: Install Kiosk Components"

    log "Installing X11 and display packages..."
    apt install --no-install-recommends -y \
        xserver-xorg \
        x11-xserver-utils \
        xinit \
        openbox \
        chromium-browser \
        unclutter \
        fonts-liberation \
        fonts-dejavu

    log "Configuring auto-login..."
    mkdir -p /etc/systemd/system/getty@tty1.service.d/
    cat > /etc/systemd/system/getty@tty1.service.d/autologin.conf << 'EOF'
[Service]
ExecStart=
ExecStart=-/sbin/agetty --autologin pi --noclear %I $TERM
EOF

    log "Installing kiosk startup scripts..."

    # Install .xinitrc
    cp "$APPLIANCE_DIR/kiosk/xinitrc" /home/pi/.xinitrc
    chmod +x /home/pi/.xinitrc
    chown pi:pi /home/pi/.xinitrc

    # Install .bash_profile
    cp "$APPLIANCE_DIR/kiosk/bash_profile" /home/pi/.bash_profile
    chown pi:pi /home/pi/.bash_profile

    # Install openbox autostart
    mkdir -p /etc/xdg/openbox
    cp "$APPLIANCE_DIR/kiosk/openbox-autostart" /etc/xdg/openbox/autostart

    # Create kiosk log file
    touch /var/log/kcr-kiosk.log
    chown pi:pi /var/log/kcr-kiosk.log

    log_success "Kiosk components installed"
}

phase4_install_first_boot() {
    log_header "Phase 4: Configure First-Boot System"

    log "Installing first-boot script..."
    cp "$APPLIANCE_DIR/first-boot/kcr-first-boot.sh" /usr/local/bin/
    chmod +x /usr/local/bin/kcr-first-boot.sh

    log "Installing first-boot service..."
    cp "$APPLIANCE_DIR/first-boot/kcr-first-boot.service" /etc/systemd/system/
    systemctl daemon-reload
    systemctl enable kcr-first-boot.service

    log "Installing configuration template to boot partition..."
    # Try both possible boot partition locations
    if [ -d /boot/firmware ]; then
        cp "$APPLIANCE_DIR/boot-files/kcr-config.txt" /boot/firmware/
        touch /boot/firmware/kcr-first-boot-pending
    else
        cp "$APPLIANCE_DIR/boot-files/kcr-config.txt" /boot/
        touch /boot/kcr-first-boot-pending
    fi

    log_success "First-boot system configured"
}

phase4b_install_usb_system() {
    log_header "Phase 4b: Configure USB Auto-Mount System"

    log "Creating USB mount point..."
    mkdir -p /media/kcr-usb

    log "Installing udev rules for USB detection..."
    cp "$APPLIANCE_DIR/usb/99-kcr-usb.rules" /etc/udev/rules.d/
    chmod 644 /etc/udev/rules.d/99-kcr-usb.rules

    log "Installing USB mount/unmount scripts..."
    cp "$APPLIANCE_DIR/usb/kcr-usb-mount.sh" /usr/local/bin/
    cp "$APPLIANCE_DIR/usb/kcr-usb-unmount.sh" /usr/local/bin/
    chmod +x /usr/local/bin/kcr-usb-mount.sh
    chmod +x /usr/local/bin/kcr-usb-unmount.sh

    log "Installing exFAT support (for modern USB drives)..."
    apt install -y exfat-fuse exfat-utils 2>/dev/null || apt install -y exfatprogs 2>/dev/null || true

    log "Configuring sudo for USB eject (www-data)..."
    echo "www-data ALL=(ALL) NOPASSWD: /bin/umount /media/kcr-usb" > /etc/sudoers.d/kcr-usb-eject
    chmod 440 /etc/sudoers.d/kcr-usb-eject

    log "Reloading udev rules..."
    udevadm control --reload-rules

    log "Creating USB log file..."
    touch /var/log/kcr-usb.log
    chmod 644 /var/log/kcr-usb.log

    log_success "USB auto-mount system configured"
}

phase4c_configure_music_drive() {
    log_header "Phase 4c: Configure Separate Music Drive"

    log "Creating music drive mount point..."
    mkdir -p /mnt/kcr-music

    log "Adding music drive to fstab (mount by label)..."
    if ! grep -q "LABEL=KCR-MUSIC" /etc/fstab; then
        echo "" >> /etc/fstab
        echo "# KCR Tracks - Music storage drive (USB SSD labelled KCR-MUSIC)" >> /etc/fstab
        echo "LABEL=KCR-MUSIC  /mnt/kcr-music  auto  defaults,nofail,x-systemd.device-timeout=10  0  2" >> /etc/fstab
    fi

    log "Installing music drive setup script..."
    cp "$APPLIANCE_DIR/music-drive/setup-music-drive.sh" /usr/local/bin/
    chmod +x /usr/local/bin/setup-music-drive.sh

    log "Setting up music directory symlink..."
    # Remove the default music directory if it exists and create symlink
    if [ -d "$KCR_INSTALL_DIR/music" ] && [ ! -L "$KCR_INSTALL_DIR/music" ]; then
        # Move any existing music to a backup
        if [ "$(ls -A "$KCR_INSTALL_DIR/music" 2>/dev/null)" ]; then
            log_warn "Backing up existing music files to music.bak/"
            mv "$KCR_INSTALL_DIR/music" "$KCR_INSTALL_DIR/music.bak"
        else
            rmdir "$KCR_INSTALL_DIR/music"
        fi
    fi

    # Create symlink (will point to the music drive once it's mounted)
    if [ ! -L "$KCR_INSTALL_DIR/music" ]; then
        ln -s /mnt/kcr-music/music "$KCR_INSTALL_DIR/music"
        log_success "Symlink created: $KCR_INSTALL_DIR/music -> /mnt/kcr-music/music"
    fi

    log_success "Music drive configuration complete"
}

phase5_apply_security() {
    log_header "Phase 5: Apply Security Hardening"

    log "Disabling SSH by default..."
    systemctl disable ssh || true

    log "Configuring firewall..."
    if command -v ufw &> /dev/null; then
        ufw default deny incoming
        ufw default allow outgoing
        ufw allow 80/tcp comment 'HTTP'
        ufw allow 443/tcp comment 'HTTPS'
        ufw --force enable
        log_success "Firewall configured"
    else
        apt install -y ufw
        ufw default deny incoming
        ufw default allow outgoing
        ufw allow 80/tcp comment 'HTTP'
        ufw allow 443/tcp comment 'HTTPS'
        ufw --force enable
        log_success "Firewall installed and configured"
    fi

    log "Securing shared memory..."
    if ! grep -q "tmpfs /run/shm" /etc/fstab; then
        echo "tmpfs /run/shm tmpfs defaults,noexec,nosuid 0 0" >> /etc/fstab
    fi

    log_success "Security hardening applied"
}

phase6_configure_display() {
    log_header "Phase 6: Configure Display Settings"

    # Determine config.txt location
    local boot_config="/boot/firmware/config.txt"
    if [ ! -f "$boot_config" ]; then
        boot_config="/boot/config.txt"
    fi

    log "Configuring display settings in $boot_config..."

    # Add settings for official 7" touchscreen if not present
    if ! grep -q "disable_overscan=1" "$boot_config"; then
        echo "" >> "$boot_config"
        echo "# KCR Tracks display configuration" >> "$boot_config"
        echo "disable_overscan=1" >> "$boot_config"
    fi

    # Enable DRM VC4 V3D driver for better performance
    if ! grep -q "dtoverlay=vc4-kms-v3d" "$boot_config"; then
        echo "dtoverlay=vc4-kms-v3d" >> "$boot_config"
    fi

    log_success "Display settings configured"
}

phase7_optimize() {
    log_header "Phase 7: Optimize for Appliance Use"

    log "Disabling unnecessary services..."
    systemctl disable bluetooth.service || true
    systemctl disable hciuart.service || true
    systemctl disable triggerhappy.service || true

    log "Configuring swap..."
    # Reduce swappiness for SSD longevity
    echo "vm.swappiness=10" > /etc/sysctl.d/99-swappiness.conf

    log "Setting up log rotation..."
    cat > /etc/logrotate.d/kcr-tracks << 'EOF'
/var/www/html/kcr-tracks/logs/*.log {
    weekly
    rotate 4
    compress
    missingok
    notifempty
    create 644 www-data www-data
}
EOF

    log_success "Optimizations applied"
}

phase8_cleanup() {
    log_header "Phase 8: Cleanup for Imaging"

    log "Removing temporary files..."
    apt clean
    apt autoremove -y
    rm -rf /tmp/*
    rm -rf /var/tmp/*
    rm -rf /home/pi/.cache/*

    log "Clearing logs (keeping structure)..."
    find /var/log -type f -name "*.log" -exec truncate -s 0 {} \;
    find /var/log -type f -name "*.gz" -delete

    log "Clearing bash history..."
    rm -f /home/pi/.bash_history
    rm -f /root/.bash_history
    history -c

    log "Removing SSH host keys (will regenerate on first boot)..."
    rm -f /etc/ssh/ssh_host_*

    # Regenerate on boot
    cat > /etc/systemd/system/regenerate-ssh-keys.service << 'EOF'
[Unit]
Description=Regenerate SSH Host Keys
Before=ssh.service
ConditionPathExists=!/etc/ssh/ssh_host_rsa_key

[Service]
Type=oneshot
ExecStart=/usr/sbin/dpkg-reconfigure openssh-server

[Install]
WantedBy=multi-user.target
EOF
    systemctl enable regenerate-ssh-keys.service

    log_success "Cleanup complete"
}

show_summary() {
    log_header "Build Complete!"

    echo ""
    echo -e "${GREEN}============================================================${NC}"
    echo -e "${GREEN} KCR Tracks Appliance Build Complete!${NC}"
    echo -e "${GREEN}============================================================${NC}"
    echo ""
    echo "The system is now configured as a KCR Tracks appliance."
    echo ""
    echo "  OS:    SD card (this card)"
    echo "  Music: Separate USB SSD labelled 'KCR-MUSIC'"
    echo ""
    echo "IMPORTANT: Before imaging, set up the music drive:"
    echo ""
    echo "  1. Plug in the USB SSD that will store music"
    echo "  2. Run:  sudo setup-music-drive.sh"
    echo "  3. This formats and labels it as KCR-MUSIC"
    echo ""
    echo "Then create the distributable SD card image:"
    echo ""
    echo "  4. Shutdown this Pi:"
    echo "     sudo shutdown -h now"
    echo ""
    echo "  5. Remove the SD card and connect to your Windows PC"
    echo ""
    echo "  6. Use Win32 Disk Imager to read the SD card to a file:"
    echo "     Save as: KCR-Tracks-Master.img"
    echo ""
    echo "  7. Use Win32 Disk Imager to write that file to new SD cards"
    echo ""
    echo "  Note: Each station needs its own KCR-MUSIC USB SSD."
    echo "  Run setup-music-drive.sh on each one, or clone from a"
    echo "  formatted drive using Win32 Disk Imager."
    echo ""
    echo -e "${GREEN}============================================================${NC}"
}

# ============================================================
# Main Execution
# ============================================================

main() {
    log_header "KCR Tracks Appliance Build Script v2.0"

    check_root
    check_prerequisites

    phase1_system_update
    phase2_install_kcr_tracks
    phase3_install_kiosk
    phase4_install_first_boot
    phase4b_install_usb_system
    phase4c_configure_music_drive
    phase5_apply_security
    phase6_configure_display
    phase7_optimize
    phase8_cleanup

    show_summary
}

# Run main function
main "$@"
