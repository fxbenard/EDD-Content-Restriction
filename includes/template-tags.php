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
 * @since  2.1
 * @param int $user_id The User ID to check (defaults to logged in user)
 * @param int $post_id The Post ID to check access for (defaults to current post)
 * @return bool If the current user has permission to view the current URL
 */
function edd_cr_user_has_access( $user_id = 0, $post_id = 0 ) {
	global $post;

	$user_id = empty( $user_id )                       ? get_current_user_id() : $user_id;
	$post_id = empty( $post_id ) && is_object( $post ) ? $post->ID             : $post_id;

	$has_access = true;

	if ( ! empty( $post_id ) ) {

		$is_post_restricted = edd_cr_is_restricted( $post_id );

		if ( $is_post_restricted ) {
			$user_has_access = edd_cr_user_can_access( $user_id, $is_post_restricted, $post_id );
			$has_access      = $user_has_access['status'] == false  ? false : true;
		}

	}

	return apply_filters( 'ecc_cr_user_has_access', $has_access );

}
