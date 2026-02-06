=== LSAH Admin Help Search ===
Contributors: lenasterg
Tags: admin, help, multisite, dashboard, documentation
Requires at least: 5.5
Tested up to: 6.9
Stable tag: 1.2.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Adds a search field in the admin menu  allowing administrators to quickly search your custom help documentation or knowledge base.

== Description ==

**LSAH Admin Help Search** adds a convenient search field right in the WordPress admin menu (position 1, immediately after the Dashboard), allowing administrators to quickly search your custom help documentation or knowledge base.

**Key Features:**
* **Embedded Dashboard Search:** Adds a search box directly to the admin sidebar for seamless access to help articles.
* **User can hide the help search field, via the Screen Options.
* **Integrated Manual Tracking:** Since version 1.1. Now captures search queries performed directly on the frontend of the site designated as the User Manual (when hosted within the same multisite network).
* **Smart De-duplication:** Features an intelligent mechanism to prevent double entries caused by redirects from the dashboard or page refreshes, using a 5-second "look-back" window.
* **Data Integrity & Security:** High-level sanitization of search terms and use of prepared SQL statements to ensure database security.
* **Performance Optimized:** Uses targeted hooks (`template_redirect`) and "fail-early" logic (length checks) to ensure zero impact on site speed across large networks.
* **Multibyte Support:** Correctly handles Greek and other non-Latin characters for accurate search term logging.
* **Translation-ready (text domain: lsah-admin-help-search).


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

Yes if you are the website superadmin (or in a single-site installation the administrator). Go to **Network Admin → Settings → Help Search Statistics** (or **Settings → Help Search Statistics** on single-site). The page includes a searchable and sortable table of all recorded searches.

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
= 1.2.0 =
* Updated plugin name and version, improved permission checks
* Added functionality for subsite member to hide the help search field, via the Screen Options.
* Enhanced user feedback messages and AJAX handling for user preferences.

= 1.1.0 =
* NEW: Added tracking for searches performed directly on the manual's frontend (if in same multisite).
* IMPROVEMENT: Implemented a smart de-duplication mechanism to filter out redundant logs from redirects.
* IMPROVEMENT: Added a 3-character minimum length requirement for logging (supporting multibyte/Greek characters).
* IMPROVEMENT: Refactored code into a core logging function for better maintainability and consistency.

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
