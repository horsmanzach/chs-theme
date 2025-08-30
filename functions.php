<?php 

add_action( 'wp_enqueue_scripts', 'chs_assets' ); 

function chs_assets() { 
    wp_enqueue_script( 'theme-scripts', get_stylesheet_directory_uri() . '/js/chs-custom-scripts.js', array( 'jquery' ), '1.0', true );
    
    // USP placeholder images for homeshare listings
    if (is_single() && get_post_type() === 'homeshare-listings') {
        wp_enqueue_script(
            'usp-placeholder-images',
            get_stylesheet_directory_uri() . '/js/usp-placeholder-images.js',
            array('jquery'),
            '1.0',
            true
        );
    }
}

function enqueue_dynamic_image_lightbox() {
    wp_enqueue_script(
        'dynamic-image-lightbox',
        get_stylesheet_directory_uri() . '/js/dynamic-image-lightbox.js',
        array('jquery'),
        '1.0',
        true
    );
}
add_action('wp_enqueue_scripts', 'enqueue_dynamic_image_lightbox');


// ----  Change the password form header text

function custom_password_form_simple() {
    $pwbox_id = rand();
    $form_output = sprintf(
        '<div class="et_password_protected_form">
            <h1>%1$s</h1>
            <p>%2$s</p>
            <form action="%3$s" method="post">
                <p><label for="%4$s">%5$s: </label><input name="post_password" id="%4$s" type="password" size="20" maxlength="20" /></p>
                <p><button type="submit" name="et_divi_submit_button" class="et_submit_button et_pb_button">%6$s</button></p>
            </form>
        </div>',
        'This page is password protected', // Custom header text
        'To view the Member Portal, please enter the password that was emailed to you when you registered:', // Custom paragraph text
        esc_url( site_url( 'wp-login.php?action=postpass', 'login_post' ) ),
        esc_attr( 'pwbox-' . $pwbox_id ),
        esc_html__( 'Password', 'Divi' ),
        esc_html__( 'Submit', 'Divi' )
    );
    
    $output = sprintf(
        '<div class="et_pb_section et_section_regular">
            <div class="et_pb_row">
                <div class="et_pb_column et_pb_column_4_4">
                    %1$s
                </div>
            </div>
        </div>',
        $form_output
    );
    
    return $output;
}
add_filter( 'the_password_form', 'custom_password_form_simple', 9999 );


/* ---- Add Section After Default Password Protect Section */


function add_section_after_password_form( $content ) {
    // Only run on password protected pages
    if ( post_password_required() ) {
        $custom_section = '<div id="password-additional-section">';
        $custom_section .= do_shortcode('[et_pb_section global_module="247456"][/et_pb_section]');
        $custom_section .= '</div> <!-- #password-additional-section -->';
        
        return $content . $custom_section;
    }
    
    return $content;
}
add_filter( 'the_content', 'add_section_after_password_form' );


/**
 * CWM Announcements Relative Time Display
 * Shows "X hours ago", "X days ago", or "X months ago" instead of regular dates
 */

/**
 * Enqueue script for CWM announcements relative time
 */
function enqueue_cwm_announcements_script() {
    // Only load on pages that might have cwm-announcements
    global $post;
    if (is_a($post, 'WP_Post') && (has_shortcode($post->post_content, 'et_pb_blog') || is_post_type_archive('cwm-announcements'))) {
        wp_enqueue_script(
            'cwm-announcements-relative-time',
            get_stylesheet_directory_uri() . '/js/cwm-announcements-relative-time.js',
            array('jquery'),
            '1.0',
            true
        );
        
        // Get all cwm-announcements posts and their dates
        $posts = get_posts(array(
            'post_type' => 'cwm-announcements',
            'numberposts' => -1,
            'post_status' => 'publish'
        ));
        
        $post_dates = array();
        foreach ($posts as $announcement_post) {
            $post_dates[$announcement_post->ID] = get_the_date('c', $announcement_post); // ISO 8601 format for JavaScript
        }
        
        // Localize the script with post data
        wp_localize_script('cwm-announcements-relative-time', 'cwmAnnouncementData', array(
            'postDates' => $post_dates,
            'currentTime' => current_time('c') // ISO 8601 format
        ));
    }
}
add_action('wp_enqueue_scripts', 'enqueue_cwm_announcements_script');

/**
 * Enqueue script for CWM Members join date formatting
 */
function enqueue_cwm_members_script() {
    // Only load on pages that might have cwm-members
    global $post;
    if (is_a($post, 'WP_Post') && (has_shortcode($post->post_content, 'et_pb_blog') || is_post_type_archive('cwm-members'))) {
        wp_enqueue_script(
            'cwm-members-join-date',
            get_stylesheet_directory_uri() . '/js/cwm-members-join-date.js',
            array('jquery'),
            '1.0',
            true
        );
        
        // Get all cwm-members posts and their dates
        $posts = get_posts(array(
            'post_type' => 'cwm-members',
            'numberposts' => -1,
            'post_status' => 'publish'
        ));
        
        $post_dates = array();
        foreach ($posts as $member_post) {
            $post_dates[$member_post->ID] = get_the_date('c', $member_post); // ISO 8601 format for JavaScript
        }
        
        // Localize the script with post data
        wp_localize_script('cwm-members-join-date', 'cwmMemberData', array(
            'postDates' => $post_dates,
            'debug' => false // Set to true for debugging
        ));
    }
}
add_action('wp_enqueue_scripts', 'enqueue_cwm_members_script');


/*=====Pinned Posts on CWM Announcements Post Type=====*/


