# Localization Guide — Internationalization (i18n) Standards

## Overview

This guide outlines the internationalization (i18n) standards and practices for {FRAMEWORK}-based projects. It ensures consistent translation workflows, maintainable translation keys, and proper localization of user-facing content across all views, components, and server-side messages.

<!-- INSTRUCTIONS: Replace {FRAMEWORK} with your framework name (e.g., Vue.js, React, Angular) -->
<!-- INSTRUCTIONS: Replace {DEFAULT_LOCALE} with your default language code (e.g., en, en-US) -->
<!-- INSTRUCTIONS: Replace {I18N_LIBRARY} with your library name (e.g., vue-i18n, i18next, react-intl) -->

**Default Locale:** {DEFAULT_LOCALE}
**i18n Library:** {I18N_LIBRARY}
**Translation Files Location:** `src/locales/` or `i18n/locales/`

### What Gets Localized

- **UI Text:** All labels, buttons, headings, placeholders, tooltips, and help text
- **Messages:** Error messages, validation messages, success notifications, toasts
- **Dates & Times:** Formatted according to locale conventions
- **Numbers & Currencies:** Including thousands separators and decimal formatting
- **Email Templates:** Subject lines and body text sent to users
- **PDF & Document Export Content:** Titles, headers, footers, labels

### How Translations Are Loaded

Translations are stored in JSON or YAML files organized by locale under `src/locales/`:

```
src/locales/
├── en/
│   ├── common.json
│   ├── dashboard.json
│   └── emails.json
├── es/
│   ├── common.json
│   ├── dashboard.json
│   └── emails.json
└── fr/
    ├── common.json
    ├── dashboard.json
    └── emails.json
```

The {I18N_LIBRARY} library automatically loads the appropriate language files based on the user's selected locale or browser language preferences.

---

## The Golden Rule

**Every user-facing string must go through the translation system. No hardcoded strings in templates or UI code.**

Hard-coding text makes translation impossible and fragments your translation keys across files. Always use translation keys, even in the default language.

**Bad:**
```vue
<button>{{ loading ? 'Loading...' : 'Save' }}</button>
```

**Good:**
```vue
<button>{{ loading ? $t('common.messages.loading') : $t('common.buttons.save') }}</button>
```

---

## What Requires Localization

### Checklist: Localization Requirements

#### ✓ MUST Localize

- [ ] **UI Labels & Text**
  - Button text (Save, Cancel, Delete, Submit)
  - Form labels (Name, Email, Password)
  - Headings and section titles
  - Placeholder text in inputs
  - Tab titles, menu items

- [ ] **Error Messages**
  - Validation errors (required field, invalid format)
  - API error responses
  - Server-side error messages
  - User-friendly error descriptions

- [ ] **Toast & Notification Messages**
  - Success notifications ("Bug created successfully")
  - Warning messages
  - Confirmation dialogs
  - Alert text

- [ ] **Validation Messages**
  - Field-level errors (too short, invalid email)
  - Form-level validations
  - Business rule violations

- [ ] **Email Templates**
  - Subject lines
  - Greeting and closing
  - Body text and action links
  - Call-to-action buttons

- [ ] **Dynamic Content Formatting**
  - Date formatting (locale-aware)
  - Time formatting (locale-aware)
  - Number formatting (thousand separators, decimals)
  - Currency symbols and positioning

#### ✗ DO NOT Localize

- [ ] **Log Messages** — Development/debugging only, not user-facing
- [ ] **API Field Names** — Part of data contracts, must remain consistent
- [ ] **Database Column Names** — Technical schema, not UI
- [ ] **Code Comments** — For developers, localized documentation handles this
- [ ] **Internal Configuration Keys** — Env vars, feature flags, internal settings
- [ ] **Debug/Dev-Only Output** — Console logs, dev-mode warnings

---

## Translation Key Patterns

### Hierarchical Key Structure

Use a `{view}.{section}.{element}` pattern to organize keys logically. This prevents naming collisions and makes keys self-documenting.

#### Pattern Template

```
{domain}.{feature}.{component}.{element}
```

- **domain:** Top-level scope (common, dashboard, bugTracker, admin)
- **feature:** Feature area (auth, settings, stats)
- **component:** Component name (modal, form, sidebar)
- **element:** Specific text (label, placeholder, button)

