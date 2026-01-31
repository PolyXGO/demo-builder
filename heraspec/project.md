# Demo Builder for WordPress - Project Specification

> **Version:** 1.0.0
> **Last Updated:** 2026-01-31
> **Platform:** WordPress 6.x+
> **Reference:** Perfex CRM Demo Builder Module

## 1. Overview

Demo Builder for WordPress is a comprehensive plugin designed to manage demo sites effectively. It provides automated backup/restore functionality, demo account management with permission restrictions, and scheduled maintenance for demo environments.

### Core Objectives

1. **Database & Source Code Backup** - Full backup capability for WordPress database and source code
2. **Automated Restore** - Scheduled auto-restore at configurable intervals
3. **Demo Account Management** - Create and manage demo user accounts with limited permissions
4. **Permission Restrictions** - Protect critical operations from demo users
5. **Telegram Notifications** - Real-time notifications for backup/restore events
6. **Countdown Timer** - Visual indicator showing time until next restore
7. **Cloud Storage** - Auto-backup to OneDrive, Google Drive (via extensions)

---

## 2. Development Standards & Skills

### 2.1 UI/UX Design Standards

#### 2.1.1 TailwindCSS Integration

- Use TailwindCSS 3.x for all UI components
- Implement through WordPress enqueue system
- Prefix all classes to avoid conflicts: `db-` (demo-builder)

#### 2.1.2 Design Theme: Modern Pastel Elegance

```css
/* Color Palette - Light Pastel Tones */
--db-primary: #A5B4FC;      /* Pastel Indigo */
--db-secondary: #C4B5FD;    /* Pastel Purple */
--db-success: #86EFAC;      /* Pastel Green */
--db-warning: #FDE68A;      /* Pastel Yellow */
--db-danger: #FCA5A5;       /* Pastel Red */
--db-info: #7DD3FC;         /* Pastel Blue */
--db-bg-primary: #F8FAFC;   /* Light Gray */
--db-bg-secondary: #F1F5F9; /* Slate 100 */
--db-text-primary: #334155; /* Slate 700 */
--db-text-secondary: #64748B; /* Slate 500 */
--db-border: #E2E8F0;       /* Slate 200 */
```

#### 2.1.3 UI Components

- **Cards**: Rounded corners (12px), subtle shadows, hover effects
- **Buttons**: Pill-shaped or rounded, gradient backgrounds
- **Forms**: Floating labels, smooth focus transitions
- **Tables**: Striped rows, sticky headers, sortable columns
- **Modals**: Backdrop blur, slide-in animations
- **Alerts**: Soft backgrounds, subtle borders

### 2.2 WordPress Standards (Skills to Apply)

#### 2.2.1 Plugin Standards (`skill:plugin-standard`)

- Follow WordPress Plugin Handbook guidelines
- Proper file organization and naming conventions
- Secure coding practices (nonces, capabilities, sanitization)
- Internationalization (i18n) ready
- Proper hooks and filters architecture

#### 2.2.2 Plugin Check (`skill:plugin-check`)

- Pass WordPress Plugin Check tool
- No PHP errors or warnings
- Proper escaping of output
- Valid readme.txt format
- Correct plugin headers

#### 2.2.3 Plugin Directory Ready (`skill:plugin-directory`)

- Meet WordPress.org plugin repository requirements
- GPL-compatible licensing
- No external dependencies without disclosure
- Proper asset handling
- Security best practices

---

## 3. Feature Specifications

### 3.1 Backup System

#### 3.1.1 Database Backup

- Export complete WordPress database to SQL file
- Support for table exclusion (e.g., session tables, logs)
- Compression support (ZIP format)
- Portable backup option (remove domain-specific URLs)

#### 3.1.2 Source Code Backup

- Configurable directories to include:
  - `wp-content/uploads/` - Media files
  - `wp-content/themes/` - Theme files
  - `wp-content/plugins/` - Plugin files (excluding demo-builder itself)
- Directory size calculation and display
- Selective directory toggling

#### 3.1.3 Smart Exclusion Options

- **Plugin Exclusions:**

  - [ ] Exclude inactive plugins
  - [ ] Exclude specific plugins (multi-select)
  - [ ] Exclude plugin data directories
- **Theme Exclusions:**

  - [ ] Exclude inactive themes
  - [ ] Exclude specific themes (multi-select)
- **Data Exclusions:**

  - [ ] Exclude post revisions
  - [ ] Exclude spam comments
  - [ ] Exclude transients
  - [ ] Exclude session tables
  - [ ] Exclude activity/audit logs
  - [ ] Exclude cache tables
  - [ ] Exclude WooCommerce sessions (if applicable)
  - [ ] Custom table exclusion list
