#!/bin/bash
###############################################################################
# KCR Tracks v2.0 - Raspberry Pi Installer
# Complete installation script for fresh Raspberry Pi OS
###############################################################################

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
APP_NAME="KCR Tracks"
APP_VERSION="2.0"
INSTALL_DIR="/var/www/html/kcr-tracks"
APACHE_USER="www-data"
PHP_VERSION="7.4" # Will auto-detect

# Functions
print_header() {
    echo -e "${BLUE}"
    echo "╔══════════════════════════════════════════════════════════╗"
    echo "║                                                          ║"
    echo "║          KCR Tracks v2.0 - Raspberry Pi Installer       ║"
    echo "║                                                          ║"
    echo "╚══════════════════════════════════════════════════════════╝"
    echo -e "${NC}"
}

print_step() {
    echo -e "${GREEN}==>${NC} $1"
}

print_error() {
    echo -e "${RED}ERROR:${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}WARNING:${NC} $1"
}

print_success() {
    echo -e "${GREEN}✓${NC} $1"
}

check_root() {
    if [[ $EUID -ne 0 ]]; then
        print_error "This script must be run as root or with sudo"
        exit 1
    fi
}

# Check if running on Raspberry Pi
check_raspberry_pi() {
    if [ ! -f /proc/device-tree/model ]; then
        print_warning "Cannot detect Raspberry Pi model"
        read -p "Continue anyway? (y/n): " -n 1 -r
        echo
        if [[ ! $REPLY =~ ^[Yy]$ ]]; then
            exit 1
        fi
    else
        MODEL=$(cat /proc/device-tree/model)
        print_step "Detected: $MODEL"
    fi
}

# Update system
update_system() {
    print_step "Updating system packages..."
    apt-get update -qq
    apt-get upgrade -y -qq
    print_success "System updated"
}

# Install Apache
install_apache() {
    print_step "Installing Apache web server..."

    if command -v apache2 &> /dev/null; then
        print_success "Apache already installed"
    else
        apt-get install -y apache2
        systemctl enable apache2
        systemctl start apache2
        print_success "Apache installed and started"
    fi

    # Enable required modules
    a2enmod rewrite
    a2enmod headers
    a2enmod expires
    systemctl restart apache2
}

# Install PHP
install_php() {
    print_step "Installing PHP and required extensions..."

    # Auto-detect available PHP version
    if apt-cache show php8.1 &> /dev/null; then
        PHP_VERSION="8.1"
    elif apt-cache show php7.4 &> /dev/null; then
        PHP_VERSION="7.4"
    else
        PHP_VERSION="7.3"
    fi

    print_step "Installing PHP ${PHP_VERSION}..."

    apt-get install -y \
        php${PHP_VERSION} \
        php${PHP_VERSION}-cli \
        php${PHP_VERSION}-common \
        php${PHP_VERSION}-curl \
        php${PHP_VERSION}-mbstring \
        php${PHP_VERSION}-xml \
        libapache2-mod-php${PHP_VERSION}

    print_success "PHP ${PHP_VERSION} installed"
}

# Create installation directory
create_directories() {
    print_step "Creating application directories..."

    # Backup existing installation if present
    if [ -d "$INSTALL_DIR" ]; then
        print_warning "Existing installation found"
        BACKUP_DIR="${INSTALL_DIR}_backup_$(date +%Y%m%d_%H%M%S)"
        mv "$INSTALL_DIR" "$BACKUP_DIR"
        print_success "Backup created: $BACKUP_DIR"
    fi

    # Create new directory
    mkdir -p "$INSTALL_DIR"
    mkdir -p "$INSTALL_DIR/music"
    mkdir -p "$INSTALL_DIR/logs"
    mkdir -p "$INSTALL_DIR/images"
    mkdir -p "$INSTALL_DIR/css"
    mkdir -p "$INSTALL_DIR/js"

    print_success "Directories created"
}

