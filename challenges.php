<?php
/**
 * Template Name: Challenge Handler
 * Description: Handles private challenge invitation links and the create-challenge AJAX action.
 *
 * @package Avantage_Baccarat
 */

// ── Resolve challenge from token ──────────────────────────────────────────────
$_ch_token = isset( $_GET['token'] ) ? sanitize_text_field( wp_unslash( $_GET['token'] ) ) : '';
$_ch_post  = null;

if ( $_ch_token ) {
    $found = get_posts( [
        'post_type'      => 'challenge',
        'post_status'    => 'publish',
        'posts_per_page' => 1,
        'meta_query'     => [ [ 'key' => '_challenge_token', 'value' => $_ch_token, 'compare' => '=' ] ],
    ] );
    $_ch_post = $found[0] ?? null;
}

$_ch_invalid = ( ! $_ch_token || ! $_ch_post );

if ( ! $_ch_invalid ) {
    $_ch_id         = $_ch_post->ID;
    $_ch_inv_email  = (string) get_post_meta( $_ch_id, '_invitee_email',      true );
    $_ch_inv_fname  = (string) get_post_meta( $_ch_id, '_invitee_first_name', true );
    $_ch_inv_lname  = (string) get_post_meta( $_ch_id, '_invitee_last_name',  true );
    $_ch_status     = (string) get_post_meta( $_ch_id, '_challenge_status',   true );
    $_ch_chal_id    = (int)    get_post_meta( $_ch_id, '_challenger_id',      true );

    // Case 1a: already accepted + logged in → go to challenges
    if ( $_ch_status === 'accepted' && is_user_logged_in() ) {
        wp_redirect( home_url( '/portal/challenges/' ) );
        exit;
    }

    // Case 1b: not yet accepted + logged in → accept immediately
    if ( $_ch_status !== 'accepted' && is_user_logged_in() ) {
        $uid = get_current_user_id();
        update_post_meta( $_ch_id, '_challenge_status', 'accepted' );
        update_post_meta( $_ch_id, '_accepted_user_id', $uid );
        wp_redirect( home_url( '/portal/challenges/' ) );
        exit;
    }
}

// ── Form submission (Cases 2 & 3) ─────────────────────────────────────────────
$_ch_error = '';

if (
    ! $_ch_invalid
    && $_ch_status !== 'accepted'
    && $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset( $_POST['ch_nonce'] )
) {
    if ( ! wp_verify_nonce( $_POST['ch_nonce'], 'ch_action_' . $_ch_token ) ) {
        $_ch_error = 'Security check failed. Please try again.';
    } else {
        $action = sanitize_key( $_POST['ch_action'] ?? '' );

        // Case 2: Login
        if ( $action === 'login' ) {
            $user = wp_signon( [
                'user_login'    => sanitize_text_field( wp_unslash( $_POST['ch_login']    ?? '' ) ),
                'user_password' => wp_unslash( $_POST['ch_password'] ?? '' ),
                'remember'      => false,
            ], false );

            if ( is_wp_error( $user ) ) {
                $_ch_error = $user->get_error_message();
            } else {
                update_post_meta( $_ch_id, '_challenge_status', 'accepted' );
                update_post_meta( $_ch_id, '_accepted_user_id', $user->ID );
                wp_redirect( home_url( '/portal/challenges/' ) );
                exit;
            }

        // Case 3: Register
        } elseif ( $action === 'register' ) {
            $reg_email = sanitize_email( wp_unslash( $_POST['ch_email']     ?? $_ch_inv_email ) );
            $reg_fname = sanitize_text_field( wp_unslash( $_POST['ch_fname'] ?? $_ch_inv_fname ) );
            $reg_lname = sanitize_text_field( wp_unslash( $_POST['ch_lname'] ?? $_ch_inv_lname ) );
            $reg_pass  = wp_unslash( $_POST['ch_password']  ?? '' );
            $reg_pass2 = wp_unslash( $_POST['ch_password2'] ?? '' );

            if ( ! $reg_fname || ! $reg_lname ) {
                $_ch_error = 'First and last name are required.';
            } elseif ( $reg_pass !== $reg_pass2 ) {
                $_ch_error = 'Passwords do not match.';
            } elseif ( strlen( $reg_pass ) < 8 ) {
                $_ch_error = 'Password must be at least 8 characters.';
            } elseif ( email_exists( $reg_email ) ) {
                $_ch_error = 'An account with this email already exists. <a href="' .
                    esc_url( add_query_arg( [ 'token' => $_ch_token, 'form' => 'login' ], home_url( '/challenge-handler/' ) ) ) .
                    '" style="color:#b8972f;">Log in instead</a>.';
            } else {
                $base     = sanitize_user( strtolower( $reg_fname . '.' . $reg_lname ) );
                $username = $base;
                $i        = 1;
                while ( username_exists( $username ) ) {
                    $username = $base . $i++;
                }

                $new_uid = wp_insert_user( [
                    'user_login' => $username,
                    'user_email' => $reg_email,
                    'first_name' => $reg_fname,
                    'last_name'  => $reg_lname,
                    'user_pass'  => $reg_pass,
                    'role'       => 'influencer',
                ] );

                if ( is_wp_error( $new_uid ) ) {
                    $_ch_error = $new_uid->get_error_message();
                } else {
                    wp_set_current_user( $new_uid );
                    wp_set_auth_cookie( $new_uid );
                    update_post_meta( $_ch_id, '_challenge_status', 'accepted' );
                    update_post_meta( $_ch_id, '_accepted_user_id', $new_uid );
                    wp_redirect( home_url( '/portal/challenges/' ) );
                    exit;
                }
            }
        }
    }
}

