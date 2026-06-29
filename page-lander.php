<?php
/**
 * Template Name: Lander
 * Description: Expanded landing page — Figma design 37031-3140.
 *
 * @package influencer-hq
 */

add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_style( 'lander-style', get_template_directory_uri() . '/css/lander-style.css' );
	wp_enqueue_style( 'homepage-style-claude', get_template_directory_uri() . '/css/homepage-style-claude.css' );
} );

/**
 * Lander-only tracking (removed from global header in 934ab9b / ENGR-5469).
 * Meta Pixel was loaded via GTM-PVNC3K92, not a direct fbq snippet in this repo.
 */
add_action(
	'wp_head',
	function () {
		?>
<meta name="facebook-domain-verification" content="duql6iqb4luzf8d44y9uvsdtvlb1tl" />
<!-- Google Tag Manager (lander only) -->
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','GTM-PVNC3K92');</script>
<!-- End Google Tag Manager -->
<!-- Clixtell Tracking Code -->
<script type="text/javascript">
var script=document.createElement('script');
var prefix=document.location.protocol;
script.async=true;script.type='text/javascript';
var target=prefix + '//scripts.clixtell.com/track.js';
script.src=target;var elem=document.head;
elem.appendChild(script);
</script>
		<?php
	},
	2
);

add_action(
	'wp_body_open',
	function () {
		?>
<!-- Google Tag Manager (noscript) -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-PVNC3K92"
height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<!-- End Google Tag Manager (noscript) -->
		<?php
	},
	2
);

get_header();

$ihq_modal_reg_nonce       = wp_create_nonce( 'ihq_reg_code_nonce' );
$ihq_modal_login_nonce     = wp_create_nonce( 'ihq_login_code_nonce' );
$ihq_telegram_login_pubkey_nonce = wp_create_nonce( 'ihq_telegram_login_pubkey' );
$ihq_cf_country_iso_alpha2 = function_exists( 'ihq_get_cloudflare_country_iso_alpha2' )
	? ihq_get_cloudflare_country_iso_alpha2()
	: 'US';
$ihq_telegram_client_id    = 0;
if ( defined( 'IHQ_TELEGRAM_LOGIN_CLIENT_ID' ) && preg_match( '/^\d+$/', (string) IHQ_TELEGRAM_LOGIN_CLIENT_ID ) ) {
	$ihq_telegram_client_id = (int) IHQ_TELEGRAM_LOGIN_CLIENT_ID;
}
$ihq_modal_telegram_lbl_default = __( 'We\'ll note your @username; code still arrives by email', 'influencer-hq' );
if ( $ihq_telegram_client_id > 0 ) {
	$ihq_modal_telegram_lbl_default = __( 'Sign in with Telegram to verify your @username — code still arrives by email', 'influencer-hq' );
}
$ihq_modal_telegram_verified_lbl = __( 'Verified — ', 'influencer-hq' );
$ihq_turnstile_site_modal  = '';
if ( function_exists( 'ihq_turnstile_is_configured' ) && ihq_turnstile_is_configured() && defined( 'CF_TURNSTILE_SITE_KEY' ) ) {
	$ihq_turnstile_site_modal = CF_TURNSTILE_SITE_KEY;
}

$ihq_modal_comm_placeholder = __( 'handle or URL', 'influencer-hq' );
$ihq_modal_comm_methods_left  = array(
	array( 'key' => 'botim', 'label' => 'Botim' ),
	array( 'key' => 'email', 'label' => 'Email' ),
	array( 'key' => 'imo', 'label' => 'IMO' ),
	array( 'key' => 'kakaotalk', 'label' => 'KakaoTalk' ),
	array( 'key' => 'line', 'label' => 'Line' ),
	array( 'key' => 'qq', 'label' => 'QQ' ),
	array( 'key' => 'signal', 'label' => 'Signal' ),
);
$ihq_modal_comm_methods_right = array(
	array( 'key' => 'sms', 'label' => 'SMS' ),
	array( 'key' => 'telegram', 'label' => 'Telegram' ),
	array( 'key' => 'viber', 'label' => 'Viber' ),
	array( 'key' => 'wechat', 'label' => 'WeChat' ),
	array( 'key' => 'whatsapp', 'label' => 'WhatsApp' ),
	array( 'key' => 'zalo', 'label' => 'Zalo' ),
);
$ihq_modal_comm_methods_all = array_merge( $ihq_modal_comm_methods_left, $ihq_modal_comm_methods_right );

$ihq_modal_social_placeholder = __( 'handle or URL', 'influencer-hq' );
$ihq_modal_social_platforms   = array(
	array( 'key' => 'kick', 'label' => 'Kick' ),
	array( 'key' => 'facebook', 'label' => 'Facebook' ),
	array( 'key' => 'reddit', 'label' => 'Reddit' ),
	array( 'key' => 'tiktok', 'label' => 'TikTok' ),
	array( 'key' => 'naver-blog', 'label' => 'Naver Blog' ),
	array( 'key' => 'rednote', 'label' => 'Rednote' ),
	array( 'key' => 'bilibili', 'label' => 'Bilibili' ),
	array( 'key' => 'x', 'label' => 'X' ),
	array( 'key' => 'kakao-business', 'label' => 'Kakao B' ),
	array( 'key' => 'twitch', 'label' => 'Twitch' ),
	array( 'key' => 'instagram', 'label' => 'Instagram' ),
	array( 'key' => 'telegram-channel', 'label' => 'Telegram' ),
	array( 'key' => 'ameba', 'label' => 'Ameba' ),
	array( 'key' => 'line', 'label' => 'LINE' ),
	array( 'key' => 'youtube', 'label' => 'YouTube' ),
);
?>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,300;1,400&family=Inter:wght@400;700;900&display=swap" rel="stylesheet">
<?php if ( $ihq_turnstile_site_modal !== '' ) : ?>
<script src="https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit" async defer></script>
<?php endif; ?>

