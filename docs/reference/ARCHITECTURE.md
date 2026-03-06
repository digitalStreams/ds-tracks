# {PROJECT_NAME} - Architecture Overview

<!-- INSTRUCTIONS:
     This is a reusable architecture documentation template.
     Replace all {PLACEHOLDER} values with your project-specific details.
     Delete any sections that do not apply to your project.
     Remove all <!-- INSTRUCTIONS --> comments once the document is finalised.
-->

**Project:** {PROJECT_NAME}
**Version:** {VERSION}
**Last Updated:** {DATE}
**Status:** {STATUS}
**Live URL:** {APP_URL}

---

## 1. Technology Stack

<!-- INSTRUCTIONS:
     Fill in each layer with the specific technology used.
     Add or remove rows as needed. The layers below cover most web apps.
-->

| Layer          | Technology                          | Notes                          |
|----------------|-------------------------------------|--------------------------------|
| Frontend       | {FRAMEWORK}                         |                                |
| Build Tool     | {BUILD_TOOL}                        |                                |
| UI Library     | {UI_LIBRARY}                        |                                |
| Backend        | {BACKEND_FRAMEWORK}                 |                                |
| Database       | {DATABASE_TYPE}                     |                                |
| ORM            | {ORM_NAME}                          |                                |
| Authentication | {AUTH_SYSTEM}                       |                                |
| Email          | {EMAIL_PROVIDER}                    |                                |
| Hosting        | {DEPLOY_PLATFORM}                   |                                |
| CI/CD          | {CI_CD_TOOL}                        |                                |
| Monitoring     | {MONITORING_TOOL}                   |                                |

---

## 2. System Architecture

<!-- INSTRUCTIONS:
     Adapt the ASCII diagram below to match your system topology.
     Show the path from client browser through to the database.
     Include any reverse proxies, CDNs, or message queues your system uses.
-->

```
+-------------------+         +------------------------+         +------------------+
|                   |  HTTPS  |                        |         |                  |
|   Browser/Client  +-------->+   {DEPLOY_PLATFORM}    |         |  {DATABASE_TYPE} |
|                   |         |   Reverse Proxy        |         |                  |
+-------------------+         +----------+-------------+         +--------+---------+
                                         |                                ^
                              +----------v-------------+                  |
                              |                        |                  |
                              |  {BACKEND_FRAMEWORK}   +------------------+
                              |  API Server             |    {ORM_NAME}
                              |  (port {API_PORT})      |
                              +----------+-------------+
                                         |
                              +----------v-------------+
                              |                        |
                              |  Static File Server    |
                              |  {FRAMEWORK} SPA Build |
                              |                        |
                              +-----------------------+
```

### Request Flow

```
Client Request
  |
  v
[Reverse Proxy / Platform Router]
  |
  +---> /api/*    ---> Backend API Server
  |                      |
  |                      +---> Auth Middleware
  |                      +---> Route Handler
  |                      +---> Controller
  |                      +---> Provider / Service
  |                      +---> ORM Model
  |                      +---> Database
  |
  +---> /*        ---> Static SPA (index.html + assets)
```

<!-- INSTRUCTIONS:
     If your frontend and backend are deployed separately, adjust the
     diagram to show two distinct services with CORS between them.
-->

---

## 3. Authentication & Authorization

### Auth Flow

<!-- INSTRUCTIONS:
     Describe your authentication mechanism. The template below assumes
     JWT-based auth. Replace with OAuth2, session-based, or API key
     flows as needed.
-->

```
1. Client sends credentials   POST /api/auth/login
2. Server validates            --> {AUTH_SYSTEM} verification
3. Server issues token         <-- JWT (access + refresh)
4. Client stores token         localStorage / httpOnly cookie
5. Subsequent requests         Authorization: Bearer <token>
6. Server middleware           verifyToken --> attachUser --> next()
```

### Middleware Chain

<!-- INSTRUCTIONS:
     List your middleware in execution order. Add or remove entries to
     match your actual middleware stack.
-->

