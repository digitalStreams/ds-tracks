# Naming Conventions System

**Version:** 1.0.0
**Date:** 2026-02-03
**Status:** Implemented

---

## Overview

The Naming Conventions System is preventive infrastructure designed to eliminate naming inconsistency bugs caused by AI coding agents across development sessions. It consists of two core files and integration points in the development workflow.

## Problem Statement

AI coding agents start each session without memory of previous naming decisions. This led to production bugs including:

- `'token'` vs `'authToken'` in localStorage keys (caused 401 errors)
- `user.id` vs `user.uid` for user lookups (caused 404 errors)
- `passwordHash` vs `password_hash` across different files
- Mixed camelCase and snake_case in database column references

These are not style preferences — they are bug vectors that cost debugging time and cause production incidents.

## Solution Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                    NAMING CONVENTIONS SYSTEM                     │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  ┌──────────────────────┐    ┌──────────────────────────────┐  │
│  │   .conventions.md    │    │     NAMING_REGISTER.md       │  │
│  │                      │    │                              │  │
│  │  - Quick reference   │    │  - Complete inventory        │  │
│  │  - Case rules        │    │  - Every name in codebase    │  │
│  │  - Canonical names   │    │  - Usage locations           │  │
│  │  - Agent instructions│    │  - Known inconsistencies     │  │
│  └──────────────────────┘    └──────────────────────────────┘  │
│              │                            │                      │
│              └────────────┬───────────────┘                      │
│                           │                                      │
│                           ▼                                      │
│  ┌─────────────────────────────────────────────────────────┐    │
│  │                  INTEGRATION POINTS                      │    │
│  │                                                          │    │
│  │  START-HERE.md         → First rule: Read .conventions   │    │
│  │  FEATURE-WRAP-UP       → Step 2.3: Update register       │    │
│  │  When Switching Areas  → Check register before naming    │    │
│  └─────────────────────────────────────────────────────────┘    │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

## Core Files

### 1. `.conventions.md` (Project Root)

**Purpose:** Quick-reference rules for naming decisions

**Contents:**
- Case rules by context (camelCase, PascalCase, kebab-case, etc.)
- Canonical names with "NEVER Use" alternatives
- localStorage key registry
- File naming conventions
- API endpoint patterns
- Instructions for AI coding agents

**When to Read:** Before writing ANY new code

**When to Update:** When establishing new patterns (rare)

### 2. `NAMING_REGISTER.md` (Project Root)

**Purpose:** Complete inventory of every significant name in the codebase

**Contents:**
- All names organized by domain (Auth, Users, Projects, Bugs, etc.)
- Usage locations (file:line references)
- API endpoint catalog
- localStorage keys with set/read locations
- Environment variables
- Database tables and key columns
- Known inconsistencies with fix recommendations

**When to Read:** Before introducing any new name

**When to Update:** After implementing any feature that adds new names

## Integration Points

### START-HERE.md

Added as the **first** critical rule:

```markdown
| **Naming** | Read [.conventions.md](.conventions.md) BEFORE creating any new names |
```

Also added to "When Switching Code Areas" checklist:
- Read `.conventions.md` for naming standards
- Check `NAMING_REGISTER.md` before introducing any new names

### FEATURE-WRAP-UP-PROCEDURE.md

Added Step 2.3: Update Naming Register

Required when feature introduces:
- localStorage keys
- API endpoints
- Database columns or tables
- Environment variables
- Vue events or props

## Naming Rules Summary

| Context | Convention | Example |
|---------|-----------|---------|
| JavaScript variables | camelCase | `authToken`, `userId` |
| Components | PascalCase | `OrderEditModal.vue` |
| Store files | camelCase + `use` prefix | `useAuthStore.js` |
| ORM models | PascalCase | `OrderItem`, `UserRole` |
| Database columns | camelCase | `customerId`, `createdAt` |
| API routes | kebab-case | `/api/forgot-password` |
| Route files | camelCase + `Routes` | `moduleRoutes.js` |
| Environment vars | UPPER_SNAKE | `JWT_SECRET` |
| localStorage keys | camelCase | `authToken` |
| Events | kebab-case | `close`, `item-updated` |

## Critical Canonical Names

These names have caused bugs in the past. Always use exactly:

| Concept | Canonical Name | NEVER Use |
|---------|---------------|-----------|
| JWT access token | `authToken` | `token`, `accessToken`, `jwt` |
| User database ID | `user.id` | `user.userId`, `user.dbId` |
| Foreign key to user | `userId` | `user_id`, `fkUserId` |
| Entity owner | `ownerId` | `assignedToId`, `assigneeId` |
| Password hash | `passwordHash` | `password_hash`, `pwHash` |

## Maintenance Procedures

### Adding a New Name

1. Search `NAMING_REGISTER.md` for existing names for that concept
2. If exists, use the canonical name exactly
3. If new concept, add to register before using:
   - Find appropriate section
   - Add row with: Concept | Canonical Name | Used In | Notes
4. Follow case rules from `.conventions.md`

### Fixing an Inconsistency

1. Document in "Known Inconsistencies" section of register
2. Create separate fix task (don't mix with feature work)
3. Update all occurrences in a single commit
4. Remove from inconsistencies list after fix

### Auditing the Codebase

Run periodic audits using grep patterns:

```bash
# Find all localStorage usage
grep -rn "localStorage\.\(get\|set\|remove\)Item" client/src/

# Find all token-related names
grep -rn "\(token\|Token\|TOKEN\)" server/ --include="*.js"

# Find all user ID patterns
grep -rn "user\.\(id\|uid\|userId\)" . --include="*.js" --include="*.vue"
```

## Known Inconsistencies (As of 2026-02-03)

### Critical

| Issue | Location | Fix |
|-------|----------|-----|
| Test files use `'token'` | `client/src/__tests__/views/*.test.js` | Change to `'authToken'` |

### Medium

| Issue | Location | Fix |
|-------|----------|-----|
| EmailLog snake_case columns | `server/models.js` | Document as exception or migrate |
| Deprecated Bug fields | `server/models.js` | Add deprecation comments |

## Success Metrics

The system is working when:

1. No new naming bugs appear in production
2. Agents can find existing names without searching codebase
3. Register stays current (updated with each feature)
4. New developers understand naming immediately

## Related Documentation

- [.conventions.md](../../.conventions.md) — Quick reference rules
- [NAMING_REGISTER.md](../../NAMING_REGISTER.md) — Complete name inventory
- [start-here.md](../../start-here.md) — Entry point with naming rule

---

<!-- INSTRUCTIONS: Update author and date when adapting this document for your project. -->
*Template based on naming convention system design patterns.*
