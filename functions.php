<?php 

add_action( 'wp_enqueue_scripts', 'chs_assets' ); 

function chs_assets() { 

	wp_enqueue_script( 'theme-scripts', get_stylesheet_directory_uri() . '/js/chs-custom-scripts.js', array( 'jquery' ), '1.0', true );

}


/*====Modify the Divi blog module query ======*/


function modify_divi_blog_query_args($args) {
    // Only modify if viewing homeshare-listings
    if ($args['post_type'] === 'homeshare-listings') {

        // Initialize meta_query array
        $args['meta_query'] = [];
        // Add tax query
        $args['tax_query'] = [
            [
                'taxonomy' => 'listing_type',
                'field'    => 'slug',
                'terms'    => 'host-listing',
            ]
        ];
        
        // Region filter
        if (isset($_GET['region']) && !empty($_GET['region'])) {
            $args['meta_query'][] = [
                'key'     => 'which_region',
                'value'   => sanitize_text_field($_GET['region']),
                'compare' => '=',
            ];
        }

          // Term of Lease filter
        if (isset($_GET['lease_term']) && !empty($_GET['lease_term'])) {
            $args['meta_query'][] = [
                'key'     => 'term_of_lease',
                'value'   => sanitize_text_field($_GET['lease_term']),
                'compare' => '=',
            ];
        }

           // Housing Arrangement filter
        if (isset($_GET['arrangement']) && !empty($_GET['arrangement'])) {
            $args['meta_query'][] = [
                'key'     => 'housing_arrangements',
                'value'   => sanitize_text_field($_GET['arrangement']),
                'compare' => '=',
            ];
        }

           // Rent Filter
     // Rent Filter
    if (isset($_GET['rent']) && !empty($_GET['rent'])) {
        $rent_range = explode('-', sanitize_text_field($_GET['rent']));
        if (count($rent_range) === 2) {
        $args['meta_query'][] = [
            'key'     => 'rent',
            'value'   => [$rent_range[0], $rent_range[1]],
            'type'    => 'NUMERIC',
            'compare' => 'BETWEEN',
            ];
        }
    }
        
        // Add other filter conditions...
    
    }
    
    return $args;
}
add_filter('et_pb_blog_query_args', 'modify_divi_blog_query_args', 10, 1);

/**
 * HomeShare Listings Filters
 * 
 * Creates shortcodes for filtering 'homeshare-listings' custom post type
 * based on ACF fields
 */


// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get field choices directly from ACF field settings
 * This is more reliable than get_field_object in some contexts
 */
function homeshare_get_acf_field_choices($field_name) {
    // Get all ACF field groups
    $field_groups = acf_get_field_groups();
    $choices = array();
    
    // Loop through each field group
    foreach($field_groups as $field_group) {
        // Get fields in this group
        $fields = acf_get_fields($field_group['key']);
        
        // Look for our target field
        foreach($fields as $field) {
            if($field['name'] === $field_name) {
                // For select and checkbox fields, get choices
                if(isset($field['choices'])) {
                    return $field['choices'];
                }
            }
        }
    }
    
    return $choices;
}

/**
 * Region Filter Shortcode - Fixed
 */
function homeshare_region_filter_shortcode() {
    // Manually defined choices as fallback
    $choices = homeshare_get_acf_field_choices('which_region');
    
    // If choices couldn't be fetched dynamically, check if we have a hardcoded backup
    if(empty($choices)) {
        // Fallback: Create test options for development
        // IMPORTANT: Replace these with your actual options from ACF
        $choices = array(
            'waletown' => 'Waletown',
            'squirrel-cove' => 'Squirrel Cove',
            'mansons' => 'Mansons',
            'bartholomew' => 'Bartholomew',
            'tiber' => 'Tiber',
            'other' => 'Other'
        );
    }
    
    $current_region = isset($_GET['region']) ? sanitize_text_field($_GET['region']) : '';
    
    $output = '<div class="homeshare-filter region-filter">';
    $output .= '<label for="region-filter">Region:</label>';
    $output .= '<select id="region-filter" name="region" class="homeshare-filter-select js-homeshare-filter">';
    $output .= '<option value="">All Regions</option>';
    
    foreach($choices as $value => $label) {
        $selected = $current_region === $value ? 'selected' : '';
        $output .= sprintf(
            '<option value="%s" %s>%s</option>',
            esc_attr($value),
            $selected,
            esc_html($label)
        );
    }
    
    $output .= '</select>';
    $output .= '</div>';
    
    return $output;
}
add_shortcode('homeshare_region_filter', 'homeshare_region_filter_shortcode');

/**
 * Term of Lease Filter Shortcode - Fixed
 */
function homeshare_lease_term_filter_shortcode() {
    // Get choices from ACF
    $choices = homeshare_get_acf_field_choices('term_of_lease');
    
    // If choices couldn't be fetched dynamically, check if we have a hardcoded backup
    if(empty($choices)) {
        // Fallback: Create test options for development
        // IMPORTANT: Replace these with your actual options from ACF
        $choices = array(
            'month-to-month' => 'Month-to-month',
            '6-months' => '6 Months',
            '1-year' => '1 Year',
            '2-years' => '2 Years',
            'other' => 'Other'
        );
    }
    
    $current_term = isset($_GET['lease_term']) ? sanitize_text_field($_GET['lease_term']) : '';
    
    $output = '<div class="homeshare-filter lease-term-filter">';
    $output .= '<label for="lease-term-filter">Term of Lease:</label>';
    $output .= '<select id="lease-term-filter" name="lease_term" class="homeshare-filter-select js-homeshare-filter">';
    $output .= '<option value="">Any Term</option>';
    
    foreach($choices as $value => $label) {
        $selected = $current_term === $value ? 'selected' : '';
        $output .= sprintf(
            '<option value="%s" %s>%s</option>',
            esc_attr($value),
            $selected,
            esc_html($label)
        );
    }
    
    $output .= '</select>';
    $output .= '</div>';
    
    return $output;
}
add_shortcode('homeshare_lease_term_filter', 'homeshare_lease_term_filter_shortcode');

