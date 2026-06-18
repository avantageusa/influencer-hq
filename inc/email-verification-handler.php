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
    $competition_preferences = ihq_sanitize_competition_preferences_input(
        isset( $_POST['competition_preferences'] ) ? wp_unslash( $_POST['competition_preferences'] ) : ''
    );
    $country_iso_client = isset( $_POST['country_iso'] ) ? sanitize_text_field( wp_unslash( $_POST['country_iso'] ) ) : '';

    if (!is_email($email)) {
        wp_send_json_error('Invalid email address');
        return;
    }
    
    if ( $password !== '' && strlen( $password ) < 6 ) {
        wp_send_json_error( 'Password must be at least 6 characters' );
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
        'password' => $password,
        'first_name' => $first_name,
        'last_name' => $last_name,
        'platform_handle' => $platform_handle,
        'comm_methods' => $comm_methods,
        'challenge_type' => $challenge_type,
        'competition_preferences' => $competition_preferences,
        'country_iso' => ihq_normalize_country_iso_alpha2( $country_iso_client ),
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

/** Registration / login email code validity in seconds. */
const IHQ_REG_CODE_EXPIRY_SECONDS = 900;

/** Registration send-code throttle per IP in seconds (1 attempt / 5 minutes). */
const IHQ_REG_IP_ATTEMPT_WINDOW_SECONDS = 300;

/** Login-only email code uses the same TTL as registration. */
const IHQ_LOGIN_CODE_EXPIRY_SECONDS = 900;

/**
 * Normalize comma-separated competition preference slug(s) from portal home (s7).
 *
 * @param mixed $raw Raw POST or stored string.
 * @return string Allowed tokens joined by comma, or empty string.
 */
function ihq_sanitize_competition_preferences_input( $raw ) {
    $allowed = array( 'world-competition', 'community-competition' );
    $raw     = is_string( $raw ) ? trim( wp_unslash( $raw ) ) : '';
    if ( $raw === '' ) {
        return '';
    }
    $parts = array_map( 'trim', explode( ',', $raw ) );
    $out   = array();
    foreach ( $parts as $p ) {
        if ( $p === '' ) {
            continue;
        }
        if ( in_array( $p, $allowed, true ) && ! in_array( $p, $out, true ) ) {
            $out[] = $p;
        }
    }
    return implode( ',', $out );
}

/**
 * Whether the WP user has the influencer role.
 *
 * @param WP_User $user User object.
 * @return bool
 */
function ihq_user_has_influencer_role( $user ) {
    return $user instanceof WP_User && in_array( 'influencer', (array) $user->roles, true );
}

/**
 * Create influencer user + meta + OAuth from pending registration data.
 *
 * @param array $registration_data Keys: email, password (optional — auto-generated if missing/short), first_name, last_name, platform_handle, comm_methods, challenge_type, competition_preferences (optional), country_iso (optional, client ISO 3166-1 alpha-2).
 * @return int|WP_Error User ID or error.
 */
function ihq_create_influencer_user_from_registration_data( array $registration_data ) {
    $email = isset( $registration_data['email'] ) ? sanitize_email( $registration_data['email'] ) : '';
    $password = isset( $registration_data['password'] ) ? (string) $registration_data['password'] : '';
    if ( $password === '' ) {
        $password = wp_generate_password( 32, true, true );
    }
    $first_name       = isset( $registration_data['first_name'] ) ? $registration_data['first_name'] : '';
    $last_name        = isset( $registration_data['last_name'] ) ? $registration_data['last_name'] : '';
    $platform_handle  = isset( $registration_data['platform_handle'] ) ? $registration_data['platform_handle'] : '';
    $comm_methods     = isset( $registration_data['comm_methods'] ) && is_array( $registration_data['comm_methods'] ) ? $registration_data['comm_methods'] : array();
    $challenge_type   = isset( $registration_data['challenge_type'] ) ? $registration_data['challenge_type'] : '';
    $competition_prefs = isset( $registration_data['competition_preferences'] )
        ? ihq_sanitize_competition_preferences_input( (string) $registration_data['competition_preferences'] )
        : '';
    $country_iso       = isset( $registration_data['country_iso'] ) ? (string) $registration_data['country_iso'] : '';
    $telegram_user_id  = isset( $registration_data['telegram_user_id'] ) ? (int) $registration_data['telegram_user_id'] : 0;

    if ( ! is_email( $email ) ) {
        return new WP_Error( 'invalid_email', 'Invalid email address' );
    }

    if ( email_exists( $email ) ) {
        return new WP_Error( 'email_exists', 'This email is already registered' );
    }

    $username = sanitize_user( current( explode( '@', $email ) ) );
    $original_username = $username;
    $counter           = 1;
    while ( username_exists( $username ) ) {
        $username = $original_username . $counter;
        ++$counter;
    }

    $user_id = wp_create_user( $username, $password, $email );
    if ( is_wp_error( $user_id ) ) {
        return $user_id;
    }

    if ( $first_name ) {
        update_user_meta( $user_id, 'first_name', $first_name );
    }
    if ( $last_name ) {
        update_user_meta( $user_id, 'last_name', $last_name );
    }
    if ( $platform_handle ) {
        update_user_meta( $user_id, 'platform_handle', $platform_handle );
    }

    $user = new WP_User( $user_id );
    $user->set_role( 'influencer' );

    if ( ! empty( $comm_methods ) ) {
        update_user_meta( $user_id, 'communication_methods', $comm_methods );
        $first_method = array_key_first( $comm_methods );
        if ( $first_method ) {
            update_user_meta( $user_id, 'preferred_communication', $first_method );
            update_user_meta( $user_id, 'communication_username', $comm_methods[ $first_method ] );
        }
    }
    if ( $telegram_user_id > 0 ) {
        update_user_meta( $user_id, 'telegram_user_id', $telegram_user_id );
    }

    if ( ! empty( $challenge_type ) ) {
        update_user_meta( $user_id, 'challenge_type', $challenge_type );
    }

    if ( $competition_prefs !== '' ) {
        update_user_meta( $user_id, 'competition_preferences', $competition_prefs );
    }

    update_user_meta( $user_id, 'registration_date', current_time( 'mysql' ) );
    update_user_meta( $user_id, 'email_verified', true );
    update_user_meta( $user_id, 'ihq_oauth_country_iso', ihq_normalize_country_iso_alpha2( $country_iso ) );

    $ihq_oauth_response = ihq_register_oauth_user( $user_id, $first_name, $last_name, $email, $country_iso );
    if ( $ihq_oauth_response && ! empty( $ihq_oauth_response['AccessToken'] ) ) {
        update_user_meta( $user_id, 'ihq_access_token', $ihq_oauth_response['AccessToken'] );
        update_user_meta( $user_id, 'ihq_id_token', $ihq_oauth_response['IdToken'] );
        update_user_meta( $user_id, 'ihq_refresh_token', $ihq_oauth_response['RefreshToken'] ?? '' );
        update_user_meta( $user_id, 'ihq_token_type', $ihq_oauth_response['TokenType'] ?? 'Bearer' );
        update_user_meta( $user_id, 'ihq_token_expires', time() + (int) ( $ihq_oauth_response['ExpiresIn'] ?? 3600 ) );
    }

    // Active Braze sync (replaces legacy Genius Referrals → Braze hook in functions.php).
    if ( function_exists( 'ihq_send_influencer_to_braze' ) ) {
        ihq_send_influencer_to_braze( $user_id );
    }

    if ( function_exists( 'ihq_mark_portal_username_pending' ) ) {
        ihq_mark_portal_username_pending( $user_id );
    }

    return (int) $user_id;
}

/**
 * Verify Turnstile for registration AJAX when keys are configured.
 *
 * @return true|WP_Error
 */
function ihq_verify_turnstile_or_error_for_ajax() {
    if ( function_exists( 'ihq_turnstile_is_configured' ) && ihq_turnstile_is_configured() ) {
        $token = isset( $_POST['cf-turnstile-response'] ) ? sanitize_text_field( wp_unslash( $_POST['cf-turnstile-response'] ) ) : '';
        $check = ihq_turnstile_verify_response( $token );
        if ( empty( $check['success'] ) ) {
            return new WP_Error( 'turnstile_failed', __( 'Human verification failed. Please try again.', 'avantage-baccarat' ) );
        }
    }
    return true;
}

/**
 * Best-effort client IP for rate limiting.
 *
 * @return string
 */
function ihq_get_client_ip_for_rate_limit() {
    $server_keys = array(
        'HTTP_CF_CONNECTING_IP',
        'HTTP_X_FORWARDED_FOR',
        'REMOTE_ADDR',
    );

    foreach ( $server_keys as $key ) {
        if ( empty( $_SERVER[ $key ] ) ) {
            continue;
        }
        $raw = (string) wp_unslash( $_SERVER[ $key ] );
        if ( $raw === '' ) {
            continue;
        }
        $candidate = trim( explode( ',', $raw )[0] );
        if ( $candidate !== '' ) {
            return $candidate;
        }
    }

    return '';
}

/**
 * Parse communication methods map from modal POST (JSON object).
 *
 * @return array<string, string>
 */
function ihq_parse_comm_methods_from_post() {
    if ( ! isset( $_POST['comm_methods'] ) ) {
        return array();
    }

    $raw = wp_unslash( $_POST['comm_methods'] );
    if ( ! is_string( $raw ) || $raw === '' ) {
        return array();
    }

    $decoded = json_decode( $raw, true );
    if ( ! is_array( $decoded ) ) {
        return array();
    }

    $methods = array();
    foreach ( $decoded as $method_key => $method_value ) {
        $key = sanitize_key( (string) $method_key );
        if ( $key === '' ) {
            continue;
        }
        $methods[ $key ] = sanitize_text_field( (string) $method_value );
    }

    return $methods;
}

/**
 * Send 6-digit registration code email (landing-page modal flow).
 */
function ihq_handle_send_registration_code_ajax() {
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'ihq_reg_code_nonce' ) ) {
        wp_send_json_error( array( 'message' => __( 'Invalid security token', 'avantage-baccarat' ) ) );
        return;
    }

    $turn = ihq_verify_turnstile_or_error_for_ajax();
    if ( is_wp_error( $turn ) ) {
        wp_send_json_error( array( 'message' => $turn->get_error_message() ) );
        return;
    }

    $email           = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
    $first_name      = isset( $_POST['first_name'] ) ? sanitize_text_field( wp_unslash( $_POST['first_name'] ) ) : '';
    $last_name       = isset( $_POST['last_name'] ) ? sanitize_text_field( wp_unslash( $_POST['last_name'] ) ) : '';
    $platform_handle = isset( $_POST['platform_handle'] ) ? sanitize_text_field( wp_unslash( $_POST['platform_handle'] ) ) : '';
    $challenge_type  = isset( $_POST['challenge_type'] ) ? sanitize_text_field( wp_unslash( $_POST['challenge_type'] ) ) : '';
    $comm_primary           = isset( $_POST['comm_primary'] ) ? sanitize_text_field( wp_unslash( $_POST['comm_primary'] ) ) : 'email';
    $telegram_user          = isset( $_POST['telegram_username'] ) ? sanitize_text_field( wp_unslash( $_POST['telegram_username'] ) ) : '';
    $telegram_session_token = isset( $_POST['telegram_session_token'] ) ? sanitize_text_field( wp_unslash( $_POST['telegram_session_token'] ) ) : '';
    $country_iso_raw        = isset( $_POST['country_iso'] ) ? sanitize_text_field( wp_unslash( $_POST['country_iso'] ) ) : '';
    $comm_methods           = ihq_parse_comm_methods_from_post();

    // Previously: force email code delivery when modal posted comm_methods including email.
    // if ( ! empty( $comm_methods ) && isset( $comm_methods['email'] ) ) {
    //     $comm_primary = 'email';
    // }

    if ( ! is_email( $email ) ) {
        wp_send_json_error( array( 'message' => __( 'Invalid email address', 'avantage-baccarat' ) ) );
        return;
    }

    if ( $first_name === '' || $last_name === '' ) {
        wp_send_json_error( array( 'message' => __( 'First and last name are required', 'avantage-baccarat' ) ) );
        return;
    }

    $telegram_user_id = 0;
    if ( $comm_primary === 'telegram' && empty( $comm_methods ) ) {
        $tu = ltrim( trim( $telegram_user ), '@' );
        if ( $tu === '' ) {
            wp_send_json_error( array( 'message' => __( 'Please enter your Telegram username', 'avantage-baccarat' ) ) );
            return;
        }
        if ( ! function_exists( 'ihq_get_telegram_registration_session' ) ) {
            wp_send_json_error( array( 'message' => __( 'Telegram registration is unavailable right now. Please choose Email.', 'avantage-baccarat' ) ) );
            return;
        }
        $tg_session = ihq_get_telegram_registration_session( $telegram_session_token );
        if ( ! is_array( $tg_session ) || empty( $tg_session['telegram_user_id'] ) || empty( $tg_session['telegram_username'] ) ) {
            wp_send_json_error( array( 'message' => __( 'Telegram login session expired. Please reselect Telegram and authenticate again.', 'avantage-baccarat' ) ) );
            return;
        }
        $session_username = ltrim( (string) $tg_session['telegram_username'], '@' );
        if ( strtolower( $session_username ) !== strtolower( $tu ) ) {
            wp_send_json_error( array( 'message' => __( 'Telegram account mismatch. Please authenticate again.', 'avantage-baccarat' ) ) );
            return;
        }
        $telegram_user_id = (int) $tg_session['telegram_user_id'];
        if ( $telegram_user_id <= 0 ) {
            wp_send_json_error( array( 'message' => __( 'Telegram account id missing. Please authenticate again.', 'avantage-baccarat' ) ) );
            return;
        }
        $comm_methods = array( 'telegram' => '@' . $tu );
    } else {
        if ( empty( $comm_methods ) ) {
            $comm_methods = array( 'email' => $email );
        } else {
            $comm_methods['email'] = $email;
        }
        $comm_primary = 'email';

        // Previously: optional Telegram session when portal posted multi-method comm_methods.
        // if ( isset( $comm_methods['telegram'] ) ) {
        //     $tu = ltrim( trim( $telegram_user !== '' ? $telegram_user : $comm_methods['telegram'] ), '@' );
        //     if ( $tu !== '' && function_exists( 'ihq_get_telegram_registration_session' ) ) {
        //         $tg_session = ihq_get_telegram_registration_session( $telegram_session_token );
        //         if ( is_array( $tg_session ) && ! empty( $tg_session['telegram_user_id'] ) ) {
        //             $session_username = ltrim( (string) $tg_session['telegram_username'], '@' );
        //             if ( strtolower( $session_username ) === strtolower( $tu ) ) {
        //                 $telegram_user_id = (int) $tg_session['telegram_user_id'];
        //                 $comm_methods['telegram'] = '@' . $tu;
        //             }
        //         }
        //     }
        // }
    }

    if ( email_exists( $email ) ) {
        wp_send_json_error( array( 'message' => __( 'This email is already registered', 'avantage-baccarat' ) ) );
        return;
    }

    $client_ip = ihq_get_client_ip_for_rate_limit();
    if ( $client_ip !== '' ) {
        $ip_throttle_key = 'ihq_reg_send_ip_' . md5( $client_ip );
        if ( get_transient( $ip_throttle_key ) ) {
            wp_send_json_error( array( 'message' => __( 'Only one registration attempt is allowed every 5 minutes from this IP', 'avantage-baccarat' ) ) );
            return;
        }
        set_transient( $ip_throttle_key, 1, IHQ_REG_IP_ATTEMPT_WINDOW_SECONDS );
    }

    $throttle_key = 'ihq_reg_send_' . md5( strtolower( $email ) );
    if ( get_transient( $throttle_key ) ) {
        wp_send_json_error( array( 'message' => __( 'Please wait a moment before requesting another code', 'avantage-baccarat' ) ) );
        return;
    }
    set_transient( $throttle_key, 1, 45 );

    $email_map_key = 'ihq_pending_reg_email_' . md5( strtolower( $email ) );
    $old_token     = get_option( $email_map_key, '' );
    if ( is_string( $old_token ) && $old_token !== '' ) {
        delete_option( 'pending_reg_code_' . $old_token );
    }

    $signup_token = wp_generate_password( 32, false, false );
    $code         = sprintf( '%06d', wp_rand( 0, 999999 ) );
    $code_hash    = hash_hmac( 'sha256', $code, wp_salt( 'ihq_reg_code' ) . $signup_token );

    $expires = time() + IHQ_REG_CODE_EXPIRY_SECONDS;

    $record = array(
        'email'            => $email,
        'first_name'       => $first_name,
        'last_name'        => $last_name,
        'platform_handle'  => $platform_handle,
        'comm_methods'     => $comm_methods,
        'challenge_type'   => $challenge_type,
        'country_iso'      => ihq_normalize_country_iso_alpha2( $country_iso_raw ),
        'telegram_user_id' => $telegram_user_id,
        'code_hash'        => $code_hash,
        'expires'          => $expires,
        'timestamp'        => time(),
    );

    update_option( 'pending_reg_code_' . $signup_token, $record, false );
    update_option( $email_map_key, $signup_token, false );

    $minutes_left = (int) ceil( IHQ_REG_CODE_EXPIRY_SECONDS / 60 );

    $delivery_error = null;
    if ( 'telegram' === $comm_primary ) {
        if ( ! function_exists( 'ihq_telegram_send_direct_message' ) ) {
            $delivery_error = __( 'Telegram delivery is unavailable right now. Please choose Email.', 'avantage-baccarat' );
        } else {
            $telegram_message = sprintf(
                /* translators: 1: 6-digit code, 2: expiry minutes */
                __( "Influencer HQ registration code: %1\$s\n\nThis code expires in %2\$d minutes.", 'avantage-baccarat' ),
                $code,
                (int) $minutes_left
            );
            $send_result = ihq_telegram_send_direct_message( $telegram_user_id, $telegram_message );
            if ( is_wp_error( $send_result ) ) {
                $delivery_error = $send_result->get_error_message();
            }
        }
        if ( null !== $delivery_error ) {
            delete_option( 'pending_reg_code_' . $signup_token );
            delete_option( $email_map_key );
            wp_send_json_error( array( 'message' => $delivery_error ) );
            return;
        }
    } else {
        $subject = __( 'Your Influencer HQ registration code', 'avantage-baccarat' );
        $message = '
    <!DOCTYPE html>
    <html><head><meta charset="UTF-8"></head>
    <body style="margin:0;padding:0;font-family:Arial,Helvetica,sans-serif;background:#0a0a0a;color:#f0f0f0;">
      <table width="100%" cellpadding="0" cellspacing="0" style="background:#0a0a0a;padding:40px 20px;">
        <tr><td align="center">
          <table width="560" cellpadding="0" cellspacing="0" style="background:#161612;border:1px solid rgba(240,201,58,.35);border-radius:12px;">
            <tr><td style="padding:40px 32px;text-align:center;">
              <h1 style="color:#F0C93A;font-size:26px;margin:0 0 16px;">Influencer HQ</h1>
              <p style="color:#EAD9B0;font-size:16px;line-height:1.6;margin:0 0 24px;">Your registration code is:</p>
              <div style="font-size:36px;font-weight:700;letter-spacing:12px;color:#fff;margin:16px 0 24px;">' . esc_html( $code ) . '</div>
              <p style="color:#888;font-size:14px;line-height:1.6;margin:0;">This code expires in ' . (int) $minutes_left . ' minutes.</p>
            </td></tr>
          </table>
        </td></tr>
      </table>
    </body></html>';

    $headers = array(
        'Content-Type: text/html; charset=UTF-8',
        'From: Influencer HQ <verify@influencerhq.co>',
    );

    $mail_error = null;
    $failed_hook = function ( $wp_error ) use ( &$mail_error ) {
        $mail_error = $wp_error->get_error_message();
    };
    add_action( 'wp_mail_failed', $failed_hook );

    $sent = wp_mail( $email, $subject, $message, $headers );
    remove_action( 'wp_mail_failed', $failed_hook );

    if ( ! $sent ) {
        delete_option( 'pending_reg_code_' . $signup_token );
        delete_option( $email_map_key );
        $err = $mail_error ? $mail_error : __( 'Failed to send email', 'avantage-baccarat' );
        error_log( 'IHQ send_registration_code failed for ' . $email . ': ' . $err );
        wp_send_json_error( array( 'message' => $err ) );
        return;
    }
    }

    wp_send_json_success(
        array(
            'signup_token'     => $signup_token,
            'expires_minutes'  => $minutes_left,
        )
    );
}
add_action( 'wp_ajax_ihq_send_registration_code', 'ihq_handle_send_registration_code_ajax' );
add_action( 'wp_ajax_nopriv_ihq_send_registration_code', 'ihq_handle_send_registration_code_ajax' );

