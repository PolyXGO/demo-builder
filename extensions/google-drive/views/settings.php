<?php
/**
 * Google Drive Settings View
 *
 * @package DemoBuilder
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

$provider = Demo_Builder_Google_Drive::get_instance();
$settings = get_option('demo_builder_google_drive', []);
$is_connected = $provider->is_connected();
?>

<div class="db-cloud-provider db-cloud-gdrive">
    <div class="db-cloud-header">
        <div class="db-cloud-icon">
            <svg width="24" height="24" viewBox="0 0 87.3 78" xmlns="http://www.w3.org/2000/svg">
                <path d="m6.6 66.85 3.85 6.65c.8 1.4 1.95 2.5 3.3 3.3l13.75-23.8h-27.5c0 1.55.4 3.1 1.2 4.5z" fill="#0066da"/>
                <path d="m43.65 25-13.75-23.8c-1.35.8-2.5 1.9-3.3 3.3l-25.4 44a9.06 9.06 0 0 0 -1.2 4.5h27.5z" fill="#00ac47"/>
                <path d="m73.55 76.8c1.35-.8 2.5-1.9 3.3-3.3l1.6-2.75 7.65-13.25c.8-1.4 1.2-2.95 1.2-4.5h-27.502l5.852 11.5z" fill="#ea4335"/>
                <path d="m43.65 25 13.75-23.8c-1.35-.8-2.9-1.2-4.5-1.2h-18.5c-1.6 0-3.15.45-4.5 1.2z" fill="#00832d"/>
                <path d="m59.8 53h-32.3l-13.75 23.8c1.35.8 2.9 1.2 4.5 1.2h50.8c1.6 0 3.15-.45 4.5-1.2z" fill="#2684fc"/>
                <path d="m73.4 26.5-12.7-22c-.8-1.4-1.95-2.5-3.3-3.3l-13.75 23.8 16.15 28h27.45c0-1.55-.4-3.1-1.2-4.5z" fill="#ffba00"/>
            </svg>
        </div>
        <div class="db-cloud-info">
            <h3>Google Drive</h3>
            <span class="db-cloud-status <?php echo $is_connected ? 'db-status--success' : 'db-status--secondary'; ?>">
                <?php echo $is_connected ? __('Connected', 'demo-builder') : __('Not Connected', 'demo-builder'); ?>
            </span>
        </div>
    </div>

    <div class="db-cloud-body">
        <?php if (!$is_connected) : ?>
            <div class="db-form-group">
                <label class="db-label"><?php esc_html_e('Client ID', 'demo-builder'); ?></label>
                <input type="text" id="gdrive-client-id" class="db-input" 
                       value="<?php echo esc_attr($settings['client_id'] ?? ''); ?>" 
                       placeholder="<?php esc_attr_e('Enter Google Client ID', 'demo-builder'); ?>" />
            </div>

            <div class="db-form-group">
                <label class="db-label"><?php esc_html_e('Client Secret', 'demo-builder'); ?></label>
                <input type="password" id="gdrive-client-secret" class="db-input" 
                       value="<?php echo esc_attr($settings['client_secret'] ?? ''); ?>" 
                       placeholder="<?php esc_attr_e('Enter Google Client Secret', 'demo-builder'); ?>" />
            </div>

            <div class="db-form-group">
                <label class="db-label"><?php esc_html_e('Redirect URI', 'demo-builder'); ?></label>
                <div class="db-input-group">
                    <input type="text" class="db-input" readonly 
                           value="<?php echo esc_attr(admin_url('admin-ajax.php?action=demo_builder_cloud_callback&provider=google-drive')); ?>" />
                    <button type="button" class="db-btn db-btn--secondary db-btn--copy" title="<?php esc_attr_e('Copy', 'demo-builder'); ?>">
                        <span class="dashicons dashicons-clipboard"></span>
                    </button>
                </div>
                <p class="db-help-text"><?php esc_html_e('Add this URI to your Google Cloud Console credentials.', 'demo-builder'); ?></p>
            </div>

            <div class="db-form-actions">
                <button type="button" id="gdrive-save-settings" class="db-btn db-btn--secondary">
                    <span class="dashicons dashicons-saved"></span>
                    <?php esc_html_e('Save Settings', 'demo-builder'); ?>
                </button>
                <button type="button" id="gdrive-connect" class="db-btn db-btn--primary">
                    <span class="dashicons dashicons-cloud"></span>
                    <?php esc_html_e('Connect to Google Drive', 'demo-builder'); ?>
                </button>
            </div>
        <?php else : ?>
            <div class="db-form-group">
                <label class="db-label"><?php esc_html_e('Folder ID (optional)', 'demo-builder'); ?></label>
                <input type="text" id="gdrive-folder-id" class="db-input" 
                       value="<?php echo esc_attr($settings['folder_id'] ?? ''); ?>" 
                       placeholder="<?php esc_attr_e('Leave empty for root', 'demo-builder'); ?>" />
            </div>

            <div class="db-form-group">
                <label class="db-toggle">
                    <input type="checkbox" id="gdrive-auto-sync" <?php checked($settings['auto_sync'] ?? false); ?> />
                    <span class="db-toggle-slider"></span>
                    <span class="db-toggle-label"><?php esc_html_e('Auto-sync backups to Google Drive', 'demo-builder'); ?></span>
                </label>
            </div>

            <div class="db-form-group">
                <label class="db-label"><?php esc_html_e('Max Cloud Backups', 'demo-builder'); ?></label>
                <input type="number" id="gdrive-max-backups" class="db-input" 
                       value="<?php echo esc_attr($settings['max_backups'] ?? 5); ?>" 
                       min="1" max="50" />
                <p class="db-help-text"><?php esc_html_e('Older backups will be automatically deleted.', 'demo-builder'); ?></p>
            </div>

            <div id="gdrive-quota" class="db-quota-info" style="display:none;">
                <!-- Quota info loaded via JS -->
            </div>

            <div class="db-form-actions">
                <button type="button" id="gdrive-save-settings" class="db-btn db-btn--primary">
                    <span class="dashicons dashicons-saved"></span>
                    <?php esc_html_e('Save Settings', 'demo-builder'); ?>
                </button>
                <button type="button" id="gdrive-disconnect" class="db-btn db-btn--danger">
                    <span class="dashicons dashicons-no"></span>
                    <?php esc_html_e('Disconnect', 'demo-builder'); ?>
                </button>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.db-cloud-provider {
    background: var(--db-bg);
    border: 1px solid var(--db-border);
    border-radius: var(--db-radius-lg);
    padding: var(--db-space-lg);
    margin-bottom: var(--db-space-lg);
}
.db-cloud-header {
    display: flex;
    align-items: center;
    gap: var(--db-space-md);
    margin-bottom: var(--db-space-lg);
    padding-bottom: var(--db-space-md);
    border-bottom: 1px solid var(--db-border);
}
.db-cloud-icon {
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--db-bg-alt);
    border-radius: var(--db-radius);
}
.db-cloud-info h3 {
    margin: 0 0 4px;
    font-size: 16px;
}
.db-cloud-status {
    font-size: 12px;
    padding: 2px 8px;
    border-radius: 20px;
}
.db-cloud-status.db-status--success {
    background: #D1FAE5;
    color: #059669;
}
.db-cloud-status.db-status--secondary {
    background: var(--db-bg-alt);
    color: var(--db-text-muted);
}
.db-input-group {
    display: flex;
    gap: var(--db-space-xs);
}
.db-input-group .db-input {
    flex: 1;
}
.db-quota-info {
    background: var(--db-bg-alt);
    padding: var(--db-space-md);
    border-radius: var(--db-radius);
    margin-bottom: var(--db-space-md);
}
</style>

<script>
jQuery(document).ready(function($) {
    // Save settings
    $('#gdrive-save-settings').on('click', function() {
        const data = {
            client_id: $('#gdrive-client-id').val(),
            client_secret: $('#gdrive-client-secret').val(),
            folder_id: $('#gdrive-folder-id').val(),
            auto_sync: $('#gdrive-auto-sync').is(':checked'),
            max_backups: $('#gdrive-max-backups').val()
        };
        
        DemoBuilder.ajax('demo_builder_gdrive_settings', data).then(function(response) {
            if (response.success) {
                DemoBuilder.success(response.data.message);
            } else {
                DemoBuilder.error(response.data.message);
            }
        });
    });

    // Connect
    $('#gdrive-connect').on('click', function() {
        DemoBuilder.ajax('demo_builder_gdrive_auth_url').then(function(response) {
            if (response.success) {
                window.location.href = response.data.url;
            }
        });
    });

    // Disconnect
    $('#gdrive-disconnect').on('click', function() {
        DemoBuilder.confirm({
            title: '<?php esc_html_e('Disconnect Google Drive?', 'demo-builder'); ?>',
            text: '<?php esc_html_e('You will need to reconnect to sync backups.', 'demo-builder'); ?>',
            icon: 'warning'
        }).then(function(result) {
            if (result.isConfirmed) {
                DemoBuilder.ajax('demo_builder_gdrive_disconnect').then(function(response) {
                    if (response.success) {
                        DemoBuilder.success(response.data.message);
                        location.reload();
                    }
                });
            }
        });
    });

    // Copy redirect URI
    $('.db-btn--copy').on('click', function() {
        const input = $(this).siblings('input');
        navigator.clipboard.writeText(input.val()).then(function() {
            DemoBuilder.success('<?php esc_html_e('Copied!', 'demo-builder'); ?>');
        });
    });
});
</script>
