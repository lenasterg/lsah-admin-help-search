=== LSAH Admin Help Search (Multisite) ===
Contributors: lenasterg
Donate link: https://example.com/donate
Tags: admin, search, help, multisite, network, logging, statistics, dashboard, documentation
Requires at least: 5.5
Tested up to: 6.9
Stable tag: 1.0.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Adds a quick help search box directly in the admin menu, with configurable search URL and search term logging. Fully multisite compatible.

== Description ==

**LSAH Admin Help Search** adds a convenient search field right in the WordPress admin menu (position 1, immediately after the Dashboard), allowing administrators to quickly search your custom help documentation or knowledge base.

### Key Features:
- Works on **all sites** in a multisite network.
- Configurable search action URL set by the Network Administrator (or site admin on single-site installs).
- Logs all searches in a network-wide database table (including blog_id, search term, count, first/last searched dates, and full search URL).
- **New in v1.0.0**: Dedicated statistics page under Network Admin → Settings → Help Search Statistics (with term search, sorting, and site URL display on multisite).
- Fully secure: strict URL validation (client-side + server-side), safe form action via JavaScript, nonce-protected AJAX logging.
- Translation-ready (text domain: lsah-admin-help-search).
- Clean, standards-compliant code with separate enqueued CSS/JS assets.

Perfect for multisite networks with a centralized help system (e.g., internal knowledge base, custom manual, external search engines like Google Custom Search, Algolia, etc.).

== Installation ==

1. Upload the `lsah-admin-help-search` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in Network Admin (multisite) or the regular Plugins page (single-site).
3. Go to **Network Admin → Settings → Help Search** (or **Settings → Help Search** on single-site).
4. Enter the full URL of your search results page (e.g., `https://help.example.com/search?q=`).
   - The URL **must** start with `http://` or `https://`.
5. Click "Save Changes".

The "Help for…" search box will now appear in the admin menu for all administrators.

== Frequently Asked Questions ==

= Does it work on single-site installations? =

Yes, it works perfectly on both single-site and multisite installations.

= Where are the searches stored? =

Searches are stored in a network-wide table named `{prefix}lsah_admin_searches` (using your database prefix). The table includes `blog_id` to distinguish searches per site.

= Can I view search statistics? =

Yes! Go to **Network Admin → Settings → Help Search Statistics** (or **Settings → Help Search Statistics** on single-site). The page includes a searchable and sortable table of all recorded searches.

= Is it secure? =

Yes. The plugin includes:
- Strict URL input validation (both client-side JavaScript and server-side PHP)
- Safe form action assignment via enqueued JavaScript
- Nonce verification for AJAX logging
- Full sanitization and escaping of all inputs and outputs

== Screenshots ==

1. The search box in the admin menu.
2. The Help Search settings page in Network Admin.
3. The Help Search Statistics page showing recorded searches.

== Changelog ==

= 1.0.0 =
* Initial public release
* Configurable search URL with full client + server validation
* Network-wide search logging with blog_id support
* Secure form implementation using enqueued assets
* Built-in statistics page with WP_List_Table
* Translation-ready strings
* Full PHPDoc documentation

== Upgrade Notice ==

= 1.0.0 =
Initial release. No upgrade path needed from previous versions.