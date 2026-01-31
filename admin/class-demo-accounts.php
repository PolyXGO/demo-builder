<?php
/**
 * Demo Accounts Class
 *
 * Handles demo account management operations
 *
 * @package DemoBuilder
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Demo_Builder_Demo_Accounts
 */
class Demo_Builder_Demo_Accounts {

    /**
     * Instance
     *
     * @var Demo_Builder_Demo_Accounts
     */
    private static $instance = null;

    /**
     * Table name
     *
     * @var string
     */
    private $table;

    /**
     * Get instance
     *
     * @return Demo_Builder_Demo_Accounts
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
        global $wpdb;
        $this->table = $wpdb->prefix . 'demobuilder_demo_accounts';
        $this->init_hooks();
    }

    /**
     * Initialize AJAX hooks
     */
    private function init_hooks() {
        add_action('wp_ajax_demo_builder_get_demo_accounts', [$this, 'ajax_get_accounts']);
        add_action('wp_ajax_demo_builder_create_demo_account', [$this, 'ajax_create_account']);
        add_action('wp_ajax_demo_builder_update_demo_account', [$this, 'ajax_update_account']);
        add_action('wp_ajax_demo_builder_delete_demo_account', [$this, 'ajax_delete_account']);
        add_action('wp_ajax_demo_builder_toggle_demo_account', [$this, 'ajax_toggle_account']);
        add_action('wp_ajax_demo_builder_update_sort_order', [$this, 'ajax_update_sort_order']);
        add_action('wp_ajax_demo_builder_generate_demo_accounts', [$this, 'ajax_generate_accounts']);
        add_action('wp_ajax_demo_builder_truncate_demo_accounts', [$this, 'ajax_truncate_accounts']);
        add_action('wp_ajax_demo_builder_get_wp_users', [$this, 'ajax_get_wp_users']);
        add_action('wp_ajax_demo_builder_get_wp_roles', [$this, 'ajax_get_wp_roles']);
    }

