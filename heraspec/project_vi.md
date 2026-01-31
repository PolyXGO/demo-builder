# Demo Builder cho WordPress - Đặc Tả Dự Án

> **Phiên bản:** 1.0.0  
> **Cập nhật lần cuối:** 2026-01-31  
> **Nền tảng:** WordPress 6.x+  
> **Tham khảo:** Module Demo Builder của Perfex CRM

## 1. Tổng Quan

Demo Builder cho WordPress là một plugin toàn diện được thiết kế để quản lý các trang demo hiệu quả. Plugin cung cấp chức năng sao lưu/khôi phục tự động, quản lý tài khoản demo với quyền hạn bị giới hạn, và bảo trì theo lịch cho môi trường demo.

### Mục Tiêu Chính

1. **Sao lưu Cơ sở dữ liệu & Mã nguồn** - Khả năng sao lưu đầy đủ cho database WordPress và source code
2. **Khôi phục Tự động** - Tự động khôi phục theo lịch với khoảng thời gian có thể cấu hình
3. **Quản lý Tài khoản Demo** - Tạo và quản lý tài khoản người dùng demo với quyền hạn giới hạn
4. **Hạn chế Quyền** - Bảo vệ các thao tác quan trọng khỏi người dùng demo
5. **Thông báo Telegram** - Thông báo thời gian thực cho các sự kiện sao lưu/khôi phục
6. **Bộ đếm Thời gian** - Hiển thị thời gian còn lại đến lần khôi phục tiếp theo
7. **Lưu trữ Đám mây** - Tự động backup lên OneDrive, Google Drive (qua extensions)

---

## 2. Tiêu Chuẩn Phát Triển & Skills

### 2.1 Tiêu Chuẩn Thiết Kế UI/UX

#### 2.1.1 Tích hợp TailwindCSS
- Sử dụng TailwindCSS 3.x cho tất cả UI components
- Triển khai thông qua hệ thống enqueue của WordPress
- Tiền tố tất cả classes để tránh xung đột: `db-` (demo-builder)

