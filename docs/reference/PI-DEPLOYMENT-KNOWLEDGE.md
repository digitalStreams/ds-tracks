# Raspberry Pi Deployment Knowledge Base

Lessons learned from building and updating DS-Tracks kiosk appliances on Raspberry Pi OS Bookworm (2024+).

## Critical Platform Facts

| Topic | Detail |
|-------|--------|
| Chromium package | `chromium` (NOT `chromium-browser`) |
| PHP version | 8.4 (not 8.1 or 7.4) |
| Apache PrivateTmp | Enabled by default — PHP cannot read `/tmp/` files from other services |
| UFW firewall | Blocks port 22 by default — must `sudo ufw allow 22` for SSH |
| udev sandbox | Cannot run scripts directly on Bookworm — use systemd service templates |

## File Transfer & Deployment

### Windows to Pi File Transfer
- **Line endings**: All scripts edited on Windows have `\r\n` line endings. These MUST be converted before execution: `sed -i 's/\r//' scriptname.sh`
- **Permissions**: Cannot SCP directly to `/var/www/html/ds-tracks/` — transfer to `/home/pi/` first, then `sudo cp`
- **Bulk transfer**: Use `scp -r` or `rsync` instead of individual file transfers
- **Deploy script**: Use `scripts/deploy-to-pi.sh` for automated transfer + permissions

### After Updating Files on Pi
1. Fix ownership: `sudo chown -R www-data:www-data /var/www/html/ds-tracks/music /var/www/html/ds-tracks/logs`
2. Fix permissions: `sudo chmod 755 /var/www/html/ds-tracks/music /var/www/html/ds-tracks/logs`
3. Restart Apache: `sudo systemctl restart apache2`
4. Clear Chromium cache or reboot the Pi

## USB System Architecture

### Why udev Cannot Run Scripts Directly
Raspberry Pi OS Bookworm sandboxes udev — scripts called from udev rules fail silently. The solution is a systemd service template:

- **udev rule** (`99-kcr-usb.rules`): Tags the device and sets `ENV{SYSTEMD_WANTS}` to trigger the service
- **systemd service** (`kcr-usb-mount@.service`): Runs the actual mount script
- **Mount script** (`kcr-usb-mount.sh`): Mounts the drive and writes status JSON

### USB Status File Location
- **Correct**: `/run/kcr-usb-status.json`
- **Wrong**: `/tmp/kcr-usb-status.json` (Apache PrivateTmp blocks access)

### USB Mount Point
- User USB drives: `/media/kcr-usb`
- Music SSD (if separate): `/mnt/ds-music` (labelled `DS-MUSIC`)

## Chromium Kiosk Issues

### JavaScript Caching
Chromium in kiosk mode caches JS aggressively. After updating JS files:
- Add version query strings to script tags: `<script src="js/app.js?v=2"></script>`
- Or clear the cache: SSH in, `rm -rf /home/pi/.cache/chromium/`
- Or reboot the Pi (most reliable)

### Kiosk URL
- The kiosk loads `http://localhost/login.php`
- NOT `http://localhost/index.php` or `http://localhost/ds-tracks/login.php`
- Apache document root: `/var/www/html/kcr-tracks/` (pending KCR to DS rebrand)

### Kiosk Crash Recovery
The xinitrc script runs Chromium in a `while true` loop — if it crashes, it restarts after 3 seconds.

## Screen Blanking (Power Save)

The kiosk screen blanks after 5 minutes of idle (no touch input). Configuration is via `xset` in `.xinitrc`.

### Critical: xset Must Run After openbox

If `xset` commands are placed before `openbox-session &`, openbox resets them on startup. The xset commands **must** run after openbox and all waits, just before Chromium launches.

```bash
# WRONG — openbox will override these
xset s 300
openbox-session &

# RIGHT — set after openbox has started
openbox-session &
sleep 1
# ... waits ...
xset s 300
xset s blank
xset +dpms
xset dpms 300 300 300
```

### Verifying Screen Blanking

```bash
DISPLAY=:0 xset q | grep -A5 "Screen Saver\|DPMS"
```

Expected output: `timeout: 300`, `prefer blanking: yes`, `DPMS is Enabled`.

### Adjusting Timeout

To change temporarily (until reboot): `DISPLAY=:0 xset s <seconds> && DISPLAY=:0 xset dpms <s> <s> <s>`
To change permanently: edit the `xset` lines in `/home/pi/.xinitrc`.

