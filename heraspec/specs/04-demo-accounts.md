# 04 - Demo Accounts

> **Priority:** Medium | **Complexity:** Medium  
> **Estimated Time:** 2-3 days

## Summary | Tóm tắt

**EN:** Implement demo account management system with login form integration, account generation, and credentials display.

**VI:** Triển khai hệ thống quản lý tài khoản demo với tích hợp form đăng nhập, tạo tài khoản, và hiển thị thông tin đăng nhập.

---

## Proposed Changes | Các thay đổi đề xuất

### 4.1 Demo Accounts CRUD
**File:** `admin/class-demo-accounts.php`

**EN:**
- Link existing WordPress users as demo accounts
- Store demo-specific metadata in custom table
- CRUD operations via AJAX:
  - Create: Link user ID, set role name, password, settings
  - Read: List all demo accounts with user details
  - Update: Modify account settings
  - Delete: Remove demo account (not the user)
- Toggle active/inactive status
- Drag-and-drop sorting

**VI:**
- Liên kết người dùng WordPress hiện có làm tài khoản demo
- Lưu metadata đặc thù demo trong bảng tùy chỉnh
- Thao tác CRUD qua AJAX:
  - Tạo: Liên kết user ID, đặt tên vai trò, mật khẩu, cài đặt
  - Đọc: Liệt kê tất cả tài khoản demo với chi tiết user
  - Cập nhật: Sửa đổi cài đặt tài khoản
  - Xóa: Xóa tài khoản demo (không xóa user)
- Bật/tắt trạng thái active
- Sắp xếp kéo thả

---

### 4.2 Account Data Schema
**Table:** `{prefix}demobuilder_demo_accounts`

| Field | Type | Description (EN) | Mô tả (VI) |
|-------|------|------------------|------------|
| id | BIGINT | Primary key | Khóa chính |
| user_id | BIGINT | WordPress user ID | ID người dùng WordPress |
| role_name | VARCHAR(100) | Display role name | Tên vai trò hiển thị |
| password_plain | VARCHAR(255) | Plain password for display | Mật khẩu plain để hiển thị |
| login_type | VARCHAR(50) | 'wp-login' or 'custom' | Loại đăng nhập |
| redirect_url | VARCHAR(500) | URL after login | URL sau đăng nhập |
| message | TEXT | Custom instructions | Hướng dẫn tùy chỉnh |
| is_active | TINYINT(1) | Active status | Trạng thái hoạt động |
| sort_order | INT | Display order | Thứ tự hiển thị |

---

### 4.3 Batch Operations
**File:** `admin/class-demo-accounts.php`

**EN:**
- **Generate Demo Accounts:**
  - Auto-create WordPress users with demo role
  - Options: number of accounts, role, password pattern
  - Auto-link to demo accounts table
  
- **Truncate Demo Accounts:**
  - Delete all demo accounts except protected ones
  - Show confirmation with count
  - Option to also delete WordPress users

**VI:**
- **Tạo Tài khoản Demo:**
  - Tự động tạo người dùng WordPress với role demo
  - Tùy chọn: số lượng tài khoản, role, mẫu mật khẩu
  - Tự động liên kết vào bảng tài khoản demo
  
- **Xóa Tất cả Tài khoản Demo:**
  - Xóa tất cả tài khoản demo trừ các tài khoản được bảo vệ
  - Hiển thị xác nhận với số lượng
  - Tùy chọn xóa cả người dùng WordPress

---

### 4.4 Login Form Integration
**Files:** `public/class-login-form.php`, `public/views/login-selector.php`

**EN:**
- Display demo account selector on `wp-login.php`
- Hook into `login_form` action
- Vue.js selector component:
  - Dropdown or button list
  - Show role badges
  - Click to auto-fill credentials
- Styles matching pastel theme
- Hide when no active demo accounts

**VI:**
- Hiển thị bộ chọn tài khoản demo trên `wp-login.php`
- Hook vào action `login_form`
- Component selector Vue.js:
  - Dropdown hoặc danh sách nút
  - Hiển thị badge vai trò
  - Click để tự động điền thông tin
- Styles theo theme pastel
- Ẩn khi không có tài khoản demo active

---

### 4.5 Admin UI
**File:** `admin/views/demo-accounts.php`

**EN:**
- Statistics cards: Total, Active, Inactive counts
- Filter: All / Active / Inactive
- Search by name or email
- Sortable table with drag handles
- Actions: View credentials, Edit, Delete, Toggle status
- Create/Edit modal with form fields
- Credentials modal with copy buttons

**VI:**
- Cards thống kê: Tổng, Active, Inactive
- Bộ lọc: Tất cả / Đang hoạt động / Không hoạt động
- Tìm kiếm theo tên hoặc email
- Bảng có thể sắp xếp với handles kéo
- Hành động: Xem thông tin, Sửa, Xóa, Bật/tắt
- Modal tạo/sửa với các trường form
- Modal thông tin đăng nhập với nút copy

---

## Files to Create | Các file cần tạo

```
demo-builder/
├── admin/
│   ├── class-demo-accounts.php   # [NEW] Account management
│   └── views/
│       └── demo-accounts.php     # [NEW] Account UI
├── public/
│   ├── class-login-form.php      # [NEW] Login integration
│   └── views/
│       └── login-selector.php    # [NEW] Selector template
└── assets/
    └── js/
        ├── admin/
        │   └── demo-accounts.js  # [NEW] Vue.js admin app
        └── frontend/
            └── login-form.js     # [NEW] Login selector
```

---

## API Endpoints | Các endpoint API

```php
// AJAX Actions
wp_ajax_demo_builder_get_demo_accounts
wp_ajax_demo_builder_create_demo_account
wp_ajax_demo_builder_update_demo_account
wp_ajax_demo_builder_delete_demo_account
wp_ajax_demo_builder_toggle_demo_account
wp_ajax_demo_builder_update_sort_order
wp_ajax_demo_builder_generate_demo_accounts
wp_ajax_demo_builder_truncate_demo_accounts
wp_ajax_demo_builder_get_wp_users  // For linking
wp_ajax_demo_builder_get_wp_roles  // For role selection
```

---

## Verification | Xác minh

### Manual Tests
1. Create demo account → Appears in list
2. Edit demo account → Changes saved
3. Delete demo account → Removed from list, user remains
4. Toggle status → Active/Inactive toggles
5. Drag to reorder → Order saved
6. View credentials → Modal shows info with copy
7. Login form → Selector appears, click fills credentials
8. Generate accounts → Multiple accounts created
9. Truncate accounts → All removed except protected

---

## Dependencies | Phụ thuộc

- 01-core-foundation (database, settings)
- SortableJS for drag-and-drop
- ClipboardJS for copy functionality