// 1. Modify the blog query to show pinned posts first
function modify_cwm_announcements_query($query, $args) {
    // Only modify queries for cwm-announcements post type
    if (!isset($args['post_type']) || $args['post_type'] !== 'cwm-announcements') {
        return $query;
    }
    
    // Check if this is the main announcements display (you might want to add a specific check here)
    // For example, check for a specific module class or page
    
    // Step 1: Get all pinned post IDs first
    $pinned_posts = get_posts(array(
        'post_type' => 'cwm-announcements',
        'posts_per_page' => -1,
        'fields' => 'ids',
        'tax_query' => array(
            array(
                'taxonomy' => 'announcements-categories',
                'field'    => 'slug',
                'terms'    => 'Pinned',
            ),
        ),
    ));
    
    // Step 2: Get regular posts (excluding pinned ones)
    $regular_posts_args = array(
        'post_type' => 'cwm-announcements',
        'posts_per_page' => $args['posts_per_page'] ? max(0, $args['posts_per_page'] - count($pinned_posts)) : -1,
        'orderby' => 'date',
        'order' => 'DESC',
        'post_status' => 'publish',
    );
    
    // Exclude pinned posts from regular posts
    if (!empty($pinned_posts)) {
        $regular_posts_args['post__not_in'] = $pinned_posts;
    }
    
    $regular_posts = get_posts($regular_posts_args);
    
    // Step 3: Get pinned posts with proper order (you can customize this order)
    $pinned_posts_objects = array();
    if (!empty($pinned_posts)) {
        $pinned_posts_objects = get_posts(array(
            'post_type' => 'cwm-announcements',
            'posts_per_page' => -1,
            'post__in' => $pinned_posts,
            'orderby' => 'menu_order date', // You can customize this ordering
            'order' => 'ASC DESC', // Menu order ASC, then date DESC
        ));
    }
    
    // Step 4: Combine posts (pinned first, then regular)
    $combined_posts = array_merge($pinned_posts_objects, $regular_posts);
    
    // Step 5: Create a new WP_Query with the combined posts
    if (!empty($combined_posts)) {
        $post_ids = wp_list_pluck($combined_posts, 'ID');
        $new_query = new WP_Query(array(
            'post_type' => 'cwm-announcements',
            'post__in' => $post_ids,
            'orderby' => 'post__in', // Maintain the order we specified
            'posts_per_page' => count($post_ids),
        ));
        
        return $new_query;
    }
    
    return $query;
}
add_filter('et_builder_blog_query', 'modify_cwm_announcements_query', 10, 2);


// 3. Add custom field for pinned post ordering
function add_pinned_order_meta_box() {
    add_meta_box(
        'pinned_order',
        'Pinned Post Order',
        'pinned_order_callback',
        'cwm-announcements',
        'side',
        'default'
    );
}
add_action('add_meta_boxes', 'add_pinned_order_meta_box');

function pinned_order_callback($post) {
    wp_nonce_field('save_pinned_order', 'pinned_order_nonce');
    $value = get_post_meta($post->ID, '_pinned_order', true);
    ?>
    <p>
        <label for="pinned_order">Order (lower numbers appear first):</label><br>
        <input type="number" id="pinned_order" name="pinned_order" value="<?php echo esc_attr($value); ?>" min="0" step="1" style="width: 100%;">
    </p>
    <p><small>Only applies to posts in the "Pinned" category. Leave blank for default ordering.</small></p>
    <?php
}

function save_pinned_order($post_id) {
    if (!isset($_POST['pinned_order_nonce']) || !wp_verify_nonce($_POST['pinned_order_nonce'], 'save_pinned_order')) {
        return;
    }
    
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    
    if (isset($_POST['pinned_order'])) {
        update_post_meta($post_id, '_pinned_order', sanitize_text_field($_POST['pinned_order']));
    } else {
        delete_post_meta($post_id, '_pinned_order');
    }
}
add_action('save_post', 'save_pinned_order');

// 4. Optional: Add visual indicator in admin for pinned posts
function add_pinned_column($columns) {
    $columns['pinned'] = 'Pinned';
    return $columns;
}
add_filter('manage_cwm-announcements_posts_columns', 'add_pinned_column');

function show_pinned_column($column, $post_id) {
    if ($column === 'pinned') {
        $terms = get_the_terms($post_id, 'announcements-categories');
        if ($terms && !is_wp_error($terms)) {
            foreach ($terms as $term) {
                if ($term->slug === 'pinned') { // WordPress creates lowercase slugs
                    echo '<span style="color: #d63638; font-weight: bold;">📌 PINNED</span>';
                    $order = get_post_meta($post_id, '_pinned_order', true);
                    if ($order) {
                        echo '<br><small>Order: ' . $order . '</small>';
                    }
                    return;
                }
            }
        }
        echo '—';
    }
}
add_action('manage_cwm-announcements_posts_custom_column', 'show_pinned_column', 10, 2);

// 6. Add class to pinned posts for styling
function add_pinned_post_class($classes, $class, $post_id) {
    if (get_post_type($post_id) === 'cwm-announcements') {
        $terms = get_the_terms($post_id, 'announcements-categories');
        if ($terms && !is_wp_error($terms)) {
            foreach ($terms as $term) {
                if ($term->slug === 'pinned') {
                    $classes[] = 'pinned-announcement';
                    break;
                }
            }
        }
    }
    return $classes;
}
add_filter('post_class', 'add_pinned_post_class', 10, 3);





/*CWM Member Count as a Shortcode*/

/**
 * Simple Active Members Count Shortcode
 * Displays the total number of published cwm-members posts
 * 
 * Usage: [active_members]
 * Output: "8 active members" or "1 active member"
 */

function cwm_active_members_shortcode() {
    // Count published cwm-members posts
    $member_count = wp_count_posts('cwm-members');
    $published_count = isset($member_count->publish) ? intval($member_count->publish) : 0;
    
    // Handle singular vs plural
    if ($published_count === 1) {
        $output = '1 active member';
    } else {
        $output = $published_count . ' active members';
    }
    
    return $output;
}
add_shortcode('active_members', 'cwm_active_members_shortcode');

/**
 * SAFE Resource Category Filter - PHP Only
 * Adds taxonomy term classes to Resource posts for CSS filtering
 * No AJAX, No loops, No server stress
 */

// Add taxonomy classes to Resource posts only
function add_resource_taxonomy_classes_safe($classes, $class, $post_id) {
    // Only run for Resources post type
    if (get_post_type($post_id) === 'housing-policy') {
        // Get terms from the resource-categories taxonomy
        $terms = get_the_terms($post_id, 'resource-categories');
        
        if (!empty($terms) && !is_wp_error($terms)) {
            foreach ($terms as $term) {
                // Add class with 'resource-term-' prefix to avoid conflicts
                $classes[] = 'resource-term-' . $term->slug;
            }
        }
    }
    return $classes;
}
add_filter('post_class', 'add_resource_taxonomy_classes_safe', 10, 3);



/**
 * PRODUCTION CODE: Approval Email Fix for USP Pro
 * This fixes the issue where approval emails weren't being sent
 */

/**
 * Set proper From email address for all WordPress emails
 */
function custom_wp_mail_from($original_email_address) {
    // Change default WordPress email to our custom address
    if ($original_email_address === 'wordpress@' . $_SERVER['SERVER_NAME'] || 
        $original_email_address === 'wordpress@cortescommunityhousing.org') {
        return 'info@cortescommunityhousing.org';
    }
    return $original_email_address;
}
add_filter('wp_mail_from', 'custom_wp_mail_from');

/**
 * Set proper From name for all WordPress emails
 */
function custom_wp_mail_from_name($original_email_from) {
    // Change default WordPress name to our organization name
    if ($original_email_from === 'WordPress' || empty($original_email_from)) {
        return 'Cortes Community Housing';
    }
    return $original_email_from;
}
add_filter('wp_mail_from_name', 'custom_wp_mail_from_name');

