<?php
/**
 * Avantage Baccarat functions and definitions
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package Avantage_Baccarat
 */
add_filter('wpcf7_autop_or_not', '__return_false');
if ( ! defined( '_S_VERSION' ) ) {
	// Replace the version number of the theme on each release.
	define( '_S_VERSION', '1.0.0' );
}

/**
 * Sets up theme defaults and registers support for various WordPress features.
 *
 * Note that this function is hooked into the after_setup_theme hook, which
 * runs before the init hook. The init hook is too late for some features, such
 * as indicating support for post thumbnails.
 */
function avantage_baccarat_setup() {
	/*
		* Make theme available for translation.
		* Translations can be filed in the /languages/ directory.
		* If you're building a theme based on Avantage Baccarat, use a find and replace
		* to change 'avantage-baccarat' to the name of your theme in all the template files.
		*/
	load_theme_textdomain( 'avantage-baccarat', get_template_directory() . '/languages' );

	// Add default posts and comments RSS feed links to head.
	add_theme_support( 'automatic-feed-links' );

	/*
		* Let WordPress manage the document title.
		* By adding theme support, we declare that this theme does not use a
		* hard-coded <title> tag in the document head, and expect WordPress to
		* provide it for us.
		*/
	add_theme_support( 'title-tag' );

	/*
		* Enable support for Post Thumbnails on posts and pages.
		*
		* @link https://developer.wordpress.org/themes/functionality/featured-images-post-thumbnails/
		*/
	add_theme_support( 'post-thumbnails' );

	// This theme uses wp_nav_menu() in one location.
	register_nav_menus(
		array(
			'menu-1' => esc_html__( 'Primary', 'avantage-baccarat' ),
		)
	);

	/*
		* Switch default core markup for search form, comment form, and comments
		* to output valid HTML5.
		*/
	add_theme_support(
		'html5',
		array(
			'search-form',
			'comment-form',
			'comment-list',
			'gallery',
			'caption',
			'style',
			'script',
		)
	);

	// Set up the WordPress core custom background feature.
	add_theme_support(
		'custom-background',
		apply_filters(
			'avantage_baccarat_custom_background_args',
			array(
				'default-color' => 'ffffff',
				'default-image' => '',
			)
		)
	);

	// Add theme support for selective refresh for widgets.
	add_theme_support( 'customize-selective-refresh-widgets' );

	/**
	 * Add support for core custom logo.
	 *
	 * @link https://codex.wordpress.org/Theme_Logo
	 */
	add_theme_support(
		'custom-logo',
		array(
			'height'      => 250,
			'width'       => 250,
			'flex-width'  => true,
			'flex-height' => true,
		)
	);
}
add_action( 'after_setup_theme', 'avantage_baccarat_setup' );

/**
 * Set the content width in pixels, based on the theme's design and stylesheet.
 *
 * Priority 0 to make it available to lower priority callbacks.
 *
 * @global int $content_width
 */
function avantage_baccarat_content_width() {
	$GLOBALS['content_width'] = apply_filters( 'avantage_baccarat_content_width', 640 );
}
add_action( 'after_setup_theme', 'avantage_baccarat_content_width', 0 );

/**
 * Register widget area.
 *
 * @link https://developer.wordpress.org/themes/functionality/sidebars/#registering-a-sidebar
 */
function avantage_baccarat_widgets_init() {
	register_sidebar(
		array(
			'name'          => esc_html__( 'Sidebar', 'avantage-baccarat' ),
			'id'            => 'sidebar-1',
			'description'   => esc_html__( 'Add widgets here.', 'avantage-baccarat' ),
			'before_widget' => '<section id="%1$s" class="widget %2$s">',
			'after_widget'  => '</section>',
			'before_title'  => '<h2 class="widget-title">',
			'after_title'   => '</h2>',
		)
	);
}
add_action( 'widgets_init', 'avantage_baccarat_widgets_init' );

/**
 * Enqueue scripts and styles.
 */
