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
$social_visible  = get_user_meta( $user->ID, '_ihq_social_visible',  true );
if ( ! is_array( $social_visible ) )  { $social_visible  = []; }
$account_visible = get_user_meta( $user->ID, '_ihq_account_visible', true );
if ( ! is_array( $account_visible ) ) { $account_visible = []; }
$comm_prefs      = get_user_meta( $user->ID, '_ihq_comm_prefs',      true );
if ( ! is_array( $comm_prefs ) )      { $comm_prefs      = []; }

$social_platforms = ['Kick','YouTube','X','TikTok','Discord','WeChat','LINE','KakaoTalk','WhatsApp','Instagram','LinkedIn','Facebook','Snapchat','Reddit','Viber','Twitch'];
$comm_platforms   = ['Email','Kick','YouTube','X','TikTok','Discord','WeChat','LINE','KakaoTalk','WhatsApp','Instagram','LinkedIn','Facebook','Snapchat','Reddit','Viber','Twitch'];

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
                        [ 'key' => 'timezone', 'label' => 'Time Zone',              'value' => $user_timezone, 'type' => 'text'   ],
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
                            <?php else : ?>
                                <span class="sett-editable" data-group="account" data-field="<?php echo esc_attr( $row['key'] ); ?>"><?php echo esc_html( $row['value'] ); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Quote -->
                <p class="sett-quote"><em>We believe visibility powers competition. The more visible you choose to be, the farther your leadership can travel.</em></p>

                <!-- SOCIAL MEDIA HANDLES -->
                <div class="sett-section-head">
                    <span class="sett-section-title">SOCIAL &nbsp;MEDIA</span>
                    <span class="sett-section-title sett-section-sub">HANDLES</span>
                    <span class="sett-hint"><span class="sett-hint-text">Click to enter / edit</span><span class="sett-info-icon">i<span class="sett-info-tooltip">Entered information is saved by pressing Enter key, or by clicking anywhere outside the input field.</span></span></span>
                </div>

                <div class="sett-card">
                    <?php foreach ( $social_platforms as $platform ) :
                        $key  = strtolower( $platform );
                        $val  = $social_handles[ $key ] ?? '';
                        $on   = ! empty( $social_visible[ $key ] );
                    ?>
                    <div class="sett-row">
                        <div class="sett-row-lbl"><?php echo esc_html( $platform ); ?></div>
                        <div class="sett-row-val">
                            <span class="sett-editable sett-editable--handle" data-group="social" data-field="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $val ); ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <div class="sett-add-more-row"><button type="button" class="sett-add-more-btn">add more</button></div>
                </div>

                <!-- HOW YOU LIKE US TO COMMUNICATE -->
                <div class="sett-section-head sett-section-head--comm">
                    <span class="sett-section-title">HOW YOU LIKE US TO COMMUNICATE WITH YOU</span>
                    <span class="sett-arrow">▼</span>
                </div>

                <div class="sett-card">
                    <?php foreach ( $comm_platforms as $platform ) :
                        $key = strtolower( $platform );
                        $on  = ! empty( $comm_prefs[ $key ] );
                    ?>
                    <div class="sett-row">
                        <div class="sett-row-lbl"><?php echo esc_html( $platform ); ?></div>
                        <div class="sett-row-val">
                            <?php if ( $platform === 'Email' ) : ?>
                                <span class="sett-editable" data-group="comm" data-field="email"><?php echo esc_html( $user_email ); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <div class="sett-add-more-row"><button type="button" class="sett-add-more-btn">add more</button></div>
                </div>

            </div><!-- .sett-content -->
            
        </div>
        
        <!-- Fixed Footer Links -->
        <div class="footer-links-fixed">
            <a href="#" class="footer-link">Terms</a>
            <span class="footer-separator">|</span>
            <a href="#" class="footer-link">Privacy</a>
        </div>

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

    /* ── Toggles ───────────────────────────────────────── */
    document.querySelectorAll('.sett-toggle-cb').forEach(function(cb){
        cb.addEventListener('change', function(){
            var lbl = cb.closest('.sett-toggle');
            cb.checked ? lbl.classList.add('sett-toggle--on') : lbl.classList.remove('sett-toggle--on');
            save('save_settings_toggle', { group: cb.dataset.group, key: cb.dataset.key, value: cb.checked ? 1 : 0 });
        });
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
})();

</script>

<?php
get_template_part( 'template-parts/portal-scripts' );
get_footer();