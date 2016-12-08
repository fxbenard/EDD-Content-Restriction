<?php
/**
 * Add Helper Functions and Template Overrides
 *
 * @package     EDD\ContentRestriction\Functions
 * @copyright   Copyright (c) 2013-2014, Pippin Williamson
 * @since       1.0.0
 */


// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Get posts/pages restricted to the purchased files
 *
 * @since       1.3.0
 * @param       int $payment_id The ID of this payment
 * @return      array $posts The list of accessible posts
 */
function edd_cr_get_restricted_pages( $payment_id = 0 ) {
	if ( empty( $payment_id ) ) {
		return false;
	}

	$posts    = array();
	$files    = edd_get_payment_meta_downloads( $payment_id );

	if ( ! empty( $files ) && is_array( $files ) ) {
		$ids = array_unique( wp_list_pluck( $files, 'id' ) );

		foreach ( $ids as $download_id ) {
			$meta = get_post_meta( $download_id, '_edd_cr_protected_post' );

			if ( $meta ) {
				foreach ( $meta as $item ) {
					if( get_post_type( $item ) !== 'revision' ) {
						$posts[ $download_id ][ $item ] = get_the_title( $item );
					}
				}
			}
		}
	}

	return $posts;
}
