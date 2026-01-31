/**
 * Demo Builder - Login Form Integration
 * 
 * Auto-fill credentials when clicking demo account
 * 
 * @package DemoBuilder
 */

(function($) {
    'use strict';
    
    $(document).ready(function() {
        const $selector = $('#db-login-selector');
        
        if (!$selector.length) {
            return;
        }
        
        // Handle account click
        $selector.on('click', '.db-login-account', function() {
            const $account = $(this);
            const username = $account.data('username');
            const password = $account.data('password');
            
            // Fill credentials
            $('#user_login').val(username).trigger('change');
            $('#user_pass').val(password).trigger('change');
            
            // Visual feedback
            $selector.find('.db-login-account').removeClass('active');
            $account.addClass('active');
            
            // Optional: auto-submit
            // $('#loginform').submit();
            
            // Add pulse animation
            $account.addClass('db-login-account--selected');
            setTimeout(function() {
                $account.removeClass('db-login-account--selected');
            }, 300);
        });
        
        // Add active styling
        const style = `
            .db-login-account.active {
                border-color: #4F46E5 !important;
                background: #EEF2FF !important;
            }
            .db-login-account--selected {
                animation: db-pulse 0.3s ease;
            }
            @keyframes db-pulse {
                0% { transform: scale(1); }
                50% { transform: scale(1.02); }
                100% { transform: scale(1); }
            }
        `;
        
        $('<style>').text(style).appendTo('head');
    });
    
})(jQuery);