/**
 * Send approval email when homeshare listing is approved
 */
function send_homeshare_approval_email($post_id) {
    // Get email address from USP Pro meta
    $email = get_post_meta($post_id, 'usp-email', true);
    if (empty($email)) {
        return false;
    }
    
    // Get approval email content from USP Pro meta
    $subject = get_post_meta($post_id, 'usp-alert-approval-subject', true);
    $message = get_post_meta($post_id, 'usp-alert-approval-message', true);
    
    if (empty($subject) || empty($message)) {
        return false;
    }
    
    // Process message for proper HTML display
    $message = html_entity_decode($message);
    $message = wpautop($message);
    $message = make_clickable($message);
    
    // Create HTML email template
    $html_message = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>' . esc_html($subject) . '</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .email-container { max-width: 600px; margin: 0 auto; padding: 20px; }
            h1 { color: #2c5aa0; }
            a { color: #2c5aa0; }
        </style>
    </head>
    <body>
        <div class="email-container">
            ' . $message . '
        </div>
    </body>
    </html>';
    
    // Set headers for HTML email
    $headers = array(
        'Content-Type: text/html; charset=UTF-8',
        'From: Cortes Community Housing <info@cortescommunityhousing.org>'
    );
    
    // Send the email
    return wp_mail($email, $subject, $html_message, $headers);
}

/**
 * Automatically send approval emails when posts are published
 */
add_action('transition_post_status', function($new_status, $old_status, $post) {
    // Only for homeshare-listings going from pending to published
    if ($post->post_type === 'homeshare-listings' && 
        $old_status === 'pending' && 
        $new_status === 'publish') {
        
        // Send the approval email
        send_homeshare_approval_email($post->ID);
    }
}, 10, 3);

/**
 * Automatically add approval emails to new USP Pro submissions
 * This ensures future submissions will have the approval email functionality
 */
add_action('wp_insert_post', function($post_id, $post, $update) {
    // Only for new homeshare-listings posts from USP Pro
    if ($post->post_type === 'homeshare-listings' && 
        isset($_POST['usp-form-submitted']) && 
        !$update) {
        
        // Check if approval email content exists in meta
        $approval_subject = get_post_meta($post_id, 'usp-alert-approval-subject', true);
        $approval_message = get_post_meta($post_id, 'usp-alert-approval-message', true);
        
        // If approval email meta exists, the system is working correctly
        // If not, we could add fallback logic here if needed
    }
}, 20, 3);


/**
 * Clean and format rent field data
 */

/**
 * Remove non-numeric characters from rent field when saving
 */
function clean_rent_field_on_save($value, $post_id, $field) {
    // Only process the 'rent' field
    if ($field['name'] !== 'rent') {
        return $value;
    }
    
    // Remove all non-numeric characters (letters, symbols, spaces, etc.)
    $cleaned_value = preg_replace('/[^0-9]/', '', $value);
    
    // Return empty string if no numbers found, otherwise return the cleaned number
    return empty($cleaned_value) ? '' : $cleaned_value;
}
add_filter('acf/update_value/name=rent', 'clean_rent_field_on_save', 10, 3);

/**
 * Display rent with dollar sign on frontend
 */
function format_rent_display($value, $post_id, $field) {
    // Only process the 'rent' field
    if ($field['name'] !== 'rent') {
        return $value;
    }
    
    // Only format for frontend display (not admin)
    if (is_admin()) {
        return $value;
    }
    
    // If value is empty or zero, return empty
    if (empty($value) || $value === '0') {
        return '';
    }
    
    // Add dollar sign and format with commas for thousands
    return '$' . number_format((int)$value);
}
add_filter('acf/format_value/name=rent', 'format_rent_display', 10, 3);

/**
 * Alternative method: Create a function to get formatted rent value
 * Use this in templates with: echo get_formatted_rent($post_id);
 */
function get_formatted_rent($post_id = null) {
    if (!$post_id) {
        $post_id = get_the_ID();
    }
    
    $rent = get_field('rent', $post_id);
    
    if (empty($rent) || $rent === '0') {
        return '';
    }
    
    // Clean any remaining non-numeric characters and format
    $cleaned_rent = preg_replace('/[^0-9]/', '', $rent);
    
    if (empty($cleaned_rent)) {
        return '';
    }
    
    return '$' . number_format((int)$cleaned_rent);
}

/**
 * Shortcode to display formatted rent
 * Usage: [display_rent] or [display_rent post_id="123"]
 */
function display_rent_shortcode($atts) {
    $attributes = shortcode_atts(array(
        'post_id' => get_the_ID()
    ), $atts);
    
    return get_formatted_rent($attributes['post_id']);
}
add_shortcode('display_rent', 'display_rent_shortcode');


/**
 * Clean USP Pro ACF Checkbox Fix - Production Ready
 * Automatically fixes USP Pro's duplicate meta entries for ACF checkbox fields
 */

/**
 * Global variable to store the current USP post ID
 */
global $current_usp_post_id;
$current_usp_post_id = null;

/**
 * The core fix function
 */
function immediate_checkbox_fix($post_id) {
    // List of checkbox fields to fix (includes both host and guest form fields)
    $checkbox_fields = array(
        'amenities', 
        'subsidy_tasks', 
        'sharing_home_with', 
        'represents_you', 
        'secondary_housing_arrangements',
        'guest_secondary_rental_type',
        'guest_tasks',
        'guest_represents_you'
    );
    
    $fixed_any = false;
    
    foreach ($checkbox_fields as $field_name) {
        // Check current state of the field
        $current_meta = get_post_meta($post_id, $field_name, false);
        
        // Check if we have the broken pattern (multiple entries with single values)
        if (count($current_meta) > 1) {
            // Get all unique values from all entries
            $all_values = array();
            foreach ($current_meta as $meta_entry) {
                if (is_array($meta_entry)) {
                    $all_values = array_merge($all_values, $meta_entry);
                } else {
                    $all_values[] = $meta_entry;
                }
            }
            
            // Remove duplicates and empty values
            $unique_values = array_unique(array_filter($all_values));
            
            if (!empty($unique_values)) {
                // Delete all existing entries
                global $wpdb;
                $wpdb->delete(
                    $wpdb->postmeta,
                    array(
                        'post_id' => $post_id,
                        'meta_key' => $field_name
                    ),
                    array('%d', '%s')
                );
                
                // Save the consolidated data correctly
                update_field($field_name, $unique_values, $post_id);
                $fixed_any = true;
            }
        }
    }
    
    return $fixed_any;
}

/**
 * Capture post ID immediately when post is created
 */
add_action('wp_insert_post', function($post_id, $post, $update) {
    // Only for new homeshare-listings posts from USP Pro
    if ($post->post_type === 'homeshare-listings' && isset($_POST['usp-form-submitted']) && !$update) {
        global $current_usp_post_id;
        $current_usp_post_id = $post_id;
        
        // Schedule multiple fix attempts with different delays
        wp_schedule_single_event(time() + 2, 'delayed_checkbox_fix', array($post_id, 'delay2'));
        wp_schedule_single_event(time() + 5, 'delayed_checkbox_fix', array($post_id, 'delay5'));
        wp_schedule_single_event(time() + 10, 'delayed_checkbox_fix', array($post_id, 'delay10'));
    }
}, 10, 3);

/**
 * Register the custom cron event
 */
add_action('delayed_checkbox_fix', function($post_id, $delay_tag = 'default') {
    immediate_checkbox_fix($post_id);
});

/**
 * Enhanced save_post hook
 */
add_action('save_post', function($post_id, $post, $update) {
    // Only for homeshare-listings posts from USP Pro
    if ($post->post_type !== 'homeshare-listings' || !isset($_POST['usp-form-submitted'])) {
        return;
    }
    
    if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
        return;
    }
    
    global $current_usp_post_id;
    $current_usp_post_id = $post_id;
    
    // Add shutdown hooks with different priorities
    add_action('shutdown', function() use ($post_id) {
        immediate_checkbox_fix($post_id);
    }, 1);
    
    add_action('shutdown', function() use ($post_id) {
        immediate_checkbox_fix($post_id);
    }, 999);
    
}, 9999, 3);

/**
 * Enhanced USP Pro hook with post ID detection and delayed execution
 */
add_action('usp_submit_post_after', function($post_data) {
    // Try multiple ways to get the post ID
    $post_id = null;
    
    // Method 1: Array with ID key
    if (is_array($post_data) && isset($post_data['ID'])) {
        $post_id = $post_data['ID'];
    }
    // Method 2: Direct numeric value
    elseif (is_numeric($post_data)) {
        $post_id = intval($post_data);
    }
    // Method 3: String that contains a number
    elseif (is_string($post_data) && is_numeric($post_data)) {
        $post_id = intval($post_data);
    }
    // Method 4: Array with different key structure
    elseif (is_array($post_data)) {
        if (isset($post_data['post_id'])) {
            $post_id = $post_data['post_id'];
        } elseif (isset($post_data['id'])) {
            $post_id = $post_data['id'];
        }
    }
    
    // Method 5: Use global variable if set
    if (!$post_id) {
        global $current_usp_post_id;
        if ($current_usp_post_id) {
            $post_id = $current_usp_post_id;
        }
    }
    
    // Method 6: Get the most recent post as fallback
    if (!$post_id) {
        global $wpdb;
        $post_id = $wpdb->get_var(
            "SELECT ID FROM {$wpdb->posts} 
             WHERE post_type = 'homeshare-listings' 
             AND post_status IN ('publish', 'pending', 'draft')
             ORDER BY post_date DESC 
             LIMIT 1"
        );
    }
    
    if ($post_id && is_numeric($post_id)) {
        // Schedule delayed fixes
        wp_schedule_single_event(time() + 2, 'delayed_checkbox_fix', array($post_id, 'usp_hook_2sec'));
        wp_schedule_single_event(time() + 5, 'delayed_checkbox_fix', array($post_id, 'usp_hook_5sec'));
        wp_schedule_single_event(time() + 10, 'delayed_checkbox_fix', array($post_id, 'usp_hook_10sec'));
        
        // Also add a shutdown hook as backup
        add_action('shutdown', function() use ($post_id) {
            sleep(1);
            immediate_checkbox_fix($post_id);
        }, 999);
    }
    
}, 999);

/**
 * Meta update hook to catch data as it's being saved
 */
add_action('updated_post_meta', function($meta_id, $post_id, $meta_key, $meta_value) {
    // Only process checkbox fields from USP Pro forms (host and guest)
    $checkbox_fields = array(
        'amenities', 
        'subsidy_tasks', 
        'sharing_home_with', 
        'represents_you', 
        'secondary_housing_arrangements',
        'guest_secondary_rental_type',
        'guest_tasks',
        'guest_represents_you'
    );
    
    if (in_array($meta_key, $checkbox_fields) && isset($_POST['usp-form-submitted'])) {
        // Store the post ID globally
        global $current_usp_post_id;
        $current_usp_post_id = $post_id;
        
        // Add a shutdown action to fix after all meta is saved
        add_action('shutdown', function() use ($post_id) {
            immediate_checkbox_fix($post_id);
        }, 999);
    }
}, 10, 4);

/**
 * Alternative approach: Hook into ACF's save process
 */
add_action('acf/save_post', function($post_id) {
    // Check if this is from USP Pro
    if (isset($_POST['usp-form-submitted'])) {
        // Run fix with a delay to ensure ACF has finished
        add_action('shutdown', function() use ($post_id) {
            immediate_checkbox_fix($post_id);
        }, 1000);
    }
}, 20);

/**
 * KEEP BULK FIX FUNCTION - DO NOT REMOVE
 * Manual bulk fix function for testing/backup
 */
add_action('init', function() {
    if (isset($_GET['bulk_fix_all']) && current_user_can('manage_options')) {
        
        echo '<h2>Bulk Fix All Posts</h2>';
        
        $posts = get_posts(array(
            'post_type' => 'homeshare-listings',
            'numberposts' => -1,
            'post_status' => 'any'
        ));
        
        $fixed_count = 0;
        $checked_count = 0;
        
        foreach ($posts as $post) {
            $checked_count++;
            
            $current_meta = get_post_meta($post->ID, 'amenities', false);
            
            echo '<h4>Post ' . $post->ID . ' (' . $post->post_title . ')</h4>';
            echo '<p>Meta entries found: ' . count($current_meta) . '</p>';
            
            // Only fix posts with duplicate entries
            if (count($current_meta) > 1) {
                echo '<p>Fixing multiple entries...</p>';
                
                // Apply the same fix logic
                $all_values = array();
                foreach ($current_meta as $meta_entry) {
                    if (is_array($meta_entry)) {
                        $all_values = array_merge($all_values, $meta_entry);
                    } else {
                        $all_values[] = $meta_entry;
                    }
                }
                
                $unique_values = array_unique(array_filter($all_values));
                
                if (!empty($unique_values)) {
                    // Delete all existing
                    global $wpdb;
                    $wpdb->delete(
                        $wpdb->postmeta,
                        array(
                            'post_id' => $post->ID,
                            'meta_key' => 'amenities'
                        ),
                        array('%d', '%s')
                    );
                    
                    // Save correctly
                    $result = update_field('amenities', $unique_values, $post->ID);
                    
                    $fixed_count++;
                    echo '<p style="color: green;">✅ Fixed with values: ' . implode(', ', $unique_values) . '</p>';
                } else {
                    echo '<p style="color: red;">❌ No valid values to save</p>';
                }
            } elseif (count($current_meta) === 1) {
                $single_entry = $current_meta[0];
                if (is_array($single_entry) && count($single_entry) === 1) {
                    echo '<p style="color: orange;">⚠️ Single entry with one value: ' . $single_entry[0] . '</p>';
                }
            } else {
                echo '<p>No amenities meta found</p>';
            }
            
            echo '<hr>';
        }
        
        echo '<h3>Summary:</h3>';
        echo '<p>Checked: ' . $checked_count . ' posts</p>';
        echo '<p>Fixed: ' . $fixed_count . ' posts</p>';
        
        wp_die();
    }
});


/**
 * Clean USP Pro Email to ACF Field Sync - Production Ready
 * Automatically populates ACF email fields with values from usp_email shortcode
 * Now supports both homeshare-listings and trades-directory post types
 */

/**
 * Main function to sync USP email to ACF email fields
 */
function sync_usp_email_to_acf($post_id) {
    // Get the post type for this post
    $post_type = get_post_type($post_id);
    
    // Define ACF email field names based on post type
    $acf_email_fields = array();
    
    if ($post_type === 'homeshare-listings') {
        $acf_email_fields = array(
            'host_email',
            'guest_email', 
            'interested_host_email',
            'interested_guest_email'
        );
    } elseif ($post_type === 'trades-directory') {
        $acf_email_fields = array(
            'email'
        );
    } else {
        // Not a supported post type
        return false;
    }
    
    // Get the email value from USP Pro (common meta keys USP Pro uses for email)
    $usp_email_keys = array(
        'usp-email',      // Most common
        'usp_email',      // Alternative format
        'email',          // Simple format
        'user_email'      // Another possibility
    );
    
    $email_value = null;
    
    // Try to find the email value from USP Pro meta
    foreach ($usp_email_keys as $email_key) {
        $email_value = get_post_meta($post_id, $email_key, true);
        if (!empty($email_value) && is_email($email_value)) {
            break; // Found a valid email
        }
    }
    
    // If no email found in meta, check if it's in the POST data
    if (empty($email_value) && isset($_POST['usp-email'])) {
        $email_value = sanitize_email($_POST['usp-email']);
    }
    
    // If we found an email value, populate ALL ACF email fields (let ACF handle which ones exist)
    if (!empty($email_value) && is_email($email_value)) {
        
        $updated_any = false;
        
        // Try to update each potential ACF email field
        foreach ($acf_email_fields as $field_name) {
            
            // Get current value
            $current_value = get_field($field_name, $post_id);
            
            // Only update if the field is empty or different
            if ($current_value !== $email_value) {
                
                // Try updating with ACF's update_field function
                $result = update_field($field_name, $email_value, $post_id);
                
                if ($result) {
                    $updated_any = true;
                }
                
                // Also try direct meta update as backup
                update_post_meta($post_id, $field_name, $email_value);
                
                // And try with ACF's internal meta key format
                update_post_meta($post_id, '_' . $field_name, 'field_' . $field_name);
            }
        }
        
        return $updated_any;
    }
    
    return false;
}

/**
 * Hook into post creation to sync email immediately
 */
add_action('wp_insert_post', function($post_id, $post, $update) {
    // Only for supported post types from USP Pro
    if (($post->post_type === 'homeshare-listings' || $post->post_type === 'trades-directory') && isset($_POST['usp-form-submitted'])) {
        
        // Schedule delayed email sync to ensure USP Pro has saved the email data
        wp_schedule_single_event(time() + 2, 'delayed_email_sync', array($post_id, 'delay2'));
        wp_schedule_single_event(time() + 5, 'delayed_email_sync', array($post_id, 'delay5'));
        
    }
}, 10, 3);

/**
 * Register the delayed email sync event
 */
add_action('delayed_email_sync', function($post_id, $delay_tag = 'default') {
    sync_usp_email_to_acf($post_id);
});

/**
 * Hook into save_post for additional coverage
 */
add_action('save_post', function($post_id, $post, $update) {
    // Only for supported post types from USP Pro
    if (($post->post_type !== 'homeshare-listings' && $post->post_type !== 'trades-directory') || !isset($_POST['usp-form-submitted'])) {
        return;
    }
    
    if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
        return;
    }
    
    // Add shutdown hook to sync email after everything is saved
    add_action('shutdown', function() use ($post_id) {
        sleep(1); // Brief delay to ensure USP Pro has finished
        sync_usp_email_to_acf($post_id);
    }, 500);
    
}, 9999, 3);

