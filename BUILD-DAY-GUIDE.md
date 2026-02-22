# KCR Tracks - Build Day Guide

## How to Create a Ready-to-Go Appliance Image

**For Windows users with no Linux experience.**

**Time needed:** About 1 hour (most of it is waiting)

**You will do this once. After that, you just clone copies.**

---

## What You Need

### Hardware (Borrow if You Don't Own)

| Item | What It Is | Where to Get It | Approx Cost |
|------|-----------|-----------------|-------------|
| **Raspberry Pi 4 or 5** | Small credit-card computer | Core Electronics, Amazon AU | $90 |
| **SD card (32GB+)** | Micro SD card | Any electronics store | $15 |
| **SD card reader** | Plugs SD card into your PC | Any electronics store | $10 |
| **USB-C power supply** | Powers the Pi | Comes with Pi kits | $25 |
| **HDMI display** | Any TV or monitor | Use your TV | - |
| **Micro-HDMI cable** | Connects Pi to display | Comes with Pi kits | $10 |
| **USB keyboard** | Any keyboard | You have one | - |
| **Ethernet cable** | For internet connection | You have one | - |
| **USB thumb drive** | To copy files from your PC to the Pi | You have one | - |

**Important:** The Pi needs a wired ethernet connection (plugged into your router) for the build. WiFi is harder to set up and not needed - this is just for building.

---

## Before Build Day

Do these steps at your desk. They take about 10 minutes.

### Step 1: Download Raspberry Pi Imager

1. Go to **https://www.raspberrypi.com/software/**
2. Click the **Windows** download button
3. Install it like any normal Windows application

### Step 2: Flash the SD Card

1. Insert your SD card into your PC's card reader
2. Open **Raspberry Pi Imager**
3. Click **CHOOSE DEVICE** → Select your Pi model (Pi 4 or Pi 5)
4. Click **CHOOSE OS** → Select **Raspberry Pi OS (other)** → Select **Raspberry Pi OS Lite (64-bit)**
   - It must be the **Lite** version (no desktop)
   - It must be **64-bit**
5. Click **CHOOSE STORAGE** → Select your SD card
   - **Double check** you selected the SD card, not your hard drive!
