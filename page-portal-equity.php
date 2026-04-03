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
        
        <div class="container py-2 the-gradient" id="portal-content" style="max-width: 1024px; padding-left: 20px; padding-right: 20px; padding-top: 240px;">

            <!-- Equity Content -->
            <div class="equity-page-content">
                
                <!-- Equity Header -->
                <div class="equity-header">
                    <div class="equity-header-top">
                        <img src="<?php echo get_template_directory_uri(); ?>/images/portal-equity.png" alt="Equity Icon" class="equity-icon">
                        <h1 class="equity-title">Equity</h1>
                    </div>
                </div>
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

                <div class="equity-section">
                <div class="equity-card">
                    <div class="equity-card-header">
                        <span class="equity-card-title">Earned Equity</span>
                        <span class="equity-card-toggle">&#x2335;</span>
                    </div>

                    <div class="equity-filter-wrap">
                        <div class="equity-gradient-divider"></div>
                        <div class="equity-filter-row">
                            <span class="equity-tab equity-filter-item active" data-period="week"><span class="equity-filter-check"></span>Week</span>
                            <span class="equity-tab equity-filter-item" data-period="month"><span class="equity-filter-check"></span>Month</span>
                            <span class="equity-tab equity-filter-item" data-period="year"><span class="equity-filter-check"></span>Year</span>
                            <span class="equity-tab equity-filter-item" data-period="all"><span class="equity-filter-check"></span>All</span>
                        </div>
                        <div class="equity-gradient-divider"></div>
                        <div class="equity-filter-row">
                            <div class="equity-legend-item equity-filter-item active" data-level="l1"><span class="equity-filter-check"></span><span>Level 1</span></div>
                            <div class="equity-legend-item equity-filter-item active" data-level="l2"><span class="equity-filter-check"></span><span>Level 2</span></div>
                            <div class="equity-legend-item equity-filter-item active" data-level="l3"><span class="equity-filter-check"></span><span>Level 3</span></div>
                        </div>
                        <div class="equity-filter-row">
                            <div class="equity-legend-item equity-filter-item active" data-level="kick"><span class="equity-filter-check"></span><span>KICK</span></div>
                            <div class="equity-legend-item equity-filter-item active" data-level="live"><span class="equity-filter-check"></span><span>Live Appearance</span></div>
                            <div class="equity-legend-item equity-filter-item" data-level="all"><span class="equity-filter-check"></span><span>All</span></div>
                        </div>
                    </div>

                    <div class="equity-panels">
                        <div class="equity-panel">
                            <div class="equity-panel-title">Earnings</div>
                            <div class="equity-panel-row"><span>Play</span><span id="equity-play-value">—</span></div>
                            <div class="equity-panel-row"><span>Shares Earned</span><span id="equity-shares-value">—</span></div>
                        </div>
                        <div class="equity-panel">
                            <div class="equity-panel-title">Share Price</div>
                            <div class="equity-panel-row"><span>Total Play</span><span id="equity-total-play-value">—</span></div>
                            <div class="equity-panel-divider"></div>
                            <div class="equity-chart-container">
                                <p style="text-align:center;color:rgba(255,255,255,0.5);font-size:14px;padding:40px 0;">[insert chart of InfluencerHQ stock price]</p>
                            </div>
                        </div>
                    </div>
                </div>
                </div><!-- /.equity-section -->

                <div class="equity-section" style="display:none">
                    <?php
                    $uid              = get_current_user_id();
                    $ihq_access       = get_user_meta( $uid, 'ihq_access_token',  true );
                    $ihq_id_token     = get_user_meta( $uid, 'ihq_id_token',      true );
                    $ihq_refresh      = get_user_meta( $uid, 'ihq_refresh_token', true );
                    $ihq_token_type   = get_user_meta( $uid, 'ihq_token_type',    true ) ?: 'Bearer';
                    $ihq_expires      = get_user_meta( $uid, 'ihq_token_expires', true );
                    $ihq_expires_dt   = $ihq_expires ? date( 'Y-m-d H:i:s', (int) $ihq_expires ) : null;
                    $current_user     = wp_get_current_user();

                    $sso_payload_preview = array(
                        'oauthLoginType' => 'InfluencerHq',
                        'payload'        => array(
                            'id'        => 'influencerhq-wpu-' . $uid,
                            'firstName' => get_user_meta( $uid, 'first_name', true ),
                            'lastName'  => get_user_meta( $uid, 'last_name',  true ),
                            'email'     => $current_user->user_email,
                        ),
                    );

                    $has_token = ! empty( $ihq_access );
                    ?>
                    <div class="equity-debug-block">
                        <div class="equity-debug-header">
                            <span>SSO Session — /account/oauth/start-session</span>
                            <span class="equity-debug-status <?php echo $has_token ? 'equity-debug-status--ok' : 'equity-debug-status--error'; ?>" id="sso-stored-badge">
                                <?php echo $has_token ? 'STORED' : 'NO TOKEN'; ?>
                            </span>
                        </div>

                        <!-- Stored token summary -->
                        <div id="sso-stored-summary">
                        <?php if ( $has_token ) : ?>
                            <pre class="equity-debug-pre" style="margin-bottom:8px;"><?php echo esc_html( json_encode( array(
                                'AccessToken'  => substr( $ihq_access,   0, 40 ) . '…',
                                'IdToken'      => substr( $ihq_id_token, 0, 40 ) . '…',
                                'RefreshToken' => $ihq_refresh ? substr( $ihq_refresh, 0, 40 ) . '…' : '(not stored)',
                                'TokenType'    => $ihq_token_type,
                                'ExpiresAt'    => $ihq_expires_dt,
                            ), JSON_PRETTY_PRINT ) ); ?></pre>
                        <?php else : ?>
                            <pre class="equity-debug-pre" style="color:#ff6b6b;margin-bottom:8px;">No tokens stored yet. Click "Run SSO Now" to fire the call and see the raw response.</pre>
                        <?php endif; ?>
                        </div>

                        <!-- Live fire button -->
                        <div style="margin-bottom:8px;">
                            <button id="sso-run-btn" class="equity-sso-btn">Run SSO Now</button>
                        </div>

                        <div class="equity-debug-header" style="margin-top:4px;">
                            <span>Payload Sent</span>
                        </div>
                        <pre class="equity-debug-pre" id="sso-payload-pre"><?php echo esc_html( json_encode( $sso_payload_preview, JSON_PRETTY_PRINT ) ); ?></pre>

                        <div class="equity-debug-header" style="margin-top:10px;">
                            <span>Raw API Response</span>
                            <span class="equity-debug-status" id="sso-status-badge"></span>
                        </div>
                        <pre class="equity-debug-pre" id="sso-response-pre">— click "Run SSO Now" to fire —</pre>
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

