<?php
/**
 * Portal footer (in-page flow, full-width band; matches HQ Figma footer layer).
 *
 * @package influencer-hq
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! function_exists( 'portal_footer_resolve_url' ) ) {
    /**
     * Return permalink for first existing WordPress page matching slug candidates.
     *
     * @param string[] $slug_candidates Slugs tried in order.
     */
    function portal_footer_resolve_url( array $slug_candidates ): string {
        foreach ( $slug_candidates as $slug ) {
            if ( '' === $slug ) {
                continue;
            }
            $page = get_page_by_path( $slug );
            if ( $page instanceof WP_Post ) {
                return (string) get_permalink( $page );
            }
        }

        return '#';
    }
}

$asset_base = trailingslashit( get_template_directory_uri() ) . 'images/portal-footer/';

$link_terms      = portal_footer_resolve_url(
    [
        'terms-and-conditions',
        'terms-conditions',
        'terms',
    ]
);
$link_privacy = portal_footer_resolve_url(
    [
        'privacy-center',
        'privacy-policy',
        'privacy',
    ]
);
$link_help      = portal_footer_resolve_url(
    [
        'help-center',
        'portal-help',
        'help',
    ]
);
$link_contact = portal_footer_resolve_url(
    [
        'contact-us',
        'contact',
    ]
);
$link_concierge = esc_url( add_query_arg( 'open', 'concierge', home_url( '/portal/portal-home/' ) ) );

$portal_contact_agent_tip = __( 'For best support, please reach out to our Agent and ask any question regarding the Influencer HQ portal.', 'influencer-hq' );
$portal_contact_form_tip  = __( 'If you have specific inquires, please fill in the form.', 'influencer-hq' );

$headline_default = __( 'FOOTER PLACEHOLDER', 'influencer-hq' );
$headline         = apply_filters( 'portal_footer_headline', $headline_default );

$legal_default = __( '©2024 AE, Ltd. All rights reserved. AE Ltd. boasts 53 US and international utility patents distributed across China, Macau, the United States, Japan, Singapore, Malta, the Philippines, Korea, Vietnam, Australia, Gibraltar, Cambodia, Malaysia, Monaco, Taiwan, Israel, Spain, UK, France, and Hong Kong. Bet 5 Games is licensed by Avantage Entertainment USA to offer Influencer HQ and use the associated Red Diamond Logo. Influencer HQ holds Recognition status issued by the Malta Gaming Authority.', 'influencer-hq' );
$legal          = apply_filters( 'portal_footer_legal_text', $legal_default );

$social_urls = apply_filters(
    'portal_footer_social_urls',
    [
        'youtube'   => 'https://www.youtube.com/',
        'instagram' => 'https://www.instagram.com/',
        'facebook'  => 'https://www.facebook.com/',
    ]
);
?>

