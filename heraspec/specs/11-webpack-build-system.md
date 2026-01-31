# 11 - Webpack Build System

> **Priority:** Medium | **Complexity:** Medium  
> **Estimated Time:** 1 day

## Summary | Tóm tắt

**EN:** Implement Webpack build configuration to bundle and minify JS/CSS assets, with toggle switches for minification and automatic library copying.

**VI:** Triển khai cấu hình Webpack để đóng gói và minify các assets JS/CSS, với công tắc chuyển đổi minification và tự động sao chép thư viện.

---

## Proposed Changes | Các thay đổi đề xuất

### 11.1 Project Structure
**Output Directory:** `demo-builder/dist/assets/`

```
demo-builder/
├── assets/                    # Source files
│   ├── js/
│   │   ├── admin/            # Admin JS (to be built)
│   │   └── frontend/         # Frontend JS (to be built)
│   ├── css/
│   │   └── admin.css         # Admin CSS (to be built)
│   └── lib/                  # Libraries (copy only, no build)
│       ├── sweetalert2/
│       └── vue/
├── dist/                     # Built output
│   └── assets/
│       ├── js/
│       │   ├── admin/        # Minified admin JS
│       │   └── frontend/     # Minified frontend JS
│       ├── css/              # Minified CSS
│       └── lib/              # Copied libraries
│           ├── sweetalert2/
│           └── vue/
├── webpack.config.js         # Main webpack config
├── package.json              # NPM dependencies
└── .babelrc                  # Babel config (optional)
```

---

### 11.2 Webpack Configuration
**File:** `webpack.config.js`

**EN:**
Header comment with detailed build instructions:

```javascript
/**
 * Demo Builder - Webpack Build Configuration
 * ============================================
 *
 * INSTALLATION:
 * 1. Ensure Node.js and npm are installed on your system
 * 2. Navigate to plugin directory: cd wp-content/plugins/demo-builder
 * 3. Install dependencies: npm install
 *
 * BUILD COMMANDS:
 * - npm run build        : Build minified production assets
 * - npm run build:dev    : Build non-minified development assets
 * - npm run watch        : Watch for changes and rebuild
 *
 * CONFIGURATION SWITCHES:
 * - is_minified_js       : true/false - Toggle JS minification
 * - is_minified_css      : true/false - Toggle CSS minification
 * - is_console_remove    : true/false - Remove console.* statements
 *
 * OUTPUT:
 * - Source files: assets/js/, assets/css/
 * - Built files: dist/assets/js/, dist/assets/css/
 * - Libraries copied: assets/lib/ → dist/assets/lib/
 *
 * NOTES:
 * - Library files (assets/lib/*) are copied, NOT rebuilt
 * - Use dist/ files in production for better performance
 * - Set is_minified_* = false for debugging
 */
```

**VI:**
Cấu hình với comment chi tiết và các switch:
- `is_minified_js` - Bật/tắt minify JS
- `is_minified_css` - Bật/tắt minify CSS
- `is_console_remove` - Xóa console.* statements

---

### 11.3 Configuration Switches
**File:** `webpack.config.js`

```javascript
// === BUILD CONFIGURATION SWITCHES ===
const is_minified_js = true;     // Toggle JS minification
const is_minified_css = true;    // Toggle CSS minification
const is_console_remove = true;  // Remove console.* in production
```

**Usage scenarios:**
| Scenario | is_minified_js | is_minified_css | is_console_remove |
|----------|----------------|-----------------|-------------------|
| Production | true | true | true |
| Development | false | false | false |
| Debug JS only | false | true | false |

---

### 11.4 Entry Points Configuration
**File:** `webpack.config.js`

```javascript
// JS Entry Points - Auto-discover from assets/js/
const jsEntry = {
    'admin/backup-restore': './assets/js/admin/backup-restore.js',
    'admin/demo-accounts': './assets/js/admin/demo-accounts.js',
    'admin/settings': './assets/js/admin/settings.js',
    'admin/upload-chunked': './assets/js/admin/upload-chunked.js',
    'frontend/countdown': './assets/js/frontend/countdown.js',
    'frontend/login-form': './assets/js/frontend/login-form.js',
};

// CSS Entry Points
const cssEntry = {
    'css/admin': './assets/css/admin.css',
};
```

