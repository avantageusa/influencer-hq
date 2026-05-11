<?php
/**
 * Template Name: Portal Profile
 * Description: A custom template for displaying user profile and settings.
 *
 * @package Avantage_Baccarat
 */

// Handle Game Portal URL form submission
if ( isset( $_POST['hq_game_url_submit'] ) && is_user_logged_in() ) {
    check_admin_referer( 'hq_game_url_save' );
    $url = isset( $_POST['hq_game_url'] ) ? esc_url_raw( wp_unslash( $_POST['hq_game_url'] ) ) : '';
    update_user_meta( get_current_user_id(), 'hq_game_url', $url );
    wp_safe_redirect( add_query_arg( 'hq_saved', '1', get_permalink() ) );
    exit;
}

get_header();

// Load styles before content to prevent FOUC
get_template_part( 'template-parts/portal-styles' );

$user            = wp_get_current_user();
$display_name    = $user->display_name ?: $user->user_login;
$first_name      = get_user_meta( $user->ID, 'first_name', true ) ?: $user->first_name;
$last_name       = get_user_meta( $user->ID, 'last_name',  true ) ?: $user->last_name;
$user_email      = $user->user_email;
$user_handle     = get_user_meta( $user->ID, '_ihq_handle',    true ) ?: ( '@' . $user->user_login );
$user_country    = get_user_meta( $user->ID, '_ihq_country',   true );
$user_city       = get_user_meta( $user->ID, '_ihq_city',      true );
$user_timezone   = get_user_meta( $user->ID, '_ihq_timezone',  true );
$user_avatar     = get_user_meta( $user->ID, '_ihq_avatar_url', true );
if ( ! $user_avatar ) {
    $user_avatar = get_avatar_url( $user->ID, [ 'size' => 100 ] );
}

$social_handles  = get_user_meta( $user->ID, '_ihq_social_handles',  true );
if ( ! is_array( $social_handles ) )  { $social_handles  = []; }

$comm_prefs      = get_user_meta( $user->ID, '_ihq_comm_prefs',      true );
if ( ! is_array( $comm_prefs ) )      { $comm_prefs      = []; }

$celebrity_selections = [
    'movie_stars'   => get_user_meta( $user->ID, '_ihq_cel_movie_stars',   true ) ?: '',
    'music_artists' => get_user_meta( $user->ID, '_ihq_cel_music_artists', true ) ?: '',
    'sports_icons'  => get_user_meta( $user->ID, '_ihq_cel_sports_icons',  true ) ?: '',
];

$intl_league_team = get_user_meta( $user->ID, '_ihq_intl_league_team', true ) ?: '';

$contact_platforms = ['Email','KakaoTalk','KICK','Line','TikTok','Twitch','WeChat','WhatsApp'];

