<?php
/**
 * Template Name: Portal home mr claude
 * Description: A custom template for displaying the influencer HQ.
 *
 * @package Avantage_Baccarat
 */

add_action('wp_enqueue_scripts', function() {
    wp_enqueue_style('homepage-style-claude', get_template_directory_uri() . '/css/homepage-style-claude.css');
});

get_header();

$ihq_modal_reg_nonce       = wp_create_nonce( 'ihq_reg_code_nonce' );
$ihq_modal_login_nonce     = wp_create_nonce( 'ihq_login_code_nonce' );
$ihq_telegram_login_pubkey_nonce = wp_create_nonce( 'ihq_telegram_login_pubkey' );
$ihq_cf_country_iso_alpha2 = ihq_get_cloudflare_country_iso_alpha2();
$ihq_telegram_client_id    = 0;
if ( defined( 'IHQ_TELEGRAM_LOGIN_CLIENT_ID' ) && preg_match( '/^\d+$/', (string) IHQ_TELEGRAM_LOGIN_CLIENT_ID ) ) {
	$ihq_telegram_client_id = (int) IHQ_TELEGRAM_LOGIN_CLIENT_ID;
}
$ihq_modal_telegram_lbl_default = __( 'We\'ll note your @username; code still arrives by email', 'avantage-baccarat' );
if ( $ihq_telegram_client_id > 0 ) {
	$ihq_modal_telegram_lbl_default = __( 'Sign in with Telegram to verify your @username — code still arrives by email', 'avantage-baccarat' );
}
$ihq_modal_telegram_verified_lbl = __( 'Verified — ', 'avantage-baccarat' );
$ihq_turnstile_site_modal  = '';
if ( function_exists( 'ihq_turnstile_is_configured' ) && ihq_turnstile_is_configured() && defined( 'CF_TURNSTILE_SITE_KEY' ) ) {
	$ihq_turnstile_site_modal = CF_TURNSTILE_SITE_KEY;
}
?>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700;900&family=Be+Vietnam+Pro:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400&display=swap" rel="stylesheet">
<?php if ( $ihq_turnstile_site_modal !== '' ) : ?>
<script src="https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit" async defer></script>
<?php endif; ?>

<style>
:root {
  color-scheme: dark;
  --gl: #F0C93A;
  --bg3: #161612;
  --warm: #EAD9B0;
}
.field-error {
  box-sizing: border-box;
  border: 2px solid rgba(255, 75, 75, 0.95) !important;
  box-shadow: 0 0 0 4px rgba(255, 75, 75, 0.16);
  animation: field-error-pulse 0.9s ease both;
}
@keyframes field-error-pulse {
  0%,100% { transform: translateX(0); }
  15% { transform: translateX(-4px); }
  30% { transform: translateX(4px); }
  45% { transform: translateX(-2px); }
  60% { transform: translateX(2px); }
  75% { transform: translateX(-1px); }
}

/* Language dropdown (portal-header parity) */
.header-lang-wrap{display:none}
.header-lang-btn{background:transparent;border:none;padding:4px 6px;cursor:pointer;display:flex;align-items:center;justify-content:center;line-height:0;border-radius:6px;color:var(--gl);transition:opacity .2s,background .2s}
.header-lang-btn:hover{opacity:.9;background:rgba(240,201,58,.08)}
.header-lang-btn:focus-visible{outline:2px solid var(--gl);outline-offset:2px}
.header-lang-btn svg{display:block}
.header-lang-dropdown{position:absolute;top:calc(100% + 8px);right:0;min-width:188px;background:var(--bg3);border:1px solid rgba(240,201,58,.35);border-radius:8px;padding:6px 0;box-shadow:0 12px 40px rgba(0,0,0,.55);z-index:120;opacity:0;visibility:hidden;transform:translateY(-6px);transition:opacity .25s ease,visibility .25s ease,transform .25s ease;pointer-events:none}
.header-lang-dropdown.open{opacity:1;visibility:visible;transform:translateY(0);pointer-events:auto}
.header-lang-option{display:block;padding:12px 18px;font-family:'Be Vietnam Pro',sans-serif;font-size:.82rem;font-weight:500;color:var(--warm);text-decoration:none;letter-spacing:.04em;border-bottom:1px solid rgba(240,201,58,.12);transition:background .15s,color .15s}
.header-lang-option:last-child{border-bottom:none}
.header-lang-option:hover,.header-lang-option:focus{background:rgba(240,201,58,.1);color:var(--gl);outline:none}