#### Example Keys (Good Hierarchy)

```json
{
  "common": {
    "buttons": {
      "save": "Save",
      "cancel": "Cancel",
      "delete": "Delete",
      "close": "Close"
    },
    "messages": {
      "loading": "Loading...",
      "error": "An error occurred",
      "success": "Operation completed successfully"
    }
  },
  "dashboard": {
    "stats": {
      "totalBugs": "Total Bugs",
      "bugsInProgress": "In Progress",
      "completionRate": "Completion Rate"
    },
    "filters": {
      "title": "Filters",
      "byStatus": "Filter by Status",
      "byPriority": "Filter by Priority"
    }
  },
  "bugTracker": {
    "form": {
      "labels": {
        "title": "Bug Title",
        "description": "Description",
        "severity": "Severity Level"
      },
      "placeholders": {
        "title": "Enter bug title...",
        "description": "Describe the issue in detail..."
      },
      "validation": {
        "titleRequired": "Title is required",
        "titleTooShort": "Title must be at least 5 characters",
        "descriptionRequired": "Description is required"
      }
    },
    "modal": {
      "deleteConfirmation": "Are you sure you want to delete this bug?",
      "deleteWarning": "This action cannot be undone."
    }
  },
  "errors": {
    "network": "Network error. Please check your connection.",
    "unauthorized": "You do not have permission to perform this action.",
    "notFound": "The requested resource was not found.",
    "validation": {
      "required": "This field is required",
      "email": "Please enter a valid email address",
      "minLength": "Must be at least {min} characters"
    }
  }
}
```

### Flat vs. Nested Approaches

<!-- INSTRUCTIONS: Choose the approach that best fits your i18n library. Most libraries support both. -->

#### Nested (Recommended for Large Projects)

Organize keys in nested objects. Better for large codebases, prevents key collisions.

```json
{
  "dashboard": {
    "stats": {
      "totalOrders": "Total Orders"
    }
  }
}
```

**Usage:** `$t('dashboard.stats.totalOrders')`

#### Flat (Recommended for Small Projects)

Use dots in key names directly. Simpler for smaller projects, easier to search.

```json
{
  "dashboard_stats_totalOrders": "Total Orders"
}
```

**Usage:** `$t('dashboard_stats_totalOrders')`

---

## Patterns by Component Type

### Page/View Components

<!-- INSTRUCTIONS: Adapt this pattern to your framework's syntax. This example uses Vue.js. -->

```vue
<template>
  <div class="dashboard">
    <h1>{{ $t('dashboard.title') }}</h1>

    <section class="stats">
      <h2>{{ $t('dashboard.stats.heading') }}</h2>
      <div class="stat-card">
        <label>{{ $t('dashboard.stats.totalBugs') }}</label>
        <span>{{ totalBugs }}</span>
      </div>
    </section>

    <section class="filters">
      <h2>{{ $t('dashboard.filters.title') }}</h2>
      <input
        :placeholder="$t('dashboard.filters.searchPlaceholder')"
        v-model="searchText"
      />
    </section>

    <button @click="refresh">
      {{ $t('common.buttons.refresh') }}
    </button>
  </div>
</template>

<script>
export default {
  data() {
    return {
      totalBugs: 0,
      searchText: ''
    }
  },
  methods: {
    refresh() {
      // Implementation
    }
  }
}
</script>
```

**Translation File (en/dashboard.json):**
```json
{
  "dashboard": {
    "title": "Dashboard",
    "stats": {
      "heading": "Statistics",
      "totalBugs": "Total Bugs",
      "bugsResolved": "Bugs Resolved"
    },
    "filters": {
      "title": "Filters",
      "searchPlaceholder": "Search bugs..."
    }
  }
}
```

### Modal Components

```vue
<template>
  <div class="modal" v-if="isOpen">
    <div class="modal-header">
      <h2>{{ $t('bugModal.deleteConfirmation.title') }}</h2>
      <button @click="close">{{ $t('common.buttons.close') }}</button>
    </div>

    <div class="modal-body">
      <p>{{ $t('bugModal.deleteConfirmation.message') }}</p>
      <p class="warning">{{ $t('bugModal.deleteConfirmation.warning') }}</p>
    </div>

    <div class="modal-footer">
      <button @click="cancel">{{ $t('common.buttons.cancel') }}</button>
      <button @click="confirm" class="danger">
        {{ $t('common.buttons.delete') }}
      </button>
    </div>
  </div>
</template>

<script>
export default {
  props: ['isOpen'],
  methods: {
    close() { this.$emit('close'); },
    cancel() { this.$emit('cancel'); },
    confirm() { this.$emit('confirm'); }
  }
}
</script>
```

