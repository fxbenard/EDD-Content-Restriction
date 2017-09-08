<?php
/**
 * Add Meta Boxes
 *
 * @package     EDD\ContentRestriction\Metabox
 * @copyright   Copyright (c) 2013-2014, Pippin Williamson
 * @since       1.0.0
 */


// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Register meta box
 *
 * @since       2.0
 * @global      object $post The post/page we are editing
 * @return      void
 */
function edd_cr_add_meta_box() {
	global $post;

	$included_types = apply_filters( 'edd_cr_included_post_types', get_post_types( array( 'show_ui' => true, 'public' => true ) ) );
	$excluded_types = apply_filters( 'edd_cr_excluded_post_types', array( 'download', 'edd_payment', 'reply', 'acf', 'deprecated_log', 'edd-checkout-fields', 'fes-forms' ) );
	$post_type      = get_post_type( $post->ID );

	if ( in_array( $post_type, $included_types ) && ! in_array( $post_type, $excluded_types ) ) {
		add_meta_box(
			'content-restriction',
			__( 'Content Restriction', 'edd-cr' ),
			'edd_cr_render_meta_box',
			'',
			'normal',
			'default'
		);
	}
}
add_action( 'add_meta_boxes', 'edd_cr_add_meta_box' );


/**
 * Render metabox
 *
 * @since       2.0
 * @param       int $post_id The ID of the post we are editing
 * @global      object $post The post/page we are editing
 * @return      void
 */
function edd_cr_render_meta_box( $post_id ) {
	global $post;

	$downloads     = get_posts( array( 'post_type' => 'download', 'posts_per_page' => -1 ) );
	$restricted_to = get_post_meta( $post->ID, '_edd_cr_restricted_to', true );
	$message       = get_post_meta( $post->ID, '_edd_cr_restricted_message', true );

	if ( $downloads ) {
		?>
		<div id="edd-cr-options" class="edd_meta_table_wrap">
			<p><strong><?php echo sprintf( __( 'Restrict this content to buyers of one or more %s.', 'edd-cr' ), strtolower( edd_get_label_plural() ) ); ?></strong></p>
			<table class="widefat edd_repeatable_table" width="100%" cellpadding="0" cellspacing="0">
				<thead>
					<th><?php echo edd_get_label_singular(); ?></th>
					<th><?php echo sprintf( __( '%s Variation', 'edd-cr' ), edd_get_label_singular() ); ?></th>
					<?php do_action( 'edd_cr_table_head', $post_id ); ?>
					<th style="width: 2%"></th>
				</thead>
				<tbody>
					<?php
					if( ! empty( $restricted_to ) && is_array( $restricted_to ) ) {
						foreach( $restricted_to as $key => $value ) {
							echo '<tr class="edd-cr-option-wrapper edd_repeatable_row" data-key="' . absint( $key ) . '">';
							do_action( 'edd_cr_render_option_row', $key, $post_id );
							echo '</tr>';
						}
					} else {
						echo '<tr class="edd-cr-option-wrapper edd_repeatable_row">';
						do_action( 'edd_cr_render_option_row', 0, $post_id );
						echo '</tr>';
					}
					?>
					<tr>
						<td class="submit" colspan="4" style="float: none; clear:both; background:#fff;">
							<a class="button-secondary edd_add_repeatable" style="margin: 6px 0;"><?php _e( 'Add New Download', 'edd-cr' ); ?></a>
						</td>
					</tr>
				</tbody>
			</table>
			<p>
				<label for="edd_cr_restricted_message"><strong><?php _e( 'Specify a custom restriction message for this content, or leave blank to use the global setting.', 'edd-cr' ); ?></strong></label>
				<?php wp_editor( wptexturize( stripslashes( $message ) ), 'edd_cr_restricted_message', array( 'textarea_name' => 'edd_cr_restricted_message', 'textarea_rows' => 5 ) ); ?>
			</p>
		</div>
		<?php

		echo wp_nonce_field( 'edd-cr-nonce', 'edd-cr-nonce' );
	}
}


/**
 * Individual Option Row
 *
 * Used to output a table row for each download.
 * Can be called directly, or attached to an action.
 *
 * @since       2.0
 * @param       int $key The unique key for this option row
 * @param       object $post The post we are editing
 * @return      void
 */
