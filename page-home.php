<?php
/**
 * Template Name: new influencer home page
 * Description: A custom template for displaying the home page of the WordPress site.
 * This template is used to render the homepage content and layout.
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package Avantage_Baccarat
 */

get_header();
?>
<?php wp_enqueue_style('homepage-style', get_template_directory_uri() . '/css/homepage-style.css'); ?>
    <main id="primary" class="site-main">
        
        <section class="hero-section">
            <div class="container">
                <div class="row">
                    <div class="vh-60 col-12 fade-in text-center align-middle hero-content">
                        <img class="hero-image" src="<?php echo get_template_directory_uri(); ?>/images/logo-hq.png" alt="Hero Logo" class="img-fluid">
                        <p class="hero-subtitle">— OWN THE FUTURE —</p>
                        <h1 class="text-center hero-title">Influence… Compete… and Own the Future</h1>
                        
                        <div class="hero-beliefs-container">
                            <p class="hero-intro">People don't buy what you do.</p>
                            <p class="hero-intro">They buy what you believe.</p>
                            
                            <div class="beliefs-list">
                                <p class="belief-item">We believe influence deserves more than short-term payouts.</p>
                                <p class="belief-item">We believe your voice should create long-term value.</p>
                                <p class="belief-item">We believe those who drive the energy deserve to share in what they help build.</p>
                            </div>
                            
                            <p class="hero-closing">These beliefs guide every opportunity represented through Influencer Headquarters, where influencers partner with global platforms that value long-term contribution — not one-time campaigns.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        
        <!-- Competition section-->
        <section id="competition" class="py-5">
            <div class="container">
                <h2 class="section-heading mb-5">The Future of the Influencer Marketplace</h2>
                <div class="row justify-content-center">
                    <div class="col-lg-10">
                        <p class="lead mb-4">Brands everywhere rely on influencers to shape culture, spark engagement, and build communities.</p>
                        <p class="lead mb-4">But traditional partnerships often end sooner than the value you provide.</p>
                        <p class="lead mb-4" style="font-weight: 600;">We believe in a different path:</p>
                        <p class="lead mb-4">When your influence grows a platform, you should share in the long-term value created by that growth.</p>
                        <p class="lead mb-4">Our partners' platforms are designed to reward sustained contribution, giving influencers a meaningful stake in the success they help build.</p>
                        <p class="lead mb-5">As these ecosystems expand across new markets and categories, the value of your influence grows with them.</p>
                    </div>
                </div>
            </div>
        </section>        
                <section id="how-you-earn" class="py-5">
            <div class="container">
                 <h2 class="section-heading mb-5">A Lesson from History</h2>
                
                <div class="row justify-content-center align-items-center">
                    <div class="col-md-5 col-lg-4 mb-4 mb-md-0 text-center">
                        <img src="<?php echo get_template_directory_uri(); ?>/images/magic-story.jpg" alt="Magic Johnson" class="img-fluid" style="max-width: 100%; height: auto; border-radius: 10px;">
                    </div>
                    <div class="col-md-7 col-lg-6">
                        <p class="lead mb-4">In 1979, Converse offered college basketball star Magic Johnson what seemed like a fortune: $100,000 — guaranteed cash.</p>
                        <p class="lead mb-4">Nike, a newcomer on the scene, offered something different: 1 cent for every shoe sold, plus stock then valued at just 11 cents per share.</p>
                        <p class="lead mb-4">Magic took the cash.</p>
                        <p class="lead mb-4">That stock offer — the one he passed — would be worth more than $5 billion today.</p>
                        <p class="lead mb-4">We believe influencers should have the opportunity to share in the ownership moment Magic missed.</p>
                        <p class="lead mb-4">TikTok star Alix Earle chose an ownership-based partnership with Poppi instead of a traditional cash sponsorship.</p>
                        <p class="lead mb-4">When Poppi was soon acquired by PepsiCo for over a billion dollars, her choice became a defining moment in the creator economy.</p>
                        <p class="lead mb-0">Ownership matters — and influence deserves the chance to share in it.</p>
                    </div>
                </div>
            </div>
        </section>
        
        <!-- Competition History section-->
        <section id="game-of-kings" class="py-5">
            <div class="container">
                <h2 class="section-heading mb-5">Competition: The Oldest Language in the World</h2>
                <div class="row justify-content-center">
                    <div class="col-lg-10">
                        <p class="lead mb-4">From the earliest days of civilization, competition has united people across cultures.</p>
                        <p class="lead mb-4">It began in ancient Greece, where the first Olympic Games ignited national pride.</p>
                        <p class="lead mb-4">It filled the arenas of Rome, where crowds gathered for courage, skill, and spectacle.</p>
                        <p class="lead mb-4">It lives in Asia's legendary traditions — games of mastery, precision, rhythm, and strategy celebrated for centuries.</p>
                        <p class="lead mb-4">Today, competition fuels:</p>
                        
                        <div class="row g-3 mb-4 justify-content-center">
                            <div class="col-lg-4 col-md-6 col-12">
                                <div class="competition-item">
                                    <div class="competition-text">Global sports</div>
                                </div>
                            </div>
                            <div class="col-lg-4 col-md-6 col-12">
                                <div class="competition-item">
                                    <div class="competition-text">Music battles</div>
                                </div>
                            </div>
                            <div class="col-lg-4 col-md-6 col-12">
                                <div class="competition-item">
                                    <div class="competition-text">Esports arenas</div>
                                </div>
                            </div>
                            <div class="col-lg-4 col-md-6 col-12">
                                <div class="competition-item">
                                    <div class="competition-text">Creator challenges</div>
                                </div>
                            </div>
                            <div class="col-lg-4 col-md-6 col-12">
                                <div class="competition-item">
                                    <div class="competition-text">Digital tournaments</div>
                                </div>
                            </div>
                            <div class="col-lg-4 col-md-6 col-12">
                                <div class="competition-item">
                                    <div class="competition-text">Moments we share together</div>
                                </div>
                            </div>
                        </div>
                        
                        <p class="lead mb-4">Competition is universal.</p>
                        <p class="lead mb-4">Competition is emotional.</p>
                        <p class="lead mb-4">Competition is human.</p>
                        <p class="lead mb-4">Throughout history, competition has always needed leaders — voices who elevate every moment, ignite anticipation, and unite communities.</p>
                        <p class="lead mb-5">Today, those leaders are influencers.</p>
                    </div>
                </div>
            </div>
        </section>
        
        <!-- Elegance and Prestige section-->
        <section id="how-you-earn-section" class="py-5">
            <div class="container">
                <h2 class="section-heading mb-5">Elegance. Prestige. Global Legacy.</h2>
                <div class="row justify-content-center">
                    <div class="col-lg-10">
                        <p class="lead mb-4">The stages influencers step onto today carry the same timeless elements found in the world's greatest competitions:</p>
                        <ul class="lead mb-4" style="list-style: none; padding-left: 0;">
                            <li class="mb-3">Elegance that never goes out of style</li>
                            <li class="mb-3">Prestige that spans centuries</li>
                            <li class="mb-3">A global following that crosses borders and cultures</li>
                            <li class="mb-3">Intensity and emotion in every moment</li>
                        </ul>
                        <p class="lead mb-5">Influence deserves a stage worthy of that legacy — and now, it has one.</p>
                    </div>
                </div>
            </div>
        </section>
        
        <!-- Global Exposure section-->
        <section id="global-exposure" class="py-5">
            <div class="container">
                <h2 class="section-heading mb-5">International Competition Series</h2>
                
                <div class="row justify-content-center">
                    <div class="col-lg-10">
                        <p class="lead mb-4">Influencers are automatically invited to appear on our partners' International Competition Series — a 24-hour global competition stage.</p>
                        <p class="lead mb-4">Each broadcast showcases the world's most popular game and offers the opportunity to make worldwide live appearances with other creators. Head to Head Influencer Competition features two Influencers playing together from their own locations, sharing their reactions, commentary, and competitive energy in real time.</p>
                        <p class="lead mb-4">Currently, the platform supports English, Mandarin, Cantonese, Korean, Japanese, Thai and Vietnamese.</p>
                        <p class="lead mb-4">When your followers watch you play, cheer, and celebrate, they become part of a shared experience that unites people the same way great sports, music, and global competitions always have.</p>
                        <p class="lead mb-5" style="font-weight: 600;">This is what influence looks like when it comes alive.</p>
                    </div>
                </div>
            </div>
        </section>
        

        <!-- Form section -->
        <section id="contact-form" class="py-5">
            <div class="container">
                <h2 class="section-heading mb-5">Start the Conversation</h2>
                
                <div class="row justify-content-center">
                    <div class="col-lg-8">
                        
                        <?php
                        // Check if this is a welcome redirect after account creation
                        $welcome = isset($_GET['welcome']) && $_GET['welcome'] === 'true';
                        
                        if ($welcome && is_user_logged_in()) {
                            // Show welcome message for newly created account
                            $current_user = wp_get_current_user();
                            ?>
                            <div class="simple-form text-center">
                                <h3 style="color: #ffd700; font-size: 2rem; margin-bottom: 20px;">Welcome to Influencer HQ!</h3>
                                <p style="color: #f0f0f0; font-size: 1.3rem; line-height: 1.6;">
                                    Your account has been successfully created!<br>
                                    <strong><?php echo esc_html($current_user->user_email); ?></strong>
                                </p>
                                <p style="color: #ffd700; font-size: 1.1rem; margin-top: 20px;">
                                    You're now ready to start your journey with us.
                                </p>
                            </div>
                            <?php
                        } elseif (!is_user_logged_in()) {
                        ?>
                            <!-- Auth tabs -->
                            <div class="auth-tabs mb-4" style="display:flex;gap:0;border-bottom:2px solid rgba(255,255,255,0.15);">
                                <button id="tab-login" onclick="switchTab('login')" type="button"
                                    style="flex:1;padding:12px 0;background:transparent;border:none;color:#ffd700;font-size:1.1rem;font-weight:700;cursor:pointer;border-bottom:3px solid #ffd700;margin-bottom:-2px;">
                                    Login
                                </button>
                                <button id="tab-register" onclick="switchTab('register')" type="button"
                                    style="flex:1;padding:12px 0;background:transparent;border:none;color:#888;font-size:1.1rem;font-weight:600;cursor:pointer;border-bottom:3px solid transparent;margin-bottom:-2px;">
                                    Register
                                </button>
                            </div>

                            <!-- Login form -->
                            <div id="pane-login">
                                <form id="login-form" class="simple-form" style="max-width:480px;margin:0 auto;" onsubmit="handleLogin(event)">
                                    <div class="mb-4">
                                        <label for="login-email" class="form-label">Email:</label>
                                        <input type="email" class="form-control" id="login-email" name="email" required placeholder="your@email.com">
                                    </div>
                                    <div class="mb-4">
                                        <label for="login-password" class="form-label">Password:</label>
                                        <input type="password" class="form-control" id="login-password" name="password" required placeholder="Your password">
                                    </div>
                                    <div id="login-error" style="color:#ff4d4d;margin-bottom:1rem;display:none;"></div>
                                    <div class="text-center">
                                        <button type="submit" class="btn btn-primary btn-lg px-5" id="login-btn">Login</button>
                                    </div>
                                </form>
                            </div>

                            <!-- Register form -->
                            <div id="pane-register" style="display:none;">
                            <form id="avantage-form" class="simple-form">
                                        
                                        <div class="mb-4">
                                            <p class="lead mb-4">If you believe you may want to participate, choose the method(s) you'd like us to use to communicate with you:</p>
                                        </div>
                                        
                                        <div class="form-check mb-4">
                                            <input class="form-check-input" type="checkbox" name="comm_methods[]" value="line" id="line">
                                            <label class="form-check-label" for="line">LINE</label>
                                        </div>
                                        
                                        <div class="form-check mb-4">
                                            <input class="form-check-input" type="checkbox" name="comm_methods[]" value="telegram" id="telegram">
                                            <label class="form-check-label" for="telegram">Telegram</label>
                                        </div>
                                        
                                        <div class="form-check mb-4">
                                            <input class="form-check-input" type="checkbox" name="comm_methods[]" value="whatsapp" id="whatsapp">
                                            <label class="form-check-label" for="whatsapp">WhatsApp</label>
                                        </div>
                                        
                                        <div class="form-check mb-4">
                                            <input class="form-check-input" type="checkbox" name="comm_methods[]" value="wechat" id="wechat">
                                            <label class="form-check-label" for="wechat">WeChat</label>
                                        </div>
                                        
                                        <!-- Dynamic Communication Method Input Fields -->
                                        <div id="comm-inputs-container" class="mb-4"></div>
                                        
                                        <!-- Challenge Section (hidden until comm method selected) -->
                                        <div id="challenge-section" style="display: none;">
                                            <hr style="border-color: rgba(255, 255, 255, 0.15); margin: 3rem 0;">
                                            
                                            <!-- Your Path to Lead Global Competition -->
                                            <div class="mb-4">
                                                <h3 class="section-subheading mb-4">Choose Your Path to Lead Global Competition</h3>
                                                <p class="lead mb-4">After your email is verified, you'll enter HQ², your private influencer portal.</p>
                                                <p class="lead mb-4">From there, you have two powerful ways to lead your community:</p>
                                            </div>
                                            
                                            <!-- Challenge Selection Boxes -->
                                            <div class="row mb-3">
                                                <div class="col-md-6 mb-3">
                                                    <input type="radio" name="challenge_type" value="weekend_world" id="weekend_world" class="challenge-radio" style="display: none;">
                                                    <label for="weekend_world" class="challenge-card-select">
                                                        <div class="challenge-card-inner">
                                                            <h3 class="mb-3 section-subheading">Weekend World Challenge</h3>
                                                            <p class="mb-0">A global challenge open to all influencers and their followers — a massive international competition.</p>
                                                        </div>
                                                    </label>
                                                </div>
                                                
                                                <div class="col-md-6 mb-3">
                                                    <input type="radio" name="challenge_type" value="community_challenge" id="community_challenge" class="challenge-radio" style="display: none;">
                                                    <label for="community_challenge" class="challenge-card-select">
                                                        <div class="challenge-card-inner">
                                                            <h3 class="mb-3 section-subheading">Community<br>Challenge</h3>
                                                            <p class="mb-0">A challenge created by you, for your community, on your schedule, with an option to limit participation to your own followers — and stream the action live.</p>
                                                        </div>
                                                    </label>
                                                </div>
                                            </div>
                                            
                                            <div class="row mb-4">
                                                <div class="col-12">
                                                    <input type="radio" name="challenge_type" value="maybe_later" id="maybe_later" class="challenge-radio" style="display: none;">
                                                    <label for="maybe_later" class="challenge-card-select challenge-card-small">
                                                        <div class="challenge-card-inner">
                                                            <h3 class="mb-0 section-subheading" style="font-size: 1.5rem;">Thanks, maybe later</h3>
                                                        </div>
                                                    </label>
                                                </div>
                                            </div>
                                            
                                            <p class="lead mb-4" style="font-weight: 600;">Whether you lead global audiences or your own community, this is your arena.</p>
                                        </div>
                                        
                                        <!-- Genius Referrals Section (hidden initially) -->
                                        <div id="genius-section" style="display: none;">
                                            <hr style="border-color: rgba(255, 255, 255, 0.15); margin: 3rem 0;">
                                            
                                            <div class="mb-4 text-center">
                                                <img src="<?php echo get_template_directory_uri(); ?>/images/genius-logo.png" alt="Genius Referrals Logo" class="img-fluid mb-4" style="max-width: 300px;">
                                            </div>
                                            
                                            <div class="mb-4">
                                                <h3 class="section-subheading mb-4">Meet Genius — Your Partner in Protecting Your Earned Equity Rewards</h3>
                                                <p class="lead mb-4">The private system that protects your influence and ensures you receive recognition for every follower and every community you inspire.</p>
                                                <p class="lead mb-4">Genius automatically manages lifetime Influencer HQ equity in your expanding network that will grow throughout the years.</p>
                                                <ul class="lead mb-4" style="color: #b6b6b6;">
                                                    <li class="mb-2">1.5% of all play of Your Direct Followers (Level 1)</li>
                                                    <li class="mb-2">1% of the play of L2 followers and .5% of Level 3 followers</li>
                                                    <li class="mb-2">1% Bonus equity on the play of (1) anyone playing along with your Live Show Appearances on the International broadcasts as well as (2) on those playing along with your Live Streaming your play on Kick Network.</li>
                                                </ul>
                                                <p class="lead mb-4">To do this safely, Genius needs to verify your identity.</p>
                                            </div>
                                            
                                            <div class="mb-4">
                                                <h3 class="section-subheading mb-4">Why Email Verification Matters</h3>
                                                <ul class="lead mb-4" style="color: #b6b6b6;">
                                                    <li class="mb-2">Protects your Rewards</li>
                                                    <li class="mb-2">Activates Genius Tracking</li>
                                                    <li class="mb-2">Confirms your Leadership identity</li>
                                                    <li class="mb-2">Prevents impersonation</li>
                                                    <li class="mb-2">Unlocks your private influencer portal</li>
                                                </ul>
                                            </div>
                                            
                                            <p class="lead mb-4 text-center" style="font-weight: 600;">After your email is verified, you will receive the key to enter your private influencer portal.</p>
                                            
                                            <div class="row mb-4">
                                                <div class="col-md-6 mb-3">
                                                    <label for="email" class="form-label">Email:</label>
                                                    <input type="email" class="form-control" id="email" name="email" required placeholder="your@email.com">
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <label for="password" class="form-label">Create Password:</label>
                                                    <input type="password" class="form-control" id="password" name="password" required placeholder="Enter your password (min 6 characters)">
                                                </div>
                                            </div>
                                            
                                            
                                            <div class="row mb-4">
                                                <div class="col-md-6 mb-3">
                                                    <label for="first_name" class="form-label">First Name:</label>
                                                    <input type="text" class="form-control" id="first_name" name="first_name" required placeholder="First Name">
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <label for="last_name" class="form-label">Last Name:</label>
                                                    <input type="text" class="form-control" id="last_name" name="last_name" required placeholder="Last Name">
                                                </div>
                                            </div>
                                            
                                            <div class="mb-4">
                                                <label for="platform_handle" class="form-label">Favorite Platform Handle:</label>
                                                <input type="text" class="form-control" id="platform_handle" name="platform_handle" placeholder="@yourhandle">
                                            </div>
                                            
                                            <small class="form-text text-muted d-block mb-4 text-center">Please check your email to verify and activate Genius.</small>
                                            
                                            <div class="form-check mb-4">
                                                <input class="form-check-input" type="checkbox" id="prefer_facial" name="prefer_facial">
                                                <label class="form-check-label" for="prefer_facial">Prefer Facial Recognition?</label>
                                            </div>
                                            
                                            <div id="facial-recognition-options" style="display: none;" class="mb-4">
                                                <p class="lead mb-3">Sign in with:</p>
                                                <div class="row g-3">
                                                    <div class="col-md-4 col-6">
                                                        <button type="button" class="btn btn-outline-light w-100">Face ID</button>
                                                    </div>
                                                    <div class="col-md-4 col-6">
                                                        <button type="button" class="btn btn-outline-light w-100">WeChat Face</button>
                                                    </div>
                                                    <div class="col-md-4 col-6">
                                                        <button type="button" class="btn btn-outline-light w-100">Alipay Face</button>
                                                    </div>
                                                    <div class="col-md-4 col-6">
                                                        <button type="button" class="btn btn-outline-light w-100">LINE Face</button>
                                                    </div>
                                                    <div class="col-md-4 col-6">
                                                        <button type="button" class="btn btn-outline-light w-100">KakaoTalk</button>
                                                    </div>
                                                    <div class="col-md-4 col-6">
                                                        <button type="button" class="btn btn-outline-light w-100">Biometric ID</button>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="text-center">
                                                <button type="submit" class="btn btn-primary btn-lg px-5">Send Verification Email</button>
                                            </div>
                                        </div>
                                        
                                    </form>
                            </div><!-- /#pane-register -->
                        <?php } else { ?>
                            <!-- User is logged in -->
                            <div class="simple-form text-center">
                                <?php $current_user = wp_get_current_user(); ?>
                                <h3 style="color:#ffd700;font-size:2rem;margin-bottom:20px;">Welcome Back!</h3>
                                <p style="color:#f0f0f0;font-size:1.3rem;line-height:1.6;">
                                    Logged in as: <strong><?php echo esc_html($current_user->user_email); ?></strong>
                                </p>
                                <div class="mt-4">
                                    <a href="<?php echo esc_url(home_url('/portal/portal-home/')); ?>" class="btn btn-primary btn-lg px-5">Go to Portal</a>
                                    <a href="<?php echo wp_logout_url(get_permalink()); ?>" class="btn btn-outline-light btn-lg px-4 ms-2">Logout</a>
                                </div>
                            </div>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </section>
        
    </main><!-- #main -->
    
<script>
// ---------------------------------------------------------------------------
// Tab switching
// ---------------------------------------------------------------------------
function switchTab(tab) {
    const loginPane    = document.getElementById('pane-login');
    const registerPane = document.getElementById('pane-register');
    const loginTab     = document.getElementById('tab-login');
    const registerTab  = document.getElementById('tab-register');
    if (!loginPane) return;

    if (tab === 'login') {
        loginPane.style.display    = 'block';
        registerPane.style.display = 'none';
        loginTab.style.color       = '#ffd700';
        loginTab.style.borderBottomColor = '#ffd700';
        registerTab.style.color   = '#888';
        registerTab.style.borderBottomColor = 'transparent';
    } else {
        loginPane.style.display    = 'none';
        registerPane.style.display = 'block';
        loginTab.style.color       = '#888';
        loginTab.style.borderBottomColor = 'transparent';
        registerTab.style.color   = '#ffd700';
        registerTab.style.borderBottomColor = '#ffd700';
    }
}

// ---------------------------------------------------------------------------
// AJAX Login
// ---------------------------------------------------------------------------
function handleLogin(e) {
    e.preventDefault();
    const errBox = document.getElementById('login-error');
    const btn    = document.getElementById('login-btn');
    errBox.style.display = 'none';
    btn.disabled = true;
    btn.textContent = 'Logging in…';

    const fd = new FormData();
    fd.append('action',       'influencer_login_ajax');
    fd.append('nonce',        '<?php echo wp_create_nonce("influencer_login_ajax"); ?>');
    fd.append('email',        document.getElementById('login-email').value);
    fd.append('password',     document.getElementById('login-password').value);
    fd.append('redirect_url', '<?php echo esc_js(home_url("/portal/portal-home/")); ?>');

    fetch('<?php echo admin_url("admin-ajax.php"); ?>', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                window.location.href = data.data.redirect;
            } else {
                errBox.textContent      = data.data || 'Login failed. Please try again.';
                errBox.style.display    = 'block';
                btn.disabled            = false;
                btn.textContent         = 'Login';
            }
        })
        .catch(() => {
            errBox.textContent   = 'Network error. Please try again.';
            errBox.style.display = 'block';
            btn.disabled         = false;
            btn.textContent      = 'Login';
        });
}

