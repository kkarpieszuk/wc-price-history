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
	public function set_defaults() : void {

		$update   = false;
		$settings = get_option( 'wc_price_history_settings' );

		// Handle settings added in 1.2.
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
			$update   = true;
		}

		// Handle settings added in 1.3.
		if ( ! isset( $settings['display_text'] ) || $settings['display_text'] === '30-day low: %s' ) {
			/* translators: Do not translate {price}, it is template slug! */
			$settings['display_text'] = esc_html__( '30-day low: {price}', 'wc-price-history' );
			$update                   = true;
		}

		if ( ! isset( $settings['old_history'] ) ) {
			$settings['old_history']             = 'custom_text'; // 'hide', 'current_price', 'custom_text'
			$settings['old_history_custom_text'] = esc_html__( 'Price in the last {days} days is the same as current', 'wc-price-history' );
			$update                              = true;
		}

		if ( $update ) {
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

	/**
	 * Get display_text setting.
	 *
	 * @since 1.3
	 *
	 * @return string
	 */
	public function get_display_text() : string {

		$settings = get_option( 'wc_price_history_settings' );
		if ( ! isset( $settings['display_text'] ) ) {
			/* translators: %s - the lowest price in the last 30 days. */
			$old_format = esc_html__( '30-day low: %s', 'wc-price-history' );
			$with_placeholders = str_replace( [ '30', '%s' ], [ '{days}', '{price}' ], $old_format );
			return $with_placeholders;
		}
		return esc_html( $settings['display_text'] );
	}

	/**
	 * Get display line through setting.
	 *
	 * @since 1.7
	 *
	 * @return bool
	 */
	public function get_display_line_through() : bool {

		$settings = get_option( 'wc_price_history_settings' );
		if ( ! isset( $settings['display_line_through'] ) ) {
			return false;
		}
		return (bool) $settings['display_line_through'];
	}

	/**
	 * Get old history setting.
	 *
	 * @since 1.9.0
	 *
	 * @return string
	 */
	public function get_old_history() : string {

		$settings = get_option( 'wc_price_history_settings' );
		if ( ! isset( $settings['old_history'] ) ) {
			return 'hide';
		}
		return $settings['old_history'];
	}

	/**
	 * Get old history custom text setting.
	 *
	 * @since 1.9.0
	 *
	 * @return string
	 */
	public function get_old_history_custom_text() : string {

		$settings = get_option( 'wc_price_history_settings' );
		if ( ! isset( $settings['old_history_custom_text'] ) ) {
			return esc_html__( 'Price in the last {days} days is the same as current', 'wc-price-history' );
		}
		return esc_html( $settings['old_history_custom_text'] );
	}
}
