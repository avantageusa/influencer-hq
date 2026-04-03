<?php
/**
 * Template Name: Portal Network
 * Description: A custom template for displaying the network page.
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

            <!-- Network Content -->
            <div class="network-page-content">
                
                <!-- Network Header -->
                <div class="page-header text-center mb-4">
                    <div class="page-icon mb-3">
                        <svg width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                    </div>
                    <h1 class="page-title" style="color: #fff; font-size: 2.5rem; font-weight: 300; letter-spacing: 0.15em;">NETWORK</h1>
                </div>

                <!-- Network Content Box -->
                <div class="content-box">
                    <p style="text-align: center; color: rgba(255, 255, 255, 0.7); font-size: 1.1rem;">
                        Network content coming soon...
                    </p>
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
    .content-box {
        background: #000;
        border: 2px solid rgba(255, 255, 255, 0.3);
        border-radius: 12px;
        padding: 60px 30px;
        max-width: 700px;
        margin: 0 auto;
    }
</style>

<?php 
get_template_part( 'template-parts/portal-scripts' );
get_footer();
