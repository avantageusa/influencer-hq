<?php
/**
 * Template Name: Thank you page
 * Description: A custom template for displaying the thank you page.
 * This template is used to render the thank you page content and layout.
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package Avantage_Baccarat
 */

get_header();
?>

    <main id="primary" class="site-main">
        <section class="hero-section">
            <div class="container">
                <div class="row">
                    <div class="col-12 hero-content">
                        <!-- Page Content Area -->
                        <div class="tab-content hero-tab-content text-center">
                            <!-- Main Title -->
                            <div class="mb-5">
                                <h1 class="text-28pt fw-bold text-yellow mb-3">You're In. Welcome to the Movement.</h1>
                            </div>

                            <!-- Subtitle -->
                            <div class="mb-5">
                                <h2 class="text-22pt fw-bold text-light-gray">Your influence just took its first step onto the global stage.</h2>
                            </div>

                            <!-- Status Messages -->
                            <div class="text-start mb-5" style="max-width: 700px; margin: 0 auto;">
                                <ul class="fs-5 text-light-gray" style="list-style: none; padding-left: 0;">
                                    <li class="mb-3 d-flex align-items-start">
                                        <span class="text-yellow me-3 fw-bold">•</span>
                                        <span><strong class="text-yellow">If you chose Count Me In</strong> → your equity position is now being secured.</span>
                                    </li>
                                    <li class="mb-3 d-flex align-items-start">
                                        <span class="text-yellow me-3 fw-bold">•</span>
                                        <span><strong class="text-yellow">If you scheduled a Challenge Event</strong> → our team will contact you to confirm the time and help you rally your followers.</span>
                                    </li>
                                    <li class="mb-3 d-flex align-items-start">
                                        <span class="text-yellow me-3 fw-bold">•</span>
                                        <span><strong class="text-yellow">If you scheduled a Challenge Match</strong> → we'll connect you with another Influencer so your communities can compete head-to-head.</span>
                                    </li>
                                </ul>
                            </div>

                            <!-- Main Body Text -->
                            <div class="mb-5" style="max-width: 800px; margin: 0 auto;">
                                <p class="fs-5 text-light-gray mb-4 line-height-relaxed">This isn't just a sign-up. It's the beginning of your role in the <strong class="text-yellow">Avantage Baccarat World Championship</strong> — modeled after the World Series of Poker, rooted in the 400-year prestige of Baccarat, and expanding to all of the world's most popular games.</p>
                            </div>

                            <!-- Closing Statement -->
                            <div class="mt-5">
                                <h3 class="text-24pt fw-bold text-yellow">Your influence. Their belief. One global stage.</h3>
                            </div>
                        </div>
                    </div>
                    <?php if (!empty($_POST)): ?>
                            <!-- POST Data Display Section -->
                            <div class="mb-5" style="max-width: 800px; margin: 0 auto;">
                                <div class="alert alert-info" style="background: rgba(255, 149, 0, 0.15); border: 2px solid rgba(255, 149, 0, 0.5); border-radius: 10px; padding: 20px;">
                                    <h3 class="text-18pt fw-bold text-yellow mb-3">Form Submission Data:</h3>
                                    <div class="text-start">
                                        <?php if (isset($_POST['influencer_email'])): ?>
                                            <p class="fs-6 text-light-gray mb-2"><strong class="text-yellow">Email:</strong> <?php echo htmlspecialchars($_POST['influencer_email']); ?></p>
                                        <?php endif; ?>
                                        <?php if (isset($_POST['influencer_name'])): ?>
                                            <p class="fs-6 text-light-gray mb-2"><strong class="text-yellow">Name:</strong> <?php echo htmlspecialchars($_POST['influencer_name']); ?></p>
                                        <?php endif; ?>
                                        
                                        <?php if (isset($_POST['count_me_in'])): ?>
                                            <p class="fs-6 text-light-gray mb-2"><strong class="text-yellow">✓ Count Me In as an Influencer:</strong> Selected</p>
                                        <?php endif; ?>
                                        
                                        <?php if (isset($_POST['schedule_challenge_event'])): ?>
                                            <p class="fs-6 text-light-gray mb-2"><strong class="text-yellow">✓ Schedule a Challenge Event:</strong> Selected</p>
                                        <?php endif; ?>
                                        
                                        <?php if (isset($_POST['schedule_match'])): ?>
                                            <p class="fs-6 text-light-gray mb-2"><strong class="text-yellow">✓ Schedule a Challenge Match:</strong> Selected</p>
                                        <?php endif; ?>
                                        
                                        <hr style="border-color: rgba(255, 149, 0, 0.3); margin: 15px 0;">
                                        <p class="fs-6 text-light-gray mb-0"><strong class="text-yellow">Submission Time:</strong> <?php echo date('F j, Y \a\t g:i A T'); ?></p>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                </div>
            </div>
        </section>
    </main><!-- #main -->
