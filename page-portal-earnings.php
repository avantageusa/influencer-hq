<?php
/**
 * Template Name: Portal Earnings
 * Description: A custom template for displaying earnings information.
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

            <!-- Earnings Content -->
            <div class="earnings-page-content">
                
                <!-- Earnings Header -->
                <div class="page-header text-center mb-4">
                    <div class="page-icon mb-3">
                        <svg width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2">
                            <line x1="12" y1="20" x2="12" y2="10"></line>
                            <line x1="18" y1="20" x2="18" y2="4"></line>
                            <line x1="6" y1="20" x2="6" y2="16"></line>
                        </svg>
                    </div>
                    <h1 class="page-title" style="color: #fff; font-size: 2.5rem; font-weight: 300; letter-spacing: 0.15em;">EARNINGS</h1>
                </div>

                <!-- Earnings Box -->
                <div class="earnings-box">
                    
                    <!-- Time Period Filters -->
                    <div class="earnings-filters mb-3">
                        <button class="earnings-filter-btn">Week</button>
                        <button class="earnings-filter-btn">1 Month</button>
                        <button class="earnings-filter-btn">6 months</button>
                        <button class="earnings-filter-btn">1 Year</button>
                        <button class="earnings-filter-btn">All</button>
                    </div>

                    <!-- Referral Level Filters -->
                    <div class="earnings-filters mb-3">
                        <button class="earnings-filter-btn active">Direct Referral</button>
                        <button class="earnings-filter-btn">Level 2 Referral</button>
                        <button class="earnings-filter-btn">Level 3 Referral</button>
                    </div>

                    <!-- Source Filters -->
                    <div class="earnings-filters mb-4">
                        <button class="earnings-filter-btn">Live Appearance</button>
                        <button class="earnings-filter-btn">Live Stream</button>
                        <button class="earnings-filter-btn">All</button>
                    </div>

                    <!-- Earnings Section -->
                    <div class="earnings-section mb-4">
                        <h3 class="earnings-subtitle">Earnings</h3>
                        <div class="earnings-row">
                            <span class="earnings-label">Total Play</span>
                            <span class="earnings-value">1,245.38</span>
                        </div>
                        <div class="earnings-row">
                            <span class="earnings-label">Shares Earned</span>
                            <span class="earnings-value">182.75</span>
                        </div>
                    </div>

                    <!-- Share Price Section -->
                    <div class="share-price-section">
                        <h3 class="earnings-subtitle">Share Price</h3>
                        <div class="earnings-row mb-3">
                            <span class="earnings-label">Total Play</span>
                            <span class="earnings-value">1,245.38</span>
                        </div>

                        <!-- Chart Placeholder -->
                        <div class="chart-placeholder">
                            <p style="text-align: center; color: rgba(255, 255, 255, 0.5); padding: 60px 20px;">
                                [insert chart]
                            </p>
                        </div>
                    </div>

                </div>

            </div>
            
        </div>
        
        <!-- Fixed Footer Links -->
        <?php get_template_part( 'template-parts/portal-footer' ); ?>
    </main><!-- #main -->

<style>
    .earnings-box {
        background: #000;
        border: 2px solid rgba(255, 255, 255, 0.3);
        border-radius: 12px;
        padding: 30px;
        max-width: 700px;
        margin: 0 auto;
    }

    .earnings-filters {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }

    .earnings-filter-btn {
        background: transparent;
        border: 1px solid rgba(255, 255, 255, 0.4);
        color: #fff;
        padding: 8px 16px;
        border-radius: 6px;
        font-size: 0.9rem;
        cursor: pointer;
        transition: all 0.2s;
    }

    .earnings-filter-btn:hover,
    .earnings-filter-btn.active {
        background: rgba(255, 255, 255, 0.1);
        border-color: #fff;
    }

    .earnings-subtitle {
        color: #fff;
        font-size: 1.1rem;
        font-weight: 600;
        margin-bottom: 15px;
        padding-bottom: 10px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.2);
    }

    .earnings-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px 0;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }

    .earnings-row:last-child {
        border-bottom: none;
    }

    .earnings-label {
        color: rgba(255, 255, 255, 0.7);
        font-size: 0.95rem;
    }

    .earnings-value {
        color: #fff;
        font-size: 1.1rem;
        font-weight: 600;
    }

    .chart-placeholder {
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 8px;
        min-height: 200px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
</style>

<?php 
get_template_part( 'template-parts/portal-scripts' );
get_footer();