/**
 * Verify 6-digit code and complete registration (modal flow).
 */
function ihq_handle_verify_registration_code_ajax() {
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'ihq_reg_code_nonce' ) ) {
        wp_send_json_error( array( 'message' => __( 'Invalid security token', 'avantage-baccarat' ) ) );
        return;
    }

    $signup_token = isset( $_POST['signup_token'] ) ? sanitize_text_field( wp_unslash( $_POST['signup_token'] ) ) : '';
    $code_raw     = isset( $_POST['code'] ) ? preg_replace( '/\D/', '', (string) wp_unslash( $_POST['code'] ) ) : '';

    if ( $signup_token === '' || strlen( $code_raw ) !== 6 ) {
        wp_send_json_error( array( 'message' => __( 'Enter the 6-digit code we sent you', 'avantage-baccarat' ) ) );
        return;
    }

    $opt_key  = 'pending_reg_code_' . $signup_token;
    $pending  = get_option( $opt_key );
    if ( ! is_array( $pending ) || empty( $pending['email'] ) ) {
        wp_send_json_error( array( 'message' => __( 'Invalid or expired code. Please start again', 'avantage-baccarat' ) ) );
        return;
    }

    if ( time() > (int) $pending['expires'] ) {
        delete_option( $opt_key );
        $emap = 'ihq_pending_reg_email_' . md5( strtolower( $pending['email'] ) );
        delete_option( $emap );
        wp_send_json_error( array( 'message' => __( 'This code has expired. Request a new one', 'avantage-baccarat' ) ) );
        return;
    }

    $expected_hash = isset( $pending['code_hash'] ) ? $pending['code_hash'] : '';
    $try_hash      = hash_hmac( 'sha256', $code_raw, wp_salt( 'ihq_reg_code' ) . $signup_token );

    if ( ! hash_equals( $expected_hash, $try_hash ) ) {
        wp_send_json_error( array( 'message' => __( 'That code does not match. Check your email and try again', 'avantage-baccarat' ) ) );
        return;
    }

    $posted_country = isset( $_POST['country_iso'] ) ? sanitize_text_field( wp_unslash( $_POST['country_iso'] ) ) : '';
    $stored_country = isset( $pending['country_iso'] ) ? (string) $pending['country_iso'] : '';
    $pending['country_iso'] = ihq_normalize_country_iso_alpha2(
        ( $posted_country !== '' ) ? $posted_country : $stored_country
    );

    $emap    = 'ihq_pending_reg_email_' . md5( strtolower( $pending['email'] ) );
    $user_id = ihq_create_influencer_user_from_pending_data_normalized( $pending );

    if ( is_wp_error( $user_id ) ) {
        delete_option( $opt_key );
        delete_option( $emap );
        wp_send_json_error( array( 'message' => $user_id->get_error_message() ) );
        return;
    }

    delete_option( $opt_key );
    delete_option( $emap );

    wp_set_current_user( $user_id );
    wp_set_auth_cookie( $user_id, false );

    wp_send_json_success(
        array(
            'redirect_url' => function_exists( 'ihq_portal_profile_setup_url' )
                ? ihq_portal_profile_setup_url()
                : add_query_arg( 'setup_portal_username', '1', home_url( '/portal/account/' ) ),
        )
    );
}
add_action( 'wp_ajax_ihq_verify_registration_code', 'ihq_handle_verify_registration_code_ajax' );
add_action( 'wp_ajax_nopriv_ihq_verify_registration_code', 'ihq_handle_verify_registration_code_ajax' );

