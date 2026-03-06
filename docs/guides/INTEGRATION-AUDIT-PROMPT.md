# Integration Audit Prompt — Cross-Component Verification

## Overview

This prompt template enables systematic verification of API contracts across all client-server integrations in a project. It's designed to catch silent failures where client code assumes an endpoint exists or returns data in a specific format, but the server implementation has diverged.

This methodology identified **5 broken VSIX endpoints** in production and prevented similar issues across web, mobile, and CLI integrations.

---

## When to Use This Template

- **Before releases** — Especially major versions (v4.x.x), to ensure all components will work
- **After major refactors** — Endpoint signatures, response formats, or auth mechanisms changed
- **When adding new integrations** — New client component (web feature, extension, mobile app, CLI) needs server APIs
- **When client code silently fails** — No console errors, HTTP 200s, but data doesn't appear or behavior is wrong
- **After API dependency updates** — If you updated server frameworks, auth libraries, or response serialization

---

## The Audit Prompt Template

<!-- INSTRUCTIONS: Copy this section and fill in [BRACKETED] placeholders. Pass to an AI agent with read access to your codebase. -->

### Prompt: Verify API Contract Consistency for `[PROJECT_NAME]`

You are a cross-component integration auditor. Your task is to identify mismatches between what client code expects and what the server actually provides.

**Input:** Source code for:
- Server routes and controllers (express/fastify/rails/django backend)
- Client components that make HTTP requests (web, mobile, extension, CLI)
- Any shared API contracts (OpenAPI specs, type definitions)

**Output:** Structured audit report with all mismatches, severity, and remediation.

---

### Step 1: Component Inventory

List all components that communicate with the server. For each, note:
- **Component name** (e.g., "BugTracker.vue web client", "VS Code extension")
- **Transport** (HTTP REST, WebSocket, GraphQL)
- **Authentication** (API key, JWT, session cookie, none)
- **Approximate number of endpoints** it calls

<!-- INSTRUCTIONS: Example output is shown below. Your actual output should list every component in [PROJECT_NAME]. -->

**Example:**
| Component | Transport | Auth | ~Endpoints |
|-----------|-----------|------|-----------|
| Web client (Vue) | HTTP REST | JWT token | 18 |
| VS Code extension | HTTP REST | API key header | 6 |
| Mobile app (React Native) | HTTP REST + WebSocket | JWT token | 12 |
| CLI tool | HTTP REST | API key or JWT | 8 |

---

### Step 2: Extract Client Endpoint Calls

For each component, find every HTTP request it makes. Document:
- **Endpoint path** (e.g., `GET /api/projects/:id`)
- **HTTP method** (GET, POST, PATCH, DELETE)
- **Request body shape** (if applicable)
- **Expected response shape**
- **Auth requirement** stated in client code

<!-- INSTRUCTIONS: Use grep/search to find all fetch(), axios(), or HTTP calls in client code. Build a table per component. -->

**Example for web client:**
| Endpoint | Method | Request Body | Expected Response | Auth |
|----------|--------|---|---|---|
| `/api/projects/:id` | GET | none | `{id, title, Bugs: [...]}` | JWT |
| `/api/projects/:id/bugs` | GET | none | `[{id, item, urgency, ...}]` | JWT |
| `/api/bugs` | POST | `{projectId, item, description}` | `{id, item, ...}` | JWT |
| `/api/bugs/:id` | PATCH | `{item?, urgency?, ...}` | `{id, item, ...}` | JWT |

---

### Step 3: Verify Endpoints Exist on Server

For each endpoint extracted in Step 2:
1. Search server route files (controllers, route handlers) for the exact path
2. Verify the HTTP method matches
3. If the endpoint doesn't exist, mark as **CRITICAL**

<!-- INSTRUCTIONS: Check all server route files. Look for route definitions like router.get(), app.post(), etc. If you can't find it, it's broken. -->