---

### 11.5 Library Copy (No Build)
**File:** `webpack.config.js`

```javascript
// Copy library files WITHOUT processing
new CopyWebpackPlugin({
    patterns: [
        {
            from: path.resolve(__dirname, './assets/lib'),
            to: path.resolve(__dirname, 'dist/assets/lib'),
            noErrorOnMissing: true,
        },
    ],
}),
```

**EN:** Libraries in `assets/lib/` (SweetAlert2, Vue.js) are copied directly to `dist/assets/lib/` without modification.

**VI:** Các thư viện trong `assets/lib/` (SweetAlert2, Vue.js) được sao chép trực tiếp sang `dist/assets/lib/` mà không thay đổi.

---

### 11.6 Package.json Scripts
**File:** `package.json`

```json
{
  "name": "demo-builder",
  "version": "1.0.0",
  "description": "Demo Builder WordPress Plugin",
  "scripts": {
    "build": "webpack --config webpack.config.js",
    "build:dev": "webpack --config webpack.config.js --env development",
    "watch": "webpack --watch --config webpack.config.js",
    "clean": "rm -rf dist/"
  },
  "devDependencies": {
    "webpack": "^5.90.0",
    "webpack-cli": "^5.1.4",
    "babel-loader": "^9.1.3",
    "@babel/core": "^7.23.9",
    "@babel/preset-env": "^7.23.9",
    "css-loader": "^6.10.0",
    "mini-css-extract-plugin": "^2.8.0",
    "css-minimizer-webpack-plugin": "^6.0.0",
    "terser-webpack-plugin": "^5.3.10",
    "copy-webpack-plugin": "^12.0.2",
    "fs-extra": "^11.2.0",
    "glob": "^10.3.10"
  }
}
```

---

### 11.7 Plugin Loader Update
**File:** `demo-builder.php`

**EN:**
Update asset enqueue functions to load from `dist/` when available:

```php
// Check if minified assets exist
$assets_dir = plugin_dir_path(__FILE__);
$use_dist = file_exists($assets_dir . 'dist/assets/js/admin/');

if ($use_dist) {
    // Load from dist/ (minified)
    wp_enqueue_script(
        'demo-builder-admin',
        plugins_url('dist/assets/js/admin/backup-restore.min.js', __FILE__),
        ['jquery'],
        DEMO_BUILDER_VERSION,
        true
    );
} else {
    // Load from assets/ (development)
    wp_enqueue_script(
        'demo-builder-admin',
        plugins_url('assets/js/admin/backup-restore.js', __FILE__),
        ['jquery'],
        DEMO_BUILDER_VERSION,
        true
    );
}
```

**VI:**
Cập nhật hàm enqueue để load từ `dist/` khi có sẵn.

---

## Files to Create | Các file cần tạo

```
demo-builder/
├── webpack.config.js    # [NEW] Webpack configuration
├── package.json         # [NEW] NPM dependencies
├── .babelrc             # [NEW] Babel configuration (optional)
└── demo-builder.php     # [MODIFY] Update asset loading
```

---

## Verification | Xác minh

### Build Tests
1. `npm install` → All dependencies installed
2. `npm run build` → Files created in `dist/assets/`
3. `npm run build:dev` → Non-minified files created
4. Libraries copied to `dist/assets/lib/`
5. Toggle `is_minified_js = false` → Non-minified JS output

### File Size Comparison
| File | Original | Minified | Reduction |
|------|----------|----------|-----------|
| admin.css | ~20KB | ~15KB | ~25% |
| backup-restore.js | ~10KB | ~4KB | ~60% |

---

## Dependencies | Phụ thuộc

- Node.js >= 16.x
- npm >= 8.x
- 01-core-foundation (asset structure)
