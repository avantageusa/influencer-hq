<?php
/**
 * Influencer User Role Management
 * Creates and manages the custom "Influencer" user role
 * 
 * To use: Include this file in your theme's functions.php:
 * require_once get_template_directory() . '/inc/influencer-role.php';
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Create Influencer Role on Theme Activation
 */
function create_influencer_role() {
    // Remove role if it exists to ensure clean setup
    remove_role('influencer');
    
    // Create the influencer role with minimal capabilities
    add_role(
        'influencer',
        'Influencer',
        array(
            // NO dashboard access - remove 'read' capability
            'read' => false,                   // Cannot access WordPress dashboard
            
            // Explicitly deny common capabilities
            'edit_posts' => false,             // Cannot create/edit posts
            'delete_posts' => false,           // Cannot delete posts
            'publish_posts' => false,          // Cannot publish posts
            'edit_pages' => false,             // Cannot edit pages
            'delete_pages' => false,           // Cannot delete pages
            'publish_pages' => false,          // Cannot publish pages
            'edit_others_posts' => false,      // Cannot edit others' posts
            'delete_others_posts' => false,   // Cannot delete others' posts
            'edit_others_pages' => false,     // Cannot edit others' pages
            'delete_others_pages' => false,   // Cannot delete others' pages
            'manage_categories' => false,      // Cannot manage categories
            'manage_links' => false,           // Cannot manage links
            'upload_files' => false,           // Cannot upload files (for now)
            'import' => false,                 // Cannot import content
            'unfiltered_html' => false,        // Cannot use unfiltered HTML
            'edit_comments' => false,          // Cannot edit comments
            'moderate_comments' => false,      // Cannot moderate comments
            'manage_options' => false,         // Cannot access admin settings
            'activate_plugins' => false,       // Cannot activate plugins
            'edit_plugins' => false,           // Cannot edit plugins
            'edit_users' => false,             // Cannot edit users
            'edit_themes' => false,            // Cannot edit themes
            'install_plugins' => false,       // Cannot install plugins
            'install_themes' => false,        // Cannot install themes
            'update_plugins' => false,        // Cannot update plugins
            'update_themes' => false,         // Cannot update themes
            'update_core' => false,            // Cannot update WordPress core
            
            // Custom capabilities for influencer-specific features
            'view_influencer_profile' => true, // Can view their own profile
            'access_influencer_hq' => true,    // Can access the Influencer HQ page
        )
    );
}

/**
 * Remove Influencer Role on Theme Deactivation
 */
function remove_influencer_role() {
    remove_role('influencer');
}

/**
 * Hook role creation to theme activation
 * Note: WordPress doesn't have a direct theme activation hook,
 * so we'll check if the role exists and create it if needed
 */
function init_influencer_role() {
    // Check if role exists, if not create it
    if (!get_role('influencer')) {
        create_influencer_role();
    }
}
add_action('init', 'init_influencer_role');

/**
 * Block admin bar for influencers completely
 */
function hide_admin_bar_for_influencer() {
    $user = wp_get_current_user();
    
    if (in_array('influencer', $user->roles)) {
        // Hide admin bar completely
        show_admin_bar(false);
    }
}
add_action('wp_loaded', 'hide_admin_bar_for_influencer');

/**
 * Block wp-admin URLs completely for influencers
 */
function block_wp_admin_access_for_influencer() {
    $user = wp_get_current_user();
    
    // Never block admins or editors
    if ( $user->has_cap('edit_posts') ) {
        return;
    }

    if (in_array('influencer', $user->roles)) {
        // Block direct access to wp-admin URLs
        if (strpos($_SERVER['REQUEST_URI'], '/wp-admin') !== false && !wp_doing_ajax()) {
            wp_redirect(home_url('/portal/portal-home/'));
            exit;
        }
    }
}
add_action('init', 'block_wp_admin_access_for_influencer');

/**
 * Completely block influencers from accessing any admin/dashboard pages
 */
function block_influencer_from_admin() {
    $user = wp_get_current_user();

    // Never block admins or editors
    if ( $user->has_cap('edit_posts') ) {
        return;
    }
    
    if (in_array('influencer', $user->roles)) {
        // Block ALL admin access
        if (is_admin() && !wp_doing_ajax()) {
            wp_redirect(home_url('/portal/portal-home/'));
            exit;
        }
    }
}
add_action('admin_init', 'block_influencer_from_admin');

/**
 * Remove login redirect to admin for influencers
 */
function redirect_influencer_after_login($redirect_to, $request, $user) {
    if (is_wp_error($user)) {
        return $redirect_to;
    }

    // Never redirect admins or editors away from where they intended to go
    if ( $user->has_cap('edit_posts') ) {
        return $redirect_to;
    }
    
    if (in_array('influencer', $user->roles)) {
        return home_url('/portal/portal-home/');
    }
    
    return $redirect_to;
}
add_filter('login_redirect', 'redirect_influencer_after_login', 10, 3);

/**
 * Register Custom Post Type: Challenges
 */
