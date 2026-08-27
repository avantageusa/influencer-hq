<?php
/**
 * Template Name: Portal Competition
 * Description: A custom template for displaying competition.
 *
 * @package influencer-hq
 */
get_header();

// Load styles before content to prevent FOUC
get_template_part( 'template-parts/portal-styles' );

$user            = wp_get_current_user();
$celebrity_selections = [
    'movie_stars'   => get_user_meta( $user->ID, '_ihq_cel_movie_stars',   true ) ?: '',
    'music_artists' => get_user_meta( $user->ID, '_ihq_cel_music_artists', true ) ?: '',
    'sports_icons'  => get_user_meta( $user->ID, '_ihq_cel_sports_icons',  true ) ?: '',
];
$intl_league_team = get_user_meta( $user->ID, '_ihq_intl_league_team', true ) ?: '';
$celeb_lists = [
    'movie_stars'   => ['Leonardo DiCaprio','Fan Bingbing','Scarlett Johansson','Tony Leung','Anya Wong','Maggie Cheung','Iko Uwais','Tom Cruise','Hyun Bin','Chow Yun-fat','Zhang Ziyi','Song Hye-kyo','Gong Yoo','Michelle Yeoh','Donnie Yen','Vicky Chen','Bruce Lee','Gong Li','Liu Yifei','Jackie Chan'],
    'music_artists' => ['Jolin Tsai','Namewee','IU (Lee Ji-eun)','BTS','Ariana Grande','Bruno Mars','PSY','Blackpink','Twice','Tomorrow X Together','Billie Eilish','Jay Chou','Lisa (BLACKPINK)','Zhou Shen','G-Dragon','Lady Gaga','Taylor Swift','Deng Liqi','Justin Bieber','Ed Sheeran'],
    'sports_icons'  => ['Son Heung-min','Lionel Messi','Roger Federer','Naomi Osaka','Ding Junhui','Jeremy Lin','Cristiano Ronaldo','Stephen Curry','Michael Jordan','Novak Djokovic','Kento Momota','Sachin Tendulkar','Rafael Nadal','Virat Kohli','Manny Pacquiao','Shohei Ohtani','Yao Ming','LeBron James','Kylian Mbappé','Lee Chong Wei'],
];
$celeb_labels = ['movie_stars' => 'Movie Stars', 'music_artists' => 'Music Artists', 'sports_icons' => 'Sports Icons'];
$intl_league_regions = ['South Korea','Europe','Malaysia','Thailand','Africa','Singapore','Asia','India','China','Hong Kong','Philippines','Taiwan','United States','Canada','Macao','Pakistan','South America','Japan','Australia','South Africa'];

