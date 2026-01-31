# Demo Builder for WordPress

[![WordPress](https://img.shields.io/badge/WordPress-6.x+-21759B?style=for-the-badge&logo=wordpress&logoColor=white)](https://wordpress.org)
[![PHP](https://img.shields.io/badge/PHP-8.0+-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://php.net)
[![License](https://img.shields.io/badge/License-MIT-blue?style=for-the-badge)](LICENSE)

A comprehensive WordPress plugin for creating and managing demo sites with automated backup/restore, demo account management, and cloud storage integration.

## Features

### 🗄️ Backup System
- **Database Backup** - Complete SQL export with compression
- **Source Code Backup** - Selective directory backup (uploads, themes, plugins)
- **Smart Exclusions** - Exclude inactive plugins/themes, revisions, transients, cache
- **Migration Package** - Portable backup for site cloning

### 🔄 Restore System
- **Manual Restore** - One-click restore with maintenance mode
- **Scheduled Auto-Restore** - Automatic restore at configurable intervals
- **Countdown Timer** - Visual countdown display on admin/frontend
- **URL Search & Replace** - Handle serialized data for site migration

### 👥 Demo Accounts
- **Account Management** - Link WordPress users as demo accounts
- **Login Form Integration** - Demo account selector on wp-login.php
- **Batch Operations** - Generate and truncate demo accounts
- **Permission Restrictions** - Limit demo user capabilities

### ☁️ Cloud Storage Extensions
- **Google Drive** - OAuth 2.0 integration
- **OneDrive** - Microsoft authentication
- **Auto-sync** - Upload backups to cloud automatically

### 📱 Notifications
- **Telegram Integration** - Real-time notifications for backup/restore events

### 🛠️ Maintenance Mode
- **Custom Page** - Configurable maintenance page with pastel design
- **Bypass Options** - IP whitelist, URL key, Super Admin access

## Requirements

- WordPress 6.0+
- PHP 8.0+
- ZipArchive PHP extension
- MySQL 5.7+ / MariaDB 10.3+

## Installation

1. Download or clone this repository
2. Upload to `wp-content/plugins/demo-builder/`
3. Activate the plugin via WordPress admin
4. Navigate to **Demo Builder** in the admin menu

## Directory Structure

```
demo-builder/
├── admin/                 # Admin classes and views
├── assets/                # CSS, JS, images
│   ├── css/
│   ├── js/
│   └── lib/               # Third-party libraries
├── extensions/            # Cloud storage extensions
├── includes/              # Core classes
├── languages/             # Translation files
├── heraspec/              # Development specifications
└── demo-builder.php       # Main plugin file
```

## Development

### Tech Stack
- **Frontend**: Vue.js 3 (Composition API), TailwindCSS, SweetAlert2
- **Backend**: PHP 8.0+, WordPress Plugin API
- **Storage**: MySQL/MariaDB, ZipArchive

### Build Assets
```bash
npm install
npm run build
```

### Coding Standards
- WordPress Plugin Handbook guidelines
- No inline CSS/JS - use proper enqueuing
- i18n support via data attributes

## HeraSpec Documentation

Development specifications are located in `/heraspec/`:
- `project.md` - Full project specification (EN)
- `project_vi.md` - Vietnamese version
- `AGENTS.heraspec.md` - Agent instructions
- `specs/` - Individual feature specifications

## License

This project is licensed under the MIT License.

## Support

<div align="center">

If you find this source code helpful, consider buying me a coffee to support my work! ☕

[![Buy Me A Coffee](https://img.shields.io/badge/BUY_ME_A_COFFEE-FFDD00?style=for-the-badge&logo=buy-me-a-coffee&logoColor=black)](https://paypal.me/polyxgo)

-or-

<img src="https://polyxgo.com/wp-content/uploads/2026/01/CafeCodeTrauNheNgokNgok.png" alt="Cafe code trau nhe Ngok Ngok" width="300" />

</div>
