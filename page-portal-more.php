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
<style>
/* More page — parent accordion categories */
.more-parent-accordion {
    display: flex;
    flex-direction: column;
    gap: 0;
}

.more-parent-item {
    background: transparent;
    border: none !important;
}

.more-parent-item > .accordion-header .more-parent-button {
    background-color: #2a2b37;
    border: 1px solid #b8972f;
    border-radius: 4px !important;
    color: #ffffff;
    font-family: 'Cinzel', serif;
    font-weight: 700;
    font-size: 1.1rem;
    letter-spacing: 0.05em;
    text-align: center;
    justify-content: center;
    padding: 16px 20px;
    box-shadow: 0 3.55px 3.55px rgba(0,0,0,0.25);
    transition: background-color 0.2s ease;
}

.more-parent-item > .accordion-header .more-parent-button:not(.collapsed) {
    background-color: #2a2b37;
    color: #ffffff;
    box-shadow: 0 3.55px 3.55px rgba(0,0,0,0.25);
}

/* Hide Bootstrap's default caret on parent buttons */
.more-parent-button::after {
    display: none !important;
}

.more-parent-body {
    background-color: #2a2b37;
    border: 1px solid #b8972f;
    border-top: none;
    border-radius: 0 0 4px 4px;
    padding: 12px;
}

