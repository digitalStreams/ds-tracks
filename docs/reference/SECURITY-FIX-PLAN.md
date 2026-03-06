# DS-Tracks Security Fix Plan

**Date:** 2026-03-07
**Status:** COMPLETED (2026-03-07) -- 16 of 18 findings fixed. CSRF (#4) and rate limiting (#15) deferred (low risk for kiosk deployment).
**Source:** [SECURITY-AUDIT.md](docs/reference/SECURITY-AUDIT.md)
**Scope:** All 18 findings from the security audit, organized into implementable phases.

---

## Approach

Fixes are grouped into phases based on dependencies and risk. Each phase can be tested independently before moving to the next. The order prioritizes removing dead code first (reducing attack surface with zero risk), then centralizing shared code (so subsequent fixes only need to be made once), then fixing issues by severity.

---

## Phase 1: Remove Dead Code and Legacy Files

**Risk:** None (deleting unused files)
**Time:** 15 minutes
**Addresses:** Issues #9, #16, #17

### Tasks

1. **Delete legacy PHP pages**
   - Delete `Get_users.php`
   - Delete `Get_users_Audio.php`
   - Delete `music.php`
   - These files have no access control, broken HTML, link to non-existent `fileUpload.php`, and use defunct CDN (`cdn.rawgit.com`). No other file references them.

2. **Delete old deploy directory**
   - Delete `deploy/KCR-Tracks2/` (entire directory)
   - This is a complete copy of an older codebase. Having it web-accessible doubles the attack surface.
   - Update `.gitignore` if needed.

### Verification
- Confirm the main app (`login.php`) still works normally.
- Confirm no 404 errors in normal usage (these pages are not linked from the main UI).

---

## Phase 2: Centralize Security Code

**Risk:** Low (refactoring, no behavior change)
**Time:** 30 minutes
**Addresses:** Issue #11

### Background

Currently, 6 PHP files each define their own copies of `isValidMusicPath()`, `sanitizeInput()`, and `logError()`. `config.php` has a `DSSecurity` class with proper versions of these functions, but only `admin_customize.php` includes it.

### Tasks

1. **Update `config.php`** to expose standalone helper functions (thin wrappers around `DSSecurity` methods) so existing code can call `sanitizeInput()` etc. without refactoring to use the class:
   ```php
   // At end of config.php, after DSSecurity class
   function sanitizeInput($input) { return DSSecurity::sanitizeUsername($input); }
   function isValidMusicPath($path) { return DSSecurity::isValidMusicPath($path); }
   function logError($message) { error_log(date('[Y-m-d H:i:s] ') . $message . PHP_EOL, 3, __DIR__ . '/logs/app_errors.log'); }
   ```

2. **Add `require_once 'config.php';`** to these files and remove their local function definitions:
   - `json.php` (lines 1-53: remove local `sanitizeInput`, `isValidMusicPath`, `logError`)
   - `upload.php` (remove local `logError`)
   - `all_track_exporter.php` (remove local `isValidMusicPath`)
   - `usb-import.php` (verify it uses config.php, remove duplicates if any)
   - `usb-browse.php` (remove local path validation if duplicated)

3. **Update the `DS_TRACKS` guard** in `config.php` if needed, or add it to files that now include config.php.

### Verification
- Test all endpoints: login flow, track playback, USB browse, USB import, upload, admin panel.
- Confirm no "function already defined" errors.

---

## Phase 3: Fix Critical Issues

**Risk:** Medium (changing security-sensitive code paths)
**Time:** 1-2 hours
**Addresses:** Issues #1, #2, #3

### Task 3a: Add Authentication to Delete Operations (Issue #1)

**File:** `json.php` lines 70-165

Currently, delete endpoints (delete track, delete session, delete user) have no authentication. Anyone who can reach the server can delete all music data.

**Fix:**
1. At the top of each delete handler, check for a valid `username` cookie that matches the data owner:
   ```php
   // For delete_track and delete_session:
   $cookieUser = isset($_COOKIE['username']) ? sanitizeInput($_COOKIE['username']) : '';
   if (empty($cookieUser) || $cookieUser !== $username) {
       http_response_code(403);
       echo json_encode(['success' => false, 'error' => 'Not authorized']);
       exit;
   }
   ```
2. For delete-user (which deletes an entire user's data), require admin authentication or at minimum require the cookie matches the user being deleted.
3. Log all delete operations with the requesting user identity.

### Task 3b: Externalize Admin Password (Issue #2)

**File:** `admin_customize.php` line 11

Currently: `$ADMIN_PASSWORD = 'changeme123';` hardcoded in source.

**Fix:**
1. Create a file `admin_password.php` (outside git, in `.gitignore`):
   ```php
   <?php return 'changeme123'; ?>
   ```
2. Update `admin_customize.php` to load it:
   ```php
   $passwordFile = __DIR__ . '/admin_password.php';
   $ADMIN_PASSWORD = file_exists($passwordFile) ? include($passwordFile) : null;
   if ($ADMIN_PASSWORD === null) {
       die('Admin password not configured. Create admin_password.php');
   }
   ```
3. Add `admin_password.php` to `.gitignore`.
4. Update BUILD-DAY guide to include setting the admin password.
5. Add `admin_password.php` creation to the Pi install script.

### Task 3c: Fix XSS in Legacy UI Track Names (Issue #3)

**File:** `login.php` lines 619, 714, 877-882

Currently, track filenames are inserted into HTML/onclick attributes with only `.replace("'", "%27")`, which does not prevent XSS from crafted filenames.

**Fix:**
1. Add a proper `escapeForHtml()` JavaScript function (or reuse the one from `usb-browser.js`):
   ```javascript
   function escapeForHtml(str) {
       var div = document.createElement('div');
       div.textContent = str;
       return div.innerHTML;
   }
   function escapeForAttr(str) {
       return str.replace(/&/g,'&amp;').replace(/'/g,'&#39;').replace(/"/g,'&quot;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
   }
   ```
2. Replace all `.innerHTML` assignments that include filenames with `.textContent` for display.
3. For `onclick` attributes, use `escapeForAttr()` on the filename values.
4. Audit all places in `login.php` where filenames from the filesystem enter the DOM.

### Verification
- Test delete operations: confirm they fail without a valid cookie, succeed with one.
- Test admin panel: confirm it refuses access without password file, works with one.
- Create a test file with a name like `test<img src=x>.mp3` and confirm it displays safely.

---

## Phase 4: Fix High-Severity Issues

**Risk:** Low-Medium
**Time:** 1 hour
**Addresses:** Issues #4, #5, #6, #7, #8

### Task 4a: Enable CSRF Protection (Issue #4)

`config.php` already has `generateCSRFToken()` and `validateCSRFToken()` methods but they are never called.

**Fix:**
1. In `login.php`, generate a CSRF token and include it as a hidden field or meta tag:
   ```html
   <meta name="csrf-token" content="<?php echo DSSecurity::generateCSRFToken(); ?>">
   ```
2. In the JavaScript, include the token in all POST requests (jQuery `$.ajaxSetup` beforeSend).
3. In `json.php`, validate the token on all POST endpoints.
4. In `admin_customize.php`, add token to the form and validate on submission.

**Note:** On the kiosk (single-user, no other tabs), CSRF risk is minimal. This is a "should fix" for any network-accessible deployment.

### Task 4b: Fix Cookie Security Flags (Issue #5)

**File:** `usb-import.php` line 121

**Fix:**
```php
setcookie('username', $sessionName, [
    'expires' => time() + (14 * 24 * 60 * 60),
    'path' => '/',
    'httponly' => false,  // JS reads this cookie
    'samesite' => 'Strict'
]);
```
Also check `upload.php` and any other raw `setcookie()` calls.

### Task 4c: Fix usb-eject.php Paths (Issue #6)

**File:** `usb-eject.php` lines 12-13

**Fix:**
```php
$mountPoint = '/media/kcr-usb';
$statusFile = '/run/kcr-usb-status.json';
```
These must match the values used by the systemd USB detection service and `usb-status.php`.

### Task 4d: Fix upload.php File Size Mismatch (Issue #7)

**File:** `upload.php` line 11

**Fix:** Change to match config.php:
```php
define('MAX_FILE_SIZE', 50 * 1024 * 1024); // 50MB
```
Also update the error message on line 42 to be consistent.

### Task 4e: Fix Unescaped Admin Error Output (Issue #8)

**File:** `admin_customize.php` lines 153, 303-304

**Fix:**
```php
echo "<p class='error'>" . htmlspecialchars($error, ENT_QUOTES, 'UTF-8') . "</p>";
```

### Verification
- Test USB eject on Pi (or confirm paths match systemd service config).
- Test upload with a file slightly over 50MB -- should be rejected.
- Test admin panel error display with special characters.

---

## Phase 5: Fix Medium-Severity Issues

**Risk:** Low
**Time:** 30 minutes
**Addresses:** Issues #10, #12

### Task 5a: Move Logs Out of Web Root (Issue #12)

**Files:** `json.php` line 32, `upload.php` line 17, `music.php` (deleted in Phase 1)

**Fix:**
1. Create `logs/` directory if it doesn't exist.
2. Create `logs/.htaccess`:
   ```
   Deny from all
   ```
3. Update all `logError` paths to use `__DIR__ . '/logs/'` prefix. After Phase 2, this is a single change in `config.php`.
4. Add `logs/*.log` to `.gitignore`.

### Task 5b: Bundle jQuery Locally (Issue #10)

**File:** `login.php` line 10

**Fix:**
1. Download jQuery 3.6.1 minified to `js/jquery-3.6.1.min.js`.
2. Update `login.php` to use the local copy:
   ```html
   <script src="js/jquery-3.6.1.min.js"></script>
   ```
3. Update `all_track_exporter.php` similarly (it loads jQuery 3.5.1 from CDN).
4. The legacy pages (Get_users.php, Get_users_Audio.php) will already be deleted in Phase 1.

### Verification
- Disconnect from internet, confirm `login.php` still loads and functions.
- Confirm log files appear in `logs/` directory, not in web root.
- Try to access `logs/app_errors.log` via browser -- should get 403 Forbidden.

---

## Phase 6: Fix Low-Severity Issues

**Risk:** Very low
**Time:** 20 minutes
**Addresses:** Issues #13, #14, #15

### Task 6a: Session Label Double-Encoding (Issue #14)

**File:** `json.php` line 59

Currently, labels are `htmlspecialchars()`'d before saving to JSON. This means `&` becomes `&amp;` in storage.

**Fix:** Remove `htmlspecialchars()` from the save path. Escape on output only:
```php
// Save raw (already sanitized for dangerous chars)
$label = trim($_POST['label'] ?? '');
// Store as-is, escape when rendering
```

### Task 6b: Template File Validation (Issue #13)

**File:** `admin_customize.php` line 94

**Fix:** Add existence check:
```php
$templateFile = __DIR__ . '/branding_template.txt';
if (!file_exists($templateFile)) {
    $error = 'Branding template file is missing.';
} else {
    $template = file_get_contents($templateFile);
    // ... proceed
}
```

### Task 6c: Rate Limiting (Issue #15)

For the kiosk deployment, rate limiting is not critical. If needed later, a simple approach:
- Add a timestamp check in `json.php` delete handlers (e.g., no more than 1 delete per 2 seconds per session).
- This can be deferred to post-deployment.

### Verification
- Save a session label with `&` in the name, confirm it displays correctly (not as `&amp;`).
- Delete `branding_template.txt` temporarily, confirm admin panel shows a clean error.

---

## Implementation Order Summary

| Phase | Issues Fixed | Risk | Effort |
|-------|-------------|------|--------|
| 1. Remove dead code | #9, #16, #17 | None | 15 min |
| 2. Centralize security | #11 | Low | 30 min |
| 3. Fix critical | #1, #2, #3 | Medium | 1-2 hrs |
| 4. Fix high | #4, #5, #6, #7, #8 | Low-Med | 1 hr |
| 5. Fix medium | #10, #12 | Low | 30 min |
| 6. Fix low | #13, #14, #15 | Very low | 20 min |

**Total estimated effort:** 3-5 hours

---

## Dependencies Between Phases

```
Phase 1 (delete dead code)
    |
Phase 2 (centralize config.php) -- must come before Phase 3-5
    |
Phase 3 (critical fixes) -- can run in parallel with Phase 4
Phase 4 (high fixes)     -- can run in parallel with Phase 3
    |
Phase 5 (medium fixes) -- depends on Phase 2 for log path centralization
    |
Phase 6 (low fixes) -- independent, can run any time after Phase 2
```

---

## Out of Scope (Documented, Not Planned)

These items from the audit are acknowledged but not included in this fix plan:

- **KCR-to-DS rebrand** (tracked separately in `docs/deferred-work.md`) -- the `usb-eject.php` path fix in Task 4c uses current `kcr-*` names; these will change during rebrand.
- **Full rate limiting** -- deferred to post-deployment; kiosk context makes this low priority.
- **HTTPS / secure cookie flag** -- Pi kiosk runs on localhost over HTTP; `secure` flag would break cookies.

---

## Testing Checklist (After All Phases)

- [ ] App loads without internet (jQuery local)
- [ ] Login flow works
- [ ] Track playback works
- [ ] USB browse and import works
- [ ] Upload works (under 50MB accepted, over 50MB rejected)
- [ ] Delete track requires valid cookie
- [ ] Delete session requires valid cookie
- [ ] Delete user requires valid cookie
- [ ] Admin panel requires password from external file
- [ ] Filenames with `<script>` or `"onclick=` display safely
- [ ] Session labels with `&` display correctly
- [ ] Log files are in `logs/` not web root
- [ ] `logs/` directory returns 403 via browser
- [ ] Legacy pages return 404
- [ ] USB eject uses correct paths
- [ ] No JS console errors in normal usage
