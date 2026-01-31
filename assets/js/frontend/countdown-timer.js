/**
 * Demo Builder - Frontend Countdown Timer
 * 
 * Real-time countdown display for demo site visitors
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
            return null; // Will trigger overdue message
        }
        
        const days = Math.floor(seconds / 86400);
        const hours = Math.floor((seconds % 86400) / 3600);
        const minutes = Math.floor((seconds % 3600) / 60);
        const secs = seconds % 60;
        
        let parts = [];
        
        if (days > 0) {
            parts.push(days + ' ' + (i18n.days || 'd'));
        }
        
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
            return settings.overdue_message || 'Demo reset imminent!';
        }
        
        const template = settings.message_template || 'Demo resets in: {{countdown}}';
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
        
        // Update frontend countdown elements
        const elements = [
            '#db-frontend-countdown',
            '#db-content-countdown'
        ];
        
        elements.forEach(function(selector) {
            const $el = $(selector);
            if ($el.length) {
                $el.html('<span class="db-countdown-icon">⏱</span> ' + message);
                $el.toggleClass('db-countdown-overdue', isOverdue);
                $el.show();
            }
        });
        
        // Custom CSS selector injection (frontend)
        if (settings.css_selector && settings.position === 'custom') {
            const $target = $(settings.css_selector);
            if ($target.length) {
                let $injected = $target.find('.db-countdown-injected');
                if (!$injected.length) {
                    $target.prepend('<div class="db-countdown db-countdown-injected"></div>');
                    $injected = $target.find('.db-countdown-injected');
                }
                $injected.html('<span class="db-countdown-icon">⏱</span> ' + message);
                $injected.toggleClass('db-countdown-overdue', isOverdue);
            }
        }
        
        // Header position (next to logo)
        if (settings.position === 'header') {
            let $countdown = $('#db-header-countdown');
            if (!$countdown.length) {
                // Try to find site logo/branding
                const logoSelectors = [
                    '.site-branding',
                    '.site-logo',
                    '#logo',
                    '.logo',
                    'header .custom-logo-link'
                ];
                
                for (const selector of logoSelectors) {
                    const $logo = $(selector);
                    if ($logo.length) {
                        $logo.after('<div id="db-header-countdown" class="db-countdown db-countdown-header"></div>');
                        break;
                    }
                }
                $countdown = $('#db-header-countdown');
            }
            
            if ($countdown.length) {
                $countdown.html('<span class="db-countdown-icon">⏱</span> ' + message);
                $countdown.toggleClass('db-countdown-overdue', isOverdue);
            }
        }
    }
    
    /**
     * Initialize countdown
     */
    function init() {
        if (!nextRestore) {
            return;
        }
        
        // Add additional styles
        addStyles();
        
        // Initial update
        updateCountdown();
        
        // Update every second
        setInterval(updateCountdown, 1000);
    }
    
    /**
     * Add additional frontend styles
     */
    function addStyles() {
        const css = `
            .db-countdown-header {
                display: inline-flex !important;
                vertical-align: middle;
                margin-left: 15px;
            }
            .db-countdown-content {
                margin: 15px 0;
                text-align: center;
            }
        `;
        
        $('<style>').text(css).appendTo('head');
    }
    
    // Initialize when document is ready
    $(document).ready(init);
    
})(jQuery);
