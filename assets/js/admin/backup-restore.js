/**
 * Demo Builder - Backup & Restore Page
 * 
 * Vue.js application for backup management (Enhanced)
 * 
 * @package DemoBuilder
 */

window.addEventListener("load", function () {
    "use strict";

    (function ($) {
        if (typeof Vue === "undefined") return;
        if (!document.getElementById("demo-builder-backup")) return;

        const { createApp, ref, computed, onMounted, watch } = Vue;

        createApp({
            setup() {
                /* =========================
                 * State
                 * ========================= */
                const backups = ref(window.demoBuilderBackups || []);
                const sizes = ref(window.demoBuilderSizes || {});
                const settings = ref(window.demoBuilderSettings || {});
                
                const backupName = ref('');
                const backupType = ref('full');
                const isCreating = ref(false);
                const isRestoring = ref(false);
                const dragOver = ref(false);
                
                // Directory selection
                const directories = ref({
                    uploads: true,
                    themes: true,
                    plugins: true
                });
                
                // Exclusion options
                const exclusions = ref({
                    revisions: true,
                    spamComments: true,
                    transients: true,
                    inactivePlugins: false,
                    inactiveThemes: false,
                    cacheFiles: true,
                    logFiles: true
                });
                
                // Auto restore settings
                const autoRestore = ref({
                    enabled: settings.value.restore?.auto_restore_enabled || false,
                    interval: settings.value.restore?.restore_interval || 'daily',
                    backupId: settings.value.restore?.auto_restore_backup_id || ''
                });
                
                const nextRestoreTimestamp = ref(window.demoBuilderNextRestore || null);

                /* =========================
                 * Derived / Computed
                 * ========================= */
                const defaultBackupName = computed(() => {
                    const now = new Date();
                    const date = now.toISOString().slice(0, 10);
                    const time = now.toTimeString().slice(0, 5).replace(':', '');
                    return `backup-${date}-${time}`;
                });
                
                const estimatedSize = computed(() => {
                    let total = 0;
                    
                    if (backupType.value === 'full' || backupType.value === 'database') {
                        total += sizes.value.database?.bytes || 0;
                    }
                    
                    if (backupType.value === 'full' || backupType.value === 'files') {
                        if (directories.value.uploads) total += sizes.value.uploads?.bytes || 0;
                        if (directories.value.themes) total += sizes.value.themes?.bytes || 0;
                        if (directories.value.plugins) total += sizes.value.plugins?.bytes || 0;
                    }
                    
                    return total > 0 ? formatSize(total) : '';
                });
                
                const nextRestoreTime = computed(() => {
                    if (!nextRestoreTimestamp.value) return null;
                    return formatDate(new Date(nextRestoreTimestamp.value * 1000).toISOString());
                });

                /* =========================
                 * Actions / Intents
                 * ========================= */
                const formatSize = (bytes) => {
                    if (!bytes || bytes === 0) return '0 B';
                    const k = 1024;
                    const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
                    const i = Math.floor(Math.log(bytes) / Math.log(k));
                    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
                };

                const formatDate = (dateString) => {
                    if (!dateString) return '';
                    const date = new Date(dateString);
                    return date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
                };
                
                const loadSizes = async () => {
                    try {
                        const response = await DemoBuilder.ajax('demo_builder_get_directory_sizes');
                        if (response.success) {
                            sizes.value = response.data.sizes;
                        }
                    } catch (error) {
                        console.error('Failed to load directory sizes', error);
                    }
                };

                const createBackup = async () => {
                    const result = await DemoBuilder.confirm({
                        title: demoBuilderData.i18n.confirm || 'Create Backup',
                        text: demoBuilderData.i18n.confirmBackup || 'Are you sure you want to create a new backup?',
                        icon: 'question'
                    });

                    if (!result.isConfirmed) return;

                    isCreating.value = true;
                    DemoBuilder.loading(demoBuilderData.i18n.backupCreating || 'Creating backup...');

                    try {
                        const selectedDirs = Object.entries(directories.value)
                            .filter(([key, val]) => val)
                            .map(([key]) => key);
                        
                        const response = await DemoBuilder.ajax('demo_builder_create_backup', {
                            name: backupName.value || defaultBackupName.value,
                            type: backupType.value,
                            options: {
                                directories: selectedDirs,
                                exclusions: exclusions.value
                            }
                        });

                        DemoBuilder.closeLoading();

                        if (response.success) {
                            backups.value.unshift(response.data.backup);
                            backupName.value = '';
                            DemoBuilder.success(demoBuilderData.i18n.backupCreated || 'Backup created successfully!');
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
                        text: demoBuilderData.i18n.confirmRestoreText || 'Are you sure you want to restore this backup? Current data will be replaced.',
                        icon: 'warning'
                    });

                    if (!result.isConfirmed) return;

                    isRestoring.value = true;
                    DemoBuilder.loading(demoBuilderData.i18n.restoreInProgress || 'Restoring backup...');

                    try {
                        const response = await DemoBuilder.ajax('demo_builder_restore_backup', {
                            backup_id: backup.id
                        });

                        DemoBuilder.closeLoading();

                        if (response.success) {
                            await Swal.fire({
                                title: demoBuilderData.i18n.restoreComplete || 'Restore Complete!',
                                text: demoBuilderData.i18n.pageReload || 'The page will reload now.',
                                icon: 'success',
                                timer: 3000,
                                showConfirmButton: false
                            });
                            window.location.reload();
                        } else {
                            DemoBuilder.error(response.data.message);
                        }
                    } catch (error) {
                        DemoBuilder.closeLoading();
                        DemoBuilder.error(demoBuilderData.i18n.networkError);
                    }

                    isRestoring.value = false;
                };

                const downloadBackup = (backup) => {
                    window.location.href = demoBuilderData.pluginUrl + 'includes/download.php?id=' + backup.id + '&nonce=' + demoBuilderData.nonce;
                };

                const deleteBackup = async (backup) => {
                    const result = await DemoBuilder.confirm({
                        title: demoBuilderData.i18n.confirmDelete || 'Delete Backup',
                        text: demoBuilderData.i18n.cannotUndo || 'This action cannot be undone.',
                        icon: 'warning'
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
                            DemoBuilder.success(demoBuilderData.i18n.deleted || 'Deleted successfully!');
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
                    event.target.value = ''; // Reset input
                };

                const handleDrop = (event) => {
                    dragOver.value = false;
                    const file = event.dataTransfer.files[0];
                    if (file) {
                        uploadBackup(file);
                    }
                };

                const uploadBackup = async (file) => {
                    // Validate file type
                    const ext = file.name.split('.').pop().toLowerCase();
                    if (!['zip', 'sql'].includes(ext)) {
                        DemoBuilder.error('Only .zip and .sql files are allowed.');
                        return;
                    }
                    
                    const formData = new FormData();
                    formData.append('action', 'demo_builder_upload_backup');
                    formData.append('nonce', demoBuilderData.nonce);
                    formData.append('backup_file', file);

                    DemoBuilder.loading(demoBuilderData.i18n.uploading || 'Uploading backup...');

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
                            DemoBuilder.success(demoBuilderData.i18n.uploaded || 'Backup uploaded successfully!');
                        } else {
                            DemoBuilder.error(response.data.message);
                        }
                    } catch (error) {
                        DemoBuilder.closeLoading();
                        DemoBuilder.error(demoBuilderData.i18n.networkError);
                    }
                };
                
                const saveAutoRestoreSettings = async () => {
                    try {
                        const response = await DemoBuilder.ajax('demo_builder_save_settings', {
                            tab: 'restore',
                            settings: {
                                auto_restore_enabled: autoRestore.value.enabled,
                                restore_interval: autoRestore.value.interval,
                                auto_restore_backup_id: autoRestore.value.backupId
                            }
                        });
                        
                        if (response.success) {
                            DemoBuilder.success(demoBuilderData.i18n.saved);
                        }
                    } catch (error) {
                        DemoBuilder.error(demoBuilderData.i18n.networkError);
                    }
                };

                /* =========================
                 * Lifecycle
                 * ========================= */
                onMounted(() => {
                    // Load directory sizes on mount
                    loadSizes();
                });

                /* =========================
                 * Expose to UI
                 * ========================= */
                return {
                    // State
                    backups,
                    sizes,
                    backupName,
                    backupType,
                    isCreating,
                    isRestoring,
                    dragOver,
                    directories,
                    exclusions,
                    autoRestore,
                    
                    // Computed
                    defaultBackupName,
                    estimatedSize,
                    nextRestoreTime,
                    
                    // Methods
                    formatSize,
                    formatDate,
                    createBackup,
                    restoreBackup,
                    downloadBackup,
                    deleteBackup,
                    handleFileSelect,
                    handleDrop,
                    saveAutoRestoreSettings
                };
            }
        }).mount('#demo-builder-backup');

    })(jQuery);
});
