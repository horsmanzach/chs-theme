jQuery(document).ready(function(){
    //For mobile Screens
    if (window.matchMedia('(max-width: 767px)').matches) {
        var initial_show_article = 3;
        var article_reveal = 10;

        // Remove the CSS rule and apply JavaScript control
        jQuery('head style:last').remove();
        jQuery(".pa-blog-load-more article").not(":nth-child(-n+" + initial_show_article + ")").css("display", "none");

        jQuery("#pa_load_more").on("click", function (event) {
            event.preventDefault();
            initial_show_article = initial_show_article + article_reveal;
            jQuery(".pa-blog-load-more article").css("display", "block");
            jQuery(".pa-blog-load-more article").not(":nth-child(-n+" + initial_show_article + ")").css("display", "none");
            var articles_num = jQuery(".pa-blog-load-more article").not('[style*="display: block"]').length
            if (articles_num == 0) {
                jQuery(this).css("display", "none");
            }
        })
    } else {
        //For desktop Screens
        var initial_row_show = 3;
        var row_reveal = 10;
        var total_articles = jQuery(".pa-blog-load-more article").length;

        // Remove the CSS rule and apply JavaScript control
        jQuery('head style:last').remove();
        jQuery(".pa-blog-load-more article").not(":nth-child(-n+" + initial_row_show + ")").css("display", "none");

        jQuery("#pa_load_more").on("click", function (event) {
            event.preventDefault();
            initial_row_show = initial_row_show + row_reveal;
            jQuery(".pa-blog-load-more article").css("display", "block");
            jQuery(".pa-blog-load-more article").not(":nth-child(-n+" + initial_row_show + ")").css("display", "none");
            var articles_num = jQuery(".pa-blog-load-more article").not('[style*="display: block"]').length
            if (articles_num == 0) {
                jQuery(this).css("display", "none");
            }
        })
    } 
})