# Pi Image Cleanup Checklist

**Purpose:** Prepare a working DS-Tracks Pi for disk imaging, removing debug artifacts, credentials, and cruft accumulated during development.
**Last Updated:** 2026-03-06

---

## Context

The current working Pi was brought to a functional state through extensive manual fixes, debugging, and trial-and-error. Before imaging it as the master appliance image, it must be cleaned of accumulated artifacts to produce a reliable, distributable image.

### Risks of Imaging Without Cleanup

| Risk | Impact | Likelihood |
|------|--------|------------|
| Debug scripts/files left in place | Confusing to future maintainers, wastes space | High |
| Orphan packages from troubleshooting | Bloated image, potential conflicts on apt upgrade | High |
| SSH keys baked in | All stations share identity; security concern | Certain |
| WiFi credentials baked in | Dev network exposed on deployed stations | Certain |
| Manual permission fixes not scripted | Lost on any apt upgrade or config regeneration | Medium |
| Stale caches | Wasted SD card space (can be significant) | High |
| bash_history exposes debug commands | Information leak, unprofessional | Certain |
| Stale logs filling disk | Wasted space, confusing timestamps | High |

---

## Pre-Imaging Cleanup Steps

### Phase 1: Audit -- Understand What Is There

Do this BEFORE deleting anything. Record findings so the install script can be updated.

```bash
# What packages were manually installed?
apt list --installed 2>/dev/null | grep -v ",automatic" > /home/pi/audit-packages.txt

# What is in the pi home directory?
ls -la /home/pi/

# What systemd services are enabled?
systemctl list-unit-files --state=enabled > /home/pi/audit-services.txt

# What is in /etc/ that was recently modified? (files changed in last 60 days)
sudo find /etc -type f -mtime -60 -ls > /home/pi/audit-etc-changes.txt

# What cron jobs exist?
sudo crontab -l 2>/dev/null
crontab -l 2>/dev/null

# What is in Apache config?
ls -la /etc/apache2/sites-enabled/
cat /etc/apache2/sites-enabled/*.conf

# Check for stray files in common locations
ls /tmp/
ls /root/
ls /var/www/html/kcr-tracks/*.log 2>/dev/null
ls /var/www/html/kcr-tracks/*.bak 2>/dev/null
ls /var/www/html/kcr-tracks/*.old 2>/dev/null
```

**Save the audit files** -- copy them to your local machine before cleanup. They become the reference for what the install script should produce.

```bash
scp pi@<PI_IP>:/home/pi/audit-*.txt .
```

---

### Phase 2: Application Cleanup

```bash
# Remove any backup/temp files from the web root
sudo find /var/www/html/kcr-tracks/ -name "*.bak" -delete
sudo find /var/www/html/kcr-tracks/ -name "*.old" -delete
sudo find /var/www/html/kcr-tracks/ -name "*.tmp" -delete
sudo find /var/www/html/kcr-tracks/ -name "*~" -delete

# Clear application logs (they will regenerate)
sudo rm -f /var/www/html/kcr-tracks/logs/*.log
sudo touch /var/www/html/kcr-tracks/logs/.gitkeep

# Ensure music directory exists but is empty (stations load their own music)
sudo rm -rf /var/www/html/kcr-tracks/music/*
sudo touch /var/www/html/kcr-tracks/music/.gitkeep

# Verify ownership is correct
sudo chown -R www-data:www-data /var/www/html/kcr-tracks/music
sudo chown -R www-data:www-data /var/www/html/kcr-tracks/logs
sudo chmod 755 /var/www/html/kcr-tracks/music
sudo chmod 755 /var/www/html/kcr-tracks/logs
```

---

### Phase 3: System Cleanup -- Credentials and Identity

```bash
# Remove SSH host keys (regenerate automatically on first boot)
sudo rm -f /etc/ssh/ssh_host_*

# Remove your personal SSH authorized keys
rm -f /home/pi/.ssh/authorized_keys
rm -f /home/pi/.ssh/known_hosts

# Reset pi user password to the default appliance password
echo "pi:changeme123" | sudo chpasswd

# Clear WiFi credentials -- edit manually:
sudo nano /etc/wpa_supplicant/wpa_supplicant.conf
# Should contain only:
#   ctrl_interface=DIR=/var/run/wpa_supplicant GROUP=netdev
#   update_config=1
#   country=NZ

# If using NetworkManager instead (Bookworm default):
sudo nmcli connection show
# Delete any saved WiFi connections:
# sudo nmcli connection delete "<connection-name>"

# Reset hostname to generic appliance name
sudo hostnamectl set-hostname ds-tracks
sudo sed -i 's/127.0.1.1.*/127.0.1.1\tds-tracks/' /etc/hosts
```