/**
 * @param array $pending Row from pending_reg_code_* option.
 * @return int|WP_Error
 */
function ihq_create_influencer_user_from_pending_data_normalized( array $pending ) {
    $data = array(
        'email'           => sanitize_email( $pending['email'] ),
        'password'        => isset( $pending['password'] ) ? $pending['password'] : '',
        'first_name'      => isset( $pending['first_name'] ) ? $pending['first_name'] : '',
        'last_name'       => isset( $pending['last_name'] ) ? $pending['last_name'] : '',
        'platform_handle' => isset( $pending['platform_handle'] ) ? $pending['platform_handle'] : '',
        'comm_methods'    => isset( $pending['comm_methods'] ) && is_array( $pending['comm_methods'] ) ? $pending['comm_methods'] : array(),
        'challenge_type'             => isset( $pending['challenge_type'] ) ? $pending['challenge_type'] : '',
        'competition_preferences'    => isset( $pending['competition_preferences'] ) ? $pending['competition_preferences'] : '',
        'country_iso'                => isset( $pending['country_iso'] ) ? (string) $pending['country_iso'] : '',
        'telegram_user_id'           => isset( $pending['telegram_user_id'] ) ? (int) $pending['telegram_user_id'] : 0,
    );
    return ihq_create_influencer_user_from_registration_data( $data );
}

