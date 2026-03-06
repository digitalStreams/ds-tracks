# DS-Tracks v2.0 - Quick Start Guide

## For Radio Station Administrators

This is a 15-minute quick-start guide to get DS-Tracks running on your Raspberry Pi.

---

## What You'll Need

- Raspberry Pi 4 (2GB+ RAM)
- MicroSD card (16GB+) with Raspberry Pi OS installed
- Network connection
- This software package

---

## Installation (10 minutes)

### Step 1: Transfer Files

**Option A - Using USB Drive:**
```bash
1. Copy ds-tracks folder to USB drive
2. Insert USB into Raspberry Pi
3. Mount: sudo mount /dev/sda1 /mnt
4. Copy: cp -r /mnt/ds-tracks /home/pi/
```

**Option B - Using Network:**
```bash
# From your computer:
scp -r ds-tracks/ pi@raspberrypi.local:/home/pi/
```

### Step 2: Run Installer

```bash
cd /home/pi/ds-tracks
chmod +x install-raspberry-pi.sh
sudo ./install-raspberry-pi.sh
```

**Wait 5-10 minutes** for installation to complete.

### Step 3: Access Application

Open browser to the URL shown at end of installation:
```
http://<raspberry-pi-ip>
```

---

## Customization (5 minutes)

### 1. Change Admin Password

```bash
sudo nano /var/www/html/ds-tracks/admin_customize.php
```

Find and change:
```php
$ADMIN_PASSWORD = 'changeme123';  // Change this!
```

Save: `Ctrl+X`, `Y`, `Enter`

### 2. Upload Your Logo

```bash
# Copy your logo to the Pi
scp your-logo.png pi@raspberrypi.local:/var/www/html/ds-tracks/images/station-logo.png
```

### 3. Configure Branding

1. Open: `http://<raspberry-pi-ip>/admin_customize.php`
2. Login with admin password
3. Enter your station details
4. Choose your colors
5. Save

---

## Security (Optional but Recommended)

```bash
cd /home/pi/ds-tracks
chmod +x security-hardening.sh
sudo ./security-hardening.sh
```

Follow the prompts. This will:
- Set up firewall
- Harden SSH
- Install Fail2Ban
- Configure backups

---

## Testing

1. Visit: `http://<raspberry-pi-ip>`
2. Enter a username
3. Click "Choose Files"
4. Select an MP3 file from USB drive
5. Upload and play

---

## Troubleshooting

### Can't Access Web Interface
```bash
sudo systemctl status apache2
hostname -I  # Get IP address
```

### Upload Not Working
```bash
sudo chown -R www-data:www-data /var/www/html/ds-tracks/music
sudo chmod 755 /var/www/html/ds-tracks/music
```

### Check Logs
```bash
sudo tail -f /var/www/html/ds-tracks/logs/app_errors.log
```

---

## Next Steps

1. ✓ Test with presenters
2. ✓ Set up regular backups
3. ✓ Configure static IP
4. ✓ Consider HTTPS (for external access)
5. ✓ Read DEPLOYMENT-GUIDE.md for details

---

## Daily Use

**Presenters:**
1. Insert USB drive with music
2. Visit application URL
3. Enter name
4. Upload tracks
5. Play music

**Simple!**

---

## Support

- Detailed Guide: [`DEPLOYMENT-GUIDE.md`](DEPLOYMENT-GUIDE.md)
- Security Info: [`SECURITY-UPDATES.md`](../archive/SECURITY-UPDATES.md)
- User Manual: [`KCR-Tracks-User-Manual-D02-2023-03-14.pdf`](../archive/KCR-Tracks-User-Manual-D02-2023-03-14.pdf)

---

**That's it! You're ready to go.** 🎵

Total time: ~15 minutes
