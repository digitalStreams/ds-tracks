#!/bin/bash
# ============================================================
# KCR Tracks First Boot Configuration Script
# ============================================================
# This script runs automatically on first boot to:
# - Expand the music partition to fill the SSD
# - Apply user configuration from /boot/kcr-config.txt
# - Configure network settings
# - Set up the station branding
# - Enable/disable services as needed
#
# Version: 2.0
# ============================================================

set -e

# Logging setup
LOG_FILE="/var/log/kcr-first-boot.log"
exec > >(tee -a "$LOG_FILE") 2>&1

# Configuration
KCR_INSTALL_DIR="/var/www/html/kcr-tracks"
CONFIG_FILE="/boot/firmware/kcr-config.txt"
CONFIG_FILE_ALT="/boot/kcr-config.txt"
BRANDING_FILE="$KCR_INSTALL_DIR/branding.php"

# ============================================================
# Helper Functions
# ============================================================

log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1"
}

log_success() {
    log "SUCCESS: $1"
}

log_error() {
    log "ERROR: $1"
}

log_info() {
    log "INFO: $1"
}

# Read a value from the config file
get_config() {
    local key="$1"
    local default="$2"
    local value=""

    # Try both possible config file locations
    if [ -f "$CONFIG_FILE" ]; then
        value=$(grep -E "^${key}=" "$CONFIG_FILE" 2>/dev/null | cut -d'=' -f2- | tr -d '"' | tr -d "'" || echo "")
    elif [ -f "$CONFIG_FILE_ALT" ]; then
        value=$(grep -E "^${key}=" "$CONFIG_FILE_ALT" 2>/dev/null | cut -d'=' -f2- | tr -d '"' | tr -d "'" || echo "")
    fi

    # Return value or default
    if [ -n "$value" ]; then
        echo "$value"
    else
        echo "$default"
    fi
}

# Update a value in branding.php
update_branding() {
    local key="$1"
    local value="$2"

    if [ -f "$BRANDING_FILE" ]; then
        # Escape special characters for sed
        local escaped_value=$(printf '%s\n' "$value" | sed -e 's/[\/&]/\\&/g')
        sed -i "s/\(public static \$$key = \)['\"][^'\"]*['\"]/\1\"$escaped_value\"/" "$BRANDING_FILE"
        log_info "Updated branding: $key = $value"
    else
        log_error "Branding file not found: $BRANDING_FILE"
    fi
}

# ============================================================
# Main Setup Functions
# ============================================================

expand_filesystem() {
    log_info "Checking filesystem expansion..."

    # Get the root partition device
    ROOT_PART=$(findmnt -n -o SOURCE /)
    ROOT_DEV=$(echo "$ROOT_PART" | sed 's/p\?[0-9]*$//')

    # Get partition number
    PART_NUM=$(echo "$ROOT_PART" | grep -o '[0-9]*$')

    log_info "Root partition: $ROOT_PART on device $ROOT_DEV"

    # Check if we can expand
    if command -v raspi-config &> /dev/null; then
        log_info "Using raspi-config to expand filesystem..."
        raspi-config --expand-rootfs || log_error "raspi-config expand failed"
    else
        # Manual expansion
        log_info "Performing manual filesystem expansion..."

        # Resize partition to use all available space
        if command -v growpart &> /dev/null; then
            growpart "$ROOT_DEV" "$PART_NUM" || true
        elif command -v parted &> /dev/null; then
            parted -s "$ROOT_DEV" resizepart "$PART_NUM" 100% || true
        fi

        # Resize the filesystem
        resize2fs "$ROOT_PART" || log_error "resize2fs failed"
    fi

    log_success "Filesystem expansion complete"
}

configure_station() {
    log_info "Configuring station settings..."

    # Read configuration values
    local station_name=$(get_config "STATION_NAME" "Community Radio")
    local station_short=$(get_config "STATION_SHORT_NAME" "Radio")
    local station_website=$(get_config "STATION_WEBSITE" "")

    log_info "Station Name: $station_name"
    log_info "Station Short Name: $station_short"

    # Update branding.php
    if [ -n "$station_name" ]; then
        update_branding "stationName" "$station_name"
    fi

    if [ -n "$station_short" ]; then
        update_branding "stationShortName" "$station_short"

        # Set hostname based on short name
        local new_hostname="kcr-$(echo "$station_short" | tr '[:upper:]' '[:lower:]' | tr -cd 'a-z0-9-')"
        log_info "Setting hostname to: $new_hostname"
        hostnamectl set-hostname "$new_hostname" || true
        echo "$new_hostname" > /etc/hostname
        sed -i "s/127.0.1.1.*/127.0.1.1\t$new_hostname/" /etc/hosts || true
    fi

    if [ -n "$station_website" ]; then
        update_branding "stationWebsite" "$station_website"
    fi

    log_success "Station configuration complete"
}

