# DS-Tracks Security & Deployment Readiness Audit

**Date:** 2026-03-07
**Scope:** Full codebase review of all PHP endpoints, JavaScript, and configuration files.
**Purpose:** Identify security risks, application bugs, and deployment blockers.

---

## CRITICAL -- Must Fix Before Deployment

### 1. Unauthenticated Delete Operations

**File:** `json.php` lines 70-165

The delete endpoints (track, session, user) have **no authentication whatsoever**. Anyone who can reach the server can delete all music data:

```
POST /json.php  ->  delete_action=user&username=Peter  ->  all of Peter's music deleted
```

No session check, no admin check, no CSRF token. This is the single highest-risk issue -- a single malicious or accidental request destroys data.

**Fix:** At minimum, require a valid session cookie matching the data owner. Delete-user should require admin auth.

---

### 2. Hardcoded Admin Password in Source Code

**File:** `admin_customize.php` line 11

```php
$ADMIN_PASSWORD = 'changeme123';
```

This is in git, visible to anyone with code access, and is the default everyone will deploy with. On the Pi kiosk it is less critical (no external network), but if the Pi is ever on a network, the admin panel is wide open.

**Fix:** Move to an environment variable or a local config file outside the repo. At minimum, the BUILD-DAY guide should mandate changing it.

---

### 3. XSS in Track/User Names in Legacy UI

**File:** `login.php` lines 619, 714, 877-882

Track filenames from the filesystem are inserted directly into HTML/onclick attributes with only a single-quote replacement (`replace("'", "%27")`). A malicious filename like `"><img src=x onerror=alert(1)>.mp3` would execute JavaScript.