/* Remove top border radius from parent button when expanded */
.more-parent-item > .accordion-header .more-parent-button:not(.collapsed) {
    border-radius: 4px 4px 0 0 !important;
}
</style>

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
                    <p>You’re one insight away from changing everything.</p>
                </div>

                <!-- Nested Accordion: 5 Parent Categories -->
                <div class="accordion more-parent-accordion" id="moreMainAccordion">

                    <!-- PERFORMANCE -->
                    <div class="accordion-item more-parent-item mb-3">
                        <h2 class="accordion-header" id="headingPerformance">
                            <button class="accordion-button more-parent-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapsePerformance" aria-expanded="true" aria-controls="collapsePerformance">
                                Performance
                            </button>
                        </h2>
                        <div id="collapsePerformance" class="accordion-collapse collapse show" aria-labelledby="headingPerformance" data-bs-parent="#moreMainAccordion">
                            <div class="accordion-body more-parent-body">
                                <div class="accordion custom-accordion equity-accordion" id="morePerformanceAccordion">

                                    <div class="accordion-item mb-3">
                                        <h2 class="accordion-header" id="headingFollowersPlaying">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFollowersPlaying" aria-expanded="false" aria-controls="collapseFollowersPlaying">
                                                <span class="question-text">How do I know which followers are actually playing vs. just signing up?</span>
                                            </button>
                                        </h2>
                                        <div id="collapseFollowersPlaying" class="accordion-collapse collapse" aria-labelledby="headingFollowersPlaying">
                                            <div class="accordion-body">
                                                <p>Content coming soon.</p>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="accordion-item mb-3">
                                        <h2 class="accordion-header" id="headingReferralsMade">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseReferralsMade" aria-expanded="false" aria-controls="collapseReferralsMade">
                                                <span class="question-text">Can I see which followers referred other people, and how many referrals they've made?</span>
                                            </button>
                                        </h2>
                                        <div id="collapseReferralsMade" class="accordion-collapse collapse" aria-labelledby="headingReferralsMade">
                                            <div class="accordion-body">
                                                <p>Content coming soon.</p>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="accordion-item mb-3">
                                        <h2 class="accordion-header" id="headingEveryonePlaying">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseEveryonePlaying" aria-expanded="false" aria-controls="collapseEveryonePlaying">
                                                <span class="question-text">How much everyone involved is playing?</span>
                                            </button>
                                        </h2>
                                        <div id="collapseEveryonePlaying" class="accordion-collapse collapse" aria-labelledby="headingEveryonePlaying">
                                            <div class="accordion-body">
                                                <p>Content coming soon.</p>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="accordion-item mb-3">
                                        <h2 class="accordion-header" id="headingCollectiveEquity">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseCollectiveEquity" aria-expanded="false" aria-controls="collapseCollectiveEquity">
                                                <span class="question-text">How much equity I've earned from all their collective play?</span>
                                            </button>
                                        </h2>
                                        <div id="collapseCollectiveEquity" class="accordion-collapse collapse" aria-labelledby="headingCollectiveEquity">
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
                                        <div id="collapseKickEquity" class="accordion-collapse collapse" aria-labelledby="headingKickEquity">
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
                                        <div id="collapseActiveTime" class="accordion-collapse collapse" aria-labelledby="headingActiveTime">
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
                                        <div id="collapseWeekVsWeek" class="accordion-collapse collapse" aria-labelledby="headingWeekVsWeek">
                                            <div class="accordion-body">
                                                <p>Content coming soon.</p>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="accordion-item mb-3">
                                        <h2 class="accordion-header" id="headingShoutouts">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseShoutouts" aria-expanded="false" aria-controls="collapseShoutouts">
                                                <span class="question-text">How do I know if my shout-out messages are working, and which ones are working best?</span>
                                            </button>
                                        </h2>
                                        <div id="collapseShoutouts" class="accordion-collapse collapse" aria-labelledby="headingShoutouts">
                                            <div class="accordion-body">
                                                <p>Content coming soon.</p>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="accordion-item mb-3">
                                        <h2 class="accordion-header" id="headingFilterCountry">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFilterCountry" aria-expanded="false" aria-controls="collapseFilterCountry">
                                                <span class="question-text">Can I filter my performance data by country or region?</span>
                                            </button>
                                        </h2>
                                        <div id="collapseFilterCountry" class="accordion-collapse collapse" aria-labelledby="headingFilterCountry">
                                            <div class="accordion-body">
                                                <p>Content coming soon.</p>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="accordion-item mb-3">
                                        <h2 class="accordion-header" id="headingCompetitionFormat">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseCompetitionFormat" aria-expanded="false" aria-controls="collapseCompetitionFormat">
                                                <span class="question-text">Can I see which competition format drives the most play from my audience?</span>
                                            </button>
                                        </h2>
                                        <div id="collapseCompetitionFormat" class="accordion-collapse collapse" aria-labelledby="headingCompetitionFormat">
                                            <div class="accordion-body">
                                                <p>Content coming soon.</p>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="accordion-item mb-3">
                                        <h2 class="accordion-header" id="headingCommChannel">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseCommChannel" aria-expanded="false" aria-controls="collapseCommChannel">
                                                <span class="question-text">How do I know which communication channel gets the best response from my followers?</span>
                                            </button>
                                        </h2>
                                        <div id="collapseCommChannel" class="accordion-collapse collapse" aria-labelledby="headingCommChannel">
                                            <div class="accordion-body">
                                                <p>Content coming soon.</p>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="accordion-item mb-3">
                                        <h2 class="accordion-header" id="headingEquityRealTime">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseEquityRealTime" aria-expanded="false" aria-controls="collapseEquityRealTime">
                                                <span class="question-text">Can I see my equity accumulation in real time?</span>
                                            </button>
                                        </h2>
                                        <div id="collapseEquityRealTime" class="accordion-collapse collapse" aria-labelledby="headingEquityRealTime">
                                            <div class="accordion-body">
                                                <p>Content coming soon.</p>
                                            </div>
                                        </div>
                                    </div>

                                </div><!-- /#morePerformanceAccordion -->
                            </div><!-- /.more-parent-body -->
                        </div>
                    </div><!-- /PERFORMANCE -->

                    <!-- MY GAME ACCOUNT -->
                    <div class="accordion-item more-parent-item mb-3">
                        <h2 class="accordion-header" id="headingMyGameAccount">
                            <button class="accordion-button more-parent-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseMyGameAccount" aria-expanded="false" aria-controls="collapseMyGameAccount">
                                My Game Account
                            </button>
                        </h2>
                        <div id="collapseMyGameAccount" class="accordion-collapse collapse" aria-labelledby="headingMyGameAccount" data-bs-parent="#moreMainAccordion">
                            <div class="accordion-body more-parent-body">
                                <div class="accordion custom-accordion equity-accordion" id="moreGameAccountAccordion">

                                    <div class="accordion-item mb-3">
                                        <h2 class="accordion-header" id="headingWidgetWhat">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseWidgetWhat" aria-expanded="false" aria-controls="collapseWidgetWhat">
                                                <span class="question-text">What exactly is the Influencer Widget and what does it look like?</span>
                                            </button>
                                        </h2>
                                        <div id="collapseWidgetWhat" class="accordion-collapse collapse" aria-labelledby="headingWidgetWhat">
                                            <div class="accordion-body"><p>Content coming soon.</p></div>
                                        </div>
                                    </div>

                                    <div class="accordion-item mb-3">
                                        <h2 class="accordion-header" id="headingWidgetDistribute">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseWidgetDistribute" aria-expanded="false" aria-controls="collapseWidgetDistribute">
                                                <span class="question-text">How do I distribute my widget to followers?</span>
                                            </button>
                                        </h2>
                                        <div id="collapseWidgetDistribute" class="accordion-collapse collapse" aria-labelledby="headingWidgetDistribute">
                                            <div class="accordion-body"><p>Content coming soon.</p></div>
                                        </div>
                                    </div>

                                    <div class="accordion-item mb-3">
                                        <h2 class="accordion-header" id="headingWidgetMultiPlatform">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseWidgetMultiPlatform" aria-expanded="false" aria-controls="collapseWidgetMultiPlatform">
                                                <span class="question-text">Can I use my widget on multiple platforms simultaneously?</span>
                                            </button>
                                        </h2>
                                        <div id="collapseWidgetMultiPlatform" class="accordion-collapse collapse" aria-labelledby="headingWidgetMultiPlatform">
                                            <div class="accordion-body"><p>Content coming soon.</p></div>
                                        </div>
                                    </div>

                                    <div class="accordion-item mb-3">
                                        <h2 class="accordion-header" id="headingFollowerNoWidget">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFollowerNoWidget" aria-expanded="false" aria-controls="collapseFollowerNoWidget">
                                                <span class="question-text">What happens if a follower signs up without using my widget?</span>
                                            </button>
                                        </h2>
                                        <div id="collapseFollowerNoWidget" class="accordion-collapse collapse" aria-labelledby="headingFollowerNoWidget">
                                            <div class="accordion-body"><p>Content coming soon.</p></div>
                                        </div>
                                    </div>

                                    <div class="accordion-item mb-3">
                                        <h2 class="accordion-header" id="headingNewWidget">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseNewWidget" aria-expanded="false" aria-controls="collapseNewWidget">
                                                <span class="question-text">Can I get a new widget if I think my link was shared incorrectly?</span>
                                            </button>
                                        </h2>
                                        <div id="collapseNewWidget" class="accordion-collapse collapse" aria-labelledby="headingNewWidget">
                                            <div class="accordion-body"><p>Content coming soon.</p></div>
                                        </div>
                                    </div>

                                    <div class="accordion-item mb-3">
                                        <h2 class="accordion-header" id="headingWidgetPlatformDiff">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseWidgetPlatformDiff" aria-expanded="false" aria-controls="collapseWidgetPlatformDiff">
                                                <span class="question-text">Does my widget work differently on different platforms?</span>
                                            </button>
                                        </h2>
                                        <div id="collapseWidgetPlatformDiff" class="accordion-collapse collapse" aria-labelledby="headingWidgetPlatformDiff">
                                            <div class="accordion-body"><p>Content coming soon.</p></div>
                                        </div>
                                    </div>

                                    <div class="accordion-item mb-3">
                                        <h2 class="accordion-header" id="headingMustPlay">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseMustPlay" aria-expanded="false" aria-controls="collapseMustPlay">
                                                <span class="question-text">Do I have to play the game myself to be an Influencer?</span>
                                            </button>
                                        </h2>
                                        <div id="collapseMustPlay" class="accordion-collapse collapse" aria-labelledby="headingMustPlay">
                                            <div class="accordion-body"><p>Content coming soon.</p></div>
                                        </div>
                                    </div>

                                    <div class="accordion-item mb-3">
                                        <h2 class="accordion-header" id="headingRealMoney">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseRealMoney" aria-expanded="false" aria-controls="collapseRealMoney">
                                                <span class="question-text">Do I have to deposit real money to have a game account?</span>
                                            </button>
                                        </h2>
                                        <div id="collapseRealMoney" class="accordion-collapse collapse" aria-labelledby="headingRealMoney">
                                            <div class="accordion-body"><p>Content coming soon.</p></div>
                                        </div>
                                    </div>

                                    <div class="accordion-item mb-3">
                                        <h2 class="accordion-header" id="headingSocialAccount">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSocialAccount" aria-expanded="false" aria-controls="collapseSocialAccount">
                                                <span class="question-text">Can I use the social game account or do I need a real money account?</span>
                                            </button>
                                        </h2>
                                        <div id="collapseSocialAccount" class="accordion-collapse collapse" aria-labelledby="headingSocialAccount">
                                            <div class="accordion-body"><p>Content coming soon.</p></div>
                                        </div>
                                    </div>

                                </div><!-- /#moreGameAccountAccordion -->
                            </div><!-- /.more-parent-body -->
                        </div>
                    </div><!-- /MY GAME ACCOUNT -->

                    <!-- REFERRAL NETWORK -->
                    <div class="accordion-item more-parent-item mb-3">
                        <h2 class="accordion-header" id="headingReferralNetwork">
                            <button class="accordion-button more-parent-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseReferralNetwork" aria-expanded="false" aria-controls="collapseReferralNetwork">
                                Referral Network
                            </button>
                        </h2>
                        <div id="collapseReferralNetwork" class="accordion-collapse collapse" aria-labelledby="headingReferralNetwork" data-bs-parent="#moreMainAccordion">
                            <div class="accordion-body more-parent-body">
                                <div class="accordion custom-accordion equity-accordion" id="moreReferralAccordion">

                                    <div class="accordion-item mb-3">
                                        <h2 class="accordion-header" id="headingLevel2Level3">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseLevel2Level3" aria-expanded="false" aria-controls="collapseLevel2Level3">
                                                <span class="question-text">Can I see who my Level 2 and Level 3 referrals are?</span>
                                            </button>
                                        </h2>
                                        <div id="collapseLevel2Level3" class="accordion-collapse collapse" aria-labelledby="headingLevel2Level3">
                                            <div class="accordion-body"><p>Content coming soon.</p></div>
                                        </div>
                                    </div>

                                    <div class="accordion-item mb-3">
                                        <h2 class="accordion-header" id="headingFollowerRecruitsOverlap">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFollowerRecruitsOverlap" aria-expanded="false" aria-controls="collapseFollowerRecruitsOverlap">
                                                <span class="question-text">What happens if one of my followers recruits someone I already recruited?</span>
                                            </button>
                                        </h2>
                                        <div id="collapseFollowerRecruitsOverlap" class="accordion-collapse collapse" aria-labelledby="headingFollowerRecruitsOverlap">
                                            <div class="accordion-body"><p>Content coming soon.</p></div>
                                        </div>
                                    </div>

                                    <div class="accordion-item mb-3">
                                        <h2 class="accordion-header" id="headingFollowerStopsPlaying">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFollowerStopsPlaying" aria-expanded="false" aria-controls="collapseFollowerStopsPlaying">
                                                <span class="question-text">If a follower stops playing do I lose that equity permanently?</span>
                                            </button>
                                        </h2>
                                        <div id="collapseFollowerStopsPlaying" class="accordion-collapse collapse" aria-labelledby="headingFollowerStopsPlaying">
                                            <div class="accordion-body"><p>Content coming soon.</p></div>
                                        </div>
                                    </div>

                                    <div class="accordion-item mb-3">
                                        <h2 class="accordion-header" id="headingTwoInfluencers">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwoInfluencers" aria-expanded="false" aria-controls="collapseTwoInfluencers">
                                                <span class="question-text">Can two influencers recruit the same follower — who gets credit?</span>
                                            </button>
                                        </h2>
                                        <div id="collapseTwoInfluencers" class="accordion-collapse collapse" aria-labelledby="headingTwoInfluencers">
                                            <div class="accordion-body"><p>Content coming soon.</p></div>
                                        </div>
                                    </div>

                                    <div class="accordion-item mb-3">
                                        <h2 class="accordion-header" id="headingReferralWorking">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseReferralWorking" aria-expanded="false" aria-controls="collapseReferralWorking">
                                                <span class="question-text">How do I know my referral links are working correctly?</span>
                                            </button>
                                        </h2>
                                        <div id="collapseReferralWorking" class="accordion-collapse collapse" aria-labelledby="headingReferralWorking">
                                            <div class="accordion-body"><p>Content coming soon.</p></div>
                                        </div>
                                    </div>

                                    <div class="accordion-item mb-3">
                                        <h2 class="accordion-header" id="headingRecruitLimit">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseRecruitLimit" aria-expanded="false" aria-controls="collapseRecruitLimit">
                                                <span class="question-text">Is there a limit to how many followers I can recruit?</span>
                                            </button>
                                        </h2>
                                        <div id="collapseRecruitLimit" class="accordion-collapse collapse" aria-labelledby="headingRecruitLimit">
                                            <div class="accordion-body"><p>Content coming soon.</p></div>
                                        </div>
                                    </div>

                                    <div class="accordion-item mb-3">
                                        <h2 class="accordion-header" id="headingCloseAccount">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseCloseAccount" aria-expanded="false" aria-controls="collapseCloseAccount">
                                                <span class="question-text">What happens to my referral network if I close my account?</span>
                                            </button>
                                        </h2>
                                        <div id="collapseCloseAccount" class="accordion-collapse collapse" aria-labelledby="headingCloseAccount">
                                            <div class="accordion-body"><p>Content coming soon.</p></div>
                                        </div>
                                    </div>

                                </div><!-- /#moreReferralAccordion -->
                            </div><!-- /.more-parent-body -->
                        </div>
                    </div><!-- /REFERRAL NETWORK -->

                    <!-- TRUST & CREDIBILITY -->
                    <div class="accordion-item more-parent-item mb-3">
                        <h2 class="accordion-header" id="headingTrustCredibility">
                            <button class="accordion-button more-parent-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTrustCredibility" aria-expanded="false" aria-controls="collapseTrustCredibility">
                                Trust &amp; Credibility
                            </button>
                        </h2>
                        <div id="collapseTrustCredibility" class="accordion-collapse collapse" aria-labelledby="headingTrustCredibility" data-bs-parent="#moreMainAccordion">
                            <div class="accordion-body more-parent-body">
                                <div class="accordion custom-accordion equity-accordion" id="moreTrustAccordion">

                                    <div class="accordion-item mb-3">
                                        <h2 class="accordion-header" id="headingPyramid">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapsePyramid" aria-expanded="false" aria-controls="collapsePyramid">
                                                <span class="question-text">How do I explain this to followers without it sounding like a pyramid scheme?</span>
                                            </button>
                                        </h2>
                                        <div id="collapsePyramid" class="accordion-collapse collapse" aria-labelledby="headingPyramid">
                                            <div class="accordion-body"><p>Content coming soon.</p></div>
                                        </div>
                                    </div>

                                    <div class="accordion-item mb-3">
                                        <h2 class="accordion-header" id="headingEquityProof">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseEquityProof" aria-expanded="false" aria-controls="collapseEquityProof">
                                                <span class="question-text">What proof can I show followers that the equity is real?</span>
                                            </button>
                                        </h2>
                                        <div id="collapseEquityProof" class="accordion-collapse collapse" aria-labelledby="headingEquityProof">
                                            <div class="accordion-body"><p>Content coming soon.</p></div>
                                        </div>
                                    </div>

                                    <div class="accordion-item mb-3">
                                        <h2 class="accordion-header" id="headingHoldingPeriod">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseHoldingPeriod" aria-expanded="false" aria-controls="collapseHoldingPeriod">
                                                <span class="question-text">How do I explain the two-year holding period without losing interest?</span>
                                            </button>
                                        </h2>
                                        <div id="collapseHoldingPeriod" class="accordion-collapse collapse" aria-labelledby="headingHoldingPeriod">
                                            <div class="accordion-body"><p>Content coming soon.</p></div>
                                        </div>
                                    </div>

                                    <div class="accordion-item mb-3">
                                        <h2 class="accordion-header" id="headingPlatformShutdown">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapsePlatformShutdown" aria-expanded="false" aria-controls="collapsePlatformShutdown">
                                                <span class="question-text">What happens to followers' money if the platform shuts down?</span>
                                            </button>
                                        </h2>
                                        <div id="collapsePlatformShutdown" class="accordion-collapse collapse" aria-labelledby="headingPlatformShutdown">
                                            <div class="accordion-body"><p>Content coming soon.</p></div>
                                        </div>
                                    </div>

                                    <div class="accordion-item mb-3">
                                        <h2 class="accordion-header" id="headingFounders">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFounders" aria-expanded="false" aria-controls="collapseFounders">
                                                <span class="question-text">Who is behind InfluencerHQ — can I see the founders?</span>
                                            </button>
                                        </h2>
                                        <div id="collapseFounders" class="accordion-collapse collapse" aria-labelledby="headingFounders">
                                            <div class="accordion-body"><p>Content coming soon.</p></div>
                                        </div>
                                    </div>

                                    <div class="accordion-item mb-3">
                                        <h2 class="accordion-header" id="headingRegulated">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseRegulated" aria-expanded="false" aria-controls="collapseRegulated">
                                                <span class="question-text">Is InfluencerHQ regulated anywhere?</span>
                                            </button>
                                        </h2>
                                        <div id="collapseRegulated" class="accordion-collapse collapse" aria-labelledby="headingRegulated">
                                            <div class="accordion-body"><p>Content coming soon.</p></div>
                                        </div>
                                    </div>

                                    <div class="accordion-item mb-3">
                                        <h2 class="accordion-header" id="headingEquityRules">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseEquityRules" aria-expanded="false" aria-controls="collapseEquityRules">
                                                <span class="question-text">What stops InfluencerHQ from changing the equity rules after I've built my network?</span>
                                            </button>
                                        </h2>
                                        <div id="collapseEquityRules" class="accordion-collapse collapse" aria-labelledby="headingEquityRules">
                                            <div class="accordion-body"><p>Content coming soon.</p></div>
                                        </div>
                                    </div>

                                </div><!-- /#moreTrustAccordion -->
                            </div><!-- /.more-parent-body -->
                        </div>
                    </div><!-- /TRUST & CREDIBILITY -->

                    <!-- THE BIGGER PICTURE -->
                    <div class="accordion-item more-parent-item mb-3">
                        <h2 class="accordion-header" id="headingBiggerPicture">
                            <button class="accordion-button more-parent-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseBiggerPicture" aria-expanded="false" aria-controls="collapseBiggerPicture">
                                The Bigger Picture
                            </button>
                        </h2>
                        <div id="collapseBiggerPicture" class="accordion-collapse collapse" aria-labelledby="headingBiggerPicture" data-bs-parent="#moreMainAccordion">
                            <div class="accordion-body more-parent-body">
                                <div class="accordion custom-accordion equity-accordion" id="moreBiggerPictureAccordion">

                                    <div class="accordion-item mb-3">
                                        <h2 class="accordion-header" id="headingOnlyBaccarat">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOnlyBaccarat" aria-expanded="false" aria-controls="collapseOnlyBaccarat">
                                                <span class="question-text">Is this only about Baccarat?</span>
                                            </button>
                                        </h2>
                                        <div id="collapseOnlyBaccarat" class="accordion-collapse collapse" aria-labelledby="headingOnlyBaccarat">
                                            <div class="accordion-body"><p>Content coming soon.</p></div>
                                        </div>
                                    </div>

                                    <div class="accordion-item mb-3">
                                        <h2 class="accordion-header" id="headingOtherGames">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOtherGames" aria-expanded="false" aria-controls="collapseOtherGames">
                                                <span class="question-text">When will other games be available?</span>
                                            </button>
                                        </h2>
                                        <div id="collapseOtherGames" class="accordion-collapse collapse" aria-labelledby="headingOtherGames">
                                            <div class="accordion-body"><p>Content coming soon.</p></div>
                                        </div>
                                    </div>

                                    <div class="accordion-item mb-3">
                                        <h2 class="accordion-header" id="headingEquityCarryOver">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseEquityCarryOver" aria-expanded="false" aria-controls="collapseEquityCarryOver">
                                                <span class="question-text">Will my equity network carry over to new games automatically?</span>
                                            </button>
                                        </h2>
                                        <div id="collapseEquityCarryOver" class="accordion-collapse collapse" aria-labelledby="headingEquityCarryOver">
                                            <div class="accordion-body"><p>Content coming soon.</p></div>
                                        </div>
                                    </div>

                                    <div class="accordion-item mb-3">
                                        <h2 class="accordion-header" id="headingAvantage">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseAvantage" aria-expanded="false" aria-controls="collapseAvantage">
                                                <span class="question-text">What is Avantage Entertainment?</span>
                                            </button>
                                        </h2>
                                        <div id="collapseAvantage" class="accordion-collapse collapse" aria-labelledby="headingAvantage">
                                            <div class="accordion-body"><p>Content coming soon.</p></div>
                                        </div>
                                    </div>

                                    <div class="accordion-item mb-3">
                                        <h2 class="accordion-header" id="headingHowLong">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseHowLong" aria-expanded="false" aria-controls="collapseHowLong">
                                                <span class="question-text">How long has this platform been operating?</span>
                                            </button>
                                        </h2>
                                        <div id="collapseHowLong" class="accordion-collapse collapse" aria-labelledby="headingHowLong">
                                            <div class="accordion-body"><p>Content coming soon.</p></div>
                                        </div>
                                    </div>

                                    <div class="accordion-item mb-3">
                                        <h2 class="accordion-header" id="headingHowManyInfluencers">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseHowManyInfluencers" aria-expanded="false" aria-controls="collapseHowManyInfluencers">
                                                <span class="question-text">How many influencers are already on the platform?</span>
                                            </button>
                                        </h2>
                                        <div id="collapseHowManyInfluencers" class="accordion-collapse collapse" aria-labelledby="headingHowManyInfluencers">
                                            <div class="accordion-body"><p>Content coming soon.</p></div>
                                        </div>
                                    </div>

                                    <div class="accordion-item mb-3">
                                        <h2 class="accordion-header" id="headingMarkets">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseMarkets" aria-expanded="false" aria-controls="collapseMarkets">
                                                <span class="question-text">What markets is this available in?</span>
                                            </button>
                                        </h2>
                                        <div id="collapseMarkets" class="accordion-collapse collapse" aria-labelledby="headingMarkets">
                                            <div class="accordion-body"><p>Content coming soon.</p></div>
                                        </div>
                                    </div>

                                </div><!-- /#moreBiggerPictureAccordion -->
                            </div><!-- /.more-parent-body -->
                        </div>
                    </div><!-- /THE BIGGER PICTURE -->

                </div><!-- /#moreMainAccordion -->

            </div><!-- /.equity-page-content -->

        </div>
        
        <!-- Fixed Footer Links -->
        <?php get_template_part( 'template-parts/portal-footer' ); ?>
    </main><!-- #main -->

<?php 
get_template_part( 'template-parts/portal-scripts' );
get_footer();
?>
