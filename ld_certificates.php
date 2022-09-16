<?php

/**
 * Passing data upon learndash complete hook and using it to generate certificate. 
 * @param $data | An array of course complete data.
 */

add_action ( 'learndash_course_completed', 'generate_ld_certificate', 20 );

function generate_ld_certificate ($data) {
	$course_id = $data['course']->ID;		
	$course_obj = get_post ( $course_id );
	$course_name = $course_obj->post_title;
	
	$args = array(
		'customer_id' => $data['user']->ID,
	);

	$orders = wc_get_orders( $args );

	foreach( $orders as $order ) :

		foreach ( $order->get_items() as $item_id => $item ) :

		if ( $item->get_name() == $course_name ) {
			$user = get_user_by( 'ID', $data['user']->ID );
			$display_first_name = $user->first_name;
			$display_last_name = $user->last_name;
			$post_title = $course_name . ' ' . $display_first_name . ' ' . $display_last_name;	
			$post_content = <<<EOD
			<div style="width:800px; height:600px; padding:20px; text-align:center; border: 10px solid #787878">
			<div style="width:750px; height:550px; padding:20px; text-align:center; border: 5px solid #787878">
				   <span style="font-size:50px; font-weight:bold">Certificate of Completion</span>
				   <br><br>
				   <span style="font-size:25px"><i>This is to certify that</i></span>
				   <br><br>
				   <span style="font-size:30px"><b> $display_first_name $display_last_name</b></span><br/><br/>
				   <span style="font-size:25px"><i>has completed the course</i></span> <br/><br/>
				   <span style="font-size:30px"> $course_name </span> <br/><br/>
				   <span style="font-size:25px"><i>dated</i></span><br>
				  <span style="font-size:30px">date</span>
			</div>
			</div>
			EOD;

		$new_cert = array(
						  'post_title'    => $post_title,
						  'post_content'  => $post_content,
						  'post_status'   => 'publish',
						  'post_type'  => 'urban_certificates',
		);

 		kses_remove_filters();
		wp_insert_post( $new_cert );
		kses_init_filters();		
		
	}
	
	$to = $user->user_email;
	$subject = $post_title;
	$body = $course_name;
	$headers = array( 'Content-Type: text/html; charset=UTF-8' );
	wp_mail( $to, $subject, $body, $headers );

  endforeach;
 
endforeach;		
}

