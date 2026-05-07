<?php
/**
 * Template Name: Portal Login
 * Description: Login and registration page for influencer portal access.
 *
 * @package Avantage_Baccarat
 */

get_header();
get_template_part( 'template-parts/portal-styles' );
?>

<main id="primary" class="site-main">
    <div class="portal-login-wrap">
        <div class="container" style="max-width: 480px; padding: 40px 20px;">

            <!-- Logo -->
            <div class="text-center mb-4">
                <img src="<?php echo esc_url( get_template_directory_uri() ); ?>/images/logo-hq.png"
                     alt="influencerHQ"
                     style="max-width: 160px;">
            </div>

            <?php if ( is_user_logged_in() ) : ?>

                <?php
                $uid        = get_current_user_id();
                $first_name = get_user_meta( $uid, 'first_name', true );
                $current_user = wp_get_current_user();
                ?>
                <div class="text-center">
                    <p style="color:#e8e8e8; font-size:1.1rem; margin-bottom:24px;">
                        Welcome back, <strong style="color: var(--gold);"><?php echo esc_html( $first_name ?: $current_user->user_login ); ?></strong>
                    </p>
                    <a href="<?php echo esc_url( home_url( '/portal-home/' ) ); ?>"
                       class="portal-btn-primary" style="display:inline-block;">
                        Go to Portal
                    </a>
                    <a href="<?php echo esc_url( wp_logout_url( get_permalink() ) ); ?>"
                       class="portal-btn-outline" style="display:inline-block; margin-top:12px;">
                        Logout
                    </a>
                </div>

            <?php else : ?>

                <!-- Flash messages -->
                <?php
                $auth_error   = isset( $_COOKIE['auth_error'] )   ? esc_html( $_COOKIE['auth_error'] )   : '';
                $auth_success = isset( $_COOKIE['auth_success'] ) ? esc_html( $_COOKIE['auth_success'] ) : '';
                if ( $auth_error ) : ?>
                    <div class="portal-alert portal-alert-error"><?php echo $auth_error; ?></div>
                <?php endif;
                if ( $auth_success ) : ?>
                    <div class="portal-alert portal-alert-success"><?php echo $auth_success; ?></div>
                <?php endif; ?>

                <!-- Tab switcher -->
                <div class="auth-tabs mb-4">
                    <button id="tab-login"
                            onclick="switchPortalTab('login')"
                            type="button"
                            class="auth-tab auth-tab-active">
                        Login
                    </button>
                    <button id="tab-register"
                            onclick="switchPortalTab('register')"
                            type="button"
                            class="auth-tab">
                        Register
                    </button>
                </div>

                <!-- ── LOGIN FORM ────────────────────────────────────────── -->
                <div id="pane-login">
                    <form id="portal-login-form" onsubmit="handlePortalLogin(event)" novalidate>

                        <div class="portal-form-group">
                            <label for="login-email" class="portal-label">Email</label>
                            <input type="email"
                                   id="login-email"
                                   name="email"
                                   class="portal-input"
                                   placeholder="your@email.com"
                                   required autocomplete="email">
                        </div>

                        <div class="portal-form-group">
                            <label for="login-password" class="portal-label">Password</label>
                            <input type="password"
                                   id="login-password"
                                   name="password"
                                   class="portal-input"
                                   placeholder="Your password"
                                   required autocomplete="current-password">
                        </div>

                        <div id="login-error" class="portal-alert portal-alert-error" style="display:none;"></div>

                        <?php if ( function_exists( 'ihq_turnstile_is_configured' ) && ihq_turnstile_is_configured() ) : ?>
                            <div id="portal-login-turnstile" class="mt-3" aria-hidden="true"></div>
                        <?php endif; ?>

                        <button type="submit" id="login-btn" class="portal-btn-primary w-100 mt-3">
                            Login
                        </button>

                        <p class="text-center mt-3" style="color:#aaa; font-size:.9rem;">
                            Don't have an account?
                            <a href="#" onclick="switchPortalTab('register'); return false;"
                               style="color: var(--gold); text-decoration:none;">Register here</a>
                        </p>

                    </form>
                </div>

                <!-- ── REGISTER FORM ─────────────────────────────────────── -->
                <div id="pane-register" style="display:none;">
                    <form id="portal-register-form" onsubmit="handlePortalRegister(event)" novalidate>

                        <div class="portal-form-row">
                            <div class="portal-form-group">
                                <label for="reg-first-name" class="portal-label">First Name</label>
                                <input type="text"
                                       id="reg-first-name"
                                       name="first_name"
                                       class="portal-input"
                                       placeholder="First name"
                                       required autocomplete="given-name">
                            </div>
                            <div class="portal-form-group">
                                <label for="reg-last-name" class="portal-label">Last Name</label>
                                <input type="text"
                                       id="reg-last-name"
                                       name="last_name"
                                       class="portal-input"
                                       placeholder="Last name"
                                       required autocomplete="family-name">
                            </div>
                        </div>

                        <div class="portal-form-group">
                            <label for="reg-email" class="portal-label">Email</label>
                            <input type="email"
                                   id="reg-email"
                                   name="email"
                                   class="portal-input"
                                   placeholder="your@email.com"
                                   required autocomplete="email">
                        </div>

                        <div class="portal-form-group">
                            <label for="reg-password" class="portal-label">Password</label>
                            <input type="password"
                                   id="reg-password"
                                   name="password"
                                   class="portal-input"
                                   placeholder="Min 6 characters"
                                   required autocomplete="new-password"
                                   minlength="6">
                        </div>

                        <div id="register-error"   class="portal-alert portal-alert-error"   style="display:none;"></div>
                        <div id="register-success" class="portal-alert portal-alert-success" style="display:none;"></div>

                        <button type="submit" id="register-btn" class="portal-btn-primary w-100 mt-3">
                            Create Account
                        </button>

                        <p class="text-center mt-3" style="color:#aaa; font-size:.9rem;">
                            Already have an account?
                            <a href="#" onclick="switchPortalTab('login'); return false;"
                               style="color: var(--gold); text-decoration:none;">Login here</a>
                        </p>

                    </form>
                </div>

            <?php endif; ?>
        </div>
    </div>
