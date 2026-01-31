# 10 - UI/UX Standards & Libraries

> **Priority:** High | **Complexity:** Low  
> **Applies To:** All admin and frontend interfaces

## Summary | Tóm tắt

**EN:** Define strict UI/UX standards including required libraries (SweetAlert2, jQuery, Vue.js), coding rules (no inline CSS/JS), and consistent styling for the pastel theme.

**VI:** Định nghĩa tiêu chuẩn UI/UX nghiêm ngặt bao gồm các thư viện bắt buộc (SweetAlert2, jQuery, Vue.js), quy tắc code (không inline CSS/JS), và styling nhất quán cho theme pastel.

---

## 10.1 Required Libraries | Thư viện Bắt buộc

### 10.1.1 SweetAlert2 (Modals & Notifications)
**Purpose:** All confirm dialogs, alerts, messages, modals, and popups

**CDN:**
```html
<link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
```

**Local (Recommended):**
```
assets/lib/sweetalert2/
├── sweetalert2.min.css
└── sweetalert2.min.js
```

---

### 10.1.2 jQuery (DOM Manipulation)
**Purpose:** DOM manipulation, AJAX requests, event handling

**Use WordPress bundled jQuery:**
```php
wp_enqueue_script('jquery');
```

---

### 10.1.3 Vue.js 3 (Two-way Binding)
**Purpose:** Reactive UI, two-way data binding, component-based interfaces

**Version:** 3.4.27 (Production build ONLY)

**Local:**
```
assets/lib/vue/
└── vue.global.prod.js    # v3.4.27 (NO dev build)
```

---

## 10.2 SweetAlert2 Custom Theme | Theme Tùy chỉnh

### 10.2.1 Pastel Theme Configuration
**File:** `assets/js/admin/sweetalert-config.js`

```javascript
// SweetAlert2 Default Configuration for Demo Builder
const DemoBuilderSwal = Swal.mixin({
    customClass: {
        popup: 'db-swal-popup',
        title: 'db-swal-title',
        htmlContainer: 'db-swal-content',
        confirmButton: 'db-swal-confirm',
        cancelButton: 'db-swal-cancel',
        denyButton: 'db-swal-deny',
        input: 'db-swal-input',
        actions: 'db-swal-actions'
    },
    buttonsStyling: false,
    showClass: {
        popup: 'animate__animated animate__fadeInDown animate__faster'
    },
    hideClass: {
        popup: 'animate__animated animate__fadeOutUp animate__faster'
    }
});

// Confirm Delete
const confirmDelete = (options = {}) => {
    return DemoBuilderSwal.fire({
        title: options.title || 'Are you sure?',
        text: options.text || 'This action cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: options.confirmText || 'Yes, delete it!',
        cancelButtonText: options.cancelText || 'Cancel'
    });
};

// Success Toast
const showSuccess = (message, title = 'Success!') => {
    return DemoBuilderSwal.fire({
        title: title,
        text: message,
        icon: 'success',
        timer: 3000,
        timerProgressBar: true,
        showConfirmButton: false
    });
};

// Error Alert
const showError = (message, title = 'Error!') => {
    return DemoBuilderSwal.fire({
        title: title,
        text: message,
        icon: 'error'
    });
};

// Loading Modal
const showLoading = (message = 'Please wait...') => {
    return DemoBuilderSwal.fire({
        title: message,
        allowOutsideClick: false,
        allowEscapeKey: false,
        showConfirmButton: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
};
```

---

### 10.2.2 Pastel CSS Styles for SweetAlert2
**File:** `assets/css/admin.css`

