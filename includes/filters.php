<?php
/**
 * Filters
 *
 * @package     EDD\ContentRestriction\Filters
 * @copyright   Copyright (c) 2013-2014, Pippin Williamson
 * @since       2.2.0
 */


// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Filter content to handle restricted posts/pages
 *
 * @since       1.0.0
 * @param       string $content The content to filter
 * @global      object $post The post we are editing
 * @return      string $content The filtered content
 */
function edd_cr_filter_content( $content ) {
	global $post;

	// If $post isn't an object, we aren't handling it!
	if ( ! is_object( $post ) ) {
		return $content;
	}

	$restricted = edd_cr_is_restricted( $post->ID );

	if ( $restricted ) {
		$content = edd_cr_filter_restricted_content( $content, $restricted, null, $post->ID );
	}

	return $content;
}
add_filter( 'the_content', 'edd_cr_filter_content' );