<style>
/* Shared modal styles (same as portal-home) */
:root {
  color-scheme: dark;
  --gl: #FDD65B;
  --bg3: #161612;
  --warm: #EAD9B0;
}
.field-error {
  box-sizing:border-box;border:2px solid rgba(255,75,75,.95)!important;
  box-shadow:0 0 0 4px rgba(255,75,75,.16);
  animation:field-error-pulse .9s ease both;
}
@keyframes field-error-pulse {
  0%,100%{transform:translateX(0)}15%{transform:translateX(-4px)}
  30%{transform:translateX(4px)}45%{transform:translateX(-2px)}
  60%{transform:translateX(2px)}75%{transform:translateX(-1px)}
}
.modal-reg-field{margin-bottom:16px;text-align:left}
.modal-reg-field label{display:block;font-family:'Be Vietnam Pro',sans-serif;font-size:.75rem;letter-spacing:.12em;text-transform:uppercase;color:var(--gl);margin-bottom:8px}
.modal-reg-field input{width:100%;box-sizing:border-box;padding:12px 14px;border-radius:8px;border:1px solid rgba(240,201,58,.25);background:var(--bg3);color:#fff;font-family:'Be Vietnam Pro',sans-serif;font-size:1rem}
.modal-reg-field input::placeholder{color:rgba(234,217,176,.45)}
.modal-reg-actions{display:flex;flex-wrap:wrap;gap:10px;margin-top:20px;justify-content:center}
.modal-reg-actions .send-btn,.modal-reg-actions .btn-modal-back{min-width:140px}
.btn-modal-back{background:transparent;border:1px solid rgba(240,201,58,.45);color:var(--warm);font-family:'Be Vietnam Pro',sans-serif;padding:14px 24px;border-radius:10px;cursor:pointer;font-size:.9rem}
.btn-modal-back:hover{border-color:var(--gl);color:var(--gl)}
.modal-code-msg{font-family:'Be Vietnam Pro',sans-serif;font-size:.95rem;color:var(--warm);line-height:1.75;margin:12px 0;text-align:center}
.modal-code-msg strong{color:var(--gl)}
#modal-reg-turnstile{display:flex;justify-content:center;margin:16px 0}
#modal-code-expires-note{font-size:.88rem;color:var(--gl);text-align:center;margin-top:8px}
.modal-step-err{font-family:'Be Vietnam Pro',sans-serif;font-size:.85rem;color:#f85149;text-align:center;margin:10px 0;min-height:1.2em}
.ln-concierge-img {
  width: auto;
  height: clamp(220px, 28vw, 320px);
  /* max-width: min(100%, 360px); */
  object-fit: contain;
  object-position: center top;
}
</style>

<main id="primary" class="site-main">

<!-- ═══ NAV ══════════════════════════════════════════════════════ -->
<nav class="ln-nav">
  <a class="logo" href="<?php echo esc_url( home_url( '/' ) ); ?>">
    <img src="<?php echo get_template_directory_uri(); ?>/images/logo-home-claude.jpg" alt="influencerHQ" style="max-height:52px;width:auto;">
  </a>
</nav>

<!-- ═══ HERO ══════════════════════════════════════════════════════ -->
<section class="ln-hero">

  <!-- Concierge band -->
  <div class="ln-concierge-band">
    <div class="ln-concierge-inner">
      <div class="ln-concierge-left">
        <span class="ln-concierge-label">Conversations should be easy.</span>
      </div>
      <div class="ln-concierge-center">
        <img class="ln-concierge-img"
             src="<?php echo esc_url( get_template_directory_uri() . '/images/concierge.png' ); ?>"
             alt="<?php esc_attr_e( 'Executive Concierge', 'influencer-hq' ); ?>">
        <p class="ln-concierge-title"><?php esc_html_e( 'Executive Concierge', 'influencer-hq' ); ?></p>
      </div>
      <div class="ln-concierge-right">
        <a href="#" class="ln-concierge-ask" id="landerConciergeBtn"><?php esc_html_e( 'Ask Me Anything', 'influencer-hq' ); ?></a>
      </div>
    </div>
  </div>

  <!-- ═══ OUR BELIEFS (part of hero) ═════════════════════════════ -->
  <div class="ln-section ln-beliefs ln-beliefs--hero">
    <div class="ln-section-center">
      <h2 class="ln-section-title"><?php esc_html_e( 'Our Beliefs', 'influencer-hq' ); ?></h2>
    </div>

    <div class="ln-beliefs-grid">
      <div class="ln-belief-col">
        <img src="<?php echo esc_url( get_template_directory_uri() . '/images/eqyuty.png' ); ?>"
             alt="" class="ln-belief-icon">
        <p class="ln-belief-item"><?php esc_html_e( 'Influence deserves more than short-term payouts.', 'influencer-hq' ); ?></p>
        <?php if ( ! is_user_logged_in() ) : ?>
          <button type="button" class="btn-arena ln-belief-cta" onclick="openModal()"><?php esc_html_e( 'TELL ME MORE!', 'influencer-hq' ); ?></button>
        <?php endif; ?>
      </div>

      <div class="ln-belief-col">
        <img src="<?php echo esc_url( get_template_directory_uri() . '/images/performance.png' ); ?>"
             alt="" class="ln-belief-icon">
        <p class="ln-belief-item"><?php esc_html_e( 'When your influence grows a platform, you should share in the value created by that growth.', 'influencer-hq' ); ?></p>
      </div>

      <div class="ln-belief-col">
        <img src="<?php echo esc_url( get_template_directory_uri() . '/images/cert.png' ); ?>"
             alt="" class="ln-belief-icon">
        <p class="ln-belief-item"><?php esc_html_e( 'Influencers deserve the opportunity to share in ownership.', 'influencer-hq' ); ?></p>
        <?php if ( ! is_user_logged_in() ) : ?>
          <button type="button" class="btn-arena ln-belief-cta" onclick="openModal()"><?php esc_html_e( 'COUNT ME IN!', 'influencer-hq' ); ?></button>
        <?php endif; ?>
      </div>
    </div>

    <?php if ( is_user_logged_in() ) : ?>
      <div class="ln-hero-logged-in-cta">
        <a class="btn-arena" href="<?php echo esc_url( home_url( '/portal/portal-home/' ) ); ?>"><?php esc_html_e( 'Go to portal', 'influencer-hq' ); ?></a>
      </div>
    <?php endif; ?>
  </div>

</section>

<!-- ═══ OWNERSHIP ════════════════════════════════════════════════ -->
<div class="ln-section ln-ownership">
  <h2 class="ln-ownership-title">Ownership</h2>

  <!-- Magic Johnson -->
  <div class="ln-story">
    <img class="ln-story-img"
         src="<?php echo esc_url( get_template_directory_uri() . '/images/magic-j.jpg' ); ?>"
         alt="Magic Johnson">
    <div class="ln-story-body">
      <div class="ln-story-name">Magic Johnson</div>
      <p class="ln-story-text">
        The biggest missed equity-for-influence deal in history was Nike's 11 cent stock deal offered to NBA superstar Magic Johnson. Magic chose Adidas' $100,000 cash instead.<br><br>
        Today's value of the Nike stock Magic turned down? $5.4 billion.<br><br>
        — and that's why equity-for-influence changes everything.
      </p>
    </div>
  </div>

  <!-- Alix Earle -->
  <div class="ln-story reverse">
    <div class="ln-story-body">
      <div class="ln-story-name">Alix Earle</div>
      <p class="ln-story-text">
        American Influencer Alix Earle chose an ownership-based partnership with Poppi instead of traditional cash. When Poppi was acquired by PepsiCo for well over a billion dollars, her choice became a defining moment in the creator economy.<br><br>
        Ownership matters — and influence deserves the chance to share in it.
      </p>
    </div>
    <img class="ln-story-img"
         src="<?php echo esc_url( get_template_directory_uri() . '/images/alix-hair.jpg' ); ?>"
         alt="Alix Earle">
  </div>

  <!-- BTS -->
  <div class="ln-story">
    <img class="ln-story-img"
         src="<?php echo esc_url( get_template_directory_uri() . '/images/bts.jpg' ); ?>"
         alt="<?php esc_attr_e( 'BTS', 'influencer-hq' ); ?>">
    <div class="ln-story-body">
      <div class="ln-story-name">BTS</div>
      <p class="ln-story-text">
        Each of the seven members of BTS received 68,000 equity shares in the HYBE record label ahead of its public offering.<br><br>
        That equity grew to $103.6 million.
      </p>
    </div>
  </div>

  <!-- COUNT ME IN CTA -->
  <div class="ln-ownership-cta">
    <?php if ( is_user_logged_in() ) : ?>
      <a class="btn-arena" href="<?php echo esc_url( home_url( '/portal/portal-home/' ) ); ?>"><?php esc_html_e( 'Go to portal', 'influencer-hq' ); ?></a>
    <?php else : ?>
      <button type="button" class="btn-arena" onclick="openModal()"><?php esc_html_e( 'COUNT ME IN!', 'influencer-hq' ); ?></button>
    <?php endif; ?>
  </div>
</div>

<!-- ═══ COMPETE TEASER ═══════════════════════════════════════════ -->
<div class="ln-section ln-compete-teaser">
  <h3 class="ln-compete-headline">
    <span><?php esc_html_e( 'Don\'t Just Post!', 'influencer-hq' ); ?></span>
    <span><?php esc_html_e( 'Compete!', 'influencer-hq' ); ?></span>
  </h3>
</div>

<!-- ═══ INTERNATIONAL COMPETITION ════════════════════════════════ -->
<div class="ln-section ln-intl">
  <div class="ln-section-center">
    <h2 class="ln-section-title"><?php esc_html_e( 'International Competition', 'influencer-hq' ); ?></h2>
  </div>
  <p class="ln-intl-sub">
    <?php esc_html_e( 'Influencers are automatically invited to appear on our partners\' International Competition.', 'influencer-hq' ); ?>
  </p>

  <div class="ln-intl-stage ln-intl-stage--triple">
    <img class="ln-intl-torch"
         src="<?php echo esc_url( get_template_directory_uri() . '/images/lander-torch.png' ); ?>"
         alt="">
    <div class="ln-intl-stage-copy">
      <p><?php esc_html_e( 'Influence deserves a stage worthy of that legacy — and now, it has one.', 'influencer-hq' ); ?></p>
      <p><?php esc_html_e( 'Elegance that never goes out of style. Prestige that spans centuries.', 'influencer-hq' ); ?></p>
    </div>
    <div class="ln-intl-miami">
      <img src="<?php echo esc_url( get_template_directory_uri() . '/images/lander-miami-worldcup.jpg' ); ?>"
           alt="<?php esc_attr_e( 'Miami skyline — 2026 World Cup', 'influencer-hq' ); ?>">
    </div>
  </div>

  <div class="ln-comp-cards">
    <div class="ln-comp-col">
      <div class="ln-comp-card ln-comp-card--world">
        <div class="ln-comp-card-title"><?php esc_html_e( 'World', 'influencer-hq' ); ?></div>
        <p class="ln-comp-card-desc"><?php esc_html_e( 'Compete against Influencers and their followers from across the world in a weekly World competition.', 'influencer-hq' ); ?></p>
      </div>
      <div class="ln-rating-row">
        <button type="button" class="ln-rating-btn" data-group="world" data-val="1" onclick="lnRate(this)">1</button>
        <button type="button" class="ln-rating-btn" data-group="world" data-val="2" onclick="lnRate(this)">2</button>
        <button type="button" class="ln-rating-btn" data-group="world" data-val="3" onclick="lnRate(this)">3</button>
      </div>
    </div>

    <div class="ln-comp-col">
      <div class="ln-comp-card ln-comp-card--comm">
        <div class="ln-comp-card-title"><?php esc_html_e( 'Community', 'influencer-hq' ); ?></div>
        <p class="ln-comp-card-desc"><?php esc_html_e( 'A weekly competition for all of your followers.', 'influencer-hq' ); ?></p>
        <div class="ln-rating-row ln-rating-row--in-card">
          <button type="button" class="ln-rating-btn" data-group="community" data-val="1" onclick="lnRate(this)">1</button>
          <button type="button" class="ln-rating-btn" data-group="community" data-val="2" onclick="lnRate(this)">2</button>
          <button type="button" class="ln-rating-btn" data-group="community" data-val="3" onclick="lnRate(this)">3</button>
        </div>
      </div>
    </div>

    <div class="ln-comp-col">
      <div class="ln-comp-card ln-comp-card--private">
        <div class="ln-comp-card-title"><?php esc_html_e( 'Private', 'influencer-hq' ); ?></div>
        <p class="ln-comp-card-desc"><?php esc_html_e( 'Invite a single Influencer friend and their followers to compete against you and your followers in a 24-hour event.', 'influencer-hq' ); ?></p>
      </div>
      <div class="ln-rating-row">
        <button type="button" class="ln-rating-btn" data-group="private" data-val="1" onclick="lnRate(this)">1</button>
        <button type="button" class="ln-rating-btn" data-group="private" data-val="2" onclick="lnRate(this)">2</button>
        <button type="button" class="ln-rating-btn" data-group="private" data-val="3" onclick="lnRate(this)">3</button>
      </div>
    </div>
  </div>

  <input type="hidden" id="ln-comp-rating-world"     value="">
  <input type="hidden" id="ln-comp-rating-community" value="">
  <input type="hidden" id="ln-comp-rating-private"   value="">

  <p class="ln-rating-note">
    <?php esc_html_e( 'Rank your level of Interest for each of these competition types.', 'influencer-hq' ); ?>
  </p>

  <div class="ln-intl-cta">
    <?php if ( is_user_logged_in() ) : ?>
      <a class="btn-arena" href="<?php echo esc_url( home_url( '/portal/portal-home/' ) ); ?>"><?php esc_html_e( 'Go to portal', 'influencer-hq' ); ?></a>
    <?php else : ?>
      <button type="button" class="btn-arena" onclick="openModal()"><?php esc_html_e( 'COUNT ME IN!', 'influencer-hq' ); ?></button>
    <?php endif; ?>
  </div>
</div>

<!-- ═══ INFLUENCE / OWNERSHIP (STREAMING) ══════════════════════════ -->
<div class="ln-section ln-spark-fire">
  <div class="ln-spark-fire-grid">
    <div class="ln-spark-fire-col">
      <h2 class="ln-spark-fire-title"><?php esc_html_e( 'Influence Is The Spark', 'influencer-hq' ); ?></h2>
      <div class="ln-stream-card">
        <img src="<?php echo esc_url( get_template_directory_uri() . '/images/girl-stream.png' ); ?>"
             alt="<?php esc_attr_e( 'Influencer streaming', 'influencer-hq' ); ?>">
      </div>
      <?php if ( is_user_logged_in() ) : ?>
        <a class="btn-arena dim" href="<?php echo esc_url( home_url( '/portal/portal-home/' ) ); ?>"><?php esc_html_e( 'Go to portal', 'influencer-hq' ); ?></a>
      <?php else : ?>
        <button type="button" class="btn-arena dim" onclick="openModal()"><?php esc_html_e( 'TELL ME MORE!', 'influencer-hq' ); ?></button>
      <?php endif; ?>
    </div>
    <div class="ln-spark-fire-col">
      <h2 class="ln-spark-fire-title"><?php esc_html_e( 'Ownership Is The Fire', 'influencer-hq' ); ?></h2>
      <div class="ln-stream-card">
        <img src="<?php echo esc_url( get_template_directory_uri() . '/images/boy-stream.png' ); ?>"
             alt="<?php esc_attr_e( 'Influencer streaming', 'influencer-hq' ); ?>">
      </div>
      <?php if ( is_user_logged_in() ) : ?>
        <a class="btn-arena dim" href="<?php echo esc_url( home_url( '/portal/portal-home/' ) ); ?>"><?php esc_html_e( 'Go to portal', 'influencer-hq' ); ?></a>
      <?php else : ?>
        <button type="button" class="btn-arena dim" onclick="openModal()"><?php esc_html_e( 'COUNT ME IN!', 'influencer-hq' ); ?></button>
      <?php endif; ?>
    </div>
  </div>

  <p class="ln-compete-sub">
    <?php esc_html_e( 'Rally your followers into action.', 'influencer-hq' ); ?><br>
    <?php esc_html_e( 'Your community competes as a unit — every invite, every interaction earns equity.', 'influencer-hq' ); ?>
  </p>

  <p class="ln-equity-note">
    <?php esc_html_e( 'Streaming is optional, not required.', 'influencer-hq' ); ?><br>
    <?php esc_html_e( 'You can fully participate and earn equity without streaming.', 'influencer-hq' ); ?>
  </p>
</div>

<!-- ═══ FINAL BAND ════════════════════════════════════════════════ -->
<div class="ln-final-band">
  <h2 class="ln-final-title">
    Your influence is<br>
    <em style="color:var(--gl);font-style:italic">worth more than a campaign.</em>
  </h2>
  <p style="font-family:'Be Vietnam Pro',sans-serif;color:var(--warm);margin-bottom:32px;font-size:1.05rem;line-height:1.8">
    We're ready when you are.<br>Reach us through the channel that works best for you.
  </p>
  <div id="cfinal">
    <?php if ( is_user_logged_in() ) : ?>
      <a class="btn-arena" href="<?php echo esc_url( home_url( '/portal/portal-home/' ) ); ?>">Go to portal</a>
    <?php else : ?>
      <button class="btn-arena" onclick="openModal('cfinal')">Yes — Let's Start the Conversation</button>
    <?php endif; ?>
  </div>
</div>

<footer class="ln-footer">
  <p>© <?php echo esc_html( date( 'Y' ) ); ?> InfluencerHQ · All Rights Reserved</p>
</footer>

<!-- ═══════════════════════════════════════════════════════════════
     MODALS  (identical to page-portal-home-claude)
     ═══════════════════════════════════════════════════════════════ -->

<!-- Contact / Competition Modal -->
<div class="overlay" id="mainModal">
  <div class="modal">
    <button class="modal-x" onclick="closeModal()">✕</button>
    <div class="mstep on" id="ms1">
      <div class="modal-comm-thanks" id="modal-comm-thanks" hidden>
        <p class="modal-comm-thanks-msg"><?php esc_html_e( 'Thank you for your submission, your message was received', 'influencer-hq' ); ?></p>
      </div>
      <div id="modal-comm-form-body">
      <h3 class="m-title m-title--conversation"><?php esc_html_e( "Let's Start The Conversation", 'influencer-hq' ); ?></h3>
      <p class="m-lede"><?php esc_html_e( 'Please give us your favorite methods of communication.', 'influencer-hq' ); ?></p>
      <div class="modal-comm-pick" id="modal-comm-pick">
        <div class="modal-comm-cols" role="group" aria-label="<?php esc_attr_e( 'Favorite methods of communication', 'influencer-hq' ); ?>">
          <div class="modal-comm-col">
            <?php foreach ( $ihq_modal_comm_methods_left as $ihq_comm ) : ?>
            <label class="modal-comm-option" id="modal-comm-row-<?php echo esc_attr( $ihq_comm['key'] ); ?>" for="modal-comm-<?php echo esc_attr( $ihq_comm['key'] ); ?>">
              <input
                type="checkbox"
                id="modal-comm-<?php echo esc_attr( $ihq_comm['key'] ); ?>"
                data-comm-key="<?php echo esc_attr( $ihq_comm['key'] ); ?>"
                autocomplete="off"
                aria-label="<?php echo esc_attr( $ihq_comm['label'] ); ?>"
              >
              <span class="modal-comm-option-name"><?php echo esc_html( $ihq_comm['label'] ); ?></span>
            </label>
            <?php endforeach; ?>
          </div>
          <div class="modal-comm-col">
            <?php foreach ( $ihq_modal_comm_methods_right as $ihq_comm ) : ?>
            <label class="modal-comm-option" id="modal-comm-row-<?php echo esc_attr( $ihq_comm['key'] ); ?>" for="modal-comm-<?php echo esc_attr( $ihq_comm['key'] ); ?>">
              <input
                type="checkbox"
                id="modal-comm-<?php echo esc_attr( $ihq_comm['key'] ); ?>"
                data-comm-key="<?php echo esc_attr( $ihq_comm['key'] ); ?>"
                autocomplete="off"
                aria-label="<?php echo esc_attr( $ihq_comm['label'] ); ?>"
              >
              <span class="modal-comm-option-name"><?php echo esc_html( $ihq_comm['label'] ); ?></span>
            </label>
            <?php endforeach; ?>
          </div>
        </div>
        <p class="modal-comm-err" id="modal-comm-err" role="alert"></p>
      </div>
      <p class="modal-comm-tg-err" id="modal-comm-tg-err"></p>
      <div class="modal-comm-inputs" id="modal-comm-inputs-panel">
        <?php foreach ( $ihq_modal_comm_methods_all as $ihq_comm ) : ?>
        <div class="modal-comm-input-row" id="modal-comm-entry-<?php echo esc_attr( $ihq_comm['key'] ); ?>" hidden>
          <span class="modal-comm-input-label"><?php echo esc_html( strtoupper( $ihq_comm['label'] ) ); ?></span>
          <input
            class="modal-comm-handle-input"
            type="text"
            id="modal-comm-input-<?php echo esc_attr( $ihq_comm['key'] ); ?>"
            data-comm-key="<?php echo esc_attr( $ihq_comm['key'] ); ?>"
            placeholder="<?php echo esc_attr( $ihq_modal_comm_placeholder ); ?>"
            aria-label="<?php echo esc_attr( $ihq_comm['label'] ); ?>"
          >
        </div>
        <?php endforeach; ?>
      </div>

      <div class="modal-social-section modal-comm-deferred">
        <h4 class="modal-social-heading"><?php esc_html_e( 'Social Media You Post On', 'influencer-hq' ); ?></h4>
        <div class="social-grid" role="group" aria-label="<?php esc_attr_e( 'Social media platforms', 'influencer-hq' ); ?>">
          <?php foreach ( $ihq_modal_social_platforms as $ihq_social ) : ?>
          <button
            type="button"
            class="social-grid-item"
            id="social-grid-<?php echo esc_attr( $ihq_social['key'] ); ?>"
            data-social-key="<?php echo esc_attr( $ihq_social['key'] ); ?>"
            aria-pressed="false"
            onclick="ihqToggleSocialPlatform('<?php echo esc_js( $ihq_social['key'] ); ?>')"
          ><?php echo esc_html( $ihq_social['label'] ); ?></button>
          <?php endforeach; ?>
        </div>
        <div class="social-inputs" id="social-inputs-panel">
          <?php foreach ( $ihq_modal_social_platforms as $ihq_social ) : ?>
          <div class="social-input-row" id="social-entry-<?php echo esc_attr( $ihq_social['key'] ); ?>" hidden>
            <span class="social-input-label"><?php echo esc_html( $ihq_social['label'] ); ?></span>
            <input
              class="social-handle-input"
              type="text"
              id="social-input-<?php echo esc_attr( $ihq_social['key'] ); ?>"
              data-social-key="<?php echo esc_attr( $ihq_social['key'] ); ?>"
              placeholder="<?php echo esc_attr( $ihq_modal_social_placeholder ); ?>"
              aria-label="<?php echo esc_attr( $ihq_social['label'] ); ?>"
            >
          </div>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="modal-lower-panel modal-comm-deferred">
        <span class="m-eye">One More Thing</span>
        <h3 class="m-title">Glory is earned one weekend at a time.</h3>
        <p class="m-ctx">Yours starts now. Choose your competition and we'll send you and your followers everything you need to get started.</p>
        <div class="ms2-quote">When your followers watch you play, cheer, and celebrate, they become part of a shared experience that unites people the same way great sports, music, and global competitions always have.</div>
        <div class="comp-list">
          <div class="comp-card" id="cw" onclick="pickComp('cw')"><div class="comp-chk"><span class="chk-in">✓</span></div><div class="comp-body"><span class="comp-tag">Global Stage</span><div class="comp-title">Influencer World Competition</div><div class="comp-desc">Join thousands of Influencers and their followers. Compete Thursday night through Sunday night.</div></div></div>
          <div class="comp-card" id="cp" onclick="pickComp('cp')"><div class="comp-chk"><span class="chk-in">✓</span></div><div class="comp-body"><span class="comp-tag">Community Competition</span><div class="comp-title">Community Competition</div><div class="comp-desc">A one week competition between your followers who choose to participate with you at the helm cheering them all on to victory.</div></div></div>
        </div>
      </div>

      <button type="button" class="send-btn" id="sendbtn" onclick="onModalSubmit()"><?php esc_html_e( 'Continue', 'influencer-hq' ); ?></button>

      <p class="m-note"><?php esc_html_e( 'All conversations are private and confidential.', 'influencer-hq' ); ?><br><?php esc_html_e( 'We operate across time zones. Expect a reply within minutes.', 'influencer-hq' ); ?></p>
      </div>
    </div>

    <div class="mstep" id="ms-reg">
      <span class="m-eye"><?php esc_html_e( 'Almost there', 'influencer-hq' ); ?></span>
      <h3 class="m-title"><?php esc_html_e( 'Your details', 'influencer-hq' ); ?></h3>
      <p class="m-sub" style="margin-bottom:20px"><?php esc_html_e( 'Tell us who you are. We\'ll email your 6-digit registration code.', 'influencer-hq' ); ?></p>
      <div class="modal-reg-field">
        <label for="modal-reg-first"><?php esc_html_e( 'First name', 'influencer-hq' ); ?> <span style="color:#f85149">*</span></label>
        <input type="text" id="modal-reg-first" autocomplete="given-name" required maxlength="120" placeholder="<?php esc_attr_e( 'First name', 'influencer-hq' ); ?>">
      </div>
      <div class="modal-reg-field">
        <label for="modal-reg-last"><?php esc_html_e( 'Last name', 'influencer-hq' ); ?> <span style="color:#f85149">*</span></label>
        <input type="text" id="modal-reg-last" autocomplete="family-name" required maxlength="120" placeholder="<?php esc_attr_e( 'Last name', 'influencer-hq' ); ?>">
      </div>
      <div class="modal-reg-field">
        <label for="modal-reg-email"><?php esc_html_e( 'Email', 'influencer-hq' ); ?> <span style="color:#f85149">*</span></label>
        <input type="email" id="modal-reg-email" autocomplete="email" required placeholder="your@email.com">
      </div>
      <div class="modal-reg-field" id="modal-reg-telegram-wrap" style="display:none">
        <label for="modal-reg-telegram"><?php esc_html_e( 'Telegram username', 'influencer-hq' ); ?> <span style="color:#f85149">*</span></label>
        <input type="text" id="modal-reg-telegram" autocomplete="username" maxlength="80" placeholder="@username">
      </div>
      <?php if ( $ihq_turnstile_site_modal !== '' ) : ?>
      <div id="modal-reg-turnstile" data-sitekey="<?php echo esc_attr( $ihq_turnstile_site_modal ); ?>"></div>
      <?php endif; ?>
      <p class="modal-step-err" id="modal-reg-err"></p>
      <div class="modal-reg-actions">
        <button type="button" class="btn-modal-back" onclick="ihqModalRegBackToMs1()"><?php esc_html_e( 'Back', 'influencer-hq' ); ?></button>
        <button type="button" class="send-btn" id="modal-send-code-btn" onclick="ihqModalRegSendCode()"><?php esc_html_e( 'Send code', 'influencer-hq' ); ?></button>
      </div>
    </div>

    <div class="mstep" id="ms-code">
      <span class="m-eye"><?php esc_html_e( 'Check your inbox', 'influencer-hq' ); ?></span>
      <h3 class="m-title"><?php esc_html_e( 'You\'re on your way.', 'influencer-hq' ); ?></h3>
      <p class="modal-code-msg"><?php esc_html_e( 'Details coming in shortly. You and your followers are on the road to glory.', 'influencer-hq' ); ?></p>
      <p class="modal-code-msg"><?php esc_html_e( 'Registration code is sent via your provided method of communication.', 'influencer-hq' ); ?></p>
      <p class="modal-code-msg"><strong><?php esc_html_e( 'Enter the 6-digit code.', 'influencer-hq' ); ?></strong></p>
      <p class="modal-code-msg" id="modal-code-expires-note"></p>
      <div class="modal-reg-field" style="max-width:280px;margin:20px auto 0">
        <label for="modal-reg-code"><?php esc_html_e( 'Code', 'influencer-hq' ); ?></label>
        <input type="text" id="modal-reg-code" inputmode="numeric" pattern="[0-9]*" maxlength="6" autocomplete="one-time-code" placeholder="000000" style="text-align:center;letter-spacing:0.4em;font-size:1.35rem">
      </div>
      <p class="modal-step-err" id="modal-code-err"></p>
      <div class="modal-reg-actions">
        <button type="button" class="btn-modal-back" onclick="show('ms-reg')"><?php esc_html_e( 'Edit details', 'influencer-hq' ); ?></button>
        <button type="button" class="send-btn" id="modal-verify-btn" onclick="ihqModalRegVerifyCode()"><?php esc_html_e( 'Verify & continue', 'influencer-hq' ); ?></button>
      </div>
      <p style="font-family:'Be Vietnam Pro',sans-serif;font-size:.78rem;color:var(--warm);text-align:center;margin-top:16px;line-height:1.6"><?php esc_html_e( 'Didn\'t receive it? Go back and tap Send code again.', 'influencer-hq' ); ?></p>
    </div>
  </div>
</div>

<!-- Auth Modal (Login / Register) -->
<div class="auth-overlay" id="authModal">
  <div class="auth-modal">
    <button class="auth-modal-x" onclick="closeAuthModal()">✕</button>

    <?php if ( is_user_logged_in() ) :
      $current_user = wp_get_current_user();
    ?>
      <div style="text-align:center;padding:24px 0">
        <div style="font-size:2rem;color:var(--gl);margin-bottom:16px">◈</div>
        <h3 style="font-family:'Be Vietnam Pro',sans-serif;font-size:1.8rem;color:#fff;margin-bottom:14px">Welcome Back!</h3>
        <p style="font-family:'Be Vietnam Pro',sans-serif;font-size:1rem;color:var(--warm);line-height:1.9;margin-bottom:28px">
          Logged in as: <strong style="color:var(--gl)"><?php echo esc_html( $current_user->user_email ); ?></strong>
        </p>
        <a href="<?php echo esc_url( home_url( '/portal/portal-home/' ) ); ?>" class="auth-submit-btn" style="display:inline-block;text-decoration:none;padding:18px 40px;width:auto">Go to Portal</a>
        <div style="margin-top:16px">
          <a href="<?php echo esc_url( wp_logout_url( get_permalink() ) ); ?>" style="font-family:'Be Vietnam Pro',sans-serif;font-size:.8rem;color:var(--warm);letter-spacing:.1em">Logout</a>
        </div>
      </div>
    <?php else :
      $welcome = isset( $_GET['welcome'] ) && $_GET['welcome'] === 'true';
      if ( $welcome ) : ?>
        <div style="text-align:center;padding:24px 0">
          <div style="font-size:2.5rem;color:var(--gl);margin-bottom:16px">◈</div>
          <h3 style="font-family:'Be Vietnam Pro',sans-serif;font-size:1.8rem;color:#fff;margin-bottom:12px">Welcome to Influencer HQ!</h3>
          <p style="font-family:'Be Vietnam Pro',sans-serif;font-size:1rem;color:var(--warm);line-height:1.9">Your account has been successfully created!<br>You're now ready to start your journey with us.</p>
        </div>
      <?php else : ?>

      <div class="auth-tabs">
        <button class="auth-tab-btn active" id="auth-tab-login" onclick="switchAuthTab('login')">Login</button>
        <button class="auth-tab-btn" id="auth-tab-register" onclick="switchAuthTab('register')">Register</button>
      </div>

      <!-- LOGIN PANE -->
      <div class="auth-pane active" id="auth-pane-login">
        <div id="auth-login-step-email" style="max-width:480px;margin:0 auto">
          <p class="auth-section-sub" style="margin-bottom:16px">Enter your email. We'll send a 6-digit sign-in code.</p>
          <div class="auth-field">
            <label for="auth-login-email">Email</label>
            <input type="email" id="auth-login-email" name="email" required placeholder="your@email.com" autocomplete="email">
          </div>
          <?php if ( $ihq_turnstile_site_modal !== '' ) : ?>
          <div id="auth-login-turnstile" data-sitekey="<?php echo esc_attr( $ihq_turnstile_site_modal ); ?>" style="display:flex;justify-content:center;margin:16px 0"></div>
          <?php endif; ?>
          <div class="auth-err" id="auth-login-err"></div>
          <div class="auth-err" id="auth-login-info" style="display:none;background:rgba(40,167,69,.12);border-color:rgba(40,167,69,.35);color:#6fcf97"></div>
          <button type="button" class="auth-submit-btn" id="auth-login-send-btn" onclick="ihqAuthLoginSendCode()">Send sign-in code</button>
        </div>
        <div id="auth-login-step-code" style="display:none;max-width:480px;margin:0 auto">
          <p class="auth-section-sub" style="margin-bottom:16px">Enter the code from your email.</p>
          <div class="auth-field">
            <label for="auth-login-code">6-digit code</label>
            <input type="text" id="auth-login-code" inputmode="numeric" pattern="[0-9]*" maxlength="6" autocomplete="one-time-code" placeholder="000000" style="text-align:center;letter-spacing:0.35em;font-size:1.2rem">
          </div>
          <p class="auth-section-sub" id="auth-login-code-expires" style="margin-top:8px"></p>
          <div class="auth-err" id="auth-login-code-err"></div>
          <button type="button" class="auth-submit-btn" id="auth-login-verify-btn" onclick="ihqAuthLoginVerify()">Verify & sign in</button>
          <button type="button" class="auth-submit-btn" style="margin-top:12px;background:transparent;border:1px solid rgba(240,201,58,.45);color:var(--warm)" id="auth-login-back-btn" onclick="ihqAuthLoginBackToEmail()">Back</button>
        </div>
      </div>

      <!-- REGISTER PANE -->
      <div class="auth-pane" id="auth-pane-register">
        <form id="auth-register-form" onsubmit="handleAuthRegister(event)">
          <input type="hidden" id="auth-competition-preferences" name="competition_preferences" value="">
          <p class="auth-section-sub">Choose the method(s) you'd like us to use to communicate with you:</p>
          <div class="auth-ch-group">
            <?php
            $comm_methods = [
              'line'     => [ 'label' => 'LINE',     'type' => 'text', 'placeholder' => 'Your LINE ID' ],
              'telegram' => [ 'label' => 'Telegram', 'type' => 'text', 'placeholder' => '@yourusername' ],
              'whatsapp' => [ 'label' => 'WhatsApp', 'type' => 'tel',  'placeholder' => '+1234567890' ],
              'wechat'   => [ 'label' => 'WeChat',   'type' => 'text', 'placeholder' => 'Your WeChat ID' ],
            ];
            foreach ( $comm_methods as $method => $info ) : ?>
            <div class="auth-check auth-ch-block">
              <input type="checkbox" id="auth-ch-<?php echo esc_attr( $method ); ?>" value="<?php echo esc_attr( $method ); ?>" onchange="toggleAuthCh('<?php echo esc_attr( $method ); ?>',this)">
              <label for="auth-ch-<?php echo esc_attr( $method ); ?>"><?php echo esc_html( $info['label'] ); ?></label>
            </div>
            <input type="<?php echo esc_attr( $info['type'] ); ?>" class="auth-ch-input" id="auth-chi-<?php echo esc_attr( $method ); ?>" placeholder="<?php echo esc_attr( $info['placeholder'] ); ?>" data-method="<?php echo esc_attr( $method ); ?>">
            <?php endforeach; ?>
          </div>

          <div id="auth-challenge-section" style="display:none">
            <hr class="auth-section-sep">
            <h3 class="auth-section-title">Choose Your Path to Lead Global Competition</h3>
            <p class="auth-section-sub">After your email is verified, you'll enter HQ², your private influencer portal. Choose how you want to lead:</p>
            <div class="auth-comp-cards">
              <div class="auth-comp-card" id="auth-cc-weekend" onclick="selectAuthComp('weekend_world',this)">
                <input type="radio" name="auth_challenge_type" value="weekend_world" id="auth-comp-weekend">
                <div class="auth-comp-card-inner"><h4>Weekend World Challenge</h4><p>A global challenge open to all influencers and their followers — a massive international competition.</p></div>
              </div>
              <div class="auth-comp-card" id="auth-cc-community" onclick="selectAuthComp('community_challenge',this)">
                <input type="radio" name="auth_challenge_type" value="community_challenge" id="auth-comp-community">
                <div class="auth-comp-card-inner"><h4>Community Challenge</h4><p>A challenge created by you, for your community, on your schedule — with an option to stream live.</p></div>
              </div>
              <div class="auth-comp-card" id="auth-cc-later" onclick="selectAuthComp('maybe_later',this)">
                <input type="radio" name="auth_challenge_type" value="maybe_later" id="auth-comp-later">
                <div class="auth-comp-card-inner"><h4>Thanks, maybe later</h4></div>
              </div>
            </div>
          </div>

          <div id="auth-genius-section" style="display:none">
            <hr class="auth-section-sep">
            <h3 class="auth-section-title">Meet Genius — Your Partner in Protecting Your Equity Rewards</h3>
            <p class="auth-section-sub">Genius automatically manages lifetime Influencer HQ equity in your expanding network. To activate, verify your identity below.</p>
            <div class="auth-field">
              <label for="auth-reg-email">Email</label>
              <input type="email" id="auth-reg-email" name="email" required placeholder="your@email.com">
            </div>
            <div class="auth-field">
              <label for="auth-reg-first">First Name</label>
              <input type="text" id="auth-reg-first" name="first_name" required placeholder="First Name">
            </div>
            <div class="auth-field">
              <label for="auth-reg-last">Last Name</label>
              <input type="text" id="auth-reg-last" name="last_name" required placeholder="Last Name">
            </div>
            <div class="auth-field">
              <label for="auth-reg-handle">Favorite Platform Handle</label>
              <input type="text" id="auth-reg-handle" name="platform_handle" placeholder="@yourhandle">
            </div>
            <div class="auth-check">
              <input type="checkbox" id="auth-prefer-facial" onchange="document.getElementById('auth-facial-opts').style.display=this.checked?'block':'none'">
              <label for="auth-prefer-facial">Prefer Facial Recognition?</label>
            </div>
            <div id="auth-facial-opts" style="display:none;margin-bottom:20px">
              <p class="auth-section-sub" style="margin-bottom:12px">Sign in with:</p>
              <div style="display:flex;flex-wrap:wrap;gap:8px">
                <?php foreach ( [ 'Face ID', 'WeChat Face', 'Alipay Face', 'LINE Face', 'KakaoTalk', 'Biometric ID' ] as $fr ) : ?>
                  <button type="button" style="padding:10px 16px;background:transparent;border:1px solid rgba(240,201,58,.4);color:var(--warm);font-family:'Be Vietnam Pro',sans-serif;font-size:.8rem;cursor:pointer"><?php echo esc_html( $fr ); ?></button>
                <?php endforeach; ?>
              </div>
            </div>
            <div class="auth-err" id="auth-reg-err"></div>
            <p style="font-family:'Be Vietnam Pro',sans-serif;font-size:.8rem;color:var(--warm);text-align:center;margin-bottom:16px">Please check your email to verify and activate Genius.</p>
            <button type="submit" class="auth-submit-btn" id="auth-reg-btn">Send Verification Email</button>
          </div>
        </form>
      </div><!-- /register pane -->

      <?php endif; // end welcome check ?>
    <?php endif; // end logged-in check ?>
  </div>
</div>

</main><!-- #primary -->

<script>
window.IHQ_CF_COUNTRY_SEED_ISO = <?php echo wp_json_encode( $ihq_cf_country_iso_alpha2 ); ?>;
if (typeof window.ihqResolveClientCountryIsoAlpha2 !== 'function') {
  window.ihqResolveClientCountryIsoAlpha2 = function () {
    var seed = typeof window.IHQ_CF_COUNTRY_SEED_ISO === 'string' ? window.IHQ_CF_COUNTRY_SEED_ISO.trim() : '';
    if (/^[A-Za-z]{2}$/.test(seed)) return seed.toUpperCase();
    return 'US';
  };
}

var IHQ_MODAL_REG = {
  ajaxUrl: <?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>,
  nonce: <?php echo wp_json_encode( $ihq_modal_reg_nonce ); ?>,
  turnstileSiteKey: <?php echo wp_json_encode( $ihq_turnstile_site_modal ); ?>,
  codeExpiresMinutes: 15,
  telegramClientId: <?php echo (int) $ihq_telegram_client_id; ?>,
  telegramLoginNonce: <?php echo wp_json_encode( $ihq_telegram_login_pubkey_nonce ); ?>,
  telegramLblDefault: <?php echo wp_json_encode( $ihq_modal_telegram_lbl_default ); ?>,
  telegramVerifiedPrefix: <?php echo wp_json_encode( $ihq_modal_telegram_verified_lbl ); ?>
};
var ihqTelegramOAuthBusy = false;
var ihqVerifiedTelegramUsername = '';
var ihqVerifiedTelegramSessionToken = '';
var ihqVerifiedTelegramFirstName = '';
var ihqVerifiedTelegramLastName = '';
var ihqModalSignupToken = '';
var ihqModalTurnstileWidgetId = null;

var IHQ_AUTH_LOGIN = {
  ajaxUrl: <?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>,
  nonce: <?php echo wp_json_encode( $ihq_modal_login_nonce ); ?>,
  turnstileSiteKey: <?php echo wp_json_encode( $ihq_turnstile_site_modal ); ?>,
  redirectUrl: <?php echo wp_json_encode( home_url( '/portal/portal-home/' ) ); ?>,
  codeExpiresMinutes: 15
};
var ihqAuthLoginSignupToken = '';
var ihqAuthLoginTurnstileWidgetId = null;

/* ── Rating buttons ─────────────────────────────────────── */
function lnRate(btn) {
  var group = btn.getAttribute('data-group');
  var val   = btn.getAttribute('data-val');
  document.querySelectorAll('.ln-rating-btn[data-group="' + group + '"]').forEach(function(b) {
    b.classList.remove('sel');
  });
  btn.classList.add('sel');
  var field = document.getElementById('ln-comp-rating-' + group);
  if (field) field.value = val;
  if (typeof window.ihqVisitorIntentSaveRating === 'function') {
    window.ihqVisitorIntentSaveRating(group, val);
  }
  var accountUrl = (window.IHQ_VISITOR_INTENT && window.IHQ_VISITOR_INTENT.accountUrl) ? window.IHQ_VISITOR_INTENT.accountUrl : '/portal/account/';
  window.location.href = accountUrl;
}

/* ── Concierge (ElevenLabs) ─────────────────────────────── */
(function () {
  document.addEventListener('DOMContentLoaded', function () {
    var btn = document.getElementById('landerConciergeBtn');
    if (!btn || typeof ihqElevenLabs === 'undefined') return;
    var activeSession = null;
    var originalText  = 'Ask Me Anything';
    var DEFAULT_LANG  = 'en';

    function pageLang() {
      var raw = (document.documentElement.getAttribute('lang') || '').trim();
      if (!raw) return DEFAULT_LANG;
      var p = raw.split(/[-_\s]/)[0].toLowerCase();
      return /^[a-z]{2,10}$/.test(p) ? p : DEFAULT_LANG;
    }

    btn.addEventListener('click', function (e) {
      e.preventDefault();
      if (activeSession) { activeSession.endSession(); return; }
      btn.style.pointerEvents = 'none';
      btn.textContent = 'Connecting…';

      fetch(ihqElevenLabs.ajax_url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=ihq_elevenlabs_signed_url&nonce=' + encodeURIComponent(ihqElevenLabs.nonce) +
              '&agent_id=' + encodeURIComponent(ihqElevenLabs.agent_id_portal_home_claude),
      })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (data.success && data.data && data.data.signed_url) {
          ElevenLabsClient.Conversation.startSession({
            signedUrl: data.data.signed_url,
            overrides: { agent: { language: pageLang() } },
            onConnect:    function () { btn.textContent = 'End Talk'; btn.style.pointerEvents = ''; },
            onDisconnect: function () { activeSession = null; btn.style.pointerEvents = ''; btn.textContent = originalText; },
            onError:      function () { activeSession = null; btn.style.pointerEvents = ''; btn.textContent = originalText; },
            onMessage:    function () {},
          }).then(function (s) { activeSession = s; }).catch(function () { btn.style.pointerEvents = ''; btn.textContent = originalText; });
        } else {
          btn.style.pointerEvents = ''; btn.textContent = originalText;
        }
      })
      .catch(function () { btn.style.pointerEvents = ''; btn.textContent = originalText; });
    });
  });
})();