/**
 * Default ISO 3166-1 alpha-2 when CF-IPCountry is missing or meaningless.
 * Matches fallback used on `page-portal-home.php`.
 */
const IHQ_CLOUDFLARE_COUNTRY_ISO_FALLBACK = 'US';

/**
 * Cloudflare uses XX when country cannot be determined.
 *
 * @link https://developers.cloudflare.com/fundamentals/reference/http-request-headers/#cf-ipcountry
 */
const IHQ_CLOUDFLARE_IPCOUNTRY_UNKNOWN = 'XX';

/**
 * Normalize a string to ISO 3166-1 alpha-2 (ASCII two letters).
 *
 * @param string $raw Trimmed uppercase candidate (e.g. from CF header).
 * @return string Two-letter uppercase code or {@see IHQ_CLOUDFLARE_COUNTRY_ISO_FALLBACK}.
 */
function ihq_normalize_country_iso_alpha2( $raw ) {
    $code = strtoupper( trim( (string) $raw ) );
    if ( strlen( $code ) !== 2 || ! ctype_alpha( $code ) ) {
        return IHQ_CLOUDFLARE_COUNTRY_ISO_FALLBACK;
    }
    if ( $code === IHQ_CLOUDFLARE_IPCOUNTRY_UNKNOWN ) {
        return IHQ_CLOUDFLARE_COUNTRY_ISO_FALLBACK;
    }
    return $code;
}

