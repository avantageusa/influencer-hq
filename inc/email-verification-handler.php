<?php
/**
 * Add this code to your theme's functions.php file
 * This handles the verification email sending and user creation
 */

// AJAX handler for logged-in users
add_action('wp_ajax_send_verification_email', 'handle_verification_email');
// AJAX handler for non-logged-in users
add_action('wp_ajax_nopriv_send_verification_email', 'handle_verification_email');

function handle_verification_email() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'verification_email_nonce')) {
        wp_send_json_error('Invalid security token');
        return;
    }
    
    // Get form data
    $email = sanitize_email($_POST['email']);
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $first_name = isset($_POST['first_name']) ? sanitize_text_field($_POST['first_name']) : '';
    $last_name = isset($_POST['last_name']) ? sanitize_text_field($_POST['last_name']) : '';
    $platform_handle = isset($_POST['platform_handle']) ? sanitize_text_field($_POST['platform_handle']) : '';
    $comm_methods_json = isset($_POST['comm_methods']) ? $_POST['comm_methods'] : '{}';
    $comm_methods = json_decode(stripslashes($comm_methods_json), true);
    $challenge_type = isset($_POST['challenge_type']) ? sanitize_text_field($_POST['challenge_type']) : '';
    
    if (!is_email($email)) {
        wp_send_json_error('Invalid email address');
        return;
    }
    
    if (empty($password) || strlen($password) < 6) {
        wp_send_json_error('Password must be at least 6 characters');
        return;
    }
    
    // Check if email already exists
    if (email_exists($email)) {
        wp_send_json_error('This email is already registered');
        return;
    }
    
    // Generate a unique verification token
    $verification_token = wp_generate_password(32, false);
    
    // Store registration data temporarily in options (expires in 24 hours)
    $registration_data = array(
        'email' => $email,
        'password' => $password, // Will be hashed when user is created
        'first_name' => $first_name,
        'last_name' => $last_name,
        'platform_handle' => $platform_handle,
        'comm_methods' => $comm_methods,
        'challenge_type' => $challenge_type,
        'timestamp' => time(),
        'expires' => time() + (24 * 60 * 60) // 24 hours
    );
    
    // Store with token as key
    update_option('pending_registration_' . $verification_token, $registration_data, false);
    
    // Build verification link with token
    $hq_url = home_url('/portal/portal-home/');
    $verification_link = add_query_arg(
        array(
            'verify_token' => $verification_token,
            'action' => 'verify_email',
            'welcome' => 'true'
        ),
        $hq_url
    );
    
    // Email subject
    $subject = 'Verify your email - Influencer HQ';
    
    // Email body with styled button
    $message = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
    </head>
    <body style="margin: 0; padding: 0; font-family: Arial, Helvetica, sans-serif; background-color: #000000;">
        <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #000000;">
            <tr>
                <td align="center" style="padding: 40px 20px;">
                    <table width="600" cellpadding="0" cellspacing="0" style="background: linear-gradient(135deg, rgba(215, 24, 42, 0.08) 0%, rgba(0, 0, 0, 0.4) 50%, rgba(255, 215, 0, 0.08) 100%); border-radius: 20px; border: 2px solid rgba(255, 255, 255, 0.1);">
                        <tr>
                            <td style="padding: 60px 40px; text-align: center;">
                                <h1 style="color: #ffffff; font-size: 32px; margin: 0 0 20px 0; font-weight: 700;">Verify Your Email</h1>
                                <p style="color: #f0f0f0; font-size: 18px; line-height: 1.6; margin: 0 0 30px 0;">
                                    Thank you for joining Influencer HQ! Click the button below to verify your email address and activate your account.
                                </p>
                                <div style="margin: 40px 0;">
                                    <a href="' . esc_url($verification_link) . '" style="display: inline-block; background: linear-gradient(135deg, #d7182a 0%, #a01320 100%); color: #ffffff; text-decoration: none; padding: 16px 60px; border-radius: 12px; font-weight: 700; font-size: 18px; text-transform: uppercase; letter-spacing: 1.5px; box-shadow: 0 8px 25px rgba(215, 24, 42, 0.4);">Verify Email & Create Account</a>
                                </div>
                                <p style="color: #b0b0b0; font-size: 14px; line-height: 1.6; margin: 30px 0 0 0;">
                                    If the button doesn\'t work, copy and paste this link into your browser:<br>
                                    <a href="' . esc_url($verification_link) . '" style="color: #d7182a; word-break: break-all;">' . esc_url($verification_link) . '</a>
                                </p>
                                <p style="color: #888888; font-size: 12px; line-height: 1.6; margin: 20px 0 0 0;">
                                    This link will expire in 24 hours.
                                </p>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </body>
    </html>
    ';
    
    // Email headers
    $headers = array(
        'Content-Type: text/html; charset=UTF-8',
        'From: Influencer HQ <verify@influencerhq.co>'
    );
    
    // Capture the real PHPMailer error if delivery fails
    $mail_error = null;
    $failed_hook = function($wp_error) use (&$mail_error) {
        $mail_error = $wp_error->get_error_message();
        error_log('IHQ wp_mail failed: ' . $mail_error);
    };
    add_action('wp_mail_failed', $failed_hook);

    // Send email
    $sent = wp_mail($email, $subject, $message, $headers);

    remove_action('wp_mail_failed', $failed_hook);

    if ($sent) {
        wp_send_json_success('Verification email sent successfully');
    } else {
        $error_msg = $mail_error ?: 'Failed to send email';
        error_log('IHQ send_verification_email failed for ' . $email . ': ' . $error_msg);
        wp_send_json_error($error_msg);
    }
}