$portal_leaderboards_iframe_url = 'https://qc-game-portal-client-tf-b2c.dev.ae.games/av-baccarat/external/leaderboards';
?>

    <main id="primary" class="site-main">
        
        <?php get_template_part( 'template-parts/portal-header' ); ?>
        
        <div class="container py-2 the-gradient competition-page-wrap" id="portal-content">

            <!-- Competition Content -->
            <div class="competition-page-content">
                <div class="competition-header">
                    <div class="competition-header-top">
                        <img src="<?php echo get_template_directory_uri(); ?>/images/portal-competition.png" alt="Competition" class="competition-icon">
                        <h1 class="competition-title">Competition</h1>
                    </div>
                </div>

                <div class="competition-types">
                    <div class="competition-tabs">
                        <button class="competition-tab-btn active" data-tab="intro" type="button">
                            <img src="<?php echo get_template_directory_uri(); ?>/images/portal-c-intro.svg" alt="" class="competition-tab-icon competition-tab-icon--intro">
                            <span>Intro</span>
                        </button>
                        <button class="competition-tab-btn" data-tab="private" type="button">
                            <img src="<?php echo get_template_directory_uri(); ?>/images/portal-c-private.png" alt="" class="competition-tab-icon">
                            <span>Private</span>
                        </button>
                        <button class="competition-tab-btn" data-tab="community" type="button">
                            <img src="<?php echo get_template_directory_uri(); ?>/images/portal-c-community.png" alt="" class="competition-tab-icon">
                            <span>Community</span>
                        </button>
                        <button class="competition-tab-btn" data-tab="world" type="button">
                            <img src="<?php echo get_template_directory_uri(); ?>/images/portal-c-world.png" alt="" class="competition-tab-icon">
                            <span>World</span>
                        </button>
                        <button class="competition-tab-btn" data-tab="leagues" type="button">
                            <img src="<?php echo get_template_directory_uri(); ?>/images/portal-c-leagues.png" alt="" class="competition-tab-icon">
                            <span>Leagues</span>
                        </button>
                    </div>
                </div>

                <div class="competition-panel active" id="intro-tab">
                <!-- Lead Section: Game of Kings / Traditional / Avantage -->
                <div id="baccarat-intro" class="hm-scroll-anchor" aria-hidden="true"></div>
                <!-- Lead Section: Mobile (<1024px) -->
                <div class="comp-lead comp-lead--mobile">
                    <div class="comp-lead-sep"></div>
                    <h2 class="comp-lead-kings">The Game of Kings</h2>
                    <div class="comp-lead-body">
                        <p>For over 500 years, Baccarat has been known as the Game of Kings.</p>
                    </div>
                    <div class="comp-lead-mobile-img">
                        <img src="<?php echo esc_url( get_template_directory_uri() ); ?>/images/image-left-kings.jpg" alt="The Game of Kings" width="900">
                    </div>

                    <div class="comp-lead-sep"></div>
                    <h3 class="comp-lead-kings">The Modern Era</h3>
                    <div class="comp-lead-body">
                        <p>Propelled into the modern era over 40 years ago by James Bond. Baccarat remains the preferred game of high rollers worldwide, and stands as Asia's game of choice with over 100 million active players.</p>
                    </div>
                    <div class="comp-lead-modern-wrap">
                        <div class="comp-lead-mobile-img">
                            <img src="<?php echo esc_url( get_template_directory_uri() ); ?>/images/image-right-kings.png" alt="The Modern Era">
                        </div>
                        <div class="intro-coach-fab-host" id="intro-coach-fab-host-mobile" aria-hidden="true"></div>
                    </div>

                    <div class="comp-lead-sep"></div>
                    <h3 class="comp-lead-kings">TRADITIONAL BACCARAT</h3>
                    <div class="comp-lead-row">
                        <span>Matchup - 1 Bank vs. 1 Player</span>
                    </div>
                    <div class="comp-lead-row">
                        <span>All Plays - Before the Hand</span>
                    </div>

                    <div class="comp-lead-sep"></div>
                    <div class="comp-lead-avantage-head">
                        <img src="<?php echo esc_url( get_template_directory_uri() ); ?>/images/logo-hero-red-small.png" alt="" class="comp-lead-avantage-icon">
                        <h3 class="comp-lead-kings">Avantage Baccarat</h3>
                    </div>
                    <div class="comp-lead-sep"></div>
                    <div class="comp-lead-row"><span>Matchups - 1 Bank vs. 5 Players</span></div>
                    <div class="comp-lead-sep"></div>
                    <div class="comp-lead-row">
                        <span>All Plays - Throughout the Hand</span>
                    </div>
                    <div class="comp-lead-sep"></div>
                    <div class="comp-lead-row comp-lead-row--single">Pools - Top 30% Split</div>
                    <div class="comp-lead-sep"></div>
                    <div class="comp-lead-row comp-lead-row--single">$100 Million World Championship</div>
                    <div class="comp-lead-sep"></div>
                    <div class="comp-lead-row comp-lead-row--single">52 World Tour Events</div>
                    <div class="comp-lead-sep"></div>
                    <div class="comp-lead-row comp-lead-row--single">Leading Money Winner Competitions</div>
                    <div class="comp-lead-sep"></div>
                    <div class="comp-lead-row comp-lead-row--single">Olympic Style Medal Competitions</div>
                    <div class="comp-lead-sep"></div>
                    <div class="comp-lead-row comp-lead-row--stack">
                        <span class="comp-lead-row-main">Influencer Competitions</span><br>
                        <span class="comp-lead-row-sub">Private - Community - World</span>
                    </div>
                    <div class="comp-lead-sep"></div>
                    <div class="comp-lead-row comp-lead-row--stack">
                        <span class="comp-lead-row-main">Celebrity Followers Leagues</span><br>
                        <span class="comp-lead-row-sub">Movies - Music - Sports</span>
                    </div>
                    <div class="comp-lead-sep"></div>
                    <div class="comp-lead-row comp-lead-row--single">International League</div>
                    <div class="comp-lead-sep"></div>
                </div>

                <!-- Lead Section: Desktop (>1024px) -->
                <div class="comp-lead comp-lead--desktop">

                    <!-- Game of Kings: image left, text right -->
                    <div class="comp-lead-desktop-row" style="margin-top: 20px;">
                        <div class="comp-lead-desktop-img">
                            <img src="<?php echo esc_url( get_template_directory_uri() ); ?>/images/image-left-kings.jpg" alt="The Game of Kings" width="900">
                        </div>
                        <div class="comp-lead-desktop-text">
                            <h2 class="comp-lead-kings comp-lead-kings--desktop">The Game of Kings</h2>
                            <p>For over 500 years, Baccarat has been known as the Game of Kings.</p>
                        </div>
                    </div>

                    <!-- The Modern Era: text left, image right + coach -->
                    <div class="comp-lead-desktop-row comp-lead-desktop-row--modern" style="margin-top: 75px;">
                        <div class="comp-lead-desktop-text">
                            <h2 class="comp-lead-kings comp-lead-kings--desktop">The Modern Era</h2>
                            <p style="width:85%;">Propelled into the modern era over 40 years ago by James Bond. Baccarat remains the preferred game of high rollers worldwide, and stands as Asia's game of choice with over 100 million active players.</p>
                        </div>
                        <div class="comp-lead-desktop-img comp-lead-modern-wrap">
                            <img src="<?php echo esc_url( get_template_directory_uri() ); ?>/images/image-right-kings.png" alt="The Modern Era">
                            <div class="intro-coach-fab-host" id="intro-coach-fab-host-desktop" aria-hidden="true"></div>
                        </div>
                    </div>

                    <div class="comp-lead-sep"></div>
                    <h3 class="comp-lead-game-title">Traditional Baccarat</h3>
                    <div class="comp-lead-row comp-lead-row--single">Matchup - 1 Bank vs. 1 Player</div>
                    <div class="comp-lead-row comp-lead-row--single">All Plays - Before the Hand</div>

                    <div class="comp-lead-sep"></div>
                    <div class="comp-lead-avantage-head">
                        <img src="<?php echo esc_url( get_template_directory_uri() ); ?>/images/logo-hero-red-small.png" alt="" class="comp-lead-avantage-icon">
                        <h3 class="comp-lead-game-title">Avantage Baccarat</h3>
                    </div>
                    <div class="comp-lead-sep"></div>

                    <div class="comp-lead-desktop-2col">
                        <span>Matchups - 1 Bank vs. 5 Players</span>
                        <span>All Plays - Throughout the Hand</span>
                    </div>

                    <div class="comp-lead-row comp-lead-row--single">Pools - Top 30% Split</div>

                    <div class="comp-lead-diamond">
                        <img style="width: 50px; height: 50px;margin: 10px 0px;" src="<?php echo esc_url( get_template_directory_uri() ); ?>/images/logo-hero-red-small.png" alt="" class="comp-lead-avantage-icon">
                    </div>

                    <div class="comp-lead-sep"></div>
                    <div class="comp-lead-row comp-lead-row--single">$100 Million World Championship</div>
                    <div class="comp-lead-sep"></div>
                    <div class="comp-lead-row comp-lead-row--single">52 World Tour Events</div>
                    <div class="comp-lead-sep"></div>
                    <div class="comp-lead-row comp-lead-row--single">Leading Money Winner Competitions</div>
                    <div class="comp-lead-sep"></div>
                    <div class="comp-lead-row comp-lead-row--single">Olympic Style Medal Competitions</div>
                    <div class="comp-lead-sep"></div>

                    <div class="comp-lead-diamond">
                        <img style="width: 50px; height: 50px;margin: 10px 0px;" src="<?php echo esc_url( get_template_directory_uri() ); ?>/images/logo-hero-red-small.png" alt="" class="comp-lead-avantage-icon">
                    </div>

                    <div class="comp-lead-sep"></div>
                    <div class="comp-lead-row comp-lead-row--stack">
                        <span class="comp-lead-row-main">Influencer Competitions</span>
                        <span class="comp-lead-row-sub">Private - Community - World</span>
                    </div>
                    <div class="comp-lead-sep"></div>
                    <div class="comp-lead-row comp-lead-row--stack">
                        <span class="comp-lead-row-main">Celebrity Followers Leagues</span>
                        <span class="comp-lead-row-sub">Movies - Music - Sports</span>
                    </div>
                    <div class="comp-lead-sep"></div>
                    <div class="comp-lead-row comp-lead-row--single">International League</div>
                    <div class="comp-lead-sep"></div>

                </div>

                <div class="competition-top-accordion">
                    <?php
                    get_template_part(
                        'template-parts/competition-why-equity',
                        null,
                        array(
                            'instance_id' => 'intro',
                            'expanded'    => false,
                        )
                    );
                    ?>
                </div>
                </div><!-- /#intro-tab -->

                <!-- World Tab -->
                <div class="competition-panel" id="world-tab">
                    <div class="competition-card">
                        <p>We believe competition reveals character.</p>
                        <p>For three days, the world is watching. Not in silence - but in real time.</p>
                        <p>Every hand played moves you forward - or leaves you behind.</p>
                        <p>There is no single opponent to defeat. There is only the standard you set.</p>
                        <p>This is global visibility. Global comparison. Global recognition.</p>
                        <p>When the clock closes, the standings speak for themselves.</p>
                    </div>

                    <?php
                    get_template_part(
                        'template-parts/competition-why-equity',
                        null,
                        array(
                            'instance_id' => 'world',
                            'expanded'    => true,
                        )
                    );
                    ?>

                    <h2 class="competition-section-title">World</h2>

                    <div class="competition-block">
                        <div class="competition-block-title">World Influencer Challenge</div>
                        <div class="accordion custom-accordion" id="worldAccordion">
                            <div class="accordion-item mb-3">
                                <h2 class="accordion-header" id="headingWorldWhat">
                                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseWorldWhat" aria-expanded="true" aria-controls="collapseWorldWhat">
                                        <span class="question-text">What is the World Influencer Challenge?</span>
                                    </button>
                                </h2>
                                <div id="collapseWorldWhat" class="accordion-collapse collapse show" aria-labelledby="headingWorldWhat" data-bs-parent="#worldAccordion">
                                    <div class="accordion-body">
                                        <p>A three-day global competition where all Influencers and Followers compete during the same contest window.</p>
                                        <p>This is placement-based. Not head-to-head.</p>
                                    </div>
                                </div>
                            </div>
                            <div class="accordion-item mb-3">
                                <h2 class="accordion-header" id="headingWorldPerformance">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseWorldPerformance" aria-expanded="false" aria-controls="collapseWorldPerformance">
                                        <span class="question-text">How is performance calculated?</span>
                                    </button>
                                </h2>
                                <div id="collapseWorldPerformance" class="accordion-collapse collapse" aria-labelledby="headingWorldPerformance" data-bs-parent="#worldAccordion">
                                    <div class="accordion-body">
                                        <p>Performance is calculated as:</p>
                                        <p>Net Gain / Total Amount Played = Performance Percent</p>
                                        <p>Scores range from 0 percent upward. Scores can never be negative.</p>
                                    </div>
                                </div>
                            </div>
                            <div class="accordion-item mb-3">
                                <h2 class="accordion-header" id="headingWorldPoints">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseWorldPoints" aria-expanded="false" aria-controls="collapseWorldPoints">
                                        <span class="question-text">How does Win percent convert to points?</span>
                                    </button>
                                </h2>
                                <div id="collapseWorldPoints" class="accordion-collapse collapse" aria-labelledby="headingWorldPoints" data-bs-parent="#worldAccordion">
                                    <div class="accordion-body">
                                        <p>After each contest, participants are ranked by Performance Percent.</p>
                                        <p>Points are awarded based on percentile placement:</p>
                                        <ul>
                                            <li>Top 10 percent - 5 points</li>
                                            <li>11-20 percent - 4 points</li>
                                            <li>21-30 percent - 3 points</li>
                                            <li>31-40 percent - 2 points</li>
                                            <li>41-50 percent - 1 point</li>
                                            <li>Below 50 percent - 0 points</li>
                                        </ul>
                                        <p>Every contest earns points.</p>
                                    </div>
                                </div>
                            </div>
                            <div class="accordion-item mb-3">
                                <h2 class="accordion-header" id="headingWorldMedals">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseWorldMedals" aria-expanded="false" aria-controls="collapseWorldMedals">
                                        <span class="question-text">How do points convert to medals?</span>
                                    </button>
                                </h2>
                                <div id="collapseWorldMedals" class="accordion-collapse collapse" aria-labelledby="headingWorldMedals" data-bs-parent="#worldAccordion">
                                    <div class="accordion-body">
                                        <p>Points accumulate across the entire quarter.</p>
                                        <p>At the end of each quarter, Teams are ranked by total cumulative points.</p>
                                        <p>Medals are awarded based on overall percentile placement:</p>
                                        <ul>
                                            <li>Top 10 percent - Diamond Medal</li>
                                            <li>11-20 percent - Gold Medal</li>
                                            <li>21-30 percent - Silver Medal</li>
                                            <li>31-40 percent - Bronze Medal</li>
                                        </ul>
                                        <p>Teams below the top 40 percent do not receive a medal.</p>
                                    </div>
                                </div>
                            </div>
                            <div class="accordion-item mb-3">
                                <h2 class="accordion-header" id="headingWorldRepresent">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseWorldRepresent" aria-expanded="false" aria-controls="collapseWorldRepresent">
                                        <span class="question-text">What do medals represent?</span>
                                    </button>
                                </h2>
                                <div id="collapseWorldRepresent" class="accordion-collapse collapse" aria-labelledby="headingWorldRepresent" data-bs-parent="#worldAccordion">
                                    <div class="accordion-body">
                                        <p>Medals recognize sustained team performance over an entire quarter.</p>
                                        <p>They are not based on one contest - but on consistent participation and results.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Influencer Competition Results -->
                    <style>                    .icr-wrap{background:#111;border-radius:10px;padding:18px 16px 14px;margin-bottom:20px;color:#fff}
                    .icr-title{font-size:1.15rem;font-weight:700;margin-bottom:14px;color:#fff}
                    .icr-type-tabs{display:flex;gap:22px;border-bottom:1px solid #2a2a2a;padding-bottom:8px;margin-bottom:10px}
                    .icr-type-btn{background:none;border:none;cursor:pointer;color:#bbb;font-size:.95rem;font-weight:500;padding:0 0 4px}
                    .icr-type-btn.active{color:#e53e3e;border-bottom:2px solid #e53e3e;font-weight:700}
                    .icr-time-tabs{display:flex;gap:14px;flex-wrap:wrap;margin-bottom:10px}
                    .icr-time-btn{background:none;border:none;cursor:pointer;color:#bbb;font-size:.85rem;font-weight:500;padding:2px 0}
                    .icr-time-btn.active{color:#e53e3e;text-decoration:underline;font-weight:700}
                    .icr-col-label{color:#e53e3e;font-weight:700;font-size:.85rem;text-decoration:underline;margin-bottom:6px}
                    .icr-table{margin-bottom:0}
                    .icr-row{display:flex;align-items:center;gap:10px;padding:11px 0;border-bottom:1px solid #222}
                    .icr-rank{font-weight:700;font-size:.95rem;width:20px;text-align:center;flex-shrink:0}
                    .icr-flag{font-size:1.3rem;flex-shrink:0;line-height:1}
                    .icr-avatar{width:38px;height:38px;border-radius:50%;background:#3a3a3a;flex-shrink:0;display:flex;align-items:center;justify-content:center;font-size:1.1rem}
                    .icr-handle{flex:1;font-size:.95rem;font-weight:500}
                    .icr-score{font-weight:600;font-size:.95rem;margin-left:auto;padding-right:4px}
                    .icr-expand-btn{background:none;border:none;color:#aaa;font-size:1.1rem;cursor:pointer;width:26px;text-align:center;flex-shrink:0;padding:0}
                    .icr-breakdown{padding:8px 2px 10px 36px;border-bottom:1px solid #222;font-size:.78rem}
                    .icr-bd-grid{display:grid;grid-template-columns:60px repeat(5,1fr);gap:2px;text-align:center}
                    .icr-bd-grid span:first-child{text-align:left;color:#ccc;font-weight:600}
                    .icr-bd-header{margin-bottom:4px}
                    .icr-bd-points span{font-weight:600;color:#fff}
                    .icr-pct--1{color:#f5c518;font-weight:700}
                    .icr-pct--2{color:#f0a020;font-weight:700}
                    .icr-pct--3{color:#e88020;font-weight:700}
                    .icr-pct--4{color:#e06020;font-weight:700}
                    .icr-pct--5{color:#d84020;font-weight:700}
                    .icr-user-row{display:flex;align-items:center;gap:10px;padding:12px 0 4px;border-top:1px solid #333;margin-top:6px}
                    .icr-user-label{font-size:.72rem;font-weight:700;line-height:1.2;text-align:center;width:44px;flex-shrink:0;color:#fff}
                    .icr-points{font-weight:700;font-size:1rem}
                    </style>
                    <div class="icr-wrap" id="world-results">
                        <div class="icr-title" id="world-influencer">Influencer Competition Results</div>

                        <div class="icr-type-tabs">
                            <button class="icr-type-btn active" data-icr-type="world">World</button>
                            <button class="icr-type-btn" data-icr-type="community">Community</button>
                            <button class="icr-type-btn" data-icr-type="private">Private</button>
                        </div>

                        <div class="icr-time-tabs">
                            <button class="icr-time-btn active">Last Week</button>
                            <button class="icr-time-btn">This Week</button>
                            <button class="icr-time-btn">Last Quarter</button>
                            <button class="icr-time-btn">This Quarter</button>
                            <button class="icr-time-btn">All</button>
                        </div>

                        <div class="icr-col-label">Win %</div>

                        <div class="icr-table">
                            <!-- Row 1 -->
                            <div class="icr-row">
                                <span class="icr-rank">1</span>
                                <span class="icr-flag">🇭🇰</span>
                                <div class="icr-avatar">👤</div>
                                <span class="icr-handle">@DragonStream</span>
                                <span class="icr-score">162%</span>
                                <button class="icr-expand-btn">+</button>
                            </div>
                            <div class="icr-breakdown" style="display:none;">
                                <div class="icr-bd-grid icr-bd-header">
                                    <span>WIN %</span><span class="icr-pct--1">1-10%</span><span class="icr-pct--2">11-20%</span><span class="icr-pct--3">21-30%</span><span class="icr-pct--4">31-40%</span><span class="icr-pct--5">41-50%</span>
                                </div>
                                <div class="icr-bd-grid icr-bd-points">
                                    <span>POINTS</span><span>2</span><span>3</span><span>4</span><span>5</span><span>0</span>
                                </div>
                            </div>

                            <!-- Row 2 -->
                            <div class="icr-row">
                                <span class="icr-rank">2</span>
                                <span class="icr-flag">🇵🇭</span>
                                <div class="icr-avatar">👤</div>
                                <span class="icr-handle">@ManilaMaster</span>
                                <span class="icr-score">142%</span>
                                <button class="icr-expand-btn">+</button>
                            </div>
                            <div class="icr-breakdown" style="display:none;">
                                <div class="icr-bd-grid icr-bd-header">
                                    <span>WIN %</span><span class="icr-pct--1">1-10%</span><span class="icr-pct--2">11-20%</span><span class="icr-pct--3">21-30%</span><span class="icr-pct--4">31-40%</span><span class="icr-pct--5">41-50%</span>
                                </div>
                                <div class="icr-bd-grid icr-bd-points">
                                    <span>POINTS</span><span>1</span><span>2</span><span>5</span><span>3</span><span>0</span>
                                </div>
                            </div>

                            <!-- Row 3 -->
                            <div class="icr-row">
                                <span class="icr-rank">3</span>
                                <span class="icr-flag">🇯🇵</span>
                                <div class="icr-avatar">👤</div>
                                <span class="icr-handle">@TokyoAce</span>
                                <span class="icr-score">132%</span>
                                <button class="icr-expand-btn">+</button>
                            </div>
                            <div class="icr-breakdown" style="display:none;">
                                <div class="icr-bd-grid icr-bd-header">
                                    <span>WIN %</span><span class="icr-pct--1">1-10%</span><span class="icr-pct--2">11-20%</span><span class="icr-pct--3">21-30%</span><span class="icr-pct--4">31-40%</span><span class="icr-pct--5">41-50%</span>
                                </div>
                                <div class="icr-bd-grid icr-bd-points">
                                    <span>POINTS</span><span>4</span><span>0</span><span>2</span><span>3</span><span>1</span>
                                </div>
                            </div>

                            <!-- Row 4 — pre-expanded to match screenshot -->
                            <div class="icr-row">
                                <span class="icr-rank">4</span>
                                <span class="icr-flag">🇰🇷</span>
                                <div class="icr-avatar">👤</div>
                                <span class="icr-handle">@SeoulChamp</span>
                                <span class="icr-score">122%</span>
                                <button class="icr-expand-btn">&times;</button>
                            </div>
                            <div class="icr-breakdown">
                                <div class="icr-bd-grid icr-bd-header">
                                    <span>WIN %</span><span class="icr-pct--1">1-10%</span><span class="icr-pct--2">11-20%</span><span class="icr-pct--3">21-30%</span><span class="icr-pct--4">31-40%</span><span class="icr-pct--5">41-50%</span>
                                </div>
                                <div class="icr-bd-grid icr-bd-points">
                                    <span>POINTS</span><span>0</span><span>1</span><span>3</span><span>2</span><span>4</span>
                                </div>
                            </div>
                        </div>

                        <!-- Current user pinned row -->
                        <div class="icr-user-row" id="world-follower">
                            <span class="icr-user-label">Top<br>20%</span>
                            <span class="icr-flag">🇺🇸</span>
                            <div class="icr-avatar">👤</div>
                            <span class="icr-handle">Inf. User</span>
                            <span class="icr-score">42%</span>
                            <span class="icr-points">5</span>
                        </div>
                    </div>

                    <div class="competition-rule-block">
                        <div class="competition-rule-header">How World Medals Are Awarded</div>
                        <div class="competition-medals">
                            <img src="<?php echo get_template_directory_uri(); ?>/images/diamond-medal.png" alt="Diamond" class="competition-medals__img">
                            <img src="<?php echo get_template_directory_uri(); ?>/images/gold-medal.png" alt="Gold" class="competition-medals__img">
                            <img src="<?php echo get_template_directory_uri(); ?>/images/silver-medal.png" alt="Silver" class="competition-medals__img">
                            <img src="<?php echo get_template_directory_uri(); ?>/images/bronze-medal.png" alt="Bronze" class="competition-medals__img">
                        </div>
                        <div class="competition-rule-grid">
                            <div>
                                <div class="competition-rule-title">Finish</div>
                                <div class="competition-rule-list">Top 10%<br>11%-20%<br>21%-30%<br>31%-40%<br>41%-50%</div>
                            </div>
                            <div>
                                <div class="competition-rule-title">Points</div>
                                <div class="competition-rule-list">5<br>4<br>3<br>2<br>1</div>
                            </div>
                            <div>
                                <div class="competition-rule-title">Total Points</div>
                                <div class="competition-rule-list">Top 10%<br>11%-20%<br>21%-30%<br>31%-40%</div>
                            </div>
                            <div>
                                <div class="competition-rule-title">Medals</div>
                                <div class="competition-rule-list">Diamond<br>Gold<br>Silver<br>Bronze</div>
                            </div>
                        </div>
                    </div>

                    <div class="competition-dropdown" id="world-leaderboards">
                        <div class="competition-dropdown-header">See My Results</div>
                        <div class="competition-dropdown-body">
                            <div class="competition-pill-center">
                                <button class="competition-pill active">Quarter to Date</button>
                            </div>
                            <div class="competition-dropdown-section">
                                <div class="competition-dropdown-label">This Year's Previous Quarters</div>
                                <div class="competition-dropdown-select-wrap">
                                    <select class="competition-dropdown-select">
                                        <option>1st Quarter 2026</option>
                                        <option>2nd Quarter 2026</option>
                                        <option>3rd Quarter 2026</option>
                                        <option>4th Quarter 2026</option>
                                    </select>
                                    <span class="competition-dropdown-arrow">▼</span>
                                </div>
                            </div>
                            <div class="competition-dropdown-section--lg">
                                <div class="competition-dropdown-label">Previous Years</div>
                                <div class="competition-dropdown-select-wrap">
                                    <select class="competition-dropdown-select">
                                        <option>1st Quarter 2025</option>
                                        <option>2nd Quarter 2025</option>
                                        <option>3rd Quarter 2025</option>
                                        <option>4th Quarter 2025</option>
                                    </select>
                                    <span class="competition-dropdown-arrow">▼</span>
                                </div>
                            </div>
                            <div class="competition-mini-table">
                                <div class="competition-mini-row competition-mini-row--header">
                                    <span>Levels</span>
                                    <span>Points</span>
                                </div>
                                <div class="competition-mini-row"><span>Top 10%</span><span>3</span></div>
                                <div class="competition-mini-row"><span>11%-20%</span><span>8</span></div>
                                <div class="competition-mini-row"><span>21%-30%</span><span>25</span></div>
                                <div class="competition-mini-row"><span>31%-40%</span><span>45</span></div>
                                <div class="competition-mini-row competition-mini-row--total">
                                    <span>TOTAL:</span><span>81</span>
                                </div>
                                <div class="competition-mini-row competition-mini-row--api-points competition-mini-row--api-header">
                                    <span>Points from API</span>
                                    <span>resolvedPoints</span>
                                </div>
                                <div class="competition-mini-row competition-mini-row--api-points">
                                    <span>World</span>
                                    <span id="comp-points-world">&mdash;</span>
                                </div>
                                <div class="competition-mini-row competition-mini-row--api-points">
                                    <span>Continent</span>
                                    <span id="comp-points-continent">&mdash;</span>
                                </div>
                                <div class="competition-mini-row competition-mini-row--api-points">
                                    <span>Country</span>
                                    <span id="comp-points-country">&mdash;</span>
                                </div>
                                <div class="competition-mini-row competition-mini-row--api-points">
                                    <span>Town</span>
                                    <span id="comp-points-town">&mdash;</span>
                                </div>
                            </div>

                            <div class="competition-get-points-wrap">
                                <button id="comp-get-points-btn" class="competition-btn">Get Points</button>
                            </div>

                            <div class="competition-debug-block" id="comp-points-debug" style="display:none;">
                                <div class="competition-debug-label">Call &amp; Payload</div>
                                <pre id="comp-points-request" class="competition-debug-pre"></pre>
                                <div class="competition-debug-label" style="margin-top:8px;">Response</div>
                                <pre id="comp-points-response" class="competition-debug-pre"></pre>
                            </div>
                        </div>
                    </div>

                    <div class="competition-dropdown">
                        <div class="competition-dropdown-header">See My Medals</div>
                        <div class="competition-dropdown-body">
                            <div class="competition-pill-center">
                                <button class="competition-pill active">Quarter to Date</button>
                            </div>
                            <div class="competition-dropdown-section">
                                <div class="competition-dropdown-label">This Year's Previous Quarters</div>
                                <div class="competition-dropdown-select-wrap">
                                    <select class="competition-dropdown-select">
                                        <option>1st Quarter 2026</option>
                                        <option>2nd Quarter 2026</option>
                                        <option>3rd Quarter 2026</option>
                                        <option>4th Quarter 2026</option>
                                        <option>1st Quarter 2025</option>
                                        <option>2nd Quarter 2025</option>
                                        <option>3rd Quarter 2025</option>
                                        <option>4th Quarter 2025</option>
                                    </select>
                                    <span class="competition-dropdown-arrow">▼</span>
                                </div>
                            </div>
                            <div class="competition-dropdown-section--lg">
                                <div class="competition-dropdown-label">Previous Years</div>
                                <div class="competition-dropdown-select-wrap">
                                    <select class="competition-dropdown-select">
                                        <option>1st Quarter 2025</option>
                                        <option>2nd Quarter 2025</option>
                                        <option>3rd Quarter 2025</option>
                                        <option>4th Quarter 2025</option>
                                    </select>
                                    <span class="competition-dropdown-arrow">▼</span>
                                </div>
                            </div>
                            <div class="competition-medal-display">
                                <div class="competition-medal-item">
                                    <img src="<?php echo get_template_directory_uri(); ?>/images/diamond-medal.png" alt="Diamond">
                                    <div class="competition-medal-count">2</div>
                                </div>
                                <div class="competition-medal-item">
                                    <img src="<?php echo get_template_directory_uri(); ?>/images/gold-medal.png" alt="Gold">
                                    <div class="competition-medal-count">7</div>
                                </div>
                                <div class="competition-medal-item">
                                    <img src="<?php echo get_template_directory_uri(); ?>/images/silver-medal.png" alt="Silver">
                                    <div class="competition-medal-count">12</div>
                                </div>
                                <div class="competition-medal-item">
                                    <img src="<?php echo get_template_directory_uri(); ?>/images/bronze-medal.png" alt="Bronze">
                                    <div class="competition-medal-count">23</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Private Tab -->
                <div class="competition-panel" id="private-tab">
                    <!-- Circles belong to the panel, not the accordion -->
                    <button type="button" class="private-schedule-cta" id="private-schedule-cta">
                        <?php esc_html_e( 'CLICK HERE TO SCHEDULE PRIVATE CHALLENGE', 'influencer-hq' ); ?>
                    </button>
                    <div class="private-coach-fab-host" id="private-coach-fab-host" aria-hidden="true"></div>

                    <div class="accordion custom-accordion private-desc-accordion" id="privateDescAccordion">
                        <div class="accordion-item mb-3" id="private-full-description">
                            <h2 class="accordion-header" id="headingPrivateFullDesc">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapsePrivateFullDesc" aria-expanded="true" aria-controls="collapsePrivateFullDesc">
                                    <span class="question-text"><?php esc_html_e( 'Full Description of Private Challenges', 'influencer-hq' ); ?></span>
                                </button>
                            </h2>
                            <div id="collapsePrivateFullDesc" class="accordion-collapse collapse show" aria-labelledby="headingPrivateFullDesc" data-bs-parent="#privateDescAccordion">
                                <div class="accordion-body private-desc-copy">
                                    <p><?php esc_html_e( 'A Private Challenge is a head-to-head competition between two verified Influencers who already have a personal relationship OR two groups competing against each other.', 'influencer-hq' ); ?></p>
                                    <p><?php esc_html_e( 'We believe leadership is personal.', 'influencer-hq' ); ?></p>
                                    <p><?php esc_html_e( 'In a Private Challenge, there is no crowd to hide behind.', 'influencer-hq' ); ?></p>
                                    <p><?php esc_html_e( 'Every contest is a vote for your leadership.', 'influencer-hq' ); ?></p>
                                    <p><?php esc_html_e( 'Not with words —', 'influencer-hq' ); ?></p>
                                    <p><?php esc_html_e( 'but with action.', 'influencer-hq' ); ?></p>
                                    <p><?php esc_html_e( 'Not over weeks —', 'influencer-hq' ); ?></p>
                                    <p><?php esc_html_e( 'but in a single 24-hour test.', 'influencer-hq' ); ?></p>
                                    <p><?php esc_html_e( 'This format is designed to showcase authentic rivalry, chemistry, and competitive storytelling.', 'influencer-hq' ); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="accordion custom-accordion" id="privateAccordion">
                        <?php
                        get_template_part(
                            'template-parts/competition-why-equity',
                            null,
                            array(
                                'instance_id' => 'private',
                                'expanded'    => true,
                                'parent_id'   => 'privateAccordion',
                            )
                        );
                        ?>
                        <div class="accordion-item mb-3">
                            <h2 class="accordion-header" id="headingPrivatePerf">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapsePrivatePerf" aria-expanded="false" aria-controls="collapsePrivatePerf">
                                    <span class="question-text"><?php esc_html_e( 'How is Win % calculated?', 'influencer-hq' ); ?></span>
                                </button>
                            </h2>
                            <div id="collapsePrivatePerf" class="accordion-collapse collapse" aria-labelledby="headingPrivatePerf" data-bs-parent="#privateAccordion">
                                <div class="accordion-body">
                                    <p><?php esc_html_e( 'Net Gain / Total Amount Played = Performance Percent.', 'influencer-hq' ); ?></p>
                                    <p><?php esc_html_e( 'Scores can never be negative. The lowest possible score is 0 percent.', 'influencer-hq' ); ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item mb-3">
                            <h2 class="accordion-header" id="headingPrivateWinner">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapsePrivateWinner" aria-expanded="false" aria-controls="collapsePrivateWinner">
                                    <span class="question-text"><?php esc_html_e( 'How is the winner determined?', 'influencer-hq' ); ?></span>
                                </button>
                            </h2>
                            <div id="collapsePrivateWinner" class="accordion-collapse collapse" aria-labelledby="headingPrivateWinner" data-bs-parent="#privateAccordion">
                                <div class="accordion-body">
                                    <p><?php esc_html_e( 'The Influencer with the higher Performance Percent wins.', 'influencer-hq' ); ?></p>
                                    <p><?php esc_html_e( 'Private Challenges determine a match winner. Points are still awarded based on placement within the broader contest framework.', 'influencer-hq' ); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Challenge API Debug Panel -->
                    <div class="competition-panel-card" id="challenge-api-debug" style="display:none;">
                        <div class="competition-panel-title">Challenge API</div>

                        <!-- 1. Get My Challenges -->
                        <div class="challenge-api-block">
                            <div class="challenge-api-block-title">getChallengesForPlayer</div>
                            <div class="challenge-api-controls">
                                <select id="cha-status-filter" class="competition-dropdown-select" style="width:auto;">
                                    <option value="">All Statuses</option>
                                    <option value="active">Active</option>
                                    <option value="completed">Completed</option>
                                    <option value="archived">Archived</option>
                                </select>
                                <button id="cha-get-btn" class="competition-btn">Get My Challenges</button>
                            </div>
                            <div class="competition-debug-block" id="cha-get-debug" style="display:none;">
                                <div class="competition-debug-label">Call &amp; Payload</div>
                                <pre id="cha-get-req" class="competition-debug-pre"></pre>
                                <div class="competition-debug-label" style="margin-top:8px;">Response</div>
                                <pre id="cha-get-res" class="competition-debug-pre"></pre>
                            </div>
                        </div>

                        <!-- 2. Create Challenge -->
                        <div class="challenge-api-block">
                            <div class="challenge-api-block-title">createChallenge</div>
                            <div class="challenge-api-controls">
                                <input id="cha-create-name" class="competition-input" type="text" placeholder="Challenge name" value="Test Challenge">
                                <input id="cha-create-players" class="competition-input" type="text" placeholder="challengedPlayers (comma-sep IDs)">
                                <button id="cha-create-btn" class="competition-btn">Create Challenge</button>
                            </div>
                            <div class="competition-debug-block" id="cha-create-debug" style="display:none;">
                                <div class="competition-debug-label">Call &amp; Payload</div>
                                <pre id="cha-create-req" class="competition-debug-pre"></pre>
                                <div class="competition-debug-label" style="margin-top:8px;">Response</div>
                                <pre id="cha-create-res" class="competition-debug-pre"></pre>
                            </div>
                        </div>

                        <!-- 3. Get Challenge Details -->
                        <div class="challenge-api-block">
                            <div class="challenge-api-block-title">getChallengeDetails</div>
                            <div class="challenge-api-controls">
                                <input id="cha-details-id" class="competition-input" type="text" placeholder="challengeId">
                                <button id="cha-details-btn" class="competition-btn">Get Details</button>
                            </div>
                            <div class="competition-debug-block" id="cha-details-debug" style="display:none;">
                                <div class="competition-debug-label">Call &amp; Payload</div>
                                <pre id="cha-details-req" class="competition-debug-pre"></pre>
                                <div class="competition-debug-label" style="margin-top:8px;">Response</div>
                                <pre id="cha-details-res" class="competition-debug-pre"></pre>
                            </div>
                        </div>

                        <!-- 4. Join Challenge -->
                        <div class="challenge-api-block">
                            <div class="challenge-api-block-title">joinChallenges</div>
                            <div class="challenge-api-controls">
                                <input id="cha-join-id" class="competition-input" type="text" placeholder="challengeId">
                                <input id="cha-join-team" class="competition-input" type="text" placeholder="teamName (optional)">
                                <button id="cha-join-btn" class="competition-btn">Join Challenge</button>
                            </div>
                            <div class="competition-debug-block" id="cha-join-debug" style="display:none;">
                                <div class="competition-debug-label">Call &amp; Payload</div>
                                <pre id="cha-join-req" class="competition-debug-pre"></pre>
                                <div class="competition-debug-label" style="margin-top:8px;">Response</div>
                                <pre id="cha-join-res" class="competition-debug-pre"></pre>
                            </div>
                        </div>
                    </div>

                    <!-- Create Private Challenge Section -->
                    <div class="cpc-wrap" id="private-results">

                        <!-- Section header -->
                        <div class="cpc-header">
                            <img src="<?php echo get_template_directory_uri(); ?>/images/portal-competition.png" alt="" class="cpc-icon">
                            <span class="cpc-title"><?php esc_html_e( 'CREATE PRIVATE CHALLENGE', 'influencer-hq' ); ?></span>
                        </div>
                        <div class="cpc-divider"></div>

                        <?php
                        $cpc_uid   = get_current_user_id();
                        $cpc_email = wp_get_current_user()->user_email;

                        $cpc_issued = get_posts(array(
                            'post_type'      => 'challenge',
                            'post_status'    => 'publish',
                            'posts_per_page' => -1,
                            'orderby'        => 'date',
                            'order'          => 'DESC',
                            'meta_query'     => array(
                                array('key' => '_challenger_id', 'value' => $cpc_uid, 'compare' => '='),
                            ),
                        ));

                        $cpc_issued_accepted = array();
                        foreach ( $cpc_issued as $ci ) {
                            if ( get_post_meta( $ci->ID, '_challenge_status', true ) === 'accepted' ) {
                                $cpc_issued_accepted[] = $ci;
                            }
                        }

                        $cpc_received = get_posts(array(
                            'post_type'      => 'challenge',
                            'post_status'    => 'publish',
                            'posts_per_page' => -1,
                            'orderby'        => 'date',
                            'order'          => 'DESC',
                            'meta_query'     => array(
                                'relation' => 'OR',
                                array('key' => '_invitee_email',    'value' => $cpc_email, 'compare' => '='),
                                array('key' => '_invitee_user_id',  'value' => $cpc_uid,   'compare' => '='),
                                array('key' => '_accepted_user_id', 'value' => $cpc_uid,   'compare' => '='),
                            ),
                        ));

                        // Drop challenges this user created (should only appear under "created").
                        $cpc_received = array_values( array_filter( $cpc_received, function ( $cr ) use ( $cpc_uid ) {
                            return (int) get_post_meta( $cr->ID, '_challenger_id', true ) !== (int) $cpc_uid;
                        } ) );
                        ?>

                        <!-- Accordion 1: Instructions -->
                        <div class="cpc-accordion" id="cpcAccordionInstructions">
                            <div class="cpc-accordion-header collapsed" data-bs-toggle="collapse" data-bs-target="#cpcCollapseInstructions" aria-expanded="false" aria-controls="cpcCollapseInstructions">
                                <svg class="cpc-chevron" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#b8972f" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
                                <span class="competition-block-title" style="margin:0;"><?php esc_html_e( 'Instructions to Create Private Challenges', 'influencer-hq' ); ?></span>
                            </div>
                            <div id="cpcCollapseInstructions" class="collapse">
                                <div class="cpc-accordion-body">
                                    <p class="cpc-instructions-lead"><?php esc_html_e( 'Each day, you can originate and accept one private challenge which includes both influencers and their followers.', 'influencer-hq' ); ?></p>
                                    <p class="cpc-instructions-lead"><?php esc_html_e( 'Private challenges run from midnight to midnight, Hong Kong Time.', 'influencer-hq' ); ?></p>
                                    <ol class="cpc-instructions-steps">
                                        <li><?php esc_html_e( 'Reach out by any method to the Influencer you want to challenge.', 'influencer-hq' ); ?></li>
                                        <li><?php esc_html_e( 'Don\'t officially create a challenge until you receive yes from the challenged influencer.', 'influencer-hq' ); ?></li>
                                        <li><?php esc_html_e( 'Record the challenge in the form below AFTER you receive YES.', 'influencer-hq' ); ?></li>
                                        <li><?php esc_html_e( 'We will generate challenge link that you should share with your influencer opponent.', 'influencer-hq' ); ?></li>
                                    </ol>
                                </div>
                            </div>
                        </div>

                        <!-- Accordion 2: Create New Private Challenges -->
                        <div class="cpc-accordion" id="cpcAccordion2">
                            <div class="cpc-accordion-header collapsed" data-bs-toggle="collapse" data-bs-target="#cpcCollapse2" aria-expanded="false" aria-controls="cpcCollapse2">
                                <svg class="cpc-chevron" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#b8972f" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
                                <span class="competition-block-title" style="margin:0;"><?php esc_html_e( 'Create New Private Challenges', 'influencer-hq' ); ?></span>
                            </div>
                            <div id="cpcCollapse2" class="collapse">
                                <div class="cpc-accordion-body">
                                    <div class="competition-block-title"><?php esc_html_e( 'Influencer', 'influencer-hq' ); ?></div>
                                    <div class="cpc-search-wrap">
                                        <input class="cpc-input" id="cpc-username-search" type="text" placeholder="<?php esc_attr_e( 'username', 'influencer-hq' ); ?>" autocomplete="off">
                                        <div class="cpc-search-results" id="cpc-search-results" hidden></div>
                                    </div>
                                    <input type="hidden" id="cpc-invitee-user-id" value="">
                                    <input type="hidden" id="cpc-username" value="">

                                    <div class="competition-block-title"><?php esc_html_e( 'Challenge Date', 'influencer-hq' ); ?></div>
                                    <input class="cpc-input cpc-date-input" id="cpc-date" type="date" min="<?php echo esc_attr( date( 'Y-m-d' ) ); ?>" max="<?php echo esc_attr( date( 'Y-m-d', strtotime( '+2 years' ) ) ); ?>">

                                    <div class="cpc-row-controls" style="margin-top:10px;">
                                        <span class="cpc-info-icon" title="<?php esc_attr_e( 'Start time and end time for all Private Challenges are 00:01 HK time (24 clock) through midnight each night.', 'influencer-hq' ); ?>">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="#b8972f" xmlns="http://www.w3.org/2000/svg"><circle cx="12" cy="12" r="10"/><text x="12" y="16" text-anchor="middle" font-size="13" font-family="serif" fill="#000">i</text></svg>
                                        </span>
                                        <button id="cpc-create-btn" type="button" class="cpc-btn-create"><?php esc_html_e( 'Create Challenge Links', 'influencer-hq' ); ?></button>
                                    </div>

                                    <div id="cpc-form-msg" style="display:none;font-family:'Be Vietnam Pro',sans-serif;font-size:13px;margin-bottom:6px;"></div>

                                    <div class="competition-block-title" style="margin-top:14px;"><?php esc_html_e( 'My Shareable Link', 'influencer-hq' ); ?></div>
                                    <div class="cpc-share-row">
                                        <input class="cpc-input cpc-input-muted" id="cpc-share-link" type="text" placeholder="<?php esc_attr_e( '(Shareable challenge URL appears here)', 'influencer-hq' ); ?>" readonly>
                                        <button type="button" class="cpc-icon-btn" id="cpc-copy-link" aria-label="<?php esc_attr_e( 'Copy link', 'influencer-hq' ); ?>" title="<?php esc_attr_e( 'Copy', 'influencer-hq' ); ?>">
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#b8972f" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                                        </button>
                                        <button type="button" class="cpc-icon-btn" id="cpc-share-link-btn" aria-label="<?php esc_attr_e( 'Share link', 'influencer-hq' ); ?>" title="<?php esc_attr_e( 'Share', 'influencer-hq' ); ?>">
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#b8972f" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><path d="M8.59 13.51l6.83 3.98M15.41 6.51l-6.82 3.98"/></svg>
                                        </button>
                                    </div>
                                    <p class="cpc-share-help"><?php esc_html_e( 'Share this link with your influencer opponent so he/she can accept the challenge.', 'influencer-hq' ); ?></p>
                                </div>
                            </div>
                        </div>

                        <!-- Accordion 3: See Current Challenges -->
                        <div class="cpc-accordion" id="cpcAccordion1">
                            <div class="cpc-accordion-header collapsed" data-bs-toggle="collapse" data-bs-target="#cpcCollapse1" aria-expanded="false" aria-controls="cpcCollapse1">
                                <svg class="cpc-chevron" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#b8972f" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
                                <span class="competition-block-title" style="margin:0;"><?php esc_html_e( 'See Current Challenges', 'influencer-hq' ); ?></span>
                            </div>
                            <div id="cpcCollapse1" class="collapse">
                                <div class="cpc-accordion-body">

                                    <div class="competition-block-title"><?php esc_html_e( 'Private Challenges You\'ve Created That Have Been Accepted', 'influencer-hq' ); ?></div>
                                    <div class="cpc-divider-thin"></div>
                                    <div class="cpc-table">
                                        <div class="cpc-table-head">
                                            <span class="cpc-col-date"><?php esc_html_e( 'Challenge Date', 'influencer-hq' ); ?></span>
                                            <span class="cpc-col-user"><?php esc_html_e( 'Challenged Person\'s Username', 'influencer-hq' ); ?></span>
                                            <span class="cpc-col-link"><?php esc_html_e( 'Link', 'influencer-hq' ); ?></span>
                                        </div>
                                        <div class="cpc-divider-thin"></div>
                                        <?php if ( empty( $cpc_issued_accepted ) ) : ?>
                                        <div class="cpc-table-row cpc-table-empty"><span><?php esc_html_e( 'No accepted challenges yet.', 'influencer-hq' ); ?></span></div>
                                        <?php else : foreach ( $cpc_issued_accepted as $ci ) :
                                            $ci_invitee_id = (int) get_post_meta( $ci->ID, '_invitee_user_id', true );
                                            $ci_username   = (string) get_post_meta( $ci->ID, '_invitee_username', true );
                                            if ( $ci_username === '' && $ci_invitee_id > 0 && function_exists( 'ihq_challenge_display_username' ) ) {
                                                $ci_username = ihq_challenge_display_username( $ci_invitee_id );
                                            }
                                            if ( $ci_username === '' ) {
                                                $ci_username = trim(
                                                    (string) get_post_meta( $ci->ID, '_invitee_first_name', true ) . ' ' .
                                                    (string) get_post_meta( $ci->ID, '_invitee_last_name', true )
                                                );
                                            }
                                            $ci_date = get_post_meta( $ci->ID, '_challenge_date', true ) ?: get_the_date( 'Y-m-d', $ci->ID );
                                            $ci_link = function_exists( 'ihq_challenge_invite_link' )
                                                ? ihq_challenge_invite_link( $ci->ID )
                                                : '';
                                        ?>
                                        <div class="cpc-table-row">
                                            <span class="cpc-col-date"><?php echo esc_html( date( 'M j, Y', strtotime( $ci_date ) ) ); ?></span>
                                            <span class="cpc-col-user"><?php echo esc_html( $ci_username !== '' ? $ci_username : '—' ); ?></span>
                                            <span class="cpc-col-link">
                                                <?php if ( $ci_link !== '' ) : ?>
                                                <input class="cpc-link-input" type="text" readonly value="<?php echo esc_attr( $ci_link ); ?>" onclick="this.select();">
                                                <?php else : ?>
                                                —
                                                <?php endif; ?>
                                            </span>
                                        </div>
                                        <?php endforeach; endif; ?>
                                    </div>
                                    <a class="cpc-table-more" href="#private-leaderboards"><?php esc_html_e( 'more >', 'influencer-hq' ); ?></a>

                                    <div class="competition-block-title" style="margin-top:18px;"><?php esc_html_e( 'Private Challenges You\'ve Received', 'influencer-hq' ); ?></div>
                                    <div class="cpc-divider-thin"></div>
                                    <div class="cpc-table">
                                        <div class="cpc-table-head">
                                            <span class="cpc-col-date"><?php esc_html_e( 'Challenge Date', 'influencer-hq' ); ?></span>
                                            <span class="cpc-col-user"><?php esc_html_e( 'Challenger\'s Username', 'influencer-hq' ); ?></span>
                                            <span class="cpc-col-link"><?php esc_html_e( 'Link', 'influencer-hq' ); ?></span>
                                        </div>
                                        <div class="cpc-divider-thin"></div>
                                        <?php if ( empty( $cpc_received ) ) : ?>
                                        <div class="cpc-table-row cpc-table-empty"><span><?php esc_html_e( 'No challenges received yet.', 'influencer-hq' ); ?></span></div>
                                        <?php else : foreach ( $cpc_received as $cr ) :
                                            $cr_chal_id = (int) get_post_meta( $cr->ID, '_challenger_id', true );
                                            $cr_username = function_exists( 'ihq_challenge_display_username' )
                                                ? ihq_challenge_display_username( $cr_chal_id )
                                                : '';
                                            $cr_date = get_post_meta( $cr->ID, '_challenge_date', true ) ?: get_the_date( 'Y-m-d', $cr->ID );
                                            $cr_link = function_exists( 'ihq_challenge_invite_link' )
                                                ? ihq_challenge_invite_link( $cr->ID )
                                                : '';
                                        ?>
                                        <div class="cpc-table-row">
                                            <span class="cpc-col-date"><?php echo esc_html( date( 'M j, Y', strtotime( $cr_date ) ) ); ?></span>
                                            <span class="cpc-col-user"><?php echo esc_html( $cr_username !== '' ? $cr_username : '—' ); ?></span>
                                            <span class="cpc-col-link">
                                                <?php if ( $cr_link !== '' ) : ?>
                                                <input class="cpc-link-input" type="text" readonly value="<?php echo esc_attr( $cr_link ); ?>" onclick="this.select();">
                                                <?php else : ?>
                                                —
                                                <?php endif; ?>
                                            </span>
                                        </div>
                                        <?php endforeach; endif; ?>
                                    </div>
                                    <a class="cpc-table-more" href="#private-leaderboards"><?php esc_html_e( 'more >', 'influencer-hq' ); ?></a>

                                </div>
                            </div>
                        </div>

                    </div><!-- /.cpc-wrap -->

                    <span id="private-influencer" class="hm-scroll-anchor" aria-hidden="true"></span>
                    <span id="private-follower" class="hm-scroll-anchor" aria-hidden="true"></span>

                    <div class="portal-leaderboards-iframe-wrap" id="private-leaderboards">
                        <iframe
                            title="<?php echo esc_attr__( 'Influencer HQ leaderboards', 'influencer-hq' ); ?>"
                            src="<?php echo esc_url( $portal_leaderboards_iframe_url ); ?>"
                            loading="lazy"
                            referrerpolicy="strict-origin-when-cross-origin"
                            allowfullscreen
                        ></iframe>
                    </div>
                </div>

                <!-- Community Tab -->
                <div class="competition-panel" id="community-tab">
                    <div class="community-intro-row">
                        <div class="community-intro-card">
                            <p><?php esc_html_e( 'Community Competitions last weekly from Sunday to Saturday, and automatically includes all play of your followers, who are competing against each other for bragging rights.', 'influencer-hq' ); ?></p>
                            <p><?php esc_html_e( 'All of your followers\' play will automatically be included in the results of each week\'s competition.', 'influencer-hq' ); ?></p>
                            <p><?php esc_html_e( 'We believe leadership begins at home.', 'influencer-hq' ); ?></p>
                            <p><?php esc_html_e( 'This is your chance to motivate the people who already believe in you.', 'influencer-hq' ); ?></p>
                            <p><?php esc_html_e( 'Not to watch —', 'influencer-hq' ); ?></p>
                            <p><?php esc_html_e( 'but to participate.', 'influencer-hq' ); ?></p>
                            <p><?php esc_html_e( 'Every action becomes momentum.', 'influencer-hq' ); ?></p>
                            <p><?php esc_html_e( 'Not through posts.', 'influencer-hq' ); ?></p>
                            <p><?php esc_html_e( 'Not through promises.', 'influencer-hq' ); ?></p>
                            <p><?php esc_html_e( 'Through performance.', 'influencer-hq' ); ?></p>
                            <p><?php esc_html_e( 'We believe Community Competitions build follower loyalty.', 'influencer-hq' ); ?></p>
                            <p>
                                <a class="community-live-link" href="<?php echo esc_url( home_url( '/portal/live#live-request' ) ); ?>">
                                    <?php esc_html_e( 'CLICK HERE to schedule Live Appearances on the World Network.', 'influencer-hq' ); ?>
                                </a>
                            </p>
                        </div>
                        <div class="community-coach-fab-host" id="community-coach-fab-host" aria-hidden="true"></div>
                    </div>

                    <?php
                    get_template_part(
                        'template-parts/competition-why-equity',
                        null,
                        array(
                            'instance_id' => 'community',
                            'expanded'    => true,
                        )
                    );
                    ?>

                    <h2 class="competition-section-title" id="community-results"><?php esc_html_e( 'Community Competitions', 'influencer-hq' ); ?></h2>

                    <span id="community-influencer" class="hm-scroll-anchor" aria-hidden="true"></span>
                    <span id="community-follower" class="hm-scroll-anchor" aria-hidden="true"></span>

                    <div class="competition-block">
                        <div class="competition-block-title"><?php esc_html_e( 'Community Competition', 'influencer-hq' ); ?></div>
                        <div class="accordion custom-accordion" id="communityAccordion">
                            <div class="accordion-item mb-3">
                                <h2 class="accordion-header" id="headingCommunityWhat">
                                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseCommunityWhat" aria-expanded="true" aria-controls="collapseCommunityWhat">
                                        <span class="question-text"><?php esc_html_e( 'What is Community Competition?', 'influencer-hq' ); ?></span>
                                    </button>
                                </h2>
                                <div id="collapseCommunityWhat" class="accordion-collapse collapse show" aria-labelledby="headingCommunityWhat" data-bs-parent="#communityAccordion">
                                    <div class="accordion-body">
                                        <p><?php esc_html_e( 'Community Competition measures how effectively an Influencer activates their own followers.', 'influencer-hq' ); ?></p>
                                        <p><?php esc_html_e( 'It focuses on participation and shared performance.', 'influencer-hq' ); ?></p>
                                    </div>
                                </div>
                            </div>
                            <div class="accordion-item mb-3">
                                <h2 class="accordion-header" id="headingCommunityPerf">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseCommunityPerf" aria-expanded="false" aria-controls="collapseCommunityPerf">
                                        <span class="question-text"><?php esc_html_e( 'How is performance calculated?', 'influencer-hq' ); ?></span>
                                    </button>
                                </h2>
                                <div id="collapseCommunityPerf" class="accordion-collapse collapse" aria-labelledby="headingCommunityPerf" data-bs-parent="#communityAccordion">
                                    <div class="accordion-body">
                                        <p><?php esc_html_e( 'Net Gain / Total Amount Played = Performance Percent.', 'influencer-hq' ); ?></p>
                                    </div>
                                </div>
                            </div>
                            <div class="accordion-item mb-3">
                                <h2 class="accordion-header" id="headingCommunityPoints">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseCommunityPoints" aria-expanded="false" aria-controls="collapseCommunityPoints">
                                        <span class="question-text"><?php esc_html_e( 'How are points and medals awarded?', 'influencer-hq' ); ?></span>
                                    </button>
                                </h2>
                                <div id="collapseCommunityPoints" class="accordion-collapse collapse" aria-labelledby="headingCommunityPoints" data-bs-parent="#communityAccordion">
                                    <div class="accordion-body">
                                        <p><?php esc_html_e( 'Points are awarded using the same percentile system described above.', 'influencer-hq' ); ?></p>
                                        <p><?php esc_html_e( 'Quarterly medal tiers are identical.', 'influencer-hq' ); ?></p>
                                        <p><?php esc_html_e( 'Community results contribute to Team totals.', 'influencer-hq' ); ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="accordion custom-accordion competition-scoring-accordion" id="communityScoringAccordion">
                        <div class="accordion-item mb-3">
                            <h2 class="accordion-header" id="headingCommunityScoring">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseCommunityScoring" aria-expanded="true" aria-controls="collapseCommunityScoring">
                                    <span class="question-text"><?php esc_html_e( 'SCORING SYSTEM', 'influencer-hq' ); ?></span>
                                </button>
                            </h2>
                            <div id="collapseCommunityScoring" class="accordion-collapse collapse show" aria-labelledby="headingCommunityScoring" data-bs-parent="#communityScoringAccordion">
                                <div class="accordion-body">
                                    <p class="competition-rule-note"><?php esc_html_e( 'Points are awarded at the end of each contest. Medals are awarded based on total points at the end of each quarter.', 'influencer-hq' ); ?></p>
                                    <div class="competition-rule-grid">
                                        <div>
                                            <div class="competition-rule-title"><?php esc_html_e( 'Finish', 'influencer-hq' ); ?></div>
                                            <div class="competition-rule-list">Top 10%<br>11%-20%<br>21%-30%<br>31%-40%<br>41%-50%</div>
                                        </div>
                                        <div>
                                            <div class="competition-rule-title"><?php esc_html_e( 'Points', 'influencer-hq' ); ?></div>
                                            <div class="competition-rule-list">5<br>4<br>3<br>2<br>1</div>
                                        </div>
                                        <div>
                                            <div class="competition-rule-title"><?php esc_html_e( 'Total Points', 'influencer-hq' ); ?></div>
                                            <div class="competition-rule-list">Top 10%<br>11%-20%<br>21%-30%<br>31%-40%</div>
                                        </div>
                                        <div>
                                            <div class="competition-rule-title"><?php esc_html_e( 'Medals', 'influencer-hq' ); ?></div>
                                            <div class="competition-rule-list">Diamond<br>Gold<br>Silver<br>Bronze</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="portal-leaderboards-iframe-wrap" id="community-leaderboards">
                        <iframe
                            title="<?php echo esc_attr__( 'Influencer / Follower Competition Results', 'influencer-hq' ); ?>"
                            src="<?php echo esc_url( $portal_leaderboards_iframe_url ); ?>"
                            loading="lazy"
                            referrerpolicy="strict-origin-when-cross-origin"
                            allowfullscreen
                        ></iframe>
                    </div>
                </div>

                <!-- Leagues Tab -->
                <div class="competition-panel" id="leagues-tab">
                    <div class="competition-card">
                        <p>We believe competition creates identity.</p>
                        <p>A flag. A name. A team you carry into every contest.</p>
                        <p>Leagues turn influence into legacy. Where your stream represents something bigger than yourself - and everyone knows what you stand for.</p>
                        <p>That is why we built Leagues. To give your followers a team. And give your team a reason to rise.</p>
                    </div>

                    <?php
                    get_template_part(
                        'template-parts/competition-why-equity',
                        null,
                        array(
                            'instance_id' => 'leagues',
                            'expanded'    => true,
                        )
                    );
                    ?>

                    <h2 class="competition-section-title" id="leagues-results">Leagues</h2>

                    <div class="competition-panel-card">
                        <div class="competition-panel-title" id="leagues-celebrity">Celebrity Follower Leagues</div>
                        <p class="competition-panel-subtitle">Belong to something bigger. Lead a team. Structured, weekly competitions are organized around celebrity teams, where Influencers step in as team leaders and rally their followers to compete under banners inspired by Movie Stars, Sports Icons, and Music Artists.</p>
                        <p class="competition-panel-link">View Profile to Choose Your Celebrity &gt;&gt;</p>
                        <div class="competition-panel-title" id="leagues-international">International League</div>
                        <p class="competition-panel-subtitle">When you select an International League Team, you are not just joining global competition - you are planting your flag on the world stage.</p>
                        <p class="competition-panel-link">View Profile to Choose Your International Team &gt;&gt;</p>
                    </div>

                    <div class="competition-panel-card">
                        <div class="competition-panel-title">Choose a Flag. Captain a Team.</div>
                        <p class="competition-panel-subtitle">Choose one of 60 Celebrity Follower Groups (20 Sports, 20 Music, 20 Movies). That flag becomes your team - your tribe - your identity. You can also captain one of 20 International League Teams, representing countries and regions in Olympic-style matchups.</p>
                        <div class="accordion custom-accordion" id="leagueAccordion">
                            <div class="accordion-item mb-3">
                                <h2 class="accordion-header" id="headingLeaguePride">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseLeaguePride" aria-expanded="false" aria-controls="collapseLeaguePride">
                                        <span class="question-text">Global Competition. Local Pride.</span>
                                    </button>
                                </h2>
                                <div id="collapseLeaguePride" class="accordion-collapse collapse" aria-labelledby="headingLeaguePride" data-bs-parent="#leagueAccordion">
                                    <div class="accordion-body">
                                        <p>We have seen it in sports. We have seen it in the Olympics.</p>
                                        <p>Now - for the first time - streaming brings that same pride and unity to Baccarat, the Game of Kings for more than 400 years.</p>
                                    </div>
                                </div>
                            </div>
                            <div class="accordion-item mb-3">
                                <h2 class="accordion-header" id="headingLeagueScale">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseLeagueScale" aria-expanded="false" aria-controls="collapseLeagueScale">
                                        <span class="question-text">The Scale of the Leagues</span>
                                    </button>
                                </h2>
                                <div id="collapseLeagueScale" class="accordion-collapse collapse" aria-labelledby="headingLeagueScale" data-bs-parent="#leagueAccordion">
                                    <div class="accordion-body">
                                        <p>60 Celebrity Groups = more than 2 billion fans worldwide.</p>
                                        <p>The International League covers nearly every region on the planet.</p>
                                        <p>Avantage promotes directly into these communities - amplifying you and your stream, building visibility, and driving pride.</p>
                                    </div>
                                </div>
                            </div>
                            <div class="accordion-item mb-3">
                                <h2 class="accordion-header" id="headingLeagueRole">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseLeagueRole" aria-expanded="false" aria-controls="collapseLeagueRole">
                                        <span class="question-text">Your Leadership Role</span>
                                    </button>
                                </h2>
                                <div id="collapseLeagueRole" class="accordion-collapse collapse" aria-labelledby="headingLeagueRole" data-bs-parent="#leagueAccordion">
                                    <div class="accordion-body">
                                        <p>Choose your Celebrity Follower Team. Choose your International League Team. Represent both - and lead them to victory.</p>
                                        <p>As a Team Captain, your stream carries influence. Every match inspires followers. Every broadcast builds presence, momentum, and reach.</p>
                                        <p>Your leadership begins the moment you go live.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="competition-dropdown">
                        <div class="competition-dropdown-header">Celebrity Followers Leagues</div>
                        <div class="competition-dropdown-body">
                            <p class="competition-dropdown-label">Choose your favorite celebrity in each category. These selections are saved to your profile the same way they are on the Portal Profile page.</p>
                            <div class="sett-card">
                                <div class="celeb-grid-layout">
                                    <?php foreach ( $celeb_labels as $cat => $label ) :
                                        $saved = $celebrity_selections[ $cat ] ?? '';
                                    ?>
                                    <div class="celeb-col" id="leagues-<?php echo esc_attr( str_replace( '_', '-', $cat ) ); ?>">
                                        <span class="celeb-col-label"><?php echo esc_html( $label ); ?></span>
                                        <select class="celeb-select" data-category="<?php echo esc_attr( $cat ); ?>">
                                            <option value="">Open</option>
                                            <?php foreach ( $celeb_lists[ $cat ] as $name ) : ?>
                                            <option value="<?php echo esc_attr( $name ); ?>"<?php selected( $saved, $name ); ?>><?php echo esc_html( $name ); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="competition-dropdown">
                        <div class="competition-dropdown-header">International League Team</div>
                        <div class="competition-dropdown-body">
                            <p class="competition-dropdown-label">Select your country or region for the International League. This choice saves to the same profile field as Portal Profile.</p>
                            <div class="sett-card">
                                <div class="celeb-grid-layout" style="grid-template-columns:1fr;">
                                    <div class="celeb-col">
                                        <span class="celeb-col-label">Country / Region</span>
                                        <select class="celeb-select" id="intlLeagueSelect">
                                            <option value="">Open</option>
                                            <?php foreach ( $intl_league_regions as $region ) : ?>
                                            <option value="<?php echo esc_attr( $region ); ?>"<?php selected( $intl_league_team, $region ); ?>><?php echo esc_html( $region ); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <style>
                        .celeb-grid-layout {
                            display: grid;
                            grid-template-columns: 1fr 1fr 1fr;
                            gap: 10px;
                            padding: 10px;
                        }
                        .celeb-col {
                            display: flex;
                            flex-direction: column;
                            gap: 6px;
                        }
                        .celeb-col-label {
                            font-size: 15px;
                            color: #e5e5e5;
                            text-align: center;
                            font-weight: 600;
                        }
                        .celeb-select {
                            background: #000;
                            color: #fff;
                            border: 1px solid rgba(184, 151, 47, 0.5);
                            border-radius: 3px;
                            font-size: 14px;
                            padding: 3px 4px;
                            outline: none;
                            cursor: pointer;
                            width: 100%;
                        }
                        .celeb-select:focus {
                            border-color: #b8972f;
                        }
                        .celeb-select option {
                            background: #1a1a1a;
                        }
                        @media (max-width: 600px) {
                            .celeb-grid-layout {
                                grid-template-columns: 1fr;
                            }
                            .celeb-col-label {
                                text-align: left;
                            }
                        }
                    </style>

                    <div class="competition-rule-block">
                        <div class="competition-rule-header">League Medals</div>
                        <p class="competition-rule-note">Points are awarded at the end of each week. Medals are awarded to Team Captains based on total points at the end of each 23-week season.</p>
                        <div class="competition-rule-grid">
                            <div>
                                <div class="competition-rule-title">Finish</div>
                                <div class="competition-rule-list">Top 10%<br>11%-20%<br>21%-30%<br>31%-40%<br>41%-50%</div>
                            </div>
                            <div>
                                <div class="competition-rule-title">Points</div>
                                <div class="competition-rule-list">5<br>4<br>3<br>2<br>1</div>
                            </div>
                            <div>
                                <div class="competition-rule-title">Total Points</div>
                                <div class="competition-rule-list">Top 10%<br>11%-20%<br>21%-30%<br>31%-40%</div>
                            </div>
                            <div>
                                <div class="competition-rule-title">Medals</div>
                                <div class="competition-rule-list">Diamond<br>Gold<br>Silver<br>Bronze</div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
            
        </div>
        
        <?php get_template_part( 'template-parts/portal-footer' ); ?>

    </main><!-- #main -->

<script>
var _ajax            = <?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>;
var _nonce           = <?php echo wp_json_encode( wp_create_nonce( 'settings_save_nonce' ) ); ?>;
var _compAjaxUrl      = <?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>;
var _compPointsNonce  = <?php echo wp_json_encode( wp_create_nonce( 'rankings_summary_for_player_nonce' ) ); ?>;
var _challengeNonce   = <?php echo wp_json_encode( wp_create_nonce( 'challenge_api_nonce' ) ); ?>;
</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // -------------------------------------------------------
    // Get Points — calls getRankingsSummaryForPlayer via AJAX
    // -------------------------------------------------------
    var getPointsBtn  = document.getElementById('comp-get-points-btn');
    var pointsDebug   = document.getElementById('comp-points-debug');
    var pointsReqPre  = document.getElementById('comp-points-request');
    var pointsResPre  = document.getElementById('comp-points-response');
    var elWorld       = document.getElementById('comp-points-world');
    var elContinent   = document.getElementById('comp-points-continent');
    var elCountry     = document.getElementById('comp-points-country');
    var elTown        = document.getElementById('comp-points-town');

    function extractPts(level, data) {
        return (data && data[level] && data[level].myRank && data[level].myRank.resolvedPoints !== undefined)
            ? data[level].myRank.resolvedPoints
            : '—';
    }

    if (getPointsBtn) {
        getPointsBtn.addEventListener('click', function () {
            getPointsBtn.disabled    = true;
            getPointsBtn.textContent = 'Calling…';
            if (pointsDebug) pointsDebug.style.display = 'block';
            if (pointsReqPre)  pointsReqPre.textContent  = 'Waiting…';
            if (pointsResPre)  pointsResPre.textContent  = '';

            var payload = {
                action : 'rankings_summary_for_player',
                nonce  : _compPointsNonce,
                week   : new Date().toISOString()
            };

            if (pointsReqPre) pointsReqPre.textContent =
                'POST ' + _compAjaxUrl + '\n\n' +
                JSON.stringify({ action: payload.action, week: payload.week }, null, 2);

            var fd = new FormData();
            fd.append('action', payload.action);
            fd.append('nonce',  payload.nonce);
            fd.append('week',   payload.week);

            fetch(_compAjaxUrl, { method: 'POST', body: fd })
                .then(function (r) { return r.json(); })
                .then(function (res) {
                    var dbg         = res && res.data && res.data._debug ? res.data._debug : null;
                    var displayData = res && res.data ? Object.assign({}, res.data) : {};
                    delete displayData._debug;

                    if (pointsReqPre) pointsReqPre.textContent =
                        'POST ' + _compAjaxUrl + '\n\n' +
                        JSON.stringify(dbg || { action: payload.action, week: payload.week }, null, 2);

                    if (pointsResPre) pointsResPre.textContent =
                        JSON.stringify(displayData, null, 2);

                    // Extract resolvedPoints for each geographic level
                    var d = res.data || {};
                    if (elWorld)     elWorld.textContent     = extractPts('world',     d);
                    if (elContinent) elContinent.textContent = extractPts('continent', d);
                    if (elCountry)   elCountry.textContent   = extractPts('country',   d);
                    if (elTown)      elTown.textContent      = extractPts('town',      d);
                })
                .catch(function (err) {
                    if (pointsResPre) pointsResPre.textContent = 'Fetch error: ' + (err.message || err);
                })
                .finally(function () {
                    getPointsBtn.disabled    = false;
                    getPointsBtn.textContent = 'Get Points';
                });
        });
    }

    // Competition tabs functionality
    const tabButtons = document.querySelectorAll('.competition-tab-btn');

    // -------------------------------------------------------
    // Challenge API helpers
    // -------------------------------------------------------
    function challengeCall(cfg) {
        cfg.btn.disabled    = true;
        cfg.btn.textContent = 'Calling…';
        cfg.debug.style.display = 'block';
        cfg.req.textContent = 'Waiting…';
        cfg.res.textContent = '';

        var fd = new FormData();
        fd.append('action', cfg.action);
        fd.append('nonce',  _challengeNonce);
        if (cfg.fields) {
            Object.keys(cfg.fields).forEach(function(k) { fd.append(k, cfg.fields[k]); });
        }

        cfg.req.textContent = cfg.action + '\n\n' + JSON.stringify(cfg.fields || {}, null, 2);

        fetch(_compAjaxUrl, { method: 'POST', body: fd })
            .then(function(r) { return r.json(); })
            .then(function(res) {
                var dbg  = res && res.data && res.data._debug ? res.data._debug : null;
                var body = res && res.data ? Object.assign({}, res.data) : {};
                delete body._debug;
                cfg.req.textContent = JSON.stringify(dbg || cfg.fields || {}, null, 2);
                cfg.res.textContent = JSON.stringify(body, null, 2);
            })
            .catch(function(err) {
                cfg.res.textContent = 'Fetch error: ' + (err.message || err);
            })
            .finally(function() {
                cfg.btn.disabled    = false;
                cfg.btn.textContent = cfg.label;
            });
    }

    function save(action, params){
        var fd = new FormData();
        fd.append('action', action);
        fd.append('nonce',  _nonce);
        Object.keys(params).forEach(function(k){ fd.append(k, params[k]); });
        fetch(_ajax, { method:'POST', body:fd }).catch(function(){});
    }

    document.querySelectorAll('.celeb-select').forEach(function(sel){
        sel.addEventListener('change', function(){
            if (!sel.dataset.category && sel.id !== 'intlLeagueSelect') {
                return;
            }
            if (sel.id === 'intlLeagueSelect') {
                save('save_settings_field', { group: 'account', field: 'intl_league_team', value: sel.value });
                return;
            }
            save('save_settings_field', { group: 'account', field: 'celebrity_' + sel.dataset.category, value: sel.value });
        });
    });

    // 1. Get My Challenges
    var chaGetBtn = document.getElementById('cha-get-btn');
    if (chaGetBtn) {
        chaGetBtn.addEventListener('click', function() {
            var statusFilter = document.getElementById('cha-status-filter').value;
            challengeCall({
                btn: chaGetBtn, label: 'Get My Challenges',
                debug: document.getElementById('cha-get-debug'),
                req:   document.getElementById('cha-get-req'),
                res:   document.getElementById('cha-get-res'),
                action: 'get_challenges_for_player',
                fields: statusFilter ? { statusFilter: statusFilter } : {}
            });
        });
    }

    // 2. Create Challenge
    var chaCreateBtn = document.getElementById('cha-create-btn');
    if (chaCreateBtn) {
        chaCreateBtn.addEventListener('click', function() {
            var name    = document.getElementById('cha-create-name').value.trim() || 'Test Challenge';
            var players = document.getElementById('cha-create-players').value.trim();
            challengeCall({
                btn: chaCreateBtn, label: 'Create Challenge',
                debug: document.getElementById('cha-create-debug'),
                req:   document.getElementById('cha-create-req'),
                res:   document.getElementById('cha-create-res'),
                action: 'create_challenge',
                fields: { name: name, challengedPlayers: players, durationHours: 24, minNumberOfHands: 10, maxNumberOfHands: 100 }
            });
        });
    }

    // 3. Get Challenge Details
    var chaDetailsBtn = document.getElementById('cha-details-btn');
    if (chaDetailsBtn) {
        chaDetailsBtn.addEventListener('click', function() {
            var id = document.getElementById('cha-details-id').value.trim();
            if (!id) { document.getElementById('cha-details-res').textContent = 'Enter a challengeId first.'; document.getElementById('cha-details-debug').style.display = 'block'; return; }
            challengeCall({
                btn: chaDetailsBtn, label: 'Get Details',
                debug: document.getElementById('cha-details-debug'),
                req:   document.getElementById('cha-details-req'),
                res:   document.getElementById('cha-details-res'),
                action: 'get_challenge_details',
                fields: { challengeId: id }
            });
        });
    }

    // 4. Join Challenge
    var chaJoinBtn = document.getElementById('cha-join-btn');
    if (chaJoinBtn) {
        chaJoinBtn.addEventListener('click', function() {
            var id   = document.getElementById('cha-join-id').value.trim();
            var team = document.getElementById('cha-join-team').value.trim();
            if (!id) { document.getElementById('cha-join-res').textContent = 'Enter a challengeId first.'; document.getElementById('cha-join-debug').style.display = 'block'; return; }
            var fields = { challengeId: id };
            if (team) fields.teamName = team;
            challengeCall({
                btn: chaJoinBtn, label: 'Join Challenge',
                debug: document.getElementById('cha-join-debug'),
                req:   document.getElementById('cha-join-req'),
                res:   document.getElementById('cha-join-res'),
                action: 'join_challenges',
                fields: fields
            });
        });
    }
    const tabContents = document.querySelectorAll('.competition-panel');

    var COMP_HASH_SCROLL_MAX_ATTEMPTS = 48;
    var COMP_HASH_SCROLL_INTERVAL_MS = 50;
    var PORTAL_ANCHOR_OFFSET_FALLBACK_PX = 240;

    function readPortalAnchorOffsetPx() {
        var raw = window.getComputedStyle(document.documentElement).getPropertyValue('--portal-anchor-scroll-margin').trim();
        var parsed = parseFloat(raw);
        if (isFinite(parsed)) {
            return parsed;
        }
        return PORTAL_ANCHOR_OFFSET_FALLBACK_PX;
    }

    function portalScrollSmoothToElement(el) {
        if (!el || typeof el.getBoundingClientRect !== 'function') {
            return;
        }
        var rect = el.getBoundingClientRect();
        var y = window.pageYOffset + rect.top - readPortalAnchorOffsetPx();
        if (y < 0) {
            y = 0;
        }
        window.scrollTo({ top: y, behavior: 'smooth' });
    }

    function scrollCompetitionPanelIntoView(tabName) {
        if (!tabName) {
            return;
        }
        var panel = document.getElementById(tabName + '-tab');
        if (!panel) {
            return;
        }
        requestAnimationFrame(function () {
            requestAnimationFrame(function () {
                portalScrollSmoothToElement(panel);
            });
        });
    }

    function scheduleScrollToCompetitionHash(hash) {
        if (!hash) {
            return;
        }

        // Deep link into Create New Challenges: open accordion then scroll to the create button.
        if (hash === 'cpc-create-btn') {
            openCreatePrivateChallengeForm();
            return;
        }

        var target = document.getElementById(hash);
        if (!target) {
            return;
        }
        var attempts = 0;
        var initialDelayMs = hash === 'cpc-create-btn' ? 220 : 0;

        function tryScroll() {
            var panel = target.closest('.competition-panel');
            if (!panel) {
                requestAnimationFrame(function () {
                    requestAnimationFrame(function () {
                        portalScrollSmoothToElement(target);
                    });
                });
                return;
            }
            var isActive = panel.classList.contains('active');
            var display = window.getComputedStyle(panel).display;
            var panelVisible = isActive && display !== 'none';

            if (!panelVisible) {
                attempts += 1;
                if (attempts <= COMP_HASH_SCROLL_MAX_ATTEMPTS) {
                    setTimeout(tryScroll, COMP_HASH_SCROLL_INTERVAL_MS);
                    return;
                }
            }

            requestAnimationFrame(function () {
                requestAnimationFrame(function () {
                    portalScrollSmoothToElement(target);
                });
            });
        }

        requestAnimationFrame(function () {
            setTimeout(tryScroll, initialDelayMs);
        });
    }

    tabButtons.forEach(button => {
        button.addEventListener('click', function(ev) {
            const tabName = this.getAttribute('data-tab');
            
            // Remove active class from all buttons and contents
            tabButtons.forEach(btn => btn.classList.remove('active'));
            tabContents.forEach(content => content.classList.remove('active'));
            
            // Add active class to clicked button and corresponding content
            this.classList.add('active');
            document.getElementById(tabName + '-tab').classList.add('active');
            syncCompetitionCoachFab();
            if (ev && ev.isTrusted) {
                scrollCompetitionPanelIntoView(tabName);
            }
        });
    });

    function getActiveCompetitionTabName() {
        var active = document.querySelector('.competition-tab-btn.active');
        return active ? active.getAttribute('data-tab') : '';
    }

    function getIntroCoachHost() {
        if (window.matchMedia && window.matchMedia('(min-width: 1025px)').matches) {
            return document.getElementById('intro-coach-fab-host-desktop');
        }
        return document.getElementById('intro-coach-fab-host-mobile');
    }

    function syncCompetitionCoachFab() {
        var fab = document.getElementById('ihq-concierge-fab');
        if (!fab) {
            return;
        }
        var tab = getActiveCompetitionTabName();
        var host = null;
        if (tab === 'intro') {
            host = getIntroCoachHost();
        } else if (tab === 'private') {
            host = document.getElementById('private-coach-fab-host');
        } else if (tab === 'community') {
            host = document.getElementById('community-coach-fab-host');
        }
        if (host) {
            if (fab.parentNode !== host) {
                host.appendChild(fab);
            }
            return;
        }
        if (fab.parentNode && fab.parentNode.classList && fab.parentNode.classList.contains('intro-coach-fab-host')) {
            document.body.appendChild(fab);
            return;
        }
        if (fab.parentNode && fab.parentNode.id === 'private-coach-fab-host') {
            document.body.appendChild(fab);
            return;
        }
        if (fab.parentNode && fab.parentNode.id === 'community-coach-fab-host') {
            document.body.appendChild(fab);
        }
    }

    function syncCompetitionTabsCompact() {
        var tabs = document.querySelector('.competition-types');
        if (!tabs) {
            return;
        }
        var stickyNav = document.querySelector('.sticky-nav');
        var topOffset = 164;
        if (stickyNav) {
            var navRect = stickyNav.getBoundingClientRect();
            topOffset = Math.max(0, Math.round(navRect.bottom));
        }
        document.documentElement.style.setProperty('--portal-comp-tabs-top', topOffset + 'px');

        /*
         * Hysteresis: entering compact shrinks padding/icons, which bumps
         * getBoundingClientRect().top and would otherwise flip the class
         * every scroll frame. Enter tight, exit loose.
         */
        var COMPACT_ENTER_PX = 2;
        var COMPACT_EXIT_PX = 28;
        var tabsTop = tabs.getBoundingClientRect().top;
        var isCompact = tabs.classList.contains('is-compact');
        var stuck;
        if (isCompact) {
            stuck = tabsTop <= (topOffset + COMPACT_EXIT_PX);
        } else {
            stuck = tabsTop <= (topOffset + COMPACT_ENTER_PX);
        }
        tabs.classList.toggle('is-compact', stuck);
    }

    window.addEventListener('scroll', syncCompetitionTabsCompact, { passive: true });
    window.addEventListener('resize', function () {
        syncCompetitionTabsCompact();
        syncCompetitionCoachFab();
    }, { passive: true });

    function openCreatePrivateChallengeForm() {
        var privateBtn = document.querySelector('.competition-tab-btn[data-tab="private"]');
        if (privateBtn && !privateBtn.classList.contains('active')) {
            privateBtn.click();
        }
        var createCollapse = document.getElementById('cpcCollapse2');
        if (createCollapse && window.bootstrap && bootstrap.Collapse) {
            bootstrap.Collapse.getOrCreateInstance(createCollapse).show();
        }
        window.setTimeout(function () {
            var target = document.getElementById('cpcAccordion2') || document.getElementById('cpc-create-btn');
            if (target) {
                portalScrollSmoothToElement(target);
            }
        }, 220);
    }

    var privateScheduleCta = document.getElementById('private-schedule-cta');
    if (privateScheduleCta) {
        privateScheduleCta.addEventListener('click', function () {
            openCreatePrivateChallengeForm();
        });
    }

    syncCompetitionCoachFab();
    syncCompetitionTabsCompact();

    (function applyCompetitionTabFromQuery() {
        var params = new URLSearchParams(window.location.search);
        var tab = params.get('tab');
        var appliedTabFromQuery = false;
        if (tab && /^(intro|private|community|world|leagues)$/.test(tab)) {
            var btn = document.querySelector('.competition-tab-btn[data-tab="' + tab + '"]');
            if (btn) {
                btn.click();
                appliedTabFromQuery = true;
            }
        }
        var hashKey = window.location.hash.replace(/^#/, '');
        if (hashKey === 'baccarat-intro') {
            var introBtn = document.querySelector('.competition-tab-btn[data-tab="intro"]');
            if (introBtn && !introBtn.classList.contains('active')) {
                introBtn.click();
            }
        }
        if (hashKey) {
            scheduleScrollToCompetitionHash(hashKey);
        } else if (appliedTabFromQuery && tab) {
            scrollCompetitionPanelIntoView(tab);
        }
    })();

    // ICR — type tabs (World / Community / Private)
    document.querySelectorAll('.icr-type-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.icr-type-btn').forEach(function(b) { b.classList.remove('active'); });
            this.classList.add('active');
        });
    });

    // ICR — time filter tabs
    document.querySelectorAll('.icr-time-tabs').forEach(function(tabs) {
        tabs.querySelectorAll('.icr-time-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                tabs.querySelectorAll('.icr-time-btn').forEach(function(b) { b.classList.remove('active'); });
                this.classList.add('active');
            });
        });
    });

    // ICR — expand / collapse rows
    document.querySelectorAll('.icr-expand-btn').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            var breakdown = this.closest('.icr-row').nextElementSibling;
            if (!breakdown || !breakdown.classList.contains('icr-breakdown')) return;
            var isOpen = breakdown.style.display !== 'none';
            breakdown.style.display = isOpen ? 'none' : 'block';
            this.textContent = isOpen ? '+' : '×';
        });
    });

    // ── CPC: Create Private Challenge ──────────────────────────────────────────
    var cpcCreateBtn   = document.getElementById('cpc-create-btn');
    var cpcSearchInput = document.getElementById('cpc-username-search');
    var cpcSearchResults = document.getElementById('cpc-search-results');
    var cpcUsernameEl  = document.getElementById('cpc-username');
    var cpcInviteeIdEl = document.getElementById('cpc-invitee-user-id');
    var cpcSearchTimer = null;

    function cpcMsg(text, isError) {
        var msgEl = document.getElementById('cpc-form-msg');
        if (!msgEl) return;
        msgEl.textContent = text;
        msgEl.style.display = text ? 'block' : 'none';
        msgEl.style.color = isError ? '#f87b87' : '#b8972f';
    }

    function cpcHideSearchResults() {
        if (!cpcSearchResults) return;
        cpcSearchResults.hidden = true;
        cpcSearchResults.innerHTML = '';
    }

    function cpcSelectOpponent(id, username) {
        if (cpcInviteeIdEl) cpcInviteeIdEl.value = String(id);
        if (cpcUsernameEl) cpcUsernameEl.value = username;
        if (cpcSearchInput) {
            cpcSearchInput.value = username;
            cpcSearchInput.classList.add('cpc-search-selected');
        }
        cpcHideSearchResults();
        cpcMsg('Opponent selected: ' + username, false);
    }

    function cpcClearOpponentSelection() {
        if (cpcInviteeIdEl) cpcInviteeIdEl.value = '';
        if (cpcUsernameEl) cpcUsernameEl.value = '';
        if (cpcSearchInput) cpcSearchInput.classList.remove('cpc-search-selected');
    }

    function cpcRenderSearchResults(results) {
        if (!cpcSearchResults) return;
        cpcSearchResults.innerHTML = '';
        if (!results || !results.length) {
            var empty = document.createElement('div');
            empty.className = 'cpc-search-empty';
            empty.textContent = 'No matching username found.';
            cpcSearchResults.appendChild(empty);
            cpcSearchResults.hidden = false;
            return;
        }
        results.forEach(function (row) {
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'cpc-search-item';
            btn.textContent = row.username;
            btn.addEventListener('click', function () {
                cpcSelectOpponent(row.id, row.username);
            });
            cpcSearchResults.appendChild(btn);
        });
        cpcSearchResults.hidden = false;
    }

    if (cpcSearchInput) {
        cpcSearchInput.addEventListener('input', function () {
            var q = cpcSearchInput.value.trim();
            cpcClearOpponentSelection();
            if (cpcSearchTimer) clearTimeout(cpcSearchTimer);
            if (q.length < 1) {
                cpcHideSearchResults();
                return;
            }
            cpcSearchTimer = setTimeout(function () {
                var fd = new FormData();
                fd.append('action', 'ihq_search_challenge_usernames');
                fd.append('nonce', _challengeNonce);
                fd.append('q', q);
                fetch(_compAjaxUrl, { method: 'POST', body: fd })
                    .then(function (r) { return r.json(); })
                    .then(function (res) {
                        if (res.success) {
                            cpcRenderSearchResults(res.data.results || []);
                        } else {
                            cpcHideSearchResults();
                        }
                    })
                    .catch(function () {
                        cpcHideSearchResults();
                    });
            }, 200);
        });

        cpcSearchInput.addEventListener('focus', function () {
            var q = cpcSearchInput.value.trim();
            if (q.length >= 1 && cpcSearchResults && cpcSearchResults.children.length) {
                cpcSearchResults.hidden = false;
            }
        });

        document.addEventListener('click', function (e) {
            if (!cpcSearchResults || cpcSearchResults.hidden) return;
            var wrap = document.querySelector('.cpc-search-wrap');
            if (wrap && !wrap.contains(e.target)) {
                cpcHideSearchResults();
            }
        });
    }

    if (cpcCreateBtn) {
        cpcCreateBtn.addEventListener('click', function () {
            var inviteeId = (cpcInviteeIdEl && cpcInviteeIdEl.value) ? cpcInviteeIdEl.value : '';
            var dateVal   = (document.getElementById('cpc-date') || {}).value || '';
            var linkEl    = document.getElementById('cpc-share-link');

            if (!inviteeId) {
                cpcMsg('Search and select an opponent username first.', true);
                return;
            }
            if (!dateVal) {
                cpcMsg('Please choose a challenge date.', true);
                return;
            }

            cpcCreateBtn.disabled    = true;
            cpcCreateBtn.textContent = 'Creating…';
            cpcMsg('', false);

            var fd = new FormData();
            fd.append('action', 'ihq_create_private_challenge');
            fd.append('nonce', _challengeNonce);
            fd.append('invitee_user_id', inviteeId);
            fd.append('challenge_date', dateVal);

            fetch(_compAjaxUrl, { method: 'POST', body: fd })
                .then(function (r) { return r.json(); })
                .then(function (res) {
                    if (res.success) {
                        if (linkEl) {
                            linkEl.value = res.data.link;
                            linkEl.classList.remove('cpc-input-muted');
                            linkEl.select();
                        }
                        cpcMsg('Challenge created! Copy and share the link below.', false);
                    } else {
                        var msg = (res.data && res.data.message) ? res.data.message : 'Could not create challenge.';
                        cpcMsg(msg, true);
                    }
                })
                .catch(function (err) {
                    cpcMsg('Request failed: ' + (err.message || err), true);
                })
                .finally(function () {
                    cpcCreateBtn.disabled    = false;
                    cpcCreateBtn.textContent = 'Create Challenge Links';
                });
        });
    }

    function cpcReadShareLink() {
        var linkEl = document.getElementById('cpc-share-link');
        return linkEl && linkEl.value ? linkEl.value.trim() : '';
    }

    var cpcCopyBtn = document.getElementById('cpc-copy-link');
    if (cpcCopyBtn) {
        cpcCopyBtn.addEventListener('click', function () {
            var link = cpcReadShareLink();
            if (!link) {
                cpcMsg('Create a challenge link first.', true);
                return;
            }
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(link).then(function () {
                    cpcMsg('Link copied.', false);
                }).catch(function () {
                    var linkEl = document.getElementById('cpc-share-link');
                    if (linkEl) {
                        linkEl.select();
                    }
                    cpcMsg('Select and copy the link manually.', true);
                });
                return;
            }
            var linkEl = document.getElementById('cpc-share-link');
            if (linkEl) {
                linkEl.select();
            }
            cpcMsg('Select and copy the link manually.', false);
        });
    }

    var cpcShareBtn = document.getElementById('cpc-share-link-btn');
    if (cpcShareBtn) {
        cpcShareBtn.addEventListener('click', function () {
            var link = cpcReadShareLink();
            if (!link) {
                cpcMsg('Create a challenge link first.', true);
                return;
            }
            if (navigator.share) {
                navigator.share({ title: 'Private Challenge', url: link }).catch(function () {});
                return;
            }
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(link).then(function () {
                    cpcMsg('Link copied — paste it to share.', false);
                });
                return;
            }
            cpcMsg('Copy the shareable link below to share it.', false);
        });
    }
});
</script>


<?php 
get_template_part( 'template-parts/portal-scripts' );
get_footer();
