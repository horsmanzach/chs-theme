jQuery(document).ready(function ($) {
    // Function to format date as "Joined Month Year"
    function formatJoinDate(postDate) {
        var date = new Date(postDate);
        var monthNames = [
            "January", "February", "March", "April", "May", "June",
            "July", "August", "September", "October", "November", "December"
        ];

        var month = monthNames[date.getMonth()];
        var year = date.getFullYear();

        return 'Joined ' + month + ' ' + year;
    }

    // Target the cwm-members blog module specifically
    $('.cwm-members article').each(function () {
        var $article = $(this);
        var $publishedSpan = $article.find('.published');

        if ($publishedSpan.length) {
            // Get the post ID directly from the article id attribute
            var articleId = $article.attr('id');
            var postId = null;

            if (articleId) {
                // Extract post ID from article id (like 'post-247132')
                var matches = articleId.match(/post-(\d+)/);
                if (matches) {
                    postId = matches[1];
                }
            }

            // Debug logging
            if (typeof cwmMemberData !== 'undefined' && cwmMemberData.debug) {
                console.log('Processing member article:', articleId);
                console.log('Extracted post ID:', postId);
                console.log('Current published text:', $publishedSpan.text());
            }

            // If we have post data available
            if (typeof cwmMemberData !== 'undefined' && postId && cwmMemberData.postDates[postId]) {
                var postDate = cwmMemberData.postDates[postId];
                var joinDate = formatJoinDate(postDate);

                if (cwmMemberData.debug) {
                    console.log('Setting join date:', joinDate);
                }

                $publishedSpan.text(joinDate).addClass('cwm-join-date');
            } else if (typeof cwmMemberData !== 'undefined' && cwmMemberData.debug) {
                console.log('No post data found for ID:', postId);
                console.log('Available post IDs:', Object.keys(cwmMemberData.postDates || {}));
            }
        }
    });
});