/* Main modal: Email / Telegram comm + registration steps */
.modal-comm-grid{display:flex;flex-direction:column;gap:12px;margin:16px 0 8px}
.modal-comm-card{display:flex;align-items:center;gap:14px;padding:14px 16px;border:1px solid rgba(240,201,58,.28);border-radius:10px;cursor:pointer;transition:background .15s,border-color .15s;background:rgba(0,0,0,.2)}
.modal-comm-card:hover{border-color:rgba(240,201,58,.5)}
.modal-comm-card.is-on{border-color:var(--gl);background:rgba(240,201,58,.08)}
.modal-comm-card input{width:22px;height:22px;accent-color:var(--gl);cursor:pointer;flex-shrink:0}
.modal-comm-ico{width:44px;height:44px;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.modal-comm-ico svg{width:24px;height:24px}
.modal-comm-text .ch-name{font-family:'Cinzel',serif;font-size:1.05rem;color:var(--white)}
.modal-comm-text .ch-lbl{font-family:'Be Vietnam Pro',sans-serif;font-size:.78rem;color:var(--warm);margin-top:2px}
.modal-reg-field{margin-bottom:16px;text-align:left}
.modal-reg-field label{display:block;font-family:'Be Vietnam Pro',sans-serif;font-size:.75rem;letter-spacing:.12em;text-transform:uppercase;color:var(--gl);margin-bottom:8px}
.modal-reg-field input{width:100%;box-sizing:border-box;padding:12px 14px;border-radius:8px;border:1px solid rgba(240,201,58,.25);background:var(--bg3);color:var(--white);font-family:'Be Vietnam Pro',sans-serif;font-size:1rem}
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
</style>

<main id="primary" class="site-main">

<nav>
  <a class="logo" href="<?php echo esc_url(home_url('/')); ?>"><img style="max-width: 50%;" src="<?php echo get_template_directory_uri(); ?>/images/logo-home-claude.jpg" alt="influencerHQ"></a>
  <div style="display:flex;align-items:center;gap:10px">
    <div class="header-lang-wrap">
      <button type="button" class="header-lang-btn" id="headerLangBtn" aria-label="<?php esc_attr_e( 'Select language', 'avantage-baccarat' ); ?>" aria-expanded="false" aria-haspopup="true">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <circle cx="12" cy="12" r="10"></circle>
          <line x1="2" y1="12" x2="22" y2="12"></line>
          <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path>
        </svg>
      </button>
      <div class="header-lang-dropdown" id="headerLangDropdown" role="menu">
        <a href="#" class="header-lang-option" role="menuitem" data-lang="en">English</a>
        <a href="#" class="header-lang-option" role="menuitem" data-lang="es">Español</a>
        <a href="#" class="header-lang-option" role="menuitem" data-lang="fr">Français</a>
        <a href="#" class="header-lang-option" role="menuitem" data-lang="de">Deutsch</a>
        <a href="#" class="header-lang-option" role="menuitem" data-lang="zh">中文</a>
      </div>
    </div>
   
    
  </div>
</nav>

<section class="hero">
  <div class="hero-lines"></div>
  <div class="hero-glow"></div>
  <!-- <span class="hero-eye">Influencer Headquarters</span> -->
  <h1>Influence was never meant to be rented.</h1>
  <p class="hero-sub">We believe those who drive the energy deserve to share in what they help build.</p>
  <div class="hero-btns">
    <?php if ( is_user_logged_in() ) : ?>
      <a class="btn-gold" href="<?php echo esc_url( home_url( '/portal/portal-home/' ) ); ?>">Go to portal</a>
    <?php else : ?>
      <button class="btn-gold" onclick="openModal()">Yes — Let's Start the Conversation</button>
    <?php endif; ?>
  </div>
  <div class="hero-dealer-row dealer-row">
    <div class="dealer-image-container">
      <p class="concierge-text-above hero-dealer-text">We believe conversations should be easy.</p>
      <div class="dealer-image-wrap">
        <div class="dealer-gradient-overlay" aria-hidden="true"></div>
        <img src="<?php echo esc_url( get_template_directory_uri() . '/images/concierge.png' ); ?>" alt="" class="dealer-image dealer-image--hero">
      </div>
      <a href="#" class="concierge-title hero-dealer-link">Talk Now - Executive Concierge</a>
    </div>
  </div>
  <div class="scroll-hint"><span>Discover</span><div class="scroll-line"></div></div>
</section>

<div class="acc-wrap">
  <div class="tap-hint gone" id="tapHint"><span>Tap any heading to explore</span></div>

  <div class="acc-sec" id="s1">
    <div class="acc-hd" onclick="tog('s1')">
      <span class="acc-eye">Who We Are</span>
      <span class="acc-title">Our Beliefs</span>
      <div class="acc-chev"><svg viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"></polyline></svg></div>
    </div>
    <div class="acc-body"><div class="acc-inner">
      <p class="beliefs-intro">People don't buy what you do — They buy what you believe.</p>
      <p class="sec-p">The beliefs below guide every opportunity at Influencer Headquarters where influencers partner with global platforms that value long-term contribution, not one-time campaigns.</p>
      <span class="we-believe">WE BELIEVE</span>
      <div class="belief"><span class="b-num">•</span><span class="b-text">Influence deserves more than short-term payouts.</span></div>
      <div class="belief"><span class="b-num">•</span><span class="b-text">Your voice should create long-term value.</span></div>
      <div class="belief"><span class="b-num">•</span><span class="b-text">Those who drive the energy deserve to share in what they help build.</span></div>
      <div class="belief"><span class="b-num">•</span><span class="b-text">When your influence grows a platform, you should share in the value created by that growth.</span></div>
    </div></div>
  </div>

  <div class="acc-sec" id="s2">
    <div class="acc-hd" onclick="tog('s2')">
      <span class="acc-eye">The Opportunity</span>
      <span class="acc-title">The Future of the Influencer Marketplace</span>
      <div class="acc-chev"><svg viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"></polyline></svg></div>
    </div>
    <div class="acc-body"><div class="acc-inner">
      <p class="sec-p">Brands everywhere rely on influencers to shape culture, spark engagement, and build communities. But traditional partnerships often end sooner than the value you provide.</p>
      <p class="sec-p">Our partners' platforms reward ongoing contribution — giving influencers a meaningful stake in the success they help build. As these platforms expand across new markets, the value of your influence grows with them.</p>
    </div></div>
  </div>

  <div class="acc-sec" id="s3">
    <div class="acc-hd" onclick="tog('s3')">
      <span class="acc-eye">A Lesson from History</span>
      <span class="acc-title"><em>Ownership</em> Has Always Been the Difference</span>
      <div class="acc-chev"><svg viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"></polyline></svg></div>
    </div>
    <div class="acc-body"><div class="acc-inner">
      <p class="sec-p">We believe that Influencers deserve the opportunity to share in ownership.</p>
      <div class="h-card h-card--side">
        <div class="card-img-wrap magic-img"><img src="<?php echo esc_url(get_template_directory_uri()); ?>/images/magic-j.png" alt="Magic Johnson" style="width:100%;height:100%;object-fit:contain;display:block"></div>
        <div class="h-body">
          <div class="h-name">Magic Johnson</div>
          <div class="h-text">The biggest missed equity-for-influence deal in history was Nike's 11 cent stock deal offered to NBA superstar Magic Johnson. Magic chose Adidas' $100,000 cash instead.<br><br>Today's value of the Nike stock Magic turned down? $5.4 billion.</div>
          <div class="h-result">— and that's why equity-for-influence changes everything.</div>
        </div>
      </div>
      <div class="h-card h-card--side h-card--side-rev">
        <div class="card-img-wrap magic-img"><img src="<?php echo esc_url(get_template_directory_uri()); ?>/images/alix-hair.png" alt="Alix Earle" style="width:100%;height:100%;object-fit:contain;display:block"></div>
        <div class="h-body">
          <div class="h-name">Alix Earle</div>
          <div class="h-text">American Influencer Alix Earle chose an ownership-based partnership with Poppi instead of traditional cash. When Poppi was acquired by PepsiCo for well over a billion dollars, her choice became a defining moment in the creator economy.</div>
          <div class="h-result">Ownership matters — and influence deserves the chance to share in it.</div>
        </div>
      </div>
    </div></div>
  </div>

  <div class="acc-sec" id="s4">
    <div class="acc-hd" onclick="tog('s4')">
      <span class="acc-eye">The Foundation</span>
      <span class="acc-title">Competition: The Oldest Language in the World</span>
      <div class="acc-chev"><svg viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"></polyline></svg></div>
    </div>
    <div class="acc-body"><div class="acc-inner">
      <p class="sec-p">It began in ancient Greece, where the first Olympic Games ignited national pride. It filled the arenas of Rome. It lives in Asia's legendary traditions — games of mastery, precision, rhythm, and strategy celebrated for centuries.</p>
      <div class="pillars">
        <div class="pillar">Global Sports</div><div class="pillar">Music Battles</div><div class="pillar">Esports Arenas</div>
        <div class="pillar">Creator Challenges</div><div class="pillar">Digital Tournaments</div><div class="pillar">Moments We Share</div>
      </div>
      <div class="quote">
        <p>Competition is Universal<br>Competition is Emotional<br>Competition is Human</p>
        <cite>Throughout history, competition has always needed leaders who elevate every moment, ignite anticipation, and unite communities. Today, those leaders are influencers.</cite>
      </div>
    </div></div>
  </div>

  <div class="acc-sec" id="s5">
    <div class="acc-hd" onclick="tog('s5')">
      <span class="acc-eye">The Stage</span>
      <span class="acc-title">Elegance. Prestige. <em>Global Legacy.</em></span>
      <div class="acc-chev"><svg viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"></polyline></svg></div>
    </div>
    <div class="acc-body"><div class="acc-inner">
      <div class="sailboat-wrap" style="margin-left:-32px;margin-right:-32px;position:relative;overflow:hidden">
        <span class="sailboat-label sailboat-label--top">2026 World Cup</span>
        <img src="<?php echo esc_url(get_template_directory_uri()); ?>/images/miami-png.png" alt="miami" style="max-width:100%;display:block;margin:0 auto">
        <span class="sailboat-label sailboat-label--bottom">Miami</span>
      </div>
      <p class="sec-p" style="padding-top:30px">The stages influencers step onto carry the same timeless elements found in the world's greatest competitions. Elegance that never goes out of style. Prestige that spans centuries.</p>
      <p class="sec-p">A global following that crosses borders and cultures. Intensity and emotion in every moment. Influence deserves a stage worthy of that legacy — and now, it has one.</p>
    </div></div>
  </div>

  <div class="acc-sec" id="s6">
    <div class="acc-hd" onclick="tog('s6')">
      <span class="acc-eye">The Technology</span>
      <span class="acc-title"><em>Equity You Can Count On</em></span>
      <div class="acc-chev"><svg viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"></polyline></svg></div>
    </div>
    <div class="acc-body"><div class="acc-inner">
      <p class="sec-p">Every follower you invite, every level of participation they generate, and every moment of play they create is tracked and verified by <strong>GENIUS</strong> — a best-in-class technology set up expressly for this purpose.</p>
      <div class="genius-box">
        <div class="g-item"><span class="g-icon">◈</span><span class="g-text">Every share recorded at the exact moment it is earned</span></div>
        <div class="g-item"><span class="g-icon">◈</span><span class="g-text">Nothing is pooled, estimated, or assumed</span></div>
      </div>
      <p class="sec-p" style="text-align:center;color:var(--gl);font-weight:400">When your followers participate, Genius records it. When your network grows, Genius tracks it. Your equity is protected.</p>
    </div></div>
  </div>

  <div class="acc-sec open" id="s7">
    <div class="acc-hd">
      <span class="acc-eye">The Global Stage</span>
      <span class="acc-title">International Competition Series</span>
      <div class="acc-chev"><svg viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"></polyline></svg></div>
    </div>
    <div class="acc-body"><div class="acc-inner">
      <div class="dual-appearance-wrap">
        <div class="appearance-ph"><img src="<?php echo esc_url(get_template_directory_uri()); ?>/images/girl-stream.jpg" alt="Influencer Appearance" style="width:100%;height:100%;object-fit:contain;display:block"></div>
        <div class="appearance-ph"><img src="<?php echo esc_url(get_template_directory_uri()); ?>/images/boy-stream.jpg" alt="Influencer Appearance" style="width:100%;height:100%;object-fit:contain;display:block"></div>
      </div>
      <div class="comp-list comp-list--s7">
        <div class="comp-card" id="cw2" onclick="pickComp('cw2')"><div class="comp-chk"><span class="chk-in">✓</span></div><div class="comp-body"><span class="comp-tag">Global Stage</span><div class="comp-title">Influencer World Competition</div><div class="comp-desc">Join thousands of Influencers and their followers. Compete Thursday night through Sunday night.</div></div></div>
        <div class="comp-card" id="cp2" onclick="pickComp('cp2')"><div class="comp-chk"><span class="chk-in">✓</span></div><div class="comp-body"><span class="comp-tag">Community Competition</span><div class="comp-title">Community Competition</div><div class="comp-desc">A one week competition between your followers who choose to participate with you at the helm cheering them all on to victory.</div></div></div>
      </div>
      <input type="hidden" id="ihq-s7-competition-selection" value="" autocomplete="off">
      <div id="s7-competition-submit-wrap" class="s7-competition-submit-wrap" style="display:none;text-align:center;margin:24px 0 16px;">
        <button type="button" class="send-btn" id="s7-competition-submit"><?php esc_html_e( 'Submit', 'avantage-baccarat' ); ?></button>
      </div>
      <div id="s7-competition-followup" class="s7-competition-followup" style="display:none;text-align:center;margin-top:8px;margin-bottom:8px;padding:18px 16px;border:1px solid rgba(240,201,58,.28);border-radius:12px;background:rgba(0,0,0,.25);font-family:'Be Vietnam Pro',sans-serif;color:var(--warm);line-height:1.85;">
        <p style="margin:0;font-size:.95rem;"><?php esc_html_e( 'Provide favorite method of communication first in the', 'avantage-baccarat' ); ?> <a href="#" id="s7-open-registration-modal" style="color:var(--gl);text-decoration:underline;font-weight:600;"><?php esc_html_e( 'registration', 'avantage-baccarat' ); ?></a> <?php esc_html_e( 'form.', 'avantage-baccarat' ); ?></p>
      </div>
      <p class="sec-p">Influencers are automatically invited to appear on our partners' International Competition Series — a 24-hour global competition stage. Head-to-head competition features two Influencers from their own locations streaming reactions, commentary, and competitive energy in real time.</p>
      <p class="sec-p" style="color:var(--gl)">English · Mandarin · Cantonese · Korean · Japanese · Thai · Vietnamese</p>
    </div></div>
  </div>

  <div class="acc-sec" id="s8">
    <div class="acc-hd" onclick="tog('s8')">
      <span class="acc-eye">Your Invitation</span>
      <span class="acc-title">The Competitions</span>
      <div class="acc-chev"><svg viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"></polyline></svg></div>
    </div>
    <div class="acc-body"><div class="acc-inner">
      <div class="ms2-quote" style="margin-bottom:28px">When your followers watch you play, cheer, and celebrate, they become part of a shared experience that unites people the same way great sports, music, and global competitions always have.</div>
      <p class="sec-p" style="text-align:center;color:var(--white);margin-bottom:8px;font-family:'Cinzel',serif;font-size:1.4rem">Two competitions. One stage. All yours.</p>
      <p class="sec-p" style="text-align:center;color:var(--warm);margin-bottom:32px;font-size:1rem">Choose the one that feels right for you right now. You can always do both.</p>

      <p style="font-family:'Be Vietnam Pro',sans-serif;font-size:.7rem;letter-spacing:.4em;text-transform:uppercase;color:var(--gl);font-weight:700;margin-bottom:12px">Start Here — Community Competition</p>
      <div class="path-card">
        <div class="path-title">One Week. Your Community.</div>
        <p class="path-desc">A one week competition between your followers who choose to participate with you at the helm cheering them all on to victory.<br><br>Welcome to the stage that was built for you.</p>
      </div>

      <div style="height:1px;background:rgba(240,201,58,.2);margin:32px 0"></div>

      <p style="font-family:'Be Vietnam Pro',sans-serif;font-size:.7rem;letter-spacing:.4em;text-transform:uppercase;color:var(--gl);font-weight:700;margin-bottom:12px">Go Big — World Competition</p>
      <div class="path-card">
        <span class="path-tag">This Weekend · Thursday Night through Sunday Night</span>
        <div class="path-title"><div style="text-align:center;line-height:1.9">
          <span style="display:block">You and your followers</span>
          <span style="display:block;font-family:'Be Vietnam Pro',sans-serif;font-size:1.1rem;letter-spacing:.4em;color:var(--gl);font-weight:700;margin:6px 0">VS</span>
          <span style="display:block">Other Influencers and their followers</span>
          <span style="display:block;margin-top:12px;color:var(--gl)">One global stage!</span>
        </div></div>
        <div style="width:40px;height:2px;background:var(--gl);margin:18px 0"></div>
        <p class="path-desc">Join creators and their followers from across the world. Compete anytime Thursday night through Sunday night.</p>
      </div>

      <p style="font-family:'Cinzel',serif;font-size:1.2rem;font-style:italic;color:var(--warm);text-align:center;margin:32px 0 24px;line-height:1.6">The stage is set. The competition is waiting.</p>
      <div style="text-align:center;margin-bottom:16px">
        <button class="btn-scarlet" id="competeBtn" onclick="openCompetition()" style="margin:0 auto;display:block;opacity:0">Yes — I'm Ready to Compete</button>
      </div>
    </div></div>
  </div>

</div><!-- .acc-wrap -->

<div class="final-band">
  <div class="cta-line" style="margin-bottom:28px"></div>
  <h2>Your influence is<br><em>worth more than a campaign.</em></h2>
  <p>We're ready when you are.<br>Reach us through the channel that works best for you.</p>
  <div id="cfinal">
    <?php if ( is_user_logged_in() ) : ?>
      <a class="btn-gold drift-on-scroll" id="finalBtn" href="<?php echo esc_url( home_url( '/portal/portal-home/' ) ); ?>" style="margin:0 auto;display:block">Go to portal</a>
    <?php else : ?>
      <button class="btn-gold drift-on-scroll" id="finalBtn" onclick="openModal('cfinal')" style="margin:0 auto;display:block">Yes — Let's Start the Conversation</button>
    <?php endif; ?>
  </div>
</div>

<footer><p>© <?php echo esc_html(date('Y')); ?> InfluencerHQ · All Rights Reserved</p></footer>

<!-- Contact / Competition Modal -->
<div class="overlay" id="mainModal">
  <div class="modal">
    <button class="modal-x" onclick="closeModal()">✕</button>
    <div class="mstep on" id="ms1">
      <span class="m-eye">Get in Touch</span>
      <h3 class="m-title">Let's Start the Conversation</h3>
      <p class="m-sub">Choose <strong style="color:var(--gl)">Email</strong> or <strong style="color:var(--gl)">Telegram</strong> as how we’ll reach you. You’ll complete signup on the next step.</p>
      <p class="m-benefit">We believe true partnership means standing beside you — helping more people discover your voice, your content, and your audience.</p>
      <div class="modal-comm-grid">
        <label class="modal-comm-card" id="modal-comm-card-email" for="modal-comm-email">
          <input type="checkbox" id="modal-comm-email" autocomplete="off" aria-label="<?php esc_attr_e( 'Email', 'avantage-baccarat' ); ?>">
          <div class="modal-comm-ico" style="background:rgba(240,201,58,.15);color:var(--gl)">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
          </div>
          <div class="modal-comm-text"><div class="ch-name"><?php esc_html_e( 'Email', 'avantage-baccarat' ); ?></div><span class="ch-lbl"><?php esc_html_e( 'Receive your code by email', 'avantage-baccarat' ); ?></span></div>
        </label>
        <label class="modal-comm-card" id="modal-comm-card-telegram" for="modal-comm-telegram">
          <input type="checkbox" id="modal-comm-telegram" autocomplete="off" aria-label="<?php esc_attr_e( 'Telegram', 'avantage-baccarat' ); ?>">
          <div class="modal-comm-ico" style="background:rgba(0,136,204,.18);color:#2AABEE">
            <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/></svg>
          </div>
          <div class="modal-comm-text"><div class="ch-name"><?php esc_html_e( 'Telegram', 'avantage-baccarat' ); ?></div><span class="ch-lbl" id="modal-comm-telegram-lbl"><?php echo esc_html( $ihq_modal_telegram_lbl_default ); ?></span></div>
        </label>
      </div>
      <p class="modal-comm-tg-err" id="modal-comm-tg-err" style="display:none;margin:4px 0 0;font-family:'Be Vietnam Pro',sans-serif;font-size:.85rem;color:#f85149;text-align:center"></p>

      <div style="margin:24px 0 4px">
        <div style="font-family:'Cinzel',serif;font-size:1.6rem;line-height:1.4;color:var(--white);margin-bottom:14px">Favorite Social Media</div>
        <div class="ch-list social-list">
          <div class="ch-sel" id="sel-social-facebook" onclick="toggleCh('social-facebook')">
            <div class="ch-chk" id="chk-social-facebook"></div>
            <div class="ch-info"><div class="ch-name">Facebook</div></div>
            <input class="ch-input social-inline-input" type="text" placeholder="paste your channel link">
          </div>

          <div class="ch-sel" id="sel-social-instagram" onclick="toggleCh('social-instagram')">
            <div class="ch-chk" id="chk-social-instagram"></div>
            <div class="ch-info"><div class="ch-name">Instagram</div></div>
            <input class="ch-input social-inline-input" type="text" placeholder="@yourhandle">
          </div>

          <div class="ch-sel" id="sel-social-tiktok" onclick="toggleCh('social-tiktok')">
            <div class="ch-chk" id="chk-social-tiktok"></div>
            <div class="ch-info"><div class="ch-name">TikTok</div></div>
            <input class="ch-input social-inline-input" type="text" placeholder="@yourhandle">
          </div>

          <div class="ch-sel" id="sel-social-x" onclick="toggleCh('social-x')">
            <div class="ch-chk" id="chk-social-x"></div>
            <div class="ch-info"><div class="ch-name">X</div></div>
            <input class="ch-input social-inline-input" type="text" placeholder="@yourhandle">
          </div>

          <div class="ch-sel" id="sel-social-youtube" onclick="toggleCh('social-youtube')">
            <div class="ch-chk" id="chk-social-youtube"></div>
            <div class="ch-info"><div class="ch-name">YouTube</div></div>
            <input class="ch-input social-inline-input" type="text" placeholder="paste your channel link">
          </div>
        </div>
      </div>

      <div class="modal-lower-panel">
        <span class="m-eye">One More Thing</span>
        <h3 class="m-title">Glory is earned one weekend at a time.</h3>
        <p class="m-ctx">Yours starts now. Choose your competition and we'll send you and your followers everything you need to get started.</p>
        <div class="ms2-quote">When your followers watch you play, cheer, and celebrate, they become part of a shared experience that unites people the same way great sports, music, and global competitions always have.</div>
        <div class="comp-list">
          <div class="comp-card" id="cw" onclick="pickComp('cw')"><div class="comp-chk"><span class="chk-in">✓</span></div><div class="comp-body"><span class="comp-tag">Global Stage</span><div class="comp-title">Influencer World Competition</div><div class="comp-desc">Join thousands of Influencers and their followers. Compete Thursday night through Sunday night.</div></div></div>
          <div class="comp-card" id="cp" onclick="pickComp('cp')"><div class="comp-chk"><span class="chk-in">✓</span></div><div class="comp-body"><span class="comp-tag">Community Competition</span><div class="comp-title">Community Competition</div><div class="comp-desc">A one week competition between your followers who choose to participate with you at the helm cheering them all on to victory.</div></div></div>
        </div>
      </div>

      <button type="button" class="send-btn" id="sendbtn" onclick="onModalSubmit()">Continue</button>

      <p class="m-note">All conversations are private and confidential.<br>We operate across time zones. Expect a reply within minutes.</p>
    </div>

    <div class="mstep" id="ms-reg">
      <span class="m-eye"><?php esc_html_e( 'Almost there', 'avantage-baccarat' ); ?></span>
      <h3 class="m-title"><?php esc_html_e( 'Your details', 'avantage-baccarat' ); ?></h3>
      <p class="m-sub" style="margin-bottom:20px"><?php esc_html_e( 'Tell us who you are. We’ll email your 6-digit registration code.', 'avantage-baccarat' ); ?></p>
      <div class="modal-reg-field">
        <label for="modal-reg-first"><?php esc_html_e( 'First name', 'avantage-baccarat' ); ?> <span style="color:#f85149">*</span></label>
        <input type="text" id="modal-reg-first" autocomplete="given-name" required maxlength="120" placeholder="<?php esc_attr_e( 'First name', 'avantage-baccarat' ); ?>">
      </div>
      <div class="modal-reg-field">
        <label for="modal-reg-last"><?php esc_html_e( 'Last name', 'avantage-baccarat' ); ?> <span style="color:#f85149">*</span></label>
        <input type="text" id="modal-reg-last" autocomplete="family-name" required maxlength="120" placeholder="<?php esc_attr_e( 'Last name', 'avantage-baccarat' ); ?>">
      </div>
      <div class="modal-reg-field">
        <label for="modal-reg-email"><?php esc_html_e( 'Email', 'avantage-baccarat' ); ?> <span style="color:#f85149">*</span></label>
        <input type="email" id="modal-reg-email" autocomplete="email" required placeholder="your@email.com">
      </div>
      <div class="modal-reg-field" id="modal-reg-telegram-wrap" style="display:none">
        <label for="modal-reg-telegram"><?php esc_html_e( 'Telegram username', 'avantage-baccarat' ); ?> <span style="color:#f85149">*</span></label>
        <input type="text" id="modal-reg-telegram" autocomplete="username" maxlength="80" placeholder="@username">
      </div>
      <?php if ( $ihq_turnstile_site_modal !== '' ) : ?>
      <div id="modal-reg-turnstile" data-sitekey="<?php echo esc_attr( $ihq_turnstile_site_modal ); ?>"></div>
      <?php endif; ?>
      <p class="modal-step-err" id="modal-reg-err"></p>
      <div class="modal-reg-actions">
        <button type="button" class="btn-modal-back" onclick="ihqModalRegBackToMs1()"><?php esc_html_e( 'Back', 'avantage-baccarat' ); ?></button>
        <button type="button" class="send-btn" id="modal-send-code-btn" onclick="ihqModalRegSendCode()"><?php esc_html_e( 'Send code', 'avantage-baccarat' ); ?></button>
      </div>
    </div>

    <div class="mstep" id="ms-code">
      <span class="m-eye"><?php esc_html_e( 'Check your inbox', 'avantage-baccarat' ); ?></span>
      <h3 class="m-title"><?php esc_html_e( 'You’re on your way.', 'avantage-baccarat' ); ?></h3>
      <p class="modal-code-msg"><?php esc_html_e( 'Details coming in shortly. You and your followers are on the road to glory.', 'avantage-baccarat' ); ?></p>
      <p class="modal-code-msg"><?php esc_html_e( 'Registration code is sent via your provided method of communication.', 'avantage-baccarat' ); ?></p>
      <p class="modal-code-msg"><strong><?php esc_html_e( 'Enter the 6-digit code.', 'avantage-baccarat' ); ?></strong></p>
      <p class="modal-code-msg" id="modal-code-expires-note"></p>
      <div class="modal-reg-field" style="max-width:280px;margin:20px auto 0">
        <label for="modal-reg-code"><?php esc_html_e( 'Code', 'avantage-baccarat' ); ?></label>
        <input type="text" id="modal-reg-code" inputmode="numeric" pattern="[0-9]*" maxlength="6" autocomplete="one-time-code" placeholder="000000" style="text-align:center;letter-spacing:0.4em;font-size:1.35rem">
      </div>
      <p class="modal-step-err" id="modal-code-err"></p>
      <div class="modal-reg-actions">
        <button type="button" class="btn-modal-back" onclick="show('ms-reg')"><?php esc_html_e( 'Edit details', 'avantage-baccarat' ); ?></button>
        <button type="button" class="send-btn" id="modal-verify-btn" onclick="ihqModalRegVerifyCode()"><?php esc_html_e( 'Verify & continue', 'avantage-baccarat' ); ?></button>
      </div>
      <p style="font-family:'Be Vietnam Pro',sans-serif;font-size:.78rem;color:var(--warm);text-align:center;margin-top:16px;line-height:1.6"><?php esc_html_e( 'Didn’t receive it? Go back and tap Send code again.', 'avantage-baccarat' ); ?></p>
    </div>

  </div>
</div>

<!-- Auth Modal (Login / Register) -->
<div class="auth-overlay" id="authModal">
  <div class="auth-modal">
    <button class="auth-modal-x" onclick="closeAuthModal()">✕</button>

    <?php if (is_user_logged_in()):
      $current_user = wp_get_current_user();
    ?>
      <div style="text-align:center;padding:24px 0">
        <div style="font-size:2rem;color:var(--gl);margin-bottom:16px">◈</div>
        <h3 style="font-family:'Cinzel',serif;font-size:1.8rem;color:var(--white);margin-bottom:14px">Welcome Back!</h3>
        <p style="font-family:'Be Vietnam Pro',sans-serif;font-size:1rem;color:var(--warm);line-height:1.9;margin-bottom:28px">
          Logged in as: <strong style="color:var(--gl)"><?php echo esc_html($current_user->user_email); ?></strong>
        </p>
        <a href="<?php echo esc_url(home_url('/portal/portal-home/')); ?>" class="auth-submit-btn" style="display:inline-block;text-decoration:none;padding:18px 40px;width:auto">Go to Portal</a>
        <div style="margin-top:16px">
          <a href="<?php echo esc_url(wp_logout_url(get_permalink())); ?>" style="font-family:'Be Vietnam Pro',sans-serif;font-size:.8rem;color:var(--warm);letter-spacing:.1em">Logout</a>
        </div>
      </div>
    <?php else: ?>

      <?php
      $welcome = isset($_GET['welcome']) && $_GET['welcome'] === 'true';
      if ($welcome):
      ?>
        <div style="text-align:center;padding:24px 0">
          <div style="font-size:2.5rem;color:var(--gl);margin-bottom:16px">◈</div>
          <h3 style="font-family:'Cinzel',serif;font-size:1.8rem;color:var(--white);margin-bottom:12px">Welcome to Influencer HQ!</h3>
          <p style="font-family:'Be Vietnam Pro',sans-serif;font-size:1rem;color:var(--warm);line-height:1.9">Your account has been successfully created!<br>You're now ready to start your journey with us.</p>
        </div>
      <?php else: ?>

      <div class="auth-tabs">
        <button class="auth-tab-btn active" id="auth-tab-login" onclick="switchAuthTab('login')">Login</button>
        <button class="auth-tab-btn" id="auth-tab-register" onclick="switchAuthTab('register')">Register</button>
      </div>

      <!-- LOGIN PANE (passwordless: email → 6-digit code) -->
      <div class="auth-pane active" id="auth-pane-login">
        <div id="auth-login-step-email" style="max-width:480px;margin:0 auto">
          <p class="auth-section-sub" style="margin-bottom:16px">Enter your email. We’ll send a 6-digit sign-in code.</p>
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
            <div class="auth-check auth-ch-block">
              <input type="checkbox" id="auth-ch-line" value="line" onchange="toggleAuthCh('line',this)">
              <label for="auth-ch-line">LINE</label>
            </div>
            <input type="text" class="auth-ch-input" id="auth-chi-line" placeholder="Your LINE ID" data-method="line">

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
            <input type="text" class="auth-ch-input" id="auth-chi-wechat" placeholder="Your WeChat ID" data-method="wechat">
          </div>

          <div id="auth-challenge-section" style="display:none">
            <hr class="auth-section-sep">
            <h3 class="auth-section-title">Choose Your Path to Lead Global Competition</h3>
            <p class="auth-section-sub">After your email is verified, you'll enter HQ², your private influencer portal. Choose how you want to lead:</p>

            <div class="auth-comp-cards">
              <div class="auth-comp-card" id="auth-cc-weekend" onclick="selectAuthComp('weekend_world',this)">
                <input type="radio" name="auth_challenge_type" value="weekend_world" id="auth-comp-weekend">
                <div class="auth-comp-card-inner">
                  <h4>Weekend World Challenge</h4>
                  <p>A global challenge open to all influencers and their followers — a massive international competition.</p>
                </div>
              </div>
              <div class="auth-comp-card" id="auth-cc-community" onclick="selectAuthComp('community_challenge',this)">
                <input type="radio" name="auth_challenge_type" value="community_challenge" id="auth-comp-community">
                <div class="auth-comp-card-inner">
                  <h4>Community Challenge</h4>
                  <p>A challenge created by you, for your community, on your schedule — with an option to stream live.</p>
                </div>
              </div>
              <div class="auth-comp-card" id="auth-cc-later" onclick="selectAuthComp('maybe_later',this)">
                <input type="radio" name="auth_challenge_type" value="maybe_later" id="auth-comp-later">
                <div class="auth-comp-card-inner">
                  <h4>Thanks, maybe later</h4>
                </div>
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
                <?php foreach(['Face ID','WeChat Face','Alipay Face','LINE Face','KakaoTalk','Biometric ID'] as $fr): ?>
                  <button type="button" style="padding:10px 16px;background:transparent;border:1px solid rgba(240,201,58,.4);color:var(--warm);font-family:'Be Vietnam Pro',sans-serif;font-size:.8rem;cursor:pointer"><?php echo esc_html($fr); ?></button>
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
    if (/^[A-Za-z]{2}$/.test(seed)) {
      return seed.toUpperCase();
    }
    return 'US';
  };
}
console.log('[IHQ] Country ISO for POST (IHQ_CF_COUNTRY_SEED_ISO):', window.ihqResolveClientCountryIsoAlpha2());
var started = false;
var src = null;

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
  ihqAuthLoginRemoveTurnstile();
  window.setTimeout(ihqAuthLoginRenderTurnstileWhenNeeded, 80);
}

