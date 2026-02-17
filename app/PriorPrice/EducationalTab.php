<?php

namespace PriorPrice;

use PriorPrice\Helpers\Pro;

/**
 * Educational tab.
 *
 * @since 3.0.0
 */
class EducationalTab {

	/**
	 * Register hooks.
	 *
	 * @since 3.0.0
	 *
	 * @return void
	 */
	public function register_hooks() {

		if ( Pro::is_pro() ) {
			return;
		}

		add_action( 'woocommerce_product_write_panel_tabs', [ $this, 'add_tab' ] );
		add_action( 'woocommerce_product_data_panels', [ $this, 'add_panel' ] );
	}

	/**
	 * Add tab.
	 *
	 * @since 3.0.0
	 *
	 * @return void
	 */
	public function add_tab() {

		?>
		<li class="wc_price_history_educational_tab show_if_simple show_if_variable">
			<a href="#educational_tab"><span><?php esc_html_e( 'WC Price History Editor', 'wc-price-history' ); ?></span></a>
		</li>
		<?php
	}

	/**
	 * Add panel.
	 *
	 * @since 3.0.0
	 *
	 * @return void
	 */
	public function add_panel() {
		$variant = wp_rand( 1, 3 );
		$headlines = [
			1 => __( 'Fix pricing mistakes & stay compliant', 'wc-price-history' ),
			2 => __( 'Take full control over your price history', 'wc-price-history' ),
			3 => __( 'Need to edit past prices?', 'wc-price-history' ),
		];
		$headline = $headlines[ $variant ];
		?>
		<div id="educational_tab" class="panel woocommerce_options_panel">
			<div class="wc-price-history-educational-tab-content">
			<div class="wc-price-history-pro-headline-row">
				<h3 class="wc-price-history-pro-headline"><?php echo esc_html( $headline ); ?></h3>
				<p class="wc-price-history-pro-social-proof">
					<span class="wc-price-history-pro-stars" aria-hidden="true">★★★★★</span>
					<?php esc_html_e( 'Trusted by 4000+ stores', 'wc-price-history' ); ?>
				</p>
			</div>
			<ul class="wc-price-history-pro-benefits">
				<li><?php esc_html_e( 'Accidentally entered the wrong price? Correct it instantly.', 'wc-price-history' ); ?></li>
				<li><?php esc_html_e( 'Manually override the lowest price to ensure logical Omnibus display.', 'wc-price-history' ); ?></li>
				<li><?php esc_html_e( 'View detailed log: see exactly when and how prices changed.', 'wc-price-history' ); ?></li>
				<li><?php esc_html_e( 'Set fixed lowest prices, if needed.', 'wc-price-history' ); ?></li>
			</ul>
			<p class="wc-price-history-pro-price-line">
				<?php esc_html_e( '€49 / site / year', 'wc-price-history' ); ?>
			</p>
			<button type="button"
				onclick="window.open('https://wcpricehistory.lemonsqueezy.com/checkout/buy/8a1850b9-78e7-4081-9bf0-f5ea2aa00f3a?quantity=1', '_blank')"
				class="wc-price-history-button-get-pro"
				title="<?php esc_html_e( 'Get WC Price History PRO', 'wc-price-history' ); ?>">
				<?php esc_html_e( 'Unlock Price Editor', 'wc-price-history' ); ?>
			</button>
			<p class="wc-price-history-pro-learn-more">
				<a href="https://wcpricehistory.com/pro/" target="_blank" rel="noopener"><?php esc_html_e( 'Learn more — you can choose the number of licenses there', 'wc-price-history' ); ?></a> |
				<a href="https://www.youtube.com/watch?v=vBfiUauQp68" target="_blank" rel="noopener"><?php esc_html_e( 'Watch demo video on Youtube', 'wc-price-history' ); ?></a>
			</p>
			</div>
		</div>
		<?php
	}
}
