# KCR Tracks Appliance Build System

This folder contains everything needed to build a distributable Raspberry Pi appliance image for KCR Tracks.

## Folder Contents

```
appliance/
├── README.md                 # This file
├── build-appliance.sh        # Master build script
├── imager-manifest.json      # Raspberry Pi Imager integration
├── boot-files/
│   └── kcr-config.txt        # User configuration template
├── first-boot/
│   ├── kcr-first-boot.sh     # First-boot configuration script
│   └── kcr-first-boot.service # Systemd service
└── kiosk/
    ├── xinitrc               # X session startup
    ├── bash_profile          # Auto-start X on login
    └── openbox-autostart     # Window manager config
```

## Quick Start (Build an Image)

### What You Need

- Raspberry Pi 4 or 5 (for building)
- 32GB+ SD card or USB SSD
- Internet connection
- ~2 hours

### Steps

1. **Flash Raspberry Pi OS Lite (64-bit)** to your SD card using Raspberry Pi Imager

2. **Boot the Pi** and connect via SSH or keyboard

3. **Copy KCR-Tracks2** folder to the Pi:
   ```bash
   scp -r /path/to/KCR-Tracks2 pi@raspberrypi.local:/tmp/
   ```

4. **Run the build script**:
   ```bash
   cd /tmp/KCR-Tracks2/appliance
   sudo ./build-appliance.sh
   ```

5. **Shutdown** when complete:
   ```bash
   sudo shutdown -h now
   ```

6. **Create image** on another Linux computer:
   ```bash
   sudo dd if=/dev/sdX of=kcr-tracks-v2.0.img bs=4M status=progress
   ```

7. **Shrink and compress**:
   ```bash
   # Download PiShrink
   wget https://raw.githubusercontent.com/Drewsif/PiShrink/master/pishrink.sh
   chmod +x pishrink.sh

   # Shrink image
   sudo ./pishrink.sh kcr-tracks-v2.0.img

   # Compress
   xz -9 -T0 kcr-tracks-v2.0.img
   ```

8. **Generate checksum**:
   ```bash
   sha256sum kcr-tracks-v2.0.img.xz > checksums.txt
   ```

## What the Build Script Does

| Phase | Description |
|-------|-------------|
| 1 | Updates system packages |
| 2 | Installs KCR Tracks (Apache, PHP, application) |
| 3 | Installs kiosk components (X11, Chromium, auto-login) |
| 4 | Configures first-boot system (auto-expand, config reader) |
| 5 | Applies security hardening (firewall, SSH disabled) |
| 6 | Configures display settings |
| 7 | Optimizes for appliance use |
| 8 | Cleans up for imaging |

## Distribution

### Simple Download Distribution

Provide users with:
- `KCR-Tracks-v2.0.img.xz` - Compressed image
- `INSTALLATION-GUIDE.pdf` - Visual instructions
- `checksums.txt` - For verification

### Raspberry Pi Imager Integration

To make KCR Tracks appear in Raspberry Pi Imager's menu:

1. Host the image on your web server
2. Update `imager-manifest.json` with:
   - Correct download URL
   - Actual file sizes
   - SHA256 hash
3. Host the manifest JSON file
4. Users add your manifest URL to Raspberry Pi Imager

## Testing Checklist

Before distributing, test:

- [ ] Fresh flash boots successfully
- [ ] First-boot expands filesystem
- [ ] Station name configured from kcr-config.txt
- [ ] Kiosk mode starts automatically
- [ ] Touch input works
- [ ] Audio plays correctly
- [ ] File uploads work
- [ ] Sessions persist after reboot
- [ ] Admin panel accessible from network

## File Details

### build-appliance.sh

Master script that orchestrates the entire build process. Run once on a fresh Pi OS installation.

### kcr-first-boot.sh

Runs automatically on first boot of a user's appliance. It:
- Expands the filesystem to fill the SSD
- Reads `/boot/kcr-config.txt` and applies settings
- Configures WiFi if credentials provided
- Sets timezone and hostname
- Enables/disables SSH
- Sets correct file permissions
- Reboots to apply changes

### kcr-config.txt

User-editable configuration file on the boot partition. Can be edited on Windows/Mac before first boot. Settings include:
- Station name and website
- Timezone
- WiFi credentials
- Display rotation
- SSH access

### Kiosk Files

- `xinitrc` - Starts Chromium in fullscreen kiosk mode
- `bash_profile` - Auto-starts X when user logs in
- `openbox-autostart` - Disables screen blanking

## Troubleshooting Build Issues

### "Source not found"

Ensure KCR-Tracks2 is in `/tmp/`:
```bash
ls /tmp/KCR-Tracks2/install-raspberry-pi.sh
```

### "Permission denied"

Run with sudo:
```bash
sudo ./build-appliance.sh
```

### Build fails partway through

Check the log file:
```bash
cat /var/log/kcr-build.log
```

### Kiosk doesn't start

Check the kiosk log:
```bash
cat /var/log/kcr-kiosk.log
```

## Version History

- **v2.0** - Initial appliance build system
