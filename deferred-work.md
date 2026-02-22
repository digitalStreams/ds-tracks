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
