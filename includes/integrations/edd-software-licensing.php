<?php
/**
 * Software Licensing Integration
 *
 * @package     EDD\ContentRestriction\Integrations\SoftwareLicensing
 * @copyright   Copyright (c) 2013-2016, Pippin Williamson
 * @since       2.2.0
 */


// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Add metabox field
 *
 * @since       2.2.0
 * @param       int $post_id The ID of the post we are editing
 * @return      void
 */
function edd_cr_add_sl_metabox_field( $post_id, $restricted_to, $restricted_variable ) {
	$active_license = get_post_meta( $post_id, '_edd_cr_sl_require_active_license', true );
	echo '<p>';
		echo '<label for="edd_cr_sl_require_active_license">';
			echo '<input type="checkbox" name="edd_cr_sl_require_active_license" id="edd_cr_sl_require_active_license" value="1"' . checked( '1', $active_license, false ) . '/>&nbsp;';
			echo __( 'Require an active license key?', 'edd-sl' );
			echo '<span class="edd-help-tip dashicons dashicons-editor-help" alt="f223" title="<strong>' . __( 'Require an active license key?', 'edd-cr' ) . '</strong> ' . sprintf( __( 'Only customers with an active license will be able to view the content. This setting is only applied if the selected %s has licensing enabled.', 'edd-sl' ), edd_get_label_singular( true ) ) . '"></span>';
		echo '</label>';
	echo '</p>';
}
add_action( 'edd_cr_metabox', 'edd_cr_add_sl_metabox_field', 10, 3 );


/**
 * Update data on save
 *
 * @since       2.2.0
 * @param       int $post_id The ID of the post we are editing
 * @param       array $data The submitted data
 * @return      void
 */
function edd_cr_sl_metabox_save( $post_id, $data ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( isset( $data['edd_cr_sl_require_active_license'] ) ) {
		update_post_meta( $post_id, '_edd_cr_sl_require_active_license', '1' );
	} else {
		delete_post_meta( $post_id, '_edd_cr_sl_require_active_license' );
	}
}
add_action( 'edd_cr_save_meta_data', 'edd_cr_sl_metabox_save', 10, 2 );


/**
 * Override access depending on license status
 *
 * @since       2.2.0
 * @param       bool $has_access Whether or not the user has access
 * @param       int $user_id The ID of the user
 * @param       array $restricted_to The array of downloads for a post/page
 * @return      bool $has_access The updated access condition for the user
 */
function edd_cr_user_has_license( $has_access, $user_id, $restricted_to ) {
	$licensed = array();

	// Only proceed if the setting is enabled
	if ( ! $has_access || ! get_post_meta( get_the_ID(), '_edd_cr_sl_require_active_license', true ) ) {

		return $has_access;

	}

	if( ! $restricted_to || ! is_array( $restricted_to ) ) {

		return $has_access;

	}

	foreach ( $restricted_to as $item => $data ) {

		// Only proceed if licensing is enabled for this download
		if ( get_post_meta( $data['download'], '_edd_sl_enabled', true ) ) {

			// Enforce author access
			if ( (int) get_post_field( 'post_author', $data['download'] ) !== (int) $user_id && is_user_logged_in() ) {
				$licensed[] = $data;
			}
		}
	}

	// Only proceed if there are licensed products
	if ( count( $licensed )  > 0 ) {

		$user_licenses = edd_software_licensing()->get_license_keys_of_user( $user_id );

		// Only proceed if the user has actually purchased a license
		if ( empty( $user_licenses ) ) {

			return $has_access;

		}

		foreach ( $licensed as $item => $data ) {

			foreach( $user_licenses as $license_item => $license_data ) {

				$license_download = (int) edd_software_licensing()->get_download_id( $license_data->ID );

				if ( $license_download !== (int) $data['download'] ) {

					continue;

				}

				if ( ! empty( $data['price_id'] ) ) {

					$license_price_id = edd_software_licensing()->get_price_id( $license_data->ID );

					if ( $license_price_id == $data['price_id'] ) {

						// Make sure the license is active
						$license_status = edd_software_licensing()->get_license_status( $license_data->ID );
						$post_status    = get_post_status( $license_data->ID );

						if ( $license_status === 'expired' || $post_status === 'draft' ) {
							unset( $licensed[ $item ] );
						}
					}

				} else {

					// Make sure the license is active
					$license_status = edd_software_licensing()->get_license_status( $license_data->ID );
					$post_status    = get_post_status( $license_data->ID );

					if ( $license_status === 'expired' || $post_status === 'draft' ) {

						unset( $licensed[ $item ] );

					}

				}

			}

		}

		// If no licensed products remain, set to false
		if ( count( $licensed ) == 0 ) {
			$has_access = false;
		}
	}

	return $has_access;
}
add_filter( 'edd_cr_user_can_access', 'edd_cr_user_has_license', 10, 3 );
