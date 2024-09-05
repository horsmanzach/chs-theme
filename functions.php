<?php 

add_action( 'wp_enqueue_scripts', 'chs_assets' ); 

function chs_assets() { 

	wp_enqueue_script( 'theme-scripts', get_stylesheet_directory_uri() . '/js/chs-custom-scripts.js', array( 'jquery' ), '1.0', true );

} 

/*Update the default placeholder text for the body content of the Trades Directory post type*/

function custom_gutenberg_placeholder_script() {
    global $post_type;
    
    // Only enqueue script for 'trades_directory' post type
    if ( $post_type === 'trades-directory' ) {
        wp_enqueue_script(
            'custom-gutenberg-placeholder',
            get_template_directory_uri() . '/js/custom-gutenberg-placeholder.js', // Change this path if necessary
            array( 'wp-blocks', 'wp-dom-ready', 'wp-edit-post' ),
            null,
            true
        );
    }
}
add_action( 'admin_enqueue_scripts', 'custom_gutenberg_placeholder_script' );



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
        'echo' => false // Return instead of echoing
    );

    // Get the categories list
    $categories = wp_list_categories( $args );

    // Wrap it in an unordered list and return
    return '<ul class="td-categories">' . $categories . '</ul>';
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