---

### Phase 4: System Cleanup -- Caches and Logs

```bash
# Clear package cache
sudo apt clean
sudo apt autoremove -y

# Clear systemd journal (keep only last day)
sudo journalctl --vacuum-time=1d

# Clear rotated logs
sudo rm -f /var/log/*.gz
sudo rm -f /var/log/*.1
sudo rm -f /var/log/*.old

# Clear specific large logs
sudo truncate -s 0 /var/log/syslog
sudo truncate -s 0 /var/log/messages
sudo truncate -s 0 /var/log/auth.log
sudo truncate -s 0 /var/log/kern.log
sudo truncate -s 0 /var/log/daemon.log

# Clear Chromium cache and profile data
rm -rf /home/pi/.cache/chromium/
rm -rf /home/pi/.config/chromium/

# Clear bash history
rm -f /home/pi/.bash_history
rm -f /root/.bash_history 2>/dev/null
history -c

# Clear any USB status file
sudo rm -f /run/kcr-usb-status.json

# Clear thumbnail cache
rm -rf /home/pi/.cache/thumbnails/

# Check remaining disk usage
df -h
```

---

### Phase 5: Verify the Appliance Still Works

After cleanup, **reboot and test** before imaging:

```bash
sudo reboot
```

After reboot, verify:

- [ ] Pi boots to kiosk (Chromium fullscreen on login.php)
- [ ] Idle screen displays correctly
- [ ] USB detection works (insert a USB drive, check status)
- [ ] Can create a new user and session
- [ ] Can import tracks from USB
- [ ] Tracks play in the player
- [ ] Apache is running: `systemctl status apache2`
- [ ] SSH is accessible (for future maintenance)
- [ ] SSH host keys regenerated: `ls /etc/ssh/ssh_host_*`

---

### Phase 6: Create the Disk Image

On another machine with an SD card reader:

```bash
# Find the SD card device (CAREFUL -- wrong device = data loss)
lsblk

# Create the image (replace sdX with actual device)
sudo dd if=/dev/sdX of=ds-tracks-appliance-v1.0.img bs=4M status=progress

# Compress it (images are large, compression helps significantly)
xz -9 ds-tracks-appliance-v1.0.img
# Result: ds-tracks-appliance-v1.0.img.xz
```

On Windows with Win32DiskImager or Raspberry Pi Imager:
- Select the SD card as source
- Read to a .img file
- Compress with 7-Zip afterwards

---

## Post-Imaging: Update the Install Script

The audit files from Phase 1 are your reference. Compare them against `scripts/install-raspberry-pi.sh` and update the script so it can reproduce this image from a clean Pi OS install. Key things to capture:

- [ ] Every manually installed package
- [ ] Every modified file in /etc/
- [ ] Every enabled/disabled systemd service
- [ ] All file ownership and permission settings
- [ ] Apache virtual host configuration
- [ ] USB detection (udev rules + systemd service template)
- [ ] Kiosk boot chain (getty auto-login, .bash_profile, .xinitrc)
- [ ] fstab entries for music SSD (if applicable)

This ensures the image can be rebuilt from scratch in future, rather than depending on a single golden SD card.

---

## First-Boot Considerations

Things that should happen automatically when a freshly flashed image boots for the first time:

| Item | How |
|------|-----|
| SSH host key regeneration | Handled by OpenSSH automatically if keys are missing |
| Filesystem expansion | Raspberry Pi OS does this on first boot by default |
| Timezone/locale | Pre-set in image; station can change via raspi-config |
| Music directory creation | Apache/PHP creates on first session if missing |
| Hostname | Generic ds-tracks from image; station can change via raspi-config |

---

## Distribution Checklist

Before sending an image to a station:

- [ ] Image boots to kiosk without intervention
- [ ] No personal SSH keys or WiFi credentials in image
- [ ] Default password documented in deployment guide
- [ ] Music directory is empty (station loads their own)
- [ ] Image file is compressed (.img.xz)
- [ ] Image version and date noted in filename
- [ ] Deployment guide and music migration guide included
