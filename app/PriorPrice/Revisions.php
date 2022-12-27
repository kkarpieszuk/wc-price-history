<?php

namespace PriorPrice;

class Revisions {

	public function post_has_changed( $has_changed, $latest_revision, $post ) {

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

	public function enable_product_revisions( $args ) {

		$args['supports'][] = 'revisions';

		return $args;
	}

	public function save_price_revision( $post_id ) {

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