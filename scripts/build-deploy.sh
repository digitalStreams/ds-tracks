#!/bin/bash
# ============================================================
# DS-Tracks - Build Deployment Package
# ============================================================
# Creates a clean deploy/ folder containing only the files
# needed for installation on the Raspberry Pi.
#
# Run this from the ds-tracks project root:
#   bash scripts/build-deploy.sh
#
# The resulting deploy/ds-tracks/ folder is what gets
# copied to the USB stick for Build Day.
# ============================================================

set -e

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"
DEPLOY_DIR="$PROJECT_ROOT/deploy/ds-tracks"

echo "Building deployment package..."
echo ""

# Clean previous deploy
rm -rf "$PROJECT_ROOT/deploy"
mkdir -p "$DEPLOY_DIR"

# ---- Web Application (PHP, CSS, JS, images) ----

# PHP files
cp "$PROJECT_ROOT"/.htaccess "$DEPLOY_DIR/"
cp "$PROJECT_ROOT"/config.php "$DEPLOY_DIR/"
cp "$PROJECT_ROOT"/branding.php "$DEPLOY_DIR/"
cp "$PROJECT_ROOT"/branding_template.txt "$DEPLOY_DIR/"
cp "$PROJECT_ROOT"/login.php "$DEPLOY_DIR/"
cp "$PROJECT_ROOT"/upload.php "$DEPLOY_DIR/"
cp "$PROJECT_ROOT"/json.php "$DEPLOY_DIR/"
cp "$PROJECT_ROOT"/admin_customize.php "$DEPLOY_DIR/"
cp "$PROJECT_ROOT"/all_track_exporter.php "$DEPLOY_DIR/"
cp "$PROJECT_ROOT"/usb-browse.php "$DEPLOY_DIR/"
cp "$PROJECT_ROOT"/usb-eject.php "$DEPLOY_DIR/"
cp "$PROJECT_ROOT"/usb-import.php "$DEPLOY_DIR/"
cp "$PROJECT_ROOT"/usb-status.php "$DEPLOY_DIR/"
cp "$PROJECT_ROOT"/usb-export.php "$DEPLOY_DIR/"

# CSS (compiled only, no source maps or LESS)
mkdir -p "$DEPLOY_DIR/css"
cp "$PROJECT_ROOT"/css/style.css "$DEPLOY_DIR/css/"
cp "$PROJECT_ROOT"/css/touch.css "$DEPLOY_DIR/css/"

# JavaScript
mkdir -p "$DEPLOY_DIR/js"
cp "$PROJECT_ROOT"/js/js.cookie.min.js "$DEPLOY_DIR/js/"
cp "$PROJECT_ROOT"/js/js-cookie.js "$DEPLOY_DIR/js/"
cp "$PROJECT_ROOT"/js/usb-browser.js "$DEPLOY_DIR/js/"
cp "$PROJECT_ROOT"/js/on-screen-keyboard.js "$DEPLOY_DIR/js/"
cp "$PROJECT_ROOT"/js/jquery-3.6.1.min.js "$DEPLOY_DIR/js/"

# Images (only those used by the app)
mkdir -p "$DEPLOY_DIR/images"
cp "$PROJECT_ROOT"/images/station-logo.png "$DEPLOY_DIR/images/"
cp "$PROJECT_ROOT"/images/tracks-logo.png "$DEPLOY_DIR/images/"

# Logs directory protection
mkdir -p "$DEPLOY_DIR/logs"
cp "$PROJECT_ROOT"/logs/.htaccess "$DEPLOY_DIR/logs/"

# Admin password template (user must change on deployment)
cp "$PROJECT_ROOT"/admin_password.php "$DEPLOY_DIR/"

# ---- Installer Scripts ----

cp "$SCRIPT_DIR"/install-raspberry-pi.sh "$DEPLOY_DIR/"
cp "$SCRIPT_DIR"/setup.sh "$DEPLOY_DIR/"
cp "$SCRIPT_DIR"/security-hardening.sh "$DEPLOY_DIR/"

# ---- Appliance Build System ----

cp -r "$PROJECT_ROOT"/appliance "$DEPLOY_DIR/"
# Remove appliance dev docs from the deploy copy
rm -f "$DEPLOY_DIR"/appliance/README.md

# ---- Build Day Guide (the one doc users need) ----

cp "$PROJECT_ROOT"/docs/guides/BUILD-DAY-GUIDE.md "$DEPLOY_DIR/"

# ---- Fix line endings (Windows → Unix) ----
# Shell scripts edited on Windows will have \r\n line endings
# which causes "cannot execute" errors on the Pi

if command -v sed &> /dev/null; then
    echo "Converting line endings to Unix format..."
    find "$DEPLOY_DIR" -type f \( -name "*.sh" -o -name "*.service" -o -name "*.rules" \
        -o -name "xinitrc" -o -name "bash_profile" -o -name "openbox-autostart" \
        -o -name ".htaccess" -o -name "*.txt" -o -name "*.php" -o -name "*.css" \
        -o -name "*.js" -o -name "*.md" -o -name "*.json" \) \
        -exec sed -i 's/\r$//' {} +
fi

# ---- Summary ----

echo "Deployment package created at:"
echo "  deploy/ds-tracks/"
echo ""

# Count files
FILE_COUNT=$(find "$DEPLOY_DIR" -type f | wc -l)
DIR_SIZE=$(du -sh "$DEPLOY_DIR" | cut -f1)

echo "  Files: $FILE_COUNT"
echo "  Size:  $DIR_SIZE"
echo ""
echo "Copy deploy/ds-tracks/ to your USB stick for Build Day."