/**
 * Housing Arrangements Filter Shortcode - Fixed
 * Note: Even though it's a checkbox field in ACF, we're using it as a single-select in the filter
 */
/**
 * Housing Arrangements Filter Shortcode - Updated for Radio Field
 */
function homeshare_housing_arrangements_filter_shortcode() {
    // Get choices from ACF
    $choices = homeshare_get_acf_field_choices('housing_arrangements');
    
    // If choices couldn't be fetched dynamically, use hardcoded backup
    if(empty($choices)) {
        // Fallback: Create options that match your ACF radio field
        $choices = array(
            'entire-home' => 'Entire Home',
            'suite' => 'Suite',
            'cabin' => 'Cabin (under 600 sq. ft.)',
            'bedroom' => 'Bedroom with shared amenities',
            'mobile-home' => 'Mobile Home or RV',
            'moveable-home' => 'Site for tenants moveable home',
            'permanent-home' => 'Site for tenant to build a permanent home',
            'shared-ownership' => 'Shared Ownership / Land'
        );
    }
    
    $current_arrangement = isset($_GET['arrangement']) ? sanitize_text_field($_GET['arrangement']) : '';
    
    $output = '<div class="homeshare-filter arrangement-filter">';
    $output .= '<label for="arrangement-filter">Housing Arrangement:</label>';
    $output .= '<select id="arrangement-filter" name="arrangement" class="homeshare-filter-select js-homeshare-filter">';
    $output .= '<option value="">Any Arrangement</option>';
    
    foreach($choices as $value => $label) {
        $selected = $current_arrangement === $value ? 'selected' : '';
        $output .= sprintf(
            '<option value="%s" %s>%s</option>',
            esc_attr($value),
            $selected,
            esc_html($label)
        );
    }
    
    $output .= '</select>';
    $output .= '</div>';
    
    return $output;
}
add_shortcode('homeshare_housing_arrangements_filter', 'homeshare_housing_arrangements_filter_shortcode');


function homeshare_rent_filter_shortcode() {
    // Create predefined rent ranges for the filter
    $rent_ranges = array(
        '' => 'Any Price',
        '0-500' => 'Up to $500',
        '501-1000' => '$501 - $1,000',
        '1001-1500' => '$1,001 - $1,500',
        '1501-2000' => '$1,501 - $2,000',
        '2001-3000' => '$2,001 - $3,000',
        '3001-999999' => 'Above $3,000'
    );
    
    $current_rent = isset($_GET['rent']) ? sanitize_text_field($_GET['rent']) : '';
    
    $output = '<div class="homeshare-filter rent-filter">';
    $output .= '<label for="rent-filter">Monthly Rent:</label>';
    $output .= '<select id="rent-filter" name="rent" class="homeshare-filter-select js-homeshare-filter">';
    
    foreach ($rent_ranges as $range => $label) {
        $selected = $current_rent === $range ? 'selected' : '';
        $output .= sprintf(
            '<option value="%s" %s>%s</option>',
            esc_attr($range),
            $selected,
            esc_html($label)
        );
    }
    
    $output .= '</select>';
    $output .= '</div>';
    
    return $output;
}
add_shortcode('homeshare_rent_filter', 'homeshare_rent_filter_shortcode');

/**
 * Modified pre_get_posts function to match the housing_arrangements field correctly
*/
/**
 * Modified pre_get_posts function to handle all filters correctly
 */
function homeshare_filter_pre_get_posts($query) {
    // Only modify the main query on the frontend for our custom post type
    if (is_admin() || !$query->is_main_query() || !is_post_type_archive('homeshare-listings')) {
        return;
    }
    
    // Filter by taxonomy to only show host-listings
    $tax_query = [
        [
            'taxonomy' => 'listing_type',
            'field'    => 'slug',
            'terms'    => 'host-listing',
        ]
    ];
    
    $query->set('tax_query', $tax_query);
    
    // Add meta queries based on filter selections
    $meta_query = [];
    
    // Region filter
    if (isset($_GET['region']) && !empty($_GET['region'])) {
        $meta_query[] = [
            'key'     => 'which_region',
            'value'   => sanitize_text_field($_GET['region']),
            'compare' => '=',
        ];
    }
    
    // Lease term filter
    if (isset($_GET['lease_term']) && !empty($_GET['lease_term'])) {
        $meta_query[] = [
            'key'     => 'term_of_lease',
            'value'   => sanitize_text_field($_GET['lease_term']),
            'compare' => '=',
        ];
    }
    
    // Rent filter - handle price ranges
    if (isset($_GET['rent']) && !empty($_GET['rent'])) {
        $rent_range = explode('-', sanitize_text_field($_GET['rent']));
        if (count($rent_range) === 2) {
            $meta_query[] = [
                'key'     => 'rent',
                'value'   => [$rent_range[0], $rent_range[1]],
                'type'    => 'NUMERIC',
                'compare' => 'BETWEEN',
            ];
        }
    }
    
    // Housing arrangement filter
  // Housing arrangement filter
    if (isset($_GET['arrangement']) && !empty($_GET['arrangement'])) {
        $meta_query[] = [
        'key'     => 'housing_arrangements',
        'value'   => sanitize_text_field($_GET['arrangement']),
        'compare' => '=',
        ];
    }
    
    if (!empty($meta_query)) {
        $query->set('meta_query', $meta_query);
    }
}
add_action('pre_get_posts', 'homeshare_filter_pre_get_posts');

/**
 * Enqueue scripts and styles
 */
function homeshare_filters_enqueue_scripts() {
    // Enqueue JavaScript
    wp_enqueue_script(
        'homeshare-filters',
        get_stylesheet_directory_uri() . '/js/homeshare-filters.js',
        ['jquery'],
        '1.0.1',
        true
    );
}
add_action('wp_enqueue_scripts', 'homeshare_filters_enqueue_scripts');

/**
 * Add a simple reset filters shortcode
 */
function homeshare_reset_filters_shortcode() {
    $current_url = remove_query_arg(['region', 'lease_term', 'rent', 'arrangement']);
    return '<a href="' . esc_url($current_url) . '" class="homeshare-filters-reset">Reset Filters</a>';
}
add_shortcode('homeshare_reset_filters', 'homeshare_reset_filters_shortcode');



