<?php
/**
 * Template Name: Home Page Fold
 * Description: A custom template for displaying the home page of the WordPress site.
 * This template is used to render the homepage content and layout.
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package Avantage_Baccarat
 */

// Extend login session duration to 30 days
add_filter('auth_cookie_expiration', function($expiration, $user_id, $remember) {
    // If "remember me" is checked, extend to 30 days (2592000 seconds)
    // Otherwise, session expires when browser closes (2 days default)
    return $remember ? 30 * DAY_IN_SECONDS : 2 * DAY_IN_SECONDS;
}, 10, 3);

// Initialize errors variable
$errors = new WP_Error();
$success_message = '';

// Handle Login
if (isset($_POST['action']) && $_POST['action'] === 'influencer_login') {
    
    // Verify nonce
    if (!isset($_POST['login_nonce']) || !wp_verify_nonce($_POST['login_nonce'], 'influencer_login')) {
        $errors->add('nonce_error', 'Security verification failed. Please try again.');
    } else {
        // Get form data
        $email = sanitize_email($_POST['login_email_existing']);
        $password = $_POST['login_password'];
        $remember = isset($_POST['remember_me']) && $_POST['remember_me'] === 'on';
        
        // Validation
        if (empty($email) || !is_email($email)) {
            $errors->add('email', 'Valid email address is required');
        }
        
        if (empty($password)) {
            $errors->add('password', 'Password is required');
        }
        
        // If no validation errors, try to authenticate
        if (!$errors->has_errors()) {
            // Get user by email
            $user = get_user_by('email', $email);
            
            if (!$user) {
                $errors->add('email_not_found', 'No account found with this email address');
            } else {
                // Authenticate
                $creds = array(
                    'user_login'    => $user->user_login,
                    'user_password' => $password,
                    'remember'      => $remember
                );
                
                $user = wp_signon($creds, false);
                
                if (is_wp_error($user)) {
                    $errors->add('incorrect_password', 'Incorrect password');
                } else {
                    // Success - redirect to Influencer HQ
                    wp_redirect(home_url('/hq'));
                    exit;
                }
            }
        }
    }
}

