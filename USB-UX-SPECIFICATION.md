# KCR Tracks - USB Auto-Detect & Touch UX Specification

**Version:** 1.0
**Date:** January 2026
**Status:** Approved for Implementation

---

## 1. Problem Statement

### Current User Experience

The current system uses the browser's native file picker (`<input type="file">`) to select music files. On a 15x8cm Raspberry Pi touchscreen, this is problematic:

- The OS file dialog has tiny text and buttons
- Folder navigation requires precise tapping
- The dialog is not part of our UI - we cannot control its appearance
- Users must know where their files are before the dialog opens
- The multi-step process (login → choose files → navigate → select → upload) is too complex for technophobe users

### Current Technical Flow

```
User → Login screen → Enter name → OK → Browser file dialog →
Navigate to USB → Select files → HTTP upload to server → Player
```

### Target User Experience

```
User inserts USB → Files appear → Tap to select → Play
```

---

## 2. Design Principles

| Principle | Implementation |
|-----------|----------------|
| **Touch-first, mouse-supported** | All targets 48px minimum height, clickable too |
| **One action per screen** | Each screen has one clear purpose and one primary action |
| **Eliminate decisions** | System detects what it can; only ask when necessary |
| **Show, don't tell** | No instruction text - the interface is self-evident |
| **Forgiveness** | Every action is reversible; back button always available |

---

## 3. Screen Specifications

### 3.1 Display Dimensions

| Property | Value |
|----------|-------|
| Official 7" touchscreen | 800 x 480 pixels |
| Current CSS dimensions | 795 x 447 pixels |
| Target dimensions | 800 x 480 pixels (full screen) |
| Minimum touch target | 48px height |
| Visible list items | 5-6 at 48-56px row height |
| Font size (primary) | 18px |
| Font size (secondary) | 14px |

### 3.2 Screen Flow

```
┌──────────┐     USB        ┌──────────┐              ┌──────────┐
│          │   detected      │          │   files      │          │
│  IDLE    │ ──────────────→ │  BROWSE  │ ──────────→  │  PLAYER  │
│  SCREEN  │                 │  FILES   │  selected    │          │
│          │ ←───────────── │          │              │          │
└──────────┘   USB removed   └──────────┘              └──────────┘
      │                           │                         │
      │    tap "Return to         │  tap "← Back" at        │  tap
      │    Session"               │  root level              │  "← Sessions"
      ▼                           ▼                         ▼
┌──────────┐              ┌──────────┐              ┌──────────┐
│  LOGIN   │              │  SESSION  │ ←──────────  │  SESSION │
│ (keyboard│              │  LIST    │              │  LIST    │
│  entry)  │              │          │              │          │
└──────────┘              └──────────┘              └──────────┘
```

### 3.3 Screen 1: Idle Screen

Displayed when no user is active and no USB is connected.

```
┌────────────────────────────────────────────────────────────────┐
│                                                                │
│                      [Station Logo]                            │
│                                                                │
│                                                                │
│           ┌────────────────────────────────────┐               │
│           │                                    │               │
│           │    Insert your USB drive            │               │
│           │    to begin                        │               │
│           │                                    │               │
│           └────────────────────────────────────┘               │
│                                                                │
│           ┌────────────────────────────────────┐               │
│           │    Return to a previous session     │               │
│           └────────────────────────────────────┘               │
│                                                                │
│  [Reports]                                                     │
└────────────────────────────────────────────────────────────────┘
```

**Behaviour:**
- Polls `usb-status.php` every 2 seconds
- When USB detected, automatically transitions to Browse Files screen
- "Return to a previous session" goes to the existing Login/Session flow
- "Reports" button remains in bottom-left

### 3.4 Screen 2: USB File Browser

Displayed when USB is mounted. Shows contents of current directory.

**Root level view (showing folders and files):**

```
┌────────────────────────────────────────────────────────────────┐
│  USB Drive                                    [ Select All ]   │
├────────────────────────────────────────────────────────────────┤
│                                                                │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │  📁  Friday Show                          4 tracks   →  │  │
│  ├──────────────────────────────────────────────────────────┤  │
│  │  📁  Jazz Collection                      8 tracks   →  │  │
│  ├──────────────────────────────────────────────────────────┤  │
│  │  ☐  Intro Theme.mp3                                     │  │
│  ├──────────────────────────────────────────────────────────┤  │
│  │  ☐  Outro Music.mp3                                     │  │
│  ├──────────────────────────────────────────────────────────┤  │
│  │  ☐  Station Jingle.mp3                                  │  │
│  └──────────────────────────────────────────────────────────┘  │
│                                                            ▼   │
├────────────────────────────────────────────────────────────────┤
│  0 selected           [ ← Back ]      [ Use These Tracks → ]  │
└────────────────────────────────────────────────────────────────┘
```

