<?php
/**
 * Plugin Name: LSAH Admin Help Search
 * Description: Adds a search field within the WordPress dashboard for instant access to user manuals and logs queries to help administrators improve documentation. Fully multisite compatible.
 * Version: 1.2.1
 * Author: lenasterg
 * Author URI:        https://lenasterg.wordpress.com
 * Text Domain: lsah-admin-help-search
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Domain Path: /languages
 * Network: true
 *
 * @package LSAH_Admin_Help_Search
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * ------------------------------------------------------------------------
 * Constants
 * ------------------------------------------------------------------------
 */
define('LSAH_OPTION_ACTION_URL', 'lsah_help_search_action_url');
define('LSAH_TABLE_SEARCHES', 'lsah_admin_searches');
define('LSAH_OPTION_SHOW_NOTICE', 'lsah_notice_set_help_url');

/**
 * ------------------------------------------------------------------------
 * Plugin activation
 * ------------------------------------------------------------------------
 */

/**
 * Creates the searches table on plugin activation and sets a notice flag for the superadmin.
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 * @return void
 */
function lsah_activate_plugin() {
    global $wpdb;

    $table_name      = $wpdb->base_prefix . LSAH_TABLE_SEARCHES;
    $charset_collate = $wpdb->get_charset_collate();

// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
   $sql = "CREATE TABLE IF NOT EXISTS $table_name (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    blog_id BIGINT UNSIGNED NOT NULL,
    search_term VARCHAR(255) NOT NULL,
    search_url TEXT NOT NULL,
    search_count INT UNSIGNED NOT NULL DEFAULT 1,
    first_searched DATETIME NOT NULL,
    last_searched DATETIME NOT NULL,
    PRIMARY KEY  (id),
    UNIQUE KEY blog_search (blog_id, search_term),
    INDEX idx_blog_id (blog_id),
    INDEX idx_search_term (search_term)
) $charset_collate;";
// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared
// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);

    // Flag to show notice to superadmin.
    if (is_multisite()) {
        add_site_option(LSAH_OPTION_SHOW_NOTICE, 1);
    } else {
        add_option(LSAH_OPTION_SHOW_NOTICE, 1);
    }
}
register_activation_hook(__FILE__, 'lsah_activate_plugin');

/**
 * Add a "Settings" link to the plugin action links.
 *
 * @param array $links Existing action links.
 * @return array Modified action links.
 * 
 * @version 1.0
 * @since 1.2.1
 */
function lsah_add_plugin_action_links($links) {
	if (is_multisite() && !is_network_admin()) {
        return $links;
    }
    // Determine the correct settings URL based on Multisite status
  if (is_multisite()) {
        $settings_url   = network_admin_url('settings.php?page=lsah-help-search-settings');
        $statistics_url = network_admin_url('settings.php?page=lsah-help-search-statistics');
    } else {
        $settings_url   = admin_url('options-general.php?page=lsah-help-search-settings');
        $statistics_url = admin_url('options-general.php?page=lsah-help-search-statistics');
    }

    $action_links = array(
        'settings'   => '<a href="' . esc_url($settings_url) . '">' . esc_html__('Settings', 'lsah-admin-help-search') . '</a>',
        'statistics' => '<a href="' . esc_url($statistics_url) . '">' . esc_html__('Statistics', 'lsah-admin-help-search') . '</a>',
    );

    // Add the link to the beginning of the array
    return array_merge($action_links, $links);
    
    
    
    return $links;
}

// For single site 
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'lsah_add_plugin_action_links');

// Network Activated in multisite
add_filter('network_admin_plugin_action_links_' . plugin_basename(__FILE__), 'lsah_add_plugin_action_links');




/**
 * ------------------------------------------------------------------------
 * Admin notice for superadmin to set help URL
 * ------------------------------------------------------------------------
 */

/**
 * Displays an admin notice if the action URL is not configured.
 *
 * Shows in network admin for multisite, or regular admin for single-site.
 *
 * @since 1.0
 * @return void
 */