get_header();
?>

    <main id="primary" class="site-main">
        
        <section class="hero-section">
            <div class="container-fluid h-100">
                <div class="row justify-content-center mb-1 pt-4">
                    <div class="col-12 text-center">
                        <img class="hero-logo" src="<?php echo get_template_directory_uri(); ?>/images/logo-hq.png" alt="Hero Logo">
                        
                    </div>
                </div>
                <div class="row hero-content-row align-items-center">
                    <div class="col-lg-6 col-md-6 hero-left">
                        <h1 class="hero-title">Influence…<br>Compete…<br>and Own the Future</h1>
                        
                        
                        <div class="hero-text">
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
                    <div class="col-lg-6 col-md-6 hero-right">
                        <div class="hero-right-content">
                            <img class="handphone-image" src="<?php echo get_template_directory_uri(); ?>/images/handphone.png" alt="Mobile App">
                        </div>
                    </div>
                </div>
                <div class="row justify-content-center mt-4">
                    <div class="col-12 text-center">
                        <button class="cta-button" onclick="document.getElementById('contact-form').scrollIntoView({ behavior: 'smooth', block: 'center' });">Submit Your Email</button>
                    </div>
                </div>
            </div>
        </section>
        
        <!-- Competition section-->
        <section id="competition" class="py-5 fade-in-on-scroll">
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
                <section id="how-you-earn" class="py-5 fade-in-on-scroll">
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
        <section id="game-of-kings" class="py-5 fade-in-on-scroll">
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
        <section id="how-you-earn-section" class="py-5 fade-in-on-scroll">
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
        <section id="global-exposure" class="py-5 fade-in-on-scroll">
            <div class="container">
                <h2 class="section-heading mb-5">International Competition Series — A 24-Hour Global Stage</h2>
                
                <div class="row justify-content-center">
                    <div class="col-lg-10">
                        <p class="lead mb-4">Influencers and streamers who deliver outstanding engagement are invited to appear on our partners' International Competition Series — a 24-hour global stage being introduced to highlight top creators from around the world.</p>
                        <p class="lead mb-4">Each broadcast showcases competitions from around the world, with creators appearing together from their own locations, sharing their reactions, commentary, and competitive energy in real time.</p>
                        <p class="lead mb-4">When your followers watch you play, cheer, and celebrate, they become part of a shared experience that unites people the same way great sports, music, and global competitions always have.</p>
                        <p class="lead mb-5" style="font-weight: 600;">This is what influence looks like when it comes alive.</p>
                    </div>
                </div>
            </div>
        </section>
        

        <!-- Form section -->
        <section id="contact-form" class="py-5 fade-in-on-scroll">
            <div class="container">
                <h2 class="section-heading mb-5">Start the Conversation</h2>
                
                <div class="row justify-content-center">
                    <div class="col-lg-8">
                        
                        <?php
                        // Display Messages
                        if ($errors->has_errors()) {
                            $error_messages = $errors->get_error_messages();
                            echo '<div class="alert alert-danger text-center mb-4" style="max-width: 600px; margin: 0 auto; background: rgba(215, 24, 42, 0.15) !important; border: 1px solid rgba(215, 24, 42, 0.4) !important; color: #f0f0f0 !important;">';
                            foreach ($error_messages as $error) {
                                echo esc_html($error) . '<br>';
                            }
                            echo '</div>';
                        }
                        
                        // Display success message
                        if (!empty($success_message)) {
                            echo '<div class="alert alert-success text-center mb-4" style="max-width: 600px; margin: 0 auto; background: rgba(40, 167, 69, 0.15) !important; border: 1px solid rgba(40, 167, 69, 0.4) !important; color: #f0f0f0 !important;">';
                            echo esc_html($success_message);
                            echo '</div>';
                        }
                        
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
                            <form id="avantage-form" class="simple-form">
                                        
                                        <div class="mb-4">
                                            <p class="lead mb-4">If you believe you're ready to lead, choose the method(s) you'd like us to use to communicate with you:</p>
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
                                        
                                        <!-- Genius Referrals & Challenge Selection -->
                                        <div id="challenge-section" style="max-height: 100% !important;">
                                            <hr style="border-color: rgba(255, 255, 255, 0.15); margin: 3rem 0;">
                                            
                                            <div class="mb-4">
                                                <h3 class="section-subheading mb-4">Meet Genius — Your Partner in Protecting Your Rewards</h3>
                                                <p class="lead mb-4">Once you share your contact information, you'll meet Genius Referrals — the private system that protects your influence and ensures you receive recognition for every follower and every community you inspire.</p>
                                                <p class="lead mb-4">Genius automatically tracks:</p>
                                                <ul class="lead mb-4" style="list-style: none; padding-left: 0;">
                                                    <li class="mb-2">your direct followers</li>
                                                    <li class="mb-2">their followers</li>
                                                    <li class="mb-2">the expanding network that grows from your influence</li>
                                                </ul>
                                                <p class="lead mb-4">To do this safely, Genius needs to verify your identity.</p>
                                            </div>
                                            
                                            <div class="mb-4">
                                                <h3 class="section-subheading mb-4">Why Email Verification Matters</h3>
                                                <ul class="lead mb-4" style="list-style: none; padding-left: 0;">
                                                    <li class="mb-2">Protects your rewards</li>
                                                    <li class="mb-2">Activates Genius tracking</li>
                                                    <li class="mb-2">Confirms your leadership identity</li>
                                                    <li class="mb-2">Prevents impersonation</li>
                                                    <li class="mb-2">Unlocks your private influencer portal (HQ²)</li>
                                                </ul>
                                            </div>
                                            
                                            <div class="mb-4">
                                                <h3 class="section-subheading mb-4">Choose Your Path to Lead Global Competition</h3>
                                                <p class="lead mb-4">After your email is verified, you'll enter HQ², your private influencer portal.</p>
                                                <p class="lead mb-4">From there, you have two powerful ways to lead your community:</p>
                                            </div>
                                            
                                            <div class="row mb-4">
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
                                                    <input type="radio" name="challenge_type" value="private_challenge" id="private_challenge" class="challenge-radio" style="display: none;">
                                                    <label for="private_challenge" class="challenge-card-select">
                                                        <div class="challenge-card-inner">
                                                            <h3 class="mb-3 section-subheading">Private Challenge (1–48 hours)</h3>
                                                            <p class="mb-0">A challenge created by you, for your community, on your schedule, with an option to limit participation to your own followers — and stream the action live.</p>
                                                        </div>
                                                    </label>
                                                </div>
                                            </div>
                                            
                                            <p class="lead mb-4" style="font-weight: 600;">Whether you lead global audiences or your own community, this is your arena.</p>
                                            
                                            <div class="mb-4">
                                                <label for="email" class="form-label">Email (for verification):</label>
                                                <input type="email" class="form-control" id="email" name="email" required placeholder="your@email.com">
                                                <small class="form-text text-muted">Please check your email to verify and activate Genius.</small>
                                            </div>
                                            
                                            <div class="mb-4">
                                                <label for="password" class="form-label">Password:</label>
                                                <input type="password" class="form-control" id="password" name="password" required placeholder="Enter your password (min 6 characters)">
                                            </div>
                                            
                                            <div class="text-center">
                                                <button type="submit" class="btn btn-primary btn-lg px-5">Send Verification Email</button>
                                            </div>
                                        </div>
                                        
                                    </form>
                        <?php } else { ?>
                            <!-- User is logged in -->
                            <div class="simple-form text-center">
                                <?php
                                $current_user = wp_get_current_user();
                                $preferred_comm = get_user_meta($current_user->ID, 'preferred_communication', true);
                                ?>
                                <h3 style="color: #ffd700; font-size: 2rem; margin-bottom: 20px;">Welcome Back!</h3>
                                <p style="color: #f0f0f0; font-size: 1.3rem; line-height: 1.6;">
                                    Logged in as: <strong><?php echo esc_html($current_user->user_email); ?></strong>
                                </p>
                                <?php if ($preferred_comm) : ?>
                                    <p style="color: #f0f0f0; font-size: 1.1rem;">
                                        Preferred Communication: <strong><?php echo esc_html(ucfirst($preferred_comm)); ?></strong>
                                    </p>
                                <?php endif; ?>
                                <div class="mt-4">
                                    <a href="<?php echo wp_logout_url(get_permalink()); ?>" class="btn btn-primary btn-lg px-5">Logout</a>
                                </div>
                            </div>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </section>
        
    </main><!-- #main -->
<style>
    /* Hero Section */
    .hero-section {
        min-height: 100vh;
        height: 100vh;
        display: flex;
        align-items: center;
        position: relative;
        background: url('<?php echo get_template_directory_uri(); ?>/images/herobgnd.jpg') center center no-repeat;
        background-size: cover;
        overflow: hidden;
        padding: 0;
    }
    
    .hero-section::before {
        content: "";
        position: absolute;
        inset: 0;
        background: rgba(0,0,0,0.6);
        z-index: 1;
        pointer-events: none;
    }
    
    .hero-section .container-fluid {
        position: relative;
        z-index: 2;
        display: flex;
        flex-direction: column;
        height: 100%;
    }
    
    .hero-content-row {
        height: 68%;
    }
    
    .hero-logo {
        max-width: 400px;
        width: 100%;
        height: auto;
    }
    
    .hero-left {
        padding-right: 2rem;
        padding-left: 4rem;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }
    
    .hero-title {
        font-size: 3.5rem;
        font-weight: 700;
        margin-bottom: 1.5rem;
        color: #fff;
        line-height: 1.2;
    }
    
    .hero-subtitle {
        color: #d7182a;
        font-size: 1.5rem;
        font-weight: 600;
        letter-spacing: 0.2em;
        margin-bottom: 2rem;
    }
    
    .hero-text {
        max-width: 100%;
    }
    
    .hero-intro {
        font-size: 1.4rem;
        font-weight: 400;
        color: #f0f0f0;
        margin-bottom: 0.75rem;
        font-style: italic;
        line-height: 1.4;
    }
    
    .beliefs-list {
        margin: 1rem 0;
        padding: 0;
        list-style: none;
    }
    
    .belief-item {
        font-size: 1.25rem;
        font-weight: 400;
        color: #fff;
        margin-bottom: 0.5rem;
        line-height: 1.4;
    }
    
    .hero-closing {
        font-size: 1.25rem;
        font-weight: 400;
        color: #ffd700;
        margin-top: 1rem;
        line-height: 1.4;
    }
    
    .hero-right {
        display: flex;
        justify-content: flex-end;
        align-items: center;
        padding-right: 0 !important;
    }
    
    .hero-right-content {
        display: flex;
        justify-content: flex-end;
        align-items: center;
        width: 100%;
    }
    
    .handphone-image {
        max-width: 700px;
        width: 100%;
        height: auto;
        filter: drop-shadow(0 10px 30px rgba(0, 0, 0, 0.5));
    }
    
    .cta-button {
        background: linear-gradient(135deg, #d7182a 0%, #a01320 100%);
        border: none;
        color: #fff;
        font-weight: 700;
        padding: 20px 50px;
        border-radius: 50px;
        font-size: 1.5rem;
        text-transform: uppercase;
        letter-spacing: 2px;
        cursor: pointer;
        transition: all 0.3s ease;
        box-shadow: 0 10px 30px rgba(215, 24, 42, 0.5),
                    0 0 40px rgba(215, 24, 42, 0.3);
        position: relative;
        overflow: hidden;
    }
    
    .cta-button::before {
        content: "";
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
        transition: left 0.6s ease;
    }
    
    .cta-button:hover::before {
        left: 100%;
    }
    
    .cta-button:hover {
        background: linear-gradient(135deg, #e91e35 0%, #b01525 100%);
        transform: translateY(-3px) scale(1.05);
        box-shadow: 0 15px 40px rgba(215, 24, 42, 0.7),
                    0 0 60px rgba(215, 24, 42, 0.4);
    }
    
    .cta-button:active {
        transform: translateY(-1px) scale(1.02);
    }
    
    /* Section Headings */
    .section-heading {
        font-size: 32px;
        font-weight: 600;
        color: #fff;
        margin-bottom: 2.5rem;
        text-align: center;
    }
    
    .section-subheading {
        font-size: 26px;
        font-weight: 600;
        color: #ffd700;
    }
    
    /* Increase all text size by 15% */
    .lead {
        font-size: 1.38rem;
    }
    
    h2 {
        font-size: 2.3rem;
    }
    
    /* Body text left-aligned */
    #competition .col-lg-10,
    #how-you-earn .col-md-12,
    #how-you-earn .col-lg-10,
    #game-of-kings .col-lg-10,
    #how-you-earn-section .col-lg-10,
    #global-exposure .col-lg-10 {
        text-align: left;
    }
    
    /* 40px spacing between sections */
    section.py-5 {
        padding-top: 40px !important;
        padding-bottom: 40px !important;
    }
    
    /* Responsive adjustments */
    @media (max-width: 991px) {
        .hero-section {
            min-height: auto;
            height: auto;
            padding: 3rem 0;
        }
        
        .hero-logo {
            max-width: 250px;
            margin-bottom: 2rem;
        }
        
        .hero-left {
            padding-right: 1rem;
            padding-left: 1rem;
            margin-bottom: 3rem;
        }
        
        .hero-title {
            font-size: 2.5rem;
        }
        
        .hero-subtitle {
            font-size: 1.2rem;
        }
        
        .hero-intro {
            font-size: 1.2rem;
        }
        
        .belief-item {
            font-size: 1.1rem;
        }
        
        .hero-closing {
            font-size: 1.1rem;
        }
        
        .hero-right {
            justify-content: center;
        }
        
        .hero-right-content {
            align-items: center;
        }
        
        .handphone-image {
            max-width: 250px;
        }
        
        .cta-button {
            padding: 16px 40px;
            font-size: 1.2rem;
        }
    }
    
    @media (max-width: 767px) {
        .hero-title {
            font-size: 2rem;
        }
        
        .hero-subtitle {
            font-size: 1rem;
        }
        
        .section-heading {
            font-size: 26px;
        }
        
        .section-subheading {
            font-size: 16px;
        }
        
        .hero-intro, .belief-item, .hero-closing {
            font-size: 1rem;
        }
        
        .hero-right {
            display: none;
        }
        
        .handphone-image {
            display: none;
        }
        
        .cta-button {
            padding: 14px 30px;
            font-size: 1rem;
            width: 100%;
            max-width: 300px;
        }
    }
    
    .two-vertical-images {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }
    
    /* Responsive CTA Button */
    @media (max-width: 767px) {
        .btn-cta {
            width: 100% !important;
            display: block !important;
        }
    }
    
    @media (min-width: 768px) {
        .btn-cta {
            display: inline-block !important;
        }
    }
    
    #call-to-action {
        background: linear-gradient(135deg, rgba(26, 26, 26, 0.95) 0%, rgba(0, 0, 0, 0.95) 100%);
        border-top: 1px solid rgba(255, 173, 0, 0.3);
        border-bottom: 1px solid rgba(255, 173, 0, 0.3);
        display: none; /* Hide initially */
        transition: all 0.5s ease-in-out;
    }
    #call-to-action.show {
        display: block;
        animation: slideInFromBottom 0.8s ease-out forwards;
    }
    @keyframes slideInFromBottom {
        from {
            opacity: 0;
            transform: translateY(50px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    .verification-badges {
        display: flex;
        justify-content: center;
        gap: 30px;
        flex-wrap: wrap;
    }
    .badge-item {
        color: #ffad00;
        font-size: 1.1rem;
        font-weight: 600;
        text-shadow: 0 1px 2px rgba(0, 0, 0, 0.5);
    }
    @media (max-width: 767px) {
        .verification-badges {
            gap: 20px;
        }
        .badge-item {
            font-size: 1rem;
        }
    }

    

    body, h1, h2, h3, h4, h5, h6, p, a, ul, ol, li, .btn-69, .card, .card-title, .lead, label, .navbar-brand, .nav-link, .carousel-item, .display-5, .fw-bold, .hero-content, .hero-image, .button-container, .card-body, .card-img-top, .text-center, .align-middle, .vh-100, .mb-3, .mb-4, .mb-5, .py-5, .container, .row, .col, .col-12, .col-md-6, .col-lg-7, .col-lg-8, .d-grid, .shadow-sm, .rounded, .bg-dark, .bg-light, .ratio, .list-unstyled, .fs-5, .fw-bold, .text-center, .justify-content-center, .align-items-center, .fade-in, .fade-in-on-scroll {
        font-family: 'Source Sans Pro', Arial, Helvetica, sans-serif !important;
    }
    .page {
    margin: 0;
}
    #competition {
    position: relative;
    background: linear-gradient(135deg, rgba(10, 10, 15, 0.98) 0%, rgba(20, 15, 25, 0.95) 100%);
    border-top: 1px solid rgba(215, 24, 42, 0.2);
    box-shadow: inset 0 1px 20px rgba(215, 24, 42, 0.05);
}

/* Streamers section styling */
#streamers {
    background: linear-gradient(to right, rgba(15, 20, 25, 0.95) 0%, rgba(25, 30, 35, 0.98) 50%, rgba(15, 20, 25, 0.95) 100%);
    border-top: 1px solid rgba(255, 255, 255, 0.05);
}

#streamers .card {
    border: 1px solid rgba(255, 255, 255, 0.1);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    background: rgba(0, 0, 0, 0.3);
}

