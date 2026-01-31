# 02 - Backup System

> **Priority:** High | **Complexity:** High  
> **Estimated Time:** 4-5 days

## Summary | Tóm tắt

**EN:** Implement comprehensive backup system for WordPress database and source code with smart exclusion options, backup management UI, and file storage.

**VI:** Triển khai hệ thống sao lưu toàn diện cho database WordPress và mã nguồn với các tùy chọn loại trừ thông minh, giao diện quản lý backup, và lưu trữ file.

---

## Proposed Changes | Các thay đổi đề xuất

### 2.1 Database Backup
**File:** `admin/class-backup.php`

**EN:**
- Export complete WordPress database to SQL file
- Support table exclusion (sessions, transients, logs)
- Portable backup option (replace domain URLs with placeholders)
- Compress SQL to ZIP format
- Progress tracking via AJAX for large databases

**VI:**
- Xuất toàn bộ database WordPress ra file SQL
- Hỗ trợ loại trừ bảng (sessions, transients, logs)
- Tùy chọn backup portable (thay thế URL domain bằng placeholders)
- Nén SQL sang định dạng ZIP
- Theo dõi tiến độ qua AJAX cho database lớn

---

### 2.2 Source Code Backup
**File:** `admin/class-backup.php`

**EN:**
- Backup configurable directories:
  - `wp-content/uploads/` - Media files
  - `wp-content/themes/` - Theme files
  - `wp-content/plugins/` - Plugin files
- Calculate and display directory sizes
- Recursive ZIP compression
- Exclude demo-builder plugin from backup

**VI:**
- Sao lưu các thư mục có thể cấu hình:
  - `wp-content/uploads/` - File media
  - `wp-content/themes/` - File theme
  - `wp-content/plugins/` - File plugin
- Tính toán và hiển thị kích thước thư mục
- Nén ZIP đệ quy
- Loại trừ plugin demo-builder khỏi backup

---

### 2.3 Smart Exclusion Options
**Files:** `admin/class-backup.php`, `admin/views/backup-restore.php`

**EN:**

**Plugin Exclusions:**
- [ ] Exclude inactive plugins (auto-detect from WordPress)
- [ ] Exclude specific plugins (multi-select dropdown)
- [ ] Exclude plugin data directories

**Theme Exclusions:**
- [ ] Exclude inactive themes (keep only active theme + parent)
- [ ] Exclude specific themes (multi-select dropdown)

**Database Exclusions:**
- [ ] Exclude post revisions (`wp_posts` WHERE post_type='revision')
- [ ] Exclude spam comments (`wp_comments` WHERE comment_approved='spam')
- [ ] Exclude transient options (`wp_options` WHERE option_name LIKE '%_transient_%')
- [ ] Exclude session tables (`wp_usermeta` session tokens)
- [ ] Exclude activity/audit log tables (if plugins detected)
- [ ] Exclude cache tables (object cache, etc.)
- [ ] Exclude WooCommerce sessions (`wp_woocommerce_sessions`)
- [ ] Custom table exclusion list (textarea input)

**File Exclusions:**
- [ ] Exclude cache directories (`/cache/`, `/wp-content/cache/`)
- [ ] Exclude backup directories (prevent recursion)
- [ ] Exclude log files (`*.log`, `debug.log`)
- [ ] Exclude temporary files (`*.tmp`, `*.bak`, `*.swp`)
- [ ] Custom file pattern exclusion (textarea with glob patterns)

**VI:**

**Loại trừ Plugin:**
- [ ] Loại trừ plugins không active (tự động phát hiện từ WordPress)
- [ ] Loại trừ plugins cụ thể (dropdown multi-select)
- [ ] Loại trừ thư mục data của plugin

**Loại trừ Theme:**
- [ ] Loại trừ themes không active (chỉ giữ theme đang dùng + parent)
- [ ] Loại trừ themes cụ thể (dropdown multi-select)

