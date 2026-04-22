<?php
/**
 * API AJAX Calls
 * Registers WordPress AJAX actions that proxy requests to the external equity API.
 *
 * API base: https://02nvfvonol.execute-api.eu-west-2.amazonaws.com/qc
 * Override in wp-config.php if the endpoint changes:
 *   define( 'INFLUENCER_API_BASE', 'https://...' );
 *
 * User IDs in API paths use the format  influencerhq-wpu-{wp_user_id}  (e.g. "influencerhq-wpu-42").
 *
 * ============================================================
 * EQUITY TOTALS ENDPOINT
 * ============================================================
 *
 * REQUEST
 * -------
 * Method : GET
 * URL    : {base}/referral/user/{sub}/equity/totals
 *          ?referralLevel=L1,KICK,L2
 *          &period=week
 *
 * Example:
 *   curl --location \
 *     'https://02nvfvonol.execute-api.eu-west-2.amazonaws.com/qc/referral/user/30fc57c5-e259-48a3-9d54-3b82ed577c7f/equity/totals?referralLevel=L1%2CKICK%2CL2&period=week'
 *
 * Query parameters:
 *   referralLevel  string  Comma-separated level codes: L1 | L2 | L3 | KICK | LIVE
 *   period         string  Aggregation window: week | month | year | all
 *
 * Headers:
 *   Authorization: Bearer {idToken}   (IdToken from OAuth start-session stored as ihq_id_token)
 *
 * -------
 * RESPONSE  200 OK
 * -------
 * {
 *     "userId": "influencerhq-wpu-42",
 *     "period": "week",
 *     "labels": ["Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun"],
 *     "datasets": [
 *         {
 *             "level":           "L1",
 *             "label":           "Level 1",
 *             "data":            [12.50, 18.00, 9.75, 22.10, 14.80, 30.00, 25.40],
 *             "borderColor":     "#D4AF37",
 *             "backgroundColor": "#D4AF3726"
 *         },
 *         {
 *             "level":           "KICK",
 *             "label":           "KICK",
 *             "data":            [5.00, 8.50, 6.25, 11.00, 7.30, 15.20, 10.90],
 *             "borderColor":     "#53FC18",
 *             "backgroundColor": "#53FC1826"
 *         },
 *         {
 *             "level":           "L2",
 *             "label":           "Level 2",
 *             "data":            [3.10, 4.80, 2.90, 6.50, 5.00, 9.30, 7.20],
 *             "borderColor":     "#8B7536",
 *             "backgroundColor": "#8B753626"
 *         }
 *     ],
 *     "earnings": {
 *         "play":          "132.25",
 *         "shares_earned": "0.1323"
 *     },
 *     "share_price": {
 *         "total_play": "132.25"
 *     }
 * }
 *
 * Error responses:
 *   400  { "message": "Invalid referralLevel value" }
 *   404  { "message": "User not found" }
 *   500  { "message": "Internal server error" }
 * ============================================================
 */

if ( ! defined( 'INFLUENCER_API_BASE' ) ) {
    define( 'INFLUENCER_API_BASE', 'https://02nvfvonol.execute-api.eu-west-2.amazonaws.com/qc' );
}

// ---------------------------------------------------------------------------
// Challenge API handlers — shared nonce: challenge_api_nonce
// Endpoints: createChallenge | getChallengesForPlayer |
//            getChallengeDetails | joinChallenges
// ---------------------------------------------------------------------------

add_action( 'wp_ajax_create_challenge',               'create_challenge_ajax' );
add_action( 'wp_ajax_nopriv_create_challenge',        'create_challenge_ajax' );
add_action( 'wp_ajax_get_challenges_for_player',      'get_challenges_for_player_ajax' );
add_action( 'wp_ajax_nopriv_get_challenges_for_player', 'get_challenges_for_player_ajax' );
add_action( 'wp_ajax_get_challenge_details',          'get_challenge_details_ajax' );
add_action( 'wp_ajax_nopriv_get_challenge_details',   'get_challenge_details_ajax' );
add_action( 'wp_ajax_join_challenges',                'join_challenges_ajax' );
add_action( 'wp_ajax_nopriv_join_challenges',         'join_challenges_ajax' );

/**
 * Shared: decode JWT sub from stored id_token. Returns array with 'sub' or 'error'.
 */
function _challenge_get_sub( $id_token ) {
    $parts   = explode( '.', $id_token );
    $payload = isset( $parts[1] )
        ? json_decode( base64_decode( strtr( $parts[1], '-_', '+/' ) ), true )
        : null;
    $sub = $payload['sub'] ?? null;
    return $sub ? array( 'sub' => $sub, 'payload' => $payload ) : array( 'error' => 'Could not decode sub from token.' );
}