</main>

<style>
/* ── Portal login page styles ──────────────────────────────────── */
.portal-login-wrap {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--dark-bg, #30313e);
    padding: 40px 0;
}

/* Tabs */
.auth-tabs {
    display: flex;
    border-bottom: 2px solid rgba(255,255,255,0.12);
    gap: 0;
}

.auth-tab {
    flex: 1;
    padding: 12px 0;
    background: transparent;
    border: none;
    color: #888;
    font-size: 1.05rem;
    font-weight: 600;
    cursor: pointer;
    border-bottom: 3px solid transparent;
    margin-bottom: -2px;
    transition: color .2s, border-color .2s;
    font-family: inherit;
}

.auth-tab:hover {
    color: #ccc;
}

.auth-tab-active {
    color: var(--gold, #E6CFA0) !important;
    border-bottom-color: var(--gold, #E6CFA0) !important;
}

/* Form elements */
.portal-form-group {
    margin-bottom: 18px;
}

.portal-form-row {
    display: flex;
    gap: 16px;
}

.portal-form-row .portal-form-group {
    flex: 1;
}

.portal-label {
    display: block;
    color: #ccc;
    font-size: .88rem;
    margin-bottom: 6px;
    font-weight: 500;
}

.portal-input {
    width: 100%;
    box-sizing: border-box;
    background: rgba(255,255,255,.06);
    border: 1px solid rgba(255,255,255,.18);
    border-radius: 6px;
    color: #fff;
    font-size: 1rem;
    padding: 10px 14px;
    transition: border-color .2s;
    font-family: inherit;
}

.portal-input::placeholder {
    color: #666;
}

.portal-input:focus {
    outline: none;
    border-color: var(--gold, #E6CFA0);
    background: rgba(255,255,255,.09);
}

/* Buttons */
.portal-btn-primary {
    display: block;
    width: 100%;
    box-sizing: border-box;
    background: var(--gold, #E6CFA0);
    color: #1a1a1a;
    border: none;
    border-radius: 6px;
    padding: 12px 24px;
    font-size: 1rem;
    font-weight: 700;
    cursor: pointer;
    text-align: center;
    text-decoration: none;
    transition: opacity .2s;
    font-family: inherit;
}

.portal-btn-primary:hover {
    opacity: .88;
    color: #1a1a1a;
    text-decoration: none;
}

.portal-btn-primary:disabled {
    opacity: .5;
    cursor: not-allowed;
}

.portal-btn-outline {
    display: block;
    width: 100%;
    box-sizing: border-box;
    background: transparent;
    color: var(--gold, #E6CFA0);
    border: 1px solid var(--gold, #E6CFA0);
    border-radius: 6px;
    padding: 11px 24px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    text-align: center;
    text-decoration: none;
    transition: background .2s, color .2s;
    font-family: inherit;
}

.portal-btn-outline:hover {
    background: rgba(230,207,160,.1);
    color: var(--gold, #E6CFA0);
    text-decoration: none;
}

/* Alerts */
.portal-alert {
    border-radius: 6px;
    padding: 10px 14px;
    font-size: .9rem;
    margin-bottom: 12px;
}

.portal-alert-error {
    background: rgba(220,53,69,.15);
    border: 1px solid rgba(220,53,69,.4);
    color: #ff6b6b;
}

.portal-alert-success {
    background: rgba(40,167,69,.15);
    border: 1px solid rgba(40,167,69,.4);
    color: #6fcf97;
}

/* Utility */
.w-100 { width: 100%; }
.mt-3  { margin-top: 1rem; }
</style>

<?php
$ihq_portal_turnstile_site_key = ( function_exists( 'ihq_turnstile_is_configured' ) && ihq_turnstile_is_configured() )
	? CF_TURNSTILE_SITE_KEY
	: '';
?>
<?php if ( $ihq_portal_turnstile_site_key !== '' ) : ?>
<script src="https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit"></script>
<?php endif; ?>

<script>
(function () {
    'use strict';

    const AJAX_URL   = <?php echo json_encode( admin_url( 'admin-ajax.php' ) ); ?>;
    const PORTAL_URL = <?php echo json_encode( home_url( '/portal-home/' ) ); ?>;
    const TURNSTILE_SITE_KEY = <?php echo wp_json_encode( $ihq_portal_turnstile_site_key ); ?>;
    const PORTAL_LOGIN_NONCE = <?php echo json_encode( wp_create_nonce( 'influencer_login_portal_ajax' ) ); ?>;
    const REGISTER_NONCE = <?php echo json_encode( wp_create_nonce( 'influencer_register_ajax' ) ); ?>;

    var turnstileWidgetId = null;
    var pendingPortalLogin = null;

    if (TURNSTILE_SITE_KEY && typeof window.turnstile !== 'undefined') {
        turnstileWidgetId = window.turnstile.render('#portal-login-turnstile', {
            sitekey: TURNSTILE_SITE_KEY,
            size: 'invisible',
            callback: function (token) {
                if (!pendingPortalLogin) {
                    return;
                }
                var p = pendingPortalLogin;
                pendingPortalLogin = null;
                if (token) {
                    p.fd.append('cf-turnstile-response', token);
                }
                sendPortalLogin(p.fd, p.btn, p.errBox);
            },
            'error-callback': function () {
                pendingPortalLogin = null;
                var errBox = document.getElementById('login-error');
                var btn = document.getElementById('login-btn');
                if (errBox) {
                    errBox.textContent = 'Verification failed to load. Please refresh the page.';
                    errBox.style.display = 'block';
                }
                if (btn) {
                    btn.disabled = false;
                    btn.textContent = 'Login';
                }
            }
        });
    }

    function sendPortalLogin(fd, btn, errBox) {
        fetch(AJAX_URL, {
            method: 'POST',
            body: fd,
            credentials: 'same-origin',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(function (r) {
                return r.json().then(function (data) {
                    return { ok: r.ok, status: r.status, data: data };
                });
            })
            .then(function (result) {
                if (result.data && result.data.success) {
                    window.location.href = result.data.data.redirect;
                    return;
                }
                var payload = result.data && result.data.data;
                var msg = typeof payload === 'string' ? payload : 'Login failed. Please try again.';
                if (result.status === 403) {
                    msg = 'Human verification failed. Please try again.';
                }
                errBox.textContent = msg;
                errBox.style.display = 'block';
                btn.disabled = false;
                btn.textContent = 'Login';
                if (TURNSTILE_SITE_KEY && typeof window.turnstile !== 'undefined' && turnstileWidgetId !== null) {
                    window.turnstile.reset(turnstileWidgetId);
                }
            })
            .catch(function () {
                errBox.textContent = 'Network error. Please try again.';
                errBox.style.display = 'block';
                btn.disabled = false;
                btn.textContent = 'Login';
                if (TURNSTILE_SITE_KEY && typeof window.turnstile !== 'undefined' && turnstileWidgetId !== null) {
                    window.turnstile.reset(turnstileWidgetId);
                }
            });
    }

    // ── Tab switching ────────────────────────────────────────────
    window.switchPortalTab = function (tab) {
        var loginPane    = document.getElementById('pane-login');
        var registerPane = document.getElementById('pane-register');
        var loginTab     = document.getElementById('tab-login');
        var registerTab  = document.getElementById('tab-register');

        if (tab === 'login') {
            loginPane.style.display    = 'block';
            registerPane.style.display = 'none';
            loginTab.classList.add('auth-tab-active');
            registerTab.classList.remove('auth-tab-active');
        } else {
            loginPane.style.display    = 'none';
            registerPane.style.display = 'block';
            loginTab.classList.remove('auth-tab-active');
            registerTab.classList.add('auth-tab-active');
        }
    };

    // ── AJAX Login (portal template — Turnstile when keys configured) ──
    window.handlePortalLogin = function (e) {
        e.preventDefault();

        var errBox = document.getElementById('login-error');
        var btn    = document.getElementById('login-btn');
        errBox.style.display = 'none';
        btn.disabled = true;
        btn.textContent = 'Logging in…';

        var fd = new FormData();
        fd.append('action',       'influencer_login_portal_ajax');
        fd.append('nonce',        PORTAL_LOGIN_NONCE);
        fd.append('email',        document.getElementById('login-email').value);
        fd.append('password',     document.getElementById('login-password').value);
        fd.append('redirect_url', PORTAL_URL);

        if (TURNSTILE_SITE_KEY && typeof window.turnstile !== 'undefined' && turnstileWidgetId !== null) {
            pendingPortalLogin = { fd: fd, btn: btn, errBox: errBox };
            window.turnstile.execute(turnstileWidgetId);
            return;
        }

        sendPortalLogin(fd, btn, errBox);
    };

    // ── AJAX Register ────────────────────────────────────────────
    window.handlePortalRegister = function (e) {
        e.preventDefault();

        var errBox     = document.getElementById('register-error');
        var successBox = document.getElementById('register-success');
        var btn        = document.getElementById('register-btn');
        errBox.style.display     = 'none';
        successBox.style.display = 'none';
        btn.disabled = true;
        btn.textContent = 'Creating account…';

        var payload = {
            action: 'influencer_register_ajax',
            nonce: REGISTER_NONCE,
            first_name: document.getElementById('reg-first-name').value,
            last_name: document.getElementById('reg-last-name').value,
            email: document.getElementById('reg-email').value,
            password: document.getElementById('reg-password').value,
            redirect_url: PORTAL_URL,
        };

        console.log('Register payload:', payload, 'AJAX URL:', AJAX_URL);

        var params = new URLSearchParams();
        Object.keys(payload).forEach(function (key) {
            params.append(key, payload[key]);
        });

        fetch(AJAX_URL, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: params.toString(),
        })
            .then(function (r) {
                return r.text().then(function (text) {
                    if (!r.ok) {
                        console.error('Register request failed:', r.status, r.statusText, text);
                        throw new Error(text || 'Request failed with status ' + r.status);
                    }
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        console.error('Register response is not valid JSON:', text);
                        throw new Error('Unexpected server response: ' + text);
                    }
                });
            })
            .then(function (data) {
                if (data.success) {
                    window.location.href = data.data.redirect;
                } else {
                    errBox.textContent   = data.data || 'Registration failed. Please try again.';
                    errBox.style.display = 'block';
                    btn.disabled         = false;
                    btn.textContent      = 'Create Account';
                }
            })
            .catch(function (error) {
                console.error('Register error:', error);
                errBox.textContent   = error.message || 'Network error. Please try again.';
                errBox.style.display = 'block';
                btn.disabled         = false;
                btn.textContent      = 'Create Account';
            });
    };
}());
</script>

<?php get_footer(); ?>
