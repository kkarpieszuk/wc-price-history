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
		?>
		<div id="educational_tab" class="panel woocommerce_options_panel">
			<div class="wc-price-history-educational-tab-content" data-checkout-url="https://wcpricehistory.lemonsqueezy.com/checkout/buy/8a1850b9-78e7-4081-9bf0-f5ea2aa00f3a">
			<h3><?php esc_html_e( 'WC Price History PRO — full history & edit past prices', 'wc-price-history' ); ?></h3>
			<p>
				<?php
				printf(
					/* translators: %1$s: WC Price History PRO */
					esc_html__( 'Upgrade to %1$s to review the full price history and edit past prices directly in your store.', 'wc-price-history' ),
					'<strong>' . esc_html__( 'WC Price History PRO', 'wc-price-history' ) . '</strong>'
				);
				?>
			</p>
			<p class="wc-price-history-pro-price-line">
				<span class="wc-price-history-pro-price-display" aria-live="polite">
					<span class="wc-price-history-pro-price-struck">€49</span>
					<span class="wc-price-history-pro-price-current">€49</span>
					<?php echo ' ' . esc_html__( '/ site / year', 'wc-price-history' ); ?>
				</span>
			</p>
			<p class="wc-price-history-pro-licenses-row">
				<label for="wc-price-history-pro-licenses"><?php esc_html_e( 'Choose number of sites', 'wc-price-history' ); ?></label>
				<span class="wc-price-history-pro-licenses-slider-row">
					<input type="range" id="wc-price-history-pro-licenses" min="1" max="25" value="1" class="wc-price-history-pro-licenses-slider">
					<span id="wc-price-history-pro-licenses-value" class="wc-price-history-pro-licenses-value">1</span>
				</span>
			</p>
			<button type="button"
				class="wc-price-history-button-get-pro wc-price-history-pro-checkout-btn"
				title="<?php esc_html_e( 'Get WC Price History PRO', 'wc-price-history' ); ?>">
				<?php esc_html_e( 'Get PRO', 'wc-price-history' ); ?>
			</button>
			<p class="wc-price-history-pro-learn-more">
				<a href="https://wcpricehistory.com/pro/" target="_blank" rel="noopener"><?php esc_html_e( 'Learn more', 'wc-price-history' ); ?></a>
			</p>
			</div>
		</div>
		<?php
	}
}