/**
 * Filtering HomeShare Listings by Taxonomy Term
 */

// Add taxonomy term classes to posts
add_filter('post_class', 'add_taxonomy_term_classes', 10, 3);
function add_taxonomy_term_classes($classes, $class, $post_id) {
    $terms = get_the_terms($post_id, 'listing_type');
    if (!empty($terms) && !is_wp_error($terms)) {
        foreach ($terms as $term) {
            $classes[] = 'term-' . $term->slug;
        }
    }
    return $classes;
}

// Add initial CSS to hide all posts
add_action('wp_head', 'add_initial_hiding_css');
function add_initial_hiding_css() {
    ?>
    <style>
    /* Hide all posts in these modules initially */
    #host_module .et_pb_post,
    #guest_module .et_pb_post {
        display: none;
    }
    
    /* These classes will be added by JS when filtering is done */
    #host_module .et_pb_post.show-host-post,
    #guest_module .et_pb_post.show-guest-post {
        display: block;
    }
    </style>
    <?php
}

// jQuery to show only appropriate posts
add_action('wp_footer', 'filter_homeshare_listings');
function filter_homeshare_listings() {
    ?>
    <script>
    jQuery(document).ready(function($) {
        // Show only host listings in host module
        $('#host_module .et_pb_post').each(function() {
            if ($(this).hasClass('term-host-listing')) {
                $(this).addClass('show-host-post');
            }
        });
        
        // Show only guest listings in guest module
        $('#guest_module .et_pb_post').each(function() {
            if ($(this).hasClass('term-guest-listing')) {
                $(this).addClass('show-guest-post');
            }
        });
    });
    </script>
    <?php
}

/**
 * Remove Yoast SEO metabox from specific post types
 */
function remove_yoast_seo_from_custom_post_types() {
    // Remove Yoast meta box from homeshare-listings
    remove_meta_box('wpseo_meta', 'homeshare-listings', 'normal');
    
    // Remove Yoast meta box from trades-directory
    remove_meta_box('wpseo_meta', 'trades-directory', 'normal');
}
add_action('add_meta_boxes', 'remove_yoast_seo_from_custom_post_types', 100);

/**
 * Disable Yoast SEO columns in post list view for specific post types
 */
function disable_yoast_seo_columns_for_custom_post_types($columns) {
    global $typenow;
    
    // Only modify for specified post types
    if ($typenow === 'homeshare-listings' || $typenow === 'trades-directory') {
        // Remove SEO columns
        unset($columns['wpseo-score']);
        unset($columns['wpseo-score-readability']);
        unset($columns['wpseo-title']);
        unset($columns['wpseo-metadesc']);
        unset($columns['wpseo-focuskw']);
        unset($columns['wpseo-links']);
        unset($columns['wpseo-linked']);
    }
    
    return $columns;
}
add_filter('manage_posts_columns', 'disable_yoast_seo_columns_for_custom_post_types', 10, 1);

/**
 * Disable Yoast SEO analysis for specific post types
 */
function disable_yoast_seo_for_custom_post_types($enabled, $post_type) {
    if ($post_type === 'homeshare-listings' || $post_type === 'trades-directory') {
        return false;
    }
    return $enabled;
}
add_filter('wpseo_metabox_enabled', 'disable_yoast_seo_for_custom_post_types', 10, 2);



// ==============Add filter dropdown for listing-types taxonomy in admin
function add_listing_type_filter_to_admin() {
    global $typenow;
    
    // Only add on the homeshare-listings post type admin screen
    if ($typenow == 'homeshare-listings') {
        // Create the dropdown for the taxonomy
        $taxonomy = 'listing_type';
        $tax = get_taxonomy($taxonomy);
        $selected = isset($_GET[$taxonomy]) ? $_GET[$taxonomy] : '';
        
        wp_dropdown_categories(array(
            'show_option_all' => sprintf(__('Show All %s', 'textdomain'), $tax->label),
            'taxonomy' => $taxonomy,
            'name' => $taxonomy,
            'orderby' => 'name',
            'selected' => $selected,
            'hierarchical' => true,
            'show_count' => true,
            'hide_empty' => false,
            'value_field' => 'slug',
        ));
    }
}
add_action('restrict_manage_posts', 'add_listing_type_filter_to_admin');

// Make sure the filter works with the URL query parameter
function custom_taxonomy_filter_post_type_request($query) {
    global $pagenow, $typenow;
    
    // Only add to admin main query on the homeshare-listings list page
    if (is_admin() && $pagenow == 'edit.php' && $typenow == 'homeshare-listings' && isset($_GET['listing_type']) && $_GET['listing_type'] != '') {
        $query->query_vars['tax_query'] = array(
            array(
                'taxonomy' => 'listing_type',
                'field' => 'slug',
                'terms' => $_GET['listing_type']
            )
        );
    }
    
    return $query;
}
add_filter('parse_query', 'custom_taxonomy_filter_post_type_request');



/*=====Create ACF Checkbox Shortcode=====*/

function render_acf_checkbox_field_shortcode($atts) {
    // Set up default attributes and merge with user provided ones
    $attributes = shortcode_atts(array(
        'field_name' => '',
        'label' => '',
        'required' => 'false',
        'class' => 'td-form'
    ), $atts);
    
    // Bail if no field name is provided
    if (empty($attributes['field_name'])) {
        return 'Error: field_name parameter is required';
    }
    
    // Get field information directly from ACF
    $field = acf_get_field($attributes['field_name']);
    
    // If that doesn't work, try getting the field object a different way
    if (!$field || empty($field['choices'])) {
        // Try to get field from current post type
        $post_type = get_post_type_object(get_post_type());
        $field = get_field_object($attributes['field_name']);
        
        // For debugging
        if (!$field) {
            return 'Error: Could not find field: ' . $attributes['field_name'] . '. Please check the field name.';
        }
        
        if (empty($field['choices'])) {
            return 'Error: Field found but no choices available for: ' . $attributes['field_name'];
        }
    }
    
    // Prepare label and required marker
    $label_text = !empty($attributes['label']) ? $attributes['label'] : $field['label'];
    $required_marker = ($attributes['required'] === 'true') ? ' *' : '';
    
    $output = '<div class="acf-checkbox-group ' . esc_attr($attributes['class']) . '">';
    $output .= '<label class="usp-label">' . esc_html($label_text) . $required_marker . '</label>';
    
    // Generate checkboxes based on ACF field choices
    foreach ($field['choices'] as $value => $label) {
        $output .= '<div class="checkbox-option">';
        $output .= '<input type="checkbox" name="' . esc_attr($attributes['field_name']) . '[]" value="' . esc_attr($value) . '" id="' . esc_attr($attributes['field_name']) . '_' . esc_attr($value) . '" class="' . esc_attr($attributes['class']) . '">';
        $output .= '<label for="' . esc_attr($attributes['field_name']) . '_' . esc_attr($value) . '">' . esc_html($label) . '</label>';
        $output .= '</div>';
    }
    
    $output .= '</div>';
    
    return $output;
}
add_shortcode('acf_checkbox_field', 'render_acf_checkbox_field_shortcode');

