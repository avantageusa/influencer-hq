<?php
/**
 * Template Name: Portal Login
 * Description: Login and registration page for influencer portal access.
 *
 * @package influencer-hq
 */

get_header();
get_template_part( 'template-parts/portal-styles' );

$ihq_portal_login_nonce = wp_create_nonce( 'ihq_login_code_nonce' );
$ihq_portal_reg_nonce   = wp_create_nonce( 'ihq_reg_code_nonce' );
$ihq_portal_ts_site_key = '';
if ( function_exists( 'ihq_turnstile_is_configured' ) && ihq_turnstile_is_configured() && defined( 'CF_TURNSTILE_SITE_KEY' ) ) {
	$ihq_portal_ts_site_key = CF_TURNSTILE_SITE_KEY;
}
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

                <!-- ── LOGIN (6-digit email code) ───────────────────────────── -->
                <div id="pane-login">
                    <div id="login-step-email">
                        <p style="color:#aaa;font-size:.9rem;margin-bottom:16px;">We’ll email you a one-time code to sign in. No password needed.</p>
                        <div class="portal-form-group">
                            <label for="login-email" class="portal-label">Email</label>
                            <input type="email"
                                   id="login-email"
                                   class="portal-input"
                                   placeholder="your@email.com"
                                   autocomplete="email">
                        </div>
                        <div id="login-error" class="portal-alert portal-alert-error" style="display:none;"></div>
                        <div id="login-info" class="portal-alert portal-alert-success" style="display:none;background:rgba(255,215,122,.08);border-color:rgba(230,207,160,.35);color:#e8dcb8;"></div>

                        <?php if ( $ihq_portal_ts_site_key !== '' ) : ?>
                        <div id="portal-login-turnstile" class="mt-3" data-sitekey="<?php echo esc_attr( $ihq_portal_ts_site_key ); ?>" style="display:flex;justify-content:center;"></div>
                        <?php endif; ?>

                        <button type="button" id="login-send-code-btn" class="portal-btn-primary w-100 mt-3">
                            Send sign-in code
                        </button>
                    </div>

                    <div id="login-step-code" style="display:none;">
                        <p style="color:#aaa;font-size:.9rem;margin-bottom:16px;">Enter the code we sent you.</p>
                        <div class="portal-form-group">
                            <label for="login-code" class="portal-label">6-digit code</label>
                            <input type="text"
                                   id="login-code"
                                   class="portal-input"
                                   placeholder="000000"
                                   maxlength="6"
                                   inputmode="numeric"
                                   autocomplete="one-time-code"
                                   style="letter-spacing:.35em;text-align:center;">
                        </div>
                        <p id="login-code-expires" style="color:var(--gold);font-size:.85rem;margin-bottom:8px;"></p>
                        <div id="login-code-error" class="portal-alert portal-alert-error" style="display:none;"></div>
                        <button type="button" id="login-verify-btn" class="portal-btn-primary w-100 mt-3">Verify & sign in</button>
                        <button type="button" id="login-back-btn" class="portal-btn-outline w-100 mt-2">Back</button>
                    </div>

                        <p class="text-center mt-3" style="color:#aaa; font-size:.9rem;">
                            Don't have an account?
                            <a href="#" onclick="switchPortalTab('register'); return false;"
                               style="color: var(--gold); text-decoration:none;">Register here</a>
                        </p>
                </div>

                <!-- ── REGISTER (6-digit email code) ───────────────────────── -->
                <div id="pane-register" style="display:none;">
                    <div id="reg-step-details">
                        <p style="color:#aaa;font-size:.9rem;margin-bottom:16px;">Tell us who you are. We’ll email a registration code.</p>

                        <div class="portal-form-row">
                            <div class="portal-form-group">
                                <label for="reg-first-name" class="portal-label">First Name</label>
                                <input type="text"
                                       id="reg-first-name"
                                       class="portal-input"
                                       placeholder="First name"
                                       autocomplete="given-name">
                            </div>
                            <div class="portal-form-group">
                                <label for="reg-last-name" class="portal-label">Last Name</label>
                                <input type="text"
                                       id="reg-last-name"
                                       class="portal-input"
                                       placeholder="Last name"
                                       autocomplete="family-name">
                            </div>
                        </div>

                        <div class="portal-form-group">
                            <label for="reg-email" class="portal-label">Email</label>
                            <input type="email"
                                   id="reg-email"
                                   class="portal-input"
                                   placeholder="your@email.com"
                                   autocomplete="email">
                        </div>

                        <div id="register-error"   class="portal-alert portal-alert-error"   style="display:none;"></div>
                        <div id="register-success" class="portal-alert portal-alert-success" style="display:none;"></div>

                        <?php if ( $ihq_portal_ts_site_key !== '' ) : ?>
                        <div id="portal-reg-turnstile" class="mt-3" data-sitekey="<?php echo esc_attr( $ihq_portal_ts_site_key ); ?>" style="display:flex;justify-content:center;"></div>
                        <?php endif; ?>

                        <button type="button" id="register-send-btn" class="portal-btn-primary w-100 mt-3">
                            Send registration code
                        </button>
                    </div>

                    <div id="reg-step-code" style="display:none;">
                        <p style="color:#aaa;font-size:.9rem;margin-bottom:16px;">Enter your code to finish signup.</p>
                        <div class="portal-form-group">
                            <label for="reg-code" class="portal-label">6-digit code</label>
                            <input type="text"
                                   id="reg-code"
                                   class="portal-input"
                                   placeholder="000000"
                                   maxlength="6"
                                   inputmode="numeric"
                                   autocomplete="one-time-code"
                                   style="letter-spacing:.35em;text-align:center;">
                        </div>
                        <p id="reg-code-expires" style="color:var(--gold);font-size:.85rem;"></p>
                        <div id="reg-code-error" class="portal-alert portal-alert-error" style="display:none;"></div>
                        <button type="button" id="reg-verify-btn" class="portal-btn-primary w-100 mt-3">Verify & create account</button>
                        <button type="button" id="reg-back-btn" class="portal-btn-outline w-100 mt-2">Back</button>
                    </div>

                        <p class="text-center mt-3" style="color:#aaa; font-size:.9rem;">
                            Already have an account?
                            <a href="#" onclick="switchPortalTab('login'); return false;"
                               style="color: var(--gold); text-decoration:none;">Login here</a>
                        </p>
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

