# Music Migration Guide — Upgrading an Existing Pi Appliance

**Purpose:** Preserve existing music files when upgrading a DS-Tracks Pi appliance to a new version.
**Last Updated:** 2026-03-06

---

## Overview

When upgrading an existing DS-Tracks Pi appliance, the music library must be preserved. Music files live in session directories under the web root and represent user-curated content that cannot be regenerated. This guide covers backing up, transferring, and restoring music across an appliance upgrade.

---

## Prerequisites

- SSH access to the existing Pi (`ssh pi@<PI_IP>`)
- Sufficient disk space on your local machine for the backup
- The new Pi image flashed and accessible via SSH

---

## 1. Assess the Existing Installation

Before backing up, check what you're working with:

```bash
ssh pi@<PI_IP>

# Check where music lives
ls /var/www/html/kcr-tracks/music/

# Check total size
sudo du -sh /var/www/html/kcr-tracks/music/

# Check if a separate music SSD is mounted
mount | grep ds-music
df -h
```

### Music on a Separate SSD (DS-MUSIC label)

If the Pi uses a separate USB SSD mounted at `/mnt/ds-music`, the music survives an SD card reflash automatically — the SSD is untouched. In this case:

1. Reflash the SD card with the new image
2. Boot the Pi — the SSD auto-mounts via fstab/systemd
3. Verify music is accessible: `ls /mnt/ds-music/`
4. **Skip to Section 4 (Post-Restore Verification)**

### Music on the SD Card

If music is stored on the SD card under `/var/www/html/kcr-tracks/music/`, you must back it up before reflashing. Continue to Section 2.

---

## 2. Back Up Music from the Old Pi

### Option A: Tar Archive (Recommended)

Creates a single compressed archive. Best for reliable transfer of many small files.

```bash
# On the Pi — create the archive
ssh pi@<PI_IP>
sudo tar czf /home/pi/music-backup.tar.gz -C /var/www/html/kcr-tracks music/
ls -lh /home/pi/music-backup.tar.gz
exit

# On your local machine — download
scp pi@<PI_IP>:/home/pi/music-backup.tar.gz .
```

For large libraries, you can stream directly without creating a temp file on the Pi:

```bash
ssh pi@<PI_IP> "sudo tar czf - -C /var/www/html/kcr-tracks music/" > music-backup.tar.gz
```

### Option B: Rsync (Alternative)

Better for incremental backups or resuming interrupted transfers.

```bash
# Requires rsync on both machines
# First, make music readable
ssh pi@<PI_IP> "sudo chmod -R o+r /var/www/html/kcr-tracks/music"

# Pull to local machine
rsync -avz --progress pi@<PI_IP>:/var/www/html/kcr-tracks/music/ ./music-backup/
```

### Verify the Backup

```bash
# For tar archive — list contents
tar tzf music-backup.tar.gz | head -20

# Check session directory count
tar tzf music-backup.tar.gz | grep -c '/$'

# For rsync — check directory count
ls ./music-backup/ | wc -l
```

---

## 3. Restore Music to the Upgraded Pi

After flashing the new image and confirming the Pi is accessible:

```bash
# Upload the archive
scp music-backup.tar.gz pi@<PI_IP>:/home/pi/

# SSH in and restore
ssh pi@<PI_IP>

# Extract into the web root
sudo tar xzf /home/pi/music-backup.tar.gz -C /var/www/html/kcr-tracks/

# Fix ownership and permissions
sudo chown -R www-data:www-data /var/www/html/kcr-tracks/music
sudo chmod 755 /var/www/html/kcr-tracks/music
sudo find /var/www/html/kcr-tracks/music -type d -exec chmod 755 {} \;
sudo find /var/www/html/kcr-tracks/music -type f -exec chmod 644 {} \;

# Clean up
rm /home/pi/music-backup.tar.gz
```

If restoring from an rsync backup:

```bash
# Upload the directory
scp -r ./music-backup/* pi@<PI_IP>:/home/pi/music-restore/

# SSH in and move into place
ssh pi@<PI_IP>
sudo cp -r /home/pi/music-restore/* /var/www/html/kcr-tracks/music/
sudo chown -R www-data:www-data /var/www/html/kcr-tracks/music
rm -rf /home/pi/music-restore
```

---

## 4. Post-Restore Verification

```bash
# Check music directory exists and has content
ls /var/www/html/kcr-tracks/music/

# Verify ownership
ls -la /var/www/html/kcr-tracks/music/

# Check a session directory has tracks
ls /var/www/html/kcr-tracks/music/<any-session-folder>/

# Check disk space
df -h
```

Then open the kiosk UI and verify:

- Sessions appear in the session list
- Tracks load and play within a session
- New USB imports still work alongside restored music

---

## 5. Session Directory Format

Music is organized in session directories named `Username-YYMMDD-HHMMSS`:

```
music/
  Peter-260304-143022/
    song1.mp3
    song2.wav
  Sarah-260305-091500/
    track1.mp3
```

The new version of DS-Tracks uses the same naming convention, so restored sessions are recognized automatically.

### Cookie Continuity

User identity is stored in a browser cookie (`username=Name-YYMMDD-HHMMSS`). On a kiosk Pi with a single Chromium instance:

- If the SD card was reflashed, the Chromium profile is wiped and cookies are lost. Users will need to log in again, but can select their existing session from the session list via "Return to a previous session."
- If only application files were updated (not a full reflash), cookies survive and sessions resume automatically.

---

## 6. Web Root Path Note

The old installation uses `/var/www/html/kcr-tracks/` as the web root. If the KCR-to-DS rebrand has been completed in the new image, the web root may change to `/var/www/html/ds-tracks/`. Adjust the restore path accordingly:

```bash
# If rebranded
sudo tar xzf /home/pi/music-backup.tar.gz -C /var/www/html/ds-tracks/
sudo chown -R www-data:www-data /var/www/html/ds-tracks/music
```

---

## Troubleshooting

| Problem | Cause | Solution |
|---------|-------|----------|
| Sessions don't appear in UI | Wrong ownership | `sudo chown -R www-data:www-data .../music` |
| Tracks won't play | File permissions too restrictive | `sudo find .../music -type f -exec chmod 644 {} \;` |
| "No space left on device" | SD card too small for music library | Use a separate USB SSD for music storage |
| Tar extract fails | Corrupt download | Re-download; compare sizes with `ls -l` on both ends |
| SCP permission denied to web root | Pi user can't write there | SCP to `/home/pi/` first, then `sudo cp` |
