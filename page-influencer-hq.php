<?php
/**
 * Template Name: Influencer HQ
 * Description: A custom template for displaying the influencer HQ.
 * This template is used to render the influencer HQ content and layout.
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package Avantage_Baccarat
 */

// Initialize errors variable
$errors = new WP_Error();
$success_message = '';

// Handle Registration
if (isset($_POST['action']) && $_POST['action'] === 'influencer_register') {
    
    // Verify nonce
    if (!isset($_POST['register_nonce']) || !wp_verify_nonce($_POST['register_nonce'], 'influencer_register')) {
        $errors->add('nonce_error', 'Security verification failed. Please try again.');
    } else {
        // Get form data
        $first_name = sanitize_text_field($_POST['first_name']);
        $last_name = sanitize_text_field($_POST['last_name']);
        $email = sanitize_email($_POST['email']);
        $country = sanitize_text_field($_POST['country']);
        $password = $_POST['password'];
        
        // Validation
        if (empty($first_name)) {
            $errors->add('first_name', 'First name is required');
        }
        
        if (empty($last_name)) {
            $errors->add('last_name', 'Last name is required');
        }
        
        if (empty($email) || !is_email($email)) {
            $errors->add('email', 'Valid email address is required');
        }
        
        if (email_exists($email)) {
            $errors->add('email_exists', 'This email is already registered');
        }
        
        if (empty($country)) {
            $errors->add('country', 'Country is required');
        }
        
        if (empty($password) || strlen($password) < 6) {
            $errors->add('password', 'Password must be at least 6 characters');
        }
        
        // If no errors, create user
        if (!$errors->has_errors()) {
            // Create username from email
            $username = sanitize_user(current(explode('@', $email)));
            
            // Make username unique if it exists
            $original_username = $username;
            $counter = 1;
            while (username_exists($username)) {
                $username = $original_username . $counter;
                $counter++;
            }
            
            // Create the user
            $user_id = wp_create_user($username, $password, $email);
            
            if (is_wp_error($user_id)) {
                $errors->add('registration_failed', $user_id->get_error_message());
            } else {
                // Update user meta
                update_user_meta($user_id, 'first_name', $first_name);
                update_user_meta($user_id, 'last_name', $last_name);
                update_user_meta($user_id, 'billing_country', $country);
                
                // Set user role to influencer
                $user = new WP_User($user_id);
                $user->set_role('influencer');
                
                // Log the user in
                wp_set_current_user($user_id);
                wp_set_auth_cookie($user_id);
                
                // Success - redirect
                wp_redirect(get_permalink() . '#login');
                exit;
            }
        }
    }
}

// Handle Login
if (isset($_POST['action']) && $_POST['action'] === 'influencer_login') {
    
    // Verify nonce
    if (!isset($_POST['login_nonce']) || !wp_verify_nonce($_POST['login_nonce'], 'influencer_login')) {
        $errors->add('nonce_error', 'Security verification failed. Please try again.');
    } else {
        // Get form data
        $email = sanitize_email($_POST['email']);
        $password = $_POST['password'];
        
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
                    'remember'      => true
                );
                
                $user = wp_signon($creds, false);
                
                if (is_wp_error($user)) {
                    $errors->add('incorrect_password', 'Incorrect password');
                } else {
                    // Success - redirect
                    wp_redirect(get_permalink() . '#login');
                    exit;
                }
            }
        }
    }
}

