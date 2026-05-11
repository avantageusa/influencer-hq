<?php
/**
 * Template Name: Portal home
 * Description: A custom template for displaying the influencer HQ.
 * This template is used to render the influencer HQ content and layout.
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package Avantage_Baccarat
 */
get_header();

// Load styles before content to prevent FOUC
get_template_part( 'template-parts/portal-styles' );
?>

    <main id="primary" class="site-main">
        
        <?php get_template_part( 'template-parts/portal-header' ); ?>
        <?php
            $cf_country = strtoupper( $_SERVER['HTTP_CF_IPCOUNTRY'] ?? 'US' );
            $phone_map  = [
                'RS' => [ 'href' => '+381601234567',  'label' => '+381 60 123-4567' ],
                'NL' => [ 'href' => '+311234567890',  'label' => '+31 123 456-7890' ],
            ];
            $phone = $phone_map[ $cf_country ] ?? [ 'href' => '+11234567890', 'label' => '123-456-7890' ];
        ?>
        <!-- Dealer Image (Home Page Only) -->
        <div class="dealer-row">
            <div class="dealer-image-container">
                <p class="concierge-text-above">We believe conversations should be easy.</p>
                <div class="dealer-image-wrap">
                    <img src="<?php echo get_template_directory_uri(); ?>/images/concierge.png" alt="Casino Dealer" class="dealer-image dealer-image--fade-bottom">
                </div>
                <a href="#" class="concierge-title">Talk Now - Executive Concierge</a>
            </div>
        </div>
        
        <div class="container py-2 the-gradient" style="max-width: 1024px; padding-left: 20px; padding-right: 20px;padding-top:20px!important;">


                    <?php
                    // Show registration summary panel on first arrival after email verification
                    if (isset($_GET['welcome']) && $_GET['welcome'] === 'true' && is_user_logged_in()) :
                        $uid             = get_current_user_id();
                        $first_name      = get_user_meta($uid, 'first_name', true);
                        $last_name       = get_user_meta($uid, 'last_name', true);
                        $platform_handle = get_user_meta($uid, 'platform_handle', true);
                        $comm_methods    = get_user_meta($uid, 'communication_methods', true);
                        $challenge_type  = get_user_meta($uid, 'challenge_type', true);
                        $reg_date        = get_user_meta($uid, 'registration_date', true);
                        $email_verified  = get_user_meta($uid, 'email_verified', true);
                        $ihq_access      = get_user_meta($uid, 'ihq_access_token', true);
                        $ihq_id_token    = get_user_meta($uid, 'ihq_id_token', true);
                        $ihq_expires     = get_user_meta($uid, 'ihq_token_expires', true);
                        $current_user    = wp_get_current_user();

                        $ihq_payload_sent = array(
                            'oauthLoginType' => 'InfluencerHq',
                            'payload' => array(
                                'id'        => 'wpu-' . $uid,
                                'firstName' => $first_name,
                                'lastName'  => $last_name,
                                'email'     => $current_user->user_email,
                            ),
                        );
                        $ihq_response_stored = array(
                            'AccessToken' => $ihq_access  ? substr($ihq_access, 0, 40)   . '…' : null,
                            'IdToken'     => $ihq_id_token ? substr($ihq_id_token, 0, 40) . '…' : null,
                            'ExpiresAt'   => $ihq_expires ? date('Y-m-d H:i:s', $ihq_expires) : null,
                            'stored'      => !empty($ihq_access),
                        );
                    ?>
                    <div style="margin-bottom:28px;">
                        <h3 style="color:#ffd700;font-size:1.4rem;margin-bottom:16px;">🎉 Welcome, <?php echo esc_html($first_name ?: $current_user->user_login); ?>! Your account is ready.</h3>

                        <!-- Profile -->
                        <div style="background:rgba(255,255,255,.05);border:1px solid rgba(255,215,0,.2);border-radius:10px;padding:18px;margin-bottom:12px;">
                            <p style="color:#ffd700;font-size:.72rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;margin-bottom:10px;">Your Profile</p>
                            <table style="width:100%;font-size:.88rem;color:#e8e8e8;border-collapse:collapse;">
                                <tr><td style="color:#888;padding:3px 0;width:150px;">WP ID</td><td><?php echo esc_html($uid); ?></td></tr>
                                <tr><td style="color:#888;padding:3px 0;">IHQ ID</td><td>wpu-<?php echo esc_html($uid); ?></td></tr>
                                <tr><td style="color:#888;padding:3px 0;">Email</td><td><?php echo esc_html($current_user->user_email); ?></td></tr>
                                <tr><td style="color:#888;padding:3px 0;">Name</td><td><?php echo esc_html(trim($first_name . ' ' . $last_name)); ?></td></tr>
                                <?php if ($platform_handle): ?><tr><td style="color:#888;padding:3px 0;">Handle</td><td><?php echo esc_html($platform_handle); ?></td></tr><?php endif; ?>
                                <?php if ($challenge_type): ?><tr><td style="color:#888;padding:3px 0;">Challenge</td><td><?php echo esc_html($challenge_type); ?></td></tr><?php endif; ?>
                                <tr><td style="color:#888;padding:3px 0;">Registered</td><td><?php echo esc_html($reg_date ?: '—'); ?></td></tr>
                                <tr><td style="color:#888;padding:3px 0;">Email Verified</td><td><?php echo $email_verified ? '<span style="color:#3fb950">✓ Yes</span>' : '<span style="color:#f85149">✗ No</span>'; ?></td></tr>
                                <?php if (!empty($comm_methods) && is_array($comm_methods)): ?>
                                <tr><td style="color:#888;padding:3px 0;vertical-align:top;">Comm Methods</td><td><?php foreach($comm_methods as $m=>$v) echo esc_html(ucfirst($m).': '.$v).'<br>'; ?></td></tr>
                                <?php endif; ?>
                            </table>
                        </div>

                        <!-- OAuth Payload sent -->
                        <div style="background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1);border-radius:10px;padding:18px;margin-bottom:12px;">
                            <p style="color:#ffd700;font-size:.72rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;margin-bottom:10px;">IHQ OAuth — Payload Sent</p>
                            <pre style="background:#0a0a0a;border-radius:6px;padding:12px;font-size:.78rem;color:#a5d6ff;overflow:auto;margin:0;"><?php echo esc_html(json_encode($ihq_payload_sent, JSON_PRETTY_PRINT)); ?></pre>
                        </div>

                        <!-- OAuth Response stored -->
                        <div style="background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1);border-radius:10px;padding:18px;margin-bottom:20px;">
                            <p style="color:#ffd700;font-size:.72rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;margin-bottom:10px;">IHQ OAuth — Response Stored</p>
                            <?php if (!empty($ihq_access)): ?>
                            <pre style="background:#0a0a0a;border-radius:6px;padding:12px;font-size:.78rem;color:#3fb950;overflow:auto;margin:0;"><?php echo esc_html(json_encode($ihq_response_stored, JSON_PRETTY_PRINT)); ?></pre>
                            <p style="color:#888;font-size:.73rem;margin-top:6px;">Full tokens stored in user meta (truncated above).</p>
                            <?php else: ?>
                            <p style="color:#f85149;font-size:.85rem;">⚠ No IHQ tokens stored — OAuth call may have failed. Check <code>wp-content/debug.log</code>.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="accordion-gradient-container">
                        
                        <!-- Accordion Questions -->
                        <div class="accordion custom-accordion" id="equityAccordion">
                        
                            <!-- Q1 -->
                            <div class="accordion-item mb-3">
                                <h2 class="accordion-header" id="headingOne">
                                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                                        <span class="question-text">What's "this" Opportunity All About?</span>
                                    </button>
                                </h2>
                                <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#equityAccordion">
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
                                        <span class="question-text">How do I know that accepting equity for Influence is the best choice?</span>
                                    </button>
                                </h2>
                                <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#equityAccordion">
                                    <div class="accordion-body">
                                        <p>There's never a guarantee in the equity game, but it's a fact that Global equity markets added roughly $20 trillion in value from 2015 to 2020 — and more than twice that, about $50 trillion, from 2020 to 2025."</p>
                                        <p>One of the world's most famous "missed equity opportunities" was when 3-time NBA MVP Magic Johnson, fresh out of college, turned down Nike's 1 cent per shoe royalty and 11 cent stock in exchange for promoting Nike shoes. The 11 cent stock is now worth over $5.4 billion.</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Q3 -->
                            <div class="accordion-item mb-3">
                                <h2 class="accordion-header" id="headingThree">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                                        <span class="question-text">How does Competition fit Into this Picture?</span>
                                    </button>
                                </h2>
                                <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#equityAccordion">
                                    <div class="accordion-body">
                                        <p>Competition is the oldest language in the world.From the earliest days of civilization, competition has united people across cultures.</p>
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
                                        <span class="question-text">Tell me more about this particular opportunity.</span>
                                    </button>
                                </h2>
                                <div id="collapseFive" class="accordion-collapse collapse" aria-labelledby="headingFive" data-bs-parent="#equityAccordion">
                                    <div class="accordion-body">
                                        <p>There are hundreds of platforms offering hundreds of different competitions.</p>
                                        <p>Over 10 years ago, the Founder, traveling across Asia visited Macau and discovered an oasis of excitement — thousands of players, young and old, gathered inside a single grand parlor around Baccarat tables stretching almost as far as the eye could see.</p>
                                        <p>Each table alive with anticipation.</p>
                                        <p>Each card squeezed in ritual suspense.</p>
                                        <p>The game is Baccarat.</p>
                                        <p>Known as the Game of the Kings for over 500 years, Baccarat became Asia's game of choice — and the choice of high rollers worldwide after James Bond introduced it to the modern era more than 40 years ago.</p>
                                        <p>The founder asked himself:</p>
                                        <p>Why couldn't there be an online version of Baccarat that captures the same elegance on display in Macau?</p>
                                        <p>Avantage Baccarat World Tour and World Championship are the result of that vision.</p>
                                        <p>But Avantage Baccarat is more than just a game.</p>
                                        <p>It is a Competition System — built around the Avantage model of International Competition, including Celebrity Followers Leagues and the Influencer Challenge Series — where top Influencers and their followers compete in Private Challenges and the World Challenge Series.</p>
                                        <p>All of this culminates in the Avantage Baccarat World Championship —</p>
                                        <p>$100 Million Guaranteed Prize Pool.</p>
                                        <p>Baccarat succeeded in Asia for the same reason it became the game of choice in Monte Carlo, Asia and other elite locations across the world:</p>
                                        <p>Simple rules.</p>
                                        <p>No bluffing.</p>
                                        <p>Enduring prestige dating back to the French Royal Court 500 years ago.</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Q6 -->
                            <div class="accordion-item mb-3">
                                <h2 class="accordion-header" id="headingSix">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSix" aria-expanded="false" aria-controls="collapseSix">
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
                                        <span class="question-text">Tell me more about Live Streaming on Kick?</span>
                                    </button>
                                </h2>
                                <div id="collapseEight" class="accordion-collapse collapse" aria-labelledby="headingEight" data-bs-parent="#equityAccordion">
                                    <div class="accordion-body">
                                        <p>Easy for experienced Streamers - Choose your own broadcast times and invite all your followers to compete by your side. New to Streaming? Just download our Streaming Made Easy tutorial from this website.</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Q9 -->
                            <div class="accordion-item mb-3">
                                <h2 class="accordion-header" id="headingNine">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseNine" aria-expanded="false" aria-controls="collapseNine">
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
                                        <p>Genius exists for one reason:</p>
                                        <p>to make sure that when your influence drives participation, you receive credit for it.</p>
                                        <p>Email verification activated Genius; so the door to earning equity for influence has already been opened.</p>
                                        
                                        <p><strong>STEP 4 — Equity Is Earned Through Play</strong></p>
                                        <p>Equity is created by participation — not promises.</p>
                                        <p>You earn equity shares from:</p>
                                        <p>• Play generated by people in your referral network, and</p>
                                        <p>• Bonus equity from anyone who plays while watching your live streams or participating during your live appearances.</p>
                                        
                                        <p><strong>STEP 5 — Performance Reveals What Works</strong></p>
                                        <p>Inside your private portal, Performance shows you exactly where your influence is most effective.</p>
                                        <p>You'll see:</p>
                                        <p>• How much play your influence generated</p>
                                        <p>• Where your equity came from</p>
                                        <p>• Which Challenges, time windows, and formats perform best</p>
                                        <p>When something works — you'll know it.</p>
                                        <p>And when it doesn't — you'll know that too.</p>
                                        <p>Clarity replaces guesswork.</p>
                                        <p><strong>Have Fun and Good Luck!</strong></p>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
        </div><!-- /.container.the-gradient -->

        <?php get_template_part( 'template-parts/portal-footer' ); ?>
    </main><!-- #main -->