<?php if ( $ihq_portal_ts_site_key !== '' ) : ?>
<script src="https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit"></script>
<?php endif; ?>

<script>
(function () {
    'use strict';

    var AJAX_URL = <?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>;
    var PORTAL_URL = <?php echo wp_json_encode( home_url( '/portal-home/' ) ); ?>;
    var LOGIN_NONCE = <?php echo wp_json_encode( $ihq_portal_login_nonce ); ?>;
    var REG_NONCE = <?php echo wp_json_encode( $ihq_portal_reg_nonce ); ?>;
    var TURNSTILE_SITE_KEY = <?php echo wp_json_encode( $ihq_portal_ts_site_key ); ?>;

    var loginTurnstileWidgetId = null;
    var regTurnstileWidgetId = null;
    var portalLoginToken = '';
    var portalRegToken = '';

    function jsonErrMessage(data) {
        if (!data || !data.data) {
            return 'Something went wrong.';
        }
        var d = data.data;
        if (typeof d === 'string') {
            return d;
        }
        return d.message ? d.message : 'Something went wrong.';
    }

    function removeLoginTs() {
        var host = document.getElementById('portal-login-turnstile');
        if (!host || !TURNSTILE_SITE_KEY) {
            return;
        }
        if (loginTurnstileWidgetId !== null && typeof window.turnstile !== 'undefined') {
            try {
                window.turnstile.remove(loginTurnstileWidgetId);
            } catch (e1) {}
            loginTurnstileWidgetId = null;
        }
        host.removeAttribute('data-rendered');
        host.innerHTML = '';
    }

    function renderLoginTs() {
        if (!TURNSTILE_SITE_KEY) {
            return;
        }
        var el = document.getElementById('portal-login-turnstile');
        if (!el || el.getAttribute('data-rendered') === '1') {
            return;
        }
        if (typeof window.turnstile === 'undefined') {
            window.setTimeout(renderLoginTs, 200);
            return;
        }
        loginTurnstileWidgetId = window.turnstile.render(el, {
            sitekey: TURNSTILE_SITE_KEY
        });
        el.setAttribute('data-rendered', '1');
    }

    function removeRegTs() {
        var host = document.getElementById('portal-reg-turnstile');
        if (!host || !TURNSTILE_SITE_KEY) {
            return;
        }
        if (regTurnstileWidgetId !== null && typeof window.turnstile !== 'undefined') {
            try {
                window.turnstile.remove(regTurnstileWidgetId);
            } catch (e2) {}
            regTurnstileWidgetId = null;
        }
        host.removeAttribute('data-rendered');
        host.innerHTML = '';
    }

    function renderRegTs() {
        if (!TURNSTILE_SITE_KEY) {
            return;
        }
        var el = document.getElementById('portal-reg-turnstile');
        if (!el || el.getAttribute('data-rendered') === '1') {
            return;
        }
        if (typeof window.turnstile === 'undefined') {
            window.setTimeout(renderRegTs, 200);
            return;
        }
        regTurnstileWidgetId = window.turnstile.render(el, {
            sitekey: TURNSTILE_SITE_KEY
        });
        el.setAttribute('data-rendered', '1');
    }

    function resetLoginPane() {
        portalLoginToken = '';
        document.getElementById('login-step-email').style.display = 'block';
        document.getElementById('login-step-code').style.display = 'none';
        document.getElementById('login-email').value = '';
        document.getElementById('login-code').value = '';
        document.getElementById('login-error').style.display = 'none';
        document.getElementById('login-info').style.display = 'none';
        document.getElementById('login-code-error').style.display = 'none';
        document.getElementById('login-code-expires').textContent = '';
        removeLoginTs();
        window.setTimeout(renderLoginTs, 80);
    }

    function resetRegisterPane() {
        portalRegToken = '';
        document.getElementById('reg-step-details').style.display = 'block';
        document.getElementById('reg-step-code').style.display = 'none';
        document.getElementById('reg-first-name').value = '';
        document.getElementById('reg-last-name').value = '';
        document.getElementById('reg-email').value = '';
        document.getElementById('reg-code').value = '';
        document.getElementById('register-error').style.display = 'none';
        document.getElementById('register-success').style.display = 'none';
        document.getElementById('reg-code-error').style.display = 'none';
        document.getElementById('reg-code-expires').textContent = '';
        removeRegTs();
        window.setTimeout(renderRegTs, 80);
    }

    window.switchPortalTab = function (tab) {
        var loginPane = document.getElementById('pane-login');
        var registerPane = document.getElementById('pane-register');
        var loginTab = document.getElementById('tab-login');
        var registerTab = document.getElementById('tab-register');
        if (tab === 'login') {
            loginPane.style.display = 'block';
            registerPane.style.display = 'none';
            loginTab.classList.add('auth-tab-active');
            registerTab.classList.remove('auth-tab-active');
            resetLoginPane();
        } else {
            loginPane.style.display = 'none';
            registerPane.style.display = 'block';
            loginTab.classList.remove('auth-tab-active');
            registerTab.classList.add('auth-tab-active');
            resetRegisterPane();
        }
    };

    function onLoginSendCode() {
        var errBox = document.getElementById('login-error');
        var infoBox = document.getElementById('login-info');
        errBox.style.display = 'none';
        infoBox.style.display = 'none';
        var email = document.getElementById('login-email').value.trim();
        if (!email || email.indexOf('@') === -1) {
            errBox.textContent = 'Please enter a valid email address.';
            errBox.style.display = 'block';
            return;
        }
        var tsToken = '';
        if (TURNSTILE_SITE_KEY) {
            if (typeof window.turnstile === 'undefined' || loginTurnstileWidgetId === null) {
                errBox.textContent = 'Please wait for the security check to load.';
                errBox.style.display = 'block';
                return;
            }
            tsToken = window.turnstile.getResponse(loginTurnstileWidgetId) || '';
            if (!tsToken) {
                errBox.textContent = 'Please complete the security verification.';
                errBox.style.display = 'block';
                return;
            }
        }
        var btn = document.getElementById('login-send-code-btn');
        btn.disabled = true;
        var fd = new FormData();
        fd.append('action', 'ihq_send_login_code');
        fd.append('nonce', LOGIN_NONCE);
        fd.append('email', email);
        fd.append('cf-turnstile-response', tsToken);
        fetch(AJAX_URL, { method: 'POST', body: fd, credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                btn.disabled = false;
                if (!data.success) {
                    errBox.textContent = jsonErrMessage(data);
                    errBox.style.display = 'block';
                    removeLoginTs();
                    window.setTimeout(renderLoginTs, 80);
                    return;
                }
                var d = data.data;
                infoBox.textContent = d.message || 'If that email matches an account, you will receive a code.';
                infoBox.style.display = 'block';
                if (!d.signup_token || d.skipped) {
                    removeLoginTs();
                    window.setTimeout(renderLoginTs, 80);
                    return;
                }
                portalLoginToken = d.signup_token;
                var minutes = d.expires_minutes || 15;
                document.getElementById('login-code-expires').textContent =
                    'Code expires in ' + minutes + ' minute' + (minutes === 1 ? '' : 's') + '.';
                document.getElementById('login-code').value = '';
                document.getElementById('login-code-error').style.display = 'none';
                document.getElementById('login-step-email').style.display = 'none';
                document.getElementById('login-step-code').style.display = 'block';
            })
            .catch(function () {
                btn.disabled = false;
                errBox.textContent = 'Network error. Please try again.';
                errBox.style.display = 'block';
            });
    }

    function onLoginVerify() {
        var errEl = document.getElementById('login-code-error');
        errEl.style.display = 'none';
        var raw = document.getElementById('login-code').value.replace(/\D/g, '');
        if (raw.length !== 6) {
            errEl.textContent = 'Enter the 6-digit code from your email.';
            errEl.style.display = 'block';
            return;
        }
        if (!portalLoginToken) {
            errEl.textContent = 'Send a code first.';
            errEl.style.display = 'block';
            return;
        }
        var btn = document.getElementById('login-verify-btn');
        btn.disabled = true;
        var fd = new FormData();
        fd.append('action', 'ihq_verify_login_code');
        fd.append('nonce', LOGIN_NONCE);
        fd.append('signup_token', portalLoginToken);
        fd.append('code', raw);
        fd.append('redirect_url', PORTAL_URL);
        fetch(AJAX_URL, { method: 'POST', body: fd, credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                btn.disabled = false;
                if (!data.success) {
                    errEl.textContent = jsonErrMessage(data);
                    errEl.style.display = 'block';
                    return;
                }
                window.location.href = data.data.redirect_url || PORTAL_URL;
            })
            .catch(function () {
                btn.disabled = false;
                errEl.textContent = 'Network error. Please try again.';
                errEl.style.display = 'block';
            });
    }

    function onRegSendCode() {
        var errBox = document.getElementById('register-error');
        errBox.style.display = 'none';
        var first = document.getElementById('reg-first-name').value.trim();
        var last = document.getElementById('reg-last-name').value.trim();
        var email = document.getElementById('reg-email').value.trim();
        if (!first || !last) {
            errBox.textContent = 'Please enter your first and last name.';
            errBox.style.display = 'block';
            return;
        }
        if (!email || email.indexOf('@') === -1) {
            errBox.textContent = 'Please enter a valid email address.';
            errBox.style.display = 'block';
            return;
        }
        var tsToken = '';
        if (TURNSTILE_SITE_KEY) {
            if (typeof window.turnstile === 'undefined' || regTurnstileWidgetId === null) {
                errBox.textContent = 'Please wait for the security check to load.';
                errBox.style.display = 'block';
                return;
            }
            tsToken = window.turnstile.getResponse(regTurnstileWidgetId) || '';
            if (!tsToken) {
                errBox.textContent = 'Please complete the security verification.';
                errBox.style.display = 'block';
                return;
            }
        }
        var btn = document.getElementById('register-send-btn');
        btn.disabled = true;
        var fd = new FormData();
        fd.append('action', 'ihq_send_registration_code');
        fd.append('nonce', REG_NONCE);
        fd.append('first_name', first);
        fd.append('last_name', last);
        fd.append('email', email);
        fd.append('platform_handle', '');
        fd.append('challenge_type', 'maybe_later');
        fd.append('comm_primary', 'email');
        fd.append('telegram_username', '');
        fd.append('cf-turnstile-response', tsToken);
        fetch(AJAX_URL, { method: 'POST', body: fd, credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                btn.disabled = false;
                if (!data.success) {
                    errBox.textContent = jsonErrMessage(data);
                    errBox.style.display = 'block';
                    removeRegTs();
                    window.setTimeout(renderRegTs, 80);
                    return;
                }
                portalRegToken = data.data.signup_token;
                var minutes = data.data.expires_minutes || 15;
                document.getElementById('reg-code-expires').textContent =
                    'Code expires in ' + minutes + ' minute' + (minutes === 1 ? '' : 's') + '.';
                document.getElementById('reg-code').value = '';
                document.getElementById('reg-code-error').style.display = 'none';
                document.getElementById('reg-step-details').style.display = 'none';
                document.getElementById('reg-step-code').style.display = 'block';
            })
            .catch(function () {
                btn.disabled = false;
                errBox.textContent = 'Network error. Please try again.';
                errBox.style.display = 'block';
            });
    }

    function onRegVerify() {
        var errEl = document.getElementById('reg-code-error');
        errEl.style.display = 'none';
        var raw = document.getElementById('reg-code').value.replace(/\D/g, '');
        if (raw.length !== 6) {
            errEl.textContent = 'Enter the 6-digit code from your email.';
            errEl.style.display = 'block';
            return;
        }
        if (!portalRegToken) {
            errEl.textContent = 'Send a code first.';
            errEl.style.display = 'block';
            return;
        }
        var btn = document.getElementById('reg-verify-btn');
        btn.disabled = true;
        var fd = new FormData();
        fd.append('action', 'ihq_verify_registration_code');
        fd.append('nonce', REG_NONCE);
        fd.append('signup_token', portalRegToken);
        fd.append('code', raw);
        fetch(AJAX_URL, { method: 'POST', body: fd, credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                btn.disabled = false;
                if (!data.success) {
                    errEl.textContent = jsonErrMessage(data);
                    errEl.style.display = 'block';
                    return;
                }
                window.location.href = data.data.redirect_url;
            })
            .catch(function () {
                btn.disabled = false;
                errEl.textContent = 'Network error. Please try again.';
                errEl.style.display = 'block';
            });
    }

    document.getElementById('login-send-code-btn').addEventListener('click', onLoginSendCode);
    document.getElementById('login-verify-btn').addEventListener('click', onLoginVerify);
    document.getElementById('login-back-btn').addEventListener('click', function () {
        removeLoginTs();
        portalLoginToken = '';
        document.getElementById('login-step-code').style.display = 'none';
        document.getElementById('login-step-email').style.display = 'block';
        document.getElementById('login-code-error').style.display = 'none';
        window.setTimeout(renderLoginTs, 80);
    });

    document.getElementById('register-send-btn').addEventListener('click', onRegSendCode);
    document.getElementById('reg-verify-btn').addEventListener('click', onRegVerify);
    document.getElementById('reg-back-btn').addEventListener('click', function () {
        removeRegTs();
        portalRegToken = '';
        document.getElementById('reg-step-code').style.display = 'none';
        document.getElementById('reg-step-details').style.display = 'block';
        document.getElementById('reg-code-error').style.display = 'none';
        window.setTimeout(renderRegTs, 80);
    });

    window.setTimeout(function () {
        renderLoginTs();
        renderRegTs();
    }, 100);
}());
</script>

<?php get_footer(); ?>