- **File Exclusions:**

  - [ ] Exclude cache directories (`/cache/`, `/wp-content/cache/`)
  - [ ] Exclude backup directories (prevent recursion)
  - [ ] Exclude log files (`*.log`, `debug.log`)
  - [ ] Exclude temporary files (`*.tmp`, `*.bak`)
  - [ ] Custom file pattern exclusion

#### 3.1.4 Backup Management

- List all backups with metadata:
  - Backup name
  - Created date/time
  - File size (total, database, directories)
  - Created by user
  - Status (available/missing)
  - Cloud sync status
- Download backup files
- Delete individual backups
- Upload external backup files
- Clean orphaned backup files

#### 3.1.5 Backup Storage

- Custom database table: `{prefix}demobuilder_backups`
- Backup files stored in: `wp-content/backups/demo-builder/`

---

### 3.2 Restore System

#### 3.2.1 Manual Restore

- Select backup from list
- Confirmation dialog with warnings
- Restore process steps:
  1. Enable maintenance mode
  2. Restore database
  3. Restore source files (if included)
  4. Clear WordPress cache
  5. Disable maintenance mode

#### 3.2.2 Scheduled Auto-Restore

- **Schedule Options (Dropdown):**

  - Every 30 minutes
  - Every 60 minutes (1 hour)
  - Every 120 minutes (2 hours)
  - Every 24 hours (1 day)
  - Every 48 hours (2 days)
  - Every 72 hours (3 days)
  - Every 7 days (1 week)
  - Every 14 days (2 weeks)
  - Custom hours (numeric input)
- **Configuration Options:**

  - Enable/disable auto-restore toggle
  - Select source backup
  - Auto-backup after restore option
  - Last restore timestamp display
  - Next restore timestamp display

#### 3.2.3 WP-Cron Integration

- Hook into WordPress cron system
- Check schedule timing before execution
- Update timestamps after completion
- Log all restore activities

---

### 3.3 Cloud Storage Extensions

> **Location:** `demo-builder/extensions/`

#### 3.3.1 Extension Architecture

```
demo-builder/
└── extensions/
    ├── cloud-storage-base/       # Base abstract class
    │   ├── class-cloud-storage.php
    │   └── interface-cloud-provider.php
    ├── google-drive/             # Google Drive integration
    │   ├── google-drive.php
    │   ├── class-google-drive-provider.php
    │   ├── assets/
    │   │   └── admin.js
    │   └── views/
    │       └── settings.php
    └── onedrive/                 # OneDrive integration
        ├── onedrive.php
        ├── class-onedrive-provider.php
        ├── assets/
        │   └── admin.js
        └── views/
            └── settings.php
```

#### 3.3.2 Google Drive Extension

- **Configuration:**

  - OAuth 2.0 authentication
  - Client ID & Secret setup
  - Folder selection/creation
  - Auto-sync toggle
- **Features:**

  - Manual upload to Google Drive
  - Auto-upload after backup
  - Sync status indicator
  - Download from Google Drive
  - Delete old cloud backups (retention policy)

#### 3.3.3 OneDrive Extension

- **Configuration:**

  - Microsoft OAuth authentication
  - App registration guide
  - Folder selection/creation
  - Auto-sync toggle
- **Features:**

  - Manual upload to OneDrive
  - Auto-upload after backup
  - Sync status indicator
  - Download from OneDrive
  - Delete old cloud backups (retention policy)

#### 3.3.4 Extension Interface

```php
interface Cloud_Provider_Interface {
    public function authenticate();
    public function upload($file_path, $remote_name);
    public function download($remote_name, $local_path);
    public function delete($remote_name);
    public function list_files($folder = '');
    public function get_quota();
    public function is_connected();
}
```

---

### 3.4 Countdown Timer

#### 3.4.1 Display Options

- **Positions:**

  - Fixed position (custom X/Y coordinates)
  - Header (next to logo)
  - Admin bar
  - Before content
  - After content
  - Custom CSS selector
- **Style Settings (Pastel Theme):**

  - Background: Gradient pastel colors
  - Text color: Slate tones
  - Border radius: 12px
  - Font: System font stack
  - Smooth animations

#### 3.4.2 Content Settings

- Customizable message template with `{{countdown}}` placeholder
- Overdue message when countdown expires
- Separate settings for admin and frontend

---

### 3.5 Demo Account Management

#### 3.5.1 Demo Account Features