document.addEventListener("DOMContentLoaded", function() {
    function revealOnScroll() {
        var elements = document.querySelectorAll('.fade-in-on-scroll');
        var windowHeight = window.innerHeight || document.documentElement.clientHeight;
        elements.forEach(function(el) {
            var rect = el.getBoundingClientRect();
            if (rect.top < windowHeight - 100) {
                el.classList.add('visible');
            }
        });
    }

    window.addEventListener('scroll', revealOnScroll);
    revealOnScroll();
    
    // Check if this is a welcome redirect and scroll to form
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('welcome') === 'true') {
        setTimeout(function() {
            document.getElementById('contact-form').scrollIntoView({ behavior: 'smooth', block: 'center' });
        }, 500);
    }
    
    // Communication method selection handler (multiple checkboxes)
    const commMethodCheckboxes = document.querySelectorAll('input[name="comm_methods[]"]');
    const commInputsContainer = document.getElementById('comm-inputs-container');
    const challengeSection = document.getElementById('challenge-section');
    const geniusSection = document.getElementById('genius-section');
    
    const inputFieldsData = {};
    
    // Challenge selection handler - show Genius section when any box is clicked
    const challengeRadios = document.querySelectorAll('input[name="challenge_type"]');
    challengeRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            if (geniusSection) {
                geniusSection.style.display = 'block';
            }
        });
    });
    
    // Facial recognition toggle handler
    const facialCheckbox = document.getElementById('prefer_facial');
    const facialOptions = document.getElementById('facial-recognition-options');
    if (facialCheckbox && facialOptions) {
        facialCheckbox.addEventListener('change', function() {
            if (this.checked) {
                facialOptions.style.display = 'block';
            } else {
                facialOptions.style.display = 'none';
            }
        });
    }
    
    if (commMethodCheckboxes.length > 0) {
        commMethodCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                updateCommInputs();
                // Show challenge section when any comm method is checked
                const anyChecked = Array.from(commMethodCheckboxes).some(cb => cb.checked);
                if (anyChecked && challengeSection) {
                    challengeSection.style.display = 'block';
                } else if (!anyChecked && challengeSection) {
                    challengeSection.style.display = 'none';
                }
            });
        });
    }
    
    function updateCommInputs() {
        commInputsContainer.innerHTML = '';
        
        commMethodCheckboxes.forEach(checkbox => {
            if (checkbox.checked) {
                const method = checkbox.value;
                const labels = {
                    'line': 'Enter your LINE ID:',
                    'telegram': 'Enter your Telegram handle:',
                    'whatsapp': 'Enter your WhatsApp phone number:',
                    'wechat': 'Enter your WeChat ID:'
                };
                const placeholders = {
                    'line': 'YourLINEID',
                    'telegram': '@yourusername',
                    'whatsapp': '+1234567890',
                    'wechat': 'YourWeChatID'
                };
                
                const inputDiv = document.createElement('div');
                inputDiv.className = 'mb-4';
                inputDiv.innerHTML = `
                    <label for="comm_${method}" class="form-label">${labels[method]}</label>
                    <input type="text" class="form-control comm-input" id="comm_${method}" name="comm_${method}" placeholder="${placeholders[method]}" data-method="${method}">
                    ${method === 'wechat' ? '<small class="form-text text-muted">WeChat requires you to message us first. After entering your ID, please add our official account using the QR code we provide and send us a quick "Hi" to activate communications. We cannot contact you first on WeChat.</small>' : ''}
                `;
                commInputsContainer.appendChild(inputDiv);
                
                // Restore previous value if exists
                if (inputFieldsData[method]) {
                    document.getElementById(`comm_${method}`).value = inputFieldsData[method];
                }
                
                // Add event listener to track input value
                document.getElementById(`comm_${method}`).addEventListener('input', function() {
                    inputFieldsData[method] = this.value;
                });
            }
        });
    }
    
    // Original form submission handler for email verification
    const avantageForm = document.getElementById('avantage-form');
    if (avantageForm) {
        avantageForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            const firstName = document.getElementById('first_name').value;
            const lastName = document.getElementById('last_name').value;
            const platformHandle = document.getElementById('platform_handle').value;
            const commMethods = Array.from(commMethodCheckboxes).filter(cb => cb.checked);
            const challengeType = document.querySelector('input[name="challenge_type"]:checked');
            
            if (!email) {
                alert('Please enter your email address.');
                return;
            }
            
            if (!password) {
                alert('Please enter a password.');
                return;
            }
            
            if (password.length < 6) {
                alert('Password must be at least 6 characters.');
                return;
            }
            
            if (!firstName || !lastName) {
                alert('Please enter your first and last name.');
                return;
            }
            
            if (commMethods.length === 0) {
                alert('Please select at least one communication method.');
                return;
            }
            
            // Check all method inputs are filled
            let allMethodsFilled = true;
            const methodsData = {};
            
            commMethods.forEach(checkbox => {
                const method = checkbox.value;
                const inputValue = inputFieldsData[method] || '';
                if (!inputValue.trim()) {
                    allMethodsFilled = false;
                } else {
                    methodsData[method] = inputValue.trim();
                }
            });
            
            if (!allMethodsFilled) {
                alert('Please enter contact information for all selected methods.');
                return;
            }
            
            if (!challengeType) {
                alert('Please select a challenge type.');
                return;
            }
            
            // Send form data via AJAX
            const formData = new FormData();
            formData.append('action', 'send_verification_email');
            formData.append('email', email);
            formData.append('password', password);
            formData.append('first_name', firstName);
            formData.append('last_name', lastName);
            formData.append('platform_handle', platformHandle);
            formData.append('comm_methods', JSON.stringify(methodsData));
            formData.append('challenge_type', challengeType.value);
            formData.append('nonce', '<?php echo wp_create_nonce("verification_email_nonce"); ?>');
            
            fetch('<?php echo admin_url("admin-ajax.php"); ?>', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Remove form fields and show success message
                    const form = document.getElementById('avantage-form');
                    form.innerHTML = '<div style="text-align: center; padding: 40px 20px;"><h3 style="color: #ffd700; font-size: 2rem; margin-bottom: 20px;">Thank You!</h3><p style="color: #f0f0f0; font-size: 1.3rem; line-height: 1.6;">Thank you for starting the conversation!<br>Please check your email to verify your address.</p></div>';
                } else {
                    alert('There was an error sending the verification email. Please try again.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('There was an error. Please try again.');
            });
        });
    }
    
});
</script>
<?php
get_footer();
