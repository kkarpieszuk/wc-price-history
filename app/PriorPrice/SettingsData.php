<?php

namespace PriorPrice;

class SettingsData {

	/**
	 * Cached REST API response flags; invalidated when settings are written.
	 *
	 * @var array{show_lowest_price: bool, show_full_history: bool}|null
	 */
	private $rest_api_response_flags_cache = null;

	/**
	 * Register hooks.
	 *
	 * @since 1.2
	 *
	 * @return void
	 */
	public function register_hooks() : void {
		add_action( 'admin_init', [ $this, 'set_defaults' ] );
		add_action( 'update_option_wc_price_history_settings', [ $this, 'bust_rest_api_response_flags_cache' ] );
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

		if ( ! isset( $settings[ FirstScan::OPTION_NAME ] ) ) {
			$settings[ FirstScan::OPTION_NAME ] = FirstScan::SCAN_NOT_STARTED;
			$update                             = true;
		}

		if ( ! isset( $settings['variable_product_defer_lowest_price'] ) ) {
			$settings['variable_product_defer_lowest_price'] = false;
			$update                                          = true;
		}

		if ( ! isset( $settings['variable_product_defer_placeholder_text'] ) ) {
			$settings['variable_product_defer_placeholder_text'] = esc_html__( 'Select a variant to see the lowest price', 'wc-price-history' );
			$update                                             = true;
		}

		if ( ! isset( $settings['rest_api_show_lowest_price'] ) ) {
			$settings['rest_api_show_lowest_price'] = false;
			$update                                 = true;
		}

		if ( ! isset( $settings['rest_api_show_full_history'] ) ) {
			$settings['rest_api_show_full_history'] = false;
			$update                                 = true;
		}

		if ( $update ) {
			update_option( 'wc_price_history_settings', $settings );
		}
	}

	/**
	 * Set all settings.
	 *
	 * @since 2.1.3
	 *
	 * @param array<string, mixed> $settings Settings.
	 *
	 * @return void
	 */
	public function set_all_settings( array $settings ): void {

		update_option( 'wc_price_history_settings', $settings );
	}

	/**
	 * Get all settings.
	 *
	 * @since 2.1.3
	 *
	 * @return array<string, mixed>
	 */
	public function get_settings(): array {

		return get_option( 'wc_price_history_settings' );
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

	/**
	 * Get old history custom text setting.
	 *
	 * @since 2.0
	 *
	 * @return int
	 */
	public function get_first_scan_status(): int {

		$settings = get_option( 'wc_price_history_settings' );

		if ( ! isset( $settings[ FirstScan::OPTION_NAME ] ) ) {
			return FirstScan::SCAN_NOT_STARTED;
		}

		return (int) $settings[ FirstScan::OPTION_NAME ];
	}

	/**
	 * Set first scan status.
	 *
	 * @since 2.0
	 *
	 * @param int $status Status.
	 *
	 * @return void
	 */
	public function set_first_scan_status( int $status ): void {

		$settings                           = get_option( 'wc_price_history_settings' );
		$settings[ FirstScan::OPTION_NAME ] = $status;

		update_option( 'wc_price_history_settings', $settings );
	}

	/**
	 * Get variable product defer lowest price setting.
	 *
	 * When true, lowest price for variable products is shown only after a variant is selected.
	 *
	 * @since 3.2.0
	 *
	 * @return bool
	 */
	public function get_variable_product_defer_lowest_price(): bool {

		$settings = get_option( 'wc_price_history_settings' );
		if ( ! isset( $settings['variable_product_defer_lowest_price'] ) ) {
			return false;
		}
		return (bool) $settings['variable_product_defer_lowest_price'];
	}

	/**
	 * Get variable product placeholder text (shown before variant is selected).
	 *
	 * @since 3.2.0
	 *
	 * @return string
	 */
	public function get_variable_product_defer_placeholder_text(): string {

		$settings = get_option( 'wc_price_history_settings' );
		if ( ! isset( $settings['variable_product_defer_placeholder_text'] ) || $settings['variable_product_defer_placeholder_text'] === '' ) {
			return '';
		}
		return esc_html( $settings['variable_product_defer_placeholder_text'] );
	}

	/**
	 * REST API product response toggles (one option read per instance until cache bust).
	 *
	 * @since 3.2.5
	 *
	 * @return array{show_lowest_price: bool, show_full_history: bool}
	 */
	public function get_rest_api_product_response_flags(): array {

		return $this->resolve_rest_api_response_flags();
	}

	/**
	 * Whether to expose lowest prior price on WooCommerce REST API product responses.
	 *
	 * @since 3.2.5
	 *
	 * @return bool
	 */
	public function get_rest_api_show_lowest_price(): bool {

		return $this->resolve_rest_api_response_flags()['show_lowest_price'];
	}

	/**
	 * Whether to expose full price history on WooCommerce REST API product responses.
	 *
	 * @since 3.2.5
	 *
	 * @return bool
	 */
	public function get_rest_api_show_full_history(): bool {

		return $this->resolve_rest_api_response_flags()['show_full_history'];
	}

	/**
	 * Clear cached REST API flags after option write (any source).
	 *
	 * Callback for {@see 'update_option_wc_price_history_settings'}.
	 *
	 * @since 3.2.5
	 *
	 * @param mixed ...$_args Old value, new value, option name (see WordPress core).
	 *
	 * @return void
	 */
	public function bust_rest_api_response_flags_cache( ...$_args ): void {

		$this->rest_api_response_flags_cache = null;
	}

	/**
	 * @return array{show_lowest_price: bool, show_full_history: bool}
	 */
	private function resolve_rest_api_response_flags(): array {

		if ( null !== $this->rest_api_response_flags_cache ) {
			return $this->rest_api_response_flags_cache;
		}

		$settings = get_option( 'wc_price_history_settings' );
		$lowest   = false;
		$history  = false;

		if ( is_array( $settings ) ) {
			if ( isset( $settings['rest_api_show_lowest_price'] ) ) {
				$lowest = (bool) $settings['rest_api_show_lowest_price'];
			}
			if ( isset( $settings['rest_api_show_full_history'] ) ) {
				$history = (bool) $settings['rest_api_show_full_history'];
			}
		}

		$this->rest_api_response_flags_cache = [
			'show_lowest_price' => $lowest,
			'show_full_history' => $history,
		];

		return $this->rest_api_response_flags_cache;
	}
}