- Link existing WordPress users as demo accounts
- Store demo-specific metadata:
  - Display name/role name
  - Password (plain text for display on login form)
  - Login type (admin/frontend)
  - Redirect URL after login
  - Custom message/instructions
  - Sort order
  - Active status

#### 3.5.2 Account Operations

- **CRUD Operations:**

  - Create demo account (link existing user)
  - Update demo account settings
  - Delete demo account
  - Toggle active status
- **Batch Operations:**

  - Generate demo accounts automatically
  - Truncate all demo accounts (except super admin)

#### 3.5.3 Login Form Integration

- Display demo account selector on login form
- Auto-fill credentials on selection
- Show account role badges
- Vue.js powered interface with pastel styling

---

### 3.6 Permission Restrictions

#### 3.6.1 Restricted Actions for Demo Accounts

- **Plugin Management:**

  - Cannot activate/deactivate plugins
  - Cannot install/upload plugins
  - Cannot delete plugins
- **User Management:**

  - Cannot delete super admin
  - Cannot modify super admin password
  - Cannot change own password
  - Cannot delete protected users
- **Settings:**

  - Cannot access General Settings
  - Cannot modify site URL
  - Cannot change security settings

---

### 3.7 Telegram Notifications

- Bot Token configuration with validation
- Chat ID with test message
- Events: backup/restore started, completed, failed

---

### 3.8 Maintenance Mode

- Enable/disable via toggle
- Custom maintenance page with pastel design
- Super admin bypass

---

## 4. Database Schema

### 4.1 Tables with Indexes

```sql
-- Backups table with optimized indexes
CREATE TABLE {prefix}demobuilder_backups (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    filename VARCHAR(255) NOT NULL,
    size BIGINT UNSIGNED DEFAULT 0,
    database_size BIGINT UNSIGNED DEFAULT 0,
    directories_size BIGINT UNSIGNED DEFAULT 0,
    directories LONGTEXT,
    excluded_tables LONGTEXT,
    excluded_plugins LONGTEXT,
    excluded_themes LONGTEXT,
    exclusion_options LONGTEXT,
    is_portable TINYINT(1) DEFAULT 0,
    cloud_synced TINYINT(1) DEFAULT 0,
    cloud_provider VARCHAR(50),
    cloud_path VARCHAR(500),
    created_by BIGINT UNSIGNED,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    from_directory TINYINT(1) DEFAULT 0,
  
    -- Performance indexes
    INDEX idx_created_at (created_at),
    INDEX idx_created_by (created_by),
    INDEX idx_cloud_synced (cloud_synced),
    INDEX idx_filename (filename(191)),
    INDEX idx_name_created (name(100), created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Demo accounts table with optimized indexes
CREATE TABLE {prefix}demobuilder_demo_accounts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    role_name VARCHAR(100),
    password_plain VARCHAR(255),
    login_type VARCHAR(50) DEFAULT 'wp-login',
    redirect_url VARCHAR(500),
    message TEXT,
    is_active TINYINT(1) DEFAULT 1,
    sort_order INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP,
  
    -- Performance indexes
    INDEX idx_user_id (user_id),
    INDEX idx_is_active (is_active),
    INDEX idx_sort_order (sort_order),
    INDEX idx_login_type (login_type),
    INDEX idx_active_sort (is_active, sort_order),
    UNIQUE INDEX idx_unique_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Activity logs table with optimized indexes
CREATE TABLE {prefix}demobuilder_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_type VARCHAR(50) NOT NULL,
    event_subtype VARCHAR(50),
    message TEXT NOT NULL,
    context LONGTEXT,
    user_id BIGINT UNSIGNED,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  
    -- Performance indexes
    INDEX idx_event_type (event_type),
    INDEX idx_event_subtype (event_subtype),
    INDEX idx_user_id (user_id),
    INDEX idx_created_at (created_at),
    INDEX idx_type_created (event_type, created_at),
    INDEX idx_ip_address (ip_address)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Cloud sync queue table
CREATE TABLE {prefix}demobuilder_cloud_queue (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    backup_id BIGINT UNSIGNED NOT NULL,
    provider VARCHAR(50) NOT NULL,
    status ENUM('pending', 'uploading', 'completed', 'failed') DEFAULT 'pending',
    attempts INT DEFAULT 0,
    last_error TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP,
  
    -- Performance indexes
    INDEX idx_backup_id (backup_id),
    INDEX idx_provider (provider),
    INDEX idx_status (status),
    INDEX idx_status_attempts (status, attempts),
    FOREIGN KEY (backup_id) REFERENCES {prefix}demobuilder_backups(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 4.2 Index Design Principles

1. **Primary Keys**: Auto-increment BIGINT for scalability
2. **Foreign Keys**: With CASCADE delete for data integrity
3. **Composite Indexes**: For common query patterns
4. **Prefix Indexes**: For long VARCHAR columns (191 chars for utf8mb4)
5. **Covering Indexes**: Include columns for query optimization

---

## 5. File Structure

```
demo-builder/
├── demo-builder.php              # Main plugin file
├── uninstall.php                 # Cleanup on uninstall
├── readme.txt                    # WordPress readme
├── tailwind.config.js            # TailwindCSS configuration
├── package.json                  # NPM dependencies
├── assets/
│   ├── css/
│   │   ├── admin.css             # Compiled TailwindCSS
│   │   ├── admin.min.css         # Minified production
│   │   └── frontend.css
│   ├── js/
│   │   ├── admin/
│   │   │   ├── dashboard.js
│   │   │   ├── backup-restore.js
│   │   │   ├── demo-accounts.js
│   │   │   ├── settings.js
│   │   │   └── countdown-timer.js
│   │   └── frontend/
│   │       ├── login-form.js
│   │       └── countdown-timer.js
│   ├── src/                      # TailwindCSS source
│   │   └── admin.css
│   └── lib/
│       ├── vue.global.prod.js
│       ├── sweetalert2.min.js
│       ├── select2/
│       └── clipboard.min.js
├── includes/
│   ├── class-activator.php       # Activation hooks
│   ├── class-deactivator.php     # Deactivation hooks
│   ├── class-loader.php          # Autoloader
│   ├── class-i18n.php            # Internationalization
│   └── class-demo-builder.php    # Core class
├── admin/
│   ├── class-admin.php           # Admin core
│   ├── class-backup.php          # Backup functionality
│   ├── class-restore.php         # Restore functionality
│   ├── class-demo-accounts.php   # Demo accounts
│   ├── class-settings.php        # Settings management
│   ├── class-telegram.php        # Telegram integration
│   └── views/
│       ├── dashboard.php
│       ├── backup-restore.php
│       ├── demo-accounts.php
│       └── settings.php
├── public/
│   ├── class-public.php          # Frontend core
│   └── views/
│       ├── maintenance.php
│       ├── login-selector.php
│       └── countdown-timer.php
├── includes/hooks/
│   ├── class-permission-hooks.php
│   ├── class-scheduled-hooks.php
│   └── class-module-hooks.php
├── extensions/                   # Cloud storage extensions
│   ├── cloud-storage-base/
│   │   ├── class-cloud-storage.php
│   │   └── interface-cloud-provider.php
│   ├── google-drive/
│   │   ├── google-drive.php
│   │   ├── class-google-drive-provider.php
│   │   ├── assets/
│   │   └── views/
│   └── onedrive/
│       ├── onedrive.php
│       ├── class-onedrive-provider.php
│       ├── assets/
│       └── views/
├── languages/
│   ├── demo-builder.pot
│   ├── demo-builder-vi.po
│   └── demo-builder-vi.mo
└── heraspec/
    ├── project.md                # This file
    └── project_vi.md             # Vietnamese version
