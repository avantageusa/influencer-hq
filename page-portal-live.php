<?php
/**
 * Template Name: Portal Live
 * Description: A custom template for displaying the live streaming page.
 *
 * @package influencer-hq
 */
get_header();

// Load styles before content to prevent FOUC
get_template_part( 'template-parts/portal-styles' );

$la_calendar_occupied = [];
$la_calendar_details  = [];
$la_calendar_posts    = get_posts( [
    'post_type'      => 'live_appearance',
    'post_status'    => 'publish',
    'posts_per_page' => -1,
    'fields'         => 'ids',
    'meta_query'     => [
        [
            'key'     => '_live_appearance_status',
            'value'   => [ 'confirmed', '1st_choice_accepted', '2nd_choice_accepted', '3rd_choice_accepted' ],
            'compare' => 'IN',
        ],
    ],
] );

if ( ! empty( $la_calendar_posts ) ) {
    foreach ( $la_calendar_posts as $la_post_id ) {
        $la_date_candidates = [];
        $la_date_created    = get_post_meta( $la_post_id, '_live_appearance_date_created', true );
        $la_year            = $la_date_created ? (int) date( 'Y', strtotime( $la_date_created ) ) : (int) current_time( 'Y' );

        $la_day_raw   = get_post_meta( $la_post_id, '_live_appearance_day', true );
        $la_bkday_raw = get_post_meta( $la_post_id, '_live_appearance_backup_day', true );
        $la_c3m       = absint( get_post_meta( $la_post_id, '_live_appearance_choice_3_month', true ) );
        $la_c3d       = absint( get_post_meta( $la_post_id, '_live_appearance_choice_3_day', true ) );

        if ( $la_day_raw ) {
            $la_date_candidates[] = $la_day_raw;
        }
        if ( $la_bkday_raw ) {
            $la_date_candidates[] = $la_bkday_raw;
        }
        if ( $la_c3m >= 1 && $la_c3m <= 12 && $la_c3d >= 1 && $la_c3d <= 31 ) {
            $la_date_candidates[] = $la_c3m . '/' . $la_c3d;
        }

        foreach ( $la_date_candidates as $la_md ) {
            $la_parts = explode( '/', (string) $la_md );
            if ( count( $la_parts ) < 2 ) {
                continue;
            }

            $la_month = absint( trim( $la_parts[0] ) );
            $la_day   = absint( trim( $la_parts[1] ) );
            if ( $la_month < 1 || $la_month > 12 || $la_day < 1 || $la_day > 31 ) {
                continue;
            }

            $la_key = $la_year . '-' . $la_month;
            if ( ! isset( $la_calendar_occupied[ $la_key ] ) ) {
                $la_calendar_occupied[ $la_key ] = [];
            }
            $la_calendar_occupied[ $la_key ][ $la_day ] = $la_day;
        }
    }

    foreach ( $la_calendar_occupied as $la_key => $la_days ) {
        sort( $la_days, SORT_NUMERIC );
        $la_calendar_occupied[ $la_key ] = array_values( $la_days );
    }

    foreach ( $la_calendar_posts as $la_post_id ) {
        $la_day_raw   = get_post_meta( $la_post_id, '_live_appearance_day', true );
        $la_bkday_raw = get_post_meta( $la_post_id, '_live_appearance_backup_day', true );
        $la_c3m       = absint( get_post_meta( $la_post_id, '_live_appearance_choice_3_month', true ) );
        $la_c3d       = absint( get_post_meta( $la_post_id, '_live_appearance_choice_3_day', true ) );
        $la_choice3   = ( $la_c3m >= 1 && $la_c3m <= 12 && $la_c3d >= 1 && $la_c3d <= 31 ) ? $la_c3m . '/' . $la_c3d : '';

        $la_time_map = [
            $la_day_raw   => get_post_meta( $la_post_id, '_live_appearance_start_time', true ),
            $la_bkday_raw => get_post_meta( $la_post_id, '_live_appearance_backup_start_time', true ),
            $la_choice3   => get_post_meta( $la_post_id, '_live_appearance_choice_3_time', true ),
        ];

        $la_opp   = get_post_meta( $la_post_id, '_live_appearance_opponent_handle', true );
        $la_bkopp = get_post_meta( $la_post_id, '_live_appearance_backup_opponent_handle', true );
        $la_status = get_post_meta( $la_post_id, '_live_appearance_status', true );
        $la_status_label = $la_status;
        if ( $la_status === 'confirmed' ) {
            $la_status_label = 'Confirmed';
        } elseif ( strpos( $la_status, 'choice' ) !== false ) {
            $la_status_label = ucwords( str_replace( '_', ' ', $la_status ) );
        }

        $la_opp_label = $la_opp ? $la_opp : 'Opponent TBD';
        $la_display_name = $la_opp_label;
        if ( ! empty( $la_bkopp ) ) {
            $la_display_name .= ' (backup: ' . $la_bkopp . ')';
        }

        $la_date_candidates = array_filter( [ $la_day_raw, $la_bkday_raw, $la_choice3 ] );
        foreach ( $la_date_candidates as $la_md ) {
            $la_parts = explode( '/', (string) $la_md );
            if ( count( $la_parts ) < 2 ) {
                continue;
            }

            $la_month = absint( trim( $la_parts[0] ) );
            $la_day   = absint( trim( $la_parts[1] ) );
            if ( $la_month < 1 || $la_month > 12 || $la_day < 1 || $la_day > 31 ) {
                continue;
            }

            $la_time = isset( $la_time_map[ $la_md ] ) ? $la_time_map[ $la_md ] : '';
            $la_hour = 12;
            if ( preg_match( '/^(\d{1,2}):\d{2}$/', $la_time, $la_match ) ) {
                $la_hour = min( 23, max( 0, absint( $la_match[1] ) ) );
            }

            $la_key_date = $la_year . '-' . $la_month . '-' . $la_day;
            if ( ! isset( $la_calendar_details[ $la_key_date ] ) ) {
                $la_calendar_details[ $la_key_date ] = [];
            }
            $la_calendar_details[ $la_key_date ][] = [
                'post_id'      => $la_post_id,
                'time'         => $la_time ?: 'TBD',
                'hour'         => $la_hour,
                'opp'          => $la_opp_label,
                'bkopp'        => $la_bkopp ? $la_bkopp : 'Backup TBD',
                'display'      => $la_display_name,
                'request_type' => $la_status_label,
                'month'        => $la_month,
                'day'          => $la_day,
            ];
        }
    }
}
?>

    <main id="primary" class="site-main">
        
        <?php get_template_part( 'template-parts/portal-header' ); ?>
        
        <div class="container py-2 live-page-wrap" id="portal-content">

            <!-- Live Content -->
            <div class="live-page-content">
                

                <?php
                $la_theme_uri = get_template_directory_uri();
                $la_months    = [ 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December' ];
                ?>
                <div class="live-wn-logo">
                    <img src="<?php echo esc_url( $la_theme_uri ); ?>/images/live/world-network-logo.png" alt="" class="live-wn-logo__camera">
                    <img src="<?php echo esc_url( $la_theme_uri ); ?>/images/live/world-network-wordmark.png" alt="world network live appearance" class="live-wn-logo__wordmark">
                </div>

                <section class="live-intro-text">
                    <p>We believe live competition creates real connection and lasting influence.</p>
                    <p>Influencers will automatically be eligible to make a live appearance on the World Network.</p>
                </section>

                    <div class="live-separator"></div>
                    <h2 class="live-section-heading live-section-heading--banner">SCHEDULE A LIVE APPEARANCE ON THE WORLD NETWORK FOR ANY OF THESE CONTEST TYPES</h2>
                    <div class="live-separator"></div>

                    <div class="live-contest-info" data-live-contest-info>
                        <div class="live-contest-tabs" role="tablist">
                            <button type="button" class="live-contest-tab is-open" role="tab" aria-selected="true" data-contest-panel="classic">
                                <span>CLASSIC</span>
                                <img src="<?php echo esc_url( $la_theme_uri ); ?>/images/live/chevron-right.svg" alt="" class="live-contest-chevron" aria-hidden="true">
                            </button>
                            <button type="button" class="live-contest-tab" role="tab" aria-selected="false" data-contest-panel="world_tour">
                                <span>WORLD TOUR</span>
                                <img src="<?php echo esc_url( $la_theme_uri ); ?>/images/live/chevron-right.svg" alt="" class="live-contest-chevron" aria-hidden="true">
                            </button>
                            <button type="button" class="live-contest-tab" role="tab" aria-selected="false" data-contest-panel="world_championship">
                                <span>WORLD CHAMPIONSHIP</span>
                                <img src="<?php echo esc_url( $la_theme_uri ); ?>/images/live/chevron-right.svg" alt="" class="live-contest-chevron" aria-hidden="true">
                            </button>
                        </div>
                        <div class="live-contest-panel is-open" id="live-contest-panel-classic" data-contest-panel="classic" role="tabpanel">
                            <ul>
                                <li>Top 30% Split the Pool at the end of each 1 Hand contest.</li>
                                <li>Four Entry Fees - $1, $10, $100, $1,000</li>
                                <li>Example: 110 Finalists in the $10 contest would result in $1,000 being distributed to the top 30% of Finalists.</li>
                            </ul>
                        </div>
                        <div class="live-contest-panel" id="live-contest-panel-world_tour" data-contest-panel="world_tour" role="tabpanel" hidden>
                            <ul>
                                <li>Weekend 3-level tournaments which alternate between 13 International cities throughout the year.</li>
                                <li>Runs each week from 5 PM Thursday through 10 PM Sunday.</li>
                                <li>Enter on the $10 or $100 level and win your way into the $1,000 finals.</li>
                                <li>Example: 1100 Finalists will result in $1,000,000 being distributed to the top 30% of Finalists.</li>
                            </ul>
                        </div>
                        <div class="live-contest-panel" id="live-contest-panel-world_championship" data-contest-panel="world_championship" role="tabpanel" hidden>
                            <ul>
                                <li>Yearly 5-level tournament.</li>
                                <li>Enter on the $1, $10, $100 or $1,000 level and win your way into the $10,000 finals.</li>
                                <li>Example: 11,000 Finalists will result in $100,000,000 being distributed to the top 30% of Finalists.</li>
                            </ul>
                        </div>
                    </div>

                    <div class="live-form-block" id="live-request">
                        <form id="live-request-form">
                            <h2 class="live-heading-gold">SELECT CONTEST TYPE</h2>
                            <div class="live-check-list">
                                <label class="live-check-opt">
                                    <input type="radio" name="la_contest_type" value="classic" class="live-type-radio" data-live-contest-type checked>
                                    <span class="live-check-label">Classic</span>
                                </label>
                                <label class="live-check-opt">
                                    <input type="radio" name="la_contest_type" value="world_tour" class="live-type-radio" data-live-contest-type>
                                    <span class="live-check-label">World Tour</span>
                                </label>
                                <label class="live-check-opt">
                                    <input type="radio" name="la_contest_type" value="world_championship" class="live-type-radio" data-live-contest-type>
                                    <span class="live-check-label">World Championship</span>
                                </label>
                            </div>

                            <h2 class="live-heading-gold">SCHEDULE WORLD NETWORK APPEARANCE</h2>
                            <div class="live-check-list">
                                <label class="live-check-opt">
                                    <input type="radio" name="la_stream_mode" value="individual" class="live-type-radio" data-live-stream>
                                    <span class="live-check-label">Individual Stream</span>
                                </label>
                                <label class="live-check-opt">
                                    <input type="radio" name="la_stream_mode" value="joint" class="live-type-radio" data-live-stream checked>
                                    <span class="live-check-label">Joint Stream - you and the Influencer you&rsquo;ve challenged who has already verbaly accepted.</span>
                                </label>
                            </div>

                            <p class="live-referral-note">Please provide this link to Influencer you are referring who do not have already created account, so you will receive full credit of the play from his/hers and his/hers followers.</p>

                            <div id="live-opponent-block">
                                <p class="live-label live-label--opponent-info">Private Challenge Opponent Information</p>
                                <div class="live-input-row live-input-row-2">
                                    <div class="live-field">
                                        <input type="text" name="la_opponent_first_name" id="la_opponent_first_name" class="live-input" placeholder="First Name" autocomplete="off">
                                    </div>
                                    <div class="live-field">
                                        <input type="text" name="la_opponent_last_name" id="la_opponent_last_name" class="live-input" placeholder="Last Name" autocomplete="off">
                                    </div>
                                </div>
                                <div class="live-input-row live-input-row-2">
                                    <div class="live-field">
                                        <input type="email" name="la_opponent_email" id="la_opponent_email" class="live-input" placeholder="Opponent email" autocomplete="off">
                                    </div>
                                    <div class="live-field">
                                        <input type="text" name="la_opponent_handle" id="la_opponent_handle" class="live-input" placeholder="Opponent username" autocomplete="off">
                                    </div>
                                </div>
                            </div>

                            <div class="live-calendar-launch-row">
                                <span class="live-calendar-launch-label">Available time slots:</span>
                                <button type="button" id="live-calendar-open-btn" class="live-calendar-open-btn" aria-haspopup="dialog" aria-controls="live-calendar-modal" aria-label="Open available time slots calendar"><img src="<?php echo esc_url( $la_theme_uri ); ?>/images/calendar.png" alt="Calendar" width="45" height="45"></button>
                            </div>

                            <div class="live-datetime-grid">
                                <div class="live-field">
                                    <label class="live-field-label" for="la_choice_1_month">Month</label>
                                    <select name="la_choice_1_month" id="la_choice_1_month" class="live-input">
                                        <option value="" disabled selected>Month</option>
                                        <?php foreach ( $la_months as $la_mi => $la_month ) : ?>
                                        <option value="<?php echo (int) ( $la_mi + 1 ); ?>"><?php echo esc_html( $la_month ); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="live-field">
                                    <label class="live-field-label" for="la_choice_1_day">Day</label>
                                    <select name="la_choice_1_day" id="la_choice_1_day" class="live-input">
                                        <option value="" disabled selected>Day</option>
                                        <?php for ( $la_d = 1; $la_d <= 31; $la_d++ ) : ?>
                                        <option value="<?php echo (int) $la_d; ?>"><?php echo esc_html( str_pad( (string) $la_d, 2, '0', STR_PAD_LEFT ) ); ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <div class="live-field">
                                    <label class="live-field-label" for="la_choice_1_time">Start Time</label>
                                    <input type="time" name="la_choice_1_time" id="la_choice_1_time" class="live-input" placeholder="Start Time">
                                </div>
                                <div class="live-field">
                                    <label class="live-field-label" for="la_choice_1_end_time">End Time</label>
                                    <input type="time" name="la_choice_1_end_time" id="la_choice_1_end_time" class="live-input" placeholder="End Time">
                                </div>
                            </div>

                            <div class="live-check-list">
                                <label class="live-check-opt">
                                    <input type="checkbox" name="la_make_appearance" id="la_make_appearance" value="1" class="live-type-radio">
                                    <span class="live-check-label">Make This Appearance</span>
                                </label>
                                <label class="live-check-opt live-check-opt--nested">
                                    <input type="checkbox" name="la_daily" id="la_daily" value="1" class="live-type-radio">
                                    <span class="live-check-label">Daily</span>
                                </label>
                            </div>

                            <input type="hidden" name="la_type" id="la_type" value="single">
                            <input type="hidden" name="la_choice_2_month" id="la_choice_2_month" value="">
                            <input type="hidden" name="la_choice_2_day" id="la_choice_2_day" value="">
                            <input type="hidden" name="la_choice_2_time" id="la_choice_2_time" value="">
                            <input type="hidden" name="la_choice_3_month" id="la_choice_3_month" value="">
                            <input type="hidden" name="la_choice_3_day" id="la_choice_3_day" value="">
                            <input type="hidden" name="la_choice_3_time" id="la_choice_3_time" value="">
                            <input type="hidden" name="la_backup_opponent_handle" id="la_backup_opponent_handle" value="">
                            <input type="hidden" name="la_opponent_comm" id="la_opponent_comm" value="">
                            <input type="hidden" name="la_backup_opponent_comm" id="la_backup_opponent_comm" value="">

                            <p class="live-label">Unique Live Appearance URL Address to Share</p>
                            <div id="live-url-wrap" class="live-url-wrap">
                                <div class="live-url live-url--display" id="live-url-display">URL will appear here...</div>
                                <button type="button" id="live-url-copy-btn" class="live-inline-btn" onclick="(function(){
                                    var txt=document.getElementById('live-url-display').textContent;
                                    if(!txt||txt==='URL will appear here...')return;
                                    navigator.clipboard.writeText(txt).then(function(){
                                        var btn=document.getElementById('live-url-copy-btn');
                                        btn.textContent='copied!';
                                        setTimeout(function(){btn.textContent='copy';},2000);
                                    });
                                })()">copy</button>
                            </div>
                            <div id="live-url-qr" style="display:none;margin-top:12px;"></div>
                            <div id="live-qr-caption" style="display:none;margin-top:8px;font-size:13px;color:#ccc;display:none;align-items:center;gap:6px;">
                                Download and share your QR image
                                <a id="live-qr-download-btn" download="qr-code.png" href="#" style="background:#b8972f;color:#fff;font-size:13px;font-weight:600;text-decoration:none;padding:6px 14px;border-radius:4px;margin-left:6px;">Download</a>
                                <span class="live-qr-info-icon" tabindex="0" aria-label="How to scan" style="position:relative;cursor:pointer;display:inline-flex;align-items:center;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                                    <span class="live-qr-tooltip" role="tooltip" style="display:none;position:absolute;left:24px;top:-8px;background:#1a1a1a;border:1px solid #555;border-radius:6px;padding:14px 16px;width:260px;font-size:12px;line-height:1.6;color:#ddd;z-index:9999;pointer-events:none;">
                                        <strong style="display:block;margin-bottom:6px;">How to scan:</strong>
                                        &bull; Open your phone&rsquo;s camera<br>
                                        &bull; Point it at the code<br>
                                        &bull; Tap the link that appears<br><br>
                                        <em>Viewing on this phone? Press and hold the code, then tap the link.</em><br><br>
                                        Works on any iPhone or Android from 2017 or newer. No app needed.
                                    </span>
                                </span>
                            </div>

                            <div class="live-submit-row live-submit-row--pair">
                                <button type="submit" class="live-submit live-submit--wide" id="live-request-btn">SUBMIT</button>
                                <button type="button" class="live-submit live-submit--wide" id="live-request-cancel">CANCEL</button>
                            </div>
                            <div id="live-request-msg" class="live-request-msg" style="display:none;"></div>

                            <button type="button" class="live-add-another" id="live-add-another">
                                <img src="<?php echo esc_url( $la_theme_uri ); ?>/images/live/icon-plus.png" alt="" class="live-add-another__icon" width="48" height="48">
                                <span>CLICK TO ADD ANOTHER SCHEDULED APPEARANCE</span>
                            </button>
                        </form>

                        <p class="live-label">Status of Request</p>
                        <div class="live-status" id="live-request-status">—</div>
                    </div>

                    <div id="live-calendar-modal" class="live-calendar-modal" style="display:none;">
                        <div class="live-calendar-modal__overlay"></div>
                        <div class="live-calendar-modal__box" role="dialog" aria-modal="true" aria-labelledby="live-calendar-modal-title">
                            <div class="live-calendar-modal__head">
                                <h3 id="live-calendar-modal-title" class="live-calendar-modal__title">AVAILABLE TIME SLOTS</h3>
                                <button type="button" id="live-calendar-close-btn" class="live-inline-btn">close</button>
                            </div>
                            <?php ihq_calendar( $la_calendar_occupied, $la_calendar_details ); ?>
                        </div>
                    </div>

                    <div id="live-calendar-day-modal" class="live-calendar-modal" style="display:none;">
                        <div class="live-calendar-modal__overlay"></div>
                        <div class="live-calendar-modal__box" role="dialog" aria-modal="true" aria-labelledby="live-calendar-day-modal-title">
                            <div class="live-calendar-modal__head">
                                <div>
                                    <h3 id="live-calendar-day-modal-title" class="live-calendar-modal__title">Day Schedule</h3>
                                    <div id="live-calendar-day-subtitle" style="color:#ccc;font-size:13px;margin-top:4px;"></div>
                                <button type="button" id="live-calendar-day-time-toggle" class="live-inline-btn" style="font-size:13px;margin-top:8px;">AM/PM</button>
                                </div>
                                <button type="button" id="live-calendar-day-close-btn" class="live-inline-btn">back</button>
                            </div>
                            <div id="live-calendar-hour-grid" style="display:grid;gap:10px;"></div>
                        </div>
                    </div>

                    <?php
                    // ── Upcoming Live Appearance Schedule ───────────────────
                    $la_months_map = ['','January','February','March','April','May','June','July','August','September','October','November','December'];
                    $la_confirmed  = get_posts( array(
                        'post_type'      => 'live_appearance',
                        'post_status'    => 'publish',
                        'author'         => get_current_user_id(),
                        'posts_per_page' => -1,
                        'orderby'        => 'meta_value',
                        'meta_key'       => '_live_appearance_date_created',
                        'order'          => 'DESC',
                        'meta_query'     => array(
                            array(
                                'key'   => '_live_appearance_status',
                                'value' => 'confirmed',
                            ),
                        ),
                    ) );
                    ?>
                    <div class="live-separator"></div>
                    <h2 class="live-section-heading">LIVE APPEARANCE SCHEDULE</h2>
                    <div class="live-separator"></div>

                    <div class="la-schedule-wrap">
                        <div class="la-schedule-header">
                            <span>Upcoming Live Appearances</span>
                        </div>
                        <div id="la-schedule-list">
                            <?php
                            $la_logged_user = wp_get_current_user();
                            $la_user_name   = $la_logged_user->display_name ?: $la_logged_user->user_login;
                            ?>
                            <?php if ( empty( $la_confirmed ) ) : ?>
                            <p class="live-copy-muted la-schedule-empty">No confirmed appearances yet.</p>
                            <?php else : ?>
                            <?php foreach ( $la_confirmed as $la_cpost ) :
                                $la_cid         = $la_cpost->ID;
                                $la_day_raw     = get_post_meta( $la_cid, '_live_appearance_day',                    true );
                                $la_bkday_raw   = get_post_meta( $la_cid, '_live_appearance_backup_day',             true );
                                $la_c1t         = get_post_meta( $la_cid, '_live_appearance_start_time',             true );
                                $la_c2t         = get_post_meta( $la_cid, '_live_appearance_backup_start_time',      true );
                                $la_c3m         = get_post_meta( $la_cid, '_live_appearance_choice_3_month',         true );
                                $la_c3d         = get_post_meta( $la_cid, '_live_appearance_choice_3_day',           true );
                                $la_c3t         = get_post_meta( $la_cid, '_live_appearance_choice_3_time',          true );
                                $la_opp         = get_post_meta( $la_cid, '_live_appearance_opponent_handle',        true );
                                $la_bkopp       = get_post_meta( $la_cid, '_live_appearance_backup_opponent_handle', true );
                                $la_oppcomm     = get_post_meta( $la_cid, '_live_appearance_opponent_comm',          true );
                                $la_bkoppcomm   = get_post_meta( $la_cid, '_live_appearance_backup_opponent_comm',   true );
                                $la_day_parts   = $la_day_raw   ? explode( '/', $la_day_raw )   : [ '', '' ];
                                $la_bkday_parts = $la_bkday_raw ? explode( '/', $la_bkday_raw ) : [ '', '' ];
                                $la_c1m = $la_day_parts[0]   ?? '';
                                $la_c1d = $la_day_parts[1]   ?? '';
                                $la_c2m = $la_bkday_parts[0] ?? '';
                                $la_c2d = $la_bkday_parts[1] ?? '';
                                $la_display_date = 'Date TBD';
                                $la_month_num = (int) $la_c1m;
                                $la_day_num   = (int) $la_c1d;
                                if ( $la_month_num >= 1 && $la_month_num <= 12 && $la_day_num >= 1 && $la_day_num <= 31 ) {
                                    $la_year = (int) current_time( 'Y' );
                                    $la_ts   = mktime( 12, 0, 0, $la_month_num, $la_day_num, $la_year );
                                    if ( $la_ts < ( current_time( 'timestamp' ) - DAY_IN_SECONDS ) ) {
                                        $la_ts = mktime( 12, 0, 0, $la_month_num, $la_day_num, $la_year + 1 );
                                    }
                                    if ( $la_ts ) {
                                        $la_display_date = date_i18n( 'Y F l jS', $la_ts );
                                    }
                                }
                                $la_opp_label   = $la_opp ? $la_opp : 'Opponent TBD';
                                $la_matchup_txt = $la_user_name . ' vs ' . $la_opp_label;
                                if ( ! empty( $la_bkopp ) ) {
                                    $la_matchup_txt .= ' (backup: ' . $la_bkopp . ')';
                                }
                            ?>
                            <div class="la-schedule-item" data-id="<?php echo esc_attr( $la_cid ); ?>">
                                <span class="la-schedule-name"><?php echo esc_html( $la_matchup_txt . ' | ' . $la_display_date ); ?></span>
                                <div class="la-schedule-actions">
                                    <button type="button" class="live-inline-btn la-edit-btn"
                                        data-id="<?php echo esc_attr( $la_cid ); ?>"
                                        data-c1m="<?php echo esc_attr( $la_c1m ); ?>"
                                        data-c1d="<?php echo esc_attr( $la_c1d ); ?>"
                                        data-c1t="<?php echo esc_attr( $la_c1t ); ?>"
                                        data-c2m="<?php echo esc_attr( $la_c2m ); ?>"
                                        data-c2d="<?php echo esc_attr( $la_c2d ); ?>"
                                        data-c2t="<?php echo esc_attr( $la_c2t ); ?>"
                                        data-c3m="<?php echo esc_attr( $la_c3m ); ?>"
                                        data-c3d="<?php echo esc_attr( $la_c3d ); ?>"
                                        data-c3t="<?php echo esc_attr( $la_c3t ); ?>"
                                        data-opp="<?php echo esc_attr( $la_opp ); ?>"
                                        data-bkopp="<?php echo esc_attr( $la_bkopp ); ?>"
                                        data-oppcomm="<?php echo esc_attr( $la_oppcomm ); ?>"
                                        data-bkoppcomm="<?php echo esc_attr( $la_bkoppcomm ); ?>"
                                    >Edit</button>
                                    <button type="button" class="live-inline-btn la-cancel-btn"
                                        data-id="<?php echo esc_attr( $la_cid ); ?>"
                                    >Cancel</button>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>


                </div>
            
            </div>
        
        <!-- Edit Live Appearance Modal -->
        <div id="la-edit-modal" style="display:none;position:fixed;inset:0;z-index:9999;">
            <div class="la-edit-overlay" style="position:absolute;inset:0;background:rgba(0,0,0,0.75);"></div>
            <div class="la-edit-box" style="position:relative;z-index:1;background:#1a1a1a;border:1px solid #b8972f;border-radius:10px;padding:24px 20px;width:92%;max-width:480px;margin:10vh auto;max-height:80vh;overflow-y:auto;">
                <h3 style="color:#b8972f;font-size:15px;letter-spacing:1px;margin-bottom:16px;">EDIT LIVE APPEARANCE</h3>
                <input type="hidden" id="la-edit-post-id">
                <p class="live-label">Request Day &amp; Start Time (1 hour)</p>
                <?php
                $la_edit_choices = [ ['1st','1'], ['2nd','2'], ['3rd','3'] ];
                foreach ( $la_edit_choices as [ $la_ord, $la_n ] ) :
                ?>
                <div class="live-input-row live-input-row-choice">
                    <span class="live-choice-label"><?php echo esc_html( $la_ord ); ?> Choice</span>
                    <div class="live-field">
                        <select id="la_edit_c<?php echo $la_n; ?>_month" class="live-input">
                            <option value="">month</option>
                            <?php foreach ( $la_months_map as $la_mi => $la_mn ) : if ( $la_mi === 0 ) continue; ?>
                            <option value="<?php echo $la_mi; ?>"><?php echo esc_html( $la_mn ); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="live-field">
                        <select id="la_edit_c<?php echo $la_n; ?>_day" class="live-input">
                            <option value="">day</option>
                            <?php for ( $la_di = 1; $la_di <= 31; $la_di++ ) : ?>
                            <option value="<?php echo $la_di; ?>"><?php echo $la_di; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="live-field">
                        <input type="time" id="la_edit_c<?php echo $la_n; ?>_time" class="live-input">
                    </div>
                </div>
                <?php endforeach; ?>
                <div class="live-input-row live-input-row-2">
                    <div class="live-field">
                        <input type="text" id="la-edit-opp" class="live-input" placeholder="opponent handle">
                    </div>
                    <div class="live-field">
                        <input type="text" id="la-edit-bkopp" class="live-input" placeholder="backup opponent handle">
                    </div>
                </div>
                <div class="live-input-row live-input-row-2">
                    <div class="live-field">
                        <select id="la-edit-oppcomm" class="live-input live-select">
                            <option value="" disabled selected>select comm method</option>
                            <option value="email">Email</option>
                            <option value="whatsapp">WhatsApp</option>
                            <option value="kakaotalk">KakaoTalk</option>
                            <option value="line">Line</option>
                            <option value="wechat">WeChat</option>
                            <option value="telegram">Telegram</option>
                        </select>
                    </div>
                    <div class="live-field">
                        <select id="la-edit-bkoppcomm" class="live-input live-select">
                            <option value="" disabled selected>select comm method</option>
                            <option value="email">Email</option>
                            <option value="whatsapp">WhatsApp</option>
                            <option value="kakaotalk">KakaoTalk</option>
                            <option value="line">Line</option>
                            <option value="wechat">WeChat</option>
                            <option value="telegram">Telegram</option>
                        </select>
                    </div>
                </div>
                <div class="live-submit-row" style="gap:12px;">
                    <button type="button" class="live-submit" id="la-edit-save-btn">SAVE</button>
                    <button type="button" class="live-inline-btn" id="la-edit-close-btn" style="font-size:13px;">close</button>
                </div>
                <div id="la-edit-msg" class="live-request-msg" style="display:none;margin-top:10px;"></div>
            </div>
        </div>


        <!-- Fixed Footer Links -->
        <?php get_template_part( 'template-parts/portal-footer' ); ?>
    </main><!-- #main -->

