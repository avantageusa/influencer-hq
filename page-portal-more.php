<?php
/**
 * Template Name: Portal More
 * Description: A custom template for the More section.
 *
 * @package Avantage_Baccarat
 */
get_header();

// Load styles before content to prevent FOUC
get_template_part( 'template-parts/portal-styles' );
?>

    <main id="primary" class="site-main">
        
        <?php get_template_part( 'template-parts/portal-header' ); ?>
        
        <div class="container py-2 the-gradient" id="portal-content" style="max-width: 1024px; padding-left: 20px; padding-right: 20px;">

            <!-- More Content -->
            <div class="equity-page-content">
                
                <!-- More Header -->
                <div class="equity-header">
                    <div class="equity-header-top">
                        <img src="<?php echo get_template_directory_uri(); ?>/images/cross.png" alt="More Icon" class="equity-icon">
                        <h1 class="equity-title">More</h1>
                    </div>
                </div>
                <div class="equity-intro">
                    <p>Introduction statement about the More section</p>
                </div>

                <!-- Performance Accordion -->
                <div class="accordion-gradient-container" style="border: 1px solid #b8972f; border-radius: 8px; padding: 20px;">
                    <h2 style="text-align:center; font-variant: small-caps; color: #ffffff; font-size: 1.3rem; letter-spacing: 0.05em; margin-bottom: 16px;">Performance</h2>
                    <div class="accordion custom-accordion equity-accordion" id="morePerformanceAccordion">

                        <div class="accordion-item mb-3">
                            <h2 class="accordion-header" id="headingFollowersPlaying">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFollowersPlaying" aria-expanded="false" aria-controls="collapseFollowersPlaying">
                                    <span class="question-text">How do I know which followers are actually playing vs. just signing up?</span>
                                </button>
                            </h2>
                            <div id="collapseFollowersPlaying" class="accordion-collapse collapse" aria-labelledby="headingFollowersPlaying" data-bs-parent="#morePerformanceAccordion">
                                <div class="accordion-body">
                                    <p>Content coming soon.</p>
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item mb-3">
                            <h2 class="accordion-header" id="headingReferrals">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseReferrals" aria-expanded="false" aria-controls="collapseReferrals">
                                    <span class="question-text">Can I see which followers referred other people, and how many referrals they've made?</span>
                                </button>
                            </h2>
                            <div id="collapseReferrals" class="accordion-collapse collapse" aria-labelledby="headingReferrals" data-bs-parent="#morePerformanceAccordion">
                                <div class="accordion-body">
                                    <p>Content coming soon.</p>
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item mb-3">
                            <h2 class="accordion-header" id="headingEveryonePlaying">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseEveryonePlaying" aria-expanded="false" aria-controls="collapseEveryonePlaying">
                                    <span class="question-text">How much everyone involved is playing? How much equity I've earned from all their collective play?</span>
                                </button>
                            </h2>
                            <div id="collapseEveryonePlaying" class="accordion-collapse collapse" aria-labelledby="headingEveryonePlaying" data-bs-parent="#morePerformanceAccordion">
                                <div class="accordion-body">
                                    <p>Content coming soon.</p>
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item mb-3">
                            <h2 class="accordion-header" id="headingKickEquity">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseKickEquity" aria-expanded="false" aria-controls="collapseKickEquity">
                                    <span class="question-text">How do I see how much Equity I've earned from people watching on KICK or the World Network?</span>
                                </button>
                            </h2>
                            <div id="collapseKickEquity" class="accordion-collapse collapse" aria-labelledby="headingKickEquity" data-bs-parent="#morePerformanceAccordion">
                                <div class="accordion-body">
                                    <p>Content coming soon.</p>
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item mb-3">
                            <h2 class="accordion-header" id="headingActiveTime">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseActiveTime" aria-expanded="false" aria-controls="collapseActiveTime">
                                    <span class="question-text">How do I know which time of day my followers are most active?</span>
                                </button>
                            </h2>
                            <div id="collapseActiveTime" class="accordion-collapse collapse" aria-labelledby="headingActiveTime" data-bs-parent="#morePerformanceAccordion">
                                <div class="accordion-body">
                                    <p>Content coming soon.</p>
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item mb-3">
                            <h2 class="accordion-header" id="headingWeekVsWeek">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseWeekVsWeek" aria-expanded="false" aria-controls="collapseWeekVsWeek">
                                    <span class="question-text">Can I compare my performance this week vs. last week?</span>
                                </button>
                            </h2>
                            <div id="collapseWeekVsWeek" class="accordion-collapse collapse" aria-labelledby="headingWeekVsWeek" data-bs-parent="#morePerformanceAccordion">
                                <div class="accordion-body">
                                    <p>Content coming soon.</p>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

            </div><!-- /.equity-page-content -->

        </div>
        
        <!-- Fixed Footer Links -->
        <div class="footer-links-fixed">
            <a href="#" class="footer-link">Terms</a>
            <span class="footer-separator">|</span>
            <a href="#" class="footer-link">Privacy</a>
        </div>
    </main><!-- #main -->

<?php 
get_template_part( 'template-parts/portal-scripts' );
get_footer();
?>