6. Click **NEXT**
7. It will ask **"Would you like to apply OS customisation settings?"**
   - Click **EDIT SETTINGS**
   - On the **GENERAL** tab:
     - Set hostname: `kcr-tracks`
     - Set username: `pi`
     - Set password: `raspberry` (you'll change this later)
     - Skip the WiFi section (we're using ethernet cable)
   - On the **SERVICES** tab:
     - Tick **Enable SSH**
     - Select **Use password authentication**
   - Click **SAVE**
   - Click **YES** to apply settings
8. Click **YES** to confirm writing
9. Wait for it to finish (5-10 minutes)
10. When it says "Write Successful" click **CONTINUE**
11. Remove the SD card from your PC

### Step 3: Copy KCR Tracks to a USB Stick

1. Insert a USB thumb drive into your PC
2. Copy the entire **KCR-Tracks2** folder onto the USB stick
3. Safely eject the USB stick

You're now ready for build day.

---

## Build Day

### Step 1: Connect Everything (5 minutes)

Connect the Pi in this order:

1. **Plug the SD card** into the Pi (slot on the underside)
2. **Plug in the ethernet cable** (from your router to the Pi)
3. **Plug in the HDMI cable** (from the Pi to your TV/monitor)
   - Use the HDMI port **closest to the USB-C power port**
4. **Plug in the USB keyboard**
5. **Plug in the USB-C power supply** (this turns it on)

You'll see text scrolling on the screen. Wait about 30 seconds.

### Step 2: Login (1 minute)

When you see a login prompt:

```
kcr-tracks login:
```

Type:
```
pi
```
Press Enter. Then type the password:
```
raspberry
```
Press Enter.

You'll see a command prompt that looks like:
```
pi@kcr-tracks:~ $
```

**This is the Linux command line. Don't panic. You only need to type 5 commands total.**

### Step 3: Copy Files from USB Stick (2 minutes)

Insert your USB thumb drive (the one with KCR-Tracks2 on it) into any USB port on the Pi.

Wait 5 seconds, then type these commands **exactly as shown**. Press Enter after each one:

```
sudo mkdir -p /mnt/usb
```

```
sudo mount /dev/sda1 /mnt/usb
```

If you get an error on that second command, try this instead:
```
sudo mount /dev/sdb1 /mnt/usb
```

Now copy the files:
```
cp -r /mnt/usb/KCR-Tracks2 /tmp/
```

Check it worked:
```
ls /tmp/KCR-Tracks2/
```

You should see a list of files including `login.php`, `config.php`, `appliance/` etc.

Unmount the USB stick:
```
sudo umount /mnt/usb
```

You can now remove the USB stick from the Pi.

### Step 4: Run the Build Script (30-45 minutes)

This is the big one. Type these two commands:

```
cd /tmp/KCR-Tracks2/appliance
```

```
sudo bash build-appliance.sh
```

**Now walk away and make a cup of tea.**

The script will:
- Update the operating system
- Install the web server (Apache) and PHP
- Install KCR Tracks
- Install the touchscreen kiosk
- Configure USB auto-detection
- Set up the file browser
- Apply security settings
- Set all permissions correctly
- Clean up

You'll see lots of text scrolling past. This is normal. It takes 30-45 minutes depending on your internet speed.

**If you see red error text**, don't panic. Some warnings are normal. The script will stop completely if something actually fails.

When it finishes, you'll see a green message:

```
============================================================
 KCR Tracks Appliance Build Complete!
============================================================
```

### Step 5: Shutdown (1 minute)

Type:
```
sudo shutdown -h now
```

Wait for the green light on the Pi to stop flashing (about 10 seconds).

**Unplug the power cable.**

---

## Creating Your Master Image (On Your Windows PC)

Now you'll save the SD card as a file on your PC, so you can make copies.

### Step 1: Download Win32 Disk Imager

1. Go to **https://sourceforge.net/projects/win32diskimager/**
2. Download and install it

### Step 2: Read the SD Card

1. Put the Pi's SD card into your PC's card reader
2. Open **Win32 Disk Imager**
3. In the **Image File** box, click the folder icon
4. Choose where to save it (e.g., Desktop)
5. Name it: `KCR-Tracks-Master.img`
6. Make sure the correct drive letter is selected (your SD card)
7. Click **Read**
8. Wait (10-15 minutes for a 32GB card)
9. When it says "Read Successful" click **OK**

**You now have your master image file.** Keep this file safe. It's your golden copy.

---

## Cloning (Making Copies for Each Station)

Repeat this for each appliance you want to create.

### Step 1: Write to a New Card

1. Insert a **blank** SD card (or SSD via USB adapter) into your PC
2. Open **Win32 Disk Imager**
3. In **Image File**, browse to your `KCR-Tracks-Master.img`
4. Select the drive letter of the blank card
5. Click **Write**
6. Click **Yes** to confirm
7. Wait (10-15 minutes)
8. "Write Successful" - done!

### Step 2: Customise the Station Name (Optional)

After writing, the card will show a small partition called **bootfs** or **boot** in Windows Explorer.

1. Open that drive
2. Find the file `kcr-config.txt`
3. Open it with Notepad
4. Change the station name:
   ```
   STATION_NAME=Kiama Community Radio
   STATION_SHORT_NAME=KCR
   ```
5. Save and close
6. Safely eject the card

### Step 3: Boot the Appliance

1. Insert the card into the target Raspberry Pi
2. Connect the display and power
3. Wait 2-3 minutes for first-boot setup
4. KCR Tracks appears on screen
5. Done - it's ready to use!

The first boot takes a bit longer because it expands the storage to fill whatever size card/SSD you used. After that, it boots in about 30 seconds.

---

## Troubleshooting

### Build Script Problems

| Problem | Solution |
|---------|----------|
| "Permission denied" | Make sure you typed `sudo` at the start |
| "No such file or directory" | Check you typed the path correctly. Try `ls /tmp/KCR-Tracks2/appliance/` to see if the file is there |
| "Could not resolve host" | The Pi isn't connected to the internet. Check the ethernet cable |
| Script stops with red text | Read the error message. Usually it's a network issue. Try running the script again |
| USB stick not detected | Try a different USB port. Try `sudo mount /dev/sdb1 /mnt/usb` instead |

### Cloning Problems

| Problem | Solution |
|---------|----------|
| Win32 Disk Imager can't see the card | Try a different card reader. Make sure it's a micro SD adapter |
| Write fails | Make sure the target card is at least as large as the original |
| Clone won't boot | Re-write the image. Try a different SD card |

### After Cloning

| Problem | Solution |
|---------|----------|
| Screen is black | Check HDMI cable. Try the other HDMI port on the Pi |
| Stuck on boot text | Wait 2-3 minutes - first boot takes time |
| Station name is wrong | Edit `kcr-config.txt` on the boot partition from Windows |

---

## Quick Reference Card

Print this and keep it with your Pi kit.

```
═══════════════════════════════════════════════
  KCR TRACKS BUILD - QUICK REFERENCE
═══════════════════════════════════════════════

  LOGIN:     pi / raspberry

  COPY FILES FROM USB:
    sudo mkdir -p /mnt/usb
    sudo mount /dev/sda1 /mnt/usb
    cp -r /mnt/usb/KCR-Tracks2 /tmp/
    sudo umount /mnt/usb

  BUILD:
    cd /tmp/KCR-Tracks2/appliance
    sudo bash build-appliance.sh

  SHUTDOWN:
    sudo shutdown -h now

═══════════════════════════════════════════════
  Total Linux commands needed: 7
  Total time: ~1 hour (mostly waiting)
═══════════════════════════════════════════════
```

---

## What You End Up With

| Item | What It Is |
|------|-----------|
| `KCR-Tracks-Master.img` on your PC | Your golden master image file |
| Cloned SD cards / SSDs | Ready-to-boot appliances |

To make more appliances in future: open Win32 Disk Imager, write `KCR-Tracks-Master.img` to a new card. 5 minutes, no Linux needed ever again.

---

## Summary

| Phase | What | Linux Needed? | Time |
|-------|------|--------------|------|
| Prep (at desk) | Flash Pi OS, copy files to USB | No | 15 mins |
| Build (one time) | Boot Pi, run script | Yes (7 commands) | 45 mins |
| Save master | Read SD card on PC | No | 15 mins |
| Clone (per station) | Write image to new card | No | 10 mins each |
| Customise (optional) | Edit text file on Windows | No | 2 mins |

**Total Linux exposure: 7 commands, once, never again.**