/**
 * Hook into USP Pro's completion
 */
add_action('usp_submit_post_after', function($post_data) {
    // Get post ID using same logic as checkbox fix
    $post_id = null;
    
    if (is_array($post_data) && isset($post_data['ID'])) {
        $post_id = $post_data['ID'];
    } elseif (is_numeric($post_data)) {
        $post_id = intval($post_data);
    } elseif (is_string($post_data) && is_numeric($post_data)) {
        $post_id = intval($post_data);
    } elseif (is_array($post_data)) {
        if (isset($post_data['post_id'])) {
            $post_id = $post_data['post_id'];
        } elseif (isset($post_data['id'])) {
            $post_id = $post_data['id'];
        }
    }
    
    // Fallback: get most recent post from either supported post type
    if (!$post_id) {
        global $wpdb;
        $post_id = $wpdb->get_var(
            "SELECT ID FROM {$wpdb->posts} 
             WHERE post_type IN ('homeshare-listings', 'trades-directory')
             AND post_status IN ('publish', 'pending', 'draft')
             ORDER BY post_date DESC 
             LIMIT 1"
        );
    }
    
    if ($post_id && is_numeric($post_id)) {
        // Schedule delayed email sync
        wp_schedule_single_event(time() + 2, 'delayed_email_sync', array($post_id, 'usp_hook'));
        
        // Also add shutdown hook as backup
        add_action('shutdown', function() use ($post_id) {
            sleep(1);
            sync_usp_email_to_acf($post_id);
        }, 600);
    }
    
}, 999);