**Example mismatch to report:**
- **Client expects:** `GET /api/projects/:projectId/bugs`
- **Server has:** `GET /api/bugs` (no projectId filter)
- **Result:** MISSING — Endpoint doesn't exist as client calls it

---

### Step 4: Verify Request/Response Format Matches

For endpoints that exist, verify the contract matches:

1. **Request body schema** — Does the server controller accept the fields the client sends?
2. **Response shape** — Does the server return the shape the client expects?
3. **Field names** — Do they match exactly? (not `name` vs `title`, `comment` vs `content`)
4. **Wrapping** — Is response `{success: true, data: {...}}` or bare `{...}`?
5. **Status codes** — Does server return expected codes (200, 201, 400, 404, 500)?

<!-- INSTRUCTIONS: Read server controller and client code side-by-side. Check field names, response structure, error formats. -->

**Example mismatch to report:**
- **Client sends:** `{item: "Fix login", urgency: "HIGH"}`
- **Server expects:** `{title: "Fix login", priority: "HIGH"}` (using deprecated `priority` instead of `urgency`)
- **Result:** FIELD_NAME_MISMATCH — Server will ignore `item` field

---

### Step 5: Verify Authentication Requirements Match

For each endpoint, verify:
1. **Client sends auth correctly** — JWT in Authorization header? API key in custom header? Cookie?
2. **Server validates auth correctly** — Middleware checks for the same auth method?
3. **Scope matches** — If API key is project-scoped, does the endpoint enforce project isolation?

<!-- INSTRUCTIONS: Check client HTTP headers, server middleware, and scope enforcement. -->

**Example mismatch to report:**
- **Client sends:** `Authorization: Bearer [JWT]`
- **Server expects:** `X-Api-Key: [API_KEY]` header
- **Result:** AUTH_METHOD_MISMATCH — Client auth is ignored; endpoint returns 401

---

### Step 6: Generate Findings Table

Compile all mismatches into a structured report:

| Component | Endpoint | Issue Type | Actual vs Expected | Severity | Remediation |
|-----------|----------|------------|-------------------|----------|-------------|
| Web client | `GET /api/projects/:id/bugs` | MISSING | Client calls it; doesn't exist in routes | CRITICAL | Add endpoint to server |
| Extension | `POST /api/attachments` | FIELD_MISMATCH | Client sends `filename`; server expects `name` | HIGH | Rename field in controller |
| Mobile | `PATCH /api/bugs/:id` | WRAPPING | Server returns `{success, data}`; client expects bare object | MEDIUM | Unwrap in client or server |

---

## Common Mismatches to Check (Checklist)

<!-- INSTRUCTIONS: Review these common patterns before reporting findings. They're the most frequent culprits. -->

### Endpoint Paths
- [ ] Client guesses endpoint path that doesn't exist in server routes
- [ ] Client assumes path parameters (`:id`) that server doesn't support
- [ ] Client uses deprecated endpoint (old version removed from routes)
- [ ] Server route path changed but client still calls old path

### Response Shape & Wrapping
- [ ] Server wraps response: `{success: true, data: {...}}`, but client expects bare object
- [ ] Server returns array, client expects object with `data` property
- [ ] Server doesn't include nested associations (e.g., `Bugs` array missing from project)
- [ ] Server includes extra fields client doesn't expect (harmless but confusing)

### Field Names
- [ ] Client uses `title`, server uses `name` (or vice versa)
- [ ] Client expects `ownerId`, server sends `assignedToId`
- [ ] Client sends `comment`, server expects `content` or `description`
- [ ] Client expects `displayName`, server has `userName`
- [ ] Bug status is `UPPERCASE` on server but lowercase on client

### Association Aliases
- [ ] Client expects `User` association, server provides `author`, `actor`, or `reporter`
- [ ] Nested object property mismatch (e.g., `reporter.name` vs `reportedBy.displayName`)
- [ ] Client assumes associations are populated; server returns IDs only

