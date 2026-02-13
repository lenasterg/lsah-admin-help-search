/**
 * LSAH Admin Help Search JS
 * [2026-02-11] Updated to include tooltip, side-panel iframe support,  screen options.
 */
document.addEventListener('DOMContentLoaded', function () {

// 1. Χρησιμοποιούμε το jQuery σε noConflict mode
    var $ = jQuery;

    // Build Form
    const menuItem = document.querySelector('#toplevel_page_lsah-admin-help-search');
    if (!menuItem) return;

    // Build Form
    menuItem.innerHTML = `
        <form class="lsah-admin-search-form" role="search">
            <input type="search" id="lsah-search-input" placeholder="${lsahData.placeholder}" aria-label="${lsahData.ariaLabel}" required>
        </form>
    `;

    // 2. Μετατρέπουμε το στοιχείο σε jQuery αντικείμενο για να δουλέψει το .pointer()
    var $target = $('#lsah-search-input'); 

    var pointerOptions = {
        content: `<h3>${lsahData.pointerTitle}</h3>${lsahData.pointerContent}`, 
        position: {
            edge: 'left',  
            align: 'top',  
        },
        pointerClass: "wp-pointer arrow-top",
		// Το callback που τρέχει όταν κλείνει ο pointer
        close: function() {
            // Ενημέρωση της βάσης δεδομένων μέσω AJAX
            $.post(ajaxurl, {
                pointer: 'lsah_intro_pointer', // Το ID που ελέγχεις στην PHP
                action: 'dismiss-wp-pointer'
            });
        }
    };
	if ($target.length) {
        // Διόρθωση συντακτικού: lsahData.showPointer (με παρενθέσεις στο if)
        if (lsahData.showPointer) {
            $target.pointer(pointerOptions).pointer('open');
        }
    }   

  const form = menuItem.querySelector('form');
  const input = form.querySelector('input');
    
// Έλεγχος αν υπάρχει το URL
const actionUrl = lsahData.actionUrl;
if (!actionUrl) {
    form.innerHTML = `<p style="color:#d63638;padding:8px 10px;font-size:12px;">${lsahData.notConfigured}</p>`;
}
    /**
     * [2026-02-11] Handle Screen Options checkbox change via AJAX.
     * Uses jQuery (as it is standard in WP admin) to save preference.
     */
    jQuery(document).on('change', '#lsah_side_panel_trigger', function() {
        var isChecked = jQuery(this).is(':checked') ? 1 : 0;
        
        jQuery.post(ajaxurl, {
            action: 'lsah_save_user_pref',
            value: isChecked,
            security: lsahData.nonce // Using your existing nonce
        });
    });

/**
 * [2026-02-11]
 * Handle Opening Iframe URL in a New Tab
 */
jQuery(document).on('click', '#lsah-open-external', function(e) {
    e.preventDefault();
    var currentUrl = jQuery('#lsah-iframe').attr('src');
    if (currentUrl && currentUrl !== 'about:blank') {
		jQuery('#lsah-help-panel').removeClass('open').attr('aria-hidden', 'true');
		jQuery('#lsah-iframe').attr('src', 'about:blank');
        window.open(currentUrl, '_blank', 'noopener');
    }
});


/**
 * [2026-02-11]
 * Existing Close Logic updated for consistency
 */
jQuery(document).on('click', '#lsah-close-panel', function() {
    jQuery('#lsah-help-panel').removeClass('open').attr('aria-hidden', 'true');
    jQuery('#lsah-iframe').attr('src', 'about:blank');
});

    /**
     * [2026-02-11] Hide loading spinner once iframe content is fully loaded.
     */
    jQuery('#lsah-iframe').on('load', function() {
        jQuery('#lsah-panel-loading').hide();
    });

    /**
     * Updated Form Submit Handler
     * [2026-02-11] Modified to open in side-panel iframe if enabled in Screen Options.
     */
    form.addEventListener('submit', function (e) {
        e.preventDefault();

        const value = input.value.trim();
        if (!value) return;

        // Χτίζουμε το τελικό URL
        let finalUrl;
        if (actionUrl.includes('{query}')) {
            finalUrl = actionUrl.replace('{query}', encodeURIComponent(value));
        } else {
            finalUrl = actionUrl + encodeURIComponent(value);
        }



        // Logging
        const data = new FormData();
        data.append('action', 'lsah_log_admin_help_search');
        data.append('search', value);
        data.append('search_url', finalUrl);
        data.append('security', lsahData.nonce);

        navigator.sendBeacon(ajaxurl, data);

        // [2026-02-11] Check if side panel is enabled
        const useSidePanel = jQuery('#lsah_side_panel_trigger').is(':checked');

        if (useSidePanel) {
            //Open in Iframe Panel
            jQuery('#lsah-panel-loading').show();
            jQuery('#lsah-help-panel').addClass('open').attr('aria-hidden', 'false');
            // Attempt to jump to site-navigation if the URL doesn't already have a hash, in order to 'hide' the 'useless' banner
			if (finalUrl.indexOf('#') === -1) {
				// Δοκίμασε κοινά IDs που χρησιμοποιεί το WordPress ή documentation sites
				finalUrl += '#site-navigation'; 
			}
			jQuery('#lsah-iframe').attr('src', finalUrl);
        } else {
            //  Παραδοσιακό Redirect σε νέα καρτέλα
            window.open(finalUrl, '_blank', 'noopener');
        }
    });
});
