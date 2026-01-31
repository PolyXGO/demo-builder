/**
 * Demo Builder - Demo Accounts Page
 * 
 * Vue.js application for demo account management (Enhanced)
 * 
 * @package DemoBuilder
 */

window.addEventListener("load", function () {
    "use strict";

    (function ($) {
        if (typeof Vue === "undefined") return;
        if (!document.getElementById("demo-builder-accounts")) return;

        const { createApp, ref, computed, onMounted, watch } = Vue;

        createApp({
            setup() {
                /* =========================
                 * State
                 * ========================= */
                const accounts = ref(window.demoBuilderAccounts || []);
                const availableUsers = ref([]);
                const wpRoles = ref([]);
                
                const searchQuery = ref('');
                const filterStatus = ref('all');
                
                const showModal = ref(false);
                const showGenerateModal = ref(false);
                const showCredentialsModal = ref(false);
                
                const isEditing = ref(false);
                const isSaving = ref(false);
                const isGenerating = ref(false);
                
                const selectedAccount = ref({});
                
                const form = ref({
                    id: null,
                    user_id: '',
                    role_name: '',
                    password_plain: '',
                    message: ''
                });
                
                const generateForm = ref({
                    count: 5,
                    role: 'subscriber',
                    role_name: 'Demo User',
                    password_type: 'random',
                    password_value: 'demo123'
                });

                /* =========================
                 * Computed
                 * ========================= */
                const stats = computed(() => {
                    const total = accounts.value.length;
                    const active = accounts.value.filter(a => a.is_active == 1).length;
                    return {
                        total,
                        active,
                        inactive: total - active
                    };
                });
                
                const filteredAccounts = computed(() => {
                    let result = accounts.value;
                    
                    if (filterStatus.value === 'active') {
                        result = result.filter(a => a.is_active == 1);
                    } else if (filterStatus.value === 'inactive') {
                        result = result.filter(a => a.is_active == 0);
                    }
                    
                    if (searchQuery.value) {
                        const query = searchQuery.value.toLowerCase();
                        result = result.filter(a => 
                            (a.user_login && a.user_login.toLowerCase().includes(query)) ||
                            (a.user_email && a.user_email.toLowerCase().includes(query)) ||
                            (a.display_name && a.display_name.toLowerCase().includes(query)) ||
                            (a.role_name && a.role_name.toLowerCase().includes(query))
                        );
                    }
                    
                    return result;
                });

                /* =========================
                 * Methods
                 * ========================= */
                const loadUsers = async () => {
                    try {
                        const response = await DemoBuilder.ajax('demo_builder_get_wp_users');
                        if (response.success) {
                            availableUsers.value = response.data.users;
                        }
                    } catch (error) {
                        console.error('Failed to load users', error);
                    }
                };
                
                const loadRoles = async () => {
                    try {
                        const response = await DemoBuilder.ajax('demo_builder_get_wp_roles');
                        if (response.success) {
                            wpRoles.value = response.data.roles;
                        }
                    } catch (error) {
                        console.error('Failed to load roles', error);
                    }
                };
                
                const openCreateModal = () => {
                    isEditing.value = false;
                    form.value = {
                        id: null,
                        user_id: '',
                        role_name: '',
                        password_plain: '',
                        message: ''
                    };
                    loadUsers();
                    showModal.value = true;
                };
                
                const editAccount = (account) => {
                    isEditing.value = true;
                    form.value = {
                        id: account.id,
                        user_id: account.user_id,
                        role_name: account.role_name || '',
                        password_plain: account.password_plain || '',
                        message: account.message || ''
                    };
                    showModal.value = true;
                };
                
                const closeModal = () => {
                    showModal.value = false;
                };
                
                const saveAccount = async () => {
                    isSaving.value = true;
                    
                    try {
                        const action = isEditing.value 
                            ? 'demo_builder_update_demo_account' 
                            : 'demo_builder_create_demo_account';
                        
                        const response = await DemoBuilder.ajax(action, form.value);
                        
                        if (response.success) {
                            if (isEditing.value) {
                                const index = accounts.value.findIndex(a => a.id == form.value.id);
                                if (index > -1) {
                                    accounts.value[index] = response.data.account;
                                }
                            } else {
                                accounts.value.push(response.data.account);
                            }
                            
                            DemoBuilder.success(response.data.message);
                            closeModal();
                        } else {
                            DemoBuilder.error(response.data.message);
                        }
                    } catch (error) {
                        DemoBuilder.error(demoBuilderData.i18n.networkError);
                    }
                    
                    isSaving.value = false;
                };
                
                const deleteAccount = async (account) => {
                    const result = await DemoBuilder.confirm({
                        title: demoBuilderData.i18n.confirmDelete || 'Delete Account',
                        text: demoBuilderData.i18n.cannotUndo || 'This action cannot be undone.',
                        icon: 'warning'
                    });
                    
                    if (!result.isConfirmed) return;
                    
                    try {
                        const response = await DemoBuilder.ajax('demo_builder_delete_demo_account', {
                            id: account.id
                        });
                        
                        if (response.success) {
                            const index = accounts.value.findIndex(a => a.id == account.id);
                            if (index > -1) {
                                accounts.value.splice(index, 1);
                            }
                            DemoBuilder.success(response.data.message);
                        } else {
                            DemoBuilder.error(response.data.message);
                        }
                    } catch (error) {
                        DemoBuilder.error(demoBuilderData.i18n.networkError);
                    }
                };
                
                const toggleAccount = async (account) => {
                    try {
                        const response = await DemoBuilder.ajax('demo_builder_toggle_demo_account', {
                            id: account.id
                        });
                        
                        if (response.success) {
                            account.is_active = response.data.is_active;
                            DemoBuilder.success(response.data.message);
                        }
                    } catch (error) {
                        DemoBuilder.error(demoBuilderData.i18n.networkError);
                    }
                };
                
                const viewCredentials = (account) => {
                    selectedAccount.value = account;
                    showCredentialsModal.value = true;
                };
                
                const closeCredentialsModal = () => {
                    showCredentialsModal.value = false;
                };
                
                const copyPassword = (account) => {
                    copyToClipboard(account.password_plain);
                };
                
                const copyToClipboard = (text) => {
                    navigator.clipboard.writeText(text).then(() => {
                        DemoBuilder.success(demoBuilderData.i18n.copied || 'Copied!');
                    });
                };
                
                const openGenerateModal = () => {
                    loadRoles();
                    showGenerateModal.value = true;
                };
                
                const closeGenerateModal = () => {
                    showGenerateModal.value = false;
                };
                
                const generateAccounts = async () => {
                    isGenerating.value = true;
                    
                    try {
                        const response = await DemoBuilder.ajax('demo_builder_generate_demo_accounts', generateForm.value);
                        
                        if (response.success) {
                            accounts.value.push(...response.data.accounts);
                            DemoBuilder.success(response.data.message);
                            closeGenerateModal();
                        } else {
                            DemoBuilder.error(response.data.message);
                        }
                    } catch (error) {
                        DemoBuilder.error(demoBuilderData.i18n.networkError);
                    }
                    
                    isGenerating.value = false;
                };
                
                const truncateAccounts = async () => {
                    const result = await DemoBuilder.confirm({
                        title: demoBuilderData.i18n.confirmDelete || 'Remove All Accounts',
                        text: 'This will remove all demo accounts. Are you sure?',
                        icon: 'warning',
                        confirmButtonText: 'Yes, remove all'
                    });
                    
                    if (!result.isConfirmed) return;
                    
                    try {
                        const response = await DemoBuilder.ajax('demo_builder_truncate_demo_accounts', {
                            delete_users: false
                        });
                        
                        if (response.success) {
                            accounts.value = [];
                            DemoBuilder.success(response.data.message);
                        } else {
                            DemoBuilder.error(response.data.message);
                        }
                    } catch (error) {
                        DemoBuilder.error(demoBuilderData.i18n.networkError);
                    }
                };
                
                const initSortable = () => {
                    if (typeof Sortable !== 'undefined') {
                        const tbody = document.querySelector('#accounts-table tbody');
                        if (tbody) {
                            Sortable.create(tbody, {
                                handle: '.db-drag-handle',
                                animation: 150,
                                onEnd: async function(evt) {
                                    const order = [];
                                    tbody.querySelectorAll('tr[data-id]').forEach(tr => {
                                        order.push(tr.dataset.id);
                                    });
                                    
                                    await DemoBuilder.ajax('demo_builder_update_sort_order', { order });
                                }
                            });
                        }
                    }
                };

                /* =========================
                 * Lifecycle
                 * ========================= */
                onMounted(() => {
                    // Initialize sortable after Vue renders
                    setTimeout(initSortable, 100);
                });

                /* =========================
                 * Expose
                 * ========================= */
                return {
                    // State
                    accounts,
                    availableUsers,
                    wpRoles,
                    searchQuery,
                    filterStatus,
                    showModal,
                    showGenerateModal,
                    showCredentialsModal,
                    isEditing,
                    isSaving,
                    isGenerating,
                    selectedAccount,
                    form,
                    generateForm,
                    
                    // Computed
                    stats,
                    filteredAccounts,
                    
                    // Methods
                    openCreateModal,
                    editAccount,
                    closeModal,
                    saveAccount,
                    deleteAccount,
                    toggleAccount,
                    viewCredentials,
                    closeCredentialsModal,
                    copyPassword,
                    copyToClipboard,
                    openGenerateModal,
                    closeGenerateModal,
                    generateAccounts,
                    truncateAccounts
                };
            }
        }).mount('#demo-builder-accounts');

    })(jQuery);
});
