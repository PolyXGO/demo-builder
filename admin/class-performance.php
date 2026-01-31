<?php
/**
 * Performance Optimization Class
 *
 * Provides optimized methods for large database and file operations
 *
 * @package DemoBuilder
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Demo_Builder_Performance
 */
class Demo_Builder_Performance {

    /**
     * Instance
     *
     * @var Demo_Builder_Performance
     */
    private static $instance = null;

    /**
     * Configuration constants
     */
    const DB_CHUNK_SIZE = 1000;
    const INSERT_BATCH_SIZE = 100;
    const FILE_BATCH_SIZE = 100;
    const DOWNLOAD_CHUNK_SIZE = 1048576; // 1MB
    const SQL_READ_CHUNK_SIZE = 8192; // 8KB
    const MEMORY_CHECK_INTERVAL = 50;
    const PROGRESS_LOG_INTERVAL = 50;

    /**
     * Get instance
     *
     * @return Demo_Builder_Performance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->init_hooks();
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action('wp_ajax_demo_builder_get_progress', [$this, 'ajax_get_progress']);
        add_action('wp_ajax_demo_builder_cancel_operation', [$this, 'ajax_cancel_operation']);
    }

    /**
     * Prepare for large operation
     */
    public function prepare_for_large_operation() {
        // Increase memory limit
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

    /**
     * Find mysqldump path
     *
     * @return string|false
     */
    public function find_mysqldump() {
        $possible_paths = [
            'mysqldump',
            '/usr/bin/mysqldump',
            '/usr/local/bin/mysqldump',
            '/usr/local/mysql/bin/mysqldump',
            '/Applications/XAMPP/bin/mysqldump',
            '/Applications/XAMPP/xamppfiles/bin/mysqldump',
            '/opt/lampp/bin/mysqldump',
            'C:\\xampp\\mysql\\bin\\mysqldump.exe',
            'C:\\wamp64\\bin\\mysql\\mysql8.0.31\\bin\\mysqldump.exe',
        ];

        foreach ($possible_paths as $path) {
            // Check if executable
            if ($this->is_executable($path)) {
                return $path;
            }
        }

        return false;
    }

    /**
     * Check if path is executable
     *
     * @param string $path Path to check
     * @return bool
     */
    private function is_executable($path) {
        if (PHP_OS_FAMILY === 'Windows') {
            $output = [];
            $return = 0;
            @exec("where {$path} 2>NUL", $output, $return);
            return $return === 0;
        } else {
            $output = [];
            $return = 0;
            @exec("which {$path} 2>/dev/null", $output, $return);
            return $return === 0;
        }
    }

    /**
     * Backup database using mysqldump
     *
     * @param string $output_file Output SQL file path
     * @param string $operation_id Operation ID for progress tracking
     * @return array Result
     */
    public function backup_database_mysqldump($output_file, $operation_id = '') {
        $mysqldump = $this->find_mysqldump();
        
        if (!$mysqldump) {
            return ['success' => false, 'error' => 'mysqldump not found'];
        }

        $this->prepare_for_large_operation();

        global $wpdb;
        
        // Get database credentials
        $host = DB_HOST;
        $port = 3306;
        if (strpos($host, ':') !== false) {
            list($host, $port) = explode(':', $host);
        }
        
        $database = DB_NAME;
        $username = DB_USER;
        $password = DB_PASSWORD;

        // Build command
        $command = sprintf(
            '%s --host=%s --port=%d --user=%s --password=%s ' .
            '--single-transaction --quick --lock-tables=false ' .
            '--routines --triggers %s > %s 2>&1',
            escapeshellarg($mysqldump),
            escapeshellarg($host),
            (int) $port,
            escapeshellarg($username),
            escapeshellarg($password),
            escapeshellarg($database),
            escapeshellarg($output_file)
        );

        // Update progress
        if ($operation_id) {
            $this->update_progress($operation_id, [
                'status' => 'running',
                'percent' => 10,
                'message' => __('Running mysqldump...', 'demo-builder'),
                'step' => 1,
                'total_steps' => 2,
            ]);
        }

        // Execute
        $output = [];
        $return = 0;
        exec($command, $output, $return);

        if ($return !== 0) {
            return [
                'success' => false,
                'error' => implode("\n", $output),
            ];
        }

        // Verify file was created
        if (!file_exists($output_file) || filesize($output_file) === 0) {
            return [
                'success' => false,
                'error' => __('Database backup file is empty or not created.', 'demo-builder'),
            ];
        }

        // Update progress
        if ($operation_id) {
            $this->update_progress($operation_id, [
                'status' => 'complete',
                'percent' => 100,
                'message' => __('Database backup complete!', 'demo-builder'),
                'step' => 2,
                'total_steps' => 2,
            ]);
        }

        return [
            'success' => true,
            'file' => $output_file,
            'size' => filesize($output_file),
        ];
    }

    /**
     * Backup database using chunked PHP method
     *
     * @param string $output_file Output SQL file path
     * @param string $operation_id Operation ID for progress tracking
     * @return array Result
     */
    public function backup_database_chunked($output_file, $operation_id = '') {
        global $wpdb;
        
        $this->prepare_for_large_operation();

        // Get all tables
        $tables = $wpdb->get_col("SHOW TABLES");
        $total_tables = count($tables);
        
        if (empty($tables)) {
            return ['success' => false, 'error' => __('No tables found.', 'demo-builder')];
        }

        // Open output file
        $handle = fopen($output_file, 'w');
        if (!$handle) {
            return ['success' => false, 'error' => __('Cannot create backup file.', 'demo-builder')];
        }

        // Write header
        fwrite($handle, "-- Demo Builder Database Backup\n");
        fwrite($handle, "-- Generated: " . wp_date('Y-m-d H:i:s') . "\n");
        fwrite($handle, "-- Database: " . DB_NAME . "\n\n");
        fwrite($handle, "SET FOREIGN_KEY_CHECKS=0;\n\n");

        // Process each table
        foreach ($tables as $index => $table) {
            // Update progress
            if ($operation_id && $index % self::PROGRESS_LOG_INTERVAL === 0) {
                $this->update_progress($operation_id, [
                    'status' => 'running',
                    'percent' => round(($index / $total_tables) * 100),
                    'message' => sprintf(__('Processing table: %s', 'demo-builder'), $table),
                    'step' => $index + 1,
                    'total_steps' => $total_tables,
                ]);
            }

            // Check memory
            if ($index % self::MEMORY_CHECK_INTERVAL === 0) {
                $this->check_memory_usage();
            }

            // Write table structure
            $create = $wpdb->get_row("SHOW CREATE TABLE `{$table}`", ARRAY_N);
            if ($create) {
                fwrite($handle, "DROP TABLE IF EXISTS `{$table}`;\n");
                fwrite($handle, $create[1] . ";\n\n");
            }

            // Backup table data in chunks
            $this->backup_table_chunked($handle, $table);
            
            // Reset execution time
            if (function_exists('set_time_limit')) {
                set_time_limit(30);
            }
        }

        // Write footer
        fwrite($handle, "\nSET FOREIGN_KEY_CHECKS=1;\n");
        fclose($handle);

        // Update progress
        if ($operation_id) {
            $this->update_progress($operation_id, [
                'status' => 'complete',
                'percent' => 100,
                'message' => __('Database backup complete!', 'demo-builder'),
                'step' => $total_tables,
                'total_steps' => $total_tables,
            ]);
        }

        return [
            'success' => true,
            'file' => $output_file,
            'size' => filesize($output_file),
        ];
    }

    /**
     * Backup single table in chunks
     *
     * @param resource $handle File handle
     * @param string $table Table name
     */
    private function backup_table_chunked($handle, $table) {
        global $wpdb;
        
        $chunk_size = self::DB_CHUNK_SIZE;
        $offset = 0;
        $has_more = true;

        while ($has_more) {
            // Fetch chunk of rows
            $rows = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM `{$table}` LIMIT %d OFFSET %d",
                    $chunk_size,
                    $offset
                ),
                ARRAY_A
            );

            if (empty($rows)) {
                $has_more = false;
                break;
            }

            // Write INSERT statements in batches
            $this->write_insert_statements($handle, $table, $rows);

            $offset += $chunk_size;
            $has_more = count($rows) === $chunk_size;

            // Free memory
            unset($rows);
            wp_cache_flush();
            
            // Reset execution time
            if (function_exists('set_time_limit')) {
                set_time_limit(30);
            }
        }
    }

    /**
     * Write INSERT statements
     *
     * @param resource $handle File handle
     * @param string $table Table name
     * @param array $rows Rows to insert
     */
    private function write_insert_statements($handle, $table, $rows) {
        global $wpdb;
        
        $batch_size = self::INSERT_BATCH_SIZE;
        $total = count($rows);

        for ($i = 0; $i < $total; $i += $batch_size) {
            $batch = array_slice($rows, $i, $batch_size);
            
            if (empty($batch)) {
                continue;
            }

            // Get column names
            $columns = array_keys($batch[0]);
            $columns_str = '`' . implode('`, `', $columns) . '`';

            // Format values
            $values = [];
            foreach ($batch as $row) {
                $row_values = [];
                foreach ($row as $value) {
                    if ($value === null) {
                        $row_values[] = 'NULL';
                    } else {
                        $row_values[] = "'" . $wpdb->_real_escape($value) . "'";
                    }
                }
                $values[] = '(' . implode(', ', $row_values) . ')';
            }

            // Write INSERT
            $sql = "INSERT INTO `{$table}` ({$columns_str}) VALUES\n";
            $sql .= implode(",\n", $values) . ";\n\n";
            fwrite($handle, $sql);
        }
    }

    /**
     * Restore database in chunks
     *
     * @param string $sql_file SQL file path
     * @param string $operation_id Operation ID for progress tracking
     * @return array Result
     */
    public function restore_database_chunked($sql_file, $operation_id = '') {
        global $wpdb;
        
        $this->prepare_for_large_operation();

        if (!file_exists($sql_file)) {
            return ['success' => false, 'error' => __('SQL file not found.', 'demo-builder')];
        }

        $file_size = filesize($sql_file);
        $handle = fopen($sql_file, 'rb');
        if (!$handle) {
            return ['success' => false, 'error' => __('Cannot open SQL file.', 'demo-builder')];
        }

        $buffer = '';
        $chunk_size = self::SQL_READ_CHUNK_SIZE;
        $queries_executed = 0;
        $bytes_read = 0;

        while (!feof($handle)) {
            $chunk = fread($handle, $chunk_size);
            $buffer .= $chunk;
            $bytes_read += strlen($chunk);

            // Look for complete statements
            while (($pos = strpos($buffer, ";\n")) !== false) {
                $statement = substr($buffer, 0, $pos + 1);
                $buffer = substr($buffer, $pos + 2);

                // Clean statement
                $statement = trim($statement);
                if (empty($statement) || strpos($statement, '--') === 0) {
                    continue;
                }

                // Execute statement
                $result = $wpdb->query($statement);
                $queries_executed++;

                // Update progress every 100 queries
                if ($operation_id && $queries_executed % 100 === 0) {
                    $percent = min(99, round(($bytes_read / $file_size) * 100));
                    $this->update_progress($operation_id, [
                        'status' => 'running',
                        'percent' => $percent,
                        'message' => sprintf(__('Executed %d queries...', 'demo-builder'), $queries_executed),
                        'step' => $queries_executed,
                        'total_steps' => 0,
                    ]);

                    // Reset execution time
                    if (function_exists('set_time_limit')) {
                        set_time_limit(30);
                    }
                }
            }
        }

        fclose($handle);

        // Handle remaining buffer
        if (!empty(trim($buffer))) {
            $wpdb->query(trim($buffer));
            $queries_executed++;
        }

        // Update progress
        if ($operation_id) {
            $this->update_progress($operation_id, [
                'status' => 'complete',
                'percent' => 100,
                'message' => sprintf(__('Restore complete! %d queries executed.', 'demo-builder'), $queries_executed),
                'step' => $queries_executed,
                'total_steps' => $queries_executed,
            ]);
        }

        return [
            'success' => true,
            'queries' => $queries_executed,
        ];
    }

    /**
     * Stream file download
     *
     * @param string $file_path File to download
     * @param string $filename Download filename
     */
    public function stream_file_download($file_path, $filename = '') {
        if (!file_exists($file_path)) {
            wp_die(__('File not found.', 'demo-builder'));
        }

        $this->prepare_for_large_operation();

        $filename = $filename ?: basename($file_path);
        $file_size = filesize($file_path);

        // Set headers
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . $file_size);
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');

        // Stream file
        $handle = fopen($file_path, 'rb');
        if (!$handle) {
            wp_die(__('Cannot open file.', 'demo-builder'));
        }

        while (!feof($handle)) {
            $buffer = fread($handle, self::DOWNLOAD_CHUNK_SIZE);
            echo $buffer;
            
            if (ob_get_level()) {
                ob_flush();
            }
            flush();

            // Reset execution time
            if (function_exists('set_time_limit')) {
                set_time_limit(30);
            }
        }

        fclose($handle);
        exit;
    }

    /**
     * Update operation progress
     *
     * @param string $operation_id Operation ID
     * @param array $data Progress data
     */
    public function update_progress($operation_id, $data) {
        $data['updated_at'] = time();
        set_transient('demo_builder_progress_' . $operation_id, $data, 3600);
    }

    /**
     * Check memory usage
     */
    private function check_memory_usage() {
        $current = memory_get_usage(true);
        $limit = $this->parse_size(ini_get('memory_limit'));

        // Warning at 80% usage
        if ($limit > 0 && $current > $limit * 0.8) {
            // Force garbage collection
            if (function_exists('gc_collect_cycles')) {
                gc_collect_cycles();
            }

            // Clear WP cache
            wp_cache_flush();
        }
    }

    /**
     * Parse size string to bytes
     *
     * @param string $size Size string
     * @return int
     */
    private function parse_size($size) {
        $size = trim($size);
        if (empty($size)) {
            return 0;
        }
        
        $last = strtolower($size[strlen($size) - 1]);
        $size = (int) $size;
        
        switch ($last) {
            case 'g':
                $size *= 1024 * 1024 * 1024;
                break;
            case 'm':
                $size *= 1024 * 1024;
                break;
            case 'k':
                $size *= 1024;
                break;
        }
        
        return $size;
    }

    // AJAX Handlers

    /**
     * AJAX: Get progress
     */
    public function ajax_get_progress() {
        check_ajax_referer('demo_builder_nonce', 'nonce');

        $operation_id = isset($_POST['operation_id']) ? sanitize_text_field(wp_unslash($_POST['operation_id'])) : '';
        
        if (empty($operation_id)) {
            wp_send_json_error(['message' => __('Invalid operation ID.', 'demo-builder')]);
        }

        $progress = get_transient('demo_builder_progress_' . $operation_id);
        
        if ($progress) {
            wp_send_json_success($progress);
        } else {
            wp_send_json_error(['message' => __('No progress data.', 'demo-builder')]);
        }
    }

    /**
     * AJAX: Cancel operation
     */
    public function ajax_cancel_operation() {
        check_ajax_referer('demo_builder_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied.', 'demo-builder')]);
        }

        $operation_id = isset($_POST['operation_id']) ? sanitize_text_field(wp_unslash($_POST['operation_id'])) : '';
        
        if (!empty($operation_id)) {
            // Mark operation as cancelled
            $this->update_progress($operation_id, [
                'status' => 'cancelled',
                'percent' => 0,
                'message' => __('Operation cancelled.', 'demo-builder'),
            ]);
            
            // Delete progress transient
            delete_transient('demo_builder_progress_' . $operation_id);
        }

        wp_send_json_success(['message' => __('Operation cancelled.', 'demo-builder')]);
    }
}

// Initialize
Demo_Builder_Performance::get_instance();