/*=====Create Radio ACF Field========*/

function render_acf_radio_field_shortcode($atts) {
    // Set up default attributes and merge with user provided ones
    $attributes = shortcode_atts(array(
        'field_name' => '',
        'label' => '',
        'required' => 'false',
        'class' => 'td-form'
    ), $atts);
    
    // Bail if no field name is provided
    if (empty($attributes['field_name'])) {
        return 'Error: field_name parameter is required';
    }
    
    // Get field information directly from ACF
    $field = acf_get_field($attributes['field_name']);
    
    // If that doesn't work, try getting the field object a different way
    if (!$field || empty($field['choices'])) {
        // Try to get field from current post type
        $post_type = get_post_type_object(get_post_type());
        $field = get_field_object($attributes['field_name']);
        
        // For debugging
        if (!$field) {
            return 'Error: Could not find field: ' . $attributes['field_name'] . '. Please check the field name.';
        }
        if (empty($field['choices'])) {
            return 'Error: Field found but no choices available for: ' . $attributes['field_name'];
        }
    }
    
    // Prepare label and required marker
    $label_text = !empty($attributes['label']) ? $attributes['label'] : $field['label'];
    $required_marker = ($attributes['required'] === 'true') ? ' *' : '';
    
    $output = '<div class="acf-radio-group ' . esc_attr($attributes['class']) . '">';
    $output .= '<label class="usp-label">' . esc_html($label_text) . $required_marker . '</label>';
    
    // Generate radio buttons based on ACF field choices
    foreach ($field['choices'] as $value => $label) {
        $output .= '<div class="radio-option">';
        $output .= '<input type="radio" name="' . esc_attr($attributes['field_name']) . '" value="' . esc_attr($value) . '" id="' . esc_attr($attributes['field_name']) . '_' . esc_attr($value) . '" class="' . esc_attr($attributes['class']) . '">';
        $output .= '<label for="' . esc_attr($attributes['field_name']) . '_' . esc_attr($value) . '">' . esc_html($label) . '</label>';
        $output .= '</div>';
    }
    
    $output .= '</div>';
    return $output;
}
add_shortcode('acf_radio_field', 'render_acf_radio_field_shortcode');

/*======Create ACF Select Shortcode=====*/

function render_acf_select_field_shortcode($atts) {
    // Set up default attributes and merge with user provided ones
    $attributes = shortcode_atts(array(
        'field_name' => '',
        'label' => '',
        'required' => 'false',
        'class' => 'select-form',
        'placeholder' => 'Please select an option'
    ), $atts);
    
    // Bail if no field name is provided
    if (empty($attributes['field_name'])) {
        return 'Error: field_name parameter is required';
    }
    
    // Get field information directly from ACF
    $field = acf_get_field($attributes['field_name']);
    
    // If that doesn't work, try getting the field object a different way
    if (!$field) {
        // Try to get field from current post type
        $field = get_field_object($attributes['field_name']);
        
        // For debugging
        if (!$field) {
            return 'Error: Could not find field: ' . $attributes['field_name'] . '. Please check the field name.';
        }
    }
    
    // Get field choices/options
    $options = array();
    if (!empty($field['choices'])) {
        $options = $field['choices'];
    }
    
    // Prepare label and required marker
    $label_text = !empty($attributes['label']) ? $attributes['label'] : $field['label'];
    $required_marker = ($attributes['required'] === 'true') ? ' *' : '';
    $required_attr = ($attributes['required'] === 'true') ? ' required' : '';
    
    // Generate a unique ID for this select field
    $unique_id = 'acf-select-' . $attributes['field_name'];
    
    // Build the HTML output
    $output = '<div class="acf-select-container ' . esc_attr($attributes['class']) . '-container">';
    $output .= '<label class="usp-label">' . esc_html($label_text) . $required_marker . '</label>';
    
    // Add select-wrapper div for pseudo-element styling
    $output .= '<div class="select-wrapper">';
    
    // Add select field
    $output .= '<select name="' . esc_attr($attributes['field_name']) . '" id="' . esc_attr($unique_id) . '" class="' . esc_attr($attributes['class']) . '"' . $required_attr . '>';
    
    // Add placeholder option
    $output .= '<option value="">' . esc_html($attributes['placeholder']) . '</option>';
    
    // Add options from field choices
    foreach ($options as $value => $label) {
        $output .= '<option value="' . esc_attr($value) . '">' . esc_html($label) . '</option>';
    }
    
    $output .= '</select>';
    
    // Close the select-wrapper div
    $output .= '</div>';
    
    $output .= '</div>'; // Close the container
    
    return $output;
}
add_shortcode('acf_select_field', 'render_acf_select_field_shortcode');


/*====Create Featured Image Upload Shortcode for Hosts*/