/**
 * Shared: send WP_Remote response as JSON. Returns early on error.
 */
function _challenge_send_response( $response, $debug ) {
    if ( is_wp_error( $response ) ) {
        $debug['http_status'] = 0;
        $debug['wp_error']    = $response->get_error_message();
        wp_send_json_error( array( 'message' => 'API request failed: ' . $response->get_error_message(), '_debug' => $debug ) );
    }
    $status_code          = wp_remote_retrieve_response_code( $response );
    $body                 = json_decode( wp_remote_retrieve_body( $response ), true );
    $debug['http_status'] = $status_code;
    $data                 = is_array( $body ) ? $body : array();
    $data['_debug']       = $debug;
    if ( 200 !== $status_code ) {
        wp_send_json_error( $data );
    }
    wp_send_json_success( $data );
}

function create_challenge_ajax() {
    if ( ! check_ajax_referer( 'challenge_api_nonce', 'nonce', false ) ) {
        wp_send_json_error( 'Security check failed.', 403 );
    }
    $wp_user_id = get_current_user_id();
    $id_token   = get_user_meta( $wp_user_id, 'ihq_id_token', true );
    if ( empty( $id_token ) ) {
        wp_send_json_error( 'No IHQ session token.' );
    }
    $decoded = _challenge_get_sub( $id_token );
    if ( isset( $decoded['error'] ) ) {
        wp_send_json_error( $decoded['error'] );
    }
    $sub = $decoded['sub'];

    $wp_user = get_userdata( $wp_user_id );

    $name              = sanitize_text_field( $_POST['name']                   ?? 'Test Challenge ' . gmdate( 'Y-m-d H:i' ) );
    $start_dt          = sanitize_text_field( $_POST['scheduledStartDateTime'] ?? gmdate( 'Y-m-d\TH:i:s.000\Z', strtotime( '+1 day' ) ) );
    $duration          = intval( $_POST['durationHours']      ?? 24 );
    $min_hands         = intval( $_POST['minNumberOfHands']   ?? 10 );
    $max_hands         = intval( $_POST['maxNumberOfHands']   ?? 100 );
    $challenged_raw    = sanitize_text_field( $_POST['challengedPlayers'] ?? '' );
    $challenged        = array_values( array_filter( array_map( 'trim', explode( ',', $challenged_raw ) ) ) );

    $request_body = array(
        'authenticatedUser'      => $sub,
        'authenticatedEmail'     => $wp_user ? $wp_user->user_email : '',
        'authenticatedFirstName' => $wp_user ? $wp_user->first_name : '',
        'authenticatedLastName'  => $wp_user ? $wp_user->last_name  : '',
        'name'                   => $name,
        'scheduledStartDateTime' => $start_dt,
        'durationHours'          => $duration,
        'minNumberOfHands'       => $min_hands,
        'maxNumberOfHands'       => $max_hands,
        'challengedPlayers'      => $challenged,
    );

    $api_url  = INFLUENCER_API_BASE . '/rankings/createChallenge';
    $response = wp_remote_post( $api_url, array(
        'timeout'   => 15,
        'sslverify' => true,
        'headers'   => array( 'Content-Type' => 'application/json', 'Authorization' => 'Bearer ' . $id_token ),
        'body'      => wp_json_encode( $request_body ),
    ) );

    _challenge_send_response( $response, array(
        'url'        => 'POST ' . $api_url,
        'body_sent'  => $request_body,
        'jwt_sub'    => $sub,
        'wp_user_id' => $wp_user_id,
    ) );
}

function get_challenges_for_player_ajax() {
    if ( ! check_ajax_referer( 'challenge_api_nonce', 'nonce', false ) ) {
        wp_send_json_error( 'Security check failed.', 403 );
    }
    $wp_user_id = get_current_user_id();
    $id_token   = get_user_meta( $wp_user_id, 'ihq_id_token', true );
    if ( empty( $id_token ) ) {
        wp_send_json_error( 'No IHQ session token.' );
    }
    $decoded = _challenge_get_sub( $id_token );
    if ( isset( $decoded['error'] ) ) {
        wp_send_json_error( $decoded['error'] );
    }
    $sub = $decoded['sub'];

    $status_filter = sanitize_text_field( $_POST['statusFilter']   ?? '' );
    $last_days     = intval( $_POST['lastDaysFilter'] ?? 0 );

    $query = array( 'authenticatedUser' => $sub );
    if ( $status_filter ) $query['statusFilter']   = $status_filter;
    if ( $last_days > 0 ) $query['lastDaysFilter'] = $last_days;

    $api_url  = INFLUENCER_API_BASE . '/rankings/getChallengesForPlayer?' . http_build_query( $query );
    $response = wp_remote_get( $api_url, array(
        'timeout'   => 15,
        'sslverify' => true,
        'headers'   => array( 'Authorization' => 'Bearer ' . $id_token ),
    ) );

    _challenge_send_response( $response, array(
        'url'         => 'GET ' . $api_url,
        'query_sent'  => $query,
        'jwt_sub'     => $sub,
        'wp_user_id'  => $wp_user_id,
    ) );
}

