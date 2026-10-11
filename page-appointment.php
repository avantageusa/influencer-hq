<?php
/**
 * Template Name: Appointment link
 * Description: The page an AI Coach appointment link opens (PO-3109, FR-18). What it shows
 * (waiting, join, missed, expired, ended or invalid) comes from the server clock and the
 * stored appointment; see inc/aicoach-appointment-page.php. Create a page with the slug
 * `appointment` and this template on every environment: the link is built from that page.
 *
 * @package influencer-hq
 */
get_header();

get_template_part( 'template-parts/portal-styles' );

$theme_uri = get_template_directory_uri();

// Same Sami portrait as the AI Coach page, with the same cache-busting version on its URL.
$portrait_path = get_template_directory() . '/images/aicoach/coach-portrait.webp';
$portrait_url  = $theme_uri . '/images/aicoach/coach-portrait.webp';
$portrait_time = file_exists( $portrait_path ) ? filemtime( $portrait_path ) : false;
if ( $portrait_time ) {
	$portrait_url = add_query_arg( 'v', $portrait_time, $portrait_url );
}

$link_token = isset( $_GET[ IHQ_AICOACH_APPOINTMENT_TOKEN_PARAM ] ) ? sanitize_text_field( wp_unslash( $_GET[ IHQ_AICOACH_APPOINTMENT_TOKEN_PARAM ] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- a link, not a form; the token is verified by its signature.
$link_context = ihq_aicoach_appointment_page_context( $link_token, time() );
$link_view    = ihq_aicoach_appointment_page_view( $link_context['state'] );
$shows_time   = in_array( $link_context['state'], array( IHQ_AICOACH_APPOINTMENT_STATE_WAITING, IHQ_AICOACH_APPOINTMENT_STATE_JOIN ), true );
$shows_clock  = IHQ_AICOACH_APPOINTMENT_STATE_WAITING === $link_context['state'];
?>

    <main id="primary" class="site-main">

        <?php get_template_part( 'template-parts/portal-header' ); ?>

        <div class="container py-2 aicoach-page" id="portal-content" style="max-width: 1024px; padding-left: 20px; padding-right: 20px;">

            <section
                class="aicoachlink"
                id="aicoach-link"
                data-state="<?php echo esc_attr( $link_context['state'] ); ?>"
                <?php if ( null !== $link_context['secondsToStart'] ) : ?>
                    data-seconds-to-start="<?php echo esc_attr( (string) $link_context['secondsToStart'] ); ?>"
                <?php endif; ?>
                <?php if ( null !== $link_context['secondsToNextState'] ) : ?>
                    data-reload-in="<?php echo esc_attr( (string) $link_context['secondsToNextState'] ); ?>"
                <?php endif; ?>
                aria-label="<?php esc_attr_e( 'Your appointment with Sami', 'influencer-hq' ); ?>"
            >

                <div class="aicoachlink-avatar">
                    <img src="<?php echo esc_url( $portrait_url ); ?>" alt="<?php esc_attr_e( 'Sami', 'influencer-hq' ); ?>" width="452" height="452" fetchpriority="high">
                </div>

                <h1 class="aicoachlink-title"><?php echo esc_html( $link_view['title'] ); ?></h1>

                <?php if ( $shows_time ) : ?>
                    <p class="aicoachlink-time">
                        <time datetime="<?php echo esc_attr( gmdate( 'c', (int) $link_context['startsAt'] ) ); ?>">
                            <?php echo esc_html( ihq_aicoach_appointment_display_time( (int) $link_context['startsAt'], $link_context['timeZone'] ) ); ?>
                        </time>
                    </p>
                <?php endif; ?>

                <?php if ( '' !== $link_view['message'] ) : ?>
                    <p class="aicoachlink-message"><?php echo esc_html( $link_view['message'] ); ?></p>
                <?php endif; ?>

                <?php if ( $shows_clock ) : ?>
                    <p class="aicoachlink-countdown" id="aicoach-link-countdown" role="timer" aria-live="off"></p>
                <?php endif; ?>

                <div class="aicoachlink-actions">
                    <?php foreach ( $link_view['actions'] as $link_action ) : ?>
                        <?php if ( IHQ_AICOACH_APPOINTMENT_ACTION_BOOK === $link_action['type'] ) : ?>
                            <a class="aicoachlink-button" href="<?php echo esc_url( ihq_aicoach_appointment_coach_page_url() ); ?>"><?php echo esc_html( $link_action['label'] ); ?></a>
                        <?php else : ?>
                            <form method="post" action="<?php echo esc_url( get_permalink() ); ?>">
                                <input type="hidden" name="<?php echo esc_attr( IHQ_AICOACH_APPOINTMENT_TOKEN_PARAM ); ?>" value="<?php echo esc_attr( $link_token ); ?>">
                                <input type="hidden" name="<?php echo esc_attr( IHQ_AICOACH_APPOINTMENT_JOIN_FIELD ); ?>" value="1">
                                <?php wp_nonce_field( IHQ_AICOACH_APPOINTMENT_JOIN_NONCE_ACTION ); ?>
                                <button type="submit" class="aicoachlink-button"><?php echo esc_html( $link_action['label'] ); ?></button>
                            </form>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>

            </section>

        </div>

        <?php get_template_part( 'template-parts/portal-footer' ); ?>
    </main><!-- #primary -->

<style>
    /* The look of the AI Coach page (black, the Sami portrait, white text, the green
    button), kept here because that page's own styles live inside its template. No design
    exists for these pages yet (PO-3109); replace this when one does. */
    .aicoachlink {
        max-width: 560px;
        margin: 0 auto 56px;
        padding: 0 0 40px;
        color: #fff;
        font-family: 'Be Vietnam Pro', sans-serif;
        text-align: center;
    }

    .aicoachlink-avatar {
        width: min(220px, 55vw);
        aspect-ratio: 1;
        margin: 8px auto 28px;
        border-radius: 50%;
        overflow: hidden;
        background: #12131a;
    }

    .aicoachlink-avatar img {
        display: block;
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .aicoachlink-title {
        margin: 0 0 14px;
        font-weight: 700;
        font-size: clamp(1.7rem, 7vw, 2.2rem);
        line-height: 1.15;
        color: #fff;
    }

    .aicoachlink-time {
        margin: 0 0 14px;
        font-weight: 600;
        font-size: clamp(1.05rem, 4.4vw, 1.3rem);
        line-height: 1.35;
    }

    .aicoachlink-message {
        margin: 0 0 14px;
        font-weight: 500;
        font-size: clamp(1rem, 4vw, 1.2rem);
        line-height: 1.4;
    }

    .aicoachlink-countdown {
        margin: 0 0 24px;
        font-weight: 700;
        font-size: clamp(2rem, 9vw, 2.8rem);
        font-variant-numeric: tabular-nums;
        color: #fdd65b;
    }

    .aicoachlink-actions {
        display: grid;
        gap: 14px;
        margin-top: 20px;
    }

    .aicoachlink-actions form {
        margin: 0;
    }

    .aicoachlink-button {
        display: block;
        width: 100%;
        box-sizing: border-box;
        padding: 14px 12px;
        border: none;
        border-radius: 2px;
        background: #148942;
        color: #fff;
        font-family: inherit;
        font-weight: 600;
        font-size: clamp(1.3rem, 6vw, 1.7rem);
        line-height: 1.2;
        text-align: center;
        text-decoration: none;
        cursor: pointer;
    }

    .aicoachlink-button:focus-visible {
        outline: 3px solid #fdd65b;
        outline-offset: 2px;
    }

    /* Same shell as the AI Coach page: no menus or footer around a one-purpose page. */
    body.page-template-page-appointment-php .hamburger-menu,
    body.page-template-page-appointment-php .header-help-btn,
    body.page-template-page-appointment-php .header-login-link,
    body.page-template-page-appointment-php .header-logout-btn,
    body.page-template-page-appointment-php .go-to-game-btn,
    body.page-template-page-appointment-php .sticky-nav,
    body.page-template-page-appointment-php .portal-footer,
    body.page-template-page-appointment-php footer.bg-dark {
        display: none !important;
    }
</style>

<?php
get_template_part( 'template-parts/portal-scripts' );
get_footer();
