<?php
/**
 * Plugin Name: LearnDash Certificate Generator
 * Description: Automatically generates a certificate post and sends a notification
 *              email when a user completes a LearnDash course. Matches the completed
 *              course to a WooCommerce order item to verify the purchase before
 *              creating the certificate.
 * Version:     1.1.0
 * Author:      Milos Milosevic
 * Requires:    LearnDash, WooCommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'learndash_course_completed', 'generate_ld_certificate', 20 );

/**
 * Generate a certificate when a LearnDash course is completed.
 *
 * Hooks into `learndash_course_completed`, verifies the course purchase
 * via WooCommerce orders, creates an `urban_certificates` post with the
 * certificate HTML, and emails the student a completion notification.
 *
 * @param array $data {
 *     Course completion data provided by LearnDash.
 *
 *     @type WP_Post $course The completed course post object.
 *     @type WP_User $user   The user who completed the course.
 * }
 * @return void
 */
function generate_ld_certificate( $data ) {
	// --- Guard: validate incoming data ---
	if ( empty( $data ) || ! is_array( $data ) ) {
		return;
	}

	if ( empty( $data['course'] ) || ! $data['course'] instanceof WP_Post ) {
		return;
	}

	if ( empty( $data['user'] ) || ! $data['user'] instanceof WP_User ) {
		return;
	}

	$course_id  = $data['course']->ID;
	$course_obj = get_post( $course_id );

	if ( ! $course_obj instanceof WP_Post ) {
		return;
	}

	$course_name = $course_obj->post_title;
	$user_id     = $data['user']->ID;

	// --- Guard: validate user ---
	$user = get_user_by( 'ID', $user_id );

	if ( ! $user instanceof WP_User ) {
		return;
	}

	// --- Check for existing certificate to prevent duplicates ---
	$existing = get_posts( array(
		'post_type'  => 'urban_certificates',
		'meta_query' => array(
			'relation' => 'AND',
			array(
				'key'   => '_certificate_user_id',
				'value' => $user_id,
			),
			array(
				'key'   => '_certificate_course_id',
				'value' => $course_id,
			),
		),
		'posts_per_page' => 1,
		'fields'         => 'ids',
	) );

	if ( ! empty( $existing ) ) {
		return;
	}

	// --- Find matching WooCommerce order item ---
	$orders = wc_get_orders( array(
		'customer_id' => $user_id,
	) );

	$order_matched = false;

	foreach ( $orders as $order ) {
		foreach ( $order->get_items() as $item ) {
			if ( $item->get_name() === $course_name ) {
				$order_matched = true;
				break 2;
			}
		}
	}

	if ( ! $order_matched ) {
		return;
	}

	// --- Build certificate ---
	$first_name      = esc_html( $user->first_name );
	$last_name       = esc_html( $user->last_name );
	$safe_course     = esc_html( $course_name );
	$completion_date = date_i18n( get_option( 'date_format' ) );
	$post_title      = $course_name . ' ' . $user->first_name . ' ' . $user->last_name;

	$post_content = <<<EOD
<div style="width:800px; height:600px; padding:20px; text-align:center; border: 10px solid #787878">
<div style="width:750px; height:550px; padding:20px; text-align:center; border: 5px solid #787878">
	<span style="font-size:50px; font-weight:bold">Certificate of Completion</span>
	<br><br>
	<span style="font-size:25px"><i>This is to certify that</i></span>
	<br><br>
	<span style="font-size:30px"><b>{$first_name} {$last_name}</b></span><br/><br/>
	<span style="font-size:25px"><i>has completed the course</i></span><br/><br/>
	<span style="font-size:30px">{$safe_course}</span><br/><br/>
	<span style="font-size:25px"><i>dated</i></span><br>
	<span style="font-size:30px">{$completion_date}</span>
</div>
</div>
EOD;

	$new_cert = array(
		'post_title'   => sanitize_text_field( $post_title ),
		'post_content' => $post_content,
		'post_status'  => 'publish',
		'post_type'    => 'urban_certificates',
		'meta_input'   => array(
			'_certificate_user_id'   => $user_id,
			'_certificate_course_id' => $course_id,
		),
	);

	kses_remove_filters();
	$cert_id = wp_insert_post( $new_cert, true );
	kses_init_filters();

	if ( is_wp_error( $cert_id ) || 0 === $cert_id ) {
		error_log( 'LearnDash Certificate: failed to create certificate for user ' . $user_id . ', course ' . $course_id );
		return;
	}

	// --- Send notification email ---
	$to      = sanitize_email( $user->user_email );
	$subject = sprintf( 'Certificate of Completion: %s', $course_name );
	$body    = sprintf(
		'Congratulations %s! You have completed <strong>%s</strong>. Your certificate has been generated.',
		$first_name,
		$safe_course
	);
	$headers = array( 'Content-Type: text/html; charset=UTF-8' );

	wp_mail( $to, $subject, $body, $headers );
}
