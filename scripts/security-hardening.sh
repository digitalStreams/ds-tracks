#!/bin/bash
###############################################################################
# DS-Tracks v2.0 - Raspberry Pi Security Hardening Script
# Hardens Raspberry Pi OS for production use
###############################################################################

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Configuration
INSTALL_DIR="/var/www/html/ds-tracks"
SSH_PORT=22
NEW_SSH_PORT=2222

print_header() {
    echo -e "${BLUE}"
    echo "╔══════════════════════════════════════════════════════════╗"
    echo "║                                                          ║"
    echo "║       DS-Tracks - Security Hardening Script            ║"
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

# Update system
update_system() {
    print_step "Updating system packages..."
    apt-get update -qq
    apt-get upgrade -y -qq
    apt-get dist-upgrade -y -qq
    apt-get autoremove -y -qq
    print_success "System updated"
}

# Configure firewall
configure_firewall() {
    print_step "Configuring UFW firewall..."

    # Install UFW if not present
    if ! command -v ufw &> /dev/null; then
        apt-get install -y ufw
    fi

    # Reset UFW to defaults
    ufw --force reset

    # Default policies
    ufw default deny incoming
    ufw default allow outgoing

    # Allow SSH
    print_warning "Current SSH port: ${SSH_PORT}"
    read -p "Change SSH port to ${NEW_SSH_PORT}? (recommended) (y/n): " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        SSH_PORT=$NEW_SSH_PORT
        # Update SSH config
        sed -i "s/^#Port 22/Port ${SSH_PORT}/" /etc/ssh/sshd_config
        sed -i "s/^Port 22/Port ${SSH_PORT}/" /etc/ssh/sshd_config
        systemctl restart sshd || systemctl restart ssh
        print_success "SSH port changed to ${SSH_PORT}"
    fi

    ufw allow ${SSH_PORT}/tcp comment 'SSH'

    # Allow HTTP
    ufw allow 80/tcp comment 'HTTP Web Server'

    # Allow HTTPS (for future)
    ufw allow 443/tcp comment 'HTTPS Web Server'

    # Enable UFW
    echo "y" | ufw enable

    print_success "Firewall configured"
    ufw status
}

# Harden SSH
harden_ssh() {
    print_step "Hardening SSH configuration..."

    # Backup original config
    cp /etc/ssh/sshd_config /etc/ssh/sshd_config.bak

    # Disable root login
    sed -i 's/^PermitRootLogin yes/PermitRootLogin no/' /etc/ssh/sshd_config
    sed -i 's/^#PermitRootLogin prohibit-password/PermitRootLogin no/' /etc/ssh/sshd_config

    # Disable password authentication (use key-based only)
    read -p "Disable password authentication? (Ensure you have SSH keys set up!) (y/n): " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        sed -i 's/^#PasswordAuthentication yes/PasswordAuthentication no/' /etc/ssh/sshd_config
        sed -i 's/^PasswordAuthentication yes/PasswordAuthentication no/' /etc/ssh/sshd_config
        print_success "Password authentication disabled"
    fi

    # Other security settings
    grep -q "^ClientAliveInterval" /etc/ssh/sshd_config || echo "ClientAliveInterval 300" >> /etc/ssh/sshd_config
    grep -q "^ClientAliveCountMax" /etc/ssh/sshd_config || echo "ClientAliveCountMax 2" >> /etc/ssh/sshd_config
    grep -q "^MaxAuthTries" /etc/ssh/sshd_config || echo "MaxAuthTries 3" >> /etc/ssh/sshd_config
    grep -q "^Protocol" /etc/ssh/sshd_config || echo "Protocol 2" >> /etc/ssh/sshd_config

    systemctl restart sshd || systemctl restart ssh
    print_success "SSH hardened"
}

# Install Fail2Ban
install_fail2ban() {
    print_step "Installing Fail2Ban..."

    apt-get install -y fail2ban

    # Create local config
    cat > /etc/fail2ban/jail.local <<EOF
[DEFAULT]
bantime = 3600
findtime = 600
maxretry = 3

[sshd]
enabled = true
port = ${SSH_PORT}

[apache-auth]
enabled = true

[apache-overflows]
enabled = true

[apache-badbots]
enabled = true
EOF

    systemctl enable fail2ban
    systemctl restart fail2ban

    print_success "Fail2Ban installed and configured"
}

# Secure shared memory
secure_shared_memory() {
    print_step "Securing shared memory..."

    if ! grep -q "tmpfs /run/shm" /etc/fstab; then
        echo "tmpfs /run/shm tmpfs defaults,noexec,nosuid 0 0" >> /etc/fstab
        print_success "Shared memory secured"
    else
        print_success "Shared memory already secured"
    fi
}

# Disable unnecessary services
disable_services() {
    print_step "Disabling unnecessary services..."

    # List of services to consider disabling
    SERVICES_TO_DISABLE=(
        "bluetooth"
        "avahi-daemon"
    )

    for service in "${SERVICES_TO_DISABLE[@]}"; do
        if systemctl is-enabled --quiet $service 2>/dev/null; then
            read -p "Disable $service? (y/n): " -n 1 -r
            echo
            if [[ $REPLY =~ ^[Yy]$ ]]; then
                systemctl disable $service
                systemctl stop $service
                print_success "$service disabled"
            fi
        fi
    done
}

# Configure automatic updates
setup_auto_updates() {
    print_step "Setting up automatic security updates..."

    apt-get install -y unattended-upgrades apt-listchanges

    # Configure unattended upgrades
    dpkg-reconfigure -plow unattended-upgrades

    print_success "Automatic updates configured"
}

# Harden Apache
harden_apache() {
    print_step "Hardening Apache configuration..."

    # Disable server signature
    sed -i 's/^ServerTokens .*/ServerTokens Prod/' /etc/apache2/conf-available/security.conf
    sed -i 's/^ServerSignature .*/ServerSignature Off/' /etc/apache2/conf-available/security.conf

    # Disable directory listing (already done in .htaccess, but belt and braces)
    sed -i 's/Options Indexes FollowSymLinks/Options -Indexes +FollowSymLinks/' /etc/apache2/apache2.conf

    systemctl restart apache2

    print_success "Apache hardened"
}

# Set up log rotation
setup_log_rotation() {
    print_step "Setting up log rotation for DS-Tracks..."

    cat > /etc/logrotate.d/ds-tracks <<EOF
${INSTALL_DIR}/logs/*.log {
    weekly
    rotate 4
    compress
    delaycompress
    notifempty
    missingok
    create 0644 www-data www-data
}
EOF

    print_success "Log rotation configured"
}

# Create backup script
create_backup_script() {
    print_step "Creating backup script..."

    cat > /usr/local/bin/ds-tracks-backup.sh <<'EOF'
#!/bin/bash
# DS-Tracks Backup Script

BACKUP_DIR="/home/pi/ds-tracks-backups"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
SOURCE_DIR="/var/www/html/ds-tracks/music"

mkdir -p "$BACKUP_DIR"

# Create backup
tar -czf "$BACKUP_DIR/ds-tracks_${TIMESTAMP}.tar.gz" "$SOURCE_DIR"

# Keep only last 7 backups
cd "$BACKUP_DIR"
ls -t ds-tracks_*.tar.gz | tail -n +8 | xargs -r rm

echo "Backup completed: ds-tracks_${TIMESTAMP}.tar.gz"
EOF

    chmod +x /usr/local/bin/ds-tracks-backup.sh

    # Add to crontab (weekly backup)
    (crontab -l 2>/dev/null; echo "0 2 * * 0 /usr/local/bin/ds-tracks-backup.sh") | crontab -

    print_success "Backup script created (runs weekly)"
}

# Monitor disk space
setup_disk_monitoring() {
    print_step "Setting up disk space monitoring..."

    cat > /usr/local/bin/check-disk-space.sh <<'EOF'
#!/bin/bash
# Disk Space Monitor

THRESHOLD=80
CURRENT=$(df / | grep / | awk '{print $5}' | sed 's/%//g')

if [ $CURRENT -gt $THRESHOLD ]; then
    echo "WARNING: Disk space usage is at ${CURRENT}%"
    # You can add email notification here
fi
EOF

    chmod +x /usr/local/bin/check-disk-space.sh

    # Add to crontab (daily check)
    (crontab -l 2>/dev/null; echo "0 0 * * * /usr/local/bin/check-disk-space.sh") | crontab -

    print_success "Disk monitoring configured"
}

# Change default passwords
remind_passwords() {
    print_warning "IMPORTANT: Change default passwords!"
    echo ""
    echo "1. Change Pi user password:"
    echo "   passwd"
    echo ""
    echo "2. Change DS-Tracks admin password in:"
    echo "   ${INSTALL_DIR}/admin_customize.php"
    echo ""
}

# Create security checklist
create_security_checklist() {
    cat > /home/pi/SECURITY_CHECKLIST.txt <<EOF
DS-Tracks - Security Checklist
================================

Completed by this script:
✓ System updated
✓ Firewall configured (UFW)
✓ SSH hardened
✓ Fail2Ban installed
✓ Shared memory secured
✓ Automatic updates enabled
✓ Apache hardened
✓ Log rotation configured
✓ Backup script created
✓ Disk monitoring setup

Manual tasks required:
□ Change Pi user password (run: passwd)
□ Change admin password in admin_customize.php
□ Set up SSH keys for authentication
□ Consider setting up HTTPS with Let's Encrypt
□ Review firewall rules (ufw status)
□ Test fail2ban (fail2ban-client status)
□ Verify backups are working
□ Set up off-site backup location

Optional enhancements:
□ Install ClamAV for antivirus scanning
□ Set up email alerts for security events
□ Configure external syslog server
□ Implement network intrusion detection (Snort)
□ Set up VPN access

Important files:
- SSH config: /etc/ssh/sshd_config
- Firewall: ufw status
- Fail2Ban: /etc/fail2ban/jail.local
- Backups: /home/pi/ds-tracks-backups/
- Logs: /var/www/html/ds-tracks/logs/

For more information, see docs/archive/SECURITY-UPDATES.md in the application directory.
EOF

    chown pi:pi /home/pi/SECURITY_CHECKLIST.txt
    print_success "Security checklist created at /home/pi/SECURITY_CHECKLIST.txt"
}

# Show completion message
show_completion() {
    echo ""
    echo -e "${GREEN}╔══════════════════════════════════════════════════════════╗${NC}"
    echo -e "${GREEN}║                                                          ║${NC}"
    echo -e "${GREEN}║       Security Hardening Complete!                      ║${NC}"
    echo -e "${GREEN}║                                                          ║${NC}"
    echo -e "${GREEN}╚══════════════════════════════════════════════════════════╝${NC}"
    echo ""
    echo -e "${YELLOW}IMPORTANT:${NC}"
    echo ""
    if [ $SSH_PORT != 22 ]; then
        echo -e "${RED}SSH port changed to: ${SSH_PORT}${NC}"
        echo "Connect with: ssh -p ${SSH_PORT} pi@<ip-address>"
        echo ""
    fi
    echo "Review the security checklist:"
    echo "  cat /home/pi/SECURITY_CHECKLIST.txt"
    echo ""
    echo "Next steps:"
    echo "1. Change Pi user password: passwd"
    echo "2. Reboot the system: sudo reboot"
    echo "3. Test SSH connection after reboot"
    echo ""
}

# Main function
main() {
    print_header

    check_root

    echo -e "${YELLOW}This script will harden your Raspberry Pi security.${NC}"
    echo "It will make significant system changes."
    echo ""
    read -p "Continue? (y/n): " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        exit 1
    fi

    update_system
    configure_firewall
    harden_ssh
    install_fail2ban
    secure_shared_memory
    disable_services
    setup_auto_updates
    harden_apache
    setup_log_rotation
    create_backup_script
    setup_disk_monitoring
    create_security_checklist

    remind_passwords
    show_completion
}

# Run main function
main
