# DS-Tracks v2.0 - Deployment Guide for Radio Stations

## Table of Contents
1. [Hardware Requirements](#hardware-requirements)
2. [Pre-Installation](#pre-installation)
3. [Installation](#installation)
4. [Customization](#customization)
5. [Security Hardening](#security-hardening)
6. [Testing](#testing)
7. [Troubleshooting](#troubleshooting)
8. [Maintenance](#maintenance)

---

## Hardware Requirements

### Recommended Setup
- **Raspberry Pi 4 Model B** (4GB or 8GB RAM)
- **32GB microSD card** (Class 10 or better)
- **Power Supply** (Official Raspberry Pi 4 Power Supply)
- **Touch Screen Display** (7" official Raspberry Pi touchscreen recommended)
- **Case** (with cooling fan recommended)
- **USB Audio Output** (optional, for better audio quality)

### Minimum Requirements
- Raspberry Pi 3 Model B+ (2GB RAM)
- 16GB microSD card
- Any 5V 2.5A power supply
- Any HDMI display
- USB keyboard and mouse

---

## Pre-Installation

### 1. Prepare the SD Card

**Download Raspberry Pi OS:**
- Visit: https://www.raspberrypi.com/software/
- Download "Raspberry Pi OS Lite" (64-bit recommended)
- Or use "Raspberry Pi OS with Desktop" if you want a GUI

**Flash the SD Card:**
```bash
# Using Raspberry Pi Imager (recommended)
1. Open Raspberry Pi Imager
2. Choose OS: Raspberry Pi OS Lite (64-bit)
3. Choose Storage: Your SD card
4. Click Settings (gear icon):
   - Set hostname: ds-tracks
   - Enable SSH
   - Set username and password
   - Configure WiFi (optional)
5. Write
```

### 2. First Boot

**Insert SD card and power on:**
```bash
# Wait for boot (first boot takes longer)
# Find the IP address on your network
# From another computer, SSH in:
ssh pi@ds-tracks.local
# or
ssh pi@<ip-address>
```

**Update the system:**
```bash
sudo apt-get update
sudo apt-get upgrade -y
sudo reboot
```

---

## Installation

### Method 1: Automated Installation (Recommended)

**1. Transfer files to Raspberry Pi:**
```bash
# From your computer, copy the ds-tracks directory
scp -r ds-tracks/ pi@ds-tracks.local:/home/pi/

# Or use a USB drive:
# - Copy files to USB
# - Insert USB into Raspberry Pi
# - Mount: sudo mount /dev/sda1 /mnt
# - Copy: cp -r /mnt/ds-tracks /home/pi/
```

**2. Run the installer:**
```bash
cd /home/pi/ds-tracks
chmod +x install-raspberry-pi.sh
sudo ./install-raspberry-pi.sh
```

The installer will:
- ✓ Install Apache web server
- ✓ Install PHP and extensions
- ✓ Create application directories
- ✓ Configure Apache virtual host
- ✓ Set proper permissions
- ✓ Configure PHP settings
- ✓ Set up systemd service

**3. Note the access URL** (displayed at end of installation)

### Method 2: Manual Installation

See [MANUAL_INSTALLATION.md](MANUAL_INSTALLATION.md) for step-by-step manual installation.

---

## Customization

### Upload Your Station Logos

**1. Prepare your logo files:**
- Main logo: PNG format, transparent background, recommended size 200x60px
- Tracks logo: PNG format, transparent background, recommended size 150x50px
- Favicon: ICO format, 32x32px

**2. Upload to Raspberry Pi:**
```bash
# From your computer
scp logo.png pi@ds-tracks.local:/var/www/html/ds-tracks/images/station-logo.png
scp tracks-logo.png pi@ds-tracks.local:/var/www/html/ds-tracks/images/
scp favicon.ico pi@ds-tracks.local:/var/www/html/ds-tracks/images/
```

### Configure Branding

**1. Change the admin password:**
```bash
sudo nano /var/www/html/ds-tracks/admin_customize.php
# Find: $ADMIN_PASSWORD = 'changeme123';
# Change to a strong password
# Save: Ctrl+X, Y, Enter
```

**2. Access the customization interface:**
- Open browser: `http://<raspberry-pi-ip>/admin_customize.php`
- Login with the password you just set
- Fill in your station details:
  - Station Name (e.g., "Community Radio 101.5")
  - Short Name (e.g., "CR101")
  - Website URL
  - Logo paths
  - Color scheme

**3. Test the changes:**
- Visit: `http://<raspberry-pi-ip>/`
- Verify your branding appears correctly

### Color Customization Tips

**Choosing Colors:**
- **Primary Color**: Your station's main brand color
- **Primary Dark**: Slightly darker shade (for hover effects)
- **Primary Light**: Lighter shade (for highlights)
- **Accent Color**: Contrasting color for call-to-action buttons
- **Accent Light**: Lighter accent (for active track highlighting)

**Color Picker Tools:**
- Use https://coolors.co/ for palette generation
- Use https://contrast-ratio.com/ for accessibility checking

---

## Security Hardening

**CRITICAL: Run the security hardening script before going live!**

```bash
cd /home/pi/ds-tracks
chmod +x security-hardening.sh
sudo ./security-hardening.sh
```

The script will:
- ✓ Configure UFW firewall
- ✓ Harden SSH (change port, disable root login)
- ✓ Install Fail2Ban (brute-force protection)
- ✓ Secure shared memory
- ✓ Set up automatic security updates
- ✓ Harden Apache configuration
- ✓ Configure log rotation
- ✓ Create backup script
- ✓ Set up disk monitoring

**Post-Hardening:**
```bash
# Change Pi user password
passwd

# Review security checklist
cat /home/pi/SECURITY_CHECKLIST.txt

# Reboot
sudo reboot
```

**Connect via new SSH port (if changed):**
```bash
ssh -p 2222 pi@<ip-address>
```

---

## Testing

### Test Checklist

**Basic Functionality:**
- [ ] Web interface loads
- [ ] Can enter username
- [ ] Can select files from USB drive
- [ ] Can upload MP3 file
- [ ] Upload rejects non-audio files
- [ ] Can play uploaded track
- [ ] Audio plays through correct output
- [ ] Can create new session
- [ ] Can load existing session
- [ ] Auto-play mode works
- [ ] Can export track list

**Security:**
- [ ] Firewall is active (`sudo ufw status`)
- [ ] Fail2Ban is running (`sudo fail2ban-client status`)
- [ ] Admin password changed
- [ ] Pi user password changed
- [ ] Log files are being created
- [ ] Directory listing is disabled

**Performance:**
- [ ] Page loads quickly (<2 seconds)
- [ ] File upload works smoothly
- [ ] Audio playback is smooth
- [ ] No errors in logs

**Test Upload:**
```bash
# Create a test MP3 file or use an existing one
# Try uploading through the web interface
# Check logs:
sudo tail -f /var/www/html/ds-tracks/logs/upload_errors.log
```

---

## Troubleshooting

### Common Issues

#### 1. **Can't access web interface**
```bash
# Check Apache is running
sudo systemctl status apache2

# Check IP address
hostname -I

# Check firewall
sudo ufw status

# Check Apache logs
sudo tail -f /var/log/apache2/ds-tracks-error.log
```

#### 2. **Upload fails**
```bash
# Check permissions
ls -la /var/www/html/ds-tracks/music

# Should show: drwxr-xr-x www-data www-data

# Fix if needed:
sudo chown -R www-data:www-data /var/www/html/ds-tracks/music
sudo chmod 755 /var/www/html/ds-tracks/music
```

#### 3. **File type rejected**
- Ensure file is MP3, WAV, OGG, FLAC, or M4A
- Check file is not corrupted
- Check logs: `/var/www/html/ds-tracks/logs/upload_errors.log`

#### 4. **Audio doesn't play**
```bash
# Check audio output
aplay -l

# Test audio
speaker-test -t wav -c 2

# Configure audio output
sudo raspi-config
# Select: System Options > Audio
```

#### 5. **Disk space full**
```bash
# Check disk space
df -h

# Check music directory size
du -sh /var/www/html/ds-tracks/music

# Clean old sessions (carefully!)
# Navigate to music directory and manually delete old folders
```

---

## Maintenance

### Daily Tasks
- Check application is accessible
- Monitor for any user-reported issues

### Weekly Tasks
```bash
# Check logs for errors
sudo tail -50 /var/www/html/ds-tracks/logs/app_errors.log

# Check disk space
df -h

# Verify backups are running
ls -lh /home/pi/ds-tracks-backups/
```

### Monthly Tasks
```bash
# Check for system updates
sudo apt-get update
sudo apt-get upgrade

# Review security logs
sudo fail2ban-client status
sudo tail -100 /var/log/auth.log

# Test restore from backup
```

### Backup Management

**Manual backup:**
```bash
sudo /usr/local/bin/ds-tracks-backup.sh
```

**Restore from backup:**
```bash
cd /home/pi/ds-tracks-backups
tar -xzf ds-tracks_YYYYMMDD_HHMMSS.tar.gz -C /tmp/
sudo cp -r /tmp/music/* /var/www/html/ds-tracks/music/
```

**Off-site backup (recommended):**
```bash
# Copy backups to external drive
sudo cp /home/pi/ds-tracks-backups/*.tar.gz /mnt/external-drive/

# Or use rsync to remote server
rsync -avz /home/pi/ds-tracks-backups/ user@backup-server:/backups/ds-tracks/
```

### Cleaning Old Sessions

**Script to delete sessions older than 30 days:**
```bash
#!/bin/bash
# Save as: /usr/local/bin/cleanup-old-sessions.sh

MUSIC_DIR="/var/www/html/ds-tracks/music"
DAYS_OLD=30

find "$MUSIC_DIR" -type d -mtime +$DAYS_OLD -exec rm -rf {} \;

chmod +x /usr/local/bin/cleanup-old-sessions.sh

# Add to crontab (runs monthly)
(crontab -l; echo "0 3 1 * * /usr/local/bin/cleanup-old-sessions.sh") | crontab -
```

---

## Advanced Configuration

### Enable HTTPS (Recommended for production)

```bash
# Install Certbot
sudo apt-get install certbot python3-certbot-apache

# Get SSL certificate
sudo certbot --apache -d yourdomain.com

# Auto-renewal is set up automatically
```

### Configure Static IP Address

```bash
# Edit dhcpcd.conf
sudo nano /etc/dhcpcd.conf

# Add at the end:
interface eth0
static ip_address=192.168.1.100/24
static routers=192.168.1.1
static domain_name_servers=192.168.1.1 8.8.8.8

# Save and reboot
sudo reboot
```

### Set up Email Alerts

```bash
# Install msmtp
sudo apt-get install msmtp msmtp-mta

# Configure (use your email provider's SMTP settings)
sudo nano /etc/msmtprc

# Test
echo "Test message" | mail -s "Test" your@email.com
```

---

## Support & Resources

### Documentation Files
- [`SECURITY-UPDATES.md`](../archive/SECURITY-UPDATES.md) - Security improvements details
- [`CHANGES-SUMMARY.md`](../archive/CHANGES-SUMMARY.md) - Quick reference of changes
- `README.md` - User guide
- [`KCR-Tracks-User-Manual-D02-2023-03-14.pdf`](../archive/KCR-Tracks-User-Manual-D02-2023-03-14.pdf) - Detailed user manual

### Log Files
- Application errors: `/var/www/html/ds-tracks/logs/app_errors.log`
- Upload errors: `/var/www/html/ds-tracks/logs/upload_errors.log`
- Apache errors: `/var/log/apache2/ds-tracks-error.log`
- System logs: `/var/log/syslog`

### Getting Help
1. Check logs for error messages
2. Review this deployment guide
3. Check security checklist: `/home/pi/SECURITY_CHECKLIST.txt`
4. Consult Raspberry Pi documentation: https://www.raspberrypi.com/documentation/

---

## Version Information
- **DS-Tracks Version**: 2.0
- **Minimum PHP Version**: 7.3
- **Recommended Pi Model**: Raspberry Pi 4 (4GB)
- **Tested On**: Raspberry Pi OS Bullseye (64-bit)

---

**Deployment Complete!** Your radio station presenters can now safely upload and play their music tracks. 🎵