/**
 * Hook into meta updates to catch email when it's saved
 */
add_action('updated_post_meta', function($meta_id, $post_id, $meta_key, $meta_value) {
    // Check if this is an email meta update from USP Pro
    $email_meta_keys = array('usp-email', 'usp_email', 'email', 'user_email');
    
    if (in_array($meta_key, $email_meta_keys) && isset($_POST['usp-form-submitted'])) {
        // Check if this is a supported post type
        $post_type = get_post_type($post_id);
        if ($post_type === 'homeshare-listings' || $post_type === 'trades-directory') {
            // Add shutdown action to sync after all meta is saved
            add_action('shutdown', function() use ($post_id) {
                sync_usp_email_to_acf($post_id);
            }, 700);
        }
    }
}, 10, 4);

/**
 * Alternative approach: Hook into ACF's save process
 */
add_action('acf/save_post', function($post_id) {
    // Check if this is from USP Pro
    if (isset($_POST['usp-form-submitted'])) {
        // Check if this is a supported post type
        $post_type = get_post_type($post_id);
        if ($post_type === 'homeshare-listings' || $post_type === 'trades-directory') {
            // Run fix with a delay to ensure ACF has finished
            add_action('shutdown', function() use ($post_id) {
                sync_usp_email_to_acf($post_id);
            }, 1000);
        }
    }
}, 20);

