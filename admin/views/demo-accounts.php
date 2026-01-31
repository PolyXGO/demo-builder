<?php
/**
 * Demo Accounts View
 *
 * @package DemoBuilder
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

// Get demo accounts
$accounts_table = $wpdb->prefix . 'demobuilder_demo_accounts';
$accounts = $wpdb->get_results("
    SELECT da.*, u.user_login, u.user_email, u.display_name as wp_display_name
    FROM {$accounts_table} da
    LEFT JOIN {$wpdb->users} u ON da.user_id = u.ID
    ORDER BY da.sort_order ASC, da.created_at DESC
");

// Get available users (not yet demo accounts)
$existing_ids = array_column($accounts, 'user_id');
$existing_ids = array_filter($existing_ids);
$exclude_ids = !empty($existing_ids) ? implode(',', array_map('intval', $existing_ids)) : '0';
$available_users = $wpdb->get_results("
    SELECT ID, user_login, user_email, display_name 
    FROM {$wpdb->users} 
    WHERE ID NOT IN ({$exclude_ids})
    ORDER BY display_name ASC
");

// Get roles
$wp_roles = wp_roles();
$roles = $wp_roles->get_names();
?>

<div class="wrap db-wrap">
    <div class="db-header">
        <h1 class="db-title">
            <span class="dashicons dashicons-groups"></span>
            <?php esc_html_e('Demo Accounts', 'demo-builder'); ?>
        </h1>
    </div>

    <div id="demo-builder-accounts" v-cloak>
        <!-- Stats -->
        <div class="db-stats-grid db-stats-grid--sm">
            <div class="db-stat-card db-stat-card--primary">
                <div class="db-stat-value">{{ accounts.length }}</div>
                <div class="db-stat-label"><?php esc_html_e('Total Accounts', 'demo-builder'); ?></div>
            </div>
            <div class="db-stat-card db-stat-card--success">
                <div class="db-stat-value">{{ activeAccounts }}</div>
                <div class="db-stat-label"><?php esc_html_e('Active', 'demo-builder'); ?></div>
            </div>
            <div class="db-stat-card db-stat-card--secondary">
                <div class="db-stat-value">{{ inactiveAccounts }}</div>
                <div class="db-stat-label"><?php esc_html_e('Inactive', 'demo-builder'); ?></div>
            </div>
        </div>

        <!-- Actions -->
        <div class="db-toolbar">
            <button 
                type="button" 
                class="db-btn db-btn--primary"
                @click="showAddModal = true"
            >
                <span class="dashicons dashicons-plus-alt"></span>
                <?php esc_html_e('Add Account', 'demo-builder'); ?>
            </button>
            <button 
                type="button" 
                class="db-btn db-btn--secondary"
                @click="generateAccounts"
                data-popup-title="<?php esc_attr_e('Generate Demo Accounts', 'demo-builder'); ?>"
                data-popup-text="<?php esc_attr_e('This will create demo accounts for all WordPress roles.', 'demo-builder'); ?>"
                data-popup-confirm="<?php esc_attr_e('Generate', 'demo-builder'); ?>"
                data-popup-cancel="<?php esc_attr_e('Cancel', 'demo-builder'); ?>"
            >
                <span class="dashicons dashicons-admin-users"></span>
                <?php esc_html_e('Generate Accounts', 'demo-builder'); ?>
            </button>
            <button 
                type="button" 
                class="db-btn db-btn--danger"
                @click="truncateAccounts"
                data-popup-title="<?php esc_attr_e('Remove All Accounts', 'demo-builder'); ?>"
                data-popup-text="<?php esc_attr_e('This will remove all demo accounts. This action cannot be undone.', 'demo-builder'); ?>"
                data-popup-confirm="<?php esc_attr_e('Yes, remove all', 'demo-builder'); ?>"
                data-popup-cancel="<?php esc_attr_e('Cancel', 'demo-builder'); ?>"
            >
                <span class="dashicons dashicons-trash"></span>
                <?php esc_html_e('Remove All', 'demo-builder'); ?>
            </button>
        </div>

        <!-- Accounts Table -->
        <div class="db-section">
            <div class="db-table-wrap">
                <table class="db-table widefat striped">
                    <thead>
                        <tr>
                            <th style="width: 50px;"><?php esc_html_e('Order', 'demo-builder'); ?></th>
                            <th><?php esc_html_e('Display Name', 'demo-builder'); ?></th>
                            <th><?php esc_html_e('WordPress User', 'demo-builder'); ?></th>
                            <th><?php esc_html_e('Role', 'demo-builder'); ?></th>
                            <th><?php esc_html_e('Status', 'demo-builder'); ?></th>
                            <th class="db-table-actions"><?php esc_html_e('Actions', 'demo-builder'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="accounts.length === 0">
                            <td colspan="6" class="db-text-center db-text-muted">
                                <?php esc_html_e('No demo accounts found. Add your first account above.', 'demo-builder'); ?>
                            </td>
                        </tr>
                        <tr v-for="(account, index) in accounts" :key="account.id">
                            <td>
                                <span class="db-sort-handle">
                                    <span class="dashicons dashicons-menu"></span>
                                    {{ index + 1 }}
                                </span>
                            </td>
                            <td>
                                <strong>{{ account.display_name || account.wp_display_name }}</strong>
                                <br v-if="account.description">
                                <small class="db-text-muted">{{ account.description }}</small>
                            </td>
                            <td>
                                {{ account.user_login }}
                                <br>
                                <small class="db-text-muted">{{ account.user_email }}</small>
                            </td>
                            <td>
                                <span class="db-badge db-badge--secondary">{{ account.role_name }}</span>
                            </td>
                            <td>
                                <span 
                                    class="db-status-dot" 
                                    :class="account.is_active == 1 ? 'db-status-dot--active' : 'db-status-dot--inactive'"
                                ></span>
                                {{ account.is_active == 1 ? '<?php esc_attr_e('Active', 'demo-builder'); ?>' : '<?php esc_attr_e('Inactive', 'demo-builder'); ?>' }}
                            </td>
                            <td class="db-table-actions">
                                <button 
                                    class="db-btn db-btn--sm db-btn--info"
                                    @click="showCredentials(account)"
                                    title="<?php esc_attr_e('View Credentials', 'demo-builder'); ?>"
                                >
                                    <span class="dashicons dashicons-visibility"></span>
                                </button>
                                <button 
                                    class="db-btn db-btn--sm db-btn--secondary"
                                    @click="editAccount(account)"
                                    title="<?php esc_attr_e('Edit', 'demo-builder'); ?>"
                                >
                                    <span class="dashicons dashicons-edit"></span>
                                </button>
                                <button 
                                    class="db-btn db-btn--sm db-btn--danger"
                                    @click="deleteAccount(account)"
                                    title="<?php esc_attr_e('Delete', 'demo-builder'); ?>"
                                >
                                    <span class="dashicons dashicons-trash"></span>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Add/Edit Modal (using SweetAlert2 via JS) -->
    </div>
</div>

<script>
// Initial data from PHP
window.demoBuilderAccounts = <?php echo wp_json_encode($accounts); ?>;
window.demoBuilderAvailableUsers = <?php echo wp_json_encode($available_users); ?>;
window.demoBuilderRoles = <?php echo wp_json_encode($roles); ?>;
</script>
