# Documentation Library Index

**Purpose:** Index of all project documentation with estimated token sizes for AI context budgeting.

---

## How to Use This Index

1. **Identify Your Task** — Scan the sections below to find relevant documentation
2. **Check Token Budget** — Each doc lists estimated tokens needed to understand it
3. **Read Strategically** — Start with Priority: **Critical** docs, then add secondary references
4. **Context Window Management** — Budget 40-50% of your token limit for docs, rest for work

### Token Estimates Explained
- **~500 tokens** = Quick reference (1-2 screens, 200-300 words)
- **~1,500 tokens** = Comprehensive guide (3-5 screens, 600-800 words)
- **~3,000+ tokens** = Detailed architecture or large reference (10+ screens)

---

## Reference Documentation

| File | Purpose | ~Tokens | Priority |
|------|---------|---------|----------|
| [`ARCHITECTURE.md`](ARCHITECTURE.md) | Tech stack, system diagrams, auth flow, frontend/backend/DB architecture | ~2,500 | **High** |
| [`CONFIGURATION.md`](CONFIGURATION.md) | Environment variables, auth setup, email config, RBAC, security checklist | ~1,500 | **High** |
| [`DATABASE-SCHEMA.md`](DATABASE-SCHEMA.md) | Database models, relationships, indexes, query examples | ~2,000 | **High** |
| [`FEATURES.md`](FEATURES.md) | Feature inventory by area, API endpoints summary, roadmap | ~1,000 | **Medium** |
| [`SYSTEM-STATE.md`](SYSTEM-STATE.md) | Current metrics: table counts, endpoints, views, components, integrations | ~800 | **Medium** |
| [`NAMING-CONVENTIONS-SYSTEM.md`](NAMING-CONVENTIONS-SYSTEM.md) | System design for naming conventions methodology | ~1,200 | **Medium** |
| [`CHANGELOG.md`](CHANGELOG.md) | Version history, breaking changes, migration notes | ~500 | **Medium** |

---

## Guides

| File | Purpose | ~Tokens | Priority |
|------|---------|---------|----------|
| [`BUILD-DAY-GUIDE.md`](../guides/BUILD-DAY-GUIDE.md) | Step-by-step walkthrough for creating a Pi appliance image on Build Day | ~1,500 | **High** |
| [`DEPLOYMENT-GUIDE.md`](../guides/DEPLOYMENT-GUIDE.md) | Deployment instructions for radio stations (hardware, install, config) | ~2,500 | **High** |
| [`INSTALLATION-GUIDE.md`](../guides/INSTALLATION-GUIDE.md) | End-user appliance setup guide (flash SD card and boot) | ~1,000 | **High** |
| [`QUICK-START.md`](../guides/QUICK-START.md) | 15-minute quick start for getting DS-Tracks running | ~800 | **High** |
| [`INTEGRATION-AUDIT-PROMPT.md`](../guides/INTEGRATION-AUDIT-PROMPT.md) | Cross-component verification methodology for catching endpoint mismatches | ~600 | **High** |
| [`LOCALIZATION.md`](../guides/LOCALIZATION.md) | i18n standards, translation key patterns, adding new languages | ~1,000 | **Medium** |

---

## Specifications

| File | Purpose | ~Tokens | Priority |
|------|---------|---------|----------|
| [`APPLIANCE-BUILD-SPECIFICATION.md`](../specifications/APPLIANCE-BUILD-SPECIFICATION.md) | Technical specification for the Raspberry Pi appliance build system | ~3,000 | **High** |
| [`USB-UX-SPECIFICATION.md`](../specifications/USB-UX-SPECIFICATION.md) | UX and technical specification for USB auto-detect and file browser | ~2,000 | **Medium** |

---

## Planning & Tracking

| File | Purpose | ~Tokens | Priority |
|------|---------|---------|----------|
| [`deferred-work.md`](../deferred-work.md) | Tasks postponed for future work (DS-Tracks rebrand scope) | ~300 | **Medium** |
| [`resolved-work.md`](../resolved-work.md) | Archive of completed deferred items with resolution details | ~300 | **Medium** |

---

## Archive

Historical documents from v2.0 development. Not actively maintained but preserved for reference.

| File | Purpose | ~Tokens | Priority |
|------|---------|---------|----------|
| [`CHANGES-SUMMARY.md`](../archive/CHANGES-SUMMARY.md) | One-time v2.0 security update summary | ~500 | Low |
| [`PROJECT-DOCUMENTATION.md`](../archive/PROJECT-DOCUMENTATION.md) | Complete v2.0 work documentation and project record | ~3,000 | Low |
| [`README-DISTRIBUTION.md`](../archive/README-DISTRIBUTION.md) | Distribution package overview (pre-appliance era) | ~500 | Low |
| [`SECURITY-UPDATES.md`](../archive/SECURITY-UPDATES.md) | v2.0 security fixes documentation | ~500 | Low |
| [`TECHNICAL-BRIEFING.md`](../archive/TECHNICAL-BRIEFING.md) | Complete technical review of v2.0 changes | ~3,000 | Low |
| [`DS-Tracks-User-Manual-V2.md`](../archive/DS-Tracks-User-Manual-V2.md) | End-user manual | ~1,500 | Low |
| [`KCR-Tracks-User-Manual-D02-2023-03-14.pdf`](../archive/KCR-Tracks-User-Manual-D02-2023-03-14.pdf) | Legacy v1.2 PDF manual | N/A | Low |

---

## Quick Reference by Task

**Deploying to a Raspberry Pi?**
→ Start: `QUICK-START.md` → `DEPLOYMENT-GUIDE.md` → `INSTALLATION-GUIDE.md`

**Building an Appliance Image?**
→ Start: `BUILD-DAY-GUIDE.md` → `APPLIANCE-BUILD-SPECIFICATION.md`

**Debugging API Mismatches?**
→ Start: `INTEGRATION-AUDIT-PROMPT.md` → `ARCHITECTURE.md`

**Understanding the Database?**
→ Start: `DATABASE-SCHEMA.md` → `ARCHITECTURE.md`

**Adding USB/Storage Features?**
→ Start: `USB-UX-SPECIFICATION.md` → `CONFIGURATION.md`

---

## Maintenance Notes

- **Token estimates** are based on actual file size and complexity; recalibrate quarterly
- **Priority levels** reflect how often agents need these docs for common tasks
- **Links are relative** from `docs/reference/` — adjust if moving this file