function lsah_show_notice_set_url() {
    if (is_multisite() && !is_super_admin()) {
        return;
    }

    if (!is_multisite() && !current_user_can('manage_options')) {
        return;
    }

    $action_url = get_site_option(LSAH_OPTION_ACTION_URL);

    if ($action_url) {
        return;
    }

    $settings_url = is_multisite()
        ? network_admin_url('settings.php?page=lsah-help-search-settings')
        : admin_url('options-general.php?page=lsah-help-search-settings');

    ?>
   <div class="notice notice-warning is-dismissible">
    <p>
        <?php
        /* translators: %s: Settings page URL */
        $settings_text = __('Settings → Help Search', 'lsah-admin-help-search');
        printf(
            /* translators: %1$s: message text, %2$s: link */
            esc_html__('LSAH: Please configure the Help Search Action URL in %1$s.', 'lsah-admin-help-search'),
            '<a href="' . esc_url($settings_url) . '">' . esc_html($settings_text) . '</a>'
        );
        ?>
    </p>
</div>
    <?php
}

if (is_multisite()) {
    add_action('network_admin_notices', 'lsah_show_notice_set_url');
} else {
    add_action('admin_notices', 'lsah_show_notice_set_url');
}


/**
 * ------------------------------------------------------------------------
 * Admin menu and search form
 * ------------------------------------------------------------------------
 */
 
/**
 * Adds a top-level admin menu item that will contain the search form.
 *
 * Only adds the menu if the action URL is configured.
 *
 * @since 1.0
 *
 * @version 2.0, don't run in network admin pages
 * @return void
 */
function lsah_add_admin_menu() {
	if ( is_network_admin() ) {
         wp_die();
    }
    $action_url = get_site_option(LSAH_OPTION_ACTION_URL);

    // Μην προσθέτουμε το menu αν δεν έχει ρυθμιστεί URL
    if (!$action_url) {
        return;
    }

    add_menu_page(
       esc_html__('Help Search', 'lsah-admin-help-search'),
       esc_html__('Help for...', 'lsah-admin-help-search'),
       'read',
       'lsah-admin-help-search',
       '__return_null',
       'dashicons-search',
      1
	);
}
add_action('admin_menu', 'lsah_add_admin_menu');

/**
 * Enqueues CSS and JavaScript for the admin search form.
 *
 * Only loads if the action URL has been configured.
 *
 * @return void
 */
function lsah_admin_assets() {
    $action_url = get_site_option(LSAH_OPTION_ACTION_URL);

    if (!$action_url) {
        return;
    }

    // Enqueue CSS
    wp_enqueue_style(
        'lsah-admin-search',
        plugin_dir_url(__FILE__) . 'assets/css/admin-search.css',
        array(),
        '1.0.0'
    );

    // Localize data for JavaScript
  $lsah_data = array(
    'actionUrl'     => esc_url($action_url),
    'nonce'         => wp_create_nonce('lsah_log_admin_help_search'),
    'placeholder'   => esc_js(__('Help for...', 'lsah-admin-help-search')),
    'ariaLabel'     => esc_js(__('Search the help manual', 'lsah-admin-help-search')),
    'notConfigured' => esc_js(__('Help Search URL not configured.', 'lsah-admin-help-search')),
);

    // Enqueue JS
    wp_enqueue_script(
        'lsah-admin-search',
        plugin_dir_url(__FILE__) . 'assets/js/admin-search.js',
        array(), // dependencies - π.χ. array('jquery') αν χρειαστεί
        '1.0.0',
        true // in footer
    );

    wp_localize_script('lsah-admin-search', 'lsahData', $lsah_data);
}
add_action('admin_enqueue_scripts', 'lsah_admin_assets');

/**
 * ------------------------------------------------------------------------
 * AJAX logging for searches
 * ------------------------------------------------------------------------
 */

/**
 * Handles AJAX request to log an admin help search term.
 *
 ** Intercepts and logs search requests originating from the WordPress Admin sidebar.
 * This function monitors the admin-side search form. When a user submits a 
 * "Help for..." query, it sanitizes the input, records the search via  lsah_save_search_query().
 * 
 * @since 1.0.0
 * @version 2.0, Don't run in network admin pages
 * 1.1.0 Refactored to use the central lsah_save_search_query() function.
 * @return void
 * 
 * @global wpdb $wpdb
 */
