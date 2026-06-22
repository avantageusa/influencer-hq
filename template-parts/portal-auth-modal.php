<?php
/**
 * Login / Register modal for portal header (parity with page-portal-home-claude auth overlay).
 *
 * @package influencer-hq
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( is_user_logged_in() ) {
	return;
}

$ihq_portal_auth_login_nonce = wp_create_nonce( 'ihq_login_code_nonce' );
$ihq_portal_auth_telegram_nonce = wp_create_nonce( 'ihq_telegram_login_pubkey' );
$ihq_portal_auth_turnstile   = '';
if ( function_exists( 'ihq_turnstile_is_configured' ) && ihq_turnstile_is_configured() && defined( 'CF_TURNSTILE_SITE_KEY' ) ) {
	$ihq_portal_auth_turnstile = CF_TURNSTILE_SITE_KEY;
}
$ihq_portal_auth_redirect   = home_url( '/portal/portal-home/' );
$ihq_portal_cf_country_seed = function_exists( 'ihq_get_cloudflare_country_iso_alpha2' ) ? ihq_get_cloudflare_country_iso_alpha2() : 'US';
$ihq_portal_telegram_client_id = 0;
if ( defined( 'IHQ_TELEGRAM_LOGIN_CLIENT_ID' ) && preg_match( '/^\d+$/', (string) IHQ_TELEGRAM_LOGIN_CLIENT_ID ) ) {
	$ihq_portal_telegram_client_id = (int) IHQ_TELEGRAM_LOGIN_CLIENT_ID;
}
?>
<?php if ( $ihq_portal_auth_turnstile !== '' ) : ?>
<script src="https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit" async defer></script>
<?php endif; ?>

<!-- Auth Modal (Login / Register) — same flow as page-portal-home-claude -->
<div class="auth-overlay" id="authModal" aria-hidden="true">
	<div class="auth-modal" role="dialog" aria-modal="true" aria-labelledby="portal-auth-modal-title">
		<button type="button" class="auth-modal-x" onclick="closeAuthModal()" aria-label="<?php esc_attr_e( 'Close', 'influencer-hq' ); ?>">✕</button>

		<?php
		$welcome = isset( $_GET['welcome'] ) && 'true' === $_GET['welcome'];
		if ( $welcome ) :
			?>
			<div style="text-align:center;padding:24px 0">
				<div style="font-size:2.5rem;color:var(--gl);margin-bottom:16px">◈</div>
				<h3 id="portal-auth-modal-title" style="font-family:'Cinzel',serif;font-size:1.8rem;color:var(--white);margin-bottom:12px"><?php esc_html_e( 'Welcome to Influencer HQ!', 'influencer-hq' ); ?></h3>
				<p style="font-family:'Be Vietnam Pro',sans-serif;font-size:1rem;color:var(--warm);line-height:1.9"><?php esc_html_e( 'Your account has been successfully created! You\'re now ready to start your journey with us.', 'influencer-hq' ); ?></p>
			</div>
		<?php else : ?>

			<div class="auth-tabs">
					<button type="button" class="auth-tab-btn active" id="auth-tab-login" onclick="switchAuthTab('login')"><?php esc_html_e( 'Login', 'influencer-hq' ); ?></button>
					<button type="button" style="display: none;" class="auth-tab-btn" id="auth-tab-register" onclick="switchAuthTab('register')"><?php esc_html_e( 'Register', 'influencer-hq' ); ?></button>
				</div>

				<div class="auth-pane active" id="auth-pane-login">
					<div id="auth-login-step-email" style="max-width:480px;margin:0 auto">
						<p class="auth-section-sub" style="margin-bottom:16px"><?php esc_html_e( 'Enter your email. We’ll send a 6-digit sign-in code.', 'influencer-hq' ); ?></p>
						<div class="auth-field">
							<label for="auth-login-email"><?php esc_html_e( 'Email', 'influencer-hq' ); ?></label>
							<input type="email" id="auth-login-email" name="email" required placeholder="your@email.com" autocomplete="email">
						</div>
						<?php if ( $ihq_portal_auth_turnstile !== '' ) : ?>
						<div id="auth-login-turnstile" data-sitekey="<?php echo esc_attr( $ihq_portal_auth_turnstile ); ?>" style="display:flex;justify-content:center;margin:16px 0"></div>
						<?php endif; ?>
						<div class="auth-err" id="auth-login-err"></div>
						<div class="auth-err" id="auth-login-info" style="display:none;background:rgba(40,167,69,.12);border-color:rgba(40,167,69,.35);color:#6fcf97"></div>
						<button type="button" class="auth-submit-btn" id="auth-login-send-btn" onclick="ihqAuthLoginSendCode()"><?php esc_html_e( 'Send sign-in code', 'influencer-hq' ); ?></button>
						<?php if ( $ihq_portal_telegram_client_id > 0 ) : ?>
						<div style="display:flex;align-items:center;gap:10px;margin:14px 0">
							<div style="height:1px;flex:1;background:rgba(240,201,58,.22)"></div>
							<div style="font-family:'Be Vietnam Pro',sans-serif;font-size:.72rem;letter-spacing:.16em;color:var(--warm)">OR</div>
							<div style="height:1px;flex:1;background:rgba(240,201,58,.22)"></div>
						</div>
						<button type="button" class="auth-submit-btn" id="auth-login-telegram-btn" style="background:transparent;border:1px solid rgba(42,171,238,.55);color:#7ecdf8" onclick="ihqAuthLoginWithTelegram()"><?php esc_html_e( 'Login with Telegram', 'influencer-hq' ); ?></button>
						<div class="auth-err" id="auth-login-telegram-err"></div>
						<?php endif; ?>
					</div>
					<div id="auth-login-step-code" style="display:none;max-width:480px;margin:0 auto">
						<p class="auth-section-sub" style="margin-bottom:16px"><?php esc_html_e( 'Enter the code from your email.', 'influencer-hq' ); ?></p>
						<div class="auth-field">
							<label for="auth-login-code"><?php esc_html_e( '6-digit code', 'influencer-hq' ); ?></label>
							<input type="text" id="auth-login-code" inputmode="numeric" pattern="[0-9]*" maxlength="6" autocomplete="one-time-code" placeholder="000000" style="text-align:center;letter-spacing:0.35em;font-size:1.2rem">
						</div>
						<p class="auth-section-sub" id="auth-login-code-expires" style="margin-top:8px"></p>
						<div class="auth-err" id="auth-login-code-err"></div>
						<button type="button" class="auth-submit-btn" id="auth-login-verify-btn" onclick="ihqAuthLoginVerify()"><?php esc_html_e( 'Verify & sign in', 'influencer-hq' ); ?></button>
						<button type="button" class="auth-submit-btn" style="margin-top:12px;background:transparent;border:1px solid rgba(240,201,58,.45);color:var(--warm)" id="auth-login-back-btn" onclick="ihqAuthLoginBackToEmail()"><?php esc_html_e( 'Back', 'influencer-hq' ); ?></button>
					</div>
				</div>

				<div class="auth-pane" id="auth-pane-register">
					<form id="auth-register-form" onsubmit="handleAuthRegister(event)">
						<input type="hidden" id="auth-competition-preferences" name="competition_preferences" value="">

						<p class="auth-section-sub"><?php esc_html_e( 'Choose the method(s) you\'d like us to use to communicate with you:', 'influencer-hq' ); ?></p>

						<div class="auth-ch-group">
							<div class="auth-check auth-ch-block">
								<input type="checkbox" id="auth-ch-line" value="line" onchange="toggleAuthCh('line',this)">
								<label for="auth-ch-line">LINE</label>
							</div>
							<input type="text" class="auth-ch-input" id="auth-chi-line" placeholder="<?php esc_attr_e( 'Your LINE ID', 'influencer-hq' ); ?>" data-method="line">

							<div class="auth-check auth-ch-block">
								<input type="checkbox" id="auth-ch-telegram" value="telegram" onchange="toggleAuthCh('telegram',this)">
								<label for="auth-ch-telegram">Telegram</label>
							</div>
							<input type="text" class="auth-ch-input" id="auth-chi-telegram" placeholder="@yourusername" data-method="telegram">

							<div class="auth-check auth-ch-block">
								<input type="checkbox" id="auth-ch-whatsapp" value="whatsapp" onchange="toggleAuthCh('whatsapp',this)">
								<label for="auth-ch-whatsapp">WhatsApp</label>
							</div>
							<input type="tel" class="auth-ch-input" id="auth-chi-whatsapp" placeholder="+1234567890" data-method="whatsapp">

							<div class="auth-check auth-ch-block">
								<input type="checkbox" id="auth-ch-wechat" value="wechat" onchange="toggleAuthCh('wechat',this)">
								<label for="auth-ch-wechat">WeChat</label>
							</div>
							<input type="text" class="auth-ch-input" id="auth-chi-wechat" placeholder="<?php esc_attr_e( 'Your WeChat ID', 'influencer-hq' ); ?>" data-method="wechat">
						</div>

						<div id="auth-challenge-section" style="display:none">
							<hr class="auth-section-sep">
							<h3 class="auth-section-title"><?php esc_html_e( 'Choose Your Path to Lead Global Competition', 'influencer-hq' ); ?></h3>
							<p class="auth-section-sub"><?php esc_html_e( 'After your email is verified, you\'ll enter HQ², your private influencer portal. Choose how you want to lead:', 'influencer-hq' ); ?></p>

							<div class="auth-comp-cards">
								<div class="auth-comp-card" id="auth-cc-weekend" onclick="selectAuthComp('weekend_world',this)">
									<input type="radio" name="auth_challenge_type" value="weekend_world" id="auth-comp-weekend">
									<div class="auth-comp-card-inner">
										<h4><?php esc_html_e( 'Weekend World Challenge', 'influencer-hq' ); ?></h4>
										<p><?php esc_html_e( 'A global challenge open to all influencers and their followers — a massive international competition.', 'influencer-hq' ); ?></p>
									</div>
								</div>
								<div class="auth-comp-card" id="auth-cc-community" onclick="selectAuthComp('community_challenge',this)">
									<input type="radio" name="auth_challenge_type" value="community_challenge" id="auth-comp-community">
									<div class="auth-comp-card-inner">
										<h4><?php esc_html_e( 'Community Challenge', 'influencer-hq' ); ?></h4>
										<p><?php esc_html_e( 'A challenge created by you, for your community, on your schedule — with an option to stream live.', 'influencer-hq' ); ?></p>
									</div>
								</div>
								<div class="auth-comp-card" id="auth-cc-later" onclick="selectAuthComp('maybe_later',this)">
									<input type="radio" name="auth_challenge_type" value="maybe_later" id="auth-comp-later">
									<div class="auth-comp-card-inner">
										<h4><?php esc_html_e( 'Thanks, maybe later', 'influencer-hq' ); ?></h4>
									</div>
								</div>
							</div>
						</div>

						<div id="auth-genius-section" style="display:none">
							<hr class="auth-section-sep">
							<h3 class="auth-section-title"><?php esc_html_e( 'Meet Genius — Your Partner in Protecting Your Equity Rewards', 'influencer-hq' ); ?></h3>
							<p class="auth-section-sub"><?php esc_html_e( 'Genius automatically manages lifetime Influencer HQ equity in your expanding network. To activate, verify your identity below.', 'influencer-hq' ); ?></p>

							<div class="auth-field">
								<label for="auth-reg-email"><?php esc_html_e( 'Email', 'influencer-hq' ); ?></label>
								<input type="email" id="auth-reg-email" name="email" required placeholder="your@email.com">
							</div>
							<div class="auth-field">
								<label for="auth-reg-first"><?php esc_html_e( 'First Name', 'influencer-hq' ); ?></label>
								<input type="text" id="auth-reg-first" name="first_name" required placeholder="<?php esc_attr_e( 'First Name', 'influencer-hq' ); ?>">
							</div>
							<div class="auth-field">
								<label for="auth-reg-last"><?php esc_html_e( 'Last Name', 'influencer-hq' ); ?></label>
								<input type="text" id="auth-reg-last" name="last_name" required placeholder="<?php esc_attr_e( 'Last Name', 'influencer-hq' ); ?>">
							</div>
							<div class="auth-field">
								<label for="auth-reg-handle"><?php esc_html_e( 'Favorite Platform Handle', 'influencer-hq' ); ?></label>
								<input type="text" id="auth-reg-handle" name="platform_handle" placeholder="@yourhandle">
							</div>

							<div class="auth-check">
								<input type="checkbox" id="auth-prefer-facial" onchange="document.getElementById('auth-facial-opts').style.display=this.checked?'block':'none'">
								<label for="auth-prefer-facial"><?php esc_html_e( 'Prefer Facial Recognition?', 'influencer-hq' ); ?></label>
							</div>
							<div id="auth-facial-opts" style="display:none;margin-bottom:20px">
								<p class="auth-section-sub" style="margin-bottom:12px"><?php esc_html_e( 'Sign in with:', 'influencer-hq' ); ?></p>
								<div style="display:flex;flex-wrap:wrap;gap:8px">
									<?php foreach ( array( 'Face ID', 'WeChat Face', 'Alipay Face', 'LINE Face', 'KakaoTalk', 'Biometric ID' ) as $fr ) : ?>
										<button type="button" style="padding:10px 16px;background:transparent;border:1px solid rgba(240,201,58,.4);color:var(--warm);font-family:'Be Vietnam Pro',sans-serif;font-size:.8rem;cursor:pointer"><?php echo esc_html( $fr ); ?></button>
									<?php endforeach; ?>
								</div>
							</div>

							<div class="auth-err" id="auth-reg-err"></div>
							<p style="font-family:'Be Vietnam Pro',sans-serif;font-size:.8rem;color:var(--warm);text-align:center;margin-bottom:16px"><?php esc_html_e( 'Please check your email to verify and activate Genius.', 'influencer-hq' ); ?></p>
							<button type="submit" class="auth-submit-btn" id="auth-reg-btn"><?php esc_html_e( 'Send Verification Email', 'influencer-hq' ); ?></button>
						</div>

					</form>
				</div>

		<?php endif; ?>

	</div>
</div>

<script>
window.IHQ_CF_COUNTRY_SEED_ISO = <?php echo wp_json_encode( $ihq_portal_cf_country_seed ); ?>;
if (typeof window.ihqResolveClientCountryIsoAlpha2 !== 'function') {
	window.ihqResolveClientCountryIsoAlpha2 = function () {
		var seed = typeof window.IHQ_CF_COUNTRY_SEED_ISO === 'string' ? window.IHQ_CF_COUNTRY_SEED_ISO.trim() : '';
		if (/^[A-Za-z]{2}$/.test(seed)) {
			return seed.toUpperCase();
		}
		return 'US';
	};
}
var IHQ_AUTH_LOGIN = {
	ajaxUrl: <?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>,
	nonce: <?php echo wp_json_encode( $ihq_portal_auth_login_nonce ); ?>,
	turnstileSiteKey: <?php echo wp_json_encode( $ihq_portal_auth_turnstile ); ?>,
	redirectUrl: <?php echo wp_json_encode( $ihq_portal_auth_redirect ); ?>,
	codeExpiresMinutes: 15,
	telegramClientId: <?php echo (int) $ihq_portal_telegram_client_id; ?>,
	telegramNonce: <?php echo wp_json_encode( $ihq_portal_auth_telegram_nonce ); ?>
};
var ihqAuthLoginSignupToken = '';
var ihqAuthLoginTurnstileWidgetId = null;
var ihqAuthLoginTelegramBusy = false;

function ihqModalAjaxErrMessage(data) {
	if (!data || !data.data) {
		return 'Something went wrong.';
	}
	var d = data.data;
	if (typeof d === 'string') {
		return d;
	}
	if (d.message) {
		return d.message;
	}
	return 'Something went wrong.';
}

function ihqAuthLoginRemoveTurnstile() {
	var host = document.getElementById('auth-login-turnstile');
	if (!host || !IHQ_AUTH_LOGIN.turnstileSiteKey) {
		return;
	}
	if (ihqAuthLoginTurnstileWidgetId !== null && typeof window.turnstile !== 'undefined') {
		try {
			window.turnstile.remove(ihqAuthLoginTurnstileWidgetId);
		} catch (eRm) {}
		ihqAuthLoginTurnstileWidgetId = null;
	}
	host.removeAttribute('data-rendered');
	host.innerHTML = '';
}

function ihqAuthLoginRenderTurnstileWhenNeeded() {
	if (!IHQ_AUTH_LOGIN.turnstileSiteKey) {
		return;
	}
	var el = document.getElementById('auth-login-turnstile');
	if (!el || el.getAttribute('data-rendered') === '1') {
		return;
	}
	if (typeof window.turnstile === 'undefined') {
		window.setTimeout(ihqAuthLoginRenderTurnstileWhenNeeded, 200);
		return;
	}
	ihqAuthLoginTurnstileWidgetId = window.turnstile.render(el, {
		sitekey: IHQ_AUTH_LOGIN.turnstileSiteKey
	});
	el.setAttribute('data-rendered', '1');
}

function ihqAuthLoginResetPanels() {
	ihqAuthLoginSignupToken = '';
	var stepEmail = document.getElementById('auth-login-step-email');
	var stepCode = document.getElementById('auth-login-step-code');
	if (stepEmail) stepEmail.style.display = 'block';
	if (stepCode) stepCode.style.display = 'none';
	var e = document.getElementById('auth-login-email');
	var c = document.getElementById('auth-login-code');
	if (e) e.value = '';
	if (c) c.value = '';
	var er = document.getElementById('auth-login-err');
	var inf = document.getElementById('auth-login-info');
	var cer = document.getElementById('auth-login-code-err');
	if (er) { er.textContent = ''; er.style.display = 'none'; }
	if (inf) { inf.textContent = ''; inf.style.display = 'none'; }
	if (cer) cer.textContent = '';
	var exp = document.getElementById('auth-login-code-expires');
	if (exp) exp.textContent = '';
	var tgErr = document.getElementById('auth-login-telegram-err');
	if (tgErr) { tgErr.textContent = ''; tgErr.style.display = 'none'; }
	ihqAuthLoginTelegramBusy = false;
	ihqAuthLoginRemoveTurnstile();
	window.setTimeout(ihqAuthLoginRenderTurnstileWhenNeeded, 80);
}

function ihqAuthClearTelegramErr() {
	var el = document.getElementById('auth-login-telegram-err');
	if (el) {
		el.textContent = '';
		el.style.display = 'none';
	}
}

function ihqAuthShowTelegramErr(msg) {
	var el = document.getElementById('auth-login-telegram-err');
	if (el) {
		el.textContent = msg || '';
		el.style.display = msg ? 'block' : 'none';
	}
}

function ihqEnsureTelegramLoginScript(next) {
	if (window.Telegram && window.Telegram.Login) {
		next(true);
		return;
	}
	var s = document.createElement('script');
	s.async = true;
	s.src = 'https://telegram.org/js/telegram-login.js';
	s.onload = function () { next(true); };
	s.onerror = function () { next(false); };
	document.head.appendChild(s);
}

function ihqAuthLoginWithTelegram() {
	if (!IHQ_AUTH_LOGIN.telegramClientId || ihqAuthLoginTelegramBusy) {
		return;
	}
	ihqAuthClearTelegramErr();
	ihqAuthLoginTelegramBusy = true;
	var btn = document.getElementById('auth-login-telegram-btn');
	if (btn) btn.disabled = true;
	var fd = new FormData();
	fd.append('action', 'ihq_telegram_login_nonce');
	fd.append('nonce', IHQ_AUTH_LOGIN.telegramNonce);
	fetch(IHQ_AUTH_LOGIN.ajaxUrl, { method: 'POST', body: fd })
		.then(function (r) { return r.json(); })
		.then(function (data) {
			if (!data.success || !data.data || !data.data.server_nonce) {
				ihqAuthLoginTelegramBusy = false;
				if (btn) btn.disabled = false;
				ihqAuthShowTelegramErr(ihqModalAjaxErrMessage(data) || 'Could not start Telegram login.');
				return;
			}
			var serverNonce = data.data.server_nonce;
			ihqEnsureTelegramLoginScript(function (ok) {
				if (!ok || !window.Telegram || !window.Telegram.Login || typeof window.Telegram.Login.auth !== 'function') {
					ihqAuthLoginTelegramBusy = false;
					if (btn) btn.disabled = false;
					ihqAuthShowTelegramErr('Telegram login is unavailable.');
					return;
				}
				window.Telegram.Login.auth(
					{ client_id: IHQ_AUTH_LOGIN.telegramClientId, nonce: serverNonce, lang: 'en' },
					function (result) {
						if (result === false) {
							ihqAuthLoginTelegramBusy = false;
							if (btn) btn.disabled = false;
							return;
						}
						if (!result || result.error || !result.id_token) {
							ihqAuthLoginTelegramBusy = false;
							if (btn) btn.disabled = false;
							ihqAuthShowTelegramErr(result && result.error ? String(result.error) : 'Telegram did not return an ID token.');
							return;
						}
						var fd2 = new FormData();
						fd2.append('action', 'ihq_verify_telegram_id_token');
						fd2.append('nonce', IHQ_AUTH_LOGIN.telegramNonce);
						fd2.append('id_token', result.id_token);
						fetch(IHQ_AUTH_LOGIN.ajaxUrl, { method: 'POST', body: fd2 })
							.then(function (r2) { return r2.json(); })
							.then(function (data2) {
								if (!data2.success || !data2.data || !data2.data.telegram_session_token) {
									ihqAuthLoginTelegramBusy = false;
									if (btn) btn.disabled = false;
									ihqAuthShowTelegramErr(ihqModalAjaxErrMessage(data2) || 'Telegram verification failed.');
									return;
								}
								var fd3 = new FormData();
								fd3.append('action', 'ihq_login_telegram_user');
								fd3.append('nonce', IHQ_AUTH_LOGIN.telegramNonce);
								fd3.append('telegram_session_token', data2.data.telegram_session_token);
								fetch(IHQ_AUTH_LOGIN.ajaxUrl, { method: 'POST', body: fd3 })
									.then(function (r3) { return r3.json(); })
									.then(function (data3) {
										ihqAuthLoginTelegramBusy = false;
										if (btn) btn.disabled = false;
										if (!data3.success || !data3.data || !data3.data.redirect_url) {
											ihqAuthShowTelegramErr(ihqModalAjaxErrMessage(data3) || 'Could not sign in with Telegram.');
											return;
										}
										window.location.href = data3.data.redirect_url;
									})
									.catch(function () {
										ihqAuthLoginTelegramBusy = false;
										if (btn) btn.disabled = false;
										ihqAuthShowTelegramErr('Network error. Please try again.');
									});
							})
							.catch(function () {
								ihqAuthLoginTelegramBusy = false;
								if (btn) btn.disabled = false;
								ihqAuthShowTelegramErr('Network error. Please try again.');
							});
					}
				);
			});
		})
		.catch(function () {
			ihqAuthLoginTelegramBusy = false;
			if (btn) btn.disabled = false;
			ihqAuthShowTelegramErr('Network error. Please try again.');
		});
}

function ihqAuthLoginBackToEmail() {
	ihqAuthLoginRemoveTurnstile();
	ihqAuthLoginSignupToken = '';
	var cer = document.getElementById('auth-login-code-err');
	if (cer) cer.textContent = '';
	var sc = document.getElementById('auth-login-step-code');
	var se = document.getElementById('auth-login-step-email');
	if (sc) sc.style.display = 'none';
	if (se) se.style.display = 'block';
	window.setTimeout(ihqAuthLoginRenderTurnstileWhenNeeded, 80);
}

function ihqAuthLoginSendCode() {
	var errBox = document.getElementById('auth-login-err');
	var infoBox = document.getElementById('auth-login-info');
	if (errBox) { errBox.style.display = 'none'; errBox.textContent = ''; }
	if (infoBox) { infoBox.style.display = 'none'; infoBox.textContent = ''; }
	var emailEl = document.getElementById('auth-login-email');
	if (!emailEl) return;
	var email = emailEl.value.trim();
	if (!email || email.indexOf('@') === -1) {
		if (errBox) {
			errBox.textContent = 'Please enter a valid email address.';
			errBox.style.display = 'block';
		}
		return;
	}
	var tsToken = '';
	if (IHQ_AUTH_LOGIN.turnstileSiteKey) {
		if (typeof window.turnstile === 'undefined' || ihqAuthLoginTurnstileWidgetId === null) {
			if (errBox) {
				errBox.textContent = 'Please wait for the security check to load.';
				errBox.style.display = 'block';
			}
			return;
		}
		tsToken = window.turnstile.getResponse(ihqAuthLoginTurnstileWidgetId) || '';
		if (!tsToken) {
			if (errBox) {
				errBox.textContent = 'Please complete the security verification.';
				errBox.style.display = 'block';
			}
			return;
		}
	}
	var btn = document.getElementById('auth-login-send-btn');
	if (btn) btn.disabled = true;
	var fd = new FormData();
	fd.append('action', 'ihq_send_login_code');
	fd.append('nonce', IHQ_AUTH_LOGIN.nonce);
	fd.append('email', email);
	fd.append('cf-turnstile-response', tsToken);
	fetch(IHQ_AUTH_LOGIN.ajaxUrl, { method: 'POST', body: fd })
		.then(function (r) { return r.json(); })
		.then(function (data) {
			if (btn) btn.disabled = false;
			if (!data.success) {
				if (errBox) {
					errBox.textContent = ihqModalAjaxErrMessage(data);
					errBox.style.display = 'block';
				}
				ihqAuthLoginRemoveTurnstile();
				window.setTimeout(ihqAuthLoginRenderTurnstileWhenNeeded, 80);
				return;
			}
			var d = data.data;
			var msg = d.message || 'If that email matches an account, you will receive a code.';
			if (infoBox) {
				infoBox.textContent = msg;
				infoBox.style.display = 'block';
			}
			if (!d.signup_token || d.skipped) {
				ihqAuthLoginRemoveTurnstile();
				window.setTimeout(ihqAuthLoginRenderTurnstileWhenNeeded, 80);
				return;
			}
			ihqAuthLoginSignupToken = d.signup_token;
			var minutes = d.expires_minutes || IHQ_AUTH_LOGIN.codeExpiresMinutes;
			var expEl = document.getElementById('auth-login-code-expires');
			if (expEl) {
				expEl.textContent = 'Code expires in ' + minutes + ' minute' + (minutes === 1 ? '' : 's') + '.';
			}
			var codeErr = document.getElementById('auth-login-code-err');
			if (codeErr) codeErr.textContent = '';
			var codeInput = document.getElementById('auth-login-code');
			if (codeInput) codeInput.value = '';
			var se = document.getElementById('auth-login-step-email');
			var sc = document.getElementById('auth-login-step-code');
			if (se) se.style.display = 'none';
			if (sc) sc.style.display = 'block';
		})
		.catch(function () {
			if (btn) btn.disabled = false;
			if (errBox) {
				errBox.textContent = 'Network error. Please try again.';
				errBox.style.display = 'block';
			}
		});
}

function ihqAuthLoginVerify() {
	var errEl = document.getElementById('auth-login-code-err');
	if (errEl) errEl.textContent = '';
	var codeInput = document.getElementById('auth-login-code');
	if (!codeInput) return;
	var raw = codeInput.value.replace(/\D/g, '');
	if (raw.length !== 6) {
		if (errEl) errEl.textContent = 'Enter the 6-digit code from your email.';
		return;
	}
	if (!ihqAuthLoginSignupToken) {
		if (errEl) errEl.textContent = 'Send a code first.';
		ihqAuthLoginBackToEmail();
		return;
	}
	var btn = document.getElementById('auth-login-verify-btn');
	if (btn) btn.disabled = true;
	var fd = new FormData();
	fd.append('action', 'ihq_verify_login_code');
	fd.append('nonce', IHQ_AUTH_LOGIN.nonce);
	fd.append('signup_token', ihqAuthLoginSignupToken);
	fd.append('code', raw);
	fd.append('redirect_url', IHQ_AUTH_LOGIN.redirectUrl);
	fd.append('country_iso', window.ihqResolveClientCountryIsoAlpha2());
	fetch(IHQ_AUTH_LOGIN.ajaxUrl, { method: 'POST', body: fd })
		.then(function (r) { return r.json(); })
		.then(function (data) {
			if (btn) btn.disabled = false;
			if (!data.success) {
				if (errEl) errEl.textContent = ihqModalAjaxErrMessage(data);
				return;
			}
			window.location.href = data.data.redirect_url || IHQ_AUTH_LOGIN.redirectUrl;
		})
		.catch(function () {
			if (btn) btn.disabled = false;
			if (errEl) errEl.textContent = 'Network error. Please try again.';
		});
}

function openAuthModal(tab) {
	var modal = document.getElementById('authModal');
	if (!modal) return;
	modal.classList.add('open');
	modal.setAttribute('aria-hidden', 'false');
	document.body.style.overflow = 'hidden';
	if (tab) switchAuthTab(tab);
	window.setTimeout(ihqAuthLoginRenderTurnstileWhenNeeded, 100);
}

function closeAuthModal() {
	var modal = document.getElementById('authModal');
	if (modal) {
		modal.classList.remove('open');
		modal.setAttribute('aria-hidden', 'true');
	}
	document.body.style.overflow = '';
}

function switchAuthTab(tab) {
	var loginPane = document.getElementById('auth-pane-login');
	var registerPane = document.getElementById('auth-pane-register');
	var loginTab = document.getElementById('auth-tab-login');
	var registerTab = document.getElementById('auth-tab-register');
	if (!loginPane) return;
	if (tab === 'login') {
		loginPane.classList.add('active');
		if (registerPane) registerPane.classList.remove('active');
		if (loginTab) loginTab.classList.add('active');
		if (registerTab) registerTab.classList.remove('active');
		ihqAuthLoginResetPanels();
	} else {
		loginPane.classList.remove('active');
		if (registerPane) registerPane.classList.add('active');
		if (loginTab) loginTab.classList.remove('active');
		if (registerTab) registerTab.classList.add('active');
	}
}

function toggleAuthCh(method, cb) {
	var input = document.getElementById('auth-chi-' + method);
	if (input) input.classList.toggle('show', cb.checked);
	var anyChecked = document.querySelectorAll('.auth-ch-group input[type=checkbox]:checked').length > 0;
	var challengeSection = document.getElementById('auth-challenge-section');
	if (challengeSection) challengeSection.style.display = anyChecked ? 'block' : 'none';
}

function selectAuthComp(value, card) {
	document.querySelectorAll('.auth-comp-card').forEach(function(c) { c.classList.remove('sel'); });
	card.classList.add('sel');
	var radio = card.querySelector('input[type=radio]');
	if (radio) radio.checked = true;
	var geniusSection = document.getElementById('auth-genius-section');
	if (geniusSection) geniusSection.style.display = 'block';
}

function handleAuthRegister(e) {
	e.preventDefault();

	var email = document.getElementById('auth-reg-email') ? document.getElementById('auth-reg-email').value : '';
	var firstName = document.getElementById('auth-reg-first') ? document.getElementById('auth-reg-first').value : '';
	var lastName = document.getElementById('auth-reg-last') ? document.getElementById('auth-reg-last').value : '';
	var platformHandle = document.getElementById('auth-reg-handle') ? document.getElementById('auth-reg-handle').value : '';
	var challengeType = document.querySelector('input[name="auth_challenge_type"]:checked');
	var errBox = document.getElementById('auth-reg-err');
	var btn = document.getElementById('auth-reg-btn');

	if (!errBox || !btn) return;

	errBox.style.display = 'none';

	var checkedMethods = document.querySelectorAll('.auth-ch-group input[type=checkbox]:checked');
	if (checkedMethods.length === 0) { errBox.textContent = 'Please select at least one communication method.'; errBox.style.display = 'block'; return; }
	if (!email) { errBox.textContent = 'Please enter your email address.'; errBox.style.display = 'block'; return; }
	if (!firstName || !lastName) { errBox.textContent = 'Please enter your first and last name.'; errBox.style.display = 'block'; return; }

	var methodsData = {};
	var allFilled = true;
	checkedMethods.forEach(function(cb) {
		var method = cb.value;
		var inputEl = document.getElementById('auth-chi-' + method);
		var val = inputEl ? inputEl.value.trim() : '';
		if (!val) { allFilled = false; } else { methodsData[method] = val; }
	});
	if (!allFilled) { errBox.textContent = 'Please enter contact info for all selected methods.'; errBox.style.display = 'block'; return; }
	if (!challengeType) { errBox.textContent = 'Please select a challenge type.'; errBox.style.display = 'block'; return; }

	btn.disabled = true;
	btn.textContent = 'Sending…';

	var fd = new FormData();
	fd.append('action', 'send_verification_email');
	fd.append('email', email);
	fd.append('first_name', firstName);
	fd.append('last_name', lastName);
	fd.append('platform_handle', platformHandle);
	fd.append('comm_methods', JSON.stringify(methodsData));
	fd.append('challenge_type', challengeType.value);
	var compPrefEl = document.getElementById('auth-competition-preferences');
	fd.append('competition_preferences', compPrefEl ? compPrefEl.value : '');
	fd.append('country_iso', window.ihqResolveClientCountryIsoAlpha2());
	fd.append('nonce', '<?php echo esc_js( wp_create_nonce( 'verification_email_nonce' ) ); ?>');

	fetch(<?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>, { method: 'POST', body: fd })
		.then(function(r) { return r.json(); })
		.then(function(data) {
			if (data.success) {
				var modal = document.getElementById('authModal');
				var inner = modal ? modal.querySelector('.auth-modal') : null;
				if (inner) {
					inner.innerHTML = '<button type="button" class="auth-modal-x" onclick="closeAuthModal()" aria-label="Close">✕</button>'
						+ '<div style="text-align:center;padding:32px 0">'
						+ '<div style="font-size:2.5rem;color:var(--gl);margin-bottom:18px">◈</div>'
						+ '<h3 style="font-family:\'Cinzel\',serif;font-size:1.8rem;color:var(--white);margin-bottom:14px">Thank You!</h3>'
						+ '<p style="font-family:\'Be Vietnam Pro\',sans-serif;font-size:1rem;color:var(--warm);line-height:2">Thank you for starting the conversation!<br>Please check your email to verify your address.</p>'
						+ '</div>';
				}
			} else {
				errBox.textContent = 'There was an error. Please try again.';
				errBox.style.display = 'block';
				btn.disabled = false;
				btn.textContent = 'Send Verification Email';
			}
		})
		.catch(function() {
			errBox.textContent = 'Network error. Please try again.';
			errBox.style.display = 'block';
			btn.disabled = false;
			btn.textContent = 'Send Verification Email';
		});
}

document.addEventListener('DOMContentLoaded', function() {
	var authModal = document.getElementById('authModal');
	if (authModal) {
		authModal.addEventListener('click', function(e) {
			if (e.target === this) closeAuthModal();
		});
	}

	document.querySelectorAll('.portal-header-auth-trigger').forEach(function(btn) {
		btn.addEventListener('click', function(e) {
			e.preventDefault();
			var tab = btn.getAttribute('data-auth-tab') || 'login';
			openAuthModal(tab);
		});
	});

	var urlParams = new URLSearchParams(window.location.search);
	if (urlParams.get('welcome') === 'true') {
		openAuthModal('login');
	}

	window.setTimeout(ihqAuthLoginRenderTurnstileWhenNeeded, 300);
});
</script>