/**
 * ISO 3166-1 alpha-2 from Cloudflare `CF-IPCountry` (`HTTP_CF_IPCOUNTRY`) on this request (also available behind WP Engine with Cloudflare edge).
 *
 * @return string
 */
function ihq_get_cloudflare_country_iso_alpha2() {
    if ( empty( $_SERVER['HTTP_CF_IPCOUNTRY'] ) ) {
        return IHQ_CLOUDFLARE_COUNTRY_ISO_FALLBACK;
    }
    $raw = sanitize_text_field( wp_unslash( $_SERVER['HTTP_CF_IPCOUNTRY'] ) );
    return ihq_normalize_country_iso_alpha2( $raw );
}

/**
 * Refresh IHQ platform OAuth tokens after sign-in (used by passwordless login).
 *
 * @param int    $user_id WordPress user ID.
 * @param string $country_iso Raw client ISO 3166-1 alpha-2 (normalized in {@see ihq_register_oauth_user}).
 */
function ihq_refresh_influencer_oauth_tokens( $user_id, $country_iso = '' ) {
    $user_id = (int) $user_id;
    if ( $user_id <= 0 ) {
        return;
    }
    $user = get_user_by( 'id', $user_id );
    if ( ! $user ) {
        return;
    }
    $first_name = get_user_meta( $user_id, 'first_name', true );
    $last_name  = get_user_meta( $user_id, 'last_name', true );
    $ihq_data   = ihq_register_oauth_user( $user_id, $first_name, $last_name, $user->user_email, $country_iso );
    if ( $ihq_data && ! empty( $ihq_data['AccessToken'] ) ) {
        update_user_meta( $user_id, 'ihq_oauth_country_iso', ihq_normalize_country_iso_alpha2( $country_iso ) );
        update_user_meta( $user_id, 'ihq_access_token', $ihq_data['AccessToken'] );
        update_user_meta( $user_id, 'ihq_id_token', $ihq_data['IdToken'] );
        update_user_meta( $user_id, 'ihq_refresh_token', $ihq_data['RefreshToken'] ?? '' );
        update_user_meta( $user_id, 'ihq_token_type', $ihq_data['TokenType'] ?? 'Bearer' );
        update_user_meta( $user_id, 'ihq_token_expires', time() + (int) ( $ihq_data['ExpiresIn'] ?? 3600 ) );
    }
}

/**
 * Passwordless influencer login: send 6-digit email code.
 */
