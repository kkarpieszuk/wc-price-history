<?php

namespace PriorPrice;

class Compatibility {

	/**
	 * Check WP revisions settings.
	 *
	 * @since 1.0
	 *
	 * @param \WC_Product $wc_product WC Product.
	 *
	 * @return bool
	 */
	public function check_revisions_settings( $wc_product ): bool {

		return wp_revisions_to_keep( get_post( $wc_product->get_id() ) ) === -1 || post_type_supports( 'product', 'revisions' );
	}

	/**
	 * Get incorrect revisions settings warning.
	 *
	 * @since 1.0
	 *
	 * @return string
	 */
	public function get_revisions_warning(): string {

		if ( ! current_user_can( 'manage_options' ) ) {
			return '';
		}

		return sprintf(
			'<div class="wc-price-history price-error revisions-disabled" style="color:red;font-style: italic">%s</div>',
			__( 'Lowest price display is not possible. Please enable WP revisions for WC products and set unlimited revisions numbers', 'wc-price-history' )
		);
	}
}
