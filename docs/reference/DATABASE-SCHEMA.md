# Database Schema Reference

<!-- INSTRUCTIONS: Replace all {PLACEHOLDERS} with project-specific values. Remove all INSTRUCTIONS comments once populated. -->

**Last Updated**: {DATE} | **Version**: {VERSION} | **Database**: {DATABASE_TYPE} {DATABASE_VERSION} | **ORM**: {ORM_NAME} {ORM_VERSION} | **Tables**: {TABLE_COUNT}

---

## 1. Overview

{PROJECT_NAME} uses **{DATABASE_TYPE}** managed through **{ORM_NAME}**.

| Feature        | Implementation           |
|----------------|--------------------------|
| Multi-tenancy  | {TENANCY_STRATEGY}       |
| Soft deletes   | {SOFT_DELETE_STRATEGY}   |
| Audit logging  | {AUDIT_STRATEGY}         |
| Authentication | {AUTH_STRATEGY}          |
| Hosting        | {HOSTING_PROVIDER}       |

<!-- INSTRUCTIONS: TENANCY_STRATEGY: "Row-level via tenantId FK" / "Schema-per-tenant" / "Single-tenant"
     SOFT_DELETE_STRATEGY: "paranoid mode (deletedAt)" / "isDeleted flag" / "Hard deletes only"
     AUDIT_STRATEGY: "Unified history table" / "Per-table shadow tables" / "Not implemented" -->

**Conventions**: PKs = `id` (auto-increment or UUID). Timestamps = `createdAt`/`updatedAt`/`deletedAt`. FKs = `{table}Id`. Enums = {ENUM_STRATEGY}.

---

## 2. Table Structure

<!-- INSTRUCTIONS: Replace {ENTITY_N} with actual entity names. Duplicate or remove entries as needed. -->

### 2.1 Core Tables

```sql
-- Standard CREATE TABLE pattern (adapt per table)
CREATE TABLE IF NOT EXISTS "{ENTITY_1_TABLE}" (
    "id"        SERIAL PRIMARY KEY,
    "tenantId"  INTEGER NOT NULL REFERENCES "{TENANT_TABLE}"("id"),
    "title"     VARCHAR(255) NOT NULL,
    "status"    VARCHAR(50) NOT NULL DEFAULT '{DEFAULT_STATUS}',
    "createdBy" INTEGER REFERENCES "{USER_TABLE}"("id"),
    "createdAt" TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW(),
    "updatedAt" TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW(),
    "deletedAt" TIMESTAMP WITH TIME ZONE
);
```

#### {ENTITY_1} (`{ENTITY_1_TABLE}`) -- {ENTITY_1_DESCRIPTION}

| Column     | Type         | Constraints                    | Notes           |
|------------|--------------|--------------------------------|-----------------|
| id         | INTEGER      | PK, auto-increment             |                 |
| tenantId   | INTEGER      | FK -> {TENANT_TABLE}, NOT NULL | Isolation key   |
| title      | VARCHAR(255) | NOT NULL                       |                 |
| status     | VARCHAR(50)  | NOT NULL, DEFAULT              | {STATUS_VALUES} |
| {FK_FIELD} | INTEGER      | FK -> {FK_TABLE}               |                 |
| createdAt  | TIMESTAMPTZ  | NOT NULL                       | ORM-managed     |
| updatedAt  | TIMESTAMPTZ  | NOT NULL                       | ORM-managed     |
| deletedAt  | TIMESTAMPTZ  |                                | Soft delete     |

#### {ENTITY_2} (`{ENTITY_2_TABLE}`) -- {ENTITY_2_DESCRIPTION}

| Column    | Type     | Constraints                    | Notes |
|-----------|----------|--------------------------------|-------|
| id        | INTEGER  | PK                             |       |
| tenantId  | INTEGER  | FK -> {TENANT_TABLE}, NOT NULL |       |
| {FIELD_1} | {TYPE_1} | {CONSTRAINTS_1}                |       |
| {FIELD_2} | {TYPE_2} | {CONSTRAINTS_2}                |       |

<!-- INSTRUCTIONS: Duplicate the block above for each core entity (typically 3-8). -->

### 2.2 Supporting Tables

#### {JOIN_TABLE} (`{JOIN_TABLE_NAME}`) -- M:N between {ENTITY_A} and {ENTITY_B}

| Column       | Type    | Constraints                      | Notes                    |
|--------------|---------|----------------------------------|--------------------------|
| {ENTITY_A}Id | INTEGER | FK -> {ENTITY_A_TABLE}, NOT NULL | Composite unique w/ next |
| {ENTITY_B}Id | INTEGER | FK -> {ENTITY_B_TABLE}, NOT NULL |                          |

