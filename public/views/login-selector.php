<?php
/**
 * Login Selector Template
 *
 * Displays demo account buttons on wp-login.php
 *
 * @package DemoBuilder
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="db-login-selector" id="db-login-selector">
    <div class="db-login-selector-title">
        <?php esc_html_e('Demo Accounts', 'demo-builder'); ?>
    </div>
    
    <div class="db-login-accounts">
        <?php foreach ($accounts as $account) : ?>
            <div class="db-login-account" 
                 data-username="<?php echo esc_attr($account->user_login); ?>"
                 data-password="<?php echo esc_attr($account->password_plain); ?>"
            >
                <div class="db-login-account-info">
                    <span class="db-login-account-name">
                        <?php echo esc_html($account->display_name ?: $account->user_login); ?>
                    </span>
                    <?php if (!empty($account->role_name)) : ?>
                        <span class="db-login-account-role">
                            <?php echo esc_html($account->role_name); ?>
                        </span>
                    <?php endif; ?>
                    <?php if (!empty($account->message)) : ?>
                        <span class="db-login-account-message">
                            <?php echo esc_html($account->message); ?>
                        </span>
                    <?php endif; ?>
                </div>
                <span class="db-login-account-badge">
                    <?php esc_html_e('Click to login', 'demo-builder'); ?>
                </span>
            </div>
        <?php endforeach; ?>
    </div>
</div>
