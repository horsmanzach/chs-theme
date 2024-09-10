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


// Add Custom Validating for Trades Directory Phone Field
// Enusures that fields receives exactly 10 digits

function validate_phone_number_length($valid, $value, $field, $input) {
    // Only run validation for the "phone" field (check the field key or name)
    if ($field['name'] === 'phone') { 

        // Remove non-numeric characters from the phone number
        $cleaned_value = preg_replace('/\D/', '', $value);

        // Check if the number contains only digits and is exactly 10 characters long
      /*  if (!ctype_digit($cleaned_value) || strlen($cleaned_value) !== 10) {
            $valid = 'Phone number must contain exactly 10 digits and be all numeric.';
        } */
    }

    return $valid;
}
add_filter('acf/validate_value/name=phone', 'validate_phone_number_length', 10, 4);



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