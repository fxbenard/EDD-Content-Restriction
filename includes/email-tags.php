<?php
/**
 * Email tags
 *
 * @package     EDD\ContentRestriction\EmailTags
 * @copyright   Copyright (c) 2013-2014, Pippin Williamson
 * @since       2.2.0
 */


// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Registers our email tags
 *
 * @since       1.5.4
 * @return      void
 */
function edd_cr_register_email_tags() {
	edd_add_email_tag( 'page_list', __( 'Shows a list of restricted pages the customer has access to', 'edd-cr' ), 'edd_cr_add_template_tags' );
}
add_action( 'edd_add_email_tags', 'edd_cr_register_email_tags' );


/**
 * Add email template tags
 *
 * @since       1.3.0
 * @param       int $payment_id The payment ID
 * @return      string $page_list The list of accessible pages
 */
function edd_cr_add_template_tags( $payment_id ) {
	// Get the array of restricted pages for this payment
	$meta = edd_cr_get_restricted_pages( $payment_id );

	// No pages? Quit!
	if ( ! is_array( $meta ) || empty( $meta ) ) {
		return '';
	}

	$page_list  = '<div class="edd_cr_accessible_pages">' . __( 'Pages', 'edd-cr' ) . '</div>';
	$page_list .= '<ul>';

	foreach ( $meta as $download_id => $pages ) {
		$page_list .= '<lh>' . get_the_title( $download_id ) . '</lh>';

		foreach ( $pages as $page_id => $page_title ) {
			$page_list .= '<li><a href="' . esc_url( get_permalink( $page_id ) ) . '">' . $page_title . '</a></li>';
		}
	}

	$page_list .= '</ul>';
	$page_list .= '</li>';

	return $page_list;
}
