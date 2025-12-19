document.addEventListener('DOMContentLoaded', function () {
    const menuItem = document.querySelector('#toplevel_page_lsah-admin-help-search');
    if (!menuItem) return;

    menuItem.innerHTML = `
        <form class="lsah-admin-search-form" role="search">
            <input
                type="search"
                placeholder="${lsahData.placeholder}"
                aria-label="${lsahData.ariaLabel}"
                required
            >
        </form>
    `;

    const form = menuItem.querySelector('form');
    const input = form.querySelector('input');
    const actionUrl = lsahData.actionUrl;

    if (!actionUrl) {
        form.innerHTML = `<p style="color:#d63638;padding:8px 10px;font-size:12px;">${lsahData.notConfigured}</p>`;
        return;
    }

    form.addEventListener('submit', function (e) {
        e.preventDefault();

        const value = input.value.trim();
        if (!value) return;

        // 🔑 Χτίζουμε το τελικό URL (χωρίς ?s)
        let finalUrl;

        if (actionUrl.includes('{query}')) {
            finalUrl = actionUrl.replace('{query}', encodeURIComponent(value));
        } else {
            finalUrl = actionUrl + encodeURIComponent(value);
        }

        // Logging (αποθηκεύουμε ΚΑΙ το URL)
        const data = new FormData();
        data.append('action', 'lsah_log_admin_help_search');
        data.append('search', value);
        data.append('search_url', finalUrl);
        data.append('security', lsahData.nonce);

        navigator.sendBeacon(ajaxurl, data);

        // 🔹 Redirect στο ΑΚΡΙΒΩΣ ίδιο URL
        window.open(finalUrl, '_blank', 'noopener');
    });
});
