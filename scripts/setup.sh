#!/bin/bash
# DS-Tracks v2.0 - Setup Script for Raspberry Pi
# This script sets up the necessary directories and permissions

echo "====================================="
echo "DS-Tracks v2.0 - Setup Script"
echo "====================================="
echo ""

# Get the directory where this script is located
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
cd "$SCRIPT_DIR"

echo "Setting up in: $SCRIPT_DIR"
echo ""

# Create logs directory if it doesn't exist
if [ ! -d "logs" ]; then
    echo "Creating logs directory..."
    mkdir -p logs
    chmod 755 logs
    echo "✓ Logs directory created"
else
    echo "✓ Logs directory already exists"
fi

# Create music directory if it doesn't exist
if [ ! -d "music" ]; then
    echo "Creating music directory..."
    mkdir -p music
    chmod 755 music
    echo "✓ Music directory created"
else
    echo "✓ Music directory already exists"
fi

# Set ownership to web server user (usually www-data on Raspberry Pi)
if command -v www-data &> /dev/null; then
    echo "Setting ownership to www-data..."
    sudo chown -R www-data:www-data music logs 2>/dev/null || echo "Note: Run with sudo to change ownership"
    echo "✓ Ownership set"
fi

# Set proper permissions
echo "Setting permissions..."
chmod 755 music logs 2>/dev/null
chmod 644 *.php 2>/dev/null
chmod 644 .htaccess 2>/dev/null
echo "✓ Permissions set"

echo ""
echo "====================================="
echo "Setup Complete!"
echo "====================================="
echo ""
echo "Next steps:"
echo "1. Verify Apache is running"
echo "2. Access the application in your browser"
echo "3. Test file upload functionality"
echo "4. Check logs/app_errors.log for any issues"
echo ""
echo "For detailed information, see:"
echo "- docs/archive/CHANGES-SUMMARY.md"
echo "- docs/archive/SECURITY-UPDATES.md"
echo ""
