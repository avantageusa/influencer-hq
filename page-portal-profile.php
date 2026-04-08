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
                        [ 'key' => 'name',     'label' => 'Name',                   'value' => $display_name,  'type' => 'text'   ],
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
    </main><!-- #main -->

<style>
/* ── Settings page layout ─────────────────────────────── */
.sett-wrap   { max-width: 1024px; padding-left: 18px; padding-right: 18px; }
.sett-content{ padding-bottom: 80px; }

/* Game Portal URL form */
.hq-game-url-input {
    background: transparent;
    border: none;
    border-bottom: 1px solid #b8972f;
    color: #fff;
    font-size: 16px;
    width: 100%;
    outline: none;
    padding: 2px 4px;
}
.hq-game-url-input::placeholder { color: #555; font-style: italic; }
.hq-game-url-save-btn {
    background: none;
    border: 1px solid #b8972f;
    color: #b8972f;
    font-size: 13px;
    padding: 3px 10px;
    border-radius: 3px;
    cursor: pointer;
    flex-shrink: 0;
    margin-left: 8px;
}
.hq-game-url-save-btn:hover { background: rgba(184,151,47,.15); }

/* Separators */
.sett-sep {
    height: 1px;
    margin: 6px 0;
    background: radial-gradient(ellipse 80% 100% at 50% 50%, rgba(184,151,47,.8) 0%, rgba(184,151,47,0) 100%);
}

/* Header */
.sett-header { display:flex; align-items:center; gap:12px; padding:10px 0 6px; }
.sett-header-icon { width:44px; height:44px; object-fit:contain; }
.sett-title {
    font-family: 'Cinzel', serif;
    font-size: 22px; font-weight:700; color:#fff;
    margin:0; letter-spacing:.05em;
}

/* Identity */
.sett-identity { display:flex; align-items:center; gap:14px; padding:14px 0 12px; }
.sett-avatar-ring {
    width:66px; height:66px; border-radius:50%;
    border:2px solid #fff; overflow:hidden; flex-shrink:0;
}
.sett-avatar-img { width:100%; height:100%; object-fit:cover; display:block; }
.sett-identity-body { flex:1; display:flex; flex-direction:column; gap:2px; }
.sett-display-name { font-size:16px; font-weight:700; color:#fff; }
.sett-user-handle  { font-size:16px; color:#616161; }
.sett-social-row   { display:flex; align-items:center; gap:7px; margin-top:4px; }
.sett-soc-icon     { width:11px; height:11px; object-fit:contain; opacity:.9; }
.sett-identity-right { display:flex; flex-direction:column; align-items:center; gap:4px; }
.sett-lang         { font-size:16px; font-weight:600; color:#fff; text-transform:uppercase; }
.sett-country-icon { width:24px; height:24px; object-fit:contain; }

/* Section headers */
.sett-section-head {
    display:flex; align-items:baseline; flex-wrap:wrap; gap:5px;
    margin:14px 0 4px; padding-left:10px; padding-right:10px;
}
.sett-section-head > .sett-section-title:first-child {
    min-width:280px; flex-shrink:0;
}
.sett-section-title {
    font-size:16px; font-weight:700; color:#fff;
    text-transform:uppercase; letter-spacing:.06em;
}
.sett-section-sub { font-size:16px; }
.sett-hint  { flex:1; display:flex; justify-content:end; align-items:center; gap:6px; }
.sett-hint-text { font-size:13px; color:#919191; }
.sett-info-icon {
    display:inline-flex; align-items:center; justify-content:center;
    width:18px; height:18px; border-radius:50%;
    background:#D4AF37; color:#000; font-size:11px; font-weight:700;
    font-style:italic; cursor:default; position:relative; line-height:1;
    font-family:Georgia, 'Times New Roman', serif; flex-shrink:0;
    user-select:none;
}
.sett-info-icon .sett-info-tooltip {
    display:none; position:absolute; bottom:calc(100% + 8px); right:0;
    transform:none;
    background:#1a1a1a; color:#e5e5e5;
    font-size:13px; font-style:normal; font-weight:400; font-family:inherit;
    padding:7px 11px; border-radius:4px;
    border:1px solid #b8972f; z-index:200;
    width:260px; white-space:normal; text-align:center;
    pointer-events:none; line-height:1.45;
    box-shadow:0 4px 12px rgba(0,0,0,.5);
}
.sett-info-icon:hover .sett-info-tooltip { display:block; }
.sett-section-head--comm { justify-content:space-between; }
.sett-arrow { font-size:16px; color:#fff; }
/* Card */
.sett-card {
    background:#000;
    border:1px solid #b8972f;
    border-radius:5px;
    margin-bottom:14px;
    overflow:hidden;
}

/* Row */
.sett-row {
    display:flex; align-items:center; justify-content:space-between;
    min-height:26px; padding:3px 10px;
    border-bottom:1px solid rgba(184,151,47,.2);
    gap:6px;
}
.sett-row:last-of-type { border-bottom:none; }

.sett-row-lbl {
    font-size:16px; color:#e5e5e5;
    flex:1; min-width:120px; max-width:280px;
}
.sett-row-val {
    width:33%; flex-shrink:0; display:flex; align-items:center; justify-content:flex-end;
}

/* Editable spans */
.sett-editable {
    font-size:16px; color:#fff; text-align:right;
    cursor:pointer; padding:1px 3px; border-radius:2px;
    display:inline-block; min-width:60px; min-height:20px;
}
.sett-editable:hover { background:rgba(184,151,47,.12); }
.sett-editable:empty::before {
    content:'tap to add'; color:#555; font-style:italic;
}
.sett-editable--handle {
    border:1px dashed transparent; min-width:90px;
}
.sett-editable--handle:hover { border-color:rgba(255,255,255,.25); }
.sett-editable--handle:empty::before {
    content:'tap to add'; color:#555; font-style:italic;
}
.sett-change-photo {
    font-size:16px; color:#919191; text-decoration:underline;
    background:none; border:none; cursor:pointer; padding:0;
}



/* Quote */
.sett-quote {
    font-size:16px; color:#fff; font-style:italic;
    margin:2px 0 14px; line-height:1.55;
}

/* Add more */
.sett-add-more-row {
    padding:5px 10px; text-align:right;
    border-top:1px solid rgba(184,151,47,.15);
}
.sett-add-more-btn {
    font-size:16px; color:#919191; text-decoration:underline;
    background:none; border:none; cursor:pointer; padding:0;
}
</style>

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
                save('save_settings_field', { group: el.dataset.group, field: el.dataset.field, value: v });
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

    function save(action, params){
        var fd = new FormData();
        fd.append('action', action);
        fd.append('nonce',  _nonce);
        Object.keys(params).forEach(function(k){ fd.append(k, params[k]); });
        fetch(_ajax, { method:'POST', body:fd }).catch(function(){});
    }
})();

</script>

<?php
get_template_part( 'template-parts/portal-scripts' );
get_footer();