<?php
/**
 * Template Name: Home Page 2 (Elite HQ Edition)
 * Description: A cinematic, high-end landing page featuring a sticky brand logo, layered atmospheric depth, and full mobile optimization.
 *
 * @package Avantage_Baccarat
 */

/* ---------------------------------------------------------
   CORE PHP LOGIC - PRESERVED
--------------------------------------------------------- */

add_filter('auth_cookie_expiration', function($expiration, $user_id, $remember) {
    return $remember ? 30 * DAY_IN_SECONDS : 2 * DAY_IN_SECONDS;
}, 10, 3);

$errors = new WP_Error();
$success_message = '';

if (isset($_POST['action']) && $_POST['action'] === 'influencer_login') {
    if (!isset($_POST['login_nonce']) || !wp_verify_nonce($_POST['login_nonce'], 'influencer_login')) {
        $errors->add('nonce_error', 'Security verification failed.');
    } else {
        $email = sanitize_email($_POST['login_email_existing']);
        $password = $_POST['login_password'];
        $remember = isset($_POST['remember_me']) && $_POST['remember_me'] === 'on';
        
        if (empty($email) || !is_email($email)) {
            $errors->add('email', 'Valid email address is required');
        }
        if (empty($password)) {
            $errors->add('password', 'Password is required');
        }
        
        if (!$errors->has_errors()) {
            $user = get_user_by('email', $email);
            if (!$user) {
                $errors->add('email_not_found', 'No account found.');
            } else {
                $creds = array('user_login' => $user->user_login, 'user_password' => $password, 'remember' => $remember);
                $user = wp_signon($creds, false);
                if (is_wp_error($user)) {
                    $errors->add('incorrect_password', 'Incorrect password');
                } else {
                    wp_redirect(home_url('/hq'));
                    exit;
                }
            }
        }
    }
}

get_header();
?>

