<?php
/**
 * Template Name: Influencer HQ empty
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
        
        <!-- Mobile Hamburger Menu Button -->
        <button class="mobile-menu-toggle" type="button" aria-label="Toggle menu">
            <span class="hamburger-line"></span>
            <span class="hamburger-line"></span>
            <span class="hamburger-line"></span>
        </button>
        
        <!-- Fixed Left Sidebar Tabs -->
        <div class="hq-sidebar-tabs">
            <ul class="nav nav-pills flex-column" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="dashboard-tab" data-bs-toggle="tab" data-bs-target="#dashboard" type="button" role="tab" aria-controls="dashboard" aria-selected="true">Dashboard</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="challenges-tab" data-bs-toggle="tab" data-bs-target="#challenges" type="button" role="tab" aria-controls="challenges" aria-selected="false">Challenges</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="genius-network-tab" data-bs-toggle="tab" data-bs-target="#genius-network" type="button" role="tab" aria-controls="genius-network" aria-selected="false">Genius Network</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="followers-tab" data-bs-toggle="tab" data-bs-target="#followers" type="button" role="tab" aria-controls="followers" aria-selected="false">Followers</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="rankings-tab" data-bs-toggle="tab" data-bs-target="#rankings" type="button" role="tab" aria-controls="rankings" aria-selected="false">Rankings</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="profile-settings-tab" data-bs-toggle="tab" data-bs-target="#profile-settings" type="button" role="tab" aria-controls="profile-settings" aria-selected="false">Profile & Settings</button>
                </li>
            </ul>
        </div>
        
        <section class="hero-section">
            <div class="container">
                <div class="row">
                    <div class="col-12 hero-content">
                        <div class="text-center mb-4 pt-4">
                            <img src="<?php echo get_template_directory_uri(); ?>/images/logo-hq.png" alt="Influencer HQ" class="logo-hq mb-4">
                            <h1 class="display-4 fw-bold text-gradient mb-3">Your Influencer Portal (HQ²)</h1>
                            <p class="lead text-light-gray mb-3">Welcome to HQ², your private influencer command center.</p>
                            <p class="lead text-light-gray">Here you can track your influence, lead your community, start Challenges, and see your Genius network grow in real time.</p>
                        </div>
                        
                        <!-- Tab Content -->
                        <div class="tab-content" id="hqTabContent">
                            
                            <!-- SECTION 1: Dashboard -->
                            <div class="tab-pane fade show active" id="dashboard" role="tabpanel" aria-labelledby="dashboard-tab">
                                <div class="text-start" style="max-width: 1000px; margin: 0 auto; line-height: 1.6;">
                                    <h2 class="text-22pt fw-bold text-yellow mb-4">Dashboard</h2>
                                    
                                    <?php if (isset($_GET['welcome']) && $_GET['welcome'] === 'true') : ?>
                                        <h2 class="text-22pt fw-bold text-yellow mb-4 text-center">🎉 WELCOME TO YOUR HQ!</h2>
                                    <?php endif; ?>
                                    
                                    <?php if (is_user_logged_in()) : 
                                        $current_user = wp_get_current_user();
                                        $user_id = $current_user->ID;
                                        
                                        // Get all user meta
                                        $communication_methods = get_user_meta($user_id, 'communication_methods', true);
                                        $preferred_communication = get_user_meta($user_id, 'preferred_communication', true);
                                        $communication_username = get_user_meta($user_id, 'communication_username', true);
                                        $challenge_type = get_user_meta($user_id, 'challenge_type', true);
                                        $registration_date = get_user_meta($user_id, 'registration_date', true);
                                        $email_verified = get_user_meta($user_id, 'email_verified', true);
                                    ?>
                                    
                                    <!-- DEV: User Meta Debug Section -->
                                    <div style="background: rgba(255, 215, 0, 0.1); border: 2px solid rgba(255, 215, 0, 0.3); border-radius: 10px; padding: 20px; margin-bottom: 30px;">
                                        <h3 class="text-18pt fw-bold text-yellow mb-3">🔧 DEV: User Registration Data</h3>
                                        <div class="fs-6 text-light-gray">
                                            <p><strong>User ID:</strong> <?php echo $user_id; ?></p>
                                            <p><strong>Username:</strong> <?php echo $current_user->user_login; ?></p>
                                            <p><strong>Email:</strong> <?php echo $current_user->user_email; ?></p>
                                            <p><strong>Role:</strong> <?php echo implode(', ', $current_user->roles); ?></p>
                                            <p><strong>Registration Date:</strong> <?php echo $registration_date ? $registration_date : 'Not set'; ?></p>
                                            <p><strong>Email Verified:</strong> <?php echo $email_verified ? 'Yes' : 'No'; ?></p>
                                            <hr style="border-color: rgba(255, 215, 0, 0.2); margin: 15px 0;">
                                            <p><strong>Communication Methods:</strong></p>
                                            <?php if (!empty($communication_methods) && is_array($communication_methods)) : ?>
                                                <ul style="margin-left: 20px;">
                                                    <?php foreach ($communication_methods as $method => $value) : ?>
                                                        <li><strong><?php echo ucfirst($method); ?>:</strong> <?php echo esc_html($value); ?></li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            <?php else : ?>
                                                <p style="margin-left: 20px;">None set</p>
                                            <?php endif; ?>
                                            <p><strong>Preferred Communication:</strong> <?php echo $preferred_communication ? ucfirst($preferred_communication) : 'Not set'; ?></p>
                                            <p><strong>Communication Username:</strong> <?php echo $communication_username ? esc_html($communication_username) : 'Not set'; ?></p>
                                            <p><strong>Challenge Type:</strong> <?php echo $challenge_type ? ($challenge_type === 'weekend_world' ? 'Weekend World Challenge' : 'Private Challenge') : 'Not set'; ?></p>
                                        </div>
                                    </div>
                                    
                                    <?php endif; ?>
                                    
                                    <hr class="section-divider text-light-gray mb-5">
                                    
                                    <h3 class="text-20pt fw-bold text-yellow mb-4">This Week at a Glance</h3>
                                    <ul class="fs-5 text-light-gray mb-5">
                                        <li class="mb-2">New followers</li>
                                        <li class="mb-2">New L1 referrals</li>
                                        <li class="mb-2">New L2 referrals</li>
                                        <li class="mb-2">New L3 referrals</li>
                                        <li class="mb-2">Network activity</li>
                                        <li class="mb-2">Streams watched</li>
                                    </ul>
                                    
                                    <hr class="section-divider text-light-gray mb-5">
                                    
                                    <h3 class="text-20pt fw-bold text-yellow mb-4">Your Challenge Status</h3>
                                    <ul class="fs-5 text-light-gray mb-5">
                                        <li class="mb-2">Current Challenge</li>
                                        <li class="mb-2">Time remaining</li>
                                        <li class="mb-2">Contests completed</li>
                                        <li class="mb-2">Your current ranking</li>
                                    </ul>
                                    
                                    <hr class="section-divider text-light-gray mb-5">
                                    
                                    <h3 class="text-20pt fw-bold text-yellow mb-4">Your Scores</h3>
                                    <ul class="fs-5 text-light-gray mb-5">
                                        <li class="mb-2">This Week's Score</li>
                                        <li class="mb-2">Last Week's Score</li>
                                    </ul>
                                    
                                    <hr class="section-divider text-light-gray mb-5">
                                    
                                    <h3 class="text-20pt fw-bold text-yellow mb-4">Quick Actions</h3>
                                    <ul class="fs-5 text-light-gray mb-5">
                                        <li class="mb-2">Start a Challenge</li>
                                        <li class="mb-2">View Genius Network</li>
                                        <li class="mb-2">Invite Followers</li>
                                    </ul>
                                    
                                </div>
                            </div>
                            
                            <!-- SECTION 2: Challenges -->
                            <div class="tab-pane fade" id="challenges" role="tabpanel" aria-labelledby="challenges-tab">
                                <div class="text-start" style="max-width: 1000px; margin: 0 auto; line-height: 1.6;">
                                    <h2 class="text-22pt fw-bold text-yellow mb-4">Challenges</h2>
                                    
                                    <hr class="section-divider text-light-gray mb-5">
                                    <?php get_template_part('template-parts/challenges'); ?>
                                    <h3 class="text-20pt fw-bold text-yellow mb-4">Weekend World Challenge</h3>
                                    <ul class="fs-5 text-light-gray mb-5">
                                        <li class="mb-2">Enter</li>
                                        <li class="mb-2">Rules</li>
                                        <li class="mb-2">Leaderboard</li>
                                        <li class="mb-2">Current ranking</li>
                                    </ul>
                                    
                                    <hr class="section-divider text-light-gray mb-5">
                                    
                                    <h3 class="text-20pt fw-bold text-yellow mb-4">Private Challenge (1–48 hours)</h3>
                                    <ul class="fs-5 text-light-gray mb-5">
                                        <li class="mb-2">Create Challenge</li>
                                        <li class="mb-2">Set duration</li>
                                        <li class="mb-2">Limit to your followers</li>
                                        <li class="mb-2">Enable streaming</li>
                                        <li class="mb-2">Track accepts vs plays</li>
                                        <li class="mb-2">Show "No data yet" if no participation</li>
                                    </ul>
                                    
                                    <hr class="section-divider text-light-gray mb-5">
                                    
                                    <h3 class="text-20pt fw-bold text-yellow mb-4">Influencer-to-Influencer Challenge</h3>
                                    <ul class="fs-5 text-light-gray mb-5">
                                        <li class="mb-2">Challenge another influencer</li>
                                        <li class="mb-2">Show both communities</li>
                                        <li class="mb-2">Dual scoring</li>
                                        <li class="mb-2">Winner result</li>
                                    </ul>
                                    
                                </div>
                            </div>
                            
                            <!-- SECTION 3: Genius Network -->
                            <div class="tab-pane fade" id="genius-network" role="tabpanel" aria-labelledby="genius-network-tab">
                                <div class="text-start" style="max-width: 1000px; margin: 0 auto; line-height: 1.6;">
                                    <h2 class="text-22pt fw-bold text-yellow mb-4">Genius Network</h2>
                                    
                                    <hr class="section-divider text-light-gray mb-5">
                                    <!-- <div class="genius-template-part"> -->
                                    <?php get_template_part('template-parts/genious-referrals'); ?>
                                    <!-- </div> -->
                                    <h3 class="text-20pt fw-bold text-yellow mb-4">Level 1 Referrals</h3>
                                    <div class="callout-box mb-5">
                                        <p class="fs-5 text-light-gray mb-0">(list)</p>
                                    </div>
                                    
                                    <hr class="section-divider text-light-gray mb-5">
                                    
                                    <h3 class="text-20pt fw-bold text-yellow mb-4">Level 2 Referrals</h3>
                                    <div class="callout-box mb-5">
                                        <p class="fs-5 text-light-gray mb-0">(list)</p>
                                    </div>
                                    
                                    <hr class="section-divider text-light-gray mb-5">
                                    
                                    <h3 class="text-20pt fw-bold text-yellow mb-4">Level 3 Referrals</h3>
                                    <div class="callout-box mb-5">
                                        <p class="fs-5 text-light-gray mb-0">(list)</p>
                                    </div>
                                    
                                    <hr class="section-divider text-light-gray mb-5">
                                    
                                    <h3 class="text-20pt fw-bold text-yellow mb-4">Streaming Bonus</h3>
                                    <div class="callout-box mb-5">
                                        <p class="fs-5 text-light-gray mb-0">(stream contribution)</p>
                                    </div>
                                    
                                    <hr class="section-divider text-light-gray mb-5">
                                    
                                    <h3 class="text-20pt fw-bold text-yellow mb-4">Genius Tools</h3>
                                    <ul class="fs-5 text-light-gray mb-5">
                                        <li class="mb-2">Copy referral link</li>
                                        <li class="mb-2">Download QR code</li>
                                        <li class="mb-2">Invite Followers</li>
                                    </ul>
                                    
                                </div>
                            </div>
                            
                            <!-- SECTION 4: Rankings -->
                            <div class="tab-pane fade" id="rankings" role="tabpanel" aria-labelledby="rankings-tab">
                                <div class="text-start" style="max-width: 1000px; margin: 0 auto; line-height: 1.6;">
                                    <h2 class="text-22pt fw-bold text-yellow mb-4">Rankings</h2>
                                    
                                    <hr class="section-divider text-light-gray mb-5">
                                    
                                    <h3 class="text-20pt fw-bold text-yellow mb-4">This Week</h3>
                                    <ul class="fs-5 text-light-gray mb-5">
                                        <li class="mb-2">Leaderboard</li>
                                        <li class="mb-2">Score</li>
                                        <li class="mb-2">Rank</li>
                                        <li class="mb-2">Movement</li>
                                    </ul>
                                    
                                    <hr class="section-divider text-light-gray mb-5">
                                    
                                    <h3 class="text-20pt fw-bold text-yellow mb-4">Last Week</h3>
                                    <ul class="fs-5 text-light-gray mb-5">
                                        <li class="mb-2">Final scores</li>
                                        <li class="mb-2">Results archive</li>
                                    </ul>
                                    
                                    <hr class="section-divider text-light-gray mb-5">
                                    
                                    <h3 class="text-20pt fw-bold text-yellow mb-4">Challenge Results</h3>
                                    <ul class="fs-5 text-light-gray mb-5">
                                        <li class="mb-2">Past challenge performance</li>
                                    </ul>
                                    
                                </div>
                            </div>
                            
                            <!-- SECTION 5: Followers -->
                            <div class="tab-pane fade" id="followers" role="tabpanel" aria-labelledby="followers-tab">
                                <div class="text-start" style="max-width: 1000px; margin: 0 auto; line-height: 1.6;">
                                    <h2 class="text-22pt fw-bold text-yellow mb-4">Followers</h2>
                                    
                                    <hr class="section-divider text-light-gray mb-5">
                                    
                                    <h3 class="text-20pt fw-bold text-yellow mb-4">My Followers</h3>
                                    <ul class="fs-5 text-light-gray mb-5">
                                        <li class="mb-2">Names</li>
                                        <li class="mb-2">Join date</li>
                                        <li class="mb-2">Activity status</li>
                                        <li class="mb-2">Participation count</li>
                                    </ul>
                                    
                                    <hr class="section-divider text-light-gray mb-5">
                                    
                                    <h3 class="text-20pt fw-bold text-yellow mb-4">Activity Feed</h3>
                                    <div class="callout-box mb-5">
                                        <p class="fs-5 text-light-gray mb-0">(recent follower actions)</p>
                                    </div>
                                    
                                    <hr class="section-divider text-light-gray mb-5">
                                    
                                    <h3 class="text-20pt fw-bold text-yellow mb-4">Top Contributors</h3>
                                    <div class="callout-box mb-5">
                                        <p class="fs-5 text-light-gray mb-0">(list)</p>
                                    </div>
                                    
                                </div>
                            </div>
                            
                            <!-- SECTION 6: Profile & Settings -->
                            <div class="tab-pane fade" id="profile-settings" role="tabpanel" aria-labelledby="profile-settings-tab">
                                <div class="text-start" style="max-width: 1000px; margin: 0 auto; line-height: 1.6;">
                                    <h2 class="text-22pt fw-bold text-yellow mb-4">Profile & Settings</h2>
                                    
                                    <hr class="section-divider text-light-gray mb-5">
                                    
                                    <h3 class="text-20pt fw-bold text-yellow mb-4">Profile</h3>
                                    <ul class="fs-5 text-light-gray mb-5">
                                        <li class="mb-2">Photo</li>
                                        <li class="mb-2">Display name</li>
                                        <li class="mb-2">Timezone</li>
                                    </ul>
                                    
                                    <hr class="section-divider text-light-gray mb-5">
                                    
                                    <h3 class="text-20pt fw-bold text-yellow mb-4">Communication Methods</h3>
                                    <div class="callout-box mb-5">
                                        <p class="fs-5 text-light-gray mb-0">(Editable: WhatsApp, LINE, Telegram, WeChat)</p>
                                    </div>
                                    
                                    <hr class="section-divider text-light-gray mb-5">
                                    
                                    <h3 class="text-20pt fw-bold text-yellow mb-4">Security</h3>
                                    <ul class="fs-5 text-light-gray mb-5">
                                        <li class="mb-2">Verified email status</li>
                                        <li class="mb-2">Option to re-send verification</li>
                                    </ul>
                                    
                                    <hr class="section-divider text-light-gray mb-5">
                                    
                                    <h3 class="text-20pt fw-bold text-yellow mb-4">Help / FAQ</h3>
                                    <div class="callout-box mb-5">
                                        <p class="fs-5 text-light-gray mb-0">(help resources)</p>
                                    </div>
                                    
                                    <hr class="section-divider text-light-gray mb-5">
                                    
                                    <h3 class="text-20pt fw-bold text-yellow mb-4">Logout</h3>
                                    <div class="mb-5">
                                        <button class="btn btn-outline-warning btn-lg px-4 py-2">Logout</button>
                                    </div>
                                    
                                </div>
                            </div>
                            
                        </div>
                        <!-- End Tab Content -->
    
                    </div>
                </div>
            </div>
        </section>
    </main><!-- #main -->
<style>
    /* Modern CSS Variables */
    :root {
        --accent-red: #d9222a;
        --accent-yellow: #d9222a;
        --accent-gold: #d9222a;
        --light-gray: #e0e0e0;
        --dark-bg: #0a0a0a;
        --card-bg: rgba(26, 26, 26, 0.95);
        --border-color: rgba(217, 34, 42, 0.2);
    }

    /* Page Layout */
    body {
        background: linear-gradient(135deg, var(--dark-bg) 0%, #1a1a1a 100%);
        min-height: 100vh;
    }
    
    .page {
        margin: 0;
        min-height: 100vh;
    }

    /* Hero Section */
    .hero-section {
        background: var(--dark-bg);
        min-height: 100vh;
        padding: 2rem 0;
    }

    .hero-content {
        padding: 1rem 0;
    }
    
    /* Logo Styling */
    .logo-hq {
        height: auto;
    }
    
    /* Modern Text Gradient */
    .text-gradient {
        background: linear-gradient(135deg, var(--accent-gold) 0%, var(--accent-yellow) 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }
    /* Typography */
    body, h1, h2, h3, h4, h5, h6, p, label, input, select, textarea {
        font-family: 'Source Sans Pro', -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif !important;
    }
    
    h1, h2, h3 {
        color: #fff;
    }
    
    p, label {
        color: var(--light-gray);
    }
    
    /* Color Utilities */
    .text-yellow {
        color: var(--accent-yellow) !important;
    }
    
    .text-light-gray {
        color: var(--light-gray) !important;
    }
    
    /* Form Elements */
    .form-select {
        --bs-form-select-bg-img: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23fff' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m2 5 6 6 6-6'/%3e%3c/svg%3e");
    }
    
    .form-control, .form-select {
        background: var(--card-bg);
        border: 2px solid var(--accent-gold);
        color: #fff;
        border-radius: 10px;
        transition: all 0.3s ease;
    }
    
    .form-control:focus, .form-select:focus {
        background: var(--card-bg);
        border-color: var(--accent-yellow);
        color: #fff;
        box-shadow: 0 0 0 0.25rem rgba(255, 149, 0, 0.25);
    }
    
    .form-control::placeholder,
    .form-select::placeholder,
    textarea::placeholder {
        color: #fff !important;
        opacity: 0.7;
    }
    /* Content Cards */
    .challenge-builder {
        background: var(--card-bg);
        border: 1px solid var(--border-color);
        border-radius: 15px;
        padding: 2rem;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
        transition: all 0.3s ease;
    }
    
    .challenge-builder:hover {
        border-color: rgba(255, 149, 0, 0.4);
        box-shadow: 0 15px 50px rgba(255, 149, 0, 0.2);
    }
    /* Mobile Responsive */
    @media (max-width: 767px) {
        .page {
            min-height: 100vh;
            overflow: auto;
        }

        .hero-content {
            padding: 0.5rem 0;
        }
        
        .hero-section {
            overflow: auto;
            padding: 1rem 0;
        }
        
        h1 {
            font-size: 2rem;
        }
        
        .challenge-builder {
            padding: 1.5rem;
        }
    }
    
    /* Buttons */
    .btn-warning {
        background: linear-gradient(135deg, var(--accent-gold) 0%, var(--accent-yellow) 100%);
        border: none;
        color: #000;
        font-weight: 600;
        padding: 0.75rem 2rem;
        border-radius: 50px;
        transition: all 0.3s ease;
        box-shadow: 0 5px 20px rgba(255, 193, 7, 0.4);
    }
    
    .btn-warning:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 30px rgba(255, 193, 7, 0.6);
        background: linear-gradient(135deg, var(--accent-yellow) 0%, var(--accent-gold) 100%);
    }
    
    .btn-outline-warning {
        border: 2px solid var(--accent-gold);
        color: var(--accent-gold);
        background: transparent;
        border-radius: 50px;
        padding: 0.5rem 1.5rem;
        transition: all 0.3s ease;
    }
    
    .btn-outline-warning:hover {
        background: var(--accent-gold);
        color: #000;
        transform: translateY(-2px);
    }

    /* Alert Styling */
    .alert-warning {
        background: rgba(255, 149, 0, 0.15);
        border: 1px solid rgba(255, 149, 0, 0.5);
        color: var(--light-gray);
        border-radius: 10px;
        padding: 1rem 1.25rem;
    }
    
    /* Section Dividers */
    .section-divider {
        border-color: rgba(255, 149, 0, 0.3);
        margin: 2rem 0;
    }
    
    /* Callout Boxes */
    .callout-box {
        background: var(--card-bg);
        border-left: 4px solid var(--accent-yellow);
        border-radius: 10px;
        padding: 1.25rem;
        margin: 1.5rem 0;
    }
    
    .pro-tip {
        background: var(--card-bg);
        border: 1px solid var(--border-color);
        border-radius: 10px;
        padding: 1.25rem;
        margin: 1.5rem 0;
        font-style: italic;
    }
    
    /* Fixed Left Sidebar Tabs */
    .hq-sidebar-tabs {
        position: fixed;
        left: 0;
        top: 80px;
        width: 220px;
        height: calc(100vh - 80px);
        background: var(--card-bg);
        border-right: 2px solid var(--border-color);
        padding: 2rem 0;
        z-index: 100;
        overflow-y: auto;
    }
    
    .hq-sidebar-tabs .nav-pills {
        padding: 0 1rem;
    }
    
    .hq-sidebar-tabs .nav-item {
        margin-bottom: 0.5rem;
    }
    
    .hq-sidebar-tabs .nav-link {
        color: var(--light-gray);
        font-size: 1rem;
        font-weight: 600;
        padding: 12px 16px;
        border: none;
        background: transparent;
        transition: all 0.3s ease;
        border-radius: 8px;
        text-align: left;
        width: 100%;
        border-left: 3px solid transparent;
    }
    
    .hq-sidebar-tabs .nav-link:hover {
        color: #fff;
        background: rgba(217, 34, 42, 0.15);
        border-left-color: rgba(217, 34, 42, 0.5);
    }
    
    .hq-sidebar-tabs .nav-link.active {
        color: var(--accent-yellow);
        background: rgba(217, 34, 42, 0.2);
        border-left-color: var(--accent-yellow);
        font-weight: 700;
    }
    
    /* Adjust main content for sidebar */
    .hero-section {
        margin-left: 220px;
    }
    
    /* Tab content styling */
    .tab-content {
        padding-top: 0;
    }
    
    /* Mobile Hamburger Menu Button */
    .mobile-menu-toggle {
        display: none;
        position: fixed;
        top: 20px;
        left: 20px;
        z-index: 200;
        background: var(--accent-yellow);
        border: none;
        border-radius: 8px;
        width: 50px;
        height: 50px;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        gap: 6px;
        padding: 0;
        cursor: pointer;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        transition: all 0.3s ease;
    }
    
    .mobile-menu-toggle:hover {
        background: #e02030;
        transform: scale(1.05);
    }
    
    .hamburger-line {
        display: block;
        width: 30px;
        height: 3px;
        background: #000;
        border-radius: 2px;
        transition: all 0.3s ease;
    }
    
    .mobile-menu-toggle.active .hamburger-line:nth-child(1) {
        transform: translateY(9px) rotate(45deg);
    }
    
    .mobile-menu-toggle.active .hamburger-line:nth-child(2) {
        opacity: 0;
    }
    
    .mobile-menu-toggle.active .hamburger-line:nth-child(3) {
        transform: translateY(-9px) rotate(-45deg);
    }
    
    /* Mobile responsive */
    @media (max-width: 991px) {
        .mobile-menu-toggle {
            display: flex;
        }
        
        .hq-sidebar-tabs {
            transform: translateX(-100%);
            transition: transform 0.3s ease;
            z-index: 150;
        }
        
        .hq-sidebar-tabs.show {
            transform: translateX(0);
        }
        
        .hero-section {
            margin-left: 0;
        }
    }

</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Mobile menu toggle
    const menuToggle = document.querySelector('.mobile-menu-toggle');
    const sidebar = document.querySelector('.hq-sidebar-tabs');
    const navLinks = document.querySelectorAll('.hq-sidebar-tabs .nav-link');
    
    if (menuToggle && sidebar) {
        menuToggle.addEventListener('click', function() {
            this.classList.toggle('active');
            sidebar.classList.toggle('show');
        });
        
        // Close menu when clicking a nav link on mobile
        navLinks.forEach(link => {
            link.addEventListener('click', function() {
                if (window.innerWidth <= 991) {
                    menuToggle.classList.remove('active');
                    sidebar.classList.remove('show');
                }
            });
        });
        
        // Close menu when clicking outside on mobile
        document.addEventListener('click', function(event) {
            if (window.innerWidth <= 991) {
                if (!sidebar.contains(event.target) && !menuToggle.contains(event.target)) {
                    menuToggle.classList.remove('active');
                    sidebar.classList.remove('show');
                }
            }
        });
    }
});
</script>

<?php
get_footer();
