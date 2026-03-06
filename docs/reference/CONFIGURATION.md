# {PROJECT_NAME} — Configuration Guide

<!-- INSTRUCTIONS: Replace {PLACEHOLDER} values with project-specific details. Remove instruction comments once populated. -->

**Last Updated**: {DATE}  |  **Version**: {VERSION}  |  **URL**: {APP_URL}

---

## 1. Environment Variables

### Server / Core
| Variable | Required | Default | Description |
|----------|----------|---------|-------------|
| `PORT` | No | `3000` | HTTP listen port |
| `NODE_ENV` | Yes | `development` | `development`, `staging`, or `production` |
| `DATABASE_URL` | Yes | — | Database connection string |
| `APP_URL` | Yes | — | Public app URL (no trailing slash) |
| `API_URL` | No | `{APP_URL}/api` | API base URL if separate from app |
| `LOG_LEVEL` | No | `info` | `debug`, `info`, `warn`, `error` |
<!-- INSTRUCTIONS: For individual DB fields, replace DATABASE_URL with: DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASSWORD -->

### Authentication
| Variable | Required | Default | Description |
|----------|----------|---------|-------------|
| `JWT_SECRET` | Yes | — | JWT signing key (min 32 chars) |
| `JWT_EXPIRES_IN` | No | `24h` | Access token TTL |
| `JWT_REFRESH_EXPIRES_IN` | No | `30d` | Refresh token TTL |
| `BCRYPT_SALT_ROUNDS` | No | `12` | Password hash cost factor |
<!-- INSTRUCTIONS: For third-party auth (Firebase, Auth0), add: AUTH_PROVIDER_PROJECT_ID, AUTH_PROVIDER_CLIENT_EMAIL, AUTH_PROVIDER_PRIVATE_KEY -->

### Email (SMTP)
| Variable | Required | Default | Description |
|----------|----------|---------|-------------|
| `SMTP_HOST` | Yes | — | SMTP server hostname |
| `SMTP_PORT` | No | `587` | `587` TLS / `465` SSL / `2525` PaaS |
| `SMTP_USER` | Yes | — | SMTP username |
| `SMTP_PASSWORD` | Yes | — | SMTP password |
| `SMTP_FROM_EMAIL` | Yes | — | Default sender address |
| `SMTP_FROM_NAME` | No | `{PROJECT_NAME}` | Sender display name |
<!-- INSTRUCTIONS: For HTTP email fallback (SendGrid, Mailgun), add: EMAIL_HTTP_API_KEY -->

### External Services
<!-- INSTRUCTIONS: One row per third-party integration. Keep only what applies. -->
| Variable | Required | Default | Description |
|----------|----------|---------|-------------|
| `{SERVICE}_API_KEY` | No | — | {Service} API key |
| `SENTRY_DSN` | No | — | Error tracking DSN |
| `REDIS_URL` | No | — | Redis connection (cache/queues) |

### Feature Flags
<!-- INSTRUCTIONS: One row per flag. Values: string "true"/"false". -->
| Variable | Required | Default | Description |
|----------|----------|---------|-------------|
| `FEATURE_{NAME}` | No | `false` | {Feature description} |

---

## 2. Authentication Setup

### Token Lifecycle
| Parameter | Value | Notes |
|-----------|-------|-------|
| Algorithm | `HS256` | <!-- INSTRUCTIONS: Or RS256 for asymmetric keys --> |
| Access token TTL | `{JWT_EXPIRES_IN}` | `Authorization: Bearer` header |
| Refresh token TTL | `{JWT_REFRESH_EXPIRES_IN}` | httpOnly cookie or secure storage |
| Token rotation | {Yes/No} | Single-use refresh tokens |

### Token Payload
```jsonc
{
  "sub": "{userId}", "email": "{userEmail}", "roles": ["{role}"],
  // INSTRUCTIONS: Add custom claims (tenantId, permissions, etc.)
  "iat": 1700000000, "exp": 1700086400
}
```

### Password Policies
| Rule | Value |
|------|-------|
| Minimum length | {8} characters |
| Require uppercase / lowercase / digit / special | {Yes/No} each |
| Bcrypt salt rounds | `{BCRYPT_SALT_ROUNDS}` |
| Reset link expiry | {1 hour} |

### Authorized Domains
- `{PRODUCTION_DOMAIN}`
- `localhost:{PORT}` (development only)

---

## 3. Email Configuration

**Primary provider**: {SMTP provider name}

1. Create account and SMTP credentials
2. Set `SMTP_*` environment variables
3. Verify sender domain (SPF, DKIM, DMARC)
4. Test delivery after deploy
<!-- INSTRUCTIONS: If PaaS blocks ports 25/587/465, note the alternative (commonly 2525). -->

### Templates
**Location**: `{VOLUME_MOUNT_PATH}/config/emailTemplates.json`

