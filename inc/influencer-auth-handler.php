<?php
/**
 * Influencer Authentication Handler
 * Processes registration and login for influencer users
 * 
 * This file should be included in functions.php:
 * require_once get_template_directory() . '/inc/influencer-auth-handler.php';
 */

// Hook into WordPress init to process forms early
add_action('init', 'process_influencer_auth_forms');

function process_influencer_auth_forms() {
    // Only process if this is a POST request
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return;
    }
    
    // Only process if action is set
    if (!isset($_POST['action'])) {
        return;
    }

// Handle Registration
if ($_POST['action'] === 'influencer_register') {
    
    // Verify nonce
    if (!isset($_POST['register_nonce']) || !wp_verify_nonce($_POST['register_nonce'], 'influencer_register')) {
        set_auth_error('Security verification failed. Please try again.');
        wp_redirect($_POST['redirect_url']);
        exit;
    }
    
    // Get form data
    $first_name = sanitize_text_field($_POST['first_name']);
    $last_name = sanitize_text_field($_POST['last_name']);
    $email = sanitize_email($_POST['email']);
    $country = sanitize_text_field($_POST['country']);
    $password = $_POST['password'];
    $redirect_url = isset($_POST['redirect_url']) ? esc_url_raw($_POST['redirect_url']) : home_url();
    
    // Validation
    $errors = array();
    
    if (empty($first_name)) {
        $errors[] = 'First name is required';
    }
    
    if (empty($last_name)) {
        $errors[] = 'Last name is required';
    }
    
    if (empty($email) || !is_email($email)) {
        $errors[] = 'Valid email address is required';
    }
    
    if (email_exists($email)) {
        $errors[] = 'This email is already registered';
    }
    
    if (empty($country)) {
        $errors[] = 'Country is required';
    }
    
    if (empty($password) || strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters';
    }
    
    // If errors, redirect back
    if (!empty($errors)) {
        set_auth_error(implode('. ', $errors));
        wp_redirect($redirect_url);
        exit;
    }
    
    // Create username from email
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
        set_auth_error('Registration failed: ' . $user_id->get_error_message());
        wp_redirect($redirect_url);
        exit;
    }
    
    // Update user meta
    update_user_meta($user_id, 'first_name', $first_name);
    update_user_meta($user_id, 'last_name', $last_name);
    update_user_meta($user_id, 'billing_country', $country);
    
    // Set user role to influencer
    $user = new WP_User($user_id);
    $user->set_role('influencer');
    
    // Log the user in
    wp_set_current_user($user_id);
    wp_set_auth_cookie($user_id);
    
    // Success message
    set_auth_success('Registration successful! Welcome to Avantage.');
    
    // Redirect
    wp_redirect($redirect_url);
    exit;
}

// Handle Login
if (isset($_POST['action']) && $_POST['action'] === 'influencer_login') {
    
    // Verify nonce
    if (!isset($_POST['login_nonce']) || !wp_verify_nonce($_POST['login_nonce'], 'influencer_login')) {
        set_auth_error('Security verification failed. Please try again.');
        wp_redirect($_POST['redirect_url']);
        exit;
    }
    
    // Get form data
    $email = sanitize_email($_POST['email']);
    $password = $_POST['password'];
    $redirect_url = isset($_POST['redirect_url']) ? esc_url_raw($_POST['redirect_url']) : home_url();
    
    // Validation
    $errors = array();
    
    if (empty($email) || !is_email($email)) {
        $errors[] = 'Valid email address is required';
    }
    
    if (empty($password)) {
        $errors[] = 'Password is required';
    }
    
    // If errors, redirect back
    if (!empty($errors)) {
        set_auth_error(implode('. ', $errors));
        wp_redirect($redirect_url);
        exit;
    }
    
    // Get user by email
    $user = get_user_by('email', $email);
    
    if (!$user) {
        set_auth_error('No account found with this email address');
        wp_redirect($redirect_url);
        exit;
    }
    
    // Authenticate
    $creds = array(
        'user_login'    => $user->user_login,
        'user_password' => $password,
        'remember'      => true
    );
    
    $user = wp_signon($creds, false);
    
    if (is_wp_error($user)) {
        set_auth_error('Incorrect password');
        wp_redirect($redirect_url);
        exit;
    }
    
    // Success
    set_auth_success('Login successful! Welcome back.');
    
    // Redirect
    wp_redirect($redirect_url);
    exit;
}

// If no valid action, redirect to home
wp_redirect(home_url());
exit;
