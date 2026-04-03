<?php
/**
 * Template Name: Portal Help
 * Description: A custom template for displaying the help page.
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

            <!-- Help Content -->
            <div class="help-page-content">
                
                <!-- Help Header -->
                <div class="page-header text-center mb-4">
                    <div class="page-icon mb-3">
                        <svg width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path>
                            <line x1="12" y1="17" x2="12.01" y2="17"></line>
                        </svg>
                    </div>
                    <h1 class="page-title" style="color: #fff; font-size: 2.5rem; font-weight: 300; letter-spacing: 0.15em;">HELP</h1>
                </div>

                <!-- Help Content Box -->
                <div class="content-box">
                    <p style="text-align: center; color: rgba(255, 255, 255, 0.7); font-size: 1.1rem;">
                        Help & Support content coming soon...
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