**Translation File (en/common.json):**
```json
{
  "bugModal": {
    "deleteConfirmation": {
      "title": "Delete Bug",
      "message": "Are you sure you want to delete this bug?",
      "warning": "This action cannot be undone."
    }
  }
}
```

### Shared/Common Components

Create a `common.json` file for shared UI elements used across views:

```json
{
  "common": {
    "buttons": {
      "save": "Save",
      "cancel": "Cancel",
      "delete": "Delete",
      "close": "Close",
      "submit": "Submit",
      "refresh": "Refresh"
    },
    "messages": {
      "loading": "Loading...",
      "success": "Operation completed successfully",
      "error": "An error occurred",
      "noResults": "No results found"
    },
    "pagination": {
      "previous": "Previous",
      "next": "Next",
      "page": "Page {page} of {total}"
    }
  }
}
```

### Server-Side Messages

For error messages and email content generated on the server, use a server-side i18n library or store translations in the database:

**Express.js + i18next Example:**

```javascript
const i18next = require('i18next');
const Backend = require('i18next-fs-backend');

i18next.use(Backend).init({
  fallbackLng: '{DEFAULT_LOCALE}',
  ns: ['common', 'emails'],
  defaultNS: 'common',
  backend: {
    loadPath: './locales/{{lng}}/{{ns}}.json'
  }
});

// In a route handler
app.post('/api/bugs', (req, res) => {
  if (!req.body.title) {
    return res.status(400).json({
      error: i18next.t('errors.validation.required', {
        lng: req.user.locale || '{DEFAULT_LOCALE}'
      })
    });
  }
  // Create bug...
});

// Email example
function sendBugNotification(user, bug) {
  const locale = user.locale || '{DEFAULT_LOCALE}';
  const subject = i18next.t('emails.bugCreated.subject', { lng: locale });
  const body = i18next.t('emails.bugCreated.body', {
    lng: locale,
    bugTitle: bug.item
  });
  // Send email...
}
```

---

## Adding a New Language

### Step-by-Step Guide

1. **Create Language Directory**
   ```bash
   mkdir -p src/locales/{{NEW_LANG_CODE}}/
   ```

2. **Copy English Translation Files**
   ```bash
   cp src/locales/en/*.json src/locales/{{NEW_LANG_CODE}}/
   ```

3. **Translate All JSON Files**
   <!-- INSTRUCTIONS: Provide translation files to a professional translator or use CAT tools. -->

   Translate the values (not keys) in each JSON file:

   **Before (en.json):**
   ```json
   {
     "dashboard": {
       "title": "Dashboard",
       "stats": { "totalBugs": "Total Bugs" }
     }
   }
   ```

   **After (es.json — Spanish):**
   ```json
   {
     "dashboard": {
       "title": "Panel de Control",
       "stats": { "totalBugs": "Bugs Totales" }
     }
   }
   ```

4. **Register Language in i18n Configuration**

   Update your i18n init file:

   ```javascript
   // i18n.js or locales.js
   import en from './locales/en/common.json';
   import es from './locales/es/common.json';
   import fr from './locales/fr/common.json';

   const messages = {
     en, es, fr
   };

   export default messages;
   ```

5. **Add Language to Language Selector UI**

   ```vue
   <select v-model="$i18n.locale">
     <option value="en">English</option>
     <option value="es">Español</option>
     <option value="fr">Français</option>
   </select>
   ```

6. **Test All Pages in New Language**
   - Check all views and modals
   - Verify RTL languages render correctly (if applicable)
   - Test date/number formatting

7. **Document Language Addition**
   - Update README with supported languages
   - Note any incomplete translations
   - Add language to deployment checklist

---

## Common Mistakes & How to Avoid Them

### ❌ Mistake 1: Concatenating Translated Strings

**Problem:** Strings change meaning or order in different languages.

```vue
<!-- BAD: Works in English, breaks in other languages -->
{{ $t('common.labels.greeting') }} {{ userName }}!
```

