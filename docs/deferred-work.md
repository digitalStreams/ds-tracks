# DS-Tracks - Deferred Work

## 1. Rebrand from "KCR Tracks" to "DS-Tracks"

**Status:** Planned, not yet implemented

**Scope:** 683 occurrences of "KCR"/"kcr" across 44 files. Every client-facing reference needs updating.

### Naming Convention

| Current | New |
|---------|-----|
| KCR Tracks | DS-Tracks |
| kcr-tracks | ds-tracks |
| KCR-MUSIC | DS-MUSIC |
| KCRSecurity | DSSecurity |
| kcrUsb | dsUsb |
| kcr-config.txt | ds-config.txt |

### Client-Facing Changes

| Category | Current | New | Files |
|----------|---------|-----|-------|
| Product name | KCR Tracks | DS-Tracks | All docs, UI, scripts |
| Web URL path | `/kcr-tracks/` | `/ds-tracks/` | login.php, json.php, JS, .htaccess, installer |
| Install directory | `/var/www/html/kcr-tracks/` | `/var/www/html/ds-tracks/` | All build/boot scripts |
| USB drive label | `KCR-MUSIC` | `DS-MUSIC` | Mount scripts, fstab, setup script, guide |
| Hostname | `kcr-tracks` | `ds-tracks` | Config, first-boot, build guide |
| Config file | `kcr-config.txt` | `ds-config.txt` | Boot files, first-boot script, guide |
| Default branding | "KCR" / "Kiama Community Radio" | Generic defaults | branding.php, config template |
| Documentation | All .md files | DS-Tracks throughout | 10 markdown files |

### Internal Changes

| Category | Current | New | Files |
|----------|---------|-----|-------|
| Script names | `kcr-usb-mount.sh`, `kcr-first-boot.sh` etc | `ds-*` equivalents | 6 scripts + references |
| Service names | `kcr-first-boot.service` | `ds-first-boot.service` | Systemd unit + build script |
| udev rules | `99-kcr-usb.rules` | `99-ds-usb.rules` | Rules file + build script |
| Mount points | `/mnt/kcr-music`, `/media/kcr-usb` | `/mnt/ds-music`, `/media/ds-usb` | Multiple scripts |
| Log files | `kcr-usb.log`, `kcr-build.log` etc | `ds-*.log` | Build/mount scripts |
| PHP class | `KCRSecurity` | `DSSecurity` | config.php + callers |
| JS namespace | `window.kcrUsb` | `window.dsUsb` | usb-browser.js |
| CSS classes | `.kcr-*` | `.ds-*` | touch.css + login.php |
| Shell variables | `KCR_SOURCE_DIR`, `KCR_INSTALL_DIR` | `DS_*` | All shell scripts |

### Risk

The riskiest part is the **URL path change** (`/kcr-tracks/` to `/ds-tracks/`) because it affects Apache routing and browser resource loading. All other changes are straightforward find-and-replace.

### Estimated Effort

Approximately 1 hour of careful find-and-replace work across all files.

---

## 2. Configurable Music Storage (SD Card or USB)

**Status:** Planned, not yet implemented

**Scope:** Allow users to choose between storing music on the SD card or on a separate USB SSD, via a setting in the config file.

See separate implementation — this was approved and is being built.

---

## 3. Admin UI Toggle for Mouse Cursor Visibility

**Status:** Planned, not yet implemented

**Scope:** Add a "Show mouse cursor" toggle to the admin settings page (`admin_customize.php`), allowing administrators to switch between touchscreen-only mode (cursor hidden) and mouse+keyboard mode (cursor visible).

### Current State
- `HIDE_CURSOR` setting already exists in `/boot/firmware/kcr-config.txt`
- `.bash_profile` and `.xinitrc` already read this setting and conditionally hide the cursor
- Changing the setting currently requires SSH access or editing the config file manually

### Implementation
1. Add checkbox/toggle to admin settings UI
2. PHP save action writes `HIDE_CURSOR=true|false` to the config file
3. Requires `sudo` access to write to `/boot/firmware/` — needs a helper script (check how music storage toggle handles this)
4. Display note: "Takes effect after reboot"
5. Optionally offer a "Reboot now" button

