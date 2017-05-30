<?php
/**
 * User functions
 *
 * @package     EDD\ContentRestriction\UserFunctions
 * @copyright   Copyright (c) 2013-2014, Pippin Williamson
 * @since       2.2.0
 */


// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Check to see if a user has access to a post/page
 *
 * @since       2.0
 * @param       int|bool $user_id Optional. The ID of the user to check. Default is false.
 * @param       array $restricted_to The array of downloads for a post/page
 * @param       int|false $post_id Optional. The ID of the object we are viewing. Default is false.
 * @return      array $return An array containing the status and optional message
 */
function edd_cr_user_can_access( $user_id = false, $restricted_to, $post_id = false ) {

	$message          = '';
	$has_access       = false;
	$restricted_count = count( $restricted_to );
	$products         = array();

	// If no user is given, use the current user
	if ( ! $user_id ) {
		$user_id = get_current_user_id();
	}

	// bbPress specific checks. Moderators can see everything
	if ( class_exists( 'bbPress' ) && current_user_can( 'moderate' ) ) {
		$has_access = true;
	}

	// Admins have full access
	if ( current_user_can( 'manage_options' ) ) {
		$has_access = true;
	}

	// The post author can always access
	if ( $post_id && current_user_can( 'edit_post', $post_id ) ) {
		$has_access = true;
	}

	if ( $restricted_to && is_array( $restricted_to ) && ! $has_access ) {
		foreach ( $restricted_to as $item => $data ) {
			if ( empty( $data['download'] ) ) {
				$has_access = true;
			}

			// The author of a download always has access
			if ( (int) get_post_field( 'post_author', $data['download'] ) === (int) $user_id && is_user_logged_in() ) {
				$has_access = true;
				break;
			}

			// If restricted to any customer and user has purchased something
			if ( 'any' === $data['download'] && edd_has_purchases( $user_id ) && is_user_logged_in() ) {
				$has_access = true;
				break;
			} elseif ( 'any' === $data['download'] ) {
				$has_access = false;
				break;
			}

			// Check for variable prices
			if ( ! $has_access ) {
				if ( edd_has_variable_prices( $data['download'] ) ) {
					if ( strtolower( $data['price_id'] ) !== 'all' && ! empty( $data['price_id'] ) ) {
						$products[] = '<a href="' . get_permalink( $data['download'] ) . '">' . get_the_title( $data['download'] ) . ' - ' . edd_get_price_option_name( $data['download'], $data['price_id'] ) . '</a>';

						if ( edd_has_user_purchased( $user_id, $data['download'], $data['price_id'] ) ) {
							$has_access = true;
						}
					} else {
						$products[] = '<a href="' . get_permalink( $data['download'] ) . '">' . get_the_title( $data['download'] ) . '</a>';

						if ( edd_has_user_purchased( $user_id, $data['download'] ) ) {
							$has_access = true;
						}
					}
				} else {
					$products[] = '<a href="' . get_permalink( $data['download'] ) . '">' . get_the_title( $data['download'] ) . '</a>';

					if ( is_user_logged_in() && edd_has_user_purchased( $user_id, $data['download'] ) ) {
						$has_access = true;
					}
				}
			}

			if ( ! $has_access && is_user_logged_in() && edd_get_option( 'edd_content_restriction_include_bundled_products', false ) ) {
				$purchased = edd_get_users_purchased_products( $user_id );

				foreach ( $purchased as $item ) {
					if ( edd_is_bundled_product( $item->ID ) ) {
						$bundled = (array) edd_get_bundled_products( $item->ID );

						if ( in_array( $data['download'], $bundled ) ) {
							$has_access = true;
							break;
						}
					}
				}
			}
		}

		if ( $has_access == false ) {
			if ( $restricted_count > 1 ) {
				$message      = edd_cr_get_multi_restriction_message();
				$product_list = '';

				if ( ! empty( $products ) ) {
					$product_list .= '<ul>';

					foreach ( $products as $id => $product ) {
						$product_list .= '<li>' . $product . '</li>';
					}

					$product_list .= '</ul>';
				}

				$message = str_replace( '{product_names}', $product_list, $message );
			} else {
				if ( 'any' === $data['download'] ) {
					$message = edd_cr_get_any_restriction_message();
				} else {
					$message = edd_cr_get_single_restriction_message();
					$message = str_replace( '{product_name}', $products[0], $message );
				}
			}
		}

		// Override message if per-content message is defined
		$content_message = get_post_meta( $post_id, '_edd_cr_restricted_message', true );
		$message         = ( $content_message && $content_message !== '' ? $content_message : $message );

		if ( ! isset( $message ) ) {
			$message = __( 'This content is restricted to buyers.', 'edd-cr' );
		}
	} else {
		// Just in case we're checking something unrestricted...
		$has_access = true;
	}

	// Allow plugins to modify the restriction requirements
	$return['status']  = apply_filters( 'edd_cr_user_can_access', $has_access, $user_id, $restricted_to );
	$return['message'] = apply_filters( 'edd_cr_user_can_access_message', $message, $user_id, $restricted_to );

	return $return;
}


/**
 * Determine if a user has permission to view the currently viewed URL
 * Mainly for use in template files
 *
 * @since       2.1
 * @param       int $user_id The User ID to check (defaults to logged in user)
 * @param       int $post_id The Post ID to check access for (defaults to current post)
 * @return      bool If the current user has permission to view the current URL
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