function get_challenge_details_ajax() {
    if ( ! check_ajax_referer( 'challenge_api_nonce', 'nonce', false ) ) {
        wp_send_json_error( 'Security check failed.', 403 );
    }
    $wp_user_id   = get_current_user_id();
    $id_token     = get_user_meta( $wp_user_id, 'ihq_id_token', true );
    $challenge_id = sanitize_text_field( $_POST['challengeId'] ?? '' );
    if ( empty( $id_token ) ) { wp_send_json_error( 'No IHQ session token.' ); }
    if ( empty( $challenge_id ) ) { wp_send_json_error( 'challengeId is required.' ); }

    $api_url  = INFLUENCER_API_BASE . '/rankings/getChallengeDetails/' . rawurlencode( $challenge_id );
    $response = wp_remote_get( $api_url, array(
        'timeout'   => 15,
        'sslverify' => true,
        'headers'   => array( 'Authorization' => 'Bearer ' . $id_token ),
    ) );

    _challenge_send_response( $response, array(
        'url'         => 'GET ' . $api_url,
        'challengeId' => $challenge_id,
        'wp_user_id'  => $wp_user_id,
    ) );
}

function join_challenges_ajax() {
    if ( ! check_ajax_referer( 'challenge_api_nonce', 'nonce', false ) ) {
        wp_send_json_error( 'Security check failed.', 403 );
    }
    $wp_user_id = get_current_user_id();
    $id_token   = get_user_meta( $wp_user_id, 'ihq_id_token', true );
    if ( empty( $id_token ) ) {
        wp_send_json_error( 'No IHQ session token.' );
    }
    $decoded = _challenge_get_sub( $id_token );
    if ( isset( $decoded['error'] ) ) {
        wp_send_json_error( $decoded['error'] );
    }
    $sub = $decoded['sub'];

    $challenge_id = sanitize_text_field( $_POST['challengeId'] ?? '' );
    $team_name    = sanitize_text_field( $_POST['teamName']    ?? '' );
    if ( empty( $challenge_id ) ) { wp_send_json_error( 'challengeId is required.' ); }

    $entry = array( 'challengeId' => $challenge_id );
    if ( $team_name ) $entry['teamName'] = $team_name;

    $request_body = array(
        'authenticatedUser' => $sub,
        'challenges'        => array( $entry ),
    );

    $api_url  = INFLUENCER_API_BASE . '/rankings/joinChallenges';
    $response = wp_remote_post( $api_url, array(
        'timeout'   => 15,
        'sslverify' => true,
        'headers'   => array( 'Content-Type' => 'application/json', 'Authorization' => 'Bearer ' . $id_token ),
        'body'      => wp_json_encode( $request_body ),
    ) );

    _challenge_send_response( $response, array(
        'url'        => 'POST ' . $api_url,
        'body_sent'  => $request_body,
        'jwt_sub'    => $sub,
        'wp_user_id' => $wp_user_id,
    ) );
}

// ---------------------------------------------------------------------------
// Live Appearance Request handlers — nonce: request_live_appearance_nonce
// Actions: request_live_appearance | get_live_appearance_status
// ---------------------------------------------------------------------------

add_action( 'wp_ajax_request_live_appearance', 'request_live_appearance_ajax' );

