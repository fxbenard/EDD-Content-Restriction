<?php
/**
 * Register settings
 *
 * @package     EDD\ContentRestriction\Settings
 * @copyright   Copyright (c) 2013-2014, Pippin Williamson
 * @since       2.2.0
 */


// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Add settings section
 *
 * @since       2.2.0
 * @param       array $sections The existing extensions sections
 * @return      array The modified extensions settings
 */
function edd_cr_add_settings_section( $sections ) {
	$sections['content-restriction'] = __( 'Content Restriction', 'edd-cr' );

	return $sections;
}
add_filter( 'edd_settings_sections_extensions', 'edd_cr_add_settings_section' );


/**
 * Add settings
 *
 * @since       2.2.0
 * @param       array $settings The current plugin settings
 * @return      array The modified plugin settings
 */
function edd_cr_add_settings( $settings ) {
	$new_settings = array(
		'content-restriction' => apply_filters( 'edd_cr_settings', array(
			array(
				'id'    => 'edd_cr_settings',
				'name'  => '<strong>' . __( 'Content Restriction Settings', 'edd-cr' ) . '</strong>',
				'desc'  => '',
				'type'  => 'header'
			),
			array(
				'id'    => 'edd_content_restriction_hide_menu_items',
				'name'  => __( 'Hide Menu Items', 'edd-cr' ),
				'desc'  => __( 'Should we hide menu items a user doesn\'t have access to?', 'edd-cr' ),
				'type'  => 'checkbox',
			),
			array(
				'id'    => 'edd_content_restriction_include_bundled_products',
				'name'  => __( 'Include Bundled Products', 'edd-cr' ),
				'desc'  => __( 'Should products purchased as part of a bundle be considered purchased when determining access rights?', 'edd-cr' ),
				'type'  => 'checkbox'
			),
			array(
				'id'          => 'edd_cr_single_resriction_message',
				'name'        => __( 'Single Restriction Message', 'edd-cr' ),
				'desc'        => __( 'When access is restricted by a single product, this message will show to the user when they do not have access. <code>{product_name}</code> will be replaced by the restriction requirements.', 'edd-cr' ),
				'type'        => 'rich_editor',
				'allow_blank' => false,
				'size'        => 5,
				'std'         => edd_cr_get_single_restriction_message(),
			),
			array(
				'id'          => 'edd_cr_multi_resriction_message',
				'name'        => __( 'Multiple Restriction Message', 'edd-cr' ),
				'desc'        => __( 'When access is restricted by multiple products, this message will show to the user when they do not have access. <code>{product_names}</code> will be replaced by a list of the restriction requirements.', 'edd-cr' ),
				'type'        => 'rich_editor',
				'allow_blank' => false,
				'size'        => 5,
				'std'         => edd_cr_get_multi_restriction_message(),
			),
			array(
				'id'          => 'edd_cr_any_resriction_message',
				'name'        => __( 'Restriction for "Any Product"', 'edd-cr' ),
				'desc'        => __( 'When access to content is restricted to anyone who has made a purchase, this is the message displayed to people without a purchase.', 'edd-cr' ),
				'type'        => 'rich_editor',
				'allow_blank' => false,
				'size'        => 5,
				'std'         => edd_cr_get_any_restriction_message(),
			)
		) )
	);

	return array_merge( $settings, $new_settings );
}
add_filter( 'edd_settings_extensions', 'edd_cr_add_settings' );