function ihq_handle_send_login_code_ajax() {
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'ihq_login_code_nonce' ) ) {
        wp_send_json_error( array( 'message' => __( 'Invalid security token', 'avantage-baccarat' ) ) );
        return;
    }

    $turn = ihq_verify_turnstile_or_error_for_ajax();
    if ( is_wp_error( $turn ) ) {
        wp_send_json_error( array( 'message' => $turn->get_error_message() ) );
        return;
    }

    $email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
    if ( ! is_email( $email ) ) {
        wp_send_json_error( array( 'message' => __( 'Invalid email address', 'avantage-baccarat' ) ) );
        return;
    }

    $user = get_user_by( 'email', $email );

    /**
     * Always return the same success copy if the inbox exists — avoids leaking which emails are registered.
     * Only deliver a code when the user exists and is an influencer.
     */
    $generic_success = __( 'If that email matches an Influencer HQ account, you will receive a sign-in code shortly.', 'avantage-baccarat' );

    if ( ! $user || ! ihq_user_has_influencer_role( $user ) ) {
        wp_send_json_success(
            array(
                'signup_token'    => '',
                'expires_minutes' => (int) ceil( IHQ_LOGIN_CODE_EXPIRY_SECONDS / 60 ),
                'message'         => $generic_success,
                'skipped'         => true,
            )
        );
        return;
    }

    $throttle_key = 'ihq_login_send_' . md5( strtolower( $email ) );
    if ( get_transient( $throttle_key ) ) {
        wp_send_json_error( array( 'message' => __( 'Please wait a moment before requesting another code', 'avantage-baccarat' ) ) );
        return;
    }
    set_transient( $throttle_key, 1, 45 );

    $email_map_key = 'ihq_pending_login_email_' . md5( strtolower( $email ) );
    $old_token     = get_option( $email_map_key, '' );
    if ( is_string( $old_token ) && $old_token !== '' ) {
        delete_option( 'pending_login_code_' . $old_token );
    }

    $signup_token = wp_generate_password( 32, false, false );
    $code         = sprintf( '%06d', wp_rand( 0, 999999 ) );
    $code_hash    = hash_hmac( 'sha256', $code, wp_salt( 'ihq_login_code' ) . $signup_token );

    $expires = time() + IHQ_LOGIN_CODE_EXPIRY_SECONDS;

    $record = array(
        'email'       => $email,
        'user_id'     => (int) $user->ID,
        'code_hash'   => $code_hash,
        'expires'     => $expires,
        'timestamp'   => time(),
    );

    update_option( 'pending_login_code_' . $signup_token, $record, false );
    update_option( $email_map_key, $signup_token, false );

    $minutes_left = (int) ceil( IHQ_LOGIN_CODE_EXPIRY_SECONDS / 60 );

    $subject = __( 'Your Influencer HQ sign-in code', 'avantage-baccarat' );
    $message = '
    <!DOCTYPE html>
    <html><head><meta charset="UTF-8"></head>
    <body style="margin:0;padding:0;font-family:Arial,Helvetica,sans-serif;background:#0a0a0a;color:#f0f0f0;">
      <table width="100%" cellpadding="0" cellspacing="0" style="background:#0a0a0a;padding:40px 20px;">
        <tr><td align="center">
          <table width="560" cellpadding="0" cellspacing="0" style="background:#161612;border:1px solid rgba(240,201,58,.35);border-radius:12px;">
            <tr><td style="padding:40px 32px;text-align:center;">
              <h1 style="color:#F0C93A;font-size:26px;margin:0 0 16px;">Influencer HQ</h1>
              <p style="color:#EAD9B0;font-size:16px;line-height:1.6;margin:0 0 24px;">Your sign-in code is:</p>
              <div style="font-size:36px;font-weight:700;letter-spacing:12px;color:#fff;margin:16px 0 24px;">' . esc_html( $code ) . '</div>
              <p style="color:#888;font-size:14px;line-height:1.6;margin:0;">This code expires in ' . (int) $minutes_left . ' minutes.</p>
            </td></tr>
          </table>
        </td></tr>
      </table>
    </body></html>';

    $headers = array(
        'Content-Type: text/html; charset=UTF-8',
        'From: Influencer HQ <verify@influencerhq.co>',
    );

    $mail_error = null;
    $failed_hook = function ( $wp_error ) use ( &$mail_error ) {
        $mail_error = $wp_error->get_error_message();
    };
    add_action( 'wp_mail_failed', $failed_hook );

    $sent = wp_mail( $email, $subject, $message, $headers );
    remove_action( 'wp_mail_failed', $failed_hook );

    if ( ! $sent ) {
        delete_option( 'pending_login_code_' . $signup_token );
        delete_option( $email_map_key );
        $err = $mail_error ? $mail_error : __( 'Failed to send email', 'avantage-baccarat' );
        error_log( 'IHQ send_login_code failed for ' . $email . ': ' . $err );
        wp_send_json_error( array( 'message' => $err ) );
        return;
    }

    wp_send_json_success(
        array(
            'signup_token'    => $signup_token,
            'expires_minutes' => $minutes_left,
            'message'         => $generic_success,
        )
    );
}
add_action( 'wp_ajax_ihq_send_login_code', 'ihq_handle_send_login_code_ajax' );
add_action( 'wp_ajax_nopriv_ihq_send_login_code', 'ihq_handle_send_login_code_ajax' );

/**
 * Passwordless influencer login: verify 6-digit email code.
 */