```css
/* ========================================
   SweetAlert2 Pastel Theme
   ======================================== */

/* Popup Container */
.db-swal-popup {
    border-radius: 16px !important;
    padding: 24px !important;
    background: #FFFFFF !important;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15) !important;
}

/* Title */
.db-swal-title {
    color: #334155 !important;
    font-size: 1.5rem !important;
    font-weight: 600 !important;
}

/* Content */
.db-swal-content {
    color: #64748B !important;
    font-size: 1rem !important;
}

/* Confirm Button - Primary Pastel */
.db-swal-confirm {
    background: linear-gradient(135deg, #A5B4FC 0%, #818CF8 100%) !important;
    color: #FFFFFF !important;
    border: none !important;
    border-radius: 12px !important;
    padding: 12px 24px !important;
    font-weight: 600 !important;
    font-size: 0.875rem !important;
    transition: all 0.2s ease !important;
    margin: 0 8px !important;
}

.db-swal-confirm:hover {
    background: linear-gradient(135deg, #818CF8 0%, #6366F1 100%) !important;
    transform: translateY(-2px) !important;
    box-shadow: 0 4px 12px rgba(129, 140, 248, 0.4) !important;
}

/* Cancel Button - Neutral */
.db-swal-cancel {
    background: #F1F5F9 !important;
    color: #64748B !important;
    border: 1px solid #E2E8F0 !important;
    border-radius: 12px !important;
    padding: 12px 24px !important;
    font-weight: 600 !important;
    font-size: 0.875rem !important;
    transition: all 0.2s ease !important;
    margin: 0 8px !important;
}

.db-swal-cancel:hover {
    background: #E2E8F0 !important;
    color: #334155 !important;
}

/* Deny Button - Danger Pastel */
.db-swal-deny {
    background: linear-gradient(135deg, #FCA5A5 0%, #F87171 100%) !important;
    color: #FFFFFF !important;
    border: none !important;
    border-radius: 12px !important;
    padding: 12px 24px !important;
    font-weight: 600 !important;
}

/* Input Fields */
.db-swal-input {
    border: 2px solid #E2E8F0 !important;
    border-radius: 12px !important;
    padding: 12px 16px !important;
    font-size: 1rem !important;
    transition: border-color 0.2s ease !important;
}

.db-swal-input:focus {
    border-color: #A5B4FC !important;
    box-shadow: 0 0 0 3px rgba(165, 180, 252, 0.2) !important;
    outline: none !important;
}

/* Icon Colors - Pastel */
.swal2-icon.swal2-success {
    border-color: #86EFAC !important;
    color: #86EFAC !important;
}

.swal2-icon.swal2-success .swal2-success-line-tip,
.swal2-icon.swal2-success .swal2-success-line-long {
    background-color: #22C55E !important;
}

.swal2-icon.swal2-warning {
    border-color: #FDE68A !important;
    color: #F59E0B !important;
}

.swal2-icon.swal2-error {
    border-color: #FCA5A5 !important;
    color: #EF4444 !important;
}

.swal2-icon.swal2-info {
    border-color: #7DD3FC !important;
    color: #0EA5E9 !important;
}

/* Timer Progress Bar */
.swal2-timer-progress-bar {
    background: linear-gradient(90deg, #A5B4FC 0%, #C4B5FD 100%) !important;
}
```

---

## 10.3 Strict Coding Rules | Quy tắc Code Nghiêm ngặt

### 10.3.1 ❌ TUYỆT ĐỐI KHÔNG Inline CSS/JS

**KHÔNG ĐƯỢC:**
```html
<!-- ❌ WRONG - Inline CSS -->
<div style="color: red; padding: 10px;">Content</div>

<!-- ❌ WRONG - Inline JS -->
<button onclick="doSomething()">Click</button>
<a href="javascript:void(0)" onclick="delete(1)">Delete</a>

<!-- ❌ WRONG - Style tag in body -->
<style>.my-class { color: blue; }</style>

<!-- ❌ WRONG - Script tag with inline code in views -->
<script>
var x = 1;
function doSomething() {}
</script>
```

**PHẢI LÀM:**
```html
<!-- ✅ CORRECT - CSS classes -->
<div class="db-content db-text-error db-p-3">Content</div>

<!-- ✅ CORRECT - Data attributes + jQuery/Vue binding -->
<button class="db-btn-delete" data-id="123">Click</button>

<!-- ✅ CORRECT - External files -->
<!-- In PHP: wp_enqueue_style(), wp_enqueue_script() -->
```

---

### 10.3.2 ✅ Required Patterns | Mẫu Bắt buộc