<?php 
get_template_part( 'template-parts/portal-scripts' );
?>



<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
(function () {
    var ajaxUrl        = <?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>;
    var nonce         = <?php echo wp_json_encode( wp_create_nonce( 'equity_chart_nonce' ) ); ?>;
    var ssoNonce      = <?php echo wp_json_encode( wp_create_nonce( 'equity_sso_debug_nonce' ) ); ?>;

    // influencerhq-wpu- prefixed user ID used in API paths.
    var wpuId         = <?php echo wp_json_encode( 'influencerhq-wpu-' . get_current_user_id() ); ?>;

    var chartInstance = null;
    var activePeriod  = 'week';
    var activeLevels  = ['l1', 'l2', 'l3', 'kick', 'live'];

    // ---------------------------------------------------------------
    // Fetch data from WP AJAX → api-ajax-calls.php handler
    // ---------------------------------------------------------------
    function fetchChartData() {
        var loading = document.querySelector('.equity-chart-loading');
        if (loading) loading.style.display = 'block';

        var levels  = activeLevels.length ? activeLevels.join(',') : 'all';
        var payload = {
            action : 'equity_chart_data',
            nonce  : nonce,
            period : activePeriod,
            levels : levels
        };

        // Show pending state immediately while the request is in-flight
        var requestEl  = document.getElementById('equity-debug-request');
        var responseEl = document.getElementById('equity-debug-response');
        var statusEl   = document.getElementById('equity-debug-status');
        if (requestEl)  requestEl.textContent  = JSON.stringify({ action: payload.action, period: payload.period, levels: levels, note: 'sending request…' }, null, 2);
        if (responseEl) responseEl.textContent = 'Waiting…';
        if (statusEl)   { statusEl.textContent = ''; statusEl.className = 'equity-debug-status'; }

        var formData = new FormData();
        formData.append('action', payload.action);
        formData.append('nonce',  payload.nonce);
        formData.append('period', payload.period);
        formData.append('levels', payload.levels);

        fetch(ajaxUrl, { method: 'POST', body: formData })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (loading) loading.style.display = 'none';

                // Populate request context from server-side _debug
                var dbg = res && res.data && res.data._debug ? res.data._debug : null;
                if (requestEl) requestEl.textContent = JSON.stringify(dbg || { period: activePeriod, levels: activeLevels }, null, 2);

                // Show raw API response data (strip internal _debug key)
                var displayData = res && res.data ? Object.assign({}, res.data) : res;
                if (displayData) delete displayData._debug;
                if (responseEl) responseEl.textContent = JSON.stringify(displayData, null, 2);

                // Hard AJAX/WP failure
                if (!res || !res.success) {
                    if (statusEl) { statusEl.textContent = 'ERROR'; statusEl.classList.add('equity-debug-status--error'); }
                    showNoData('Could not load equity data.');
                    return;
                }

                if (statusEl) { statusEl.textContent = '200 OK'; statusEl.classList.add('equity-debug-status--ok'); }

                // Map API response to panels
                var d = res.data;
                var playEl      = document.getElementById('equity-play-value');
                var sharesEl    = document.getElementById('equity-shares-value');
                var totalPlayEl = document.getElementById('equity-total-play-value');
                if (playEl)      playEl.textContent      = (d.totalPlay          != null) ? d.totalPlay          : '—';
                if (sharesEl)    sharesEl.textContent    = (d.totalSharesEarned  != null) ? d.totalSharesEarned  : '—';
                if (totalPlayEl) totalPlayEl.textContent = (d.totalPlay          != null) ? d.totalPlay          : '—';

                // Draw chart
                if (d.labels && d.datasets) {
                    drawChart(d.labels, d.datasets);
                } else {
                    showNoData('No activity recorded for the selected period and levels.');
                }
            })
            .catch(function (err) {
                if (loading) loading.style.display = 'none';
                var msg = err && err.message ? err.message : 'unknown error';
                if (requestEl)  requestEl.textContent  = JSON.stringify({ period: activePeriod, levels: activeLevels, note: 'fetch failed before server responded' }, null, 2);
                if (responseEl) responseEl.textContent = 'Fetch error: ' + msg;
                if (statusEl)   { statusEl.textContent = 'ERROR'; statusEl.classList.add('equity-debug-status--error'); }
                showNoData('Error loading data: ' + msg);
            });
    }

    // ---------------------------------------------------------------
    // Show a "no data" message inside the chart container
    // ---------------------------------------------------------------
    function showNoData(msg) {
        // Destroy any existing chart so the canvas is clean
        if (chartInstance) { chartInstance.destroy(); chartInstance = null; }
        var canvas = document.getElementById('equityChart');
        if (canvas) canvas.style.display = 'none';

        var container = document.querySelector('.equity-chart-container');
        if (!container) return;
        var existing = container.querySelector('.equity-no-data');
        if (existing) existing.remove();

        var el = document.createElement('div');
        el.className = 'equity-no-data';
        el.textContent = msg;
        container.appendChild(el);
    }

    // Clear the earnings panel back to dashes
    function clearEarnings() {
        ['equity-play-value', 'equity-shares-value', 'equity-total-play-value'].forEach(function (id) {
            var el = document.getElementById(id);
            if (el) el.textContent = '\u2014';
        });
    }

    // ---------------------------------------------------------------
    // Render / update Chart.js line chart
    // ---------------------------------------------------------------
    function drawChart(labels, datasets) {
        var canvas = document.getElementById('equityChart');
        if (!canvas) return;

        // Clear any previous no-data message and restore canvas
        var container = document.querySelector('.equity-chart-container');
        if (container) {
            var nd = container.querySelector('.equity-no-data');
            if (nd) nd.remove();
        }
        canvas.style.display = 'block';

        if (!datasets || !datasets.length) {
            showNoData('No activity recorded for the selected period and levels.');
            return;
        }

        if (chartInstance) {
            chartInstance.destroy();
            chartInstance = null;
        }

        chartInstance = new Chart(canvas, {
            type: 'line',
            data: { labels: labels, datasets: datasets },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: { duration: 300 },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        backgroundColor: 'rgba(18,19,25,0.95)',
                        titleColor: '#D4AF37',
                        bodyColor: '#ffffff',
                        borderColor: 'rgba(212,175,55,0.35)',
                        borderWidth: 1,
                    },
                },
                scales: {
                    x: {
                        grid:  { color: 'rgba(255,255,255,0.06)' },
                        ticks: { color: '#aaa', font: { size: 10 } },
                    },
                    y: {
                        grid:  { color: 'rgba(255,255,255,0.06)' },
                        ticks: { color: '#aaa', font: { size: 10 } },
                    },
                },
            },
        });
    }

    // ---------------------------------------------------------------
    // Period tab clicks
    // ---------------------------------------------------------------
    document.querySelectorAll('.equity-tab').forEach(function (tab) {
        tab.addEventListener('click', function () {
            document.querySelectorAll('.equity-tab').forEach(function (t) {
                t.classList.remove('active');
            });
            this.classList.add('active');
            activePeriod = this.dataset.period;
            fetchChartData();
        });
    });

    // ---------------------------------------------------------------
    // Legend item clicks — toggle individual levels in/out of chart
    // ---------------------------------------------------------------
    document.querySelectorAll('.equity-legend-item').forEach(function (item) {
        item.addEventListener('click', function () {
            var lvl = this.dataset.level;

            if (lvl === 'all') {
                // Toggle: if all are on, turn all off; otherwise turn all on
                var allOn = activeLevels.length === 5;
                activeLevels = allOn ? [] : ['l1', 'l2', 'l3', 'kick', 'live'];

                document.querySelectorAll('.equity-legend-item[data-level!="all"]').forEach(function (i) {
                    i.classList.toggle('active', !allOn);
                });
                this.classList.toggle('active', !allOn);
            } else {
                var idx = activeLevels.indexOf(lvl);
                if (idx > -1) {
                    activeLevels.splice(idx, 1);
                    this.classList.remove('active');
                } else {
                    activeLevels.push(lvl);
                    this.classList.add('active');
                }
                // Sync the 'All' indicator
                var allItem = document.querySelector('.equity-legend-item[data-level="all"]');
                if (allItem) allItem.classList.toggle('active', activeLevels.length === 5);
            }

            fetchChartData();
        });
    });

    // Initial load on DOM ready
    fetchChartData();

    // ---------------------------------------------------------------
    // Equity card collapse/expand toggles
    // ---------------------------------------------------------------
    document.querySelectorAll('.equity-card-header').forEach(function (header) {
        var card = header.closest('.equity-card');
        if (!card) return;
        header.style.cursor = 'pointer';
        card.classList.add('equity-card--open');
        header.addEventListener('click', function () {
            var isOpen = card.classList.contains('equity-card--open');
            var toggle = header.querySelector('.equity-card-toggle');
            Array.prototype.forEach.call(card.children, function (child) {
                if (child !== header) child.style.display = isOpen ? 'none' : '';
            });
            card.classList.toggle('equity-card--open', !isOpen);
            if (toggle) toggle.style.transform = isOpen ? 'rotate(-90deg)' : 'rotate(0deg)';
        });
    });

    // ---------------------------------------------------------------
    // SSO Debug button
    // ---------------------------------------------------------------
    var ssoBtn = document.getElementById('sso-run-btn');
    if (ssoBtn) {
        ssoBtn.addEventListener('click', function () {
            ssoBtn.disabled = true;
            ssoBtn.textContent = 'Running…';

            var statusBadge   = document.getElementById('sso-status-badge');
            var responsePre   = document.getElementById('sso-response-pre');
            var storedBadge   = document.getElementById('sso-stored-badge');
            var storedSummary = document.getElementById('sso-stored-summary');

            if (responsePre) responsePre.textContent = 'Waiting…';
            if (statusBadge) { statusBadge.textContent = ''; statusBadge.className = 'equity-debug-status'; }

            var fd = new FormData();
            fd.append('action', 'equity_sso_debug');
            fd.append('nonce',  ssoNonce);

            fetch(ajaxUrl, { method: 'POST', body: fd })
                .then(function (r) { return r.json(); })
                .then(function (res) {
                    ssoBtn.disabled    = false;
                    ssoBtn.textContent = 'Run SSO Now';

                    if (!res || !res.success) {
                        if (responsePre) responsePre.textContent = 'WP AJAX error: ' + JSON.stringify(res);
                        return;
                    }

                    var d = res.data;

                    // Show HTTP status badge
                    if (statusBadge) {
                        statusBadge.textContent = 'HTTP ' + d.http_status;
                        statusBadge.className   = 'equity-debug-status ' +
                            (d.http_status === 200 ? 'equity-debug-status--ok' : 'equity-debug-status--error');
                    }

                    // Show raw body
                    if (responsePre) responsePre.textContent = JSON.stringify(d.raw_body, null, 2);

                    // Update stored badge
                    if (storedBadge) {
                        storedBadge.textContent = d.stored ? 'STORED' : 'NOT STORED';
                        storedBadge.className   = 'equity-debug-status ' +
                            (d.stored ? 'equity-debug-status--ok' : 'equity-debug-status--error');
                    }

                    // Update stored summary
                    if (storedSummary && d.stored) {
                        storedSummary.innerHTML = '<p style="font-family:Be Vietnam Pro,sans-serif;font-size:11px;color:#53FC18;margin:0 0 8px;">Tokens stored successfully. Reload the page to see them.</p>';
                    }
                })
                .catch(function (err) {
                    ssoBtn.disabled    = false;
                    ssoBtn.textContent = 'Run SSO Now';
                    if (responsePre) responsePre.textContent = 'Fetch error: ' + (err && err.message ? err.message : err);
                });
        });
    }
}());
</script>

<?php get_footer(); ?>
<?php
get_footer();
