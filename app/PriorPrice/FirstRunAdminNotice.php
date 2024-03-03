<?php

namespace PriorPrice;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class FirstRunAdminNotice {

	public function __construct() {
		add_action( 'admin_notices', [ $this, 'display_first_run_notice' ] );
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
}