#streamers .card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(215, 24, 42, 0.3);
}

/* How You Earn section - Magic Johnson */
#how-you-earn {
    background: linear-gradient(165deg, rgba(25, 15, 20, 0.98) 0%, rgba(35, 20, 25, 0.95) 100%);
    position: relative;
    border-left: 3px solid rgba(215, 24, 42, 0.1);
}

#how-you-earn::after {
    content: "";
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 1px;
    background: linear-gradient(90deg, transparent, rgba(255, 215, 0, 0.3), transparent);
}

/* First Step section styling - Baccarat Championship */
#first-step {
    background: linear-gradient(to bottom, rgba(5, 10, 15, 0.98) 0%, rgba(15, 20, 25, 0.98) 100%);
    box-shadow: inset 0 10px 30px rgba(0, 0, 0, 0.5);
}

/* How You Earn Section - Earnings breakdown */
#how-you-earn-section {
    background: linear-gradient(135deg, rgba(30, 25, 20, 0.95) 0%, rgba(20, 15, 10, 0.98) 100%);
    border-top: 2px solid rgba(255, 215, 0, 0.15);
    border-bottom: 1px solid rgba(255, 215, 0, 0.1);
}

/* Global Exposure section */
#global-exposure {
    background: linear-gradient(45deg, rgba(12, 18, 22, 0.98) 0%, rgba(22, 28, 32, 0.95) 50%, rgba(12, 18, 22, 0.98) 100%);
    position: relative;
}

