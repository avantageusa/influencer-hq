<?php
/**
 * Template Name: Portal Equity
 * Description: A custom template for displaying the equity information.
 *
 * @package influencer-hq
 */
get_header();

// Load styles before content to prevent FOUC
get_template_part( 'template-parts/portal-styles' );

$portal_equity_iframe_url = ihq_build_hq_game_portal_external_url( '/external/equity' );
$equity_attribution_expanded = is_user_logged_in();
?>

    <main id="primary" class="site-main">
        
        <?php get_template_part( 'template-parts/portal-header' ); ?>
        
        <div class="container py-2 the-gradient" id="portal-content" style="max-width: 1024px; padding-left: 20px; padding-right: 20px;">

            <!-- Equity Content -->
            <div class="equity-page-content">
                
                <!-- Equity Header -->
                <div class="equity-header">
                    <div class="equity-header-top">
                        <img src="<?php echo esc_url( get_template_directory_uri() ); ?>/images/portal-equity.png" alt="" class="equity-icon">
                        <h1 class="equity-title">Equity</h1>
                    </div>
                </div>

                <section class="equity-intro" aria-labelledby="equity-believe-heading">
                    <div class="equity-intro-believe">
                        <p id="equity-believe-heading" class="equity-intro-believe-label">We Believe</p>
                        <p class="equity-intro-believe-line">Influence Deserves More Than Short-Term Payouts.</p>
                        <p class="equity-intro-believe-line">Your Voice Should Create Long-Term Value.</p>
                        <p class="equity-intro-believe-line">Those Who Drive The Energy Deserve To Share In What They Help Build.</p>
                    </div>

                    <div class="equity-intro-multiplies">
                        <h2 class="equity-intro-multiplies-title">How Equity Multiplies With Follower Participation</h2>
                        <ul class="equity-intro-list">
                            <li class="equity-intro-item">
                                <img src="<?php echo esc_url( get_template_directory_uri() ); ?>/images/logo-hero-red-small.png" alt="" class="equity-intro-icon" width="24" height="24">
                                <div class="equity-intro-copy">
                                    <strong>Level 1 – Direct Influence</strong>
                                    <p>You earn 1.5% of play from your followers.</p>
                                </div>
                            </li>
                            <li class="equity-intro-item">
                                <img src="<?php echo esc_url( get_template_directory_uri() ); ?>/images/logo-hero-red-small.png" alt="" class="equity-intro-icon" width="24" height="24">
                                <div class="equity-intro-copy">
                                    <strong>Level 2 – Expanding Reach</strong>
                                    <p>As your community invites others, you earn 1.0% of their play.</p>
                                </div>
                            </li>
                            <li class="equity-intro-item">
                                <img src="<?php echo esc_url( get_template_directory_uri() ); ?>/images/logo-hero-red-small.png" alt="" class="equity-intro-icon" width="24" height="24">
                                <div class="equity-intro-copy">
                                    <strong>Level 3 – Network Momentum</strong>
                                    <p>As participation continues to grow, you earn 0.5% of the next layer.</p>
                                </div>
                            </li>
                            <li class="equity-intro-item">
                                <img src="<?php echo esc_url( get_template_directory_uri() ); ?>/images/logo-hero-red-small.png" alt="" class="equity-intro-icon" width="24" height="24">
                                <div class="equity-intro-copy">
                                    <strong>KICK Stream</strong>
                                    <p>1.0% of the play of everyone who plays along.</p>
                                </div>
                            </li>
                            <li class="equity-intro-item">
                                <img src="<?php echo esc_url( get_template_directory_uri() ); ?>/images/logo-hero-red-small.png" alt="" class="equity-intro-icon" width="24" height="24">
                                <div class="equity-intro-copy">
                                    <strong>World Network Appearance</strong>
                                    <p>1.0% of the play of everyone who plays along.</p>
                                </div>
                            </li>
                        </ul>
                    </div>

                    <div class="equity-intro-closing">
                        <p>Influence Builds</p>
                        <p>Participation Compounds</p>
                        <p>Equity Grows with Every Layer of Play</p>
                    </div>
                </section>

                <div class="equity-section" id="equity-earned">
                    <div class="equity-card<?php echo $equity_attribution_expanded ? '' : ' ihq-gate-collapsed'; ?>" id="equityAttributionCard">
                        <div
                            class="equity-card-header"
                            id="equityAttributionHead"
                            role="button"
                            tabindex="0"
                            aria-expanded="<?php echo $equity_attribution_expanded ? 'true' : 'false'; ?>"
                            aria-controls="equityAttributionBody"
                        >
                            <span class="equity-card-title"><?php esc_html_e( 'Equity Attribution', 'influencer-hq' ); ?></span>
                            <span class="equity-card-toggle" aria-hidden="true"><?php echo $equity_attribution_expanded ? '▴' : '▾'; ?></span>
                        </div>
                        <div class="equity-card-body" id="equityAttributionBody"<?php echo $equity_attribution_expanded ? '' : ' hidden'; ?>>
                            <div class="portal-equity-iframe-wrap" id="equity-external-embed">
                                <?php if ( $equity_attribution_expanded ) : ?>
                                <iframe
                                    title="<?php echo esc_attr__( 'Influencer HQ equity', 'influencer-hq' ); ?>"
                                    src="<?php echo esc_url( $portal_equity_iframe_url ); ?>"
                                    loading="lazy"
                                    referrerpolicy="strict-origin-when-cross-origin"
                                    allowfullscreen
                                ></iframe>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="equity-results" class="hm-scroll-anchor" aria-hidden="true"></div>

                <!-- Equity Accordion -->
                <div class="accordion-gradient-container">
                    <div class="accordion custom-accordion equity-accordion" id="equityInfoAccordion">

                        <!-- How To Earn Equity -->
                        <div class="accordion-item mb-3 equity-earn-item" id="how-to-earn-equity">
                            <h2 class="accordion-header" id="headingEquityEarn">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseEquityEarn" aria-expanded="true" aria-controls="collapseEquityEarn">
                                    <span class="question-text">How To Earn Equity?</span>
                                </button>
                            </h2>
                            <div id="collapseEquityEarn" class="accordion-collapse collapse show" aria-labelledby="headingEquityEarn" data-bs-parent="#equityInfoAccordion">
                                <div class="accordion-body equity-earn-body">
                                    <ul class="equity-earn-list equity-earn-list--actions">
                                        <li class="equity-earn-item-row">
                                            <img src="<?php echo esc_url( get_template_directory_uri() ); ?>/images/logo-hero-red-small.png" alt="" class="equity-earn-icon" width="22" height="22">
                                            <span>Invite your friends</span>
                                        </li>
                                        <li class="equity-earn-item-row">
                                            <img src="<?php echo esc_url( get_template_directory_uri() ); ?>/images/logo-hero-red-small.png" alt="" class="equity-earn-icon" width="22" height="22">
                                            <span>Create Group Challenges</span>
                                        </li>
                                    </ul>

                                    <p class="equity-earn-watch"><?php esc_html_e( 'WATCH YOUR EQUITY TOTAL COME ALIVE', 'influencer-hq' ); ?></p>

                                    <div class="equity-earn-crowd">
                                        <img
                                            src="<?php echo esc_url( get_template_directory_uri() ); ?>/images/equity-crowd-silhouette.png"
                                            alt=""
                                            class="equity-earn-crowd-img"
                                            width="1456"
                                            height="513"
                                        >
                                    </div>

                                    <p class="equity-earn-circle"><?php esc_html_e( 'Your company. Your closest friends. Your hobby group. Your football buddies. Your whole circle.', 'influencer-hq' ); ?></p>

                                    <ul class="equity-earn-list equity-earn-list--rates">
                                        <li class="equity-earn-item-row">
                                            <img src="<?php echo esc_url( get_template_directory_uri() ); ?>/images/logo-hero-red-small.png" alt="" class="equity-earn-icon" width="22" height="22">
                                            <span>Personal: 1% from your own play.</span>
                                        </li>
                                        <li class="equity-earn-item-row">
                                            <img src="<?php echo esc_url( get_template_directory_uri() ); ?>/images/logo-hero-red-small.png" alt="" class="equity-earn-icon" width="22" height="22">
                                            <span>Level 1: 1.5% of the play of your followers.</span>
                                        </li>
                                        <li class="equity-earn-item-row">
                                            <img src="<?php echo esc_url( get_template_directory_uri() ); ?>/images/logo-hero-red-small.png" alt="" class="equity-earn-icon" width="22" height="22">
                                            <span>Level 2: 1.0% of the play of Level 1 referrals.</span>
                                        </li>
                                        <li class="equity-earn-item-row">
                                            <img src="<?php echo esc_url( get_template_directory_uri() ); ?>/images/logo-hero-red-small.png" alt="" class="equity-earn-icon" width="22" height="22">
                                            <span>Level 3: 0.5% of the play of Level 2 referrals.</span>
                                        </li>
                                        <li class="equity-earn-item-row equity-earn-item-row--bonus">
                                            <img src="<?php echo esc_url( get_template_directory_uri() ); ?>/images/logo-hero-red-small.png" alt="" class="equity-earn-icon" width="22" height="22">
                                            <span>
                                                Bonus: 1% of the play of everyone playing along on your World Network streams.
                                                <button
                                                    type="button"
                                                    class="equity-earn-info"
                                                    aria-label="<?php esc_attr_e( 'More information about World Network bonus equity', 'influencer-hq' ); ?>"
                                                    title="<?php esc_attr_e( '1% Bonus Equity on verified play of everyone who plays along during your World Network appearance.', 'influencer-hq' ); ?>"
                                                >i</button>
                                            </span>
                                        </li>
                                    </ul>

                                    <p class="equity-earn-closing"><?php esc_html_e( 'Your influence was never meant to be rented. It was meant to be owned.', 'influencer-hq' ); ?></p>
                                    <p class="equity-earn-closing"><?php esc_html_e( "Every equity share is recorded the moment you earn it. This isn't a promotion. It's a seat at the table.", 'influencer-hq' ); ?></p>
                                </div>
                            </div>
                        </div>

                        <!-- Why Equity Matters -->
                        <div class="accordion-item mb-3">
                            <h2 class="accordion-header" id="headingEquityMatters">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseEquityMatters" aria-expanded="false" aria-controls="collapseEquityMatters">
                                    <span class="question-text">Why Equity Matters</span>
                                </button>
                            </h2>
                            <div id="collapseEquityMatters" class="accordion-collapse collapse" aria-labelledby="headingEquityMatters" data-bs-parent="#equityInfoAccordion">
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

                <div id="equity-referrals" class="hm-scroll-anchor" aria-hidden="true"></div>

            </div>

        </div>

        <!-- Fixed Footer Links -->
        <?php get_template_part( 'template-parts/portal-footer' ); ?>
    </main><!-- #main -->