```
Request
  |
  v
[CORS]
  |
  v
[Body Parser]
  |
  v
[Rate Limiter]           <!-- optional -->
  |
  v
[Auth Token Verification]
  |
  v
[User Context Injection]
  |
  v
[Role / Permission Check]
  |
  v
[Route Handler]
```

### Roles & Permissions

<!-- INSTRUCTIONS:
     Define every role in your system and what each can do.
     Use a table or matrix format for clarity.
-->

| Role            | Description                              | Example Permissions              |
|-----------------|------------------------------------------|----------------------------------|
| {ROLE_1}        | {ROLE_1_DESCRIPTION}                     | {ROLE_1_PERMISSIONS}             |
| {ROLE_2}        | {ROLE_2_DESCRIPTION}                     | {ROLE_2_PERMISSIONS}             |
| {ROLE_3}        | {ROLE_3_DESCRIPTION}                     | {ROLE_3_PERMISSIONS}             |

### API Key Authentication (if applicable)

<!-- INSTRUCTIONS:
     If your app supports API keys for programmatic access, describe
     the key format, scoping rules, and validation endpoint here.
     Delete this subsection if not applicable.
-->

| Key Type        | Prefix         | Scope                            |
|-----------------|----------------|----------------------------------|
| {KEY_TYPE_1}    | {PREFIX_1}     | {SCOPE_1}                        |
| {KEY_TYPE_2}    | {PREFIX_2}     | {SCOPE_2}                        |

---

## 4. Frontend Architecture

### Component Hierarchy

<!-- INSTRUCTIONS:
     Map your component tree from the root App down to leaf components.
     Only include structural/layout components -- not every UI element.
-->

```
App.{EXT}
  |
  +--- Layout / Shell
  |      +--- Navbar
  |      +--- Sidebar (if applicable)
  |      +--- Footer
  |
  +--- Router View
         |
         +--- {VIEW_1}
         |      +--- {COMPONENT_A}
         |      +--- {COMPONENT_B}
         |
         +--- {VIEW_2}
         |      +--- {COMPONENT_C}
         |
         +--- {VIEW_N}
                +--- ...
```

### State Management

<!-- INSTRUCTIONS:
     Describe how global and local state is managed.
     Examples: Vuex/Pinia, Redux/Zustand, Context API, signals, etc.
-->

| Concern              | Approach                                 |
|----------------------|------------------------------------------|
| Global state         | {STATE_LIBRARY} (e.g. Pinia, Redux)      |
| Component state      | Local reactive state                     |
| Server cache         | {CACHE_STRATEGY} (e.g. TanStack Query)   |
| Form state           | {FORM_APPROACH}                          |

### Routing

<!-- INSTRUCTIONS:
     List top-level routes. For large apps, group by feature area.
-->

| Path                 | Component          | Auth Required | Description          |
|----------------------|--------------------|---------------|----------------------|
| `/`                  | {HOME_VIEW}        | No            | Landing / Home       |
| `/login`             | {LOGIN_VIEW}       | No            | Authentication       |
| `/{FEATURE_1}`       | {FEATURE_1_VIEW}   | Yes           | {FEATURE_1_DESC}     |
| `/{FEATURE_2}`       | {FEATURE_2_VIEW}   | Yes           | {FEATURE_2_DESC}     |
| `/admin`             | {ADMIN_VIEW}       | Yes (admin)   | Administration       |

### Internationalisation (i18n)

<!-- INSTRUCTIONS:
     If your app supports multiple languages, describe the setup.
     Delete this subsection if not applicable.
-->

- Library: {I18N_LIBRARY}
- Default locale: {DEFAULT_LOCALE}
- Translation files: `{TRANSLATIONS_PATH}`
- Supported locales: {LOCALE_LIST}

---

## 5. Backend Architecture

### Layered Pattern

<!-- INSTRUCTIONS:
     Most backends follow some variation of this layered pattern.
     Adjust the layer names to match your codebase conventions.
-->

```
Routes (URL mapping)
  |
  v
Middleware (auth, validation, rate limiting)
  |
  v
Controllers (request/response handling, HTTP concerns)
  |
  v
Providers / Services (business logic, orchestration)
  |
  v
Models ({ORM_NAME} definitions, data access)
  |
  v
Database ({DATABASE_TYPE})
```