### Authentication
- [ ] Client sends JWT in wrong header (e.g., `X-Authorization` instead of `Authorization`)
- [ ] API key format differs (e.g., `bt_prj_xxx` vs `prj_xxx`)
- [ ] Server middleware doesn't validate the auth method client sends
- [ ] Scope enforcement missing (project-scoped key can access other projects)

### Status Codes & Error Handling
- [ ] Server returns 500 for validation errors instead of 400
- [ ] Server returns bare 404; client expects `{error: "Not found"}`
- [ ] Client assumes 200 means success, but server returns 200 with error in body

### Deprecated Fields & Naming Migrations
- [ ] New codebase uses `urgency`, old client still sends `priority`
- [ ] API key format changed; old keys still floating in client config

---

## Example Findings Format

<!-- INSTRUCTIONS: Use this table format when reporting mismatches. It's designed for quick triage and tracking to fix. -->

### Audit Results for [PROJECT_NAME] v[VERSION]

**Audited:** [DATE]
**Components Scanned:** [COUNT]
**Endpoints Verified:** [COUNT]
**Mismatches Found:** [COUNT]

---

### High Severity (Must Fix Before Release)

| Component | Endpoint | Issue | Expected | Actual | Status |
|-----------|----------|-------|----------|--------|--------|
| VS Code extension | `POST /api/attachments` | MISSING | Endpoint exists | 404 Not Found | Endpoint not in routes |
| Web client | `GET /api/bugs/:id/history` | FIELD_MISMATCH | `eventType` STRING | `event_type` undefined | Rename in controller |
| Mobile app | `PATCH /api/bugs/:id` | AUTH_FAIL | JWT in `Authorization` header | Server ignores; returns 401 | Verify middleware |

---

### Medium Severity (Should Fix; May Cause Silent Failures)

| Component | Endpoint | Issue | Expected | Actual | Status |
|-----------|----------|-------|----------|--------|--------|
| Web client | `GET /api/projects/:id` | MISSING_ASSOC | `Bugs` array in response | `bugs: []` not included | Add eager load in controller |
| Extension | `POST /api/bugs` | WRAPPING | Bare object `{id, item, ...}` | `{success: true, data: {...}}` | Unwrap in client |

---

### Low Severity (Code Quality; Works but Risky)

| Component | Endpoint | Issue | Expected | Actual | Status |
|-----------|----------|-------|----------|--------|--------|
| CLI | `GET /api/projects` | INCONSISTENT_NAMING | `title` field | Sometimes `name` | Standardize to `title` |

---

## Running the Audit

1. **Identify components** in your project that will use the APIs
2. **Gather audit context:**
   - Server route files (all HTTP endpoints)
   - Client HTTP request code (fetch/axios/HTTP library calls)
   - Shared schemas (TypeScript types, OpenAPI specs)
3. **Fill in placeholders** in the prompt above with your project details
4. **Execute the audit** by passing the prompt + code context to an AI agent
5. **Triage findings** — Group by severity, assign ownership, prioritize fixes
6. **Fix mismatches** — Use the "Remediation" column to guide fixes
7. **Re-audit after fixes** — Run the audit again to verify all mismatches resolved

---

## Integration with CI/CD

<!-- INSTRUCTIONS: If your team uses automated tests, consider adding contract testing. -->

For teams with API contract testing:
- Add integration tests that verify client expectations against server responses
- Run before each release
- Consider tools like Pact, Spring Cloud Contract, or Dredd

For teams without formal contract testing:
- Run this audit manually every release cycle
- Track findings in a spreadsheet or issue tracker
- Assign remediation to component owners

---

## Notes

- This audit assumes read access to full source code (client and server). For closed-source clients, use API documentation + reverse-engineer client behavior.
- The audit is most effective when run by someone not deeply familiar with the codebase (fresh eyes catch assumptions).
- Automating the first few steps (component inventory, endpoint extraction) saves time on large projects.
- Track the audit timeline — knowing when mismatches were introduced helps prevent recurrence.

