# DS-Tracks - Changelog

All notable changes to DS-Tracks are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).

---

## Unreleased

### SECURITY
- Centralized security functions: all PHP files now use config.php (removed 6 local duplicates)
- Added authentication check on delete operations (track, session, user) via cookie validation
- Externalized admin password to admin_password.php (gitignored, not in source control)
- Fixed XSS in legacy UI: track/user names now properly escaped with escapeHtml()/escapeAttr()
- Fixed usb-eject.php wrong paths (/media/ds-usb -> /media/kcr-usb, /tmp/ -> /run/)
- Fixed upload.php file size mismatch (was 512MB, now 50MB matching config.php)
- Fixed unescaped error output in admin panel (now uses htmlspecialchars())
- Fixed session label double-encoding (removed htmlspecialchars() on save, escape on output only)
- Added branding_template.txt existence check before saving branding config
- Fixed usb-import.php cookie: added SameSite=Strict flag
- Bundled jQuery 3.6.1 locally (was CDN-only, broke offline kiosk)
- Protected logs/ directory with .htaccess deny rule
- Deleted legacy unsecured pages: Get_users.php, Get_users_Audio.php, music.php
- Deleted deploy/KCR-Tracks2/ (old codebase copy doubling attack surface)
- Added config.php direct-access guard using SCRIPT_FILENAME check

### FEAT
- USB music export to USB drive (admin-only, via admin panel)
  - Copies all session folders with audio files to DS-Tracks-Export/ on USB
  - Generates session-info.json manifest with metadata (username, date, time, label, tracks)
  - Progress bar UI in admin panel
- On-screen keyboard for Pi kiosk touchscreen (no physical keyboard needed)
  - 4 layouts: lowercase, uppercase, numbers, symbols
  - Auto-shows on text input focus, compact white/corporate theme
  - Positioned inside #content container, overlays bottom of screen
- Delete functionality at three levels: individual tracks, sessions, and users
- All deletes physically remove files from disk to free space
- Auto-cleanup of empty session directories after last track deleted
- Session labels cleaned up on session/user deletion

### FIX
- Fixed user dropdown not showing newly created users (existingUsers array not resetting)
- Fixed Add Tracks creating new sessions instead of adding to existing ones
- Fixed wrong screen shown after USB import (now returns to player view)
- Fixed USB browser screen not hiding after import in legacy mode
- Fixed session label not saving on creation (usb-import.php now uses passed session name)
- Fixed browseAndImport() missing if-guard causing JS syntax error
- Fixed USB browser auto-opening on page load (now only on new USB insertion)

### UX
- Delete icons on track rows, session rows, and user rows with confirmation dialogs
- After adding tracks via USB, player view opens showing imported tracks
- Redesigned session display: label primary, date compact (DD/MM/YY-HH:MM) on same line
- Removed debug console.logs, added favicon

---

## 2026-03-08

### FIX
- Fixed track playback: filenames with commas broke playlist (comma-separated encoding replaced with JSON)
- Fixed track playback: filenames with apostrophes broke HTML attribute quoting (now JSON-encoded)
- Fixed autoplay stalling: added `onerror` handler to skip unplayable tracks instead of stopping
- Fixed single-play track name display: now uses `decodeURIComponent()` instead of manual `%27` replace
- Fixed layout offset caused by removing CSS borders (teal borders preserve box model)

### FEAT
- USB import filename sanitisation: MIME-type detection for extensionless files, special character removal
- Screen blanking: display powers off after 5 minutes of idle (wakes on touch)
- Plymouth boot splash: DS-Tracks branded image shown during boot (replaces scrolling Linux text)

### UX
- App viewport expanded from 795x447 to 800x480 (fills Pi 7" touchscreen completely)
- Borders changed from visible grey/white to teal (invisible, matches background)
- Boot sequence streamlined: removed unnecessary 30-second network wait loop

### DOCS
- Updated PI-DEPLOYMENT-KNOWLEDGE.md with screen blanking, Plymouth, and boot sequence details

---

## 2026-03-05

### FEAT
- Added `browseAndImport()` function for legacy UI to trigger USB file browser
- Added `legacySessionFolder` support to pass existing session through import flow
- Added `loadSessionIntoPlayer()` to show tracks after import
- USB import (`usb-import.php`) now supports adding files to existing sessions via `session` parameter

### FIX
- Fixed `browseAndImport is not defined` JS error (function body was missing)
- Fixed `station-logo.png` 404 (file existed locally but was never deployed to Pi)
- Fixed `showScreen()` coordination between touch and legacy UI systems

### DOCS
- Created `start-here.md` agent initiation document
- Updated `deferred-work.md` with rebrand scope and implementation details

---

## 2026-03-04

### FEAT
- Touch interface: USB file browser with folder navigation
- USB polling, mount detection, file selection UI
- Touch player with session management
- Dual UI system: touch (kiosk) and legacy (browser)

### FIX
- Replaced native file dialogs with USB browser for Pi kiosk (no file system access)
- Fixed new user login flow in touch interface

### DOCS
- Reorganized project structure: docs and scripts into subfolders
- Added build day guide, deployment KB, architecture docs

---

## 2026-03-03

### FEAT
- Configurable music storage: SD card vs USB SSD toggle in admin UI
- Separate music drive support (OS on SD card, music on USB SSD)

---

## Legend

- **FEAT**: New features and additions
- **FIX**: Bug fixes and corrections
- **REFACTOR**: Code reorganization (no functional changes)
- **DOCS**: Documentation updates
- **UX**: User experience improvements
- **SECURITY**: Security-related fixes
