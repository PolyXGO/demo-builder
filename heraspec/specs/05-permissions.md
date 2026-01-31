# 05 - Permission Restrictions

> **Priority:** Medium | **Complexity:** Medium  
> **Estimated Time:** 2 days

## Summary | Tóm tắt

**EN:** Implement permission restrictions for demo accounts to prevent destructive actions like deleting admin users, plugins, or modifying critical settings.

**VI:** Triển khai hạn chế quyền cho tài khoản demo để ngăn các hành động phá hoại như xóa admin users, plugins, hoặc thay đổi cài đặt quan trọng.

---

## Proposed Changes | Các thay đổi đề xuất

### 5.1 URL-Based Restrictions
**File:** `includes/hooks/class-permission-hooks.php`

**EN:**
Hook into `admin_init` to check current URL and redirect demo accounts from restricted pages:

**Restricted URLs:**
- `plugins.php` - Plugin list (if delete action)
- `plugin-install.php` - Install plugins
- `theme-install.php` - Install themes
- `user-edit.php` - Edit admin users
- `options-general.php` - General settings
- `options-writing.php` - Writing settings
- `options-reading.php` - Reading settings
- `options-permalink.php` - Permalink settings

**VI:**
Hook vào `admin_init` để kiểm tra URL hiện tại và chuyển hướng tài khoản demo khỏi các trang bị hạn chế.

---

### 5.2 Capability-Based Restrictions
**File:** `includes/hooks/class-permission-hooks.php`

**EN:**
Hook into `user_has_cap` and `map_meta_cap` filters:

**Blocked Capabilities for Demo Accounts:**
- `delete_users` - Cannot delete any user
- `remove_users` - Cannot remove users from site
- `edit_users` - Cannot edit admin users
- `activate_plugins` - Cannot activate plugins
- `deactivate_plugins` - Cannot deactivate plugins
- `delete_plugins` - Cannot delete plugins
- `install_plugins` - Cannot install plugins
- `upload_plugins` - Cannot upload plugins
- `switch_themes` - Cannot switch themes
- `delete_themes` - Cannot delete themes
- `install_themes` - Cannot install themes
- `edit_themes` - Cannot edit theme files
- `manage_options` - Cannot access settings (configurable)

**VI:**
Hook vào filters `user_has_cap` và `map_meta_cap`:

**Capabilities Bị chặn cho Tài khoản Demo:**
- Không thể xóa users
- Không thể sửa admin users
- Không thể activate/deactivate plugins
- Không thể xóa/cài đặt plugins
- Không thể chuyển/xóa/cài đặt themes
- Không thể truy cập settings (có thể cấu hình)

---

### 5.3 Specific Action Restrictions
**File:** `includes/hooks/class-permission-hooks.php`

**EN:**

**Protect Super Admin:**
- Cannot delete admin user ID 1
- Cannot modify admin user ID 1 password
- Cannot change admin user ID 1 role

**Protect Demo Account Self:**
- Cannot change own password
- Cannot change own email
- Cannot deactivate own account

**Protect Other Demo Accounts:**
- Cannot delete other demo accounts
- Cannot modify other demo accounts

**VI:**

**Bảo vệ Super Admin:**
- Không thể xóa admin user ID 1
- Không thể thay đổi mật khẩu admin user ID 1
- Không thể thay đổi role admin user ID 1

**Bảo vệ Tài khoản Demo:**
- Không thể thay đổi mật khẩu của mình
- Không thể thay đổi email của mình
- Không thể vô hiệu hóa tài khoản của mình

---

### 5.4 Configurable Permissions
**File:** `admin/views/settings.php` (Permissions tab)

**EN:**
Settings to allow/disallow for demo accounts:
- [ ] Allow profile updates
- [ ] Allow password changes
- [ ] Allow user status changes
- [ ] Allow settings access
- [ ] Custom URL blacklist (textarea)
- [ ] Protected user IDs (comma-separated)

**VI:**
Cài đặt cho phép/không cho phép tài khoản demo:
- [ ] Cho phép cập nhật hồ sơ
- [ ] Cho phép thay đổi mật khẩu
- [ ] Cho phép thay đổi trạng thái user
- [ ] Cho phép truy cập settings
- [ ] Danh sách đen URL tùy chỉnh
- [ ] ID người dùng được bảo vệ

---

### 5.5 Restriction Messages
**File:** `includes/hooks/class-permission-hooks.php`

**EN:**
- Show admin notice when action is blocked
- Log blocked attempts to activity log
- Redirect to dashboard with warning message

**VI:**
- Hiển thị thông báo admin khi hành động bị chặn
- Ghi log các lần thử bị chặn
- Chuyển hướng về dashboard với tin nhắn cảnh báo

---

## Files to Create | Các file cần tạo

```
demo-builder/
└── includes/
    └── hooks/
        └── class-permission-hooks.php  # [NEW] All permission hooks
```

---

## Hook Implementation | Triển khai Hook

```php
// Check if user is demo account
private function is_demo_account($user_id) {
    global $wpdb;
    $table = $wpdb->prefix . 'demobuilder_demo_accounts';
    return (bool) $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM $table WHERE user_id = %d AND is_active = 1",
        $user_id
    ));
}

// Filter capabilities
add_filter('user_has_cap', function($allcaps, $caps, $args, $user) {
    if ($this->is_demo_account($user->ID)) {
        foreach ($this->blocked_caps as $cap) {
            $allcaps[$cap] = false;
        }
    }
    return $allcaps;
}, 10, 4);

// Admin init check
add_action('admin_init', function() {
    $user_id = get_current_user_id();
    if ($this->is_demo_account($user_id)) {
        $current_screen = get_current_screen();
        if (in_array($current_screen->id, $this->restricted_screens)) {
            wp_redirect(admin_url());
            exit;
        }
    }
});
```

---

## Settings Schema | Cấu trúc Cài đặt

```json
{
  "permissions": {
    "allow_profile_update": false,
    "allow_password_change": false,
    "allow_user_status_change": false,
    "allow_settings_access": false,
    "custom_url_blacklist": [],
    "protected_user_ids": [1]
  }
}
```

---

## Verification | Xác minh

### Manual Tests
1. Login as demo account → Cannot access Plugins menu
2. Try delete plugin → Action blocked, message shown
3. Try edit admin profile → Redirected to dashboard
4. Try change own password → Error message
5. Enable "allow profile update" → Profile edit works
6. Add URL to blacklist → Access blocked

---

## Dependencies | Phụ thuộc

- 01-core-foundation (settings framework)
- 04-demo-accounts (demo account detection)