#### 2.1.2 Chủ đề Thiết kế: Pastel Hiện Đại & Sang Trọng
```css
/* Bảng màu - Tone Pastel Sáng */
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

#### 2.1.3 Các Thành Phần UI
- **Cards**: Bo góc (12px), shadow nhẹ, hiệu ứng hover
- **Buttons**: Hình viên thuốc hoặc bo tròn, gradient backgrounds
- **Forms**: Floating labels, transitions mượt khi focus
- **Tables**: Rows sọc, headers cố định, columns sắp xếp được
- **Modals**: Backdrop blur, animations slide-in
- **Alerts**: Backgrounds mềm, borders tinh tế

### 2.2 Tiêu Chuẩn WordPress (Skills Áp Dụng)

#### 2.2.1 Plugin Standards (`skill:plugin-standard`)
- Tuân theo hướng dẫn WordPress Plugin Handbook
- Tổ chức file và quy ước đặt tên đúng chuẩn
- Thực hành coding an toàn (nonces, capabilities, sanitization)
- Sẵn sàng đa ngôn ngữ (i18n)
- Kiến trúc hooks và filters đúng chuẩn

#### 2.2.2 Plugin Check (`skill:plugin-check`)
- Đạt kiểm tra WordPress Plugin Check tool
- Không có lỗi hoặc cảnh báo PHP
- Escape output đúng cách
- Định dạng readme.txt hợp lệ
- Headers plugin đúng chuẩn

#### 2.2.3 Sẵn sàng Plugin Directory (`skill:plugin-directory`)
- Đáp ứng yêu cầu của WordPress.org plugin repository
- Giấy phép tương thích GPL
- Không có dependencies bên ngoài không được khai báo
- Xử lý assets đúng cách
- Best practices về bảo mật

---

## 3. Đặc Tả Tính Năng

### 3.1 Hệ Thống Sao Lưu

#### 3.1.1 Sao lưu Cơ sở dữ liệu
- Xuất toàn bộ database WordPress ra file SQL
- Hỗ trợ loại trừ bảng (ví dụ: bảng session, logs)
- Hỗ trợ nén (định dạng ZIP)
- Tùy chọn sao lưu portable (xóa URL theo domain cụ thể)

#### 3.1.2 Sao lưu Mã nguồn
- Các thư mục có thể cấu hình:
  - `wp-content/uploads/` - File media
  - `wp-content/themes/` - File theme
  - `wp-content/plugins/` - File plugin (loại trừ chính demo-builder)
- Tính toán và hiển thị kích thước thư mục
- Bật/tắt thư mục theo lựa chọn

#### 3.1.3 Tùy Chọn Loại Trừ Thông Minh
- **Loại trừ Plugin:**
  - [ ] Loại trừ plugins không active
  - [ ] Loại trừ plugins cụ thể (multi-select)
  - [ ] Loại trừ thư mục data của plugin

- **Loại trừ Theme:**
  - [ ] Loại trừ themes không active
  - [ ] Loại trừ themes cụ thể (multi-select)

- **Loại trừ Dữ liệu:**
  - [ ] Loại trừ post revisions
  - [ ] Loại trừ spam comments
  - [ ] Loại trừ transients
  - [ ] Loại trừ bảng session
  - [ ] Loại trừ activity/audit logs
  - [ ] Loại trừ bảng cache
  - [ ] Loại trừ WooCommerce sessions (nếu có)
  - [ ] Danh sách loại trừ bảng tùy chỉnh

- **Loại trừ File:**
  - [ ] Loại trừ thư mục cache (`/cache/`, `/wp-content/cache/`)
  - [ ] Loại trừ thư mục backup (tránh đệ quy)
  - [ ] Loại trừ file log (`*.log`, `debug.log`)
  - [ ] Loại trừ file tạm (`*.tmp`, `*.bak`)
  - [ ] Mẫu loại trừ file tùy chỉnh

#### 3.1.4 Quản lý Bản sao lưu
- Liệt kê tất cả bản sao lưu với metadata:
  - Tên bản sao lưu
  - Ngày/giờ tạo
  - Kích thước file (tổng, database, thư mục)
  - Người tạo
  - Trạng thái (có sẵn/thiếu)
  - Trạng thái đồng bộ cloud
- Tải xuống file sao lưu
- Xóa từng bản sao lưu
- Tải lên file sao lưu từ bên ngoài
- Dọn dẹp file sao lưu mồ côi

---

### 3.2 Hệ Thống Khôi Phục

#### 3.2.1 Khôi phục Thủ công
- Chọn bản sao lưu từ danh sách
- Hộp thoại xác nhận với cảnh báo
- Các bước khôi phục:
  1. Bật chế độ bảo trì
  2. Khôi phục database
  3. Khôi phục file nguồn (nếu có)
  4. Xóa cache WordPress
  5. Tắt chế độ bảo trì

#### 3.2.2 Tự động Khôi phục theo Lịch
- **Các tùy chọn Lịch trình:**
  - Mỗi 30 phút
  - Mỗi 60 phút (1 giờ)
  - Mỗi 120 phút (2 giờ)
  - Mỗi 24 giờ (1 ngày)
  - Mỗi 48 giờ (2 ngày)
  - Mỗi 72 giờ (3 ngày)
  - Mỗi 7 ngày (1 tuần)
  - Mỗi 14 ngày (2 tuần)
  - Số giờ tùy chỉnh (nhập số)

---

### 3.3 Extensions Lưu Trữ Đám Mây

> **Vị trí:** `demo-builder/extensions/`

#### 3.3.1 Kiến trúc Extensions
```
demo-builder/
└── extensions/
    ├── cloud-storage-base/       # Class abstract cơ sở
    │   ├── class-cloud-storage.php
    │   └── interface-cloud-provider.php
    ├── google-drive/             # Tích hợp Google Drive
    │   ├── google-drive.php
    │   ├── class-google-drive-provider.php
    │   ├── assets/
    │   │   └── admin.js
    │   └── views/
    │       └── settings.php
    └── onedrive/                 # Tích hợp OneDrive
        ├── onedrive.php
        ├── class-onedrive-provider.php
        ├── assets/
        │   └── admin.js
        └── views/
            └── settings.php