function edd_cr_render_option_row( $key, $post ) {
	$downloads     = get_posts( array( 'post_type' => 'download', 'posts_per_page' => -1 ) );
	$restricted_to = get_post_meta( $post->ID, '_edd_cr_restricted_to', true );
	$download_id   = isset( $restricted_to[ $key ]['download'] ) ? $restricted_to[ $key ]['download'] : 0;
	?>
	<td>
		<select name="edd_cr_download[<?php echo $key; ?>][download]" id="edd_cr_download[<?php echo $key; ?>][download]" class="edd_cr_download" data-key="<?php echo esc_attr( $key ); ?>">
			<option value=""><?php echo __( 'None', 'edd-cr' ); ?></option>
			<option value="any"<?php selected( 'any', $download_id ); ?>><?php echo sprintf( __( 'Customers who have purchased any %s', 'edd-cr' ), edd_get_label_singular() ); ?></option>
			<?php
			foreach ( $downloads as $download ) {
				echo '<option value="' . absint( $download->ID ) . '" ' . selected( $download_id, $download->ID, false ) . '>' . esc_html( get_the_title( $download->ID ) ) . '</option>';
			}
			?>
		</select>
	</td>
	<td>
		<?php
		if ( isset( $restricted_to[ $key ]['price_id'] ) && edd_has_variable_prices( $restricted_to[ $key ]['download'] ) ) {
			$prices = edd_get_variable_prices( $restricted_to[ $key ]['download'] );
			echo '<select class="edd_price_options_select edd-select edd-select edd_cr_download" name="edd_cr_download[' . $key . '][price_id]">';
				echo '<option value="all" ' . selected( 'all', $restricted_to[ $key ]['price_id'], false ) . '>' . __( 'All prices', 'edd-cr' ) . '</option>';
				foreach ( $prices as $id => $data ) {
					echo '<option value="' . absint( $id ) . '" ' . selected( $id, $restricted_to[ $key ]['price_id'], false ) . '>' . esc_html( $data['name'] )  . '</option>';
				}
			echo '</select>';
			echo '<p class="edd_cr_variable_none" style="display: none;">' . __( 'None', 'edd-cr' ) . '</p>';
		} else {
			echo '<p class="edd_cr_variable_none">' . __( 'None', 'edd-cr' ) . '</p>';
		}
		?>
		<img src="<?php echo admin_url( '/images/wpspin_light.gif' ); ?>" class="waiting edd_cr_loading" style="display:none;"/>
	</td>
	<td>
		<a href="#" class="edd_remove_repeatable" data-type="price" style="background: url(<?php echo admin_url('/images/xit.gif'); ?>) no-repeat;">&times;</a>
	</td>
	<?php

	do_action( 'edd_cr_metabox', $post->ID, $restricted_to, null );
}
add_action( 'edd_cr_render_option_row', 'edd_cr_render_option_row', 10, 3 );


/**
 * Save metabox data
 *
 * @since       1.0.0
 * @param       int $post_id The ID of this post
 * @return      void
 */
function edd_cr_save_meta_data( $post_id ) {
	// Return if nothing to update
	if ( ! isset( $_POST['edd_cr_download'] ) || ! is_array( $_POST['edd_cr_download'] ) ) {
		return;
	}

	// Return if nonce validation fails
	if ( ! isset( $_POST['edd-cr-nonce'] ) || ! wp_verify_nonce( $_POST['edd-cr-nonce'], 'edd-cr-nonce' ) ) {
		return;
	}

	if ( ! empty( $_POST['edd_cr_download'] ) ) {

		// Grab the items this post was previously restricted to and remove related meta
		$previous_items = get_post_meta( $post_id, '_edd_cr_restricted_to', true );

		if ( $previous_items ) {
			foreach ( $previous_items as $item ) {
				if( 'any' !== $item['download'] ) {
					delete_post_meta( $item['download'], '_edd_cr_protected_post', $post_id );
				}
			}
		}

		$has_items = false;

		foreach ( $_POST['edd_cr_download'] as $item ) {
			if ( 'any' !== $item['download'] && ! empty( $item['download'] ) ) {
				$saved_ids = get_post_meta( $item['download'], '_edd_cr_protected_post' );

				if ( ! in_array( $post_id, $saved_ids ) ) {
					add_post_meta( $item['download'], '_edd_cr_protected_post', $post_id );
				}

				$has_items = true;
			} elseif ( 'any' == $item['download'] ) {
				$has_items = true;
			}
		}

		if ( $has_items ) {
			update_post_meta( $post_id, '_edd_cr_restricted_to', $_POST['edd_cr_download'] );
		} else {
			delete_post_meta( $post_id, '_edd_cr_restricted_to' );
		}
	} else {
		delete_post_meta( $post_id, '_edd_cr_restricted_to' );
	}

	if ( ! empty( $_POST['edd_cr_restricted_message'] ) ) {
		update_post_meta( $post_id, '_edd_cr_restricted_message', trim( wp_kses_post( $_POST['edd_cr_restricted_message'] ) ) );
	} else {
		delete_post_meta( $post_id, '_edd_cr_restricted_message' );
	}

	do_action( 'edd_cr_save_meta_data', $post_id, $_POST );
}
add_action( 'save_post', 'edd_cr_save_meta_data' );
