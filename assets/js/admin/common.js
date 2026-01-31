/**
 * Demo Builder - Common Admin Scripts
 * 
 * Shared utilities and SweetAlert2 integration
 * 
 * @package DemoBuilder
 */

window.addEventListener("load", function () {
    "use strict";

    (function ($) {
        /**
         * Global namespace for Demo Builder
         */
        window.DemoBuilder = window.DemoBuilder || {};

        /**
         * SweetAlert2 Pastel Theme Configuration
         */
        const swalConfig = {
            customClass: {
                popup: 'db-swal-popup',
                confirmButton: 'db-btn db-btn--primary',
                cancelButton: 'db-btn db-btn--secondary',
                denyButton: 'db-btn db-btn--danger'
            },
            buttonsStyling: false,
            showClass: {
                popup: 'animate__animated animate__fadeIn animate__faster'
            },
            hideClass: {
                popup: 'animate__animated animate__fadeOut animate__faster'
            }
        };

        /**
         * Show confirmation dialog
         * 
         * @param {Object} options - Dialog options
         * @returns {Promise}
         */
        DemoBuilder.confirm = function (options) {
            const defaults = {
                title: demoBuilderData.i18n.confirm || 'Confirm',
                text: demoBuilderData.i18n.confirmDelete || 'Are you sure?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: demoBuilderData.i18n.yes || 'Yes',
                cancelButtonText: demoBuilderData.i18n.cancel || 'Cancel',
                reverseButtons: true
            };

            return Swal.fire(Object.assign({}, swalConfig, defaults, options));
        };

        /**
         * Show success toast
         * 
         * @param {string} message - Success message
         */
        DemoBuilder.success = function (message) {
            Swal.fire(Object.assign({}, swalConfig, {
                icon: 'success',
                title: message || demoBuilderData.i18n.saved,
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true
            }));
        };

        /**
         * Show error toast
         * 
         * @param {string} message - Error message
         */
        DemoBuilder.error = function (message) {
            Swal.fire(Object.assign({}, swalConfig, {
                icon: 'error',
                title: message || demoBuilderData.i18n.errorGeneric,
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 5000,
                timerProgressBar: true
            }));
        };

        /**
         * Show loading overlay
         * 
         * @param {string} message - Loading message
         */
        DemoBuilder.loading = function (message) {
            Swal.fire(Object.assign({}, swalConfig, {
                title: message || demoBuilderData.i18n.pleaseWait,
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            }));
        };

        /**
         * Close loading overlay
         */
        DemoBuilder.closeLoading = function () {
            Swal.close();
        };

        /**
         * AJAX helper with nonce
         * 
         * @param {string} action - AJAX action name
         * @param {Object} data - Request data
         * @returns {Promise}
         */
        DemoBuilder.ajax = function (action, data) {
            return $.ajax({
                url: demoBuilderData.ajaxUrl,
                type: 'POST',
                data: Object.assign({
                    action: action,
                    nonce: demoBuilderData.nonce
                }, data)
            });
        };

        /**
         * Format file size
         * 
         * @param {number} bytes - Size in bytes
         * @returns {string}
         */
        DemoBuilder.formatSize = function (bytes) {
            if (bytes === 0) return '0 B';
            const k = 1024;
            const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        };

        /**
         * Format date
         * 
         * @param {string} dateString - Date string
         * @returns {string}
         */
        DemoBuilder.formatDate = function (dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
        };

        /**
         * Get data attributes from element
         * 
         * @param {jQuery} $el - jQuery element
         * @returns {Object}
         */
        DemoBuilder.getDataAttrs = function ($el) {
            return {
                popupTitle: $el.data('popup-title'),
                popupText: $el.data('popup-text'),
                popupConfirm: $el.data('popup-confirm'),
                popupCancel: $el.data('popup-cancel'),
                successMessage: $el.data('success-message'),
                errorMessage: $el.data('error-message'),
                loadingText: $el.data('loading-text')
            };
        };

        /**
         * Initialize confirmation buttons with data attributes
         */
        function initConfirmButtons() {
            $(document).on('click', '[data-popup-title]', function (e) {
                const $btn = $(this);
                
                // Skip if it's a Vue component (handled by Vue)
                if ($btn.closest('[v-cloak]').length) {
                    return;
                }

                e.preventDefault();

                const attrs = DemoBuilder.getDataAttrs($btn);

                DemoBuilder.confirm({
                    title: attrs.popupTitle,
                    text: attrs.popupText,
                    confirmButtonText: attrs.popupConfirm,
                    cancelButtonText: attrs.popupCancel
                }).then(function (result) {
                    if (result.isConfirmed) {
                        // Trigger custom event for handling
                        $btn.trigger('db:confirmed');
                    }
                });
            });
        }

        /**
         * Initialize form submission
         */
        function initForms() {
            $(document).on('submit', '#db-settings-form', function (e) {
                e.preventDefault();

                const $form = $(this);
                const tab = $form.data('tab');
                const formData = {};

                // Collect form data
                $form.find('input, select, textarea').each(function () {
                    const $input = $(this);
                    const name = $input.attr('name');
                    
                    if (!name) return;

                    if ($input.is(':checkbox')) {
                        formData[name] = $input.is(':checked');
                    } else if ($input.is(':radio')) {
                        if ($input.is(':checked')) {
                            formData[name] = $input.val();
                        }
                    } else {
                        formData[name] = $input.val();
                    }
                });

                DemoBuilder.loading(demoBuilderData.i18n.loading);

                DemoBuilder.ajax('demo_builder_save_settings', {
                    tab: tab,
                    settings: formData
                }).done(function (response) {
                    DemoBuilder.closeLoading();
                    
                    if (response.success) {
                        DemoBuilder.success(response.data.message);
                    } else {
                        DemoBuilder.error(response.data.message);
                    }
                }).fail(function () {
                    DemoBuilder.closeLoading();
                    DemoBuilder.error(demoBuilderData.i18n.networkError);
                });
            });
        }

        /**
         * Initialize on document ready
         */
        $(function () {
            initConfirmButtons();
            initForms();
        });

    })(jQuery);
});