function request_live_appearance_ajax() {
    if ( ! check_ajax_referer( 'request_live_appearance_nonce', 'nonce', false ) ) {
        wp_send_json_error( array( 'message' => 'Security check failed.' ), 403 );
    }
    $user_id = get_current_user_id();
    if ( ! $user_id ) {
        wp_send_json_error( array( 'message' => 'You must be logged in to submit a request.' ) );
    }

    $day             = sanitize_text_field( $_POST['la_choice_1_month'] ?? '' ) . '/' . sanitize_text_field( $_POST['la_choice_1_day'] ?? '' );
    $backup_day      = sanitize_text_field( $_POST['la_choice_2_month'] ?? '' ) . '/' . sanitize_text_field( $_POST['la_choice_2_day'] ?? '' );
    $start_time      = sanitize_text_field( $_POST['la_choice_1_time']  ?? '' );
    $backup_time     = sanitize_text_field( $_POST['la_choice_2_time']  ?? '' );
    $choice_3_month  = sanitize_text_field( $_POST['la_choice_3_month'] ?? '' );
    $choice_3_day    = sanitize_text_field( $_POST['la_choice_3_day']   ?? '' );
    $choice_3_time   = sanitize_text_field( $_POST['la_choice_3_time']  ?? '' );
    $opponent        = sanitize_text_field( $_POST['la_opponent_name']        ?? '' );
    $backup_opponent = sanitize_text_field( $_POST['la_backup_opponent_name'] ?? '' );
    $opponent_email  = sanitize_email( $_POST['la_opponent_email']        ?? '' );
    $backup_opp_email = sanitize_email( $_POST['la_backup_opponent_email'] ?? '' );
    $url_raw         = sanitize_text_field( $_POST['la_url']                    ?? '' );
    $url             = $url_raw ? esc_url_raw( $url_raw ) : '';

    if ( $url && ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
        wp_send_json_error( array( 'message' => 'Invalid URL provided.' ) );
    }

    $user       = get_userdata( $user_id );
    $user_name  = $user ? $user->display_name : 'User ' . $user_id;
    $post_title = $user_name . ' — ' . ( $day ?: 'Live Request' ) . ' ' . gmdate( 'Y-m-d H:i' );

    $post_id = wp_insert_post( array(
        'post_type'   => 'live_appearance',
        'post_title'  => $post_title,
        'post_status' => 'publish',
        'post_author' => $user_id,
    ), true );

    if ( is_wp_error( $post_id ) ) {
        wp_send_json_error( array( 'message' => 'Could not save request: ' . $post_id->get_error_message() ) );
    }

    update_post_meta( $post_id, '_live_appearance_status',               'pending' );
    update_post_meta( $post_id, '_live_appearance_user_id',                $user_id );
    update_post_meta( $post_id, '_live_appearance_day',                    $day );
    update_post_meta( $post_id, '_live_appearance_backup_day',             $backup_day );
    update_post_meta( $post_id, '_live_appearance_start_time',             $start_time );
    update_post_meta( $post_id, '_live_appearance_backup_start_time',      $backup_time );
    update_post_meta( $post_id, '_live_appearance_choice_3_month',         $choice_3_month );
    update_post_meta( $post_id, '_live_appearance_choice_3_day',           $choice_3_day );
    update_post_meta( $post_id, '_live_appearance_choice_3_time',          $choice_3_time );
    update_post_meta( $post_id, '_live_appearance_opponent_handle',        $opponent );
    update_post_meta( $post_id, '_live_appearance_backup_opponent_handle', $backup_opponent );
    update_post_meta( $post_id, '_live_appearance_opponent_email',         $opponent_email );
    update_post_meta( $post_id, '_live_appearance_backup_opponent_email',  $backup_opp_email );
    update_post_meta( $post_id, '_live_appearance_url',                    $url );
    update_post_meta( $post_id, '_live_appearance_date_created',           current_time( 'mysql' ) );

    wp_send_json_success( array(
        'post_id'      => $post_id,
        'status_key'   => 'pending',
        'status_label' => 'Pending',
        'url'          => $url,
        'message'      => 'Your live appearance request has been submitted.',
    ) );
}

add_action( 'wp_ajax_get_live_appearance_status', 'get_live_appearance_status_ajax' );

function get_live_appearance_status_ajax() {
    if ( ! check_ajax_referer( 'request_live_appearance_nonce', 'nonce', false ) ) {
        wp_send_json_error( array( 'message' => 'Security check failed.' ), 403 );
    }
    $user_id = get_current_user_id();
    if ( ! $user_id ) {
        wp_send_json_success( array( 'found' => false, 'status_label' => '—', 'url' => '' ) );
    }

    $posts = get_posts( array(
        'post_type'      => 'live_appearance',
        'post_status'    => 'publish',
        'author'         => $user_id,
        'posts_per_page' => 1,
        'orderby'        => 'date',
        'order'          => 'DESC',
        'fields'         => 'ids',
    ) );

    if ( empty( $posts ) ) {
        wp_send_json_success( array( 'found' => false, 'status_label' => '—', 'url' => '' ) );
    }

    $post_id = $posts[0];
    $status  = get_post_meta( $post_id, '_live_appearance_status', true ) ?: 'pending';
    $url     = get_post_meta( $post_id, '_live_appearance_url', true );

    $labels = array(
        'pending'              => 'Pending',
        'confirmed'            => 'Confirmed',
        '1st_choice_accepted'  => '1st Choice Accepted',
        '2nd_choice_accepted'  => '2nd Choice Accepted',
        '3rd_choice_accepted'  => '3rd Choice Accepted',
        'declined'             => 'Declined',
    );
    $status_label = $labels[ $status ] ?? ucwords( $status );

    wp_send_json_success( array(
        'found'        => true,
        'post_id'      => $post_id,
        'status_key'   => $status,
        'status_label' => $status_label,
        'url'          => $url ?: '',
    ) );
}