<?php
$_live_nonce = wp_create_nonce( 'request_live_appearance_nonce' );
?>
<script>
(function () {
    var _liveAjaxUrl     = <?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>;
    var _liveNonce       = <?php echo wp_json_encode( $_live_nonce ); ?>;

    var calendarModal     = document.getElementById('live-calendar-modal');
    var calendarOpenBtn   = document.getElementById('live-calendar-open-btn');
    var calendarCloseBtn  = document.getElementById('live-calendar-close-btn');
    var calendarOverlay   = calendarModal ? calendarModal.querySelector('.live-calendar-modal__overlay') : null;
    var dayModal          = document.getElementById('live-calendar-day-modal');
    var dayCloseBtn       = document.getElementById('live-calendar-day-close-btn');
    var dayOverlay        = dayModal ? dayModal.querySelector('.live-calendar-modal__overlay') : null;
    var daySubtitle       = document.getElementById('live-calendar-day-subtitle');
    var dayTimeToggle     = document.getElementById('live-calendar-day-time-toggle');
    var dayHourGrid       = document.getElementById('live-calendar-hour-grid');
    var liveCalendarDayDetails = <?php echo wp_json_encode( $la_calendar_details ); ?>;
    var liveCalendarMonthNames = ['January','February','March','April','May','June','July','August','September','October','November','December'];
    var liveCalendarTimeFormat = window.ihqCalTimeFormat || '12';
    var currentDayInfo = { year: null, month: null, day: null };

    function openCalendarModal() {
        if (!calendarModal) return;
        calendarModal.style.display = '';
    }

    function closeCalendarModal() {
        if (!calendarModal) return;
        calendarModal.style.display = 'none';
    }

    function openDayModal() {
        if (!dayModal) return;
        dayModal.style.display = '';
    }

    function updateLiveCalendarTimeToggle() {
        if (!dayTimeToggle) return;
        dayTimeToggle.textContent = liveCalendarTimeFormat === '24' ? '24h' : 'AM/PM';
    }

    function setLiveCalendarTimeFormat(format) {
        if (format !== '24' && format !== '12') return;
        liveCalendarTimeFormat = format;
        updateLiveCalendarTimeToggle();
        document.dispatchEvent(new CustomEvent('ihqCalTimeFormatRequest', { detail: { format: format } }));
        if (dayModal && dayModal.style.display !== 'none') {
            populateDayModal(currentDayInfo.year, currentDayInfo.month, currentDayInfo.day);
        }
    }

    document.addEventListener('ihqCalTimeFormatChange', function(e) {
        if (!e.detail || !e.detail.format) return;
        liveCalendarTimeFormat = e.detail.format;
        updateLiveCalendarTimeToggle();
        if (dayModal && dayModal.style.display !== 'none') {
            populateDayModal(currentDayInfo.year, currentDayInfo.month, currentDayInfo.day);
        }
    });

    function closeDayModal() {
        if (!dayModal) return;
        dayModal.style.display = 'none';
        currentDayInfo.year = null;
        currentDayInfo.month = null;
        currentDayInfo.day = null;
    }

    function pad2(num) {
        return (num < 10 ? '0' : '') + num;
    }

    function formatHourLabel(hour, use24) {
        if (use24) {
            return pad2(hour) + ':00';
        }
        var label = hour % 12 || 12;
        return label + ' ' + (hour < 12 ? 'AM' : 'PM');
    }

    function parseDayTime(value) {
        if (!value || typeof value !== 'string') {
            return null;
        }
        var trimmed = value.trim();
        var ampmMatch = trimmed.match(/^(\d{1,2})(?::(\d{2}))?\s*(AM|PM)$/i);
        if (ampmMatch) {
            var hour = parseInt(ampmMatch[1], 10);
            var minute = parseInt(ampmMatch[2] || '0', 10);
            var ampm = ampmMatch[3].toUpperCase();
            if (ampm === 'PM' && hour < 12) {
                hour += 12;
            }
            if (ampm === 'AM' && hour === 12) {
                hour = 0;
            }
            return { hour: hour, minute: minute };
        }
        var timeMatch = trimmed.match(/^(\d{1,2})(?::(\d{2}))$/);
        if (timeMatch) {
            return { hour: parseInt(timeMatch[1], 10), minute: parseInt(timeMatch[2], 10) };
        }
        return null;
    }

    function formatDayTime(value, fallbackHour, use24) {
        var parsed = parseDayTime(value);
        var hour = fallbackHour;
        var minute = 0;
        if (parsed) {
            hour = parsed.hour;
            minute = parsed.minute;
        }
        if (hour == null || isNaN(hour)) {
            return 'TBD';
        }
        if (use24) {
            return pad2(hour) + ':' + pad2(minute);
        }
        var label = hour % 12 || 12;
        return label + ':' + pad2(minute) + (hour < 12 ? ' AM' : ' PM');
    }

    function populateDayModal(year, month, day) {
        currentDayInfo.year = year;
        currentDayInfo.month = month;
        currentDayInfo.day = day;
        if (!dayHourGrid || !dayModal) return;
        var key = year + '-' + (month + 1) + '-' + day;
        var entries = liveCalendarDayDetails[key] || [];
        if (daySubtitle) {
            daySubtitle.textContent = liveCalendarMonthNames[month] + ' ' + day + ', ' + year;
        }
        var html = '';
        for (var hour = 0; hour < 24; hour++) {
            var hourLabel = formatHourLabel(hour, liveCalendarTimeFormat === '24');
            var slotEntries = entries.filter(function(item) {
                return parseInt(item.hour, 10) === hour;
            });
            if (slotEntries.length) {
                html += '<div style="display:grid;grid-template-columns:80px minmax(0,1fr);gap:10px;align-items:start;padding:10px 12px;background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.08);border-radius:10px;"><div style="color:#b8972f;font-weight:700;font-size:13px;">' + hourLabel + '</div><div style="display:grid;gap:6px;color:#ddd;font-size:13px;">';
                slotEntries.forEach(function(item) {
                    var displayTime = formatDayTime(item.time, parseInt(item.hour, 10), liveCalendarTimeFormat === '24');
                    html += '<div style="color:#fff;font-size:14px;font-weight:600;">' + (item.request_type || 'Live Appearance') + '</div>';
                    html += '<div style="color:#bbb;font-size:12px;line-height:1.4;">' + displayTime + ' · ' + (item.opp || 'Opponent TBD') + ' · ' + (item.bkopp || 'Backup TBD') + '</div>';
                });
                html += '</div></div>';
            } else {
                html += '<div style="display:grid;grid-template-columns:80px minmax(0,1fr);gap:10px;align-items:start;padding:10px 12px;background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.08);border-radius:10px;"><div style="color:#b8972f;font-weight:700;font-size:13px;">' + hourLabel + '</div><div style="color:#ddd;font-size:13px;">No scheduled appearance.</div></div>';
            }
        }
        dayHourGrid.innerHTML = html;
        openDayModal();
    }

    document.addEventListener('ihqCalDayClick', function(e) {
        if (!e.detail) return;
        populateDayModal(e.detail.year, e.detail.month, e.detail.day);
    });

    if (calendarOpenBtn) { calendarOpenBtn.addEventListener('click', openCalendarModal); }
    if (calendarCloseBtn) { calendarCloseBtn.addEventListener('click', closeCalendarModal); }
    if (calendarOverlay) { calendarOverlay.addEventListener('click', closeCalendarModal); }
    if (dayCloseBtn) { dayCloseBtn.addEventListener('click', closeDayModal); }
    if (dayOverlay) { dayOverlay.addEventListener('click', closeDayModal); }
    if (dayTimeToggle) { dayTimeToggle.addEventListener('click', function() { setLiveCalendarTimeFormat(liveCalendarTimeFormat === '24' ? '12' : '24'); }); }
    updateLiveCalendarTimeToggle();
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            if (dayModal && dayModal.style.display !== 'none') {
                closeDayModal();
                return;
            }
            closeCalendarModal();
        }
    });

    function setStatus(label, statusKey) {
        var el = document.getElementById('live-request-status');
        if (!el) return;
        el.textContent = label;
        el.className   = 'live-status' + (statusKey === 'confirmed' ? ' live-status--confirmed' : ' live-status--pending');
    }

    function setReferralUrl(url) {
        var el = document.getElementById('live-url-display');
        if (!el) return;
        var qrWrap = document.getElementById('live-url-qr');
        if (url) {
            el.textContent = url;
            el.classList.add('live-url--has-value');
            if (qrWrap) {
                qrWrap.innerHTML = '';
                qrWrap.style.display = '';
                new QRCode(qrWrap, { text: url, width: 150, height: 150, correctLevel: QRCode.CorrectLevel.M });
                setTimeout(function() {
                    var dlBtn = document.getElementById('live-qr-download-btn');
                    if (!dlBtn) return;
                    var canvas = qrWrap.querySelector('canvas');
                    if (canvas) {
                        dlBtn.href = canvas.toDataURL('image/png');
                    } else {
                        var img = qrWrap.querySelector('img');
                        if (img) { dlBtn.href = img.src; }
                    }
                }, 200);
            }
            var cap = document.getElementById('live-qr-caption');
            if (cap) { cap.style.display = 'flex'; }
        } else {
            el.textContent = 'URL will appear here...';
            el.classList.remove('live-url--has-value');
            if (qrWrap) { qrWrap.innerHTML = ''; qrWrap.style.display = 'none'; }
            var cap = document.getElementById('live-qr-caption');
            if (cap) { cap.style.display = 'none'; }
        }
    }

    // Tooltip show/hide for the QR info icon
    document.addEventListener('DOMContentLoaded', function() {
        var icon = document.querySelector('.live-qr-info-icon');
        if (!icon) return;
        var tip = icon.querySelector('.live-qr-tooltip');
        if (!tip) return;
        function showTip() { tip.style.display = 'block'; }
        function hideTip() { tip.style.display = 'none'; }
        icon.addEventListener('mouseenter', showTip);
        icon.addEventListener('mouseleave', hideTip);
        icon.addEventListener('focus', showTip);
        icon.addEventListener('blur', hideTip);
    });

    function showMsg(text, isError) {
        var el = document.getElementById('live-request-msg');
        if (!el) return;
        el.textContent   = text;
        el.className     = 'live-request-msg live-request-msg--' + (isError ? 'error' : 'success');
        el.style.display = '';
    }

    // Load the latest status on page load
    (function loadStatus() {
        var fd = new FormData();
        fd.append('action', 'get_live_appearance_status');
        fd.append('nonce',  _liveNonce);
        fetch(_liveAjaxUrl, { method: 'POST', body: fd })
            .then(function(r) { return r.json(); })
            .then(function(res) {
                if (res.success && res.data.found) {
                    setStatus(res.data.status_label, res.data.status_key);
                }
            }).catch(function() {});
    })();

    // Load the referral share URL on page load (separate from live-request status URL).
    (function loadReferralUrl() {
        var fd = new FormData();
        fd.append('action', 'get_referral_link');
        fd.append('nonce',  _liveNonce);
        fetch(_liveAjaxUrl, { method: 'POST', body: fd })
            .then(function(r) { return r.json(); })
            .then(function(res) {
                if (res.success && res.data && res.data.url) {
                    setReferralUrl(res.data.url);
                    return;
                }
                var errMsg = (res.data && res.data.message) ? res.data.message : 'Referral link unavailable.';
                setReferralUrl('');
                var el = document.getElementById('live-url-display');
                if (el) {
                    el.textContent = errMsg;
                }
            }).catch(function() {
                var el = document.getElementById('live-url-display');
                if (el) {
                    el.textContent = 'Could not load referral link.';
                }
            });
    })();

    function setLiveContestPanel(panelKey) {
        var tabs = document.querySelectorAll('.live-contest-tab');
        var panels = document.querySelectorAll('.live-contest-panel');
        tabs.forEach(function(tab) {
            var isOpen = tab.getAttribute('data-contest-panel') === panelKey;
            tab.classList.toggle('is-open', isOpen);
            tab.setAttribute('aria-selected', isOpen ? 'true' : 'false');
        });
        panels.forEach(function(panel) {
            var isOpen = panel.getAttribute('data-contest-panel') === panelKey;
            panel.classList.toggle('is-open', isOpen);
            if (isOpen) {
                panel.removeAttribute('hidden');
            } else {
                panel.setAttribute('hidden', 'hidden');
            }
        });
        var typeInput = document.querySelector('input[name="la_contest_type"][value="' + panelKey + '"]');
        if (typeInput) {
            typeInput.checked = true;
        }
    }

    function syncLiveStreamMode() {
        var joint = document.querySelector('input[name="la_stream_mode"][value="joint"]');
        var block = document.getElementById('live-opponent-block');
        if (!block) return;
        var isJoint = joint ? joint.checked : false;
        block.hidden = !isJoint;
        var fields = block.querySelectorAll('input');
        fields.forEach(function(field) {
            field.disabled = !isJoint;
        });
    }

    function syncLiveTypeHidden() {
        var typeEl = document.getElementById('la_type');
        var dailyEl = document.getElementById('la_daily');
        if (!typeEl) return;
        typeEl.value = (dailyEl && dailyEl.checked) ? 'regular' : 'single';
    }

    function syncLiveOpponentComm() {
        var commEl = document.getElementById('la_opponent_comm');
        var emailEl = document.getElementById('la_opponent_email');
        if (!commEl) return;
        commEl.value = (emailEl && emailEl.value.trim()) ? 'email' : '';
    }

    var form = document.getElementById('live-request-form');

    function resetLiveRequestForm(keepMessage) {
        if (!form) return;
        var opponentBlock = document.getElementById('live-opponent-block');
        if (opponentBlock) {
            opponentBlock.querySelectorAll('input').forEach(function(field) {
                field.disabled = false;
            });
        }
        form.reset();
        var classic = document.querySelector('input[name="la_contest_type"][value="classic"]');
        var joint = document.querySelector('input[name="la_stream_mode"][value="joint"]');
        if (classic) classic.checked = true;
        if (joint) joint.checked = true;
        setLiveContestPanel('classic');
        syncLiveStreamMode();
        syncLiveTypeHidden();
        if (!keepMessage) {
            var msg = document.getElementById('live-request-msg');
            if (msg) msg.style.display = 'none';
        }
    }

    document.querySelectorAll('.live-contest-tab').forEach(function(tab) {
        tab.addEventListener('click', function() {
            setLiveContestPanel(tab.getAttribute('data-contest-panel'));
        });
    });

    document.querySelectorAll('[data-live-contest-type]').forEach(function(input) {
        input.addEventListener('change', function() {
            if (input.checked) {
                setLiveContestPanel(input.value);
            }
        });
    });

    document.querySelectorAll('[data-live-stream]').forEach(function(input) {
        input.addEventListener('change', syncLiveStreamMode);
    });

    var dailyEl = document.getElementById('la_daily');
    var makeEl = document.getElementById('la_make_appearance');
    if (dailyEl) {
        dailyEl.addEventListener('change', function() {
            if (dailyEl.checked && makeEl) {
                makeEl.checked = true;
            }
            syncLiveTypeHidden();
        });
    }
    if (makeEl) {
        makeEl.addEventListener('change', function() {
            if (!makeEl.checked && dailyEl) {
                dailyEl.checked = false;
            }
            syncLiveTypeHidden();
        });
    }

    // Form submission
    syncLiveStreamMode();
    syncLiveTypeHidden();
    if (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            syncLiveTypeHidden();
            syncLiveOpponentComm();
            var btn = document.getElementById('live-request-btn');
            if (btn) { btn.disabled = true; btn.textContent = 'SUBMITTING...'; }
            var fd = new FormData(form);
            fd.append('action', 'request_live_appearance');
            fd.append('nonce',  _liveNonce);
            fetch(_liveAjaxUrl, { method: 'POST', body: fd })
                .then(function(r) { return r.json(); })
                .then(function(res) {
                    if (btn) { btn.disabled = false; btn.textContent = 'SUBMIT'; }
                    if (res.success) {
                        showMsg(res.data.message || 'Request submitted successfully.', false);
                        setStatus(res.data.status_label, res.data.status_key);
                        resetLiveRequestForm(true);
                    } else {
                        var msg = (res.data && res.data.message) ? res.data.message : 'An error occurred.';
                        showMsg(msg, true);
                    }
                }).catch(function () {
                    if (btn) { btn.disabled = false; btn.textContent = 'SUBMIT'; }
                    showMsg('Network error. Please try again.', true);
                });
        });
    }

    var cancelBtn = document.getElementById('live-request-cancel');
    if (cancelBtn) {
        cancelBtn.addEventListener('click', function() {
            resetLiveRequestForm();
        });
    }

    var addAnotherBtn = document.getElementById('live-add-another');
    if (addAnotherBtn) {
        addAnotherBtn.addEventListener('click', function() {
            resetLiveRequestForm();
            var formBlock = document.getElementById('live-request');
            if (formBlock && formBlock.scrollIntoView) {
                formBlock.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
            var firstType = document.querySelector('[data-live-contest-type]');
            if (firstType) firstType.focus();
        });
    }

    // ── Live Appearance Schedule — edit & cancel ──────────────────────────────

    var laModal     = document.getElementById('la-edit-modal');
    var laPostIdEl  = document.getElementById('la-edit-post-id');
    var laSaveBtn   = document.getElementById('la-edit-save-btn');
    var laCloseBtn  = document.getElementById('la-edit-close-btn');
    var laOverlay   = laModal ? laModal.querySelector('.la-edit-overlay') : null;

    function laSetSel(id, val) {
        var el = document.getElementById(id);
        if (el) el.value = val || '';
    }

    function laOpenModal(btn) {
        if (!laModal) return;
        var d = btn.dataset;
        laPostIdEl.value = d.id;
        laSetSel('la_edit_c1_month', d.c1m);
        laSetSel('la_edit_c1_day',   d.c1d);
        laSetSel('la_edit_c1_time',  d.c1t);
        laSetSel('la_edit_c2_month', d.c2m);
        laSetSel('la_edit_c2_day',   d.c2d);
        laSetSel('la_edit_c2_time',  d.c2t);
        laSetSel('la_edit_c3_month', d.c3m);
        laSetSel('la_edit_c3_day',   d.c3d);
        laSetSel('la_edit_c3_time',  d.c3t);
        laSetSel('la-edit-opp',        d.opp);
        laSetSel('la-edit-bkopp',      d.bkopp);
        var oppCommEl   = document.getElementById('la-edit-oppcomm');
        var bkoppCommEl = document.getElementById('la-edit-bkoppcomm');
        if (oppCommEl)   oppCommEl.value   = d.oppcomm   || '';
        if (bkoppCommEl) bkoppCommEl.value = d.bkoppcomm || '';
        var msg = document.getElementById('la-edit-msg');
        if (msg) msg.style.display = 'none';
        laModal.style.display = '';
    }

    function laCloseModal() {
        if (laModal) laModal.style.display = 'none';
    }

    if (laCloseBtn) laCloseBtn.addEventListener('click', laCloseModal);
    if (laOverlay)  laOverlay.addEventListener('click', laCloseModal);

    var laList = document.getElementById('la-schedule-list');
    if (laList) {
        laList.addEventListener('click', function (e) {
            var editBtn   = e.target.closest('.la-edit-btn');
            var cancelBtn = e.target.closest('.la-cancel-btn');

            if (editBtn) {
                laOpenModal(editBtn);
            }

            if (cancelBtn) {
                var pid = cancelBtn.getAttribute('data-id');
                if (!confirm('Cancel this live appearance? This cannot be undone.')) return;
                cancelBtn.disabled = true;
                var fd = new FormData();
                fd.append('action',  'delete_live_appearance');
                fd.append('nonce',   _liveNonce);
                fd.append('post_id', pid);
                fetch(_liveAjaxUrl, { method: 'POST', body: fd })
                    .then(function (r) { return r.json(); })
                    .then(function (res) {
                        if (res.success) {
                            var row = laList.querySelector('.la-schedule-item[data-id="' + pid + '"]');
                            if (row) row.remove();
                            if (!laList.querySelector('.la-schedule-item')) {
                                laList.innerHTML = '<p class="live-copy-muted la-schedule-empty">No confirmed appearances yet.</p>';
                            }
                        } else {
                            cancelBtn.disabled = false;
                            alert((res.data && res.data.message) ? res.data.message : 'Error cancelling.');
                        }
                    }).catch(function () { cancelBtn.disabled = false; });
            }
        });
    }

    if (laSaveBtn) {
        laSaveBtn.addEventListener('click', function () {
            laSaveBtn.disabled   = true;
            laSaveBtn.textContent = 'SAVING...';
            var pid = laPostIdEl.value;
            var fd  = new FormData();
            fd.append('action',                  'update_live_appearance');
            fd.append('nonce',                   _liveNonce);
            fd.append('post_id',                 pid);
            fd.append('la_choice_1_month',       document.getElementById('la_edit_c1_month').value);
            fd.append('la_choice_1_day',         document.getElementById('la_edit_c1_day').value);
            fd.append('la_choice_1_time',        document.getElementById('la_edit_c1_time').value);
            fd.append('la_choice_2_month',       document.getElementById('la_edit_c2_month').value);
            fd.append('la_choice_2_day',         document.getElementById('la_edit_c2_day').value);
            fd.append('la_choice_2_time',        document.getElementById('la_edit_c2_time').value);
            fd.append('la_choice_3_month',       document.getElementById('la_edit_c3_month').value);
            fd.append('la_choice_3_day',         document.getElementById('la_edit_c3_day').value);
            fd.append('la_choice_3_time',        document.getElementById('la_edit_c3_time').value);
            fd.append('la_opponent_handle',        document.getElementById('la-edit-opp').value);
            fd.append('la_backup_opponent_handle', document.getElementById('la-edit-bkopp').value);
            fd.append('la_opponent_comm',          document.getElementById('la-edit-oppcomm').value);
            fd.append('la_backup_opponent_comm',   document.getElementById('la-edit-bkoppcomm').value);
            fetch(_liveAjaxUrl, { method: 'POST', body: fd })
                .then(function (r) { return r.json(); })
                .then(function (res) {
                    laSaveBtn.disabled   = false;
                    laSaveBtn.textContent = 'SAVE';
                    if (res.success) {
                        laCloseModal();
                    } else {
                        var msg = document.getElementById('la-edit-msg');
                        if (msg) {
                            msg.textContent   = (res.data && res.data.message) ? res.data.message : 'Error saving.';
                            msg.className     = 'live-request-msg live-request-msg--error';
                            msg.style.display = '';
                        }
                    }
                }).catch(function () {
                    laSaveBtn.disabled   = false;
                    laSaveBtn.textContent = 'SAVE';
                });
        });
    }

})();
</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<?php
get_template_part( 'template-parts/portal-scripts' );
get_footer();