#### {CONFIG_TABLE} (`{CONFIG_TABLE_NAME}`) -- Key-value config store

| Column   | Type         | Constraints  | Notes                |
|----------|--------------|--------------|----------------------|
| id       | INTEGER      | PK           |                      |
| tenantId | INTEGER      | FK, nullable | NULL = global config |
| key      | VARCHAR(255) | NOT NULL     |                      |
| value    | JSONB / TEXT | NOT NULL     |                      |

### 2.3 System Tables

#### Audit / History (`{AUDIT_TABLE}`)

| Column     | Type        | Constraints        | Notes                       |
|------------|-------------|--------------------|-----------------------------|
| id         | INTEGER     | PK                 |                             |
| entityType | VARCHAR(50) | NOT NULL           | Table/model name            |
| entityId   | INTEGER     | NOT NULL           | PK of changed record        |
| eventType  | VARCHAR(50) | NOT NULL           | created / updated / deleted |
| actorId    | INTEGER     | FK -> {USER_TABLE} | Who did it                  |
| data       | JSONB       |                    | Before/after snapshot       |
| createdAt  | TIMESTAMPTZ | NOT NULL           | When                        |

#### Sessions (`{SESSION_TABLE}`)

| Column    | Type        | Constraints | Notes     |
|-----------|-------------|-------------|-----------|
| sid       | VARCHAR(36) | PK          |           |
| data      | JSONB       | NOT NULL    |           |
| expiresAt | TIMESTAMPTZ |             | Cleanup   |

#### Migrations (`{MIGRATION_TABLE}`) -- ORM-managed

| Column    | Type         | Constraints |
|-----------|--------------|-------------|
| name      | VARCHAR(255) | PK          |
| appliedAt | TIMESTAMPTZ  |             |

---

## 3. Relationships

```
{TENANT_TABLE}              {USER_TABLE}
  id (PK) ──┐                id (PK)
             │                tenantId (FK) ──┐
             │          1:M       │           │
             │                    ▼           │
             │          {ENTITY_1_TABLE}      │
             └───────── tenantId (FK) ────────┘
                          id (PK)
                          createdBy (FK) → {USER_TABLE}
                              │
            ┌─────────────────┼──────────────┐
            │ 1:M             │ 1:M          │ M:N
            ▼                 ▼              ▼
      {ENTITY_2}        {AUDIT_TABLE}   {JOIN_TABLE}
```

| Parent             | Child              | Type | FK Column             |
|--------------------|--------------------|------|-----------------------|
| {TENANT_TABLE}     | {USER_TABLE}       | 1:M  | tenantId              |
| {USER_TABLE}       | {ENTITY_1_TABLE}   | 1:M  | createdBy             |
| {ENTITY_1_TABLE}   | {ENTITY_2_TABLE}   | 1:M  | {ENTITY_1}Id          |
| {ENTITY_1_TABLE}   | {ENTITY_3_TABLE}   | M:N  | via {JOIN_TABLE}      |
| {ENTITY_1_TABLE}   | {AUDIT_TABLE}      | 1:M  | entityId + entityType |

---

## 4. Indexes

| Table            | Columns                     | Type   | Purpose                   |
|------------------|-----------------------------|--------|---------------------------|
| {ENTITY_1_TABLE} | tenantId, status            | B-tree | Scoped listing            |
| {ENTITY_1_TABLE} | createdBy                   | B-tree | "My items" queries        |
| {ENTITY_2_TABLE} | {ENTITY_1}Id                | B-tree | Child lookups             |
| {USER_TABLE}     | email                       | Unique | Login                     |
| {AUDIT_TABLE}    | entityType, entityId        | B-tree | Per-record audit trail    |
| {JOIN_TABLE}     | {ENTITY_A}Id, {ENTITY_B}Id  | Unique | No duplicate associations |

<!-- INSTRUCTIONS: FK columns need indexes (some ORMs auto-create). Consider partial indexes
     (WHERE "deletedAt" IS NULL) and GIN indexes for JSONB columns. -->

---

## 5. Data Isolation Pattern

<!-- INSTRUCTIONS: Remove this section if single-tenant. -->

All tenant-scoped tables include `tenantId` FK. Every query MUST filter by `tenantId`.

```javascript
app.use((req, res, next) => { req.tenantId = req.user?.tenantId; next(); });
```

<!-- INSTRUCTIONS: Replace with your enforcement: default scopes, middleware, or DB-level RLS. -->