/**
 * Manual email sync function for backup/testing
 * Usage: https://yoursite.com/?sync_emails=1
 */
add_action('init', function() {
    if (isset($_GET['sync_emails']) && current_user_can('manage_options')) {
        
        $posts = get_posts(array(
            'post_type' => array('homeshare-listings', 'trades-directory'),
            'numberposts' => -1,
            'post_status' => 'any'
        ));
        
        $synced_count = 0;
        
        foreach ($posts as $post) {
            $result = sync_usp_email_to_acf($post->ID);
            if ($result) {
                $synced_count++;
            }
        }
        
        echo '<h2>Email Sync Complete</h2>';
        echo '<p>Processed: ' . count($posts) . ' posts</p>';
        echo '<p>Synced: ' . $synced_count . ' posts</p>';
        
        wp_die();
    }
});



/**
 * Set default featured image for all posts when no featured image is set
 */
 function set_default_featured_image( $post_id ) {
    $default_image_url = 'https://cortescommunityhousing.org/wp-content/uploads/2025/06/Placeholder-better.webp'; // Replace with the actual URL
    if ( ! has_post_thumbnail( $post_id ) ) {
        $image_id = attachment_url_to_postid( $default_image_url );
        if ( $image_id ) {
            set_post_thumbnail( $post_id, $image_id );
        }
    }
}
add_action( 'save_post', 'set_default_featured_image' ); 



function ensure_magnific_popup() {
    wp_enqueue_script('magnific-popup', get_site_url() . '/wp-content/themes/Divi/includes/builder/feature/dynamic-assets/assets/js/magnific-popup.js', array('jquery'), null, true);
   /* wp_enqueue_style('magnific-popup-style', get_site_url() . '/wp-content/themes/Divi/includes/builder/feature/dynamic-assets/assets/css/magnific-popup.css'); */
}
add_action('wp_enqueue_scripts', 'ensure_magnific_popup', 99);


/*Enqueue USP File JS Fix*/

/*function enqueue_usp_file_upload_fix() {
    // Only enqueue on pages with USP forms
    if (has_shortcode(get_the_content(), 'usp_form') || 
        has_shortcode(get_the_content(), 'custom_usp_files') || 
        has_shortcode(get_the_content(), 'acf_image_field')) {
        
        wp_enqueue_script(
            'usp-file-uploads-fix',
            get_stylesheet_directory_uri() . '/js/usp-file-uploads-fix.js',
            array('jquery'),
            '1.0.1',
            true // Load in footer
        );
    }
}
add_action('wp_enqueue_scripts', 'enqueue_usp_file_upload_fix');  */

/*Enqueue USP File JS Styling*/

/**
 * Enqueue script for styling USP Pro file uploads
 */
function styled_usp_files_script() {
    // Only load on pages with USP forms
    if (has_shortcode(get_the_content(), 'usp_form')) {
        wp_enqueue_script(
            'styled-usp-files',
            get_stylesheet_directory_uri() . '/js/styled-usp-files.js',
            array('jquery'),
            '1.0',
            true
        );
    }
}
add_action('wp_enqueue_scripts', 'styled_usp_files_script');


/**
 * Fix USP Pro file validation for exactly 4 files
 */
function fix_usp_files_validation($errors, $form_data) {
    // Track file count
    $file_count = 0;
    
    // Count valid files in the submission
    if (isset($_FILES['usp-files']) && isset($_FILES['usp-files']['name'])) {
        foreach ($_FILES['usp-files']['name'] as $key => $filename) {
            if (!empty($filename) && $_FILES['usp-files']['error'][$key] === 0) {
                $file_count++;
            }
        }
    }
    
    // If not enough files, add a custom error
    if ($file_count < 4) {
        $errors['usp_files'] = 'Please upload exactly 4 image files.';
    } else {
        // If we have enough files, remove any file-related errors
        foreach ($errors as $key => $error) {
            if (strpos($key, 'usp-files') !== false || $key === 'usp_files') {
                unset($errors[$key]);
            }
        }
    }
    
    return $errors;
}
add_filter('usp_pro_pre_process_form', 'fix_usp_files_validation', 999, 2);

// Always return true for file validation
add_filter('usp_pro_check_files', '__return_true', 999);


/*====Show all Checkbox CHoices on Front End Shortcode=======*/