// ── Determine form to show ─────────────────────────────────────────────────────
$_ch_show_form = 'login';
if ( ! $_ch_invalid && $_ch_status !== 'accepted' ) {
    $existing_user = get_user_by( 'email', $_ch_inv_email );
    if ( $existing_user ) {
        $_ch_show_form = ( isset( $_GET['form'] ) && $_GET['form'] === 'register' ) ? 'register' : 'login';
    } else {
        $_ch_show_form = ( isset( $_GET['form'] ) && $_GET['form'] === 'login' ) ? 'login' : 'register';
    }
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Challenge Invitation &mdash; <?php bloginfo( 'name' ); ?></title>
    <?php wp_head(); ?>
    <style>
    *, *::before, *::after { box-sizing: border-box; }
    body {
        background: #0a0a0a;
        color: #fff;
        font-family: 'Be Vietnam Pro', sans-serif;
        margin: 0;
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 32px 16px;
    }
    .ch-card {
        background: #000;
        border: 1px solid #b8972f;
        border-radius: 8px;
        padding: 36px 32px 40px;
        width: 100%;
        max-width: 460px;
    }
    .ch-logo { text-align: center; margin-bottom: 24px; }
    .ch-logo img { max-height: 56px; width: auto; }
    .ch-title {
        font-family: 'Cinzel', serif;
        font-size: 22px;
        font-weight: 700;
        letter-spacing: .08em;
        text-transform: uppercase;
        color: #fff;
        text-align: center;
        margin: 0 0 4px;
    }
    .ch-subtitle { font-size: 14px; color: rgba(255,255,255,.5); text-align: center; margin: 0 0 24px; }
    .ch-divider {
        height: 2px;
        background: radial-gradient(ellipse at center, rgba(184,151,47,.8) 0%, rgba(184,151,47,0) 100%);
        margin-bottom: 22px;
    }
    .ch-invite-box {
        background: rgba(184,151,47,.06);
        border: 1px solid rgba(184,151,47,.3);
        border-radius: 6px;
        padding: 14px 16px;
        margin-bottom: 22px;
        font-size: 14px;
        line-height: 1.6;
        color: rgba(255,255,255,.75);
    }
    .ch-invite-box strong { color: #b8972f; }
    .ch-label {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .07em;
        color: rgba(255,255,255,.45);
        margin-bottom: 5px;
        display: block;
    }
    .ch-input {
        width: 100%;
        background: #000;
        border: 1px solid #b8972f;
        border-radius: 4px;
        padding: 9px 12px;
        font-family: 'Be Vietnam Pro', sans-serif;
        font-size: 15px;
        color: #fff;
        margin-bottom: 14px;
        outline: none;
    }
    .ch-input::placeholder { color: #555; }
    .ch-input:focus { border-color: #d4af37; }
    .ch-btn {
        width: 100%;
        background: #b8972f;
        border: none;
        border-radius: 4px;
        padding: 11px 20px;
        font-family: 'Be Vietnam Pro', sans-serif;
        font-size: 15px;
        font-weight: 700;
        color: #000;
        cursor: pointer;
        transition: opacity .2s;
        margin-top: 6px;
    }
    .ch-btn:hover { opacity: .85; }
    .ch-error {
        background: rgba(220,53,69,.1);
        border: 1px solid rgba(220,53,69,.35);
        border-radius: 4px;
        padding: 10px 14px;
        font-size: 13px;
        color: #f87b87;
        margin-bottom: 18px;
    }
    .ch-toggle { text-align: center; font-size: 13px; color: rgba(255,255,255,.45); margin-top: 18px; }
    .ch-toggle a { color: #b8972f; text-decoration: none; }
    .ch-toggle a:hover { text-decoration: underline; }
    .ch-center { text-align: center; }
    .ch-center h2 { font-family: 'Cinzel', serif; font-size: 20px; color: #b8972f; margin: 0 0 12px; }
    .ch-center p { color: rgba(255,255,255,.55); font-size: 14px; margin: 0 0 18px; }
    .ch-center a { color: #b8972f; font-size: 14px; }
    </style>
</head>
<body>
<div class="ch-card">

    <div class="ch-logo">
        <img src="<?php echo esc_url( get_template_directory_uri() . '/images/new-logo-small.png' ); ?>"
             alt="<?php bloginfo( 'name' ); ?>">
    </div>

    <?php if ( $_ch_invalid ) : ?>

        <div class="ch-center">
            <h2>Invalid Invitation</h2>
            <p>This challenge link is invalid or has expired.<br>Please contact the person who invited you.</p>
        </div>

    <?php elseif ( $_ch_status === 'accepted' ) : ?>

        <div class="ch-center">
            <h2>Already Accepted</h2>
            <p>This challenge has already been accepted.</p>
            <a href="<?php echo esc_url( home_url( '/portal/challenges/' ) ); ?>">Go to Challenges &rarr;</a>
        </div>

    <?php else :
        $chal_obj = get_userdata( $_ch_chal_id );
        $c_first  = get_user_meta( $_ch_chal_id, 'first_name', true );
        $c_last   = get_user_meta( $_ch_chal_id, 'last_name',  true );
        $c_name   = trim( $c_first . ' ' . $c_last ) ?: ( $chal_obj ? $chal_obj->display_name : 'Someone' );

        $toggle_login    = add_query_arg( [ 'token' => $_ch_token, 'form' => 'login'    ], home_url( '/challenge-handler/' ) );
        $toggle_register = add_query_arg( [ 'token' => $_ch_token, 'form' => 'register' ], home_url( '/challenge-handler/' ) );
    ?>

    <h1 class="ch-title">You're Challenged!</h1>
    <p class="ch-subtitle">
        <?php echo $_ch_show_form === 'register' ? 'Create an account to accept' : 'Log in to accept'; ?>
    </p>
    <div class="ch-divider"></div>

    <div class="ch-invite-box">
        <strong><?php echo esc_html( $c_name ); ?></strong> has challenged
        <strong><?php echo esc_html( trim( $_ch_inv_fname . ' ' . $_ch_inv_lname ) ); ?></strong>
        to a private competition.
    </div>

    <?php if ( $_ch_error ) : ?>
    <div class="ch-error"><?php echo wp_kses( $_ch_error, [ 'a' => [ 'href' => [], 'style' => [] ] ] ); ?></div>
    <?php endif; ?>

    <?php if ( $_ch_show_form === 'login' ) : ?>

    <form method="post" autocomplete="on">
        <?php wp_nonce_field( 'ch_action_' . $_ch_token, 'ch_nonce' ); ?>
        <input type="hidden" name="ch_action" value="login">
        <label class="ch-label">Email or Username</label>
        <input class="ch-input" type="text" name="ch_login"
               value="<?php echo esc_attr( $_ch_inv_email ); ?>"
               autocomplete="username" required>
        <label class="ch-label">Password</label>
        <input class="ch-input" type="password" name="ch_password"
               autocomplete="current-password" required>
        <button type="submit" class="ch-btn">Log In &amp; Accept Challenge</button>
    </form>
    <p class="ch-toggle">Don't have an account?
        <a href="<?php echo esc_url( $toggle_register ); ?>">Register here</a>
    </p>

    <?php else : ?>

    <form method="post" autocomplete="on">
        <?php wp_nonce_field( 'ch_action_' . $_ch_token, 'ch_nonce' ); ?>
        <input type="hidden" name="ch_action" value="register">
        <label class="ch-label">First Name</label>
        <input class="ch-input" type="text" name="ch_fname"
               value="<?php echo esc_attr( $_ch_inv_fname ); ?>"
               autocomplete="given-name" required>
        <label class="ch-label">Last Name</label>
        <input class="ch-input" type="text" name="ch_lname"
               value="<?php echo esc_attr( $_ch_inv_lname ); ?>"
               autocomplete="family-name" required>
        <label class="ch-label">Email</label>
        <input class="ch-input" type="email" name="ch_email"
               value="<?php echo esc_attr( $_ch_inv_email ); ?>"
               autocomplete="email" required>
        <label class="ch-label">Password <span style="color:rgba(255,255,255,.3);font-weight:400;">(min. 8 characters)</span></label>
        <input class="ch-input" type="password" name="ch_password"
               minlength="8" autocomplete="new-password" required>
        <label class="ch-label">Confirm Password</label>
        <input class="ch-input" type="password" name="ch_password2"
               minlength="8" autocomplete="new-password" required>
        <button type="submit" class="ch-btn">Register &amp; Accept Challenge</button>
    </form>
    <p class="ch-toggle">Already have an account?
        <a href="<?php echo esc_url( $toggle_login ); ?>">Log in here</a>
    </p>

    <?php endif; ?>

    <?php endif; ?>

</div>
<?php wp_footer(); ?>
</body>
</html>