```

---

## 6. Development Roadmap

### Phase 1: Core Foundation

- [ ] Plugin structure setup with TailwindCSS
- [ ] Database tables creation with indexes
- [ ] Admin menu registration
- [ ] Settings framework with pastel UI

### Phase 2: Backup System

- [ ] Database backup functionality
- [ ] Source code backup with exclusions
- [ ] Smart exclusion options
- [ ] Backup management UI

### Phase 3: Restore System

- [ ] Manual restore
- [ ] Scheduled auto-restore
- [ ] WP-Cron integration
- [ ] Countdown timer

### Phase 4: Demo Accounts

- [ ] Demo accounts CRUD
- [ ] Login form integration
- [ ] Generate/truncate features
- [ ] Permission restrictions

### Phase 5: Cloud Extensions

- [ ] Cloud storage base interface
- [ ] Google Drive extension
- [ ] OneDrive extension
- [ ] Auto-sync functionality

### Phase 6: Polish & Release

- [ ] Telegram integration
- [ ] Maintenance mode
- [ ] Plugin Check validation
- [ ] Documentation

---

## 7. References

- **Source Reference:** Perfex CRM Demo Builder Module (`/modules/demo_builder/`)
- **WordPress Plugin Handbook:** https://developer.wordpress.org/plugins/
- **WordPress Coding Standards:** https://developer.wordpress.org/coding-standards/
- **TailwindCSS Documentation:** https://tailwindcss.com/docs
- **Vue.js 3 Documentation:** https://vuejs.org/guide/