function lsah_log_admin_help_search() {
    if (!is_user_logged_in()) {
        wp_die();
    }
	
    check_ajax_referer('lsah_log_admin_help_search', 'security', true);

    $blog_id    = get_current_blog_id();
    $search     = sanitize_text_field(wp_unslash($_POST['search']) ?? '');
    
    if (!$search) {
        wp_die();
    }
        // Καλούμε την κεντρική συνάρτηση αντί να γράφουμε SQL εδώ
    lsah_save_search_query( $search, $blog_id );

    wp_die();
}
add_action('wp_ajax_lsah_log_admin_help_search', 'lsah_log_admin_help_search');



/**
 * Core function to save or update search queries in the custom database table.
 *
 * This function handles the logging logic, including de-duplication checks,
 * minimum length validation, and updating counts for existing terms.
 *
 * @since 1.1.0
 *
 * @param string $search  The search term to be logged.
 * @param int    $blog_id The ID of the blog where the search originated.
 * @return void
 */
function lsah_save_search_query( $search, $blog_id ) {
    global $wpdb;

   // Minimum length validation (multibyte supported).
   if ( mb_strlen( $search ) < 4 ) { 
       return;
   }
    
    /** * Use base_prefix to ensure the table is treated as a network-wide global table 
     * in multisite environments.
     */
     $table_name = $wpdb->base_prefix . LSAH_TABLE_SEARCHES;
     $now        = current_time( 'mysql' );
     
    /**DE-DUPLICATION
     * Check if the same term was logged very recently (within 5 seconds).
     * This prevents duplicate logs from redirects or rapid page refreshes.
     */
    $is_duplicate = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT id FROM $table_name 
             WHERE search_term = %s 
             AND last_searched > DATE_SUB(%s, INTERVAL 5 SECOND)
             ORDER BY id DESC LIMIT 1",
            $search,
            $now
        )
    );
    if ( $is_duplicate ) {
        return;
    }
    $search_url = get_site_option( LSAH_OPTION_ACTION_URL ) . urlencode( $search );
      
    // Check if the term already exists for this specific blog.
    $row = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT id, search_count FROM $table_name WHERE blog_id = %d AND search_term = %s",
            $blog_id,
            $search
        )
    );

    if ( $row ) {
        // Update existing record: increment counter and update timestamp/URL.
        $wpdb->update(
            $table_name,
            [
                'search_count'  => $row->search_count + 1,
                'last_searched' => $now,
                'search_url'    => $search_url,
            ],
            ['id' => $row->id],
            ['%d', '%s', '%s'],
            ['%d']
        );
    } else {
        // Αν δεν υπάρχει, δημιουργούμε νέα εγγραφή
        $wpdb->insert(
            $table_name,
            [
                'blog_id'        => $blog_id,
                'search_term'    => $search,
                'search_url'     => $search_url,
                'search_count'   => 1,
                'first_searched' => $now,
                'last_searched'  => $now,
            ],
            ['%d', '%s', '%s', '%d', '%s', '%s']
        );
    }
}


/**
 * ------------------------------------------------------------------------
 * Network Admin – Settings
 * ------------------------------------------------------------------------
 */


/**
 * Adds the Help Search settings page to the network admin menu.
 *
 * @return void
 */
function lsah_add_network_settings() {
    add_submenu_page(
        'settings.php',
        esc_html__('Help Search Settings', 'lsah-admin-help-search'),
        esc_html__('Help Search', 'lsah-admin-help-search'),
        'manage_network',
        'lsah-help-search-settings',
        'lsah_render_settings_page'
    );
}
add_action('network_admin_menu', 'lsah_add_network_settings');


/**
 * Adds the Help Search settings page to the regular admin menu on single-site installations.
 *
 * @since 1.0
 * @return void
 */
function lsah_add_single_site_settings() {
    if (is_multisite()) {
        return;
    }

    add_options_page(
        esc_html__('Help Search Settings', 'lsah-admin-help-search'),
        esc_html__('Help Search', 'lsah-admin-help-search'),
        'manage_options',
        'lsah-help-search-settings',
        'lsah_render_settings_page' // Χρησιμοποιούμε την ίδια συνάρτηση rendering
    );
}
add_action('admin_menu', 'lsah_add_single_site_settings');

/**
 * Renders the settings page for configuring the Help Search action URL.
 *
 * Works in both multisite (network admin) and single-site installations.
 *
 * @since 1.0
 * @return void
 */