**jQuery Event Binding:**
```javascript
// ✅ CORRECT - Delegated events
$(document).on('click', '.db-btn-delete', function(e) {
    e.preventDefault();
    const id = $(this).data('id');
    confirmDelete({
        title: 'Delete this item?'
    }).then((result) => {
        if (result.isConfirmed) {
            // AJAX delete
        }
    });
});
```

**Vue.js Two-way Binding (Composition API):**
```javascript
// ✅ CORRECT - Vue 3 Composition API
window.addEventListener("load", function () {
  (function ($) {
    "use strict";
    if (typeof Vue === "undefined") return;
    
    const { createApp, ref } = Vue;
    
    createApp({
      setup() {
        const items = ref([]);
        const form = ref({ name: '', email: '' });
        
        const deleteItem = async (id) => {
          const result = await confirmDelete({ title: 'Delete?' });
          if (result.isConfirmed) {
            showSuccess('Deleted!');
          }
        };
        
        return { items, form, deleteItem };
      }
    }).mount('#my-app');
  })(jQuery);
});
```

---

## 10.4 File Organization | Tổ chức File

### 10.4.1 Directory Structure
```
assets/
├── css/
│   ├── admin.css              # All admin styles (compiled)
│   ├── admin.min.css          # Minified
│   └── frontend.css           # Frontend styles
├── js/
│   ├── admin/
│   │   ├── common.js          # Shared functions
│   │   ├── sweetalert-config.js
│   │   ├── backup-restore.js
│   │   ├── demo-accounts.js
│   │   └── settings.js
│   └── frontend/
│       ├── login-form.js
│       └── countdown-timer.js
├── lib/
│   ├── vue/
│   │   └── vue.global.prod.js
│   ├── sweetalert2/
│   │   ├── sweetalert2.min.css
│   │   └── sweetalert2.min.js
│   ├── select2/
│   │   ├── select2.min.css
│   │   └── select2.min.js
│   ├── sortablejs/
│   │   └── Sortable.min.js
│   └── clipboard/
│       └── clipboard.min.js
└── src/
    └── admin.css              # TailwindCSS source (if used)
```

---

### 10.4.2 Enqueue Standards
**File:** `admin/class-admin.php`

```php
public function enqueue_admin_assets($hook) {
    // Only on plugin pages
    if (strpos($hook, 'demo-builder') === false) {
        return;
    }
    
    $version = DEMO_BUILDER_VERSION;
    $base_url = DEMO_BUILDER_URL . 'assets/';
    
    // Libraries
    wp_enqueue_style('sweetalert2', $base_url . 'lib/sweetalert2/sweetalert2.min.css', [], '11.0.0');
    wp_enqueue_script('sweetalert2', $base_url . 'lib/sweetalert2/sweetalert2.min.js', [], '11.0.0', true);
    wp_enqueue_script('vue', $base_url . 'lib/vue/vue.global.prod.js', [], '3.4.0', true);
    
    // Plugin styles
    wp_enqueue_style('demo-builder-admin', $base_url . 'css/admin.css', ['sweetalert2'], $version);
    
    // Common scripts
    wp_enqueue_script('demo-builder-common', $base_url . 'js/admin/common.js', ['jquery', 'sweetalert2', 'vue'], $version, true);
    wp_enqueue_script('demo-builder-swal-config', $base_url . 'js/admin/sweetalert-config.js', ['sweetalert2'], $version, true);
    
    // Page-specific scripts
    $page = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '';
    
    if ($page === 'demo-builder-backup') {
        wp_enqueue_script('demo-builder-backup', $base_url . 'js/admin/backup-restore.js', ['demo-builder-common'], $version, true);
    }
    
    // Localize for AJAX
    wp_localize_script('demo-builder-common', 'demoBuilderData', [
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('demo_builder_nonce'),
        'strings' => [
            'confirmDelete' => __('Are you sure you want to delete?', 'demo-builder'),
            'success' => __('Success!', 'demo-builder'),
            'error' => __('Error!', 'demo-builder')
        ]
    ]);
}
```

---

## 10.5 Modal Examples | Ví dụ Modal

