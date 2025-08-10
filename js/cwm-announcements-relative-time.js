jQuery(document).ready(function($) {
    // Function to calculate relative time
    function getRelativeTime(postDate) {
        var currentTime = new Date();
        var postTime = new Date(postDate);
        var timeDiff = currentTime - postTime;
        
        // Convert to hours
        var hours = Math.floor(timeDiff / (1000 * 60 * 60));
        
        if (hours < 24) {
            // Less than 24 hours - show hours
            if (hours <= 0) {
                return 'Posted just now';
            } else if (hours == 1) {
                return 'Posted 1 hour ago';
            } else {
                return 'Posted ' + hours + ' hours ago';
            }
        } else if (hours < 720) { // Less than 30 days (24 * 30 = 720)
            // Show days
            var days = Math.floor(hours / 24);
            if (days == 1) {
                return 'Posted 1 day ago';
            } else {
                return 'Posted ' + days + ' days ago';
            }
        } else {
            // Show months
            var months = Math.floor(hours / 720); // Approximate months (30 days each)
            if (months == 1) {
                return 'Posted 1 month ago';
            } else {
                return 'Posted ' + months + ' months ago';
            }
        }
    }
    
    // Target the announcements blog module specifically
    $('.announcements .et_pb_post').each(function() {
        var $post = $(this);
        var $publishedSpan = $post.find('.published');
        
        if ($publishedSpan.length) {
            // Get the article ID to fetch the post date
            var $article = $post.find('article');
            var postId = null;
            
            if ($article.length && $article.attr('id')) {
                // Extract post ID from article id (usually like 'post-123')
                var articleId = $article.attr('id');
                var matches = articleId.match(/post-(\d+)/);
                if (matches) {
                    postId = matches[1];
                }
            }
            
            // If we have post data available
            if (typeof cwmAnnouncementData !== 'undefined' && postId && cwmAnnouncementData.postDates[postId]) {
                var postDate = cwmAnnouncementData.postDates[postId];
                var relativeTime = getRelativeTime(postDate);
                $publishedSpan.text(relativeTime).addClass('cwm-relative-time');
            }
        }
    });
});