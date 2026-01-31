/**
 * Demo Builder - Demo Accounts Page
 * 
 * Vue.js application for demo account management
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
                const accounts = ref(window.demoBuilderAccounts || []);
                const availableUsers = ref(window.demoBuilderAvailableUsers || []);
                const roles = ref(window.demoBuilderRoles || {});
                const showAddModal = ref(false);
                const editingAccount = ref(null);
                const isLoading = ref(false);

                /* =========================
                 * Derived / Computed
                 * ========================= */
                const activeAccounts = computed(() => {
                    return accounts.value.filter(a => a.is_active == 1).length;
                });

                const inactiveAccounts = computed(() => {
                    return accounts.value.filter(a => a.is_active != 1).length;
                });

                /* =========================
                 * Actions / Intents
                 * ========================= */
                const showCredentials = async (account) => {
                    const credentials = account.credentials ? JSON.parse(account.credentials) : {};
                    
                    await Swal.fire({
                        title: account.display_name || account.wp_display_name,
                        html: `
                            <div style="text-align: left; padding: 10px;">
                                <p><strong>Username:</strong> ${account.user_login}</p>
                                <p><strong>Password:</strong> ${credentials.password || '(hidden)'}</p>
                                <p><strong>Role:</strong> ${account.role_name}</p>
                            </div>
                        `,
                        icon: 'info',
                        confirmButtonText: demoBuilderData.i18n.confirm || 'OK'
                    });
                };

                const editAccount = (account) => {
                    editingAccount.value = account;
                    showEditModal(account);
                };

                const showEditModal = async (account) => {
                    const { value: formValues } = await Swal.fire({
                        title: demoBuilderData.i18n.edit || 'Edit Account',
                        html: `
                            <div style="text-align: left;">
                                <label style="display: block; margin-bottom: 8px;">
                                    <strong>Display Name</strong>
                                    <input id="swal-display-name" class="swal2-input" value="${account.display_name || ''}" placeholder="Display Name">
                                </label>
                                <label style="display: block; margin-bottom: 8px;">
                                    <strong>Role Name</strong>
                                    <input id="swal-role-name" class="swal2-input" value="${account.role_name || ''}" placeholder="Role Name">
                                </label>
                                <label style="display: block; margin-bottom: 8px;">
                                    <strong>Description</strong>
                                    <textarea id="swal-description" class="swal2-textarea" placeholder="Description">${account.description || ''}</textarea>
                                </label>
                                <label style="display: block;">
                                    <input id="swal-is-active" type="checkbox" ${account.is_active == 1 ? 'checked' : ''}>
                                    <strong>Active</strong>
                                </label>
                            </div>
                        `,
                        focusConfirm: false,
                        showCancelButton: true,
                        confirmButtonText: demoBuilderData.i18n.save || 'Save',
                        cancelButtonText: demoBuilderData.i18n.cancel || 'Cancel',
                        preConfirm: () => {
                            return {
                                display_name: document.getElementById('swal-display-name').value,
                                role_name: document.getElementById('swal-role-name').value,
                                description: document.getElementById('swal-description').value,
                                is_active: document.getElementById('swal-is-active').checked ? 1 : 0
                            };
                        }
                    });

                    if (formValues) {
                        await updateAccount(account.id, formValues);
                    }
                };

                const updateAccount = async (id, data) => {
                    DemoBuilder.loading(demoBuilderData.i18n.loading);

                    try {
                        const response = await DemoBuilder.ajax('demo_builder_update_account', {
                            account_id: id,
                            ...data
                        });

                        DemoBuilder.closeLoading();

                        if (response.success) {
                            const index = accounts.value.findIndex(a => a.id === id);
                            if (index > -1) {
                                accounts.value[index] = { ...accounts.value[index], ...data };
                            }
                            DemoBuilder.success(demoBuilderData.i18n.updated);
                        } else {
                            DemoBuilder.error(response.data.message);
                        }
                    } catch (error) {
                        DemoBuilder.closeLoading();
                        DemoBuilder.error(demoBuilderData.i18n.networkError);
                    }
                };

                const deleteAccount = async (account) => {
                    const result = await DemoBuilder.confirm({
                        title: demoBuilderData.i18n.confirmDelete || 'Delete Account',
                        text: demoBuilderData.i18n.cannotUndo,
                        icon: 'warning',
                        confirmButtonText: demoBuilderData.i18n.delete,
                        cancelButtonText: demoBuilderData.i18n.cancel
                    });

                    if (!result.isConfirmed) return;

                    DemoBuilder.loading(demoBuilderData.i18n.pleaseWait);

                    try {
                        const response = await DemoBuilder.ajax('demo_builder_delete_account', {
                            account_id: account.id
                        });

                        DemoBuilder.closeLoading();

                        if (response.success) {
                            const index = accounts.value.findIndex(a => a.id === account.id);
                            if (index > -1) {
                                accounts.value.splice(index, 1);
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

                const generateAccounts = async () => {
                    const result = await DemoBuilder.confirm({
                        title: demoBuilderData.i18n.generateAccounts || 'Generate Demo Accounts',
                        text: 'This will create demo accounts for all WordPress roles.',
                        icon: 'question'
                    });

                    if (!result.isConfirmed) return;

                    DemoBuilder.loading(demoBuilderData.i18n.pleaseWait);

                    try {
                        const response = await DemoBuilder.ajax('demo_builder_generate_accounts');

                        DemoBuilder.closeLoading();

                        if (response.success) {
                            accounts.value = response.data.accounts;
                            DemoBuilder.success(demoBuilderData.i18n.created);
                        } else {
                            DemoBuilder.error(response.data.message);
                        }
                    } catch (error) {
                        DemoBuilder.closeLoading();
                        DemoBuilder.error(demoBuilderData.i18n.networkError);
                    }
                };

                const truncateAccounts = async () => {
                    const result = await DemoBuilder.confirm({
                        title: demoBuilderData.i18n.truncateAccounts || 'Remove All Accounts',
                        text: 'This will remove all demo accounts. This action cannot be undone.',
                        icon: 'warning'
                    });

                    if (!result.isConfirmed) return;

                    DemoBuilder.loading(demoBuilderData.i18n.pleaseWait);

                    try {
                        const response = await DemoBuilder.ajax('demo_builder_truncate_accounts');

                        DemoBuilder.closeLoading();

                        if (response.success) {
                            accounts.value = [];
                            DemoBuilder.success(demoBuilderData.i18n.deleted);
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
                    // Initialize sortable if needed
                });

                /* =========================
                 * Expose to UI
                 * ========================= */
                return {
                    // State
                    accounts,
                    availableUsers,
                    roles,
                    showAddModal,
                    isLoading,
                    
                    // Computed
                    activeAccounts,
                    inactiveAccounts,
                    
                    // Methods
                    showCredentials,
                    editAccount,
                    deleteAccount,
                    generateAccounts,
                    truncateAccounts
                };
            }
        }).mount('#demo-builder-accounts');

    })(jQuery);
});