# Set permissions
set_permissions() {
    print_step "Setting file permissions..."

    chown -R ${APACHE_USER}:${APACHE_USER} "$INSTALL_DIR"
    chmod 755 "$INSTALL_DIR"
    chmod 755 "$INSTALL_DIR/music"
    chmod 755 "$INSTALL_DIR/logs"
    chmod 644 "$INSTALL_DIR"/*.php 2>/dev/null || true
    chmod 644 "$INSTALL_DIR"/.htaccess 2>/dev/null || true

    print_success "Permissions set"
}

# Configure Apache
configure_apache() {
    print_step "Configuring Apache virtual host..."

    # Get the Raspberry Pi's IP address
    IP_ADDR=$(hostname -I | awk '{print $1}')

    # Create Apache config
    cat > /etc/apache2/sites-available/kcr-tracks.conf <<EOF
<VirtualHost *:80>
    ServerAdmin webmaster@localhost
    DocumentRoot ${INSTALL_DIR}

    <Directory ${INSTALL_DIR}>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted

        # Security headers
        Header always set X-Frame-Options "SAMEORIGIN"
        Header always set X-Content-Type-Options "nosniff"
        Header always set X-XSS-Protection "1; mode=block"
    </Directory>

    # Protect sensitive files
    <FilesMatch "\.(log|ini)$">
        Require all denied
    </FilesMatch>

    # Logging
    ErrorLog \${APACHE_LOG_DIR}/kcr-tracks-error.log
    CustomLog \${APACHE_LOG_DIR}/kcr-tracks-access.log combined

    # PHP settings
    php_value upload_max_filesize 50M
    php_value post_max_size 50M
    php_value max_execution_time 300
    php_value max_input_time 300
</VirtualHost>
EOF

    # Enable site
    a2ensite kcr-tracks.conf
    a2dissite 000-default.conf 2>/dev/null || true
    systemctl reload apache2

    print_success "Apache configured"
    echo -e "  Access URL: ${GREEN}http://${IP_ADDR}${NC}"
}

# Configure PHP
configure_php() {
    print_step "Configuring PHP settings..."

    PHP_INI="/etc/php/${PHP_VERSION}/apache2/php.ini"

    if [ -f "$PHP_INI" ]; then
        # Backup original
        cp "$PHP_INI" "${PHP_INI}.bak"

        # Update settings
        sed -i 's/upload_max_filesize = .*/upload_max_filesize = 50M/' "$PHP_INI"
        sed -i 's/post_max_size = .*/post_max_size = 50M/' "$PHP_INI"
        sed -i 's/max_execution_time = .*/max_execution_time = 300/' "$PHP_INI"
        sed -i 's/max_input_time = .*/max_input_time = 300/' "$PHP_INI"
        sed -i 's/memory_limit = .*/memory_limit = 256M/' "$PHP_INI"

        systemctl restart apache2
        print_success "PHP configured"
    else
        print_warning "PHP ini file not found at $PHP_INI"
    fi
}

# Install application files
install_application() {
    print_step "Installing application files..."

    # Check if running from source directory
    if [ -f "$(dirname $0)/config.php" ]; then
        print_step "Copying files from source directory..."
        cp -r "$(dirname $0)"/* "$INSTALL_DIR/"
        rm -f "$INSTALL_DIR/install-raspberry-pi.sh"
        rm -f "$INSTALL_DIR/security-hardening.sh"
    else
        print_error "Application files not found"
        print_step "Please place this installer in the KCR-Tracks2 directory"
        exit 1
    fi

    # Create logs directory if it doesn't exist
    mkdir -p "$INSTALL_DIR/logs"
    chmod 755 "$INSTALL_DIR/logs"

    print_success "Application installed"
}

# Create systemd service for auto-start
create_systemd_service() {
    print_step "Creating systemd service..."

    cat > /etc/systemd/system/kcr-tracks.service <<EOF
[Unit]
Description=KCR Tracks Web Application
After=network.target apache2.service

[Service]
Type=oneshot
ExecStart=/bin/true
RemainAfterExit=yes

[Install]
WantedBy=multi-user.target
EOF

    systemctl daemon-reload
    systemctl enable kcr-tracks.service

    print_success "Systemd service created"
}

# Display completion message
show_completion() {
    IP_ADDR=$(hostname -I | awk '{print $1}')

    echo ""
    echo -e "${GREEN}╔══════════════════════════════════════════════════════════╗${NC}"
    echo -e "${GREEN}║                                                          ║${NC}"
    echo -e "${GREEN}║          Installation Complete!                          ║${NC}"
    echo -e "${GREEN}║                                                          ║${NC}"
    echo -e "${GREEN}╚══════════════════════════════════════════════════════════╝${NC}"
    echo ""
    echo -e "${BLUE}Application URL:${NC}     http://${IP_ADDR}"
    echo -e "${BLUE}Installation Directory:${NC}  ${INSTALL_DIR}"
    echo ""
    echo -e "${YELLOW}IMPORTANT NEXT STEPS:${NC}"
    echo ""
    echo "1. Change the admin password in:"
    echo "   ${INSTALL_DIR}/admin_customize.php"
    echo "   (Search for \$ADMIN_PASSWORD and change 'changeme123')"
    echo ""
    echo "2. Customize your branding:"
    echo "   Visit: http://${IP_ADDR}/admin_customize.php"
    echo ""
    echo "3. Upload your station logos to:"
    echo "   ${INSTALL_DIR}/images/"
    echo ""
    echo "4. Run security hardening (recommended):"
    echo "   sudo bash security-hardening.sh"
    echo ""
    echo "5. Test the application:"
    echo "   - Visit http://${IP_ADDR}"
    echo "   - Try uploading a test audio file"
    echo "   - Check logs in ${INSTALL_DIR}/logs/"
    echo ""
    echo -e "${GREEN}Enjoy KCR Tracks!${NC}"
    echo ""
}

# Main installation
main() {
    print_header

    print_step "Starting installation..."
    echo ""

    # Checks
    check_root
    check_raspberry_pi

    # Installation steps
    update_system
    install_apache
    install_php
    create_directories
    install_application
    set_permissions
    configure_apache
    configure_php
    create_systemd_service

    # Finish
    show_completion
}

# Run main function
main