// ---------------------------------------------------------------------------
// Live Appearance — user-facing edit & delete
// Nonce: request_live_appearance_nonce
// Actions: update_live_appearance | delete_live_appearance
// ---------------------------------------------------------------------------

add_action( 'wp_ajax_update_live_appearance', 'update_live_appearance_ajax' );

function update_live_appearance_ajax() {
    if ( ! check_ajax_referer( 'request_live_appearance_nonce', 'nonce', false ) ) {
        wp_send_json_error( array( 'message' => 'Security check failed.' ), 403 );
    }
    $user_id = get_current_user_id();
    if ( ! $user_id ) {
        wp_send_json_error( array( 'message' => 'Not logged in.' ), 403 );
    }

    $post_id = intval( $_POST['post_id'] ?? 0 );
    if ( ! $post_id ) {
        wp_send_json_error( array( 'message' => 'Invalid post.' ) );
    }

    $post = get_post( $post_id );
    if ( ! $post || $post->post_type !== 'live_appearance' || (int) $post->post_author !== $user_id ) {
        wp_send_json_error( array( 'message' => 'Not authorised.' ), 403 );
    }

    $day              = sanitize_text_field( wp_unslash( $_POST['la_choice_1_month'] ?? '' ) ) . '/' . sanitize_text_field( wp_unslash( $_POST['la_choice_1_day'] ?? '' ) );
    $backup_day       = sanitize_text_field( wp_unslash( $_POST['la_choice_2_month'] ?? '' ) ) . '/' . sanitize_text_field( wp_unslash( $_POST['la_choice_2_day'] ?? '' ) );
    $start_time       = sanitize_text_field( wp_unslash( $_POST['la_choice_1_time']  ?? '' ) );
    $backup_time      = sanitize_text_field( wp_unslash( $_POST['la_choice_2_time']  ?? '' ) );
    $choice_3_month   = sanitize_text_field( wp_unslash( $_POST['la_choice_3_month'] ?? '' ) );
    $choice_3_day     = sanitize_text_field( wp_unslash( $_POST['la_choice_3_day']   ?? '' ) );
    $choice_3_time    = sanitize_text_field( wp_unslash( $_POST['la_choice_3_time']  ?? '' ) );
    $opponent         = sanitize_text_field( wp_unslash( $_POST['la_opponent_name']        ?? '' ) );
    $backup_opponent  = sanitize_text_field( wp_unslash( $_POST['la_backup_opponent_name'] ?? '' ) );
    $opponent_email   = sanitize_email( wp_unslash( $_POST['la_opponent_email']        ?? '' ) );
    $backup_opp_email = sanitize_email( wp_unslash( $_POST['la_backup_opponent_email'] ?? '' ) );

    update_post_meta( $post_id, '_live_appearance_day',                    $day );
    update_post_meta( $post_id, '_live_appearance_backup_day',             $backup_day );
    update_post_meta( $post_id, '_live_appearance_start_time',             $start_time );
    update_post_meta( $post_id, '_live_appearance_backup_start_time',      $backup_time );
    update_post_meta( $post_id, '_live_appearance_choice_3_month',         $choice_3_month );
    update_post_meta( $post_id, '_live_appearance_choice_3_day',           $choice_3_day );
    update_post_meta( $post_id, '_live_appearance_choice_3_time',          $choice_3_time );
    update_post_meta( $post_id, '_live_appearance_opponent_handle',        $opponent );
    update_post_meta( $post_id, '_live_appearance_backup_opponent_handle', $backup_opponent );
    update_post_meta( $post_id, '_live_appearance_opponent_email',         $opponent_email );
    update_post_meta( $post_id, '_live_appearance_backup_opponent_email',  $backup_opp_email );
    update_post_meta( $post_id, '_live_appearance_status',                 'pending' );

    wp_send_json_success( array( 'message' => 'Updated and set back to pending.' ) );
}

add_action( 'wp_ajax_delete_live_appearance', 'delete_live_appearance_ajax' );