```

#### 3.3.2 Google Drive Extension
- **Cấu hình:**
  - Xác thực OAuth 2.0
  - Thiết lập Client ID & Secret
  - Chọn/tạo thư mục
  - Bật/tắt tự động đồng bộ

- **Tính năng:**
  - Upload thủ công lên Google Drive
  - Tự động upload sau khi backup
  - Chỉ báo trạng thái đồng bộ
  - Download từ Google Drive
  - Xóa backups cloud cũ (chính sách lưu giữ)

#### 3.3.3 OneDrive Extension
- **Cấu hình:**
  - Xác thực Microsoft OAuth
  - Hướng dẫn đăng ký App
  - Chọn/tạo thư mục
  - Bật/tắt tự động đồng bộ

- **Tính năng:**
  - Upload thủ công lên OneDrive
  - Tự động upload sau khi backup
  - Chỉ báo trạng thái đồng bộ
  - Download từ OneDrive
  - Xóa backups cloud cũ (chính sách lưu giữ)

#### 3.3.4 Interface Extension
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

### 3.4 Bộ Đếm Thời Gian (Countdown Timer)

- Nhiều vị trí hiển thị (fixed, header, admin bar, selector tùy chỉnh)
- Style theo theme Pastel (gradient, bo góc 12px)
- Template tin nhắn tùy chỉnh với placeholder `{{countdown}}`

---

### 3.5 Quản Lý Tài Khoản Demo

- Liên kết người dùng WordPress làm tài khoản demo
- CRUD operations với giao diện Vue.js
- Tích hợp form đăng nhập với styling pastel
- Thao tác hàng loạt (generate, truncate)

---

### 3.6 Hạn Chế Quyền

- Không cho phép activate/deactivate plugins
- Không cho phép xóa super admin
- Không cho phép thay đổi mật khẩu demo
- Không cho phép truy cập Settings nhạy cảm

---

### 3.7 Thông Báo Telegram

- Cấu hình Bot Token và Chat ID
- Thông báo các sự kiện backup/restore

---

## 4. Sơ Đồ Cơ Sở Dữ Liệu

### 4.1 Các Bảng với Indexes Tối Ưu

```sql
-- Bảng Sao lưu với indexes tối ưu
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
    
    -- Indexes hiệu suất
    INDEX idx_created_at (created_at),
    INDEX idx_created_by (created_by),
    INDEX idx_cloud_synced (cloud_synced),
    INDEX idx_filename (filename(191)),
    INDEX idx_name_created (name(100), created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng Tài khoản Demo với indexes tối ưu
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
    
    -- Indexes hiệu suất
    INDEX idx_user_id (user_id),
    INDEX idx_is_active (is_active),
    INDEX idx_sort_order (sort_order),
    INDEX idx_login_type (login_type),
    INDEX idx_active_sort (is_active, sort_order),
    UNIQUE INDEX idx_unique_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng Log hoạt động với indexes tối ưu
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
    
    -- Indexes hiệu suất
    INDEX idx_event_type (event_type),
    INDEX idx_event_subtype (event_subtype),
    INDEX idx_user_id (user_id),
    INDEX idx_created_at (created_at),
    INDEX idx_type_created (event_type, created_at),
    INDEX idx_ip_address (ip_address)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng hàng đợi Cloud sync
CREATE TABLE {prefix}demobuilder_cloud_queue (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    backup_id BIGINT UNSIGNED NOT NULL,
    provider VARCHAR(50) NOT NULL,
    status ENUM('pending', 'uploading', 'completed', 'failed') DEFAULT 'pending',
    attempts INT DEFAULT 0,
    last_error TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP,
    
    -- Indexes hiệu suất
    INDEX idx_backup_id (backup_id),
    INDEX idx_provider (provider),
    INDEX idx_status (status),
    INDEX idx_status_attempts (status, attempts),
    FOREIGN KEY (backup_id) REFERENCES {prefix}demobuilder_backups(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 4.2 Nguyên Tắc Thiết Kế Index

1. **Primary Keys**: Auto-increment BIGINT cho khả năng mở rộng
2. **Foreign Keys**: Với CASCADE delete để đảm bảo tính toàn vẹn dữ liệu
3. **Composite Indexes**: Cho các mẫu truy vấn phổ biến
4. **Prefix Indexes**: Cho cột VARCHAR dài (191 ký tự cho utf8mb4)
5. **Covering Indexes**: Bao gồm các cột để tối ưu truy vấn

---

## 5. Cấu Trúc Thư Mục

```
demo-builder/
├── demo-builder.php              # File plugin chính
├── uninstall.php                 # Dọn dẹp khi gỡ cài đặt
├── readme.txt                    # Readme WordPress
├── tailwind.config.js            # Cấu hình TailwindCSS
├── package.json                  # Dependencies NPM
├── assets/
│   ├── css/
│   │   ├── admin.css             # TailwindCSS đã compile
│   │   ├── admin.min.css         # Minified production
│   │   └── frontend.css
│   ├── js/
│   │   └── admin/
│   ├── src/                      # TailwindCSS source
│   │   └── admin.css
│   └── lib/
│       ├── vue.global.prod.js
│       └── sweetalert2.min.js
├── includes/
│   ├── class-activator.php
│   ├── class-deactivator.php
│   └── class-demo-builder.php
├── admin/
│   ├── class-admin.php
│   ├── class-backup.php
│   ├── class-restore.php
│   └── views/
├── public/
│   ├── class-public.php
│   └── views/
├── extensions/                   # Extensions lưu trữ cloud
│   ├── cloud-storage-base/
│   │   ├── class-cloud-storage.php
│   │   └── interface-cloud-provider.php
│   ├── google-drive/
│   │   ├── google-drive.php
│   │   └── class-google-drive-provider.php
│   └── onedrive/
│       ├── onedrive.php
│       └── class-onedrive-provider.php
├── languages/
│   ├── demo-builder.pot
│   └── demo-builder-vi.mo
└── heraspec/
    ├── project.md
    └── project_vi.md
```

---

## 6. Lộ Trình Phát Triển

### Giai đoạn 1: Nền tảng Cốt lõi
- [ ] Thiết lập cấu trúc plugin với TailwindCSS
- [ ] Tạo các bảng database với indexes
- [ ] Đăng ký menu admin
- [ ] Framework cài đặt với UI pastel

### Giai đoạn 2: Hệ thống Sao lưu
- [ ] Chức năng sao lưu database
- [ ] Sao lưu mã nguồn với loại trừ
- [ ] Các tùy chọn loại trừ thông minh
- [ ] Giao diện quản lý sao lưu

### Giai đoạn 3: Hệ thống Khôi phục
- [ ] Khôi phục thủ công
- [ ] Tự động khôi phục theo lịch
- [ ] Tích hợp WP-Cron
- [ ] Bộ đếm thời gian

### Giai đoạn 4: Tài khoản Demo
- [ ] CRUD tài khoản demo
- [ ] Tích hợp form đăng nhập
- [ ] Tính năng tạo/xóa hàng loạt
- [ ] Hạn chế quyền

### Giai đoạn 5: Cloud Extensions
- [ ] Interface lưu trữ cloud cơ sở
- [ ] Extension Google Drive
- [ ] Extension OneDrive
- [ ] Chức năng tự động đồng bộ

### Giai đoạn 6: Hoàn thiện & Phát hành
- [ ] Tích hợp Telegram
- [ ] Chế độ bảo trì
- [ ] Xác nhận Plugin Check
- [ ] Tài liệu hướng dẫn

---

## 7. Tài Liệu Tham Khảo

- **Source Tham khảo:** Module Demo Builder của Perfex CRM (`/modules/demo_builder/`)
- **WordPress Plugin Handbook:** https://developer.wordpress.org/plugins/
- **WordPress Coding Standards:** https://developer.wordpress.org/coding-standards/
- **TailwindCSS Documentation:** https://tailwindcss.com/docs
- **Vue.js 3 Documentation:** https://vuejs.org/guide/