**Loại trừ Database:**
- [ ] Loại trừ post revisions
- [ ] Loại trừ spam comments
- [ ] Loại trừ transient options
- [ ] Loại trừ bảng session
- [ ] Loại trừ bảng activity/audit log
- [ ] Loại trừ bảng cache
- [ ] Loại trừ WooCommerce sessions
- [ ] Danh sách loại trừ bảng tùy chỉnh

**Loại trừ File:**
- [ ] Loại trừ thư mục cache
- [ ] Loại trừ thư mục backup
- [ ] Loại trừ file log
- [ ] Loại trừ file tạm
- [ ] Mẫu loại trừ file tùy chỉnh

---

### 2.4 Backup Management UI
**File:** `admin/views/backup-restore.php`

**EN:**
- Vue.js powered single-page interface
- Create backup form with:
  - Backup name input
  - Directory checkboxes with sizes
  - Exclusion options accordion
  - Portable backup toggle
  - Progress bar during creation
- Backup list table:
  - Name, date, size columns
  - Status indicator (available/missing/synced)
  - Actions: download, restore, delete, cloud sync
- Upload backup section (drag & drop)
- Clean orphaned files button

**VI:**
- Giao diện single-page với Vue.js
- Form tạo backup với:
  - Input tên backup
  - Checkboxes thư mục với kích thước
  - Accordion tùy chọn loại trừ
  - Toggle backup portable
  - Progress bar trong quá trình tạo
- Bảng danh sách backup:
  - Các cột: Tên, ngày, kích thước
  - Chỉ báo trạng thái (có sẵn/thiếu/đã đồng bộ)
  - Hành động: tải xuống, khôi phục, xóa, đồng bộ cloud
- Phần upload backup (kéo thả)
- Nút dọn dẹp file mồ côi

---

### 2.5 Backup Storage
**Files:** `admin/class-backup.php`, `includes/class-db.php`

**EN:**
- Store backup metadata in `{prefix}demobuilder_backups` table
- Store ZIP files in `wp-content/backups/demo-builder/`
- Create `.htaccess` to protect backup directory
- Generate unique filenames with timestamp
- Track: size, database_size, directories_size, exclusion settings

**VI:**
- Lưu metadata backup trong bảng `{prefix}demobuilder_backups`
- Lưu file ZIP trong `wp-content/backups/demo-builder/`
- Tạo `.htaccess` để bảo vệ thư mục backup
- Tạo tên file duy nhất với timestamp
- Theo dõi: size, database_size, directories_size, cài đặt loại trừ

---

## Files to Create/Modify | Các file cần tạo/sửa

```
demo-builder/
├── admin/
│   ├── class-backup.php          # [NEW] Backup functionality
│   └── views/
│       └── backup-restore.php    # [NEW] Backup UI
├── assets/
│   └── js/
│       └── admin/
│           └── backup-restore.js # [NEW] Vue.js app
└── includes/
    └── class-db.php              # [MODIFY] Add backup methods
```

---

## API Endpoints | Các endpoint API

```php
// AJAX Actions
wp_ajax_demo_builder_create_backup
wp_ajax_demo_builder_get_backups
wp_ajax_demo_builder_download_backup
wp_ajax_demo_builder_delete_backup
wp_ajax_demo_builder_upload_backup
wp_ajax_demo_builder_clean_orphaned
wp_ajax_demo_builder_get_directory_sizes
wp_ajax_demo_builder_get_excludable_items
```

---

## Verification | Xác minh

### Automated Tests
- Backup creation with default settings
- Backup creation with exclusions
- Backup list retrieval
- Backup deletion

### Manual Tests
1. Create backup → ZIP file created in backup directory
2. Download backup → File downloads correctly
3. Check exclusions → Inactive plugins/themes not in backup
4. Upload backup → File added to list
5. Delete backup → File removed from disk and database

---

## Dependencies | Phụ thuộc

- ZipArchive PHP extension
- Adequate disk space
- Write permissions to `wp-content/backups/`

---

## 2.6 wp-config.php Handling (Advanced)
**File:** `admin/class-backup.php`

**EN:**

> ⚠️ **Security Note:** wp-config.php contains sensitive database credentials. Handle with care.