#global-exposure::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 2px;
    background: linear-gradient(90deg, transparent, rgba(215, 24, 42, 0.2), transparent);
}

/* Influencer Equity News section */
#influencer-equity-news {
    background: linear-gradient(135deg, rgba(22, 22, 22, 0.98) 0%, rgba(32, 32, 32, 0.95) 100%);
    position: relative;
}

#influencer-equity-news::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 1px;
    background: linear-gradient(90deg, transparent, rgba(255, 215, 0, 0.2), transparent);
}

#first-step ul li {
    color: #ffffff;
    font-size: 1.1rem;
    margin-bottom: 1rem;
}

#first-step ul li::before {
    content: "✓";
    color: #F23E3E;
    font-weight: bold;
    margin-right: 10px;
}

/* Section Title Styles */
.section-title {
    position: relative;
    display: inline-block;
    padding-bottom: 1rem;
}

.section-title::after {
    content: "";
    position: absolute;
    bottom: 0;
    left: 50%;
    transform: translateX(-50%);
    width: 80px;
    height: 3px;
    background: linear-gradient(90deg, #d7182a, #ff4757);
    border-radius: 2px;
}

/* Competition Items Styling */
.competition-item {
    background: linear-gradient(135deg, rgba(215, 24, 42, 0.1) 0%, rgba(0, 0, 0, 0.3) 100%);
    border: 2px solid rgba(255, 255, 255, 0.1);
    border-radius: 15px;
    padding: 1.5rem 1rem;
    text-align: center;
    transition: all 0.3s ease;
    height: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-height: 120px;
}

.competition-item:hover {
    transform: translateY(-5px);
    border-color: rgba(255, 215, 0, 0.4);
    box-shadow: 0 10px 30px rgba(215, 24, 42, 0.3);
    background: linear-gradient(135deg, rgba(215, 24, 42, 0.15) 0%, rgba(0, 0, 0, 0.4) 100%);
}

.competition-icon {
    font-size: 2.5rem;
    margin-bottom: 0.75rem;
    filter: drop-shadow(0 2px 8px rgba(255, 215, 0, 0.3));
}

.competition-text {
    color: #fff;
    font-size: 1rem;
    font-weight: 600;
    text-transform: capitalize;
    letter-spacing: 0.5px;
}


    .mb-3 {
    color: #fff;
    margin-bottom: 0.5rem !important;
}
ul, ol
 {
    margin: 0;
}
    .carousel-control-next, .carousel-control-prev {
        width: inherit;
    }
    .card {
        background-color: rgb(14 14 14)!important;
    }
    .button-container {
        padding:40px 40px;
    }
    body {
        background-color: #000000;

    }
    .navbar-brand, .nav-link,label {
        color: rgb(255, 255, 252)!important;

    }
    #about {
        padding: 60px 40px;
        background: linear-gradient(0deg, rgba(0, 0, 0, 1) 0%, rgb(36 36 36) 50%, rgba(0, 0, 0, 1) 100%);
    }
   
    h1 {
        color:rgb(255, 255, 252);
        font-size: 3rem;
        margin-bottom: 30px;
    }
    h2,p {
        color: rgb(255, 255, 252);
    }
    a:visited {
        color: inherit;
    }