### 10.5.1 Confirm Delete
```javascript
$('.delete-backup').on('click', async function(e) {
    e.preventDefault();
    const id = $(this).data('id');
    const name = $(this).data('name');
    
    const result = await DemoBuilderSwal.fire({
        title: 'Delete Backup?',
        html: `Are you sure you want to delete <strong>${name}</strong>?<br>This action cannot be undone.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel'
    });
    
    if (result.isConfirmed) {
        showLoading('Deleting...');
        
        // AJAX call
        $.post(demoBuilderData.ajaxUrl, {
            action: 'demo_builder_delete_backup',
            nonce: demoBuilderData.nonce,
            id: id
        }).done(function(response) {
            Swal.close();
            if (response.success) {
                showSuccess('Backup deleted successfully!');
                // Remove from list
            } else {
                showError(response.data.message);
            }
        }).fail(function() {
            showError('Network error. Please try again.');
        });
    }
});
```

---

### 10.5.2 Input Modal
```javascript
async function promptBackupName() {
    const { value: name } = await DemoBuilderSwal.fire({
        title: 'Backup Name',
        input: 'text',
        inputLabel: 'Enter a name for this backup',
        inputPlaceholder: 'my-backup-2026-01-31',
        inputValidator: (value) => {
            if (!value) {
                return 'Please enter a backup name';
            }
            if (!/^[a-zA-Z0-9\-_]+$/.test(value)) {
                return 'Only letters, numbers, hyphens and underscores allowed';
            }
        },
        showCancelButton: true
    });
    
    return name;
}
```

---

### 10.5.3 Progress Modal
```javascript
function showProgressModal(title = 'Processing...') {
    return DemoBuilderSwal.fire({
        title: title,
        html: `
            <div class="db-progress-container">
                <div class="db-progress-bar" id="swal-progress-bar">
                    <div class="db-progress-fill" style="width: 0%"></div>
                </div>
                <div class="db-progress-text" id="swal-progress-text">0%</div>
                <div class="db-progress-status" id="swal-progress-status">Starting...</div>
            </div>
        `,
        allowOutsideClick: false,
        allowEscapeKey: false,
        showConfirmButton: false
    });
}

function updateProgress(percent, status = '') {
    const fill = document.querySelector('#swal-progress-bar .db-progress-fill');
    const text = document.getElementById('swal-progress-text');
    const statusEl = document.getElementById('swal-progress-status');
    
    if (fill) fill.style.width = percent + '%';
    if (text) text.textContent = percent + '%';
    if (statusEl && status) statusEl.textContent = status;
}
```

---

## 10.6 Vue.js Patterns | Mẫu Vue.js

### 10.6.1 Standard App Structure (Composition API)
**File:** `assets/js/admin/[feature].js`

```javascript
import { effectA } from "./common/effects.js";
import { helperA } from "./common/helpers.js";
import { serviceBackup } from "./services/backup.js";