<script>
(function () {
    console.log('[ElevenLabs] Script block running');
    console.log('[ElevenLabs] ihqElevenLabs defined:', typeof ihqElevenLabs !== 'undefined' ? ihqElevenLabs : 'NOT DEFINED');
    document.addEventListener('DOMContentLoaded', function () {
        var buttons = document.querySelectorAll('.concierge-title');
        if (!buttons.length) return;
        console.log('[ElevenLabs] Buttons found:', buttons.length);

        var activeSession = null;
        var buttonOriginalTexts = Array.from(buttons).map(function (button) { return button.textContent; });
        var errMsg = null;

        function createErrorElement(target) {
            if (!errMsg) {
                errMsg = document.createElement('p');
                errMsg.style.cssText = 'color:#f85149;font-size:.85rem;text-align:center;margin-top:8px;display:none;';
            }
            if (errMsg.parentNode !== target.parentNode) {
                target.parentNode.insertBefore(errMsg, target.nextSibling);
            }
            return errMsg;
        }

        function showError(msg, target) {
            var error = createErrorElement(target);
            error.textContent = msg;
            error.style.display = 'block';
        }

        function hideError() {
            if (errMsg) errMsg.style.display = 'none';
        }

        function setAllButtonText(text) {
            buttons.forEach(function (button) { button.textContent = text; });
        }

        function resetButtonText() {
            buttons.forEach(function (button, index) {
                button.textContent = buttonOriginalTexts[index];
            });
        }

        buttons.forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                hideError();

                if (activeSession) {
                    activeSession.endSession();
                    return;
                }

                setAllButtonText('Connecting…');

                fetch(ihqElevenLabs.ajax_url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'action=ihq_elevenlabs_signed_url&nonce=' + encodeURIComponent(ihqElevenLabs.nonce),
                })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    console.log('[ElevenLabs] API response:', data);
                    console.log('[ElevenLabs] Raw ElevenLabs data:', data.data);
                    if (data.success && data.data && data.data.signed_url) {
                        var signedUrl = data.data.signed_url;
                        console.log('[ElevenLabs] Starting session with:', signedUrl);
                        ElevenLabsClient.Conversation.startSession({
                            signedUrl: signedUrl,
                            onConnect: function () {
                                console.log('[ElevenLabs] Connected');
                                setAllButtonText('End Talk');
                            },
                            onDisconnect: function () {
                                console.log('[ElevenLabs] Disconnected');
                                activeSession = null;
                                resetButtonText();
                            },
                            onError: function (error) {
                                console.error('[ElevenLabs] Error:', error);
                                showError('Connection error. Please try again.', btn);
                                activeSession = null;
                                resetButtonText();
                            },
                            onMessage: function (msg) { console.log('[ElevenLabs] Message:', msg); },
                        }).then(function (session) {
                            activeSession = session;
                        }).catch(function (err) {
                            console.error('[ElevenLabs] startConversation failed:', err);
                            showError('Could not start conversation. Please try again.', btn);
                            activeSession = null;
                            resetButtonText();
                        });
                    } else {
                        showError('Could not connect. Please try again.', btn);
                        resetButtonText();
                    }
                })
                .catch(function (err) {
                    console.error('[ElevenLabs] Fetch error:', err);
                    showError('Connection error. Please try again.', btn);
                    resetButtonText();
                });
            });
        });
    });
})();
</script>

<script>
// Auto-click .concierge-title if arriving with ?open=concierge
if (window.location.search.indexOf('open=concierge') !== -1) {
    window.addEventListener('load', function () {
        var el = document.querySelector('.concierge-title');
        if (el) el.click();
    });
}
</script>

<?php 
get_template_part( 'template-parts/portal-scripts' );
get_footer();