.fade-in {
    opacity: 0;
    animation: fadeIn 2s ease-in forwards;
}
.card img {
    max-width: 200px;
}
.btn-69 {
    display: inline-block;
    outline: none;
    font-size: 16px;
    box-sizing: border-box;
    border-radius: 5px;
    padding: 13px 25px;
    text-transform: uppercase;
    box-shadow: 0 3px 6px rgba(0, 0, 0, 0.16),
                0 3px 6px rgba(110, 80, 20, 0.4),
                inset 0 -2px 5px 1px rgba(139, 66, 8, 1),
                inset 0 -1px 1px 3px rgba(250, 227, 133, 1);
    background-image: linear-gradient(160deg,  #a54e07, #b47e11, #fef1a2, #bc881b, #a54e07)!important;
    border: 1px solid #a55d07;
    color: rgb(120, 50, 5);
    text-shadow: 0 2px 2px rgba(250, 227, 133, 1);
    cursor: pointer;
    transition: all 0.2s ease-in-out;
    background-size: 100% 100%;
    background-position: center;
    user-select: none;
    font-family: Arial, Helvetica, sans-serif;
    font-weight: bold;
    /*** full width block ***/
    /* width: 100%; */
}

.btn-69:focus,
.btn-69:hover {
    background-size: 150% 150%;
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.19),
                0 6px 6px rgba(0, 0, 0, 0.23),
                inset 0 -2px 5px 1px #b17d10,
                inset 0 -1px 1px 3px rgba(250, 227, 133, 1);
    border: 1px solid rgba(165, 93, 7, 0.6);
    color: rgba(120, 50, 5, 0.8);
}