get_header();
?>

    <main id="primary" class="site-main">
        
        <section class="hero-section ">
            <div class="container">
                <div class="row">
                    <div class="col-12 hero-content">
                        <!-- Hero Tabs Navigation -->
                        <nav class="hero-tabs-nav mb-4">
                            <!-- Desktop Navigation (hidden on mobile) -->
                            <div class="nav nav-tabs hero-nav-tabs justify-content-center d-none d-md-flex" id="hero-nav-tab" role="tablist">
                                <button class="nav-link hero-tab-link active" id="tab1-tab" data-bs-toggle="tab" data-bs-target="#tab1" data-hash="welcome" type="button" role="tab" aria-controls="tab1" aria-selected="true">WELCOME</button>
                                <button class="nav-link hero-tab-link" id="tab2-tab" data-bs-toggle="tab" data-bs-target="#tab2" data-hash="monetization" type="button" role="tab" aria-controls="tab2" aria-selected="false">MONETIZATION & EQUITY</button>
                                <button class="nav-link hero-tab-link" id="tab3-tab" data-bs-toggle="tab" data-bs-target="#tab3" data-hash="mindset" type="button" role="tab" aria-controls="tab3" aria-selected="false">THE POWER OF COMPETITION</button>
                                <button class="nav-link hero-tab-link" id="tab4-tab" data-bs-toggle="tab" data-bs-target="#tab4" data-hash="championship" type="button" role="tab" aria-controls="tab4" aria-selected="false">CELEBRITY FOLLOWERS LEAGUES</button>
                                <button class="nav-link hero-tab-link" id="tab5-tab" data-bs-toggle="tab" data-bs-target="#tab5" data-hash="celebrity" type="button" role="tab" aria-controls="tab5" aria-selected="false">YOUR FIRST STEP</button>
                                <button class="nav-link hero-tab-link" id="tab6-tab" data-bs-toggle="tab" data-bs-target="#tab6" data-hash="login" type="button" role="tab" aria-controls="tab6" aria-selected="false"><?php echo is_user_logged_in() ? 'MY PROFILE' : 'LOGIN/REGISTER'; ?></button>
                            </div>
                            
                            <!-- Mobile Carousel Navigation (visible only on mobile) -->
                            <div class="d-block d-md-none">
                                <div id="tabsCarousel" class="carousel slide" data-bs-ride="false">
                                    <!-- Carousel Wrapper -->
                                    <div class="carousel-inner">
                                        <!-- Slide 1: Welcome -->
                                        <div class="carousel-item active">
                                            <div class="nav nav-tabs hero-nav-tabs-mobile justify-content-center" role="tablist">
                                                <button class="nav-link hero-tab-link-mobile active" id="tab1-tab-mobile" data-hash="welcome" type="button" role="tab" aria-controls="tab1" aria-selected="true">WELCOME</button>
                                            </div>
                                        </div>
                                        
                                        <!-- Slide 2: Monetization -->
                                        <div class="carousel-item">
                                            <div class="nav nav-tabs hero-nav-tabs-mobile justify-content-center" role="tablist">
                                                <button class="nav-link hero-tab-link-mobile" id="tab2-tab-mobile" data-hash="monetization" type="button" role="tab" aria-controls="tab2" aria-selected="false">MONETIZATION & EQUITY</button>
                                            </div>
                                        </div>
                                        
                                        <!-- Slide 3: Mindset -->
                                        <div class="carousel-item">
                                            <div class="nav nav-tabs hero-nav-tabs-mobile justify-content-center" role="tablist">
                                                <button class="nav-link hero-tab-link-mobile" id="tab3-tab-mobile" data-hash="mindset" type="button" role="tab" aria-controls="tab3" aria-selected="false">THE POWER OF COMPETITION</button>
                                            </div>
                                        </div>
                                        
                                        <!-- Slide 4: Celebrity Followers Leagues -->
                                        <div class="carousel-item">
                                            <div class="nav nav-tabs hero-nav-tabs-mobile justify-content-center" role="tablist">
                                                <button class="nav-link hero-tab-link-mobile" id="tab4-tab-mobile" data-hash="championship" type="button" role="tab" aria-controls="tab4" aria-selected="false">CELEBRITY FOLLOWERS LEAGUES</button>
                                            </div>
                                        </div>
                                        
                                        <!-- Slide 5: Your First Step -->
                                        <div class="carousel-item">
                                            <div class="nav nav-tabs hero-nav-tabs-mobile justify-content-center" role="tablist">
                                                <button class="nav-link hero-tab-link-mobile" id="tab5-tab-mobile" data-hash="celebrity" type="button" role="tab" aria-controls="tab5" aria-selected="false">YOUR FIRST STEP</button>
                                            </div>
                                        </div>
                                        
                                        <!-- Slide 6: Final Close -->
                                        <div class="carousel-item">
                                            <div class="nav nav-tabs hero-nav-tabs-mobile justify-content-center" role="tablist">
                                                <button class="nav-link hero-tab-link-mobile" id="tab6-tab-mobile" data-hash="login" type="button" role="tab" aria-controls="tab6" aria-selected="false"><?php echo is_user_logged_in() ? 'MY PROFILE' : 'LOGIN/REGISTER'; ?></button>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Carousel Controls -->
                                    <button class="carousel-control-prev" type="button" data-bs-target="#tabsCarousel" data-bs-slide="prev">
                                        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                        <span class="visually-hidden">Previous</span>
                                    </button>
                                    <button class="carousel-control-next" type="button" data-bs-target="#tabsCarousel" data-bs-slide="next">
                                        <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                        <span class="visually-hidden">Next</span>
                                    </button>
                                </div>
                            </div>
                        </nav>

                        <!-- Hero Tabs Content -->
                        <div class="tab-content hero-tab-content" id="hero-nav-tabContent">
                            <!-- TAB 1: WELCOME -->
                            <div class="tab-pane fade show active" id="tab1" role="tabpanel" aria-labelledby="tab1-tab">
                                <div class="hero-tab-content-wrapper text-center">
                                    <!-- Tab Image -->
                                    <div class="mb-4" style="max-width:800px; margin:0 auto;">
                                        <img src="<?php echo get_template_directory_uri(); ?>/images/tabs/welcome.jpg" alt="Welcome" width="700" height="auto" style="display:block;margin:0 auto;" class="img-fluid rounded shadow" />
                                    </div>
                                    
                                    <!-- Main Title -->
                                    <div class="text-center mb-4">
                                        <h1 class="text-28pt fw-bold text-yellow mb-3">WELCOME</h1>
                                    </div>
                                    <h3 class="text-22pt fw-bold text-yellow mb-4 text-center">People don’t buy what you do.<br>They buy what you believe.</h3>
                                    <!-- Body Copy -->
                                    <div class="text-start" style="max-width: 800px; margin: 40px auto; line-height: 1.5;">
                                        <p class="fs-5 text-light-gray mb-4">We believe Influencers deserve more than short-term payouts.</p>
                                        
                                        <p class="fs-5 text-light-gray mb-4">We believe you deserve rewards that grow as your influence grows.</p>
                                        
                                        <p class="fs-5 text-light-gray mb-4">We believe in equity — ownership in what you help build that compounds with every season.</p>
                                        
                                        
                                    </div>

                                    <h3 class="text-22pt fw-bold text-yellow mb-5 text-center">These beliefs will be the foundation of the partnership between you and Avantage — should you accept our  offer.</h3>

                                    <p class="fs-5 text-light-gray mb-0">The Avantage World Championship is modeled after the World Series of Poker, rooted in the 400-year prestige of Baccarat — and soon expanding  to include many more of the world’s most popular games.</p>
                                    
                                    <!-- Next Tab Button -->
                                    <div class="text-center mt-5">
                                        <button class="btn btn-warning btn-lg px-5 py-3" onclick="activateTabByHash('monetization')" style="background-color: #ffc107; border-color: #ffc107; color: #000; font-weight: bold;">
                                            Next Tab: MONETIZATION
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- TAB 2: MONETIZATION -->
                            <div class="tab-pane fade" id="tab2" role="tabpanel" aria-labelledby="tab2-tab">
                                <div class="hero-tab-content-wrapper row">
                                    <!-- Tab Image -->
                                    <div class="mb-4 text-center" style="max-width:800px; margin:0 auto;">
                                        <img src="<?php echo get_template_directory_uri(); ?>/images/tabs/monetization.jpg" alt="Monetization" width="700" height="auto" style="display:block;margin:0 auto;" class="img-fluid rounded shadow" />
                                    </div>
                                    
                                    <!-- Main Title -->
                                    <div class="text-center mb-5">
                                        <h1 class="text-28pt fw-bold text-yellow mb-4">MONETIZATION & EQUITY</h1>
                                    </div>

                                    <!-- Body Copy -->
                                    <div class="text-start" style="max-width: 800px; margin: 0 auto; line-height: 1.5;">
                                        <p class="fs-5 text-light-gray mb-4">Avantage pays Influencers like early partners in a high-growth company:</p>
                                        
                                        <ul class="fs-5 text-light-gray mb-4">
                                            <li class="mb-2">Level 1 — 1.5% of volume from the people you directly refer.</li>
                                            <li class="mb-2">Level 2 — 1% from the people they bring in.</li>
                                            <li class="mb-2">Level 3 — 0.5% from the next level beyond.</li>
                                        </ul>
                                        
                                        <p class="fs-5 text-light-gray mb-5">More importantly, every layer of your influence pays you — for life.</p>
                                        
                                        <h3 class="text-22pt fw-bold text-yellow mb-3">The $1 Lesson</h3>
                                        <p class="fs-5 text-light-gray mb-3">Cash is limited. One dollar in cash is just a dollar.</p>
                                        <p class="fs-5 text-light-gray mb-5">Equity has no ceiling. That same dollar in equity can multiply 10x, 20x, even 40x as a company grows.</p>
                                        
                                        <h3 class="text-22pt fw-bold text-yellow mb-3">The $5 Billion Lesson</h3>
                                        <p class="fs-5 text-light-gray mb-3">In HBO's Winning Time, Magic Johnson turned down Nike equity for $100,000 cash from Converse.</p>
                                        <p class="fs-5 text-light-gray mb-3">Today, that stock would be worth more than $5 billion.</p>
                                        <p class="fs-5 text-light-gray mb-5">Magic Johnson didn't just pass on a deal. He passed on ownership.</p>
                                        
                                        <p class="fs-5 text-light-gray mb-0"><strong class="text-yellow">Cash pays bills. Equity builds wealth. Belief builds movements.</strong></p>
                                    </div>
                                    
                                    <!-- Next Tab Button -->
                                    <div class="text-center mt-5">
                                        <button class="btn btn-warning btn-lg px-5 py-3" onclick="activateTabByHash('mindset')" style="background-color: #ffc107; border-color: #ffc107; color: #000; font-weight: bold;">
                                            Next Tab: THE POWER OF COMPETITION
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- TAB 3: MINDSET -->
                            <div class="tab-pane fade" id="tab3" role="tabpanel" aria-labelledby="tab3-tab">
                                <div class="hero-tab-content-wrapper row">
                                    <!-- Tab Image -->
                                    <div class="mb-4 text-center" style="max-width:800px; margin:0 auto;">
                                        <img src="<?php echo get_template_directory_uri(); ?>/images/tabs/power-comp.jpg" alt="Mindset" width="700" height="auto" style="display:block;margin:0 auto;" class="img-fluid rounded shadow" />
                                    </div>
                                    
                                    <!-- Main Title -->
                                    <div class="text-center mb-5">
                                        <h1 class="text-28pt fw-bold text-yellow mb-4">THE POWER OF COMPETITION</h1>
                                    </div>
                                    
                                    <!-- Body Copy -->
                                    <div class="text-start" style="max-width: 800px; margin: 0 auto; line-height: 1.5;">
                                        <p class="fs-5 text-light-gray mb-4">We believe in the importance of finding Influencers who believe in our mission.</p>
                                        
                                        <p class="fs-5 text-light-gray mb-4">From the beginning of civilization, competition has fueled progress and captured imagination.</p>
                                        
                                        <ul class="fs-5 text-light-gray mb-4">
                                            <li class="mb-2">In ancient times, Roman gladiators filled arenas with energy and spectacle.</li>
                                            <li class="mb-2">In the modern era, the Olympics unite nations, the World Cup crowns global champions, and the Super Bowl creates legends.</li>
                                            <li class="mb-2">Music, film, and entertainment thrive on competition — from Grammys to Oscars to global charts.</li>
                                            <li class="mb-2">One of the most recent additions: fantasy sports, where millions of fans draft players and compete head-to-head.</li>
                                        </ul>
                                        
                                        <h3 class="text-22pt fw-bold text-yellow mb-3">Modeled After the World Series of Poker</h3>
                                        <p class="fs-5 text-light-gray mb-3">The World Series of Poker (WSOP) is open to anyone in the world. Players can start small and literally win their way into the finals. In 2024, WSOP events paid out over $250 million, with more than $100 million going to the top 20% of the finals.</p>
                                        <p class="fs-5 text-light-gray mb-5">This combination of accessibility and prestige is exactly what inspired the Avantage Baccarat World Championship.</p>
                                        
                                        <h3 class="text-22pt fw-bold text-yellow mb-3">Why Baccarat First</h3>
                                        <p class="fs-5 text-light-gray mb-3">At its core is Baccarat:</p>
                                        <ul class="fs-5 text-light-gray mb-4">
                                            <li class="mb-2">Celebrated for more than 400 years as the game of kings.</li>
                                            <li class="mb-2">Immortalized as James Bond's first choice.</li>
                                            <li class="mb-2">Today, the most popular game in Asia and rapidly expanding worldwide.</li>
                                        </ul>
                                        
                                        <p class="fs-5 text-light-gray mb-0">Baccarat is just the beginning. Avantage will expand to all of the world's most popular games — building a movement that grows stronger with every season.</p>
                                    </div>
                                    
                                    <!-- Next Tab Button -->
                                    <div class="text-center mt-5">
                                        <button class="btn btn-warning btn-lg px-5 py-3" onclick="activateTabByHash('championship')" style="background-color: #ffc107; border-color: #ffc107; color: #000; font-weight: bold;">
                                            Next Tab: CELEBRITY FOLLOWERS LEAGUES
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- TAB 4: AVANTAGE BACCARAT WORLD CHAMPIONSHIP -->
                            <div class="tab-pane fade" id="tab4" role="tabpanel" aria-labelledby="tab4-tab">
                                <div class="hero-tab-content-wrapper row">
                                    
                                    <!-- Main Title -->
                                    <div class="text-center mb-5">
                                        <h1 class="text-28pt fw-bold text-yellow mb-4">CELEBRITY FOLLOWERS LEAGUES</h1>
                                    </div>
                                    
                                    <!-- Body Copy -->
                                    <div class="text-start" style="max-width: 800px; margin: 0 auto; line-height: 1.5;">
                                        <p class="fs-5 text-light-gray mb-4">We believe that Influencers who associate with the Avantage Celebrity Followers Leagues will see more than equity in the association.</p>
                                        
                                        <p class="fs-5 text-light-gray mb-3">Championing competitions between the followers of the world's top celebrities will:</p>
                                        
                                        <ul class="fs-5 text-light-gray mb-5">
                                            <li class="mb-2">Elevate your brand by aligning with global celebrity energy.</li>
                                            <li class="mb-2">Strengthen bonds with audiences who already overlap with these massive fan bases.</li>
                                            <li class="mb-2">Multiply your influence by connecting personal followers to a movement measured in billions.</li>
                                        </ul>
                                        
                                        <h3 class="text-22pt fw-bold text-yellow mb-3">The Four Celebrity Followers Leagues</h3>
                                        <ol class="fs-5 text-light-gray mb-5">
                                            <li class="mb-2">Sports Icons — followers of the world's most admired athletes.</li>
                                            <li class="mb-2">Music Stars — followers of chart-topping global artists.</li>
                                            <li class="mb-2">Movie Legends — followers of blockbuster actors and actresses.</li>
                                            <li class="mb-2">International League — Olympic-style national and regional teams. Each season (calendar quarter), the Overall Team Winner is crowned.</li>
                                        </ol>
                                        
                                        <h3 class="text-22pt fw-bold text-yellow mb-3">Unlimited Influencer Leagues & Challenge Formats</h3>
                                        <p class="fs-5 text-light-gray mb-3">In addition to the four global leagues, Influencers can:</p>
                                        
                                        <ul class="fs-5 text-light-gray mb-4">
                                            <li class="mb-2">Host Open Challenge Competitions for all their followers.</li>
                                            <li class="mb-2">Organize Private Competitions between two or more Influencers.</li>
                                            <li class="mb-2">Join or form Influencer Leagues consisting of 20 Influencers from around the world.</li>
                                        </ul>
                                        
                                        <p class="fs-5 text-light-gray mb-5">There is no limit to the number of Influencer Leagues. Every Influencer can create a League of their own — with their followers, their leaderboard, their community.</p>
                                        
                                        <p class="fs-5 text-yellow mb-0"><strong>Your influence. Their belief. One global stage.</strong></p>
                                    </div>
                                    
                                    <!-- Next Tab Button -->
                                    <div class="text-center mt-5">
                                        <button class="btn btn-warning btn-lg px-5 py-3" onclick="activateTabByHash('celebrity')" style="background-color: #ffc107; border-color: #ffc107; color: #000; font-weight: bold;">
                                            Next Tab: YOUR FIRST STEP
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- TAB 5: CELEBRITY FOLLOWERS LEAGUES & INTERNATIONAL LEAGUE -->
                            <div class="tab-pane fade" id="tab5" role="tabpanel" aria-labelledby="tab5-tab">
                                <div class="hero-tab-content-wrapper row">
                                    
                                    <!-- Main Title -->
                                    <div class="text-center mb-5">
                                        <h1 class="text-28pt fw-bold text-yellow mb-4">YOUR FIRST STEP</h1>
                                    </div>
                                    
                                    <!-- Body Copy -->
                                    <div class="text-start" style="max-width: 800px; margin: 0 auto; line-height: 1.5;">
                                        <p class="fs-5 text-light-gray mb-4">Secure your equity position today.</p>
                                        
                                        <p class="fs-5 text-light-gray mb-5">Lead your audience into the world's first global Baccarat World Championship.</p>
                                        
                                        <p class="fs-5 text-light-gray mb-3">By confirming your information today, you will:</p>
                                        
                                        <ul class="fs-5 text-light-gray mb-5">
                                            <li class="mb-2">Secure your position as an equity partner.</li>
                                            <li class="mb-2">Lead your followers into the Championship.</li>
                                            <li class="mb-2">Be first in line for every expansion Avantage launches in the future.</li>
                                        </ul>
                                        
                                        <p class="fs-5 text-light-gray mb-0">This isn't just an invitation to promote. It's your chance to own a piece of the future of global competition.</p>
                                    </div>


                                    
                                    <!-- Next Tab Button -->
                                    <div class="text-center mt-5">
                                        <button class="btn btn-warning btn-lg px-5 py-3" onclick="activateTabByHash('login')" style="background-color: #ffc107; border-color: #ffc107; color: #000; font-weight: bold;">
                                            Next Tab: <?php echo is_user_logged_in() ? 'MY PROFILE' : 'LOGIN/REGISTER'; ?>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- TAB 6: HOW WE HELP RECRUIT FOLLOWERS FOR YOU -->
                            <div class="tab-pane fade" id="tab5" role="tabpanel" aria-labelledby="tab5-tab">
                                <div class="hero-tab-content-wrapper row">
                                    <!-- Tab Image -->
                                    <div class="mb-4 text-center" style="max-width:800px; margin:0 auto;">
                                        <img src="<?php echo get_template_directory_uri(); ?>/images/tabs/monetization.jpg" alt="Monetization" width="700" height="auto" style="display:block;margin:0 auto;" class="img-fluid rounded shadow" />
                                    </div>
                                    
                                    <!-- Main Title -->
                                    <div class="text-center mb-5">
                                        <h1 class="text-28pt fw-bold text-yellow mb-4">MONETIZATION & EQUITY</h1>
                                    </div>

                                    <!-- Accordion Sections -->
                                    <div class="accordion accordion-flush" id="monetizationAccordion" style="max-width: 800px; margin: 0 auto;">
                                        
                                        <!-- REAL MONETIZATION — WITH EQUITY Section -->
                                        <div class="accordion-item mb-3 expanded-accordion">
                                            <h2 class="accordion-header" id="realMonetizationHeading">
                                                <button class="accordion-button collapsed fw-bold text-18pt text-yellow" type="button" data-bs-toggle="collapse" data-bs-target="#realMonetizationCollapse" aria-expanded="false" aria-controls="realMonetizationCollapse">
                                                    REAL MONETIZATION — WITH EQUITY
                                                </button>
                                            </h2>
                                            <div id="realMonetizationCollapse" class="accordion-collapse collapse" aria-labelledby="realMonetizationHeading">
                                                <div class="accordion-body">
                                                    <p class="fs-5 text-light-gray mb-3">Avantage pays streamers based on performance — not promises.</p>
                                                    <p class="fs-5 text-light-gray mb-4">Rewards grow as your influence grows.</p>
                                                    
                                                    <p class="fs-5 text-light-gray mb-3">How it works:</p>
                                                    
                                                    <ul class="fs-5 text-light-gray mb-4">
                                                        <li class="mb-2">Earn 1% of play volume of viewers who follow your stream</li>
                                                        <li class="mb-2">Earn 1% of play volume of players you personally refer</li>
                                                        <li class="mb-2">Unlock 0.5% from players your referrals refer</li>
                                                    </ul>
                                                    
                                                    <p class="fs-5 text-light-gray mb-0">Earnings repeat as your network expands.</p>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- BECAUSE YOU'RE A PARTNER — NOT AN EMPLOYEE Section -->
                                        <div class="accordion-item mb-3 expanded-accordion">
                                            <h2 class="accordion-header" id="partnerNotEmployeeHeading">
                                                <button class="accordion-button collapsed fw-bold text-18pt text-yellow" type="button" data-bs-toggle="collapse" data-bs-target="#partnerNotEmployeeCollapse" aria-expanded="false" aria-controls="partnerNotEmployeeCollapse">
                                                    BECAUSE YOU'RE A PARTNER — NOT AN EMPLOYEE
                                                </button>
                                            </h2>
                                            <div id="partnerNotEmployeeCollapse" class="accordion-collapse collapse" aria-labelledby="partnerNotEmployeeHeading">
                                                <div class="accordion-body">
                                                    <p class="fs-5 text-light-gray mb-3">Captains don't work for tips.</p>
                                                    <p class="fs-5 text-light-gray mb-3">They don't settle for scraps.</p>
                                                    <p class="fs-5 text-light-gray mb-4">They build equity.</p>
                                                    
                                                    <p class="fs-5 text-light-gray mb-3">We're not offering tips.</p>
                                                    <p class="fs-5 text-light-gray mb-3">We're not offering affiliate scraps.</p>
                                                    <p class="fs-5 text-light-gray mb-4">We're offering something real.</p>
                                                    
                                                    <p class="fs-5 text-light-gray mb-0">You also earn stock warrants based on your network's play volume — just like an early partner in a high-growth company.</p>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- THE $5 BILLION MISTAKE Section -->
                                        <div class="accordion-item mb-3 expanded-accordion">
                                            <h2 class="accordion-header" id="billionMistakeHeading">
                                                <button class="accordion-button collapsed fw-bold text-18pt text-yellow" type="button" data-bs-toggle="collapse" data-bs-target="#billionMistakeCollapse" aria-expanded="false" aria-controls="billionMistakeCollapse">
                                                    THE $5 BILLION MISTAKE
                                                </button>
                                            </h2>
                                            <div id="billionMistakeCollapse" class="accordion-collapse collapse" aria-labelledby="billionMistakeHeading">
                                                <div class="accordion-body">
                                                    <p class="fs-5 text-light-gray mb-3">In HBO’s <em>Winning Time</em>, Magic Johnson turned down Nike equity for $100,000 cash from Converse.</p>
                                                    <p class="fs-5 text-light-gray mb-3">That stock today? Worth <strong class="text-yellow">$5 billion</strong>.</p>
                                                    <p class="fs-5 text-light-gray mb-4">He didn’t just pass on a deal — he passed on ownership.</p>
                                                    <div class="text-center my-4">
                                                        <span class="fs-5 text-yellow fw-bold">XXXXDon’t make the same mistake.XXXX</span>
                                                    </div>
                                                    <p class="fs-5 text-yellow mb-0">“We believe the ones who’ll succeed believe in the upside — and  —  feel their time has arrived.”</p>
                                                </div>
                                            </div>
                                        </div>
                                        
                                    </div>
                                    
                                    <!-- Final Emphasis Section -->
                                    <div class="text-start mt-5 mb-4" style="max-width: 800px; margin: 0 auto; line-height: 1.5;">
                                        <p class="fs-5 text-light-gray mb-4">Avantage is investing millions to promote Captains who show potential — across Meta, X, Kick, Twitch, YouTube, TikTok.</p>
                                        
                                        <p class="fs-5 text-light-gray mb-3">As a Captain, <strong class="text-yellow">your voice is your brand</strong>.</p>
                                        <p class="fs-5 text-light-gray mb-3"><strong class="text-yellow">Your network is your equity</strong>.</p>
                                        <p class="fs-5 text-yellow mb-0"><strong>And your equity is your future</strong>.</p>
                                    </div>
                                </div>
                            </div>

            <!-- TAB 6: <?php echo is_user_logged_in() ? 'MY PROFILE' : 'LOGIN/REGISTER'; ?> -->
            <div class="tab-pane fade" id="tab6" role="tabpanel" aria-labelledby="tab6-tab">
                <div class="hero-tab-content-wrapper row">
                    <?php if (is_user_logged_in()) : ?>
                        <!-- User is logged in - Show Profile Content with Sub-tabs -->
                        <div class="text-center mb-5">
                            <h1 class="text-28pt fw-bold text-yellow mb-4">MY PROFILE</h1>
                        </div>
                        
                        <!-- Profile Sub-tabs Navigation -->
                        <div class="profile-sub-tabs-nav mb-4">
                            <ul class="nav nav-tabs justify-content-center" id="profileSubTabs" role="tablist" style="border-bottom: 2px solid rgba(255, 149, 0, 0.3);">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active profile-sub-tab" id="profile-info-tab" data-bs-toggle="tab" data-bs-target="#profile-info" type="button" role="tab" aria-controls="profile-info" aria-selected="true">
                                        Profile Information
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link profile-sub-tab" id="genious-referrals-tab" data-bs-toggle="tab" data-bs-target="#genious-referrals" type="button" role="tab" aria-controls="genious-referrals" aria-selected="false">
                                        Genious Referrals
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link profile-sub-tab" id="challenges-tab" data-bs-toggle="tab" data-bs-target="#challenges" type="button" role="tab" aria-controls="challenges" aria-selected="false">
                                        Challenges
                                    </button>
                                </li>
                            </ul>
                        </div>
                        
                        <!-- Profile Sub-tabs Content -->
                        <div class="tab-content" id="profileSubTabsContent">
                            <!-- Profile Information Sub-tab -->
                            <div class="tab-pane fade show active" id="profile-info" role="tabpanel" aria-labelledby="profile-info-tab">
                                <?php get_template_part('template-parts/influencer-profile'); ?>
                                <script>
                                function activateProfileInfoTabPane() {
                                    setTimeout(function() {
                                        var profileInfoPane = document.getElementById('profile-info');
                                        if (profileInfoPane) {
                                            profileInfoPane.classList.add('show', 'active');
                                        }
                                    }, 100);
                                }

                                document.addEventListener('DOMContentLoaded', function() {
                                    if (window.location.hash === '#login') {
                                        activateProfileInfoTabPane();
                                    }
                                    var tab6Btn = document.getElementById('tab6-tab');
                                    if (tab6Btn) {
                                        tab6Btn.addEventListener('click', function() {
                                            activateProfileInfoTabPane();
                                        });
                                    }
                                });
                                </script>
                            </div>
                            
                            <!-- Genious Referrals Sub-tab -->
                            <div class="tab-pane fade" id="genious-referrals" role="tabpanel" aria-labelledby="genious-referrals-tab">
                                <?php get_template_part('template-parts/genious-referrals'); ?>
                            </div>
                            
                            <!-- Challenges Sub-tab -->
                            <div class="tab-pane fade" id="challenges" role="tabpanel" aria-labelledby="challenges-tab">
                                <?php get_template_part('template-parts/challenges'); ?>
                            </div>
                        </div>

                            
                        
                    <?php else : ?>
                        <!-- User is not logged in - Show Login/Register Forms -->
                        <div class="text-center mb-5">
                            <h1 class="text-28pt fw-bold text-yellow mb-4">LOGIN / REGISTER</h1>
                            <p class="fs-5 text-light-gray mb-4">Join the Avantage community and secure your equity position</p>
                        </div>
                        
                        <!-- Display Messages -->
                        <?php 
                        // Display error messages
                        if ($errors->has_errors()) {
                            $error_messages = $errors->get_error_messages();
                            echo '<div class="alert alert-danger text-center mb-4" style="max-width: 600px; margin: 0 auto; background: rgba(215, 24, 42, 0.15) !important; border: 1px solid rgba(215, 24, 42, 0.4) !important; color: var(--light-gray) !important;">';
                            foreach ($error_messages as $error) {
                                echo esc_html($error) . '<br>';
                            }
                            echo '</div>';
                        }
                        
                        // Display success message
                        if (!empty($success_message)) {
                            echo '<div class="alert alert-success text-center mb-4" style="max-width: 600px; margin: 0 auto;">';
                            echo esc_html($success_message);
                            echo '</div>';
                        }
                        ?>
                        
                        <!-- Form Toggle Tabs -->
                        <div class="text-center mb-4" style="max-width: 600px; margin: 0 auto;">
                            <div class="btn-group" role="group">
                                <button type="button" class="btn btn-outline-warning active" id="show-register-btn" onclick="showRegisterForm()">Register</button>
                                <button type="button" class="btn btn-outline-warning" id="show-login-btn" onclick="showLoginForm()">Login</button>
                            </div>
                        </div>
                        
                        <!-- Register Form -->
                        <div class="text-center" id="register-form" style="max-width: 600px; margin: 0 auto;">
                            <form class="text-start" method="POST" action="">
                                <?php wp_nonce_field('influencer_register', 'register_nonce'); ?>
                                <input type="hidden" name="action" value="influencer_register">
                                
                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="reg_first_name" class="form-label fs-5 text-light-gray mb-3">First Name</label>
                                            <input type="text" class="form-control form-control-lg" id="reg_first_name" name="first_name" required 
                                                   style="background: rgba(33, 37, 41, 0.8); border: 2px solid #ffc107; color: #fff; text-align: center; padding: 15px;"
                                                   placeholder="Your First Name">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="reg_last_name" class="form-label fs-5 text-light-gray mb-3">Last Name</label>
                                            <input type="text" class="form-control form-control-lg" id="reg_last_name" name="last_name" required 
                                                   style="background: rgba(33, 37, 41, 0.8); border: 2px solid #ffc107; color: #fff; text-align: center; padding: 15px;"
                                                   placeholder="Your Last Name">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="reg_email" class="form-label fs-5 text-light-gray mb-3">Email Address</label>
                                            <input type="email" class="form-control form-control-lg" id="reg_email" name="email" required 
                                                   style="background: rgba(33, 37, 41, 0.8); border: 2px solid #ffc107; color: #fff; text-align: center; padding: 15px;"
                                                   placeholder="your.email@example.com">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="reg_country" class="form-label fs-5 text-light-gray mb-3">Country</label>
                                            <select class="form-control form-control-lg" id="reg_country" name="country" required 
                                                    style="background: rgba(33, 37, 41, 0.8); border: 2px solid #ffc107; color: #fff; text-align: center; padding: 15px;">
                                                <option value="">Select Your Country</option>
                                                <option value="US">United States</option>
                                                <option value="CA">Canada</option>
                                                <option value="GB">United Kingdom</option>
                                                <option value="AU">Australia</option>
                                                <option value="DE">Germany</option>
                                                <option value="FR">France</option>
                                                <option value="ES">Spain</option>
                                                <option value="IT">Italy</option>
                                                <option value="NL">Netherlands</option>
                                                <option value="BR">Brazil</option>
                                                <option value="MX">Mexico</option>
                                                <option value="JP">Japan</option>
                                                <option value="KR">South Korea</option>
                                                <option value="CN">China</option>
                                                <option value="IN">India</option>
                                                <option value="SG">Singapore</option>
                                                <option value="other">Other</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-4">
                                    <div class="form-group">
                                        <label for="reg_password" class="form-label fs-5 text-light-gray mb-3">Password</label>
                                        <input type="password" class="form-control form-control-lg" id="reg_password" name="password" required 
                                               style="background: rgba(33, 37, 41, 0.8); border: 2px solid #ffc107; color: #fff; text-align: center; padding: 15px;"
                                               placeholder="Create a secure password">
                                    </div>
                                </div>
                                
                                <div class="text-center">
                                    <button type="submit" class="btn btn-warning btn-lg px-5 py-3" style="background-color: #ffc107; border-color: #ffc107; color: #000; font-weight: bold;">
                                        Join Avantage
                                    </button>
                                </div>
                            </form>
                        </div>
                        
                        <!-- Login Form -->
                        <div class="text-center" id="login-form" style="max-width: 600px; margin: 0 auto; display: none;">
                            <form class="text-start" method="POST" action="">
                                <?php wp_nonce_field('influencer_login', 'login_nonce'); ?>
                                <input type="hidden" name="action" value="influencer_login">
                                
                                <div class="mb-4">
                                    <div class="form-group">
                                        <label for="login_email" class="form-label fs-5 text-light-gray mb-3">Email Address</label>
                                        <input type="email" class="form-control form-control-lg" id="login_email" name="email" required 
                                               style="background: rgba(33, 37, 41, 0.8); border: 2px solid #ffc107; color: #fff; text-align: center; padding: 15px;"
                                               placeholder="your.email@example.com">
                                    </div>
                                </div>
                                
                                <div class="mb-4">
                                    <div class="form-group">
                                        <label for="login_password" class="form-label fs-5 text-light-gray mb-3">Password</label>
                                        <input type="password" class="form-control form-control-lg" id="login_password" name="password" required 
                                               style="background: rgba(33, 37, 41, 0.8); border: 2px solid #ffc107; color: #fff; text-align: center; padding: 15px;"
                                               placeholder="Your password">
                                    </div>
                                </div>
                                
                                <div class="text-center">
                                    <button type="submit" class="btn btn-warning btn-lg px-5 py-3" style="background-color: #ffc107; border-color: #ffc107; color: #000; font-weight: bold;">
                                        Login
                                    </button>
                                </div>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            </div>








                        </div>
                        
                        <!-- GO PLAY Button -->
                        <!-- <div class="text-center mt-5">
                            <a href="#" class="btn btn-go-play btn-lg px-5 py-3">
                                <span class="go-play-text">GO PLAY!</span>
                            </a>
        
                        </div> -->
                    </div>
                </div>
            </div>
        </section>
       
       
        <script>
            // Form switching functionality
            function showRegisterForm() {
                const registerForm = document.getElementById('register-form');
                const loginForm = document.getElementById('login-form');
                const registerBtn = document.getElementById('show-register-btn');
                const loginBtn = document.getElementById('show-login-btn');
                
                if (registerForm && loginForm && registerBtn && loginBtn) {
                    registerForm.style.display = 'block';
                    loginForm.style.display = 'none';
                    registerBtn.classList.add('active');
                    loginBtn.classList.remove('active');
                }
            }
            
            function showLoginForm() {
                const registerForm = document.getElementById('register-form');
                const loginForm = document.getElementById('login-form');
                const registerBtn = document.getElementById('show-register-btn');
                const loginBtn = document.getElementById('show-login-btn');
                
                if (registerForm && loginForm && registerBtn && loginBtn) {
                    registerForm.style.display = 'none';
                    loginForm.style.display = 'block';
                    registerBtn.classList.remove('active');
                    loginBtn.classList.add('active');
                }
            }

            // Global function to activate a tab by hash (needed for onclick handlers)
            function activateTabByHash(hash) {
                // Remove # from hash if present
                hash = hash.replace('#', '');
                
                const allTabs = document.querySelectorAll('.hero-tab-link, .hero-tab-link-mobile');
                const tabPanes = document.querySelectorAll('.tab-pane');
                const isMobile = window.innerWidth < 768;
                
                // Helper function to scroll to top
                function scrollToTop() {
                    // Scroll to top of the page
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                    
                    // Also scroll tab content container to top if it exists
                    const tabContentContainer = document.querySelector('.hero-tab-content');
                    if (tabContentContainer) {
                        tabContentContainer.scrollTop = 0;
                    }
                }
                
                // Find both desktop and mobile tabs with matching data-hash
                const targetTabs = document.querySelectorAll(`[data-hash="${hash}"]`);
                
                if (targetTabs.length > 0) {
                    // Handle mobile and desktop consistently with fade transitions
                    if (isMobile || window.innerWidth < 768) {
                        // For mobile: Use Bootstrap tab switching but ensure carousel syncs
                        const currentActivePane = document.querySelector('.tab-pane.show.active');
                        const targetPaneId = targetTabs[0].getAttribute('data-bs-target') || 
                                           targetTabs[0].getAttribute('aria-controls') ||
                                           `#tab${getTabNumberFromHash(hash)}`;
                        const targetPane = document.querySelector(targetPaneId);
                        
                        if (currentActivePane && targetPane && currentActivePane !== targetPane) {
                            // Start fade out of current pane
                            currentActivePane.classList.remove('show');
                            
                            // Update tab states
                            allTabs.forEach(tab => {
                                tab.classList.remove('active');
                                tab.setAttribute('aria-selected', 'false');
                            });
                            
                            targetTabs.forEach(tab => {
                                tab.classList.add('active');
                                tab.setAttribute('aria-selected', 'true');
                            });
                            
                            // Wait for fade out to complete, then fade in new content
                            setTimeout(() => {
                                // Remove active from old pane and add to new pane
                                currentActivePane.classList.remove('active');
                                targetPane.classList.add('active');
                                
                                // Small delay to ensure DOM is ready, then fade in
                                setTimeout(() => {
                                    targetPane.classList.add('show');
                                    
                                    // Scroll to top
                                    scrollToTop();
                                }, 50);
                            }, 150); // Bootstrap fade transition duration
                        } else if (!currentActivePane && targetPane) {
                            // First load case
                            allTabs.forEach(tab => {
                                tab.classList.remove('active');
                                tab.setAttribute('aria-selected', 'false');
                            });
                            
                            targetTabs.forEach(tab => {
                                tab.classList.add('active');
                                tab.setAttribute('aria-selected', 'true');
                            });
                            
                            targetPane.classList.add('active', 'show');
                            scrollToTop();
                        }
                        
                        // Navigate mobile carousel
                        autoNavigateCarousel(hash);
                    } else {
                        // For desktop: Let Bootstrap handle it normally
                        allTabs.forEach(tab => {
                            tab.classList.remove('active');
                            tab.setAttribute('aria-selected', 'false');
                        });
                        
                        targetTabs.forEach(tab => {
                            tab.classList.add('active');
                            tab.setAttribute('aria-selected', 'true');
                        });
                        
                        tabPanes.forEach(pane => {
                            pane.classList.remove('show', 'active');
                        });
                        
                        const targetPaneId = targetTabs[0].getAttribute('data-bs-target') || 
                                           targetTabs[0].getAttribute('aria-controls') ||
                                           `#tab${getTabNumberFromHash(hash)}`;
                        const targetPane = document.querySelector(targetPaneId);
                        if (targetPane) {
                            targetPane.classList.add('show', 'active');
                            // Scroll to top
                            scrollToTop();
                        }
                    }
                }
            }

            // Helper function to get tab number from hash
            function getTabNumberFromHash(hash) {
                const hashMap = {
                    'welcome': '1',
                    'monetization': '2', 
                    'mindset': '3',
                    'championship': '4',
                    'celebrity': '5',
                    'login': '6'
                };
                return hashMap[hash] || '1';
            }
            
            // Function to navigate carousel to the correct slide based on active tab
            function autoNavigateCarousel(hash) {
                const carousel = document.getElementById('tabsCarousel');
                if (!carousel) return;
                
                const bsCarousel = bootstrap.Carousel.getInstance(carousel) || new bootstrap.Carousel(carousel);
                
                // Determine which slide contains the active tab (one tab per slide)
                let slideIndex = 0;
                switch(hash) {
                    case 'welcome': slideIndex = 0; break;
                    case 'monetization': slideIndex = 1; break;
                    case 'mindset': slideIndex = 2; break;
                    case 'championship': slideIndex = 3; break;
                    case 'celebrity': slideIndex = 4; break;
                    case 'login': slideIndex = 5; break;
                }
                
                bsCarousel.to(slideIndex);
            }

            // Hash Navigation for Hero Tabs (Desktop and Mobile)
            document.addEventListener('DOMContentLoaded', function() {
                const allTabs = document.querySelectorAll('.hero-tab-link, .hero-tab-link-mobile');
                const tabPanes = document.querySelectorAll('.tab-pane');
                const mobileTabsCarousel = document.getElementById('tabsCarousel');
                
                // Prevent default Bootstrap tab behavior on mobile tabs and handle manually
                const mobileTabLinks = document.querySelectorAll('.hero-tab-link-mobile');
                mobileTabLinks.forEach(tab => {
                    tab.addEventListener('click', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        
                        const hash = this.getAttribute('data-hash');
                        if (hash) {
                            // Update URL hash and trigger our custom tab switching
                            const baseUrl = window.location.href.split('#')[0];
                            history.pushState(null, null, baseUrl + '#' + hash);
                            activateTabByHash(hash);
                        }
                    });
                });
                
                // Handle desktop tabs with Bootstrap's native functionality
                const desktopTabLinks = document.querySelectorAll('.hero-tab-link');
                desktopTabLinks.forEach(tab => {
                    tab.addEventListener('click', function(e) {
                        const hash = this.getAttribute('data-hash');
                        if (hash) {
                            // Update URL hash 
                            const baseUrl = window.location.href.split('#')[0];
                            history.pushState(null, null, baseUrl + '#' + hash);
                            // Let Bootstrap handle the tab switching for desktop
                            // Add scroll to top after a short delay to ensure tab is activated
                            setTimeout(() => {
                                const targetPaneId = this.getAttribute('data-bs-target') || 
                                                   this.getAttribute('aria-controls') ||
                                                   `#tab${getTabNumberFromHash(hash)}`;
                                const targetPane = document.querySelector(targetPaneId);
                                if (targetPane) {
                                    // Use the same scrollToTop function as Next Tab buttons
                                    window.scrollTo({ top: 0, behavior: 'smooth' });
                                    
                                    // Also scroll tab content container to top if it exists
                                    const tabContentContainer = document.querySelector('.hero-tab-content');
                                    if (tabContentContainer) {
                                        tabContentContainer.scrollTop = 0;
                                    }
                                }
                            }, 50);
                        }
                    });
                });
                
                // Listen for carousel slide events to sync tab activation on mobile
                if (mobileTabsCarousel) {
                    mobileTabsCarousel.addEventListener('slid.bs.carousel', function(e) {
                        const activeSlideIndex = e.to;
                        const slideHashes = ['welcome', 'monetization', 'mindset', 'championship', 'celebrity', 'login'];
                        
                        if (slideHashes[activeSlideIndex]) {
                            const hash = slideHashes[activeSlideIndex];
                            const baseUrl = window.location.href.split('#')[0];
                            history.pushState(null, null, baseUrl + '#' + hash);
                            activateTabByHash(hash);
                        }
                    });
                }
                
                // Check for hash on page load
                if (window.location.hash) {
                    activateTabByHash(window.location.hash);
                } else {
                    // Default to welcome tab
                    activateTabByHash('welcome');
                }
                
                // Listen for hash changes
                window.addEventListener('hashchange', function() {
                    if (window.location.hash) {
                        activateTabByHash(window.location.hash);
                    }
                });
                
                // Handle browser back/forward buttons
                window.addEventListener('popstate', function() {
                    if (window.location.hash) {
                        activateTabByHash(window.location.hash);
                    } else {
                        // If no hash, activate the first tab (Welcome)
                        activateTabByHash('welcome');
                    }
                });
                
                // Handle window resize to detect mobile/desktop changes
                window.addEventListener('resize', function() {
                    // Update isMobile flag but don't change current tab
                    // This helps with responsive behavior
                });
                
            });
        </script>
        
    </main><!-- #main -->