### Directory Structure

<!-- INSTRUCTIONS:
     Show your backend directory layout. Adjust paths to match your repo.
-->

```
{BACKEND_ROOT}/
  +--- server.js              Entry point
  +--- routes/
  |      +--- index.js        Route registration
  |      +--- {feature}Routes.js
  +--- controllers/
  |      +--- {feature}Controller.js
  +--- providers/             (or services/)
  |      +--- {feature}Provider.js
  +--- models/
  |      +--- index.js        ORM initialisation + model registry
  |      +--- {Model}.js
  +--- middleware/
  |      +--- auth.js
  |      +--- errorHandler.js
  +--- config/
  +--- utils/
```

### Key Middleware

<!-- INSTRUCTIONS:
     List application-level middleware in the order they are registered.
-->

| Middleware              | Purpose                                  | Applied To      |
|-------------------------|------------------------------------------|-----------------|
| `cors()`                | Cross-origin resource sharing            | All routes      |
| `express.json()`        | JSON body parsing                        | All routes      |
| `{AUTH_MIDDLEWARE}`      | Token verification                       | Protected routes|
| `{ERROR_MIDDLEWARE}`     | Centralised error handling               | All routes      |

---

## 6. Database Architecture

### ORM Configuration

<!-- INSTRUCTIONS:
     Describe your ORM setup, connection pooling, and any multi-tenancy
     patterns you employ (row-level, schema-level, database-level).
-->

- **ORM:** {ORM_NAME}
- **Dialect:** {DATABASE_TYPE}
- **Connection:** {CONNECTION_METHOD} (e.g. connection string from env var)
- **Pool:** min {POOL_MIN}, max {POOL_MAX}
- **SSL:** {SSL_CONFIG}

### Entity Relationship Summary

<!-- INSTRUCTIONS:
     List your core tables/models, their primary relationships, and
     approximate row counts if known. For a full ERD, link to a
     separate diagram file.
-->

| Model / Table        | Key Relationships                        | Notes                  |
|----------------------|------------------------------------------|------------------------|
| {MODEL_1}            | hasMany {MODEL_2}                        |                        |
| {MODEL_2}            | belongsTo {MODEL_1}, hasMany {MODEL_3}   |                        |
| {MODEL_3}            | belongsTo {MODEL_2}                      |                        |

**Total tables:** {TABLE_COUNT}

### Multi-Tenancy Pattern (if applicable)

<!-- INSTRUCTIONS:
     Describe how data isolation between tenants is achieved.
     Common patterns: row-level filtering via tenantId FK,
     schema-per-tenant, or database-per-tenant.
     Delete this subsection if not a multi-tenant app.
-->

- **Strategy:** {TENANCY_STRATEGY} (e.g. row-level with `{TENANT_FK}` foreign key)
- **Enforcement:** {TENANCY_ENFORCEMENT} (e.g. middleware injects tenant scope)
- **Shared tables:** {SHARED_TABLES} (e.g. Users, global config)

### Migration Strategy

<!-- INSTRUCTIONS:
     Describe how schema changes are managed (ORM migrations, raw SQL,
     sync-on-boot, etc.) and any caveats.
-->

- **Tool:** {MIGRATION_TOOL}
- **Auto-sync:** {AUTO_SYNC} (e.g. `alter: true` in dev, migrations in prod)
- **Migration location:** `{MIGRATIONS_PATH}`

---

## 7. Deployment Architecture

### Build Pipeline

<!-- INSTRUCTIONS:
     Describe the steps from code push to live deployment.
-->

```
Developer pushes to {BRANCH}
  |
  v
{CI_CD_TOOL} detects change
  |
  v
Install dependencies     ({INSTALL_CMD})
  |
  v
Build frontend           ({BUILD_CMD})
  |
  v
Run tests (if configured) ({TEST_CMD})
  |
  v
Deploy to {DEPLOY_PLATFORM}
  |
  v
Health check             GET {HEALTH_ENDPOINT}
```

### Environment Variables

<!-- INSTRUCTIONS:
     List all required environment variables. NEVER include actual secrets.
     Group by concern (database, auth, email, etc.).
