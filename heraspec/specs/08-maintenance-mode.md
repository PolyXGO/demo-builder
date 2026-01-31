# 08 - Maintenance Mode

> **Priority:** Low | **Complexity:** Low  
> **Estimated Time:** 1 day

## Summary | Tóm tắt

**EN:** Implement maintenance mode feature to display a custom page during restore operations or manual activation.

**VI:** Triển khai chế độ bảo trì để hiển thị trang tùy chỉnh trong quá trình restore hoặc khi bật thủ công.

---

## Proposed Changes | Các thay đổi đề xuất

### 8.1 Maintenance Mode Toggle
**Files:** `admin/class-settings.php`, `public/class-maintenance.php`

**EN:**
- Manual enable/disable toggle in settings
- Auto-enable during restore operations
- Auto-disable after restore completes
- Quick toggle from admin bar

**VI:**
- Toggle bật/tắt thủ công trong cài đặt
- Tự động bật trong quá trình restore
- Tự động tắt sau khi restore hoàn thành
- Toggle nhanh từ admin bar

---

### 8.2 Maintenance Page Customization
**File:** `admin/views/settings.php` (Maintenance tab)

**EN:**
- Background image upload
- Logo upload
- Title text
- Description/message (rich text editor)
- Custom HTML content
- Countdown display (if restore scheduled)
- Color scheme (pastel theme)

**VI:**
- Upload ảnh nền
- Upload logo
- Text tiêu đề
- Mô tả/tin nhắn (rich text editor)
- Nội dung HTML tùy chỉnh
- Hiển thị countdown (nếu restore theo lịch)
- Bảng màu (theme pastel)

---

### 8.3 Bypass Options
**File:** `public/class-maintenance.php`

**EN:**
- Super admin bypass (always can access)
- Admin Access list bypass (from settings)
- IP whitelist (comma-separated IPs)
- URL parameter bypass (secret key)
- Cookie-based bypass (for logged users)

**VI:**
- Bypass cho super admin (luôn có thể truy cập)
- Bypass cho Admin Access list (từ cài đặt)
- Whitelist IP (IPs phân cách bằng dấu phẩy)
- Bypass bằng tham số URL (key bí mật)
- Bypass dựa trên cookie (cho users đã đăng nhập)

---

### 8.4 Maintenance Page Template
**File:** `public/views/maintenance.php`

```html
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo esc_html($title); ?></title>
    <style>
        :root {
            --db-primary: #A5B4FC;
            --db-secondary: #C4B5FD;
            --db-bg: #F8FAFC;
            --db-text: #334155;
        }
        body {
            font-family: system-ui, -apple-system, sans-serif;
            background: var(--db-bg);
            background-image: url('<?php echo esc_url($background_image); ?>');
            background-size: cover;
            background-position: center;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 20px;
        }
        .maintenance-box {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 24px;
            padding: 48px;
            max-width: 500px;
            text-align: center;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15);
        }
        .logo {
            max-width: 150px;
            margin-bottom: 24px;
        }
        h1 {
            color: var(--db-text);
            font-size: 28px;
            margin: 0 0 16px 0;
        }
        p {
            color: #64748B;
            line-height: 1.6;
        }
        .countdown {
            background: linear-gradient(135deg, var(--db-primary), var(--db-secondary));
            color: white;
            padding: 16px 32px;
            border-radius: 12px;
            font-size: 24px;
            font-weight: bold;
            margin-top: 24px;
            display: inline-block;
        }
    </style>
</head>
<body>
    <div class="maintenance-box">
        <?php if ($logo): ?>
            <img src="<?php echo esc_url($logo); ?>" alt="Logo" class="logo">
        <?php endif; ?>
        
        <h1><?php echo esc_html($title); ?></h1>
        
        <div class="description">
            <?php echo wp_kses_post($description); ?>
        </div>
        
        <?php if ($show_countdown && $next_restore): ?>
            <div class="countdown" id="countdown">
                <?php echo esc_html($countdown_initial); ?>
            </div>
            <script>
                // Countdown JavaScript
            </script>
        <?php endif; ?>
        
        <?php echo wp_kses_post($custom_html); ?>
    </div>
</body>
</html>
```

---

### 8.5 Hook Implementation
**File:** `public/class-maintenance.php`

```php
class Demo_Builder_Maintenance {
    public function init() {
        add_action('template_redirect', [$this, 'check_maintenance_mode'], 1);
    }
    
    public function check_maintenance_mode() {
        if (!$this->is_maintenance_mode()) {
            return;
        }
        
        if ($this->should_bypass()) {
            return;
        }
        
        $this->display_maintenance_page();
        exit;
    }
    
    private function is_maintenance_mode() {
        return get_option('demo_builder_maintenance_mode') === '1';
    }
    
    private function should_bypass() {
        // Check super admin
        if (current_user_can('manage_options')) {
            $settings = $this->get_settings();
            if (!empty($settings['admin_bypass'])) {
                return true;
            }
        }
        
        // Check IP whitelist
        $ip = $this->get_client_ip();
        if ($this->is_ip_whitelisted($ip)) {
            return true;
        }
        
        // Check URL bypass key
        if (isset($_GET['bypass']) && $_GET['bypass'] === $this->get_bypass_key()) {
            return true;
        }
        
        return false;
    }
}
```

---

## Files to Create | Các file cần tạo

```
demo-builder/
├── public/
│   ├── class-maintenance.php     # [NEW] Maintenance handler
│   └── views/
│       └── maintenance.php       # [NEW] Maintenance template
└── admin/
    └── views/
        └── settings.php          # [MODIFY] Add maintenance tab
```

---

## Settings Schema | Cấu trúc Cài đặt

```json
{
  "maintenance": {
    "enabled": false,
    "title": "Under Maintenance",
    "description": "We are currently performing scheduled maintenance. Please check back soon.",
    "logo": "",
    "background_image": "",
    "custom_html": "",
    "show_countdown": true,
    "admin_bypass": true,
    "ip_whitelist": "",
    "bypass_key": ""
  }
}
```

---

## Verification | Xác minh

### Manual Tests
1. Enable maintenance mode → Site shows maintenance page
2. Login as admin → Can access site normally
3. Add IP to whitelist → That IP bypasses maintenance
4. Use bypass key → Access granted
5. Customize page → Changes appear on maintenance page
6. Start restore → Maintenance auto-enables
7. Complete restore → Maintenance auto-disables

---

## Dependencies | Phụ thuộc

- 01-core-foundation (settings framework)
- WordPress Media Library (for uploads)