function lsah_render_settings_page() {
    // Έλεγχος δικαιωμάτων ανάλογα με την εγκατάσταση
    if (is_multisite() && !current_user_can('manage_network')) {
		wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'lsah-admin-help-search' ) );
    }

    if (!is_multisite() && !current_user_can('manage_options')) {
        wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'lsah-admin-help-search' ) );
    }
    $messages_displayed = false;

    if (isset($_POST['lsah_save_settings']) && check_admin_referer('lsah_save_settings')) {
        $raw_url = trim( sanitize_text_field(wp_unslash( $_POST['lsah_action_url'] ?? '' ) ) ) ;

        if (empty($raw_url)) {
            add_settings_error('lsah_settings', 'url_empty',  esc_html__('Please enter a URL.', 'lsah-admin-help-search'), 'error');
            $url = '';
        } else {
            if (!preg_match('#^https?://#i', $raw_url)) {
                add_settings_error('lsah_settings', 'url_scheme',  esc_html__('The URL must start with http:// or https://.', 'lsah-admin-help-search'), 'error');
                $url = '';
            } elseif (filter_var($raw_url, FILTER_VALIDATE_URL) === false) {
                add_settings_error('lsah_settings', 'url_invalid',  esc_html__('Please enter a valid URL.', 'lsah-admin-help-search'), 'error');
                $url = '';
            } else {
                $url = esc_url_raw($raw_url);
                add_settings_error('lsah_settings', 'settings_updated',  esc_html__('Settings saved.', 'lsah-admin-help-search'), 'updated');
            }
        }

        update_site_option(LSAH_OPTION_ACTION_URL, $url);
        update_site_option(LSAH_OPTION_SHOW_NOTICE, 0);
        $messages_displayed = true;
    }

    $action_url = get_site_option(LSAH_OPTION_ACTION_URL, '');

    if ($messages_displayed) {
        settings_errors('lsah_settings');
    }
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Help Search Settings', 'lsah-admin-help-search'); ?></h1>
        <form method="post">
            <?php wp_nonce_field('lsah_save_settings'); ?>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="lsah_action_url"><?php esc_html_e('Search Action URL', 'lsah-admin-help-search'); ?></label></th>
                    <td>
                      <input type="url" name="lsah_action_url"  id="lsah_action_url"  value="<?php echo esc_attr($action_url); ?>"   
			     class="regular-text"   placeholder="https://help.example.com/search?q="  required>
                    <p class="description">
			<strong><?php esc_html_e('Examples:', 'lsah-admin-help-search'); ?></strong><br>
			<code>https://help.example.com/search?s=</code><br>
			<code>https://help.example.com/search?q=</code><br>
			<code>https://docs.example.com/search/</code>
		    </p>
                    </td>
                </tr>
            </table>
            <?php submit_button( esc_html__('Save Changes', 'lsah-admin-help-search'), 'primary', 'lsah_save_settings'); ?>
        </form>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.querySelector('form');
        const urlInput = document.getElementById('lsah_action_url');
        const submitButton = form.querySelector('input[type="submit"]');

        if (!urlInput || !form) return;

        let errorMessage = null;

        function validateURL() {
            if (errorMessage) {
                errorMessage.remove();
                errorMessage = null;
            }

            const value = urlInput.value.trim();

            if (value === '') {
                showError('<?php echo esc_js(__('Please enter a URL.', 'lsah-admin-help-search')); ?>');
                return false;
            }

            if (!/^https?:\/\//i.test(value)) {
                showError('<?php echo esc_js(__('The URL must start with http:// or https://.', 'lsah-admin-help-search')); ?>');
                return false;
            }

            try {
                new URL(value);
            } catch (e) {
                showError('<?php echo esc_js(__('Please enter a valid URL.', 'lsah-admin-help-search')); ?>');
                return false;
            }

            return true;
        }

        function showError(message) {
            errorMessage = document.createElement('div');
            errorMessage.className = 'notice notice-error inline';
            errorMessage.style.marginTop = '10px';
            errorMessage.innerHTML = '<p>' + message + '</p>';
            urlInput.parentNode.appendChild(errorMessage);
            submitButton.disabled = true;
        }

        function removeError() {
            if (errorMessage) {
                errorMessage.remove();
                errorMessage = null;
            }
            submitButton.disabled = false;
        }

        urlInput.addEventListener('input', function () {
            if (errorMessage && validateURL()) {
                removeError();
            }
        });

        form.addEventListener('submit', function (e) {
            if (!validateURL()) {
                e.preventDefault();
                urlInput.focus();
            }
        });
    });
    </script>
    <?php
}