<?php if ( is_user_logged_in() ) : ?>
<script>
(function () {
    var head = document.getElementById('equityAttributionHead');
    var body = document.getElementById('equityAttributionBody');
    if (!head || !body) {
        return;
    }

    var toggle = head.querySelector('.equity-card-toggle');

    function setExpanded(isExpanded) {
        body.hidden = !isExpanded;
        head.setAttribute('aria-expanded', isExpanded ? 'true' : 'false');
        if (toggle) {
            toggle.textContent = isExpanded ? '\u25B4' : '\u25BE';
        }
        head.closest('.equity-card').classList.toggle('ihq-gate-collapsed', !isExpanded);
    }

    function onToggle() {
        setExpanded(body.hidden);
    }

    head.addEventListener('click', onToggle);
    head.addEventListener('keydown', function (event) {
        if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            onToggle();
        }
    });

    window.ihqEquitySetAttributionExpanded = setExpanded;
})();
</script>
<?php endif; ?>

<script>
(function () {
    function portalScrollToId(id) {
        var el = document.getElementById(id);
        if (!el) {
            return;
        }
        if (typeof window.portalScrollSmoothToElement === 'function') {
            window.portalScrollSmoothToElement(el);
            return;
        }
        el.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function applyEquityHash() {
        var hash = window.location.hash.replace(/^#/, '');
        if (!hash) {
            return;
        }

        if (hash === 'how-to-earn-equity') {
            var earnCollapse = document.getElementById('collapseEquityEarn');
            if (earnCollapse && window.bootstrap && bootstrap.Collapse) {
                bootstrap.Collapse.getOrCreateInstance(earnCollapse).show();
            }
            window.setTimeout(function () {
                portalScrollToId('how-to-earn-equity');
            }, 80);
            return;
        }

        if (hash === 'equity-earned') {
            if (typeof window.ihqEquitySetAttributionExpanded === 'function') {
                window.ihqEquitySetAttributionExpanded(true);
            }
            window.setTimeout(function () {
                portalScrollToId('equity-earned');
            }, 80);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', applyEquityHash);
    } else {
        applyEquityHash();
    }
    window.addEventListener('hashchange', applyEquityHash);
})();
</script>

<?php 
get_template_part( 'template-parts/portal-scripts' );
?>



<?php
get_footer();