function register_challenges_post_type() {
    $labels = array(
        'name'                  => 'Challenges',
        'singular_name'         => 'Challenge',
        'menu_name'             => 'Challenges',
        'name_admin_bar'        => 'Challenge',
        'add_new'               => 'Add New',
        'add_new_item'          => 'Add New Challenge',
        'new_item'              => 'New Challenge',
        'edit_item'             => 'Edit Challenge',
        'view_item'             => 'View Challenge',
        'all_items'             => 'All Challenges',
        'search_items'          => 'Search Challenges',
        'not_found'             => 'No challenges found.',
        'not_found_in_trash'    => 'No challenges found in Trash.'
    );

    $args = array(
        'labels'                => $labels,
        'public'                => false,
        'publicly_queryable'    => true,
        'show_ui'               => true,
        'show_in_menu'          => true,
        'query_var'             => true,
        'rewrite'               => array('slug' => 'challenge'),
        'capability_type'       => 'post',
        'has_archive'           => false,
        'hierarchical'          => false,
        'menu_position'         => 25,
        'menu_icon'             => 'dashicons-awards',
        'supports'              => array('title'),
        'show_in_rest'          => true,
    );

    register_post_type('challenge', $args);
}
add_action('init', 'register_challenges_post_type');

/**
 * AJAX Handler for challenge submission
 */
add_action('wp_ajax_submit_challenge', 'handle_challenge_submission');

function handle_challenge_submission() {
    // Verify nonce for security
    check_ajax_referer('challenge_nonce', 'nonce');
    
    // Get POST data
    $challenger_id = intval($_POST['challenger_id']);
    $challenged_id = intval($_POST['challenged_id']);
    $challenge_type = sanitize_text_field($_POST['challenge_type']);
    
    // Verify the challenger is the current logged-in user
    if ($challenger_id !== get_current_user_id()) {
        wp_send_json_error(array('message' => 'Unauthorized action'));
        return;
    }
    
    // Validate challenge type
    $valid_types = array('speed', 'endurance', 'accuracy');
    if (!in_array($challenge_type, $valid_types)) {
        wp_send_json_error(array('message' => 'Invalid challenge type'));
        return;
    }
    
    // Get user display names for the post title
    $challenger = get_user_by('ID', $challenger_id);
    $challenged = get_user_by('ID', $challenged_id);
    
    $challenger_name = get_user_meta($challenger_id, 'first_name', true) . ' ' . get_user_meta($challenger_id, 'last_name', true);
    $challenger_name = trim($challenger_name) ?: $challenger->display_name;
    
    $challenged_name = get_user_meta($challenged_id, 'first_name', true) . ' ' . get_user_meta($challenged_id, 'last_name', true);
    $challenged_name = trim($challenged_name) ?: $challenged->display_name;
    
    // Create the challenge post
    $post_data = array(
        'post_title'    => $challenger_name . ' vs ' . $challenged_name . ' - ' . ucfirst($challenge_type),
        'post_type'     => 'challenge',
        'post_status'   => 'publish',
        'post_author'   => $challenger_id,
    );
    
    $post_id = wp_insert_post($post_data);
    
    if (is_wp_error($post_id)) {
        wp_send_json_error(array('message' => 'Failed to create challenge'));
        return;
    }
    
    // Save challenge metadata
    update_post_meta($post_id, '_challenger_id', $challenger_id);
    update_post_meta($post_id, '_challenged_id', $challenged_id);
    update_post_meta($post_id, '_challenge_type', $challenge_type);
    update_post_meta($post_id, '_challenge_date', current_time('Y-m-d'));
    update_post_meta($post_id, '_challenge_status', 'pending');
    
    $challenge_data = array(
        'post_id' => $post_id,
        'challenger_id' => $challenger_id,
        'challenged_id' => $challenged_id,
        'challenge_type' => $challenge_type,
        'challenge_date' => current_time('mysql'),
        'title' => $post_data['post_title']
    );
    
    wp_send_json_success(array(
        'message' => 'Challenge sent successfully!',
        'data' => $challenge_data
    ));
}

/**
 * Add custom meta boxes for Challenge post type
 */