$_settings_nonce = wp_create_nonce( 'settings_save_nonce' );
?>

    <main id="primary" class="site-main">
        
        <?php get_template_part( 'template-parts/portal-header' ); ?>
        
        <div class="container py-2 sett-wrap" id="portal-content">

            <div class="sett-content">

                <!-- PROFILE Header -->
                <header class="sett-header">
                    <img src="<?php echo esc_url( get_template_directory_uri() ); ?>/images/profile.png" alt="" class="sett-header-icon">
                    <h1 class="sett-title">PROFILE</h1>
                </header>

                <div class="sett-sep"></div>

                <!-- GAME PORTAL URL -->
                <?php
                $hq_current_url = get_user_meta( $user->ID, 'hq_game_url', true );
                $hq_default_url = 'https://qc-game-portal-client-tf-b2c.dev.ae.games/av-baccarat';
                ?>
                <form method="post" action="" class="hq-game-url-form">
                    <?php wp_nonce_field( 'hq_game_url_save' ); ?>
                    <div class="sett-card" style="margin-bottom:14px;">
                        <div class="sett-row">
                            <label for="hq_game_url" class="sett-row-lbl">Game Portal URL</label>
                            <div class="sett-row-val" style="width:auto;flex:1;">
                                <input
                                    type="url"
                                    id="hq_game_url"
                                    name="hq_game_url"
                                    value="<?php echo esc_attr( $hq_current_url ); ?>"
                                    placeholder="<?php echo esc_attr( $hq_default_url ); ?>"
                                    class="hq-game-url-input"
                                >
                            </div>
                            <button type="submit" name="hq_game_url_submit" class="hq-game-url-save-btn">Save</button>
                        </div>
                        <div class="sett-row" style="border-bottom:none;">
                            <span class="sett-row-lbl" style="color:#616161;font-size:13px;">Default</span>
                            <span style="font-size:13px;color:#616161;flex:1;text-align:right;word-break:break-all;"><?php echo esc_html( $hq_default_url ); ?></span>
                        </div>
                    </div>
                    <?php if ( isset( $_GET['hq_saved'] ) ) : ?>
                    <p style="color:#7CCA8A;font-size:13px;margin:-8px 0 10px;">&#10003; Saved.</p>
                    <?php endif; ?>
                </form>

                <!-- Identity Badge -->
                <div class="sett-identity">
                    <div class="sett-avatar-ring">
                        <img src="<?php echo esc_url( $user_avatar ); ?>" alt="<?php echo esc_attr( $display_name ); ?>" class="sett-avatar-img" id="settAvatarImg">
                    </div>
                    <div class="sett-identity-body">
                        <div class="sett-display-name"><?php echo esc_html( $display_name ); ?></div>
                        <div class="sett-user-handle"><?php echo esc_html( $user_handle ); ?></div>
                        <div class="sett-social-row">
                            <img src="<?php echo esc_url( get_template_directory_uri() ); ?>/images/youtube.png" alt="YouTube" class="sett-soc-icon">
                            <img src="<?php echo esc_url( get_template_directory_uri() ); ?>/images/x.png" alt="X" class="sett-soc-icon">
                            <img src="<?php echo esc_url( get_template_directory_uri() ); ?>/images/tiktok.png" alt="TikTok" class="sett-soc-icon">
                            <img src="<?php echo esc_url( get_template_directory_uri() ); ?>/images/kick.png" alt="Kick" class="sett-soc-icon">
                        </div>
                    </div>
                    <div class="sett-identity-right">
                        <span class="sett-lang">EN</span>
                        <img src="http://localhost:3845/assets/2bc209382386079bf713baaee5d9f59d18a81c36.png" alt="Country" class="sett-country-icon">
                    </div>
                </div>

                <!-- ACCOUNT INFORMATION -->
                <div class="sett-section-head">
                    <span class="sett-section-title">ACCOUNT &nbsp;INFORMATION</span>
                    <span class="sett-hint"><span class="sett-hint-text">Click to enter / edit</span><span class="sett-info-icon">i<span class="sett-info-tooltip">Entered information is saved by pressing Enter key, or by clicking anywhere outside the input field.</span></span></span>
                </div>

                <div class="sett-card">
                    <?php
                    $acct_rows = [
                        [ 'key' => 'first_name', 'label' => 'First Name',             'value' => $first_name,    'type' => 'text'   ],
                        [ 'key' => 'last_name',  'label' => 'Last Name',              'value' => $last_name,     'type' => 'text'   ],
                        [ 'key' => 'email',    'label' => 'Email',                  'value' => $user_email,    'type' => 'email'  ],
                        [ 'key' => 'country',  'label' => 'Country',                'value' => $user_country,  'type' => 'text'   ],
                        [ 'key' => 'city',     'label' => 'City',                   'value' => $user_city,     'type' => 'text'   ],
                        [ 'key' => 'timezone', 'label' => 'Time Zone',              'value' => $user_timezone, 'type' => 'timezone' ],
                        [ 'key' => 'handle',   'label' => 'InfluencerHQ Handle',    'value' => $user_handle,   'type' => 'text'   ],
                        [ 'key' => 'avatar',   'label' => 'Profile Photo or Avatar','value' => '',             'type' => 'avatar' ],
                    ];
                    foreach ( $acct_rows as $row ) :
                    ?>
                    <div class="sett-row">
                        <div class="sett-row-lbl"><?php echo esc_html( $row['label'] ); ?></div>
                        <div class="sett-row-val">
                            <?php if ( $row['type'] === 'avatar' ) : ?>
                                <button type="button" class="sett-change-photo" id="sett-avatar-btn">Add or Change Photo</button>
                            <?php elseif ( $row['type'] === 'timezone' ) : ?>
                                <?php
                                $now = new DateTime('now', new DateTimeZone('UTC'));
                                $tz_list = [];
                                foreach ( DateTimeZone::listIdentifiers( DateTimeZone::ALL ) as $tz_id ) {
                                    $dtz    = new DateTimeZone( $tz_id );
                                    $offset = $dtz->getOffset( $now );
                                    $hours  = (int) floor( abs( $offset ) / 3600 );
                                    $mins   = (int) ( ( abs( $offset ) % 3600 ) / 60 );
                                    $sign   = $offset >= 0 ? '+' : '-';
                                    $parts  = explode( '/', $tz_id );
                                    $city   = str_replace( '_', ' ', end( $parts ) );
                                    $region = count( $parts ) > 1 ? str_replace( '_', ' ', $parts[0] ) : '';
                                    $city_label = $region ? $city . ' (' . $region . ')' : $city;
                                    $label  = sprintf( '%s — UTC%s%02d:%02d', $city_label, $sign, $hours, $mins );
                                    $tz_list[] = [ 'id' => $tz_id, 'offset' => $offset, 'label' => $label, 'city' => $city ];
                                }
                                usort( $tz_list, function( $a, $b ) { return strcasecmp( $a['city'], $b['city'] ); } );
                                ?>
                                <select class="sett-timezone-select" data-group="account" data-field="timezone" data-saved="<?php echo esc_attr( $row['value'] ); ?>">
                                    <option value="">-- Detecting... --</option>
                                    <?php foreach ( $tz_list as $tz_item ) : ?>
                                    <option value="<?php echo esc_attr( $tz_item['id'] ); ?>"<?php selected( $row['value'], $tz_item['id'] ); ?>><?php echo esc_html( $tz_item['label'] ); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            <?php else : ?>
                                <span class="sett-editable" data-group="account" data-field="<?php echo esc_attr( $row['key'] ); ?>"><?php echo esc_html( $row['value'] ); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- CELEBRITY FOLLOWERS LEAGUES -->
                <div class="sett-section-head sett-section-head--comm celeb-leagues-head" id="celebLeaguesHead" style="cursor:pointer;">
                    <span class="sett-section-title">CELEBRITY &nbsp;FOLLOWERS &nbsp;LEAGUES</span>
                    <span class="sett-arrow" id="celebLeaguesArrow">▼</span>
                </div>

                <div id="celebLeaguesBody">
                    <p class="sett-quote" style="margin-top:0;"><em>Choose your favorite celebrity in all three categories. Influencer Headquarters will be promoting you as a Team Captain with all new game participants.</em></p>

                    <?php
                    $celeb_lists = [
                        'movie_stars'   => ['Leonardo DiCaprio','Fan Bingbing','Scarlett Johansson','Tony Leung','Anya Wong','Maggie Cheung','Iko Uwais','Tom Cruise','Hyun Bin','Chow Yun-fat','Zhang Ziyi','Song Hye-kyo','Gong Yoo','Michelle Yeoh','Donnie Yen','Vicky Chen','Bruce Lee','Gong Li','Liu Yifei','Jackie Chan'],
                        'music_artists' => ['Jolin Tsai','Namewee','IU (Lee Ji-eun)','BTS','Ariana Grande','Bruno Mars','PSY','Blackpink','Twice','Tomorrow X Together','Billie Eilish','Jay Chou','Lisa (BLACKPINK)','Zhou Shen','G-Dragon','Lady Gaga','Taylor Swift','Deng Liqi','Justin Bieber','Ed Sheeran'],
                        'sports_icons'  => ['Son Heung-min','Lionel Messi','Roger Federer','Naomi Osaka','Ding Junhui','Jeremy Lin','Cristiano Ronaldo','Stephen Curry','Michael Jordan','Novak Djokovic','Kento Momota','Sachin Tendulkar','Rafael Nadal','Virat Kohli','Manny Pacquiao','Shohei Ohtani','Yao Ming','LeBron James','Kylian Mbappé','Lee Chong Wei'],
                    ];
                    $celeb_labels = ['movie_stars' => 'Movie Stars', 'music_artists' => 'Music Artists', 'sports_icons' => 'Sports Icons'];
                    ?>

                    <div class="sett-card">
                        <div class="celeb-grid-layout">
                            <?php foreach ( $celeb_labels as $cat => $label ) :
                                $saved = $celebrity_selections[ $cat ] ?? '';
                            ?>
                            <div class="celeb-col">
                                <span class="celeb-col-label"><?php echo esc_html( $label ); ?></span>
                                <select class="celeb-select" data-category="<?php echo esc_attr( $cat ); ?>">
                                    <option value="">Open</option>
                                    <?php foreach ( $celeb_lists[ $cat ] as $name ) : ?>
                                    <option value="<?php echo esc_attr( $name ); ?>"<?php selected( $saved, $name ); ?>><?php echo esc_html( $name ); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- INTERNATIONAL LEAGUE TEAM -->
                <div class="sett-section-head sett-section-head--comm celeb-leagues-head" id="intlLeagueHead" style="cursor:pointer;">
                    <span class="sett-section-title">CHOOSE YOUR INTERNATIONAL LEAGUE TEAM</span>
                    <span class="sett-arrow" id="intlLeagueArrow">▼</span>
                </div>

                <div id="intlLeagueBody">
                    <div class="sett-card">
                        <div class="celeb-grid-layout" style="grid-template-columns:1fr;">
                            <div class="celeb-col">
                                <span class="celeb-col-label">Country / Region</span>
                                <select class="celeb-select" id="intlLeagueSelect">
                                    <option value="">Open</option>
                                    <?php
                                    $intl_league_regions = ['South Korea','Europe','Malaysia','Thailand','Africa','Singapore','Asia','India','China','Hong Kong','Philippines','Taiwan','United States','Canada','Macao','Pakistan','South America','Japan','Australia','South Africa'];
                                    foreach ( $intl_league_regions as $region ) :
                                    ?>
                                    <option value="<?php echo esc_attr( $region ); ?>"<?php selected( $intl_league_team, $region ); ?>><?php echo esc_html( $region ); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quote -->
                <p class="sett-quote"><em>We believe visibility powers competition. The more visible you choose to be, the farther your leadership can travel.</em></p>

                <!-- USERNAME OR CONTACT -->
                <div class="sett-section-head sett-section-head--comm" id="contactHead" style="cursor:pointer;">
                    <span class="sett-section-title">USERNAME OR CONTACT</span>
                    <span class="sett-arrow" id="contactArrow">▼</span>
                </div>

                <div id="contactBody">
                    <div class="sett-card contact-card">
                        <?php
                        foreach ( $contact_platforms as $cp ) :
                            $ckey  = strtolower( $cp );
                            $cval  = $social_handles[ $ckey ] ?? '';
                            $ccomm = ! empty( $comm_prefs[ $ckey ] );
                        ?>
                        <div class="contact-row" data-key="<?php echo esc_attr( $ckey ); ?>">
                            <div class="contact-row-main">
                                <span class="contact-row-lbl"><?php echo esc_html( $cp ); ?></span>
                                <span class="contact-row-addval<?php echo $cval ? ' contact-row-addval--filled' : ''; ?>"><?php echo $cval ? esc_html( $cval ) : 'add'; ?></span>
                            </div>
                            <div class="contact-row-expand" style="display:none;">
                                <input type="text" class="contact-input" value="<?php echo esc_attr( $cval ); ?>" placeholder="click to enter / edit">
                                <div class="contact-toggles">
                                    <label class="contact-toggle<?php echo $ccomm ? ' contact-toggle--on' : ''; ?>">
                                        <input type="checkbox" class="contact-toggle-cb" data-type="comm"<?php checked( $ccomm ); ?>>
                                        <span class="contact-toggle-label">Communicate with Me</span>
                                        <span class="contact-toggle-track"></span>
                                    </label>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

            </div><!-- .sett-content -->
            
        </div>
        
        <!-- Fixed Footer Links -->
        <?php get_template_part( 'template-parts/portal-footer' ); ?>

        <!-- API Debug Panel -->
        <div id="ihq-api-debug" style="margin:24px 16px;background:#111;border:1px solid #444;border-radius:8px;padding:16px;font-family:monospace;font-size:12px;color:#ccc;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
                <strong style="color:#b8972f;">API Debug</strong>
                <button onclick="document.getElementById('ihq-api-debug-log').innerHTML='';" style="background:none;border:1px solid #555;color:#aaa;padding:2px 8px;border-radius:4px;cursor:pointer;font-size:11px;">Clear</button>
            </div>
            <div id="ihq-api-debug-log" style="max-height:400px;overflow-y:auto;"></div>
        </div>

    </main><!-- #main -->


