<?php
/**
 * Permission Hooks Class
 *
 * Handles permission restrictions for demo accounts
 *
 * @package DemoBuilder
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Demo_Builder_Permission_Hooks
 */
class Demo_Builder_Permission_Hooks {

    /**
     * Instance
     *
     * @var Demo_Builder_Permission_Hooks
     */
    private static $instance = null;

    /**
     * Settings
     *
     * @var array
     */
    private $settings = [];

    /**
     * Permission settings
     *
     * @var array
     */
    private $permissions = [];

    /**
     * Blocked capabilities for demo accounts
     *
     * @var array
     */
    private $blocked_caps = [
        'delete_users',
        'remove_users',
        'create_users',
        'activate_plugins',
        'deactivate_plugins',
        'delete_plugins',
        'install_plugins',
        'upload_plugins',
        'update_plugins',
        'switch_themes',
        'delete_themes',
        'install_themes',
        'upload_themes',
        'update_themes',
        'edit_themes',
        'edit_files',
        'update_core',
    ];

    /**
     * Restricted admin pages
     *
     * @var array
     */
    private $restricted_pages = [
        'plugin-install.php',
        'theme-install.php',
        'options-general.php',
        'options-writing.php',
        'options-reading.php',
        'options-discussion.php',
        'options-media.php',
        'options-permalink.php',
        'options-privacy.php',
        'update-core.php',
        'export.php',
        'tools.php',
    ];

    /**
     * Get instance
     *
     * @return Demo_Builder_Permission_Hooks
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
        $this->settings = get_option('demo_builder_settings', []);
        $this->permissions = $this->settings['permissions'] ?? $this->get_default_permissions();
        $this->init_hooks();
    }

    /**
     * Get default permission settings
     *
     * @return array
     */
    private function get_default_permissions() {
        return [
            'allow_profile_update' => false,
            'allow_password_change' => false,
            'allow_user_status_change' => false,
            'allow_settings_access' => false,
            'custom_url_blacklist' => [],
            'protected_user_ids' => [1],
        ];
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Capability filtering
        add_filter('user_has_cap', [$this, 'filter_user_capabilities'], 100, 4);
        add_filter('map_meta_cap', [$this, 'filter_meta_capabilities'], 100, 4);
        
        // Admin page restrictions
        add_action('admin_init', [$this, 'check_admin_page_access'], 1);
        add_action('current_screen', [$this, 'check_screen_access']);
        
        // User edit protection
        add_action('edit_user_profile', [$this, 'check_edit_user_access']);
        add_action('personal_options_update', [$this, 'block_profile_update']);
        add_action('edit_user_profile_update', [$this, 'block_user_edit']);
        
        // Plugin/Theme actions
        add_action('check_admin_referer', [$this, 'block_plugin_theme_actions'], 10, 2);
        
        // Delete user protection
        add_action('delete_user', [$this, 'protect_super_admin'], 10, 2);
        add_action('wpmu_delete_user', [$this, 'protect_super_admin'], 10, 2);
        
        // Hide admin menu items
        add_action('admin_menu', [$this, 'hide_restricted_menus'], 999);
        
        // Admin notice for blocked actions
        add_action('admin_notices', [$this, 'show_blocked_notice']);
        
        // AJAX for saving permissions
        add_action('wp_ajax_demo_builder_save_permissions', [$this, 'ajax_save_permissions']);
    }

