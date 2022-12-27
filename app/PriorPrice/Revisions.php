<?php

namespace PriorPrice;

class Revisions {

	/**
	 * Update $has_changed flag when user is updating product's price or sale price.
	 *
	 * If user updates only product price, WordPress does not save post revision by default.
	 * Setting $has_changed to true in that case makes WP to save revision and allows us to track prices.
	 *
	 * @since 1.0
	 *
	 * @param bool     $has_changed     Flag indicating if post has changed.
	 * @param \WP_Post $latest_revision Latest post revision.
	 * @param \WP_Post $post            Current version of the post.
	 *
	 * @return true
	 */
	public function post_has_changed( $has_changed, $latest_revision, $post ): bool {

		if ( get_post_type( $post ) !== 'product' ) {
			return $has_changed;
		}

		if ( ! isset( $_POST['_regular_price'] ) && ! isset( $_POST['_sale_price'] ) ) {
			return $has_changed;
		}

		$product_price = (float) get_post_meta( $post->ID, '_regular_price', true );
		$updated_price = (float) $_POST['_regular_price'];
		$sale_price    = (float) get_post_meta( $post->ID, '_sale_price', true );
		$updated_sale  = (float) $_POST['_sale_price'];

		if ( $product_price !== $updated_price || $sale_price !== $updated_sale ) {
			return true;
		}

		return $has_changed;
	}

	/**
	 * Enable product revisions.
	 *
	 * @since 1.0
	 *
	 * @param array $args Register post type args.
	 *
	 * @return array
	 */
	public function enable_product_revisions( $args ): array {

		$args['supports'][] = 'revisions';

		return $args;
	}

	/**
	 * Save price revision.
	 *
	 * @since 1.0
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return void
	 */
	public function save_price_revision( $post_id ): void {

		$parent_id = wp_is_post_revision( $post_id );

		if ( ! $parent_id ) {
			return;
		}

		$parent = get_post( $parent_id );
		$_price = get_post_meta( $parent->ID, '_price', true );

		if ( false !== $_price ) {
			add_metadata( 'post', $post_id, '_price', $_price );
		}
	}
}