/* ── Turnstile helpers ───────────────────────────────────── */
function ihqAuthLoginRemoveTurnstile() {
  var host = document.getElementById('auth-login-turnstile');
  if (!host || !IHQ_AUTH_LOGIN.turnstileSiteKey) return;
  if (ihqAuthLoginTurnstileWidgetId !== null && typeof window.turnstile !== 'undefined') {
    try { window.turnstile.remove(ihqAuthLoginTurnstileWidgetId); } catch (e) {}
    ihqAuthLoginTurnstileWidgetId = null;
  }
  host.removeAttribute('data-rendered'); host.innerHTML = '';
}
function ihqAuthLoginRenderTurnstileWhenNeeded() {
  if (!IHQ_AUTH_LOGIN.turnstileSiteKey) return;
  var el = document.getElementById('auth-login-turnstile');
  if (!el || el.getAttribute('data-rendered') === '1') return;
  if (typeof window.turnstile === 'undefined') { window.setTimeout(ihqAuthLoginRenderTurnstileWhenNeeded, 200); return; }
  ihqAuthLoginTurnstileWidgetId = window.turnstile.render(el, { sitekey: IHQ_AUTH_LOGIN.turnstileSiteKey });
  el.setAttribute('data-rendered', '1');
}
function ihqAuthLoginResetPanels() {
  ihqAuthLoginSignupToken = '';
  var se = document.getElementById('auth-login-step-email');
  var sc = document.getElementById('auth-login-step-code');
  if (se) se.style.display = 'block'; if (sc) sc.style.display = 'none';
  ['auth-login-email','auth-login-code'].forEach(function(id){var x=document.getElementById(id);if(x)x.value='';});
  ['auth-login-err','auth-login-info','auth-login-code-err'].forEach(function(id){var x=document.getElementById(id);if(x){x.textContent='';x.style.display='none';}});
  var exp=document.getElementById('auth-login-code-expires'); if(exp)exp.textContent='';
  ihqAuthLoginRemoveTurnstile(); window.setTimeout(ihqAuthLoginRenderTurnstileWhenNeeded, 80);
}
function ihqAuthLoginBackToEmail() {
  ihqAuthLoginRemoveTurnstile(); ihqAuthLoginSignupToken='';
  var cer=document.getElementById('auth-login-code-err'); if(cer)cer.textContent='';
  document.getElementById('auth-login-step-code').style.display='none';
  document.getElementById('auth-login-step-email').style.display='block';
  window.setTimeout(ihqAuthLoginRenderTurnstileWhenNeeded,80);
}
function ihqAuthLoginSendCode() {
  var errBox=document.getElementById('auth-login-err');
  var infoBox=document.getElementById('auth-login-info');
  if(errBox){errBox.style.display='none';errBox.textContent='';}
  if(infoBox){infoBox.style.display='none';infoBox.textContent='';}
  var email=document.getElementById('auth-login-email').value.trim();
  if(!email||email.indexOf('@')===-1){if(errBox){errBox.textContent='Please enter a valid email address.';errBox.style.display='block';}return;}
  var tsToken='';
  if(IHQ_AUTH_LOGIN.turnstileSiteKey){
    if(typeof window.turnstile==='undefined'||ihqAuthLoginTurnstileWidgetId===null){if(errBox){errBox.textContent='Please wait for the security check to load.';errBox.style.display='block';}return;}
    tsToken=window.turnstile.getResponse(ihqAuthLoginTurnstileWidgetId)||'';
    if(!tsToken){if(errBox){errBox.textContent='Please complete the security verification.';errBox.style.display='block';}return;}
  }
  var btn=document.getElementById('auth-login-send-btn'); if(btn)btn.disabled=true;
  var fd=new FormData();fd.append('action','ihq_send_login_code');fd.append('nonce',IHQ_AUTH_LOGIN.nonce);fd.append('email',email);fd.append('cf-turnstile-response',tsToken);
  fetch(IHQ_AUTH_LOGIN.ajaxUrl,{method:'POST',body:fd})
    .then(function(r){return r.json();})
    .then(function(data){
      if(btn)btn.disabled=false;
      if(!data.success){if(errBox){errBox.textContent=ihqModalAjaxErrMessage(data);errBox.style.display='block';}ihqAuthLoginRemoveTurnstile();window.setTimeout(ihqAuthLoginRenderTurnstileWhenNeeded,80);return;}
      var d=data.data;var msg=d.message||'If that email matches an account, you will receive a code.';
      if(infoBox){infoBox.textContent=msg;infoBox.style.display='block';}
      if(!d.signup_token||d.skipped){ihqAuthLoginRemoveTurnstile();window.setTimeout(ihqAuthLoginRenderTurnstileWhenNeeded,80);return;}
      ihqAuthLoginSignupToken=d.signup_token;
      var minutes=d.expires_minutes||IHQ_AUTH_LOGIN.codeExpiresMinutes;
      var expEl=document.getElementById('auth-login-code-expires');if(expEl)expEl.textContent='Code expires in '+minutes+' minute'+(minutes===1?'':'s')+'.';
      var codeErr=document.getElementById('auth-login-code-err');if(codeErr)codeErr.textContent='';
      var codeInput=document.getElementById('auth-login-code');if(codeInput)codeInput.value='';
      document.getElementById('auth-login-step-email').style.display='none';
      document.getElementById('auth-login-step-code').style.display='block';
    })
    .catch(function(){if(btn)btn.disabled=false;if(errBox){errBox.textContent='Network error. Please try again.';errBox.style.display='block';}});
}
function ihqAuthLoginVerify() {
  var errEl=document.getElementById('auth-login-code-err'); if(errEl)errEl.textContent='';
  var raw=document.getElementById('auth-login-code').value.replace(/\D/g,'');
  if(raw.length!==6){if(errEl)errEl.textContent='Enter the 6-digit code from your email.';return;}
  if(!ihqAuthLoginSignupToken){if(errEl)errEl.textContent='Send a code first.';ihqAuthLoginBackToEmail();return;}
  var btn=document.getElementById('auth-login-verify-btn'); if(btn)btn.disabled=true;
  var fd=new FormData();fd.append('action','ihq_verify_login_code');fd.append('nonce',IHQ_AUTH_LOGIN.nonce);fd.append('signup_token',ihqAuthLoginSignupToken);fd.append('code',raw);fd.append('redirect_url',IHQ_AUTH_LOGIN.redirectUrl);fd.append('country_iso',window.ihqResolveClientCountryIsoAlpha2());
  fetch(IHQ_AUTH_LOGIN.ajaxUrl,{method:'POST',body:fd})
    .then(function(r){return r.json();})
    .then(function(data){if(btn)btn.disabled=false;if(!data.success){if(errEl)errEl.textContent=ihqModalAjaxErrMessage(data);return;}window.location.href=data.data.redirect_url||IHQ_AUTH_LOGIN.redirectUrl;})
    .catch(function(){if(btn)btn.disabled=false;if(errEl)errEl.textContent='Network error. Please try again.';});
}

