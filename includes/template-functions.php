<?php
/**
 * Add Template Overrides
 *
 * @package     EDD\ContentRestriction\TemplateFunctions
 * @copyright   Copyright (c) 2013-2014, Pippin Williamson
 * @since       2.2.0
 */


// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Check if a post/page is restricted
 *
 * @since       1.0.0
 * @param       int $post_id the ID of the post to check
 * @return      bool True if post is restricted, false otherwise
 */
function edd_cr_is_restricted( $post_id ) {
	$restricted = get_post_meta( $post_id, '_edd_cr_restricted_to', true );

	return $restricted;
}


/**
 * Filter restricted content
 *
 * @since       1.0.0
 * @param       string $content The content to filter
 * @param       array $restricted The items to which this is restricted
 * @param       string $message The message to display to users
 * @param       int $post_id The ID of the current post/page
 * @param       string $class Additional classes for the displayed error
 * @global      int $user_ID The ID of the current user
 * @return      string $content The content to display to the user
 */
function edd_cr_filter_restricted_content( $content = '', $restricted = false, $message = null, $post_id = 0, $class = '' ) {
	global $user_ID;

	// If the current user can edit this post, it can't be restricted!
	if ( ! current_user_can( 'edit_post', $post_id ) && $restricted ) {
		$has_access = edd_cr_user_can_access( $user_ID, $restricted, $post_id );

		if ( $has_access['status'] == false ) {
			if ( ! empty( $message ) ) {
				$has_access['message'] = $message;
			}

			$content = '<div class="edd_cr_message ' . $class . '">' . $has_access['message'] . '</div>';
		}
	}

	return do_shortcode( $content );
}


/**
 * Get the message to display to people who have not purchased
 * the necessary product to view the content
 *
 * @since       2.1
 * @return      string $message The message for non-purchases of a product
 */
function edd_cr_get_single_restriction_message() {
	$default_message = sprintf( __( 'This content is restricted to buyers of %s.', 'edd-cr' ), '{product_name}' );
	$saved_message   = edd_get_option( 'edd_cr_single_resriction_message', false );
	$message         = ! empty( $saved_message ) ? $saved_message : $default_message;

	return wpautop( $message );
}


/**
 * Get the message to display to people who have not purchased
 * the necessary product(s) to view the content
 *
 * @since       2.1
 * @return      string $message The message for non-purchases of the products
 */
function edd_cr_get_multi_restriction_message() {
	$default_message = sprintf( __( 'This content is restricted to buyers of:' . "\n\n" . '%s', 'edd-cr' ), '{product_names}' );
	$saved_message   = edd_get_option( 'edd_cr_multi_resriction_message', false );
	$message         = ! empty( $saved_message ) ? $saved_message : $default_message;

	return wpautop( $message );
}


/**
 * Get the message to display to people who have not purchased any products
 *
 * @since       2.1
 * @return      string $message The message for non-purchases
 */
function edd_cr_get_any_restriction_message() {
	$default_message = __( 'If you want to view this content, you need to buy any product.' );
	$saved_message   = edd_get_option( 'edd_cr_any_resriction_message', false );
	$message         = ! empty( $saved_message ) ? $saved_message : $default_message;

	return wpautop( $message );
}


/**
 * Add restricted content to confirmation page
 *
 * @since       1.3.0
 * @param       object $payment The payment we are processing
 * @param       array $edd_receipt_args The args for a given receipt
 * @return      void
 */
function edd_cr_add_to_receipt( $payment, $edd_receipt_args ) {
	// Get the array of restricted pages for this payment
	$meta = edd_cr_get_restricted_pages( $payment->ID );

	// No pages? Quit!
	if ( empty( $meta ) ) {
		return;
	}
	?>

	<h3><?php echo apply_filters( 'edd_cr_payment_receipt_pages_title', __( 'Pages', 'edd-cr' ) ); ?></h3>

	<table id="edd_purchase_receipt_pages" class="edd-table">
		<thead>
			<tr>
				<th><?php _e( 'Product', 'edd-cr' ); ?></th>
				<th><?php _e( 'Pages', 'edd-cr' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $meta as $download_id => $pages ) : ?>
				<tr>
					<td class="edd_purchase_receipt_pages_download">
						<?php echo get_the_title( $download_id ); ?>
					</td>
					<td class="edd_purchase_receipt_pages">
						<ul>
							<?php foreach ( $pages as $page_id => $page_title ) : ?>
								<li><a href="<?php echo esc_url( get_permalink( $page_id ) ); ?>"><?php echo $page_title; ?></a></li>
							<?php endforeach; ?>
						</ul>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
	<?php
}
add_action( 'edd_payment_receipt_after_table', 'edd_cr_add_to_receipt', 1, 2 );
