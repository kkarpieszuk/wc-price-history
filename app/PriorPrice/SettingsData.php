<?php

namespace PriorPrice;

class SettingsData {

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

	public function get_count_from() {
$settings = get_option( 'wc_price_history_settings' );
		if ( ! isset( $settings['count_from'] ) ) {
			return 'sale_start';
		}
		return $settings['count_from'];
	}
}
