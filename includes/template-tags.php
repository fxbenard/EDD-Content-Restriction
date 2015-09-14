<?php

/**
 * Template Tags
 *
 * @package     EDD\ContentRestriction\TemplateTags
 * @copyright   Copyright (c) 2013-2014, Pippin Williamson
 * @since       2.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Determine if a user has permission to view the currently viewed URL
 *
 * Mainly for use in template files
 * @return bool If the current user has permission to view the current URL
 */
function edd_cr_user_has_access() {
	global $post;

	$has_access = true;

	if ( is_object( $post ) && ! empty( $post->ID ) ) {

		$is_post_restricted = edd_cr_is_restricted( $post->ID );

		if ( $is_post_restricted ) {
			$user_has_access = edd_cr_user_can_access( get_current_user_id(), $is_post_restricted, $post->ID );
			$has_access      = $user_has_access['status'] == false  ? false : true;
		}

	}

	return apply_filters( 'ecc_cr_user_has_access', $has_access );

}
