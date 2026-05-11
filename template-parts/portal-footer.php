<?php
/**
 * Portal footer (in-page flow, full-width band; matches HQ Figma footer layer).
 *
 * @package Avantage_Baccarat
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

$headline_default = __( 'FOOTER PLACEHOLDER', 'avantage-baccarat' );
$headline         = apply_filters( 'portal_footer_headline', $headline_default );

$legal_default = __( '©2024 AE, Ltd. All rights reserved. AE Ltd. boasts 53 US and international utility patents distributed across China, Macau, the United States, Japan, Singapore, Malta, the Philippines, Korea, Vietnam, Australia, Gibraltar, Cambodia, Malaysia, Monaco, Taiwan, Israel, Spain, UK, France, and Hong Kong. Bet 5 Games is licensed by Avantage Entertainment USA to offer Avantage Baccarat and use the associated Red Diamond Logo. Avantage Baccarat holds Recognition status issued by the Malta Gaming Authority.', 'avantage-baccarat' );
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
        <p class="portal-footer-headline"><?php echo esc_html( $headline ); ?></p>

        <ul class="portal-footer-social" aria-label="<?php esc_attr_e( 'Social media', 'avantage-baccarat' ); ?>">
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
        </ul>

        <nav class="portal-footer-nav" aria-label="<?php esc_attr_e( 'Footer links', 'avantage-baccarat' ); ?>">
            <a href="<?php echo esc_url( $link_terms ); ?>"><?php esc_html_e( 'Terms & Conditions', 'avantage-baccarat' ); ?></a>
            <a href="<?php echo esc_url( $link_privacy ); ?>"><?php esc_html_e( 'Privacy Center', 'avantage-baccarat' ); ?></a>
            <a href="<?php echo esc_url( $link_help ); ?>"><?php esc_html_e( 'Help Center', 'avantage-baccarat' ); ?></a>
            <a href="<?php echo esc_url( $link_contact ); ?>"><?php esc_html_e( 'Contact Us', 'avantage-baccarat' ); ?></a>
        </nav>

        <div class="portal-footer-logos">
            <img class="portal-footer-logo-img portal-footer-logo-img--cogra" src="<?php echo esc_url( $asset_base . 'cogra.png' ); ?>" alt="<?php esc_attr_e( 'eCOGRA', 'avantage-baccarat' ); ?>" width="283" height="60" decoding="async" loading="lazy">
            <img class="portal-footer-logo-img portal-footer-logo-img--mga" src="<?php echo esc_url( $asset_base . 'mga.png' ); ?>" alt="<?php esc_attr_e( 'Malta Gaming Authority', 'avantage-baccarat' ); ?>" width="259" height="59" decoding="async" loading="lazy">
            <img class="portal-footer-logo-img portal-footer-logo-img--praxis" src="<?php echo esc_url( $asset_base . 'praxis.png' ); ?>" alt="<?php esc_attr_e( 'Praxis', 'avantage-baccarat' ); ?>" width="247" height="59" decoding="async" loading="lazy">
        </div>

        <hr class="portal-footer-rule">

        <p class="portal-footer-legal"><?php echo esc_html( $legal ); ?></p>
    </div>
</div>