function ihq_handle_verify_login_code_ajax() {
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'ihq_login_code_nonce' ) ) {
        wp_send_json_error( array( 'message' => __( 'Invalid security token', 'avantage-baccarat' ) ) );
        return;
    }

    $signup_token = isset( $_POST['signup_token'] ) ? sanitize_text_field( wp_unslash( $_POST['signup_token'] ) ) : '';
    $code_raw     = isset( $_POST['code'] ) ? preg_replace( '/\D/', '', (string) wp_unslash( $_POST['code'] ) ) : '';

    if ( $signup_token === '' || strlen( $code_raw ) !== 6 ) {
        wp_send_json_error( array( 'message' => __( 'Enter the 6-digit code from your email', 'avantage-baccarat' ) ) );
        return;
    }

    $opt_key = 'pending_login_code_' . $signup_token;
    $pending = get_option( $opt_key );
    if ( ! is_array( $pending ) || empty( $pending['email'] ) ) {
        wp_send_json_error( array( 'message' => __( 'Invalid or expired code. Request a new one', 'avantage-baccarat' ) ) );
        return;
    }

    if ( time() > (int) $pending['expires'] ) {
        delete_option( $opt_key );
        $emap = 'ihq_pending_login_email_' . md5( strtolower( $pending['email'] ) );
        delete_option( $emap );
        wp_send_json_error( array( 'message' => __( 'This code has expired. Request a new one', 'avantage-baccarat' ) ) );
        return;
    }

    $expected_hash = isset( $pending['code_hash'] ) ? $pending['code_hash'] : '';
    $try_hash      = hash_hmac( 'sha256', $code_raw, wp_salt( 'ihq_login_code' ) . $signup_token );

    if ( ! hash_equals( $expected_hash, $try_hash ) ) {
        wp_send_json_error( array( 'message' => __( 'That code does not match. Check your email and try again', 'avantage-baccarat' ) ) );
        return;
    }

    $user_id = isset( $pending['user_id'] ) ? (int) $pending['user_id'] : 0;
    $user    = ( $user_id > 0 ) ? get_user_by( 'id', $user_id ) : null;

    if ( ! $user || ! ihq_user_has_influencer_role( $user ) ) {
        $user = get_user_by( 'email', sanitize_email( $pending['email'] ) );
        if ( ! $user || ! ihq_user_has_influencer_role( $user ) ) {
            delete_option( $opt_key );
            $emap = 'ihq_pending_login_email_' . md5( strtolower( $pending['email'] ) );
            delete_option( $emap );
            wp_send_json_error( array( 'message' => __( 'Invalid or expired code. Request a new one', 'avantage-baccarat' ) ) );
            return;
        }
        $user_id = (int) $user->ID;
    }

    $emap = 'ihq_pending_login_email_' . md5( strtolower( $pending['email'] ) );
    delete_option( $opt_key );
    delete_option( $emap );

    wp_set_current_user( $user_id );
    wp_set_auth_cookie( $user_id, false );

    $country_iso_login = isset( $_POST['country_iso'] ) ? sanitize_text_field( wp_unslash( $_POST['country_iso'] ) ) : '';
    ihq_refresh_influencer_oauth_tokens( $user_id, $country_iso_login );

    $redirect = isset( $_POST['redirect_url'] ) ? esc_url_raw( wp_unslash( $_POST['redirect_url'] ) ) : home_url( '/portal/portal-home/' );
    if ( $redirect === '' ) {
        $redirect = home_url( '/portal/portal-home/' );
    }

    wp_send_json_success(
        array(
            'redirect_url' => $redirect,
        )
    );
}
add_action( 'wp_ajax_ihq_verify_login_code', 'ihq_handle_verify_login_code_ajax' );
add_action( 'wp_ajax_nopriv_ihq_verify_login_code', 'ihq_handle_verify_login_code_ajax' );

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
    
    $comm_methods      = isset( $registration_data['comm_methods'] ) && is_array( $registration_data['comm_methods'] ) ? $registration_data['comm_methods'] : array();
    $challenge_type    = isset( $registration_data['challenge_type'] ) ? $registration_data['challenge_type'] : '';
    $competition_prefs = isset( $registration_data['competition_preferences'] ) ? $registration_data['competition_preferences'] : '';
    $country_iso       = isset( $registration_data['country_iso'] ) ? (string) $registration_data['country_iso'] : '';

    // Check if email already exists
    if ( email_exists( $registration_data['email'] ) ) {
        delete_option( 'pending_registration_' . $token );
        wp_die( 'This email is already registered. Please login instead.', 'Already Registered', array( 'response' => 400 ) );
        return;
    }

    $result = ihq_create_influencer_user_from_registration_data(
        array(
            'email'           => $registration_data['email'],
            'password'        => $registration_data['password'],
            'first_name'      => isset( $registration_data['first_name'] ) ? $registration_data['first_name'] : '',
            'last_name'       => isset( $registration_data['last_name'] ) ? $registration_data['last_name'] : '',
            'platform_handle' => isset( $registration_data['platform_handle'] ) ? $registration_data['platform_handle'] : '',
            'comm_methods'    => $comm_methods,
            'challenge_type'             => $challenge_type,
            'competition_preferences'    => $competition_prefs,
            'country_iso'                => $country_iso,
        )
    );

    if ( is_wp_error( $result ) ) {
        wp_die( 'Failed to create account: ' . esc_html( $result->get_error_message() ), 'Registration Failed', array( 'response' => 500 ) );
        return;
    }

    $user_id = $result;

    // Delete the temporary registration data
    delete_option( 'pending_registration_' . $token );

    // Log the user in automatically
    wp_set_current_user( $user_id );
    wp_set_auth_cookie( $user_id, false );
    
    // Redirect to profile for mandatory portal username setup.
    $redirect_url = function_exists( 'ihq_portal_profile_setup_url' )
        ? ihq_portal_profile_setup_url()
        : add_query_arg( 'setup_portal_username', '1', home_url( '/portal/account/' ) );

    wp_redirect( $redirect_url );
    exit;
}

// Clean up expired registration tokens daily
add_action('wp_scheduled_delete', 'cleanup_expired_registrations');

if ( ! defined( 'IHQ_INFLUENCER_API_KEY' ) ) {
	define( 'IHQ_INFLUENCER_API_KEY', 'Z9sSPTV0lV95EcnFlajWua9G9mQGBYns7lyZZL59' );
}

/**
 * Headers for POST /account/oauth/start-session (server-side only).
 *
 * @return array<string, string>
 */
