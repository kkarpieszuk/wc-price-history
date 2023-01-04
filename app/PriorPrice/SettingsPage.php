<?php

namespace PriorPrice;

class SettingsPage {

	/**
	 * Register hooks.
	 *
	 * @since 1.1
	 *
	 * @return void
	 */
	public function register_hooks() {
		add_action( 'admin_menu', [ $this, 'add_menu' ] );
		add_action( 'admin_init', [ $this, 'register_settings' ] );
		add_action( 'admin_init', [ $this, 'set_defaults' ] );
	}

	/**
	 * Add settings menu.
	 *
	 * @since 1.1
	 *
	 * @return void
	 */
	public function add_menu() {
		add_submenu_page(
			'woocommerce',
			__( 'Price History', 'wc-price-history' ),
			__( 'Price History', 'wc-price-history' ),
			'manage_woocommerce',
			'wc-price-history',
			[ $this, 'render' ]
		);
	}

	/**
	 * Register settings.
	 *
	 * @since 1.1
	 *
	 * @return void
	 */
	public function register_settings() {
		register_setting( 'wc_price_history_settings', 'wc_price_history_settings' );
	}

	/**
	 * Set default settings.
	 *
	 * @since 1.1
	 *
	 * @return void
	 */
	public function set_defaults() {
		$settings = get_option( 'wc_price_history_settings' );
		if ( $settings === false ) {
			$settings = [
				'display_on' => [
					'product_page' => '1',
					'shop_page'    => '1',
				],
			];
			update_option( 'wc_price_history_settings', $settings );
		}
	}

	/**
	 * Render settings page.
	 *
	 * @since 1.1
	 *
	 * @return void
	 */
	public function render() {
		if ( isset( $_GET['settings-updated'] ) ) {
			// Add a settings updated message
			add_settings_error( 'wc_price_history_settings', 'settings_updated', __( 'Settings saved.', 'wc-price-history' ), 'updated' );
		}
		$settings = get_option( 'wc_price_history_settings' );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Price History Settings', 'wc-price-history' ); ?></h1>
			<?php settings_errors( 'wc_price_history_settings' ); ?>
			<form method="post" action="options.php">
				<?php
				settings_fields( 'wc_price_history_settings' );
				do_settings_sections( 'wc_price_history_settings' );
				?>
				<table class="form-table">
					<tr>
						<th scope="row"><?php esc_html_e( 'Display minimal price on:', 'wc-price-history' ); ?></th>
						<td>
							<label>
								<input
									type="checkbox"
									name="wc_price_history_settings[display_on][product_page]"
									value="1"
									<?php checked( isset( $settings['display_on']['product_page'] ) ? $settings['display_on']['product_page'] : false, 1 ); ?>
								/>
								<?php esc_html_e( 'Product page', 'wc-price-history' ); ?>
							</label>
							<br />
							<label>
								<input
									type="checkbox"
									name="wc_price_history_settings[display_on][shop_page]"
									value="1"
									<?php checked( isset( $settings['display_on']['shop_page'] ) ? $settings['display_on']['shop_page'] : false, 1 ); ?>
								/>
								<?php esc_html_e( 'Shop page', 'wc-price-history' ); ?>
							</label>
						</td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}
}
