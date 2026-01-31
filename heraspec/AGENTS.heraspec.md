# Demo Builder - HeraSpec Agent Instructions

> **Plugin Name:** Demo Builder  
> **Version:** 1.0.0  
> **Platform:** WordPress 6.x+

## Overview

This document contains HeraSpec change proposals for developing the Demo Builder WordPress plugin. Each proposal follows WordPress coding standards and includes both English and Vietnamese descriptions.

## Development Standards

### Required Skills
- `skill:plugin-standard` - WordPress Plugin Handbook guidelines
- `skill:plugin-check` - Pass WordPress Plugin Check tool
- `skill:plugin-directory` - Meet WordPress.org repository requirements

### UI/UX Standards
- TailwindCSS 3.x with `db-` prefix
- Pastel color palette (light tones)
- Modern, elegant design

---

## Proposed Changes

### 01 - Core Foundation
**File:** `heraspec/specs/01-core-foundation.md`
**Priority:** High | **Complexity:** Medium

Setup plugin structure with TailwindCSS, database tables, and admin menu.

---

### 02 - Backup System
**File:** `heraspec/specs/02-backup-system.md`
**Priority:** High | **Complexity:** High

Database and source code backup with smart exclusion options.

---

### 03 - Restore System
**File:** `heraspec/specs/03-restore-system.md`
**Priority:** High | **Complexity:** High

Manual and scheduled auto-restore with countdown timer.

---

### 04 - Demo Accounts
**File:** `heraspec/specs/04-demo-accounts.md`
**Priority:** Medium | **Complexity:** Medium

Demo account management with login form integration.

---

### 05 - Permission Restrictions
**File:** `heraspec/specs/05-permissions.md`
**Priority:** Medium | **Complexity:** Medium

Permission restrictions for demo accounts.

---

### 06 - Cloud Storage Extensions
**File:** `heraspec/specs/06-cloud-extensions.md`
**Priority:** Low | **Complexity:** High

Google Drive and OneDrive integration in extensions folder.

---

### 07 - Telegram Notifications
**File:** `heraspec/specs/07-notifications.md`
**Priority:** Low | **Complexity:** Low

Telegram bot notifications for backup/restore events.

---

### 08 - Maintenance Mode
**File:** `heraspec/specs/08-maintenance-mode.md`
**Priority:** Low | **Complexity:** Low

Maintenance mode with custom page.

---

## Implementation Order

1. **Phase 1:** 01-core-foundation
2. **Phase 2:** 02-backup-system
3. **Phase 3:** 03-restore-system
4. **Phase 4:** 04-demo-accounts, 05-permissions
5. **Phase 5:** 06-cloud-extensions
6. **Phase 6:** 07-notifications, 08-maintenance-mode

## References

- [project.md](file:///Applications/XAMPP/xamppfiles/htdocs/polyxgo/wp-content/plugins/demo-builder/heraspec/project.md)
- [project_vi.md](file:///Applications/XAMPP/xamppfiles/htdocs/polyxgo/wp-content/plugins/demo-builder/heraspec/project_vi.md)

---

### 09 - Performance Optimization
**File:** `heraspec/specs/09-performance-optimization.md`
**Priority:** High | **Complexity:** High

Optimization for large databases and files with chunked processing, streaming, and batch operations.

---

### 10 - UI/UX Standards & Libraries
**File:** `heraspec/specs/10-ui-ux-standards.md`
**Priority:** High | **Complexity:** Low

SweetAlert2 pastel theme, strict no inline CSS/JS rules, jQuery and Vue.js requirements.

> ⚠️ **CRITICAL RULES:**
> - ❌ TUYỆT ĐỐI KHÔNG dùng inline CSS (`style=""`) hoặc inline JS (`onclick=""`)
> - ✅ SweetAlert2 cho tất cả confirm, alert, modal, popup
> - ✅ jQuery cho DOM manipulation
> - ✅ Vue.js 3 cho two-way binding và reactive UI

---

## Progress Tracking Rules

> ⚠️ **MANDATORY:** Follow these rules to maintain accurate progress tracking.

### PROGRESS.md Workflow

**Location:** `/PROGRESS.md` (root of plugin)

**When to Update:**
1. After completing implementation of each spec
2. When starting work on a new spec
3. When context changes (new session/conversation)

### Update Actions

**Before Starting a Spec:**
```markdown
| 01 | spec.md | Feature Name | 🔄 In Progress | - | Notes... |
```

**After Completing a Spec:**
```markdown
| 01 | spec.md | Feature Name | ✅ Done | ~XX,000 | Notes... |
```

**Token Estimation:**
- Count approximate tokens used during implementation
- Include planning, coding, testing, and fixes
- Update Implementation History table

### Context Change Protocol

When starting a new conversation/context:
1. **Read** `PROGRESS.md` first
2. **Check** current status of all features
3. **Continue** from last incomplete spec
4. **Update** status when resuming work

### Sample Update Commit
```bash
git add PROGRESS.md
git commit -m "Update progress: [SPEC_NAME] completed (~XX,XXX tokens)"
```

---

## Quick Commands

```bash
# View current progress
cat PROGRESS.md

# Update and commit progress
git add PROGRESS.md && git commit -m "Progress update: [description]"

# Push to GitHub
git push origin main
```