/**
 * ------------------------------------------------------------------------
 * Allow blog user to hide the help search field in the specific blog via the Screen options
 * ------------------------------------------------------------------------
**/
/**
 * Adds a checkbox to the "Screen Options" pull-down menu on all admin pages.
 *
 * @since 1.2.0
 * @param string $status The current screen settings HTML.
 * @param object $args   Screen settings arguments.
 * @return string        Modified screen settings HTML.
 * 
 * @version 2.0, don't display it to network admin pages
 */
function lsah_add_screen_options_global($status, $args) {
    if ( is_network_admin() ) {
        return $status;
    }
    // Generate a unique meta key for the current subsite to allow site-specific preferences
    $meta_key = 'lsah_hide_search_site_' . get_current_blog_id();
    $hide_search = get_user_meta(get_current_user_id(), $meta_key, true);
    
    $output = '<fieldset class="metabox-prefs" style="padding: 10px 0;">';
    $output .= '<legend><strong>' . esc_html__('LSAH Search Settings', 'lsah-admin-help-search') . '</strong></legend>';
    $output .= '<label for="lsah_hide_search_check">';
    $output .= '<input type="checkbox" id="lsah_hide_search_check" name="lsah_hide_search_check" value="1" ' . checked($hide_search, 1, false) . ' />';
    $output .= ' ' . esc_html__('Hide help search field on this site', 'lsah-admin-help-search');
    $output .= '</label></fieldset>';

    return $status . $output;
}
add_filter('screen_settings', 'lsah_add_screen_options_global', 10, 2);


/**
 * Handles the AJAX request to save the user's search field visibility preference.
 * If the user unchecks the box, the meta key is deleted to keep the database clean.
 *
 * @since 1.2.0
 * 
 * @version 2.0, don't run it to network admin pages 
 * @return void
 */
function lsah_save_screen_option_callback() {
	
	if ( is_network_admin() ) {
        return;
    }
    // Verify the security nonce to prevent CSRF attacks
    check_ajax_referer('lsah_security_nonce', 'security');

    // Check if the current user has permission to be in the admin area
    if ( ! current_user_can('read') ) {
        wp_send_json_error('Forbidden', 403);
    }

    $user_id = get_current_user_id();
    $meta_key = 'lsah_hide_search_site_' . get_current_blog_id();
    
    // Check if the user wants to hide the field
    $hide_requested = ( isset($_POST['hide']) && $_POST['hide'] === 'true' );
    
    if ( $hide_requested ) {
        // Save the preference
        update_user_meta($user_id, $meta_key, 1);
        wp_send_json_success('Preference saved');
    } else {
        // If unchecking, delete the meta key entirely to save DB space
        delete_user_meta($user_id, $meta_key);
        wp_send_json_success('Preference deleted');
    }
}
add_action('wp_ajax_lsah_save_screen_option', 'lsah_save_screen_option_callback');

/**
 * Injects CSS to hide the search field immediately if preference is set.
 * Also injects the jQuery logic to handle real-time toggling and AJAX saving.
 *
 * @since 1.2.0
 *
 * @version 2.0, don't run it to network admin pages 
 * @return void
 */
function lsah_apply_visibility_global() {
	if ( is_network_admin() ) {
        return;
    }
    $meta_key = 'lsah_hide_search_site_' . get_current_blog_id();
    $hide_search = get_user_meta(get_current_user_id(), $meta_key, true);
    
    // The specific ID for the help search field container
    $target_id = '#toplevel_page_lsah-admin-help-search';
    
    // 1. CSS: If the preference is set to hide, inject CSS immediately to prevent flickering
    if ( $hide_search == 1 ) {
        echo '<style id="lsah-toggle-style">' . esc_attr($target_id) . ' { display: none !important; }</style>';
    }

    // 2. JS: Logic for the Screen Options checkbox interaction
    ?>
    <script type="text/javascript">
        jQuery(document).ready(function($) {
            var targetId = '<?php echo esc_js($target_id); ?>';

            /**
             * Using event delegation on 'change' because Screen Options content 
             * can sometimes be re-rendered by WordPress or other plugins.
             */
            $(document).on('change', '#lsah_hide_search_check', function() {
                var isChecked = $(this).is(':checked');
                
                // Real-time UI Toggle: add or remove the style element
                if(isChecked) {
                    if (!$('#lsah-toggle-style').length) {
                        $('head').append('<style id="lsah-toggle-style">' + targetId + ' { display: none !important; }</style>');
                    }
                } else {
                    $('#lsah-toggle-style').remove();
                }

                // Fire the AJAX request to save preference in user meta
                $.post(ajaxurl, {
                    action: 'lsah_save_screen_option',
                    hide: isChecked,
                    security: '<?php echo esc_js(wp_create_nonce("lsah_security_nonce")); ?>'
                });
            });
        });
    </script>
    <?php
}
add_action('admin_head', 'lsah_apply_visibility_global');