function render_acf_image_field_shortcode($atts) {
    // Set up default attributes and merge with user provided ones
    $attributes = shortcode_atts(array(
        'field_name' => '',
        'label' => '',
        'required' => 'false',
        'class' => 'td-form',
        'max_size' => '10MB'
    ), $atts);
    
    // Bail if no field name is provided
    if (empty($attributes['field_name'])) {
        return 'Error: field_name parameter is required';
    }
    
    // Get field information directly from ACF
    $field = acf_get_field($attributes['field_name']);
    
    // If that doesn't work, try getting the field object a different way
    if (!$field) {
        // Try to get field from current post type
        $field = get_field_object($attributes['field_name']);
        
        // For debugging
        if (!$field) {
            return 'Error: Could not find field: ' . $attributes['field_name'] . '. Please check the field name.';
        }
    }
    
    // Prepare label and required marker
    $label_text = !empty($attributes['label']) ? $attributes['label'] : $field['label'];
    $required_marker = ($attributes['required'] === 'true') ? ' *' : '';
    $required_attr = ($attributes['required'] === 'true') ? ' required' : '';
    
    // Generate a unique ID for this upload field
    $unique_id = 'acf-image-' . $attributes['field_name'];
    $drop_zone_id = 'dropzone-' . $attributes['field_name'];
    
    // Build the HTML output
    $output = '<div class="acf-image-upload-container ' . esc_attr($attributes['class']) . '-container">';
    $output .= '<label class="usp-label">' . esc_html($label_text) . $required_marker . '</label>';
    
    // Create the stylized upload wrapper
    $output .= '<div class="custom-file-upload-wrapper">';
    
    // Main upload section at the top
    $output .= '<div class="main-upload-section">';
    $output .= '<div class="upload-icon"><img src="https://cortescommunityhousing.org/wp-content/uploads/2025/04/Default-Image.png" alt="Upload"></div>';
    $output .= '<p class="upload-instructions">Upload your photos</p>';
    $output .= '<p class="upload-subtitle">Choose a file from your computer</p>';
    $output .= '</div>';
    
    // File preview area (hidden initially)
    $output .= '<div class="file-preview-container" style="display:none;"><img id="preview-' . esc_attr($attributes['field_name']) . '" src="" alt="Preview"></div>';
    
    // Drag and drop zone below
    $output .= '<div id="' . esc_attr($drop_zone_id) . '" class="drag-drop-zone">';
    $output .= '<div class="drag-drop-content">';
    $output .= '<div class="drag-icon"><img src="https://cortescommunityhousing.org/wp-content/uploads/2025/04/Upload-Image.png" alt="Drag"></div>';
    $output .= '<p class="drag-instructions">Drag and drop your file here</p>';
    $output .= '<p class="or-separator">or</p>';
    $output .= '<button type="button" class="select-file-button">Select File</button>';
    $output .= '</div>';
    $output .= '</div>';
    
    // The actual file input (hidden, triggered by JS)
    $output .= '<input type="file" name="' . esc_attr($attributes['field_name']) . '" id="' . esc_attr($unique_id) . '" class="hidden-file-input" accept="image/*"' . $required_attr . ' style="display:none;">';
    
    // File information display
    $output .= '<div class="file-info" style="display:none;">';
    $output .= '<p>Selected file: <span class="filename"></span></p>';
    $output .= '<button type="button" class="remove-file">Remove</button>';
    $output .= '</div>';
    
    // Supported formats and size info
    $output .= '<p class="file-restrictions">Supported formats: JPG, JPEG, PNG, GIF<br>Maximum file size: ' . esc_html($attributes['max_size']) . '</p>';
    
    $output .= '</div>'; // Close the wrapper
    $output .= '</div>'; // Close the container
    
    // Enqueue the JavaScript
    wp_enqueue_script('acf-drag-drop-upload');
    
    return $output;
}
add_shortcode('acf_image_field', 'render_acf_image_field_shortcode');


/**
 * Enqueue scripts for the drag-and-drop file upload
 */
function acf_drag_drop_upload_scripts() {
    // Register the script
    wp_register_script(
        'acf-drag-drop-upload',
        '', // No source, we'll add inline
        array('jquery'),
        '1.0',
        true
    );
    
    // Add the JS as inline script
    $script = "
    jQuery(document).ready(function($) {
        $('.drag-drop-zone').each(function() {
            const dropZone = $(this);
            const dropZoneId = dropZone.attr('id');
            const fieldName = dropZoneId.replace('dropzone-', '');
            const fileInput = $('#acf-image-' + fieldName);
            const wrapper = dropZone.closest('.custom-file-upload-wrapper');
            const selectButton = dropZone.find('.select-file-button');
            const dragInstructions = dropZone.find('.drag-instructions');
            const mainUploadSection = wrapper.find('.main-upload-section');
            const filePreview = wrapper.find('.file-preview-container');
            const preview = $('#preview-' + fieldName);
            const fileInfo = wrapper.find('.file-info');
            const filename = fileInfo.find('.filename');
            const removeButton = fileInfo.find('.remove-file');
            
            // Open file dialog when button is clicked
            selectButton.on('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                fileInput.trigger('click');
            });
            
            // Handle file selection
            fileInput.on('change', function(e) {
                e.stopPropagation();
                if (this.files && this.files.length) {
                    handleFiles(this.files);
                }
            });
            
            // Prevent default drag behaviors
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                dropZone[0].addEventListener(eventName, function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                }, false);
                
                document.body.addEventListener(eventName, function(e) {
                    if (!dropZone[0].contains(e.target)) {
                        e.preventDefault();
                    }
                }, false);
            });
            
            // Highlight drop zone when dragging over it
            dropZone[0].addEventListener('dragenter', function() {
                dropZone.addClass('highlight');
            }, false);
            
            dropZone[0].addEventListener('dragover', function() {
                dropZone.addClass('highlight');
            }, false);
            
            dropZone[0].addEventListener('dragleave', function() {
                dropZone.removeClass('highlight');
            }, false);
            
            // Handle dropped files
            dropZone[0].addEventListener('drop', function(e) {
                dropZone.removeClass('highlight');
                const dt = e.dataTransfer;
                const files = dt.files;
                handleFiles(files);
            }, false);
            
            // Remove file when button is clicked
            removeButton.on('click', function() {
                fileInput.val('');
                filePreview.hide();
                fileInfo.hide();
                mainUploadSection.show();
                dropZone.show();
                dropZone.removeClass('has-file');
            });
            
            function handleFiles(files) {
                if (files.length) {
                    const file = files[0];
                    if (file.type.match('image.*')) {
                        uploadFile(file);
                        previewFile(file);
                    } else {
                        alert('Please select an image file (JPG, PNG, GIF)');
                    }
                }
            }
            
            function uploadFile(file) {
                // Update the UI to show the file was selected
                filename.text(file.name);
                fileInfo.show();
                mainUploadSection.hide();
                dropZone.addClass('has-file');
            }
            
            function previewFile(file) {
                const reader = new FileReader();
                reader.onloadend = function() {
                    preview.attr('src', reader.result);
                    filePreview.show();
                };
                reader.readAsDataURL(file);
            }
        });
    });";
    
    wp_add_inline_script('acf-drag-drop-upload', $script);
}
add_action('wp_enqueue_scripts', 'acf_drag_drop_upload_scripts');

