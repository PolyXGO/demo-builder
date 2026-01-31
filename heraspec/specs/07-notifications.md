# 07 - Telegram Notifications

> **Priority:** Low | **Complexity:** Low  
> **Estimated Time:** 1 day

## Summary | Tóm tắt

**EN:** Implement Telegram bot integration for sending notifications about backup, restore, and other important events.

**VI:** Triển khai tích hợp Telegram bot để gửi thông báo về backup, restore, và các sự kiện quan trọng khác.

---

## Proposed Changes | Các thay đổi đề xuất

### 7.1 Telegram Configuration
**Files:** `admin/class-telegram.php`, `admin/views/settings.php`

**EN:**
- Bot Token input with validation
- Chat ID input
- Test message button
- Enable/disable toggle
- Connection status indicator

**VI:**
- Input Bot Token với xác thực
- Input Chat ID
- Nút gửi tin nhắn test
- Toggle bật/tắt
- Chỉ báo trạng thái kết nối

---

### 7.2 Notification Events
**File:** `admin/class-telegram.php`

**EN:**

| Event | Trigger | Message |
|-------|---------|---------|
| Backup Started | Manual/Scheduled backup begins | 🔄 Backup started: {name} |
| Backup Success | Backup completed successfully | ✅ Backup completed: {name} ({size}) |
| Backup Failed | Backup encountered error | ❌ Backup failed: {name} - {error} |
| Restore Started | Manual/Scheduled restore begins | 🔄 Restore started from: {backup_name} |
| Restore Success | Restore completed successfully | ✅ Restore completed |
| Restore Failed | Restore encountered error | ❌ Restore failed: {error} |
| Cloud Sync | Backup uploaded to cloud | ☁️ Synced to {provider}: {name} |
| Low Disk Space | Backup directory low on space | ⚠️ Low disk space: {available} remaining |

**VI:**

| Sự kiện | Kích hoạt | Tin nhắn |
|---------|-----------|----------|
| Backup Bắt đầu | Backup thủ công/theo lịch bắt đầu | 🔄 Backup bắt đầu: {name} |
| Backup Thành công | Backup hoàn thành thành công | ✅ Backup hoàn thành: {name} ({size}) |
| Backup Thất bại | Backup gặp lỗi | ❌ Backup thất bại: {name} - {error} |
| Restore Bắt đầu | Restore thủ công/theo lịch bắt đầu | 🔄 Restore bắt đầu từ: {backup_name} |
| Restore Thành công | Restore hoàn thành | ✅ Restore hoàn thành |
| Restore Thất bại | Restore gặp lỗi | ❌ Restore thất bại: {error} |
| Cloud Sync | Backup đã upload lên cloud | ☁️ Đã đồng bộ lên {provider}: {name} |
| Disk Space Thấp | Thư mục backup sắp hết dung lượng | ⚠️ Dung lượng thấp: còn {available} |

---

### 7.3 Message Format
**File:** `admin/class-telegram.php`

```
🔧 Demo Builder Notification
━━━━━━━━━━━━━━━━━━━━━━

📌 Event: {event_type}
🔘 Status: {status}
🕐 Time: {timestamp}
🌐 Site: {site_url}

{details}

━━━━━━━━━━━━━━━━━━━━━━
Schedule: {schedule_type}
```

---

### 7.4 Telegram API Integration
**File:** `admin/class-telegram.php`

```php
class Demo_Builder_Telegram {
    private $bot_token;
    private $chat_id;
    
    public function send_message($message, $parse_mode = 'HTML') {
        $url = "https://api.telegram.org/bot{$this->bot_token}/sendMessage";
        
        $response = wp_remote_post($url, [
            'body' => [
                'chat_id' => $this->chat_id,
                'text' => $message,
                'parse_mode' => $parse_mode,
            ],
            'timeout' => 10,
        ]);
        
        if (is_wp_error($response)) {
            return ['success' => false, 'error' => $response->get_error_message()];
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        return ['success' => $body['ok'], 'result' => $body];
    }
    
    public function validate_token($token) {
        $url = "https://api.telegram.org/bot{$token}/getMe";
        $response = wp_remote_get($url, ['timeout' => 10]);
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        return isset($body['ok']) && $body['ok'];
    }
}
```

---

## Files to Create | Các file cần tạo

```
demo-builder/
└── admin/
    └── class-telegram.php    # [NEW] Telegram integration
```

---

## Settings Schema | Cấu trúc Cài đặt

```json
{
  "telegram": {
    "enabled": false,
    "bot_token": "",
    "chat_id": "",
    "notify_backup_start": true,
    "notify_backup_success": true,
    "notify_backup_failed": true,
    "notify_restore_start": true,
    "notify_restore_success": true,
    "notify_restore_failed": true,
    "notify_cloud_sync": false,
    "notify_low_disk": true
  }
}
```

---

## Verification | Xác minh

### Manual Tests
1. Enter Bot Token → Validation shows success/error
2. Enter Chat ID → Test message received
3. Create backup → Notification received
4. Restore backup → Notification received
5. Disable notifications → No messages sent

---

## Dependencies | Phụ thuộc

- 01-core-foundation (settings framework)
- cURL or wp_remote_post
- Valid Telegram Bot Token
