<?php
/**
 * Template Name: Portal Equity
 * Description: A custom template for displaying the equity information.
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

            <!-- Equity Content -->
            <div class="equity-page-content">
                
                <!-- Equity Header -->
                <div class="equity-header">
                    <div class="equity-header-top">
                        <img src="<?php echo get_template_directory_uri(); ?>/images/portal-equity.png" alt="Equity Icon" class="equity-icon">
                        <h1 class="equity-title">Equity</h1>
                    </div>
                </div>
                <div id="equity-results" class="hm-scroll-anchor" aria-hidden="true"></div>
                <div class="equity-intro">
                    <p>We believe influence deserves more than short-term payouts.</p>
                    <p>We believe your voice should create long-term value.</p>
                    <p>We believe those who drive the energy deserve to share in what they help build.</p>

                    <p><strong>Equity That Multiplies With Participation</strong></p>
                    <p>Every moment of verified play creates equity.</p>
                    <p>But your opportunity doesn't stop with your own audience.</p>
                    <p>Your equity grows as your followers increase their level of participation.</p>

                    <p><strong>Level 1 – Direct Influence</strong><br>You earn 1.5% of verified play from your followers.</p>
                    <p><strong>Level 2 – Expanding Reach</strong><br>As your community invites others, you earn 1.0% of their verified play.</p>
                    <p><strong>Level 3 – Network Momentum</strong><br>As participation continues to grow, you earn 0.5% of the next layer.</p>

                    <p>And when you are streaming live or appearing on stage:<br>
                    <strong>Bonus Equity</strong> – 1.0% of the play of everyone who plays along with your live KICK stream or live appearance on the World Network.</p>

                    <div style="text-align:center; margin-top: 24px; line-height: 1.8;">
                        <p style="margin:0;">Influence Builds</p>
                        <p style="margin:0;">Participation Compounds</p>
                        <p style="margin:0;">Equity Grows with Every Layer of Play</p>
                    </div>
                </div>
                <div id="equity-referrals" class="hm-scroll-anchor" aria-hidden="true"></div>

                <!-- Equity Accordion -->
                <div class="accordion-gradient-container">
                    <div class="accordion custom-accordion equity-accordion" id="equityInfoAccordion">

                        <!-- Why Equity Matters -->
                        <div class="accordion-item mb-3">
                            <h2 class="accordion-header" id="headingEquityMatters">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseEquityMatters" aria-expanded="true" aria-controls="collapseEquityMatters">
                                    <span class="question-text">Why Equity Matters</span>
                                </button>
                            </h2>
                            <div id="collapseEquityMatters" class="accordion-collapse collapse show" aria-labelledby="headingEquityMatters" data-bs-parent="#equityInfoAccordion">
                                <div class="accordion-body">
                                    <p>Since the beginning of recorded history, wealth has been built through ownership.</p>
                                    <p>In ancient Egypt, wealth was measured by ownership of land, control of agricultural production, and access to trade along the Nile. In ancient China, families accumulated and preserved wealth through land rights, merchant guild participation, and ownership in enterprises that lasted generations. In Mesopotamia, early written records document shared ownership, partnerships, and profit-sharing arrangements.</p>
                                    <p>Across civilizations, the pattern has always been the same:</p>
                                    <ul>
                                        <li>Income sustains life</li>
                                        <li>Ownership creates wealth</li>
                                        <li>Equity preserves it over time</li>
                                    </ul>
                                    <p>Equity is not a modern invention. It is the oldest wealth-building mechanism humanity has ever recorded.</p>
                                </div>
                            </div>
                        </div>

                        <!-- Why Equity Exists at All -->
                        <div class="accordion-item mb-3">
                            <h2 class="accordion-header" id="headingEquityExists">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseEquityExists" aria-expanded="false" aria-controls="collapseEquityExists">
                                    <span class="question-text">Why Equity Exists at All</span>
                                </button>
                            </h2>
                            <div id="collapseEquityExists" class="accordion-collapse collapse" aria-labelledby="headingEquityExists" data-bs-parent="#equityInfoAccordion">
                                <div class="accordion-body">
                                    <p>Businesses do not offer equity casually.</p>
                                    <p>They allow outside participation only when growth requires capital, reach, or contribution beyond what founders alone can provide. Equity aligns participants with the long-term success of the business.</p>
                                    <p>Equity is not payment for effort alone. It is participation in future value.</p>
                                    <p><strong>EQUITY PARTICIPATION REQUIRES STRUCTURE, MEASUREMENT, AND ACCOUNTABILITY. WITHOUT THOSE ELEMENTS, IT CANNOT SCALE.</strong></p>
                                </div>
                            </div>
                        </div>

                        <!-- Income vs. Equity -->
                        <div class="accordion-item mb-3">
                            <h2 class="accordion-header" id="headingIncomeEquity">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseIncomeEquity" aria-expanded="false" aria-controls="collapseIncomeEquity">
                                    <span class="question-text">Income vs. Equity</span>
                                </button>
                            </h2>
                            <div id="collapseIncomeEquity" class="accordion-collapse collapse" aria-labelledby="headingIncomeEquity" data-bs-parent="#equityInfoAccordion">
                                <div class="accordion-body">
                                    <p><strong>Why a Dollar of Income and a Dollar of Equity Are Not the Same</strong></p>
                                    <p>A dollar of income is static. It is earned once, spent once, and gone.</p>
                                    <p>A dollar of equity behaves differently. Over time, ownership can grow far beyond its original value.</p>
                                    <p>Businesses are often valued at 5×, 10×, or 20× earnings, meaning ownership can be worth far more than the original amount invested.</p>
                                    <p><strong>VALUATIONS REFLECT EXPECTATIONS ABOUT FUTURE PERFORMANCE, NOT GUARANTEES.</strong></p>
                                </div>
                            </div>
                        </div>

                        <!-- Growth Over Time -->
                        <div class="accordion-item mb-3">
                            <h2 class="accordion-header" id="headingGrowth">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseGrowth" aria-expanded="false" aria-controls="collapseGrowth">
                                    <span class="question-text">Growth Over Time</span>
                                </button>
                            </h2>
                            <div id="collapseGrowth" class="accordion-collapse collapse" aria-labelledby="headingGrowth" data-bs-parent="#equityInfoAccordion">
                                <div class="accordion-body">
                                    <p>A valuation reflects a moment in time.</p>
                                    <p>The real strength of equity is what happens as a business improves year after year.</p>
                                    <p>When performance improves:</p>
                                    <ul>
                                        <li>earnings increase</li>
                                        <li>confidence in the future improves</li>
                                        <li>more people want to own the business</li>
                                    </ul>
                                    <p>As demand for ownership rises, the value of each share increases.</p>
                                    <p>This is why ownership can grow steadily over time — not because of a single good year, but because future prospects continue to improve.</p>
                                    <p>Equity captures value across time, not just at a moment.</p>
                                </div>
                            </div>
                        </div>

                        <!-- From Gold to Digital Ownership -->
                        <div class="accordion-item mb-3">
                            <h2 class="accordion-header" id="headingDigitalOwnership">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseDigitalOwnership" aria-expanded="false" aria-controls="collapseDigitalOwnership">
                                    <span class="question-text">From Gold to Digital Ownership</span>
                                </button>
                            </h2>
                            <div id="collapseDigitalOwnership" class="accordion-collapse collapse" aria-labelledby="headingDigitalOwnership" data-bs-parent="#equityInfoAccordion">
                                <div class="accordion-body">
                                    <p>For thousands of years, gold and silver served as standards of value. Later, ownership in businesses was represented by paper stock certificates.</p>
                                    <p>Today, ownership is evolving again.</p>
                                    <p>Equity is moving toward digital representation, including tokenization — not as a disruption, but as a continuation of history: a more precise, transparent, and globally accessible way to represent ownership.</p>
                                    <p>The principle has not changed. Only the medium has evolved.</p>
                                </div>
                            </div>
                        </div>

                        <!-- Promotion Has Always Been Compensated -->
                        <div class="accordion-item mb-3">
                            <h2 class="accordion-header" id="headingPromotionCompensated">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapsePromotionCompensated" aria-expanded="false" aria-controls="collapsePromotionCompensated">
                                    <span class="question-text">Promotion Has Always Been Compensated</span>
                                </button>
                            </h2>
                            <div id="collapsePromotionCompensated" class="accordion-collapse collapse" aria-labelledby="headingPromotionCompensated" data-bs-parent="#equityInfoAccordion">
                                <div class="accordion-body">
                                    <p>Businesses have always compensated those who help them grow.</p>
                                    <p>For decades, movie stars, sports icons, and music artists were paid cash to promote products and services. In rare cases, a few negotiated something more powerful: equity.</p>
                                    <p>Those arrangements produced outcomes far greater than traditional fees.</p>
                                    <p><strong>THE STRUCTURE — NOT CELEBRITY STATUS — CREATED THOSE OUTCOMES.</strong></p>
                                </div>
                            </div>
                        </div>

                        <!-- Equity Deals Are Real — But Rare -->
                        <div class="accordion-item mb-3">
                            <h2 class="accordion-header" id="headingEquityDeals">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseEquityDeals" aria-expanded="false" aria-controls="collapseEquityDeals">
                                    <span class="question-text">Equity Deals Are Real — But Rare</span>
                                </button>
                            </h2>
                            <div id="collapseEquityDeals" class="accordion-collapse collapse" aria-labelledby="headingEquityDeals" data-bs-parent="#equityInfoAccordion">
                                <div class="accordion-body">
                                    <p>Historically, equity participation has been:</p>
                                    <ul>
                                        <li>privately negotiated</li>
                                        <li>limited to insiders</li>
                                        <li>reserved for a small group</li>
                                    </ul>
                                    <p>The structure — not celebrity — created the outcome.</p>
                                    <p>Most influencers have never been presented with this option.</p>
                                </div>
                            </div>
                        </div>

                        <!-- What Actually Changed -->
                        <div class="accordion-item mb-3">
                            <h2 class="accordion-header" id="headingChanged">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseChanged" aria-expanded="false" aria-controls="collapseChanged">
                                    <span class="question-text">What Actually Changed</span>
                                </button>
                            </h2>
                            <div id="collapseChanged" class="accordion-collapse collapse" aria-labelledby="headingChanged" data-bs-parent="#equityInfoAccordion">
                                <div class="accordion-body">
                                    <p>The change was not generosity.</p>
                                    <p>The change was technology.</p>
                                    <p>Modern platforms make participation measurable, trackable, and scalable. Contribution can now be observed in real time.</p>
                                    <p>In theory, this should have expanded access to equity. In practice, it largely has not.</p>
                                </div>
                            </div>
                        </div>

                        <!-- Why Most Influencers Are Still Paid in Cash -->
                        <div class="accordion-item mb-3">
                            <h2 class="accordion-header" id="headingCash">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseCash" aria-expanded="false" aria-controls="collapseCash">
                                    <span class="question-text">Why Most Influencers Are Still Paid in Cash</span>
                                </button>
                            </h2>
                            <div id="collapseCash" class="accordion-collapse collapse" aria-labelledby="headingCash" data-bs-parent="#equityInfoAccordion">
                                <div class="accordion-body">
                                    <p>Most influencers are advised to take cash not because equity lacks value, but because the system around them favors short-term income.</p>
                                    <p>Agencies monetize cash immediately. Equity represents future value rather than instant payment.</p>
                                    <p>As a result, long-term ownership remains inaccessible to most creators.</p>
                                </div>
                            </div>
                        </div>

                        <!-- Where Influencers Look for Equity Today -->
                        <div class="accordion-item mb-3">
                            <h2 class="accordion-header" id="headingWhereEquity">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseWhereEquity" aria-expanded="false" aria-controls="collapseWhereEquity">
                                    <span class="question-text">Where Influencers Look for Equity Today</span>
                                </button>
                            </h2>
                            <div id="collapseWhereEquity" class="accordion-collapse collapse" aria-labelledby="headingWhereEquity" data-bs-parent="#equityInfoAccordion">
                                <div class="accordion-body">
                                    <p>Influencers seeking equity must navigate private negotiations, limited opportunities, and permission-based access.</p>
                                    <p>The process is fragmented, inconsistent, and constrained.</p>
                                    <p><strong>THIS PLATFORM INTRODUCES A STRUCTURED, MEASURABLE MODEL FOR EQUITY PARTICIPATION.</strong></p>
                                </div>
                            </div>
                        </div>

                        <!-- Per-Share Pricing and Why It Was Chosen -->
                        <div class="accordion-item mb-3">
                            <h2 class="accordion-header" id="headingSharePricing">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSharePricing" aria-expanded="false" aria-controls="collapseSharePricing">
                                    <span class="question-text">Per-Share Pricing and Why It Was Chosen</span>
                                </button>
                            </h2>
                            <div id="collapseSharePricing" class="accordion-collapse collapse" aria-labelledby="headingSharePricing" data-bs-parent="#equityInfoAccordion">
                                <div class="accordion-body">
                                    <p>More than $80 million has been invested in building the software, competition system, and infrastructure behind this platform. On a traditional basis, that investment could support a higher starting share price.</p>
                                    <p>That is not the price being offered.</p>
                                    <p>Before any public market availability, InfluencerHQ is offering equity participation at $0.20 per share.</p>
                                    <p>This pricing was chosen deliberately.</p>
                                </div>
                            </div>
                        </div>

                        <!-- Why a Higher Starting Valuation Was Rejected -->
                        <div class="accordion-item mb-3">
                            <h2 class="accordion-header" id="headingValuation">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseValuation" aria-expanded="false" aria-controls="collapseValuation">
                                    <span class="question-text">Why a Higher Starting Valuation Was Rejected</span>
                                </button>
                            </h2>
                            <div id="collapseValuation" class="accordion-collapse collapse" aria-labelledby="headingValuation" data-bs-parent="#equityInfoAccordion">
                                <div class="accordion-body">
                                    <p>We modeled higher starting valuations. Those scenarios consistently produced less participation and less long-term upside.</p>
                                    <p>Higher entry prices limit access and reduce the ability for ownership value to grow over time.</p>
                                    <p><strong>THE OBJECTIVE IS BROAD PARTICIPATION, NOT MAXIMUM INITIAL PRICING.</strong></p>
                                </div>
                            </div>
                        </div>

                        <!-- Cost to Build vs. Value to Be Created -->
                        <div class="accordion-item mb-3">
                            <h2 class="accordion-header" id="headingCost">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseCost" aria-expanded="false" aria-controls="collapseCost">
                                    <span class="question-text">Cost to Build vs. Value to Be Created</span>
                                </button>
                            </h2>
                            <div id="collapseCost" class="accordion-collapse collapse" aria-labelledby="headingCost" data-bs-parent="#equityInfoAccordion">
                                <div class="accordion-body">
                                    <p>Past investment reflects the cost to build the platform. Equity value is created by future participation, growth, and performance.</p>
                                    <p>This structure aligns contributors with the value they help create.</p>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="equity-section">
                    <div class="equity-card">
                        <div class="equity-card-header">
                            <span class="equity-card-title">Equity Attribution</span>
                            <span class="equity-card-toggle">▾</span>
                        </div>
                        <div class="equity-attribution-grid">
                            <div class="equity-attribution-row"><span>Level 1 (L1)</span><span>1.5% of Verified Play</span></div>
                            <div class="equity-attribution-row"><span>Level 2 (L2)</span><span>1.0% of Verified Play</span></div>
                            <div class="equity-attribution-row"><span>Level 3 (L3)</span><span>0.5% of Verified Play</span></div>
                            <div class="equity-attribution-row"><span>KICK Bonus</span><span>1.0% of Verified Play</span></div>
                            <div class="equity-attribution-row"><span>Live Appearance Bonus</span><span>1.0% of Verified Play</span></div>
                        </div>
                    </div>
                </div>

                <h2 style="text-align: center;">Insert design here</h2>

            </div>

        </div>

        <!-- Fixed Footer Links -->
        <?php get_template_part( 'template-parts/portal-footer' ); ?>
    </main><!-- #main -->

<?php 
get_template_part( 'template-parts/portal-scripts' );
?>



<?php
get_footer();
