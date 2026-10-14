<?php
/**
 * Stand-in emails for accounts registered without one (ENGR-7511).
 *
 * The AI Coach flow lets a visitor register with any one channel (PO-3099),
 * but a WordPress account needs an email, so such accounts get
 * <uuid>@chat.invalid. This file is the one place that knows the domain, so
 * every consumer can tell a stand-in from a real address — and PO-3255 can
 * find these accounts to merge them once IHQ has chat-app sign-in.
 *
 * @package influencer-hq
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * .invalid can never deliver (RFC 2606); it is also the placeholder domain
 * PO-3155 uses for chat-app-only game accounts.
 */
const IHQ_PLACEHOLDER_EMAIL_DOMAIN = 'chat.invalid';

/**
 * Whether an address is a stand-in rather than one the influencer gave us.
 *
 * @param string $email Email address.
 * @return bool
 */
function ihq_is_placeholder_email( $email ) {
	$suffix = '@' . IHQ_PLACEHOLDER_EMAIL_DOMAIN;
	$email  = strtolower( trim( (string) $email ) );
	return strlen( $email ) > strlen( $suffix )
		&& substr( $email, -strlen( $suffix ) ) === $suffix;
}