function add_challenge_meta_boxes() {
    add_meta_box(
        'challenge_details',
        'Challenge Details',
        'render_challenge_meta_box',
        'challenge',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'add_challenge_meta_boxes');

/**
 * Render the Challenge meta box
 */
function render_challenge_meta_box($post) {
    // Add nonce for security
    wp_nonce_field('challenge_meta_box', 'challenge_meta_box_nonce');
    
    // Get current values
    $challenger_id = get_post_meta($post->ID, '_challenger_id', true);
    $challenged_id = get_post_meta($post->ID, '_challenged_id', true);
    $challenge_type = get_post_meta($post->ID, '_challenge_type', true);
    $challenge_date = get_post_meta($post->ID, '_challenge_date', true);
    $challenge_status = get_post_meta($post->ID, '_challenge_status', true);
    
    // Get all influencers for dropdowns
    $influencers = get_users(array(
        'role' => 'influencer',
        'orderby' => 'display_name',
        'order' => 'ASC'
    ));
    
    ?>
    <div style="padding: 10px 0;">
        <p>
            <label for="challenger_id" style="display: inline-block; width: 150px; font-weight: bold;">Challenger:</label>
            <select name="challenger_id" id="challenger_id" style="width: 300px;">
                <option value="">Select Challenger</option>
                <?php foreach ($influencers as $influencer) : 
                    $first_name = get_user_meta($influencer->ID, 'first_name', true);
                    $last_name = get_user_meta($influencer->ID, 'last_name', true);
                    $display_name = trim($first_name . ' ' . $last_name) ?: $influencer->display_name;
                ?>
                    <option value="<?php echo esc_attr($influencer->ID); ?>" <?php selected($challenger_id, $influencer->ID); ?>>
                        <?php echo esc_html($display_name . ' (' . $influencer->user_email . ')'); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </p>
        
        <p>
            <label for="challenged_id" style="display: inline-block; width: 150px; font-weight: bold;">Challenged:</label>
            <select name="challenged_id" id="challenged_id" style="width: 300px;">
                <option value="">Select Challenged</option>
                <?php foreach ($influencers as $influencer) : 
                    $first_name = get_user_meta($influencer->ID, 'first_name', true);
                    $last_name = get_user_meta($influencer->ID, 'last_name', true);
                    $display_name = trim($first_name . ' ' . $last_name) ?: $influencer->display_name;
                ?>
                    <option value="<?php echo esc_attr($influencer->ID); ?>" <?php selected($challenged_id, $influencer->ID); ?>>
                        <?php echo esc_html($display_name . ' (' . $influencer->user_email . ')'); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </p>
        
        <p>
            <label for="challenge_type" style="display: inline-block; width: 150px; font-weight: bold;">Challenge Type:</label>
            <select name="challenge_type" id="challenge_type" style="width: 300px;">
                <option value="">Select Challenge Type</option>
                <option value="speed" <?php selected($challenge_type, 'speed'); ?>>Speed Challenge</option>
                <option value="endurance" <?php selected($challenge_type, 'endurance'); ?>>Endurance Challenge</option>
                <option value="accuracy" <?php selected($challenge_type, 'accuracy'); ?>>Accuracy Challenge</option>
            </select>
        </p>
        
        <p>
            <label for="challenge_date" style="display: inline-block; width: 150px; font-weight: bold;">Challenge Date:</label>
            <input type="date" name="challenge_date" id="challenge_date" value="<?php echo esc_attr($challenge_date); ?>" style="width: 300px;">
        </p>
        
        <p>
            <label for="challenge_status" style="display: inline-block; width: 150px; font-weight: bold;">Status:</label>
            <select name="challenge_status" id="challenge_status" style="width: 300px;">
                <option value="pending" <?php selected($challenge_status, 'pending'); ?>>Pending</option>
                <option value="accepted" <?php selected($challenge_status, 'accepted'); ?>>Accepted</option>
                <option value="declined" <?php selected($challenge_status, 'declined'); ?>>Declined</option>
                <option value="completed" <?php selected($challenge_status, 'completed'); ?>>Completed</option>
            </select>
        </p>
    </div>
    <?php
}

/**
 * Save Challenge meta box data
 */
function save_challenge_meta_box($post_id) {
    // Check if nonce is set
    if (!isset($_POST['challenge_meta_box_nonce'])) {
        return;
    }
    
    // Verify nonce
    if (!wp_verify_nonce($_POST['challenge_meta_box_nonce'], 'challenge_meta_box')) {
        return;
    }
    
    // Check if this is an autosave
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    
    // Check user permissions
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    
    // Save Challenger ID
    if (isset($_POST['challenger_id'])) {
        update_post_meta($post_id, '_challenger_id', sanitize_text_field($_POST['challenger_id']));
    }
    
    // Save Challenged ID
    if (isset($_POST['challenged_id'])) {
        update_post_meta($post_id, '_challenged_id', sanitize_text_field($_POST['challenged_id']));
    }
    
    // Save Challenge Type
    if (isset($_POST['challenge_type'])) {
        update_post_meta($post_id, '_challenge_type', sanitize_text_field($_POST['challenge_type']));
    }
    
    // Save Challenge Date
    if (isset($_POST['challenge_date'])) {
        update_post_meta($post_id, '_challenge_date', sanitize_text_field($_POST['challenge_date']));
    }
    
    // Save Challenge Status
    if (isset($_POST['challenge_status'])) {
        update_post_meta($post_id, '_challenge_status', sanitize_text_field($_POST['challenge_status']));
    }
}
add_action('save_post_challenge', 'save_challenge_meta_box');

/**
 * Debug function to check if role was created properly
 * Remove this in production
 */
function debug_influencer_role() {
    if (current_user_can('manage_options')) { // Only for admins
        $role = get_role('influencer');
        if ($role) {
            echo '<!-- Influencer role exists with capabilities: ' . implode(', ', array_keys($role->capabilities)) . ' -->';
        } else {
            echo '<!-- Influencer role does not exist -->';
        }
    }
}
add_action('wp_head', 'debug_influencer_role');