.btn-69:active {
    box-shadow: 0 3px 6px rgba(0, 0, 0, 0.16),
                0 3px 6px rgba(110, 80, 20, 0.4),
                inset 0 -2px 5px 1px #b17d10,
                inset 0 -1px 1px 3px rgba(250, 227, 133, 1);
}

.btn-69:disabled {
    pointer-events: none;
    opacity: .65;
    color: #7e7e7e;
    background: #dcdcdc;
    box-shadow: none;
    text-shadow: none;
    border-color: #c2c2c2;
}
.card-title {
        color: #efefef;
}

/* Form Styling */
.simple-form {
    background: linear-gradient(135deg, rgba(215, 24, 42, 0.08) 0%, rgba(0, 0, 0, 0.4) 50%, rgba(255, 215, 0, 0.08) 100%);
    padding: 4rem;
    border-radius: 20px;
    backdrop-filter: blur(20px);
    border: 2px solid rgba(255, 255, 255, 0.1);
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5), 
                0 0 100px rgba(215, 24, 42, 0.1),
                inset 0 1px 1px rgba(255, 255, 255, 0.1);
    position: relative;
    overflow: visible;
    min-height: auto;
    transition: min-height 0.5s ease;
}

.simple-form .form-step {
    position: relative;
    z-index: 1;
}

.simple-form .form-step h4 {
    color: #fff;
    font-weight: 600;
    text-shadow: 0 2px 10px rgba(0, 0, 0, 0.5);
    font-size: 1.5rem;
}

.simple-form .form-check {
    padding-left: 1.8rem;
    margin-bottom: 1.5rem;
}

.simple-form .form-check-input {
    width: 1.3rem;
    height: 1.3rem;
    margin-top: 0.25rem;
    cursor: pointer;
    border: 2px solid rgba(255, 255, 255, 0.3);
    background: rgba(0, 0, 0, 0.3);
}

.simple-form .form-check-input:checked {
    background-color: #d7182a;
    border-color: #d7182a;
    box-shadow: 0 0 15px rgba(215, 24, 42, 0.6);
}

.simple-form .form-check-label {
    color: #f0f0f0;
    cursor: pointer;
    padding-left: 15px;
    font-size: 1.15rem;
    text-shadow: 0 1px 3px rgba(0, 0, 0, 0.5);
}

.simple-form .form-control {
    background: rgba(0, 0, 0, 0.4);
    border: 2px solid rgba(255, 255, 255, 0.2);
    color: #fff;
    padding: 0.9rem 1.2rem;
    border-radius: 10px;
    font-size: 1.1rem;
    transition: all 0.3s ease;
}

.simple-form .form-control::placeholder {
    color: rgba(255, 255, 255, 0.4);
}

