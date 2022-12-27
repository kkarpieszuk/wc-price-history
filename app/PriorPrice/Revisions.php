<?php

namespace PriorPrice;

class Revisions {

	public function enable_product_revisions( $args ) {

		$args['supports'][] = 'revisions';

		return $args;
	}

	public function save_price_revision( $post_id ) {

		if ( get_post_type( $post_id ) !== 'product' ) {

			return;
		}

		$parent_id = wp_is_post_revision( $post_id );

		if ( ! $parent_id ) {

			return;
		}

		$parent  = get_post( $parent_id );
		$_regular_price = get_post_meta( $parent->ID, '_regular_price', true );

		if ( false !== $_regular_price ) {
			add_metadata( 'post', $post_id, '_regular_price', $_regular_price );
		}

	}

}