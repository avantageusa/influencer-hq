<?php
/**
 * Template Name: Portal home old
 * Description: A custom template for displaying the influencer HQ.
 * This template is used to render the influencer HQ content and layout.
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package Avantage_Baccarat
 */
get_header();
?>

    <main id="primary" class="site-main">
        
        <!-- Search Bar -->
        <div class="search-bar-container">
            <div class="container" style="max-width: 1024px; padding-left: 20px; padding-right: 20px;">
                <div class="search-bar">
                    <svg class="search-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#666" stroke-width="2">
                        <circle cx="11" cy="11" r="8"></circle>
                        <path d="m21 21-4.35-4.35"></path>
                    </svg>
                    <input type="text" class="search-input" placeholder="Search...">
                </div>
            </div>
        </div>
        
        <!-- Sticky Header: Hamburger, Logo, Globe -->
        <div class="sticky-header">
            <div class="container" style="max-width: 1024px; padding-left: 20px; padding-right: 20px; margin-top: 20px; padding-bottom: 30px;">
                <div class="d-flex justify-content-between align-items-center">
                    <button class="hamburger-menu bg-transparent border-0 p-0">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#3B9FFF" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="3" y1="6" x2="21" y2="6"></line>
                            <line x1="3" y1="12" x2="21" y2="12"></line>
                            <line x1="3" y1="18" x2="21" y2="18"></line>
                        </svg>
                    </button>
                    <div class="logo-container text-center">
                        <img src="<?php echo get_template_directory_uri(); ?>/images/logo-hq.png" alt="influencerHQ" class="img-fluid" style="max-height: 40px;">
                    </div>
                    <div class="globe-icon">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#999" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path></svg>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Sticky Navigation Tabs -->
        <div class="sticky-nav">
            <div class="container" style="max-width: 1024px; padding-left: 20px; padding-right: 20px;">
                <div class="navigation-wrapper">
                    <div class="nav-row-1 text-center mb-2">
                        <a href="<?php echo home_url('/portal-home'); ?>" class="nav-link-inline active">Home</a>
                        <span class="nav-separator">|</span>
                        <a href="<?php echo home_url('/portal/equity'); ?>" class="nav-link-inline">Equity</a>
                        <span class="nav-separator">|</span>
                        <a href="<?php echo home_url('/portal/challenges'); ?>" class="nav-link-inline">Challenges</a>
                        <span class="nav-separator">|</span>
                        <a href="<?php echo home_url('/portal/live'); ?>" class="nav-link-inline">Live</a>
                    </div>
                    <div class="nav-row-2 text-center">
                        <a href="<?php echo home_url('/portal/rankings'); ?>" class="nav-link-inline">Rankings</a>
                        <span class="nav-separator">|</span>
                        <a href="<?php echo home_url('/portal/network'); ?>" class="nav-link-inline">Network</a>
                        <span class="nav-separator">|</span>
                        <a href="<?php echo home_url('/portal/account'); ?>" class="nav-link-inline">Account</a>
                        <span class="nav-separator">|</span>
                        <a href="<?php echo home_url('/portal/help'); ?>" class="nav-link-inline">Help</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Dealer Image -->
        <div class="dealer-image-container">
            <img src="<?php echo get_template_directory_uri(); ?>/images/new-dealer.jpg" alt="Casino Dealer" class="dealer-image">
        </div>
        <div class="container py-2" style="max-width: 1024px; padding-left: 20px; padding-right: 20px;">

            <!-- Hamburger Dropdown Menu -->
            <div class="hamburger-dropdown" id="hamburgerDropdown">
                <ul class="dropdown-menu">
                    <li><a href="<?php echo home_url('/portal-home'); ?>" class="dropdown-link active"><i class="icon-home"></i> Home</a></li>
                    <li><a href="<?php echo home_url('/portal/equity'); ?>" class="dropdown-link"><i class="icon-equity"></i> Equity</a></li>
                    <li><a href="<?php echo home_url('/portal/challenges'); ?>" class="dropdown-link"><i class="icon-challenges"></i> Challenges</a></li>
                    <li><a href="<?php echo home_url('/portal/rankings'); ?>" class="dropdown-link"><i class="icon-ranking"></i> Ranking</a></li>
                    <li><a href="<?php echo home_url('/portal/equity-impact'); ?>" class="dropdown-link"><i class="icon-impact"></i> Equity Impact</a></li>
                    <li><a href="<?php echo home_url('/portal/live'); ?>" class="dropdown-link"><i class="icon-live"></i> Live</a></li>
                    <li><a href="<?php echo home_url('/portal/profile'); ?>" class="dropdown-link"><i class="icon-profile"></i> Profile</a></li>
                    <li><a href="<?php echo home_url('/portal/settings'); ?>" class="dropdown-link"><i class="icon-settings"></i> Settings</a></li>
                    <li><a href="<?php echo wp_logout_url(); ?>" class="dropdown-link"><i class="icon-logout"></i> Logout</a></li>
                </ul>
            </div>

            <!-- Tab Content -->
            <div class="tab-content" id="mainTabContent">
                
                <!-- Home Tab -->
                <div class="tab-pane fade show active" id="home" role="tabpanel">
                    <!-- Accordion Questions -->
                    <div class="accordion custom-accordion" id="equityAccordion">
                        
                        <!-- Q1 -->
                <div class="accordion-item mb-3">
                    <h2 class="accordion-header" id="headingOne">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="false" aria-controls="collapseOne">
                            <span class="question-number">Q1</span>
                            <span class="question-text">What's "this" Opportunity All About</span>
                        </button>
                    </h2>
                    <div id="collapseOne" class="accordion-collapse collapse" aria-labelledby="headingOne" data-bs-parent="#equityAccordion">
                        <div class="accordion-body">
                            <p>Brands everywhere rely on influencers to shape culture, spark engagement, and build communities.</p>
                            <p>But the vast majority of traditional partnerships offer only short-term payouts — and no opportunity to share in the long-term value created by their influence.</p>
                            <p>We believe in a different path.</p>
                            <p>When your influence helps grow a platform, you'll be guaranteed to receive a share in the long-term value created in the form of equity.</p>
                        </div>
                    </div>
                </div>

                <!-- Q2 -->
                <div class="accordion-item mb-3">
                    <h2 class="accordion-header" id="headingTwo">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                            <span class="question-number">Q2</span>
                            <span class="question-text">How do I know that accepting equity for Influence is the best choice?</span>
                        </button>
                    </h2>
                    <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#equityAccordion">
                        <div class="accordion-body">
                            <p>There's never a guarantee in the equity game, but it's a fact that Global equity markets added roughly $20 trillion in value from 2015 to 2020 — and more than twice that, about $50 trillion, from 2020 to 2025.</p>
                            <p>One of the world's most famous "missed equity opportunities" was when 3-time NBA MVP Magic Johnson, fresh out of college, turned down Nike's 1 cent per shoe royalty and 11 cent stock in exchange for promoting Nike shoes. The 11 cent stock is now worth over $5.4 billion.</p>
                        </div>
                    </div>
                </div>

                <!-- Q3 -->
                <div class="accordion-item mb-3">
                    <h2 class="accordion-header" id="headingThree">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                            <span class="question-number">Q3</span>
                            <span class="question-text">How does Competition fit Into this Picture?</span>
                        </button>
                    </h2>
                    <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#equityAccordion">
                        <div class="accordion-body">
                            <p>Competition is the oldest language in the world. From the earliest days of civilization, competition has united people across cultures.</p>
                            <p>It began in ancient Greece, where the first Olympic Games ignited national pride.</p>
                            <p>It filled the arenas of Rome, and it lives in Asia's legendary traditions — games of mastery, precision, rhythm, and strategy celebrated for centuries.</p>
                            <p>Competition represents elegance… prestige… global legacy.</p>
                            <p>And most of all — it represents memorable moments we share together.</p>
                        </div>
                    </div>
                </div>

                <!-- Q4 -->
                <div class="accordion-item mb-3">
                    <h2 class="accordion-header" id="headingFour">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFour" aria-expanded="false" aria-controls="collapseFour">
                            <span class="question-number">Q4</span>
                            <span class="question-text">How do I fit in?</span>
                        </button>
                    </h2>
                    <div id="collapseFour" class="accordion-collapse collapse" aria-labelledby="headingFour" data-bs-parent="#equityAccordion">
                        <div class="accordion-body">
                            <p>People don't buy what you do.</p>
                            <p>They buy what you believe.</p>
                            <p>When your followers watch you play, cheer, and celebrate, they become part of a shared experience — the same way great sports, music, and global competitions always have.</p>
                            <p>This is what influence looks like when it comes alive.</p>
                        </div>
                    </div>
                </div>

                <!-- Q5 -->
                <div class="accordion-item mb-3">
                    <h2 class="accordion-header" id="headingFive">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFive" aria-expanded="false" aria-controls="collapseFive">
                            <span class="question-number">Q5</span>
                            <span class="question-text">What sets this opportunity apart?</span>
                        </button>
                    </h2>
                    <div id="collapseFive" class="accordion-collapse collapse" aria-labelledby="headingFive" data-bs-parent="#equityAccordion">
                        <div class="accordion-body">
                            <p>Over 10 years ago, an Entrepreneur, while traveling across Asia, visited Macau and discovered an oasis of excitement - thousands of players, young and old, in a single grande parlor of a single grande hotel, sitting and standing around Baccarat tables stretching almost as far as the eye could see…all separately cheering and shouting in anticipation of each card received and squeezed in the most ritualistic manner imaginable.</p>
                            <p>The game is Baccarat — Known as the Game of the Kings for over 500 years — Baccarat had become Asia's game of choice and the choice of high rollers across the world about the time James Bond introduced the game to the modern world over 40 years ago.</p>
                            <p>The founder asked himself, "why couldn't there be an on-line version of Baccarat that captures the same elegance on display in Macau?"</p>
                            <p>Avantage Baccarat World Tour and World Championship official launch is the result of our founder's love for the game.</p>
                            <p>But Avantage Baccarat is more than just a game…it's a Competition System built around the Avantage model of International Competition including Celebrity Followers Leagues and the Influencer Challenge Series, where top Influencers and their followers square off against each other in both Private Challenges and the World Challenge Series.</p>
                            <p>All of this culminates with the Avantage Baccarat World Championship, which guarantees a prize pool of $100 Million.</p>
                            <p>Baccarat succeeded in Asia for the same reason it became the game of choice in Monte Carlo and other elite locations across the world…simple rules, no bluffing and high prestige dating back to the French Royal Court 500 years ago.</p>
                        </div>
                    </div>
                </div>

                <!-- Q6 -->
                <div class="accordion-item mb-3">
                    <h2 class="accordion-header" id="headingSix">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSix" aria-expanded="false" aria-controls="collapseSix">
                            <span class="question-number">Q6</span>
                            <span class="question-text">How does the Equity Award System work?</span>
                        </button>
                    </h2>
                    <div id="collapseSix" class="accordion-collapse collapse" aria-labelledby="headingSix" data-bs-parent="#equityAccordion">
                        <div class="accordion-body">
                            <p><strong>Influencer HQ equity on play related to your direct or indirect referral</strong></p>
                            <ul>
                                <li>Level 1 - Direct Referral 1.5% x amount played</li>
                                <li>Level 2 - 1% x amount played by referrals of your L1 Players</li>
                                <li>Level 3 - .5% x amount played by Persons referred by your Level 2 players</li>
                                <li>Bonuses: 1% x amount played by anyone playing along with you.</li>
                            </ul>
                            <p>Your followers will be entitled to the same equity based on the play of persons referred by them.</p>
                            <p>Every equity share is recorded at the moment it is earned.</p>
                            <p>Each share has its own timeline.</p>
                            <p>Nothing is pooled, estimated, or assumed.</p>
                            <p>This is ownership built one moment at a time.</p>
                        </div>
                    </div>
                </div>

                <!-- Q7 -->
                <div class="accordion-item mb-3">
                    <h2 class="accordion-header" id="headingSeven">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSeven" aria-expanded="false" aria-controls="collapseSeven">
                            <span class="question-number">Q7</span>
                            <span class="question-text">Tell me more about the Live Appearances on the World Broadcasts.</span>
                        </button>
                    </h2>
                    <div id="collapseSeven" class="accordion-collapse collapse" aria-labelledby="headingSeven" data-bs-parent="#equityAccordion">
                        <div class="accordion-body">
                            <p>Influencers and streamers who deliver outstanding engagement will be invited to appear live on the World Broadcast alongside top creators from around the world.</p>
                            <p>Each broadcast showcases competitors from different regions, appearing together from their own locations — sharing reactions, commentary, and competitive energy in real time.</p>
                            <p>It's prime time somewhere in the world, 24 hours a day.</p>
                            <p>Live appearances amplify influence — and create unforgettable shared moments.</p>
                        </div>
                    </div>
                </div>

                <!-- Q8 -->
                <div class="accordion-item mb-3">
                    <h2 class="accordion-header" id="headingEight">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseEight" aria-expanded="false" aria-controls="collapseEight">
                            <span class="question-number">Q8</span>
                            <span class="question-text">Tell me more about Live Streaming on Kick?</span>
                        </button>
                    </h2>
                    <div id="collapseEight" class="accordion-collapse collapse" aria-labelledby="headingEight" data-bs-parent="#equityAccordion">
                        <div class="accordion-body">
                            <p>Easy for experienced Streamers - Choose your own broadcast times and invite all your followers to compete by your side.</p>
                            <p>New to Streaming? Just download our Streaming Made Easy tutorial from this website.</p>
                        </div>
                    </div>
                </div>

                <!-- Q9 -->
                <div class="accordion-item mb-3">
                    <h2 class="accordion-header" id="headingNine">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseNine" aria-expanded="false" aria-controls="collapseNine">
                            <span class="question-number">Q9</span>
                            <span class="question-text">When will I be able to sell my equity?</span>
                        </button>
                    </h2>
                    <div id="collapseNine" class="accordion-collapse collapse" aria-labelledby="headingNine" data-bs-parent="#equityAccordion">
                        <div class="accordion-body">
                            <p>As with most all founder equity, there's a 2 year holding period on equity awarded as described above.</p>
                        </div>
                    </div>
                </div>

                <!-- Q10 -->
                <div class="accordion-item mb-3">
                    <h2 class="accordion-header" id="headingTen">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTen" aria-expanded="false" aria-controls="collapseTen">
                            <span class="question-number">Q10</span>
                            <span class="question-text">How do I get Started?</span>
                        </button>
                    </h2>
                    <div id="collapseTen" class="accordion-collapse collapse" aria-labelledby="headingTen" data-bs-parent="#equityAccordion">
                        <div class="accordion-body">
                            <p><strong>STEP 1 — Communicate Invitations to Compete to Your Followers</strong></p>
                            <p>Competition is the moment where influence turns into participation.</p>
                            <p>Your followers aren't just watching anymore — they're playing along with you.</p>
                            <p>By entering your first Challenge, you step onto a competitive stage where every hand played, every moment shared, and every decision made begins to create measurable participation.</p>
                            <p>This is where influence stops being abstract — and starts being counted.</p>
                            
                            <p><strong>STEP 2 — Challenges Concentrate Attention</strong></p>
                            <p>Avantage Private and World Challenge Events are designed to bring people together at the same time, around the same experience.</p>
                            <p>They concentrate energy, participation, and momentum.</p>
                            <p>Whether you create a private Challenge for your own community or join a global competition open to all Influencers, Challenges turn everyday play into shared moments that matter.</p>
                            <p>And shared moments are what generate real engagement.</p>
                            
                            <p><strong>STEP 3 — Genius Protects Your Influence</strong></p>
                            <p>As participation grows, accuracy matters.</p>
                            <p>That's why every follower, every level of participation, and every moment of play is tracked by Genius — the private system that ensures your influence is recognized and recorded correctly.</p>
                            <p>Genius exists for one reason: to make sure that when your influence drives participation, you receive credit for it.</p>
                            <p>Email verification activated Genius; so the door to earning equity for influence has already been opened.</p>
                            
                            <p><strong>STEP 4 — Equity Is Earned Through Play</strong></p>
                            <p>Equity is created by participation — not promises.</p>
                            <p>You earn equity shares from:</p>
                            <ul>
                                <li>Play generated by people in your referral network, and</li>
                                <li>Bonus equity from anyone who plays while watching your live streams or participating during your live appearances.</li>
                            </ul>
                            
                            <p><strong>STEP 5 — Performance Reveals What Works</strong></p>
                            <p>Inside your private portal, Performance shows you exactly where your influence is most effective.</p>
                            <p>You'll see:</p>
                            <ul>
                                <li>How much play your influence generated</li>
                                <li>Where your equity came from</li>
                                <li>Which Challenges, time windows, and formats perform best</li>
                            </ul>
                            <p>When something works — you'll know it.</p>
                            <p>And when it doesn't — you'll know that too.</p>
                            <p>Clarity replaces guesswork.</p>
                            <p><strong>Have Fun and Good Luck!</strong></p>
                        </div>
                    </div>
                </div>

                </div>
                
                <!-- Equity Tab -->
                <div class="tab-pane fade" id="equity" role="tabpanel">
                    <p class="text-white-50">Equity Content</p>
                </div>
                
                <!-- Challenges Tab -->
                <div class="tab-pane fade" id="challenges" role="tabpanel">
                    <p class="text-white-50">Challenges Content</p>
                </div>
                
                <!-- Live Tab -->
                <div class="tab-pane fade" id="live" role="tabpanel">
                    <p class="text-white-50">Live Content</p>
                </div>
                
                <!-- Rankings Tab -->
                <div class="tab-pane fade" id="rankings" role="tabpanel">
                    <p class="text-white-50">Rankings Content</p>
                </div>
                
                <!-- Network Tab -->
                <div class="tab-pane fade" id="network" role="tabpanel">
                    <p class="text-white-50">Network Content</p>
                </div>
                
                <!-- Account Tab -->
                <div class="tab-pane fade" id="account" role="tabpanel">
                    <p class="text-white-50">Account Content</p>
                </div>
                
                <!-- Help Tab -->
                <div class="tab-pane fade" id="help" role="tabpanel">
                    <p class="text-white-50">Help Content</p>
                </div>
                
            </div>
        </div>
        
        <!-- Fixed Footer Links -->
        <div class="footer-links-fixed">
            <a href="#" class="footer-link">Terms</a>
            <span class="footer-separator">|</span>
            <a href="#" class="footer-link">Privacy</a>
        </div>
    </main><!-- #main -->

