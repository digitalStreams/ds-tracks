# {PROJECT_NAME} System State Documentation

**Version:** {VERSION}
**Last Verified:** {DATE}
**Environment:** Production

<!-- INSTRUCTIONS: Replace all {PLACEHOLDER} values with actual project data. This template provides standardised documentation of system architecture, database schema, API surface, and frontend structure. -->

---

## Quick Reference

| Metric | Count |
|--------|-------|
| Database Tables | {TABLE_COUNT} |
| API Endpoints | {ENDPOINT_COUNT} |
| Frontend Views | {VIEW_COUNT} |
| Shared Components | {COMPONENT_COUNT} |
| Data Models | {MODEL_COUNT} |
| Database Migrations | {MIGRATION_COUNT} |
| Test Suites | {TEST_COUNT} |

<!-- INSTRUCTIONS: Obtain these counts by:
- TABLE_COUNT: Query `SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='DATABASE_NAME'`
- ENDPOINT_COUNT: Sum all route definitions across route files
- VIEW_COUNT: Count .vue files in `src/views/` and `src/pages/`
- COMPONENT_COUNT: Count .vue files in `src/components/shared/`
- MODEL_COUNT: Count files in `server/models/`
- MIGRATION_COUNT: Count files in `server/migrations/`
- TEST_COUNT: Count test files across `tests/` and `.test.js` files
-->

---

## Database Tables

### Core Tables

| Table Name | Columns | Purpose |
|-----------|---------|---------|
| {TABLE_NAME_1} | {COL_COUNT} | {TABLE_PURPOSE} |
| {TABLE_NAME_2} | {COL_COUNT} | {TABLE_PURPOSE} |
| {TABLE_NAME_3} | {COL_COUNT} | {TABLE_PURPOSE} |

<!-- INSTRUCTIONS: List primary tables that form the domain model (e.g., Users, Projects, Bugs, Tasks). Include column count and purpose. Example: "Users | 15 | User authentication and profile management" -->

### Supporting Tables

| Table Name | Columns | Purpose |
|-----------|---------|---------|
| {TABLE_NAME_4} | {COL_COUNT} | {TABLE_PURPOSE} |
| {TABLE_NAME_5} | {COL_COUNT} | {TABLE_PURPOSE} |
| {TABLE_NAME_6} | {COL_COUNT} | {TABLE_PURPOSE} |

<!-- INSTRUCTIONS: List junction tables, history tables, and supporting entities (e.g., UserRoles, AuditLog, ProjectMembers). -->

### System Tables

| Table Name | Columns | Purpose |
|-----------|---------|---------|
| {TABLE_NAME_7} | {COL_COUNT} | {TABLE_PURPOSE} |
| {TABLE_NAME_8} | {COL_COUNT} | {TABLE_PURPOSE} |

<!-- INSTRUCTIONS: List infrastructure/system tables used for audit, logging, or operational purposes (e.g., AuditLog, SessionStore, ApiKeys). -->

---

## API Endpoints

### Authentication Routes
**Route File:** `{ROUTE_FILE_PATH}`
**Mount Point:** `{MOUNT_PATH}`

| Endpoint | Method | Purpose |
|----------|--------|---------|
| `{ENDPOINT_PATH_1}` | {HTTP_METHOD} | {ENDPOINT_PURPOSE} |
| `{ENDPOINT_PATH_2}` | {HTTP_METHOD} | {ENDPOINT_PURPOSE} |

<!-- INSTRUCTIONS: Document authentication endpoints (login, logout, token validation, password reset, etc.). -->

### {RESOURCE_TYPE} Routes
**Route File:** `{ROUTE_FILE_PATH}`
**Mount Point:** `{MOUNT_PATH}`

| Endpoint | Method | Purpose |
|----------|--------|---------|
| `{ENDPOINT_PATH_1}` | {HTTP_METHOD} | {ENDPOINT_PURPOSE} |
| `{ENDPOINT_PATH_2}` | {HTTP_METHOD} | {ENDPOINT_PURPOSE} |
| `{ENDPOINT_PATH_3}` | {HTTP_METHOD} | {ENDPOINT_PURPOSE} |

