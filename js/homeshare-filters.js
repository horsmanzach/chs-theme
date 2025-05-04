jQuery(document).ready(function($) {
    // Listen for changes on filter dropdowns
    $('.js-homeshare-filter').on('change', function() {
        // Get current URL
        let currentUrl = new URL(window.location.href);
        let searchParams = currentUrl.searchParams;
        
        // Collect all filter values
        $('.js-homeshare-filter').each(function() {
            const name = $(this).attr('name');
            const value = $(this).val();
            
            // Update or remove parameter based on selection
            if (value) {
                searchParams.set(name, value);
            } else {
                searchParams.delete(name);
            }
        });
        
        // Redirect to filtered URL
        window.location.href = currentUrl.toString();
    });
    
    // Add reset button functionality if needed
    $('.js-reset-filters').on('click', function(e) {
        e.preventDefault();
        
        // Reset all filter dropdowns
        $('.js-homeshare-filter').val('');
        
        // Get base URL without parameters
        let currentUrl = new URL(window.location.href);
        window.location.href = currentUrl.origin + currentUrl.pathname;
    });
});