**Inside a folder:**

```
┌────────────────────────────────────────────────────────────────┐
│  ← Friday Show                                [ Select All ]  │
├────────────────────────────────────────────────────────────────┤
│                                                                │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │  ☑  01 Opening Theme.mp3                                │  │
│  ├──────────────────────────────────────────────────────────┤  │
│  │  ☑  02 First Song.mp3                                   │  │
│  ├──────────────────────────────────────────────────────────┤  │
│  │  ☐  03 Interview Bed.mp3                                │  │
│  ├──────────────────────────────────────────────────────────┤  │
│  │  ☐  04 Closing Theme.mp3                                │  │
│  └──────────────────────────────────────────────────────────┘  │
│                                                                │
├────────────────────────────────────────────────────────────────┤
│  2 selected           [ ← Back ]      [ Use These Tracks → ]  │
└────────────────────────────────────────────────────────────────┘
```

**Behaviour:**
- Folders shown first, then audio files
- Non-audio files are hidden completely
- Tapping a folder navigates into it
- Tapping a file row toggles its checkbox
- "Select All" selects all audio files in current folder
- Tapping again becomes "Deselect All"
- "← Back" navigates to parent folder, or to Idle screen if at root
- Selected count updates live
- "Use These Tracks → " is disabled (greyed) when 0 selected
- Selections persist across folder navigation
- Scroll indicator shown when list exceeds visible area

**Row Height:** 52px (comfortable touch target)
**Visible Items:** 5 items in scrollable area (260px)

### 3.5 Screen 3: User Identification

Shown after user taps "Use These Tracks" if username is not yet known.

```
┌────────────────────────────────────────────────────────────────┐
│                                                                │
│                      [Station Logo]                            │
│                                                                │
│                                                                │
│              ┌──────────────────────────────┐                  │
│              │  Enter your name             │                  │
│              │                              │                  │
│              │  [ ________________________ ]│                  │
│              │                              │                  │
│              │         [ OK ]               │                  │
│              └──────────────────────────────┘                  │
│                                                                │
│              ┌──────────────────────────────┐                  │
│              │  Or select your name:        │                  │
│              ├──────────────────────────────┤                  │
│              │  Peter                       │                  │
│              │  Sarah                       │                  │
│              │  Mike                        │                  │
│              └──────────────────────────────┘                  │
│                                                                │
└────────────────────────────────────────────────────────────────┘
```

**Behaviour:**
- Shows text input for new users (keyboard/on-screen)
- Shows existing user list below for returning users
- Tapping an existing name uses that name immediately
- After identification, files are copied and player loads

### 3.6 Screen 4: Player (Redesigned for Touch)

```
┌────────────────────────────────────────────────────────────────┐
│  Peter  •  Friday Show                        [ + Add More ]  │
├──────────────────────────────┬─────────────────────────────────┤
│                              │                                 │
│  ┌────────────────────────┐  │   Now Playing:                  │
│  │ ▶ 01 Opening Theme    │  │   01 Opening Theme.mp3          │
│  ├────────────────────────┤  │                                 │
│  │   02 First Song       │  │   ━━━━━━━━━●━━━━━  2:31 / 3:22  │
│  ├────────────────────────┤  │                                 │
│  │   03 Interview Bed    │  │   ┌──────────┐  ┌──────────┐    │
│  ├────────────────────────┤  │   │          │  │          │    │
│  │   04 Closing Theme    │  │   │  ⏸ PAUSE │  │  ⏭ NEXT  │    │
│  └────────────────────────┘  │   │          │  │          │    │
│                              │   └──────────┘  └──────────┘    │
│                              │                                 │
├──────────────────────────────┴─────────────────────────────────┤
│  [ ← Sessions ]       Auto-play  [●━━━]         [ 🏠 Home ]   │
└────────────────────────────────────────────────────────────────┘
```

**Behaviour:**
- Left panel: track list with 48px row height
- Currently playing track highlighted with colour
- ▶ indicator on playing track
- Right panel: large playback controls
- PAUSE/PLAY button: 80x60px minimum
- NEXT button: 80x60px minimum
- Progress bar: tappable to seek
- Auto-play toggle in footer
- "+ Add More" returns to USB browser (keeping current session)
- "← Sessions" goes to session list
- "Home" returns to idle screen
- No volume control (desk fader handles this)