configure_network() {
    log_info "Configuring network settings..."

    local wifi_ssid=$(get_config "WIFI_SSID" "")
    local wifi_password=$(get_config "WIFI_PASSWORD" "")
    local static_ip=$(get_config "STATIC_IP" "")
    local gateway=$(get_config "GATEWAY" "")
    local dns=$(get_config "DNS" "8.8.8.8")

    # Configure WiFi if credentials provided
    if [ -n "$wifi_ssid" ] && [ -n "$wifi_password" ]; then
        log_info "Configuring WiFi: $wifi_ssid"

        # For newer Pi OS using NetworkManager
        if command -v nmcli &> /dev/null; then
            nmcli device wifi connect "$wifi_ssid" password "$wifi_password" || log_error "nmcli WiFi connection failed"
        else
            # Fallback to wpa_supplicant
            cat > /etc/wpa_supplicant/wpa_supplicant.conf << EOF
country=AU
ctrl_interface=DIR=/var/run/wpa_supplicant GROUP=netdev
update_config=1

network={
    ssid="$wifi_ssid"
    psk="$wifi_password"
    key_mgmt=WPA-PSK
}
EOF
            wpa_cli -i wlan0 reconfigure || true
        fi

        log_success "WiFi configured"
    fi

    # Configure static IP if provided
    if [ -n "$static_ip" ] && [ -n "$gateway" ]; then
        log_info "Configuring static IP: $static_ip"

        # For dhcpcd
        if [ -f /etc/dhcpcd.conf ]; then
            cat >> /etc/dhcpcd.conf << EOF

# KCR Tracks static IP configuration
interface eth0
static ip_address=$static_ip/24
static routers=$gateway
static domain_name_servers=$dns
EOF
            log_success "Static IP configured"
        fi
    fi
}

configure_timezone() {
    log_info "Configuring timezone..."

    local timezone=$(get_config "TIMEZONE" "Australia/Sydney")

    if [ -n "$timezone" ]; then
        timedatectl set-timezone "$timezone" || log_error "Failed to set timezone"
        log_success "Timezone set to: $timezone"
    fi
}

configure_display() {
    log_info "Configuring display settings..."

    local rotation=$(get_config "SCREEN_ROTATION" "0")
    local hide_cursor=$(get_config "HIDE_CURSOR" "true")

    # Handle screen rotation in config.txt
    local boot_config="/boot/firmware/config.txt"
    if [ ! -f "$boot_config" ]; then
        boot_config="/boot/config.txt"
    fi

    if [ -f "$boot_config" ] && [ "$rotation" != "0" ]; then
        log_info "Setting screen rotation to: $rotation degrees"

        # Remove existing rotation settings
        sed -i '/^display_rotate=/d' "$boot_config"
        sed -i '/^lcd_rotate=/d' "$boot_config"

        # Add new rotation (convert degrees to display_rotate value)
        case "$rotation" in
            90)  echo "display_rotate=1" >> "$boot_config" ;;
            180) echo "display_rotate=2" >> "$boot_config" ;;
            270) echo "display_rotate=3" >> "$boot_config" ;;
        esac
    fi

    log_success "Display configuration complete"
}

configure_ssh() {
    log_info "Configuring SSH access..."

    local enable_ssh=$(get_config "ENABLE_SSH" "false")
    local ssh_port=$(get_config "SSH_PORT" "22")

    if [ "$enable_ssh" = "true" ]; then
        log_info "Enabling SSH on port $ssh_port"

        # Update SSH port if not default
        if [ "$ssh_port" != "22" ]; then
            sed -i "s/^#*Port .*/Port $ssh_port/" /etc/ssh/sshd_config
        fi

        systemctl enable ssh
        systemctl start ssh
        log_success "SSH enabled on port $ssh_port"
    else
        log_info "SSH disabled (default secure configuration)"
        systemctl disable ssh || true
        systemctl stop ssh || true
    fi
}

