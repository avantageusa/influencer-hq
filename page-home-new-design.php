<?php
/**
 * Template Name: New design Home Page
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
<link rel="stylesheet" href="<?php echo get_template_directory_uri(); ?>/new-design/assets/css/plugins.css">
<link rel="stylesheet" href="<?php echo get_template_directory_uri(); ?>/new-design/assets/css/style.css">
<link rel="preload" href="<?php echo get_template_directory_uri(); ?>/new-design/assets/css/fonts/space.css" as="style" onload="this.rel='stylesheet'">
<style>
    .hero-section {
        min-height: 100vh;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        padding: 0 !important;
    }
    
    .hero-section .container {
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        min-height: 100vh;
    }
    
    .hero-top-row {
        padding-top: 50px;
        margin-top: 25px;
    }
    
    .hero-image {
        max-width: 85%;
        height: auto;
        margin-bottom: 2rem;
    }
    
    .hero-section .btn {
        margin-top: 25px;
        background: #d9222a !important;
        border: none !important;
        font-size: 1.25rem;
    }
    
    .hero-bottom-row {
        margin-top: auto;
        margin-bottom: 0;
        padding-bottom: 0;
    }
    
    .hero-bottom-row .col-lg-8 p,
    .hero-bottom-row .col-lg-8 h4 {
        color: #343f52;
    }
    
    .hero-mic-container {
        display: flex;
        align-items: flex-end;
        justify-content: flex-end;
        height: 100%;
    }
    
    .hero-mic-container img {
        max-height: 400px;
        width: auto;
        object-fit: contain;
    }
    
    @media (max-width: 991px) {
        .hero-mic-container {
            display: none;
        }
        
        .hero-image {
            max-width: 90%;
        }
        
        .hero-section {
            min-height: auto;
        }
        
        .hero-section .container {
            min-height: auto;
        }
        
        .hero-section .btn {
            margin-top: 0;
            margin-bottom: 25px;
        }
    }
    
    @media (max-width: 767px) {
        .hero-image {
            max-width: 95%;
        }
    }
    
    .form-check-label {
        color: #343f52 !important;
        font-weight: 500;
    }
    
    .form-check-input {
        border-color: #d9222a !important;
    }
    
    .form-check-input:checked {
        background-color: #d9222a !important;
        border-color: #d9222a !important;
    }
</style>
    <div class="content-wrapper">
    <section class="wrapper hero-section">
      <div class="container">
        <div class="row text-center hero-top-row">
          <div class="col-lg-9 col-xl-8 col-xxl-7 mx-auto" data-cues="slideInDown" data-group="page-title" data-delay="500">
            <img class="hero-image" src="<?php echo get_template_directory_uri(); ?>/images/logo-black-hq.png" alt="Hero Logo">
            <h1 class="display-1 ls-sm mb-4 px-md-8 px-lg-0">Influence… Compete… and Own the Future</h1>
            <div>
              <a href="#contact-form" class="btn btn-lg btn-primary rounded" id="hero-cta-btn">Yes I'm ready</a>
            </div>
          </div>
          <!-- /column -->
        </div>
        <!-- /.row -->
        <div class="row align-items-center hero-bottom-row" data-cues="slideInUp" data-group="hero-bottom" data-delay="800">
          <div class="col-lg-8 text-start">
            <h4>People don't buy what you do.</h4>
            <h4>They buy what you believe.</h4>
            <h4>We believe influence deserves more than short-term payouts.</h4>
            <h4>We believe your voice should create long-term value.</h4>
            <h4>We believe those who drive the energy deserve to share in what they help build.</h4>
            <h4>These beliefs guide every opportunity represented through Influencer Headquarters, where influencers partner with global platforms that value long-term contribution — not one-time campaigns.</h4>
          </div>
          <!-- /column -->
          <div class="col-lg-4 hero-mic-container">
            <figure class="mb-0"><img class="img-fluid" src="<?php echo get_template_directory_uri(); ?>/images/hero-mic.png" alt="Hero Microphone"></figure>
          </div>
          <!-- /column -->
        </div>
        <!-- /.row -->
        
      </div>
      <!-- /.container -->
      
    </section>
    <!-- /section -->
    
    <!-- The Future of Influencer Marketplace -->
    <section class="wrapper bg-gradient-primary py-8">
      <div class="container">
        <div class="row text-center">
          <div class="col-12 mb-12">
            <h3 class="display-2 ls-sm">The Future of the Influencer Marketplace</h3>
          </div>
          <!-- /column -->
        </div>
        <!-- /.row -->
        <div class="row gy-6">
          <div class="col-12">
            <p class="lead fs-lg mb-4">Brands everywhere rely on influencers to shape culture, spark engagement, and build communities.</p>
            <p class="lead fs-lg mb-4">But traditional partnerships often end sooner than the value you provide.</p>
            <p class="lead fs-lg mb-4" style="font-weight: 600;">We believe in a different path:</p>
            <p class="lead fs-lg mb-4">When your influence grows a platform, you should share in the long-term value created by that growth.</p>
            <p class="lead fs-lg mb-4">Our partners' platforms are designed to reward sustained contribution, giving influencers a meaningful stake in the success they help build.</p>
            <p class="lead fs-lg mb-0">As these ecosystems expand across new markets and categories, the value of your influence grows with them.</p>
          </div>
        </div>
      </div>
      <!-- /.container -->
    </section>
    <!-- /section -->
    
    <!-- Magic Johnson Story -->
    <section class="wrapper bg-light py-8">
      <div class="container">
        <div class="row text-center">
          <div class="col-12 mb-10">
            <h3 class="display-2 ls-sm">Learn from Magic's moment</h3>
          </div>
          <!-- /column -->
        </div>
        <!-- /.row -->
        <div class="row gy-10 align-items-center">
          <div class="col-lg-5">
            <figure class="rounded"><img class="img-fluid" src="<?php echo get_template_directory_uri(); ?>/images/magic-story.jpg" alt="Magic Johnson" /></figure>
          </div>
          <!-- /column -->
          <div class="col-lg-6 ms-auto">
            <p class="lead fs-lg mb-4">In 1979, Converse offered college basketball star Magic Johnson what seemed like a fortune: $100,000 — guaranteed cash.</p>
            <p class="lead fs-lg mb-4">Nike, a newcomer on the scene, offered something different: 1 cent for every shoe sold, plus stock then valued at just 11 cents per share.</p>
            <p class="lead fs-lg mb-4">Magic took the cash.</p>
            <p class="lead fs-lg mb-4">That stock offer — the one he passed — would be worth more than $5 billion today.</p>
            <p class="lead fs-lg mb-4">We believe influencers should have the opportunity to share in the ownership moment Magic missed.</p>
            <p class="lead fs-lg mb-4">TikTok star Alix Earle chose an ownership-based partnership with Poppi instead of a traditional cash sponsorship.</p>
            <p class="lead fs-lg mb-4">When Poppi was soon acquired by PepsiCo for over a billion dollars, her choice became a defining moment in the creator economy.</p>
            <p class="lead fs-lg mb-0">Ownership matters — and influence deserves the chance to share in it.</p>
          </div>
          <!-- /column -->
        </div>
        <!-- /.row -->
      </div>
      <!-- /.container -->
    </section>
    <!-- /section -->
    
    <!-- Competition Section -->
    <section class="wrapper bg-light py-8">
      <div class="container">
        <div class="row text-center">
          <div class="col-12 mb-10">
            <h3 class="display-2 ls-sm">Competition: The Oldest Language in the World</h3>
          </div>
          <!-- /column -->
        </div>
        <!-- /.row -->
        <div class="row gy-6 mb-10">
          <div class="col-lg-6">
            <p class="lead fs-lg mb-4">From the earliest days of civilization, competition has united people across cultures.</p>
            <p class="lead fs-lg mb-4">It began in ancient Greece, where the first Olympic Games ignited national pride.</p>
            <p class="lead fs-lg mb-4">It filled the arenas of Rome, where crowds gathered for courage, skill, and spectacle.</p>
            <p class="lead fs-lg mb-0">It lives in Asia's legendary traditions — games of mastery, precision, rhythm, and strategy celebrated for centuries.</p>
          </div>
          <div class="col-lg-6">
            <p class="lead fs-lg mb-3 fw-bold">Today, competition fuels:</p>
            <ul class="lead fs-lg">
              <li class="mb-2">Global sports</li>
              <li class="mb-2">Music battles</li>
              <li class="mb-2">Esports arenas</li>
              <li class="mb-2">Creator challenges</li>
              <li class="mb-2">Digital tournaments</li>
              <li class="mb-0">Moments we share together</li>
            </ul>
          </div>
        </div>
        
        <div class="row gy-6" style="margin-top: 50px; margin-bottom: 50px;">
          <div class="col-lg-4">
            <div class="card bg-soft-fuchsia h-100">
              <div class="card-body p-6 text-center">
                <p class="lead mb-0">Competition is universal.</p>
              </div>
            </div>
          </div>
          <div class="col-lg-4">
            <div class="card bg-soft-fuchsia h-100">
              <div class="card-body p-6 text-center">
                <p class="lead mb-0">Competition is emotional.</p>
              </div>
            </div>
          </div>
          <div class="col-lg-4">
            <div class="card bg-soft-fuchsia h-100">
              <div class="card-body p-6 text-center">
                <p class="lead mb-0">Competition is human.</p>
              </div>
            </div>
          </div>
        </div>
        
        <div class="row">
          <div class="col-12">
            <p class="lead fs-lg mb-4">Throughout history, competition has always needed leaders — voices who elevate every moment, ignite anticipation, and unite communities.</p>
            <p class="lead fs-lg mb-0">Today, those leaders are influencers.</p>
          </div>
        </div>
      </div>
      <!-- /.container -->
    </section>
    <!-- /section -->
    
    <!-- Elegance Section -->
    <section class="wrapper py-8" style="background: #e7e7e7;">
      <div class="container">
        <div class="row text-center">
          <div class="col-12 mb-10">
            <h3 class="display-2 ls-sm">Elegance. Prestige. Global Legacy.</h3>
          </div>
          <!-- /column -->
        </div>
        <!-- /.row -->
        
        <div class="row gy-6 align-items-center">
          <div class="col-lg-8">
            <p class="lead fs-lg mb-4">The stages influencers step onto today carry the same timeless elements found in the world's greatest competitions:</p>
            <ul class="lead fs-lg mb-4" style="list-style: none; padding-left: 0;">
              <li class="mb-3">Elegance that never goes out of style</li>
              <li class="mb-3">Prestige that spans centuries</li>
              <li class="mb-3">A global following that crosses borders and cultures</li>
              <li class="mb-3">Intensity and emotion in every moment</li>
            </ul>
            <p class="lead fs-lg mb-0">Influence deserves a stage worthy of that legacy — and now, it has one.</p>
          </div>
          <!-- /column -->
          <div class="col-lg-4">
            <figure class="rounded"><img class="img-fluid" src="<?php echo get_template_directory_uri(); ?>/images/placeholder-red.jpg" alt="Elegance" /></figure>
          </div>
          <!-- /column -->
        </div>
      </div>
      <!-- /.container -->
    </section>
    <!-- /section -->
    
    <!-- International Competition Series -->
    <section class="wrapper bg-soft-primary py-8">
      <div class="container">
        <div class="row text-center mb-10">
          <div class="col-12">
            <h3 class="display-2 ls-sm mb-0">International Competition Series — A 24-Hour Global Stage</h3>
          </div>
          <!-- /column -->
        </div>
        <!-- /.row -->
        
        <div class="row gy-6 align-items-center">
          <div class="col-lg-4">
            <figure class="rounded"><img class="img-fluid" src="<?php echo get_template_directory_uri(); ?>/images/placeholder-red.jpg" alt="International Competition" /></figure>
          </div>
          <!-- /column -->
          <div class="col-lg-8">
            <p class="lead fs-lg mb-4">Influencers and streamers who deliver outstanding engagement are invited to appear on our partners' International Competition Series — a 24-hour global stage being introduced to highlight top creators from around the world.</p>
            <p class="lead fs-lg mb-4">Each broadcast showcases competitions from around the world, with creators appearing together from their own locations, sharing their reactions, commentary, and competitive energy in real time.</p>
            <p class="lead fs-lg mb-4">When your followers watch you play, cheer, and celebrate, they become part of a shared experience that unites people the same way great sports, music, and global competitions always have.</p>
            <p class="lead fs-lg mb-0" style="font-weight: 600;">This is what influence looks like when it comes alive.</p>
          </div>
          <!-- /column -->
        </div>
      </div>
      <!-- /.container -->
      <div class="overflow-hidden">
        <div class="divider text-soft-primary mx-n2">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 70">
            <path fill="currentColor" d="M1440,70H0V45.16a5762.49,5762.49,0,0,1,1440,0Z" />
          </svg>
        </div>
      </div>
    </section>
    <!-- /section -->
    
    <!-- Form section -->
    <section id="contact-form" class="py-8 fade-in-on-scroll wrapper bg-light">
        <div class="container">
            <h2 class="section-heading mb-5 text-center">Start the Conversation</h2>
            
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    
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
                                    
                                    <div class="text-center mb-4">
                                        <button type="button" id="show-genius-btn" class="btn btn-primary btn-lg px-5" style="display: none;">Yes I'm ready</button>
                                    </div>
                                    
                                    <!-- Genius Referrals & Challenge Selection -->
                                    <div id="challenge-section" style="display: none;">
                                        
                                        
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
                                        
                                        <div class="mb-4">
                                            <div class="form-check mb-3">
                                                <input class="form-check-input" type="radio" name="challenge_type" value="weekend_world" id="weekend_world">
                                                <label class="form-check-label" for="weekend_world">
                                                    <strong>Weekend World Challenge</strong><br>
                                                    <span class="text-muted">A global challenge open to all influencers and their followers — a massive international competition.</span>
                                                </label>
                                            </div>
                                            
                                            <div class="form-check mb-3">
                                                <input class="form-check-input" type="radio" name="challenge_type" value="private_challenge" id="private_challenge">
                                                <label class="form-check-label" for="private_challenge">
                                                    <strong>Private Challenge (1–48 hours)</strong><br>
                                                    <span class="text-muted">A challenge created by you, for your community, on your schedule, with an option to limit participation to your own followers — and stream the action live.</span>
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
                                            <button type="submit" class="btn btn-lg px-5" style="background: #d9222a !important; border: none !important; color: white;">Send Verification Email</button>
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
    
    </div><!-- /.content-wrapper -->
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
                    checkAndShowButton();
                });
            }
        });
    }
    
    function checkAndShowButton() {
        const showGeniusBtn = document.getElementById('show-genius-btn');
        let hasValue = false;
        
        for (const method in inputFieldsData) {
            if (inputFieldsData[method] && inputFieldsData[method].trim() !== '') {
                hasValue = true;
                break;
            }
        }
        
        if (hasValue) {
            showGeniusBtn.style.display = 'inline-block';
        } else {
            showGeniusBtn.style.display = 'none';
        }
    }
    
    // Show genius section when button is clicked
    const showGeniusBtn = document.getElementById('show-genius-btn');
    if (showGeniusBtn) {
        showGeniusBtn.addEventListener('click', function() {
            document.getElementById('challenge-section').style.display = 'block';
            this.style.display = 'none';
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
</script><script src="<?php echo get_template_directory_uri(); ?>/new-design/assets/js/plugins.js"></script>
<script src="<?php echo get_template_directory_uri(); ?>/new-design/assets/js/theme.js"></script><?php
get_footer();