---

## 4. Technical Architecture

### 4.1 System Diagram

```
┌──────────────────────────────────────────────────────────────┐
│                      LINUX LAYER                              │
│                                                              │
│  USB inserted → udev rule triggers                           │
│       ↓                                                      │
│  kcr-usb-mount.sh runs:                                      │
│    1. Identifies filesystem                                  │
│    2. Mounts to /media/kcr-usb                              │
│    3. Writes /tmp/kcr-usb-status.json                       │
│                                                              │
│  USB removed → udev rule triggers                            │
│       ↓                                                      │
│  kcr-usb-unmount.sh runs:                                    │
│    1. Unmounts /media/kcr-usb                               │
│    2. Removes /tmp/kcr-usb-status.json                      │
├──────────────────────────────────────────────────────────────┤
│                      PHP API LAYER                            │
│                                                              │
│  usb-status.php                                              │
│    GET → { "mounted": true, "label": "PETER_USB" }          │
│                                                              │
│  usb-browse.php                                              │
│    POST path=/                                               │
│    → { "current": "/", "folders": [...], "files": [...] }   │
│                                                              │
│  usb-import.php                                              │
│    POST files=[...], username=Peter                          │
│    → { "success": true, "session": "Peter-260122-1430",     │
│         "copied": 4, "errors": [] }                         │
│                                                              │
│  usb-eject.php                                               │
│    POST → { "success": true }                                │
├──────────────────────────────────────────────────────────────┤
│                    JAVASCRIPT LAYER                           │
│                                                              │
│  Poll usb-status.php every 2 seconds                        │
│  When mounted → fetch usb-browse.php → render file browser  │
│  On folder tap → fetch usb-browse.php with path             │
│  On "Use These" → fetch usb-import.php → render player      │
│  On USB removed → return to idle screen                     │
└──────────────────────────────────────────────────────────────┘
```

### 4.2 USB Mount Location

| Path | Purpose |
|------|---------|
| `/media/kcr-usb` | USB drive mount point |
| `/tmp/kcr-usb-status.json` | Status file (created/deleted by mount scripts) |

### 4.3 USB Status JSON Format

```json
{
    "mounted": true,
    "label": "PETER_USB",
    "device": "/dev/sda1",
    "filesystem": "vfat",
    "size": "16G",
    "mountpoint": "/media/kcr-usb",
    "timestamp": "2026-01-22T14:30:00"
}
```

### 4.4 Browse Response JSON Format

```json
{
    "current_path": "/Friday Show",
    "parent_path": "/",
    "folders": [
        {
            "name": "Subfolder",
            "audio_count": 3,
            "path": "/Friday Show/Subfolder"
        }
    ],
    "files": [
        {
            "name": "01 Opening Theme.mp3",
            "size": 4521984,
            "size_human": "4.3 MB",
            "extension": "mp3",
            "path": "/Friday Show/01 Opening Theme.mp3"
        }
    ]
}
```

### 4.5 Import Request/Response

**Request:**
```json
{
    "username": "Peter",
    "files": [
        "/Friday Show/01 Opening Theme.mp3",
        "/Friday Show/02 First Song.mp3"
    ]
}
```

**Response:**
```json
{
    "success": true,
    "session": "Peter-260122-143022",
    "copied": 2,
    "errors": [],
    "tracks": [
        {
            "name": "01 Opening Theme.mp3",
            "url": "/kcr-tracks/music/Peter-260122-143022/01_Opening_Theme.mp3"
        }
    ]
}
```

---

## 5. Security Considerations

### 5.1 USB File Access

| Risk | Mitigation |
|------|------------|
| Path traversal via browse API | Validate all paths are within /media/kcr-usb |
| Malicious filenames | Reuse existing sanitizeFilename() from KCRSecurity |
| Non-audio files | Extension + MIME type validation before copy |
| Symlink attacks | Use realpath() to resolve before access |
| Executable files on USB | Never execute anything from USB; read-only mount |

### 5.2 Mount Security

| Setting | Value | Reason |
|---------|-------|--------|
| Mount options | `ro,noexec,nosuid,nodev` | Read-only, no execution |
| User | Root mounts, www-data reads | Least privilege |
| Filesystem types | vfat, ntfs, exfat, ext4 | Common USB formats only |

### 5.3 PHP Security

- usb-browse.php only reads from `/media/kcr-usb` (validated with realpath)
- usb-import.php applies all existing upload validations (extension, MIME, size)
- No user input is used in shell commands
- All filenames sanitised before filesystem operations
- CSRF tokens on import actions

