<?php
/**
 * Influencer video submissions (Profile). Status is stored for a future
 * moderation queue and is not shown in the current UI.
 *
 * @package influencer-hq
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function ihq_video_submission_max() {
	return 5;
}

function ihq_video_subject_max_length() {
	return 50;
}

/**
 * Default status written on every save. Later approval can use pending / approved / rejected.
 */
function ihq_video_status_auto_promoted() {
	return 'auto_promoted';
}

function ihq_video_submissions_meta_key() {
	return '_ihq_video_submissions';
}

/**
 * @param string $raw Pasted link from any platform.
 * @return string Sanitized http(s) URL or empty string.
 */
function ihq_sanitize_http_media_url( $raw ) {
	$url = esc_url_raw( trim( (string) $raw ) );
	if ( $url === '' || ! preg_match( '#^https?://#i', $url ) ) {
		return '';
	}
	if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
		return '';
	}
	return $url;
}

/**
 * @param array<string, mixed> $item Raw stored row.
 * @return array{id: string, url: string, subject: string, status: string, created_at: string, updated_at: string}|null
 */
function ihq_normalize_video_submission( $item ) {
	if ( ! is_array( $item ) ) {
		return null;
	}
	$url = ihq_sanitize_http_media_url( isset( $item['url'] ) ? (string) $item['url'] : '' );
	if ( $url === '' ) {
		return null;
	}
	$subject = sanitize_text_field( isset( $item['subject'] ) ? (string) $item['subject'] : '' );
	$subject = function_exists( 'mb_substr' )
		? mb_substr( $subject, 0, ihq_video_subject_max_length() )
		: substr( $subject, 0, ihq_video_subject_max_length() );
	$id = isset( $item['id'] ) ? sanitize_text_field( (string) $item['id'] ) : '';
	if ( $id === '' ) {
		$id = wp_generate_uuid4();
	}
	$created = isset( $item['created_at'] ) ? sanitize_text_field( (string) $item['created_at'] ) : '';
	$updated = isset( $item['updated_at'] ) ? sanitize_text_field( (string) $item['updated_at'] ) : '';
	$now     = gmdate( 'c' );
	if ( $created === '' ) {
		$created = $now;
	}
	if ( $updated === '' ) {
		$updated = $created;
	}
	$status = isset( $item['status'] ) ? sanitize_key( (string) $item['status'] ) : '';
	if ( $status === '' ) {
		$status = ihq_video_status_auto_promoted();
	}
	return array(
		'id'         => $id,
		'url'        => $url,
		'subject'    => $subject,
		'status'     => $status,
		'created_at' => $created,
		'updated_at' => $updated,
	);
}

/**
 * @param int $user_id WP user ID.
 * @return array<int, array{id: string, url: string, subject: string, status: string, created_at: string, updated_at: string}>
 */
function ihq_get_video_submissions( $user_id ) {
	$user_id = (int) $user_id;
	$stored  = get_user_meta( $user_id, ihq_video_submissions_meta_key(), true );
	$items   = array();
	if ( is_array( $stored ) ) {
		foreach ( $stored as $row ) {
			$normalized = ihq_normalize_video_submission( $row );
			if ( $normalized !== null ) {
				$items[] = $normalized;
			}
		}
		return array_slice( $items, 0, ihq_video_submission_max() );
	}

	$legacy     = get_user_meta( $user_id, '_ihq_gameplay_video_url', true );
	$legacy_url = ihq_sanitize_http_media_url( is_string( $legacy ) ? $legacy : '' );
	if ( $legacy_url !== '' ) {
		$migrated = ihq_normalize_video_submission(
			array(
				'url'     => $legacy_url,
				'subject' => __( 'Gameplay video', 'influencer-hq' ),
				'status'  => ihq_video_status_auto_promoted(),
			)
		);
		if ( $migrated !== null ) {
			$items[] = $migrated;
			ihq_save_video_submissions( $user_id, $items );
		}
	}
	return array_slice( $items, 0, ihq_video_submission_max() );
}

/**
 * @param int $user_id WP user ID.
 * @param array<int, array<string, mixed>> $items Normalized rows.
 */
function ihq_save_video_submissions( $user_id, $items ) {
	$user_id = (int) $user_id;
	$clean   = array();
	foreach ( $items as $row ) {
		$normalized = ihq_normalize_video_submission( $row );
		if ( $normalized !== null ) {
			$clean[] = $normalized;
		}
	}
	$clean = array_slice( $clean, 0, ihq_video_submission_max() );
	update_user_meta( $user_id, ihq_video_submissions_meta_key(), $clean );
	$first_url = isset( $clean[0]['url'] ) ? $clean[0]['url'] : '';
	update_user_meta( $user_id, '_ihq_gameplay_video_url', $first_url );
}
