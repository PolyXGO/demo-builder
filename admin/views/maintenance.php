<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title><?php echo esc_html($title); ?></title>
    <style>
        :root {
            --db-primary: #A5B4FC;
            --db-secondary: #C4B5FD;
            --db-bg: #F8FAFC;
            --db-text: #334155;
            --db-text-muted: #64748B;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, var(--db-bg) 0%, #E2E8F0 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .maintenance-container {
            max-width: 600px;
            width: 100%;
            text-align: center;
        }
        
        .maintenance-icon {
            width: 120px;
            height: 120px;
            margin: 0 auto 30px;
            background: linear-gradient(135deg, var(--db-primary) 0%, var(--db-secondary) 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: pulse 2s ease-in-out infinite;
        }
        
        .maintenance-icon svg {
            width: 60px;
            height: 60px;
            fill: white;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        
        .maintenance-card {
            background: white;
            border-radius: 24px;
            padding: 50px 40px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.1);
        }
        
        .maintenance-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--db-text);
            margin-bottom: 16px;
        }
        
        .maintenance-message {
            font-size: 1.125rem;
            color: var(--db-text-muted);
            line-height: 1.6;
            margin-bottom: 30px;
        }
        
        .maintenance-progress {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            color: var(--db-text-muted);
            font-size: 0.875rem;
        }
        
        .maintenance-progress .dot {
            width: 8px;
            height: 8px;
            background: var(--db-primary);
            border-radius: 50%;
            animation: bounce 1.4s ease-in-out infinite;
        }
        
        .maintenance-progress .dot:nth-child(2) { animation-delay: 0.2s; }
        .maintenance-progress .dot:nth-child(3) { animation-delay: 0.4s; }
        
        @keyframes bounce {
            0%, 80%, 100% { transform: translateY(0); }
            40% { transform: translateY(-10px); }
        }
        
        .maintenance-footer {
            margin-top: 40px;
            font-size: 0.875rem;
            color: var(--db-text-muted);
        }
    </style>
</head>
<body>
    <div class="maintenance-container">
        <div class="maintenance-icon">
            <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
            </svg>
        </div>
        
        <div class="maintenance-card">
            <h1 class="maintenance-title"><?php echo esc_html($title); ?></h1>
            <p class="maintenance-message"><?php echo esc_html($message); ?></p>
            
            <div class="maintenance-progress">
                <span class="dot"></span>
                <span class="dot"></span>
                <span class="dot"></span>
                <span><?php esc_html_e('Working on it', 'demo-builder'); ?></span>
            </div>
        </div>
        
        <div class="maintenance-footer">
            <?php esc_html_e('We apologize for any inconvenience.', 'demo-builder'); ?>
        </div>
    </div>
</body>
</html>