.simple-form .form-control:focus {
    background: rgba(0, 0, 0, 0.5);
    border-color: rgba(215, 24, 42, 0.6);
    color: #fff;
    box-shadow: 0 0 20px rgba(215, 24, 42, 0.3), 
                0 0 0 0.2rem rgba(215, 24, 42, 0.15);
    transform: translateY(-2px);
}

.simple-form .conditional-field {
    margin-left: 1.8rem;
    transition: all 0.4s ease;
}

.simple-form hr {
    border-color: rgba(255, 255, 255, 0.15);
    margin: 3rem 0;
    box-shadow: 0 1px 10px rgba(215, 24, 42, 0.2);
}

.simple-form .btn-primary {
    background: linear-gradient(135deg, #d7182a 0%, #a01320 100%);
    border: none;
    font-weight: 700;
    padding: 16px 60px;
    border-radius: 12px;
    transition: all 0.3s ease;
    font-size: 1.25rem;
    text-transform: uppercase;
    letter-spacing: 1.5px;
    box-shadow: 0 8px 25px rgba(215, 24, 42, 0.4),
                0 0 30px rgba(215, 24, 42, 0.2);
    position: relative;
    overflow: hidden;
}

.simple-form .btn-primary::before {
    content: "";
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
    transition: left 0.5s ease;
}

.simple-form .btn-primary:hover::before {
    left: 100%;
}

.simple-form .btn-primary:hover {
    background: linear-gradient(135deg, #e91e35 0%, #b01525 100%);
    transform: translateY(-3px) scale(1.05);
    box-shadow: 0 12px 35px rgba(215, 24, 42, 0.6),
                0 0 50px rgba(215, 24, 42, 0.3);
}

.simple-form .btn-primary:active {
    transform: translateY(-1px) scale(1.02);
}

.simple-form .info-box {
    background: linear-gradient(135deg, rgba(255, 215, 0, 0.1) 0%, rgba(215, 24, 42, 0.05) 100%) !important;
    border: 2px solid rgba(255, 215, 0, 0.2);
    border-radius: 15px;
    box-shadow: 0 5px 20px rgba(0, 0, 0, 0.3),
                inset 0 1px 1px rgba(255, 215, 0, 0.1);
}

.simple-form .info-box p {
    color: #f0f0f0;
    margin-bottom: 0.5rem;
    text-shadow: 0 1px 3px rgba(0, 0, 0, 0.5);
}

.text-muted {
    color: rgb(187 187 187 / 75%) !important;
}

/* Tab Styling */
.auth-tabs .nav-tabs {
    border-bottom: 2px solid rgba(255, 255, 255, 0.1);
    margin-bottom: 2rem;
}

.auth-tabs .nav-link {
    color: #b0b0b0;
    font-size: 1.2rem;
    font-weight: 600;
    padding: 12px 40px;
    border: none;
    background: transparent;
    transition: all 0.3s ease;
}

.auth-tabs .nav-link:hover {
    color: #ffd700;
    border-color: transparent;
}

.auth-tabs .nav-link.active {
    color: #ffd700;
    background: rgba(255, 215, 0, 0.1);
    border: none;
    border-bottom: 3px solid #d7182a;
}

/* Tab content styling */
.tab-content {
    position: relative;
    z-index: 1;
}

.tab-pane .simple-form {
    position: relative;
    z-index: 1;
}

/* Challenge section labels */
.simple-form .form-check-label strong {
    display: block;
    margin-bottom: 0.3rem;
}

.simple-form .form-check-label small {
    color: rgba(255, 255, 255, 0.7);
    font-size: 0.95rem;
    line-height: 1.4;
}

/* Challenge section styling */
#challenge-section {
    animation: slideDown 0.5s ease-out forwards;
    overflow: visible;
}

@keyframes slideDown {
    from {
        opacity: 0;
        max-height: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        max-height: 2000px;
        transform: translateY(0);
    }
}

#challenge-section h4 {
    color: #ffd700;
    font-weight: 600;
    text-shadow: 0 2px 10px rgba(0, 0, 0, 0.5);
}

#challenge-section .text-center h4 {
    font-size: 1.6rem;
}

#challenge-section .lead {
    color: #f0f0f0;
    text-shadow: 0 1px 3px rgba(0, 0, 0, 0.5);
}