function avantage_baccarat_scripts() {
	wp_enqueue_style( 'avantage-baccarat-style', get_stylesheet_uri(), array(), _S_VERSION );
	wp_style_add_data( 'avantage-baccarat-style', 'rtl', 'replace' );

	// Enqueue Bootstrap CSS
	wp_enqueue_style( 'bootstrap-css', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css', array(), '5.3.0' );

	wp_enqueue_script( 'avantage-baccarat-navigation', get_template_directory_uri() . '/js/navigation.js', array(), _S_VERSION, true );
	
	wp_localize_script( 'avantage-baccarat-navigation', 'myAjax', [
        'ajax_url' => admin_url( 'admin-ajax.php' )
    ]);
	// Enqueue Bootstrap JS
	wp_enqueue_script( 'bootstrap-js', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js', array(), '5.3.0', true );

	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
}
add_action( 'wp_enqueue_scripts', 'avantage_baccarat_scripts' );

/**
 * Implement the Custom Header feature.
 */
require get_template_directory() . '/inc/custom-header.php';

/**
 * Custom template tags for this theme.
 */
require get_template_directory() . '/inc/template-tags.php';

/**
 * Custom template tags for this theme.
 */
require get_template_directory() . '/inc/teams-selection.php';

/**
 * Functions which enhance the theme by hooking into WordPress.
 */
require get_template_directory() . '/inc/template-functions.php';

/**
 * Customizer additions.
 */
require get_template_directory() . '/inc/customizer.php';

/**
 * Load Jetpack compatibility file.
 */
if ( defined( 'JETPACK__VERSION' ) ) {
	require get_template_directory() . '/inc/jetpack.php';
}

/**
 * Load Influencer Role Management
 */
require get_template_directory() . '/inc/influencer-role.php';

/**
 * Email Verification Handler
 */
require_once get_template_directory() . '/inc/email-verification-handler.php';

/**
 * Check if user exists in Braze (for influencer integration)
 */
function check_braze_user_exists_influencer($email, $external_id = null) {
    $braze_api_key = '20bea073-5d29-40ca-b7b5-17126a5893c6';
    $braze_endpoint = 'https://rest.iad-05.braze.com/users/export/ids';
    
    // Use email as external_id if not provided
    if (!$external_id) {
        $external_id = $email;
    }
    
    $payload = [
        'external_ids' => [$external_id]
    ];
    
    $response = wp_remote_post($braze_endpoint, [
        'headers' => [
            'Authorization' => 'Bearer ' . $braze_api_key,
            'Content-Type' => 'application/json',
        ],
        'body' => wp_json_encode($payload),
        'timeout' => 15,
        'sslverify' => true
    ]);
    
    if (is_wp_error($response)) {
        return [
            'success' => false,
            'exists' => false,
            'error' => $response->get_error_message(),
            'user_data' => null
        ];
    }
    
    $response_code = wp_remote_retrieve_response_code($response);
    $response_body = wp_remote_retrieve_body($response);
    $data = json_decode($response_body, true);
    
    if ($response_code >= 200 && $response_code < 300) {
        $user_exists = false;
        $existing_user_data = null;
        
        if (isset($data['users']) && !empty($data['users'])) {
            // Check if the email field exists and matches target email
            foreach ($data['users'] as $user) {
                if (isset($user['email']) && $user['email'] === $email) {
                    $user_exists = true;
                    $existing_user_data = $user;
                    break;
                }
            }
        }
        
        return [
            'success' => true,
            'exists' => $user_exists,
            'user_data' => $existing_user_data,
            'full_response' => $data
        ];
    } else {
        return [
            'success' => false,
            'exists' => false,
            'error' => 'API returned error code: ' . $response_code,
            'user_data' => null
        ];
    }
}

// Hook: Create Genius Referrals advocate when a new influencer account is created
add_action('set_user_role', function($user_id, $role, $old_roles) {
	error_log('=== GENIUS REFERRALS: set_user_role hook triggered for user ID: ' . $user_id);
	error_log('=== GENIUS REFERRALS: New role: ' . $role . ', Old roles: ' . print_r($old_roles, true));
	
	// Only proceed if the new role is influencer
	if ($role !== 'influencer') {
		error_log('=== GENIUS REFERRALS: New role is NOT influencer, skipping advocate creation');
		return;
	}
	
	error_log('=== GENIUS REFERRALS: User IS now an influencer, proceeding with advocate creation');
	
	$user = get_userdata($user_id);
	if (!$user) {
		error_log('=== GENIUS REFERRALS: Could not load user data, aborting');
		return;
	}
	
	$email = $user->user_email;
	$first_name = get_user_meta($user_id, 'first_name', true);
	$last_name = get_user_meta($user_id, 'last_name', true);
	
	error_log('=== GENIUS REFERRALS: Email: "' . $email . '", First name: "' . $first_name . '", Last name: "' . $last_name . '"');
	
	$advocate_data = array(
		'advocate' => array(
			'firstname' => $first_name ? $first_name : $user->display_name,
			'lastname' => $last_name ? $last_name : 'User',
			'email' => $email,
			'payout_threshold' => 20,
			'currency_code' => 'USD',
			'can_refer' => 1,
			'status' => 'active'
		)
	);
	
	error_log('=== GENIUS REFERRALS: Advocate data prepared: ' . json_encode($advocate_data));
	
	$url = 'https://api.geniusreferrals.com/accounts/dev_qc/advocates';
	$args = array(
		'method' => 'POST',
		'headers' => array(
			'X-Auth-Token' => '1a5b59cf8307b1b1f0f922aa12d4807c05ebaa10',
			'Content-Type' => 'application/json',
			'Accept' => 'application/json'
		),
		'body' => json_encode($advocate_data)
	);
	
	error_log('=== GENIUS REFERRALS: Sending API request to: ' . $url);
	
	$response = wp_remote_post($url, $args);
	
	if (is_wp_error($response)) {
		error_log('=== GENIUS REFERRALS: API Error: ' . $response->get_error_message());
	} else {
		$body = wp_remote_retrieve_body($response);
		$code = wp_remote_retrieve_response_code($response);
		error_log('=== GENIUS REFERRALS: API Response Code: ' . $code);
		error_log('=== GENIUS REFERRALS: API Response Body: ' . $body);
		
		// Save the token to user meta
		$response_data = json_decode($body, true);
		if (isset($response_data['token'])) {
			$genius_token = $response_data['token'];
			update_user_meta($user_id, 'genius_token', $genius_token);
			error_log('=== GENIUS REFERRALS: Token saved to user meta: ' . $genius_token);
			
			// Send influencer data to Braze
			$country = get_user_meta($user_id, 'country', true);
			
			// Check if email exists in Braze
			$braze_user_check = check_braze_user_exists_influencer($email);
			$external_id_to_use = '';
			$influencer_guid = '';
			
			if ($braze_user_check['success'] && $braze_user_check['exists']) {
				// CASE A: Email exists in Braze - use existing external_id as WordPress GUID
				$existing_braze_user_info = $braze_user_check['user_data'];
				$external_id_to_use = $existing_braze_user_info['external_id'] ?? $email;
				
				// Use the existing Braze external_id as WordPress influencer GUID
				$influencer_guid = $external_id_to_use;
			} else {
				// CASE B: Email does not exist in Braze - create new GUID and use as external_id
				$influencer_guid = 'wpinfluencer_' . bin2hex(random_bytes(12));
				$external_id_to_use = $influencer_guid; // Use GUID as external_id for new users
			}
			
			$braze_api_key = '81adeace-fad5-4566-bdd9-06095acdd3ee';
			$braze_endpoint = 'https://rest.iad-05.braze.com/users/track';
			
			$braze_data = [
				'attributes' => [
					[
						'external_id' => $external_id_to_use,
						'email' => $email,
						'first_name' => $first_name ? $first_name : $user->display_name,
						'last_name' => $last_name ? $last_name : '',
						'Language' => $country ? $country : '',
						'wp_influencer_guid' => $influencer_guid
					]
				],
				'events' => [
					[
						'external_id' => $external_id_to_use,
						'name' => 'influencer_registered',
						'time' => date('c')
					]
				]
			];
			
			error_log('=== GENIUS REFERRALS: Sending to Braze with external_id: ' . $external_id_to_use);
			error_log('=== GENIUS REFERRALS: Braze payload: ' . wp_json_encode($braze_data));
			
			$braze_response = wp_remote_post($braze_endpoint, [
				'headers' => [
					'Content-Type' => 'application/json',
					'Authorization' => 'Bearer ' . $braze_api_key,
				],
				'body' => wp_json_encode($braze_data),
				'timeout' => 15,
				'sslverify' => true
			]);
			
			if (is_wp_error($braze_response)) {
				error_log('=== GENIUS REFERRALS: Braze API Error: ' . $braze_response->get_error_message());
			} else {
				$braze_body = wp_remote_retrieve_body($braze_response);
				$braze_code = wp_remote_retrieve_response_code($braze_response);
				error_log('=== GENIUS REFERRALS: Braze API Response Code: ' . $braze_code);
				error_log('=== GENIUS REFERRALS: Braze API Response Body: ' . $braze_body);
				
				if ($braze_code >= 200 && $braze_code < 300) {
					error_log('=== GENIUS REFERRALS: Braze submission SUCCESS');
				} else {
					error_log('=== GENIUS REFERRALS: Braze submission FAILED - Check response above');
				}
			}
		} else {
			error_log('=== GENIUS REFERRALS: No token found in response');
		}
	}
}, 10, 3);

/**
 * Register Custom Post Type: Live Appearance
 */
function register_live_appearance_post_type() {
    $labels = array(
        'name'                  => _x('Live Appearances', 'Post Type General Name', 'avantage-baccarat'),
        'singular_name'         => _x('Live Appearance', 'Post Type Singular Name', 'avantage-baccarat'),
        'menu_name'             => __('Live Appearances', 'avantage-baccarat'),
        'name_admin_bar'        => __('Live Appearance', 'avantage-baccarat'),
        'archives'              => __('Live Appearance Archives', 'avantage-baccarat'),
        'attributes'            => __('Live Appearance Attributes', 'avantage-baccarat'),
        'parent_item_colon'     => __('Parent Live Appearance:', 'avantage-baccarat'),
        'all_items'             => __('All Live Appearances', 'avantage-baccarat'),
        'add_new_item'          => __('Add New Live Appearance', 'avantage-baccarat'),
        'add_new'               => __('Add New', 'avantage-baccarat'),
        'new_item'              => __('New Live Appearance', 'avantage-baccarat'),
        'edit_item'             => __('Edit Live Appearance', 'avantage-baccarat'),
        'update_item'           => __('Update Live Appearance', 'avantage-baccarat'),
        'view_item'             => __('View Live Appearance', 'avantage-baccarat'),
        'view_items'            => __('View Live Appearances', 'avantage-baccarat'),
        'search_items'          => __('Search Live Appearance', 'avantage-baccarat'),
        'not_found'             => __('Not found', 'avantage-baccarat'),
        'not_found_in_trash'    => __('Not found in Trash', 'avantage-baccarat'),
        'featured_image'        => __('Featured Image', 'avantage-baccarat'),
        'set_featured_image'    => __('Set featured image', 'avantage-baccarat'),
        'remove_featured_image' => __('Remove featured image', 'avantage-baccarat'),
        'use_featured_image'    => __('Use as featured image', 'avantage-baccarat'),
        'insert_into_item'      => __('Insert into live appearance', 'avantage-baccarat'),
        'uploaded_to_this_item' => __('Uploaded to this live appearance', 'avantage-baccarat'),
        'items_list'            => __('Live appearances list', 'avantage-baccarat'),
        'items_list_navigation' => __('Live appearances list navigation', 'avantage-baccarat'),
        'filter_items_list'     => __('Filter live appearances list', 'avantage-baccarat'),
    );
    
    $args = array(
        'label'                 => __('Live Appearance', 'avantage-baccarat'),
        'description'           => __('Live appearance events and performances', 'avantage-baccarat'),
        'labels'                => $labels,
        'supports'              => array('title', 'editor', 'excerpt', 'author', 'thumbnail', 'comments', 'revisions', 'custom-fields'),
        'taxonomies'            => array('category', 'post_tag'),
        'hierarchical'          => false,
        'public'                => true,
        'show_ui'               => true,
        'show_in_menu'          => true,
        'menu_position'         => 20,
        'menu_icon'             => 'dashicons-microphone',
        'show_in_admin_bar'     => true,
        'show_in_nav_menus'     => true,
        'can_export'            => true,
        'has_archive'           => true,
        'exclude_from_search'   => false,
        'publicly_queryable'    => true,
        'capability_type'       => 'post',
        'show_in_rest'          => true,
    );
    
    register_post_type('live_appearance', $args);
}
add_action('init', 'register_live_appearance_post_type', 0);

/**
 * Add Custom Meta Boxes for Live Appearance
 */
function add_live_appearance_meta_boxes() {
    add_meta_box(
        'live_appearance_details',
        __('Live Appearance Details', 'avantage-baccarat'),
        'live_appearance_meta_box_callback',
        'live_appearance',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'add_live_appearance_meta_boxes');

/**
 * Meta Box Callback for Live Appearance Details
 */
function live_appearance_meta_box_callback($post) {
    // Add nonce field for security
    wp_nonce_field('live_appearance_meta_box', 'live_appearance_meta_box_nonce');
    
    // Get existing values
    $user_id = get_post_meta($post->ID, '_live_appearance_user_id', true);
    $appearance_datetime = get_post_meta($post->ID, '_live_appearance_datetime', true);
    $location = get_post_meta($post->ID, '_live_appearance_location', true);
    $duration = get_post_meta($post->ID, '_live_appearance_duration', true);
    $notes = get_post_meta($post->ID, '_live_appearance_notes', true);
    
    // Format datetime for input field
    if ($appearance_datetime) {
        $formatted_datetime = date('Y-m-d\TH:i', strtotime($appearance_datetime));
    } else {
        $formatted_datetime = '';
    }
    
    echo '<table class="form-table">';
    
    // User ID field with dropdown
    echo '<tr>';
    echo '<th><label for="live_appearance_user_id">' . __('User', 'avantage-baccarat') . '</label></th>';
    echo '<td>';
    wp_dropdown_users(array(
        'name' => 'live_appearance_user_id',
        'id' => 'live_appearance_user_id',
        'selected' => $user_id,
        'show_option_none' => __('Select a user', 'avantage-baccarat'),
        'option_none_value' => '',
    ));
    echo '<p class="description">' . __('Select the user associated with this live appearance.', 'avantage-baccarat') . '</p>';
    echo '</td>';
    echo '</tr>';
    
    // Date/Time field
    echo '<tr>';
    echo '<th><label for="live_appearance_datetime">' . __('Date & Time', 'avantage-baccarat') . '</label></th>';
    echo '<td>';
    echo '<input type="datetime-local" id="live_appearance_datetime" name="live_appearance_datetime" value="' . esc_attr($formatted_datetime) . '" style="width: 250px;" />';
    echo '<p class="description">' . __('Select the date and time of the live appearance.', 'avantage-baccarat') . '</p>';
    echo '</td>';
    echo '</tr>';
    
    // Location field
    echo '<tr>';
    echo '<th><label for="live_appearance_location">' . __('Location', 'avantage-baccarat') . '</label></th>';
    echo '<td>';
    echo '<input type="text" id="live_appearance_location" name="live_appearance_location" value="' . esc_attr($location) . '" style="width: 100%;" />';
    echo '<p class="description">' . __('Enter the location of the live appearance.', 'avantage-baccarat') . '</p>';
    echo '</td>';
    echo '</tr>';
    
    // Duration field
    echo '<tr>';
    echo '<th><label for="live_appearance_duration">' . __('Duration (minutes)', 'avantage-baccarat') . '</label></th>';
    echo '<td>';
    echo '<input type="number" id="live_appearance_duration" name="live_appearance_duration" value="' . esc_attr($duration) . '" min="0" style="width: 100px;" />';
    echo '<p class="description">' . __('Duration of the appearance in minutes.', 'avantage-baccarat') . '</p>';
    echo '</td>';
    echo '</tr>';
    
    // Notes field
    echo '<tr>';
    echo '<th><label for="live_appearance_notes">' . __('Additional Notes', 'avantage-baccarat') . '</label></th>';
    echo '<td>';
    echo '<textarea id="live_appearance_notes" name="live_appearance_notes" rows="4" style="width: 100%;">' . esc_textarea($notes) . '</textarea>';
    echo '<p class="description">' . __('Any additional notes or details about the live appearance.', 'avantage-baccarat') . '</p>';
    echo '</td>';
    echo '</tr>';
    
    echo '</table>';
}

/**
 * Save Live Appearance Meta Box Data
 */
function save_live_appearance_meta_box_data($post_id) {
    // Check if nonce is valid
    if (!isset($_POST['live_appearance_meta_box_nonce']) || !wp_verify_nonce($_POST['live_appearance_meta_box_nonce'], 'live_appearance_meta_box')) {
        return;
    }
    
    // Check if user has permissions to save data
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    
    // Check if not an autosave
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    
    // Save User ID
    if (isset($_POST['live_appearance_user_id'])) {
        update_post_meta($post_id, '_live_appearance_user_id', sanitize_text_field($_POST['live_appearance_user_id']));
    }
    
    // Save Date/Time
    if (isset($_POST['live_appearance_datetime'])) {
        $datetime = sanitize_text_field($_POST['live_appearance_datetime']);
        if ($datetime) {
            // Convert to MySQL datetime format
            $formatted_datetime = date('Y-m-d H:i:s', strtotime($datetime));
            update_post_meta($post_id, '_live_appearance_datetime', $formatted_datetime);
        } else {
            delete_post_meta($post_id, '_live_appearance_datetime');
        }
    }
    
    // Save Location
    if (isset($_POST['live_appearance_location'])) {
        update_post_meta($post_id, '_live_appearance_location', sanitize_text_field($_POST['live_appearance_location']));
    }
    
    // Save Duration
    if (isset($_POST['live_appearance_duration'])) {
        $duration = intval($_POST['live_appearance_duration']);
        update_post_meta($post_id, '_live_appearance_duration', $duration);
    }
    
    // Save Notes
    if (isset($_POST['live_appearance_notes'])) {
        update_post_meta($post_id, '_live_appearance_notes', sanitize_textarea_field($_POST['live_appearance_notes']));
    }
}
add_action('save_post', 'save_live_appearance_meta_box_data');

/**
 * Add custom columns to Live Appearance admin list
 */
function add_live_appearance_admin_columns($columns) {
    $new_columns = array();
    $new_columns['cb'] = $columns['cb'];
    $new_columns['title'] = $columns['title'];
    $new_columns['live_user'] = __('User', 'avantage-baccarat');
    $new_columns['live_datetime'] = __('Date & Time', 'avantage-baccarat');
    $new_columns['live_location'] = __('Location', 'avantage-baccarat');
    $new_columns['live_duration'] = __('Duration', 'avantage-baccarat');
    $new_columns['date'] = $columns['date'];
    
    return $new_columns;
}
add_filter('manage_live_appearance_posts_columns', 'add_live_appearance_admin_columns');

/**
 * Display custom column content in Live Appearance admin list
 */
function display_live_appearance_admin_columns($column, $post_id) {
    switch ($column) {
        case 'live_user':
            $user_id = get_post_meta($post_id, '_live_appearance_user_id', true);
            if ($user_id) {
                $user = get_userdata($user_id);
                if ($user) {
                    echo esc_html($user->display_name . ' (' . $user->user_email . ')');
                } else {
                    echo __('User not found', 'avantage-baccarat');
                }
            } else {
                echo '—';
            }
            break;
            
        case 'live_datetime':
            $datetime = get_post_meta($post_id, '_live_appearance_datetime', true);
            if ($datetime) {
                echo esc_html(date('M j, Y g:i A', strtotime($datetime)));
            } else {
                echo '—';
            }
            break;
            
        case 'live_location':
            $location = get_post_meta($post_id, '_live_appearance_location', true);
            echo $location ? esc_html($location) : '—';
            break;
            
        case 'live_duration':
            $duration = get_post_meta($post_id, '_live_appearance_duration', true);
            if ($duration) {
                echo esc_html($duration . ' min');
            } else {
                echo '—';
            }
            break;
    }
}
add_action('manage_live_appearance_posts_custom_column', 'display_live_appearance_admin_columns', 10, 2);

/**
 * Make custom columns sortable
 */
function make_live_appearance_columns_sortable($columns) {
    $columns['live_datetime'] = 'live_datetime';
    $columns['live_duration'] = 'live_duration';
    return $columns;
}
add_filter('manage_edit-live_appearance_sortable_columns', 'make_live_appearance_columns_sortable');

/**
 * Handle sorting for custom columns
 */
function live_appearance_custom_orderby($query) {
    if (!is_admin() || !$query->is_main_query()) {
        return;
    }
    
    $orderby = $query->get('orderby');
    
    if ('live_datetime' === $orderby) {
        $query->set('meta_key', '_live_appearance_datetime');
        $query->set('orderby', 'meta_value');
    } elseif ('live_duration' === $orderby) {
        $query->set('meta_key', '_live_appearance_duration');
        $query->set('orderby', 'meta_value_num');
    }
}
add_action('pre_get_posts', 'live_appearance_custom_orderby');