/**
 * Custom USP Pro multi-file upload field with drag-and-drop interface
 */

function custom_usp_files_shortcode($atts) {
    // Extract the original shortcode attributes
    $attributes = shortcode_atts(array(
        'min' => '4',
        'max' => '8',
        'types' => 'jpg,jpeg,png,gif',
        'class' => 'td-form',
        'label' => 'Upload Files',
        'required' => 'false',
        'data-required' => 'false',
        'display' => 'multiple'
    ), $atts);
    
    // Generate a unique ID for this upload field
    $unique_id = 'usp-files-' . uniqid();
    $drop_zone_id = 'dropzone-' . uniqid();
    
    // Create the required attribute
    $required_attr = ($attributes['required'] === 'true' || $attributes['data-required'] === 'true') ? ' required' : '';
    $required_marker = ($attributes['required'] === 'true' || $attributes['data-required'] === 'true') ? ' *' : '';
    
    // Build the HTML output
    $output = '<div class="acf-image-upload-container ' . esc_attr($attributes['class']) . '-container">';
    $output .= '<label class="usp-label">' . esc_html($attributes['label']) . $required_marker . '</label>';
    
    // Create the stylized upload wrapper
    $output .= '<div class="custom-file-upload-wrapper">';
    
    // Main upload section at the top
    $output .= '<div class="main-upload-section">';
    $output .= '<div class="upload-icon"><img src="https://cortescommunityhousing.org/wp-content/uploads/2025/04/Default-Image.png" alt="Upload"></div>';
    $output .= '<p class="upload-instructions">Upload your photos</p>';
    $output .= '<p class="upload-subtitle">Choose files from your computer.</p>';
    $output .= '</div>';
    
    // File preview area for multiple files
    $output .= '<div class="file-preview-container multi-preview" style="display:none;"></div>';
    
    // Drag and drop zone below
    $output .= '<div id="' . esc_attr($drop_zone_id) . '" class="drag-drop-zone">';
    $output .= '<div class="drag-drop-content">';
    $output .= '<div class="drag-icon"><img src="https://cortescommunityhousing.org/wp-content/uploads/2025/04/Upload-Image.png" alt="Drag"></div>';
    $output .= '<p class="drag-instructions">Drag and drop your files here</p>';
    $output .= '<p class="or-separator">or</p>';
    $output .= '<button type="button" class="select-file-button">Select Files</button>';
    $output .= '</div>';
    $output .= '</div>';
    
    // The actual USP Pro file input (hidden, triggered by JS)
    $output .= '<div class="hidden-usp-container" style="display:none;">';
    $output .= do_shortcode('[usp_files min="' . $attributes['min'] . '" max="' . $attributes['max'] . '" types="' . $attributes['types'] . '" class="hidden-file-input" required="' . $attributes['required'] . '" data-required="' . $attributes['data-required'] . '" display="' . $attributes['display'] . '"]');
    $output .= '</div>';
    
    // File information display
    $output .= '<div class="file-info multi-info" style="display:none;">';
    $output .= '<p><span class="file-count">0</span> files selected</p>';
    $output .= '<button type="button" class="remove-files">Remove All</button>';
    $output .= '</div>';
    
    // Supported formats and size info
    $output .= '<p class="file-restrictions">Supported formats: ' . esc_html(strtoupper($attributes['types'])) . '<br>Required: ' . $attributes['min'] . '-' . $attributes['max'] . ' images. Maximum file size: 10MB</p>';
    
    $output .= '</div>'; // Close the wrapper
    $output .= '</div>'; // Close the container
    
    // Add custom JavaScript for this particular field
    $output .= '<script>
    jQuery(document).ready(function($) {
        const dropZone = $("#' . esc_attr($drop_zone_id) . '");
        const wrapper = dropZone.closest(".custom-file-upload-wrapper");
        const selectButton = dropZone.find(".select-file-button");
        const uspContainer = wrapper.find(".hidden-usp-container");
        const fileInput = uspContainer.find("input[type=\'file\']");
        const dragInstructions = dropZone.find(".drag-instructions");
        const mainUploadSection = wrapper.find(".main-upload-section");
        const filePreview = wrapper.find(".file-preview-container");
        const fileInfo = wrapper.find(".file-info");
        const fileCount = fileInfo.find(".file-count");
        const removeButton = fileInfo.find(".remove-files");
        const minFiles = ' . $attributes['min'] . ';
        const maxFiles = ' . $attributes['max'] . ';
        let selectedFiles = [];
        
        // Open file dialog when button is clicked
        selectButton.on("click", function(e) {
            e.preventDefault();
            e.stopPropagation();
            fileInput.trigger("click");
        });
        
        // Handle file selection
        fileInput.on("change", function(e) {
            e.stopPropagation();
            if (this.files && this.files.length) {
                handleFiles(Array.from(this.files));
            }
        });
        
        // Prevent default drag behaviors
        ["dragenter", "dragover", "dragleave", "drop"].forEach(eventName => {
            dropZone[0].addEventListener(eventName, function(e) {
                e.preventDefault();
                e.stopPropagation();
            }, false);
            
            document.body.addEventListener(eventName, function(e) {
                if (!dropZone[0].contains(e.target)) {
                    e.preventDefault();
                }
            }, false);
        });
        
        // Highlight drop zone when dragging over it
        dropZone[0].addEventListener("dragenter", function() {
            dropZone.addClass("highlight");
        }, false);
        
        dropZone[0].addEventListener("dragover", function() {
            dropZone.addClass("highlight");
        }, false);
        
        dropZone[0].addEventListener("dragleave", function() {
            dropZone.removeClass("highlight");
        }, false);
        
        // Handle dropped files
        dropZone[0].addEventListener("drop", function(e) {
            dropZone.removeClass("highlight");
            const dt = e.dataTransfer;
            const files = Array.from(dt.files);
            handleFiles(files);
        }, false);
        
        // Remove all files when button is clicked
        removeButton.on("click", function() {
            fileInput.val("");
            filePreview.empty().hide();
            fileInfo.hide();
            mainUploadSection.show();
            dropZone.show();
            dropZone.removeClass("has-file");
            selectedFiles = [];
            updateFileCount();
        });
        
        function handleFiles(files) {
            // Filter for image files only
            const imageFiles = files.filter(file => file.type.match("image.*"));
            
            if (imageFiles.length === 0) {
                alert("Please select image files only (JPG, JPEG, PNG, GIF)");
                return;
            }
            
            // Check if adding these files would exceed the maximum
            if (selectedFiles.length + imageFiles.length > maxFiles) {
                alert("You can only upload a maximum of " + maxFiles + " files. You are trying to add " + imageFiles.length + " files to your existing " + selectedFiles.length + " files.");
                return;
            }
            
            // Add these files to our selected files array - but check for duplicates by name
            const newFiles = [];
            imageFiles.forEach(file => {
                // Check if this file already exists in selectedFiles (by name)
                const isDuplicate = selectedFiles.some(existingFile => 
                    existingFile.name === file.name && 
                    existingFile.size === file.size
                );
                
                if (!isDuplicate) {
                    selectedFiles.push(file);
                    newFiles.push(file);
                }
            });
            
            // Update the UI
            updateFileCount();
            previewFiles(newFiles); // Only preview the new, non-duplicate files
            
            if (selectedFiles.length >= minFiles) {
                // Hide the drag instructions once we have enough files
                mainUploadSection.hide();
                dropZone.addClass("has-file");
                
                // Only hide completely if we\'re at max
                if (selectedFiles.length >= maxFiles) {
                    dropZone.hide();
                }
            }
            
            // Make sure the USP Pro field gets these files
            try {
                // Create a DataTransfer object
                const dataTransfer = new DataTransfer();
                
                // Add all selected files to it
                selectedFiles.forEach(file => {
                    dataTransfer.items.add(file);
                });
                
                // Set the files property of the input element
                fileInput[0].files = dataTransfer.files;
                
                // Don\'t trigger change event again to avoid loop
                // fileInput.trigger("change");
            } catch (e) {
                console.error("Could not set files on input element", e);
            }
        }
        
        function updateFileCount() {
            fileCount.text(selectedFiles.length);
            
            if (selectedFiles.length > 0) {
                fileInfo.show();
            } else {
                fileInfo.hide();
            }
        }
        
        function previewFiles(files) {
            if (files.length === 0) return;
            
            // Make sure preview container is visible
            filePreview.show();
            
            // Create preview for each file
            files.forEach(file => {
                // Check if we already have a preview for this file
                const fileId = file.name + "-" + file.size;
                if (filePreview.find("[data-file-id=\'" + fileId + "\']").length > 0) {
                    return; // Skip if we already have a preview
                }
                
                const reader = new FileReader();
                reader.onloadend = function() {
                    const preview = $("<div class=\'preview-item\' data-file-id=\'" + fileId + "\'><img src=\'" + reader.result + "\' alt=\'Preview\'><span class=\'remove-preview\'>×</span></div>");
                    filePreview.append(preview);
                    
                    // Add click handler to remove individual file
                    preview.find(".remove-preview").on("click", function() {
                        // Find the file in our array
                        const index = selectedFiles.findIndex(f => 
                            f.name === file.name && f.size === file.size
                        );
                        
                        if (index > -1) {
                            selectedFiles.splice(index, 1);
                            preview.remove();
                            updateFileCount();
                            
                            // If we no longer have the minimum files, show the upload interface again
                            if (selectedFiles.length < minFiles) {
                                mainUploadSection.show();
                                dropZone.removeClass("has-file");
                            }
                            
                            // Always show the drop zone if we\'re under max
                            if (selectedFiles.length < maxFiles) {
                                dropZone.show();
                            }
                            
                            // Update the actual file input
                            try {
                                const dataTransfer = new DataTransfer();
                                selectedFiles.forEach(f => {
                                    dataTransfer.items.add(f);
                                });
                                fileInput[0].files = dataTransfer.files;
                            } catch (e) {
                                console.error("Could not update files on input element", e);
                            }
                        }
                    });
                };
                reader.readAsDataURL(file);
            });
        }
    });
    </script>';
    
    return $output;
}
add_shortcode('custom_usp_files', 'custom_usp_files_shortcode');