// Create a reusable shortcode for displaying checkbox fields with selected/unselected icons
function display_checkbox_field_shortcode($atts) {
    // Define default attributes
    $attributes = shortcode_atts(
        array(
            'field' => 'represents_you',     // Default field name
            'title' => '',              // Optional title
            'columns' => 4,             // Number of columns in grid
            'icon_type' => 'default',   // Icon type: 'default' or 'sun'
        ), 
        $atts,
        'display_checkbox_field'
    );
    
    // Get the field name from attributes
    $field_name = $attributes['field'];
    $columns = intval($attributes['columns']);
    $title = $attributes['title'];
    $icon_type = $attributes['icon_type'];
    
    // Get the current post ID
    $post_id = get_the_ID();
    
    // Add error checking
    if (!function_exists('get_field') || !function_exists('get_field_object')) {
        return '<p>Error: ACF functions not available</p>';
    }
    
    // Get the field object with error checking
    $field = get_field_object($field_name);
    if (!$field || !isset($field['choices']) || empty($field['choices'])) {
        return '<p>Error: Could not find the field "' . esc_html($field_name) . '" or its choices</p>';
    }
    
    // Get the selected values for this post
    $selected_values = get_field($field_name, $post_id);
    
    // If nothing is selected, return empty for display_checkbox_field
    if (!is_array($selected_values) || empty($selected_values)) {
        return '<p>No selections for ' . esc_html($field['label']) . '</p>';
    }
    
    // Get all possible choices from the ACF field
    $all_choices = $field['choices'];
    
    // Start building the output with a container
    $output = '<div class="acf-checkbox-display">';
    
    // Add title if provided
    if (!empty($title)) {
        $output .= '<h3 class="checkbox-field-title">' . esc_html($title) . '</h3>';
    }
    
    $output .= '<div class="checkbox-grid" style="grid-template-columns: repeat(' . $columns . ', 1fr);">';
    
    // Only display selected items
    foreach ($selected_values as $value) {
        // Skip if the value doesn't exist in choices
        if (!isset($all_choices[$value])) {
            continue;
        }
        
        $label = $all_choices[$value];
        
        // Set the icon URLs based on the icon_type parameter
        if ($icon_type === 'sun') {
            $selected_icon_url = 'https://cortescommunityhousing.org/wp-content/uploads/2025/05/sun-icon.png';
        } else {
            $selected_icon_url = 'https://cortescommunityhousing.org/wp-content/uploads/2025/05/checkmark-icon.png';
        }
        
        $output .= '<div class="checkbox-item selected">';
        $output .= '<span class="checkbox-icon"><img src="' . esc_url($selected_icon_url) . '" alt="Selected" class="checkbox-icon-img"></span>';
        $output .= '<span class="checkbox-label">' . esc_html($label) . '</span>';
        $output .= '</div>';
    }
    
    $output .= '</div>'; // End checkbox-grid
    $output .= '</div>'; // End acf-checkbox-display
    
    return $output;
}
add_shortcode('display_checkbox_field', 'display_checkbox_field_shortcode');

// Original Amenities function to display all amenities (both selected and unselected)
function display_all_amenities_shortcode($atts = array()) {
    // Allow field parameter to be passed for flexibility
    $attributes = shortcode_atts(
        array(
            'field' => 'amenities',  // Default to amenities but can be overridden
            'title' => '',
            'columns' => 4,
            'icon_type' => 'default',
        ), 
        $atts,
        'display_all_amenities'
    );
    
    // Call the main function with these attributes
    return display_checkbox_field_with_all_options($attributes);
}
add_shortcode('display_all_amenities', 'display_all_amenities_shortcode');

// Add specific shortcode for guest_tasks
function display_all_guest_tasks_shortcode($atts = array()) {
    // Merge passed attributes with defaults
    $attributes = shortcode_atts(
        array(
            'columns' => 4,
            'title' => '',
            'icon_type' => 'default',
        ), 
        $atts,
        'display_all_guest_tasks'
    );
    
    // Force field to be guest_tasks
    $attributes['field'] = 'guest_tasks';
    
    // Call the shared function
    return display_checkbox_field_with_all_options($attributes);
}
add_shortcode('display_all_guest_tasks', 'display_all_guest_tasks_shortcode');

// The core function that displays all options for a checkbox field
function display_checkbox_field_with_all_options($attributes) {
    // Get the field name from attributes
    $field_name = $attributes['field'];
    $columns = intval($attributes['columns']);
    $title = $attributes['title'];
    $icon_type = $attributes['icon_type'];
    
    // Get current post ID
    $post_id = get_the_ID();
    
    // Error checking
    if (!function_exists('get_field') || !function_exists('get_field_object')) {
        return '<p>Error: ACF functions not available</p>';
    }
    
    // Get field object
    $field = get_field_object($field_name);
    if (!$field || !isset($field['choices']) || empty($field['choices'])) {
        return '<p>Error: Could not find the field "' . esc_html($field_name) . '" or its choices</p>';
    }
    
    // Get selected values
    $selected_values = get_field($field_name, $post_id);
    if (!is_array($selected_values)) {
        $selected_values = array();
    }
    
    // Build the output
    $output = '<div class="acf-checkbox-display">';
    
    if (!empty($title)) {
        $output .= '<h3 class="checkbox-field-title">' . esc_html($title) . '</h3>';
    }
    
    $output .= '<div class="checkbox-grid" style="grid-template-columns: repeat(' . $columns . ', 1fr);">';
    
    foreach ($field['choices'] as $value => $label) {
        $is_selected = in_array($value, $selected_values);
        $icon_class = $is_selected ? 'selected' : 'not-selected';
        
        // Set icons based on type
        if ($icon_type === 'sun') {
            $selected_icon_url = 'https://cortescommunityhousing.org/wp-content/uploads/2025/05/sun-icon.png';
            $x_url = 'https://cortescommunityhousing.org/wp-content/uploads/2025/05/x-icon.png';
        } else {
            $selected_icon_url = 'https://cortescommunityhousing.org/wp-content/uploads/2025/05/checkmark-icon.png';
            $x_url = 'https://cortescommunityhousing.org/wp-content/uploads/2025/05/x-icon.png';
        }
        
        $icon_img = $is_selected ? 
            '<img src="' . esc_url($selected_icon_url) . '" alt="Selected" class="checkbox-icon-img">' : 
            '<img src="' . esc_url($x_url) . '" alt="Not selected" class="checkbox-icon-img">';
        
        $output .= '<div class="checkbox-item ' . esc_attr($icon_class) . '">';
        $output .= '<span class="checkbox-icon">' . $icon_img . '</span>';
        $output .= '<span class="checkbox-label">' . esc_html($label) . '</span>';
        $output .= '</div>';
    }
    
    $output .= '</div>'; // End checkbox-grid
    $output .= '</div>'; // End acf-checkbox-display
    
    return $output;
}


// Add a convenience shortcode for sharing_home_with checkbox field
function display_sharing_home_shortcode($atts) {
    // Define default attributes
    $attributes = shortcode_atts(
        array(
            'columns' => 4,         // Number of columns in grid
        ), 
        $atts,
        'display_sharing_home'
    );
    
    // Merge with required parameters
    $params = array(
        'field' => 'sharing_home_with',
        'icon_type' => 'sun',
        'columns' => $attributes['columns'],
    );
    
    // Call the main shortcode function with combined parameters
    return display_checkbox_field_shortcode($params);
}
add_shortcode('display_sharing_home', 'display_sharing_home_shortcode');



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
        'max_size' => '10MB',
        'set_featured' => 'true' // New attribute to control featured image behavior
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
    
    // Add a hidden field to indicate this should be set as featured image
    $featured_field = '';
    if ($attributes['set_featured'] === 'true') {
        $featured_field = '<input type="hidden" name="set_as_featured" value="' . esc_attr($attributes['field_name']) . '">';
    }
    
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
    
    // Add the hidden field for featured image
    $output .= $featured_field;
    
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
 * Handle image upload, save to ACF field, and set as featured image
 * Modified to handle any specified ACF field as featured image
 */