/**
 * ------------------------------------------------------------------------
 * Log search queries performed directly on the manual's frontend.
 * ------------------------------------------------------------------------
 */

/**
 * Tracks and logs search queries performed directly on the manual's frontend.
 * This function hooks into 'template_redirect' to intercept frontend searches.
 * It validates if the current site matches the designated Manual URL and ensures
 * the search term meets the required criteria before logging it to the database.
 * 
 * @since 1.1.0
 * @return void
 */
function lsah_track_manual_searches() {
    // 1. Primary check: Exit early if this is not a search results page.
    if ( ! is_search() ) {
        return;
    }
    $search_query = sanitize_text_field(wp_unslash(get_search_query()) ?? '');
    
    // Fail early if the search term is too short.
    if ( mb_strlen( $search_query ) < 4 ) { 
	return;
    }

    // 2. Ensure the option constant is defined before proceeding.
    if ( ! defined( 'LSAH_OPTION_ACTION_URL' ) ) {
        return;
    }
	
    // 3. Retrieve the saved Manual URL from site options.
    $raw_action_url = get_site_option(LSAH_OPTION_ACTION_URL);
    if (! $raw_action_url){
	return;
    }
    /**
     * Normalize URLs for comparison.
     * We strip query strings and ensure trailing slashes for consistency.
     */
    $manual_url = trailingslashit( strtok( $raw_action_url, '?' ) );
    $current_home_url = trailingslashit( home_url() );

    // 4. Verify if the current site is indeed the designated Manual site.
    if ( $current_home_url !== $manual_url ) {
        return;
    }

// 5. If all checks pass, record the search query.
    lsah_save_search_query( $search_query, get_current_blog_id() );

}
add_action( 'template_redirect', 'lsah_track_manual_searches' );


/**
 * ------------------------------------------------------------------------
 * Statistics page
 * ------------------------------------------------------------------------
 */
 
/**
 * Registers a submenu page for Help Search Statistics in the Network Admin dashboard.
 *
 * This function adds a statistics page specifically for the Multisite Network 
 * administration area, located under the "Settings" menu. It allows Super Admins 
 * to view aggregated search data across the entire network.
 *
 * @since 1.0.0
 * @see add_submenu_page()
 * @return void
 */
function lsah_add_network_statistics_menu() {
    add_submenu_page(
        'settings.php',
         esc_html__('Help Search Statistics', 'lsah-admin-help-search'),
         esc_html__('Help Search Statistics', 'lsah-admin-help-search'),
        'manage_network',
        'lsah-help-search-statistics',
        'lsah_render_statistics_page'
    );
}
add_action('network_admin_menu', 'lsah_add_network_statistics_menu');

/**
 * For single site installation: Registers a submenu page for Help Search Statistics under the Settings menu.
 *
 * This menu item is only registered for single-site installations. 
 * If the site is part of a Multisite network, the function returns early 
 * to prevent the menu from appearing.
 *
 * @since 1.0.0
 * @see add_submenu_page()
 *
 * @return void
 */
function lsah_add_single_site_statistics_menu() {
    if (is_multisite()) {
		return;
	}
    add_submenu_page(
        'options-general.php',
         esc_html__('Help Search Statistics', 'lsah-admin-help-search'),
         esc_html__('Help Search Statistics', 'lsah-admin-help-search'),
        'manage_options',
        'lsah-help-search-statistics',
        'lsah_render_statistics_page'
    );
}
add_action('admin_menu', 'lsah_add_single_site_statistics_menu');