/*Save ACF Checkboxes to post*/

function save_acf_fields_to_post($post_id) {
    // Get all ACF field groups
    $field_groups = acf_get_field_groups();
    
    foreach ($field_groups as $field_group) {
        // Get fields in this group
        $fields = acf_get_fields($field_group);
        
        if (!$fields) continue;
        
        foreach ($fields as $field) {
            // Process checkbox fields (array values)
            if ($field['type'] === 'checkbox' && isset($_POST[$field['name']]) && is_array($_POST[$field['name']])) {
                update_field($field['name'], $_POST[$field['name']], $post_id);
            }
            // Process select fields (single value)
            elseif ($field['type'] === 'select' && isset($_POST[$field['name']])) {
                // Handle both single and multiple select fields
                if (is_array($_POST[$field['name']])) {
                    update_field($field['name'], $_POST[$field['name']], $post_id);
                } else {
                    update_field($field['name'], sanitize_text_field($_POST[$field['name']]), $post_id);
                }
            }
            // Other field types would be handled here
        }
    }
    
    // Handle image upload for host_featured_image field
    // This needs to happen outside the loop since it's coming from FILES not POST
    handle_image_upload($post_id);
}
add_action('usp_pro_update_post', 'save_acf_fields_to_post');

/**
 * Handle image upload, save to ACF field, and set as featured image
 */