---

## 6. File Inventory

### 6.1 New Files

| File | Type | Purpose |
|------|------|---------|
| `appliance/usb/99-kcr-usb.rules` | udev rule | Detects USB insert/remove |
| `appliance/usb/kcr-usb-mount.sh` | Shell script | Mounts USB, writes status |
| `appliance/usb/kcr-usb-unmount.sh` | Shell script | Unmounts USB, removes status |
| `usb-status.php` | PHP API | Returns USB mount status |
| `usb-browse.php` | PHP API | Returns directory listing |
| `usb-import.php` | PHP API | Copies files from USB to music/ |
| `usb-eject.php` | PHP API | Safely unmounts USB |
| `css/touch.css` | Stylesheet | Touch-optimised styles |
| `js/usb-browser.js` | JavaScript | File browser UI logic |

### 6.2 Modified Files

| File | Changes |
|------|---------|
| `login.php` | Add USB browser section, idle screen, touch player |
| `css/style.css` | Minor adjustments for new layout |
| `appliance/build-appliance.sh` | Add USB mount configuration to build |

### 6.3 Unchanged Files

| File | Reason |
|------|--------|
| `upload.php` | Still available for non-appliance use |
| `json.php` | Session API unchanged |
| `config.php` | Reused by new PHP files |
| `branding.php` | Unchanged |
| `admin_customize.php` | Unchanged |

---

## 7. Fallback Behaviour

### 7.1 No USB Detected

The system works exactly as it does today. Users can:
- Tap "Return to a previous session" on the idle screen
- Use the existing login → session → upload flow
- Use keyboard and mouse with the traditional file picker

### 7.2 USB With No Audio Files

```
┌────────────────────────────────────────────────────────────────┐
│  USB Drive                                                     │
├────────────────────────────────────────────────────────────────┤
│                                                                │
│                                                                │
│              No audio files found on this drive.               │
│                                                                │
│              Supported formats:                                │
│              MP3, WAV, OGG, FLAC, M4A                         │
│                                                                │
│                                                                │
├────────────────────────────────────────────────────────────────┤
│                              [ ← Back ]                        │
└────────────────────────────────────────────────────────────────┘
```

### 7.3 Non-Appliance Use (Windows/XAMPP Development)

When running on Windows (development), the USB detection will not be active (no udev). The system falls back to the traditional file upload. `usb-status.php` returns `{"mounted": false}` and the idle screen shows the "Return to a previous session" button as the primary action.

---

## 8. Testing Plan

### 8.1 Frontend Testing (on XAMPP/Windows)

| Test | Method |
|------|--------|
| Idle screen renders | Open login.php |
| File browser UI | Mock usb-status.php to return mounted=true |
| Touch targets are 48px+ | Browser developer tools |
| Folder navigation | Mock usb-browse.php responses |
| Selection/deselection | Click/tap testing |
| Player layout | Load with mock track data |

### 8.2 Integration Testing (on Raspberry Pi)

| Test | Method |
|------|--------|
| USB insert detected | Insert USB, observe screen change |
| Folder contents display | Check files match actual USB contents |
| File copy works | Select tracks, verify in music/ directory |
| Player plays copied files | Select and play track |
| USB removal handled | Remove USB, verify return to idle |
| Security validation | Try path traversal in browse API |

---

## 9. Implementation Notes

### 9.1 USB Polling vs WebSocket

**Decision: Polling (2-second interval)**

| Option | Pros | Cons |
|--------|------|------|
| Polling | Simple, works everywhere, no additional server | 2-second delay on detection |
| WebSocket | Instant detection | Requires WebSocket server, more complex |

A 2-second delay is acceptable for USB detection. Users won't notice.

### 9.2 Server-Side Copy vs HTTP Upload

**Decision: Server-side copy for USB, HTTP upload retained as fallback**

| Method | Speed | Limitation |
|--------|-------|------------|
| Server-side copy | ~1 second for 50MB | Only works when USB and server are same machine |
| HTTP upload | ~10 seconds for 50MB | Works over network |

The appliance always has USB and server on the same Pi, so server-side copy is used. The existing HTTP upload remains for network-based access.

### 9.3 Preserving Backward Compatibility

The existing login.php flow is preserved completely. The new USB browser is an **additional entry point** that sits alongside the existing flow. If USB detection fails or is unavailable:
- The system works exactly as it does today
- Keyboard and mouse users can use the traditional flow
- No existing functionality is removed

---

**End of Specification**