/**
 * Renders the Help Search Statistics page content.
 *
 * This function handles the security checks for both single-site and multisite
 * environments, ensures the WP_List_Table class is loaded, and initializes 
 * the custom statistics table to display search data.
 *
 * @since 1.0.0
 * @see LSAH_Search_Statistics_Table
 * @see WP_List_Table
 * @return void
 */
function lsah_render_statistics_page() {
    if (is_multisite() && !current_user_can('manage_network')) {
        wp_die( esc_html__('You do not have sufficient permissions to access this page.', 'lsah-admin-help-search'));
    }
    if (!is_multisite() && !current_user_can('manage_options')) {
        wp_die( esc_html__('You do not have sufficient permissions to access this page.', 'lsah-admin-help-search'));
    }

    if (!class_exists('WP_List_Table')) {
        require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
    }

    $statistics_table = new LSAH_Search_Statistics_Table();
    $statistics_table->prepare_items();
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Help Search Statistics', 'lsah-admin-help-search'); ?></h1>
        <p><?php esc_html_e('Overview of all recorded help search terms.', 'lsah-admin-help-search'); ?></p>
        <form method="get">
            <input type="hidden" name="page" value="lsah-help-search-statistics">
            <?php
            $statistics_table->search_box( esc_html__('Search terms', 'lsah-admin-help-search'), 'search_term');
            $statistics_table->display();
            ?>
        </form>
    </div>
    <?php
}