    /**
     * Check if user is demo account
     *
     * @param int $user_id User ID
     * @return bool
     */
    public function is_demo_account($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        if (!$user_id) {
            return false;
        }
        
        // Check user meta first (faster)
        $is_demo = get_user_meta($user_id, '_demo_builder_demo_user', true);
        
        if ($is_demo) {
            // Verify in database
            global $wpdb;
            $table = $wpdb->prefix . 'demobuilder_demo_accounts';
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$table} WHERE user_id = %d AND is_active = 1",
                $user_id
            ));
            
            return (bool) $exists;
        }
        
        return false;
    }

    /**
     * Check if user ID is protected
     *
     * @param int $user_id User ID
     * @return bool
     */
    private function is_protected_user($user_id) {
        $protected = $this->permissions['protected_user_ids'] ?? [1];
        return in_array($user_id, (array) $protected);
    }

    /**
     * Filter user capabilities
     *
     * @param array $allcaps All capabilities
     * @param array $caps Requested capabilities
     * @param array $args Arguments
     * @param WP_User $user User object
     * @return array
     */
    public function filter_user_capabilities($allcaps, $caps, $args, $user) {
        if (!$user || !$this->is_demo_account($user->ID)) {
            return $allcaps;
        }
        
        // Block all restricted capabilities
        foreach ($this->blocked_caps as $cap) {
            $allcaps[$cap] = false;
        }
        
        // Block manage_options if settings access not allowed
        if (empty($this->permissions['allow_settings_access'])) {
            $allcaps['manage_options'] = false;
        }
        
        // Block user editing capabilities
        $allcaps['edit_users'] = false;
        
        return $allcaps;
    }

    /**
     * Filter meta capabilities
     *
     * @param array $caps Required capabilities
     * @param string $cap Requested capability
     * @param int $user_id User ID
     * @param array $args Arguments
     * @return array
     */
    public function filter_meta_capabilities($caps, $cap, $user_id, $args) {
        if (!$this->is_demo_account($user_id)) {
            return $caps;
        }
        
        // Protect specific users from editing
        if (in_array($cap, ['edit_user', 'delete_user', 'remove_user'])) {
            $target_user_id = isset($args[0]) ? $args[0] : 0;
            
            // Cannot edit/delete protected users
            if ($this->is_protected_user($target_user_id)) {
                return ['do_not_allow'];
            }
            
            // Cannot edit other demo accounts
            if ($this->is_demo_account($target_user_id) && $target_user_id != $user_id) {
                return ['do_not_allow'];
            }
        }
        
        return $caps;
    }

    /**
     * Check admin page access
     */
    public function check_admin_page_access() {
        if (!is_admin() || wp_doing_ajax()) {
            return;
        }
        
        if (!$this->is_demo_account()) {
            return;
        }
        
        // Get current page
        global $pagenow;
        
        // Check restricted pages
        if (in_array($pagenow, $this->restricted_pages)) {
            $this->redirect_blocked('page');
            return;
        }
        
        // Check custom blacklist
        $blacklist = $this->permissions['custom_url_blacklist'] ?? [];
        if (!empty($blacklist)) {
            $current_url = $_SERVER['REQUEST_URI'] ?? '';
            foreach ($blacklist as $pattern) {
                if (!empty($pattern) && strpos($current_url, $pattern) !== false) {
                    $this->redirect_blocked('custom');
                    return;
                }
            }
        }
        
        // Check plugins.php with delete action
        if ($pagenow === 'plugins.php') {
            $action = isset($_GET['action']) ? $_GET['action'] : '';
            $action2 = isset($_POST['action']) ? $_POST['action'] : '';
            $action2 = isset($_POST['action2']) ? $_POST['action2'] : $action2;
            
            $blocked_actions = ['delete-selected', 'deactivate-selected', 'activate-selected'];
            
            if (in_array($action, $blocked_actions) || in_array($action2, $blocked_actions)) {
                $this->redirect_blocked('plugin_action');
            }
        }
        
        // Check themes.php with delete action
        if ($pagenow === 'themes.php') {
            $action = isset($_GET['action']) ? $_GET['action'] : '';
            if ($action === 'delete') {
                $this->redirect_blocked('theme_action');
            }
        }
        
        // Check users.php with delete action
        if ($pagenow === 'users.php') {
            $action = isset($_GET['action']) ? $_GET['action'] : '';
            if ($action === 'delete') {
                $this->redirect_blocked('user_action');
            }
        }
    }

    /**
     * Check screen access
     *
     * @param WP_Screen $screen Current screen
     */
    public function check_screen_access($screen) {
        if (!$this->is_demo_account()) {
            return;
        }
        
        // Block user edit for protected users
        if ($screen->id === 'user-edit') {
            $user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
            if ($user_id && $this->is_protected_user($user_id)) {
                $this->redirect_blocked('protected_user');
            }
        }
    }

    /**
     * Check edit user access
     *
     * @param WP_User $user User being edited
     */
    public function check_edit_user_access($user) {
        if (!$this->is_demo_account()) {
            return;
        }
        
        if ($this->is_protected_user($user->ID)) {
            wp_die(
                __('You do not have permission to edit this user.', 'demo-builder'),
                __('Permission Denied', 'demo-builder'),
                ['back_link' => true]
            );
        }
    }

    /**
     * Block profile update
     *
     * @param int $user_id User ID
     */
    public function block_profile_update($user_id) {
        if (!$this->is_demo_account($user_id)) {
            return;
        }
        
        // Block password change
        if (empty($this->permissions['allow_password_change']) && !empty($_POST['pass1'])) {
            wp_die(
                __('Demo accounts cannot change passwords.', 'demo-builder'),
                __('Permission Denied', 'demo-builder'),
                ['back_link' => true]
            );
        }
        
        // Block profile update
        if (empty($this->permissions['allow_profile_update'])) {
            $this->log_blocked_action('profile_update');
        }
    }

    /**
     * Block user edit
     *
     * @param int $user_id User ID being edited
     */
    public function block_user_edit($user_id) {
        if (!$this->is_demo_account()) {
            return;
        }
        
        if ($this->is_protected_user($user_id)) {
            wp_die(
                __('You do not have permission to edit this user.', 'demo-builder'),
                __('Permission Denied', 'demo-builder'),
                ['back_link' => true]
            );
        }
    }

    /**
     * Block plugin/theme actions
     *
     * @param string $action Action name
     * @param string $result Result
     */
    public function block_plugin_theme_actions($action, $result) {
        if (!$this->is_demo_account()) {
            return;
        }
        
        $blocked_actions = [
            'activate-plugin',
            'deactivate-plugin',
            'delete-plugin',
            'activate-theme',
            'delete-theme',
            'switch-theme',
            'update-plugin',
            'update-theme',
        ];
        
        foreach ($blocked_actions as $blocked) {
            if (strpos($action, $blocked) !== false) {
                wp_die(
                    __('Demo accounts cannot perform this action.', 'demo-builder'),
                    __('Permission Denied', 'demo-builder'),
                    ['back_link' => true]
                );
            }
        }
    }

    /**
     * Protect super admin from deletion
     *
     * @param int $id User ID
     * @param int|null $reassign Reassign posts to
     */
    public function protect_super_admin($id, $reassign = null) {
        if ($this->is_protected_user($id)) {
            wp_die(
                __('This user is protected and cannot be deleted.', 'demo-builder'),
                __('Permission Denied', 'demo-builder'),
                ['back_link' => true]
            );
        }
    }

    /**
     * Hide restricted menu items
     */
    public function hide_restricted_menus() {
        if (!$this->is_demo_account()) {
            return;
        }
        
        global $menu, $submenu;
        
        // Remove plugin install
        remove_submenu_page('plugins.php', 'plugin-install.php');
        
        // Remove theme install
        remove_submenu_page('themes.php', 'theme-install.php');
        remove_submenu_page('themes.php', 'customize.php');
        
        // Remove tools and options if not allowed
        if (empty($this->permissions['allow_settings_access'])) {
            remove_menu_page('options-general.php');
            remove_menu_page('tools.php');
        }
        
        // Hide update notices
        remove_action('admin_notices', 'update_nag', 3);
        remove_action('admin_notices', 'maintenance_nag', 10);
    }

    /**
     * Redirect for blocked action
     *
     * @param string $reason Reason for blocking
     */
    private function redirect_blocked($reason) {
        $this->log_blocked_action($reason);
        
        // Set transient for notice
        set_transient('demo_builder_blocked_' . get_current_user_id(), $reason, 30);
        
        wp_redirect(admin_url('?demo_blocked=' . $reason));
        exit;
    }

    /**
     * Log blocked action
     *
     * @param string $action Action that was blocked
     */
    private function log_blocked_action($action) {
        if (class_exists('Demo_Builder_Core')) {
            Demo_Builder_Core::get_instance()->log(
                'permission',
                sprintf('Blocked action: %s for user %d', $action, get_current_user_id())
            );
        }
    }

    /**
     * Show blocked notice
     */
    public function show_blocked_notice() {
        $reason = get_transient('demo_builder_blocked_' . get_current_user_id());
        
        if ($reason) {
            delete_transient('demo_builder_blocked_' . get_current_user_id());
            
            $messages = [
                'page' => __('You do not have permission to access that page.', 'demo-builder'),
                'custom' => __('Access to this page is restricted for demo accounts.', 'demo-builder'),
                'plugin_action' => __('Demo accounts cannot modify plugins.', 'demo-builder'),
                'theme_action' => __('Demo accounts cannot modify themes.', 'demo-builder'),
                'user_action' => __('Demo accounts cannot delete users.', 'demo-builder'),
                'protected_user' => __('You cannot edit this protected user.', 'demo-builder'),
                'profile_update' => __('Demo accounts cannot update profiles.', 'demo-builder'),
            ];
            
            $message = $messages[$reason] ?? __('This action is not allowed for demo accounts.', 'demo-builder');
            
            echo '<div class="notice notice-warning is-dismissible">';
            echo '<p><strong>' . esc_html__('Demo Builder:', 'demo-builder') . '</strong> ' . esc_html($message) . '</p>';
            echo '</div>';
        }
    }

    /**
     * AJAX: Save permissions settings
     */
    public function ajax_save_permissions() {
        if (!check_ajax_referer('demo_builder_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => __('Security check failed.', 'demo-builder')]);
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied.', 'demo-builder')]);
        }
        
        $settings = get_option('demo_builder_settings', []);
        
        $permissions = [
            'allow_profile_update' => isset($_POST['allow_profile_update']) && $_POST['allow_profile_update'] === 'true',
            'allow_password_change' => isset($_POST['allow_password_change']) && $_POST['allow_password_change'] === 'true',
            'allow_user_status_change' => isset($_POST['allow_user_status_change']) && $_POST['allow_user_status_change'] === 'true',
            'allow_settings_access' => isset($_POST['allow_settings_access']) && $_POST['allow_settings_access'] === 'true',
            'custom_url_blacklist' => isset($_POST['custom_url_blacklist']) 
                ? array_filter(array_map('trim', explode("\n", sanitize_textarea_field(wp_unslash($_POST['custom_url_blacklist'])))))
                : [],
            'protected_user_ids' => isset($_POST['protected_user_ids'])
                ? array_filter(array_map('intval', explode(',', sanitize_text_field(wp_unslash($_POST['protected_user_ids'])))))
                : [1],
        ];
        
        $settings['permissions'] = $permissions;
        update_option('demo_builder_settings', $settings);
        
        wp_send_json_success(['message' => __('Permissions saved!', 'demo-builder')]);
    }
}

// Initialize
Demo_Builder_Permission_Hooks::get_instance();
