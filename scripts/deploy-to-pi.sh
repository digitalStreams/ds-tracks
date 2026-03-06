#!/bin/bash
# deploy-to-pi.sh — Deploy DS-Tracks files to a Raspberry Pi
#
# Usage:
#   ./scripts/deploy-to-pi.sh [pi-address] [pi-user]
#
# Examples:
#   ./scripts/deploy-to-pi.sh 10.1.1.146
#   ./scripts/deploy-to-pi.sh ds-tracks.local pi
#   ./scripts/deploy-to-pi.sh 10.1.1.146 pi

set -e

# --- Configuration ---
PI_HOST="${1:-10.1.1.146}"
PI_USER="${2:-pi}"
PI_WEB_ROOT="/var/www/html/kcr-tracks"   # Update after KCR->DS rebrand
STAGING_DIR="/home/${PI_USER}/ds-tracks-deploy"

# Auto-detect project root (parent of scripts/)
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"

# Files to deploy (application code only)
APP_FILES=(
    "login.php"
    "json.php"
    "upload.php"
    "config.php"
    "branding.php"
    "branding_template.txt"
    "admin_customize.php"
    "usb-status.php"
    "usb-browse.php"
    "usb-import.php"
    "usb-eject.php"
    ".htaccess"
)

APP_DIRS=(
    "css"
    "js"
    "images"
)

# --- Main ---

echo "============================================"
echo "DS-Tracks Deploy to Pi"
echo "============================================"
echo "  Target:   ${PI_USER}@${PI_HOST}"
echo "  Web root: ${PI_WEB_ROOT}"
echo "  Source:    ${PROJECT_ROOT}"
echo ""

# Test SSH connectivity
echo "Testing SSH connection..."
if ! ssh -o ConnectTimeout=5 "${PI_USER}@${PI_HOST}" "echo 'Connected'" 2>/dev/null; then
    echo "ERROR: Cannot connect to ${PI_USER}@${PI_HOST}"
    echo ""
    echo "Troubleshooting:"
    echo "  - Is the Pi powered on and on the network?"
    echo "  - Is SSH enabled? (sudo ufw allow 22)"
    echo "  - Is the IP address correct?"
    exit 1
fi

# Create staging directory on Pi
echo "Creating staging directory on Pi..."
ssh "${PI_USER}@${PI_HOST}" "mkdir -p ${STAGING_DIR}"

# Transfer application files
echo ""
echo "Transferring application files..."
for file in "${APP_FILES[@]}"; do
    if [ -f "${PROJECT_ROOT}/${file}" ]; then
        echo "  ${file}"
        scp -q "${PROJECT_ROOT}/${file}" "${PI_USER}@${PI_HOST}:${STAGING_DIR}/"
    else
        echo "  ${file} (skipped - not found)"
    fi
done

# Transfer application directories
echo ""
echo "Transferring directories..."
for dir in "${APP_DIRS[@]}"; do
    if [ -d "${PROJECT_ROOT}/${dir}" ]; then
        echo "  ${dir}/"
        scp -q -r "${PROJECT_ROOT}/${dir}" "${PI_USER}@${PI_HOST}:${STAGING_DIR}/"
    else
        echo "  ${dir}/ (skipped - not found)"
    fi
done

# Deploy on the Pi: fix line endings, copy to web root, set permissions
echo ""
echo "Deploying to web root and fixing permissions..."
ssh "${PI_USER}@${PI_HOST}" bash -s "${STAGING_DIR}" "${PI_WEB_ROOT}" << 'REMOTE_SCRIPT'
STAGING="$1"
WEB_ROOT="$2"

# Fix Windows line endings on any shell/config files
find "$STAGING" -type f \( -name "*.sh" -o -name "*.conf" -o -name ".htaccess" \) -exec sed -i 's/\r//' {} +

# Copy files to web root (preserving directory structure)
sudo cp -r "$STAGING"/* "$WEB_ROOT"/

# Ensure required directories exist
sudo mkdir -p "$WEB_ROOT/music" "$WEB_ROOT/logs"

# Fix ownership and permissions
sudo chown -R www-data:www-data "$WEB_ROOT/music" "$WEB_ROOT/logs"
sudo chmod 755 "$WEB_ROOT/music" "$WEB_ROOT/logs"
sudo find "$WEB_ROOT" -type f \( -name "*.php" -o -name "*.css" -o -name "*.js" -o -name ".htaccess" \) -exec chmod 644 {} +

# Restart Apache
sudo systemctl restart apache2

# Clean up staging directory
rm -rf "$STAGING"

echo "Done!"
REMOTE_SCRIPT

echo ""
echo "============================================"
echo "Deployment complete!"
echo "============================================"
echo ""
echo "The Pi kiosk may still show cached content."
echo "To force a refresh, reboot the Pi:"
echo "  ssh ${PI_USER}@${PI_HOST} 'sudo reboot'"
echo ""
