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
?>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700;900&family=Be+Vietnam+Pro:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400&display=swap" rel="stylesheet">

<style>
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
</style>

<main id="primary" class="site-main">

<nav>
  <a class="logo" href="#"><span style="font-family:'Be Vietnam Pro',sans-serif;font-weight:700;color:var(--gl)">influencer</span><span style="font-family:'Be Vietnam Pro',sans-serif;font-weight:700;color:var(--gl)">HQ</span></a>
  <div style="display:flex;align-items:center;gap:10px">
    <?php if (is_user_logged_in()): ?>
      <a style="display:none;" href="<?php echo esc_url(home_url('/portal/portal-home/')); ?>" class="nav-auth-btn portal">Go to Portal</a>
    <?php else: ?>
      <button style="display:none;" class="nav-auth-btn login" id="navLoginBtn" onclick="openAuthModal('login')">Login / Register</button>
    <?php endif; ?>
    <button class="nav-cta" id="navCta" onclick="openModal()">Yes — Let's Talk</button>
  </div>
</nav>

<section class="hero">
  <div class="hero-lines"></div>
  <div class="hero-glow"></div>
  <span class="hero-eye">Influencer Headquarters</span>
  <h1>Influence was never meant to be rented.</h1>
  <p class="hero-sub">We believe those who drive the energy deserve to share in what they help build.</p>
  <div class="hero-btns">
    <button class="btn-gold" onclick="openModal()">Yes — Let's Start the Conversation</button>
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

  <div class="acc-sec" id="s7">
    <div class="acc-hd" onclick="tog('s7')">
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
    <button class="btn-gold drift-on-scroll" id="finalBtn" onclick="openModal('cfinal')" style="margin:0 auto;display:block">Yes — Let's Start the Conversation</button>
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
      <p class="m-sub">Select your favorite communication methods. Choose as many as you like.</p>
      <p class="m-benefit">We believe that communication is the foundation of all business relationships and all success.</p>
      <div class="ch-list">
        <div class="ch-sel" id="sel-email" onclick="toggleCh('email')">
          <div class="ch-chk" id="chk-email"></div>
          <div class="ch-icon" style="background:rgba(240,201,58,.15)">✉</div>
          <div class="ch-info"><div class="ch-name">Email</div><span class="ch-lbl">We'll reach you by email</span></div>
        </div>
        <div class="ch-entry" id="entry-email" style="display:none"><input class="ch-input" type="email" placeholder="Your email address"></div>

        <div class="ch-sel" id="sel-wa" onclick="toggleCh('wa')">
          <div class="ch-chk" id="chk-wa"></div>
          <div class="ch-icon" style="background:rgba(37,211,102,.15);color:#25D366">💬</div>
          <div class="ch-info"><div class="ch-name">WhatsApp</div><span class="ch-lbl">Message us on WhatsApp</span></div>
        </div>
        <div class="ch-entry" id="entry-wa" style="display:none"><input class="ch-input" type="tel" placeholder="Your WhatsApp number"></div>

        <div class="ch-sel" id="sel-kakao" onclick="toggleCh('kakao')">
          <div class="ch-chk" id="chk-kakao"></div>
          <div class="ch-icon" style="background:rgba(255,232,18,.15);color:#FEE418">💛</div>
          <div class="ch-info"><div class="ch-name">KakaoTalk</div><span class="ch-lbl">Your KakaoTalk ID</span></div>
        </div>
        <div class="ch-entry" id="entry-kakao" style="display:none"><input class="ch-input" type="text" placeholder="KakaoTalk ID"></div>

        <div class="ch-sel" id="sel-line" onclick="toggleCh('line')">
          <div class="ch-chk" id="chk-line"></div>
          <div class="ch-icon" style="background:rgba(0,195,0,.15);color:#00C300">✔</div>
          <div class="ch-info"><div class="ch-name">LINE</div><span class="ch-lbl">Your LINE ID</span></div>
        </div>
        <div class="ch-entry" id="entry-line" style="display:none"><input class="ch-input" type="text" placeholder="LINE ID"></div>

        <div class="ch-sel" id="sel-wechat" onclick="toggleCh('wechat')">
          <div class="ch-chk" id="chk-wechat"></div>
          <div class="ch-icon" style="background:rgba(9,187,7,.15);color:#09BB07">💬</div>
          <div class="ch-info"><div class="ch-name">WeChat</div><span class="ch-lbl">Your WeChat ID</span></div>
        </div>
        <div class="ch-entry" id="entry-wechat" style="display:none"><input class="ch-input" type="text" placeholder="WeChat ID"></div>

        <div class="ch-sel" id="sel-telegram" onclick="toggleCh('telegram')">
          <div class="ch-chk" id="chk-telegram"></div>
          <div class="ch-icon" style="background:rgba(0,136,204,.15);color:#0088CC">✈</div>
          <div class="ch-info"><div class="ch-name">Telegram</div><span class="ch-lbl">Your Telegram username</span></div>
        </div>
        <div class="ch-entry" id="entry-telegram" style="display:none"><input class="ch-input" type="text" placeholder="@username"></div>
      </div>

      <div class="m-belief-block">
        <h4>How We Will Promote You</h4>
        <p>You built something real. Your audience follows you because of you — your voice, your eye, your way of seeing the world.</p>
      </div>

      <div class="m-belief-block">
        <h4>We Believe In</h4>
        <ul>
          <li>Creating true partnerships</li>
          <li>Standing alongside you</li>
          <li>Building alongside you</li>
          <li>Growing alongside you</li>
        </ul>
      </div>

      <div class="m-belief-block">
        <h4>We Also Believe</h4>
        <p>It's our responsibility to promote your reach, your growth, and your visibility.</p>
      </div>

      <div class="m-belief-block">
        <h4>What We Do</h4>
        <ul>
          <li>Put platform marketing dollars behind your visibility across the channels you already use.</li>
          <li>Run paid campaigns featuring your content on Instagram, TikTok, YouTube, X, and regional platforms through formal creator partnership tools.</li>
          <li>Drive new followers to your handles through targeted follow campaigns and lookalike audiences seeded from your existing fans.</li>
        </ul>
      </div>

      <div class="m-belief-block">
        <h4>What We Report Back To You</h4>
        <ul>
          <li>New followers gained, by platform, every month</li>
          <li>Views and engagement delivered to your content</li>
          <li>The full picture of growth measured, transparent, and yours</li>
        </ul>
      </div>

      <div class="m-belief-block">
        <h4>What Stays Yours</h4>
        <ul>
          <li>Every follower we help you gain stays your follower, on your platform, forever</li>
          <li>Your content remains yours</li>
          <li>Your audience remains yours</li>
          <li>The relationship with your fans is yours alone — we build the bridge, you keep what's built</li>
        </ul>
      </div>

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

      <button onclick="onContinue()" style="margin-top:16px;width:100%;padding:20px;background:var(--gl);border:none;font-family:'Be Vietnam Pro',sans-serif;font-size:.75rem;font-weight:700;letter-spacing:.3em;text-transform:uppercase;color:#000;cursor:pointer;transition:opacity .3s" id="ch-continue">Continue →</button>

      <p class="m-note">All conversations are private and confidential.<br>We operate across time zones. Expect a reply within minutes.</p>
    </div>
    <div class="mstep" id="ms2">
      <span class="m-eye">One More Thing</span>
      <h3 class="m-title">Glory is earned one weekend at a time.</h3>
      <p class="m-ctx">Yours starts now. Choose your competition and we'll send you and your followers everything you need to get started.</p>
      <div class="ms2-quote">When your followers watch you play, cheer, and celebrate, they become part of a shared experience that unites people the same way great sports, music, and global competitions always have.</div>
      <div class="comp-list">
        <div class="comp-card" id="cw" onclick="pickComp('cw')"><div class="comp-chk"><span class="chk-in">✓</span></div><div class="comp-body"><span class="comp-tag">Global Stage</span><div class="comp-title">Influencer World Competition</div><div class="comp-desc">Join thousands of Influencers and their followers. Compete Thursday night through Sunday night.</div></div></div>
        <div class="comp-card" id="cp" onclick="pickComp('cp')"><div class="comp-chk"><span class="chk-in">✓</span></div><div class="comp-body"><span class="comp-tag">Community Competition</span><div class="comp-title">Community Competition</div><div class="comp-desc">A one week competition between your followers who choose to participate with you at the helm cheering them all on to victory.</div></div></div>
      </div>
      <button class="send-btn" id="sendbtn" onclick="redirectLogin()">Continue</button>
      <div class="dealer-row" style="margin-top:24px;">
        <div class="dealer-image-container">
          <p class="concierge-text-above" style="font-size:20px;text-align:center;margin:0 0 12px;">We believe conversations should be easy.</p>
          <div class="dealer-image-wrap">
            <div class="dealer-gradient-overlay" style="position:absolute;inset:0;width: 100px;pointer-events:none;z-index:2;"></div>
            <img style="width: 100%;" src="<?php echo get_template_directory_uri(); ?>/images/concierge.png" alt="Casino Dealer" class="dealer-image" style="position:relative;z-index:1;">
          </div>
          <a href="#" class="concierge-title" style="color:white;font-size:20px;display:block;text-align:center;margin-top:12px;">Talk Now - Executive Concierge</a>
        </div>
      </div>
      <button class="back-btn" onclick="goBack()">← Back</button>
    </div>
    <div class="mstep" id="ms3">
      <div class="confirm-wrap">
        <div class="confirm-icon">◈</div>
        <h3 class="confirm-title">You're on your way.</h3>
        <p class="confirm-sub">Details coming shortly. You and your followers are on the road to glory.</p>
        <button class="btn-gold" onclick="closeModal()" style="margin:0 auto;display:block">Close</button>
      </div>
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

      <!-- LOGIN PANE -->
      <div class="auth-pane active" id="auth-pane-login">
        <form id="auth-login-form" onsubmit="handleAuthLogin(event)" style="max-width:480px;margin:0 auto">
          <div class="auth-field">
            <label for="auth-login-email">Email</label>
            <input type="email" id="auth-login-email" name="email" required placeholder="your@email.com">
          </div>
          <div class="auth-field">
            <label for="auth-login-password">Password</label>
            <input type="password" id="auth-login-password" name="password" required placeholder="Your password">
          </div>
          <div class="auth-err" id="auth-login-err"></div>
          <button type="submit" class="auth-submit-btn" id="auth-login-btn">Login</button>
        </form>
      </div>

      <!-- REGISTER PANE -->
      <div class="auth-pane" id="auth-pane-register">
        <form id="auth-register-form" onsubmit="handleAuthRegister(event)">

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
              <label for="auth-reg-password">Create Password</label>
              <input type="password" id="auth-reg-password" name="password" required placeholder="Minimum 6 characters">
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
var started = false;
var src = null;

