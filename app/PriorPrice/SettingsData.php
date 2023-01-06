<?php

namespace PriorPrice;

class SettingsData {

	/**
	 * Register hooks.
	 *
	 * @since 1.2
	 *
	 * @return void
	 */
	public function register_hooks() : void {
		add_action( 'admin_init', [ $this, 'set_defaults' ] );
	}

	/**
	 * Set default settings.
	 *
	 * @since 1.2
	 *
	 * @return void
	 */
	public function set_defaults() {
		$settings = get_option( 'wc_price_history_settings' );
		if ( $settings === false ) {
			$settings = [
				'display_on'   => [
					'product_page' => '1',
					'shop_page'    => '1',
				],
				'display_when' => 'on_sale',
				'days_number'  => '30',
				'count_from'   => 'sale_start',
			];
			update_option( 'wc_price_history_settings', $settings );
		}
	}

	/**
	 * Get the display on settings.
	 *
	 * @since 1.2
	 *
	 * @return array<array<bool>>
	 */
	public function get_display_on() : array {

		$settings = get_option( 'wc_price_history_settings' );
		if ( ! isset( $settings['display_on'] ) ) {
			return [];
		}
		return $settings['display_on'];
	}

	/**
	 * Get the display when settings.
	 *
	 * @since 1.2
	 *
	 * @return string
	 */
	public function get_display_when() : string {

		$settings = get_option( 'wc_price_history_settings' );
		if ( ! isset( $settings['display_when'] ) ) {
			return 'always';
		}
		return $settings['display_when'];
	}

	/**
	 * Get days settings.
	 *
	 * @since 1.2
	 *
	 * @return int
	 */
	public function get_days_number() : int {

		$settings = get_option( 'wc_price_history_settings' );
		if ( ! isset( $settings['days_number'] ) ) {
			return 30;
		}
		return (int) $settings['days_number'];
	}

	/**
	 * Get count from setting.
	 *
	 * @since 1.2
	 *
	 * @return string
	 */
	public function get_count_from() : string {

		$settings = get_option( 'wc_price_history_settings' );
		if ( ! isset( $settings['count_from'] ) ) {
			return 'sale_start';
		}
		return $settings['count_from'];
	}
}