// Handle email verification and user creation
add_action('template_redirect', 'handle_email_verification_and_user_creation');

function handle_email_verification_and_user_creation() {
    // Check if this is a verification request
    if (!isset($_GET['action']) || $_GET['action'] !== 'verify_email' || !isset($_GET['verify_token'])) {
        return;
    }
    
    $token = sanitize_text_field($_GET['verify_token']);
    
    // Retrieve registration data
    $registration_data = get_option('pending_registration_' . $token);
    
    if (!$registration_data) {
        wp_die('Invalid or expired verification link. Please try registering again.', 'Verification Failed', array('response' => 400));
        return;
    }
    
    // Check if token has expired
    if (time() > $registration_data['expires']) {
        delete_option('pending_registration_' . $token);
        wp_die('This verification link has expired. Please register again.', 'Link Expired', array('response' => 400));
        return;
    }
    
    $email = $registration_data['email'];
    $password = $registration_data['password'];
    $first_name = isset($registration_data['first_name']) ? $registration_data['first_name'] : '';
    $last_name = isset($registration_data['last_name']) ? $registration_data['last_name'] : '';
    $platform_handle = isset($registration_data['platform_handle']) ? $registration_data['platform_handle'] : '';
    $comm_methods = $registration_data['comm_methods'];
    $challenge_type = $registration_data['challenge_type'];
    
    // Check if email already exists
    if (email_exists($email)) {
        delete_option('pending_registration_' . $token);
        wp_die('This email is already registered. Please login instead.', 'Already Registered', array('response' => 400));
        return;
    }
    
    // Create username from email (part before @)
    $username = sanitize_user(current(explode('@', $email)));
    
    // Make username unique if it exists
    $original_username = $username;
    $counter = 1;
    while (username_exists($username)) {
        $username = $original_username . $counter;
        $counter++;
    }
    
    // Create the user
    $user_id = wp_create_user($username, $password, $email);
    
    if (is_wp_error($user_id)) {
        wp_die('Failed to create account: ' . $user_id->get_error_message(), 'Registration Failed', array('response' => 500));
        return;
    }
    
    // Store name and handle
    if ($first_name) update_user_meta($user_id, 'first_name', $first_name);
    if ($last_name) update_user_meta($user_id, 'last_name', $last_name);
    if ($platform_handle) update_user_meta($user_id, 'platform_handle', $platform_handle);

    // Set user role to influencer
    $user = new WP_User($user_id);
    $user->set_role('influencer');
    
    // Store communication methods in user meta
    if (!empty($comm_methods) && is_array($comm_methods)) {
        update_user_meta($user_id, 'communication_methods', $comm_methods);
        
        // Also store the first method as preferred method for backwards compatibility
        $first_method = array_key_first($comm_methods);
        if ($first_method) {
            update_user_meta($user_id, 'preferred_communication', $first_method);
            update_user_meta($user_id, 'communication_username', $comm_methods[$first_method]);
        }
    }
    
    // Store challenge type
    if (!empty($challenge_type)) {
        update_user_meta($user_id, 'challenge_type', $challenge_type);
    }
    
    // Store registration date
    update_user_meta($user_id, 'registration_date', current_time('mysql'));
    update_user_meta($user_id, 'email_verified', true);

    // ── Register user in InfluencerHQ platform via OAuth ─────────────────────
    $ihq_oauth_response = ihq_register_oauth_user($user_id, $first_name, $last_name, $email);
    if ($ihq_oauth_response && !empty($ihq_oauth_response['AccessToken'])) {
        update_user_meta($user_id, 'ihq_access_token',   $ihq_oauth_response['AccessToken']);
        update_user_meta($user_id, 'ihq_id_token',       $ihq_oauth_response['IdToken']);
        update_user_meta($user_id, 'ihq_refresh_token',  $ihq_oauth_response['RefreshToken'] ?? '');
        update_user_meta($user_id, 'ihq_token_type',     $ihq_oauth_response['TokenType']     ?? 'Bearer');
        update_user_meta($user_id, 'ihq_token_expires',  time() + (int)($ihq_oauth_response['ExpiresIn'] ?? 3600));
    }
    // ─────────────────────────────────────────────────────────────────────────

    // Delete the temporary registration data
    delete_option('pending_registration_' . $token);
    
    // Log the user in automatically
    wp_set_current_user($user_id);
    wp_set_auth_cookie($user_id, true); // true = remember me
    
    // Redirect to portal with welcome parameter
    $redirect_url = add_query_arg('welcome', 'true', home_url('/portal/portal-home/'));
    
    wp_redirect($redirect_url);
    exit;
}

