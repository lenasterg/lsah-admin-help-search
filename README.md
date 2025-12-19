# LSAH Admin Help Search

**A WordPress plugin that adds a quick help search box directly in the admin menu.**  
Configurable search URL, full search term logging, and built-in statistics page. Fully compatible with multisite networks.

[![WordPress Version](https://img.shields.io/wordpress/plugin/v/lsah-admin-help-search?label=Tested%20up%20to)](https://wordpress.org/plugins/)
[![PHP Version](https://img.shields.io/badge/PHP-7.4%2B-blue)](https://www.php.net/)
[![License](https://img.shields.io/badge/License-GPLv2%20or%20later-green)](https://www.gnu.org/licenses/gpl-2.0.html)
[![Contributors](https://img.shields.io/badge/Contributor-lenasterg-orange)](https://github.com/lenasterg)

## Description

**LSAH Admin Help Search** adds a convenient search field right in the WordPress admin menu (position 1, immediately after the Dashboard). Administrators can quickly search your custom help documentation, knowledge base, or any external search engine.

### Key Features

- Works on **all sites** in a multisite network and on single-site installations.
- Configurable search action URL (set by Network Admin or site admin).
- Logs every search in a network-wide database table (`{prefix}lsah_admin_searches`) including:
  - Site ID (`blog_id`)
  - Search term
  - Full search URL
  - Count
  - First and last searched dates
- **Statistics page** with searchable and sortable table of all recorded searches (Network Admin → Settings → Help Search Statistics).
- Fully secure:
  - Strict client-side + server-side URL validation
  - Safe form action assignment via enqueued JavaScript
  - Nonce-protected AJAX logging
  - Proper sanitization and escaping throughout
- Translation-ready (`lsah-admin-help-search` text domain).
- Clean, standards-compliant code with separate CSS/JS assets.

Ideal for multisite networks with a centralized help system (e.g., internal wiki, custom manual, Google Custom Search, Algolia, etc.).

## Installation

1. Upload the `lsah-admin-help-search` folder to `/wp-content/plugins/`.
2. Activate the plugin:
   - On multisite: via **Network Admin → Plugins**
   - On single-site: via **Plugins**
3. Go to:
   - Multisite: **Network Admin → Settings → Help Search**
   - Single-site: **Settings → Help Search**
4. Enter your search results URL (e.g., `https://help.example.com/search?q=`).  
   The URL **must** start with `http://` or `https://`.
5. Click **Save Changes**.

The "Help for…" search box will appear in the admin menu for all administrators.

## Screenshots

1. The search box in the admin menu  
   ![Search box in admin menu](assets/screenshot-1.png)

2. Settings page in Network Admin  
   ![Settings page](assets/screenshot-2.png)

3. Statistics page with recorded searches  
   ![Statistics page](assets/screenshot-3.png)

## Frequently Asked Questions

### Does it work on single-site installations?
Yes — it works perfectly on both single-site and multisite setups.

### Where are the searches stored?
In a network-wide table named `{prefix}lsah_admin_searches` (using your database prefix). The `blog_id` column distinguishes searches per site.

### Can I view search statistics?
Yes! Visit **Network Admin → Settings → Help Search Statistics** (or **Settings → Help Search Statistics** on single-site) for a full searchable and sortable overview.

### Is the plugin secure?
I think so. It includes:
- Strict URL validation (client-side JavaScript + server-side PHP)
- Safe form handling via enqueued JS
- Nonce verification for AJAX logging
- Full input sanitization and output escaping

## Changelog

### 1.0.0
- Initial public release
- Configurable search URL with full validation
- Network-wide search logging with site support
- Secure implementation using enqueued assets
- Built-in statistics page using `WP_List_Table`
- Translation-ready strings
- Comprehensive PHPDoc documentation

## License

This plugin is licensed under the **GPLv2 or later**.  
https://www.gnu.org/licenses/gpl-2.0.html

---

Made with ❤️ by [lenasterg](https://github.com/lenasterg)