## Boot Splash Screen (Plymouth)

Plymouth displays a branded image during boot, replacing scrolling Linux text.

### Setup Components

| Component | Location |
|-----------|----------|
| Theme descriptor | `/usr/share/plymouth/themes/ds-tracks/ds-tracks.plymouth` |
| Theme script | `/usr/share/plymouth/themes/ds-tracks/ds-tracks.script` |
| Splash image | `/usr/share/plymouth/themes/ds-tracks/splash.png` (800x480 PNG) |
| Kernel cmdline | `/boot/firmware/cmdline.txt` — needs `quiet splash logo.nologo vt.global_cursor_default=0` |
| Config | `/boot/firmware/config.txt` — needs `disable_splash=1` (hides rainbow square) |

### Plymouth Lessons Learned

- **Do NOT redirect `console=tty3`** — X server fails with `AddScreen/ScreenInit failed` because it cannot find the display on tty1
- **Do NOT mask `plymouth-quit.service`** — this prevents proper boot completion and causes black screen hangs
- Plymouth quits at the display-manager systemd target; some Debian text may show briefly between Plymouth exit and X11 start — this gap is currently unavoidable without framebuffer manipulation
- `feh` fullscreen splash as a bridge was tested but the gap is too short to be effective
- After changing Plymouth theme: `sudo update-initramfs -u` is required

### Rebuilding Plymouth Theme

```bash
sudo /usr/sbin/plymouth-set-default-theme ds-tracks
sudo update-initramfs -u
```

## Boot Sequence (xinitrc)

The kiosk boot sequence in `/home/pi/.xinitrc` has been optimised:

### Old Sequence (35+ seconds of black screen)
1. xset commands (overridden by openbox)
2. openbox start + sleep 2
3. Network ping loop (up to 30 seconds) — **unnecessary for localhost appliance**
4. sleep 3 (Apache wait)
5. Chromium launch

### New Sequence (10 seconds typical)
1. openbox start + sleep 1
2. curl loop checking `http://localhost/login.php` (up to 10 seconds, exits as soon as Apache responds)
3. xset screen blanking commands (after openbox, so they stick)
4. Plymouth quit
5. Chromium launch

Key change: the 30-second network wait loop was removed entirely — the appliance runs everything on localhost with no network dependency.

## Build Script Best Practices

- **No hardcoded paths** — scripts must auto-detect their location
- **`set -e` caution** — any failure kills the build; use `|| true` for optional packages
- **Idempotent** — scripts must be safe to re-run after partial failure
- **Test on actual Pi OS** — never assume package names or versions

## Known Working Configuration (Mar 2026)

- Raspberry Pi OS Bookworm (64-bit)
- PHP 8.4 with Apache2
- Chromium (kiosk mode, auto-restart)
- USB auto-detect via systemd service template
- Music storage: configurable (SD card or USB SSD)
- Plymouth boot splash (DS-Tracks branded)
- Screen blanking after 5 min idle (DPMS)
- feh installed (available for future splash bridging)

## Troubleshooting Quick Reference

| Problem | Solution |
|---------|----------|
| SSH connection refused | `sudo ufw allow 22` on the Pi |
| SCP permission denied | Transfer to `/home/pi/` then `sudo cp` |
| Shell script won't run | `sed -i 's/\r//' script.sh` (Windows line endings) |
| USB drive not detected | Check `systemctl status kcr-usb-mount@sda1` and `/var/log/kcr-usb.log` |
| PHP can't read USB status | Status file must be in `/run/` not `/tmp/` |
| Kiosk shows old JS | Add `?v=N` to script tags or reboot Pi |
| Apache won't start | Check PHP version matches config: `php -v` |
| Chromium package not found | Use `chromium` not `chromium-browser` |
| Kiosk black screen | SSH in, check `systemctl status apache2`, check xinitrc |
| Screen won't blank | Verify xset runs AFTER openbox: `DISPLAY=:0 xset q` |
| Plymouth not showing | Check `cmdline.txt` has `quiet splash`, run `sudo update-initramfs -u` |
| X server fails after cmdline change | Do NOT use `console=tty3` — X needs tty1 |
| Boot hangs on black screen | Do NOT mask `plymouth-quit.service` — unmask and reboot |
