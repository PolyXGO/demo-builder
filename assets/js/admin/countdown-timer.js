/**
 * Demo Builder - Admin Countdown Timer
 * 
 * Real-time countdown display for next auto-restore
 * 
 * @package DemoBuilder
 */

(function($) {
    'use strict';
    
    if (typeof demoBuilderCountdown === 'undefined') {
        return;
    }
    
    const config = demoBuilderCountdown;
    const settings = config.settings || {};
    const i18n = config.i18n || {};
    
    let nextRestore = config.nextRestore ? parseInt(config.nextRestore) : null;
    let serverTime = config.serverTime ? parseInt(config.serverTime) : Math.floor(Date.now() / 1000);
    let timeDrift = serverTime - Math.floor(Date.now() / 1000);
    
    /**
     * Format countdown time
     * 
     * @param {number} seconds Total seconds remaining
     * @returns {string} Formatted time string
     */
    function formatCountdown(seconds) {
        if (seconds <= 0) {
            return settings.overdue_message || 'Restore overdue!';
        }
        
        const days = Math.floor(seconds / 86400);
        const hours = Math.floor((seconds % 86400) / 3600);
        const minutes = Math.floor((seconds % 3600) / 60);
        const secs = seconds % 60;
        
        let parts = [];
        
        if (days > 0) {
            parts.push(days + ' ' + (i18n.days || 'd'));
        }
        
        // Always show at least HH:MM:SS
        parts.push(
            String(hours).padStart(2, '0') + ':' +
            String(minutes).padStart(2, '0') + ':' +
            String(secs).padStart(2, '0')
        );
        
        return parts.join(' ');
    }
    
    /**
     * Get message with countdown
     * 
     * @param {string} countdown Formatted countdown
     * @param {boolean} isOverdue Is time expired
     * @returns {string} Message with countdown
     */
    function getMessage(countdown, isOverdue) {
        if (isOverdue) {
            return settings.overdue_message || 'Restore overdue!';
        }
        
        const template = settings.message_template || 'Next restore in: {{countdown}}';
        return template.replace('{{countdown}}', '<span class="db-countdown-time">' + countdown + '</span>');
    }
    
    /**
     * Update countdown display
     */
    function updateCountdown() {
        if (!nextRestore) {
            return;
        }
        
        const currentTime = Math.floor(Date.now() / 1000) + timeDrift;
        const remaining = nextRestore - currentTime;
        const isOverdue = remaining <= 0;
        
        const countdown = formatCountdown(remaining);
        const message = getMessage(countdown, isOverdue);
        
        // Update all countdown elements
        const elements = [
            '#db-admin-countdown',
            '#db-frontend-countdown',
            '#db-content-countdown',
            '#db-adminbar-countdown'
        ];
        
        elements.forEach(function(selector) {
            const $el = $(selector);
            if ($el.length) {
                if (selector === '#db-adminbar-countdown') {
                    // Admin bar - just update the time
                    $el.parent().find('.db-countdown-time').html(countdown);
                } else {
                    $el.html('<span class="db-countdown-icon">⏱</span> ' + message);
                    $el.toggleClass('db-countdown-overdue', isOverdue);
                    $el.show();
                }
            }
        });
        
        // Custom CSS selector injection
        if (settings.css_selector && settings.position === 'custom') {
            const $target = $(settings.css_selector);
            if ($target.length && !$target.find('.db-countdown-injected').length) {
                $target.prepend('<div class="db-countdown db-countdown-injected">' + 
                    '<span class="db-countdown-icon">⏱</span> ' + message + '</div>');
            } else if ($target.find('.db-countdown-injected').length) {
                $target.find('.db-countdown-injected').html(
                    '<span class="db-countdown-icon">⏱</span> ' + message
                );
            }
        }
    }
    
    /**
     * Initialize countdown
     */
    function init() {
        if (!nextRestore) {
            console.log('Demo Builder: No next restore scheduled');
            return;
        }
        
        // Initial update
        updateCountdown();
        
        // Update every second
        setInterval(updateCountdown, 1000);
        
        // Refresh data every 5 minutes
        setInterval(refreshData, 300000);
    }
    
    /**
     * Refresh countdown data from server
     */
    function refreshData() {
        if (typeof demoBuilderData === 'undefined') {
            return;
        }
        
        $.ajax({
            url: demoBuilderData.ajaxUrl,
            type: 'POST',
            data: {
                action: 'demo_builder_get_countdown_data',
                nonce: demoBuilderData.nonce
            },
            success: function(response) {
                if (response.success && response.data.next_restore) {
                    nextRestore = parseInt(response.data.next_restore);
                    serverTime = parseInt(response.data.server_time);
                    timeDrift = serverTime - Math.floor(Date.now() / 1000);
                }
            }
        });
    }
    
    // Initialize when document is ready
    $(document).ready(init);
    
})(jQuery);
