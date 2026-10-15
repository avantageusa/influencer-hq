<?php
/**
 * Kill switch for the portal onboarding auto-launch (ENGR-7561, PO-3063 T15).
 *
 * The Coach page (page-portal-home.php) starts Sami's onboarding for a newly
 * registered Influencer only while this switch is on. The theme reads no
 * GrowthBook flags (PO-3063 evaluation, EQ3), so the switch is a WordPress
 * option, turned on per environment with WP-CLI once QA has signed off:
 *
 *     wp option update ihq_portal_onboarding_enabled 1
 *     wp option delete ihq_portal_onboarding_enabled     (back to off)
 *
 * The option does not exist after a deploy, which reads as off: nobody is
 * auto-launched and the Coach page behaves as it does today.
 *
 * The entry gate (ENGR-7564) calls ihq_portal_onboarding_enabled(); keep the
 * function and option names stable.
 *
 * @package influencer-hq
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** WordPress option that turns the onboarding auto-launch on. */
const IHQ_PORTAL_ONBOARDING_ENABLED_OPTION = 'ihq_portal_onboarding_enabled';

/** Option values (lower-cased, trimmed) that count as "on"; anything else is off. */
const IHQ_PORTAL_ONBOARDING_ON_VALUES = array( '1', 'true', 'yes', 'on' );

/**
 * Whether the onboarding auto-launch is switched on.
 *
 * Only an explicit "on" value counts ("1", "true", "yes", "on", 1, true);
 * a missing option, an empty string, "0", "off" or anything unrecognised is
 * off, so a typo in a WP-CLI command fails closed rather than launching
 * onboarding for everyone.
 *
 * @return bool
 */
function ihq_portal_onboarding_enabled() {
	$value = get_option( IHQ_PORTAL_ONBOARDING_ENABLED_OPTION, false );
	if ( is_bool( $value ) ) {
		return $value;
	}
	if ( is_int( $value ) ) {
		return 1 === $value;
	}
	if ( ! is_string( $value ) ) {
		return false;
	}
	return in_array( strtolower( trim( $value ) ), IHQ_PORTAL_ONBOARDING_ON_VALUES, true );
}