In a kiosk context the risk is lower (files come from the user's own USB), but any shared-use station could be exploited by a user putting crafted filenames on a USB stick.

**Fix:** Use proper HTML escaping for all data attributes and `textContent` for display names instead of `.innerHTML`.

---

## HIGH -- Should Fix Before Deployment

### 4. No CSRF Protection on Any Form

**Files:** `json.php`, `admin_customize.php` line 306

Config.php has `generateCSRFToken()` and `validateCSRFToken()` methods defined but **they are never called anywhere**. No form includes a CSRF token. Every POST endpoint is vulnerable to cross-site request forgery.

On a kiosk this is low-risk (no other sites open). On any network-accessible instance, it is high-risk.

---

### 5. Cookie Set Without Security Flags

**File:** `usb-import.php` line 121

```php
setcookie('username', $sessionName, time() + (14 * 24 * 60 * 60), '/');
```

Missing `httponly`, `samesite`, and `secure` flags. Config.php sets these for PHP session cookies but this raw `setcookie()` call bypasses those settings. The cookie is readable by JavaScript (which the app actually relies on via `js.cookie`), but `samesite` should still be set.

---

### 6. usb-eject.php Uses Wrong Status File Path

**File:** `usb-eject.php` lines 12-13

```php
$mountPoint = '/media/ds-usb';     // Should be /media/kcr-usb
$statusFile = '/tmp/ds-usb-status.json';  // Should be /run/kcr-usb-status.json
```

This uses `/tmp/` but the rest of the system uses `/run/kcr-usb-status.json`. Apache's PrivateTmp means this will never see the real status file. Also uses `/media/ds-usb` instead of `/media/kcr-usb`. **This file is non-functional on the Pi.**

---

### 7. upload.php MAX_FILE_SIZE Mismatch

**File:** `upload.php` line 11

```php
define('MAX_FILE_SIZE', 512 * 1024 * 1024); // 512MB
```

The actual limit is 512MB but the error message on line 42 says "50MB". Config.php defines it as 50MB. This inconsistency means the upload handler accepts files 10x larger than intended.

---

### 8. Unescaped Error Output in Admin

**File:** `admin_customize.php` lines 153, 303-304

```php
echo "<p class='error'>$error</p>";
```

The `$error` variable is echoed without `htmlspecialchars()`. If it contains user-influenced data (e.g. from a form submission failure), XSS is possible.

---

## MEDIUM -- Should Address

### 9. Legacy Pages Completely Unsecured

**Files:** `Get_users.php`, `Get_users_Audio.php`, `music.php`

These legacy files have no access control, no proper HTML structure (`<body>` appears mid-page), load outdated jQuery versions (2.1.1), use `cdn.rawgit.com` (deprecated/defunct), and link to `fileUpload.php` which does not exist. They appear to be leftover development/debug pages.

**Recommendation:** Delete them or restrict access. They expose the full user/music directory listing to anyone.

---

### 10. CDN Dependencies Require Internet

**Files:** `login.php` line 10, `all_track_exporter.php` lines 1-19, `Get_users.php`

jQuery is loaded from `cdnjs.cloudflare.com`. The Pi kiosk is often offline. If the CDN is unreachable, the entire UI breaks. The exporter pages load 8+ external CDN scripts.

**Fix:** Bundle all JS/CSS dependencies locally (jQuery is already local as `js/jquery-ui-custom.min.js` for jQuery UI).

---

### 11. json.php Does Not Use config.php

**File:** `json.php` lines 1-53

Duplicates all security functions (`sanitizeInput`, `isValidMusicPath`, `logError`) instead of requiring `config.php`. This means any security improvements to config.php do not propagate to the API.

---

### 12. Error Logs Written to Web Root

**Files:**
- `json.php` line 32: `__DIR__ . '/api_errors.log'`
- `upload.php` line 17: `__DIR__ . '/upload_errors.log'`

Log files are in the web-accessible root directory. On the Pi's Apache config these may be downloadable, exposing internal paths, usernames, and error details.

**Fix:** Write all logs to the `logs/` subdirectory and add a `.htaccess` deny rule.

---

## LOW -- Nice to Fix

### 13. branding_template.txt File Inclusion

**File:** `admin_customize.php` line 94

`file_get_contents('branding_template.txt')` -- if this file is missing or tampered with, the generated `branding.php` will be malformed and could break the site.

---

### 14. Session Label HTML-encoded Before Storage

**File:** `json.php` line 59

Labels are `htmlspecialchars()`'d before saving to JSON. This means the stored data contains `&amp;` etc., and if displayed without double-encoding awareness, shows escaped HTML entities to users.

---

### 15. No Rate Limiting

No endpoint has rate limiting. USB polling hits `usb-status.php` every 2 seconds by design, but delete operations could be hammered.

---

## OPERATIONAL / DEPLOYMENT ISSUES

### 16. Dead Code / Legacy Files

These files appear unused by the main application and should be removed or `.htaccess`-protected:

| File | Issue |
|------|-------|
| `Get_users.php` | Links to non-existent `fileUpload.php` |
| `Get_users_Audio.php` | Empty table, broken references |
| `music.php` | Uses `$_REQUEST` (accepts GET), accessible to anyone |
| `deploy/KCR-Tracks2/` | Entire copy of old codebase in the deploy directory |

---

### 17. deploy/ Directory Contains Full App Copy

`deploy/KCR-Tracks2/` contains a complete (older) copy of all PHP files. If this gets deployed to the Pi, both versions are web-accessible, doubling the attack surface. This directory should be gitignored or deleted.

---

### 18. jQuery Loaded from CDN

**File:** `login.php` line 10

The main entry point loads jQuery from a CDN. For a kiosk that may not have internet, this is a reliability risk. The app includes local jQuery UI but not base jQuery.

---

## Summary Table

| Severity | Count | Key Issues |
|----------|-------|------------|
| **Critical** | 3 | Unauthenticated deletes, hardcoded password, XSS in filenames |
| **High** | 5 | No CSRF anywhere, cookie flags, broken eject, file size mismatch, unescaped admin errors |
| **Medium** | 4 | Legacy pages exposed, CDN dependency, duplicated security code, logs in web root |
| **Low** | 3 | Template inclusion, double-encoding, no rate limiting |
| **Operational** | 3 | Dead code, deploy directory, offline jQuery |

---

## Recommended Fix Order

1. Add auth check to delete operations in `json.php`
2. Fix the XSS in `login.php` track/user name rendering
3. Delete or restrict legacy pages (`Get_users.php`, `Get_users_Audio.php`, `music.php`)
4. Fix `usb-eject.php` paths (or remove if not used)
5. Move logs out of web root
6. Bundle jQuery locally
7. Remove `deploy/KCR-Tracks2/` directory

---

## What Is Already Done Well

For completeness, the following security measures are already properly implemented:

| Area | Status |
|------|--------|
| Path traversal protection | `realpath()` + boundary check in all file endpoints |
| Command injection | `escapeshellarg()` used in `admin_customize.php` and `usb-eject.php` |
| Direct file inclusion | `DS_TRACKS` constant guard in `config.php` |
| Error display to users | `display_errors = 0` in `config.php` |
| Filename sanitisation | Proper regex + extension validation in `upload.php` and `usb-import.php` |
| MIME type validation | `finfo` checks on upload and import |
| File size limits | Enforced in `upload.php` and `usb-import.php` |
| USB browse path security | `realpath()` + mount point boundary check in `usb-browse.php` |
| HTML output escaping | `htmlspecialchars()` used consistently in `all_track_exporter.php`, `music.php`, `json.php` by_users |
| Touch UI (usb-browser.js) | Uses `escapeHtml()` and `escapeAttr()` helper functions |