-->

| Variable               | Purpose                          | Example / Format             |
|------------------------|----------------------------------|------------------------------|
| `DATABASE_URL`         | Database connection string       | `postgres://user:pass@host/db`|
| `JWT_SECRET`           | Token signing key                | Random 64-char string        |
| `{ENV_VAR_1}`          | {PURPOSE_1}                      | {EXAMPLE_1}                  |
| `{ENV_VAR_2}`          | {PURPOSE_2}                      | {EXAMPLE_2}                  |
| `NODE_ENV`             | Runtime environment              | `production` / `development` |
| `PORT`                 | Server listen port               | `3000`                       |

### Infrastructure Topology

<!-- INSTRUCTIONS:
     Describe your production infrastructure. Include service names,
     regions, and any persistent storage (volumes, buckets, etc.).
-->

| Component              | Service                          | Region / Notes               |
|------------------------|----------------------------------|------------------------------|
| Application            | {DEPLOY_PLATFORM}                | {REGION}                     |
| Database               | {DB_HOST_SERVICE}                | {DB_REGION}                  |
| File Storage           | {STORAGE_SERVICE}                | {STORAGE_NOTES}              |
| Email                  | {EMAIL_PROVIDER}                 | {EMAIL_NOTES}                |
| DNS                    | {DNS_PROVIDER}                   |                              |

---

## 8. API Design

### RESTful Conventions

<!-- INSTRUCTIONS:
     Define the URL patterns and HTTP methods your API uses.
     Adjust the resource names to match your domain.
-->

| Method   | Pattern                              | Purpose                      |
|----------|--------------------------------------|------------------------------|
| `GET`    | `/api/{resource}`                    | List all (with pagination)   |
| `GET`    | `/api/{resource}/:id`                | Get single by ID             |
| `POST`   | `/api/{resource}`                    | Create new                   |
| `PUT`    | `/api/{resource}/:id`                | Full update                  |
| `PATCH`  | `/api/{resource}/:id`                | Partial update               |
| `DELETE` | `/api/{resource}/:id`                | Soft/hard delete             |

**Total endpoints:** {ENDPOINT_COUNT}

### Standard Response Format

```json
// Success
{
  "success": true,
  "data": { ... },
  "message": "{OPTIONAL_MESSAGE}"
}

// Error
{
  "success": false,
  "error": "{ERROR_CODE}",
  "message": "{HUMAN_READABLE_MESSAGE}",
  "details": { ... }
}
```

<!-- INSTRUCTIONS:
     Adjust the response envelope above to match your actual API contract.
     Some APIs use { data, meta, errors } or other conventions.
-->

### Pagination Format

<!-- INSTRUCTIONS:
     Describe how list endpoints handle pagination. Common patterns:
     offset/limit, cursor-based, or page/pageSize.
-->

```json
{
  "success": true,
  "data": [ ... ],
  "pagination": {
    "page": 1,
    "pageSize": 25,
    "totalItems": 142,
    "totalPages": 6
  }
}
```

### Error Codes

<!-- INSTRUCTIONS:
     List the HTTP status codes and application error codes your API returns.
-->

| HTTP Status | Error Code           | Meaning                              |
|-------------|----------------------|--------------------------------------|
| 400         | `VALIDATION_ERROR`   | Request body/params failed validation|
| 401         | `UNAUTHORIZED`       | Missing or invalid auth token        |
| 403         | `FORBIDDEN`          | Authenticated but insufficient role  |
| 404         | `NOT_FOUND`          | Resource does not exist              |
| 409         | `CONFLICT`           | Duplicate or state conflict          |
| 500         | `INTERNAL_ERROR`     | Unhandled server error               |

---

## 9. Known Issues / Technical Debt

<!-- INSTRUCTIONS:
     Track architectural issues, deprecated patterns, and tech debt here.
     Review and update this table at least once per release cycle.
-->

| ID   | Category       | Description                               | Severity | Status     |
|------|----------------|-------------------------------------------|----------|------------|
| TD-1 | {CATEGORY}     | {DESCRIPTION}                             | {SEV}    | {STATUS}   |
| TD-2 | {CATEGORY}     | {DESCRIPTION}                             | {SEV}    | {STATUS}   |
| TD-3 | {CATEGORY}     | {DESCRIPTION}                             | {SEV}    | {STATUS}   |