// Clean up expired registration tokens daily
add_action('wp_scheduled_delete', 'cleanup_expired_registrations');

/**
 * Register a new WP user in the InfluencerHQ platform via OAuth start-session.
 * Returns the parsed `data` object on success, or false on failure.
 */
function ihq_register_oauth_user($user_id, $first_name, $last_name, $email) {
    $api_url = 'https://02nvfvonol.execute-api.eu-west-2.amazonaws.com/qc/account/oauth/start-session';

    $payload = array(
        'oauthLoginType' => 'InfluencerHq',
        'payload' => array(
            'id'        => 'wpu-' . $user_id,
            'firstName' => $first_name,
            'lastName'  => $last_name,
            'email'     => $email,
        ),
    );

    $response = wp_remote_post($api_url, array(
        'headers' => array(
            'Authorization' => 'milos_testing',
            'Content-Type'  => 'application/json',
        ),
        'body'      => wp_json_encode($payload),
        'timeout'   => 30,
        'sslverify' => true,
    ));

    if (is_wp_error($response)) {
        error_log('IHQ OAuth register error for user ' . $user_id . ': ' . $response->get_error_message());
        return false;
    }

    $body   = json_decode(wp_remote_retrieve_body($response), true);
    $status = wp_remote_retrieve_response_code($response);

    $success = !empty($body['success']) && $body['success'] === true && !empty($body['data']);
    if (!$success) {
        error_log('IHQ OAuth register bad response (HTTP ' . $status . ') for user ' . $user_id . ': ' . wp_remote_retrieve_body($response));
        return false;
    }

    return $body['data'];
}

function cleanup_expired_registrations() {
    global $wpdb;
    
    // Get all pending registration options
    $results = $wpdb->get_results(
        "SELECT option_name, option_value FROM {$wpdb->options} 
        WHERE option_name LIKE 'pending_registration_%'",
        ARRAY_A
    );
    
    $current_time = time();
    
    foreach ($results as $row) {
        $data = maybe_unserialize($row['option_value']);
        if (isset($data['expires']) && $current_time > $data['expires']) {
            delete_option($row['option_name']);
        }
    }
}