<style>
    /* --- The Elite Color System --- */
    :root {
        --bg-deep: #050505;        /* Absolute Black */
        --bg-surface: #0f0f0f;    /* Premium Charcoal */
        --bg-alt: #141414;        /* Lighter Surface for Contrast */
        --red-primary: #e61e2a;
        --red-glow: rgba(230, 30, 42, 0.5);
        --red-gradient: linear-gradient(135deg, #e61e2a 0%, #8b0000 100%);
        --gold-accent: #d4af37;
        --gold-glow: rgba(212, 175, 55, 0.4);
        --gold-gradient: linear-gradient(135deg, #d4af37 0%, #f9e29c 50%, #b8860b 100%);
        --text-white: #ffffff;
        --text-body: #e0e0e0;     /* High-visibility light gray */
        --text-muted: #a0a0a0;
        --border-color: rgba(255, 255, 255, 0.08);
        --container-width: 1280px;
        --transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1);
    }

    body { 
        background-color: var(--bg-deep); 
        color: var(--text-white); 
        font-family: 'Inter', -apple-system, sans-serif; 
        margin: 0; 
        overflow-x: hidden;
        -webkit-font-smoothing: antialiased;
        line-height: 1.8;
    }
    
    .section-wrap { padding: 140px 0; position: relative; overflow: hidden; }
    .container { max-width: var(--container-width); margin: 0 auto; padding: 0 40px; }
    
    /* --- Navigation Header --- */
    .site-nav {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        z-index: 1000;
        padding: 20px 0;
        background: transparent;
        transition: var(--transition);
        border-bottom: 1px solid transparent;
    }
    .site-nav.scrolled {
        background: rgba(5, 5, 5, 0.9);
        backdrop-filter: blur(15px);
        padding: 12px 0;
        border-bottom: 1px solid var(--border-color);
    }
    .nav-container {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .brand-logo img {
        height: 60px;
        width: auto;
        display: block;
        transition: var(--transition);
    }
    .site-nav.scrolled .brand-logo img {
        height: 50px;
    }

    /* --- Typography Patterns --- */
    h1, h2, h3, h4 { text-transform: uppercase; font-weight: 900; letter-spacing: -0.02em; margin: 0; line-height: 1.1; }
    .h-xl { font-size: clamp(3.2rem, 8vw, 6.5rem); }
    .h-lg { font-size: clamp(2.4rem, 6vw, 4.2rem); margin-bottom: 1.5rem; }
    .h-md { font-size: 1.8rem; margin-bottom: 1.2rem; }
    .gradient-text-red { background: var(--red-gradient); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
    .gold-accent { color: var(--gold-accent); }
    
    .section-indicator {
        width: 80px;
        height: 3px;
        background: var(--gold-gradient);
        margin-bottom: 2.5rem;
        display: block;
        box-shadow: 0 0 15px var(--gold-glow);
    }

    /* --- Animations --- */
    @keyframes pulse-aura {
        0% { transform: scale(1); opacity: 0.3; }
        50% { transform: scale(1.1); opacity: 0.6; }
        100% { transform: scale(1); opacity: 0.3; }
    }

    @keyframes scan-border {
        0% { left: -100%; }
        100% { left: 100%; }
    }

    @keyframes shimmer-flash {
        0% { filter: brightness(1); }
        50% { filter: brightness(1.5); text-shadow: 0 0 20px var(--red-glow); }
        100% { filter: brightness(1); }
    }

    .reveal { opacity: 0; transform: translateY(50px); transition: var(--transition); }
    .reveal.active { opacity: 1; transform: translateY(0); }

    /* --- Components --- */
    .btn {
        display: inline-flex;
        align-items: center;
        padding: 20px 48px;
        border-radius: 4px; 
        font-weight: 800;
        text-transform: uppercase;
        font-size: 0.95rem;
        letter-spacing: 2px;
        cursor: pointer;
        transition: var(--transition);
        border: none;
        text-decoration: none;
    }
    .btn-red { background: var(--red-gradient); color: #fff; box-shadow: 0 10px 20px rgba(230, 30, 42, 0.2); }
    .btn-red:hover { transform: translateY(-5px); box-shadow: 0 15px 40px var(--red-glow); }
    
    .btn-gold-outline { background: transparent; border: 1px solid var(--gold-accent); color: var(--gold-accent); }
    .btn-gold-outline:hover { background: var(--gold-accent); color: #000; box-shadow: 0 0 20px var(--gold-glow); }

    /* --- Image Glow Effects (New Component) --- */
    .image-glow-container {
        position: relative;
        z-index: 1;
        border-radius: 4px;
        padding: 10px; /* Space for glow bleeding */
    }

    .image-glow-container::before {
        content: '';
        position: absolute;
        top: 50%; left: 50%;
        transform: translate(-50%, -50%);
        width: 105%; height: 105%;
        filter: blur(40px);
        opacity: 0.4;
        z-index: -1;
        transition: var(--transition);
    }

    /* Glow Variants */
    .glow-red::before { background: radial-gradient(circle, var(--red-glow) 10%, transparent 70%); }
    .glow-gold::before { background: radial-gradient(circle, var(--gold-glow) 10%, transparent 70%); }

    .image-glow-container img {
        position: relative;
        z-index: 2;
        border-radius: 4px;
        border: 1px solid rgba(255, 255, 255, 0.1);
        box-shadow: 0 20px 50px rgba(0,0,0,0.5);
        transition: var(--transition);
        display: block;
    }

    /* Hover Animation for Images */
    .image-glow-container:hover::before { opacity: 0.7; filter: blur(50px); }
    .image-glow-container:hover img { border-color: var(--gold-accent); transform: scale(1.02); }


    /* --- Sections Visual Logic --- */

    /* Hero: Parallax Image Background */
    .hero-section { 
        height: 100vh; 
        display: flex; 
        align-items: center; 
        position: relative;
        background-image: linear-gradient(90deg, rgba(5,5,5,1) 0%, rgba(5,5,5,0.85) 45%, rgba(5,5,5,0.4) 100%), 
                          url('https://images.freeimages.com/images/large-previews/3df/poker-1306151.jpg?fmt=webp&h=1200');
        background-size: cover;
        background-position: center;
        background-attachment: fixed;
    }
    .hero-grid { display: grid; grid-template-columns: 1.2fr 1fr; width: 100%; z-index: 2; }

    /* DYNAMIC MANIFESTO SECTION */
    .section-manifesto { 
        background: #000; 
        position: relative; 
        padding: 160px 0;
    }
    
    .manifesto-aura {
        position: absolute;
        width: 600px;
        height: 600px;
        background: radial-gradient(circle, var(--red-glow) 0%, transparent 70%);
        top: 20%;
        left: -200px;
        z-index: 1;
        animation: pulse-aura 8s infinite ease-in-out;
        pointer-events: none;
    }

    .manifesto-aura-gold {
        position: absolute;
        width: 500px;
        height: 500px;
        background: radial-gradient(circle, var(--gold-glow) 0%, transparent 70%);
        bottom: 10%;
        right: -150px;
        z-index: 1;
        animation: pulse-aura 10s infinite ease-in-out reverse;
        pointer-events: none;
    }

    .manifesto-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 30px;
        z-index: 2;
        position: relative;
    }

    .manifesto-card {
        background: rgba(15, 15, 15, 0.6);
        backdrop-filter: blur(20px);
        border: 1px solid var(--border-color);
        padding: 60px 40px;
        position: relative;
        overflow: hidden;
        transition: var(--transition);
        display: flex;
        flex-direction: column;
        justify-content: center;
    }

    .manifesto-card:hover {
        border-color: var(--red-primary);
        transform: scale(1.02);
        box-shadow: 0 30px 60px rgba(0,0,0,0.8);
    }

    .manifesto-card::after {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 2px;
        background: linear-gradient(90deg, transparent, var(--red-primary), transparent);
        animation: scan-border 3s infinite linear;
    }

    .manifesto-card h3 {
        font-size: 1.8rem;
        margin-bottom: 20px;
        color: #fff;
        line-height: 1.2;
    }

    .manifesto-card .flash-text {
        font-weight: 800;
        color: var(--red-primary);
        animation: shimmer-flash 4s infinite;
    }

    /* Marketplace */
    .section-marketplace { background: var(--bg-surface); }
    .section-marketplace::after {
        content: ''; position: absolute; top: 0; right: 0; width: 40%; height: 60%;
        background: radial-gradient(circle at top right, rgba(230, 30, 42, 0.08), transparent 70%);
        pointer-events: none;
    }

    /* History */
    .section-history { background: var(--bg-deep); }
    .history-card { 
        background: #000; 
        border: 1px solid var(--border-color); 
        padding: 60px; 
        border-radius: 4px;
        position: relative;
        box-shadow: 0 40px 80px rgba(0,0,0,0.5);
    }

    /* Competition */
    .section-competition { background: var(--bg-alt); }
    .feature-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 30px; margin-top: 60px; }
    .feature-card {
        padding: 50px 40px;
        background: rgba(255, 255, 255, 0.02);
        border: 1px solid var(--border-color);
        backdrop-filter: blur(10px);
        transition: var(--transition);
    }
    .feature-card:hover { 
        border-color: var(--gold-accent); 
        transform: translateY(-10px); 
    }
    .icon-box {
        width: 64px; height: 64px; background: var(--red-gradient); 
        display: flex; align-items: center; justify-content: center; margin-bottom: 30px;
    }

    /* Series */
    .section-series {
        background: radial-gradient(circle at center, #1a1a1a 0%, #050505 100%);
        text-align: center;
    }

    /* Layout Patterns */
    .split-content { display: grid; grid-template-columns: 1fr 1fr; gap: 80px; align-items: center; }
    .split-reverse { direction: rtl; }
    .split-reverse > * { direction: ltr; }

    .footer-brand { 
        font-size: 15vw; font-weight: 900; text-align: center; line-height: 0.8;
        background: linear-gradient(180deg, rgba(230,30,42,0.1) 0%, rgba(212,175,55,0.05) 100%);
        -webkit-background-clip: text; -webkit-text-fill-color: transparent;
        margin-top: 50px; user-select: none; padding-bottom: 50px;
    }

    /* --- Mobile Optimization --- */
    @media (max-width: 992px) {
        .nav-container { justify-content: center; }
        .hero-section { 
            background-attachment: scroll; 
            background-image: linear-gradient(180deg, rgba(5,5,5,0.9) 0%, rgba(5,5,5,0.95) 100%), 
                              url('https://images.freeimages.com/images/large-previews/3df/poker-1306151.jpg?fmt=webp&h=1200');
        }
        .hero-grid, .split-content, .feature-grid, .manifesto-grid { 
            grid-template-columns: 1fr !important; 
            text-align: center; 
            justify-items: center;
        }
        .hero-grid { padding-top: 80px; }
        .hero-grid p { margin-left: auto; margin-right: auto; }
        .manifesto-card { padding: 40px 20px; width: 100%; box-sizing: border-box; }
        .section-indicator { margin-left: auto; margin-right: auto; }
        .icon-box { margin-left: auto; margin-right: auto; }
        .btn { width: 100%; justify-content: center; }
        .brand-logo img { height: 50px; }
        .image-glow-container { padding: 0; /* Remove padding on mobile for tighter fit */ }
        .image-glow-container img { width: 100%; }
    }
</style>

<main id="primary" class="site-main">

    <nav class="site-nav" id="mainNav">
        <div class="container nav-container">
            <a href="<?php echo esc_url(home_url()); ?>" class="brand-logo">
                <img src="https://influencerhq.co/wp-content/themes/avantage-baccarat/images/logo-hq.png" alt="Influencer HQ Logo">
            </a>
        </div>
    </nav>

    <section class="hero-section">
        <div class="container hero-grid">
            <div class="reveal">
                <span style="color: var(--gold-accent); letter-spacing: 5px; font-weight: 700; display: block; margin-bottom: 25px;">
                    — OWN THE FUTURE —
                </span>
                <h1 class="h-xl mb-4">
                    INFLUENCE COMPETE<br>
                    <span class="gradient-text-red">OWN THE FUTURE</span>
                </h1>
                <p style="font-size: 1.4rem; color: #fff; margin-bottom: 50px; max-width: 580px; font-weight: 500; opacity: 0.95;">
                    People don't buy what you do. They buy what you <strong>believe</strong>.
                </p>
                <div style="display: flex; gap: 20px; flex-wrap: wrap; justify-content: inherit;">
                    <a href="#login-anchor" class="btn btn-red">Join Headquarters</a>
                    <a href="#manifesto" class="btn btn-gold-outline">Our Philosophy</a>
                </div>
            </div>
            <div></div>
        </div>
    </section>

    <section id="manifesto" class="section-wrap section-manifesto">
        <div class="manifesto-aura"></div>
        <div class="manifesto-aura-gold"></div>
        
        <div class="container">
            <div class="reveal" style="text-align: center; margin-bottom: 80px;">
                <span class="section-indicator"></span>
                <h2 class="h-lg">THESE ARE OUR <span class="gradient-text-red">CORE CONVICTIONS</span></h2>
                <p style="color: var(--text-muted); font-size: 1.2rem; max-width: 700px; margin: 0 auto;">
                    We believe influencers should be partners, not temporary vendors.
                </p>
            </div>

            <div class="manifesto-grid">
                <div class="manifesto-card reveal">
                    <h3 class="flash-text">01. INFLUENCE</h3>
                    <p style="color: var(--text-white); font-weight: 700; font-size: 1.3rem;">Influence deserves more than <span style="color: var(--gold-accent);">short-term payouts.</span></p>
                </div>

                <div class="manifesto-card reveal" style="transition-delay: 0.2s;">
                    <h3 class="flash-text">02. VALUE</h3>
                    <p style="color: var(--text-white); font-weight: 700; font-size: 1.3rem;">Your voice should create <span style="color: var(--gold-accent);">permanent, long-term</span> value.</p>
                </div>

                <div class="manifesto-card reveal" style="transition-delay: 0.4s;">
                    <h3 class="flash-text">03. ENERGY</h3>
                    <p style="color: var(--text-white); font-weight: 700; font-size: 1.3rem;">Those who drive the energy deserve to <span style="color: var(--gold-accent);">share in the build.</span></p>
                </div>
            </div>

            <div class="reveal" style="margin-top: 80px; text-align: center;">
                <p style="color: var(--text-body); font-size: 1.3rem; max-width: 800px; margin: 0 auto;">
                    Influencer Headquarters connects creators with global platforms that prioritize <span class="gold-accent">long-term contribution</span> over one-time campaigns.
                </p>
            </div>
        </div>
    </section>

    <section class="section-wrap section-marketplace">
        <div class="container split-content">
            <div class="reveal">
                <span class="section-indicator"></span>
                <h2 class="h-lg mb-6">THE FUTURE OF THE<br><span class="gradient-text-red">INFLUENCER MARKETPLACE</span></h2>
                <p style="color: var(--text-body); margin-bottom: 35px; font-size: 1.2rem;">
                    Brands everywhere rely on influencers to shape culture and build communities. But traditional partnerships often end sooner than the value you provide.
                </p>
                <div style="padding: 40px; background: #000; border-left: 4px solid var(--gold-accent); box-shadow: 20px 20px 60px rgba(0,0,0,0.3); text-align: center;">
                    <p style="color: #fff; font-weight: 800; margin: 0; font-size: 1.4rem; line-height: 1.4;">
                        "When your influence grows a platform, you should share in the long-term value created by that growth."
                    </p>
                </div>
            </div>
            <div class="reveal">
                <div class="image-glow-container glow-red">
                    <img src="https://images.pexels.com/photos/2823921/pexels-photo-2823921.jpeg?_gl=1*1ci8huh*_ga*NDkzNjYzMTg4LjE3NjQ3NTUzNjY.*_ga_8JE65Q40S6*czE3NjcwNzcyMzMkbzkkZzEkdDE3NjcwNzczNDUkajkkbDAkaDA." alt="Value Growth" style="width: 100%; border-radius: 2px;">
                </div>
            </div>
        </div>
    </section>

    <section id="history" class="section-wrap section-history">
        <div class="container">
            <div class="reveal" style="text-align: center; margin-bottom: 80px;">
                <span class="section-indicator"></span>
                <h2 class="h-lg">A LESSON FROM <span class="gold-accent">HISTORY</span></h2>
            </div>
            <div class="history-card reveal">
                <span style="color: var(--gold-accent); letter-spacing: 2px; font-size: 0.9rem; font-weight: 800; display: block; margin-bottom: 20px;">1979: THE $5 BILLION DECISION</span>
                <p style="font-size: 1.3rem; color: var(--text-body); line-height: 1.8; margin-bottom: 30px; text-align: center;">
                    In 1979, Nike offered Magic Johnson 1 cent for every shoe sold, plus stock valued at 11 cents. <strong>He took the guaranteed $100,000 cash instead.</strong>
                </p>
                <p style="font-size: 2rem; color: #fff; font-weight: 900; line-height: 1.2; margin: 40px 0; border-top: 1px solid rgba(255,255,255,0.1); border-bottom: 1px solid rgba(255,255,255,0.1); padding: 40px 0; text-align: center;">
                    THAT STOCK WOULD BE WORTH <span class="gradient-text-red">OVER $5 BILLION</span> TODAY.
                </p>
                <p style="font-size: 1.25rem; color: var(--text-muted); text-align: center;">
                    TikTok star Alix Earle chose ownership with <strong>Poppi</strong> over cash. When they were acquired for over a billion dollars, her choice defined the new creator economy.
                </p>
            </div>
        </div>
    </section>

    <section class="section-wrap section-competition">
        <div class="container">
            <div style="text-align: center; margin-bottom: 80px;">
                <span class="section-indicator"></span>
                <h2 class="h-lg">COMPETITION: THE <span class="gradient-text-red">OLDEST LANGUAGE</span></h2>
                <p style="color: var(--text-body); font-size: 1.25rem; max-width: 850px; margin: 0 auto;">
                    Competition is universal, emotional, and human. From ancient Greece to Rome, it has united humanity through courage and spectacle.
                </p>
            </div>
            <div class="feature-grid">
                <div class="feature-card reveal">
                    <div class="icon-box">🏛️</div>
                    <h3 class="h-md">Ancient Pride</h3>
                    <p style="color: var(--text-body);">Origins in the Olympic Games of Greece and the precision and strategy found in Asian legendary traditions.</p>
                </div>
                <div class="feature-card reveal" style="border-top: 3px solid var(--red-primary);">
                    <div class="icon-box">🎮</div>
                    <h3 class="h-md">Modern Arenas</h3>
                    <p style="color: var(--text-body);">Fuelling global sports, music battles, esports, and creator challenges. Moments we share together.</p>
                </div>
                <div class="feature-card reveal">
                    <div class="icon-box">👑</div>
                    <h3 class="h-md">New Leaders</h3>
                    <p style="color: var(--text-body);">Throughout history, competition has always needed leaders. Today, those leaders are influencers.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="section-wrap" style="background: var(--bg-deep);">
        <div class="container split-content split-reverse">
            <div class="reveal">
                <span class="section-indicator"></span>
                <h2 class="h-lg mb-6">ELEGANCE. PRESTIGE.<br><span class="gold-accent">GLOBAL LEGACY.</span></h2>
                <ul style="list-style: none; padding: 0; color: #fff; font-weight: 700; font-size: 1.15rem; text-align: inherit;">
                    <li style="margin-bottom: 20px;">● Elegance that never goes out of style</li>
                    <li style="margin-bottom: 20px;">● Prestige that spans centuries</li>
                    <li style="margin-bottom: 20px;">● Intensity and emotion in every moment</li>
                </ul>
                <p style="color: var(--gold-accent); font-weight: 900; font-size: 1.4rem; margin-top: 40px; letter-spacing: 1px;">
                    YOUR INFLUENCE DESERVES A STAGE WORTHY OF THIS LEGACY.
                </p>
            </div>
            <div class="reveal">
                <div class="image-glow-container glow-gold">
                    <img src="https://images.pexels.com/photos/6664191/pexels-photo-6664191.jpeg?_gl=1*1qx66sd*_ga*NDkzNjYzMTg4LjE3NjQ3NTUzNjY.*_ga_8JE65Q40S6*czE3NjcwNzcyMzMkbzkkZzEkdDE3NjcwNzc0NTIkajEzJGwwJGgw" alt="Legacy Stage" style="width: 100%; border-radius: 2px;">
                </div>
            </div>
        </div>
    </section>

    <section class="section-wrap section-series">
        <div class="container">
            <div class="reveal" style="max-width: 950px; margin: 0 auto;">
                <span style="color: var(--red-primary); letter-spacing: 6px; font-weight: 900; display: block; margin-bottom: 30px;">LIVE FROM THE GLOBAL STAGE</span>
                <h2 class="h-lg mb-8">INTERNATIONAL <span class="gradient-text-red">COMPETITION SERIES</span></h2>
                <p style="font-size: 1.35rem; color: var(--text-body); margin-bottom: 50px; line-height: 1.8;">
                    Top influencers are invited to a 24-hour global stage. Share your commentary, reactions, and energy in real time, uniting followers in a shared experience.
                </p>
                <div style="background: rgba(255,255,255,0.02); padding: 60px; border: 1px solid var(--border-color);">
                    <h3 class="gradient-text-red" style="font-size: 2.2rem; line-height: 1.1;">THIS IS WHAT INFLUENCE LOOKS LIKE WHEN IT COMES ALIVE.</h3>
                </div>
            </div>
        </div>
    </section>

    <section id="login-anchor" class="section-wrap" style="background: var(--bg-deep); border-top: 1px solid var(--border-color);">
        <div class="container" style="max-width: 600px;">
            <div class="reveal" style="text-align: center; margin-bottom: 50px;">
                <h2 class="h-lg">READY TO <span class="gold-accent">LEAD?</span></h2>
                <p style="color: var(--text-body); font-size: 1.2rem;">Access your dashboard at Influencer Headquarters.</p>
            </div>
            
            <div class="reveal" style="background: var(--bg-surface); padding: 50px; border-radius: 4px; border: 1px solid var(--gold-accent); box-shadow: 0 50px 100px rgba(0,0,0,0.8);">
                <form method="post">
                    <?php wp_nonce_field('influencer_login', 'login_nonce'); ?>
                    <input type="hidden" name="action" value="influencer_login">
                    
                    <div style="margin-bottom: 25px;">
                        <label style="display: block; margin-bottom: 10px; font-size: 0.85rem; color: var(--gold-accent); font-weight: 800; letter-spacing: 2px;">SECURE IDENTIFICATION</label>
                        <input type="email" name="login_email_existing" required style="width: 100%; background: #000; border: 1px solid #333; color: #fff; padding: 18px; border-radius: 2px; font-size: 1rem;">
                    </div>
                    
                    <div style="margin-bottom: 25px;">
                        <label style="display: block; margin-bottom: 10px; font-size: 0.85rem; color: var(--gold-accent); font-weight: 800; letter-spacing: 2px;">ENCRYPTED ACCESS</label>
                        <input type="password" name="login_password" required style="width: 100%; background: #000; border: 1px solid #333; color: #fff; padding: 18px; border-radius: 2px; font-size: 1rem;">
                    </div>

                    <button type="submit" class="btn btn-red" style="width: 100%; justify-content: center;">Enter Headquarters</button>

                    <?php if ($errors->has_errors()): ?>
                        <div style="margin-top: 25px; padding: 15px; background: rgba(230,30,42,0.1); border: 1px solid var(--red-primary); color: var(--red-primary); font-weight: 800; text-align: center;">
                            <?php echo $errors->get_error_message(); ?>
                        </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </section>

<!--     <div class="footer-brand">OWN THE FUTURE</div> -->

</main>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        // Sticky Nav Logic
        const nav = document.getElementById('mainNav');
        window.addEventListener('scroll', () => {
            if (window.scrollY > 100) {
                nav.classList.add('scrolled');
            } else {
                nav.classList.remove('scrolled');
            }
        });

        // Intersection Observer for Animations
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('active');
                }
            });
        }, { threshold: 0.1 });

        document.querySelectorAll('.reveal').forEach(el => observer.observe(el));
    });
</script>

<?php get_footer(); ?>