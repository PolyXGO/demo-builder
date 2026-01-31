<?php
/**
 * Settings View
 *
 * @package DemoBuilder
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

$settings = get_option('demo_builder_settings', []);
$active_tab = isset($_GET['tab']) ? sanitize_text_field(wp_unslash($_GET['tab'])) : 'general';

$tabs = [
    'general' => __('General', 'demo-builder'),
    'backup' => __('Backup', 'demo-builder'),
    'restore' => __('Restore', 'demo-builder'),
    'countdown' => __('Countdown Timer', 'demo-builder'),
    'demo_panel' => __('Demo Panel', 'demo-builder'),
    'telegram' => __('Telegram', 'demo-builder'),
    'permissions' => __('Permissions', 'demo-builder'),
    'maintenance' => __('Maintenance', 'demo-builder'),
];
?>

<div class="wrap db-wrap">
    <div class="db-header">
        <h1 class="db-title">
            <span class="dashicons dashicons-admin-settings"></span>
            <?php esc_html_e('Settings', 'demo-builder'); ?>
        </h1>
    </div>

    <div id="demo-builder-settings" class="db-settings">
        <!-- Tabs Navigation -->
        <nav class="db-tabs">
            <?php foreach ($tabs as $tab_id => $tab_label): ?>
                <a 
                    href="<?php echo esc_url(add_query_arg('tab', $tab_id)); ?>"
                    class="db-tab <?php echo $active_tab === $tab_id ? 'db-tab--active' : ''; ?>"
                >
                    <?php echo esc_html($tab_label); ?>
                </a>
            <?php endforeach; ?>
        </nav>

        <!-- Tab Content -->
        <div class="db-tab-content">
            <?php if ($active_tab === 'general'): ?>
                <!-- General Settings -->
                <form id="db-settings-form" class="db-form" data-tab="general">
                    <div class="db-form-section">
                        <div class="db-form-group">
                            <label class="db-toggle">
                                <input 
                                    type="checkbox" 
                                    name="enable_plugin" 
                                    value="1"
                                    <?php checked(!empty($settings['general']['enable_plugin'])); ?>
                                />
                                <span class="db-toggle-slider"></span>
                                <span class="db-toggle-label"><?php esc_html_e('Enable Plugin', 'demo-builder'); ?></span>
                            </label>
                            <p class="db-form-hint"><?php esc_html_e('Enable or disable all plugin functionality.', 'demo-builder'); ?></p>
                        </div>

                        <div class="db-form-group">
                            <label class="db-toggle">
                                <input 
                                    type="checkbox" 
                                    name="super_admin_only" 
                                    value="1"
                                    <?php checked(!empty($settings['general']['super_admin_only'])); ?>
                                />
                                <span class="db-toggle-slider"></span>
                                <span class="db-toggle-label"><?php esc_html_e('Super Admin Only', 'demo-builder'); ?></span>
                            </label>
                            <p class="db-form-hint"><?php esc_html_e('Only super administrators can access backup/restore features.', 'demo-builder'); ?></p>
                        </div>
                    </div>

                    <div class="db-form-actions">
                        <button type="submit" class="db-btn db-btn--primary">
                            <span class="dashicons dashicons-saved"></span>
                            <?php esc_html_e('Save Settings', 'demo-builder'); ?>
                        </button>
                    </div>
                </form>

            <?php elseif ($active_tab === 'backup'): ?>
                <!-- Backup Settings -->
                <form id="db-settings-form" class="db-form" data-tab="backup">
                    <div class="db-form-section">
                        <h3 class="db-form-section-title"><?php esc_html_e('Auto Backup', 'demo-builder'); ?></h3>
                        
                        <div class="db-form-group">
                            <label class="db-toggle">
                                <input 
                                    type="checkbox" 
                                    name="auto_backup" 
                                    value="1"
                                    <?php checked(!empty($settings['backup']['auto_backup'])); ?>
                                />
                                <span class="db-toggle-slider"></span>
                                <span class="db-toggle-label"><?php esc_html_e('Enable Auto Backup', 'demo-builder'); ?></span>
                            </label>
                        </div>

                        <div class="db-form-group">
                            <label class="db-label"><?php esc_html_e('Schedule', 'demo-builder'); ?></label>
                            <select name="backup_schedule" class="db-select">
                                <option value="hourly" <?php selected($settings['backup']['backup_schedule'] ?? '', 'hourly'); ?>><?php esc_html_e('Hourly', 'demo-builder'); ?></option>
                                <option value="twicedaily" <?php selected($settings['backup']['backup_schedule'] ?? '', 'twicedaily'); ?>><?php esc_html_e('Twice Daily', 'demo-builder'); ?></option>
                                <option value="daily" <?php selected($settings['backup']['backup_schedule'] ?? '', 'daily'); ?>><?php esc_html_e('Daily', 'demo-builder'); ?></option>
                                <option value="weekly" <?php selected($settings['backup']['backup_schedule'] ?? '', 'weekly'); ?>><?php esc_html_e('Weekly', 'demo-builder'); ?></option>
                            </select>
                        </div>

                        <div class="db-form-group">
                            <label class="db-label"><?php esc_html_e('Max Backups to Keep', 'demo-builder'); ?></label>
                            <input 
                                type="number" 
                                name="max_backups" 
                                class="db-input db-input--sm"
                                value="<?php echo esc_attr($settings['backup']['max_backups'] ?? 10); ?>"
                                min="1"
                                max="100"
                            />
                        </div>
                    </div>

                    <div class="db-form-section">
                        <h3 class="db-form-section-title"><?php esc_html_e('Exclusions', 'demo-builder'); ?></h3>
                        
                        <div class="db-form-group">
                            <label class="db-toggle">
                                <input type="checkbox" name="exclude_inactive_plugins" value="1" <?php checked(!empty($settings['backup']['exclude_inactive_plugins'])); ?> />
                                <span class="db-toggle-slider"></span>
                                <span class="db-toggle-label"><?php esc_html_e('Exclude Inactive Plugins', 'demo-builder'); ?></span>
                            </label>
                        </div>

                        <div class="db-form-group">
                            <label class="db-toggle">
                                <input type="checkbox" name="exclude_inactive_themes" value="1" <?php checked(!empty($settings['backup']['exclude_inactive_themes'])); ?> />
                                <span class="db-toggle-slider"></span>
                                <span class="db-toggle-label"><?php esc_html_e('Exclude Inactive Themes', 'demo-builder'); ?></span>
                            </label>
                        </div>

                        <div class="db-form-group">
                            <label class="db-toggle">
                                <input type="checkbox" name="exclude_revisions" value="1" <?php checked(!empty($settings['backup']['exclude_revisions'])); ?> />
                                <span class="db-toggle-slider"></span>
                                <span class="db-toggle-label"><?php esc_html_e('Exclude Post Revisions', 'demo-builder'); ?></span>
                            </label>
                        </div>

                        <div class="db-form-group">
                            <label class="db-toggle">
                                <input type="checkbox" name="exclude_transients" value="1" <?php checked(!empty($settings['backup']['exclude_transients'])); ?> />
                                <span class="db-toggle-slider"></span>
                                <span class="db-toggle-label"><?php esc_html_e('Exclude Transients', 'demo-builder'); ?></span>
                            </label>
                        </div>

                        <div class="db-form-group">
                            <label class="db-toggle">
                                <input type="checkbox" name="exclude_spam_comments" value="1" <?php checked(!empty($settings['backup']['exclude_spam_comments'])); ?> />
                                <span class="db-toggle-slider"></span>
                                <span class="db-toggle-label"><?php esc_html_e('Exclude Spam Comments', 'demo-builder'); ?></span>
                            </label>
                        </div>
                    </div>

                    <div class="db-form-actions">
                        <button type="submit" class="db-btn db-btn--primary">
                            <span class="dashicons dashicons-saved"></span>
                            <?php esc_html_e('Save Settings', 'demo-builder'); ?>
                        </button>
                    </div>
                </form>

            <?php elseif ($active_tab === 'countdown'): ?>
                <!-- Countdown Timer Settings -->
                <form id="db-settings-form" class="db-form" data-tab="countdown">
                    <div class="db-form-section">
                        <div class="db-form-group">
                            <label class="db-toggle">
                                <input type="checkbox" name="enabled" value="1" <?php checked(!empty($settings['countdown']['enabled'])); ?> />
                                <span class="db-toggle-slider"></span>
                                <span class="db-toggle-label"><?php esc_html_e('Enable Countdown Timer', 'demo-builder'); ?></span>
                            </label>
                        </div>

                        <div class="db-form-group">
                            <label class="db-label"><?php esc_html_e('Position', 'demo-builder'); ?></label>
                            <select name="position" class="db-select">
                                <option value="fixed" <?php selected($settings['countdown']['position'] ?? '', 'fixed'); ?>><?php esc_html_e('Fixed (Bottom Right)', 'demo-builder'); ?></option>
                                <option value="header" <?php selected($settings['countdown']['position'] ?? '', 'header'); ?>><?php esc_html_e('Header', 'demo-builder'); ?></option>
                                <option value="admin_bar" <?php selected($settings['countdown']['position'] ?? '', 'admin_bar'); ?>><?php esc_html_e('Admin Bar', 'demo-builder'); ?></option>
                            </select>
                        </div>

                        <div class="db-form-group">
                            <label class="db-toggle">
                                <input type="checkbox" name="show_on_admin" value="1" <?php checked(!empty($settings['countdown']['show_on_admin'])); ?> />
                                <span class="db-toggle-slider"></span>
                                <span class="db-toggle-label"><?php esc_html_e('Show on Admin', 'demo-builder'); ?></span>
                            </label>
                        </div>

                        <div class="db-form-group">
                            <label class="db-toggle">
                                <input type="checkbox" name="show_on_frontend" value="1" <?php checked(!empty($settings['countdown']['show_on_frontend'])); ?> />
                                <span class="db-toggle-slider"></span>
                                <span class="db-toggle-label"><?php esc_html_e('Show on Frontend', 'demo-builder'); ?></span>
                            </label>
                        </div>

                        <div class="db-form-group">
                            <label class="db-label"><?php esc_html_e('Message Template', 'demo-builder'); ?></label>
                            <input 
                                type="text" 
                                name="message" 
                                class="db-input"
                                value="<?php echo esc_attr($settings['countdown']['message'] ?? 'Demo resets in {{countdown}}'); ?>"
                                placeholder="Demo resets in {{countdown}}"
                            />
                            <p class="db-form-hint"><?php esc_html_e('Use {{countdown}} placeholder for the timer.', 'demo-builder'); ?></p>
                        </div>

                        <div class="db-form-group">
                            <label class="db-label"><?php esc_html_e('Overdue Message', 'demo-builder'); ?></label>
                            <input 
                                type="text" 
                                name="overdue_message" 
                                class="db-input"
                                value="<?php echo esc_attr($settings['countdown']['overdue_message'] ?? 'Demo reset in progress...'); ?>"
                            />
                        </div>
                    </div>

                    <div class="db-form-actions">
                        <button type="submit" class="db-btn db-btn--primary">
                            <span class="dashicons dashicons-saved"></span>
                            <?php esc_html_e('Save Settings', 'demo-builder'); ?>
                        </button>
                    </div>
                </form>

            <?php elseif ($active_tab === 'telegram'): ?>
                <!-- Telegram Settings -->
                <form id="db-settings-form" class="db-form" data-tab="telegram">
                    <div class="db-form-section">
                        <div class="db-form-group">
                            <label class="db-toggle">
                                <input type="checkbox" name="enabled" value="1" <?php checked(!empty($settings['telegram']['enabled'])); ?> />
                                <span class="db-toggle-slider"></span>
                                <span class="db-toggle-label"><?php esc_html_e('Enable Telegram Notifications', 'demo-builder'); ?></span>
                            </label>
                        </div>

                        <div class="db-form-group">
                            <label class="db-label"><?php esc_html_e('Bot Token', 'demo-builder'); ?></label>
                            <input 
                                type="text" 
                                name="bot_token" 
                                class="db-input"
                                value="<?php echo esc_attr($settings['telegram']['bot_token'] ?? ''); ?>"
                                placeholder="123456789:ABCdefGHIjklMNOpqrsTUVwxyz"
                            />
                        </div>

                        <div class="db-form-group">
                            <label class="db-label"><?php esc_html_e('Chat ID', 'demo-builder'); ?></label>
                            <input 
                                type="text" 
                                name="chat_id" 
                                class="db-input"
                                value="<?php echo esc_attr($settings['telegram']['chat_id'] ?? ''); ?>"
                                placeholder="-1001234567890"
                            />
                        </div>

                        <div class="db-form-group">
                            <label class="db-toggle">
                                <input type="checkbox" name="notify_backup" value="1" <?php checked(!empty($settings['telegram']['notify_backup'])); ?> />
                                <span class="db-toggle-slider"></span>
                                <span class="db-toggle-label"><?php esc_html_e('Notify on Backup', 'demo-builder'); ?></span>
                            </label>
                        </div>

                        <div class="db-form-group">
                            <label class="db-toggle">
                                <input type="checkbox" name="notify_restore" value="1" <?php checked(!empty($settings['telegram']['notify_restore'])); ?> />
                                <span class="db-toggle-slider"></span>
                                <span class="db-toggle-label"><?php esc_html_e('Notify on Restore', 'demo-builder'); ?></span>
                            </label>
                        </div>
                    </div>

                    <div class="db-form-actions">
                        <button type="button" class="db-btn db-btn--secondary" id="db-test-telegram">
                            <span class="dashicons dashicons-email"></span>
                            <?php esc_html_e('Test Connection', 'demo-builder'); ?>
                        </button>
                        <button type="submit" class="db-btn db-btn--primary">
                            <span class="dashicons dashicons-saved"></span>
                            <?php esc_html_e('Save Settings', 'demo-builder'); ?>
                        </button>
                    </div>
                </form>

            <?php elseif ($active_tab === 'maintenance'): ?>
                <!-- Maintenance Mode Settings -->
                <form id="db-settings-form" class="db-form" data-tab="maintenance">
                    <div class="db-form-section">
                        <div class="db-form-group">
                            <label class="db-toggle">
                                <input type="checkbox" name="enabled" value="1" <?php checked(!empty($settings['maintenance']['enabled'])); ?> />
                                <span class="db-toggle-slider"></span>
                                <span class="db-toggle-label"><?php esc_html_e('Enable Maintenance Mode', 'demo-builder'); ?></span>
                            </label>
                            <p class="db-form-hint db-text-warning"><?php esc_html_e('Warning: This will block all visitors except administrators.', 'demo-builder'); ?></p>
                        </div>

                        <div class="db-form-group">
                            <label class="db-label"><?php esc_html_e('Title', 'demo-builder'); ?></label>
                            <input 
                                type="text" 
                                name="title" 
                                class="db-input"
                                value="<?php echo esc_attr($settings['maintenance']['title'] ?? 'Under Maintenance'); ?>"
                            />
                        </div>

                        <div class="db-form-group">
                            <label class="db-label"><?php esc_html_e('Message', 'demo-builder'); ?></label>
                            <textarea 
                                name="message" 
                                class="db-textarea"
                                rows="3"
                            ><?php echo esc_textarea($settings['maintenance']['message'] ?? 'We are performing scheduled maintenance. Please check back soon.'); ?></textarea>
                        </div>

                        <div class="db-form-group">
                            <label class="db-label"><?php esc_html_e('Bypass Key', 'demo-builder'); ?></label>
                            <input 
                                type="text" 
                                name="bypass_key" 
                                class="db-input"
                                value="<?php echo esc_attr($settings['maintenance']['bypass_key'] ?? ''); ?>"
                                placeholder="secret-key-here"
                            />
                            <p class="db-form-hint"><?php esc_html_e('Visitors can bypass maintenance mode by adding ?bypass=your-key to the URL.', 'demo-builder'); ?></p>
                        </div>
                    </div>

                    <div class="db-form-actions">
                        <button type="submit" class="db-btn db-btn--primary">
                            <span class="dashicons dashicons-saved"></span>
                            <?php esc_html_e('Save Settings', 'demo-builder'); ?>
                        </button>
                    </div>
                </form>

            <?php else: ?>
                <!-- Other tabs - placeholder -->
                <div class="db-notice db-notice--info">
                    <p><?php esc_html_e('Settings for this tab will be available soon.', 'demo-builder'); ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
