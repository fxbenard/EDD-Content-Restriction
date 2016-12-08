<?php
/**
 * Handle upgrades
 *
 * @package     EDD\ContentRestriction\Upgrades
 * @copyright   Copyright (c) 2013-2014, Pippin Williamson
 * @since       2.2.0
 */


// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Shows upgrade notices
 *
 * @since       2.0.0
 * @return      void
*/
function edd_cr_upgrade_notices() {
	if ( ! current_user_can( 'manage_shop_settings' ) ) {
		return;
	}

	if ( ! empty( $_GET['page'] ) && 'edd-upgrades' == $_GET['page'] ) {
		return;
	}

	$version = get_option( 'eddcr_version' );

	if ( ! $version || version_compare( $version, '2.0', '<' ) ) {
		printf(
			'<div class="updated"><p>' . esc_html__( 'Easy Digital Downloads needs to upgrade the Content Restriction settings, click %shere%s to start the upgrade.', 'edd-cr' ) . '</p></div>',
			'<a href="' . esc_url( admin_url( 'index.php?page=edd-upgrades&edd-upgrade=upgrade_cr_post_meta' ) ) . '">',
			'</a>'
		);
	}

}
add_action( 'admin_notices', 'edd_cr_upgrade_notices' );


/**
 * Upgrades all commission records to use a taxonomy for tracking
 * the status of the commission
 *
 * @since 2.0.0
 * @return void
 */
function edd_cr_upgrade_post_meta() {
	if ( ! current_user_can( 'manage_shop_settings' ) ) {
		return;
	}

	define( 'EDDCR_DOING_UPGRADES', true );

	ignore_user_abort( true );

	if ( ! edd_is_func_disabled( 'set_time_limit' ) && ! ini_get( 'safe_mode' ) ) {
		set_time_limit( 0 );
	}

	$step = isset( $_GET['step'] ) ? absint( $_GET['step'] )  : 1;

	$args = array(
		'posts_per_page' => 20,
		'paged'          => $step,
		'status'         => 'any',
		'order'          => 'ASC',
		'post_type'      => 'any',
		'fields'         => 'ids',
		'meta_key'       => '_edd_cr_restricted_to'
	);

	$items = get_posts( $args );

	if ( $items ) {

		// Items found so upgrade them
		foreach ( $items as $post_id ) {
			$restricted_to = get_post_meta( $post_id, '_edd_cr_restricted_to', true );
			$price_id      = get_post_meta( $post_id, '_edd_cr_restricted_to_variable', true );

			$args   = array();
			$args[] = array(
				'download' => $restricted_to,
				'price_id' => $price_id
			);

			update_post_meta( $post_id, '_edd_cr_restricted_to', $args );

			add_post_meta( $restricted_to, '_edd_cr_protected_post', $post_id );
		}

		$step++;

		$redirect = add_query_arg( array(
			'page'        => 'edd-upgrades',
			'edd-upgrade' => 'upgrade_cr_post_meta',
			'step'        => $step
		), admin_url( 'index.php' ) );

		wp_safe_redirect( $redirect ); exit;
	} else {
		// No more items found, finish up
		update_option( 'eddcr_version', EDD_CONTENT_RESTRICTION_VER );
		delete_option( 'edd_doing_upgrade' );

		wp_redirect( admin_url() ); exit;
	}

}
add_action( 'edd_upgrade_cr_post_meta', 'edd_cr_upgrade_post_meta' );
