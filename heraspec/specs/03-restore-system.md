# 03 - Restore System

> **Priority:** High | **Complexity:** High  
> **Estimated Time:** 3-4 days

## Summary | Tóm tắt

**EN:** Implement manual and scheduled auto-restore functionality with WP-Cron integration and countdown timer display.

**VI:** Triển khai chức năng khôi phục thủ công và tự động theo lịch với tích hợp WP-Cron và hiển thị bộ đếm thời gian.

---

## Proposed Changes | Các thay đổi đề xuất

### 3.1 Manual Restore
**File:** `admin/class-restore.php`

**EN:**
- Select backup from list to restore
- Confirmation dialog with warnings
- Restore process steps:
  1. Enable maintenance mode
  2. Extract backup ZIP
  3. Restore database (import SQL)
  4. Restore source files (overwrite)
  5. Replace portable URLs with current domain
  6. Clear WordPress cache
  7. Disable maintenance mode
- Progress tracking via AJAX
- Rollback on failure

**VI:**
- Chọn backup từ danh sách để khôi phục
- Hộp thoại xác nhận với cảnh báo
- Các bước khôi phục:
  1. Bật chế độ bảo trì
  2. Giải nén backup ZIP
  3. Khôi phục database (import SQL)
  4. Khôi phục file nguồn (ghi đè)
  5. Thay thế URL portable bằng domain hiện tại
  6. Xóa cache WordPress
  7. Tắt chế độ bảo trì
- Theo dõi tiến độ qua AJAX
- Rollback khi thất bại

---

### 3.2 Scheduled Auto-Restore
**Files:** `admin/class-restore.php`, `includes/hooks/class-scheduled-hooks.php`

**EN:**

**Schedule Options:**
| Value | Label (EN) | Label (VI) | Seconds |
|-------|------------|------------|---------|
| 30min | Every 30 minutes | Mỗi 30 phút | 1800 |
| 60min | Every 60 minutes | Mỗi 60 phút | 3600 |
| 120min | Every 2 hours | Mỗi 2 giờ | 7200 |
| 24h | Every 24 hours | Mỗi 24 giờ | 86400 |
| 48h | Every 2 days | Mỗi 2 ngày | 172800 |
| 72h | Every 3 days | Mỗi 3 ngày | 259200 |
| 7d | Every 7 days | Mỗi 7 ngày | 604800 |
| 14d | Every 14 days | Mỗi 14 ngày | 1209600 |
| custom | Custom hours | Số giờ tùy chỉnh | user-defined |

**Configuration Options:**
- Enable/disable auto-restore toggle
- Select source backup from dropdown
- Auto-backup after restore toggle
- Display last restore timestamp
- Display next restore timestamp

**VI:**
- Bật/tắt tự động khôi phục
- Chọn backup nguồn từ dropdown
- Bật/tắt tự động backup sau khôi phục
- Hiển thị thời gian khôi phục lần cuối
- Hiển thị thời gian khôi phục tiếp theo

---

### 3.3 WP-Cron Integration
**File:** `includes/hooks/class-scheduled-hooks.php`

**EN:**
- Hook into `wp_cron` for scheduled restore
- Custom cron schedules for all intervals
- Check schedule timing before execution
- Update timestamps after completion
- Log all restore activities to `{prefix}demobuilder_logs`
- Send Telegram notification on completion

**VI:**
- Hook vào `wp_cron` cho khôi phục theo lịch
- Lịch cron tùy chỉnh cho tất cả khoảng thời gian
- Kiểm tra thời gian lịch trình trước khi thực thi
- Cập nhật timestamps sau khi hoàn thành
- Ghi log tất cả hoạt động khôi phục
- Gửi thông báo Telegram khi hoàn thành

---

### 3.4 Countdown Timer
**Files:** `admin/class-countdown.php`, `public/class-countdown.php`

**EN:**

**Display Positions:**
- Fixed position (custom X/Y coordinates via CSS)
- Header (next to site logo)
- Admin bar (as admin bar node)
- Before content (hook into `the_content`)
- After content (hook into `the_content`)
- Custom CSS selector (inject via JavaScript)