| Template | Trigger | Variables |
|----------|---------|-----------|
| Welcome | Registration | `{userName}`, `{loginUrl}` |
| Password Reset | Reset request | `{resetLink}`, `{expiresIn}` |
| Invitation | Admin invite | `{inviterName}`, `{setupLink}` |
| {Name} | {Trigger} | `{var1}`, `{var2}` |

### Branding
**Location**: `{VOLUME_MOUNT_PATH}/config/emailBranding.json`

```jsonc
{
  "header": { "logoUrl": "{URL}", "backgroundColor": "{HEX}" },
  "footer": { "companyName": "{COMPANY}", "supportEmail": "{EMAIL}" },
  "colors": { "primary": "{HEX}", "text": "{HEX}", "background": "{HEX}" }
}
```

---

## 4. Role / Permission System

### Roles
| Role | Description | Assignable By |
|------|-------------|---------------|
| `super_admin` | Full system access, all tenants | System only |
| `admin` | Full tenant access | Super Admin |
| `manager` | Project management, reports | Admin |
| `member` | Standard user | Admin, Manager |
| `viewer` | Read-only | Admin, Manager |
<!-- INSTRUCTIONS: If custom roles are supported, document creation and inheritance here. -->

### Permission Matrix
| Permission | super_admin | admin | manager | member | viewer |
|------------|:-----------:|:-----:|:-------:|:------:|:------:|
| Manage users | Yes | Yes | — | — | — |
| Manage projects | Yes | Yes | Yes | — | — |
| Create items | Yes | Yes | Yes | Yes | — |
| Edit any item | Yes | Yes | Yes | — | — |
| Delete items | Yes | Yes | — | — | — |
| View reports | Yes | Yes | Yes | — | — |
| View items | Yes | Yes | Yes | Yes | Yes |
| System settings | Yes | — | — | — | — |

### Initial Admin Setup
1. Deploy the application
2. {Navigate to provisioning URL / run seed script / CLI command}
3. Create first user with `{admin_role}` role; this user invites others

---

## 5. Deployment Platform Configuration
<!-- INSTRUCTIONS: Adjust for your platform (Railway, Render, Fly.io, Vercel, AWS, etc.). -->

### Build & Start
| Setting | Value |
|---------|-------|
| Build command | `{npm run build}` |
| Start command | `{node server/app.js}` |
| Health check | `{/api/health}` |

### Persistent Storage
| Mount | Purpose |
|-------|---------|
| `{VOLUME_MOUNT_PATH}/config/` | Runtime configuration |
| `{VOLUME_MOUNT_PATH}/uploads/` | User uploads |

### Database
1. Add database plugin/addon to project
2. Map connection variables to app environment
3. Schema migrations run on startup

### CI/CD
| Step | Description |
|------|-------------|
| Trigger | Push to `{main_branch}` |
| Build | `{build_command}` |
| Migrate | {Auto on startup / manual step} |
| Deploy | {Auto-deploy / manual promotion} |

---

## 6. Security Best Practices

**Secrets Management**
- [ ] Never commit `.env` or secrets to version control
- [ ] Use platform secrets manager for production
- [ ] Rotate keys quarterly
- [ ] Separate credentials per environment

**Authentication & Access**
- [ ] Enforce strong password policies
- [ ] Account lockout after repeated failures
- [ ] Periodic role/permission review
- [ ] Disable inactive accounts

**API Security**
- [ ] Auth required on all non-public routes
- [ ] Tenant/user isolation in all queries
- [ ] Rate limiting on auth endpoints
- [ ] CORS restricted to known domains
- [ ] Validate and sanitise all input
- [ ] HTTPS enforced via redirect

**Infrastructure**
- [ ] Automated database backups
- [ ] Log monitoring and alerting
- [ ] Dependency audit on regular schedule
- [ ] WAF for public-facing services

---

## 7. Quick Reference

| Task | Where / How |
|------|-------------|
| Set env variables | {Platform dashboard / `.env`} |
| Create first admin | {Provisioning URL / seed / CLI} |
| Configure email | Set `SMTP_*` vars, verify domain |
| Manage roles | {`/admin/team`} |
| Edit email templates | {Admin UI / template files} |
| View logs | {Platform dashboard / CLI} |
| Run migrations | {Auto on startup / `npm run migrate`} |
| Toggle features | Set `FEATURE_*` variables |
| Rotate secrets | Update in dashboard, restart service |

**Key Paths**: `{VOLUME_MOUNT_PATH}/config/` (configuration) | `{VOLUME_MOUNT_PATH}/uploads/` (uploads) | `server/` (backend) | `client/` (frontend)

**Access Points**: Production `{APP_URL}` | Hosting `{PLATFORM_DASHBOARD_URL}` | Auth `{AUTH_PROVIDER_URL}` | Email `{EMAIL_PROVIDER_URL}`
