# DS-Tracks v2.0 - Distribution Package

## Overview

DS-Tracks is a secure, self-contained music playback system designed for community radio stations. It allows presenters to safely upload and play their own music from USB drives without risking malware infection of the station's network.

**Perfect for:** Community radio stations, college radio, hospital radio, or any broadcast environment where presenters bring their own music.

---

## Key Features

✅ **Malware Isolation** - USB drives never touch your network
✅ **Easy to Use** - Touchscreen-friendly interface
✅ **Session Persistence** - Saves presenter's tracks for future shows
✅ **Auto-Play Mode** - Queue and play multiple tracks
✅ **Fully Customizable** - Your station's logos and colors
✅ **Security Hardened** - Production-ready with comprehensive security
✅ **Self-Contained** - Runs on affordable Raspberry Pi hardware
✅ **No Database Required** - Simple filesystem-based storage

---

## What's Included in This Package

### Core Application Files
- `*.php` - Secure PHP backend files
- `css/` - Stylesheets
- `js/` - JavaScript files
- `images/` - Default logos and graphics

### Installation & Deployment
- `install-raspberry-pi.sh` - Automated installer for Raspberry Pi
- `security-hardening.sh` - Security configuration script
- `setup.sh` - Quick setup helper

### Customization
- `branding.php` - Branding configuration
- `admin_customize.php` - Web-based customization interface
- `config.php` - Central configuration file

### Documentation
- `QUICK-START.md` - **Start here!** 15-minute setup guide (in `docs/guides/`)
- `DEPLOYMENT-GUIDE.md` - Complete deployment instructions (in `docs/guides/`)
- `SECURITY-UPDATES.md` - Security features and improvements (in `docs/archive/`)
- `CHANGES-SUMMARY.md` - What's new in v2.0 (in `docs/archive/`)
- `KCR-Tracks-User-Manual-D02-2023-03-14.pdf` - End-user manual (in `docs/archive/`)

### Security & Configuration
- `.htaccess` - Apache security configuration
- `.gitignore` - Version control ignore file

---

## Quick Start (15 Minutes)