**Severity levels:** Critical, High, Medium, Low
**Status values:** Open, In Progress, Resolved, Accepted

---

## 10. Project Metrics

<!-- INSTRUCTIONS:
     Fill in current metrics. Update after each major release.
     These numbers help new developers gauge project size and complexity.
-->

| Metric                     | Value                                    |
|----------------------------|------------------------------------------|
| Database tables            | {TABLE_COUNT}                            |
| API endpoints              | {ENDPOINT_COUNT}                         |
| Frontend views/pages       | {VIEW_COUNT}                             |
| Frontend components        | {COMPONENT_COUNT}                        |
| Backend route files        | {ROUTE_FILE_COUNT}                       |
| Test coverage              | {TEST_COVERAGE}                          |
| Lines of code (approx)    | {LOC_ESTIMATE}                           |
| Supported languages (i18n) | {LOCALE_COUNT}                           |

---

## Appendix: Placeholder Reference

<!-- INSTRUCTIONS:
     This table lists every placeholder used in this template.
     Use it as a checklist when filling out the document.
     Delete this appendix once all placeholders are replaced.
-->

| Placeholder              | Description                                          |
|--------------------------|------------------------------------------------------|
| `{PROJECT_NAME}`         | Display name of the project                          |
| `{VERSION}`              | Current version number                               |
| `{DATE}`                 | Date this document was last updated (YYYY-MM-DD)     |
| `{STATUS}`               | Project status (e.g. Production, Beta, Development)  |
| `{APP_URL}`              | Production URL                                       |
| `{FRAMEWORK}`            | Frontend framework (e.g. Vue 3, React 18, Angular 17)|
| `{BUILD_TOOL}`           | Frontend build tool (e.g. Vite, Webpack, Turbopack)  |
| `{UI_LIBRARY}`           | CSS/component library (e.g. Bootstrap 5, Tailwind)   |
| `{BACKEND_FRAMEWORK}`    | Backend framework (e.g. Express 4, Fastify, Django)  |
| `{DATABASE_TYPE}`        | Database engine (e.g. PostgreSQL 17, MySQL 8, SQLite) |
| `{ORM_NAME}`             | ORM/query builder (e.g. Sequelize, Prisma, Drizzle)  |
| `{AUTH_SYSTEM}`          | Auth approach (e.g. JWT + Passport.js, NextAuth)     |
| `{EMAIL_PROVIDER}`       | Email service (e.g. SMTP2GO, SendGrid, AWS SES)      |
| `{DEPLOY_PLATFORM}`      | Hosting platform (e.g. Railway, Vercel, AWS)         |
| `{CI_CD_TOOL}`           | CI/CD system (e.g. GitHub Actions, Railway auto-deploy)|
| `{MONITORING_TOOL}`      | Monitoring/logging (e.g. Sentry, Datadog, none)      |
| `{API_PORT}`             | Default API server port                              |
| `{BACKEND_ROOT}`         | Backend source directory (e.g. server, src, backend)  |
| `{BRANCH}`               | Branch that triggers deploy (e.g. main, master)      |
| `{INSTALL_CMD}`          | Dependency install command (e.g. npm ci)             |
| `{BUILD_CMD}`            | Frontend build command (e.g. npm run build)          |
| `{TEST_CMD}`             | Test command (e.g. npm test)                         |
| `{HEALTH_ENDPOINT}`      | Health check URL (e.g. /api/health)                  |
| `{TABLE_COUNT}`          | Number of database tables                            |
| `{ENDPOINT_COUNT}`       | Number of API endpoints                              |
| `{VIEW_COUNT}`           | Number of frontend views/pages                       |
| `{COMPONENT_COUNT}`      | Number of frontend components                        |
| `{ROUTE_FILE_COUNT}`     | Number of backend route files                        |
| `{TEST_COVERAGE}`        | Test coverage percentage or description               |
| `{LOC_ESTIMATE}`         | Approximate lines of code                            |
