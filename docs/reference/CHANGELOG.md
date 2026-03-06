# DS-Tracks - Changelog

All notable changes to DS-Tracks are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).

---

## Unreleased

### FEAT
- Delete functionality at three levels: individual tracks, sessions, and users
- All deletes physically remove files from disk to free space
- Auto-cleanup of empty session directories after last track deleted
- Session labels cleaned up on session/user deletion

### FIX
- Fixed user dropdown not showing newly created users (existingUsers array not resetting)
- Fixed Add Tracks creating new sessions instead of adding to existing ones
- Fixed wrong screen shown after USB import (now returns to player view)
- Fixed USB browser screen not hiding after import in legacy mode

### UX
- Delete icons on track rows, session rows, and user rows with confirmation dialogs
- After adding tracks via USB, player view opens showing imported tracks

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
