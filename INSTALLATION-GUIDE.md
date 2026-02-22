# KCR Tracks Appliance

## Installation Guide

**Version 2.0**

---

## Welcome

Thank you for downloading KCR Tracks - the safe, simple music playback system for community radio stations.

This guide will help you create a ready-to-use KCR Tracks appliance in about 15 minutes.

**No Linux knowledge required.**

---

## Contents

1. [What You Need](#1-what-you-need)
2. [Download the Software](#2-download-the-software)
3. [Write the Image to Your SSD](#3-write-the-image-to-your-ssd)
4. [Pre-Configure Your Station (Optional)](#4-pre-configure-your-station-optional)
5. [Assemble and Power On](#5-assemble-and-power-on)
6. [First Boot](#6-first-boot)
7. [Using KCR Tracks](#7-using-kcr-tracks)
8. [Customising Your Station](#8-customising-your-station)
9. [Troubleshooting](#9-troubleshooting)
10. [Getting Help](#10-getting-help)

---

## 1. What You Need

### Essential Hardware

| Item | Notes | Approx. Cost |
|------|-------|--------------|
| **Raspberry Pi 4** (4GB or 8GB) | The Pi 5 also works | $90 AUD |
| **USB SSD** (128GB or larger) | Any USB 3.0 SSD works | $40-80 AUD |
| **USB-C Power Supply** (15W) | Official Pi supply recommended | $25 AUD |
| **Display** | Any HDMI monitor or TV | - |
| **HDMI Cable** | Micro-HDMI to HDMI for Pi 4 | $10 AUD |

### Recommended Additions

| Item | Notes | Approx. Cost |
|------|-------|--------------|
| **Official 7" Touchscreen** | Perfect for studio use | $120 AUD |
| **Case with Cooling** | Keeps the Pi cool | $20-40 AUD |
| **USB Audio Interface** | Better audio quality than 3.5mm | $30 AUD |

### For Installation (One-Time)

| Item | Notes |
|------|-------|
| **Computer** | Windows, Mac, or Linux |
| **USB to SATA Adapter** | To connect SSD to your computer (often included with SSD) |
| **Internet Connection** | To download the software |

### Where to Buy (Australia)

- [Core Electronics](https://core-electronics.com.au/) - Raspberry Pi specialist
- [PiAustralia](https://piaustralia.com.au/) - Official Pi reseller
- [Amazon Australia](https://amazon.com.au/) - General hardware

---

## 2. Download the Software

### Step 2.1: Download Raspberry Pi Imager

The Raspberry Pi Imager is a free tool that writes the KCR Tracks system to your SSD.

1. Go to: **https://www.raspberrypi.com/software/**
2. Click the download button for your operating system (Windows/Mac/Linux)
3. Install the application

<!-- IMAGE: Screenshot of Raspberry Pi website download page -->

### Step 2.2: Download KCR Tracks Image

Download the KCR Tracks appliance image from where you registered:

| File | Size | Description |
|------|------|-------------|
| `KCR-Tracks-v2.0.img.xz` | ~1 GB | The appliance image (compressed) |

Save this file somewhere easy to find, like your Downloads folder.

**Note:** You don't need to extract/unzip the file. Raspberry Pi Imager handles this automatically.

### Step 2.3: Verify Your Download (Optional but Recommended)

To ensure your download wasn't corrupted:

**Windows:**
1. Open Command Prompt
2. Type: `certutil -hashfile Downloads\KCR-Tracks-v2.0.img.xz SHA256`
3. Compare the result with the checksum on the download page

**Mac/Linux:**
1. Open Terminal
2. Type: `sha256sum ~/Downloads/KCR-Tracks-v2.0.img.xz`
3. Compare the result with the checksum on the download page

---

## 3. Write the Image to Your SSD

### Step 3.1: Connect Your SSD

1. Connect your SSD to your computer using a USB adapter
2. **Important:** If the SSD has existing data, it will be erased. Back up anything important first.

<!-- IMAGE: Photo of SSD connected via USB adapter -->

### Step 3.2: Open Raspberry Pi Imager

1. Launch Raspberry Pi Imager
2. You'll see three buttons: **CHOOSE DEVICE**, **CHOOSE OS**, **CHOOSE STORAGE**

<!-- IMAGE: Screenshot of Raspberry Pi Imager main screen -->

### Step 3.3: Choose Your Device

1. Click **CHOOSE DEVICE**
2. Select **Raspberry Pi 4** (or Pi 5 if using that)

<!-- IMAGE: Screenshot showing device selection -->

### Step 3.4: Choose the KCR Tracks Image

1. Click **CHOOSE OS**
2. Scroll down and select **Use custom**
3. Navigate to your downloaded `KCR-Tracks-v2.0.img.xz` file
4. Click **Open**

<!-- IMAGE: Screenshot showing "Use custom" option -->

<!-- IMAGE: Screenshot showing file browser selecting the image -->

### Step 3.5: Choose Your SSD

1. Click **CHOOSE STORAGE**
2. Select your USB SSD from the list
3. **Double-check** you've selected the correct drive!

<!-- IMAGE: Screenshot showing storage selection -->

**Warning:** Selecting the wrong drive will erase that drive. Make sure you select your SSD, not your computer's hard drive.

### Step 3.6: Write the Image

1. Click **NEXT**
2. When asked about OS customisation, click **NO** (KCR Tracks has its own configuration)
3. Click **YES** to confirm you want to write to the SSD
4. Enter your computer password if prompted
5. Wait for the write to complete (5-10 minutes)

<!-- IMAGE: Screenshot showing write progress -->

### Step 3.7: Write Complete

When you see "Write Successful", click **CONTINUE**.

**Don't eject the SSD yet** if you want to pre-configure your station (next section).

<!-- IMAGE: Screenshot showing write complete message -->

---

## 4. Pre-Configure Your Station (Optional)

You can configure your station name and settings before first boot. This is optional - you can also do this later through the admin panel.

### Step 4.1: Open the Boot Drive

After writing the image, your computer should show a new drive called **bootfs** or **boot**.

Open this drive in File Explorer (Windows) or Finder (Mac).

<!-- IMAGE: Screenshot showing boot drive in file explorer -->

### Step 4.2: Edit the Configuration File

1. Find the file called `kcr-config.txt`
2. Open it with Notepad (Windows) or TextEdit (Mac)
3. Edit the settings for your station:

```ini
# Station Information
STATION_NAME=Your Radio Station Name
STATION_SHORT_NAME=YRS
STATION_WEBSITE=https://yourstation.com

# Timezone (see guide for options)
TIMEZONE=Australia/Sydney
```

<!-- IMAGE: Screenshot showing kcr-config.txt open in Notepad -->

### Step 4.3: Save and Eject

1. Save the file
2. Safely eject the SSD:
   - **Windows:** Right-click the drive → Eject
   - **Mac:** Drag the drive to the Trash (eject icon)

### Configuration Options Reference

| Setting | Description | Example |
|---------|-------------|---------|
| `STATION_NAME` | Your station's full name | `Kiama Community Radio` |
| `STATION_SHORT_NAME` | Short name (no spaces) | `KCR` |
| `STATION_WEBSITE` | Your website URL | `https://kcr.org.au` |
| `TIMEZONE` | Your timezone | `Australia/Sydney` |
| `WIFI_SSID` | WiFi network name | `StudioWiFi` |
| `WIFI_PASSWORD` | WiFi password | `yourpassword` |
| `ENABLE_SSH` | Remote access (advanced) | `false` |

**Common Australian Timezones:**
- `Australia/Sydney` - NSW, ACT, VIC, TAS (AEST/AEDT)
- `Australia/Brisbane` - QLD (AEST, no daylight saving)
- `Australia/Adelaide` - SA (ACST/ACDT)
- `Australia/Perth` - WA (AWST)
- `Australia/Darwin` - NT (ACST)

---

## 5. Assemble and Power On

### Step 5.1: Connect the SSD to Your Raspberry Pi

1. Connect the USB SSD to one of the **blue USB 3.0 ports** on the Pi

<!-- IMAGE: Photo showing SSD connected to Pi USB 3.0 port -->

### Step 5.2: Connect the Display

**For HDMI Monitor/TV:**
1. Connect micro-HDMI cable to the Pi (use the port closest to the USB-C power)
2. Connect the other end to your monitor/TV

**For Official 7" Touchscreen:**
1. Connect the ribbon cable to the Pi's DSI port
2. Connect the power cables as per the touchscreen instructions

<!-- IMAGE: Photo showing display connection -->

### Step 5.3: Connect Audio (Optional)

**For 3.5mm Audio:**
- Connect a 3.5mm cable from the Pi's headphone jack to your mixer

**For USB Audio Interface:**
- Connect your USB audio interface to a USB port on the Pi

### Step 5.4: Connect Network (Recommended)

**For Ethernet (Recommended):**
- Connect an ethernet cable from your router/switch to the Pi

**For WiFi:**
- If you configured WiFi in `kcr-config.txt`, it will connect automatically

### Step 5.5: Power On

1. Connect the USB-C power supply to the Pi
2. The Pi will boot automatically
3. You'll see the Raspberry Pi logo, then KCR Tracks will load

<!-- IMAGE: Photo showing power connection -->

---

## 6. First Boot

### What Happens on First Boot

The first time you power on, KCR Tracks performs automatic setup:

1. **Expanding storage** - The music storage expands to use your full SSD
2. **Applying configuration** - Your station name and settings are applied
3. **Connecting to network** - WiFi connects if configured
4. **Starting kiosk mode** - The touchscreen interface loads

This takes about 2-3 minutes. The Pi may reboot once during this process.

<!-- IMAGE: Photo showing first boot screen -->

### First Boot Complete

When setup is complete, you'll see the KCR Tracks login screen with your station name.

<!-- IMAGE: Screenshot of KCR Tracks login screen -->

**Congratulations! Your KCR Tracks appliance is ready to use.**

---

## 7. Using KCR Tracks

### Quick Start for Presenters

#### Playing Music from a USB Drive

1. **Copy music** to a USB thumb drive on any computer
2. **Insert the USB drive** into the KCR Tracks appliance (not the Raspberry Pi itself - use the USB ports on your studio desk if available)
3. **Enter your name** on the touchscreen
4. **Tap OK** to start your session
5. **Tap "Choose Files"** and select your music files
6. **Tap a track** to play it

#### Supported Audio Formats

| Format | Extension |
|--------|-----------|
| MP3 | .mp3 |
| WAV | .wav |
| OGG Vorbis | .ogg |
| FLAC | .flac |
| M4A | .m4a |

**Maximum file size:** 50 MB per file

### For Detailed Instructions

See the **KCR Tracks User Guide** for complete instructions on:
- Managing sessions
- Auto-play mode
- Deleting tracks and sessions
- Reports

---

## 8. Customising Your Station

### Accessing the Admin Panel

You can customise your station's branding from any device on the same network:

1. Find your Pi's IP address (shown briefly during boot, or check your router)
2. On any computer/phone, open a web browser
3. Go to: `http://[Pi-IP-Address]/kcr-tracks/admin_customize.php`
4. Enter the admin password (default: `admin` - change this!)

<!-- IMAGE: Screenshot of admin login -->

### What You Can Customise

| Setting | Description |
|---------|-------------|
| **Station Name** | Displayed in the header |
| **Logo** | Upload your station logo |
| **Colours** | Match your station branding |
| **Footer Text** | Custom message at bottom of screen |

<!-- IMAGE: Screenshot of admin customisation panel -->

### Changing the Admin Password

**Important:** Change the default admin password after first login.

1. Log into the admin panel
2. Go to Settings
3. Enter a new secure password
4. Click Save

---

## 9. Troubleshooting

### The Pi Won't Boot

| Symptom | Solution |
|---------|----------|
| No lights on Pi | Check power supply is connected and switched on |
| Red light only | Power issue - try a different power supply |
| Green light flashing | SD/SSD not detected - reseat the SSD connection |
| Rainbow screen | SSD not recognised - try a different USB port |

### No Display

| Symptom | Solution |
|---------|----------|
| Black screen | Check HDMI cable connection, try the other HDMI port |
| Display says "No Signal" | Wait 30 seconds for boot, check cable |
| Screen is rotated wrong | Edit `kcr-config.txt` and set `SCREEN_ROTATION` |

### No Audio

| Symptom | Solution |
|---------|----------|
| No sound | Check cables, check mixer fader is up |
| Sound from wrong output | In KCR Tracks, check audio output settings |
| Crackling/distortion | Try a USB audio interface instead of 3.5mm jack |

### Upload Problems

| Symptom | Solution |
|---------|----------|
| File won't upload | Check file is under 50MB and is an audio format |
| Upload stuck | Check network connection, refresh the page |
| "File type not allowed" | Only MP3, WAV, OGG, FLAC, M4A are accepted |

### Network Issues

| Symptom | Solution |
|---------|----------|
| Can't access admin panel | Ensure device is on same network, check IP address |
| WiFi won't connect | Check credentials in `kcr-config.txt`, try ethernet |

### Starting Fresh

If you need to completely reset KCR Tracks:

1. Re-flash the image to your SSD following Section 3
2. All music and settings will be reset to default

---

## 10. Getting Help

### Online Resources

- **Documentation:** https://yoursite.com/kcr-tracks/docs
- **FAQ:** https://yoursite.com/kcr-tracks/faq
- **Community Forum:** https://yoursite.com/kcr-tracks/forum

### Reporting Issues

If you find a bug or have a feature request:

- **GitHub Issues:** https://github.com/your-repo/kcr-tracks/issues

### Contact Support

- **Email:** support@yoursite.com

When contacting support, please include:
- Your KCR Tracks version (shown at bottom of screen)
- Your Raspberry Pi model
- A description of the problem
- Any error messages you see

---

## Appendix A: Hardware Recommendations

### Recommended Complete Kits

**Budget Studio Setup (~$285 AUD)**
- Raspberry Pi 4 (4GB)
- 128GB USB SSD
- Official power supply
- Basic case
- 7" generic HDMI display
- HDMI cable

**Professional Studio Setup (~$400 AUD)**
- Raspberry Pi 4 (8GB) or Pi 5
- 256GB USB SSD
- Official power supply
- Official 7" touchscreen
- Aluminium case with cooling
- USB audio interface

### Tested Compatible Hardware

**SSDs (Any USB 3.0 SSD works, these are tested):**
- Samsung T7
- Crucial X6
- SanDisk Extreme

**USB Audio Interfaces:**
- Behringer UCA202
- Focusrite Scarlett Solo
- Native Instruments Komplete Audio 1

---

## Appendix B: Network Information

### Finding Your Pi's IP Address

**Method 1: Check Your Router**
1. Log into your router's admin page
2. Look for connected devices
3. Find the device named `kcr-[stationname]`

**Method 2: Use a Network Scanner**
- **Windows:** Advanced IP Scanner (free)
- **Mac:** LanScan (free)
- **Mobile:** Fing app (free)

### Static IP Address (Advanced)

If you need a fixed IP address, add to `kcr-config.txt`:

```ini
STATIC_IP=192.168.1.100
GATEWAY=192.168.1.1
DNS=8.8.8.8
```

---

## Appendix C: Technical Specifications

| Component | Specification |
|-----------|---------------|
| **Operating System** | Raspberry Pi OS Lite (64-bit) |
| **Web Server** | Apache 2.4 |
| **PHP Version** | 8.1+ |
| **Storage Format** | ext4 (auto-expands on first boot) |
| **Default Port** | 80 (HTTP) |
| **Supported Audio** | MP3, WAV, OGG, FLAC, M4A |
| **Max File Size** | 50 MB |
| **Kiosk Browser** | Chromium |

---

**KCR Tracks Appliance v2.0**

© 2026 Digital Streams Media

Licensed under MIT License - Free for all use.