function handle_image_upload($post_id) {
    // First, check if we have a flag to set a specific field as featured image
    if (!empty($_POST['set_as_featured'])) {
        $field_name = $_POST['set_as_featured'];
        
        // Get the image ID from the ACF field (needs to run after ACF saves the field)
        $image_id = get_field($field_name, $post_id);
        
        // Debug - log to error_log if you need to check values
        // error_log('Field name: ' . $field_name . ', Image ID: ' . print_r($image_id, true));
        
        // Check if we have a valid image ID
        if (!empty($image_id)) {
            // If the field stores an array with ID (common in ACF image fields)
            if (is_array($image_id) && isset($image_id['ID'])) {
                $image_id = $image_id['ID'];
            }
            
            // Set as featured image
            set_post_thumbnail($post_id, $image_id);
        }
    }
    
    // Keep the original functionality for direct file uploads
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

/**
 * Make sure our handle_image_upload function runs at the right time
 * Hooking into both ACF save post and USP Pro submission
 */
function acf_set_featured_image_hooks() {
    // Hook into ACF save post action
    add_action('acf/save_post', 'handle_image_upload', 20); // Priority 20 to run after ACF has saved fields
    
    // Hook into USP Pro post submission
    add_action('usp_post_submitted', 'handle_image_upload', 20);
    
    // Alternative hook for USP Pro
    add_filter('usp_update_post', function($post_data) {
        if (isset($post_data['ID'])) {
            handle_image_upload($post_data['ID']);
        }
        return $post_data;
    }, 20);
}
add_action('init', 'acf_set_featured_image_hooks');


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
        'max' => '4',
        'types' => 'jpg,jpeg,png,gif',
        'class' => 'td-form',
        'label' => 'Upload Files',
        'required' => 'true',
        'data-required' => 'true',
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
    
    // The actual file input (truly hidden)
    $output .= '<input type="file" name="usp-files[]" id="' . esc_attr($unique_id) . '" class="usp-input usp-required"' . 
               ' accept=".' . str_replace(',', ',.', $attributes['types']) . '"' .
               ' multiple="multiple"' . $required_attr . 
               ' data-min="' . $attributes['min'] . '" data-max="' . $attributes['max'] . '"' .
               ' style="position:absolute; width:1px; height:1px; overflow:hidden; opacity:0.01; z-index:-1;">';
    
    // File information display
    $output .= '<div class="file-info multi-info" style="display:none;">';
    $output .= '<p><span class="file-count">0</span> files selected</p>';
    $output .= '<button type="button" class="remove-files">Remove All</button>';
    $output .= '</div>';
    
    // Supported formats and size info
    $output .= '<p class="file-restrictions">Supported formats: ' . esc_html(strtoupper($attributes['types'])) . '<br>Required: ' . $attributes['min'] . '-' . $attributes['max'] . ' images. Maximum file size: 10MB</p>';
    
    $output .= '</div>'; // Close the wrapper
    $output .= '</div>'; // Close the container
    
    return $output;
}
add_shortcode('custom_usp_files', 'custom_usp_files_shortcode');

// Force bypass of form validation
function force_accept_files($valid, $form_data) {
    // Always return true to bypass file validation completely
    return true;
}
add_filter('usp_pro_check_files', 'force_accept_files', 1, 2);

// Also add this filter to completely disable file checking
function disable_file_requirement($require) {
    return false;
}
add_filter('usp_require_files', 'disable_file_requirement');

/**
 * Custom form validation to bypass file validation errors
 */
function usp_bypass_file_validation($array) {
    // Check if we have enough files based on our hidden field
    if (isset($_POST['usp_file_count']) && intval($_POST['usp_file_count']) >= 4) {
        // Remove any file validation errors
        if (isset($array['errors']) && is_array($array['errors'])) {
            foreach ($array['errors'] as $key => $error) {
                if (strpos($error, 'file') !== false) {
                    unset($array['errors'][$key]);
                }
            }
        }
        
        // Force the files to pass validation
        $array['files_pass'] = true;
    }
    
    return $array;
}
add_filter('usp_get_field_val', 'usp_bypass_file_validation');

/**
 * Custom error display to handle any remaining file errors
 */
function usp_handle_file_error_messages($string, $key) {
    // If this is a file error but we have the validation bypass flag
    if (strpos($key, 'file') !== false && isset($_POST['usp_pro_files_validated']) && $_POST['usp_pro_files_validated'] === 'true') {
        // Return empty string to hide the error
        return '';
    }
    return $string;
}
add_filter('usp_display_errors_custom', 'usp_handle_file_error_messages', 10, 2);

/**
 * Full bypass for file validation
 */
function usp_skip_file_validation($check_files, $form_data) {
    // If we have enough files according to our counter, bypass validation
    if (isset($_POST['usp_file_count']) && intval($_POST['usp_file_count']) >= 4) {
        return true;
    }
    return $check_files;
}
add_filter('usp_pro_check_files', 'usp_skip_file_validation', 5, 2);

/*--------End of Custom File Uploads-----*/


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
    
    // Try the original meta key
    $usp_images = get_post_meta($post_id, 'usp-file-ids', true);
    
    // If empty, try alternative meta keys that USP Pro might use
    if (empty($usp_images)) {
        $usp_images = get_post_meta($post_id, 'usp-files', true);
    }
    
    if (empty($usp_images)) {
        $usp_images = get_post_meta($post_id, '_usp_images', true);
    }
    
    if (empty($usp_images)) {
        $usp_images = get_post_meta($post_id, 'usp_files', true);
    }
    
    if (empty($usp_images)) {
        $usp_images = get_post_meta($post_id, 'usp_images', true);
    }
    
    // Debug output - you can comment this out once working
     /*echo '<p>Debug - Meta value: ' . (is_array($usp_images) ? json_encode($usp_images) : $usp_images) . '</p>';*/
    
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
        // Skip if not a valid attachment ID
        if (!$attachment_id || !is_numeric($attachment_id)) {
            continue;
        }
        
        // Temporarily comment out this check to include featured image
        // Skip featured image if it's in the array
        // if (get_post_thumbnail_id($post_id) == $attachment_id) {
        //     continue;
        // }
        
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
    
    // Debug count - you can comment this out once working
     echo '<p>Debug - Found ' . count($gallery_images) . ' gallery images</p>';
    
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

//*============================================
//Add logo to login screen
//=============================================*/


add_filter( 'login_headerurl', 'my_login_logo_url' );
function my_login_logo_url() {
    return home_url();
}

add_filter( 'login_headertitle', 'my_login_logo_url_title' );
function my_login_logo_url_title() {
    return 'Cortes Island Housing Society';
}
