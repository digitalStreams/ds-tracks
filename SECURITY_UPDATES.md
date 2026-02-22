# KCR Tracks - Security Updates v2.0

## Overview
This document outlines the security improvements made to KCR Tracks to address critical vulnerabilities.

## Security Improvements Implemented

### 1. File Upload Security (upload.php)
**Previous Issues:**
- No file type validation
- No file size limits
- Direct use of user-controlled filenames
- Directory traversal vulnerabilities
- Cookie-based authentication easily bypassed

**Fixes Applied:**
- ✅ File extension whitelist (mp3, wav, ogg, flac, m4a only)
- ✅ MIME type validation using PHP's fileinfo
- ✅ 50MB file size limit
- ✅ Filename sanitization - removes dangerous characters
- ✅ Path traversal prevention using realpath validation
- ✅ Automatic duplicate file renaming
- ✅ Comprehensive error logging
- ✅ Proper file permissions (0644)

### 2. Input Validation & Path Traversal (json.php, music.php, Get_users.php, etc.)
**Previous Issues:**
- User input directly used in scandir() and glob()
- No validation of POST parameters
- Attackers could access arbitrary filesystem locations

**Fixes Applied:**
- ✅ All user input sanitized using regex
- ✅ Path validation ensures all paths are within music directory
- ✅ Use of basename() to prevent directory traversal
- ✅ Validation of directory existence before access

### 3. Cross-Site Scripting (XSS) Protection
**Previous Issues:**
- Filenames and usernames echoed directly into HTML
- Could inject malicious JavaScript

**Fixes Applied:**
- ✅ All output escaped using htmlspecialchars() with ENT_QUOTES
- ✅ Proper UTF-8 encoding specified
- ✅ URL encoding for file paths

### 4. Session Management
**Previous Issues:**
- Using cookies instead of PHP sessions
- No CSRF protection
- No secure/httponly flags
- Long expiration time

**Fixes Applied:**
- ✅ Centralized configuration file (config.php)
- ✅ Session configuration hardening
- ✅ CSRF token generation and validation functions
- ✅ HttpOnly and SameSite cookie flags

### 5. Error Handling & Logging
**Previous Issues:**
- No error handling for file operations
- Silent failures
- No security event logging
- Production URLs hardcoded

**Fixes Applied:**
- ✅ Comprehensive error logging to dedicated log files
- ✅ Separate log directory (/logs/)
- ✅ Error messages don't expose system information
- ✅ Info logging for successful uploads and operations
- ✅ Removed hardcoded localhost URLs

### 6. Code Quality Improvements
**Fixes Applied:**
- ✅ Centralized security functions in config.php
- ✅ Consistent input sanitization across all files
- ✅ Better code documentation
- ✅ Separation of concerns
- ✅ Reusable security helper functions

### 7. Apache Security (.htaccess)
**New Features:**
- ✅ Prevents directory listing
- ✅ Protects log files and configuration
- ✅ Adds security headers (X-Frame-Options, X-Content-Type-Options)
- ✅ Proper MIME types for audio files
- ✅ File compression and caching for performance

## Installation & Update Instructions

### First-Time Installation
1. Copy all files to your web server directory
2. Ensure Apache has write permissions to:
   - `/music/` directory (for uploads)
   - `/logs/` directory (will be created automatically)
3. Verify `.htaccess` is enabled (requires `AllowOverride All`)
4. If using HTTPS, update `config.php`: set `session.cookie_secure` to 1

### Updating from v1.x
1. **BACKUP YOUR DATA** - Copy the entire `/music/` directory
2. Replace all PHP files with updated versions
3. Add new files:
   - `config.php`
   - `.htaccess`
   - `SECURITY_UPDATES.md` (this file)
4. Create `/logs/` directory and set permissions to 755
5. Test file uploads and session management

### Post-Installation Checklist
- [ ] Verify `/logs/` directory was created
- [ ] Test file upload functionality
- [ ] Verify old sessions still load
- [ ] Check that log files are being created
- [ ] Ensure `.htaccess` is working (directory listing should be disabled)
- [ ] Review `logs/app_errors.log` for any issues

## File Permissions
Set appropriate permissions on your Raspberry Pi:

```bash
# Set directory permissions
chmod 755 /path/to/KCR-Tracks2
chmod 755 /path/to/KCR-Tracks2/music
chmod 755 /path/to/KCR-Tracks2/logs

# Set file permissions
chmod 644 /path/to/KCR-Tracks2/*.php
chmod 644 /path/to/KCR-Tracks2/.htaccess
chmod 644 /path/to/KCR-Tracks2/*.css
chmod 644 /path/to/KCR-Tracks2/*.js

# Ensure web server can write to these directories
chown -R www-data:www-data /path/to/KCR-Tracks2/music
chown -R www-data:www-data /path/to/KCR-Tracks2/logs
```

## Security Best Practices

### Ongoing Maintenance
1. **Review Logs Regularly**
   - Check `logs/app_errors.log` for security issues
   - Monitor `logs/upload_errors.log` for upload problems
   - Review `logs/api_errors.log` for API issues

2. **Disk Space Management**
   - Monitor `/music/` directory size
   - Consider implementing automatic cleanup of old sessions
   - Set up alerts for low disk space

3. **Keep Software Updated**
   - Update PHP to the latest version
   - Keep Apache web server updated
   - Monitor Raspberry Pi OS for security updates

4. **Access Control**
   - Consider adding HTTP Basic Authentication for admin pages
   - Restrict network access to trusted IPs if possible
   - Change default passwords if any were set

### Additional Security Recommendations
1. **Enable HTTPS** - Obtain a free SSL certificate (Let's Encrypt)
2. **Firewall** - Configure UFW or iptables to restrict access
3. **Backups** - Regularly backup the `/music/` directory
4. **Updates** - Keep this document updated with any changes

## Log Files Reference

| Log File | Purpose |
|----------|---------|
| `logs/app_errors.log` | General application errors |
| `logs/app_info.log` | Informational messages |
| `logs/upload_errors.log` | File upload issues |
| `logs/api_errors.log` | JSON API errors |
| `logs/music_errors.log` | Music playback issues |
| `logs/php_errors.log` | PHP runtime errors |

## Known Limitations

1. **No User Authentication** - Currently relies on username entry only
2. **No Session Deletion** - Old sessions accumulate (manual cleanup required)
3. **No Upload Progress** - Large files don't show upload progress
4. **Limited File Types** - Only supports common audio formats

## Future Enhancements (Recommended)

1. Implement proper user authentication system
2. Add automatic session cleanup (e.g., delete sessions older than 30 days)
3. Add upload progress indicator for large files
4. Implement session deletion from UI
5. Add admin dashboard for system management
6. Implement rate limiting on uploads
7. Add duplicate detection for uploaded files

## Support & Issues

If you encounter any issues:
1. Check the relevant log file in `/logs/`
2. Verify file permissions are correct
3. Ensure Apache has write access to music and logs directories
4. Review this document for configuration steps

## Version History

**v2.0** (Current)
- Complete security overhaul
- Fixed critical vulnerabilities
- Added centralized configuration
- Improved error handling and logging

**v1.2** (Original)
- Basic functionality
- Multiple security vulnerabilities
- No input validation

---

**Important:** This security update addresses critical vulnerabilities. Update immediately if running v1.x in production.
