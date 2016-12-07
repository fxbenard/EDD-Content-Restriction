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
			echo __( 'Require a valid license key?', 'edd-sl' );
			echo '<span class="edd-help-tip dashicons dashicons-editor-help" alt="f223" title="<strong>' . __( 'Require a valid license key?', 'edd-cr' ) . '</strong> ' . sprintf( __( 'Only customers with a valid license key will be able to view the content. This setting is only applied if the selected %s has licensing enabled.', 'edd-sl' ), edd_get_label_singular( true ) ) . '"></span>';
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

	// Only proceed if the setting is enabled
	if ( ! $has_access || ! get_post_meta( get_the_ID(), '_edd_cr_sl_require_active_license', true ) ) {

		return $has_access;

	}

	if( ! $restricted_to || ! is_array( $restricted_to ) ) {

		return $has_access;

	}

	$user_licenses = edd_software_licensing()->get_license_keys_of_user( $user_id );

	// Only proceed if the user has actually purchased a license
	if ( empty( $user_licenses ) ) {

		return $has_access;

	}

	foreach ( $restricted_to as $item => $data ) {

		if( ! get_post_meta( $data['download'], '_edd_sl_enabled', true ) ) {

			// No need to check if licensing is not enabled on the download
			continue;

		}


		foreach( $user_licenses as $license_item => $license_data ) {

			$license_download = (int) edd_software_licensing()->get_download_id( $license_data->ID );

			if ( $license_download !== (int) $data['download'] ) {

				// License does not belong to the product we're checking
				continue;

			}

			// We have a related license so set access to false
			$has_access  = false;
			$status      = edd_software_licensing()->get_license_status( $license_data->ID );
			$post_status = get_post_status( $license_data->ID );

			if ( edd_has_variable_prices( $data['download'] ) ) {

				$license_price_id = (int) edd_software_licensing()->get_price_id( $license_data->ID );

				if ( ( 'all' === strtolower( $data['price_id'] ) || $license_price_id === (int) $data['price_id'] ) && 'expired' !== $status && 'draft' !== $post_status ) {
					$has_access = true;
					break;

				}

			} else {

				if( 'expired' !== $status && 'draft' !== $post_status ) {

					$has_access = true;
					break;

				}


			}

		}

	}

	return $has_access;
}
add_filter( 'edd_cr_user_can_access', 'edd_cr_user_has_license', 10, 3 );