function delete_live_appearance_ajax() {
    if ( ! check_ajax_referer( 'request_live_appearance_nonce', 'nonce', false ) ) {
        wp_send_json_error( array( 'message' => 'Security check failed.' ), 403 );
    }
    $user_id = get_current_user_id();
    if ( ! $user_id ) {
        wp_send_json_error( array( 'message' => 'Not logged in.' ), 403 );
    }

    $post_id = intval( $_POST['post_id'] ?? 0 );
    if ( ! $post_id ) {
        wp_send_json_error( array( 'message' => 'Invalid post.' ) );
    }

    $post = get_post( $post_id );
    if ( ! $post || $post->post_type !== 'live_appearance' || (int) $post->post_author !== $user_id ) {
        wp_send_json_error( array( 'message' => 'Not authorised.' ), 403 );
    }

    wp_delete_post( $post_id, true );
    wp_send_json_success( array( 'message' => 'Live appearance cancelled.' ) );
}

// ---------------------------------------------------------------------------
// Referral Link — nonce: request_live_appearance_nonce
// Action: get_referral_link
// Returns the user's share URL from GET /referral/user/{userId}/link
// ---------------------------------------------------------------------------
add_action( 'wp_ajax_get_referral_link', 'get_referral_link_ajax' );

function get_referral_link_ajax() {
    if ( ! check_ajax_referer( 'request_live_appearance_nonce', 'nonce', false ) ) {
        wp_send_json_error( array( 'message' => 'Security check failed.' ), 403 );
    }
    $user_id  = get_current_user_id();
    $id_token = get_user_meta( $user_id, 'ihq_id_token', true );
    if ( ! $user_id || empty( $id_token ) ) {
        wp_send_json_error( array( 'message' => 'Not authenticated.' ) );
    }

    $wpu_id   = 'influencerhq-wpu-' . $user_id;
    $api_url  = INFLUENCER_API_BASE . '/referral/user/' . rawurlencode( $wpu_id ) . '/link';
    $response = wp_remote_get( $api_url, array(
        'timeout'   => 10,
        'sslverify' => true,
        'headers'   => array(
            'Accept'        => 'application/json',
            'Authorization' => 'Bearer ' . $id_token,
        ),
    ) );

    if ( is_wp_error( $response ) ) {
        wp_send_json_error( array( 'message' => $response->get_error_message() ) );
    }

    $status = wp_remote_retrieve_response_code( $response );
    $body   = json_decode( wp_remote_retrieve_body( $response ), true );

    if ( 200 !== $status ) {
        wp_send_json_error( array( 'message' => 'API returned HTTP ' . $status, 'body' => $body ) );
    }

    wp_send_json_success( array( 'url' => isset( $body['url'] ) ? esc_url_raw( $body['url'] ) : '' ) );
}

// ============================================================
// KICK Broadcasting Schedule — nonce: kick_schedule_nonce
// Actions: add_kick_schedule | delete_kick_schedule
// Stored as user meta: _kick_broadcasting_schedule (array)
// ============================================================

add_action( 'wp_ajax_add_kick_schedule', 'add_kick_schedule_ajax' );

function add_kick_schedule_ajax() {
    if ( ! check_ajax_referer( 'kick_schedule_nonce', 'nonce', false ) ) {
        wp_send_json_error( array( 'message' => 'Security check failed.' ), 403 );
    }
    $user_id = get_current_user_id();
    if ( ! $user_id ) {
        wp_send_json_error( array( 'message' => 'Not logged in.' ), 403 );
    }

    $day        = sanitize_text_field( wp_unslash( $_POST['ks_day']        ?? '' ) );
    $start_time = sanitize_text_field( wp_unslash( $_POST['ks_start_time'] ?? '' ) );
    $end_time   = sanitize_text_field( wp_unslash( $_POST['ks_end_time']   ?? '' ) );

    if ( ! $day || ! $start_time || ! $end_time ) {
        wp_send_json_error( array( 'message' => 'Day, start time, and end time are required.' ) );
    }

    $schedule = get_user_meta( $user_id, '_kick_broadcasting_schedule', true );
    if ( ! is_array( $schedule ) ) {
        $schedule = array();
    }

    $schedule[] = array(
        'day'        => $day,
        'start_time' => $start_time,
        'end_time'   => $end_time,
    );

    update_user_meta( $user_id, '_kick_broadcasting_schedule', $schedule );

    wp_send_json_success( array( 'schedule' => $schedule ) );
}

add_action( 'wp_ajax_delete_kick_schedule', 'delete_kick_schedule_ajax' );

function delete_kick_schedule_ajax() {
    if ( ! check_ajax_referer( 'kick_schedule_nonce', 'nonce', false ) ) {
        wp_send_json_error( array( 'message' => 'Security check failed.' ), 403 );
    }
    $user_id = get_current_user_id();
    if ( ! $user_id ) {
        wp_send_json_error( array( 'message' => 'Not logged in.' ), 403 );
    }

    $index    = intval( $_POST['index'] ?? -1 );
    $schedule = get_user_meta( $user_id, '_kick_broadcasting_schedule', true );

    if ( ! is_array( $schedule ) || ! array_key_exists( $index, $schedule ) ) {
        wp_send_json_error( array( 'message' => 'Item not found.' ) );
    }

    array_splice( $schedule, $index, 1 );
    update_user_meta( $user_id, '_kick_broadcasting_schedule', $schedule );

    wp_send_json_success( array( 'schedule' => $schedule ) );
}