set_permissions() {
    log_info "Setting file permissions..."

    if [ -d "$KCR_INSTALL_DIR" ]; then
        # Set ownership
        chown -R www-data:www-data "$KCR_INSTALL_DIR"

        # Set directory permissions
        find "$KCR_INSTALL_DIR" -type d -exec chmod 755 {} \;

        # Set file permissions
        find "$KCR_INSTALL_DIR" -type f -exec chmod 644 {} \;

        # Ensure music and logs directories are writable
        chmod 755 "$KCR_INSTALL_DIR/music" 2>/dev/null || true
        chmod 755 "$KCR_INSTALL_DIR/logs" 2>/dev/null || true

        log_success "Permissions set correctly"
    else
        log_error "KCR Tracks directory not found: $KCR_INSTALL_DIR"
    fi
}

generate_instance_id() {
    log_info "Generating instance ID..."

    # Generate a unique instance ID for support purposes
    local instance_id=$(cat /proc/sys/kernel/random/uuid | cut -d'-' -f1-2)
    echo "$instance_id" > "$KCR_INSTALL_DIR/.instance-id"
    chown www-data:www-data "$KCR_INSTALL_DIR/.instance-id"

    log_success "Instance ID: $instance_id"
}

clear_sensitive_config() {
    log_info "Clearing sensitive configuration data..."

    # Clear passwords from config file (keep structure for reference)
    for config_path in "$CONFIG_FILE" "$CONFIG_FILE_ALT"; do
        if [ -f "$config_path" ]; then
            sed -i 's/^\(WIFI_PASSWORD=\).*/\1***CONFIGURED***/' "$config_path"
            sed -i 's/^\(ADMIN_PASSWORD=\).*/\1***CONFIGURED***/' "$config_path"
        fi
    done

    log_success "Sensitive data cleared from config"
}

configure_music_drive() {
    log_info "Checking music drive..."

    MUSIC_MOUNT="/mnt/kcr-music"
    MUSIC_DIR="$MUSIC_MOUNT/music"

    # Try to mount if not already mounted (fstab should handle this, but just in case)
    if ! mountpoint -q "$MUSIC_MOUNT" 2>/dev/null; then
        log_info "Music drive not mounted, attempting mount..."
        mount "$MUSIC_MOUNT" 2>/dev/null || true
    fi

    if mountpoint -q "$MUSIC_MOUNT" 2>/dev/null; then
        log_success "Music drive mounted at $MUSIC_MOUNT"

        # Ensure music directory exists with correct permissions
        mkdir -p "$MUSIC_DIR"
        chown www-data:www-data "$MUSIC_DIR"
        chmod 755 "$MUSIC_DIR"

        # Verify the symlink is correct
        if [ -L "$KCR_INSTALL_DIR/music" ]; then
            log_success "Music symlink verified: $KCR_INSTALL_DIR/music -> $MUSIC_DIR"
        else
            log_info "Creating music symlink..."
            rm -rf "$KCR_INSTALL_DIR/music"
            ln -s "$MUSIC_DIR" "$KCR_INSTALL_DIR/music"
            log_success "Music symlink created"
        fi
    else
        log_error "Music drive (KCR-MUSIC) not found!"
        log_error "Please plug in the USB SSD labelled KCR-MUSIC and reboot"

        # Create a fallback local music directory so the app doesn't break
        if [ ! -e "$KCR_INSTALL_DIR/music" ]; then
            mkdir -p "$KCR_INSTALL_DIR/music"
            chown www-data:www-data "$KCR_INSTALL_DIR/music"
            chmod 755 "$KCR_INSTALL_DIR/music"
            log_info "Created fallback local music directory"
        fi
    fi
}

# ============================================================
# Main Execution
# ============================================================

main() {
    log "============================================================"
    log "KCR Tracks First Boot Configuration"
    log "============================================================"
    log ""

    # Check if we're running as root
    if [ "$EUID" -ne 0 ]; then
        log_error "This script must be run as root"
        exit 1
    fi

    # Run configuration steps
    expand_filesystem
    configure_music_drive
    configure_station
    configure_network
    configure_timezone
    configure_display
    configure_ssh
    set_permissions
    generate_instance_id
    clear_sensitive_config

    log ""
    log "============================================================"
    log "First boot configuration complete!"
    log "============================================================"
    log ""
    log "The system will reboot in 10 seconds to apply all changes..."
    log ""

    # Remove the first-boot trigger file
    rm -f /boot/kcr-first-boot-pending
    rm -f /boot/firmware/kcr-first-boot-pending

    # Schedule reboot
    sleep 10
    reboot
}

# Run main function
main "$@"
