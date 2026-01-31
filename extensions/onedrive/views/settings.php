<?php
/**
 * OneDrive Settings View
 *
 * @package DemoBuilder
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

$provider = Demo_Builder_OneDrive::get_instance();
$settings = get_option('demo_builder_onedrive', []);
$is_connected = $provider->is_connected();
?>

<div class="db-cloud-provider db-cloud-onedrive">
    <div class="db-cloud-header">
        <div class="db-cloud-icon">
            <svg width="24" height="24" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path fill="#0078D4" d="M14.5 15.5h5.79l.05-.03C21.93 14.93 23 13.45 23 11.71c0-2.16-1.5-3.97-3.55-4.39A5.97 5.97 0 0 0 8.43 7.5h-.24a4.47 4.47 0 0 0-.2 8.93l.18.07H14.5"/>
                <path fill="#0365D6" d="M10.03 8.5H9.14c-2.42 0-4.4 1.93-4.47 4.36a4.47 4.47 0 0 0 3.51 4.56l.18.07H14.5v-.01l.01.01h5.79l.05-.03c.55-.22 1.04-.55 1.44-.96H9.14a2.97 2.97 0 0 1-.28-5.93l.3-.01h2.12c.25-.76.6-1.47 1.04-2.1"/>
                <path fill="#1490DF" d="M14.5 6.29c.88 0 1.71.19 2.46.54a5.97 5.97 0 0 0-6.52.67h-.01c.03 0 .04-.01.06-.01 1.4-.79 3-1.24 4.01-1.2"/>
            </svg>
        </div>
        <div class="db-cloud-info">
            <h3>OneDrive</h3>
            <span class="db-cloud-status <?php echo $is_connected ? 'db-status--success' : 'db-status--secondary'; ?>">
                <?php echo $is_connected ? __('Connected', 'demo-builder') : __('Not Connected', 'demo-builder'); ?>
            </span>
        </div>
    </div>

    <div class="db-cloud-body">
        <?php if (!$is_connected) : ?>
            <div class="db-form-group">
                <label class="db-label"><?php esc_html_e('Application ID', 'demo-builder'); ?></label>
                <input type="text" id="onedrive-app-id" class="db-input" 
                       value="<?php echo esc_attr($settings['app_id'] ?? ''); ?>" 
                       placeholder="<?php esc_attr_e('Enter Azure App ID', 'demo-builder'); ?>" />
            </div>

            <div class="db-form-group">
                <label class="db-label"><?php esc_html_e('Application Secret', 'demo-builder'); ?></label>
                <input type="password" id="onedrive-app-secret" class="db-input" 
                       value="<?php echo esc_attr($settings['app_secret'] ?? ''); ?>" 
                       placeholder="<?php esc_attr_e('Enter Azure App Secret', 'demo-builder'); ?>" />
            </div>

            <div class="db-form-group">
                <label class="db-label"><?php esc_html_e('Redirect URI', 'demo-builder'); ?></label>
                <div class="db-input-group">
                    <input type="text" class="db-input" readonly 
                           value="<?php echo esc_attr(admin_url('admin-ajax.php?action=demo_builder_cloud_callback&provider=onedrive')); ?>" />
                    <button type="button" class="db-btn db-btn--secondary db-btn--copy" title="<?php esc_attr_e('Copy', 'demo-builder'); ?>">
                        <span class="dashicons dashicons-clipboard"></span>
                    </button>
                </div>
                <p class="db-help-text"><?php esc_html_e('Add this URI to your Azure App Registration.', 'demo-builder'); ?></p>
            </div>

            <div class="db-form-actions">
                <button type="button" id="onedrive-save-settings" class="db-btn db-btn--secondary">
                    <span class="dashicons dashicons-saved"></span>
                    <?php esc_html_e('Save Settings', 'demo-builder'); ?>
                </button>
                <button type="button" id="onedrive-connect" class="db-btn db-btn--primary">
                    <span class="dashicons dashicons-cloud"></span>
                    <?php esc_html_e('Connect to OneDrive', 'demo-builder'); ?>
                </button>
            </div>
        <?php else : ?>
            <div class="db-form-group">
                <label class="db-label"><?php esc_html_e('Folder Path', 'demo-builder'); ?></label>
                <input type="text" id="onedrive-folder-path" class="db-input" 
                       value="<?php echo esc_attr($settings['folder_path'] ?? '/DemoBuilder'); ?>" />
                <p class="db-help-text"><?php esc_html_e('Backups will be stored in this folder.', 'demo-builder'); ?></p>
            </div>

            <div class="db-form-group">
                <label class="db-toggle">
                    <input type="checkbox" id="onedrive-auto-sync" <?php checked($settings['auto_sync'] ?? false); ?> />
                    <span class="db-toggle-slider"></span>
                    <span class="db-toggle-label"><?php esc_html_e('Auto-sync backups to OneDrive', 'demo-builder'); ?></span>
                </label>
            </div>

            <div class="db-form-group">
                <label class="db-label"><?php esc_html_e('Max Cloud Backups', 'demo-builder'); ?></label>
                <input type="number" id="onedrive-max-backups" class="db-input" 
                       value="<?php echo esc_attr($settings['max_backups'] ?? 5); ?>" 
                       min="1" max="50" />
                <p class="db-help-text"><?php esc_html_e('Older backups will be automatically deleted.', 'demo-builder'); ?></p>
            </div>

            <div id="onedrive-quota" class="db-quota-info" style="display:none;">
                <!-- Quota info loaded via JS -->
            </div>

            <div class="db-form-actions">
                <button type="button" id="onedrive-save-settings" class="db-btn db-btn--primary">
                    <span class="dashicons dashicons-saved"></span>
                    <?php esc_html_e('Save Settings', 'demo-builder'); ?>
                </button>
                <button type="button" id="onedrive-disconnect" class="db-btn db-btn--danger">
                    <span class="dashicons dashicons-no"></span>
                    <?php esc_html_e('Disconnect', 'demo-builder'); ?>
                </button>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Save settings
    $('#onedrive-save-settings').on('click', function() {
        const data = {
            app_id: $('#onedrive-app-id').val(),
            app_secret: $('#onedrive-app-secret').val(),
            folder_path: $('#onedrive-folder-path').val(),
            auto_sync: $('#onedrive-auto-sync').is(':checked'),
            max_backups: $('#onedrive-max-backups').val()
        };
        
        DemoBuilder.ajax('demo_builder_onedrive_settings', data).then(function(response) {
            if (response.success) {
                DemoBuilder.success(response.data.message);
            } else {
                DemoBuilder.error(response.data.message);
            }
        });
    });

    // Connect
    $('#onedrive-connect').on('click', function() {
        DemoBuilder.ajax('demo_builder_onedrive_auth_url').then(function(response) {
            if (response.success) {
                window.location.href = response.data.url;
            }
        });
    });

    // Disconnect
    $('#onedrive-disconnect').on('click', function() {
        DemoBuilder.confirm({
            title: '<?php esc_html_e('Disconnect OneDrive?', 'demo-builder'); ?>',
            text: '<?php esc_html_e('You will need to reconnect to sync backups.', 'demo-builder'); ?>',
            icon: 'warning'
        }).then(function(result) {
            if (result.isConfirmed) {
                DemoBuilder.ajax('demo_builder_onedrive_disconnect').then(function(response) {
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
