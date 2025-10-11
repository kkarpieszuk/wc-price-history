<?php

namespace PriorPrice;

/**
 * Front end assets class.
 *
 * @since 1.7
 */
class FrontEndAssets {

	/**
	 * @var \PriorPrice\SettingsData
	 */
	private $settings_data;

	/**
	 * Constructor.
	 *
	 * @since {VERSION}
	 *
	 * @param \PriorPrice\SettingsData $settings_data Settings data.
	 */
	public function __construct( SettingsData $settings_data ) {
		$this->settings_data = $settings_data;
	}

	/**
	 * Register hooks.
	 *
	 * @since 1.7
	 */
	public function register_hooks() : void {
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
	}

	/**
	 * Enqueue scripts.
	 *
	 * @since 1.7
	 */
	public function enqueue_scripts() : void {
		wp_enqueue_style( 'wc-price-history-frontend', WC_PRICE_HISTORY_PLUGIN_URL . 'assets/css/frontend.css', [], '1.7' );

		if ( is_product() ) {
			wp_enqueue_script( 'wc-price-history-frontend', WC_PRICE_HISTORY_PLUGIN_URL . 'assets/js/frontend.js', [ 'jquery' ], '2.1', true );

			$price_format = [
				'thousand_separator' => wc_get_price_thousand_separator(),
				'decimal_separator'  => wc_get_price_decimal_separator(),
				'decimals'           => wc_get_price_decimals(),
			];

			wp_localize_script( 'wc-price-history-frontend', 'wc_price_history_frontend', $price_format );
		}

		wp_enqueue_script(
			'wc-price-history-react-main',
			WC_PRICE_HISTORY_PLUGIN_URL . 'assets/react/main.js',
			[ 'wp-element', 'wp-i18n', 'wp-polyfill', 'wp-components' ],
			'1.0',
			[
				'strategy' => 'defer',
				'in_footer' => true,
			]
		);

		wp_localize_script(
			'wc-price-history-react-main',
			'wc_price_history_react_main',
			[
				'currency' => get_woocommerce_currency(),
				'currency_symbol' => get_woocommerce_currency_symbol(),
				'last_days' => $this->settings_data->get_days_number(),
				'display_text' => $this->settings_data->get_display_text(),
			]
		);
	}
}
