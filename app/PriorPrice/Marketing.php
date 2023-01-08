<?php

namespace PriorPrice;

/**
 * Marketing class.
 *
 * Plugin promotional stuff.
 *
 * @since 1.4
 */
class Marketing {

	/**
	 * Register hooks.
	 *
	 * @since 1.4
	 *
	 * @return void
	 */
	public function register_hooks() : void {
		add_filter( 'plugin_row_meta', [ $this, 'ask_for_review_on_plugins_row_meta' ], 10, 4 );
		add_action( 'wc_price_history_settings_page_rate_us_text', [ $this, 'settings_page_rate_us_text' ] );
	}

	/**
	 * Ask for review on plugins row meta.
	 *
	 * @since 1.4
	 *
	 * @param array<string> $plugin_meta Plugin meta.
	 * @param string        $plugin_file Plugin file.
	 * @param array<string> $plugin_data Plugin data.
	 * @param string        $status      Plugin status.
	 *
	 * @return array<string>
	 */
	public function ask_for_review_on_plugins_row_meta( array $plugin_meta, string $plugin_file, array $plugin_data, string $status ) : array {
		if ( ! $this->is_our_plugin( $plugin_data ) ) {
			return $plugin_meta;
		}
		$plugin_meta[] = $this->get_review_link();
		return $plugin_meta;
	}

	/**
	 * Settings page rate us text.
	 *
	 * @since 1.4
	 *
	 * @return void
	 */
	public function settings_page_rate_us_text() : void {
		echo $this->get_review_link();
	}

	/**
	 * Check if it's our plugin.
	 *
	 * @since 1.4
	 *
	 * @param array<string> $plugin_data Plugin data.
	 *
	 * @return bool
	 */
	private function is_our_plugin( array $plugin_data ) : bool {
		return isset( $plugin_data['slug'] ) && $plugin_data['slug'] === 'wc-price-history';
	}

	/**
	 * Get review link.
	 *
	 * @since 1.4
	 *
	 * @return string
	 */
	private function get_review_link() : string {

		return wp_kses(
			sprintf(
				/* translators: %1$s: opening link HTML tag, %2$s: closing link HTML tag, %3$s 5-star rating symbol, %4$s: smiling face with heart-shaped eyes */
				__( 'WC Price History has made your life easier? %1$sShow your support by leaving a review and %3$s rating%2$s! %4$s', 'wc-price-history' ),
				'<a href="https://wordpress.org/support/plugin/wc-price-history/reviews/?filter=5#new-post" target="_blank">',
				'</a>',
				'&#11088;&#11088;&#11088;&#11088;&#11088;',
				'&#128525;'
			),
			wp_kses_allowed_html( 'post' )
		);
	}
}
