<?php
/**
 * Template Name: Portal Live
 * Description: A custom template for displaying the live streaming page.
 *
 * @package Avantage_Baccarat
 */
get_header();

// Load styles before content to prevent FOUC
get_template_part( 'template-parts/portal-styles' );

$la_calendar_occupied = [];
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
}
?>

    <main id="primary" class="site-main">
        
        <?php get_template_part( 'template-parts/portal-header' ); ?>
        
        <div class="container py-2 live-page-wrap" id="portal-content">

            <!-- Live Content -->
            <div class="live-page-content">
                

                <div class="live-header">
                    <img src="<?php echo get_template_directory_uri(); ?>/images/live.png" alt="Live Appearance" class="live-icon">
                    <h1 class="live-title">LIVE APPEARANCE</h1>
                </div>

                <section class="live-intro-text">
                    <p>There are two types of live appearances: KICK Network and on the World Broadcast Network.</p>
                    <p>We believe live competition creates real connection and lasting influence.</p>
                    <p>Influencers will automatically be eligible to appear in all 52 World Tour weekend events and the year-long $100 Million Avantage Baccarat World Championship competition.</p>
                </section>

                    <div class="live-separator"></div>
                    <h2 class="live-section-heading">REQUEST A LIVE APPEARANCE ON THE WORLD NETWORK</h2>
                    <div class="live-separator"></div>

                    <div class="live-intro-item live-intro-item-wide">
                        <div class="live-text-content">
                        <p>The 1–1 Private Challenge format allows two verified Influencers who already know each other to compete head-to-head in a live World Network broadcast.</p>
                        <p>This format is designed for rivalry, chemistry, and competitive storytelling.</p>
                        <p><strong>Eligibility</strong></p>
                        <ul>
                            <li>Both participants must be verified Influencers</li>
                            <li>One of the two must create the challenge</li>
                            <li>Both must agree in advance to appear live</li>
                            <li>Engagement must meet approval standards</li>
                            <li>Final approval required from Influencer HQ</li>
                        </ul>
                        <p><strong>Application</strong></p>
                        <ul>
                            <li>The challenge creator submits a Live Appearance request</li>
                            <li>Influencer HQ reviews engagement and readiness</li>
                            <li>Approved challenges receive a scheduled broadcast slot</li>
                        </ul>
                        <p><strong>Live Format</strong></p>
                        <ul>
                            <li>Up to 1 hour duration</li>
                            <li>Remote participation from each Influencer's location</li>
                            <li>Real-time competitive commentary</li>
                            <li>Followers may join and play along</li>
                            <li>Performance metrics displayed live</li>
                        </ul>
                        <p><strong>Important</strong></p>
                        <p>1–1 Challenges without a live request may proceed immediately.</p>
                        <p>1–1 Challenges requesting live broadcast require review and approval.</p>
                        </div>
                    </div>

                    <div class="live-form-block">
                        <form id="live-request-form">
                            <div class="live-calendar-launch-row">
                                <span class="live-calendar-launch-label">Available time slots:</span>
                                <button type="button" id="live-calendar-open-btn" class="live-calendar-open-btn" aria-haspopup="dialog" aria-controls="live-calendar-modal" aria-label="Open available time slots calendar">📅</button>
                            </div>
                            <p class="live-label">Request Day &amp; Start Time (1 hour)</p>
                            <?php
                            $la_months  = ['January','February','March','April','May','June','July','August','September','October','November','December'];
                            $la_choices = [['1st','1'],['2nd','2'],['3rd','3']];
                            foreach ( $la_choices as [$la_ordinal, $la_num] ) :
                            ?>
                            <div class="live-input-row live-input-row-choice">
                                <span class="live-choice-label"><?php echo esc_html( $la_ordinal ); ?> Choice</span>
                                <div class="live-field">
                                    <select name="la_choice_<?php echo $la_num; ?>_month" id="la_choice_<?php echo $la_num; ?>_month" class="live-input">
                                        <option value="" disabled selected>month</option>
                                        <?php foreach ( $la_months as $la_mi => $la_month ) : ?>
                                        <option value="<?php echo $la_mi + 1; ?>"><?php echo esc_html( $la_month ); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="live-field">
                                    <select name="la_choice_<?php echo $la_num; ?>_day" id="la_choice_<?php echo $la_num; ?>_day" class="live-input">
                                        <option value="" disabled selected>day</option>
                                        <?php for ( $la_d = 1; $la_d <= 31; $la_d++ ) : ?>
                                        <option value="<?php echo $la_d; ?>"><?php echo $la_d; ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <div class="live-field">
                                    <input type="time" name="la_choice_<?php echo $la_num; ?>_time" id="la_choice_<?php echo $la_num; ?>_time" class="live-input" placeholder="start time">
                                </div>
                            </div>
                            <?php endforeach; ?>
                            <div class="live-input-row live-input-row-2">
                                <div class="live-field">
                                    <input type="text" name="la_opponent_name" id="la_opponent_name" class="live-input" placeholder="opponent name">
                                </div>
                                <div class="live-field">
                                    <input type="text" name="la_backup_opponent_name" id="la_backup_opponent_name" class="live-input" placeholder="backup opponent name">
                                </div>
                            </div>
                            <div class="live-input-row live-input-row-2">
                                <div class="live-field">
                                    <input type="email" name="la_opponent_email" id="la_opponent_email" class="live-input" placeholder="opponent email">
                                </div>
                                <div class="live-field">
                                    <input type="email" name="la_backup_opponent_email" id="la_backup_opponent_email" class="live-input" placeholder="backup opponent email">
                                </div>
                            </div>
                            <div class="live-submit-row">
                                <button type="submit" class="live-submit" id="live-request-btn">REQUEST</button>
                            </div>
                            <div id="live-request-msg" class="live-request-msg" style="display:none;"></div>
                        </form>

                        <p class="live-label">Status of Request</p>
                        <div class="live-status" id="live-request-status">—</div>

                        <p class="live-label">Referral Link</p>
                        <div id="live-url-wrap" style="display:flex;align-items:center;gap:8px;">
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
                        </div>                        <div id="live-url-qr" style="display:none;margin-top:12px;"></div>
                        <div id="live-qr-caption" style="display:none;margin-top:8px;font-size:13px;color:#ccc;display:none;align-items:center;gap:6px;">
                            Scan to share QR code with opponent
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
                    </div>

                    <div id="live-calendar-modal" class="live-calendar-modal" style="display:none;">
                        <div class="live-calendar-modal__overlay"></div>
                        <div class="live-calendar-modal__box" role="dialog" aria-modal="true" aria-labelledby="live-calendar-modal-title">
                            <div class="live-calendar-modal__head">
                                <h3 id="live-calendar-modal-title" class="live-calendar-modal__title">AVAILABLE TIME SLOTS</h3>
                                <button type="button" id="live-calendar-close-btn" class="live-inline-btn">close</button>
                            </div>
                            <?php ihq_calendar( $la_calendar_occupied ); ?>
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
                                $la_oppemail    = get_post_meta( $la_cid, '_live_appearance_opponent_email',         true );
                                $la_bkoppemail  = get_post_meta( $la_cid, '_live_appearance_backup_opponent_email',  true );
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
                                        data-oppemail="<?php echo esc_attr( $la_oppemail ); ?>"
                                        data-bkoppemail="<?php echo esc_attr( $la_bkoppemail ); ?>"
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

                    <div class="live-separator"></div>
                    <h2 class="live-section-heading">STREAMING ON KICK</h2>
                    <div class="live-separator"></div>

                    <div class="live-intro-item live-intro-item-wide">
                        <div class="live-text-content">
                        <p>Simply notify Influencer HQ of your scheduled KICK broadcasts so we can:</p>
                        <ul>
                            <li>Publish your stream schedule</li>
                            <li>Promote your appearances</li>
                            <li>Provide you with the proper tracked links</li>
                        </ul>
                        <p>Using official tracked links ensures you receive full credit for follower participation.</p>
                        <p><strong>KICK Stream Equity Bonus</strong></p>
                        <p>You earn a 1% equity bonus on all play generated by viewers participating.</p>
                        </div>
                    </div>

                    <div class="live-separator"></div>
                    <h2 class="live-section-heading">KICK BROADCASTING SCHEDULE</h2>

                    <p class="live-copy">Current Schedule</p>

                    <div id="kick-schedule-list">
                        <?php
                        $ks_schedule = get_user_meta( get_current_user_id(), '_kick_broadcasting_schedule', true );
                        if ( is_array( $ks_schedule ) && ! empty( $ks_schedule ) ) :
                            foreach ( $ks_schedule as $ks_i => $ks_item ) :
                                $ks_day_fmt   = ! empty( $ks_item['day'] )        ? date_i18n( 'D, M j', strtotime( $ks_item['day'] ) )        : '';
                                $ks_start_fmt = ! empty( $ks_item['start_time'] ) ? date_i18n( 'g:i A',   strtotime( $ks_item['start_time'] ) ) : '';
                                $ks_end_fmt   = ! empty( $ks_item['end_time'] )   ? date_i18n( 'g:i A',   strtotime( $ks_item['end_time'] ) )   : '';
                                $ks_label     = trim( $ks_day_fmt . ( $ks_start_fmt ? ', ' . $ks_start_fmt : '' ) . ( $ks_end_fmt ? ' - ' . $ks_end_fmt : '' ) );
                        ?>
                        <div class="live-schedule-item" data-index="<?php echo esc_attr( $ks_i ); ?>">
                            <span><?php echo esc_html( $ks_label ); ?></span>
                            <button type="button" class="live-inline-btn kick-cancel-btn" data-index="<?php echo esc_attr( $ks_i ); ?>">cancel</button>
                        </div>
                        <?php endforeach; else : ?>
                        <p class="live-copy-muted">No schedule entries yet.</p>
                        <?php endif; ?>
                    </div>

                    <p class="live-copy">Post Additional Schedule</p>
                    <form id="kick-schedule-form">
                        <div class="live-input-row">
                            <div class="live-field live-field--full">
                                <label class="live-field-label" for="ks_day">Date</label>
                                <input type="date" name="ks_day" id="ks_day" class="live-input live-input-md">
                            </div>
                        </div>
                        <div class="live-input-row live-input-row-2">
                            <div class="live-field">
                                <label class="live-field-label" for="ks_start_time">Start Time</label>
                                <input type="time" name="ks_start_time" id="ks_start_time" class="live-input live-input-md">
                            </div>
                            <div class="live-field">
                                <label class="live-field-label" for="ks_end_time">End Time</label>
                                <input type="time" name="ks_end_time" id="ks_end_time" class="live-input live-input-md">
                            </div>
                        </div>
                        <div class="live-submit-row">
                            <button type="submit" class="live-submit" id="kick-schedule-btn">SUBMIT</button>
                        </div>
                        <div id="kick-schedule-msg" class="live-request-msg" style="display:none;"></div>
                    </form>

                    <div class="live-separator live-separator-white"></div>

                    <h2 class="live-section-heading live-section-heading--how">HOW TO STREAM ON KICK</h2>

                    <div class="accordion-gradient-container">
                        <div class="accordion custom-accordion" id="liveStreamAccordion">

                            <!-- Set Up Your Stream -->
                            <div class="accordion-item mb-3">
                                <h2 class="accordion-header" id="headingSetup">
                                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSetup" aria-expanded="true" aria-controls="collapseSetup">
                                        <span class="question-text">Set Up Your Stream</span>
                                    </button>
                                </h2>
                                <div id="collapseSetup" class="accordion-collapse collapse show" aria-labelledby="headingSetup" data-bs-parent="#liveStreamAccordion">
                                    <div class="accordion-body">
                                        <p><strong>Purpose</strong></p>
                                        <p>By the time you leave this tab you will have:</p>
                                        <ul>
                                            <li>Created your Kick channel.</li>
                                            <li>Connected OBS Studio so your stream can go live.</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            <!-- Why Kick? -->
                            <div class="accordion-item mb-3">
                                <h2 class="accordion-header" id="headingWhyKick">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseWhyKick" aria-expanded="false" aria-controls="collapseWhyKick">
                                        <span class="question-text">Why Kick?</span>
                                    </button>
                                </h2>
                                <div id="collapseWhyKick" class="accordion-collapse collapse" aria-labelledby="headingWhyKick" data-bs-parent="#liveStreamAccordion">
                                    <div class="accordion-body">
                                        <p>Kick is the fastest-growing channel for streaming online gaming platform for live gaming and commentary — and it's where Avantage streams begin.</p>
                                        <p><strong>Important:</strong> On Kick, your account is your channel. When you sign up, you automatically create both.</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Step 1 – Create Your Kick Channel -->
                            <div class="accordion-item mb-3">
                                <h2 class="accordion-header" id="headingStep1">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseStep1" aria-expanded="false" aria-controls="collapseStep1">
                                        <span class="question-text">Step 1 – Create Your Kick Channel</span>
                                    </button>
                                </h2>
                                <div id="collapseStep1" class="accordion-collapse collapse" aria-labelledby="headingStep1" data-bs-parent="#liveStreamAccordion">
                                    <div class="accordion-body">
                                        <p><strong>1. Go to Kick.com → Sign Up.</strong></p>
                                        <ul>
                                            <li>Use email, Google, or Apple.</li>
                                            <li>Choose a username – this becomes your channel name.</li>
                                            <li>Confirm your email (Kick sends a link).</li>
                                        </ul>
                                        <p><strong>2. After confirming, Sign In.</strong></p>
                                        <ul>
                                            <li>Click your profile picture (top right) → Creator Dashboard.</li>
                                            <li>Upload a profile photo (headshot or "stage" shot).</li>
                                            <li>Upload a banner (simple background with your name or Avantage Baccarat).</li>
                                            <li>Free design tools: Canva.com or Fotor.com.</li>
                                            <li>Write a short description (2–3 lines):
                                                <ul>
                                                    <li>"Live Avantage Baccarat commentary — join me as I call the action in real time."</li>
                                                    <li>"Official Avantage Baccarat streamer. Watch, follow trends, and feel the energy with me."</li>
                                                </ul>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            <!-- Step 2 – Connect OBS Studio -->
                            <div class="accordion-item mb-3">
                                <h2 class="accordion-header" id="headingStep2">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseStep2" aria-expanded="false" aria-controls="collapseStep2">
                                        <span class="question-text">Step 2 - Connect OBS Studio</span>
                                    </button>
                                </h2>
                                <div id="collapseStep2" class="accordion-collapse collapse" aria-labelledby="headingStep2" data-bs-parent="#liveStreamAccordion">
                                    <div class="accordion-body">
                                        <p><strong>What is OBS?</strong></p>
                                        <p>Open Broadcaster Software (OBS) is the free program that acts as the control room for your stream.</p>
                                        <ul>
                                            <li>It brings together your camera, microphone, and game screen.</li>
                                            <li>It lets you arrange how everything looks.</li>
                                            <li>It sends that feed to your Kick channel so your stream actually goes live.</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            <!-- How to Set It Up -->
                            <div class="accordion-item mb-3">
                                <h2 class="accordion-header" id="headingHowToSetUp">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseHowToSetUp" aria-expanded="false" aria-controls="collapseHowToSetUp">
                                        <span class="question-text">How to Set It Up</span>
                                    </button>
                                </h2>
                                <div id="collapseHowToSetUp" class="accordion-collapse collapse" aria-labelledby="headingHowToSetUp" data-bs-parent="#liveStreamAccordion">
                                    <div class="accordion-body">
                                        <ol>
                                            <li>In your Kick Creator Dashboard → Settings → Stream → copy your Stream URL and Stream Key.</li>
                                            <li>Download OBS Studio from <a href="https://obsproject.com/" style="color: #b8972f;">obsproject.com</a>.</li>
                                            <li>Open OBS → Settings → Stream → select Custom Service → paste in the URL and Key.</li>
                                            <li>Select a suitable category such as Games or Entertainment.</li>
                                            <li>In OBS, click Start Streaming.</li>
                                            <li>You're live on your Kick channel.</li>
                                        </ol>
                                    </div>
                                </div>
                            </div>

                            <!-- Reassurance -->
                            <div class="accordion-item mb-3">
                                <h2 class="accordion-header" id="headingReassurance">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseReassurance" aria-expanded="false" aria-controls="collapseReassurance">
                                        <span class="question-text">Reassurance</span>
                                    </button>
                                </h2>
                                <div id="collapseReassurance" class="accordion-collapse collapse" aria-labelledby="headingReassurance" data-bs-parent="#liveStreamAccordion">
                                    <div class="accordion-body">
                                        <p>You now have your channel, your gear, and your settings.</p>
                                        <p>Take a deep breath, speak with confidence, and enjoy your first broadcast.</p>
                                        <p>Every stream will get easier — and every voice you reach builds your community.</p>
                                    </div>
                                </div>
                            </div>

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
                        <input type="text" id="la-edit-opp" class="live-input" placeholder="opponent name">
                    </div>
                    <div class="live-field">
                        <input type="text" id="la-edit-bkopp" class="live-input" placeholder="backup opponent name">
                    </div>
                </div>
                <div class="live-input-row live-input-row-2">
                    <div class="live-field">
                        <input type="email" id="la-edit-oppemail" class="live-input" placeholder="opponent email">
                    </div>
                    <div class="live-field">
                        <input type="email" id="la-edit-bkoppemail" class="live-input" placeholder="backup opponent email">
                    </div>
                </div>
                <div class="live-submit-row" style="gap:12px;">
                    <button type="button" class="live-submit" id="la-edit-save-btn">SAVE</button>
                    <button type="button" class="live-inline-btn" id="la-edit-close-btn" style="font-size:13px;">close</button>
                </div>
                <div id="la-edit-msg" class="live-request-msg" style="display:none;margin-top:10px;"></div>
            </div>
        </div>

        <style>
        .la-schedule-wrap { background:#111; border:1px solid #b8972f33; border-radius:8px; overflow:hidden; margin-bottom:4px; }
        .la-schedule-header { padding:10px 16px; font-size:13px; color:#888; border-bottom:1px solid #b8972f22; }
        .la-schedule-item { display:flex; align-items:center; justify-content:space-between; padding:12px 16px; border-bottom:1px solid #1e1e1e; }
        .la-schedule-item:last-child { border-bottom:none; }
        .la-schedule-name { color:#ccc; font-size:14px; flex:1; }
        .la-schedule-actions { display:flex; gap:8px; }

        .live-calendar-launch-row { display:flex; align-items:center; gap:12px; margin:2px 0 14px; }
        .live-calendar-launch-label { color:#dcdcdc; font-size:16px; }
        .live-calendar-open-btn {
            border:0;
            background:transparent;
            font-size:52px;
            line-height:1;
            cursor:pointer;
            padding:0;
            transition:transform .15s ease;
        }
        .live-calendar-open-btn:hover { transform:scale(1.04); }

        .live-calendar-modal {
            position:fixed;
            inset:0;
            z-index:10000;
        }
        .live-calendar-modal__overlay {
            position:absolute;
            inset:0;
            background:rgba(0,0,0,.76);
        }
        .live-calendar-modal__box {
            position:relative;
            z-index:1;
            width:min(960px,94vw);
            max-height:84vh;
            overflow:auto;
            margin:6vh auto;
            padding:18px;
            border:1px solid #b8972f;
            border-radius:12px;
            background:#060606;
        }
        .live-calendar-modal__head {
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:12px;
            margin-bottom:12px;
        }
        .live-calendar-modal__title {
            margin:0;
            color:#b8972f;
            font-size:14px;
            letter-spacing:1px;
        }
        </style>

        <!-- Fixed Footer Links -->
        <div class="footer-links-fixed">
            <a href="#" class="footer-link">Terms</a>
            <span class="footer-separator">|</span>
            <a href="#" class="footer-link">Privacy</a>
        </div>
    </main><!-- #main -->

<?php
$_live_nonce     = wp_create_nonce( 'request_live_appearance_nonce' );
$_schedule_nonce = wp_create_nonce( 'kick_schedule_nonce' );
?>
<script>
(function () {
    var _liveAjaxUrl    = <?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>;
    var _liveNonce      = <?php echo wp_json_encode( $_live_nonce ); ?>;
    var _scheduleNonce  = <?php echo wp_json_encode( $_schedule_nonce ); ?>;

    var calendarModal     = document.getElementById('live-calendar-modal');
    var calendarOpenBtn   = document.getElementById('live-calendar-open-btn');
    var calendarCloseBtn  = document.getElementById('live-calendar-close-btn');
    var calendarOverlay   = calendarModal ? calendarModal.querySelector('.live-calendar-modal__overlay') : null;

    function openCalendarModal() {
        if (!calendarModal) return;
        calendarModal.style.display = '';
    }

    function closeCalendarModal() {
        if (!calendarModal) return;
        calendarModal.style.display = 'none';
    }

    if (calendarOpenBtn) { calendarOpenBtn.addEventListener('click', openCalendarModal); }
    if (calendarCloseBtn) { calendarCloseBtn.addEventListener('click', closeCalendarModal); }
    if (calendarOverlay) { calendarOverlay.addEventListener('click', closeCalendarModal); }
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeCalendarModal();
    });

    function setStatus(label, statusKey) {
        var el = document.getElementById('live-request-status');
        if (!el) return;
        el.textContent = label;
        el.className   = 'live-status' + (statusKey === 'confirmed' ? ' live-status--confirmed' : ' live-status--pending');
    }

    function setUrl(url) {
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
                    setUrl(res.data.url);
                }
            }).catch(function() {});
    })();

    // Load the referral share URL on page load
    (function loadReferralUrl() {
        var fd = new FormData();
        fd.append('action', 'get_referral_link');
        fd.append('nonce',  _liveNonce);
        fetch(_liveAjaxUrl, { method: 'POST', body: fd })
            .then(function(r) { return r.json(); })
            .then(function(res) {
                if (res.success && res.data.url) {
                    setUrl(res.data.url);
                }
            }).catch(function() {});
    })();

    // Form submission
    var form = document.getElementById('live-request-form');
    if (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            var btn = document.getElementById('live-request-btn');
            if (btn) { btn.disabled = true; btn.textContent = 'SUBMITTING...'; }
            var fd = new FormData(form);
            fd.append('action', 'request_live_appearance');
            fd.append('nonce',  _liveNonce);
            fetch(_liveAjaxUrl, { method: 'POST', body: fd })
                .then(function(r) { return r.json(); })
                .then(function(res) {
                    if (btn) { btn.disabled = false; btn.textContent = 'REQUEST'; }
                    if (res.success) {
                        showMsg(res.data.message || 'Request submitted successfully.', false);
                        setStatus(res.data.status_label, res.data.status_key);
                        setUrl(res.data.url);
                        form.reset();
                    } else {
                        var msg = (res.data && res.data.message) ? res.data.message : 'An error occurred.';
                        showMsg(msg, true);
                    }
                }).catch(function () {
                    if (btn) { btn.disabled = false; btn.textContent = 'REQUEST'; }
                    showMsg('Network error. Please try again.', true);
                });
        });
    }

    // ── Kick Broadcasting Schedule ────────────────────────────

    function fmtTime(t) {
        if (!t) return '';
        var parts = t.split(':');
        var h = parseInt(parts[0], 10);
        var m = parts[1];
        var ampm = h >= 12 ? 'PM' : 'AM';
        h = h % 12 || 12;
        return h + (m && m !== '00' ? ':' + m : '') + ' ' + ampm;
    }

    function fmtDay(d) {
        if (!d) return '';
        var p = d.split('-');
        var dt = new Date(parseInt(p[0], 10), parseInt(p[1], 10) - 1, parseInt(p[2], 10));
        return dt.toLocaleDateString('en-US', {weekday: 'short', month: 'short', day: 'numeric'});
    }

    function renderSchedule(schedule) {
        var list = document.getElementById('kick-schedule-list');
        if (!list) return;
        list.innerHTML = '';
        if (!schedule || schedule.length === 0) {
            list.innerHTML = '<p class="live-copy-muted">No schedule entries yet.</p>';
            return;
        }
        schedule.forEach(function(item, i) {
            var label = fmtDay(item.day);
            if (item.start_time) label += ', ' + fmtTime(item.start_time);
            if (item.end_time)   label += ' - ' + fmtTime(item.end_time);
            var div = document.createElement('div');
            div.className = 'live-schedule-item';
            div.setAttribute('data-index', i);
            div.innerHTML = '<span>' + label + '</span>' +
                '<button type="button" class="live-inline-btn kick-cancel-btn" data-index="' + i + '">cancel</button>';
            list.appendChild(div);
        });
    }

    function showScheduleMsg(text, isError) {
        var el = document.getElementById('kick-schedule-msg');
        if (!el) return;
        el.textContent   = text;
        el.className     = 'live-request-msg live-request-msg--' + (isError ? 'error' : 'success');
        el.style.display = '';
        setTimeout(function() { el.style.display = 'none'; }, 4000);
    }

    var ksForm = document.getElementById('kick-schedule-form');
    if (ksForm) {
        ksForm.addEventListener('submit', function(e) {
            e.preventDefault();
            var btn = document.getElementById('kick-schedule-btn');
            if (btn) { btn.disabled = true; btn.textContent = 'SAVING...'; }
            var fd = new FormData(ksForm);
            fd.append('action', 'add_kick_schedule');
            fd.append('nonce', _scheduleNonce);
            fetch(_liveAjaxUrl, {method: 'POST', body: fd})
                .then(function(r) { return r.json(); })
                .then(function(res) {
                    if (btn) { btn.disabled = false; btn.textContent = 'SUBMIT'; }
                    if (res.success) {
                        renderSchedule(res.data.schedule);
                        ksForm.reset();
                        showScheduleMsg('Schedule added.', false);
                    } else {
                        showScheduleMsg((res.data && res.data.message) ? res.data.message : 'Error saving.', true);
                    }
                }).catch(function() {
                    if (btn) { btn.disabled = false; btn.textContent = 'SUBMIT'; }
                    showScheduleMsg('Network error. Please try again.', true);
                });
        });
    }

    var ksList = document.getElementById('kick-schedule-list');
    if (ksList) {
        ksList.addEventListener('click', function(e) {
            var cancelBtn = e.target.closest('.kick-cancel-btn');
            if (!cancelBtn) return;
            var idx = parseInt(cancelBtn.getAttribute('data-index'), 10);
            cancelBtn.disabled = true;
            var fd = new FormData();
            fd.append('action', 'delete_kick_schedule');
            fd.append('nonce', _scheduleNonce);
            fd.append('index', idx);
            fetch(_liveAjaxUrl, {method: 'POST', body: fd})
                .then(function(r) { return r.json(); })
                .then(function(res) {
                    if (res.success) {
                        renderSchedule(res.data.schedule);
                    } else {
                        cancelBtn.disabled = false;
                        showScheduleMsg((res.data && res.data.message) ? res.data.message : 'Error deleting.', true);
                    }
                }).catch(function() {
                    cancelBtn.disabled = false;
                    showScheduleMsg('Network error. Please try again.', true);
                });
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
        laSetSel('la-edit-oppemail',   d.oppemail);
        laSetSel('la-edit-bkoppemail', d.bkoppemail);
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
            fd.append('la_opponent_name',        document.getElementById('la-edit-opp').value);
            fd.append('la_backup_opponent_name', document.getElementById('la-edit-bkopp').value);
            fd.append('la_opponent_email',       document.getElementById('la-edit-oppemail').value);
            fd.append('la_backup_opponent_email', document.getElementById('la-edit-bkoppemail').value);
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
