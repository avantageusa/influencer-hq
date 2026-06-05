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

                <!-- Nested Accordion: parent FAQ categories -->
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
                                            <div class="accordion-body"><p>Open your Equity page and use the play-status filter in the My Referrals view — All / Played / Not Played — to isolate followers who&#039;ve actually played. Each row expands to show that follower&#039;s play dates and amounts, so you can see real activity, not just the signup.</p></div>
                                        </div>
                                    </div>

                                    <div class="accordion-item mb-3">
                                        <h2 class="accordion-header" id="headingReferralsMade">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseReferralsMade" aria-expanded="false" aria-controls="collapseReferralsMade">
                                                <span class="question-text">Can I see which followers referred other people, and how many referrals they've made?</span>
                                            </button>
                                        </h2>
                                        <div id="collapseReferralsMade" class="accordion-collapse collapse" aria-labelledby="headingReferralsMade">
                                            <div class="accordion-body"><p>Your Equity page includes a My Referrals view. You can drill down from your level 1 referrals to see who they have referred and then again to see who your level 2 referrals have referred.</p></div>
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
                                            <div class="accordion-body"><p>Your Equity page totals your earnings across every level: 1.5% of verified play from your Level 1 (direct) followers, 1.0% from Level 2, 0.5% from Level 3, plus the 1.0% Bonus Equity from your KICK streams and World Network live appearances. You also will see 1% base on your own play!</p></div>
                                        </div>
                                    </div>

                                    <div class="accordion-item mb-3">
                                        <h2 class="accordion-header" id="headingKickEquity">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseKickEquity" aria-expanded="false" aria-controls="collapseKickEquity">
                                                <span class="question-text">How do I see how much Equity I've earned from people watching on KICK or the World Network?</span>
                                            </button>
                                        </h2>
                                        <div id="collapseKickEquity" class="accordion-collapse collapse" aria-labelledby="headingKickEquity">
                                            <div class="accordion-body"><p>Each is a 1.0% Bonus Equity on the verified play of everyone who plays along with your live KICK stream or your live appearance on the World Network. You can see this broken down in the My Equity screen. You can filter to view one specific type of equity award to see exactly how much you have earned from each.</p></div>
                                        </div>
                                    </div>

                                    

                                    <div class="accordion-item mb-3">
                                        <h2 class="accordion-header" id="headingWeekVsWeek">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseWeekVsWeek" aria-expanded="false" aria-controls="collapseWeekVsWeek">
                                                <span class="question-text">Can I compare my performance this week vs. last week?</span>
                                            </button>
                                        </h2>
                                        <div id="collapseWeekVsWeek" class="accordion-collapse collapse" aria-labelledby="headingWeekVsWeek">
                                            <div class="accordion-body"><p>Yes. Your Equity page filters by This Week, Last Week, This Month, Last Month, This Year, and Last Year, so you can flip between periods to compare. The Earned Equity Chart on the same page supports the same view.</p></div>
                                        </div>
                                    </div>

                                    <div class="accordion-item mb-3">
                                        <h2 class="accordion-header" id="headingShoutouts">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseShoutouts" aria-expanded="false" aria-controls="collapseShoutouts">
                                                <span class="question-text">How do I know if my shout-out messages are working, and which ones are working best?</span>
                                            </button>
                                        </h2>
                                        <div id="collapseShoutouts" class="accordion-collapse collapse" aria-labelledby="headingShoutouts">
                                            <div class="accordion-body"><p>The platform doesn&#039;t track shout-outs you send through your own channels — those happen wherever you post. The signal is whether new sign-ups show up in the My Referrals view on your Equity page after you post. If a shout on a specific channel produces a fresh batch of referrals, that&#039;s the read.</p></div>
                                        </div>
                                    </div>

                                    <div class="accordion-item mb-3">
                                        <h2 class="accordion-header" id="headingFilterCountry">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFilterCountry" aria-expanded="false" aria-controls="collapseFilterCountry">
                                                <span class="question-text">Can I filter my performance data by country or region?</span>
                                            </button>
                                        </h2>
                                        <div id="collapseFilterCountry" class="accordion-collapse collapse" aria-labelledby="headingFilterCountry">
                                            <div class="accordion-body"><p>Not on your Equity page — both the Equity Results and My Referrals views show your full network without geographic filtering. The Rankings page does support geography (World / Continent / Country / City), but those filters apply to the leaderboard itself rather than slicing your own audience.</p></div>
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
                                            <div class="accordion-body"><p>The platform doesn&#039;t tag sign-ups by the channel they came from. Same approach as shout-outs — post on one channel at a time, then watch the My Referrals view on your Equity page to see which posts produced new sign-ups.</p></div>
                                        </div>
                                    </div>

                                    <div class="accordion-item mb-3">
                                        <h2 class="accordion-header" id="headingEquityRealTime">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseEquityRealTime" aria-expanded="false" aria-controls="collapseEquityRealTime">
                                                <span class="question-text">Can I see my equity accumulation in real time?</span>
                                            </button>
                                        </h2>
                                        <div id="collapseEquityRealTime" class="accordion-collapse collapse" aria-labelledby="headingEquityRealTime">
                                            <div class="accordion-body"><p>Yes. Every share is recorded at the exact moment it is earned — nothing is pooled, estimated, or assumed.</p></div>
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
                                            <div class="accordion-body"><p>Your widget is the Game Portal URL on your Profile page — a personalized link that delivers followers into Avantage Baccarat with you credited as their referrer.</p></div>
                                        </div>
                                    </div>

                                    <div class="accordion-item mb-3">
                                        <h2 class="accordion-header" id="headingWidgetDistribute">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseWidgetDistribute" aria-expanded="false" aria-controls="collapseWidgetDistribute">
                                                <span class="question-text">How do I distribute my widget to followers?</span>
                                            </button>
                                        </h2>
                                        <div id="collapseWidgetDistribute" class="accordion-collapse collapse" aria-labelledby="headingWidgetDistribute">
                                            <div class="accordion-body"><p>Use the My Referral Link button on your Equity page to copy your link to the clipboard, then paste it anywhere — social posts, DMs, bios, stream overlays.</p></div>
                                        </div>
                                    </div>

                                    <div class="accordion-item mb-3">
                                        <h2 class="accordion-header" id="headingWidgetMultiPlatform">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseWidgetMultiPlatform" aria-expanded="false" aria-controls="collapseWidgetMultiPlatform">
                                                <span class="question-text">Can I use my widget on multiple platforms simultaneously?</span>
                                            </button>
                                        </h2>
                                        <div id="collapseWidgetMultiPlatform" class="accordion-collapse collapse" aria-labelledby="headingWidgetMultiPlatform">
                                            <div class="accordion-body"><p>Yes. Your referral link is a single URL — paste it anywhere you want followers to find it. All sign-ups route back to your network regardless of where they originated.</p></div>
                                        </div>
                                    </div>

                                    <div class="accordion-item mb-3">
                                        <h2 class="accordion-header" id="headingFollowerNoWidget">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFollowerNoWidget" aria-expanded="false" aria-controls="collapseFollowerNoWidget">
                                                <span class="question-text">What happens if a follower signs up without using my widget?</span>
                                            </button>
                                        </h2>
                                        <div id="collapseFollowerNoWidget" class="accordion-collapse collapse" aria-labelledby="headingFollowerNoWidget">
                                            <div class="accordion-body"><p>That sign-up isn&#039;t attributed to you. This is why it matters to make sure your followers use the referral link you give them — without your link in the chain, the platform has no way to credit the relationship to you.</p></div>
                                        </div>
                                    </div>

                                    <div class="accordion-item mb-3">
                                        <h2 class="accordion-header" id="headingNewWidget">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseNewWidget" aria-expanded="false" aria-controls="collapseNewWidget">
                                                <span class="question-text">Can I get a new widget if I think my link was shared incorrectly?</span>
                                            </button>
                                        </h2>
                                        <div id="collapseNewWidget" class="accordion-collapse collapse" aria-labelledby="headingNewWidget">
                                            <div class="accordion-body"><p>Your referral link is just a URL — there&#039;s no incorrect way to share it. If you think you copied an outdated version, open your Equity page and tap My Referral Link to grab the current one again.</p></div>
                                        </div>
                                    </div>

                                    

                                    <div class="accordion-item mb-3">
                                        <h2 class="accordion-header" id="headingMustPlay">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseMustPlay" aria-expanded="false" aria-controls="collapseMustPlay">
                                                <span class="question-text">Do I have to play the game myself to be an Influencer?</span>
                                            </button>
                                        </h2>
                                        <div id="collapseMustPlay" class="accordion-collapse collapse" aria-labelledby="headingMustPlay">
                                            <div class="accordion-body"><p>You don&#039;t have to play, but two things to weigh: your followers will notice when you&#039;re not in the game, and you won&#039;t earn equity on your own play (equity attributes to whoever recruited you, not yourself). Leading from the front keeps your community invested and stacks Live and Kick bonuses on top of your network attribution.</p></div>
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
                                            <div class="accordion-body"><p>Yes. The Equity page includes a My Referrals view that shows your network by level.</p></div>
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
                                            <div class="accordion-body"><p>No. Every share is recorded at the moment it is earned, and earned equity stays with you. If they return and play again, new equity resumes accruing under their existing place in your network.</p></div>
                                        </div>
                                    </div>

                                    <div class="accordion-item mb-3">
                                        <h2 class="accordion-header" id="headingTwoInfluencers">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwoInfluencers" aria-expanded="false" aria-controls="collapseTwoInfluencers">
                                                <span class="question-text">Can two influencers recruit the same follower — who gets credit?</span>
                                            </button>
                                        </h2>
                                        <div id="collapseTwoInfluencers" class="accordion-collapse collapse" aria-labelledby="headingTwoInfluencers">
                                            <div class="accordion-body"><p>Whoever&#039;s link they click first gets the base-level equity for that follower; that attribution stays. If they later click another Influencer&#039;s link, that second Influencer will begin receiving the Kick and Live bonuses on the player&#039;s play — but base-level attribution doesn&#039;t move.</p></div>
                                        </div>
                                    </div>

                                    <div class="accordion-item mb-3">
                                        <h2 class="accordion-header" id="headingReferralWorking">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseReferralWorking" aria-expanded="false" aria-controls="collapseReferralWorking">
                                                <span class="question-text">How do I know my referral links are working correctly?</span>
                                            </button>
                                        </h2>
                                        <div id="collapseReferralWorking" class="accordion-collapse collapse" aria-labelledby="headingReferralWorking">
                                            <div class="accordion-body"><p>Open your Equity page and check the My Referrals view. Anyone who signed up through your link appears in the list; toggling the Played filter shows who&#039;s actually playing. The My Referral Link button copies your URL to the clipboard so you can test it by opening it in a private window.</p></div>
                                        </div>
                                    </div>

                                    <div class="accordion-item mb-3">
                                        <h2 class="accordion-header" id="headingRecruitLimit">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseRecruitLimit" aria-expanded="false" aria-controls="collapseRecruitLimit">
                                                <span class="question-text">Is there a limit to how many followers I can recruit?</span>
                                            </button>
                                        </h2>
                                        <div id="collapseRecruitLimit" class="accordion-collapse collapse" aria-labelledby="headingRecruitLimit">
                                            <div class="accordion-body"><p>No. Three levels. Forever. No caps. No games.</p></div>
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
                                            <div class="accordion-body"><p>This isn&#039;t a promotion. It&#039;s a seat at the table. Followers play the same games they would play anyway; equity rewards influence as the platform grows — it doesn&#039;t come from your followers&#039; wallets.</p></div>
                                        </div>
                                    </div>

                                    <div class="accordion-item mb-3">
                                        <h2 class="accordion-header" id="headingEquityProof">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseEquityProof" aria-expanded="false" aria-controls="collapseEquityProof">
                                                <span class="question-text">What proof can I show followers that the equity is real?</span>
                                            </button>
                                        </h2>
                                        <div id="collapseEquityProof" class="accordion-collapse collapse" aria-labelledby="headingEquityProof">
                                            <div class="accordion-body"><p>Point them at the Equity page — it discloses the per-share price ($0.20), the $80M+ already invested in the platform, AE Ltd.&#039;s 53 international utility patents across 20 jurisdictions, and Avantage Baccarat&#039;s Malta Gaming Authority recognition. Every share you earn is recorded at the exact moment of earning — the same page is the receipt.</p></div>
                                        </div>
                                    </div>

                                    <div class="accordion-item mb-3">
                                        <h2 class="accordion-header" id="headingHoldingPeriod">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseHoldingPeriod" aria-expanded="false" aria-controls="collapseHoldingPeriod">
                                                <span class="question-text">How do I explain the two-year holding period without losing interest?</span>
                                            </button>
                                        </h2>
                                        <div id="collapseHoldingPeriod" class="accordion-collapse collapse" aria-labelledby="headingHoldingPeriod">
                                            <div class="accordion-body"><p>It&#039;s the same model used for founder equity — a two-year holding period before shares can be sold. The framing that works: those who help build something deserve to share in what grows from it, on the same terms as the founders. Two years is the alignment window for that value to compound.</p></div>
                                        </div>
                                    </div>

                                    

                                    <div class="accordion-item mb-3">
                                        <h2 class="accordion-header" id="headingFounders">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFounders" aria-expanded="false" aria-controls="collapseFounders">
                                                <span class="question-text">Who is behind InfluencerHQ — can I see the founders?</span>
                                            </button>
                                        </h2>
                                        <div id="collapseFounders" class="accordion-collapse collapse" aria-labelledby="headingFounders">
                                            <div class="accordion-body"><p>InfluencerHQ is operated under Avantage Entertainment (AE Ltd.). In the US, Bet 5 Games is licensed by Avantage Entertainment USA.</p></div>
                                        </div>
                                    </div>

                                    <div class="accordion-item mb-3">
                                        <h2 class="accordion-header" id="headingRegulated">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseRegulated" aria-expanded="false" aria-controls="collapseRegulated">
                                                <span class="question-text">Is InfluencerHQ regulated anywhere?</span>
                                            </button>
                                        </h2>
                                        <div id="collapseRegulated" class="accordion-collapse collapse" aria-labelledby="headingRegulated">
                                            <div class="accordion-body"><p>Avantage Baccarat holds Recognition status issued by the Malta Gaming Authority. AE Ltd. holds 53 US and international utility patents distributed across China, Macau, the United States, Japan, Singapore, Malta, the Philippines, Korea, Vietnam, Australia, Gibraltar, Cambodia, Malaysia, Monaco, Taiwan, Israel, Spain, the UK, France, and Hong Kong. In the US, Bet 5 Games operates under license from Avantage Entertainment USA.</p></div>
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
                                            <div class="accordion-body"><p>Avantage Baccarat is the currently launched game — a modern 1 Bank vs. 5 Players format with plays throughout the hand and a top-30% pool split.</p></div>
                                        </div>
                                    </div>

                                    

                                    <div class="accordion-item mb-3">
                                        <h2 class="accordion-header" id="headingEquityCarryOver">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseEquityCarryOver" aria-expanded="false" aria-controls="collapseEquityCarryOver">
                                                <span class="question-text">Will my equity network carry over to new games automatically?</span>
                                            </button>
                                        </h2>
                                        <div id="collapseEquityCarryOver" class="accordion-collapse collapse" aria-labelledby="headingEquityCarryOver">
                                            <div class="accordion-body"><p>Yes. Your referral network and equity stake live at the platform level — not at the game level. When new titles like Avantage TwentyOne and Avantage Hold&#039;em come online, your existing network plays them under the same equity attribution, and any new sign-ups roll into the same upline.</p></div>
                                        </div>
                                    </div>

                                    <div class="accordion-item mb-3">
                                        <h2 class="accordion-header" id="headingAvantage">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseAvantage" aria-expanded="false" aria-controls="collapseAvantage">
                                                <span class="question-text">What is Avantage Entertainment?</span>
                                            </button>
                                        </h2>
                                        <div id="collapseAvantage" class="accordion-collapse collapse" aria-labelledby="headingAvantage">
                                            <div class="accordion-body"><p>Avantage Entertainment (AE Ltd.) is the parent group behind InfluencerHQ and Avantage Baccarat. AE Ltd. holds 53 US and international utility patents, and Bet 5 Games is its licensed operator in the US.</p></div>
                                        </div>
                                    </div>

                                    

                                    

                                    <div class="accordion-item mb-3">
                                        <h2 class="accordion-header" id="headingMarkets">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseMarkets" aria-expanded="false" aria-controls="collapseMarkets">
                                                <span class="question-text">What markets is this available in?</span>
                                            </button>
                                        </h2>
                                        <div id="collapseMarkets" class="accordion-collapse collapse" aria-labelledby="headingMarkets">
                                            <div class="accordion-body"><p>The platform is delivered in English alongside Simplified Chinese (Mandarin), Traditional Chinese (Cantonese), Japanese, Thai, Vietnamese, and Korean — the seven languages every content surface is localized into.</p></div>
                                        </div>
                                    </div>

                                </div><!-- /#moreBiggerPictureAccordion -->
                            </div><!-- /.more-parent-body -->
                        </div>
                    </div><!-- /THE BIGGER PICTURE -->

                    <!-- COMMUNICATION & FOLLOWERS -->
                    <div class="accordion-item more-parent-item mb-3">
                        <h2 class="accordion-header" id="headingCommFollowers">
                            <button class="accordion-button more-parent-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseCommFollowers" aria-expanded="false" aria-controls="collapseCommFollowers">
                                Communication &amp; Followers
                            </button>
                        </h2>
                        <div id="collapseCommFollowers" class="accordion-collapse collapse" aria-labelledby="headingCommFollowers" data-bs-parent="#moreMainAccordion">
                            <div class="accordion-body more-parent-body">
                                <div class="accordion custom-accordion equity-accordion" id="moreCommFollowersAccordion">

                                    <div class="accordion-item mb-3">
                                        <h2 class="accordion-header" id="headingCommFaqHowDoISend">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseCommFaqHowDoISend" aria-expanded="false" aria-controls="collapseCommFaqHowDoISend">
                                                <span class="question-text">How do I send shout-out messages to my followers through the platform?</span>
                                            </button>
                                        </h2>
                                        <div id="collapseCommFaqHowDoISend" class="accordion-collapse collapse" aria-labelledby="headingCommFaqHowDoISend">
                                            <div class="accordion-body"><p>Shout-outs happen on your own channels in your own voice — your referral link, your stream announcements, your follower invites all post through whatever platforms you already use. There&#039;s no in-platform shout-out tool today; if you&#039;d use one, let your Executive Concierge know.</p></div>
                                        </div>
                                    </div>

                                    <div class="accordion-item mb-3">
                                        <h2 class="accordion-header" id="headingCommFaqCanIChooseDifferent">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseCommFaqCanIChooseDifferent" aria-expanded="false" aria-controls="collapseCommFaqCanIChooseDifferent">
                                                <span class="question-text">Can I choose different channels for different followers?</span>
                                            </button>
                                        </h2>
                                        <div id="collapseCommFaqCanIChooseDifferent" class="accordion-collapse collapse" aria-labelledby="headingCommFaqCanIChooseDifferent">
                                            <div class="accordion-body"><p>You already do — your followers come to you on whatever platforms they follow you on. Post your link and your invitations on the channels that fit each audience, in whatever language and tone work best.</p></div>
                                        </div>
                                    </div>

                                    <div class="accordion-item mb-3">
                                        <h2 class="accordion-header" id="headingCommFaqHowOftenCanI">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseCommFaqHowOftenCanI" aria-expanded="false" aria-controls="collapseCommFaqHowOftenCanI">
                                                <span class="question-text">How often can I send messages to my followers through the platform?</span>
                                            </button>
                                        </h2>
                                        <div id="collapseCommFaqHowOftenCanI" class="accordion-collapse collapse" aria-labelledby="headingCommFaqHowOftenCanI">
                                            <div class="accordion-body"><p>There&#039;s no in-platform messaging — your outreach to followers happens through your own channels at whatever cadence works for you and your audience.</p></div>
                                        </div>
                                    </div>

                                </div><!-- /#moreCommFollowersAccordion -->
                            </div><!-- /.more-parent-body -->
                        </div>
                    </div><!-- /COMMUNICATION & FOLLOWERS -->

                    <!-- LIVE APPEARANCES -->
                    <div class="accordion-item more-parent-item mb-3">
                        <h2 class="accordion-header" id="headingLiveAppearances">
                            <button class="accordion-button more-parent-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseLiveAppearances" aria-expanded="false" aria-controls="collapseLiveAppearances">
                                Live Appearances
                            </button>
                        </h2>
                        <div id="collapseLiveAppearances" class="accordion-collapse collapse" aria-labelledby="headingLiveAppearances" data-bs-parent="#moreMainAccordion">
                            <div class="accordion-body more-parent-body">
                                <div class="accordion custom-accordion equity-accordion" id="moreLiveAppearancesAccordion">

                                    <div class="accordion-item mb-3">
                                        <h2 class="accordion-header" id="headingLiveFaqHowMuchNoticeDo">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseLiveFaqHowMuchNoticeDo" aria-expanded="false" aria-controls="collapseLiveFaqHowMuchNoticeDo">
                                                <span class="question-text">How much notice do I need before a scheduled stream?</span>
                                            </button>
                                        </h2>
                                        <div id="collapseLiveFaqHowMuchNoticeDo" class="accordion-collapse collapse" aria-labelledby="headingLiveFaqHowMuchNoticeDo">
                                            <div class="accordion-body"><p>There&#039;s no specific notice period stated. World Network Live Appearance requests go through HQ review and approval, and the request form asks for first, second, and third choice day-and-time — so submitting a range works best. For Kick streams, post your schedule to Report KICK Broadcast Schedule before you go live so we can publish your stream and provide tracked links.</p></div>
                                        </div>
                                    </div>

                                    <div class="accordion-item mb-3">
                                        <h2 class="accordion-header" id="headingLiveFaqWhatHappensIfMy">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseLiveFaqWhatHappensIfMy" aria-expanded="false" aria-controls="collapseLiveFaqWhatHappensIfMy">
                                                <span class="question-text">What happens if my stream is cancelled after distributing my URL?</span>
                                            </button>
                                        </h2>
                                        <div id="collapseLiveFaqWhatHappensIfMy" class="accordion-collapse collapse" aria-labelledby="headingLiveFaqWhatHappensIfMy">
                                            <div class="accordion-body"><p>Each scheduled KICK broadcast can be cancelled from the KICK Broadcasting Schedule on the Live page.</p></div>
                                        </div>
                                    </div>

                                    <div class="accordion-item mb-3">
                                        <h2 class="accordion-header" id="headingLiveFaqCanIStreamOn">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseLiveFaqCanIStreamOn" aria-expanded="false" aria-controls="collapseLiveFaqCanIStreamOn">
                                                <span class="question-text">Can I stream on platforms other than Kick?</span>
                                            </button>
                                        </h2>
                                        <div id="collapseLiveFaqCanIStreamOn" class="accordion-collapse collapse" aria-labelledby="headingLiveFaqCanIStreamOn">
                                            <div class="accordion-body"><p>KICK is the supported third-party streaming platform — viewer play during your KICK stream earns the 1% Bonus Equity. The World Broadcast Network is a separate live-appearance channel for approved 1-1 Private Challenges.</p></div>
                                        </div>
                                    </div>

                                    <div class="accordion-item mb-3">
                                        <h2 class="accordion-header" id="headingLiveFaqHowDoIKnow">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseLiveFaqHowDoIKnow" aria-expanded="false" aria-controls="collapseLiveFaqHowDoIKnow">
                                                <span class="question-text">How do I know how many viewers generated play during my stream?</span>
                                            </button>
                                        </h2>
                                        <div id="collapseLiveFaqHowDoIKnow" class="accordion-collapse collapse" aria-labelledby="headingLiveFaqHowDoIKnow">
                                            <div class="accordion-body"><p>The KICK Stream Equity Bonus is 1% of the verified play generated by participating viewers during your stream. Your Equity page breaks out the Kick Bonus separately so you can see what each stream contributed.</p></div>
                                        </div>
                                    </div>

                                    <div class="accordion-item mb-3">
                                        <h2 class="accordion-header" id="headingLiveFaqWhatIfMyFollowers">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseLiveFaqWhatIfMyFollowers" aria-expanded="false" aria-controls="collapseLiveFaqWhatIfMyFollowers">
                                                <span class="question-text">What if my followers use the wrong link?</span>
                                            </button>
                                        </h2>
                                        <div id="collapseLiveFaqWhatIfMyFollowers" class="accordion-collapse collapse" aria-labelledby="headingLiveFaqWhatIfMyFollowers">
                                            <div class="accordion-body"><p>The Kick Stream Equity Bonus is credited based on viewers participating through your official tracked link — generated when you submit your broadcast schedule. If a follower clicks an off-platform or untracked link, that play won&#039;t carry the Kick Bonus. Submit your schedule via Report KICK Broadcast Schedule before going live so the right link is in circulation.</p></div>
                                        </div>
                                    </div>

                                    <div class="accordion-item mb-3">
                                        <h2 class="accordion-header" id="headingLiveFaqCanIScheduleMultiple">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseLiveFaqCanIScheduleMultiple" aria-expanded="false" aria-controls="collapseLiveFaqCanIScheduleMultiple">
                                                <span class="question-text">Can I schedule multiple streams in the same week?</span>
                                            </button>
                                        </h2>
                                        <div id="collapseLiveFaqCanIScheduleMultiple" class="accordion-collapse collapse" aria-labelledby="headingLiveFaqCanIScheduleMultiple">
                                            <div class="accordion-body"><p>Yes. You can post additional sessions on the KICK Broadcasting Schedule on the Live page.</p></div>
                                        </div>
                                    </div>

                                    <div class="accordion-item mb-3">
                                        <h2 class="accordion-header" id="headingLiveFaqWhatIsTheMinimum">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseLiveFaqWhatIsTheMinimum" aria-expanded="false" aria-controls="collapseLiveFaqWhatIsTheMinimum">
                                                <span class="question-text">What is the minimum stream length to qualify for the equity bonus?</span>
                                            </button>
                                        </h2>
                                        <div id="collapseLiveFaqWhatIsTheMinimum" class="accordion-collapse collapse" aria-labelledby="headingLiveFaqWhatIsTheMinimum">
                                            <div class="accordion-body"><p>No minimum length is specified. The Kick Stream Equity Bonus is 1% of viewer play during your broadcast — whatever the length, qualifying play counts toward it.</p></div>
                                        </div>
                                    </div>

                                    <div class="accordion-item mb-3">
                                        <h2 class="accordion-header" id="headingLiveFaqHowSoonAfterMy">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseLiveFaqHowSoonAfterMy" aria-expanded="false" aria-controls="collapseLiveFaqHowSoonAfterMy">
                                                <span class="question-text">How soon after my stream do I see the equity credited?</span>
                                            </button>
                                        </h2>
                                        <div id="collapseLiveFaqHowSoonAfterMy" class="accordion-collapse collapse" aria-labelledby="headingLiveFaqHowSoonAfterMy">
                                            <div class="accordion-body"><p>Equity shares are recorded at the exact moment they are earned, so play during the broadcast credits as it happens.</p></div>
                                        </div>
                                    </div>

                                </div><!-- /#moreLiveAppearancesAccordion -->
                            </div><!-- /.more-parent-body -->
                        </div>
                    </div><!-- /LIVE APPEARANCES -->

                    <!-- GETTING STARTED -->
                    <div class="accordion-item more-parent-item mb-3">
                        <h2 class="accordion-header" id="headingGettingStarted">
                            <button class="accordion-button more-parent-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseGettingStarted" aria-expanded="false" aria-controls="collapseGettingStarted">
                                Getting Started
                            </button>
                        </h2>
                        <div id="collapseGettingStarted" class="accordion-collapse collapse" aria-labelledby="headingGettingStarted" data-bs-parent="#moreMainAccordion">
                            <div class="accordion-body more-parent-body">
                                <div class="accordion custom-accordion equity-accordion" id="moreGettingStartedAccordion">

                                    <div class="accordion-item mb-3">
                                        <h2 class="accordion-header" id="headingStartFaqIsThereAMinimum">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseStartFaqIsThereAMinimum" aria-expanded="false" aria-controls="collapseStartFaqIsThereAMinimum">
                                                <span class="question-text">Is there a minimum follower count to join?</span>
                                            </button>
                                        </h2>
                                        <div id="collapseStartFaqIsThereAMinimum" class="accordion-collapse collapse" aria-labelledby="headingStartFaqIsThereAMinimum">
                                            <div class="accordion-body"><p>No.</p></div>
                                        </div>
                                    </div>

                                    <div class="accordion-item mb-3">
                                        <h2 class="accordion-header" id="headingStartFaqCanIParticipateIf">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseStartFaqCanIParticipateIf" aria-expanded="false" aria-controls="collapseStartFaqCanIParticipateIf">
                                                <span class="question-text">Can I participate if my audience is primarily on TikTok?</span>
                                            </button>
                                        </h2>
                                        <div id="collapseStartFaqCanIParticipateIf" class="accordion-collapse collapse" aria-labelledby="headingStartFaqCanIParticipateIf">
                                            <div class="accordion-body"><p>Yes.</p></div>
                                        </div>
                                    </div>

                                    <div class="accordion-item mb-3">
                                        <h2 class="accordion-header" id="headingStartFaqWhatIfMyFollowers">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseStartFaqWhatIfMyFollowers" aria-expanded="false" aria-controls="collapseStartFaqWhatIfMyFollowers">
                                                <span class="question-text">What if my followers speak multiple languages?</span>
                                            </button>
                                        </h2>
                                        <div id="collapseStartFaqWhatIfMyFollowers" class="accordion-collapse collapse" aria-labelledby="headingStartFaqWhatIfMyFollowers">
                                            <div class="accordion-body"><p>The portal is delivered in seven languages — English, Mandarin (Simplified Chinese), Cantonese (Traditional Chinese), Japanese, Korean, Thai, and Vietnamese. Your referral link is the same URL for all of them; each follower picks their own language once they land.</p></div>
                                        </div>
                                    </div>

                                    <div class="accordion-item mb-3">
                                        <h2 class="accordion-header" id="headingStartFaqHowDoIInvite">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseStartFaqHowDoIInvite" aria-expanded="false" aria-controls="collapseStartFaqHowDoIInvite">
                                                <span class="question-text">How do I invite followers not on supported messaging platforms?</span>
                                            </button>
                                        </h2>
                                        <div id="collapseStartFaqHowDoIInvite" class="accordion-collapse collapse" aria-labelledby="headingStartFaqHowDoIInvite">
                                            <div class="accordion-body"><p>The referral link is a plain URL — paste it anywhere your followers actually are. The &#039;supported&#039; channel lists you see in the portal (Email, WhatsApp, KakaoTalk, Line, WeChat, Telegram, plus TikTok and Twitch on your Profile) are for InfluencerHQ-side communication — opponent invites, HQ outreach — not a restriction on where your followers must come from.</p></div>
                                        </div>
                                    </div>

                                    <div class="accordion-item mb-3">
                                        <h2 class="accordion-header" id="headingStartFaqCanIJoinAs">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseStartFaqCanIJoinAs" aria-expanded="false" aria-controls="collapseStartFaqCanIJoinAs">
                                                <span class="question-text">Can I join as an Influencer and also play as a regular player?</span>
                                            </button>
                                        </h2>
                                        <div id="collapseStartFaqCanIJoinAs" class="accordion-collapse collapse" aria-labelledby="headingStartFaqCanIJoinAs">
                                            <div class="accordion-body"><p>Yes. The Influencer experience is built around you playing — that&#039;s what the live stream and live appearance formats are for. There&#039;s no separate &#039;Influencer-only&#039; mode.</p></div>
                                        </div>
                                    </div>

                                </div><!-- /#moreGettingStartedAccordion -->
                            </div><!-- /.more-parent-body -->
                        </div>
                    </div><!-- /GETTING STARTED -->

                    <!-- COMPETITIONS -->
                    <div class="accordion-item more-parent-item mb-3">
                        <h2 class="accordion-header" id="headingCompetitions">
                            <button class="accordion-button more-parent-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseCompetitions" aria-expanded="false" aria-controls="collapseCompetitions">
                                Competitions
                            </button>
                        </h2>
                        <div id="collapseCompetitions" class="accordion-collapse collapse" aria-labelledby="headingCompetitions" data-bs-parent="#moreMainAccordion">
                            <div class="accordion-body more-parent-body">
                                <div class="accordion custom-accordion equity-accordion" id="moreCompetitionsAccordion">

                                    <div class="accordion-item mb-3">
                                        <h2 class="accordion-header" id="headingCompFaqHowDoIKnow">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseCompFaqHowDoIKnow" aria-expanded="false" aria-controls="collapseCompFaqHowDoIKnow">
                                                <span class="question-text">How do I know when the next World Challenge window opens?</span>
                                            </button>
                                        </h2>
                                        <div id="collapseCompFaqHowDoIKnow" class="accordion-collapse collapse" aria-labelledby="headingCompFaqHowDoIKnow">
                                            <div class="accordion-body"><p>World Challenges run every weekend, Thursday night through Sunday night.</p></div>
                                        </div>
                                    </div>

                                    <div class="accordion-item mb-3">
                                        <h2 class="accordion-header" id="headingCompFaqCanIParticipateIn">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseCompFaqCanIParticipateIn" aria-expanded="false" aria-controls="collapseCompFaqCanIParticipateIn">
                                                <span class="question-text">Can I participate in multiple competition types simultaneously?</span>
                                            </button>
                                        </h2>
                                        <div id="collapseCompFaqCanIParticipateIn" class="accordion-collapse collapse" aria-labelledby="headingCompFaqCanIParticipateIn">
                                            <div class="accordion-body"><p>Yes. Private Challenges, Community Challenges, and the World Competition are independent — you can run or join any combination at the same time, and the same goes for the International League and the three Celebrity Follower Leagues.</p></div>
                                        </div>
                                    </div>

                                    <div class="accordion-item mb-3">
                                        <h2 class="accordion-header" id="headingCompFaqCanMyFollowersCompete">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseCompFaqCanMyFollowersCompete" aria-expanded="false" aria-controls="collapseCompFaqCanMyFollowersCompete">
                                                <span class="question-text">Can my followers compete even if I don&#039;t personally compete?</span>
                                            </button>
                                        </h2>
                                        <div id="collapseCompFaqCanMyFollowersCompete" class="accordion-collapse collapse" aria-labelledby="headingCompFaqCanMyFollowersCompete">
                                            <div class="accordion-body"><p>Yes. Your name will still show up on the Team leaderboards for Community Play and the Weekly World Challenge, carried by the players on your Team.</p></div>
                                        </div>
                                    </div>

                                    <div class="accordion-item mb-3">
                                        <h2 class="accordion-header" id="headingCompFaqHowDoIChoose">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseCompFaqHowDoIChoose" aria-expanded="false" aria-controls="collapseCompFaqHowDoIChoose">
                                                <span class="question-text">How do I choose which Celebrity League to join?</span>
                                            </button>
                                        </h2>
                                        <div id="collapseCompFaqHowDoIChoose" class="accordion-collapse collapse" aria-labelledby="headingCompFaqHowDoIChoose">
                                            <div class="accordion-body"><p>On your Profile, pick one favorite from each of the three Celebrity Follower Leagues — Movie Stars, Music Artists, and Sports Icons. InfluencerHQ then promotes you as a Team Captain to new game participants.</p></div>
                                        </div>
                                    </div>

                                    <div class="accordion-item mb-3">
                                        <h2 class="accordion-header" id="headingCompFaqCanIChangeMy">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseCompFaqCanIChangeMy" aria-expanded="false" aria-controls="collapseCompFaqCanIChangeMy">
                                                <span class="question-text">Can I change my Celebrity League team after I&#039;ve chosen?</span>
                                            </button>
                                        </h2>
                                        <div id="collapseCompFaqCanIChangeMy" class="accordion-collapse collapse" aria-labelledby="headingCompFaqCanIChangeMy">
                                            <div class="accordion-body"><p>Yes. Your Celebrity Follower League picks (Movie Stars, Music Artists, Sports Icons) and your International League team live on your Profile page — open the dropdowns and update at any time.</p></div>
                                        </div>
                                    </div>

                                    <div class="accordion-item mb-3">
                                        <h2 class="accordion-header" id="headingCompFaqCanIBeIn">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseCompFaqCanIBeIn" aria-expanded="false" aria-controls="collapseCompFaqCanIBeIn">
                                                <span class="question-text">Can I be in both a Celebrity League and an International League at the same time?</span>
                                            </button>
                                        </h2>
                                        <div id="collapseCompFaqCanIBeIn" class="accordion-collapse collapse" aria-labelledby="headingCompFaqCanIBeIn">
                                            <div class="accordion-body"><p>Yes. The International League (chosen by country or region) and the three Celebrity Follower Leagues are independent selections on your Profile.</p></div>
                                        </div>
                                    </div>

                                </div><!-- /#moreCompetitionsAccordion -->
                            </div><!-- /.more-parent-body -->
                        </div>
                    </div><!-- /COMPETITIONS -->

                    <!-- EQUITY & TOKENS -->
                    <div class="accordion-item more-parent-item mb-3">
                        <h2 class="accordion-header" id="headingEquityTokens">
                            <button class="accordion-button more-parent-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseEquityTokens" aria-expanded="false" aria-controls="collapseEquityTokens">
                                Equity &amp; Tokens
                            </button>
                        </h2>
                        <div id="collapseEquityTokens" class="accordion-collapse collapse" aria-labelledby="headingEquityTokens" data-bs-parent="#moreMainAccordion">
                            <div class="accordion-body more-parent-body">
                                <div class="accordion custom-accordion equity-accordion" id="moreEquityTokensAccordion">

                                    <div class="accordion-item mb-3">
                                        <h2 class="accordion-header" id="headingTokenFaqWhereExactlyDoI">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTokenFaqWhereExactlyDoI" aria-expanded="false" aria-controls="collapseTokenFaqWhereExactlyDoI">
                                                <span class="question-text">Where exactly do I see my token balance?</span>
                                            </button>
                                        </h2>
                                        <div id="collapseTokenFaqWhereExactlyDoI" class="accordion-collapse collapse" aria-labelledby="headingTokenFaqWhereExactlyDoI">
                                            <div class="accordion-body"><p>Open your Equity page. It shows your Total Equity Shares, Current Share Value, and Total Equity Value, with a breakdown by Level 1 (Direct Influence), Level 2 (Expanding Reach), Level 3 (Networking Momentum), Kick Bonus, and Live Bonus — plus an Influencer&#039;s Earned Equity Chart filterable by Today / This Week / This Month / This Year / All.</p></div>
                                        </div>
                                    </div>

                                    <div class="accordion-item mb-3">
                                        <h2 class="accordion-header" id="headingTokenFaqHowDoIKnow">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTokenFaqHowDoIKnow" aria-expanded="false" aria-controls="collapseTokenFaqHowDoIKnow">
                                                <span class="question-text">How do I know when my first tokens reach maturity?</span>
                                            </button>
                                        </h2>
                                        <div id="collapseTokenFaqHowDoIKnow" class="accordion-collapse collapse" aria-labelledby="headingTokenFaqHowDoIKnow">
                                            <div class="accordion-body"><p>Each share you earn is recorded at the moment of earning, on its own timeline. With a two-year holding period on all equity awarded under this program, any individual share matures on the second anniversary of when it was recorded.</p></div>
                                        </div>
                                    </div>

                                    <div class="accordion-item mb-3">
                                        <h2 class="accordion-header" id="headingTokenFaqCanIGiftOr">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTokenFaqCanIGiftOr" aria-expanded="false" aria-controls="collapseTokenFaqCanIGiftOr">
                                                <span class="question-text">Can I gift or transfer tokens before maturity?</span>
                                            </button>
                                        </h2>
                                        <div id="collapseTokenFaqCanIGiftOr" class="accordion-collapse collapse" aria-labelledby="headingTokenFaqCanIGiftOr">
                                            <div class="accordion-body"><p>No, token transfers are not allowed before maturity.</p></div>
                                        </div>
                                    </div>

                                    <div class="accordion-item mb-3">
                                        <h2 class="accordion-header" id="headingTokenFaqIsThereAMinimum">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTokenFaqIsThereAMinimum" aria-expanded="false" aria-controls="collapseTokenFaqIsThereAMinimum">
                                                <span class="question-text">Is there a minimum number of tokens before I can sell?</span>
                                            </button>
                                        </h2>
                                        <div id="collapseTokenFaqIsThereAMinimum" class="accordion-collapse collapse" aria-labelledby="headingTokenFaqIsThereAMinimum">
                                            <div class="accordion-body"><p>No there is no minimum number of tokens required, but there is a minimum of 2 years hold before you can actually sell.</p></div>
                                        </div>
                                    </div>

                                    <div class="accordion-item mb-3">
                                        <h2 class="accordion-header" id="headingTokenFaqWillIReceiveA">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTokenFaqWillIReceiveA" aria-expanded="false" aria-controls="collapseTokenFaqWillIReceiveA">
                                                <span class="question-text">Will I receive a statement showing tokens earned and maturity dates?</span>
                                            </button>
                                        </h2>
                                        <div id="collapseTokenFaqWillIReceiveA" class="accordion-collapse collapse" aria-labelledby="headingTokenFaqWillIReceiveA">
                                            <div class="accordion-body"><p>No there is no official statemant, this information is always available on the website within Equity section.</p></div>
                                        </div>
                                    </div>

                                    <div class="accordion-item mb-3">
                                        <h2 class="accordion-header" id="headingTokenFaqWhatPlatformDoI">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTokenFaqWhatPlatformDoI" aria-expanded="false" aria-controls="collapseTokenFaqWhatPlatformDoI">
                                                <span class="question-text">What platform do I use to sell my tokens when they mature?</span>
                                            </button>
                                        </h2>
                                        <div id="collapseTokenFaqWhatPlatformDoI" class="accordion-collapse collapse" aria-labelledby="headingTokenFaqWhatPlatformDoI">
                                            <div class="accordion-body"><p>Token sales platform is Tokeny (Apex).</p></div>
                                        </div>
                                    </div>

                                    <div class="accordion-item mb-3">
                                        <h2 class="accordion-header" id="headingTokenFaqWhatCurrencyWillI">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTokenFaqWhatCurrencyWillI" aria-expanded="false" aria-controls="collapseTokenFaqWhatCurrencyWillI">
                                                <span class="question-text">What currency will I receive when I sell my tokens?</span>
                                            </button>
                                        </h2>
                                        <div id="collapseTokenFaqWhatCurrencyWillI" class="accordion-collapse collapse" aria-labelledby="headingTokenFaqWhatCurrencyWillI">
                                            <div class="accordion-body"><p>USD.</p></div>
                                        </div>
                                    </div>

                                    <div class="accordion-item mb-3">
                                        <h2 class="accordion-header" id="headingTokenFaqCanISeeThe">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTokenFaqCanISeeThe" aria-expanded="false" aria-controls="collapseTokenFaqCanISeeThe">
                                                <span class="question-text">Can I see the current estimated value of my token balance?</span>
                                            </button>
                                        </h2>
                                        <div id="collapseTokenFaqCanISeeThe" class="accordion-collapse collapse" aria-labelledby="headingTokenFaqCanISeeThe">
                                            <div class="accordion-body"><p>Yes. Your Equity page shows Current Share Value alongside your Total Equity Shares, with Total Equity Value as the product of the two — updated live.</p></div>
                                        </div>
                                    </div>

                                </div><!-- /#moreEquityTokensAccordion -->
                            </div><!-- /.more-parent-body -->
                        </div>
                    </div><!-- /EQUITY & TOKENS -->

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