/* ── Main conversation modal helpers ────────────────────── */
function ihqGetModalCommCheckboxes() {
  return document.querySelectorAll('#modal-comm-pick .modal-comm-option input[type=checkbox]');
}
function syncModalCommCardVisual() {
  ihqGetModalCommCheckboxes().forEach(function (box) {
    var row = box.closest('.modal-comm-option');
    if (row) {
      row.classList.toggle('is-on', box.checked);
    }
  });
}
function ihqClearModalCommErr() {
  var pick = document.getElementById('modal-comm-pick');
  var err = document.getElementById('modal-comm-err');
  if (pick) pick.classList.remove('field-error');
  if (err) {
    err.textContent = '';
    err.classList.remove('is-visible');
  }
}
function ihqShowModalCommErr(msg) {
  var pick = document.getElementById('modal-comm-pick');
  var err = document.getElementById('modal-comm-err');
  if (pick) pick.classList.add('field-error');
  if (err) {
    err.textContent = msg || '';
    err.classList.toggle('is-visible', Boolean(msg));
  }
}
function ihqResetModalCommMethods() {
  ihqGetModalCommCheckboxes().forEach(function (box) {
    box.checked = false;
    var key = box.getAttribute('data-comm-key');
    var entry = key ? document.getElementById('modal-comm-entry-' + key) : null;
    var input = key ? document.getElementById('modal-comm-input-' + key) : null;
    if (entry) entry.hidden = true;
    if (input) input.value = '';
  });
  syncModalCommCardVisual();
  ihqClearModalCommErr();
}
function ihqHideModalCommThanks() {
  var thanks = document.getElementById('modal-comm-thanks');
  var body = document.getElementById('modal-comm-form-body');
  if (thanks) thanks.hidden = true;
  if (body) body.hidden = false;
}
function ihqShowModalCommThanks() {
  var thanks = document.getElementById('modal-comm-thanks');
  var body = document.getElementById('modal-comm-form-body');
  if (body) body.hidden = true;
  if (thanks) thanks.hidden = false;
}
function ihqClearTelegramLoginErr() {
  var e = document.getElementById('modal-comm-tg-err');
  if (e) e.textContent = '';
}
function ihqShowTelegramLoginErr(msg) {
  var e = document.getElementById('modal-comm-tg-err');
  if (e) e.textContent = msg || '';
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
function ihqTryTelegramAccountLink() {
  if (!IHQ_MODAL_REG.telegramClientId || ihqTelegramOAuthBusy) return;
  ihqClearTelegramLoginErr();
  ihqTelegramOAuthBusy = true;
  var fd = new FormData();
  fd.append('action', 'ihq_telegram_login_nonce');
  fd.append('nonce', IHQ_MODAL_REG.telegramLoginNonce);
  fetch(IHQ_MODAL_REG.ajaxUrl, { method: 'POST', body: fd })
    .then(function (r) { return r.json(); })
    .then(function (data) {
      if (!data.success || !data.data || !data.data.server_nonce) {
        ihqTelegramOAuthBusy = false;
        ihqShowTelegramLoginErr(ihqModalAjaxErrMessage(data) || 'Could not start Telegram login.');
        return;
      }
      var serverNonce = data.data.server_nonce;
      ihqEnsureTelegramLoginScript(function (ok) {
        if (!ok) {
          ihqTelegramOAuthBusy = false;
          ihqShowTelegramLoginErr('Could not load Telegram login.');
          return;
        }
        if (!window.Telegram || !window.Telegram.Login || typeof window.Telegram.Login.auth !== 'function') {
          ihqTelegramOAuthBusy = false;
          ihqShowTelegramLoginErr('Telegram login is unavailable.');
          return;
        }
        window.Telegram.Login.auth(
          { client_id: IHQ_MODAL_REG.telegramClientId, nonce: serverNonce, lang: 'en', request_access: ['write'] },
          function (result) {
            if (result === false) {
              ihqTelegramOAuthBusy = false;
              return;
            }
            if (!result || result.error) {
              ihqTelegramOAuthBusy = false;
              if (result && result.error) ihqShowTelegramLoginErr(String(result.error));
              return;
            }
            var idToken = result.id_token;
            if (!idToken) {
              ihqTelegramOAuthBusy = false;
              ihqShowTelegramLoginErr('Telegram did not return an ID token.');
              return;
            }
            var fd2 = new FormData();
            fd2.append('action', 'ihq_verify_telegram_id_token');
            fd2.append('nonce', IHQ_MODAL_REG.telegramLoginNonce);
            fd2.append('id_token', idToken);
            fetch(IHQ_MODAL_REG.ajaxUrl, { method: 'POST', body: fd2 })
              .then(function (r2) { return r2.json(); })
              .then(function (data2) {
                ihqTelegramOAuthBusy = false;
                if (!data2.success || !data2.data || !data2.data.telegram_username) {
                  ihqShowTelegramLoginErr(ihqModalAjaxErrMessage(data2) || 'Verification failed.');
                  return;
                }
                var tu = data2.data.telegram_username;
                var ti = document.getElementById('modal-reg-telegram');
                if (ti) ti.value = tu;
                ihqVerifiedTelegramUsername = tu;
                ihqVerifiedTelegramSessionToken = data2.data.telegram_session_token || '';
                ihqVerifiedTelegramFirstName = data2.data.telegram_first_name || '';
                ihqVerifiedTelegramLastName = data2.data.telegram_last_name || '';
                var firstInput = document.getElementById('modal-reg-first');
                var lastInput = document.getElementById('modal-reg-last');
                if (firstInput && !firstInput.value && ihqVerifiedTelegramFirstName) firstInput.value = ihqVerifiedTelegramFirstName;
                if (lastInput && !lastInput.value && ihqVerifiedTelegramLastName) lastInput.value = ihqVerifiedTelegramLastName;
                var fd3 = new FormData();
                fd3.append('action', 'ihq_register_telegram_user');
                fd3.append('nonce', IHQ_MODAL_REG.telegramLoginNonce);
                fd3.append('telegram_session_token', ihqVerifiedTelegramSessionToken);
                fd3.append('challenge_type', ihqModalGetChallengeType());
                fd3.append('platform_handle', ihqModalGetPlatformHandle() || tu);
                fd3.append('country_iso', window.ihqResolveClientCountryIsoAlpha2());
                fetch(IHQ_MODAL_REG.ajaxUrl, { method: 'POST', body: fd3 })
                  .then(function (r3) { return r3.json(); })
                  .then(function (data3) {
                    if (!data3.success || !data3.data || !data3.data.redirect_url) {
                      ihqShowTelegramLoginErr(ihqModalAjaxErrMessage(data3) || 'Could not finish Telegram registration.');
                      return;
                    }
                    chosen();
                    window.location.href = data3.data.redirect_url;
                  })
                  .catch(function () { ihqShowTelegramLoginErr('Network error.'); });
              })
              .catch(function () {
                ihqTelegramOAuthBusy = false;
                ihqShowTelegramLoginErr('Network error.');
              });
          }
        );
      });
    })
    .catch(function () {
      ihqTelegramOAuthBusy = false;
      ihqShowTelegramLoginErr('Network error.');
    });
}
function ihqModalAjaxErrMessage(data) {
  if (!data || !data.data) return 'Something went wrong.';
  var d = data.data;
  if (typeof d === 'string') return d;
  if (d.message) return d.message;
  return 'Something went wrong.';
}
function ihqModalRemoveTurnstile() {
  var host = document.getElementById('modal-reg-turnstile');
  if (!host || !IHQ_MODAL_REG.turnstileSiteKey) return;
  if (ihqModalTurnstileWidgetId !== null && typeof window.turnstile !== 'undefined') {
    try { window.turnstile.remove(ihqModalTurnstileWidgetId); } catch (eRes) {}
    ihqModalTurnstileWidgetId = null;
  }
  host.removeAttribute('data-rendered');
  host.innerHTML = '';
}
function ihqModalRenderTurnstileWhenNeeded() {
  if (!IHQ_MODAL_REG.turnstileSiteKey) return;
  var el = document.getElementById('modal-reg-turnstile');
  if (!el || el.getAttribute('data-rendered') === '1') return;
  if (typeof window.turnstile === 'undefined') {
    window.setTimeout(ihqModalRenderTurnstileWhenNeeded, 200);
    return;
  }
  ihqModalTurnstileWidgetId = window.turnstile.render(el, { sitekey: IHQ_MODAL_REG.turnstileSiteKey });
  el.setAttribute('data-rendered', '1');
}
function ihqResetModalSocialPlatforms() {
  document.querySelectorAll('.social-grid-item.is-selected').forEach(function (btn) {
    btn.classList.remove('is-selected');
    btn.setAttribute('aria-pressed', 'false');
  });
  document.querySelectorAll('.social-input-row').forEach(function (row) {
    row.hidden = true;
    var inp = row.querySelector('input.social-handle-input');
    if (inp) inp.value = '';
  });
}
function ihqResetMainConversationModal() {
  ihqModalSignupToken = '';
  var codeErr = document.getElementById('modal-code-err');
  var regErr = document.getElementById('modal-reg-err');
  if (codeErr) codeErr.textContent = '';
  if (regErr) regErr.textContent = '';
  var codeEl = document.getElementById('modal-reg-code');
  if (codeEl) codeEl.value = '';
  ['modal-reg-first', 'modal-reg-last', 'modal-reg-email', 'modal-reg-telegram'].forEach(function (idEl) {
    var x = document.getElementById(idEl);
    if (x) x.value = '';
  });
  ihqResetModalCommMethods();
  ihqHideModalCommThanks();
  ihqModalRemoveTurnstile();
  var telWrap = document.getElementById('modal-reg-telegram-wrap');
  if (telWrap) telWrap.style.display = 'none';
  ihqClearTelegramLoginErr();
  ihqTelegramOAuthBusy = false;
  ihqVerifiedTelegramUsername = '';
  ihqVerifiedTelegramSessionToken = '';
  ihqVerifiedTelegramFirstName = '';
  ihqVerifiedTelegramLastName = '';
  ihqResetModalSocialPlatforms();
  document.querySelectorAll('.comp-card.sel').forEach(function (card) { card.classList.remove('sel'); });
}
function ihqToggleSocialPlatform(key) {
  var btn = document.getElementById('social-grid-' + key);
  var row = document.getElementById('social-entry-' + key);
  if (!btn || !row) return;
  var isOn = !btn.classList.contains('is-selected');
  btn.classList.toggle('is-selected', isOn);
  btn.setAttribute('aria-pressed', isOn ? 'true' : 'false');
  row.hidden = !isOn;
  if (!isOn) {
    var cleared = row.querySelector('input.social-handle-input');
    if (cleared) cleared.value = '';
    return;
  }
  var focusInput = row.querySelector('input.social-handle-input');
  if (focusInput) window.setTimeout(function () { focusInput.focus(); }, 50);
}
function ihqModalGetCommMethods() {
  var methods = {};
  ihqGetModalCommCheckboxes().forEach(function (box) {
    if (!box.checked) return;
    var key = box.getAttribute('data-comm-key');
    var input = key ? document.getElementById('modal-comm-input-' + key) : null;
    if (key && input && input.value.trim()) methods[key] = input.value.trim();
  });
  return methods;
}
function ihqModalEmailCommSelected() {
  var emailBox = document.getElementById('modal-comm-email');
  return Boolean(emailBox && emailBox.checked);
}
function ihqModalGetChallengeType() {
  if (document.getElementById('cw').classList.contains('sel')) return 'weekend_world';
  if (document.getElementById('cp').classList.contains('sel')) return 'community_challenge';
  return 'maybe_later';
}
function ihqModalGetPlatformHandle() {
  var parts = [];
  document.querySelectorAll('.social-grid-item.is-selected').forEach(function (btn) {
    var key = btn.getAttribute('data-social-key');
    var row = key ? document.getElementById('social-entry-' + key) : null;
    var inp = row ? row.querySelector('input.social-handle-input') : null;
    var label = btn.textContent.trim();
    if (inp && inp.value.trim()) parts.push(label ? label + ': ' + inp.value.trim() : inp.value.trim());
  });
  return parts.join(' | ');
}
function ihqModalRegBackToMs1() {
  ihqModalRemoveTurnstile();
  ihqModalSignupToken = '';
  var re = document.getElementById('modal-reg-err');
  var ce = document.getElementById('modal-code-err');
  if (re) re.textContent = '';
  if (ce) ce.textContent = '';
  show('ms1');
}
function ihqModalRegSendCode() {
  var errEl = document.getElementById('modal-reg-err');
  if (errEl) errEl.textContent = '';
  var first = document.getElementById('modal-reg-first').value.trim();
  var last = document.getElementById('modal-reg-last').value.trim();
  var email = document.getElementById('modal-reg-email').value.trim();
  var commPrimary = 'email';
  var telegramUsername = document.getElementById('modal-reg-telegram').value.trim();
  if (!telegramUsername && ihqVerifiedTelegramUsername) {
    telegramUsername = ihqVerifiedTelegramUsername;
    document.getElementById('modal-reg-telegram').value = telegramUsername;
  }
  if (!first || !last) {
    errEl.textContent = 'Please enter your first and last name.';
    return;
  }
  if (!email || email.indexOf('@') === -1) {
    errEl.textContent = 'Please enter a valid email address.';
    return;
  }
  var commMethods = ihqModalGetCommMethods();
  commMethods.email = email;
  var tsToken = '';
  if (IHQ_MODAL_REG.turnstileSiteKey) {
    if (typeof window.turnstile === 'undefined' || ihqModalTurnstileWidgetId === null) {
      errEl.textContent = 'Please wait for the security check to load.';
      return;
    }
    tsToken = window.turnstile.getResponse(ihqModalTurnstileWidgetId) || '';
    if (!tsToken) {
      errEl.textContent = 'Please complete the security verification.';
      return;
    }
  }
  var btn = document.getElementById('modal-send-code-btn');
  if (btn) btn.disabled = true;
  var fd = new FormData();
  fd.append('action', 'ihq_send_registration_code');
  fd.append('nonce', IHQ_MODAL_REG.nonce);
  fd.append('first_name', first);
  fd.append('last_name', last);
  fd.append('email', email);
  fd.append('platform_handle', ihqModalGetPlatformHandle());
  fd.append('challenge_type', ihqModalGetChallengeType());
  fd.append('comm_primary', commPrimary);
  fd.append('comm_methods', JSON.stringify(commMethods));
  fd.append('telegram_username', telegramUsername);
  fd.append('telegram_session_token', ihqVerifiedTelegramSessionToken);
  fd.append('cf-turnstile-response', tsToken);
  fd.append('country_iso', window.ihqResolveClientCountryIsoAlpha2());
  fetch(IHQ_MODAL_REG.ajaxUrl, { method: 'POST', body: fd })
    .then(function (r) { return r.json(); })
    .then(function (data) {
      if (btn) btn.disabled = false;
      if (!data.success) {
        if (errEl) errEl.textContent = ihqModalAjaxErrMessage(data);
        ihqModalRemoveTurnstile();
        ihqModalRenderTurnstileWhenNeeded();
        return;
      }
      ihqModalSignupToken = data.data.signup_token;
      var minutes = data.data.expires_minutes || IHQ_MODAL_REG.codeExpiresMinutes;
      document.getElementById('modal-code-expires-note').textContent =
        'Code expires in ' + minutes + ' minute' + (minutes === 1 ? '' : 's') + '.';
      document.getElementById('modal-code-err').textContent = '';
      document.getElementById('modal-reg-code').value = '';
      show('ms-code');
    })
    .catch(function () {
      if (btn) btn.disabled = false;
      errEl.textContent = 'Network error. Please try again.';
    });
}
function ihqModalRegVerifyCode() {
  var errEl = document.getElementById('modal-code-err');
  if (errEl) errEl.textContent = '';
  var raw = document.getElementById('modal-reg-code').value.replace(/\D/g, '');
  if (raw.length !== 6) {
    errEl.textContent = 'Enter the 6-digit code from your email.';
    return;
  }
  if (!ihqModalSignupToken) {
    errEl.textContent = 'Send a code first.';
    show('ms-reg');
    return;
  }
  var btn = document.getElementById('modal-verify-btn');
  if (btn) btn.disabled = true;
  var fd = new FormData();
  fd.append('action', 'ihq_verify_registration_code');
  fd.append('nonce', IHQ_MODAL_REG.nonce);
  fd.append('signup_token', ihqModalSignupToken);
  fd.append('code', raw);
  fd.append('country_iso', window.ihqResolveClientCountryIsoAlpha2());
  fetch(IHQ_MODAL_REG.ajaxUrl, { method: 'POST', body: fd })
    .then(function (r) { return r.json(); })
    .then(function (data) {
      if (btn) btn.disabled = false;
      if (!data.success) {
        errEl.textContent = ihqModalAjaxErrMessage(data);
        return;
      }
      chosen();
      window.location.href = data.data.redirect_url;
    })
    .catch(function () {
      if (btn) btn.disabled = false;
      errEl.textContent = 'Network error. Please try again.';
    });
}

/* ── Modal open / close ──────────────────────────────────── */
var src = null;
function openModal(ctaId) {
  src = ctaId || null;
  ihqResetMainConversationModal();
  document.getElementById('mainModal').classList.add('open');
  document.body.style.overflow = 'hidden';
  show('ms1');
}
function closeModal() {
  ihqResetMainConversationModal();
  document.getElementById('mainModal').classList.remove('open');
  document.body.style.overflow = '';
}
function show(id) {
  document.querySelectorAll('.mstep').forEach(function (s) { s.classList.remove('on'); });
  document.getElementById(id).classList.add('on');
  if (id === 'ms-reg') window.setTimeout(function () { ihqModalRenderTurnstileWhenNeeded(); }, 80);
}
function clearFieldErrors() {
  document.querySelectorAll('.field-error').forEach(function (el) { el.classList.remove('field-error'); });
}
function showFieldError(el) {
  if (!el) return;
  el.classList.add('field-error');
  window.setTimeout(function () { el.classList.remove('field-error'); }, 1200);
}
function validateModalStep1Communication() {
  var checked = [];
  ihqGetModalCommCheckboxes().forEach(function (box) {
    if (box.checked) checked.push(box);
  });
  if (!checked.length) {
    ihqShowModalCommErr('Please pick a method of communication');
    return false;
  }
  var missingInput = false;
  checked.forEach(function (box) {
    var key = box.getAttribute('data-comm-key');
    var input = key ? document.getElementById('modal-comm-input-' + key) : null;
    var entry = key ? document.getElementById('modal-comm-entry-' + key) : null;
    if (!input || !input.value.trim()) {
      missingInput = true;
      if (entry) showFieldError(entry);
    }
  });
  if (missingInput) return false;
  ihqClearModalCommErr();
  return true;
}
function ihqModalAdvanceToRegStep() {
  var telWrap = document.getElementById('modal-reg-telegram-wrap');
  var telInput = document.getElementById('modal-reg-telegram');
  var telegramBox = document.getElementById('modal-comm-telegram');
  if (telegramBox && telegramBox.checked) {
    telWrap.style.display = 'block';
    telInput.required = true;
    if (!telInput.value && ihqVerifiedTelegramUsername) telInput.value = ihqVerifiedTelegramUsername;
    var firstInput = document.getElementById('modal-reg-first');
    var lastInput = document.getElementById('modal-reg-last');
    if (firstInput && !firstInput.value && ihqVerifiedTelegramFirstName) firstInput.value = ihqVerifiedTelegramFirstName;
    if (lastInput && !lastInput.value && ihqVerifiedTelegramLastName) lastInput.value = ihqVerifiedTelegramLastName;
  } else {
    telWrap.style.display = 'none';
    telInput.required = false;
    telInput.value = '';
  }
  var emailInput = document.getElementById('modal-comm-input-email');
  var regEmail = document.getElementById('modal-reg-email');
  if (emailInput && regEmail && !regEmail.value.trim()) {
    var emailHandle = emailInput.value.trim();
    if (emailHandle.indexOf('@') !== -1) regEmail.value = emailHandle;
  }
  show('ms-reg');
}
function onModalSubmit() {
  clearFieldErrors();
  ihqClearModalCommErr();
  if (!validateModalStep1Communication()) {
    var modalEl = document.querySelector('#mainModal .modal');
    if (modalEl) modalEl.scrollTo({ top: 0, behavior: 'smooth' });
    return;
  }
  if (typeof window.ihqVisitorIntentSaveFromModalAndRedirect === 'function') {
    window.ihqVisitorIntentSaveFromModalAndRedirect();
    return;
  }
  if (ihqModalEmailCommSelected()) {
    ihqModalAdvanceToRegStep();
    return;
  }
  ihqShowModalCommThanks();
  window.setTimeout(function () { closeModal(); }, 2000);
}
function chosen() {
  document.querySelectorAll('.cta-block').forEach(function (b) {
    b.innerHTML = '<p class="cta-done">Connected. Details coming shortly.</p>';
  });
  var cf = document.getElementById('cfinal');
  if (cf) cf.innerHTML = '<p class="cta-done">Details coming your way shortly.</p>';
}
function pickComp(id) {
  var el = document.getElementById(id);
  if (!el) return;
  el.classList.toggle('sel');
}

/* ── Auth modal ──────────────────────────────────────────── */
function openAuthModal(tab){var m=document.getElementById('authModal');if(!m)return;m.classList.add('open');document.body.style.overflow='hidden';if(tab)switchAuthTab(tab);}
function closeAuthModal(){var m=document.getElementById('authModal');if(m)m.classList.remove('open');document.body.style.overflow='';}
function switchAuthTab(tab){
  var lp=document.getElementById('auth-pane-login'),rp=document.getElementById('auth-pane-register');
  var lt=document.getElementById('auth-tab-login'),rt=document.getElementById('auth-tab-register');
  if(!lp)return;
  if(tab==='login'){lp.classList.add('active');rp.classList.remove('active');lt.classList.add('active');rt.classList.remove('active');ihqAuthLoginResetPanels();}
  else{lp.classList.remove('active');rp.classList.add('active');lt.classList.remove('active');rt.classList.add('active');}
}
function toggleAuthCh(method,cb){
  var input=document.getElementById('auth-chi-'+method);if(input)input.classList.toggle('show',cb.checked);
  var any=document.querySelectorAll('.auth-ch-group input[type=checkbox]:checked').length>0;
  var cs=document.getElementById('auth-challenge-section');if(cs)cs.style.display=any?'block':'none';
}
function selectAuthComp(value,card){
  document.querySelectorAll('.auth-comp-card').forEach(function(c){c.classList.remove('sel');});
  card.classList.add('sel');var radio=card.querySelector('input[type=radio]');if(radio)radio.checked=true;
  var gs=document.getElementById('auth-genius-section');if(gs)gs.style.display='block';
}
function handleAuthRegister(e){
  e.preventDefault();
  var email=document.getElementById('auth-reg-email')?document.getElementById('auth-reg-email').value:'';
  var firstName=document.getElementById('auth-reg-first')?document.getElementById('auth-reg-first').value:'';
  var lastName=document.getElementById('auth-reg-last')?document.getElementById('auth-reg-last').value:'';
  var platformHandle=document.getElementById('auth-reg-handle')?document.getElementById('auth-reg-handle').value:'';
  var challengeType=document.querySelector('input[name="auth_challenge_type"]:checked');
  var errBox=document.getElementById('auth-reg-err');
  var btn=document.getElementById('auth-reg-btn');
  errBox.style.display='none';
  var cm=document.querySelectorAll('.auth-ch-group input[type=checkbox]:checked');
  if(cm.length===0){errBox.textContent='Please select at least one communication method.';errBox.style.display='block';return;}
  if(!email){errBox.textContent='Please enter your email address.';errBox.style.display='block';return;}
  if(!firstName||!lastName){errBox.textContent='Please enter your first and last name.';errBox.style.display='block';return;}
  var md={};var af=true;
  cm.forEach(function(cb){var m=cb.value;var inp=document.getElementById('auth-chi-'+m);var v=inp?inp.value.trim():'';if(!v){af=false;}else{md[m]=v;}});
  if(!af){errBox.textContent='Please enter contact info for all selected methods.';errBox.style.display='block';return;}
  if(!challengeType){errBox.textContent='Please select a challenge type.';errBox.style.display='block';return;}
  btn.disabled=true;btn.textContent='Sending…';
  var fd=new FormData();fd.append('action','send_verification_email');fd.append('email',email);fd.append('first_name',firstName);fd.append('last_name',lastName);fd.append('platform_handle',platformHandle);fd.append('comm_methods',JSON.stringify(md));fd.append('challenge_type',challengeType.value);
  var cp=document.getElementById('auth-competition-preferences');fd.append('competition_preferences',cp?cp.value:'');fd.append('country_iso',window.ihqResolveClientCountryIsoAlpha2());fd.append('nonce','<?php echo wp_create_nonce("verification_email_nonce"); ?>');
  fetch('<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>',{method:'POST',body:fd})
    .then(function(r){return r.json();})
    .then(function(data){
      if(data.success){
        var modal=document.getElementById('authModal');var inner=modal.querySelector('.auth-modal');
        inner.innerHTML='<button class="auth-modal-x" onclick="closeAuthModal()">✕</button><div style="text-align:center;padding:32px 0"><div style="font-size:2.5rem;color:var(--gl);margin-bottom:18px">◈</div><h3 style="font-family:\'Be Vietnam Pro\',sans-serif;font-size:1.8rem;color:#fff;margin-bottom:14px">Thank You!</h3><p style="font-family:\'Be Vietnam Pro\',sans-serif;font-size:1rem;color:var(--warm);line-height:2">Thank you for starting the conversation!<br>Please check your email to verify your address.</p></div>';
      }else{errBox.textContent='There was an error. Please try again.';errBox.style.display='block';btn.disabled=false;btn.textContent='Send Verification Email';}
    })
    .catch(function(){errBox.textContent='Network error. Please try again.';errBox.style.display='block';btn.disabled=false;btn.textContent='Send Verification Email';});
}

/* ── DOMContentLoaded init ───────────────────────────────── */
document.addEventListener('DOMContentLoaded', function () {
  ihqGetModalCommCheckboxes().forEach(function (box) {
    box.addEventListener('change', function () {
      var key = box.getAttribute('data-comm-key');
      var entry = key ? document.getElementById('modal-comm-entry-' + key) : null;
      var input = key ? document.getElementById('modal-comm-input-' + key) : null;
      if (entry) entry.hidden = !box.checked;
      if (!box.checked && input) input.value = '';
      if (box.checked && input) window.setTimeout(function () { input.focus(); }, 50);
      if (key === 'telegram' && !box.checked) {
        var ti = document.getElementById('modal-reg-telegram');
        if (ti) ti.value = '';
        ihqVerifiedTelegramUsername = '';
        ihqVerifiedTelegramSessionToken = '';
        ihqVerifiedTelegramFirstName = '';
        ihqVerifiedTelegramLastName = '';
        ihqClearTelegramLoginErr();
      }
      syncModalCommCardVisual();
      ihqClearModalCommErr();
    });
  });
  syncModalCommCardVisual();

  document.getElementById('mainModal').addEventListener('click', function (e) {
    if (e.target === this) closeModal();
  });
  var authModal = document.getElementById('authModal');
  if (authModal) {
    authModal.addEventListener('click', function (e) {
      if (e.target === this) closeAuthModal();
    });
  }

  var urlParams = new URLSearchParams(window.location.search);
  if (urlParams.get('welcome') === 'true') openAuthModal('login');
});
</script>

<div style="display:none">
<?php get_footer(); ?>
</div>