/* Challenge Card Selection Styling */
.challenge-card-select {
    display: block;
    width: 100%;
    height: 100%;
    min-height: 200px;
    cursor: pointer;
    border-radius: 15px;
    border: 2px solid rgba(255, 255, 255, 0.2);
    background: linear-gradient(135deg, rgba(0, 0, 0, 0.3) 0%, rgba(0, 0, 0, 0.5) 100%);
    padding: 2rem;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.challenge-card-select:hover {
    border-color: rgba(255, 215, 0, 0.5);
    transform: translateY(-5px);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
}

.challenge-card-select .challenge-card-inner {
    position: relative;
    z-index: 2;
    text-align: center;
}

.challenge-card-select .challenge-card-inner h3 {
    font-weight: 700;
    margin-bottom: 1rem;
}

.challenge-card-select .challenge-card-inner p {
    color: #f0f0f0;
    font-size: 1rem;
    line-height: 1.6;
}

/* Selected state */
.challenge-radio:checked + .challenge-card-select {
    border-color: #ffd700;
    background: linear-gradient(135deg, rgba(255, 215, 0, 0.15) 0%, rgba(215, 24, 42, 0.1) 100%);
    box-shadow: 0 0 30px rgba(255, 215, 0, 0.3), 0 10px 30px rgba(0, 0, 0, 0.5);
}

.challenge-radio:checked + .challenge-card-select::before {
    content: "✓";
    position: absolute;
    top: 15px;
    right: 15px;
    background: #ffd700;
    color: #000;
    width: 30px;
    height: 30px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 1.2rem;
    z-index: 3;
}

/* Blue card styling for Weekend World */
label[for="weekend_world"].challenge-card-select {
    border-color: rgba(0, 102, 204, 0.3);
}

label[for="weekend_world"].challenge-card-select:hover {
    border-color: rgba(77, 166, 255, 0.6);
}

.challenge-radio:checked + label[for="weekend_world"].challenge-card-select {
    border-color: #4da6ff;
    background: linear-gradient(135deg, rgba(0, 102, 204, 0.2) 0%, rgba(0, 0, 0, 0.4) 100%);
    box-shadow: 0 0 30px rgba(77, 166, 255, 0.4), 0 10px 30px rgba(0, 0, 0, 0.5);
}

/* Green card styling for Private Challenge */
label[for="private_challenge"].challenge-card-select {
    border-color: rgba(0, 204, 102, 0.3);
}

label[for="private_challenge"].challenge-card-select:hover {
    border-color: rgba(77, 255, 136, 0.6);
}

.challenge-radio:checked + label[for="private_challenge"].challenge-card-select {
    border-color: #4dff88;
    background: linear-gradient(135deg, rgba(0, 204, 102, 0.2) 0%, rgba(0, 0, 0, 0.4) 100%);
    box-shadow: 0 0 30px rgba(77, 255, 136, 0.4), 0 10px 30px rgba(0, 0, 0, 0.5);
}

#contact-form {
    background: linear-gradient(135deg, rgba(10, 10, 15, 0.98) 0%, rgba(25, 15, 20, 0.98) 50%, rgba(15, 10, 20, 0.98) 100%);
    position: relative;
    overflow: visible;
}

#contact-form::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, 
        transparent, 
        rgba(215, 24, 42, 0.5), 
        rgba(255, 215, 0, 0.5), 
        rgba(215, 24, 42, 0.5), 
        transparent);
    z-index: 1;
}

#contact-form::after {
    content: "";
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, 
        transparent, 
        rgba(255, 215, 0, 0.5), 
        rgba(215, 24, 42, 0.5), 
        rgba(255, 215, 0, 0.5), 
        transparent);
    z-index: 1;
}

.fade-in-on-scroll {
    opacity: 0;
    transform: translateY(60px);
    transition: opacity 1.9s ease, transform 1.9s cubic-bezier(.4,0,.2,1);
}
.fade-in-on-scroll.visible {
    opacity: 1;
    transform: none;
}

.section-blob {
    position: fixed;
    top: 30%;
    right: 40px;
    z-index: 1000;
    background: #fff;
    color: #111;
    padding: 28px 32px;
    border-radius: 20px;
    box-shadow: 0 8px 32px rgba(0,0,0,0.18);
    font-size: 1.25rem;
    font-weight: 700;
    max-width: 320px;
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.5s;
    display: none;
    animation: fadeInBlob 1s ease-in-out;
}
.section-blob.visible {
    transition: opacity 0.5s;
    opacity: 0.8;
    display: block;
    pointer-events: auto;
}
@keyframes fadeInBlob {
    from {
        opacity: 0;
    }
    to {
        opacity: 0.8;
    }
}
@keyframes fadeIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}
@media (max-width: 991px) {
    .section-blob {
        right: 10px;
        top: unset;
        bottom: 30px;
        max-width: 90vw;
        font-size: 1rem;
        padding: 18px 16px;
    }
}
@media (min-width: 768px) {
    .card {
        padding-top: 40px;
    }
}
@media (max-width: 767px) {
    .card {
        padding-top: 20px;
    }
    h1 {
        font-size: 2rem;
    }
}


</style>
<script>
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
    
    const inputFieldsData = {};
    
    if (commMethodCheckboxes.length > 0) {
        commMethodCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                updateCommInputs();
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