<div class="portal-footer" role="contentinfo">
    <div class="portal-footer-inner">
        <!-- <p class="portal-footer-headline"><?php echo esc_html( $headline ); ?></p> -->

        <!-- <ul class="portal-footer-social" aria-label="<?php esc_attr_e( 'Social media', 'influencer-hq' ); ?>">
            <li>
                <a class="portal-footer-social-link" href="<?php echo esc_url( $social_urls['youtube'] ?? '#' ); ?>" target="_blank" rel="noopener noreferrer" aria-label="YouTube">
                    <span class="portal-footer-social-thumb" aria-hidden="true"><img src="<?php echo esc_url( $asset_base . 'youtube.png' ); ?>" alt="" width="28" height="20" decoding="async" loading="lazy"></span>
                </a>
            </li>
            <li>
                <a class="portal-footer-social-link" href="<?php echo esc_url( $social_urls['facebook'] ?? '#' ); ?>" target="_blank" rel="noopener noreferrer" aria-label="Facebook">
                    <span class="portal-footer-social-thumb" aria-hidden="true"><img src="<?php echo esc_url( $asset_base . 'facebook.png' ); ?>" alt="" width="40" height="40" decoding="async" loading="lazy"></span>
                </a>
            </li>
            <li>
                <a class="portal-footer-social-link" href="<?php echo esc_url( $social_urls['instagram'] ?? '#' ); ?>" target="_blank" rel="noopener noreferrer" aria-label="Instagram">
                    <span class="portal-footer-social-thumb" aria-hidden="true"><img src="<?php echo esc_url( $asset_base . 'instagram.png' ); ?>" alt="" width="36" height="36" decoding="async" loading="lazy"></span>
                </a>
            </li>
        </ul> -->

        <nav class="portal-footer-nav" aria-label="<?php esc_attr_e( 'Footer links', 'influencer-hq' ); ?>">
            <a href="<?php echo esc_url( $link_terms ); ?>"><?php esc_html_e( 'Terms & Conditions', 'influencer-hq' ); ?></a>
            <a href="<?php echo esc_url( $link_privacy ); ?>"><?php esc_html_e( 'Privacy Center', 'influencer-hq' ); ?></a>
            <a href="https://influencerhq.co/portal/portal-home/?open=concierge"><?php esc_html_e( 'Help Center', 'influencer-hq' ); ?></a>
            <button type="button" class="portal-footer-contact-trigger" id="portalFooterContactBtn" aria-haspopup="dialog" aria-controls="portalContactModal">
                <?php esc_html_e( 'Contact Us', 'influencer-hq' ); ?>
            </button>
        </nav>

        <div class="portal-contact-modal-overlay" id="portalContactModal" hidden aria-hidden="true">
            <div class="portal-contact-modal" role="dialog" aria-modal="true" aria-labelledby="portalContactModalTitle">
                <button type="button" class="portal-contact-modal-close" id="portalContactModalClose" aria-label="<?php esc_attr_e( 'Close', 'influencer-hq' ); ?>">&#10005;</button>
                <h2 class="portal-contact-modal-title" id="portalContactModalTitle"><?php esc_html_e( 'Contact Us', 'influencer-hq' ); ?></h2>
                <ul class="portal-contact-modal-options">
                    <li class="portal-contact-modal-option-row">
                        <a class="portal-contact-modal-link" href="<?php echo esc_url( $link_concierge ); ?>">
                            <?php esc_html_e( 'Talk to Agent', 'influencer-hq' ); ?>
                        </a>
                        <span class="portal-contact-info" tabindex="0" aria-label="<?php echo esc_attr( $portal_contact_agent_tip ); ?>">
                            <span class="portal-contact-info-icon" aria-hidden="true">i</span>
                            <span class="portal-contact-info-tip" role="tooltip"><?php echo esc_html( $portal_contact_agent_tip ); ?></span>
                        </span>
                    </li>
                    <li class="portal-contact-modal-option-row">
                        <a class="portal-contact-modal-link" href="<?php echo esc_url( $link_contact ); ?>">
                            <?php esc_html_e( 'Send message', 'influencer-hq' ); ?>
                        </a>
                        <span class="portal-contact-info" tabindex="0" aria-label="<?php echo esc_attr( $portal_contact_form_tip ); ?>">
                            <span class="portal-contact-info-icon" aria-hidden="true">i</span>
                            <span class="portal-contact-info-tip" role="tooltip"><?php echo esc_html( $portal_contact_form_tip ); ?></span>
                        </span>
                    </li>
                </ul>
            </div>
        </div>

        <div class="portal-footer-logos">
            <!-- <img class="portal-footer-logo-img portal-footer-logo-img--cogra" src="<?php echo esc_url( $asset_base . 'cogra.png' ); ?>" alt="<?php esc_attr_e( 'eCOGRA', 'influencer-hq' ); ?>" width="283" height="60" decoding="async" loading="lazy"> -->
            <a href="https://apexregulations.com/">
            <img class="portal-footer-logo-img portal-footer-logo-img--mga" src="<?php echo esc_url( $asset_base . 'apex-logo.png' ); ?>" alt="<?php esc_attr_e( 'APEX Crypto License', 'influencer-hq' ); ?>" width="259" height="59" decoding="async" loading="lazy"></a>
            <!-- <img class="portal-footer-logo-img portal-footer-logo-img--praxis" src="<?php echo esc_url( $asset_base . 'praxis.png' ); ?>" alt="<?php esc_attr_e( 'Praxis', 'influencer-hq' ); ?>" width="247" height="59" decoding="async" loading="lazy"> -->
        </div>

        <hr class="portal-footer-rule">

        <p class="portal-footer-legal"><?php echo esc_html( $legal ); ?></p>
    </div>
</div>

<script>
(function () {
    var trigger = document.getElementById('portalFooterContactBtn');
    var modal = document.getElementById('portalContactModal');
    var closeBtn = document.getElementById('portalContactModalClose');
    if (!trigger || !modal) {
        return;
    }

    function openModal() {
        modal.hidden = false;
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('portal-contact-modal-open');
        if (closeBtn) {
            closeBtn.focus();
        }
    }

    function closeModal() {
        modal.hidden = true;
        modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('portal-contact-modal-open');
        trigger.focus();
    }

    trigger.addEventListener('click', function (e) {
        e.preventDefault();
        openModal();
    });

    if (closeBtn) {
        closeBtn.addEventListener('click', closeModal);
    }

    modal.addEventListener('click', function (e) {
        if (e.target === modal) {
            closeModal();
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && !modal.hidden) {
            closeModal();
        }
    });
})();
</script>