// ============================================================
// SETTINGS PAGE — nonce: settings_save_nonce
// Actions: save_settings_field | save_settings_toggle | save_settings_avatar
// ============================================================

add_action( 'wp_ajax_save_settings_field',  'save_settings_field_ajax' );

function save_settings_field_ajax() {
    if ( ! check_ajax_referer( 'settings_save_nonce', 'nonce', false ) ) {
        wp_send_json_error( [ 'message' => 'Security check failed.' ], 403 );
    }
    $user_id = get_current_user_id();
    if ( ! $user_id ) { wp_send_json_error( [ 'message' => 'Not logged in.' ], 403 ); }

    $group = sanitize_key( wp_unslash( $_POST['group'] ?? '' ) );
    $field = sanitize_key( wp_unslash( $_POST['field'] ?? '' ) );
    $value = sanitize_text_field( wp_unslash( $_POST['value'] ?? '' ) );

    if ( $group === 'account' ) {
        $map = [
            'name'                   => 'display_name',
            'email'                  => 'user_email',
            'country'                => '_ihq_country',
            'city'                   => '_ihq_city',
            'timezone'               => '_ihq_timezone',
            'handle'                 => '_ihq_handle',
            'celebrity_movie_stars'  => '_ihq_cel_movie_stars',
            'celebrity_music_artists'=> '_ihq_cel_music_artists',
            'celebrity_sports_icons' => '_ihq_cel_sports_icons',
            'intl_league_team'       => '_ihq_intl_league_team',
        ];
        if ( isset( $map[ $field ] ) ) {
            if ( in_array( $map[ $field ], [ 'display_name', 'user_email' ], true ) ) {
                wp_update_user( [ 'ID' => $user_id, $map[ $field ] => $value ] );
            } else {
                update_user_meta( $user_id, $map[ $field ], $value );
            }
        }
    } elseif ( $group === 'social' ) {
        $handles = get_user_meta( $user_id, '_ihq_social_handles', true );
        if ( ! is_array( $handles ) ) { $handles = []; }
        $handles[ $field ] = $value;
        update_user_meta( $user_id, '_ihq_social_handles', $handles );
    } elseif ( $group === 'comm' ) {
        update_user_meta( $user_id, '_ihq_comm_email', $value );
    }

    wp_send_json_success();
}

add_action( 'wp_ajax_save_settings_toggle', 'save_settings_toggle_ajax' );

function save_settings_toggle_ajax() {
    if ( ! check_ajax_referer( 'settings_save_nonce', 'nonce', false ) ) {
        wp_send_json_error( [ 'message' => 'Security check failed.' ], 403 );
    }
    $user_id = get_current_user_id();
    if ( ! $user_id ) { wp_send_json_error( [ 'message' => 'Not logged in.' ], 403 ); }

    $group = sanitize_key( wp_unslash( $_POST['group'] ?? '' ) );
    $key   = sanitize_key( wp_unslash( $_POST['key']   ?? '' ) );
    $val   = (int) ( $_POST['value'] ?? 0 );

    $meta_map = [
        'account' => '_ihq_account_visible',
        'comm'    => '_ihq_comm_prefs',
    ];

    if ( isset( $meta_map[ $group ] ) ) {
        $data = get_user_meta( $user_id, $meta_map[ $group ], true );
        if ( ! is_array( $data ) ) { $data = []; }
        if ( $val ) {
            $data[ $key ] = 1;
        } else {
            unset( $data[ $key ] );
        }
        update_user_meta( $user_id, $meta_map[ $group ], $data );
    }

    wp_send_json_success();
}

add_action( 'wp_ajax_save_settings_avatar', 'save_settings_avatar_ajax' );

function save_settings_avatar_ajax() {
    if ( ! check_ajax_referer( 'settings_save_nonce', 'nonce', false ) ) {
        wp_send_json_error( [ 'message' => 'Security check failed.' ], 403 );
    }
    $user_id = get_current_user_id();
    if ( ! $user_id ) { wp_send_json_error( [ 'message' => 'Not logged in.' ], 403 ); }

    if ( empty( $_FILES['avatar'] ) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK ) {
        wp_send_json_error( [ 'message' => 'Upload error.' ] );
    }

    require_once ABSPATH . 'wp-admin/includes/image.php';
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';

    $attachment_id = media_handle_upload( 'avatar', 0 );
    if ( is_wp_error( $attachment_id ) ) {
        wp_send_json_error( [ 'message' => $attachment_id->get_error_message() ] );
    }

    $url = wp_get_attachment_url( $attachment_id );
    update_user_meta( $user_id, '_ihq_avatar_url', $url );

    wp_send_json_success( [ 'url' => $url ] );
}

