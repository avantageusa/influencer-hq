<?php
/**
 * Influencer Authentication Handler
 * Handles login and registration for Influencers with custom user role
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    // Load WordPress
    $wp_load_path = dirname(__FILE__) . '/../../../../wp-load.php';
    if (file_exists($wp_load_path)) {
        require_once($wp_load_path);
    } else {
        die('WordPress not found');
    }
}

// Handle form submissions
if ($_POST && isset($_POST['action'])) {
    
    $redirect_url = isset($_POST['redirect_url']) ? $_POST['redirect_url'] : home_url();
    
    // Handle Registration
    if ($_POST['action'] === 'influencer_register' && wp_verify_nonce($_POST['register_nonce'], 'influencer_register')) {
        
        // Sanitize input data
        $first_name = sanitize_text_field($_POST['first_name']);
        $last_name = sanitize_text_field($_POST['last_name']);
        $email = sanitize_email($_POST['email']);
        $country = sanitize_text_field($_POST['country']);
        $password = $_POST['password'];
        
        // Validation
        $errors = [];
        
        if (empty($first_name)) $errors[] = 'First name is required.';
        if (empty($last_name)) $errors[] = 'Last name is required.';
        if (empty($email)) $errors[] = 'Email is required.';
        if (empty($country)) $errors[] = 'Country is required.';
        if (empty($password)) $errors[] = 'Password is required.';
        
        if (!is_email($email)) $errors[] = 'Please enter a valid email address.';
        if (email_exists($email)) $errors[] = 'An account with this email already exists.';
        if (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters long.';
        
        if (!empty($errors)) {
            $error_message = implode(' ', $errors);
            wp_redirect($redirect_url . '&error=' . urlencode($error_message));
            exit;
        }
        
        // Create the user
        $user_id = wp_create_user($email, $password, $email);
        
        if (is_wp_error($user_id)) {
            wp_redirect($redirect_url . '&error=' . urlencode($user_id->get_error_message()));
            exit;
        }
        
        // Update user profile
        wp_update_user(array(
            'ID' => $user_id,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'display_name' => $first_name . ' ' . $last_name
        ));
        
        // Save custom fields
        update_user_meta($user_id, 'country', $country);
        update_user_meta($user_id, 'registration_date', current_time('mysql'));
        update_user_meta($user_id, 'user_type', 'influencer');
        
        // Set custom user role (create it if it doesn't exist)
        if (!get_role('influencer')) {
            add_role('influencer', 'Influencer', array(
                'read' => true,
                'edit_posts' => false,
                'delete_posts' => false,
                'publish_posts' => false,
                'upload_files' => true,
            ));
        }
        
        // Assign role to user
        $user = new WP_User($user_id);
        $user->set_role('influencer');
        
        // Auto login the user
        wp_set_current_user($user_id);
        wp_set_auth_cookie($user_id, true);
        
        // Redirect with success message
        wp_redirect($redirect_url . '&message=' . urlencode('Registration successful! Welcome to Avantage.'));
        exit;
    }
    
    // Handle Login
    elseif ($_POST['action'] === 'influencer_login' && wp_verify_nonce($_POST['login_nonce'], 'influencer_login')) {
        
        $email = sanitize_email($_POST['email']);
        $password = $_POST['password'];
        
        if (empty($email) || empty($password)) {
            wp_redirect($redirect_url . '&error=' . urlencode('Email and password are required.'));
            exit;
        }
        
        // Attempt to authenticate
        $user = wp_authenticate($email, $password);
        
        if (is_wp_error($user)) {
            wp_redirect($redirect_url . '&error=' . urlencode('Invalid email or password.'));
            exit;
        }
        
        // Check if user has influencer role or convert them
        if (!in_array('influencer', $user->roles)) {
            // Optionally convert existing users to influencer role
            $user->add_role('influencer');
            update_user_meta($user->ID, 'user_type', 'influencer');
        }
        
        // Login successful
        wp_set_current_user($user->ID);
        wp_set_auth_cookie($user->ID, true);
        
        // Update last login
        update_user_meta($user->ID, 'last_login', current_time('mysql'));
        
        // Redirect with success message
        wp_redirect($redirect_url . '&message=' . urlencode('Login successful! Welcome back.'));
        exit;
    }
}

// If we get here, something went wrong
wp_redirect(home_url() . '?error=' . urlencode('Invalid request.'));
exit;