<?php
/**
 * Maintenance Page Template
 *
 * Displayed when site is in maintenance mode
 *
 * @package DemoBuilder
 * @var string $title Page title
 * @var string $description Description text
 * @var string $logo Logo URL
 * @var string $background_image Background image URL
 * @var string $custom_html Custom HTML content
 * @var bool $show_countdown Show countdown timer
 * @var int|null $next_restore Next restore timestamp
 * @var string $countdown_initial Initial countdown display
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title><?php echo esc_html($title); ?> - <?php bloginfo('name'); ?></title>
    
    <style>
        :root {
            --db-primary: #A5B4FC;
            --db-secondary: #C4B5FD;
            --db-bg: linear-gradient(135deg, #F0F4FF 0%, #FDF4FF 50%, #F0FDFA 100%);
            --db-text: #334155;
            --db-text-muted: #64748B;
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: var(--db-bg);
            <?php if ($background_image) : ?>
            background-image: url('<?php echo esc_url($background_image); ?>');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            <?php endif; ?>
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .maintenance-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: 24px;
            padding: 48px;
            max-width: 520px;
            width: 100%;
            text-align: center;
            box-shadow: 
                0 25px 50px -12px rgba(0, 0, 0, 0.1),
                0 0 0 1px rgba(255, 255, 255, 0.5);
            animation: fadeInUp 0.6s ease-out;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .maintenance-logo {
            max-width: 150px;
            max-height: 80px;
            margin-bottom: 24px;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.8; }
        }
        
        .maintenance-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--db-primary) 0%, var(--db-secondary) 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
            animation: pulse 2s infinite;
        }
        
        .maintenance-icon svg {
            width: 40px;
            height: 40px;
            fill: white;
        }
        
        h1 {
            color: var(--db-text);
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 16px;
            line-height: 1.3;
        }
        
        .maintenance-description {
            color: var(--db-text-muted);
            font-size: 16px;
            line-height: 1.7;
            margin-bottom: 32px;
        }
        
        .maintenance-description p {
            margin-bottom: 12px;
        }
        
        .maintenance-description p:last-child {
            margin-bottom: 0;
        }
        
        .countdown-container {
            background: linear-gradient(135deg, var(--db-primary) 0%, var(--db-secondary) 100%);
            border-radius: 16px;
            padding: 24px 32px;
            margin-bottom: 24px;
        }
        
        .countdown-label {
            color: rgba(255, 255, 255, 0.9);
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .countdown-timer {
            display: flex;
            justify-content: center;
            gap: 16px;
        }
        
        .countdown-unit {
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        
        .countdown-value {
            color: white;
            font-size: 36px;
            font-weight: 700;
            font-variant-numeric: tabular-nums;
            line-height: 1;
        }
        
        .countdown-text {
            color: rgba(255, 255, 255, 0.8);
            font-size: 12px;
            text-transform: uppercase;
            margin-top: 4px;
        }
        
        .countdown-separator {
            color: white;
            font-size: 36px;
            font-weight: 700;
            opacity: 0.6;
            animation: blink 1s infinite;
        }
        
        @keyframes blink {
            0%, 100% { opacity: 0.6; }
            50% { opacity: 0.3; }
        }
        
        .custom-content {
            margin-top: 24px;
            padding-top: 24px;
            border-top: 1px solid rgba(0, 0, 0, 0.05);
        }
        
        .maintenance-footer {
            margin-top: 32px;
            color: var(--db-text-muted);
            font-size: 13px;
        }
        
        .maintenance-footer a {
            color: var(--db-text);
            text-decoration: none;
        }
        
        @media (max-width: 480px) {
            .maintenance-container {
                padding: 32px 24px;
            }
            
            h1 {
                font-size: 24px;
            }
            
            .countdown-value {
                font-size: 28px;
            }
        }
    </style>
</head>
<body>
    <div class="maintenance-container">
        <?php if (!empty($logo)) : ?>
            <img src="<?php echo esc_url($logo); ?>" alt="<?php bloginfo('name'); ?>" class="maintenance-logo">
        <?php else : ?>
            <div class="maintenance-icon">
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M22.7 19l-9.1-9.1c.9-2.3.4-5-1.5-6.9-2-2-5-2.4-7.4-1.3L9 6 6 9 1.6 4.7C.4 7.1.9 10.1 2.9 12.1c1.9 1.9 4.6 2.4 6.9 1.5l9.1 9.1c.4.4 1 .4 1.4 0l2.3-2.3c.5-.4.5-1.1.1-1.4z"/>
                </svg>
            </div>
        <?php endif; ?>
        
        <h1><?php echo esc_html($title); ?></h1>
        
        <?php if (!empty($description)) : ?>
            <div class="maintenance-description">
                <?php echo wp_kses_post($description); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($show_countdown && $next_restore) : ?>
            <div class="countdown-container">
                <div class="countdown-label"><?php esc_html_e('Estimated Time Remaining', 'demo-builder'); ?></div>
                <div class="countdown-timer" id="countdown">
                    <div class="countdown-unit">
                        <span class="countdown-value" id="countdown-hours">00</span>
                        <span class="countdown-text"><?php esc_html_e('Hours', 'demo-builder'); ?></span>
                    </div>
                    <span class="countdown-separator">:</span>
                    <div class="countdown-unit">
                        <span class="countdown-value" id="countdown-minutes">00</span>
                        <span class="countdown-text"><?php esc_html_e('Minutes', 'demo-builder'); ?></span>
                    </div>
                    <span class="countdown-separator">:</span>
                    <div class="countdown-unit">
                        <span class="countdown-value" id="countdown-seconds">00</span>
                        <span class="countdown-text"><?php esc_html_e('Seconds', 'demo-builder'); ?></span>
                    </div>
                </div>
            </div>
            
            <script>
                (function() {
                    var endTime = <?php echo (int) $next_restore; ?> * 1000;
                    
                    function updateCountdown() {
                        var now = Date.now();
                        var diff = Math.max(0, endTime - now);
                        
                        var hours = Math.floor(diff / (1000 * 60 * 60));
                        var minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
                        var seconds = Math.floor((diff % (1000 * 60)) / 1000);
                        
                        document.getElementById('countdown-hours').textContent = String(hours).padStart(2, '0');
                        document.getElementById('countdown-minutes').textContent = String(minutes).padStart(2, '0');
                        document.getElementById('countdown-seconds').textContent = String(seconds).padStart(2, '0');
                        
                        if (diff > 0) {
                            requestAnimationFrame(function() {
                                setTimeout(updateCountdown, 1000);
                            });
                        } else {
                            location.reload();
                        }
                    }
                    
                    updateCountdown();
                })();
            </script>
        <?php endif; ?>
        
        <?php if (!empty($custom_html)) : ?>
            <div class="custom-content">
                <?php echo wp_kses_post($custom_html); ?>
            </div>
        <?php endif; ?>
        
        <div class="maintenance-footer">
            <a href="<?php echo esc_url(home_url()); ?>"><?php bloginfo('name'); ?></a>
        </div>
    </div>
</body>
</html>