Translation: "Hello John!" (English) vs. "¡Hola Juan!" (Spanish) — order is same, but some languages might reverse.

**Solution:** Use placeholder substitution in the translation key.

```vue
<!-- GOOD: Translation handles word order -->
{{ $t('common.labels.greeting', { name: userName }) }}
```

**Translation (en.json):**
```json
{ "greeting": "Hello {name}!" }
```

**Translation (es.json):**
```json
{ "greeting": "¡Hola {name}!" }
```

### ❌ Mistake 2: Hardcoding Plurals

**Problem:** English uses simple singular/plural, other languages have complex rules.

```vue
<!-- BAD: Only works for English -->
{{ count }} bug{{ count !== 1 ? 's' : '' }} found
```

**Solution:** Use i18n pluralization rules.

```vue
<!-- GOOD: Framework handles plural rules -->
{{ $t('search.results', { count: bugCount }) }}
```

**Translation (en.json):**
```json
{
  "search": {
    "results": "0 bugs found | 1 bug found | {count} bugs found"
  }
}
```

**Translation (ja.json):**
```json
{
  "search": {
    "results": "{count}件のバグが見つかりました"
  }
}
```

### ❌ Mistake 3: Assuming Left-to-Right (LTR)

**Problem:** Arabic, Hebrew, and other RTL languages display incorrectly.

```vue
<!-- BAD: Assumes LTR layout -->
<div class="row">
  <div class="icon">→</div>
  <div class="label">{{ $t('common.next') }}</div>
</div>
```

**Solution:** Use CSS logical properties and auto-flip icons for RTL.

```vue
<!-- GOOD: Works for both LTR and RTL -->
<div class="row" :dir="isRTL ? 'rtl' : 'ltr'">
  <div class="icon" :class="isRTL ? 'flipped' : ''">→</div>
  <div class="label">{{ $t('common.next') }}</div>
</div>
```

**CSS:**
```css
.row {
  display: flex;
  flex-direction: row;
  /* Use logical properties for compatibility */
  margin-inline-start: 1rem;
}

.icon.flipped {
  transform: scaleX(-1);
}
```

### ❌ Mistake 4: Date Format Assumptions

**Problem:** Hardcoding date formats like "MM/DD/YYYY" breaks for locales.

```vue
<!-- BAD: Only works for US date format -->
{{ bug.createdAt.toLocaleDateString() }}
<!-- Output: 2/23/2026 (US) or 23/2/2026 (EU) — inconsistent! -->
```

**Solution:** Use locale-aware date formatting.

```vue
<!-- GOOD: Uses browser/user locale -->
{{ formatDate(bug.createdAt) }}
```

**JavaScript:**
```javascript
function formatDate(date, locale = 'en-US') {
  return new Intl.DateTimeFormat(locale, {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  }).format(date);
}

// Or use a library like date-fns with locales
import { format } from 'date-fns';
import { es, fr } from 'date-fns/locale';

const locale = { es, fr }[userLanguage] || undefined;
format(bug.createdAt, 'PPP', { locale });
```

### ❌ Mistake 5: Hardcoding Number Formatting

**Problem:** Different locales use different thousand/decimal separators.

```javascript
// BAD: Only works for US numbers
const price = (1234.56).toFixed(2); // "1234.56"
```

**Solution:** Use Intl.NumberFormat.

```javascript
// GOOD: Respects locale conventions
const formatter = new Intl.NumberFormat('en-US', {
  style: 'currency',
  currency: 'USD'
});
formatter.format(1234.56); // "$1,234.56" (US)

const formatterDE = new Intl.NumberFormat('de-DE', {
  style: 'currency',
  currency: 'EUR'
});
formatterDE.format(1234.56); // "1.234,56 €" (Germany)
```

---

## Summary

- **Always use translation keys** — never hardcode user-facing strings
- **Follow the key hierarchy pattern** — `{domain}.{section}.{element}`
- **Use placeholders, not concatenation** — preserves word order across languages
- **Leverage framework pluralization** — don't hardcode singular/plural logic
- **Consider RTL, dates, and numbers** — use locale-aware formatting
- **Test new languages thoroughly** — before deploying to users

For questions, refer to your {I18N_LIBRARY} documentation or contact the internationalization team.
