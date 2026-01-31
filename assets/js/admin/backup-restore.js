/**
 * Demo Builder - Backup & Restore Page
 * 
 * Vue.js application for backup management
 * 
 * @package DemoBuilder
 */

window.addEventListener("load", function () {
    "use strict";

    (function ($) {
        if (typeof Vue === "undefined") return;

        const { createApp, ref, computed, onMounted } = Vue;

        createApp({
            setup() {
                /* =========================
                 * State
                 * ========================= */
                const backups = ref(window.demoBuilderBackups || []);
                const backupName = ref('');
                const backupType = ref('full');
                const isCreating = ref(false);
                const isRestoring = ref(false);
                const selectedFile = ref(null);

                /* =========================
                 * Derived / Computed
                 * ========================= */
                const hasBackups = computed(() => backups.value.length > 0);

                /* =========================
                 * Actions / Intents
                 * ========================= */
                const formatSize = (bytes) => {
                    return DemoBuilder.formatSize(bytes || 0);
                };

                const formatDate = (dateString) => {
                    return DemoBuilder.formatDate(dateString);
                };

                const createBackup = async () => {
                    const $btn = $('[data-popup-title]:contains("Create Backup")').first();
                    const attrs = DemoBuilder.getDataAttrs($btn);

                    const result = await DemoBuilder.confirm({
                        title: attrs.popupTitle || demoBuilderData.i18n.confirm,
                        text: attrs.popupText || 'Create a new backup?',
                        confirmButtonText: attrs.popupConfirm || demoBuilderData.i18n.yes,
                        cancelButtonText: attrs.popupCancel || demoBuilderData.i18n.cancel,
                        icon: 'question'
                    });

                    if (!result.isConfirmed) return;

                    isCreating.value = true;
                    DemoBuilder.loading(demoBuilderData.i18n.backupCreating);

                    try {
                        const response = await DemoBuilder.ajax('demo_builder_create_backup', {
                            name: backupName.value,
                            type: backupType.value
                        });

                        DemoBuilder.closeLoading();

                        if (response.success) {
                            backups.value.unshift(response.data.backup);
                            backupName.value = '';
                            DemoBuilder.success(demoBuilderData.i18n.backupCreated);
                        } else {
                            DemoBuilder.error(response.data.message);
                        }
                    } catch (error) {
                        DemoBuilder.closeLoading();
                        DemoBuilder.error(demoBuilderData.i18n.networkError);
                    }

                    isCreating.value = false;
                };

                const restoreBackup = async (backup) => {
                    const result = await DemoBuilder.confirm({
                        title: demoBuilderData.i18n.confirmRestore || 'Restore Backup',
                        text: 'Are you sure you want to restore this backup? Current data will be replaced.',
                        icon: 'warning',
                        confirmButtonText: demoBuilderData.i18n.yes,
                        cancelButtonText: demoBuilderData.i18n.cancel
                    });

                    if (!result.isConfirmed) return;

                    DemoBuilder.loading(demoBuilderData.i18n.restoreInProgress);

                    try {
                        const response = await DemoBuilder.ajax('demo_builder_restore_backup', {
                            backup_id: backup.id
                        });

                        DemoBuilder.closeLoading();

                        if (response.success) {
                            DemoBuilder.success(demoBuilderData.i18n.restoreComplete);
                            // Reload page after restore
                            setTimeout(() => window.location.reload(), 2000);
                        } else {
                            DemoBuilder.error(response.data.message);
                        }
                    } catch (error) {
                        DemoBuilder.closeLoading();
                        DemoBuilder.error(demoBuilderData.i18n.networkError);
                    }
                };

                const downloadBackup = (backup) => {
                    window.location.href = demoBuilderData.pluginUrl + 'includes/download.php?id=' + backup.id + '&nonce=' + demoBuilderData.nonce;
                };

                const deleteBackup = async (backup) => {
                    const result = await DemoBuilder.confirm({
                        title: demoBuilderData.i18n.confirmDeleteBackup || 'Delete Backup',
                        text: demoBuilderData.i18n.cannotUndo,
                        icon: 'warning',
                        confirmButtonText: demoBuilderData.i18n.delete,
                        cancelButtonText: demoBuilderData.i18n.cancel
                    });

                    if (!result.isConfirmed) return;

                    DemoBuilder.loading(demoBuilderData.i18n.pleaseWait);

                    try {
                        const response = await DemoBuilder.ajax('demo_builder_delete_backup', {
                            backup_id: backup.id
                        });

                        DemoBuilder.closeLoading();

                        if (response.success) {
                            const index = backups.value.findIndex(b => b.id === backup.id);
                            if (index > -1) {
                                backups.value.splice(index, 1);
                            }
                            DemoBuilder.success(demoBuilderData.i18n.deleted);
                        } else {
                            DemoBuilder.error(response.data.message);
                        }
                    } catch (error) {
                        DemoBuilder.closeLoading();
                        DemoBuilder.error(demoBuilderData.i18n.networkError);
                    }
                };

                const handleFileSelect = (event) => {
                    const file = event.target.files[0];
                    if (file) {
                        uploadBackup(file);
                    }
                };

                const handleDrop = (event) => {
                    const file = event.dataTransfer.files[0];
                    if (file) {
                        uploadBackup(file);
                    }
                };

                const uploadBackup = async (file) => {
                    const formData = new FormData();
                    formData.append('action', 'demo_builder_upload_backup');
                    formData.append('nonce', demoBuilderData.nonce);
                    formData.append('backup_file', file);

                    DemoBuilder.loading('Uploading backup...');

                    try {
                        const response = await $.ajax({
                            url: demoBuilderData.ajaxUrl,
                            type: 'POST',
                            data: formData,
                            processData: false,
                            contentType: false
                        });

                        DemoBuilder.closeLoading();

                        if (response.success) {
                            backups.value.unshift(response.data.backup);
                            DemoBuilder.success('Backup uploaded successfully!');
                        } else {
                            DemoBuilder.error(response.data.message);
                        }
                    } catch (error) {
                        DemoBuilder.closeLoading();
                        DemoBuilder.error(demoBuilderData.i18n.networkError);
                    }
                };

                /* =========================
                 * Lifecycle
                 * ========================= */
                onMounted(() => {
                    // Initialize any needed setup
                });

                /* =========================
                 * Expose to UI
                 * ========================= */
                return {
                    // State
                    backups,
                    backupName,
                    backupType,
                    isCreating,
                    isRestoring,
                    
                    // Computed
                    hasBackups,
                    
                    // Methods
                    formatSize,
                    formatDate,
                    createBackup,
                    restoreBackup,
                    downloadBackup,
                    deleteBackup,
                    handleFileSelect,
                    handleDrop
                };
            }
        }).mount('#demo-builder-backup');

    })(jQuery);
});