<style>
    /* Menu Button Styling */
    .accordion-item {
    border: var(--bs-accordion-border-width) solid #363636;
    }
    .form-select {
    --bs-form-select-bg-img: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23fff' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m2 5 6 6 6-6'/%3e%3c/svg%3e");
}
    optgroup {
    color: #ffffff;
    }
    .form-check .form-check-input {
    float: none;
    margin-left: 0.5em;
    margin-right: 0.5em;
}
    .btn-menu-item {
        background: rgba(33, 37, 41, 0.9);
        border: 2px solid rgba(255, 255, 255, 0.2);
        color: white!important;
        padding: 15px 20px;
        font-weight: 500;
        text-transform: none;
        letter-spacing: 0.5px;
        transition: all 0.3s ease;
        text-decoration: none;
        border-radius: 6px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
        min-height: 60px;
        display: flex;
        align-items: center;
        justify-content: center;
        text-align: center;
        line-height: 1.4;
    }
    
    .btn-menu-item:hover {
        background: rgba(255, 255, 255, 0.1);
        border-color: rgba(255, 255, 255, 0.4);
        color: white;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        text-decoration: none;
    }
    
    .btn-menu-item:focus {
        background: rgba(255, 255, 255, 0.1);
        border-color: rgba(255, 255, 255, 0.4);
        color: white;
        box-shadow: 0 0 0 2px rgba(255, 255, 255, 0.3);
        text-decoration: none;
    }
    
    .btn-menu-item:active {
        transform: translateY(0);
        box-shadow: 0 1px 4px rgba(0, 0, 0, 0.2);
    }
    
    /* Center remaining items in the last row */
    .row.justify-content-center .col-lg-3:nth-last-child(-n+3):nth-child(4n+2) {
        margin-left: auto;
        margin-right: auto;
    }
    
    /* Center single remaining item */
    .row.justify-content-center .col-lg-3:last-child:nth-child(4n+2),
    .row.justify-content-center .col-lg-3:last-child:nth-child(4n+3) {
        margin-left: auto;
        margin-right: auto;
    }

    /* Page Layout - Full Viewport Height */
    .page {
        margin: 0;
        min-height: 100vh;
        height: 100vh;
        display: flex;
        flex-direction: column;
    }

    /* Hero Section - Flex Container */
    .hero-section {
        position: relative;
        background: url('<?php echo get_template_directory_uri(); ?>/images/hero-bgnd.jpg') top center no-repeat;
        background-size: cover;
        background-attachment: fixed;
        overflow: hidden;
        flex: 1;
        display: flex;
        flex-direction: column;
        min-height: 0;
    }

    .hero-section::before {
        content: "";
        position: absolute;
        inset: 0;
        background: rgba(0,0,0,0.6); /* Black with 30% opacity */
        z-index: 1;
        pointer-events: none;
    }

    .hero-section > * {
        position: relative;
        z-index: 2;
    }

    /* Container and Content Layout */
    .hero-section .container {
        flex: 1;
        display: flex;
        flex-direction: column;
        min-height: 0;
    }

    .hero-section .row {
        flex: 1;
        display: flex;
        min-height: 0;
    }

    .hero-content {
        padding-top: 20px;
        padding-bottom: 20px;
        flex: 1;
        display: flex;
        flex-direction: column;
        min-height: 0;
    }
    

    body, h1, h2, h3, h4, h5, h6, p, a, ul, ol, li, .btn-69, .card, .card-title, .lead, .form-label, .form-control, .form-select, label, input, select, textarea, .navbar-brand, .nav-link, .carousel-item, .display-5, .fw-bold, .hero-content, .hero-image, .button-container, .card-body, .card-img-top, .text-center, .align-middle, .vh-100, .mb-3, .mb-4, .mb-5, .py-5, .container, .row, .col, .col-12, .col-md-6, .col-lg-7, .col-lg-8, .d-grid, .shadow-sm, .rounded, .bg-dark, .bg-light, .ratio, .form-check, .form-check-input, .form-check-label, .form-select, .list-unstyled, .fs-5, .fw-bold, .text-center, .justify-content-center, .align-items-center, .fade-in, .fade-in-on-scroll {
        font-family: 'Source Sans Pro', Arial, Helvetica, sans-serif !important;
    }
    
    /* Custom color variables */
    :root {
        --accent-red: rgb(215, 24, 42);
        --accent-yellow: rgb(255, 149, 0);
        --light-gray: rgb(224, 224, 224);
    }
    
    /* Color utility classes */
    .text-red {
        color: var(--accent-red) !important;
    }
    
    .text-yellow {
        color: var(--accent-yellow) !important;
    }
    
    .text-light-gray {
        color: var(--light-gray) !important;
    }
    
    .font-weight-bold {
        font-weight: bold !important;
    }
    
    /* Enhanced glassmorphism effect for hero-earning-method cards */
    .hero-earning-method {
        background: rgba(33, 37, 41, 0.25);
        border: 1px solid rgba(255, 149, 0, 0.2);
        backdrop-filter: blur(15px);
        -webkit-backdrop-filter: blur(15px);
        transition: all 0.3s ease;
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.2);
    }
    
    .hero-earning-method:hover {
        background: rgba(33, 37, 41, 0.35);
        border-color: rgba(255, 149, 0, 0.4);
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3);
    }
    
    /* Enhanced glassmorphism for warrant-bottom-line */
    .warrant-bottom-line {
        background: rgba(33, 37, 41, 0.25) !important;
        border: 1px solid rgba(224, 224, 224, 0.2) !important;
        backdrop-filter: blur(15px);
        -webkit-backdrop-filter: blur(15px);
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.2);
    }

    #competition {
        position: relative;
        background: linear-gradient(to bottom, rgb(0 0 0) 0%, rgba(0, 0, 0, 0.7) 25%, rgba(0, 0, 0, 0.7) 75%, rgb(0 0 0) 100%), url('<?php echo get_template_directory_uri(); ?>/images/carbon-bgnd.jpg') center center no-repeat;
    }

    .mb-3 {
        color: #fff;
        margin-bottom: 0.5rem !important;
    }

    ul, ol {
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

    .hero-image {
        max-width: 280px;
        width: 100%;
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
    
    /* Hero Tabs Styling */
    .hero-tabs-nav {
        margin-bottom: 1rem;
        flex-shrink: 0;
        position: sticky;
        top: 0;
        z-index: 1000;
        background: rgba(13, 27, 42, 0.95);
        backdrop-filter: blur(10px);
        padding: 10px 0;
    }
    
    .hero-nav-tabs {
        border: none;
        flex-wrap: wrap;
        gap: 5px;
    }
    
    .hero-tab-link {
        background: rgba(33, 37, 41, 0.8);
        border: 1px solid rgba(255, 149, 0, 0.3);
        color: rgba(255, 255, 252, 0.8) !important;
        padding: 8px 15px;
        font-size: 1.2rem;
        font-weight: 500;
        border-radius: 5px;
        transition: all 0.3s ease;
        margin: 2px;
        backdrop-filter: blur(5px);
        min-width: max-content;
        white-space: nowrap;
        text-align: center;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
    
    .hero-tab-link:hover {
        background: rgba(33, 37, 41, 0.9);
        border-color: rgba(255, 149, 0, 0.5);
        color: rgb(255, 255, 252) !important;
        transform: translateY(-1px);
        font-weight: 500;
    }
    
    .hero-tab-link.active {
        background: rgba(255, 149, 0, 0.9);
        border-color: rgb(255, 149, 0);
        color: #000 !important;
        font-weight: 500;
    }
    
    /* Tab Content - Flexible Height */
    .hero-tab-content {
        min-height: 0;
        background: rgba(33, 37, 41, 0.15);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 15px;
        padding: 30px;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
        overflow-y: auto;
        display: flex;
        flex-direction: column;
    }
    
    .hero-tab-content-wrapper {
        flex: 1;
        display: flex;
        flex-direction: column;
        min-height: 0;
        overflow: hidden;
        padding: 0px;
    }
    
    /* Ensure text remains visible with proper contrast */
    .hero-tab-content .text-light-gray {
        color: rgb(224, 224, 224) !important;
    }
    
    .hero-tab-content .text-yellow {
        color: rgb(255, 149, 0) !important;
    }
    
    .hero-tab-content .text-red {
        color: rgb(215, 24, 42) !important;
    }
    
    .hero-tab-content .text-white {
        color: rgb(255, 255, 252) !important;
    }
    
    /* Custom scrollbar for tab content */
    .hero-tab-content::-webkit-scrollbar,
    .hero-tab-content-wrapper::-webkit-scrollbar {
        width: 8px;
    }
    
    .hero-tab-content::-webkit-scrollbar-track,
    .hero-tab-content-wrapper::-webkit-scrollbar-track {
        background: rgba(255, 255, 255, 0.1);
        border-radius: 4px;
    }
    
    .hero-tab-content::-webkit-scrollbar-thumb,
    .hero-tab-content-wrapper::-webkit-scrollbar-thumb {
        background: rgba(255, 149, 0, 0.6);
        border-radius: 4px;
    }
    
    .hero-tab-content::-webkit-scrollbar-thumb:hover,
    .hero-tab-content-wrapper::-webkit-scrollbar-thumb:hover {
        background: rgba(255, 149, 0, 0.8);
    }
    
    /* Mobile Carousel Styles */
    @media (max-width: 767px) {
        /* Page adjustments for mobile */
        .page {
            height: 100vh;
            overflow: hidden;
        }

        .hero-content {
            padding-top: 10px;
            padding-bottom: 10px;
        }

        /* Carousel Container */
        .carousel.slide {
            position: relative;
        }
        
        /* Carousel Items */
        .carousel-item {
            padding: 0 60px; /* Space for arrows */
        }
        
        /* Mobile Tab Navigation */
        .hero-nav-tabs-mobile {
            border: none;
            flex-wrap: nowrap;
            gap: 0;
            justify-content: center;
            padding: 0 20px;
        }
        
        /* Mobile Tab Links - Single tab per slide */
        .hero-tab-link-mobile {
            background: rgba(33, 37, 41, 0.8);
            border: 1px solid rgba(255, 149, 0, 0.3);
            color: rgba(255, 255, 252, 0.8) !important;
            padding: 12px 24px;
            font-size: 1rem;
            font-weight: 500;
            border-radius: 25px;
            transition: all 0.3s ease;
            margin: 0;
            backdrop-filter: blur(5px);
            min-width: 160px;
            white-space: normal;
            text-align: center;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            line-height: 1.2;
            min-height: 48px;
        }
        
        .hero-tab-link-mobile:hover {
            background: rgba(33, 37, 41, 0.9);
            border-color: rgba(255, 149, 0, 0.5);
            color: rgb(255, 255, 252) !important;
            transform: translateY(-1px);
            font-weight: 500;
        }
        
        .hero-tab-link-mobile.active {
            background: rgba(255, 149, 0, 0.9);
            border-color: rgb(255, 149, 0);
            color: #000 !important;
            font-weight: 600;
        }
        
        /* Carousel Controls */
        .carousel-control-prev,
        .carousel-control-next {
            width: 45px;
            height: 45px;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(255, 149, 0, 0.8);
            border-radius: 50%;
            opacity: 0.9;
            border: 2px solid rgba(255, 149, 0, 1);
        }
        
        .carousel-control-prev {
            left: 5px;
        }
        
        .carousel-control-next {
            right: 5px;
        }
        
        .carousel-control-prev:hover,
        .carousel-control-next:hover {
            background: rgba(255, 149, 0, 1);
            opacity: 1;
        }
        
        .carousel-control-prev-icon,
        .carousel-control-next-icon {
            width: 22px;
            height: 22px;
            background-size: 100%;
        }
        
        /* Smooth transitions */
        .carousel-item {
            transition: transform 0.3s ease-in-out;
        }

        /* Mobile tab content adjustments */
        .hero-tab-content {
            padding: 15px;
            border-radius: 12px;
        }
    }
    
    /* Responsive tabs */
    @media (max-width: 991px) {
        .hero-nav-tabs {
            justify-content: center !important;
        }
        
        .hero-tab-link {
            padding: 6px 10px;
            font-weight: 500;
        }
        
        .hero-tab-link:hover {
            font-weight: 500;
        }
        
        .hero-tab-link.active {
            font-weight: 500;
        }

        .hero-tab-content {
            padding: 20px;
            border-radius: 12px;
            /* Mobile improvements for smooth transitions */
            min-height: 400px; /* Prevent height collapse during transitions */
            transition: opacity 0.15s ease-in-out; /* Smooth fade transitions */
        }
        
        /* Ensure smooth tab transitions on mobile */
        .tab-pane {
            transition: opacity 0.15s linear;
        }
        
        .tab-pane.fade:not(.show) {
            opacity: 0;
        }
        
        .tab-pane.fade.show {
            opacity: 1;
        }
    }
    
    @media (max-width: 576px) {
        .hero-tabs-nav {
            margin-bottom: 0.5rem;
            /* Enable horizontal scrolling for mobile */
            overflow-x: auto;
            overflow-y: hidden;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: none; /* Firefox */
            -ms-overflow-style: none; /* IE/Edge */
            position: relative;
        }
        
        /* Hide scrollbar for webkit browsers */
        .hero-tabs-nav::-webkit-scrollbar {
            display: none;
        }
        
        .hero-nav-tabs {
            flex-wrap: nowrap !important; /* Prevent wrapping on mobile */
            gap: 8px;
            justify-content: flex-start !important; /* Align to start for scrolling */
            min-width: max-content; /* Allow tabs to extend beyond container */
            padding: 0 15px; /* Add padding for better touch experience */
        }
        
        .hero-tab-link {
            flex-shrink: 0; /* Prevent tabs from shrinking */
            font-size: 0.7rem;
            padding: 8px 12px;
            margin: 0; /* Remove margin for cleaner look */
            font-weight: 500;
            min-width: max-content;
            white-space: nowrap;
            border-radius: 25px; /* Make tabs more pill-like on mobile */
        }
        
        .hero-tab-link:hover {
            font-weight: 500;
        }
        
        .hero-tab-link.active {
            font-weight: 500;
            transform: none; /* Remove transform on mobile for better performance */
        }
        
        .hero-tab-content {
            padding: 10px;
            border-radius: 10px;
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
        }
        
        /* Add scroll indicators for better UX */
        .hero-tabs-nav::before,
        .hero-tabs-nav::after {
            content: '';
            position: absolute;
            top: 0;
            bottom: 0;
            width: 15px;
            pointer-events: none;
            z-index: 10;
        }
        
        .hero-tabs-nav::before {
            left: 0;
            background: linear-gradient(to right, rgba(0,0,0,0.2), transparent);
        }
        
        .hero-tabs-nav::after {
            right: 0;
            background: linear-gradient(to left, rgba(0,0,0,0.2), transparent);
        }
    }
    
    /* GO PLAY Button Styling */
    .btn-go-play {
        background: linear-gradient(135deg, var(--accent-red) 0%, #ff1744 50%, var(--accent-red) 100%);
        border: 3px solid var(--accent-yellow);
        color: white !important;
        font-size: 2rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 2px;
        border-radius: 15px;
        transition: all 0.3s ease;
        text-decoration: none;
        box-shadow: 0 8px 25px rgba(215, 24, 42, 0.4), 
                    inset 0 1px 0 rgba(255, 255, 255, 0.2);
        position: relative;
        overflow: hidden;
        min-width: 250px;
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
    }
    
    .btn-go-play::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
        transition: left 0.5s ease;
    }
    
    .btn-go-play:hover {
        background: linear-gradient(135deg, #ff1744 0%, var(--accent-red) 50%, #ff1744 100%);
        border-color: #fff;
        transform: translateY(-3px) scale(1.05);
        box-shadow: 0 15px 35px rgba(215, 24, 42, 0.6),
                    inset 0 1px 0 rgba(255, 255, 255, 0.3);
        color: white !important;
        text-decoration: none;
    }
    
    .btn-go-play:hover::before {
        left: 100%;
    }
    
    .btn-go-play:active {
        transform: translateY(-1px) scale(1.02);
        box-shadow: 0 8px 20px rgba(215, 24, 42, 0.5);
    }
    
    .btn-go-play:focus {
        outline: none;
        box-shadow: 0 0 0 4px rgba(255, 149, 0, 0.5),
                    0 15px 35px rgba(215, 24, 42, 0.6);
        color: white !important;
        text-decoration: none;
    }
    
    .go-play-text {
        text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
    }
    
    /* Responsive GO PLAY Button */
    @media (max-width: 991px) {
        .btn-go-play {
            font-size: 1.75rem;
            min-width: 220px;
            border-radius: 12px;
        }
    }
    
    @media (max-width: 576px) {
        .btn-go-play {
            font-size: 1.5rem;
            min-width: 200px;
            padding: 12px 25px !important;
            border-radius: 10px;
            letter-spacing: 1px;
        }
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
    background-image: linear-gradient(160deg,  #a54e07, #b47e11, #fef1a2, #bc881b, #a54e07);
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
    .hero-image {
        max-width: 220px;
    }
}
.accordion-button:not(.collapsed),.accordion-button:not(.collapsed):focus {
    box-shadow:none!important;
}
.accordion-button:focus {
    box-shadow: none !important;
    outline: none !important;
}
.expanded-accordion, .expanded-accordion button {
    background-color:rgb(54, 54, 54) !important;
    border-radius: 5px !important;
}
    .accordion-button::after {
        filter: brightness(0) saturate(100%) invert(98%) sepia(1%) saturate(1178%) hue-rotate(260deg) brightness(115%) contrast(78%);
    }

    /* Form Toggle Buttons */
    .btn-outline-warning {
        border-color: #ffc107 !important;
        color: #ffc107 !important;
        background: rgba(33, 37, 41, 0.8) !important;
        font-weight: 600 !important;
        padding: 10px 30px !important;
        transition: all 0.3s ease !important;
    }
    
    .btn-outline-warning:hover,
    .btn-outline-warning.active {
        background-color: #ffc107 !important;
        border-color: #ffc107 !important;
        color: #000 !important;
        font-weight: 600 !important;
    }
    
    .btn-outline-warning:focus {
        box-shadow: 0 0 0 2px rgba(255, 193, 7, 0.5) !important;
    }

    /* Profile Sub-tabs Styling */
    .profile-sub-tabs-nav {
        margin-bottom: 2rem;
    }
    
    .profile-sub-tab {
        background: rgba(33, 37, 41, 0.6) !important;
        border: 1px solid rgba(255, 149, 0, 0.2) !important;
        color: rgba(255, 255, 252, 0.8) !important;
        padding: 12px 25px !important;
        font-size: 1.1rem !important;
        font-weight: 500 !important;
        margin: 0 5px !important;
        border-radius: 8px 8px 0 0 !important;
        transition: all 0.3s ease !important;
    }
    
    .profile-sub-tab:hover {
        background: rgba(33, 37, 41, 0.8) !important;
        border-color: rgba(255, 149, 0, 0.4) !important;
        color: rgb(255, 255, 252) !important;
    }
    
    .profile-sub-tab.active {
        background: rgba(255, 149, 0, 0.9) !important;
        border-color: rgb(255, 149, 0) !important;
        color: #000 !important;
        font-weight: 600 !important;
    }
    
    @media (max-width: 767px) {
        .profile-sub-tab {
            font-size: 0.9rem !important;
            padding: 10px 15px !important;
            margin: 0 2px !important;
        }
    }

    /* Text Size Classes for Tab Content */
    .text-28pt {
        font-size: 28pt !important;
    }
    
    .text-24pt {
        font-size: 24pt !important;
    }
    
    .text-22pt {
        font-size: 22pt !important;
    }
    
    .text-18pt {
        font-size: 18pt !important;
    }
    
    .text-16pt {
        font-size: 16pt !important;
        line-height: 1.5 !important;
    }
    
    .text-14pt {
        font-size: 14pt !important;
    }
    
    .text-12pt {
        font-size: 12pt !important;
    }
    
    /* Alert Styling */
    .alert-success {
        background: rgba(33, 37, 41, 0.25) !important;
        border: 1px solid rgba(255, 149, 0, 0.4) !important;
        color: var(--light-gray) !important;
        font-size: 1.45rem !important;
        backdrop-filter: blur(15px);
        -webkit-backdrop-filter: blur(15px);
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.2);
        border-radius: 8px !important;
        padding: 15px 20px !important;
    }
    
    .alert-warning {
        background: rgba(255, 149, 0, 0.15) !important;
        border: 1px solid rgba(255, 149, 0, 0.5) !important;
        color: var(--light-gray) !important;
        font-size: 1.45rem !important;
        backdrop-filter: blur(15px);
        -webkit-backdrop-filter: blur(15px);
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.2);
        border-radius: 8px !important;
        padding: 15px 20px !important;
    }
    
    /* Section dividers */
    .section-divider {
        color: var(--light-gray);
        font-size: 18pt;
        margin: 24px 0;
        text-align: center;
    }
    
    /* Callout boxes */
    .callout-box {
        font-style: italic;
        padding: 15px 20px;
        margin: 20px 0;
        background: rgba(255, 149, 0, 0.1);
        border-left: 4px solid var(--accent-yellow);
        border-radius: 5px;
    }
    
    /* Pro tip styling */
    .pro-tip {
        background: rgba(33, 37, 41, 0.3);
        border: 1px solid rgba(255, 149, 0, 0.3);
        border-radius: 8px;
        padding: 20px;
        margin: 20px 0;
        font-style: italic;
    }

    /* List Indentation Fixes */
    .hero-tab-content ul {
        padding-left: 25px !important;
    }
    
    .hero-tab-content ul ul {
        padding-left: 20px !important;
    }
    
    .hero-tab-content ol {
        padding-left: 25px !important;
    }
    
    .hero-tab-content ol ol {
        padding-left: 20px !important;
    }
    
    /* Mobile-specific list indentation */
    @media (max-width: 767px) {
        .hero-tab-content .walkthrough ul {
            padding-left: 0px !important;
        }
        
        .hero-tab-content .walkthrough ul ul {
            padding-left: 0px !important;
        }
        
        .hero-tab-content ol {
            padding-left: 20px !important;
        }
        
        .hero-tab-content ol ol {
            padding-left: 15px !important;
        }
        
        .hero-tab-content ul ul ul {
            padding-left: 10px !important;
        }
        
        .hero-tab-content ol ol ol {
            padding-left: 10px !important;
        }
        
        /* Mobile accordion header text size reduction */
        .accordion-button {
            font-size: 14pt !important;
            padding: 12px 16px !important;
            line-height: 1.3 !important;
        }
        
        /* Even smaller text for very small screens */
        @media (max-width: 480px) {
            .accordion-button {
                font-size: 12pt !important;
                padding: 10px 14px !important;
                line-height: 1.2 !important;
            }
        }
    }

</style>

<script>
function goToSection(tabId, sectionId) {
    // First, switch to the correct tab
    const tabButton = document.querySelector(`[data-bs-target="#${tabId}"]`);
    const tabContent = document.getElementById(tabId);
    
    if (tabButton && tabContent) {
        // Hide all tabs
        document.querySelectorAll('.tab-pane').forEach(pane => {
            pane.classList.remove('show', 'active');
        });
        
        // Remove active from all tab buttons
        document.querySelectorAll('[data-bs-toggle="tab"]').forEach(btn => {
            btn.classList.remove('active');
        });
        
        // Show the target tab
        tabButton.classList.add('active');
        tabContent.classList.add('show', 'active');
        
        // Then scroll to the section after a brief delay
        setTimeout(() => {
            document.getElementById(sectionId).scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }, 200);
    }
}

// Function to handle accordion navigation within tabs
function goToAccordion(tabId, accordionId) {
    // First, switch to the correct tab
    const tabButton = document.querySelector(`[data-bs-target="#${tabId}"]`) || document.querySelector(`[aria-controls="${tabId}"]`);
    const tabContent = document.getElementById(tabId);
    
    if (tabButton && tabContent) {
        // Update URL hash for tab navigation
        const tabHash = tabButton.getAttribute('data-hash');
        if (tabHash) {
            history.pushState(null, null, `#${tabHash}`);
            
            // Use existing tab activation function
            if (window.activateTabByHash) {
                activateTabByHash(tabHash);
            }
        }
        
        // Wait for tab to be active, then open the accordion
        setTimeout(() => {
            const accordionCollapse = document.getElementById(accordionId);
            const accordionButton = document.querySelector(`[data-bs-target="#${accordionId}"]`);
            
            if (accordionCollapse && accordionButton) {
                // Open the accordion
                const bsCollapse = new bootstrap.Collapse(accordionCollapse, {
                    show: true
                });
                
                // Update button state
                accordionButton.classList.remove('collapsed');
                accordionButton.setAttribute('aria-expanded', 'true');
                
                // Scroll to accordion after it opens, accounting for nav-tabs height
                setTimeout(() => {
                    const navTabs = document.querySelector('.nav-tabs');
                    const navTabsHeight = navTabs ? navTabs.offsetHeight : 0;
                    const additionalOffset = 20; // Extra padding
                    
                    const elementPosition = accordionButton.getBoundingClientRect().top + window.pageYOffset;
                    const offsetPosition = elementPosition - navTabsHeight - additionalOffset;
                    
                    window.scrollTo({
                        top: offsetPosition,
                        behavior: 'smooth'
                    });
                }, 300);
            }
        }, 400);
    }
}

// Enhanced hash navigation handler
function handleHashNavigation(hash) {
    // Remove # if present
    hash = hash.replace('#', '');
    
    // Define accordion mappings
    const accordionMappings = {
        'betting-strategy': { tab: 'tab7', accordion: 'bettingStrategyCollapse' },
        'what-makes-avantage-different': { tab: 'tab7', accordion: 'whyDifferentCollapse' },
        'baccarat-basics': { tab: 'tab7', accordion: 'baccaratBasicsCollapse' },
        'naturals-drawing': { tab: 'tab7', accordion: 'naturalsDrawingCollapse' },
        'tournament-structure': { tab: 'tab7', accordion: 'tournamentStructureCollapse' },
        'practice-builds-trust': { tab: 'tab7', accordion: 'practiceBuildsCollapse' },
        'key-mindset': { tab: 'tab7', accordion: 'keyMindsetCollapse' },
        'advanced-edge': { tab: 'tab7', accordion: 'advancedEdgeCollapse' },
        'streamer-role': { tab: 'tab7', accordion: 'streamerRoleCollapse' },
        
        // Mindset tab accordions
        'mindset-matters': { tab: 'tab2', accordion: 'mindsetMattersCollapse' },
        'universal-belief': { tab: 'tab2', accordion: 'universalBeliefCollapse' },
        'cultural-power': { tab: 'tab2', accordion: 'culturalPowerCollapse' },
        'final-note': { tab: 'tab2', accordion: 'finalNoteCollapse' },
        
        // Championship tab accordions
        'championship-works': { tab: 'tab3', accordion: 'championshipWorksCollapse' },
        'players-advance': { tab: 'tab3', accordion: 'playersAdvanceCollapse' },
        'built-for-streamers': { tab: 'tab3', accordion: 'builtForStreamersCollapse' },
        
        // Leagues tab accordions
        'global-competition': { tab: 'tab4', accordion: 'globalCompetitionCollapse' },
        'scale-of-leagues': { tab: 'tab4', accordion: 'scaleOfLeaguesCollapse' },
        'leadership-role': { tab: 'tab4', accordion: 'leadershipRoleCollapse' },
        
        // Monetization tab accordions
        'real-monetization': { tab: 'tab5', accordion: 'realMonetizationCollapse' },
        'partner-not-employee': { tab: 'tab5', accordion: 'partnerNotEmployeeCollapse' },
        'billion-mistake': { tab: 'tab5', accordion: 'billionMistakeCollapse' },
        
        // Amplifier tab accordions
        'promotion-plan': { tab: 'tab6', accordion: 'promotionPlanCollapse' },
        'building-presence': { tab: 'tab6', accordion: 'buildingPresenceCollapse' }
    };
    
    // Check if hash corresponds to an accordion
    if (accordionMappings[hash]) {
        const mapping = accordionMappings[hash];
        goToAccordion(mapping.tab, mapping.accordion);
        return true;
    }
    
    // Handle existing section navigation
    if (hash === 'what-makes-avantage-different') {
        goToSection('tab7', 'what-makes-avantage-different');
        return true;
    }
    if (hash === 'betting-strategy') {
        goToAccordion('tab7', 'bettingStrategyCollapse');
        return true;
    }
    
    return false;
}

// Handle URLs with hash fragments
document.addEventListener('DOMContentLoaded', function() {
    const hash = window.location.hash.substring(1);
    
    if (hash) {
        // Try accordion navigation first
        if (!handleHashNavigation(hash)) {
            // Fall back to existing tab navigation
            if (window.activateTabByHash) {
                activateTabByHash(hash);
            }
        }
    }
    
    // Listen for hash changes
    window.addEventListener('hashchange', function() {
        const newHash = window.location.hash.substring(1);
        if (newHash) {
            handleHashNavigation(newHash);
        }
    });
});
</script>
<?php
get_footer();
