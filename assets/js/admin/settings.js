/**
 * Demo Builder - Settings Page
 * 
 * Settings form enhancements
 * 
 * @package DemoBuilder
 */

window.addEventListener("load", function () {
    "use strict";

    (function ($) {
        /**
         * Test Telegram Connection
         */
        $('#db-test-telegram').on('click', function () {
            const botToken = $('input[name="bot_token"]').val();
            const chatId = $('input[name="chat_id"]').val();

            if (!botToken || !chatId) {
                DemoBuilder.error('Please enter both Bot Token and Chat ID');
                return;
            }

            DemoBuilder.loading('Testing connection...');

            DemoBuilder.ajax('demo_builder_test_telegram', {
                bot_token: botToken,
                chat_id: chatId
            }).done(function (response) {
                DemoBuilder.closeLoading();
                
                if (response.success) {
                    DemoBuilder.success('Connection successful! Check your Telegram.');
                } else {
                    DemoBuilder.error(response.data.message || 'Connection failed');
                }
            }).fail(function () {
                DemoBuilder.closeLoading();
                DemoBuilder.error(demoBuilderData.i18n.networkError);
            });
        });

        /**
         * Toggle dependent fields
         */
        function toggleDependentFields() {
            // Auto backup schedule visibility
            const autoBackup = $('input[name="auto_backup"]');
            const scheduleField = $('select[name="backup_schedule"]').closest('.db-form-group');
            
            if (autoBackup.length && scheduleField.length) {
                scheduleField.toggle(autoBackup.is(':checked'));
                
                autoBackup.on('change', function () {
                    scheduleField.toggle($(this).is(':checked'));
                });
            }

            // Countdown timer position visibility
            const countdownEnabled = $('input[name="enabled"]');
            const countdownFields = countdownEnabled.closest('.db-form-section').find('.db-form-group').not(':first');
            
            if (countdownEnabled.closest('form[data-tab="countdown"]').length) {
                countdownFields.toggle(countdownEnabled.is(':checked'));
                
                countdownEnabled.on('change', function () {
                    countdownFields.toggle($(this).is(':checked'));
                });
            }
        }

        // Initialize on load
        toggleDependentFields();

    })(jQuery);
});