window.addEventListener("load", function () {
  (function ($) {
    "use strict";

    if (typeof Vue === "undefined") return;

    const { createApp, ref, computed, onMounted, watch } = Vue;

    createApp({
      setup() {
        /* =========================
         * State
         * ========================= */
        const loading = ref(false);
        const items = ref([]);
        const selectedItem = ref(null);
        const form = ref({
          name: '',
          email: '',
          status: 'active'
        });
        const filters = ref({
          status: 'all',
          search: ''
        });

        // i18n from PHP
        const i18n = demoBuilderData.i18n || {};

        /* =========================
         * Derived / Computed
         * ========================= */
        const filteredItems = computed(() => {
          return items.value.filter(item => {
            const matchStatus = filters.value.status === 'all' || 
                                item.status === filters.value.status;
            const matchSearch = !filters.value.search || 
                                item.name.toLowerCase().includes(filters.value.search.toLowerCase());
            return matchStatus && matchSearch;
          });
        });

        const hasItems = computed(() => items.value.length > 0);

        /* =========================
         * Actions / Intents
         * ========================= */
        const fetchItems = async () => {
          loading.value = true;
          try {
            const response = await fetch(demoBuilderData.ajaxUrl + '?action=demo_builder_get_items');
            const data = await response.json();
            if (data.success) {
              items.value = data.data;
            }
          } catch (error) {
            showError(i18n.networkError || 'Network error');
          } finally {
            loading.value = false;
          }
        };

        const deleteItem = async (item) => {
          const result = await DemoBuilderSwal.fire({
            title: i18n.confirmDelete || 'Are you sure?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: i18n.delete || 'Delete',
            cancelButtonText: i18n.cancel || 'Cancel'
          });

          if (result.isConfirmed) {
            showLoading(i18n.pleaseWait || 'Please wait...');
            try {
              const response = await $.post(demoBuilderData.ajaxUrl, {
                action: 'demo_builder_delete_item',
                nonce: demoBuilderData.nonce,
                id: item.id
              });
              
              Swal.close();
              
              if (response.success) {
                items.value = items.value.filter(i => i.id !== item.id);
                showSuccess(i18n.deleted || 'Deleted successfully!');
              } else {
                showError(response.data?.message || i18n.errorGeneric);
              }
            } catch (error) {
              Swal.close();
              showError(i18n.networkError || 'Network error');
            }
          }
        };

        const saveItem = async () => {
          loading.value = true;
          // Save logic...
        };

        const resetForm = () => {
          form.value = { name: '', email: '', status: 'active' };
          selectedItem.value = null;
        };

        /* =========================
         * Lifecycle
         * ========================= */
        onMounted(() => {
          fetchItems();
        });

        /* =========================
         * Expose to UI
         * ========================= */
        return {
          // State
          loading,
          items,
          selectedItem,
          form,
          filters,
          i18n,
          
          // Computed
          filteredItems,
          hasItems,
          
          // Actions
          fetchItems,
          deleteItem,
          saveItem,
          resetForm
        };
      },
    }).mount("#demo-builder-app");
  })(jQuery);
});
```

---

### 10.6.2 File Structure for Vue Apps
```
assets/js/admin/
├── common/
│   ├── effects.js         # Side effects (animations, etc.)
│   └── helpers.js         # Utility functions
├── services/
│   ├── backup.js          # Backup API calls
│   ├── restore.js         # Restore API calls
│   └── accounts.js        # Demo accounts API
├── backup-restore.js      # Main backup/restore Vue app
├── demo-accounts.js       # Main demo accounts Vue app
├── settings.js            # Settings Vue app
└── sweetalert-config.js   # SweetAlert2 helpers
```

---

### 10.6.3 HTML Template Pattern
```php
<div id="demo-builder-app" v-cloak>
    <!-- Loading State -->
    <div v-if="loading" class="db-loading">
        <span class="spinner is-active"></span>
        {{ i18n.loading }}
    </div>
    
    <!-- Content -->
    <div v-else>
        <!-- Filters -->
        <div class="db-filters">
            <input 
                type="text" 
                v-model="filters.search" 
                :placeholder="i18n.search"
                class="db-input"
            />
            <select v-model="filters.status" class="db-select">
                <option value="all">{{ i18n.all }}</option>
                <option value="active">{{ i18n.active }}</option>
            </select>
        </div>
        
        <!-- Items List -->
        <div v-if="hasItems">
            <div 
                v-for="item in filteredItems" 
                :key="item.id"
                class="db-item"
            >
                <span>{{ item.name }}</span>
                <button 
                    @click="deleteItem(item)"
                    class="db-btn-delete"
                >
                    {{ i18n.delete }}
                </button>
            </div>
        </div>
        
        <!-- Empty State -->
        <div v-else class="db-empty">
            {{ i18n.noItems }}
        </div>
    </div>
</div>
```

---

## Verification | Xác minh

### Checklist
- [ ] No inline `style=""` attributes in PHP views
- [ ] No inline `onclick=""` or `onchange=""` handlers
- [ ] All CSS in external files (`assets/css/`)
- [ ] All JS in external files (`assets/js/`)
- [ ] SweetAlert2 used for all dialogs/modals
- [ ] jQuery used for DOM manipulation
- [ ] Vue.js used for reactive interfaces
- [ ] Proper `wp_enqueue_*` for all assets
- [ ] Consistent pastel styling across all modals

---

## Dependencies | Phụ thuộc

- SweetAlert2 v11+
- jQuery (WordPress bundled)
- Vue.js 3.x
- Select2 (optional, for enhanced dropdowns)
- SortableJS (optional, for drag-and-drop)

---

## 10.7 Internationalization (i18n) | Đa ngôn ngữ

### 10.7.1 Text Domain
**Text Domain:** `demo-builder`

**Load Text Domain:**
```php
// In main plugin file
add_action('plugins_loaded', function() {
    load_plugin_textdomain(
        'demo-builder',
        false,
        dirname(plugin_basename(__FILE__)) . '/languages/'
    );
});
```

---

### 10.7.2 PHP Labels
**Standard WordPress i18n functions:**

```php
// Simple string
__('Backup', 'demo-builder')

