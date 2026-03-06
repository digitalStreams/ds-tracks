# DS-Tracks - Agent Start Here

**Read this file first.** Then explore `docs/deferred-work.md` and `docs/reference/` for deeper context.

## What Is This?

DS-Tracks is a Raspberry Pi kiosk application for community radio stations. Users insert a USB drive, browse and select tracks via a touchscreen, create named sessions, and play music. It runs fullscreen Chromium on a Pi with no keyboard or mouse.

## Tech Stack

- **Backend:** PHP 8.x on Apache 2 (no framework, no Composer)
- **Frontend:** Vanilla JS + jQuery + jQuery UI (no npm, no bundler)
- **Audio:** HTML5 `<audio>` element
- **Deployment target:** Raspberry Pi OS Bookworm (64-bit)

## Key Files

| File | What it does |
|------|-------------|
| `login.php` | Main entry point (~1100 lines). Loads both UI systems. |
| `config.php` | Security class (DSSecurity), constants, session config |
| `js/usb-browser.js` | Touch interface: USB polling, file browser, player (namespace: `window.dsUsb`) |
| `css/touch.css` | Touch interface styles |
| `css/style.css` | Legacy interface styles |
| `usb-status.php` | Returns USB mount status as JSON |
| `usb-browse.php` | Lists files/folders on mounted USB |
| `usb-import.php` | Copies selected files from USB into session directory |
| `json.php` | API: session list, track list, session management, delete (track/session/user) |
| `upload.php` | Legacy file upload handler |
| `admin_customize.php` | Admin settings page |
| `scripts/deploy-to-pi.sh` | SCP-based deployment to Pi |
| `docs/deferred-work.md` | Planned features and tech debt |

## Dual UI System

There are **two parallel interfaces** in `login.php`:

### Touch Interface (kiosk)
- Activated when USB is detected on the Pi
- Code lives in `js/usb-browser.js`
- Flow: Idle screen -> USB file browser -> User ID -> Import -> Player
- CSS: `touch.css`, classes prefixed `.touch-`

### Legacy Interface (browser)
- Traditional form-based UI for desktop browsers
- Code lives inline in `login.php` (jQuery-heavy)
- Flow: Name entry -> Session list -> Player
- Accessible via "Return to a previous session" link
- The `legacyMode` flag in usb-browser.js bridges legacy session flow to use the USB file browser

## Deployment

### Pi Connection
```
Host: pi@10.1.1.146
Web root: /var/www/html/kcr-tracks/
URL on Pi: http://localhost/login.php  (NOT /kcr-tracks/login.php)
```

### Deploy Changes
```bash
# From project root on Windows:
./scripts/deploy-to-pi.sh 10.1.1.146 pi

# Or manually:
scp login.php pi@10.1.1.146:/home/pi/
scp js/usb-browser.js pi@10.1.1.146:/home/pi/
ssh pi@10.1.1.146
sudo cp /home/pi/login.php /var/www/html/kcr-tracks/
sudo cp /home/pi/usb-browser.js /var/www/html/kcr-tracks/js/
```

### Kiosk Boot Chain
```
getty auto-login -> pi user -> .bash_profile -> startx -> .xinitrc -> Chromium (fullscreen)
```
The Pi auto-recovers to the idle screen after reboot — no manual login needed.

## Gotchas

### Permissions
- `music/` and `logs/` directories **must** be owned by `www-data:www-data` (not `pi:pi`)
- PHP runs as `www-data`; if you manually copy files, fix ownership: `sudo chown -R www-data:www-data music/ logs/`

### Chromium Cache
- Chromium in kiosk mode caches JS aggressively
- **Always bump the cache buster** when changing JS: `<script src="js/usb-browser.js?v=4">`
- Or clear cache: `ssh pi@... 'rm -rf /home/pi/.cache/chromium/'` then reboot

### Apache PrivateTmp
- Apache uses a private `/tmp` namespace — the app cannot see files in the real `/tmp`
- USB status is stored in `/run/kcr-usb-status.json` (not `/tmp/`)

### SCP to Web Root
- You cannot SCP directly to `/var/www/html/` as user `pi`
- SCP to `/home/pi/` first, then `sudo cp` to the web root

### Windows Line Endings
- The deploy script auto-converts CRLF to LF for shell scripts
- If running scripts manually on Pi, use `sed -i 's/\r//' script.sh`

### Cookie Format
- Session identity stored in cookie: `username=Name-YYMMDD-HHMMSS` (14-day expiry)
- Set via `js.cookie.min.js` library

### URL Path Mismatch
- Dev (XAMPP): accessed at `/ds-tracks/login.php` or similar
- Pi: accessed at `/login.php` (Apache DocumentRoot is `/var/www/html/kcr-tracks/`)
- A full KCR to DS rebrand (including URL paths) is planned but not yet done

### EEXIST Bug with Edit/Write Tools
- Claude Code's Edit and Write tools sometimes fail with `EEXIST: file already exists, mkdir` on this repo
- Workaround: use Bash with `cat >`, `sed -i`, or Python scripts to modify files instead

## Pending Work

See `docs/deferred-work.md` for full details. Summary:

1. **KCR to DS rebrand** — 683 occurrences across 44 files, including URL path `/kcr-tracks/` to `/ds-tracks/`
2. **Configurable music storage** — SD card vs USB SSD toggle (partially implemented)
3. **Admin cursor toggle** — Show/hide mouse cursor from admin UI
4. **Touch restart/reboot** — Emergency restart buttons accessible from kiosk touch screen
5. **On-screen keyboard** — Pi kiosk has no virtual keyboard for text inputs (needs `onboard` or similar)
6. ~~station-logo.png 404~~ — Fixed (deployed to Pi)

## Project Structure

```
ds-tracks/
├── login.php              # Main entry point (dual UI)
├── config.php             # Security, constants
├── json.php               # Session/track API
├── usb-*.php              # USB status, browse, import, eject
├── upload.php             # Legacy upload
├── admin_customize.php    # Admin settings
├── branding.php           # Station branding
├── css/                   # Stylesheets
├── js/                    # JavaScript (usb-browser.js is the big one)
├── images/                # Logos, assets
├── music/                 # User sessions (Name-YYMMDD-HHMMSS/ subdirs)
├── logs/                  # App error/info logs
├── appliance/             # Pi image build system
├── scripts/               # Deploy, install, build scripts
└── docs/                  # Documentation
    ├── deferred-work.md   # READ THIS
    ├── reference/         # Architecture, deployment KB
    ├── guides/            # Build day, deployment, quick start
    └── archive/           # Older docs (still useful for context)
```
