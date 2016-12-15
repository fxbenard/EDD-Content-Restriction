<?php
/**
 * Add shortcodes
 *
 * @package     EDD\ContentRestriction\Shortcodes
 * @copyright   Copyright (c) 2013-2014, Pippin Williamson
 * @since       1.0.0
 */


// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Add edd_restrict shortcode
 *
 * @since       1.0.0
 * @param       array $atts The attributes to pass to the shortcode
 * @param       string $content The content of the shortcode
 * @return      string $content The data to return for the shortcode
 */
function edd_cr_restrict_shortcode( $atts, $content = null ) {
	$atts = shortcode_atts( array(
		'id'        => null,
		'price_id'  => null,
		'message'   => null,
		'class'     => ''
	), $atts, 'edd_restrict' );

	$restricted_to = array();

	if ( ! is_null( $atts['id'] ) ) {
		$ids = explode( ',', $atts['id'] );

		foreach ( $ids as $download_id ) {
			$restricted_to[] = array(
				'download' => $download_id,
				'price_id' => $atts['price_id']
			);
		}

		$content = edd_cr_filter_restricted_content( $content, $restricted_to, $atts['message'], 0, $atts['class'] );
	}

	// Allows extensions to filter on the content, restrictions, and attributes passed
	return apply_filters( 'edd_cr_restrict_shortcode_content', $content, $restricted_to, $atts );
}
add_shortcode( 'edd_restrict', 'edd_cr_restrict_shortcode' );


/**
 * Displays a list of restricted pages the currently logged-in user has access to
 *
 * @since       1.5.0
 * @param       array $atts The attributes to pass to the shortcode
 * @param       string $content The content of the shortcode
 * @return      string $content The data to return for the shortcode
 */
function edd_cr_pages_shortcode( $atts, $content = null ) {
	$atts = shortcode_atts( array(
		'class' => ''
	), $atts, 'edd_restricted_pages' );

	if ( is_user_logged_in() ) {
		$pages     = array();
		$purchases = edd_get_users_purchases( get_current_user_id(), -1 );

		if ( $purchases ) {
			foreach ( $purchases as $purchase ) {
				$restricted = edd_cr_get_restricted_pages( $purchase->ID );

				if ( empty( $restricted ) ) {
					continue;
				}

				foreach ( $restricted as $download_pages ) {
					foreach ( $download_pages as $page_id => $page_title ) {
						$pages[ $page_id ] = $page_title;
					}
				}
			}

			$pages = array_filter( $pages );

			if ( ! empty( $pages ) ) {
				$content = '<ul class="edd_cr_pages">';

				foreach ( $pages as $page_id => $page_title ) {
					$content .= '<li><a href="' . esc_url( get_permalink( $page_id ) ) . '">' . $page_title . '</a></li>';
				}

				$content .= '</ul>';
			} else {
				$content = '<div class="edd_cr_no_pages">' . __( 'You have not purchased access to any content.', 'edd-cr' ) . '</div>';
			}
		} else {
			$content = '<div class="edd_cr_no_pages">' . __( 'You have not purchased access to any content.', 'edd-cr' ) . '</div>';
		}
	} else {
		$content = '<div class="edd_cr_not_logged_in">' . __( 'You must be logged in to access your purchased content.', 'edd-cr' ) . '</div>';
	}

	return $content;
}
add_shortcode( 'edd_restricted_pages', 'edd_cr_pages_shortcode' );
