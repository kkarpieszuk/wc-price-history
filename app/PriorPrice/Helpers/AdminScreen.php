<?php

namespace PriorPrice\Helpers;

/**
 * Admin screen detection helpers.
 *
 * @since 3.2.8
 */
class AdminScreen {

	/**
	 * Whether the current screen is a classic product edit screen.
	 *
	 * @since 3.2.8
	 *
	 * @return bool
	 */
	public static function is_product_edit_page(): bool {
		$screen = self::get_screen();

		return $screen
			&& $screen->post_type === 'product'
			&& $screen->base === 'post';
	}

	/**
	 * Whether the current request is a WooCommerce Admin (wc-admin) page.
	 *
	 * @since 3.2.8
	 *
	 * @return bool
	 */
	public static function is_wc_admin_page(): bool {
		return isset( $_GET['page'] ) && $_GET['page'] === 'wc-admin'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}

	/**
	 * Whether the current screen is the products list.
	 *
	 * @since 3.2.8
	 *
	 * @return bool
	 */
	public static function is_products_list_page(): bool {
		$screen = self::get_screen();

		return $screen
			&& $screen->post_type === 'product'
			&& $screen->base === 'edit';
	}

	/**
	 * Whether the current screen is the plugin settings page.
	 *
	 * @since 3.2.8
	 *
	 * @return bool
	 */
	public static function is_settings_page(): bool {
		return isset( $_GET['page'] ) && $_GET['page'] === 'wc-price-history'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}

	/**
	 * Get product ID being edited on classic or block product editor.
	 *
	 * @since 3.2.8
	 *
	 * @return int
	 */
	public static function get_edited_product_id(): int {
		if ( isset( $_GET['post'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$classic_id = \absint( $_GET['post'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( $classic_id > 0 ) {
				return $classic_id;
			}
		}

		return self::get_block_product_id();
	}

	/**
	 * Parse product ID from WC Admin path query arg on the current request.
	 *
	 * @since 3.2.8
	 *
	 * @return int
	 */
	private static function get_block_product_id(): int {
		if ( ! isset( $_GET['path'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return 0;
		}

		$path = \wp_unslash( $_GET['path'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$path = rawurldecode( (string) $path );

		if ( ! preg_match( '#(?:^|/)product/(\d+)(?:/|$)#', $path, $matches ) ) {
			return 0;
		}

		return \absint( $matches[1] );
	}

	/**
	 * Get current admin screen when available.
	 *
	 * @since 3.2.8
	 *
	 * @return \WP_Screen|null
	 */
	private static function get_screen() {
		if ( ! function_exists( 'get_current_screen' ) ) {
			return null;
		}

		$screen = get_current_screen();

		return $screen ?: null;
	}
}
