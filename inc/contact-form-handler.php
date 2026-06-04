<?php
/**
 * Contact Us form — AJAX submission and email to concierge.
 *
 * @package Avantage_Baccarat
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Inbox for Contact Us submissions. */
const IHQ_CONTACT_FORM_RECIPIENT = 'concierge@influencerhq.co';

/** Max attachment size in bytes (5 MB). */
const IHQ_CONTACT_FORM_MAX_FILE_BYTES = 5242880;

/** Throttle window per IP in seconds (1 submission per 5 minutes). */
const IHQ_CONTACT_FORM_IP_THROTTLE_SECONDS = 300;

/**
 * Allowed subject dropdown values => email label.
 *
 * @return array<string, string>
 */
function ihq_contact_form_allowed_subjects() {
	return array(
		'technical-issues' => 'Technical issues',
		'marketing'        => 'Marketing',
		'collaboration'    => 'Collaboration',
	);
}

/**
 * WordPress-style allowed mimes (extension key => mime type).
 *
 * @return array<string, string>
 */
function ihq_contact_form_allowed_mimes() {
	return array(
		'jpg|jpeg|jpe' => 'image/jpeg',
		'png'          => 'image/png',
		'gif'          => 'image/gif',
		'webp'         => 'image/webp',
		'pdf'          => 'application/pdf',
		'txt'          => 'text/plain',
		'doc'          => 'application/msword',
		'docx'         => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
	);
}

/**
 * Validate file type for contact uploads; returns ext + mime or false.
 *
 * @param string $tmp_name Uploaded temp path.
 * @param string $filename Original filename.
 * @return array{ext: string, type: string}|false
 */
function ihq_contact_form_detect_file_type( $tmp_name, $filename ) {
	$allowed = ihq_contact_form_allowed_mimes();
	$check   = wp_check_filetype_and_ext( $tmp_name, $filename, $allowed );
	$ext     = isset( $check['ext'] ) ? $check['ext'] : false;
	$type    = isset( $check['type'] ) ? $check['type'] : false;

	$allowed_types = array_values( $allowed );

	if ( $ext && $type && in_array( $type, $allowed_types, true ) ) {
		return array(
			'ext'  => $ext,
			'type' => $type,
		);
	}

	// Some hosts return empty ext/type for valid JPEGs; fall back to filename + image probe.
	$by_name = wp_check_filetype( $filename, $allowed );
	if ( empty( $by_name['ext'] ) || empty( $by_name['type'] ) ) {
		return false;
	}
	if ( ! in_array( $by_name['type'], $allowed_types, true ) ) {
		return false;
	}

	if ( strpos( $by_name['type'], 'image/' ) === 0 ) {
		$image_info = @getimagesize( $tmp_name );
		if ( $image_info === false || empty( $image_info['mime'] ) ) {
			return false;
		}
		$detected = $image_info['mime'];
		if ( $detected === 'image/jpg' ) {
			$detected = 'image/jpeg';
		}
		if ( ! in_array( $detected, $allowed_types, true ) ) {
			return false;
		}
		return array(
			'ext'  => $by_name['ext'],
			'type' => $detected,
		);
	}

	return array(
		'ext'  => $by_name['ext'],
		'type' => $by_name['type'],
	);
}

add_action( 'wp_ajax_ihq_submit_contact_form', 'ihq_handle_submit_contact_form_ajax' );
add_action( 'wp_ajax_nopriv_ihq_submit_contact_form', 'ihq_handle_submit_contact_form_ajax' );

/**
 * Handle Contact Us form POST (multipart FormData).
 */
