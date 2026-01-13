<?php
/**
 * Template part for displaying influencer profile information
 * Shows all the data that was entered during registration
 */

// Get current user data
$current_user = wp_get_current_user();
$user_id = $current_user->ID;

// Initialize errors
$profile_errors = new WP_Error();
$profile_success = '';

// Handle profile update
if (isset($_POST['action']) && $_POST['action'] === 'update_influencer_profile') {
    
    // Verify nonce
    if (!isset($_POST['profile_nonce']) || !wp_verify_nonce($_POST['profile_nonce'], 'update_influencer_profile')) {
        $profile_errors->add('nonce_error', 'Security verification failed. Please try again.');
    } else {
        // Get form data
        $first_name = sanitize_text_field($_POST['first_name']);
        $last_name = sanitize_text_field($_POST['last_name']);
        $new_country = sanitize_text_field($_POST['country']);
        
        // Validation
        if (empty($first_name)) {
            $profile_errors->add('first_name', 'First name is required');
        }
        
        if (empty($last_name)) {
            $profile_errors->add('last_name', 'Last name is required');
        }
        
        if (empty($new_country)) {
            $profile_errors->add('country', 'Country is required');
        }
        
        // If no errors, update user data
        if (!$profile_errors->has_errors()) {
            // Update user meta
            update_user_meta($user_id, 'first_name', $first_name);
            update_user_meta($user_id, 'last_name', $last_name);
            update_user_meta($user_id, 'billing_country', $new_country);
            
            // Update WordPress user data
            wp_update_user(array(
                'ID' => $user_id,
                'first_name' => $first_name,
                'last_name' => $last_name,
                'display_name' => $first_name . ' ' . $last_name
            ));
            
            $profile_success = 'Profile updated successfully!';
            
            // Refresh current user data
            $current_user = wp_get_current_user();
        }
    }
}

// Get user meta data
$country = get_user_meta($user_id, 'billing_country', true);
$registration_date = get_user_meta($user_id, 'registration_date', true);
$last_login = get_user_meta($user_id, 'last_login', true);
$user_type = get_user_meta($user_id, 'user_type', true);

// Format dates
$registration_formatted = $registration_date ? date('F j, Y', strtotime($registration_date)) : 'Unknown';
$last_login_formatted = $last_login ? date('F j, Y g:i A', strtotime($last_login)) : 'Never';

// Get country name from code
$countries = array(
    'US' => 'United States',
    'CA' => 'Canada',
    'GB' => 'United Kingdom',
    'AU' => 'Australia',
    'DE' => 'Germany',
    'FR' => 'France',
    'ES' => 'Spain',
    'IT' => 'Italy',
    'NL' => 'Netherlands',
    'BR' => 'Brazil',
    'MX' => 'Mexico',
    'JP' => 'Japan',
    'KR' => 'South Korea',
    'CN' => 'China',
    'IN' => 'India',
    'SG' => 'Singapore',
    'other' => 'Other'
);
$country_name = isset($countries[$country]) ? $countries[$country] : $country;
?>

