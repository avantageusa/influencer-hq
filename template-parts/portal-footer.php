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

$images_base = trailingslashit( get_template_directory_uri() ) . 'images/';
$asset_base  = trailingslashit( get_template_directory_uri() ) . 'images/portal-footer/';

$link_terms = portal_footer_resolve_url(
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
$link_contact = portal_footer_resolve_url(
	[
		'contact-us',
		'contact',
	]
);
$link_concierge = esc_url( add_query_arg( 'open', 'concierge', home_url( '/portal/portal-home/' ) ) );

$portal_contact_agent_tip = __( 'For best support, please reach out to our Agent and ask any question regarding the Influencer HQ portal.', 'influencer-hq' );
$portal_contact_form_tip  = __( 'If you have specific inquires, please fill in the form.', 'influencer-hq' );

$legal_default = __( 'InfluencerHQ, LLC, incorporated in Nevis and InfluencerHQ (BVI), LTD, incorporated in the British Virgin Islands', 'influencer-hq' );
$legal         = apply_filters( 'portal_footer_legal_text', $legal_default );
?>

<div class="portal-footer" role="contentinfo">
	<div class="portal-footer-inner">
		<div class="portal-footer-brand-row" aria-label="<?php esc_attr_e( 'Partner logos', 'influencer-hq' ); ?>">
			<div class="portal-footer-brand portal-footer-brand--ih">
				<img
					class="portal-footer-brand-img"
					src="<?php echo esc_url( $images_base . 'ih-logo.png' ); ?>"
					alt="<?php esc_attr_e( 'Influencer HQ', 'influencer-hq' ); ?>"
					width="120"
					height="48"
					decoding="async"
					loading="lazy"
				>
			</div>
			<div class="portal-footer-brand portal-footer-brand--tokeny">
				<img
					class="portal-footer-brand-img portal-footer-brand-img--tokeny"
					src="<?php echo esc_url( $images_base . 'logo-tokeny.png' ); ?>"
					alt=""
					width="72"
					height="56"
					decoding="async"
					loading="lazy"
				>
				<span class="portal-footer-brand-label">tokeny</span>
			</div>
			<div class="portal-footer-brand portal-footer-brand--apex">
				<a href="https://apexregulations.com/" target="_blank" rel="noopener noreferrer">
					<img
						class="portal-footer-brand-img portal-footer-brand-img--apex"
						src="<?php echo esc_url( $asset_base . 'apex-logo.png' ); ?>"
						alt="<?php esc_attr_e( 'APEX', 'influencer-hq' ); ?>"
						width="120"
						height="59"
						decoding="async"
						loading="lazy"
					>
				</a>
			</div>
		</div>

		<div
			class="portal-footer-disclosure"
			id="portalFooterDisclosure"
			aria-hidden="true"
		>
			<div class="portal-footer-disclosure-panel">
			<div class="portal-footer-disclosure-inner">
				<ul class="portal-footer-disclosure-list">
					<li><?php esc_html_e( 'Regulated by the BVI Financial Services Commission (FSC)', 'influencer-hq' ); ?></li>
					<li><?php esc_html_e( 'BVI Business Companies Act, 2004 (as amended)', 'influencer-hq' ); ?></li>
					<li><?php esc_html_e( 'Virtual Assets Service Providers Act, 2022', 'influencer-hq' ); ?></li>
					<li><?php esc_html_e( 'Securities and Investment Business Act, 2010', 'influencer-hq' ); ?></li>
					<li><?php esc_html_e( 'Beneficial Ownership Secure Search System Act, 2017', 'influencer-hq' ); ?></li>
					<li><?php esc_html_e( 'Economic Substance (Companies and Limited Partnerships) Act, 2018', 'influencer-hq' ); ?></li>
				</ul>

				<p class="portal-footer-disclosure-heading"><?php esc_html_e( 'Apex Group Ltd.', 'influencer-hq' ); ?></p>
				<p class="portal-footer-disclosure-subheading"><?php esc_html_e( 'Established 2003, Bermuda', 'influencer-hq' ); ?></p>

				<ul class="portal-footer-disclosure-list">
					<li><?php esc_html_e( 'Operations in 50+ countries', 'influencer-hq' ); ?></li>
					<li><?php esc_html_e( '$3.4 trillion in assets under administration', 'influencer-hq' ); ?></li>
					<li><?php esc_html_e( 'Leading global financial services provider serving asset managers and capital markets', 'influencer-hq' ); ?></li>
					<li><?php esc_html_e( 'Tokeny (Luxembourg) now part of Apex Group, provides the technology rail for issuing, managing, transferring, and administering tokenized securities.', 'influencer-hq' ); ?></li>
				</ul>

				<p class="portal-footer-disclosure-section-title"><?php esc_html_e( 'PRIMARY REGULATORS:', 'influencer-hq' ); ?></p>
				<ul class="portal-footer-disclosure-list">
					<li><?php esc_html_e( 'Bermuda Monetary Authority (BMA)', 'influencer-hq' ); ?></li>
					<li><?php esc_html_e( 'Financial Conduct Authority (FCA), United Kingdom', 'influencer-hq' ); ?></li>
					<li><?php esc_html_e( 'Commission de Surveillance du Secteur Financier (CSSF), Luxembourg', 'influencer-hq' ); ?></li>
					<li><?php esc_html_e( 'Cayman Islands Monetary Authority (CIMA)', 'influencer-hq' ); ?></li>
					<li><?php esc_html_e( 'Monetary Authority of Singapore (MAS)', 'influencer-hq' ); ?></li>
					<li><?php esc_html_e( 'Central Bank of Ireland (CBI)', 'influencer-hq' ); ?></li>
					<li><?php esc_html_e( 'Securities and Futures Commission (SFC), Hong Kong', 'influencer-hq' ); ?></li>
					<li><?php esc_html_e( 'Additional regulators in each of the 50+ jurisdictions Apex operates in', 'influencer-hq' ); ?></li>
				</ul>

				<p class="portal-footer-disclosure-section-title"><?php esc_html_e( 'BERMUDA REGULATORY FRAMEWORK:', 'influencer-hq' ); ?></p>
				<ul class="portal-footer-disclosure-list">
					<li><?php esc_html_e( 'Bermuda Companies Act 1981', 'influencer-hq' ); ?></li>
					<li><?php esc_html_e( 'Bermuda Investment Business Act 2003 business', 'influencer-hq' ); ?></li>
					<li><?php esc_html_e( 'Bermuda Investment Funds Act 2006', 'influencer-hq' ); ?></li>
					<li><?php esc_html_e( 'Bermuda Digital Asset Business Act 2018', 'influencer-hq' ); ?></li>
					<li><?php esc_html_e( 'Bermuda AML/ATF Regulations', 'influencer-hq' ); ?></li>
				</ul>
			</div>
			</div>
		</div>

		<button
			type="button"
			class="portal-footer-disclosure-toggle"
			id="portalFooterDisclosureToggle"
			aria-expanded="false"
			aria-controls="portalFooterDisclosure"
			aria-label="<?php esc_attr_e( 'Show regulatory information', 'influencer-hq' ); ?>"
		>
			<span class="portal-footer-chevron" aria-hidden="true"></span>
		</button>

		<nav class="portal-footer-nav" aria-label="<?php esc_attr_e( 'Footer links', 'influencer-hq' ); ?>">
			<a href="<?php echo esc_url( $link_terms ); ?>"><?php esc_html_e( 'Terms & Conditions', 'influencer-hq' ); ?></a>
			<a href="<?php echo esc_url( $link_privacy ); ?>"><?php esc_html_e( 'Privacy Center', 'influencer-hq' ); ?></a>
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

		<hr class="portal-footer-rule">

		<p class="portal-footer-legal"><?php echo esc_html( $legal ); ?></p>
	</div>
</div>

<script>
(function () {
	var disclosure = document.getElementById('portalFooterDisclosure');
	var disclosureToggle = document.getElementById('portalFooterDisclosureToggle');
	if (disclosure && disclosureToggle) {
		var showLabel = <?php echo wp_json_encode( __( 'Show regulatory information', 'influencer-hq' ) ); ?>;
		var hideLabel = <?php echo wp_json_encode( __( 'Hide regulatory information', 'influencer-hq' ) ); ?>;

		function setDisclosureExpanded(isExpanded) {
			disclosure.classList.toggle('is-expanded', isExpanded);
			disclosure.setAttribute('aria-hidden', isExpanded ? 'false' : 'true');
			disclosureToggle.setAttribute('aria-expanded', isExpanded ? 'true' : 'false');
			disclosureToggle.setAttribute('aria-label', isExpanded ? hideLabel : showLabel);
			disclosureToggle.classList.toggle('is-expanded', isExpanded);
		}

		disclosureToggle.addEventListener('click', function () {
			var isExpanded = disclosureToggle.getAttribute('aria-expanded') === 'true';
			setDisclosureExpanded(!isExpanded);
		});
	}

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
