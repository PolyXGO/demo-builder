<?php
/**
 * Dashboard View
 *
 * @package DemoBuilder
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

$settings = get_option('demo_builder_settings', []);
global $wpdb;

// Get stats
$backups_table = $wpdb->prefix . 'demobuilder_backups';
$accounts_table = $wpdb->prefix . 'demobuilder_demo_accounts';

$total_backups = $wpdb->get_var("SELECT COUNT(*) FROM {$backups_table}");
$total_accounts = $wpdb->get_var("SELECT COUNT(*) FROM {$accounts_table}");
$next_restore = wp_next_scheduled('demo_builder_auto_restore');
?>

<div class="wrap db-wrap">
    <div class="db-header">
        <h1 class="db-title">
            <span class="dashicons dashicons-database-view"></span>
            <?php esc_html_e('Demo Builder', 'demo-builder'); ?>
        </h1>
        <span class="db-version"><?php echo 'v' . esc_html(DEMO_BUILDER_VERSION); ?></span>
    </div>

    <div id="demo-builder-dashboard" class="db-dashboard">
        <!-- Stats Cards -->
        <div class="db-stats-grid">
            <!-- Backups -->
            <div class="db-stat-card db-stat-card--primary">
                <div class="db-stat-icon">
                    <span class="dashicons dashicons-database"></span>
                </div>
                <div class="db-stat-content">
                    <div class="db-stat-value"><?php echo esc_html($total_backups); ?></div>
                    <div class="db-stat-label"><?php esc_html_e('Total Backups', 'demo-builder'); ?></div>
                </div>
            </div>

            <!-- Demo Accounts -->
            <div class="db-stat-card db-stat-card--secondary">
                <div class="db-stat-icon">
                    <span class="dashicons dashicons-groups"></span>
                </div>
                <div class="db-stat-content">
                    <div class="db-stat-value"><?php echo esc_html($total_accounts); ?></div>
                    <div class="db-stat-label"><?php esc_html_e('Demo Accounts', 'demo-builder'); ?></div>
                </div>
            </div>

            <!-- Auto-Restore Status -->
            <div class="db-stat-card db-stat-card--info">
                <div class="db-stat-icon">
                    <span class="dashicons dashicons-backup"></span>
                </div>
                <div class="db-stat-content">
                    <div class="db-stat-value">
                        <?php if ($next_restore): ?>
                            <?php echo esc_html(human_time_diff($next_restore)); ?>
                        <?php else: ?>
                            <span class="db-text-muted"><?php esc_html_e('Off', 'demo-builder'); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="db-stat-label"><?php esc_html_e('Next Auto-Restore', 'demo-builder'); ?></div>
                </div>
            </div>

            <!-- System Status -->
            <div class="db-stat-card db-stat-card--success">
                <div class="db-stat-icon">
                    <span class="dashicons dashicons-yes-alt"></span>
                </div>
                <div class="db-stat-content">
                    <div class="db-stat-value">
                        <span class="db-status-dot db-status-dot--active"></span>
                        <?php esc_html_e('Active', 'demo-builder'); ?>
                    </div>
                    <div class="db-stat-label"><?php esc_html_e('Plugin Status', 'demo-builder'); ?></div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="db-section">
            <h2 class="db-section-title"><?php esc_html_e('Quick Actions', 'demo-builder'); ?></h2>
            <div class="db-actions-grid">
                <a href="<?php echo esc_url(admin_url('admin.php?page=demo-builder-backup')); ?>" class="db-action-card">
                    <span class="dashicons dashicons-database-add"></span>
                    <span><?php esc_html_e('Create Backup', 'demo-builder'); ?></span>
                </a>
                <a href="<?php echo esc_url(admin_url('admin.php?page=demo-builder-backup#restore')); ?>" class="db-action-card">
                    <span class="dashicons dashicons-database-import"></span>
                    <span><?php esc_html_e('Restore Backup', 'demo-builder'); ?></span>
                </a>
                <a href="<?php echo esc_url(admin_url('admin.php?page=demo-builder-accounts')); ?>" class="db-action-card">
                    <span class="dashicons dashicons-admin-users"></span>
                    <span><?php esc_html_e('Manage Accounts', 'demo-builder'); ?></span>
                </a>
                <a href="<?php echo esc_url(admin_url('admin.php?page=demo-builder-settings')); ?>" class="db-action-card">
                    <span class="dashicons dashicons-admin-settings"></span>
                    <span><?php esc_html_e('Settings', 'demo-builder'); ?></span>
                </a>
            </div>
        </div>

        <!-- System Info -->
        <div class="db-section">
            <h2 class="db-section-title"><?php esc_html_e('System Information', 'demo-builder'); ?></h2>
            <div class="db-info-table">
                <table class="widefat striped">
                    <tbody>
                        <tr>
                            <td><strong><?php esc_html_e('WordPress Version', 'demo-builder'); ?></strong></td>
                            <td><?php echo esc_html(get_bloginfo('version')); ?></td>
                        </tr>
                        <tr>
                            <td><strong><?php esc_html_e('PHP Version', 'demo-builder'); ?></strong></td>
                            <td><?php echo esc_html(phpversion()); ?></td>
                        </tr>
                        <tr>
                            <td><strong><?php esc_html_e('MySQL Version', 'demo-builder'); ?></strong></td>
                            <td><?php echo esc_html($wpdb->db_version()); ?></td>
                        </tr>
                        <tr>
                            <td><strong><?php esc_html_e('Max Upload Size', 'demo-builder'); ?></strong></td>
                            <td><?php echo esc_html(size_format(wp_max_upload_size())); ?></td>
                        </tr>
                        <tr>
                            <td><strong><?php esc_html_e('PHP Memory Limit', 'demo-builder'); ?></strong></td>
                            <td><?php echo esc_html(ini_get('memory_limit')); ?></td>
                        </tr>
                        <tr>
                            <td><strong><?php esc_html_e('Backup Directory', 'demo-builder'); ?></strong></td>
                            <td>
                                <?php if (is_writable(DEMO_BUILDER_BACKUP_DIR)): ?>
                                    <span class="db-badge db-badge--success"><?php esc_html_e('Writable', 'demo-builder'); ?></span>
                                <?php else: ?>
                                    <span class="db-badge db-badge--danger"><?php esc_html_e('Not Writable', 'demo-builder'); ?></span>
                                <?php endif; ?>
                                <code class="db-code"><?php echo esc_html(DEMO_BUILDER_BACKUP_DIR); ?></code>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