<script>
(function(){
    var _ajax  = <?php echo wp_json_encode( admin_url('admin-ajax.php') ); ?>;
    var _nonce = <?php echo wp_json_encode( $_settings_nonce ); ?>;

    /* ── Inline edit ───────────────────────────────────── */
    document.querySelectorAll('.sett-editable').forEach(function(el){
        el.addEventListener('click', function(){
            if (el.querySelector('input,textarea')) return;
            var val   = el.textContent.trim();
            var input = document.createElement('input');
            input.type  = 'text';
            input.value = val;
            input.style.cssText = 'background:transparent;border:none;border-bottom:1px solid #b8972f;color:#fff;font-size:16px;text-align:right;width:100%;outline:none;';
            el.textContent = '';
            el.appendChild(input);
            input.focus();
            function commit(){
                var v = input.value.trim();
                el.textContent = v;
                var field = el.dataset.field;
                if ( field === 'first_name' || field === 'last_name' ) {
                    saveFullname();
                } else {
                    save('save_settings_field', { group: el.dataset.group, field: field, value: v });
                }
            }
            input.addEventListener('blur', commit);
            input.addEventListener('keydown', function(e){ if(e.key==='Enter'){ e.preventDefault(); commit(); }});
        });
    });

    /* ── Username or Contact ─────────────────────────── */
    var contactHead  = document.getElementById('contactHead');
    var contactBody  = document.getElementById('contactBody');
    var contactArrow = document.getElementById('contactArrow');
    if (contactHead) {
        contactHead.addEventListener('click', function(){
            var hidden = contactBody.style.display === 'none';
            contactBody.style.display = hidden ? '' : 'none';
            contactArrow.textContent  = hidden ? '▼' : '▲';
        });
    }

    document.querySelectorAll('.contact-row').forEach(function(row){
        var key    = row.dataset.key;
        var main   = row.querySelector('.contact-row-main');
        var expand = row.querySelector('.contact-row-expand');
        var input  = row.querySelector('.contact-input');
        var valEl  = row.querySelector('.contact-row-addval');

        main.addEventListener('click', function(){
            var isOpen = expand.style.display !== 'none';
            document.querySelectorAll('.contact-row-expand').forEach(function(e){ e.style.display = 'none'; });
            if (!isOpen) {
                expand.style.display = '';
                input.focus();
            }
        });

        function commitInput(){
            var v = input.value.trim();
            valEl.textContent = v || 'add';
            v ? valEl.classList.add('contact-row-addval--filled') : valEl.classList.remove('contact-row-addval--filled');
            save('save_settings_field', { group: 'social', field: key, value: v });
        }
        input.addEventListener('blur', commitInput);
        input.addEventListener('keydown', function(e){ if(e.key==='Enter'){ e.preventDefault(); commitInput(); } });
        expand.addEventListener('mousedown', function(e){
            if (e.target !== input) e.preventDefault();
        });

        var commCb = row.querySelector('.contact-toggle-cb[data-type="comm"]');
        if (commCb) {
            commCb.addEventListener('change', function(){
                var lbl = commCb.closest('.contact-toggle');
                commCb.checked ? lbl.classList.add('contact-toggle--on') : lbl.classList.remove('contact-toggle--on');
                save('save_settings_toggle', { group: 'comm', key: key, value: commCb.checked ? 1 : 0 });
            });
        }
    });

    /* ── Avatar upload ─────────────────────────────────── */
    var avatarBtn = document.getElementById('sett-avatar-btn');
    if (avatarBtn) {
        var fileInput = document.createElement('input');
        fileInput.type   = 'file';
        fileInput.accept = 'image/*';
        fileInput.style.display = 'none';
        document.body.appendChild(fileInput);
        avatarBtn.addEventListener('click', function(){ fileInput.click(); });
        fileInput.addEventListener('change', function(){
            var file = fileInput.files[0];
            if (!file) return;
            var fd = new FormData();
            fd.append('action', 'save_settings_avatar');
            fd.append('nonce',  _nonce);
            fd.append('avatar', file);
            fetch(_ajax, { method:'POST', body:fd })
                .then(function(r){ return r.json(); })
                .then(function(res){
                    if (res.success && res.data.url) {
                        var img = document.getElementById('settAvatarImg');
                        if (img) img.src = res.data.url;
                    }
                }).catch(function(){});
        });
    }

    /* ── API Debug log ────────────────────────────────── */
    function dbg(label, sent, received) {
        var log = document.getElementById('ihq-api-debug-log');
        if (!log) return;
        var ts = new Date().toLocaleTimeString();
        var color = (received && received.success) ? '#53FC18' : '#ff6b6b';
        var html = '<div style="border-bottom:1px solid #333;padding:8px 0;">';
        html += '<div style="color:' + color + ';margin-bottom:4px;">▶ ' + label + ' <span style="color:#666;font-size:11px;">' + ts + '</span></div>';
        if (sent !== null) {
            html += '<div style="color:#aaa;margin-bottom:2px;">SENT:</div>';
            html += '<pre style="color:#e0c97f;white-space:pre-wrap;word-break:break-all;margin:0 0 6px 0;">' + JSON.stringify(sent, null, 2) + '</pre>';
        }
        html += '<div style="color:#aaa;margin-bottom:2px;">RECEIVED:</div>';
        html += '<pre style="color:#ccc;white-space:pre-wrap;word-break:break-all;margin:0;">' + JSON.stringify(received, null, 2) + '</pre>';
        html += '</div>';
        log.innerHTML = html + log.innerHTML;
    }

    function saveFullname() {
        var fn = document.querySelector('.sett-editable[data-field="first_name"]');
        var ln = document.querySelector('.sett-editable[data-field="last_name"]');
        if (!fn || !ln) return;
        var payload = {
            firstName: fn.textContent.trim(),
            lastName:  ln.textContent.trim(),
        };
        var fd = new FormData();
        fd.append('action', 'ihq_update_fullname');
        fd.append('nonce',  _nonce);
        fd.append('firstName', payload.firstName);
        fd.append('lastName',  payload.lastName);
        fetch(_ajax, { method:'POST', body:fd })
            .then(function(r){ return r.json(); })
            .then(function(res){ dbg('PATCH /account/players/fullname', payload, res); })
            .catch(function(e){ dbg('PATCH /account/players/fullname', payload, {error: String(e)}); });
    }

    function save(action, params){
        var fd = new FormData();
        fd.append('action', action);
        fd.append('nonce',  _nonce);
        Object.keys(params).forEach(function(k){ fd.append(k, params[k]); });
        fetch(_ajax, { method:'POST', body:fd }).catch(function(){});
    }

    // Read first/last name from API on load
    var getPayload = { action: 'ihq_get_player_me', nonce: _nonce };
    fetch(_ajax, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=ihq_get_player_me&nonce=' + encodeURIComponent(_nonce),
    })
    .then(function(r){ return r.json(); })
    .then(function(res) {
        dbg('GET /account/players/me', getPayload, res);
        if (res.success && res.data) {
            var d = res.data;
            var fn = document.querySelector('.sett-editable[data-field="first_name"]');
            var ln = document.querySelector('.sett-editable[data-field="last_name"]');
            if (fn && d.firstName !== undefined) fn.textContent = d.firstName;
            if (ln && d.lastName  !== undefined) ln.textContent = d.lastName;
        }
    }).catch(function(e){ dbg('GET /account/players/me', getPayload, {error: String(e)}); });

    /* ── Celebrity Followers Leagues ──────────────────── */
    var celebHead  = document.getElementById('celebLeaguesHead');
    var celebBody  = document.getElementById('celebLeaguesBody');
    var celebArrow = document.getElementById('celebLeaguesArrow');
    if (celebHead) {
        celebHead.addEventListener('click', function(){
            var hidden = celebBody.style.display === 'none';
            celebBody.style.display = hidden ? '' : 'none';
            celebArrow.textContent  = hidden ? '▼' : '▲';
        });
    }

    document.querySelectorAll('.celeb-select').forEach(function(sel){
        sel.addEventListener('change', function(){
            save('save_settings_field', { group: 'account', field: 'celebrity_' + sel.dataset.category, value: sel.value });
        });
    });

    /* ── International League Team ────────────────────── */
    var intlLeagueHead  = document.getElementById('intlLeagueHead');
    var intlLeagueBody  = document.getElementById('intlLeagueBody');
    var intlLeagueArrow = document.getElementById('intlLeagueArrow');
    if (intlLeagueHead) {
        intlLeagueHead.addEventListener('click', function(){
            var hidden = intlLeagueBody.style.display === 'none';
            intlLeagueBody.style.display = hidden ? '' : 'none';
            intlLeagueArrow.textContent  = hidden ? '▼' : '▲';
        });
    }

    var intlLeagueSelect = document.getElementById('intlLeagueSelect');
    if (intlLeagueSelect) {
        intlLeagueSelect.addEventListener('change', function(){
            save('save_settings_field', { group: 'account', field: 'intl_league_team', value: intlLeagueSelect.value });
        });
    }

    /* ── Timezone dropdown ────────────────────────────── */
    var tzSelect = document.querySelector('.sett-timezone-select');
    if (tzSelect) {
        var savedTz = tzSelect.dataset.saved || '';

        if (savedTz) {
            // Use the value stored in user meta
            console.log('[Timezone] Loaded from user meta:', savedTz);
            tzSelect.value = savedTz;
        } else {
            // No saved value — detect from browser and write to DB
            try {
                var detected = Intl.DateTimeFormat().resolvedOptions().timeZone;
                var matchOpt = Array.from(tzSelect.options).find(function(o){ return o.value === detected; });
                if (matchOpt) {
                    console.log('[Timezone] None saved in DB. Browser detected:', detected, '— saving now.');
                    tzSelect.value = detected;
                    save('save_settings_field', { group: 'account', field: 'timezone', value: detected });
                } else {
                    console.warn('[Timezone] Browser detected "' + detected + '" but no matching option found in list.');
                }
            } catch(e) {
                console.error('[Timezone] Detection failed:', e);
            }
        }

        tzSelect.addEventListener('change', function(){
            console.log('[Timezone] User changed to:', tzSelect.value);
            save('save_settings_field', { group: 'account', field: 'timezone', value: tzSelect.value });
        });
    }

})();

</script>

<?php
get_template_part( 'template-parts/portal-scripts' );
get_footer();