**Default Behavior:**
- DO NOT include wp-config.php (for same-server demo restore)
- Safe for standard demo site restoration

**Optional Settings (for migration/cloning):**
- [ ] Include wp-config.php in backup
  - Sub-option: Backup only security keys/salts
  - Sub-option: Backup only table prefix  
  - Sub-option: Full wp-config.php (with warning)
- [ ] Generate wp-config template (credentials removed, placeholders added)

**VI:**

> ⚠️ **Lưu ý Bảo mật:** wp-config.php chứa thông tin database nhạy cảm.

**Hành vi Mặc định:**
- KHÔNG bao gồm wp-config.php (cho restore demo cùng server)

**Cài đặt Tùy chọn (cho migration/cloning):**
- [ ] Bao gồm wp-config.php trong backup
- [ ] Tạo template wp-config (credentials bị xóa, thêm placeholders)

---

## 2.7 Site Cloning & Migration Features
**Files:** `admin/class-backup.php`, `admin/class-migration.php`

**EN:**

### 2.7.1 Portable/Migration Backup Mode
When creating backup for cloning to another site:

- [ ] **Create as Migration Package**
  - Includes all files needed for new site setup
  - Database with URL placeholders
  - wp-config.php template
  - Installation instructions (README.txt)

### 2.7.2 URL Search & Replace
**Critical for site cloning - handles serialized data properly**

- Replace old site URL → placeholder `{{SITE_URL}}`
- Replace old home URL → placeholder `{{HOME_URL}}`  
- Handle serialized data (WordPress options, widget data, etc.)
- Use recursive unserialization for safe replacement

**Search/Replace Targets:**
- wp_options (siteurl, home, upload_path, etc.)
- wp_posts (post_content, guid)
- wp_postmeta, wp_usermeta, wp_commentmeta

### 2.7.3 Migration Package Contents
```
migration-package-{date}.zip
├── database.sql              # Portable SQL with placeholders
├── wp-content/               # All wp-content files
├── wp-config-template.php    # Template with placeholders
├── README.txt                # Installation instructions
└── manifest.json             # Package metadata
```

### 2.7.4 manifest.json Structure
```json
{
  "package_type": "migration",
  "created_at": "2026-01-31T10:30:00Z",
  "source_url": "https://original-site.com",
  "wordpress_version": "6.4.2",
  "table_prefix": "wp_",
  "placeholders": {
    "{{SITE_URL}}": "https://original-site.com",
    "{{DB_NAME}}": "original_db"
  }
}
```

### 2.7.5 Clone Site Wizard (Restore on New Site)
1. **Upload Package** - Upload migration ZIP
2. **Configure Database** - Enter new DB credentials
3. **Set New URL** - Enter new site URL/home URL
4. **Replace Placeholders** - Auto search/replace in database
5. **Generate wp-config.php** - Create with new credentials
6. **Finalize** - Clear cache, regenerate permalinks

### 2.7.6 Quick Clone Features
- [ ] One-click create migration package
- [ ] Download package with installation guide
- [ ] Email/share package link (with cloud storage)

---

**VI:**

### 2.7.1 Chế độ Backup Portable/Migration
- [ ] **Tạo dưới dạng Migration Package** cho clone sang site khác

### 2.7.2 Search & Replace URL
- Xử lý dữ liệu serialized đúng cách
- Thay thế URLs bằng placeholders

### 2.7.3 Clone Site Wizard
1. Upload Package
2. Cấu hình Database
3. Đặt URL Mới
4. Thay thế Placeholders
5. Tạo wp-config.php
6. Hoàn tất

---

## Files to Create (Updated)

```
demo-builder/
├── admin/
│   ├── class-backup.php          # [NEW] Backup functionality
│   ├── class-migration.php       # [NEW] Migration/cloning
│   └── views/
│       ├── backup-restore.php
│       └── migration-wizard.php  # [NEW] Clone wizard
├── assets/js/admin/
│   ├── backup-restore.js
│   └── migration.js              # [NEW]
└── includes/
    ├── class-db.php
    └── class-search-replace.php  # [NEW] Serialized-safe S&R
```