// Echo directly
_e('Settings', 'demo-builder')

// With placeholders
sprintf(__('Backup created at %s', 'demo-builder'), $date)

// Plural forms
_n('1 item', '%d items', $count, 'demo-builder')

// Escaped for HTML attribute
esc_attr__('Delete backup', 'demo-builder')

// Escaped for HTML output
esc_html__('Are you sure?', 'demo-builder')
```

---

### 10.7.3 ✅ JavaScript Labels via Data Attributes
**CRITICAL:** For modals, alerts, popups - use data attributes to pass translated strings from PHP to JS.

**PHP (View):**
```php
<button 
    class="db-btn-delete" 
    data-id="<?php echo esc_attr($backup->id); ?>"
    data-name="<?php echo esc_attr($backup->name); ?>"
    data-popup-title="<?php echo esc_attr__('Delete Backup?', 'demo-builder'); ?>"
    data-popup-text="<?php echo esc_attr__('Are you sure you want to delete this backup? This action cannot be undone.', 'demo-builder'); ?>"
    data-popup-confirm="<?php echo esc_attr__('Yes, delete it!', 'demo-builder'); ?>"
    data-popup-cancel="<?php echo esc_attr__('Cancel', 'demo-builder'); ?>"
    data-success-message="<?php echo esc_attr__('Backup deleted successfully!', 'demo-builder'); ?>"
    data-error-message="<?php echo esc_attr__('Failed to delete backup.', 'demo-builder'); ?>"
>
    <?php _e('Delete', 'demo-builder'); ?>
