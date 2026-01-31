# 09 - Performance Optimization

> **Priority:** High | **Complexity:** High  
> **Estimated Time:** 2-3 days
> **Applies To:** All backup/restore operations

## Summary | Tóm tắt

**EN:** Implement performance optimizations for handling large databases and files, including chunked processing, streaming uploads/downloads, batch operations, and memory management.

**VI:** Triển khai tối ưu hiệu suất cho xử lý database và files lớn, bao gồm xử lý theo chunks, streaming upload/download, thao tác batch, và quản lý bộ nhớ.

---

## 9.1 Database Backup Optimization

### 9.1.1 Primary Method: mysqldump
**File:** `admin/class-backup.php`

**EN:**
- Use `mysqldump` command for large databases (most efficient)
- Benefits: No memory limits, handles any database size
- Fallback to PHP method if mysqldump not available

**Implementation:**
```php
// mysqldump with streaming (no memory issues)
$command = sprintf(
    '%s --host=%s --port=%d --user=%s --password=%s ' .
    '--single-transaction --quick --lock-tables=false ' .
    '--routines --triggers %s > %s',
    escapeshellarg($mysqldump_path),
    escapeshellarg($host),
    (int)$port,
    escapeshellarg($username),
    escapeshellarg($password),
    escapeshellarg($database),
    escapeshellarg($output_file)
);
```

**Find mysqldump paths:**
```php
$possible_paths = [
    'mysqldump',                              // In PATH
    '/usr/bin/mysqldump',                     // Linux standard
    '/usr/local/bin/mysqldump',               // Linux alternative
    '/usr/local/mysql/bin/mysqldump',         // macOS MySQL
    '/Applications/XAMPP/bin/mysqldump',      // XAMPP macOS
    '/opt/lampp/bin/mysqldump',               // XAMPP Linux
    'C:\\xampp\\mysql\\bin\\mysqldump.exe',   // XAMPP Windows
];
```

**VI:**
- Sử dụng lệnh `mysqldump` cho database lớn (hiệu quả nhất)
- Lợi ích: Không giới hạn bộ nhớ, xử lý được database bất kỳ kích thước
- Fallback sang phương thức PHP nếu mysqldump không khả dụng

---

### 9.1.2 Fallback Method: Chunked PHP Backup
**File:** `admin/class-backup.php`

**EN:**
When mysqldump is not available, use chunked PHP backup:

**Configuration Constants:**
```php
const DB_CHUNK_SIZE = 1000;        // Rows per chunk
const INSERT_BATCH_SIZE = 100;     // Rows per INSERT statement
const MEMORY_CHECK_INTERVAL = 50;  // Check memory every N tables
```

**Chunked Table Backup:**
```php
private function backup_table_chunked($handle, $table) {
    $chunk_size = 1000; // Rows per chunk
    $offset = 0;
    
    do {
        // Fetch chunk of rows
        $query = $wpdb->prepare(
            "SELECT * FROM `{$table}` LIMIT %d OFFSET %d",
            $chunk_size,
            $offset
        );
        $rows = $wpdb->get_results($query, ARRAY_A);
        
        if (!empty($rows)) {
            $this->write_insert_statements($handle, $table, $rows);
        }
        
        $offset += $chunk_size;
        
        // Free memory
        unset($rows);
        wp_cache_flush();
        
        // Reset execution time
        if (function_exists('set_time_limit')) {
            set_time_limit(30);
        }
        
    } while (!empty($rows));
}
```

**Batch INSERT Statements:**
```php
// Split into smaller batches to avoid max_allowed_packet issues
$insert_batch_size = 100;

for ($i = 0; $i < $total_rows; $i += $insert_batch_size) {
    $batch = array_slice($rows, $i, $insert_batch_size);
    
    // Write INSERT statement for this batch
    $sql = "INSERT INTO `{$table}` VALUES\n";
    $values = [];
    
    foreach ($batch as $row) {
        $values[] = $this->format_row_values($row);
    }
    
    $sql .= implode(",\n", $values) . ";\n\n";
    fwrite($handle, $sql);
}
```

**VI:**
Khi mysqldump không khả dụng, sử dụng PHP backup theo chunks:
- 1000 rows mỗi chunk
- 100 rows mỗi câu INSERT
- Kiểm tra memory mỗi 50 tables
- Xóa cache, reset thời gian thực thi

---

## 9.2 File Backup Optimization

