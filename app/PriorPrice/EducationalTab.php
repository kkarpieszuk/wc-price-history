<?php

namespace PriorPrice;

/**
 * Educational tab.
 *
 * @since {VERSION}
 */
class EducationalTab {

	/**
	 * Register hooks.
	 *
	 * @since {VERSION}
	 */
	public function register_hooks() {
		add_action( 'woocommerce_product_write_panel_tabs', [ $this, 'add_tab' ] );
		add_action( 'woocommerce_product_data_panels', [ $this, 'add_panel' ] );
	}

	/**
	 * Add tab.
	 *
	 * @since {VERSION}
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
	 * @since {VERSION}
	 *
	 * @return void
	 */
	public function add_panel() {
		?>
		<div id="educational_tab" class="panel woocommerce_options_panel">
			<div class="wc-price-history-educational-tab-content">
			<h3><?php esc_html_e( 'Coming soon: WC Price History PRO 🎉', 'wc-price-history' ); ?></h3>
			<p>
				<?php
				printf(
					/* translators: %1$s: WC Price History PRO */
					esc_html__( 'I am working on %1$s!', 'wc-price-history' ),
					'<strong>' . esc_html__( 'WC Price History PRO', 'wc-price-history' ) . '</strong>'
				);
				?><br>
				<?php
				printf(
					/* translators: %1$s: review the full price history, %2$s: edit past prices */
					esc_html__( 'In the PRO version you will be able to %1$s and even %2$s directly in your store.', 'wc-price-history' ),
					'<strong>' . esc_html__( 'review the full price history', 'wc-price-history' ) . '</strong>',
					'<strong>' . esc_html__( 'edit past prices', 'wc-price-history' ) . '</strong>'
				);
				?>
			</p>
			<p>
				<?php esc_html_e( "Leave your email below and I will let you know as soon as it's ready.", 'wc-price-history' ); ?><br>
				<em><?php esc_html_e( 'Bonus: Early subscribers may receive a', 'wc-price-history' ); ?> <strong><?php esc_html_e( 'lifetime license', 'wc-price-history' ); ?></strong> <?php esc_html_e( '— normally licenses will be yearly!', 'wc-price-history' ); ?></em>
			</p>

			<button type="button"
				onclick="window.open('https://docs.google.com/forms/d/e/1FAIpQLSdmdCMAp3AtfXQy2Aoej2IhnuZTtAAU5YKC4FWt2l_Ei-EL4A/viewform', '_blank')"
				class="wc-price-history-button-get-pro"
				title="<?php esc_html_e( 'Click to get notified about the PRO version and get a lifetime license!', 'wc-price-history' ); ?>">
				<?php esc_html_e( 'Notify me about PRO!', 'wc-price-history' ); ?>
			</button>
			</div>
		</div>
		<?php
	}
}