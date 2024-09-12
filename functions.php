<?php 

add_action( 'wp_enqueue_scripts', 'chs_assets' ); 

function chs_assets() { 

	wp_enqueue_script( 'theme-scripts', get_stylesheet_directory_uri() . '/js/chs-custom-scripts.js', array( 'jquery' ), '1.0', true );

} 

//Order Posts Alphabetically on custom taxonomy 'job_category'

function sort_trades_directory_taxonomy_alphabetically( $query ) {
    // Check if it's not the admin area, it's the main query, and it's a job_category taxonomy archive
    if ( !is_admin() && $query->is_main_query() && is_tax( 'job_category' ) ) {
        $query->set( 'orderby', 'title' );
        $query->set( 'order', 'ASC' );
    }
}
add_action( 'pre_get_posts', 'sort_trades_directory_taxonomy_alphabetically' );

/*Update the default placeholder text for the body content of the Trades Directory post type*/

function custom_trades_directory_placeholder( $string, $post ) {
    // Check if the post type is 'trades-directory'
    if ( $post->post_type === 'trades-directory' ) {
        // Modify the placeholder text
        return 'Type to add a business description';
    }
   // Return the original string if the post type is not 'trades-directory'
    return $string;
    }
    // Hook the custom function into the 'write_your_story' filter
add_filter( 'write_your_story', 'custom_trades_directory_placeholder', 10, 2 );


/*Create shortcode to display categories for the Trades Directory post type*/

function shows_cats( $atts ) {
    // Extract custom taxonomy parameter of the shortcode
    $atts = shortcode_atts( array(
        'custom_taxonomy' => 'job_category',
    ), $atts );

    // Arguments for wp_list_categories
    $args = array(
        'taxonomy' => $atts['custom_taxonomy'],
        'title_li' => '',
        'hide_empty' => false,
        'echo' => false // Return instead of echoing
    );

    // Get the categories list
    $categories = wp_list_categories( $args );

    // Add the 'All' category at the tpp with the desired URL
    $all_link = '<li class="cat-item-all"><a href="https://corteshousingsociety.magnaprototype.com/trades-directory">All</a></li>';

    // Wrap it in an unordered list and return
    return '<ul class="td-categories">' . $all_link . $categories . '</ul>';
}
add_shortcode( 'show_business_categories', 'shows_cats' );


/*Change Events Custom Post Type Post Order from Ascending to Descedning*/

function custom_order_events_desc( $query ) {
    if ( ! is_admin() && $query->is_main_query() && is_post_type_archive( 'events' ) ) {
        $query->set( 'order', 'DESC' );
        $query->set( 'orderby', 'date' );
    }
      if ( is_admin() && $query->is_main_query() && $query->get( 'post_type' ) === 'events' ) {
        // Admin: Order events by date in descending order
        $query->set( 'order', 'DESC' );
        $query->set( 'orderby', 'date' );
    }
}
add_action( 'pre_get_posts', 'custom_order_events_desc' );

/*================================================
#Load custom Blog Module
================================================*/
function divi_custom_blog_module() {
  get_template_part( '/includes/Blog' ); 
  $dcfm = new custom_ET_Builder_Module_Blog();
  remove_shortcode( 'et_pb_blog' );
  add_shortcode( 'et_pb_blog', array( $dcfm, '_shortcode_callback' ) ); 
}
add_action( 'et_builder_ready', 'divi_custom_blog_module' );
function divi_custom_blog_class( $classlist ) {
  // Blog Module 'classname' overwrite.
  $classlist['et_pb_blog'] = array( 'classname' => 'custom_ET_Builder_Module_Blog',);
  return $classlist;
}
add_filter( 'et_module_classes', 'divi_custom_blog_class' );


//*============================================
//Loading the Custom Module into child theme
//=============================================*/
function divi_module_loading() {
    if ( ! class_exists('ET_Builder_Module') ) {
        return;
    }

get_template_part ('custom-modules/cbm');

$cbm = new Custom_ET_Builder_Module_Blog();

remove_shortcode ( 'et_pb_blog');

add_shortcode ( 'et_pb_blog', array($cbm, '_shortcode_callback'));
}

add_action ( 'wp', 'divi_child_theme_setup', 9999);

//*============================================
//Add logo to login screen
//=============================================*/

add_action( 'login_enqueue_scripts', 'my_login_logo' );
function my_login_logo() { ?>
    <style type="text/css">
        #login h1 a, .login h1 a {
            background-image: url(<?php echo get_stylesheet_directory_uri(); ?>/images/CHS-logo.png); /*replace file in path*/
            padding-bottom: 15px;
			background-size: 130px;
			background-position: center center;
			width: 130px;
            height: 130px; 
        }
</style>

<?php }


add_filter( 'login_headerurl', 'my_login_logo_url' );
function my_login_logo_url() {
    return home_url();
}

add_filter( 'login_headertitle', 'my_login_logo_url_title' );
function my_login_logo_url_title() {
    return 'Cortes Island Housing Society';
}