**Exempt tables**: `{MIGRATION_TABLE}` (infra), `{SESSION_TABLE}` (refs user), `{SYSTEM_CONFIG}` (global).

---

## 6. Migrations

**Convention**: `{MIGRATION_DIR}/{TIMESTAMP}-{description}.{EXT}`

```sql
DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns
        WHERE table_name = '{TABLE}' AND column_name = '{COLUMN}')
    THEN ALTER TABLE "{TABLE}" ADD COLUMN "{COLUMN}" {TYPE} {CONSTRAINTS};
    END IF;
END $$;
```

<!-- INSTRUCTIONS: For ORM migrations, replace with your ORM's format (queryInterface, knex.schema, etc). -->

**Rules**: Never edit applied migrations. Always write UP + DOWN. Use idempotent checks. Create parents before children. Test against production copy.

---

## 7. Query Examples

```javascript
// Eager loading
const results = await {ENTITY_1_MODEL}.findAll({
  where: { tenantId: req.tenantId, status: '{ACTIVE_STATUS}' },
  include: [{ model: {ENTITY_2_MODEL}, as: '{ALIAS}' },
            { model: {USER_MODEL}, as: 'creator', attributes: ['id', '{DISPLAY_NAME}'] }],
  order: [['createdAt', 'DESC']]
});

// Pagination
const page = parseInt(req.query.page) || 1, limit = parseInt(req.query.limit) || 25;
const { count, rows } = await {ENTITY_1_MODEL}.findAndCountAll({
  where: { tenantId: req.tenantId }, limit, offset: (page - 1) * limit
});

// Search
const { Op } = require('{ORM_PACKAGE}');
const where = { tenantId: req.tenantId };
if (req.query.search) where[Op.or] = [
  { title: { [Op.iLike]: `%${req.query.search}%` } },
  { description: { [Op.iLike]: `%${req.query.search}%` } }
];

// Transaction
const t = await sequelize.transaction();
try {
  const entity = await {ENTITY_1_MODEL}.create({ tenantId, title }, { transaction: t });
  await {AUDIT_MODEL}.create({ entityType: '{ENTITY_1}', entityId: entity.id,
    eventType: 'created', actorId: req.user.id }, { transaction: t });
  await t.commit();
} catch (e) { await t.rollback(); throw e; }
```

---

## 8. Best Practices

| Element      | Convention         | Example             |
|--------------|--------------------|---------------------|
| Tables       | {TABLE_CASE}       | `{EXAMPLE_TABLE}`   |
| Columns      | {COLUMN_CASE}      | `{EXAMPLE_COL}`     |
| Foreign keys | {FK_PATTERN}       | `{EXAMPLE_FK}`      |
| Indexes      | idx_{table}_{cols} | `idx_users_email`   |

<!-- INSTRUCTIONS: TABLE_CASE: PascalCase plural / snake_case. COLUMN_CASE: camelCase / snake_case. -->

- DB-level constraints (NOT NULL, UNIQUE, CHECK) alongside ORM validation
- FK ON DELETE: `CASCADE` (children), `SET NULL` (optional), `RESTRICT` (prevent orphans)
- Transactions for multi-table writes
- Validate enums at both app and DB level
- Every FK gets an index; composite indexes ordered by selectivity

---

## 9. Troubleshooting

| Symptom                        | Cause                    | Resolution                                |
|--------------------------------|--------------------------|-------------------------------------------|
| `UniqueConstraintError`        | Duplicate on unique col  | `findOrCreate`; check before insert       |
| `ForeignKeyConstraintError`    | Parent record missing    | Verify parent exists; check tenantId      |
| `relation does not exist`      | Migration not applied    | Run `{MIGRATE_COMMAND}`                   |
| `column does not exist`        | Model/DB mismatch        | Compare model definition with schema      |
| Slow queries                   | Missing index            | `EXPLAIN ANALYZE`; add index              |
| Stale enum after migration     | Connection pool caching  | Restart pool                              |
| `deadlock detected`            | Concurrent TX same rows  | Reduce lock scope; add retry logic        |
| Pool exhausted                 | Leaked / uncommitted TX  | Audit transactions; increase pool size    |
| Wrong tenant data              | Missing tenantId filter  | Audit query WHERE clause                  |
| Soft-deleted rows appearing    | Paranoid scope bypassed  | Check for `unscoped()` calls              |

<!-- INSTRUCTIONS: Replace error names with your ORM's classes. Replace {MIGRATE_COMMAND}. -->

---

*Template -- replace all `{PLACEHOLDERS}` and remove `<!-- INSTRUCTIONS -->` comments before use.*
