<?php
/**
 * Backup & Restore View
 *
 * @package DemoBuilder
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

$settings = get_option('demo_builder_settings', []);
global $wpdb;

// Get backups
$backups_table = $wpdb->prefix . 'demobuilder_backups';
$backups = $wpdb->get_results("SELECT * FROM {$backups_table} ORDER BY created_at DESC LIMIT 50");
?>

<div class="wrap db-wrap">
    <div class="db-header">
        <h1 class="db-title">
            <span class="dashicons dashicons-database"></span>
            <?php esc_html_e('Backup & Restore', 'demo-builder'); ?>
        </h1>
    </div>

    <div id="demo-builder-backup" v-cloak>
        <!-- Create Backup Section -->
        <div class="db-section">
            <div class="db-section-header">
                <h2 class="db-section-title"><?php esc_html_e('Create Backup', 'demo-builder'); ?></h2>
            </div>
            
            <div class="db-card">
                <div class="db-form-group">
                    <label class="db-label"><?php esc_html_e('Backup Name', 'demo-builder'); ?></label>
                    <input 
                        type="text" 
                        v-model="backupName"
                        class="db-input"
                        placeholder="<?php esc_attr_e('my-backup-2026-01-31', 'demo-builder'); ?>"
                        data-placeholder="<?php esc_attr_e('my-backup-2026-01-31', 'demo-builder'); ?>"
                    />
                </div>

                <div class="db-form-group">
                    <label class="db-label"><?php esc_html_e('Backup Type', 'demo-builder'); ?></label>
                    <div class="db-radio-group">
                        <label class="db-radio">
                            <input type="radio" v-model="backupType" value="full" />
                            <span class="db-radio-mark"></span>
                            <span class="db-radio-label">
                                <strong><?php esc_html_e('Full Backup', 'demo-builder'); ?></strong>
                                <small><?php esc_html_e('Database + Files', 'demo-builder'); ?></small>
                            </span>
                        </label>
                        <label class="db-radio">
                            <input type="radio" v-model="backupType" value="database" />
                            <span class="db-radio-mark"></span>
                            <span class="db-radio-label">
                                <strong><?php esc_html_e('Database Only', 'demo-builder'); ?></strong>
                                <small><?php esc_html_e('Faster, smaller size', 'demo-builder'); ?></small>
                            </span>
                        </label>
                        <label class="db-radio">
                            <input type="radio" v-model="backupType" value="files" />
                            <span class="db-radio-mark"></span>
                            <span class="db-radio-label">
                                <strong><?php esc_html_e('Files Only', 'demo-builder'); ?></strong>
                                <small><?php esc_html_e('wp-content directory', 'demo-builder'); ?></small>
                            </span>
                        </label>
                    </div>
                </div>

                <div class="db-form-actions">
                    <button 
                        type="button" 
                        class="db-btn db-btn--primary"
                        @click="createBackup"
                        :disabled="isCreating"
                        data-popup-title="<?php esc_attr_e('Create Backup', 'demo-builder'); ?>"
                        data-popup-text="<?php esc_attr_e('Are you sure you want to create a new backup?', 'demo-builder'); ?>"
                        data-popup-confirm="<?php esc_attr_e('Yes, create backup', 'demo-builder'); ?>"
                        data-popup-cancel="<?php esc_attr_e('Cancel', 'demo-builder'); ?>"
                        data-success-message="<?php esc_attr_e('Backup created successfully!', 'demo-builder'); ?>"
                        data-error-message="<?php esc_attr_e('Failed to create backup.', 'demo-builder'); ?>"
                    >
                        <span class="dashicons dashicons-database-add"></span>
                        {{ isCreating ? '<?php esc_attr_e('Creating...', 'demo-builder'); ?>' : '<?php esc_attr_e('Create Backup', 'demo-builder'); ?>' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Backups List -->
        <div class="db-section">
            <div class="db-section-header">
                <h2 class="db-section-title"><?php esc_html_e('Backups', 'demo-builder'); ?></h2>
                <span class="db-badge db-badge--info">{{ backups.length }} <?php esc_html_e('backups', 'demo-builder'); ?></span>
            </div>

            <div class="db-table-wrap">
                <table class="db-table widefat striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Name', 'demo-builder'); ?></th>
                            <th><?php esc_html_e('Type', 'demo-builder'); ?></th>
                            <th><?php esc_html_e('Size', 'demo-builder'); ?></th>
                            <th><?php esc_html_e('Created', 'demo-builder'); ?></th>
                            <th><?php esc_html_e('Status', 'demo-builder'); ?></th>
                            <th class="db-table-actions"><?php esc_html_e('Actions', 'demo-builder'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="backups.length === 0">
                            <td colspan="6" class="db-text-center db-text-muted">
                                <?php esc_html_e('No backups found. Create your first backup above.', 'demo-builder'); ?>
                            </td>
                        </tr>
                        <tr v-for="backup in backups" :key="backup.id">
                            <td>
                                <strong>{{ backup.name }}</strong>
                                <br>
                                <small class="db-text-muted">{{ backup.slug }}</small>
                            </td>
                            <td>
                                <span class="db-badge" :class="'db-badge--' + backup.backup_type">
                                    {{ backup.backup_type }}
                                </span>
                            </td>
                            <td>{{ formatSize(backup.file_size) }}</td>
                            <td>{{ formatDate(backup.created_at) }}</td>
                            <td>
                                <span class="db-status-dot" :class="'db-status-dot--' + backup.status"></span>
                                {{ backup.status }}
                            </td>
                            <td class="db-table-actions">
                                <button 
                                    class="db-btn db-btn--sm db-btn--secondary"
                                    @click="restoreBackup(backup)"
                                    :data-popup-title="'<?php esc_attr_e('Restore Backup', 'demo-builder'); ?>'"
                                    :data-popup-text="'<?php esc_attr_e('Are you sure you want to restore this backup? Current data will be replaced.', 'demo-builder'); ?>'"
                                    :data-popup-confirm="'<?php esc_attr_e('Yes, restore', 'demo-builder'); ?>'"
                                    :data-popup-cancel="'<?php esc_attr_e('Cancel', 'demo-builder'); ?>'"
                                >
                                    <span class="dashicons dashicons-backup"></span>
                                </button>
                                <button 
                                    class="db-btn db-btn--sm db-btn--info"
                                    @click="downloadBackup(backup)"
                                >
                                    <span class="dashicons dashicons-download"></span>
                                </button>
                                <button 
                                    class="db-btn db-btn--sm db-btn--danger"
                                    @click="deleteBackup(backup)"
                                    :data-popup-title="'<?php esc_attr_e('Delete Backup', 'demo-builder'); ?>'"
                                    :data-popup-text="'<?php esc_attr_e('Are you sure you want to delete this backup?', 'demo-builder'); ?>'"
                                    :data-popup-confirm="'<?php esc_attr_e('Yes, delete', 'demo-builder'); ?>'"
                                    :data-popup-cancel="'<?php esc_attr_e('Cancel', 'demo-builder'); ?>'"
                                >
                                    <span class="dashicons dashicons-trash"></span>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Upload Backup -->
        <div class="db-section">
            <div class="db-section-header">
                <h2 class="db-section-title"><?php esc_html_e('Upload Backup', 'demo-builder'); ?></h2>
            </div>
            
            <div class="db-card">
                <div class="db-upload-zone" @dragover.prevent @drop.prevent="handleDrop">
                    <input type="file" id="backup-upload" accept=".zip,.sql" @change="handleFileSelect" style="display: none;" />
                    <label for="backup-upload" class="db-upload-label">
                        <span class="dashicons dashicons-upload"></span>
                        <span><?php esc_html_e('Drag & drop backup file here or click to select', 'demo-builder'); ?></span>
                        <small><?php esc_html_e('Supported: .zip, .sql', 'demo-builder'); ?></small>
                    </label>
                </div>
                <p class="db-form-hint">
                    <?php 
                    printf(
                        esc_html__('Max upload size: %s', 'demo-builder'),
                        esc_html(size_format(wp_max_upload_size()))
                    ); 
                    ?>
                </p>
            </div>
        </div>
    </div>
</div>

<script>
// Initial data from PHP
window.demoBuilderBackups = <?php echo wp_json_encode($backups); ?>;
</script>