<!-- INSTRUCTIONS: Create separate subsections for each major resource type (Projects, Bugs, Users, Reports, etc.). Use consistent HTTP methods (GET, POST, PUT, DELETE, PATCH). Include brief purpose for each endpoint. -->

### Admin Routes
**Route File:** `{ROUTE_FILE_PATH}`
**Mount Point:** `{MOUNT_PATH}`

| Endpoint | Method | Purpose |
|----------|--------|---------|
| `{ENDPOINT_PATH_1}` | {HTTP_METHOD} | {ENDPOINT_PURPOSE} |
| `{ENDPOINT_PATH_2}` | {HTTP_METHOD} | {ENDPOINT_PURPOSE} |

<!-- INSTRUCTIONS: Document administrative endpoints (user management, system configuration, analytics). Typically restricted to admin roles. -->

---

## Frontend Views & Components

### Main Views

| View Name | Route | Purpose |
|-----------|-------|---------|
| `{VIEW_NAME_1}` | `{ROUTE_PATH}` | {VIEW_PURPOSE} |
| `{VIEW_NAME_2}` | `{ROUTE_PATH}` | {VIEW_PURPOSE} |
| `{VIEW_NAME_3}` | `{ROUTE_PATH}` | {VIEW_PURPOSE} |
| `{VIEW_NAME_4}` | `{ROUTE_PATH}` | {VIEW_PURPOSE} |

<!-- INSTRUCTIONS: List primary Vue components used as page-level views. Include the route path and purpose. Example: "BugTracker | /bugs | Main bug tracking interface with table view and filters" -->

### Shared Components

| Component Name | Location | Purpose |
|---|---|---|
| `{COMPONENT_NAME_1}` | `src/components/shared/{FILE}` | {COMPONENT_PURPOSE} |
| `{COMPONENT_NAME_2}` | `src/components/shared/{FILE}` | {COMPONENT_PURPOSE} |
| `{COMPONENT_NAME_3}` | `src/components/shared/{FILE}` | {COMPONENT_PURPOSE} |
| `{COMPONENT_NAME_4}` | `src/components/shared/{FILE}` | {COMPONENT_PURPOSE} |

<!-- INSTRUCTIONS: List reusable components shared across multiple views. Include file location and purpose. Example: "BugEditModal | src/components/shared/BugEditModal.vue | Modal for creating/editing bugs" -->

---

## External Integrations

| Integration | Type | Purpose |
|-------------|------|---------|
| {INTEGRATION_NAME_1} | {TYPE} | {PURPOSE} |
| {INTEGRATION_NAME_2} | {TYPE} | {PURPOSE} |
| {INTEGRATION_NAME_3} | {TYPE} | {PURPOSE} |

<!-- INSTRUCTIONS: Document third-party integrations (e.g., GitHub, Slack, email services, payment processors). Include integration name, type (API, webhook, SDK), and purpose. Leave blank if no integrations. -->

---

## Architecture Notes

<!-- INSTRUCTIONS: Add any critical architectural notes or recent changes. Example:
- Authentication uses JWT tokens stored in localStorage
- Database uses UUID primary keys across all core tables
- File uploads stored in S3-compatible object storage
- Event-driven notifications via email and webhooks
-->

---

## Version History

| Version | Date | Notes |
|---------|------|-------|
| {VERSION} | {DATE} | {RELEASE_NOTES} |

<!-- INSTRUCTIONS: Update this table when system state changes significantly (new endpoints, table migrations, component restructuring, etc.). -->

---

**Last Updated:** {DATE}
**Maintained By:** {TEAM_NAME}

<!-- INSTRUCTIONS: Update the "Last Updated" date whenever this document is revised. Ensure accuracy by verifying against actual codebase before committing. -->
