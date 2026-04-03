<?php
/**
 * Template Name: Portal Rankings
 * Description: A custom template for displaying the rankings leaderboard.
 *
 * @package Avantage_Baccarat
 */
get_header();

// Load styles before content to prevent FOUC
get_template_part( 'template-parts/portal-styles' );
?>

    <main id="primary" class="site-main">
        
        <?php get_template_part( 'template-parts/portal-header' ); ?>
        
        <div class="container py-2" style="max-width: 1024px; padding-left: 20px; padding-right: 20px;">

            <!-- Rankings Content -->
            <div class="rankings-page-content">
                
                <!-- Rankings Header -->
                <div class="page-header text-center mb-4">
                    <div class="page-icon mb-3">
                        <svg width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2">
                            <path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6"></path>
                            <path d="M18 9h1.5a2.5 2.5 0 0 0 0-5H18"></path>
                            <path d="M4 22h16"></path>
                            <path d="M10 14.66V17c0 .55-.47.98-.97 1.21C7.85 18.75 7 20.24 7 22"></path>
                            <path d="M14 14.66V17c0 .55.47.98.97 1.21C16.15 18.75 17 20.24 17 22"></path>
                            <path d="M18 2H6v7a6 6 0 0 0 12 0V2Z"></path>
                        </svg>
                    </div>
                    <h1 class="page-title" style="color: #fff; font-size: 2.5rem; font-weight: 300; letter-spacing: 0.15em;">RANKING</h1>
                </div>

                <!-- Rankings Filter Box -->
                <div class="rankings-box">
                    <h3 class="rankings-subtitle">RANKINGS</h3>
                    
                    <!-- Location Filters -->
                    <div class="ranking-filters mb-3">
                        <button class="filter-btn active">World</button>
                        <button class="filter-btn">Continent</button>
                        <button class="filter-btn">Country</button>
                        <button class="filter-btn">City</button>
                    </div>

                    <!-- Category Filters -->
                    <div class="ranking-filters mb-3">
                        <button class="filter-btn">Movie Stars</button>
                        <button class="filter-btn">Music Artists</button>
                        <button class="filter-btn">Sports Icons</button>
                    </div>

                    <!-- League Selection -->
                    <div class="league-selector mb-3">
                        <button class="league-btn">International League</button>
                    </div>

                    <!-- Time Period Filters -->
                    <div class="ranking-filters mb-4">
                        <button class="filter-btn">Week</button>
                        <button class="filter-btn">30 Days</button>
                        <button class="filter-btn">Lifetime</button>
                    </div>

                    <!-- Your Current Rank -->
                    <div class="current-rank-section mb-3">
                        <h4 class="section-label">YOUR CURRENT RANK</h4>
                        <div class="rank-display">
                            <span class="rank-location">Singapore</span>
                            <span class="rank-number">#37</span>
                        </div>
                    </div>

                    <!-- Movement -->
                    <div class="movement-section mb-4">
                        <h4 class="section-label">MOVEMENT</h4>
                        <div class="movement-display">
                            <span class="movement-label">Since Last Period</span>
                            <span class="movement-value positive">+3</span>
                        </div>
                    </div>

                    <!-- Leaderboard Table -->
                    <div class="leaderboard-section">
                        <h4 class="section-label">LEADERBOARD TABLE</h4>
                        <table class="leaderboard-table">
                            <thead>
                                <tr>
                                    <th>RANK</th>
                                    <th>INFLUENCER NAME</th>
                                    <th>SCORE</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>1</td>
                                    <td>Influencer A</td>
                                    <td>9,842</td>
                                </tr>
                                <tr>
                                    <td>2</td>
                                    <td>Influencer B</td>
                                    <td>9,610</td>
                                </tr>
                                <tr>
                                    <td>3</td>
                                    <td>Influencer C</td>
                                    <td>9,588</td>
                                </tr>
                                <tr class="user-row">
                                    <td>37</td>
                                    <td>You</td>
                                    <td>7,214</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

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

<style>
    .rankings-box {
        background: #000;
        border: 2px solid rgba(255, 255, 255, 0.3);
        border-radius: 12px;
        padding: 30px;
        max-width: 600px;
        margin: 0 auto;
    }

    .rankings-subtitle {
        color: #fff;
        font-size: 1.2rem;
        font-weight: 600;
        margin-bottom: 20px;
        letter-spacing: 0.1em;
    }

    .ranking-filters {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }

    .filter-btn {
        background: transparent;
        border: 1px solid rgba(255, 255, 255, 0.4);
        color: #fff;
        padding: 8px 16px;
        border-radius: 6px;
        font-size: 0.9rem;
        cursor: pointer;
        transition: all 0.2s;
    }

    .filter-btn:hover,
    .filter-btn.active {
        background: rgba(255, 255, 255, 0.1);
        border-color: #fff;
    }

    .league-selector {
        width: 100%;
    }

    .league-btn {
        width: 100%;
        background: transparent;
        border: 1px solid rgba(255, 255, 255, 0.4);
        color: #fff;
        padding: 12px;
        border-radius: 6px;
        font-size: 0.95rem;
        cursor: pointer;
        transition: all 0.2s;
    }

    .league-btn:hover {
        background: rgba(255, 255, 255, 0.05);
    }

    .section-label {
        color: rgba(255, 255, 255, 0.7);
        font-size: 0.8rem;
        font-weight: 600;
        letter-spacing: 0.1em;
        margin-bottom: 10px;
    }

    .rank-display,
    .movement-display {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px 0;
    }

    .rank-location,
    .movement-label {
        color: #fff;
        font-size: 1rem;
    }

    .rank-number {
        color: #fff;
        font-size: 1.5rem;
        font-weight: 600;
    }

    .movement-value {
        font-size: 1.3rem;
        font-weight: 600;
    }

    .movement-value.positive {
        color: #4ade80;
    }

    .leaderboard-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 10px;
    }

    .leaderboard-table thead th {
        color: rgba(255, 255, 255, 0.6);
        font-size: 0.75rem;
        font-weight: 600;
        text-align: left;
        padding: 10px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        letter-spacing: 0.05em;
    }

    .leaderboard-table tbody td {
        color: #fff;
        padding: 12px 10px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }

    .leaderboard-table tbody tr:last-child td {
        border-bottom: none;
    }

    .leaderboard-table tbody tr.user-row {
        background: rgba(59, 159, 255, 0.1);
    }

    .leaderboard-table tbody tr.user-row td {
        font-weight: 600;
    }
</style>

<?php 
get_template_part( 'template-parts/portal-scripts' );
get_footer();