window.addEventListener('scroll', function() {
  var hero = document.querySelector('.hero');
  var navCta = document.getElementById('navCta');
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
    var body = sec.querySelector('.acc-body');
    if (body) body.style.maxHeight = '0';
    sec.classList.remove('open');
  }
}

function openModal(ctaId) {
  src = ctaId || null;
  document.getElementById('mainModal').classList.add('open');
  document.body.style.overflow = 'hidden';
  show('ms1');
}

function openCompetition() {
  src = null;
  document.getElementById('mainModal').classList.add('open');
  document.body.style.overflow = 'hidden';
  show('ms2');
}

function closeModal() {
  document.getElementById('mainModal').classList.remove('open');
  document.body.style.overflow = '';
}

function show(id) {
  document.querySelectorAll('.mstep').forEach(function(s) { s.classList.remove('on'); });
  document.getElementById(id).classList.add('on');
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

function onContinue() {
  clearFieldErrors();
  var valid = true;
  document.querySelectorAll('.ch-sel.selected').forEach(function(sel) {
    var fieldId = sel.id.replace('sel-', '');
    var input = sel.querySelector('input') || document.querySelector('#entry-' + fieldId + ' input');
    if (input && !input.value.trim()) {
      showFieldError(sel);
      showFieldError(input);
      valid = false;
    }
  });
  if (!valid) return;
  chosen();
}

function toggleCh(id) {
  var sel = document.getElementById('sel-' + id);
  var entry = document.getElementById('entry-' + id);
  if (sel.classList.contains('selected')) {
    sel.classList.remove('selected');
    entry.style.display = 'none';
  } else {
    sel.classList.add('selected');
    entry.style.display = 'block';
  }
}

function chosen() {
  started = true;
  document.querySelectorAll('.cta-block').forEach(function(block) {
    block.innerHTML = '<p class="cta-done">Connected. Details coming shortly.</p>';
  });
  var cfinal = document.getElementById('cfinal');
  if (cfinal) cfinal.innerHTML = '<p class="cta-done">Details coming your way shortly.</p>';
  show('ms2');
}

function pickComp(id) {
  document.getElementById(id).classList.toggle('sel');
}

function redirectLogin() {
  show('ms3');
  setTimeout(function() {
    window.location.href = '/influencer-login/';
  }, 3000);
}
function goBack() { show('ms1'); }

document.getElementById('mainModal').addEventListener('click', function(e) {
  if (e.target === this) closeModal();
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

// AJAX Login
function handleAuthLogin(e) {
  e.preventDefault();
  var errBox = document.getElementById('auth-login-err');
  var btn = document.getElementById('auth-login-btn');
  errBox.style.display = 'none';
  btn.disabled = true;
  btn.textContent = 'Logging in…';

  var fd = new FormData();
  fd.append('action', 'influencer_login_ajax');
  fd.append('nonce', '<?php echo wp_create_nonce("influencer_login_ajax"); ?>');
  fd.append('email', document.getElementById('auth-login-email').value);
  fd.append('password', document.getElementById('auth-login-password').value);
  fd.append('redirect_url', '<?php echo esc_js(home_url("/portal/portal-home/")); ?>');

  fetch('<?php echo esc_js(admin_url("admin-ajax.php")); ?>', { method: 'POST', body: fd })
    .then(function(r) { return r.json(); })
    .then(function(data) {
      if (data.success) {
        window.location.href = data.data.redirect;
      } else {
        errBox.textContent = data.data || 'Login failed. Please try again.';
        errBox.style.display = 'block';
        btn.disabled = false;
        btn.textContent = 'Login';
      }
    })
    .catch(function() {
      errBox.textContent = 'Network error. Please try again.';
      errBox.style.display = 'block';
      btn.disabled = false;
      btn.textContent = 'Login';
    });
}

// AJAX Register
function handleAuthRegister(e) {
  e.preventDefault();

  var email = document.getElementById('auth-reg-email') ? document.getElementById('auth-reg-email').value : '';
  var password = document.getElementById('auth-reg-password') ? document.getElementById('auth-reg-password').value : '';
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
  if (!password || password.length < 6) { errBox.textContent = 'Password must be at least 6 characters.'; errBox.style.display = 'block'; return; }
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
  fd.append('password', password);
  fd.append('first_name', firstName);
  fd.append('last_name', lastName);
  fd.append('platform_handle', platformHandle);
  fd.append('comm_methods', JSON.stringify(methodsData));
  fd.append('challenge_type', challengeType.value);
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
        var btn          = document.querySelector('.concierge-title');
        var activeSession = null;
        var originalText  = "Talk Now - Executive Concierge";
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
                body: 'action=ihq_elevenlabs_signed_url&nonce=' + encodeURIComponent(ihqElevenLabs.nonce),
            })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.success && data.data && data.data.signed_url) {
                    ElevenLabsClient.Conversation.startSession({
                        signedUrl: data.data.signed_url,
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
}());
</script>

<div style="display:none">
<?php get_footer(); ?>
</div>

