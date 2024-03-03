<?php

namespace PriorPrice\FirstRun;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class AdminNotice {

	/**
	 * Settings data.
	 *
	 * @since 2.0
	 *
	 * @var SettingsData
	 */
	private $settings_data;

	public function __construct( $settings_data ) {

		$this->settings_data = $settings_data;
	}

	public function register_hooks() {

		if ( $this->should_display_notice() ) {
			add_action( 'admin_notices', [ $this, 'display_first_run_notice' ] );
		}
	}

	public function display_first_run_notice() {

		$settings_url = add_query_arg(
			[
				'page' => 'wc-price-history-first-run',
			],
			admin_url( 'admin.php' )
		);

		echo '<div class="notice notice-warning is-dismissible">';
		echo '<p>' . esc_html__( 'Welcome to WC Price History! It looks like this is your first time activating the plugin. Please ', 'wc-price-history' ) . '<a href="' . esc_url( $settings_url ) . '">' . esc_html__( 'click here', 'wc-price-history' ) . '</a>' . esc_html__( ' to scan your products and start tracking price history.', 'wc-price-history' ) . '</p>';
		echo '</div>';
	}

	/**
	 * Check if the first run notice should be displayed.
	 *
	 * Check if SettingsData::get_first_history_save_done() is less than 1
	 * and there is no data in postmeta with key '_wc_price_history'.
	 *
	 * If there is GET parameter 'wc-price-history-first-run' in the URL, the notice should be displayed.
	 *
	 * @since 2.0
	 *
	 * @return bool
	 */
	public function should_display_notice() : bool {

		if ( isset( $_GET['wc-price-history-first-run'] ) ) {
			return true;
		}

		if ( $this->settings_data->get_first_history_save_done() > 0 ) {
			return false;
		}

		$products = get_posts(
			[
				'post_type'      => 'product',
				'posts_per_page' => 1,
				'meta_query'     => [
					[
						'key'     => '_wc_price_history',
						'compare' => 'EXISTS',
					],
				],
			]
		);

		return empty( $products );
	}
}