function ihq_oauth_start_session_request_headers() {
	$headers = array(
		'Authorization' => 'milos_testing',
		'Content-Type'  => 'application/json',
	);
	if ( defined( 'IHQ_INFLUENCER_API_KEY' ) && IHQ_INFLUENCER_API_KEY !== '' ) {
		$headers['x-api-key'] = IHQ_INFLUENCER_API_KEY;
	}
	return $headers;
}

/**
 * User meta key for iframe SSO code from start-session (`ssoCode`).
 *
 * @return string
 */
function ihq_sso_code_meta_key() {
	return 'ihq_sso_code';
}

/**
 * @param array<string, mixed> $data Start-session `data` object or error payload.
 * @return string
 */
function ihq_extract_sso_code_from_start_session_data( $data ) {
	if ( ! is_array( $data ) ) {
		return '';
	}
	$keys = array( 'ssoCode', 'hqSsoCode', 'sso_code' );
	foreach ( $keys as $key ) {
		if ( ! empty( $data[ $key ] ) && is_string( $data[ $key ] ) ) {
			return sanitize_text_field( $data[ $key ] );
		}
	}
	return '';
}

/**
 * @param int $user_id WordPress user ID.
 * @return string
 */
function ihq_get_hq_sso_code_for_user( $user_id ) {
	$user_id = (int) $user_id;
	if ( $user_id <= 0 ) {
		return '';
	}

	$stored = get_user_meta( $user_id, ihq_sso_code_meta_key(), true );
	if ( is_string( $stored ) && $stored !== '' ) {
		return sanitize_text_field( $stored );
	}

	$snapshot = get_user_meta( $user_id, 'ihq_oauth_start_session_last', true );
	if ( ! is_string( $snapshot ) || $snapshot === '' ) {
		return '';
	}

	$decoded = json_decode( $snapshot, true );
	if ( ! is_array( $decoded ) ) {
		return '';
	}

	return ihq_extract_sso_code_from_start_session_data( $decoded );
}

/**
 * Save start-session response for display on the profile page.
 *
 * @param int                           $user_id WordPress user ID.
 * @param array<string, mixed>|false    $data    Parsed `data` object or error payload.
 */
function ihq_save_start_session_response_for_profile( $user_id, $data ) {
	$user_id = (int) $user_id;
	if ( $user_id <= 0 ) {
		return;
	}

	if ( ! is_array( $data ) ) {
		update_user_meta(
			$user_id,
			'ihq_oauth_start_session_last',
			wp_json_encode( array( 'error' => 'start-session failed' ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES )
		);
		return;
	}

	update_user_meta(
		$user_id,
		'ihq_oauth_start_session_last',
		wp_json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES )
	);

	$sso_code = ihq_extract_sso_code_from_start_session_data( $data );
	if ( $sso_code !== '' ) {
		update_user_meta( $user_id, ihq_sso_code_meta_key(), $sso_code );
	}
}

/**
 * Register a new WP user in the InfluencerHQ platform via OAuth start-session.
 *
 * @param string $country_iso Raw client ISO 3166-1 alpha-2 (typically from browser `country_iso` POST field); sanitized before send.
 *
 * Returns the parsed `data` object on success, or false on failure.
 */
function ihq_register_oauth_user( $user_id, $first_name, $last_name, $email, $country_iso = '' ) {
    $api_url = 'https://02nvfvonol.execute-api.eu-west-2.amazonaws.com/qc/account/oauth/start-session';

    $payload = array(
        'oauthLoginType' => 'InfluencerHq',
        'payload' => array(
            'id'         => 'wpu-' . $user_id,
            'firstName'  => $first_name,
            'lastName'   => $last_name,
            'email'      => $email,
            'countryIso' => ihq_normalize_country_iso_alpha2( $country_iso ),
        ),
    );

    $response = wp_remote_post($api_url, array(
        'headers' => ihq_oauth_start_session_request_headers(),
        'body'      => wp_json_encode($payload),
        'timeout'   => 30,
        'sslverify' => true,
    ));

    if (is_wp_error($response)) {
        error_log('IHQ OAuth register error for user ' . $user_id . ': ' . $response->get_error_message());
        ihq_save_start_session_response_for_profile( $user_id, array(
            'success' => false,
            'error'   => $response->get_error_message(),
        ) );
        return false;
    }

    $body   = json_decode(wp_remote_retrieve_body($response), true);
    $status = wp_remote_retrieve_response_code($response);

    $success = !empty($body['success']) && $body['success'] === true && !empty($body['data']);
    if (!$success) {
        error_log('IHQ OAuth register bad response (HTTP ' . $status . ') for user ' . $user_id . ': ' . wp_remote_retrieve_body($response));
        ihq_save_start_session_response_for_profile(
            $user_id,
            is_array( $body ) ? $body : array( 'http_status' => $status, 'raw' => wp_remote_retrieve_body( $response ) )
        );
        return false;
    }

    ihq_save_start_session_response_for_profile( $user_id, $body['data'] );

    return $body['data'];
}

function cleanup_expired_registrations() {
    global $wpdb;
    
    // Get all pending registration options
    $results = $wpdb->get_results(
        "SELECT option_name, option_value FROM {$wpdb->options}
        WHERE option_name LIKE 'pending_registration_%'
        OR option_name LIKE 'pending_reg_code_%'
        OR option_name LIKE 'pending_login_code_%'",
        ARRAY_A
    );

    $current_time = time();

    foreach ($results as $row) {
        $data = maybe_unserialize($row['option_value']);
        if ( isset( $data['expires'] ) && $current_time > $data['expires'] ) {
            delete_option( $row['option_name'] );
            $name = $row['option_name'];
            if ( strpos( $name, 'pending_reg_code_' ) === 0 && is_array( $data ) && ! empty( $data['email'] ) ) {
                delete_option( 'ihq_pending_reg_email_' . md5( strtolower( $data['email'] ) ) );
            }
            if ( strpos( $name, 'pending_login_code_' ) === 0 && is_array( $data ) && ! empty( $data['email'] ) ) {
                delete_option( 'ihq_pending_login_email_' . md5( strtolower( $data['email'] ) ) );
            }
        }
    }
}
