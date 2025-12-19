<?php
/**
 * Plugin Name: LSAH Admin Help Search (Multisite)
 * Description: Adds a quick help search box in the admin menu. Configurable search URL, logs all searches, fully multisite-compatible.
 * Version: 1.0.0
 * Author: LS
 * Text Domain: lsah-admin-help-search
 * Domain Path: /languages
 * Network: true
 *
 * @package LSAH_Admin_Help_Search
 */

if (!defined('ABSPATH')) {
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
 * ------------------------------------------------------------------------
 * Load plugin translations
 * ------------------------------------------------------------------------
 */

/**
 * Loads the plugin text domain for translation.
 *
 * @return void
 */
function lsah_load_textdomain() {
    load_plugin_textdomain(
        'lsah-admin-help-search',
        false,
        dirname(plugin_basename(__FILE__)) . '/languages'
    );
}
add_action('plugins_loaded', 'lsah_load_textdomain');

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
            printf(
                /* translators: %s: Settings page URL */
                __('LSAH: Please configure the Help Search Action URL in <a href="%s">Settings → Help Search</a>.', 'lsah-admin-help-search'),
                esc_url($settings_url)
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
 * @return void
 */
function lsah_add_admin_menu() {
    $action_url = get_site_option(LSAH_OPTION_ACTION_URL);

    // Μην προσθέτουμε το menu αν δεν έχει ρυθμιστεί URL
    if (!$action_url) {
        return;
    }

    add_menu_page(
        __('Help Search', 'lsah-admin-help-search'),
        __('Help for…', 'lsah-admin-help-search'),
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
        'placeholder'   => __('Help for…', 'lsah-admin-help-search'),
        'ariaLabel'     => __('Search the help manual', 'lsah-admin-help-search'),
        'notConfigured' => __('Help Search URL not configured.', 'lsah-admin-help-search'),
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
 * Logs the search in the network-wide table, incrementing count if the term already exists.
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 * @return void
 */
function lsah_log_admin_help_search() {
    if (!is_user_logged_in()) {
        wp_die();
    }

    check_ajax_referer('lsah_log_admin_help_search', 'security', true);

    global $wpdb;

    $table_name = $wpdb->base_prefix . LSAH_TABLE_SEARCHES;
    $blog_id    = get_current_blog_id();
    $search     = sanitize_text_field($_POST['search'] ?? '');
    
    if (!$search) {
        wp_die();
    }

    $search_url = esc_url_raw($_POST['search_url'] ?? '');
    $now = current_time('mysql');

    // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is escaped via constant.
    $row = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT id, search_count FROM $table_name WHERE blog_id = %d AND search_term = %s",
            $blog_id,
            $search
        )
    );

    if ($row) {
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

    wp_die();
}
add_action('wp_ajax_lsah_log_admin_help_search', 'lsah_log_admin_help_search');

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
        __('Help Search Settings', 'lsah-admin-help-search'),
        __('Help Search', 'lsah-admin-help-search'),
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
        __('Help Search Settings', 'lsah-admin-help-search'),
        __('Help Search', 'lsah-admin-help-search'),
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
        wp_die(__('You do not have sufficient permissions to access this page.', 'lsah-admin-help-search'));
    }

    if (!is_multisite() && !current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.', 'lsah-admin-help-search'));
    }
    $messages_displayed = false;

    if (isset($_POST['lsah_save_settings']) && check_admin_referer('lsah_save_settings')) {
        $raw_url = trim($_POST['lsah_action_url'] ?? '');

        if (empty($raw_url)) {
            add_settings_error('lsah_settings', 'url_empty', __('Please enter a URL.', 'lsah-admin-help-search'), 'error');
            $url = '';
        } else {
            if (!preg_match('#^https?://#i', $raw_url)) {
                add_settings_error('lsah_settings', 'url_scheme', __('The URL must start with http:// or https://.', 'lsah-admin-help-search'), 'error');
                $url = '';
            } elseif (filter_var($raw_url, FILTER_VALIDATE_URL) === false) {
                add_settings_error('lsah_settings', 'url_invalid', __('Please enter a valid URL.', 'lsah-admin-help-search'), 'error');
                $url = '';
            } else {
                $url = esc_url_raw($raw_url);
                add_settings_error('lsah_settings', 'settings_updated', __('Settings saved.', 'lsah-admin-help-search'), 'updated');
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
            <?php submit_button(__('Save Changes', 'lsah-admin-help-search'), 'primary', 'lsah_save_settings'); ?>
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
 * Statistics page
 * ------------------------------------------------------------------------
 */
function lsah_add_network_statistics_menu() {
    add_submenu_page(
        'settings.php',
        __('Help Search Statistics', 'lsah-admin-help-search'),
        __('Help Search Statistics', 'lsah-admin-help-search'),
        'manage_network',
        'lsah-help-search-statistics',
        'lsah_render_statistics_page'
    );
}
add_action('network_admin_menu', 'lsah_add_network_statistics_menu');

function lsah_add_single_site_statistics_menu() {
    if (is_multisite()) return;
    add_submenu_page(
        'options-general.php',
        __('Help Search Statistics', 'lsah-admin-help-search'),
        __('Help Search Statistics', 'lsah-admin-help-search'),
        'manage_options',
        'lsah-help-search-statistics',
        'lsah_render_statistics_page'
    );
}
add_action('admin_menu', 'lsah_add_single_site_statistics_menu');

function lsah_render_statistics_page() {
    if (is_multisite() && !current_user_can('manage_network')) {
        wp_die(__('You do not have sufficient permissions to access this page.', 'lsah-admin-help-search'));
    }
    if (!is_multisite() && !current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.', 'lsah-admin-help-search'));
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
            $statistics_table->search_box(__('Search terms', 'lsah-admin-help-search'), 'search_term');
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
     * Class LSAH_Search_Statistics_Table
     *
     * Displays admin help search statistics in a WP_List_Table.
     * Shows Search Term, Count, First & Last Searched.
     * On multisite, also shows the Site URL.
     */
    class LSAH_Search_Statistics_Table extends WP_List_Table {

        /**
         * Constructor
         */
        public function __construct() {
            parent::__construct([
                'singular' => __('Search Term', 'lsah-admin-help-search'),
                'plural'   => __('Search Terms', 'lsah-admin-help-search'),
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
                'search_term'    => __('Search Term', 'lsah-admin-help-search'),
                'search_count'   => __('Count', 'lsah-admin-help-search'),
                'first_searched' => __('First Searched', 'lsah-admin-help-search'),
                'last_searched'  => __('Last Searched', 'lsah-admin-help-search'),
            ];

            // Σε multisite, προσθέτουμε στήλη για Site URL
            if (is_multisite()) {
                $columns = ['blog_url' => __('Site URL', 'lsah-admin-help-search')] + $columns;
            }

            return $columns;
        }

        /**
         * Prepare items for display
         */
        public function prepare_items() {
            global $wpdb;
            $table = $wpdb->base_prefix . LSAH_TABLE_SEARCHES;

            $search = isset($_REQUEST['s']) ? sanitize_text_field($_REQUEST['s']) : '';

            if (!empty($search)) {
                $like  = '%' . $wpdb->esc_like($search) . '%';
                $query = $wpdb->prepare(
                    "SELECT * FROM $table WHERE search_term LIKE %s ORDER BY last_searched DESC",
                    $like
                );
            } else {
                $query = "SELECT * FROM $table ORDER BY last_searched DESC";
            }

            $this->items = $wpdb->get_results($query, ARRAY_A);
            $this->_column_headers = [$this->get_columns(), [], []];
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
                    // Μόνο για multisite
                    if (is_multisite() && isset($item['blog_id'])) {
                        // Άμεση ανάκτηση από wp_blogs για ταχύτητα
                        $blog_table = $wpdb->base_prefix . 'blogs';
                        $url = $wpdb->get_var(
                            $wpdb->prepare(
                                "SELECT domain FROM $blog_table WHERE blog_id = %d",
                                $item['blog_id']
                            )
                        );

                        if ($url) {
                            $path = $wpdb->get_var(
                                $wpdb->prepare(
                                    "SELECT path FROM $blog_table WHERE blog_id = %d",
                                    $item['blog_id']
                                )
                            );
                            return esc_url('https://' . $url . $path);
                        } else {
                            return esc_html($item['blog_id']);
                        }
                    }
                    return '';

                default:
                    return '';
            }
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

            if (is_multisite()) {
                $columns['blog_url'] = ['blog_url', false];
            }

            return $columns;
        }
    }
}