function ihqAuthLoginBackToEmail() {
  ihqAuthLoginRemoveTurnstile();
  ihqAuthLoginSignupToken = '';
  var cer = document.getElementById('auth-login-code-err');
  if (cer) cer.textContent = '';
  document.getElementById('auth-login-step-code').style.display = 'none';
  document.getElementById('auth-login-step-email').style.display = 'block';
  window.setTimeout(ihqAuthLoginRenderTurnstileWhenNeeded, 80);
}

function ihqAuthLoginSendCode() {
  var errBox = document.getElementById('auth-login-err');
  var infoBox = document.getElementById('auth-login-info');
  if (errBox) { errBox.style.display = 'none'; errBox.textContent = ''; }
  if (infoBox) { infoBox.style.display = 'none'; infoBox.textContent = ''; }
  var email = document.getElementById('auth-login-email').value.trim();
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
      document.getElementById('auth-login-step-email').style.display = 'none';
      document.getElementById('auth-login-step-code').style.display = 'block';
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
  var raw = document.getElementById('auth-login-code').value.replace(/\D/g, '');
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

function syncModalCommCardVisual() {
  var em = document.getElementById('modal-comm-email');
  var tg = document.getElementById('modal-comm-telegram');
  var cardEm = document.getElementById('modal-comm-card-email');
  var cardTg = document.getElementById('modal-comm-card-telegram');
  if (cardEm && em) cardEm.classList.toggle('is-on', em.checked);
  if (cardTg && tg) cardTg.classList.toggle('is-on', tg.checked);
}

function ihqClearTelegramLoginErr() {
  var e = document.getElementById('modal-comm-tg-err');
  if (e) {
    e.textContent = '';
    e.style.display = 'none';
  }
}

function ihqShowTelegramLoginErr(msg) {
  var e = document.getElementById('modal-comm-tg-err');
  if (e) {
    e.textContent = msg || '';
    e.style.display = msg ? 'block' : 'none';
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

function ihqTryTelegramAccountLink() {
  if (!IHQ_MODAL_REG.telegramClientId) {
    return;
  }
  if (ihqTelegramOAuthBusy) {
    return;
  }
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
          { client_id: IHQ_MODAL_REG.telegramClientId, nonce: serverNonce, lang: 'en' },
          function (result) {
            if (result === false) {
              ihqTelegramOAuthBusy = false;
              return;
            }
            if (!result || result.error) {
              ihqTelegramOAuthBusy = false;
              if (result && result.error) {
                ihqShowTelegramLoginErr(String(result.error));
              }
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
                if (ti) {
                  ti.value = tu;
                }
                var lbl = document.getElementById('modal-comm-telegram-lbl');
                if (lbl) {
                  lbl.textContent = IHQ_MODAL_REG.telegramVerifiedPrefix + tu;
                }
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

function ihqModalRemoveTurnstile() {
  var host = document.getElementById('modal-reg-turnstile');
  if (!host || !IHQ_MODAL_REG.turnstileSiteKey) {
    return;
  }
  if (ihqModalTurnstileWidgetId !== null && typeof window.turnstile !== 'undefined') {
    try {
      window.turnstile.remove(ihqModalTurnstileWidgetId);
    } catch (eRes) {}
    ihqModalTurnstileWidgetId = null;
  }
  host.removeAttribute('data-rendered');
  host.innerHTML = '';
}

function ihqModalRenderTurnstileWhenNeeded() {
  if (!IHQ_MODAL_REG.turnstileSiteKey) {
    return;
  }
  var el = document.getElementById('modal-reg-turnstile');
  if (!el || el.getAttribute('data-rendered') === '1') {
    return;
  }
  if (typeof window.turnstile === 'undefined') {
    window.setTimeout(ihqModalRenderTurnstileWhenNeeded, 200);
    return;
  }
  ihqModalTurnstileWidgetId = window.turnstile.render(el, {
    sitekey: IHQ_MODAL_REG.turnstileSiteKey
  });
  el.setAttribute('data-rendered', '1');
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
  var em = document.getElementById('modal-comm-email');
  var tg = document.getElementById('modal-comm-telegram');
  if (em) em.checked = false;
  if (tg) tg.checked = false;
  syncModalCommCardVisual();
  ihqModalRemoveTurnstile();
  var telWrap = document.getElementById('modal-reg-telegram-wrap');
  if (telWrap) telWrap.style.display = 'none';
  var tgl = document.getElementById('modal-comm-telegram-lbl');
  if (tgl) {
    tgl.textContent = IHQ_MODAL_REG.telegramLblDefault;
  }
  ihqClearTelegramLoginErr();
  ihqTelegramOAuthBusy = false;
}

function ihqModalGetChallengeType() {
  if (document.getElementById('cw').classList.contains('sel')) {
    return 'weekend_world';
  }
  if (document.getElementById('cp').classList.contains('sel')) {
    return 'community_challenge';
  }
  return 'maybe_later';
}

function ihqModalGetPlatformHandle() {
  var parts = [];
  document.querySelectorAll('.social-list .ch-sel.selected').forEach(function (sel) {
    var inp = sel.querySelector('input.social-inline-input');
    if (inp && inp.value.trim()) {
      parts.push(inp.value.trim());
    }
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
  var commPrimary = document.getElementById('modal-comm-telegram').checked ? 'telegram' : 'email';
  var telegramUsername = document.getElementById('modal-reg-telegram').value.trim();

  if (!first || !last) {
    errEl.textContent = 'Please enter your first and last name.';
    return;
  }
  if (!email || email.indexOf('@') === -1) {
    errEl.textContent = 'Please enter a valid email address.';
    return;
  }
  if (commPrimary === 'telegram' && !telegramUsername) {
    errEl.textContent = 'Please enter your Telegram username.';
    return;
  }

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
  if (btn) {
    btn.disabled = true;
  }

  var fd = new FormData();
  fd.append('action', 'ihq_send_registration_code');
  fd.append('nonce', IHQ_MODAL_REG.nonce);
  fd.append('first_name', first);
  fd.append('last_name', last);
  fd.append('email', email);
  fd.append('platform_handle', ihqModalGetPlatformHandle());
  fd.append('challenge_type', ihqModalGetChallengeType());
  fd.append('comm_primary', commPrimary);
  fd.append('telegram_username', telegramUsername);
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
window.addEventListener('scroll', function() {
  var hero = document.querySelector('.hero');
  var navCta = document.getElementById('navCta');
  if (!hero || !navCta) return;
  if (window.pageYOffset > hero.offsetHeight * 0.7) navCta.classList.add('visible');
  else navCta.classList.remove('visible');
});

function smoothScrollTo(target, duration) {
  var start = window.pageYOffset;
  var distance = target - start;
  var startTime = null;
  document.documentElement.style.scrollBehavior = 'auto';
  function ease(t) { return 1 - Math.pow(1 - t, 3); }
  function step(ts) {
    if (!startTime) startTime = ts;
    var elapsed = ts - startTime;
    var progress = Math.min(elapsed / duration, 1);
    window.scrollTo(0, start + distance * ease(progress));
    if (elapsed < duration) {
      requestAnimationFrame(step);
    } else {
      document.documentElement.style.scrollBehavior = '';
    }
  }
  requestAnimationFrame(step);
}

function tog(id) {
  if (id === 's7') {
    return;
  }
  var sec = document.getElementById(id);
  var isOpen = sec.classList.contains('open');
  var hint = document.getElementById('tapHint');
  if (hint) hint.classList.add('gone');
  var cb = document.getElementById('competeBtn');
  if (cb) { cb.classList.remove('drift-up'); cb.style.opacity = '0'; }

  if (!isOpen) {
    var navH = (document.querySelector('nav') || {offsetHeight: 0}).offsetHeight;
    var top = sec.getBoundingClientRect().top + window.pageYOffset - navH;
    smoothScrollTo(top, 900);
    var body = sec.querySelector('.acc-body');
    if (body) body.style.maxHeight = body.scrollHeight + 'px';
    sec.classList.add('open');
    if (id === 's8') {
      setTimeout(function() {
        var btn = document.getElementById('competeBtn');
        if (btn) { btn.style.opacity = '0'; btn.classList.add('drift-up'); }
      }, 800);
    }
  } else {
    var bodyClose = sec.querySelector('.acc-body');
    if (bodyClose) {
      bodyClose.style.maxHeight = bodyClose.scrollHeight + 'px';
      bodyClose.offsetHeight;
      bodyClose.style.maxHeight = '0';
    }
    sec.classList.remove('open');
  }
}

function openModal(ctaId) {
  src = ctaId || null;
  ihqResetMainConversationModal();
  document.getElementById('mainModal').classList.add('open');
  document.body.style.overflow = 'hidden';
  show('ms1');
}

function openCompetition() {
  src = null;
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
  document.querySelectorAll('.mstep').forEach(function(s) { s.classList.remove('on'); });
  document.getElementById(id).classList.add('on');
  if (id === 'ms-reg') {
    window.setTimeout(function() { ihqModalRenderTurnstileWhenNeeded(); }, 80);
  }
}

function clearFieldErrors() {
  document.querySelectorAll('.field-error').forEach(function(el) {
    el.classList.remove('field-error');
  });
}

function showFieldError(el) {
  if (!el) return;
  el.classList.add('field-error');
  window.setTimeout(function() {
    el.classList.remove('field-error');
  }, 1200);
}

function validateModalStep1Communication() {
  var email = document.getElementById('modal-comm-email');
  var telegram = document.getElementById('modal-comm-telegram');
  if (!email || !telegram || (!email.checked && !telegram.checked)) {
    if (document.getElementById('modal-comm-card-email')) {
      showFieldError(document.getElementById('modal-comm-card-email'));
    }
    if (document.getElementById('modal-comm-card-telegram')) {
      showFieldError(document.getElementById('modal-comm-card-telegram'));
    }
    return false;
  }
  return true;
}

function onModalSubmit() {
  clearFieldErrors();
  if (!validateModalStep1Communication()) {
    var modalEl = document.querySelector('#mainModal .modal');
    if (modalEl) {
      modalEl.scrollTo({ top: 0, behavior: 'smooth' });
    }
    return;
  }
  var telWrap = document.getElementById('modal-reg-telegram-wrap');
  var telInput = document.getElementById('modal-reg-telegram');
  if (document.getElementById('modal-comm-telegram').checked) {
    telWrap.style.display = 'block';
    telInput.required = true;
  } else {
    telWrap.style.display = 'none';
    telInput.required = false;
    telInput.value = '';
  }
  show('ms-reg');
}

function toggleCh(id) {
  var sel = document.getElementById('sel-' + id);
  if (!sel) {
    return;
  }
  var entry = document.getElementById('entry-' + id);
  if (sel.classList.contains('selected')) {
    sel.classList.remove('selected');
    if (entry) {
      entry.style.display = 'none';
    }
  } else {
    sel.classList.add('selected');
    if (entry) {
      entry.style.display = 'block';
    }
  }
}

function chosen() {
  started = true;
  document.querySelectorAll('.cta-block').forEach(function(block) {
    block.innerHTML = '<p class="cta-done">Connected. Details coming shortly.</p>';
  });
  var cfinal = document.getElementById('cfinal');
  if (cfinal) cfinal.innerHTML = '<p class="cta-done">Details coming your way shortly.</p>';
}

function pickComp(id) {
  var el = document.getElementById(id);
  if (!el) return;
  el.classList.toggle('sel');
  if (id === 'cw2' || id === 'cp2') {
    syncS7CompetitionSubmitVisibility();
  }
}

function getS7CompetitionSelectionValue() {
  var parts = [];
  var w = document.getElementById('cw2');
  var c = document.getElementById('cp2');
  if (w && w.classList.contains('sel')) {
    parts.push('world-competition');
  }
  if (c && c.classList.contains('sel')) {
    parts.push('community-competition');
  }
  return parts.join(',');
}

function syncS7SelectionToHiddenFields() {
  var val = getS7CompetitionSelectionValue();
  var h = document.getElementById('ihq-s7-competition-selection');
  var ah = document.getElementById('auth-competition-preferences');
  if (h) h.value = val;
  if (ah) ah.value = val;
}

function syncS7CompetitionSubmitVisibility() {
  var wrap = document.getElementById('s7-competition-submit-wrap');
  var fu = document.getElementById('s7-competition-followup');
  var w = document.getElementById('cw2');
  var c = document.getElementById('cp2');
  if (!wrap) return;
  var any = (w && w.classList.contains('sel')) || (c && c.classList.contains('sel'));
  wrap.style.display = any ? 'block' : 'none';
  if (!any) {
    if (fu) fu.style.display = 'none';
    var h = document.getElementById('ihq-s7-competition-selection');
    var ah = document.getElementById('auth-competition-preferences');
    if (h) h.value = '';
    if (ah) ah.value = '';
  }
}

function submitS7CompetitionChoice(ev) {
  if (ev) ev.preventDefault();
  var val = getS7CompetitionSelectionValue();
  if (!val) return;
  syncS7SelectionToHiddenFields();
  var fu = document.getElementById('s7-competition-followup');
  if (fu) fu.style.display = 'block';
}

document.getElementById('mainModal').addEventListener('click', function(e) {
  if (e.target === this) closeModal();
});

document.addEventListener('DOMContentLoaded', function() {
  var em = document.getElementById('modal-comm-email');
  var tg = document.getElementById('modal-comm-telegram');
  function bindMutual(box, other) {
    if (!box) return;
    box.addEventListener('change', function() {
      if (box.checked && other) other.checked = false;
      if (!tg.checked) {
        var tgl = document.getElementById('modal-comm-telegram-lbl');
        if (tgl) {
          tgl.textContent = IHQ_MODAL_REG.telegramLblDefault;
        }
        var ti = document.getElementById('modal-reg-telegram');
        if (ti) {
          ti.value = '';
        }
        ihqClearTelegramLoginErr();
      }
      syncModalCommCardVisual();
    });
  }
  bindMutual(em, tg);
  bindMutual(tg, em);
  syncModalCommCardVisual();
  if (tg) {
    tg.addEventListener('change', function() {
      if (tg.checked && IHQ_MODAL_REG.telegramClientId) {
        ihqTryTelegramAccountLink();
      }
    });
  }

  syncS7CompetitionSubmitVisibility();
  var s7SubmitBtn = document.getElementById('s7-competition-submit');
  if (s7SubmitBtn) {
    s7SubmitBtn.addEventListener('click', submitS7CompetitionChoice);
  }
  var s7RegLink = document.getElementById('s7-open-registration-modal');
  if (s7RegLink) {
    s7RegLink.addEventListener('click', function(e) {
      e.preventDefault();
      syncS7SelectionToHiddenFields();
      openAuthModal('register');
    });
  }
});

// Drift-in animation for final band button on scroll
var observer = new IntersectionObserver(function(entries) {
  entries.forEach(function(entry) {
    if (entry.isIntersecting) {
      entry.target.classList.add('visible');
      observer.unobserve(entry.target);
    }
  });
}, { threshold: 0.3 });
var finalBtn = document.getElementById('finalBtn');
if (finalBtn) observer.observe(finalBtn);

// -----------------------------------------------------------------------
// Auth Modal (Login / Register)
// -----------------------------------------------------------------------
function openAuthModal(tab) {
  var modal = document.getElementById('authModal');
  if (!modal) return;
  modal.classList.add('open');
  document.body.style.overflow = 'hidden';
  if (tab) switchAuthTab(tab);
}

function closeAuthModal() {
  var modal = document.getElementById('authModal');
  if (modal) modal.classList.remove('open');
  document.body.style.overflow = '';
}

document.addEventListener('DOMContentLoaded', function() {
  var authModal = document.getElementById('authModal');
  if (authModal) {
    authModal.addEventListener('click', function(e) { if (e.target === this) closeAuthModal(); });
  }

  // Auto-open if welcome redirect
  var urlParams = new URLSearchParams(window.location.search);
  if (urlParams.get('welcome') === 'true') {
    openAuthModal('login');
  }

  // Comm method toggles for register form (challenge section only — genius section stays once shown)

  var langBtn = document.getElementById('headerLangBtn');
  var langDropdown = document.getElementById('headerLangDropdown');
  if (langBtn && langDropdown) {
    langBtn.addEventListener('click', function (e) {
      e.stopPropagation();
      var open = langDropdown.classList.toggle('open');
      langBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
    document.addEventListener('click', function () {
      langDropdown.classList.remove('open');
      langBtn.setAttribute('aria-expanded', 'false');
    });
    langDropdown.querySelectorAll('.header-lang-option[data-lang]').forEach(function (opt) {
      opt.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        var code = opt.getAttribute('data-lang');
        if (!code || !/^[a-z]{2}$/i.test(code)) {
          return;
        }
        document.documentElement.setAttribute('lang', code.toLowerCase());
        langDropdown.classList.remove('open');
        langBtn.setAttribute('aria-expanded', 'false');
      });
    });
  }
});

function switchAuthTab(tab) {
  var loginPane = document.getElementById('auth-pane-login');
  var registerPane = document.getElementById('auth-pane-register');
  var loginTab = document.getElementById('auth-tab-login');
  var registerTab = document.getElementById('auth-tab-register');
  if (!loginPane) return;
  if (tab === 'login') {
    loginPane.classList.add('active');
    registerPane.classList.remove('active');
    loginTab.classList.add('active');
    registerTab.classList.remove('active');
    ihqAuthLoginResetPanels();
  } else {
    loginPane.classList.remove('active');
    registerPane.classList.add('active');
    loginTab.classList.remove('active');
    registerTab.classList.add('active');
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

// AJAX Register (legacy multi-step form — email link verification, no password)
function handleAuthRegister(e) {
  e.preventDefault();

  var email = document.getElementById('auth-reg-email') ? document.getElementById('auth-reg-email').value : '';
  var firstName = document.getElementById('auth-reg-first') ? document.getElementById('auth-reg-first').value : '';
  var lastName = document.getElementById('auth-reg-last') ? document.getElementById('auth-reg-last').value : '';
  var platformHandle = document.getElementById('auth-reg-handle') ? document.getElementById('auth-reg-handle').value : '';
  var challengeType = document.querySelector('input[name="auth_challenge_type"]:checked');
  var errBox = document.getElementById('auth-reg-err');
  var btn = document.getElementById('auth-reg-btn');

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
  fd.append('nonce', '<?php echo wp_create_nonce("verification_email_nonce"); ?>');

  fetch('<?php echo esc_js(admin_url("admin-ajax.php")); ?>', { method: 'POST', body: fd })
    .then(function(r) { return r.json(); })
    .then(function(data) {
      if (data.success) {
        var modal = document.getElementById('authModal');
        var inner = modal.querySelector('.auth-modal');
        inner.innerHTML = '<button class="auth-modal-x" onclick="closeAuthModal()">✕</button>'
          + '<div style="text-align:center;padding:32px 0">'
          + '<div style="font-size:2.5rem;color:var(--gl);margin-bottom:18px">◈</div>'
          + '<h3 style="font-family:\'Cinzel\',serif;font-size:1.8rem;color:var(--white);margin-bottom:14px">Thank You!</h3>'
          + '<p style="font-family:\'Be Vietnam Pro\',sans-serif;font-size:1rem;color:var(--warm);line-height:2">Thank you for starting the conversation!<br>Please check your email to verify your address.</p>'
          + '</div>';
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
</script>

<script>
(function () {
    document.addEventListener('DOMContentLoaded', function () {
        var btn             = document.querySelector('.concierge-title');
        var activeSession    = null;
        var originalText     = 'Talk Now - Executive Concierge';
        var DEFAULT_CONVAI_PAGE_LANGUAGE_CODE = 'en';

        /** Primary ISO 639-1 tag from `<html lang>` — passed as ConvAI overrides per ElevenLabs language docs */
        function pagePrimaryLangForElevenLabs() {
            var raw = document.documentElement ? document.documentElement.getAttribute('lang') : '';
            if (typeof raw !== 'string') {
                raw = '';
            }
            raw = raw.trim();
            if (!raw) {
                return DEFAULT_CONVAI_PAGE_LANGUAGE_CODE;
            }
            var primary = raw.split(/[-_\s]/)[0].toLowerCase();
            if (!/^[a-z]{2,10}$/.test(primary)) {
                return DEFAULT_CONVAI_PAGE_LANGUAGE_CODE;
            }
            return primary;
        }

        if (!btn) return;

        btn.addEventListener('click', function (e) {
            e.preventDefault();
            if (activeSession) {
                activeSession.endSession();
                return;
            }
            btn.style.pointerEvents = 'none';
            btn.textContent = 'Connecting…';

            fetch(ihqElevenLabs.ajax_url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=ihq_elevenlabs_signed_url&nonce=' + encodeURIComponent(ihqElevenLabs.nonce) + '&agent_id=' + encodeURIComponent(ihqElevenLabs.agent_id_portal_home_claude),
            })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.success && data.data && data.data.signed_url) {
                    var elevenLabsLanguageOverride = pagePrimaryLangForElevenLabs();
                    console.log('[ElevenLabs] overrides.agent.language:', elevenLabsLanguageOverride);
                    ElevenLabsClient.Conversation.startSession({
                        signedUrl: data.data.signed_url,
                        overrides: {
                            agent: {
                                language: elevenLabsLanguageOverride,
                            },
                        },
                        onConnect: function () {
                            btn.textContent = 'End Talk';
                            btn.style.pointerEvents = '';
                        },
                        onDisconnect: function () {
                            activeSession = null;
                            btn.style.pointerEvents = '';
                            btn.textContent = originalText;
                        },
                        onError: function () {
                            activeSession = null;
                            btn.style.pointerEvents = '';
                            btn.textContent = originalText;
                        },
                        onMessage: function () {},
                    }).then(function (session) {
                        activeSession = session;
                    }).catch(function () {
                        btn.style.pointerEvents = '';
                        btn.textContent = originalText;
                    });
                } else {
                    btn.style.pointerEvents = '';
                    btn.textContent = originalText;
                }
            })
            .catch(function () {
                btn.style.pointerEvents = '';
                btn.textContent = originalText;
            });
        });
    });
})();
</script>

<div style="display:none">
<?php get_footer(); ?>
</div>

