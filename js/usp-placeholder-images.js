
    jQuery(document).ready(function($) {
            var placeholderUrl = 'https://cortescommunityhousing.org/wp-content/uploads/2025/06/Placeholder-better.webp';
    var containerClasses = ['.usp-dynamic-image-2', '.usp-dynamic-image-3', '.usp-dynamic-image-4', '.usp-dynamic-image-5'];

    containerClasses.forEach(function(containerClass) {
                var $container = $(containerClass);

    if ($container.length) {
                    // Check if container has any img elements
                    var hasImage = $container.find('img').length > 0;

    if (!hasImage) {
        // Add placeholder image
        $container.html('<img src="' + placeholderUrl + '" alt="Placeholder Image" class="usp-placeholder-image">');
                    }
                }
            });
        });