function ihq_handle_submit_contact_form_ajax() {
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'ihq_contact_form_nonce' ) ) {
		wp_send_json_error( array( 'message' => __( 'Invalid security token. Please refresh and try again.', 'avantage-baccarat' ) ) );
		return;
	}

	if ( function_exists( 'ihq_verify_turnstile_or_error_for_ajax' ) ) {
		$turn = ihq_verify_turnstile_or_error_for_ajax();
		if ( is_wp_error( $turn ) ) {
			wp_send_json_error( array( 'message' => $turn->get_error_message() ) );
			return;
		}
	}

	$client_ip = function_exists( 'ihq_get_client_ip_for_rate_limit' ) ? ihq_get_client_ip_for_rate_limit() : '';
	if ( $client_ip !== '' ) {
		$throttle_key = 'ihq_contact_send_ip_' . md5( $client_ip );
		if ( get_transient( $throttle_key ) ) {
			wp_send_json_error( array( 'message' => __( 'Only one contact form submission is allowed every 5 minutes from this IP.', 'avantage-baccarat' ) ) );
			return;
		}
	}

	$first_name = isset( $_POST['first_name'] ) ? sanitize_text_field( wp_unslash( $_POST['first_name'] ) ) : '';
	$last_name  = isset( $_POST['last_name'] ) ? sanitize_text_field( wp_unslash( $_POST['last_name'] ) ) : '';
	$email      = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
	$subject_key = isset( $_POST['subject'] ) ? sanitize_key( wp_unslash( $_POST['subject'] ) ) : '';
	$message    = isset( $_POST['message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['message'] ) ) : '';

	$field_errors = array();

	if ( $first_name === '' ) {
		$field_errors['first_name'] = __( 'This field is required', 'avantage-baccarat' );
	}
	if ( $last_name === '' ) {
		$field_errors['last_name'] = __( 'This field is required', 'avantage-baccarat' );
	}
	if ( $email === '' ) {
		$field_errors['email'] = __( 'This field is required', 'avantage-baccarat' );
	} elseif ( ! is_email( $email ) ) {
		$field_errors['email'] = __( 'Please enter a valid email address', 'avantage-baccarat' );
	}

	$allowed_subjects = ihq_contact_form_allowed_subjects();
	if ( $subject_key === '' || ! isset( $allowed_subjects[ $subject_key ] ) ) {
		$field_errors['subject'] = __( 'This field is required', 'avantage-baccarat' );
	}

	if ( $message === '' ) {
		$field_errors['message'] = __( 'This field is required', 'avantage-baccarat' );
	}

	$attachment_path = '';
	$attachment_name = '';

	if ( ! empty( $_FILES['attachment']['name'] ) && (int) $_FILES['attachment']['error'] !== UPLOAD_ERR_NO_FILE ) {
		$file_result = ihq_contact_form_validate_upload( $_FILES['attachment'] );
		if ( is_wp_error( $file_result ) ) {
			$field_errors['attachment'] = $file_result->get_error_message();
		} else {
			$attachment_path = $file_result['path'];
			$attachment_name = $file_result['name'];
		}
	}

	if ( ! empty( $field_errors ) ) {
		wp_send_json_error(
			array(
				'message'      => __( 'Please correct the errors below.', 'avantage-baccarat' ),
				'field_errors' => $field_errors,
			)
		);
		return;
	}

	if ( $client_ip !== '' ) {
		set_transient( 'ihq_contact_send_ip_' . md5( $client_ip ), 1, IHQ_CONTACT_FORM_IP_THROTTLE_SECONDS );
	}

	$subject_label = $allowed_subjects[ $subject_key ];
	$mail_subject  = sprintf(
		/* translators: 1: subject label, 2: sender name */
		__( 'Contact Us: %1$s — %2$s', 'avantage-baccarat' ),
		$subject_label,
		$first_name . ' ' . $last_name
	);

	$body_lines = array(
		'Subject: ' . $subject_label,
		'From: ' . $first_name . ' ' . $last_name,
		'Email: ' . $email,
		'',
		'Message:',
		$message,
	);
	if ( $attachment_name !== '' ) {
		$body_lines[] = '';
		$body_lines[] = 'Attachment: ' . $attachment_name;
	}

	$mail_body = implode( "\n", $body_lines );

	$headers = array(
		'Content-Type: text/plain; charset=UTF-8',
		'From: Influencer HQ <verify@influencerhq.co>',
		'Reply-To: ' . $first_name . ' ' . $last_name . ' <' . $email . '>',
	);

	$attachments = array();
	if ( $attachment_path !== '' ) {
		$attachments[] = $attachment_path;
	}

	$mail_error = null;
	$failed_hook = function ( $wp_error ) use ( &$mail_error ) {
		$mail_error = $wp_error->get_error_message();
		error_log( 'IHQ contact form wp_mail failed: ' . $mail_error );
	};
	add_action( 'wp_mail_failed', $failed_hook );

	$sent = wp_mail( IHQ_CONTACT_FORM_RECIPIENT, $mail_subject, $mail_body, $headers, $attachments );

	remove_action( 'wp_mail_failed', $failed_hook );

	if ( $attachment_path !== '' && file_exists( $attachment_path ) ) {
		wp_delete_file( $attachment_path );
	}

	if ( ! $sent ) {
		$err = $mail_error ? $mail_error : __( 'Failed to send your message. Please try again later.', 'avantage-baccarat' );
		error_log( 'IHQ contact form failed for ' . $email . ': ' . $err );
		wp_send_json_error( array( 'message' => $err ) );
		return;
	}

	wp_send_json_success(
		array(
			'message' => __( 'Thank you! Your message has been sent. We will get back to you within 24 hours.', 'avantage-baccarat' ),
		)
	);
}

/**
 * Validate uploaded file; returns path + name or WP_Error.
 *
 * @param array $file $_FILES entry.
 * @return array{path: string, name: string}|WP_Error
 */
function ihq_contact_form_validate_upload( array $file ) {
	$error_code = isset( $file['error'] ) ? (int) $file['error'] : UPLOAD_ERR_NO_FILE;

	if ( $error_code !== UPLOAD_ERR_OK ) {
		return new WP_Error( 'upload_error', __( 'File upload failed. Please try again.', 'avantage-baccarat' ) );
	}

	if ( empty( $file['tmp_name'] ) || ! is_uploaded_file( $file['tmp_name'] ) ) {
		return new WP_Error( 'upload_invalid', __( 'Invalid file upload.', 'avantage-baccarat' ) );
	}

	$size = isset( $file['size'] ) ? (int) $file['size'] : 0;
	if ( $size <= 0 || $size > IHQ_CONTACT_FORM_MAX_FILE_BYTES ) {
		return new WP_Error(
			'upload_size',
			sprintf(
				/* translators: %d: max size in MB */
				__( 'File must be smaller than %d MB.', 'avantage-baccarat' ),
				(int) ( IHQ_CONTACT_FORM_MAX_FILE_BYTES / 1048576 )
			)
		);
	}

	$detected = ihq_contact_form_detect_file_type( $file['tmp_name'], $file['name'] );
	if ( $detected === false ) {
		return new WP_Error(
			'upload_type',
			__( 'Allowed file types: images (JPG, PNG, GIF, WebP), PDF, TXT, DOC, DOCX.', 'avantage-baccarat' )
		);
	}

	$upload_dir = wp_upload_dir();
	if ( ! empty( $upload_dir['error'] ) ) {
		return new WP_Error( 'upload_dir', __( 'Unable to process upload.', 'avantage-baccarat' ) );
	}

	$subdir = trailingslashit( $upload_dir['basedir'] ) . 'ihq-contact-temp';
	if ( ! wp_mkdir_p( $subdir ) ) {
		return new WP_Error( 'upload_dir', __( 'Unable to process upload.', 'avantage-baccarat' ) );
	}

	$safe_name = sanitize_file_name( $file['name'] );
	$dest      = $subdir . '/' . wp_unique_filename( $subdir, $safe_name );

	if ( ! move_uploaded_file( $file['tmp_name'], $dest ) ) {
		return new WP_Error( 'upload_move', __( 'File upload failed. Please try again.', 'avantage-baccarat' ) );
	}

	return array(
		'path' => $dest,
		'name' => $safe_name,
	);
}