function handle_image_upload($post_id) {
    // Check if we have a file uploaded
    if (!empty($_FILES['host_featured_image']['name'])) {
        // Include necessary files for media handling
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        
        // Handle the upload and insert as attachment
        $attachment_id = media_handle_upload('host_featured_image', $post_id);
        
        if (!is_wp_error($attachment_id)) {
            // Save to ACF field
            update_field('host_featured_image', $attachment_id, $post_id);
            
            // Set as featured image
            set_post_thumbnail($post_id, $attachment_id);
        }
    }
}

/**
 * Tell USP Pro to handle our custom file field
 */
function add_custom_file_field_to_usp($files) {
    $files[] = 'host_featured_image';
    return $files;
}
add_filter('usp_pro_filter_files', 'add_custom_file_field_to_usp');


/*Function to Retrieve USP Pro Gallery Images*/

/**
 * Get gallery images for a post
 * 
 * @param int $post_id The post ID
 * @return array Array of image URLs and attachment IDs
 */
function get_host_gallery_images($post_id = null) {
    if (!$post_id) {
        $post_id = get_the_ID();
    }
    
    // Get attachment IDs from USP Pro files
    $usp_images = get_post_meta($post_id, 'usp-file-ids', true);
    
    // If no images are found, return empty array
    if (empty($usp_images)) {
        return array();
    }
    
    // Convert comma-separated string to array if needed
    if (!is_array($usp_images)) {
        $usp_images = explode(',', $usp_images);
    }
    
    $gallery_images = array();
    
    // Process each image
    foreach ($usp_images as $attachment_id) {
        // Skip featured image if it's in the array
        if (get_post_thumbnail_id($post_id) == $attachment_id) {
            continue;
        }
        
        $image = wp_get_attachment_image_src($attachment_id, 'large');
        $thumbnail = wp_get_attachment_image_src($attachment_id, 'thumbnail');
        
        if ($image) {
            $gallery_images[] = array(
                'id' => $attachment_id,
                'url' => $image[0],
                'thumbnail' => $thumbnail[0],
                'width' => $image[1],
                'height' => $image[2],
                'alt' => get_post_meta($attachment_id, '_wp_attachment_image_alt', true)
            );
        }
    }
    
    return $gallery_images;
}

/*====Shortcode to Display USP Pro Gallery Images*/

/**
 * Shortcode to display host gallery images
 * 
 * @param array $atts Shortcode attributes
 * @return string HTML output
 */
function host_gallery_shortcode($atts) {
    $attributes = shortcode_atts(array(
        'post_id' => null,
        'class' => 'host-gallery',
        'layout' => 'grid' // grid or slider
    ), $atts);
    
    $post_id = $attributes['post_id'] ?: get_the_ID();
    $gallery_images = get_host_gallery_images($post_id);
    
    if (empty($gallery_images)) {
        return '<p>No gallery images available.</p>';
    }
    
    $output = '<div class="' . esc_attr($attributes['class']) . '">';
    
    if ($attributes['layout'] === 'slider') {
        // Slider layout (for Divi slider module)
        foreach ($gallery_images as $image) {
            $output .= '<div class="gallery-item">';
            $output .= '<img src="' . esc_url($image['url']) . '" alt="' . esc_attr($image['alt']) . '" width="' . esc_attr($image['width']) . '" height="' . esc_attr($image['height']) . '">';
            $output .= '</div>';
        }
    } else {
        // Default grid layout
        $output .= '<div class="gallery-grid">';
        foreach ($gallery_images as $image) {
            $output .= '<div class="gallery-item">';
            $output .= '<img src="' . esc_url($image['url']) . '" alt="' . esc_attr($image['alt']) . '" width="' . esc_attr($image['width']) . '" height="' . esc_attr($image['height']) . '">';
            $output .= '</div>';
        }
        $output .= '</div>';
    }
    
    $output .= '</div>';
    
    return $output;
}
add_shortcode('host_gallery', 'host_gallery_shortcode');



//Order Posts Alphabetically on custom taxonomy 'job_category'

function sort_trades_directory_taxonomy_alphabetically( $query ) {
    // Check if it's not the admin area, it's the main query, and it's a job_category taxonomy archive
    if ( !is_admin() && $query->is_main_query() && is_tax( 'job_category' ) ) {
        $query->set( 'orderby', 'title' );
        $query->set( 'order', 'ASC' );
    }
}
add_action( 'pre_get_posts', 'sort_trades_directory_taxonomy_alphabetically' );

// Change Posts Menu & Submenu Titles to News

function rename_posts_menu_label() {
    global $menu, $submenu;

    // Rename 'Posts' in the main menu
    $menu[5][0] = 'News'; // Replace 'Custom Name' with the desired label

    // Optional: Rename submenu items under 'Posts'
    if (isset($submenu['edit.php'])) {
        $submenu['edit.php'][5][0] = 'All News'; // 'All News'
        $submenu['edit.php'][10][0] = 'Add News'; // 'Add News'
    }
}
add_action('admin_menu', 'rename_posts_menu_label');

function rename_post_object_label() {
    global $wp_post_types;
    $labels = &$wp_post_types['post']->labels;
    $labels->name = 'News'; // Replace 'Custom Name' with the desired label
    $labels->singular_name = 'News';
    $labels->add_new = 'Add News';
    $labels->add_new_item = 'Add a News Post';
    $labels->edit_item = 'Edit a News Post';
    $labels->new_item = 'New News Post';
    $labels->view_item = 'View News Post';
    $labels->search_items = 'Search News';
    $labels->not_found = 'No News Post Found';
    $labels->not_found_in_trash = 'No News Post in Trash';
    $labels->all_items = 'All News';
    $labels->menu_name = 'News';
    $labels->name_admin_bar = 'News';
}
add_action('init', 'rename_post_object_label');


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
    $all_link = '<li class="cat-item-all"><a href="https://cortescommunityhousing.org/trades-directory">All</a></li>';

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