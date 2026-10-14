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