/***/
if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}
if (!class_exists('LSAH_Search_Statistics_Table')) {

/**
 * Custom List Table class to display Help Search Statistics.
 *
 * This class extends the native WordPress WP_List_Table to provide a 
 * standard admin interface for viewing, searching, and managing 
 * help search data recorded by the plugin.
 *
 * It shows Search Term, Count, First & Last Searched.
 * On multisite, also shows the Site URL.
 *
 * @since 1.0.0
 * @package LSAH_Admin_Help_Search
 * @see WP_List_Table
 */
    class LSAH_Search_Statistics_Table extends WP_List_Table {

        /**
         * Constructor
         */
        public function __construct() {
            parent::__construct([
                'singular' =>  esc_html__('Search Term', 'lsah-admin-help-search'),
                'plural'   =>  esc_html__('Search Terms', 'lsah-admin-help-search'),
                'ajax'     => false,
            ]);
        }

        /**
         * Define the table columns
         *
         * @return array Column IDs and titles
         */
        public function get_columns() {
            $columns = [
                'search_term'    =>  esc_html__('Search Term', 'lsah-admin-help-search'),
                'search_count'   =>  esc_html__('Count', 'lsah-admin-help-search'),
                'first_searched' =>  esc_html__('First Searched', 'lsah-admin-help-search'),
                'last_searched'  =>  esc_html__('Last Searched', 'lsah-admin-help-search'),
            ];

            // Σε multisite, προσθέτουμε στήλη για Site URL
            if (is_multisite()) {
				$columns = array_slice( $columns, 0, 1 ) + ['blog_url' =>  esc_html__('Site URL', 'lsah-admin-help-search')]+ $columns;
            }

            return $columns;
        }

        /**
         * Define sortable columns
         *
         * @return array Sortable columns
         */
        public function get_sortable_columns() {
            $columns = [
                'search_term'    => ['search_term', true],
                'search_count'   => ['search_count', false],
                'first_searched' => ['first_searched', false],
                'last_searched'  => ['last_searched', false],
            ];

            // blog_url is a derived column – sorted at PHP level
            if (is_multisite()) {
                $columns['blog_url'] = ['blog_url', false];
            }

            return $columns;
        }

        /**
         * Prepare items for display
         *
         * Handles search, sorting and pagination.
         *
         * @since 1.0.0
         * @return void
         */
        public function prepare_items() {
            global $wpdb;

            // Extra hardening: capability check
            if ( ! current_user_can( is_multisite() ? 'manage_network' : 'manage_options' ) ) {
                return;
            }

            $table = $wpdb->base_prefix . LSAH_TABLE_SEARCHES;

            // Search input
            $search = isset($_REQUEST['s'])
                ? sanitize_text_field(wp_unslash($_REQUEST['s']))
                : '';

            // Pagination
            $per_page     = 10;
            $current_page = max(1, (int) $this->get_pagenum());
            $offset       = ($current_page - 1) * $per_page;

            // Sorting
            $orderby = isset( $_REQUEST['orderby'] ) ? sanitize_sql_orderby( wp_unslash( $_REQUEST['orderby'] ) ) : 'last_searched';
			
            $order = ( isset( $_REQUEST['order'] ) && 'asc' === strtolower( wp_unslash( $_REQUEST['order'] ) ) )  ? 'ASC' : 'DESC';

            // Columns that physically exist in the database
            $db_sortable = [
                'search_term',
                'search_count',
                'first_searched',
                'last_searched',
            ];

            // Flag for PHP-level sorting of derived column
            $php_sort_blog_url = (is_multisite() && 'blog_url' === $orderby);

            // WHERE clause
            if (!empty($search)) {
                $like = '%' . $wpdb->esc_like($search) . '%';
                $where_sql = $wpdb->prepare('WHERE search_term LIKE %s', $like);
            } else {
                $where_sql = '';
            }

            // ORDER BY clause (SQL only if column exists)
            if (in_array($orderby, $db_sortable, true)) {
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Orderby and order are whitelisted.
                $order_by_sql = "ORDER BY {$orderby} {$order}";
            } else {
                $order_by_sql = 'ORDER BY last_searched DESC';
            }

            // Main query
            $query = $wpdb->prepare(
                "SELECT * FROM {$table}
                 {$where_sql}
                 {$order_by_sql}
                 LIMIT %d OFFSET %d",
                $per_page,
                $offset
            );
// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- SQL query prepared earlier; dynamic parts are sanitized or strictly whitelisted.
            $this->items = $wpdb->get_results($query, ARRAY_A);

            /*
             * PHP-level sorting for blog_url (derived column).
             * Applied only to the current page for performance reasons.
             */
            if ($php_sort_blog_url && !empty($this->items)) {
                static $blog_urls = [];

                foreach ($this->items as &$item) {
                    if (!isset($blog_urls[$item['blog_id']])) {
                        $blog_urls[$item['blog_id']] = get_site_url($item['blog_id']);
                    }
                    $item['_blog_url_sort'] = $blog_urls[$item['blog_id']];
                }
                unset($item);

                usort(
                    $this->items,
                    function ($a, $b) use ($order) {
                        return ('ASC' === $order)
                            ? strcasecmp($a['_blog_url_sort'], $b['_blog_url_sort'])
                            : strcasecmp($b['_blog_url_sort'], $a['_blog_url_sort']);
                    }
                );

                // Cleanup temporary field
                foreach ($this->items as &$item) {
                    unset($item['_blog_url_sort']);
                }
                unset($item);
            }

            // Total items for pagination
            if (!empty($search)) {
				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- No user input, table name from constant.
                $total_items = (int) $wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT COUNT(*) FROM {$table} WHERE search_term LIKE %s",
                        $like
                    )
                );
            } else {
                $total_items = (int) $wpdb->get_var(
                    "SELECT COUNT(*) FROM {$table}"
                );
            }

            $this->set_pagination_args([
                'total_items' => $total_items,
                'per_page'    => $per_page,
            ]);

            $this->_column_headers = [
                $this->get_columns(),
                [],
                $this->get_sortable_columns(),
            ];
        }

        /**
         * Default column rendering
         *
         * @param array  $item        Row data
         * @param string $column_name Column name
         * @return string Rendered column content
         */
        public function column_default($item, $column_name) {
            global $wpdb;

            switch ($column_name) {
                case 'search_term':
                    if (!empty($item['search_url'])) {
                        return sprintf(
                            '<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
                            esc_url($item['search_url']),
                            esc_html($item['search_term'])
                        );
                    }
                    return esc_html($item['search_term']);

                case 'search_count':
                    return esc_html($item[$column_name]);

                case 'first_searched':
                case 'last_searched':
                    return esc_html(date_i18n(
                        get_option('date_format') . ' ' . get_option('time_format'),
                        strtotime($item[$column_name])
                    ));

                case 'blog_url':
                    // Only for multisite
                    // Note: Direct DB access avoided – using get_site_url() cache
                    if (is_multisite() && isset($item['blog_id'])) {
                        return esc_url(get_site_url($item['blog_id']));
                    }
                    return '';

                default:
                    return '';
            }
        }
    }
}
