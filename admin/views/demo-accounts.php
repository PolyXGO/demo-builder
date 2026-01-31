<?php
/**
 * Demo Accounts View (Enhanced)
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
$accounts = $wpdb->get_results(
    "SELECT a.*, u.user_login, u.user_email, u.display_name 
     FROM {$accounts_table} a 
     LEFT JOIN {$wpdb->users} u ON a.user_id = u.ID 
     ORDER BY a.sort_order ASC"
);

// Counts
$total = count($accounts);
$active = count(array_filter($accounts, fn($a) => $a->is_active));
$inactive = $total - $active;
?>

<div class="wrap db-wrap">
    <div class="db-header">
        <h1 class="db-title">
            <span class="dashicons dashicons-groups"></span>
            <?php esc_html_e('Demo Accounts', 'demo-builder'); ?>
        </h1>
    </div>

    <div id="demo-builder-accounts" v-cloak>
        <!-- Statistics Cards -->
        <div class="db-stats-grid">
            <div class="db-stat-card">
                <div class="db-stat-icon db-stat-icon--primary">
                    <span class="dashicons dashicons-admin-users"></span>
                </div>
                <div class="db-stat-content">
                    <span class="db-stat-value">{{ stats.total }}</span>
                    <span class="db-stat-label"><?php esc_html_e('Total Accounts', 'demo-builder'); ?></span>
                </div>
            </div>
            <div class="db-stat-card">
                <div class="db-stat-icon db-stat-icon--success">
                    <span class="dashicons dashicons-yes-alt"></span>
                </div>
                <div class="db-stat-content">
                    <span class="db-stat-value">{{ stats.active }}</span>
                    <span class="db-stat-label"><?php esc_html_e('Active', 'demo-builder'); ?></span>
                </div>
            </div>
            <div class="db-stat-card">
                <div class="db-stat-icon db-stat-icon--warning">
                    <span class="dashicons dashicons-dismiss"></span>
                </div>
                <div class="db-stat-content">
                    <span class="db-stat-value">{{ stats.inactive }}</span>
                    <span class="db-stat-label"><?php esc_html_e('Inactive', 'demo-builder'); ?></span>
                </div>
            </div>
        </div>

        <!-- Actions Bar -->
        <div class="db-section">
            <div class="db-actions-bar">
                <div class="db-actions-left">
                    <button class="db-btn db-btn--primary" @click="openCreateModal">
                        <span class="dashicons dashicons-plus-alt2"></span>
                        <?php esc_html_e('Link Account', 'demo-builder'); ?>
                    </button>
                    <button class="db-btn db-btn--secondary" @click="openGenerateModal">
                        <span class="dashicons dashicons-admin-users"></span>
                        <?php esc_html_e('Generate Accounts', 'demo-builder'); ?>
                    </button>
                    <button class="db-btn db-btn--danger" @click="truncateAccounts" v-if="accounts.length > 0">
                        <span class="dashicons dashicons-trash"></span>
                        <?php esc_html_e('Remove All', 'demo-builder'); ?>
                    </button>
                </div>
                <div class="db-actions-right">
                    <div class="db-search">
                        <input 
                            type="text" 
                            v-model="searchQuery" 
                            class="db-input" 
                            :placeholder="'<?php esc_attr_e('Search accounts...', 'demo-builder'); ?>'"
                        />
                    </div>
                    <div class="db-filter">
                        <select v-model="filterStatus" class="db-select">
                            <option value="all"><?php esc_html_e('All', 'demo-builder'); ?></option>
                            <option value="active"><?php esc_html_e('Active', 'demo-builder'); ?></option>
                            <option value="inactive"><?php esc_html_e('Inactive', 'demo-builder'); ?></option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Accounts Table -->
        <div class="db-section">
            <div class="db-table-wrap">
                <table class="db-table widefat striped" id="accounts-table">
                    <thead>
                        <tr>
                            <th class="db-table-drag"></th>
                            <th><?php esc_html_e('User', 'demo-builder'); ?></th>
                            <th><?php esc_html_e('Role Name', 'demo-builder'); ?></th>
                            <th><?php esc_html_e('Password', 'demo-builder'); ?></th>
                            <th><?php esc_html_e('Status', 'demo-builder'); ?></th>
                            <th class="db-table-actions"><?php esc_html_e('Actions', 'demo-builder'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="filteredAccounts.length === 0">
                            <td colspan="6" class="db-text-center db-text-muted">
                                <?php esc_html_e('No demo accounts found.', 'demo-builder'); ?>
                            </td>
                        </tr>
                        <tr v-for="account in filteredAccounts" :key="account.id" :data-id="account.id">
                            <td class="db-table-drag">
                                <span class="dashicons dashicons-menu db-drag-handle"></span>
                            </td>
                            <td>
                                <div class="db-user-info">
                                    <strong>{{ account.display_name || account.user_login }}</strong>
                                    <br>
                                    <small class="db-text-muted">{{ account.user_email }}</small>
                                </div>
                            </td>
                            <td>
                                <span class="db-badge db-badge--info">{{ account.role_name || '-' }}</span>
                            </td>
                            <td>
                                <code class="db-password-display">{{ account.password_plain || '••••••' }}</code>
                                <button class="db-btn db-btn--xs db-btn--ghost" @click="copyPassword(account)" title="Copy">
                                    <span class="dashicons dashicons-clipboard"></span>
                                </button>
                            </td>
                            <td>
                                <label class="db-toggle db-toggle--inline">
                                    <input type="checkbox" :checked="account.is_active == 1" @change="toggleAccount(account)" />
                                    <span class="db-toggle-slider"></span>
                                </label>
                            </td>
                            <td class="db-table-actions">
                                <button class="db-btn db-btn--sm db-btn--info" @click="viewCredentials(account)" title="View">
                                    <span class="dashicons dashicons-visibility"></span>
                                </button>
                                <button class="db-btn db-btn--sm db-btn--secondary" @click="editAccount(account)" title="Edit">
                                    <span class="dashicons dashicons-edit"></span>
                                </button>
                                <button class="db-btn db-btn--sm db-btn--danger" @click="deleteAccount(account)" title="Delete">
                                    <span class="dashicons dashicons-trash"></span>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Create/Edit Modal -->
        <div class="db-modal" v-if="showModal" @click.self="closeModal">
            <div class="db-modal-content">
                <div class="db-modal-header">
                    <h3>{{ isEditing ? '<?php esc_html_e('Edit Account', 'demo-builder'); ?>' : '<?php esc_html_e('Link Account', 'demo-builder'); ?>' }}</h3>
                    <button class="db-modal-close" @click="closeModal">&times;</button>
                </div>
                <div class="db-modal-body">
                    <div class="db-form-group" v-if="!isEditing">
                        <label class="db-label"><?php esc_html_e('Select User', 'demo-builder'); ?></label>
                        <select v-model="form.user_id" class="db-select">
                            <option value=""><?php esc_html_e('-- Select User --', 'demo-builder'); ?></option>
                            <option v-for="user in availableUsers" :key="user.id" :value="user.id" :disabled="user.is_linked">
                                {{ user.display_name }} ({{ user.username }}) {{ user.is_linked ? '- Already linked' : '' }}
                            </option>
                        </select>
                    </div>
                    <div class="db-form-group">
                        <label class="db-label"><?php esc_html_e('Role Name (Display)', 'demo-builder'); ?></label>
                        <input type="text" v-model="form.role_name" class="db-input" placeholder="e.g. Administrator, Editor" />
                    </div>
                    <div class="db-form-group">
                        <label class="db-label"><?php esc_html_e('Password (for display)', 'demo-builder'); ?></label>
                        <input type="text" v-model="form.password_plain" class="db-input" placeholder="Plain password for demo selector" />
                    </div>
                    <div class="db-form-group">
                        <label class="db-label"><?php esc_html_e('Message', 'demo-builder'); ?></label>
                        <textarea v-model="form.message" class="db-textarea" rows="2" placeholder="<?php esc_attr_e('Optional instructions for this account', 'demo-builder'); ?>"></textarea>
                    </div>
                </div>
                <div class="db-modal-footer">
                    <button class="db-btn db-btn--secondary" @click="closeModal"><?php esc_html_e('Cancel', 'demo-builder'); ?></button>
                    <button class="db-btn db-btn--primary" @click="saveAccount" :disabled="isSaving">
                        {{ isSaving ? '<?php esc_html_e('Saving...', 'demo-builder'); ?>' : '<?php esc_html_e('Save', 'demo-builder'); ?>' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Generate Modal -->
        <div class="db-modal" v-if="showGenerateModal" @click.self="closeGenerateModal">
            <div class="db-modal-content">
                <div class="db-modal-header">
                    <h3><?php esc_html_e('Generate Demo Accounts', 'demo-builder'); ?></h3>
                    <button class="db-modal-close" @click="closeGenerateModal">&times;</button>
                </div>
                <div class="db-modal-body">
                    <div class="db-form-group">
                        <label class="db-label"><?php esc_html_e('Number of Accounts', 'demo-builder'); ?></label>
                        <input type="number" v-model="generateForm.count" class="db-input" min="1" max="50" />
                    </div>
                    <div class="db-form-group">
                        <label class="db-label"><?php esc_html_e('WordPress Role', 'demo-builder'); ?></label>
                        <select v-model="generateForm.role" class="db-select">
                            <option v-for="role in wpRoles" :key="role.slug" :value="role.slug">{{ role.name }}</option>
                        </select>
                    </div>
                    <div class="db-form-group">
                        <label class="db-label"><?php esc_html_e('Display Role Name', 'demo-builder'); ?></label>
                        <input type="text" v-model="generateForm.role_name" class="db-input" placeholder="e.g. Demo User" />
                    </div>
                    <div class="db-form-group">
                        <label class="db-label"><?php esc_html_e('Password Type', 'demo-builder'); ?></label>
                        <select v-model="generateForm.password_type" class="db-select">
                            <option value="random"><?php esc_html_e('Random', 'demo-builder'); ?></option>
                            <option value="fixed"><?php esc_html_e('Fixed Password', 'demo-builder'); ?></option>
                        </select>
                    </div>
                    <div class="db-form-group" v-if="generateForm.password_type === 'fixed'">
                        <label class="db-label"><?php esc_html_e('Password', 'demo-builder'); ?></label>
                        <input type="text" v-model="generateForm.password_value" class="db-input" />
                    </div>
                </div>
                <div class="db-modal-footer">
                    <button class="db-btn db-btn--secondary" @click="closeGenerateModal"><?php esc_html_e('Cancel', 'demo-builder'); ?></button>
                    <button class="db-btn db-btn--primary" @click="generateAccounts" :disabled="isGenerating">
                        {{ isGenerating ? '<?php esc_html_e('Generating...', 'demo-builder'); ?>' : '<?php esc_html_e('Generate', 'demo-builder'); ?>' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Credentials Modal -->
        <div class="db-modal" v-if="showCredentialsModal" @click.self="closeCredentialsModal">
            <div class="db-modal-content db-modal-content--sm">
                <div class="db-modal-header">
                    <h3><?php esc_html_e('Account Credentials', 'demo-builder'); ?></h3>
                    <button class="db-modal-close" @click="closeCredentialsModal">&times;</button>
                </div>
                <div class="db-modal-body">
                    <div class="db-credentials-card">
                        <div class="db-credentials-row">
                            <label><?php esc_html_e('Username:', 'demo-builder'); ?></label>
                            <div class="db-credentials-value">
                                <code>{{ selectedAccount.user_login }}</code>
                                <button class="db-btn db-btn--xs db-btn--ghost" @click="copyToClipboard(selectedAccount.user_login)">
                                    <span class="dashicons dashicons-clipboard"></span>
                                </button>
                            </div>
                        </div>
                        <div class="db-credentials-row">
                            <label><?php esc_html_e('Password:', 'demo-builder'); ?></label>
                            <div class="db-credentials-value">
                                <code>{{ selectedAccount.password_plain }}</code>
                                <button class="db-btn db-btn--xs db-btn--ghost" @click="copyToClipboard(selectedAccount.password_plain)">
                                    <span class="dashicons dashicons-clipboard"></span>
                                </button>
                            </div>
                        </div>
                        <div class="db-credentials-row" v-if="selectedAccount.role_name">
                            <label><?php esc_html_e('Role:', 'demo-builder'); ?></label>
                            <div class="db-credentials-value">
                                <span class="db-badge db-badge--info">{{ selectedAccount.role_name }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.db-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: var(--db-space-lg);
    margin-bottom: var(--db-space-xl);
}
.db-stat-card {
    background: var(--db-bg);
    border: 1px solid var(--db-border);
    border-radius: var(--db-radius-lg);
    padding: var(--db-space-lg);
    display: flex;
    align-items: center;
    gap: var(--db-space-md);
}
.db-stat-icon {
    width: 48px;
    height: 48px;
    border-radius: var(--db-radius);
    display: flex;
    align-items: center;
    justify-content: center;
}
.db-stat-icon .dashicons {
    font-size: 24px;
    width: 24px;
    height: 24px;
}
.db-stat-icon--primary {
    background: var(--db-primary-light);
    color: var(--db-primary);
}
.db-stat-icon--success {
    background: #D1FAE5;
    color: #059669;
}
.db-stat-icon--warning {
    background: #FEF3C7;
    color: #D97706;
}
.db-stat-content {
    display: flex;
    flex-direction: column;
}
.db-stat-value {
    font-size: 24px;
    font-weight: 700;
    color: var(--db-text);
}
.db-stat-label {
    font-size: 13px;
    color: var(--db-text-muted);
}
.db-actions-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: var(--db-space-md);
    flex-wrap: wrap;
}
.db-actions-left, .db-actions-right {
    display: flex;
    gap: var(--db-space-sm);
    align-items: center;
}
.db-table-drag {
    width: 40px;
    text-align: center;
}
.db-drag-handle {
    cursor: grab;
    color: var(--db-text-muted);
}
.db-password-display {
    font-size: 12px;
    background: var(--db-bg-alt);
    padding: 2px 6px;
    border-radius: 4px;
}
.db-toggle--inline {
    margin: 0;
}
.db-credentials-card {
    background: var(--db-bg-alt);
    border-radius: var(--db-radius);
    padding: var(--db-space-lg);
}
.db-credentials-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: var(--db-space-sm) 0;
    border-bottom: 1px solid var(--db-border);
}
.db-credentials-row:last-child {
    border-bottom: none;
}
.db-credentials-row label {
    font-weight: 500;
    color: var(--db-text-muted);
}
.db-credentials-value {
    display: flex;
    align-items: center;
    gap: var(--db-space-sm);
}
.db-credentials-value code {
    font-size: 14px;
    font-weight: 600;
}
</style>

<script>
// Initial data from PHP
window.demoBuilderAccounts = <?php echo wp_json_encode($accounts); ?>;
</script>