    /**
     * Verify AJAX request
     *
     * @return bool
     */
    private function verify_ajax() {
        if (!check_ajax_referer('demo_builder_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => __('Security check failed.', 'demo-builder')]);
            return false;
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied.', 'demo-builder')]);
            return false;
        }
        
        return true;
    }

    /**
     * AJAX: Get demo accounts list
     */
    public function ajax_get_accounts() {
        $this->verify_ajax();
        
        $accounts = $this->get_accounts();
        
        wp_send_json_success(['accounts' => $accounts]);
    }

    /**
     * Get all demo accounts
     *
     * @param array $args Query arguments
     * @return array
     */
    public function get_accounts($args = []) {
        global $wpdb;
        
        $defaults = [
            'status' => 'all',
            'search' => '',
            'orderby' => 'sort_order',
            'order' => 'ASC',
        ];
        
        $args = wp_parse_args($args, $defaults);
        
        $sql = "SELECT a.*, u.user_login, u.user_email, u.display_name 
                FROM {$this->table} a 
                LEFT JOIN {$wpdb->users} u ON a.user_id = u.ID 
                WHERE 1=1";
        
        if ($args['status'] === 'active') {
            $sql .= " AND a.is_active = 1";
        } elseif ($args['status'] === 'inactive') {
            $sql .= " AND a.is_active = 0";
        }
        
        if (!empty($args['search'])) {
            $search = '%' . $wpdb->esc_like($args['search']) . '%';
            $sql .= $wpdb->prepare(" AND (u.user_login LIKE %s OR u.user_email LIKE %s OR u.display_name LIKE %s)", $search, $search, $search);
        }
        
        $sql .= " ORDER BY a.{$args['orderby']} {$args['order']}";
        
        return $wpdb->get_results($sql);
    }

    /**
     * Get active demo accounts
     *
     * @return array
     */
    public function get_active_accounts() {
        return $this->get_accounts(['status' => 'active']);
    }

    /**
     * AJAX: Create demo account
     */
    public function ajax_create_account() {
        $this->verify_ajax();
        
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        $role_name = isset($_POST['role_name']) ? sanitize_text_field(wp_unslash($_POST['role_name'])) : '';
        $password_plain = isset($_POST['password_plain']) ? sanitize_text_field(wp_unslash($_POST['password_plain'])) : '';
        $login_type = isset($_POST['login_type']) ? sanitize_text_field(wp_unslash($_POST['login_type'])) : 'wp-login';
        $redirect_url = isset($_POST['redirect_url']) ? esc_url_raw(wp_unslash($_POST['redirect_url'])) : '';
        $message = isset($_POST['message']) ? sanitize_textarea_field(wp_unslash($_POST['message'])) : '';
        
        if (!$user_id) {
            wp_send_json_error(['message' => __('Please select a user.', 'demo-builder')]);
        }
        
        // Check if user already linked
        global $wpdb;
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$this->table} WHERE user_id = %d",
            $user_id
        ));
        
        if ($existing) {
            wp_send_json_error(['message' => __('This user is already linked as a demo account.', 'demo-builder')]);
        }
        
        // Get max sort order
        $max_order = $wpdb->get_var("SELECT MAX(sort_order) FROM {$this->table}");
        $sort_order = ($max_order !== null) ? $max_order + 1 : 0;
        
        // Insert
        $result = $wpdb->insert($this->table, [
            'user_id' => $user_id,
            'role_name' => $role_name,
            'password_plain' => $password_plain,
            'login_type' => $login_type,
            'redirect_url' => $redirect_url,
            'message' => $message,
            'is_active' => 1,
            'sort_order' => $sort_order,
            'created_at' => current_time('mysql'),
        ]);
        
        if ($result) {
            $account_id = $wpdb->insert_id;
            
            // Mark user as demo user
            update_user_meta($user_id, '_demo_builder_demo_user', 1);
            
            // Get full account data
            $account = $wpdb->get_row($wpdb->prepare(
                "SELECT a.*, u.user_login, u.user_email, u.display_name 
                 FROM {$this->table} a 
                 LEFT JOIN {$wpdb->users} u ON a.user_id = u.ID 
                 WHERE a.id = %d",
                $account_id
            ));
            
            wp_send_json_success([
                'message' => __('Demo account created!', 'demo-builder'),
                'account' => $account
            ]);
        } else {
            wp_send_json_error(['message' => __('Failed to create demo account.', 'demo-builder')]);
        }
    }

    /**
     * AJAX: Update demo account
     */
    public function ajax_update_account() {
        $this->verify_ajax();
        
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        
        if (!$id) {
            wp_send_json_error(['message' => __('Invalid account ID.', 'demo-builder')]);
        }
        
        global $wpdb;
        
        $data = [];
        
        if (isset($_POST['role_name'])) {
            $data['role_name'] = sanitize_text_field(wp_unslash($_POST['role_name']));
        }
        if (isset($_POST['password_plain'])) {
            $data['password_plain'] = sanitize_text_field(wp_unslash($_POST['password_plain']));
        }
        if (isset($_POST['login_type'])) {
            $data['login_type'] = sanitize_text_field(wp_unslash($_POST['login_type']));
        }
        if (isset($_POST['redirect_url'])) {
            $data['redirect_url'] = esc_url_raw(wp_unslash($_POST['redirect_url']));
        }
        if (isset($_POST['message'])) {
            $data['message'] = sanitize_textarea_field(wp_unslash($_POST['message']));
        }
        
        if (empty($data)) {
            wp_send_json_error(['message' => __('No data to update.', 'demo-builder')]);
        }
        
        $data['updated_at'] = current_time('mysql');
        
        $result = $wpdb->update($this->table, $data, ['id' => $id]);
        
        if ($result !== false) {
            $account = $wpdb->get_row($wpdb->prepare(
                "SELECT a.*, u.user_login, u.user_email, u.display_name 
                 FROM {$this->table} a 
                 LEFT JOIN {$wpdb->users} u ON a.user_id = u.ID 
                 WHERE a.id = %d",
                $id
            ));
            
            wp_send_json_success([
                'message' => __('Demo account updated!', 'demo-builder'),
                'account' => $account
            ]);
        } else {
            wp_send_json_error(['message' => __('Failed to update demo account.', 'demo-builder')]);
        }
    }

    /**
     * AJAX: Delete demo account
     */
    public function ajax_delete_account() {
        $this->verify_ajax();
        
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $delete_user = isset($_POST['delete_user']) && $_POST['delete_user'] === 'true';
        
        if (!$id) {
            wp_send_json_error(['message' => __('Invalid account ID.', 'demo-builder')]);
        }
        
        global $wpdb;
        
        // Get account first
        $account = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table} WHERE id = %d",
            $id
        ));
        
        if (!$account) {
            wp_send_json_error(['message' => __('Account not found.', 'demo-builder')]);
        }
        
        // Delete from demo accounts table
        $wpdb->delete($this->table, ['id' => $id]);
        
        // Remove demo user meta
        delete_user_meta($account->user_id, '_demo_builder_demo_user');
        
        // Optionally delete WordPress user
        if ($delete_user && $account->user_id) {
            require_once ABSPATH . 'wp-admin/includes/user.php';
            wp_delete_user($account->user_id);
        }
        
        wp_send_json_success(['message' => __('Demo account deleted!', 'demo-builder')]);
    }

    /**
     * AJAX: Toggle account status
     */
    public function ajax_toggle_account() {
        $this->verify_ajax();
        
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        
        if (!$id) {
            wp_send_json_error(['message' => __('Invalid account ID.', 'demo-builder')]);
        }
        
        global $wpdb;
        
        $current = $wpdb->get_var($wpdb->prepare(
            "SELECT is_active FROM {$this->table} WHERE id = %d",
            $id
        ));
        
        $new_status = $current ? 0 : 1;
        
        $wpdb->update($this->table, ['is_active' => $new_status], ['id' => $id]);
        
        wp_send_json_success([
            'message' => $new_status ? __('Account activated!', 'demo-builder') : __('Account deactivated!', 'demo-builder'),
            'is_active' => $new_status
        ]);
    }

    /**
     * AJAX: Update sort order
     */
    public function ajax_update_sort_order() {
        $this->verify_ajax();
        
        $order = isset($_POST['order']) ? $_POST['order'] : [];
        
        if (!is_array($order)) {
            wp_send_json_error(['message' => __('Invalid order data.', 'demo-builder')]);
        }
        
        global $wpdb;
        
        foreach ($order as $index => $id) {
            $wpdb->update(
                $this->table,
                ['sort_order' => intval($index)],
                ['id' => intval($id)]
            );
        }
        
        wp_send_json_success(['message' => __('Order updated!', 'demo-builder')]);
    }

    /**
     * AJAX: Generate demo accounts
     */
    public function ajax_generate_accounts() {
        $this->verify_ajax();
        
        $count = isset($_POST['count']) ? intval($_POST['count']) : 1;
        $role = isset($_POST['role']) ? sanitize_text_field(wp_unslash($_POST['role'])) : 'subscriber';
        $password_type = isset($_POST['password_type']) ? sanitize_text_field(wp_unslash($_POST['password_type'])) : 'random';
        $password_value = isset($_POST['password_value']) ? sanitize_text_field(wp_unslash($_POST['password_value'])) : '';
        $role_name = isset($_POST['role_name']) ? sanitize_text_field(wp_unslash($_POST['role_name'])) : '';
        
        $count = min($count, 50); // Limit to 50
        
        global $wpdb;
        $created = 0;
        $accounts = [];
        
        for ($i = 0; $i < $count; $i++) {
            $username = 'demo' . time() . $i;
            $email = $username . '@demo.local';
            
            // Generate password
            $password = $password_type === 'fixed' && $password_value 
                ? $password_value 
                : wp_generate_password(12, false);
            
            // Create WordPress user
            $user_id = wp_create_user($username, $password, $email);
            
            if (is_wp_error($user_id)) {
                continue;
            }
            
            // Set role
            $user = new WP_User($user_id);
            $user->set_role($role);
            
            // Get max sort order
            $max_order = $wpdb->get_var("SELECT MAX(sort_order) FROM {$this->table}");
            $sort_order = ($max_order !== null) ? $max_order + 1 : 0;
            
            // Add to demo accounts
            $wpdb->insert($this->table, [
                'user_id' => $user_id,
                'role_name' => $role_name ?: ucfirst($role),
                'password_plain' => $password,
                'login_type' => 'wp-login',
                'redirect_url' => '',
                'is_active' => 1,
                'sort_order' => $sort_order,
                'created_at' => current_time('mysql'),
            ]);
            
            // Mark as demo user
            update_user_meta($user_id, '_demo_builder_demo_user', 1);
            
            $created++;
            
            // Get full account data
            $account_id = $wpdb->insert_id;
            $account = $wpdb->get_row($wpdb->prepare(
                "SELECT a.*, u.user_login, u.user_email, u.display_name 
                 FROM {$this->table} a 
                 LEFT JOIN {$wpdb->users} u ON a.user_id = u.ID 
                 WHERE a.id = %d",
                $account_id
            ));
            
            $accounts[] = $account;
        }
        
        wp_send_json_success([
            'message' => sprintf(__('%d demo accounts created!', 'demo-builder'), $created),
            'accounts' => $accounts
        ]);
    }

    /**
     * AJAX: Truncate demo accounts
     */
    public function ajax_truncate_accounts() {
        $this->verify_ajax();
        
        $delete_users = isset($_POST['delete_users']) && $_POST['delete_users'] === 'true';
        $protected_ids = isset($_POST['protected_ids']) ? array_map('intval', (array)$_POST['protected_ids']) : [];
        
        global $wpdb;
        
        // Get accounts to delete
        $sql = "SELECT * FROM {$this->table}";
        if (!empty($protected_ids)) {
            $placeholders = implode(',', array_fill(0, count($protected_ids), '%d'));
            $sql .= $wpdb->prepare(" WHERE id NOT IN ($placeholders)", $protected_ids);
        }
        
        $accounts = $wpdb->get_results($sql);
        $deleted = 0;
        
        foreach ($accounts as $account) {
            // Delete from demo accounts table
            $wpdb->delete($this->table, ['id' => $account->id]);
            
            // Remove demo user meta
            delete_user_meta($account->user_id, '_demo_builder_demo_user');
            
            // Optionally delete WordPress user
            if ($delete_users && $account->user_id) {
                require_once ABSPATH . 'wp-admin/includes/user.php';
                wp_delete_user($account->user_id);
            }
            
            $deleted++;
        }
        
        wp_send_json_success([
            'message' => sprintf(__('%d demo accounts deleted!', 'demo-builder'), $deleted)
        ]);
    }

    /**
     * AJAX: Get WordPress users for linking
     */
    public function ajax_get_wp_users() {
        $this->verify_ajax();
        
        $search = isset($_POST['search']) ? sanitize_text_field(wp_unslash($_POST['search'])) : '';
        
        $args = [
            'number' => 50,
            'orderby' => 'display_name',
            'order' => 'ASC',
        ];
        
        if ($search) {
            $args['search'] = '*' . $search . '*';
            $args['search_columns'] = ['user_login', 'user_email', 'display_name'];
        }
        
        $users = get_users($args);
        
        // Get already linked user IDs
        global $wpdb;
        $linked_ids = $wpdb->get_col("SELECT user_id FROM {$this->table}");
        
        $result = [];
        foreach ($users as $user) {
            $result[] = [
                'id' => $user->ID,
                'username' => $user->user_login,
                'email' => $user->user_email,
                'display_name' => $user->display_name,
                'role' => implode(', ', $user->roles),
                'is_linked' => in_array($user->ID, $linked_ids),
            ];
        }
        
        wp_send_json_success(['users' => $result]);
    }

    /**
     * AJAX: Get WordPress roles
     */
    public function ajax_get_wp_roles() {
        $this->verify_ajax();
        
        $roles = wp_roles()->get_names();
        $result = [];
        
        foreach ($roles as $slug => $name) {
            $result[] = [
                'slug' => $slug,
                'name' => translate_user_role($name),
            ];
        }
        
        wp_send_json_success(['roles' => $result]);
    }
}

// Initialize
Demo_Builder_Demo_Accounts::get_instance();