### 9.2.1 Batch ZIP Processing
**File:** `admin/class-backup.php`

**EN:**
Process files in batches to prevent 503 timeout and memory issues:

```php
const FILE_BATCH_SIZE = 100;       // Files per batch
const PROGRESS_LOG_INTERVAL = 50;  // Log progress every N files

private function backup_directories_batched($zip, $directories) {
    $batch_counter = 0;
    $batch_size = 100;
    
    foreach ($directories as $dir) {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir)
        );
        
        foreach ($files as $file) {
            if ($file->isFile()) {
                // Add to ZIP
                $zip->addFile($file->getPathname(), $relative_path);
                $batch_counter++;
                
                // Log progress
                if ($batch_counter % 50 === 0) {
                    $this->log_progress("Processed $batch_counter files");
                }
                
                // Save and free memory every batch
                if ($batch_counter >= $batch_size) {
                    $zip->close();
                    $zip->open($zip_path);
                    $batch_counter = 0;
                    
                    // Reset execution time
                    if (function_exists('set_time_limit')) {
                        set_time_limit(60);
                    }
                }
            }
        }
    }
}
```

**VI:**
Xử lý files theo batch để tránh timeout 503 và vấn đề bộ nhớ:
- 100 files mỗi batch
- Log tiến độ mỗi 50 files
- Đóng/mở lại ZIP để giải phóng bộ nhớ
- Reset thời gian thực thi

---

### 9.2.2 Streaming File Download
**File:** `admin/class-backup.php`

**EN:**
Stream large backup files in chunks to prevent memory issues:

```php
const DOWNLOAD_CHUNK_SIZE = 1048576; // 1MB chunks

private function stream_file_download($file_path, $chunk_size = 1048576) {
    // Disable output buffering
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // Set headers
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . basename($file_path) . '"');
    header('Content-Length: ' . filesize($file_path));
    header('Cache-Control: no-cache');
    
    // Stream file in chunks
    $handle = fopen($file_path, 'rb');
    if ($handle === false) {
        throw new Exception('Cannot open file for reading');
    }
    
    while (!feof($handle)) {
        // Read chunk
        $buffer = fread($handle, $chunk_size);
        
        // Output chunk
        echo $buffer;
        
        // Flush output
        if (ob_get_level()) {
            ob_flush();
        }
        flush();
        
        // Reset execution time for each chunk
        if (function_exists('set_time_limit')) {
            set_time_limit(30);
        }
    }
    
    fclose($handle);
}
```

**VI:**
Stream file backup lớn theo chunks để tránh vấn đề bộ nhớ:
- 1MB mỗi chunk
- Tắt output buffering
- Flush sau mỗi chunk
- Reset thời gian thực thi

---

## 9.3 Large File Upload Support

### 9.3.1 PHP Upload Limits Display
**File:** `admin/views/backup-restore.php`

**EN:**
Display current PHP upload limits and instructions to increase:

```php
public function get_upload_limits() {
    return [
        'upload_max_filesize' => ini_get('upload_max_filesize'),
        'post_max_size' => ini_get('post_max_size'),
        'memory_limit' => ini_get('memory_limit'),
        'max_execution_time' => ini_get('max_execution_time'),
        'max_upload_mb' => $this->parse_size(ini_get('upload_max_filesize'))
    ];
}
```

**UI Display:**
```
┌─────────────────────────────────────────────┐
│ 📁 PHP Upload Limits                        │
├─────────────────────────────────────────────┤
│ upload_max_filesize:  128M                  │
│ post_max_size:        128M                  │
│ memory_limit:         256M                  │
│ max_execution_time:   300s                  │
├─────────────────────────────────────────────┤
│ 💡 To increase limits, edit php.ini:        │
│                                             │
│ upload_max_filesize = 500M                  │
│ post_max_size = 500M                        │
│ memory_limit = 512M                         │
│ max_execution_time = 600                    │
└─────────────────────────────────────────────┘
```

**VI:**
Hiển thị giới hạn upload PHP hiện tại và hướng dẫn tăng.

---

### 9.3.2 Chunked File Upload (For Very Large Files)
**Files:** `admin/class-backup.php`, `assets/js/admin/backup-restore.js`

**EN:**
For files larger than PHP upload limit, implement chunked upload:

**JavaScript (Frontend):**
```javascript
const CHUNK_SIZE = 5 * 1024 * 1024; // 5MB chunks

async function uploadLargeFile(file) {
    const totalChunks = Math.ceil(file.size / CHUNK_SIZE);
    const uploadId = generateUUID();
    
    for (let i = 0; i < totalChunks; i++) {
        const start = i * CHUNK_SIZE;
        const end = Math.min(start + CHUNK_SIZE, file.size);
        const chunk = file.slice(start, end);
        
        const formData = new FormData();
        formData.append('chunk', chunk);
        formData.append('chunkIndex', i);
        formData.append('totalChunks', totalChunks);
        formData.append('uploadId', uploadId);
        formData.append('filename', file.name);
        formData.append('action', 'demo_builder_upload_chunk');
        formData.append('nonce', demoBuilderData.nonce);
        
        await fetch(ajaxurl, {
            method: 'POST',
            body: formData
        });
        
        // Update progress
        updateProgress((i + 1) / totalChunks * 100);
    }
    
    // Finalize upload
    return await finalizeChunkedUpload(uploadId, file.name);
}
```

**PHP (Backend):**
```php
public function handle_chunk_upload() {
    $upload_id = sanitize_text_field($_POST['uploadId']);
    $chunk_index = intval($_POST['chunkIndex']);
    $total_chunks = intval($_POST['totalChunks']);
    $filename = sanitize_file_name($_POST['filename']);
    
    // Temp directory for chunks
    $temp_dir = wp_upload_dir()['basedir'] . '/demo-builder-temp/' . $upload_id;
    wp_mkdir_p($temp_dir);
    
    // Save chunk
    $chunk_file = $temp_dir . '/chunk_' . str_pad($chunk_index, 5, '0', STR_PAD_LEFT);
    move_uploaded_file($_FILES['chunk']['tmp_name'], $chunk_file);
    
    wp_send_json_success(['chunk' => $chunk_index]);
}

public function finalize_chunked_upload() {
    $upload_id = sanitize_text_field($_POST['uploadId']);
    $filename = sanitize_file_name($_POST['filename']);
    
    $temp_dir = wp_upload_dir()['basedir'] . '/demo-builder-temp/' . $upload_id;
    $final_path = WP_CONTENT_DIR . '/backups/demo-builder/' . $filename;
    
    // Merge chunks
    $final_handle = fopen($final_path, 'wb');
    $chunks = glob($temp_dir . '/chunk_*');
    sort($chunks);
    
    foreach ($chunks as $chunk) {
        $chunk_handle = fopen($chunk, 'rb');
        stream_copy_to_stream($chunk_handle, $final_handle);
        fclose($chunk_handle);
        unlink($chunk);
    }
    
    fclose($final_handle);
    rmdir($temp_dir);
    
    wp_send_json_success(['file' => $final_path]);
}
```

**VI:**
Cho files lớn hơn giới hạn upload PHP, triển khai upload theo chunks:
- 5MB mỗi chunk
- Lưu chunks vào thư mục tạm
- Merge chunks sau khi hoàn thành
- Dọn dẹp files tạm

---

## 9.4 Database Restore Optimization

### 9.4.1 Chunked SQL Import
**File:** `admin/class-restore.php`

**EN:**
Read and execute SQL file in chunks:

```php
const SQL_READ_CHUNK_SIZE = 8192; // 8KB chunks

private function restore_database_chunked($sql_file) {
    global $wpdb;
    
    $handle = fopen($sql_file, 'rb');
    if (!$handle) {
        throw new Exception('Cannot open SQL file');
    }
    
    $buffer = '';
    $chunk_size = 8192; // 8KB
    $queries_executed = 0;
    
    while (!feof($handle)) {
        $chunk = fread($handle, $chunk_size);
        $buffer .= $chunk;
        
        // Look for complete statements (ending with ;)
        while (($pos = strpos($buffer, ";\n")) !== false) {
            $statement = substr($buffer, 0, $pos + 1);
            $buffer = substr($buffer, $pos + 2);
            
            // Skip comments and empty statements
            $statement = trim($statement);
            if (empty($statement) || substr($statement, 0, 2) === '--') {
                continue;
            }
            
            // Execute statement
            $wpdb->query($statement);
            $queries_executed++;
            
            // Every 100 queries, reset execution time
            if ($queries_executed % 100 === 0) {
                if (function_exists('set_time_limit')) {
                    set_time_limit(30);
                }
            }
        }
    }
    
    fclose($handle);
    
    return $queries_executed;
}
```