<style>
    :root {
        --gold: #E6CFA0;
        --gold-dark: #C4A46D;
        --dark-bg: #0a0a0a;
    }

    body {
        margin: 0;
        padding: 0;
        font-family: 'Source Sans Pro', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        background-color: var(--dark-bg);
        color: #fff;
    }

    .site-main {
        position: relative;
        min-height: 100vh;
        overflow-x: hidden;
        padding-bottom: 80px; /* Account for fixed footer */
    }
    
    .container {
        margin-top: 280px; /* Account for search bar + header + nav */
    }

    .text-gold {
        color: var(--gold) !important;
    }

    /* Navigation Links */
    .navigation-wrapper {
        font-size: 1rem;
    }
    
    .nav-link-inline {
        color: #fff;
        text-decoration: none;
        padding: 0 8px;
        transition: color 0.2s;
        font-size: 24px;
        display: inline;
    }
    
    .nav-link-inline:hover {
        color: var(--gold);
    }
    
    .nav-link-inline.active {
        color: var(--gold);
        font-weight: 600;
    }
    
    .nav-separator {
        color: #666;
        margin: 0 4px;
    }
    
    /* Reduce margins for containers inside sticky elements */
    .sticky-header .container,
    .sticky-nav .container,
    .search-bar-container .container {
        margin-top: 0;
        margin-bottom: 0;
    }

    /* Custom Accordion */
    .custom-accordion .accordion-item {
        background: transparent;
        border: 2px solid rgba(255, 255, 255, 0.3);
        border-radius: 8px;
        overflow: hidden;
    }
    
    .custom-accordion .accordion-button {
        background: transparent;
        color: #fff;
        border: none;
        padding: 18px 20px;
        font-size: 0.95rem;
        font-weight: 400;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    
    .custom-accordion .accordion-button:not(.collapsed) {
        background: transparent;
        color: #fff;
        box-shadow: none;
    }
    
    .custom-accordion .accordion-button:focus {
        box-shadow: none;
        border: none;
    }
    
    .custom-accordion .accordion-button::after {
        content: '›';
        font-size: 2rem;
        font-weight: 300;
        color: #fff;
        background: none;
        transform: rotate(0deg);
        transition: transform 0.2s;
        width: auto;
        height: auto;
        flex-shrink: 0;
    }
    
    .custom-accordion .accordion-button:not(.collapsed)::after {
        transform: rotate(90deg);
    }
    
    .question-number {
        font-weight: 600;
        margin-right: 15px;
        flex-shrink: 0;
    }
    
    .question-text {
        flex-grow: 1;
        text-align: left;
    }
    
    .custom-accordion .accordion-body {
        background: transparent;
        color: rgba(255, 255, 255, 0.8);
        padding: 0 20px 20px 20px;
    }
    
    /* Hide footer */
    footer {
        display: none !important;
    }
    
    /* Search Bar */
    .search-bar-container {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        background: rgba(10, 10, 10, 0.98);
        backdrop-filter: blur(10px);
        z-index: 1001;
        padding: 15px 0;
    }
    
    .search-bar {
        position: relative;
        width: 100%;
        max-width: 850px;
        margin: 0 auto;
    }
    
    .search-icon {
        position: absolute;
        left: 20px;
        top: 50%;
        transform: translateY(-50%);
        pointer-events: none;
    }
    
    .search-input {
        width: 100%;
        padding: 12px 20px 12px 50px;
        background: #fff;
        border: none;
        border-radius: 25px;
        font-size: 15px;
        outline: none;
    }
    
    .search-input::placeholder {
        color: #999;
    }
    
    /* Fixed Header */
    .sticky-header {
        position: fixed;
        top: 60px;
        left: 0;
        width: 100%;
        background: rgba(10, 10, 10, 0.95);
        backdrop-filter: blur(10px);
        z-index: 1000;
        padding: 15px 0;
    }
    
    .sticky-nav {
        position: fixed;
        top: 180px;
        left: 0;
        width: 100%;
        background: rgba(10, 10, 10, 0.9);
        backdrop-filter: blur(10px);
        z-index: 999;
        padding: 10px 0;
        margin-bottom: 20px;
        border-top: 1px solid transparent;
        border-bottom: 1px solid transparent;
        border-image: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3) 20%, rgba(255, 255, 255, 0.3) 80%, transparent) 1;
    }
    
    /* Dealer Image */
    .dealer-image-container {
        margin-top: 280px; /* Account for search bar + header + nav */
        text-align: center;
        margin-bottom: 20px;
    }
    
    .dealer-image {
        max-width: 100%;
        height: auto;
        border-radius: 8px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
    }
    
    /* Fixed Footer Links */
    .footer-links-fixed {
        position: fixed;
        bottom: 0;
        left: 0;
        width: 100%;
        background: rgba(10, 10, 10, 0.95);
        backdrop-filter: blur(10px);
        color: rgba(255, 255, 255, 0.6);
        font-size: 0.9rem;
        text-align: center;
        padding: 15px 0;
        z-index: 998;
    }

    /* Hamburger Menu */
    .hamburger-menu {
        z-index: 1001;
    }
    
    .hamburger-menu:hover {
        opacity: 0.8;
    }
    
    /* Hamburger Dropdown Menu */
    .hamburger-dropdown {
        position: fixed;
        top: 135px;
        left: 20px;
        width: 250px;
        background: #000;
        border: 1px solid rgba(255, 255, 255, 0.2);
        border-radius: 8px;
        z-index: 1002;
        opacity: 0;
        visibility: hidden;
        transform: translateY(-10px);
        transition: all 0.3s ease;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.5);
        display: block;
    }
    
    .hamburger-dropdown.open {
        opacity: 1;
        visibility: visible;
        transform: translateY(0);
    }
    
    .dropdown-menu {
        list-style: none;
        padding: 0;
        margin: 0;
        display: block;
    }
    
    .dropdown-menu li {
        margin-bottom: 0;
        display: block;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }
    
    .dropdown-menu li:last-child {
        border-bottom: none;
    }
    
    .dropdown-link {
        display: flex;
        align-items: center;
        padding: 18px 20px;
        color: #fff;
        text-decoration: none;
        transition: all 0.2s ease;
        font-size: 20px;
        font-weight: 500;
    }
    
    .dropdown-link:hover {
        background: rgba(255, 255, 255, 0.05);
    }
    
    .dropdown-link.active {
        background: rgba(255, 255, 255, 0.08);
    }
    
    .dropdown-link i {
        margin-right: 15px;
        width: 30px;
        text-align: center;
        font-size: 24px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
    
    /* Icon placeholders - you can replace with actual icon fonts */
    .icon-home::before { content: '🏠'; }
    .icon-equity::before { content: '📊'; }
    .icon-challenges::before { content: '🚩'; }
    .icon-ranking::before { content: '🏆'; }
    .icon-impact::before { content: '📊'; }
    .icon-live::before { content: '📺'; }
    .icon-profile::before { content: '👤'; }
    .icon-settings::before { content: '⚙️'; }
    .icon-logout::before { content: '🚪'; }
    
    /* Footer Link Styles */\n    .footer-links-fixed .footer-link {\n        color: rgba(255, 255, 255, 0.6);\n        text-decoration: none;\n        transition: color 0.2s;\n    }\n    \n    .footer-links-fixed .footer-link:hover {\n        color: #fff;\n    }\n    \n    .footer-links-fixed .footer-separator {\n        margin: 0 8px;\n    }\n

</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Bootstrap tabs - simpler method
    var triggerTabList = document.querySelectorAll('.nav-link-inline[data-bs-toggle="tab"]');
    triggerTabList.forEach(function (triggerEl) {
        triggerEl.addEventListener('click', function (event) {
            event.preventDefault();
            
            // Remove active class from all tabs
            triggerTabList.forEach(function(tab) {
                tab.classList.remove('active');
            });
            
            // Add active class to clicked tab
            this.classList.add('active');
            
            // Hide all tab panes
            var tabPanes = document.querySelectorAll('.tab-pane');
            tabPanes.forEach(function(pane) {
                pane.classList.remove('show', 'active');
            });
            
            // Show selected tab pane
            var targetId = this.getAttribute('data-bs-target');
            var targetPane = document.querySelector(targetId);
            if (targetPane) {
                targetPane.classList.add('show', 'active');
            }
        });
    });
    
    // Hamburger dropdown menu functionality
    var hamburger = document.querySelector('.hamburger-menu');
    var dropdown = document.getElementById('hamburgerDropdown');

    function toggleDropdown() {
        dropdown.classList.toggle('open');
    }

    if (hamburger) {
        hamburger.addEventListener('click', function(e) {
            e.preventDefault();
            toggleDropdown();
        });
    }

    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (dropdown && !dropdown.contains(e.target) && !hamburger.contains(e.target)) {
            dropdown.classList.remove('open');
        }
    });
});
</script>

<?php
get_footer();
