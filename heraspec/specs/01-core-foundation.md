# 01 - Core Foundation

> **Priority:** High | **Complexity:** Medium  
> **Estimated Time:** 2-3 days

## Summary | Tóm tắt

**EN:** Setup the core plugin structure including TailwindCSS integration, database tables with indexes, admin menu registration, and settings framework with pastel UI design.

**VI:** Thiết lập cấu trúc plugin cốt lõi bao gồm tích hợp TailwindCSS, các bảng database với indexes, đăng ký menu admin, và framework cài đặt với thiết kế UI pastel.

---

## Proposed Changes | Các thay đổi đề xuất

### 1.1 Main Plugin File
**File:** `demo-builder.php`

**EN:**
- Create main plugin file with proper headers
- Define plugin constants (version, paths, URLs)
- Load plugin textdomain for i18n
- Initialize activation/deactivation hooks
- Register autoloader for classes

**VI:**
- Tạo file plugin chính với headers đúng chuẩn
- Định nghĩa các hằng số plugin (version, paths, URLs)
- Load textdomain cho đa ngôn ngữ
- Khởi tạo hooks kích hoạt/vô hiệu hóa
- Đăng ký autoloader cho các classes

---

### 1.2 Database Tables
**Files:** `includes/class-activator.php`, `includes/class-db.php`

**EN:**
- Create `{prefix}demobuilder_backups` table with indexes
- Create `{prefix}demobuilder_demo_accounts` table with unique constraint
- Create `{prefix}demobuilder_logs` table with composite indexes
- Create `{prefix}demobuilder_cloud_queue` table with foreign keys
- Implement dbDelta for safe upgrades

**VI:**
- Tạo bảng `{prefix}demobuilder_backups` với indexes
- Tạo bảng `{prefix}demobuilder_demo_accounts` với ràng buộc unique
- Tạo bảng `{prefix}demobuilder_logs` với composite indexes
- Tạo bảng `{prefix}demobuilder_cloud_queue` với foreign keys
- Triển khai dbDelta để nâng cấp an toàn

---

### 1.3 TailwindCSS Setup
**Files:** `tailwind.config.js`, `package.json`, `assets/src/admin.css`

**EN:**
- Configure TailwindCSS 3.x with custom prefix `db-`
- Define pastel color palette as CSS variables
- Setup build scripts for development and production
- Compile to `assets/css/admin.css` and `admin.min.css`

**VI:**
- Cấu hình TailwindCSS 3.x với prefix tùy chỉnh `db-`
- Định nghĩa bảng màu pastel dưới dạng CSS variables
- Thiết lập build scripts cho development và production
- Compile ra `assets/css/admin.css` và `admin.min.css`

**Color Palette:**
```css
--db-primary: #A5B4FC;      /* Pastel Indigo */
--db-secondary: #C4B5FD;    /* Pastel Purple */
--db-success: #86EFAC;      /* Pastel Green */
--db-warning: #FDE68A;      /* Pastel Yellow */
--db-danger: #FCA5A5;       /* Pastel Red */
--db-info: #7DD3FC;         /* Pastel Blue */
```

---

### 1.4 Admin Menu Registration
**File:** `admin/class-admin.php`

**EN:**
- Register top-level menu "Demo Builder" with icon
- Add submenu pages: Dashboard, Demo Accounts, Backup & Restore, Settings
- Enqueue admin styles and scripts conditionally
- Implement capability checks for menu access

**VI:**
- Đăng ký menu cấp cao nhất "Demo Builder" với icon
- Thêm các trang submenu: Dashboard, Demo Accounts, Backup & Restore, Settings
- Enqueue styles và scripts admin có điều kiện
- Triển khai kiểm tra capability cho truy cập menu

---

### 1.5 Settings Framework
**Files:** `admin/class-settings.php`, `admin/views/settings.php`

**EN:**
- Create settings page with tabbed interface
- Tabs: General, Backup, Restore, Countdown Timer, Demo Panel, Telegram, Permissions
- Store settings as JSON in `demo_builder_settings` option
- Implement AJAX save with nonce verification

**VI:**
- Tạo trang cài đặt với giao diện dạng tabs
- Các tab: Chung, Sao lưu, Khôi phục, Bộ đếm thời gian, Demo Panel, Telegram, Quyền hạn
- Lưu cài đặt dưới dạng JSON trong option `demo_builder_settings`
- Triển khai lưu AJAX với xác minh nonce

---

## Files to Create | Các file cần tạo

```
demo-builder/
├── demo-builder.php
├── uninstall.php
├── tailwind.config.js
├── package.json
├── assets/
│   ├── src/
│   │   └── admin.css
│   └── css/
│       ├── admin.css
│       └── admin.min.css
├── includes/
│   ├── class-activator.php
│   ├── class-deactivator.php
│   ├── class-loader.php
│   ├── class-i18n.php
│   ├── class-db.php
│   └── class-demo-builder.php
└── admin/
    ├── class-admin.php
    ├── class-settings.php
    └── views/
        ├── dashboard.php
        └── settings.php
```

---

## Verification | Xác minh

### Automated Tests
- Plugin activation without errors
- Database tables created with correct schema
- Admin menu appears for administrators

### Manual Tests
1. Activate plugin → No PHP errors
2. Check database → All 4 tables exist with indexes
3. Admin menu → "Demo Builder" menu visible
4. Settings page → Tabs render correctly, save works

---

## Dependencies | Phụ thuộc

- WordPress 6.0+
- PHP 7.4+
- MySQL 5.7+ or MariaDB 10.3+
