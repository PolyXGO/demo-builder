<?php
/**
 * Backup & Restore View (Enhanced)
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

// Get directory sizes (will be loaded via AJAX for performance)
$default_sizes = [
    'uploads' => ['bytes' => 0, 'formatted' => '...'],
    'themes' => ['bytes' => 0, 'formatted' => '...'],
    'plugins' => ['bytes' => 0, 'formatted' => '...'],
    'database' => ['bytes' => 0, 'formatted' => '...'],
];

// Get next restore time
$next_restore = wp_next_scheduled('demo_builder_auto_restore');
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
                        :placeholder="defaultBackupName"
                    />
                    <p class="db-form-hint"><?php esc_html_e('Leave empty to auto-generate based on date', 'demo-builder'); ?></p>
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

                <!-- Directory Selection (for full/files backup) -->
                <div class="db-form-group" v-show="backupType !== 'database'">
                    <label class="db-label"><?php esc_html_e('Directories to Backup', 'demo-builder'); ?></label>
                    <div class="db-checkbox-group">
                        <label class="db-toggle">
                            <input type="checkbox" v-model="directories.uploads" />
                            <span class="db-toggle-slider"></span>
                            <span class="db-toggle-label">
                                wp-content/uploads/ 
                                <span class="db-badge db-badge--info">{{ sizes.uploads.formatted }}</span>
                            </span>
                        </label>
                        <label class="db-toggle">
                            <input type="checkbox" v-model="directories.themes" />
                            <span class="db-toggle-slider"></span>
                            <span class="db-toggle-label">
                                wp-content/themes/
                                <span class="db-badge db-badge--info">{{ sizes.themes.formatted }}</span>
                            </span>
                        </label>
                        <label class="db-toggle">
                            <input type="checkbox" v-model="directories.plugins" />
                            <span class="db-toggle-slider"></span>
                            <span class="db-toggle-label">
                                wp-content/plugins/
                                <span class="db-badge db-badge--info">{{ sizes.plugins.formatted }}</span>
                            </span>
                        </label>
                    </div>
                </div>

                <!-- Exclusion Options Accordion -->
                <div class="db-form-group">
                    <details class="db-accordion">
                        <summary class="db-accordion-header">
                            <span class="dashicons dashicons-filter"></span>
                            <?php esc_html_e('Exclusion Options', 'demo-builder'); ?>
                        </summary>
                        <div class="db-accordion-content">
                            <!-- Database Exclusions -->
                            <div class="db-form-section" v-show="backupType !== 'files'">
                                <h4 class="db-form-section-title"><?php esc_html_e('Database Exclusions', 'demo-builder'); ?></h4>
                                <label class="db-toggle">
                                    <input type="checkbox" v-model="exclusions.revisions" />
                                    <span class="db-toggle-slider"></span>
                                    <span class="db-toggle-label"><?php esc_html_e('Exclude post revisions', 'demo-builder'); ?></span>
                                </label>
                                <label class="db-toggle">
                                    <input type="checkbox" v-model="exclusions.spamComments" />
                                    <span class="db-toggle-slider"></span>
                                    <span class="db-toggle-label"><?php esc_html_e('Exclude spam comments', 'demo-builder'); ?></span>
                                </label>
                                <label class="db-toggle">
                                    <input type="checkbox" v-model="exclusions.transients" />
                                    <span class="db-toggle-slider"></span>
                                    <span class="db-toggle-label"><?php esc_html_e('Exclude transients', 'demo-builder'); ?></span>
                                </label>
                            </div>
                            
                            <!-- File Exclusions -->
                            <div class="db-form-section" v-show="backupType !== 'database'">
                                <h4 class="db-form-section-title"><?php esc_html_e('File Exclusions', 'demo-builder'); ?></h4>
                                <label class="db-toggle">
                                    <input type="checkbox" v-model="exclusions.inactivePlugins" />
                                    <span class="db-toggle-slider"></span>
                                    <span class="db-toggle-label"><?php esc_html_e('Exclude inactive plugins', 'demo-builder'); ?></span>
                                </label>
                                <label class="db-toggle">
                                    <input type="checkbox" v-model="exclusions.inactiveThemes" />
                                    <span class="db-toggle-slider"></span>
                                    <span class="db-toggle-label"><?php esc_html_e('Exclude inactive themes', 'demo-builder'); ?></span>
                                </label>
                                <label class="db-toggle">
                                    <input type="checkbox" v-model="exclusions.cacheFiles" />
                                    <span class="db-toggle-slider"></span>
                                    <span class="db-toggle-label"><?php esc_html_e('Exclude cache directories', 'demo-builder'); ?></span>
                                </label>
                                <label class="db-toggle">
                                    <input type="checkbox" v-model="exclusions.logFiles" />
                                    <span class="db-toggle-slider"></span>
                                    <span class="db-toggle-label"><?php esc_html_e('Exclude log files (*.log)', 'demo-builder'); ?></span>
                                </label>
                            </div>
                        </div>
                    </details>
                </div>

                <div class="db-form-actions">
                    <button 
                        type="button" 
                        class="db-btn db-btn--primary"
                        @click="createBackup"
                        :disabled="isCreating"
                    >
                        <span class="dashicons dashicons-database-add"></span>
                        <span v-if="isCreating"><?php esc_html_e('Creating...', 'demo-builder'); ?></span>
                        <span v-else><?php esc_html_e('Create Backup', 'demo-builder'); ?></span>
                    </button>
                    <span class="db-form-hint" v-if="estimatedSize">
                        <?php esc_html_e('Estimated size:', 'demo-builder'); ?> {{ estimatedSize }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Auto Restore Settings -->
        <div class="db-section">
            <div class="db-section-header">
                <h2 class="db-section-title"><?php esc_html_e('Auto Restore', 'demo-builder'); ?></h2>
                <span class="db-badge db-badge--info" v-if="nextRestoreTime">
                    <?php esc_html_e('Next:', 'demo-builder'); ?> {{ nextRestoreTime }}
                </span>
            </div>
            
            <div class="db-card">
                <label class="db-toggle">
                    <input type="checkbox" v-model="autoRestore.enabled" @change="saveAutoRestoreSettings" />
                    <span class="db-toggle-slider"></span>
                    <span class="db-toggle-label"><?php esc_html_e('Enable Auto Restore', 'demo-builder'); ?></span>
                </label>
                
                <div class="db-form-group" v-show="autoRestore.enabled">
                    <label class="db-label"><?php esc_html_e('Restore Interval', 'demo-builder'); ?></label>
                    <select v-model="autoRestore.interval" class="db-select" @change="saveAutoRestoreSettings">
                        <option value="hourly"><?php esc_html_e('Every Hour', 'demo-builder'); ?></option>
                        <option value="twicedaily"><?php esc_html_e('Twice Daily', 'demo-builder'); ?></option>
                        <option value="daily"><?php esc_html_e('Daily', 'demo-builder'); ?></option>
                        <option value="weekly"><?php esc_html_e('Weekly', 'demo-builder'); ?></option>
                    </select>
                </div>
                
                <div class="db-form-group" v-show="autoRestore.enabled && backups.length > 0">
                    <label class="db-label"><?php esc_html_e('Backup to Restore', 'demo-builder'); ?></label>
                    <select v-model="autoRestore.backupId" class="db-select" @change="saveAutoRestoreSettings">
                        <option value=""><?php esc_html_e('Latest backup', 'demo-builder'); ?></option>
                        <option v-for="backup in backups" :key="backup.id" :value="backup.id">
                            {{ backup.name }} ({{ formatDate(backup.created_at) }})
                        </option>
                    </select>
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
                                    :title="'<?php esc_attr_e('Restore', 'demo-builder'); ?>'"
                                >
                                    <span class="dashicons dashicons-backup"></span>
                                </button>
                                <button 
                                    class="db-btn db-btn--sm db-btn--info"
                                    @click="downloadBackup(backup)"
                                    :title="'<?php esc_attr_e('Download', 'demo-builder'); ?>'"
                                >
                                    <span class="dashicons dashicons-download"></span>
                                </button>
                                <button 
                                    class="db-btn db-btn--sm db-btn--danger"
                                    @click="deleteBackup(backup)"
                                    :title="'<?php esc_attr_e('Delete', 'demo-builder'); ?>'"
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
                <div 
                    class="db-upload-zone" 
                    @dragover.prevent="dragOver = true"
                    @dragleave="dragOver = false"
                    @drop.prevent="handleDrop"
                    :class="{ 'db-upload-zone--active': dragOver }"
                >
                    <input 
                        type="file" 
                        id="backup-upload" 
                        accept=".zip,.sql" 
                        @change="handleFileSelect" 
                        style="display: none;" 
                    />
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

<style>
.db-accordion {
    border: 1px solid var(--db-border);
    border-radius: var(--db-radius);
    overflow: hidden;
}

.db-accordion-header {
    padding: var(--db-space-md);
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: var(--db-space-sm);
    font-weight: 500;
    background: var(--db-bg-alt);
}

.db-accordion-header:hover {
    background: var(--db-primary-light);
}

.db-accordion-content {
    padding: var(--db-space-lg);
    border-top: 1px solid var(--db-border);
}

.db-accordion-content .db-toggle {
    margin-bottom: var(--db-space-sm);
}

.db-checkbox-group {
    display: flex;
    flex-direction: column;
    gap: var(--db-space-md);
}

.db-upload-zone--active {
    border-color: var(--db-primary);
    background: var(--db-primary-light);
}
</style>

<script>
// Initial data from PHP
window.demoBuilderBackups = <?php echo wp_json_encode($backups); ?>;
window.demoBuilderSizes = <?php echo wp_json_encode($default_sizes); ?>;
window.demoBuilderNextRestore = <?php echo $next_restore ? intval($next_restore) : 'null'; ?>;
window.demoBuilderSettings = <?php echo wp_json_encode($settings); ?>;
</script>
