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
 * Add settings
 *
 * @since       2.2.0
 * @param       array $sections The existing extensions sections
 * @return      array The modified extensions settings
 */
function edd_cr_add_sl_settings( $settings ) {
	$new_settings = array(
		array(
			'id'    => 'edd_cr_sl_enforce_active_license',
			'name'  => __( 'Enforce Active Licenses', 'edd-cr' ),
			'desc'  => __( 'Require an active license for licensed products.', 'edd-cr' ),
			'type'  => 'checkbox'
			)
	);

	return array_merge( $settings, $new_settings );
}
add_filter( 'edd_cr_settings', 'edd_cr_add_sl_settings' );


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
	if ( $has_access && edd_get_option( 'edd_cr_sl_enforce_active_license', false ) ) {
		if ( $restricted_to && is_array( $restricted_to ) ) {
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
				if ( $user_licenses ) {
					foreach ( $licensed as $item => $data ) {
						foreach( $user_licenses as $license_item => $license_data ) {
							$license_download = edd_software_licensing()->get_download_id( $license_data->ID );

							if ( $license_download == $data['download'] ) {
								if ( ! empty( $data['price_id'] ) ) {
									$license_price_id = edd_software_licensing()->get_price_id( $license_data->ID );

									if ( $license_price_id == $data['price_id'] ) {
										// Make sure the license is active
										$status = edd_software_licensing()->get_license_status( $license_data->ID );

										if ( $status == 'expired' ) {
											unset( $licensed[ $item ] );
										}
									}
								} else {
									// Make sure the license is active
									$status = edd_software_licensing()->get_license_status( $license_data->ID );

									if ( $status == 'expired' ) {
										unset( $licensed[ $item ] );
									}
								}
							}
						}
					}

					// If no licensed products remain, set to false
					if ( count( $licensed ) == 0 ) {
						$has_access = false;
					}
				}
			}
		}
	}

	return $has_access;
}
add_filter( 'edd_cr_user_can_access', 'edd_cr_user_has_license', 10, 3 );