**Style Settings (Pastel Theme):**
```css
/* Default Countdown Styles */
.db-countdown {
  background: linear-gradient(135deg, #A5B4FC 0%, #C4B5FD 100%);
  color: #334155;
  padding: 8px 16px;
  border-radius: 12px;
  font-size: 14px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}
```

**Content Settings:**
- Message template with `{{countdown}}` placeholder
- Overdue message when countdown expires
- Separate settings for admin and frontend
- Mobile visibility toggle

**Timer Calculation:**
```javascript
nextRestore = lastRestore + scheduleInterval;
countdown = nextRestore - currentTime;
display = formatCountdown(countdown); // HH:MM:SS
```

**VI:**

**Vị trí Hiển thị:**
- Vị trí cố định (tọa độ X/Y tùy chỉnh qua CSS)
- Header (bên cạnh logo)
- Admin bar (như node admin bar)
- Trước nội dung (hook vào `the_content`)
- Sau nội dung (hook vào `the_content`)
- CSS selector tùy chỉnh (inject qua JavaScript)

**Cài đặt Style (Theme Pastel):**
- Gradient background pastel
- Text color slate
- Border radius 12px
- Shadow mềm

**Cài đặt Nội dung:**
- Template tin nhắn với placeholder `{{countdown}}`
- Tin nhắn khi hết thời gian
- Cài đặt riêng cho admin và frontend
- Toggle hiển thị trên mobile

---

## Files to Create/Modify | Các file cần tạo/sửa

```
demo-builder/
├── admin/
│   ├── class-restore.php         # [NEW] Restore functionality
│   ├── class-countdown.php       # [NEW] Admin countdown
│   └── views/
│       └── backup-restore.php    # [MODIFY] Add restore UI
├── public/
│   ├── class-public.php          # [MODIFY] Enqueue frontend
│   ├── class-countdown.php       # [NEW] Frontend countdown
│   └── views/
│       └── countdown-timer.php   # [NEW] Timer template
├── assets/
│   └── js/
│       ├── admin/
│       │   └── countdown-timer.js  # [NEW] Admin timer
│       └── frontend/
│           └── countdown-timer.js  # [NEW] Frontend timer
└── includes/
    └── hooks/
        └── class-scheduled-hooks.php  # [NEW] Cron hooks
```

---

## API Endpoints | Các endpoint API

```php
// AJAX Actions
wp_ajax_demo_builder_restore_backup
wp_ajax_demo_builder_get_restore_progress
wp_ajax_demo_builder_save_restore_settings
wp_ajax_demo_builder_get_countdown_data
```

---

## Settings Schema | Cấu trúc Cài đặt

```json
{
  "restore": {
    "auto_restore_enabled": false,
    "restore_schedule": "24h",
    "restore_schedule_custom_hours": 0,
    "source_backup_id": null,
    "auto_backup_after_restore": true,
    "last_auto_restore": null,
    "next_auto_restore": null
  },
  "countdown_admin": {
    "enabled": true,
    "position": "fixed",
    "position_x": "20px",
    "position_y": "20px",
    "css_selector": "",
    "message_template": "Next restore in: {{countdown}}",
    "overdue_message": "Restore overdue!",
    "show_on_mobile": true,
    "background": "linear-gradient(135deg, #A5B4FC 0%, #C4B5FD 100%)",
    "text_color": "#334155",
    "border_radius": "12px",
    "font_size": "14px"
  },
  "countdown_frontend": {
    "enabled": false,
    "position": "before_content",
    "message_template": "Demo resets in: {{countdown}}",
    "overdue_message": "Demo reset imminent!",
    "show_on_mobile": true
  }
}
```

---

## Verification | Xác minh

### Automated Tests
- Restore from backup successfully
- Cron schedules registered correctly
- Countdown calculation accuracy

### Manual Tests
1. Restore backup → Site returns to backup state
2. Schedule restore → Cron event appears in wp-cron
3. Countdown timer → Displays correct remaining time
4. Auto-restore → Triggers at scheduled time

---

## Dependencies | Phụ thuộc

- 01-core-foundation (database, settings)
- 02-backup-system (backup files)
- WP-Cron or external cron trigger
