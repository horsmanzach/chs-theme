<?php 

add_action( 'wp_enqueue_scripts', 'chs_assets' ); 

function chs_assets() { 

	wp_enqueue_script( 'theme-scripts', get_stylesheet_directory_uri() . '/js/chs-custom-scripts.js', array( 'jquery' ), '1.0', true );

} 

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