**VI:**
Đọc và thực thi file SQL theo chunks:
- 8KB mỗi chunk
- Tìm statements hoàn chỉnh (kết thúc bằng ;)
- Reset thời gian thực thi mỗi 100 queries

---

## 9.5 Memory Management

### 9.5.1 Configuration
**File:** `admin/class-backup.php`

```php
// Increase memory limit for backup operations
private function prepare_for_large_operation() {
    // Try to increase memory limit
    if (function_exists('ini_set')) {
        @ini_set('memory_limit', '512M');
    }
    
    // Increase execution time
    if (function_exists('set_time_limit')) {
        @set_time_limit(600); // 10 minutes
    }
    
    // Disable output buffering
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // Close session to prevent locking
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }
}
```

### 9.5.2 Memory Monitoring
```php
private function check_memory_usage() {
    $current = memory_get_usage(true);
    $limit = $this->parse_size(ini_get('memory_limit'));
    $limit_bytes = $limit * 1024 * 1024;
    
    // Warning at 80% usage
    if ($current > $limit_bytes * 0.8) {
        $this->log_warning('High memory usage: ' . 
            round($current / 1024 / 1024) . 'MB / ' . $limit . 'MB');
        
        // Force garbage collection
        if (function_exists('gc_collect_cycles')) {
            gc_collect_cycles();
        }
        
        // Clear WP cache
        wp_cache_flush();
    }
}
```

---

## 9.6 Progress Tracking

### 9.6.1 AJAX Progress Updates
**File:** `admin/class-backup.php`

**EN:**
Store progress in transient for AJAX polling:

```php
private function update_progress($operation_id, $data) {
    set_transient('demo_builder_progress_' . $operation_id, [
        'status' => $data['status'],
        'percent' => $data['percent'],
        'message' => $data['message'],
        'step' => $data['step'],
        'total_steps' => $data['total_steps'],
        'updated_at' => time()
    ], 3600);
}

public function get_progress() {
    $operation_id = sanitize_text_field($_POST['operation_id']);
    $progress = get_transient('demo_builder_progress_' . $operation_id);
    
    if ($progress) {
        wp_send_json_success($progress);
    } else {
        wp_send_json_error(['message' => 'No progress data']);
    }
}
```

**VI:**
Lưu tiến độ trong transient để AJAX polling:
- Trạng thái, phần trăm, thông điệp
- Bước hiện tại, tổng số bước
- Thời gian cập nhật

---

## Files to Create | Các file cần tạo

```
demo-builder/
├── admin/
│   ├── class-backup.php          # Chunked backup methods
│   ├── class-restore.php         # Chunked restore methods
│   └── class-upload-handler.php  # [NEW] Chunked upload handling
└── assets/
    └── js/
        └── admin/
            ├── backup-restore.js # Progress tracking, chunked upload
            └── upload-chunked.js # [NEW] Chunked upload JS
```

---

## API Endpoints | Các endpoint API

```php
// AJAX Actions
wp_ajax_demo_builder_upload_chunk         // Handle chunk upload
wp_ajax_demo_builder_finalize_upload      // Merge chunks
wp_ajax_demo_builder_get_progress         // Get operation progress
wp_ajax_demo_builder_get_upload_limits    // Get PHP limits
wp_ajax_demo_builder_cancel_operation     // Cancel running operation
```

---

## Performance Benchmarks | Tiêu chuẩn Hiệu suất

| Operation | Small (< 100MB) | Medium (100MB-1GB) | Large (> 1GB) |
|-----------|-----------------|--------------------| --------------|
| DB Backup (mysqldump) | < 30s | 1-5 min | 5-15 min |
| DB Backup (chunked) | < 60s | 5-15 min | 15-60 min |
| File Backup | < 60s | 5-20 min | 20-60 min |
| Upload | Depends on connection | Chunked required | Chunked required |
| Restore | < 60s | 5-15 min | 15-60 min |

---

## Verification | Xác minh

### Manual Tests
1. Large DB backup → Completes without memory error
2. Large file download → Streams without timeout
3. Chunked upload → Progress bar updates, file complete
4. Progress tracking → Accurate percentage display
5. Cancel operation → Cleanup performed

---

## Dependencies | Phụ thuộc

- 02-backup-system
- 03-restore-system
- ZipArchive PHP extension
- mysqli or PDO
