<?php
/**
 * LINE enrollment (Phase 1) — consent + Add Friend Official Account.
 *
 * Optional wp-config.php defines:
 *   define( 'IHQ_LINE_ADD_FRIEND_URL', 'https://line.me/R/ti/p/@your_oa' );
 *   define( 'IHQ_LINE_TERMS_VERSION', '2026-07-21' );
 *
 * The Add Friend URL must be the same LINE Official Account connected to the
 * Braze "Influencer - LINE" subscription group. native_line_id (U...) is filled
 * by Phase 2 LINE Login / webhook match — Phase 1 records consent only.
 *
 * @package influencer-hq
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Cookie / intent marker when LINE is selected with consent (not a LINE user ID). */
const IHQ_LINE_CONSENT_COMM_MARKER = 'consented';

/** Default consent audit source for registration modal. */
const IHQ_LINE_CONSENT_SOURCE_DEFAULT = 'registration_modal';

/** Fallback terms version when IHQ_LINE_TERMS_VERSION is not defined. */
const IHQ_LINE_TERMS_VERSION_DEFAULT = '2026-07-21';

/**
 * LINE Official Account Add Friend URL (empty if not configured).
 *
 * @return string
 */
function ihq_line_add_friend_url() {
	if ( ! defined( 'IHQ_LINE_ADD_FRIEND_URL' ) || IHQ_LINE_ADD_FRIEND_URL === '' ) {
		return '';
	}
	return esc_url_raw( (string) IHQ_LINE_ADD_FRIEND_URL );
}

/**
 * Consent terms version string for Braze audit attribute.
 *
 * @return string
 */
function ihq_line_terms_version() {
	if ( defined( 'IHQ_LINE_TERMS_VERSION' ) && IHQ_LINE_TERMS_VERSION !== '' ) {
		return sanitize_text_field( (string) IHQ_LINE_TERMS_VERSION );
	}
	return IHQ_LINE_TERMS_VERSION_DEFAULT;
}

/**
 * Consent source label written to Braze.
 *
 * @return string
 */
function ihq_line_consent_source() {
	return IHQ_LINE_CONSENT_SOURCE_DEFAULT;
}

/**
 * Whether a string looks like a LINE internal user ID (U...).
 *
 * @param string $value Candidate ID.
 * @return bool
 */
function ihq_line_is_native_user_id( $value ) {
	$value = trim( (string) $value );
	return (bool) preg_match( '/^U[a-zA-Z0-9]+$/', $value );
}

/**
 * Frontend config for LINE enrollment UI / JS.
 *
 * @return array<string, mixed>
 */
function ihq_line_enrollment_js_config() {
	return array(
		'addFriendUrl'  => ihq_line_add_friend_url(),
		'termsVersion'  => ihq_line_terms_version(),
		'consentSource' => ihq_line_consent_source(),
		'consentMarker' => IHQ_LINE_CONSENT_COMM_MARKER,
	);
}

/**
 * Braze attributes for LINE consent enrollment (no subscription group writes).
 *
 * @param array<string, mixed> $intent Visitor intent cookie payload.
 * @return array<string, mixed>
 */
function ihq_line_enrollment_braze_attributes( array $intent ) {
	$line_consent = ! empty( $intent['line_consent'] );
	$comm         = isset( $intent['comm_methods'] ) && is_array( $intent['comm_methods'] )
		? $intent['comm_methods']
		: array();
	$line_selected = isset( $comm['line'] ) && (string) $comm['line'] !== '';

	if ( ! $line_consent && ! $line_selected ) {
		return array();
	}

	$attrs = array(
		'line_consent'           => true,
		'line_consent_timestamp' => isset( $intent['line_consent_timestamp'] )
			? sanitize_text_field( (string) $intent['line_consent_timestamp'] )
			: gmdate( 'c' ),
		'line_consent_source'    => isset( $intent['line_consent_source'] )
			? sanitize_text_field( (string) $intent['line_consent_source'] )
			: ihq_line_consent_source(),
		'line_terms_version'     => isset( $intent['line_terms_version'] )
			? sanitize_text_field( (string) $intent['line_terms_version'] )
			: ihq_line_terms_version(),
	);

	$native_id = '';
	if ( isset( $intent['native_line_id'] ) ) {
		$native_id = trim( (string) $intent['native_line_id'] );
	}
	if ( $native_id === '' && isset( $comm['line'] ) && ihq_line_is_native_user_id( $comm['line'] ) ) {
		$native_id = trim( (string) $comm['line'] );
	}
	if ( ihq_line_is_native_user_id( $native_id ) ) {
		$attrs['native_line_id'] = $native_id;
	}

	return $attrs;
}

/**
 * Render LINE enrollment fields inside #modal-comm-entry-line (no manual ID input).
 *
 * @return void
 */
function ihq_render_line_enrollment_fields() {
	$add_friend_url = ihq_line_add_friend_url();
	?>
	<div class="modal-line-enrollment-panel">
		<p class="modal-line-helper">
			<?php esc_html_e( 'You must add our Official Account as a friend to receive LINE messages', 'influencer-hq' ); ?>
		</p>
		<p class="modal-line-consent-copy">
			<?php esc_html_e( 'By selecting LINE, you consent to receive messages through our LINE Official Account. You must also add the Official Account as a friend to receive messages.', 'influencer-hq' ); ?>
		</p>
		<label class="modal-line-consent-label" for="modal-line-consent">
			<input
				type="checkbox"
				id="modal-line-consent"
				name="modal_line_consent"
				autocomplete="off"
			>
			<span><?php esc_html_e( 'I consent to receive LINE messages from Influencer HQ', 'influencer-hq' ); ?></span>
		</label>
		<p class="modal-line-consent-err" id="modal-line-consent-err" role="alert"></p>
		<?php if ( $add_friend_url !== '' ) : ?>
		<a
			class="modal-line-add-friend-btn"
			id="modal-line-add-friend"
			href="<?php echo esc_url( $add_friend_url ); ?>"
			target="_blank"
			rel="noopener noreferrer"
		><?php esc_html_e( 'Add us on LINE', 'influencer-hq' ); ?></a>
		<?php else : ?>
		<p class="modal-line-helper modal-line-helper--warn">
			<?php esc_html_e( 'LINE Add Friend link is not configured yet.', 'influencer-hq' ); ?>
		</p>
		<?php endif; ?>
	</div>
	<?php
}