<style>
    /* Basic font family for all elements */
    body, h1, h2, h3, p, ul, li, strong, span, div {
        font-family: 'Source Sans Pro', Arial, Helvetica, sans-serif !important;
    }
    
    /* Custom color variables */
    :root {
        --accent-yellow: rgb(255, 149, 0);
        --light-gray: rgb(224, 224, 224);
    }
    
    /* Basic body styling */
    body {
        background-color: #000000;
        color: rgb(255, 255, 252);
    }
    
    /* Hero Section - Full viewport background */
    .hero-section {
        position: relative;
        background: url('<?php echo get_template_directory_uri(); ?>/images/hero-bgnd.jpg') top center no-repeat;
        background-size: cover;
        background-attachment: fixed;
        overflow: hidden;
        min-height: 100vh;
        display: flex;
        flex-direction: column;
    }

    .hero-section::before {
        content: "";
        position: absolute;
        inset: 0;
        background: rgba(0,0,0,0.6);
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
    }

    .hero-section .row {
        flex: 1;
        display: flex;
    }

    .hero-content {
        padding: 40px 20px;
        flex: 1;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }
    
    /* Tab Content - Glassmorphism effect */
    .hero-tab-content {
        background: rgba(33, 37, 41, 0.15);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 15px;
        padding: 30px;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
    }
    
    /* Color utility classes */
    .text-yellow {
        color: var(--accent-yellow) !important;
    }
    
    .text-light-gray {
        color: var(--light-gray) !important;
    }
    
    /* Text Size Classes */
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
    
    /* Spacing utilities */
    .mb-3 {
        margin-bottom: 1rem !important;
    }
    
    .mb-4 {
        margin-bottom: 1.5rem !important;
    }
    
    .mb-5 {
        margin-bottom: 3rem !important;
    }
    
    .mt-5 {
        margin-top: 3rem !important;
    }
    
    .me-3 {
        margin-right: 1rem !important;
    }
    
    /* Bootstrap utility classes */
    .fw-bold {
        font-weight: bold !important;
    }
    
    .text-center {
        text-align: center !important;
    }
    
    .text-start {
        text-align: left !important;
    }
    
    .fs-5 {
        font-size: 1.25rem !important;
    }
    
    .d-flex {
        display: flex !important;
    }
    
    .align-items-start {
        align-items: flex-start !important;
    }
    
    /* List styling reset */
    ul {
        margin: 0;
        padding: 0;
    }
    
    /* Responsive adjustments */
    @media (max-width: 767px) {
        .hero-content {
            padding: 20px 15px;
        }
        
        .hero-tab-content {
            padding: 20px;
            border-radius: 12px;
        }
        
        .text-28pt {
            font-size: 24pt !important;
        }
        
        .text-22pt {
            font-size: 18pt !important;
        }
        
        .text-24pt {
            font-size: 20pt !important;
        }
    }
</style>
<?php
get_footer();