// ---------------------------------------------------------------------------
// Profile: Get player data from GET /account/players/me
// ---------------------------------------------------------------------------
add_action( 'wp_ajax_ihq_get_player_me', 'ihq_get_player_me_ajax' );

function ihq_get_player_me_ajax() {
    if ( ! check_ajax_referer( 'settings_save_nonce', 'nonce', false ) ) {
        wp_send_json_error( 'Security check failed.', 403 );
    }

    $wp_user_id = get_current_user_id();
    $id_token   = get_user_meta( $wp_user_id, 'ihq_id_token', true );
    if ( empty( $id_token ) ) {
        wp_send_json_error( [ 'message' => 'No IHQ session token — run SSO first.' ] );
        return;
    }

    $decoded = _challenge_get_sub( $id_token );
    if ( isset( $decoded['error'] ) ) {
        wp_send_json_error( [ 'message' => $decoded['error'] ] );
        return;
    }
    $sub = $decoded['sub'];

    $api_url  = INFLUENCER_API_BASE . '/account/players/me';
    $response = wp_remote_get( $api_url, array(
        'timeout'   => 10,
        'sslverify' => true,
        'headers'   => array(
            'Accept'        => 'application/json',
            'Authorization' => 'Bearer ' . $id_token,
            'username'      => $sub,
        ),
    ) );

    if ( is_wp_error( $response ) ) {
        wp_send_json_error( array( 'message' => $response->get_error_message() ) );
        return;
    }

    $status = wp_remote_retrieve_response_code( $response );
    $body   = json_decode( wp_remote_retrieve_body( $response ), true );

    if ( 200 !== $status ) {
        wp_send_json_error( array( 'message' => 'API returned HTTP ' . $status, 'body' => $body, '_sub' => $sub ) );
        return;
    }

    wp_send_json_success( $body );
}

// ---------------------------------------------------------------------------
// Profile: Update fullname via PATCH /account/players/fullname
// ---------------------------------------------------------------------------
add_action( 'wp_ajax_ihq_update_fullname', 'ihq_update_fullname_ajax' );

function ihq_update_fullname_ajax() {
    if ( ! check_ajax_referer( 'settings_save_nonce', 'nonce', false ) ) {
        wp_send_json_error( 'Security check failed.', 403 );
    }

    $wp_user_id = get_current_user_id();
    $id_token   = get_user_meta( $wp_user_id, 'ihq_id_token', true );
    if ( empty( $id_token ) ) {
        wp_send_json_error( array( 'message' => 'No IHQ session token — run SSO first.' ) );
        return;
    }

    $decoded = _challenge_get_sub( $id_token );
    if ( isset( $decoded['error'] ) ) {
        wp_send_json_error( array( 'message' => $decoded['error'] ) );
        return;
    }
    $sub = $decoded['sub'];

    $first_name = sanitize_text_field( wp_unslash( $_POST['firstName'] ?? '' ) );
    $last_name  = sanitize_text_field( wp_unslash( $_POST['lastName']  ?? '' ) );

    $api_url  = INFLUENCER_API_BASE . '/account/players/fullname';
    $response = wp_remote_request( $api_url, array(
        'method'    => 'PATCH',
        'timeout'   => 10,
        'sslverify' => true,
        'headers'   => array(
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer ' . $id_token,
            'username'      => $sub,
        ),
        'body' => wp_json_encode( array(
            'firstName' => $first_name,
            'lastName'  => $last_name,
        ) ),
    ) );

    if ( is_wp_error( $response ) ) {
        wp_send_json_error( array( 'message' => $response->get_error_message() ) );
        return;
    }

    $status = wp_remote_retrieve_response_code( $response );
    $body   = json_decode( wp_remote_retrieve_body( $response ), true );

    if ( $status < 200 || $status >= 300 ) {
        wp_send_json_error( array( 'message' => 'API returned HTTP ' . $status, 'body' => $body, '_sub' => $sub ) );
        return;
    }

    // Keep WP user meta in sync
    update_user_meta( $wp_user_id, 'first_name', $first_name );
    update_user_meta( $wp_user_id, 'last_name',  $last_name );

    wp_send_json_success( array( 'firstName' => $first_name, 'lastName' => $last_name ) );
}