### Notes
- `unclutter` auto-hides the cursor after 0.5s of inactivity regardless of this setting (nice UX for mouse users)
- Two layers control cursor: X server (`-nocursor` flag) and Chromium (`--cursor=none` flag) — both must respect the config

---

## 4. Touch-Screen Restart / Reboot Controls

**Status:** Planned, not yet implemented

**Scope:** Provide emergency restart and reboot options accessible from the kiosk touch interface, without needing SSH or physical access.

### Two Levels

| Action | What it does | Speed | When to use |
|--------|-------------|-------|-------------|
| **Restart App** | Kills and restarts the X session / Chromium kiosk | ~5 seconds | UI freeze, JavaScript hang, cached state issues |
| **Reboot System** | Full OS reboot (`sudo reboot`) | ~30-60 seconds | USB detection stuck, Apache hung, network problems |

Both are non-destructive. The Pi auto-login chain (`getty → pi user → .bash_profile → startx → .xinitrc → Chromium`) brings the kiosk back to the idle screen automatically — no manual login required.

### UI Placement

1. **Admin settings page** (`admin_customize.php`) — "Restart App" and "Reboot System" buttons with confirmation dialogs
2. **Hidden emergency gesture on idle screen** — long-press (3+ seconds) on the station logo/header area as a fallback when the app is unresponsive

### Implementation

1. Create `reboot.php` endpoint that accepts `action=restart|reboot`
2. Add sudoers entries for `www-data` (tightly scoped):
   ```
   www-data ALL=(ALL) NOPASSWD: /sbin/reboot
   www-data ALL=(ALL) NOPASSWD: /bin/systemctl restart getty@tty1
   ```
3. Admin page: two buttons with confirmation dialog ("Reboot now? The system will restart in ~30 seconds")
4. Idle screen: hidden long-press handler on logo area → shows restart/reboot confirmation
5. PHP endpoint validates the action parameter and executes the appropriate command

### Security

- Sudoers entries limited to only `reboot` and `getty restart` — no other commands
- Confirmation dialog prevents accidental triggers
- Hidden gesture requires 3-second press to activate

---

## 5. On-Screen Keyboard for Kiosk

**Status:** Implemented (March 2026)

**Scope:** Custom HTML on-screen keyboard for the Pi kiosk touchscreen, with no OS-level dependencies.

### Implementation
- **File:** `js/on-screen-keyboard.js` — self-contained IIFE, appended inside `#content`
- **CSS:** keyboard styles in `css/style.css` (search for “On-Screen Keyboard” section)
- **Layouts:** 4 modes — lowercase, uppercase, numbers, symbols
- **Behaviour:** auto-shows on `<input type="text">` focus, hides on Done or tap outside
- **Shift:** tap once = shift next letter, tap twice = caps lock, tap again = off
- **Theme:** compact white/off-white corporate style, rounded container, centered (not full-width)
- **Positioning:** `position: absolute; bottom: 0` inside `#content` (overlays bottom of screen)
- **Public API:** `window.dsKeyboard.show(inputEl)` / `window.dsKeyboard.hide()`

---

## 6. Delete Functionality (Tracks, Sessions, Users)

**Status:** Implemented (March 2026)

**Scope:** Delete at three levels — individual tracks, entire sessions, and all sessions for a user. All deletes physically remove files from disk.

### Implementation
- **API:** `json.php` accepts `delete_action` POST parameter with values `track`, `session`, or `user`
- **Track delete:** Removes single file; auto-removes empty session directory
- **Session delete:** Removes all files in session directory, then removes directory
- **User delete:** Removes all sessions matching `Username-*` pattern
- **UI:** Trash icons on track rows, delete buttons on session rows, delete icons on user dropdown
- **Safety:** Confirmation dialogs before all deletes; path traversal protection via `isValidMusicPath()`
- **Cleanup:** Session labels in `session-labels.json` are cleaned up on session/user deletion
