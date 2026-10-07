<?php
/**
 * Template Name: Portal Post
 * Description: Static markup for the portal Post page.
 *
 * @package influencer-hq
 */
get_header();

get_template_part( 'template-parts/portal-styles' );

$post_theme_uri = get_template_directory_uri();
?>

    <main id="primary" class="site-main">

        <?php get_template_part( 'template-parts/portal-header' ); ?>

        <div class="container py-2 the-gradient" id="portal-content" style="max-width: 1024px; padding-left: 20px; padding-right: 20px;">

            <div class="post-page-content">

                <div class="equity-header">
                    <div class="equity-header-top">
                        <img src="<?php echo esc_url( $post_theme_uri ); ?>/images/post-arrow.png" alt="" class="equity-icon">
                        <h1 class="equity-title"><?php esc_html_e( 'Post', 'influencer-hq' ); ?></h1>
                    </div>
                </div>

                <p class="post-intro"><?php esc_html_e( 'Here are the latest Social Media posts I’ve created for you to share with your followers.', 'influencer-hq' ); ?></p>

                <section class="post-panel" aria-label="<?php esc_attr_e( 'Facebook', 'influencer-hq' ); ?>">
                    <div class="post-panel-draft"><?php esc_html_e( 'Facebook Draft Post goes here', 'influencer-hq' ); ?></div>
                    <div class="post-panel-meta">
                        <span class="post-panel-date">08-31-2026</span>
                        <label class="post-panel-posting">
                            <input type="checkbox" class="post-panel-check" tabindex="-1">
                            <span><?php esc_html_e( 'Posting Now', 'influencer-hq' ); ?></span>
                        </label>
                        <div class="post-panel-actions">
                            <button type="button" class="post-panel-copy" tabindex="-1"><?php esc_html_e( 'Copy', 'influencer-hq' ); ?></button>
                            <button type="button" class="post-panel-share" tabindex="-1" aria-label="<?php esc_attr_e( 'Share', 'influencer-hq' ); ?>">
                                <img src="<?php echo esc_url( $post_theme_uri ); ?>/images/post-share.png" alt="" width="48" height="48">
                            </button>
                        </div>
                    </div>
                </section>

                <section class="post-panel" aria-label="<?php esc_attr_e( 'LINE', 'influencer-hq' ); ?>">
                    <div class="post-panel-draft"><?php esc_html_e( 'LINE draft post goes here', 'influencer-hq' ); ?></div>
                    <div class="post-panel-meta">
                        <span class="post-panel-date">08-31-2026</span>
                        <label class="post-panel-posting">
                            <input type="checkbox" class="post-panel-check" tabindex="-1" checked>
                            <span><?php esc_html_e( 'Posting Now', 'influencer-hq' ); ?></span>
                        </label>
                        <div class="post-panel-actions">
                            <button type="button" class="post-panel-copy" tabindex="-1"><?php esc_html_e( 'Copy', 'influencer-hq' ); ?></button>
                            <button type="button" class="post-panel-share" tabindex="-1" aria-label="<?php esc_attr_e( 'Share', 'influencer-hq' ); ?>">
                                <img src="<?php echo esc_url( $post_theme_uri ); ?>/images/post-share.png" alt="" width="48" height="48">
                            </button>
                        </div>
                    </div>
                </section>

                <section class="post-panel" aria-label="<?php esc_attr_e( 'WeChat', 'influencer-hq' ); ?>">
                    <div class="post-panel-draft"><?php esc_html_e( 'WeChat draft post goes here', 'influencer-hq' ); ?></div>
                    <div class="post-panel-meta">
                        <span class="post-panel-date">08-31-2026</span>
                        <label class="post-panel-posting">
                            <input type="checkbox" class="post-panel-check" tabindex="-1">
                            <span><?php esc_html_e( 'Posting Now', 'influencer-hq' ); ?></span>
                        </label>
                        <div class="post-panel-actions">
                            <button type="button" class="post-panel-copy" tabindex="-1"><?php esc_html_e( 'Copy', 'influencer-hq' ); ?></button>
                            <button type="button" class="post-panel-share" tabindex="-1" aria-label="<?php esc_attr_e( 'Share', 'influencer-hq' ); ?>">
                                <img src="<?php echo esc_url( $post_theme_uri ); ?>/images/post-share.png" alt="" width="48" height="48">
                            </button>
                        </div>
                    </div>
                </section>

            </div>

        </div>

        <?php get_template_part( 'template-parts/portal-footer' ); ?>
    </main><!-- #main -->

<?php
get_template_part( 'template-parts/portal-scripts' );
get_footer();