</button>
```

**JavaScript:**
```javascript
$('.db-btn-delete').on('click', async function(e) {
    e.preventDefault();
    const $btn = $(this);
    
    // Get translated strings from data attributes
    const id = $btn.data('id');
    const name = $btn.data('name');
    const title = $btn.data('popup-title');
    const text = $btn.data('popup-text').replace('%s', name);
    const confirmText = $btn.data('popup-confirm');
    const cancelText = $btn.data('popup-cancel');
    const successMsg = $btn.data('success-message');
    const errorMsg = $btn.data('error-message');
    
    const result = await DemoBuilderSwal.fire({
        title: title,
        text: text,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: confirmText,
        cancelButtonText: cancelText
    });
    
    if (result.isConfirmed) {
        // AJAX call
        $.post(demoBuilderData.ajaxUrl, {
            action: 'demo_builder_delete_backup',
            nonce: demoBuilderData.nonce,
            id: id
        }).done(function(response) {
            if (response.success) {
                showSuccess(successMsg);
            } else {
                showError(errorMsg);
            }
        });
    }
});
```

---

### 10.7.4 Data Attributes Naming Convention

| Attribute | Purpose | Example |
|-----------|---------|---------|
| `data-popup-title` | Modal/alert title | `<?php echo esc_attr__('Confirm', 'demo-builder'); ?>` |
| `data-popup-text` | Modal body text | `<?php echo esc_attr__('Are you sure?', 'demo-builder'); ?>` |
| `data-popup-confirm` | Confirm button text | `<?php echo esc_attr__('Yes', 'demo-builder'); ?>` |
| `data-popup-cancel` | Cancel button text | `<?php echo esc_attr__('Cancel', 'demo-builder'); ?>` |
| `data-success-message` | Success notification | `<?php echo esc_attr__('Done!', 'demo-builder'); ?>` |
| `data-error-message` | Error notification | `<?php echo esc_attr__('Failed!', 'demo-builder'); ?>` |
| `data-loading-text` | Loading state text | `<?php echo esc_attr__('Please wait...', 'demo-builder'); ?>` |
| `data-confirm-text` | Generic confirmation | `<?php echo esc_attr__('Confirm', 'demo-builder'); ?>` |

---

### 10.7.5 Vue.js i18n Integration
**Pass translations to Vue via wp_localize_script:**

**PHP:**
```php
wp_localize_script('demo-builder-common', 'demoBuilderData', [
    'ajaxUrl' => admin_url('admin-ajax.php'),
    'nonce' => wp_create_nonce('demo_builder_nonce'),
    'i18n' => [
        // General
        'confirm' => __('Confirm', 'demo-builder'),
        'cancel' => __('Cancel', 'demo-builder'),
        'save' => __('Save', 'demo-builder'),
        'delete' => __('Delete', 'demo-builder'),
        'loading' => __('Loading...', 'demo-builder'),
        'pleaseWait' => __('Please wait...', 'demo-builder'),
        
        // Success messages
        'saved' => __('Saved successfully!', 'demo-builder'),
        'deleted' => __('Deleted successfully!', 'demo-builder'),
        
        // Error messages
        'errorGeneric' => __('An error occurred. Please try again.', 'demo-builder'),
        'networkError' => __('Network error. Please check your connection.', 'demo-builder'),
        
        // Confirmations
        'confirmDelete' => __('Are you sure you want to delete this?', 'demo-builder'),
        'confirmRestore' => __('Are you sure you want to restore this backup?', 'demo-builder'),
        
        // Backup specific
        'backupCreating' => __('Creating backup...', 'demo-builder'),
        'backupCreated' => __('Backup created successfully!', 'demo-builder'),
        'restoreInProgress' => __('Restore in progress...', 'demo-builder'),
        'restoreComplete' => __('Restore completed!', 'demo-builder')
    ]
]);
```

**Vue.js Usage (Composition API):**
```javascript
window.addEventListener("load", function () {
  (function ($) {
    "use strict";

    if (typeof Vue === "undefined") return;

    const { createApp, ref, onMounted } = Vue;

    createApp({
      setup() {
        /* =========================
         * State
         * ========================= */
        const i18n = demoBuilderData.i18n || {};
        const items = ref([]);

        /* =========================
         * Actions / Intents
         * ========================= */
        const confirmDeleteItem = async (item) => {
          const result = await DemoBuilderSwal.fire({
            title: i18n.confirmDelete || 'Are you sure?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: i18n.delete || 'Delete',
            cancelButtonText: i18n.cancel || 'Cancel'
          });

          if (result.isConfirmed) {
            showLoading(i18n.pleaseWait || 'Please wait...');
            // Delete logic...
            showSuccess(i18n.deleted || 'Deleted!');
          }
        };

        /* =========================
         * Expose to UI
         * ========================= */
        return {
          i18n,
          items,
          confirmDeleteItem
        };
      },
    }).mount("#demo-builder-app");
  })(jQuery);
});
```

---

### 10.7.6 ❌ KHÔNG ĐƯỢC Hardcode Text trong JS

```javascript
// ❌ WRONG - Hardcoded strings in JS
Swal.fire({
    title: 'Are you sure?',
    text: 'This cannot be undone'
});

// ❌ WRONG - Template literals with hardcoded text
alert(`Backup ${name} deleted`);
```

```javascript
// ✅ CORRECT - Use data attributes
const title = $(this).data('popup-title');
const text = $(this).data('popup-text');
Swal.fire({ title, text });

// ✅ CORRECT - Use i18n object
Swal.fire({
    title: demoBuilderData.i18n.confirmDelete,
    text: demoBuilderData.i18n.cannotUndo
});
```

---

### 10.7.7 Language Files Structure
```
demo-builder/
└── languages/
    ├── demo-builder.pot           # Template file
    ├── demo-builder-vi.po         # Vietnamese
    ├── demo-builder-vi.mo         # Compiled Vietnamese
    ├── demo-builder-ja.po         # Japanese
    └── demo-builder-ja.mo         # Compiled Japanese
```

**Generate POT file:**
```bash
# Using WP-CLI
wp i18n make-pot wp-content/plugins/demo-builder wp-content/plugins/demo-builder/languages/demo-builder.pot
```