### 1. Hardware Requirements
- Raspberry Pi 4 (4GB recommended, 2GB minimum)
- 32GB microSD card with Raspberry Pi OS
- Touch screen (7" official screen recommended)
- Network connection

### 2. Installation
```bash
# Copy files to Raspberry Pi
# Run installer
cd DS-Tracks2
chmod +x install-raspberry-pi.sh
sudo ./install-raspberry-pi.sh
```

### 3. Customize
```bash
# Access: http://<raspberry-pi-ip>/admin_customize.php
# Upload your logo
# Set your colors
# Enter station details
```

### 4. Secure (Recommended)
```bash
chmod +x security-hardening.sh
sudo ./security-hardening.sh
```

**Done!** See [QUICK-START.md](../guides/QUICK-START.md) for detailed steps.

---

## Customization for Your Station

### Easy Branding Changes

**No coding required!** Use the web interface:

1. Access: `http://<your-pi-ip>/admin_customize.php`
2. Change admin password first
3. Upload your logos
4. Pick your colors
5. Enter station information
6. Save changes

### What You Can Customize
- ✓ Station name and logo
- ✓ Color scheme (all colors)
- ✓ Website link
- ✓ Custom footer text
- ✓ Favicon

### Logo Requirements
- **Main Logo**: PNG, transparent, ~200x60px
- **Tracks Logo**: PNG, transparent, ~150x50px (optional)
- **Favicon**: ICO format, 32x32px

---

## Security Features

### Built-In Security
- ✅ File type validation (audio only)
- ✅ MIME type checking
- ✅ File size limits (50MB)
- ✅ Path traversal protection
- ✅ XSS protection
- ✅ Input sanitization
- ✅ Comprehensive logging

### Security Hardening Script Adds
- ✅ UFW firewall configuration
- ✅ SSH hardening (port change, key-only auth)
- ✅ Fail2Ban (brute-force protection)
- ✅ Automatic security updates
- ✅ Apache hardening
- ✅ Backup automation
- ✅ Disk monitoring

---

## Technical Specifications

### Software Requirements
- PHP 7.3 or higher (7.4+ recommended)
- Apache 2.4+
- Linux-based OS (Raspberry Pi OS recommended)

### Supported Audio Formats
- MP3 (.mp3)
- WAV (.wav)
- OGG Vorbis (.ogg)
- FLAC (.flac)
- M4A (.m4a)

### Browser Compatibility
- Chrome/Chromium (recommended)
- Firefox
- Safari
- Edge
- Any modern HTML5-compatible browser

### File Size Limits
- Maximum upload: 50MB per file
- Configurable in `config.php`

---

## Use Cases

### Perfect For

**Community Radio Stations**
- Presenters bring music on USB drives
- No network security risk
- Easy for non-technical users

**College/University Radio**
- Student DJs manage their own playlists
- Sessions saved for recurring shows
- Touch-screen friendly for studio use

**Hospital Radio**
- Volunteers can easily play requests
- Isolated from hospital network
- Simple interface for all skill levels

**Podcast Recording Studios**
- Pre-load music beds and intros
- Quick access during recording
- Session-based organization

---

## Distribution & Licensing

### For Radio Stations

**You may:**
- ✓ Install on unlimited stations
- ✓ Customize for your station
- ✓ Modify the code
- ✓ Share with other stations
- ✓ Use commercially

**Please:**
- Keep the "Powered by DS-Tracks" attribution (can be disabled in settings)
- Report bugs and improvements
- Share your customizations with the community

### Credits
- Original concept: Digital Streams Media (DS)
- Version 1.0: Peter Smith, Digital Streams Media
- Version 2.0 Security Hardening: 2025
- User Manual: Peter Smith

---

## Support & Community

### Getting Help

1. **Read the docs first:**
   - QUICK-START.md (in docs/guides/)
   - DEPLOYMENT-GUIDE.md (in docs/guides/)
   - User manual PDF

2. **Check the logs:**
   - `/var/www/html/ds-tracks/logs/`

3. **Common issues:**
   - See DEPLOYMENT-GUIDE.md troubleshooting section (in docs/guides/)

### Reporting Issues

When reporting issues, include:
- DS-Tracks version (2.0)
- Raspberry Pi model
- OS version
- Error messages from logs
- Steps to reproduce

### Contributing

Improvements welcome! Areas for contribution:
- Additional language translations
- UI/UX improvements
- Additional audio format support
- Enhanced admin features
- Documentation improvements

---

## Roadmap (Potential Future Features)

**Community Requested:**
- Multi-language support
- User authentication system
- Advanced playlist management
- Integration with broadcast automation
- Mobile app companion
- Cloud backup integration
- Analytics dashboard

**Your feedback shapes the roadmap!**

---

## Frequently Asked Questions

**Q: Does this work with other formats besides MP3?**
A: Yes! Supports MP3, WAV, OGG, FLAC, and M4A.

**Q: Can I use this without a touch screen?**
A: Yes, works fine with keyboard/mouse. Touch screen is optional.

**Q: How much does the hardware cost?**
A: Complete setup: ~$150-200 USD (Pi 4, screen, case, SD card, power)

**Q: Can multiple presenters use it simultaneously?**
A: System is designed for single-user at a time in studio environment.

**Q: Does it need internet?**
A: No internet required for normal operation. Only for initial setup/updates.

**Q: What about copyright/licensing?**
A: Presenters are responsible for ensuring they have rights to broadcast music. This is just a playback tool.

**Q: Can I integrate with existing automation?**
A: Currently standalone. Plays to audio output, just like a CD player.

**Q: How do I delete old sessions?**
A: See DEPLOYMENT-GUIDE.md (in docs/guides/) for cleanup scripts.

---

## Version History

### v2.0 (2025) - Security Hardened
- Complete security overhaul
- Fixed all critical vulnerabilities
- Added customization system
- Comprehensive deployment tools
- Production-ready

### v1.2 (2023) - Original
- Basic functionality
- Touchscreen interface
- Session management
- Auto-play mode

---

## Quick Links

- [Quick Start Guide](../guides/QUICK-START.md) - 15-minute setup
- [Deployment Guide](../guides/DEPLOYMENT-GUIDE.md) - Complete instructions
- [Security Updates](SECURITY-UPDATES.md) - Security features
- [Changes Summary](CHANGES-SUMMARY.md) - What's new
- [User Manual](KCR-Tracks-User-Manual-D02-2023-03-14.pdf) - For presenters

---

## Thank You

Thank you for choosing DS-Tracks for your radio station. We hope it makes your presenters' lives easier and your broadcasts better!

**Happy Broadcasting!** 📻🎵

---

*DS-Tracks v2.0 - Making community radio better, one track at a time.*
