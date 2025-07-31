jQuery(document).ready(function ($) {
    // Function to filter resource posts by category
    function filterResourcesByCategory() {
        // Define the category filters for each blog module
        const categoryFilters = {
            'housing-policies': ['.housing-policies-blog', 'housing-policies'],
            'cortes-ecologic-maps': ['.ecologic-maps-blog', 'cortes-ecologic-maps'],
            'water-reports': ['.water-reports-blog', 'water-reports'],
            'rainbow-ridge-studies': ['.rainbow-ridge-blog', 'rainbow-ridge-studies']
        };

        // Loop through each category filter
        Object.keys(categoryFilters).forEach(function (categorySlug) {
            const moduleSelector = categoryFilters[categorySlug][0];
            const termSlug = categoryFilters[categorySlug][1];

            // Find the blog module with this class
            const $blogModule = $(moduleSelector);

            if ($blogModule.length) {
                // Hide all posts in this module first
                $blogModule.find('.et_pb_post').hide();

                // Show only posts that have the correct taxonomy term class
                $blogModule.find('.et_pb_post.term-' + termSlug).show();

                // If no posts are visible, show a message
                if ($blogModule.find('.et_pb_post:visible').length === 0) {
                    $blogModule.find('.et_pb_posts, .et_pb_blog_grid').append(
                        '<div class="no-posts-message"><p>No ' + categorySlug.replace('-', ' ') + ' posts found.</p></div>'
                    );
                }
            }
        });
    }

    // Function to add taxonomy term classes to posts
    function addTaxonomyClasses() {
        $('.et_pb_post').each(function () {
            const $post = $(this);
            const postId = $post.attr('id');

            // Extract post ID from the id attribute (usually "post-123")
            if (postId) {
                const id = postId.replace('post-', '');

                // Make AJAX call to get taxonomy terms for this post
                $.ajax({
                    url: ajax_object.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'get_post_taxonomy_terms',
                        post_id: id,
                        nonce: ajax_object.nonce
                    },
                    success: function (response) {
                        if (response.success && response.data.terms) {
                            response.data.terms.forEach(function (term) {
                                $post.addClass('term-' + term.slug);
                                $post.addClass('taxonomy-' + term.taxonomy);
                            });

                            // After adding classes, filter the posts
                            filterResourcesByCategory();
                        }
                    }
                });
            }
        });
    }

    // Initialize the filtering
    addTaxonomyClasses();

    // Re-run after AJAX pagination or filtering
    $(document).on('ajaxComplete', function () {
        setTimeout(function () {
            addTaxonomyClasses();
        }, 500);
    });
});