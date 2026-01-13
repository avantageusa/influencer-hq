<?php
/**
 * The template for displaying the footer
 *
 * Contains the closing of the #content div and all content after.
 *
 * @link https://developer.wordpress.org/themes/basics/template-files/#template-partials
 *
 * @package Avantage_Baccarat
 */

?>

	 <!-- Footer-->
        <footer class="py-2 bg-dark">
            <div class="container px-4 text-center">
                <!-- <p style="max-width:100%;" class="m-0 text-white mb-3" style="max-width:100%;">Avantage Games</p> -->
                <img class="footer-hero-image" style="max-width:250px;padding: 10px 0px 15px 0px;" src="<?php echo get_template_directory_uri(); ?>/images/logo-hq.png" alt="Hero Logo" class="img-fluid">
                <p>2025 - All Rights Reserved</p>
                <!-- Footer Links -->
                <div class="footer-links">
                    <a href="/terms-and-conditions/" class="footer-link text-white me-4">Terms & Conditions</a>
                    <a href="/privacy-policy/" class="footer-link text-white">Privacy Policy</a>
                </div>
                
            </div>
        </footer>
</div><!-- #page -->

<?php wp_footer(); ?>

</body>
</html>