<div class="influencer-profile" style="max-width: 800px; margin: 0 auto;">
    
    <!-- Welcome Message -->
    <div class="text-center mb-5">
        <h2 class="text-22pt fw-bold text-yellow mb-3">Welcome back, <?php echo esc_html($current_user->first_name); ?>!</h2>
        <p class="fs-5 text-light-gray">Here's your Avantage Influencer profile information</p>
    </div>
    
    <!-- Display Messages -->
    <?php 
    // Display error messages
    if ($profile_errors->has_errors()) {
        $error_messages = $profile_errors->get_error_messages();
        echo '<div class="alert alert-danger text-center mb-4" style="background: rgba(215, 24, 42, 0.15) !important; border: 1px solid rgba(215, 24, 42, 0.4) !important; color: var(--light-gray) !important;">';
        foreach ($error_messages as $error) {
            echo esc_html($error) . '<br>';
        }
        echo '</div>';
    }
    
    // Display success message
    if (!empty($profile_success)) {
        echo '<div class="alert alert-success text-center mb-4" style="background: rgba(33, 37, 41, 0.25) !important; border: 1px solid rgba(255, 149, 0, 0.4) !important; color: var(--light-gray) !important;">';
        echo esc_html($profile_success);
        echo '</div>';
    }
    ?>
    
    <!-- Edit Profile Form -->
    <div class="row">
        <div class="col-12 mb-4">
            <div class="profile-card" style="background: rgba(33, 37, 41, 0.25); border: 1px solid rgba(255, 149, 0, 0.2); backdrop-filter: blur(15px); border-radius: 15px; padding: 25px; box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);">
                <h3 class="text-18pt fw-bold text-yellow mb-4">Edit Personal Information</h3>
                
                <form method="POST" action="" class="profile-edit-form">
                    <?php wp_nonce_field('update_influencer_profile', 'profile_nonce'); ?>
                    <input type="hidden" name="action" value="update_influencer_profile">
                    
                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <label for="first_name" class="form-label fs-6 text-yellow fw-bold">First Name</label>
                            <input type="text" class="form-control form-control-lg" id="first_name" name="first_name" value="<?php echo esc_attr($current_user->first_name); ?>" required 
                                   style="background: rgba(33, 37, 41, 0.8); border: 2px solid #ffc107; color: #fff; padding: 12px;">
                        </div>
                        
                        <div class="col-md-6 mb-4">
                            <label for="last_name" class="form-label fs-6 text-yellow fw-bold">Last Name</label>
                            <input type="text" class="form-control form-control-lg" id="last_name" name="last_name" value="<?php echo esc_attr($current_user->last_name); ?>" required 
                                   style="background: rgba(33, 37, 41, 0.8); border: 2px solid #ffc107; color: #fff; padding: 12px;">
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="country" class="form-label fs-6 text-yellow fw-bold">Country</label>
                        <select class="form-control form-control-lg" id="country" name="country" required 
                                style="background: rgba(33, 37, 41, 0.8); border: 2px solid #ffc107; color: #fff; padding: 12px;">
                            <option value="">Select Your Country</option>
                            <option value="US" <?php selected($country, 'US'); ?>>United States</option>
                            <option value="CA" <?php selected($country, 'CA'); ?>>Canada</option>
                            <option value="GB" <?php selected($country, 'GB'); ?>>United Kingdom</option>
                            <option value="AU" <?php selected($country, 'AU'); ?>>Australia</option>
                            <option value="DE" <?php selected($country, 'DE'); ?>>Germany</option>
                            <option value="FR" <?php selected($country, 'FR'); ?>>France</option>
                            <option value="ES" <?php selected($country, 'ES'); ?>>Spain</option>
                            <option value="IT" <?php selected($country, 'IT'); ?>>Italy</option>
                            <option value="NL" <?php selected($country, 'NL'); ?>>Netherlands</option>
                            <option value="BR" <?php selected($country, 'BR'); ?>>Brazil</option>
                            <option value="MX" <?php selected($country, 'MX'); ?>>Mexico</option>
                            <option value="JP" <?php selected($country, 'JP'); ?>>Japan</option>
                            <option value="KR" <?php selected($country, 'KR'); ?>>South Korea</option>
                            <option value="CN" <?php selected($country, 'CN'); ?>>China</option>
                            <option value="IN" <?php selected($country, 'IN'); ?>>India</option>
                            <option value="SG" <?php selected($country, 'SG'); ?>>Singapore</option>
                            <option value="other" <?php selected($country, 'other'); ?>>Other</option>
                        </select>
                    </div>
                    
                    <div class="text-center">
                        <button type="submit" class="btn btn-warning btn-lg px-5 py-3" style="background-color: #ffc107; border-color: #ffc107; color: #000; font-weight: bold;">
                            Update Profile
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Profile Information Cards -->
    <div class="row">
        
        <!-- Personal Information Card -->
        <div class="col-md-6 mb-4">
            <div class="profile-card" style="background: rgba(33, 37, 41, 0.25); border: 1px solid rgba(255, 149, 0, 0.2); backdrop-filter: blur(15px); border-radius: 15px; padding: 25px; box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);">
                <h3 class="text-18pt fw-bold text-yellow mb-4">Personal Information</h3>
                
                <div class="profile-field mb-3">
                    <label class="fs-6 text-yellow fw-bold">First Name:</label>
                    <p class="fs-5 text-light-gray mb-2"><?php echo esc_html($current_user->first_name ?: 'Not provided'); ?></p>
                </div>
                
                <div class="profile-field mb-3">
                    <label class="fs-6 text-yellow fw-bold">Last Name:</label>
                    <p class="fs-5 text-light-gray mb-2"><?php echo esc_html($current_user->last_name ?: 'Not provided'); ?></p>
                </div>
                
                <div class="profile-field mb-3">
                    <label class="fs-6 text-yellow fw-bold">Display Name:</label>
                    <p class="fs-5 text-light-gray mb-2"><?php echo esc_html($current_user->display_name); ?></p>
                </div>
                
                <div class="profile-field mb-0">
                    <label class="fs-6 text-yellow fw-bold">Country:</label>
                    <p class="fs-5 text-light-gray mb-0"><?php echo esc_html($country_name ?: 'Not provided'); ?></p>
                </div>
            </div>
        </div>
        
        <!-- Account Information Card -->
        <div class="col-md-6 mb-4">
            <div class="profile-card" style="background: rgba(33, 37, 41, 0.25); border: 1px solid rgba(255, 149, 0, 0.2); backdrop-filter: blur(15px); border-radius: 15px; padding: 25px; box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);">
                <h3 class="text-18pt fw-bold text-yellow mb-4">Account Information</h3>
                
                <div class="profile-field mb-3">
                    <label class="fs-6 text-yellow fw-bold">Email Address:</label>
                    <p class="fs-5 text-light-gray mb-2"><?php echo esc_html($current_user->user_email); ?></p>
                </div>
                
                <div class="profile-field mb-3">
                    <label class="fs-6 text-yellow fw-bold">User Role:</label>
                    <p class="fs-5 text-light-gray mb-2">
                        <span class="badge" style="background-color: #ffc107; color: #000; padding: 4px 12px; border-radius: 20px; font-weight: 600;">
                            <?php echo esc_html(ucfirst(implode(', ', $current_user->roles))); ?>
                        </span>
                    </p>
                </div>
                
                <div class="profile-field mb-3">
                    <label class="fs-6 text-yellow fw-bold">Member Since:</label>
                    <p class="fs-5 text-light-gray mb-2"><?php echo esc_html($registration_formatted); ?></p>
                </div>
                
                <div class="profile-field mb-0">
                    <label class="fs-6 text-yellow fw-bold">Last Login:</label>
                    <p class="fs-5 text-light-gray mb-0"><?php echo esc_html($last_login_formatted); ?></p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Influencer Status Card -->
    <div class="row">
        <div class="col-12 mb-4">
            <div class="profile-card" style="background: rgba(33, 37, 41, 0.25); border: 1px solid rgba(255, 149, 0, 0.2); backdrop-filter: blur(15px); border-radius: 15px; padding: 25px; box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);">
                <h3 class="text-18pt fw-bold text-yellow mb-4">Influencer Status</h3>
                
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <div class="profile-field">
                            <label class="fs-6 text-yellow fw-bold">User Type:</label>
                            <p class="fs-5 text-light-gray mb-0"><?php echo esc_html(ucfirst($user_type ?: 'Standard')); ?></p>
                        </div>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <div class="profile-field">
                            <label class="fs-6 text-yellow fw-bold">Equity Position:</label>
                            <p class="fs-5 text-light-gray mb-0">
                                <span class="badge" style="background-color: rgba(255, 149, 0, 0.2); color: #ffc107; border: 1px solid #ffc107; padding: 4px 12px; border-radius: 20px; font-weight: 600;">
                                    Secured
                                </span>
                            </p>
                        </div>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <div class="profile-field">
                            <label class="fs-6 text-yellow fw-bold">User ID:</label>
                            <p class="fs-5 text-light-gray mb-0">#<?php echo esc_html($user_id); ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="mt-4 p-3" style="background: rgba(255, 149, 0, 0.1); border-left: 4px solid #ffc107; border-radius: 5px;">
                    <p class="fs-5 text-light-gray mb-2"><strong class="text-yellow">Congratulations!</strong></p>
                    <p class="fs-6 text-light-gray mb-0">You've successfully secured your equity position in the Avantage World Championship. Your influence journey begins now!</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Action Buttons -->
    <div class="text-center mt-4">
        <div class="row">
            <div class="col-md-6 mb-3">
                <a href="<?php echo wp_logout_url(get_permalink()); ?>" class="btn btn-outline-warning btn-lg w-100" style="padding: 12px 20px;">
                    Logout
                </a>
            </div>
            <div class="col-md-6 mb-3">
                <button class="btn btn-warning btn-lg w-100" onclick="activateTabByHash('welcome')" style="background-color: #ffc107; border-color: #ffc107; color: #000; font-weight: bold; padding: 12px 20px;">
                    Back to Overview
                </button>
            </div>
        </div>
    </div>
    
</div>

<style>
    .profile-card {
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    
    .profile-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 12px 40px rgba(0, 0, 0, 0.4) !important;
    }
    
    .profile-field label {
        display: block;
        margin-bottom: 5px;
    }
    
    .profile-field p {
        word-break: break-word;
    }
    
    @media (max-width: 767px) {
        .profile-card {
            margin-bottom: 20px;
        }
        
        .text-18pt {
            font-size: 16pt !important;
        }
        
        .text-22pt {
            font-size: 20pt !important;
        }
    }
</style>