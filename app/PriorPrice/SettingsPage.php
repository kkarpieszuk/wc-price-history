<?php

namespace PriorPrice;

use PriorPrice\Database\DbMigration;
use PriorPrice\Database\Install;
use PriorPrice\Helpers\Pro;
use PriorPrice\HistoryStorage;

class SettingsPage {

	/**
	 * Register hooks.
	 *
	 * @since 1.2
	 *
	 * @return void
	 */
	public function register_hooks() : void {
		add_action( 'admin_menu', [ $this, 'add_menu' ] );
		add_action( 'admin_init', [ $this, 'register_settings' ] );
		add_filter( 'plugin_action_links_' . WC_PRICE_HISTORY_FILE, [ $this, 'add_settings_link' ] );
	}

	/**
	 * Add settings link to plugins page.
	 *
	 * @since 1.6.6
	 *
	 * @param array<string> $links Plugin links.
	 *
	 * @return array<string>
	 */
	public function add_settings_link( array $links ) : array {
		$settings_link = sprintf(
			'<a href="%s">%s</a>',
			esc_url( admin_url( 'admin.php?page=wc-price-history' ) ),
			esc_html__( 'Settings', 'wc-price-history' )
		);
		array_unshift( $links, $settings_link );
		return $links;
	}

	/**
	 * Add settings menu.
	 *
	 * @since 1.2
	 *
	 * @return void
	 */
	public function add_menu() : void {
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
	 * @since 1.2
	 *
	 * @return void
	 */
	public function register_settings() : void {
		register_setting( 'wc_price_history_settings', 'wc_price_history_settings' );
	}

	/**
	 * Render settings page.
	 *
	 * @since 1.2
	 *
	 * @return void
	 */
	public function render() {
		if ( isset( $_GET['settings-updated'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			// Add a settings updated message
			add_settings_error( 'wc_price_history_settings', 'settings_updated', __( 'Settings saved.', 'wc-price-history' ), 'updated' );
		}
		$settings = get_option( 'wc_price_history_settings' );
		?>
		<div class="wrap wc-history-price-admin">
			<h1><?php esc_html_e( 'Price History Settings', 'wc-price-history' ); ?></h1>
			<?php settings_errors( 'wc_price_history_settings' ); ?>
			<div class="wc-history-price-admin__left">
				<form method="post" action="options.php">
					<?php
					settings_fields( 'wc_price_history_settings' );
					do_settings_sections( 'wc_price_history_settings' );
					?>
					<table class="form-table">
						<tr>
							<th scope="row"><?php esc_html_e( 'Display minimal price on/for:', 'wc-price-history' ); ?></th>
							<td>
								<fieldset>
									<p>
										<label>
											<input
												type="checkbox"
												name="wc_price_history_settings[display_on][product_page]"
												value="1"
												<?php checked( isset( $settings['display_on']['product_page'] ) ? $settings['display_on']['product_page'] : false, 1 ); ?>
											/>
											<?php esc_html_e( 'Single product page', 'wc-price-history' ); ?>
										</label>
										<br />
										<label class="wc-price-history-related-product-label">
											<input
												type="checkbox"
												name="wc_price_history_settings[display_on][related_products]"
												value="1"
												<?php checked( isset( $settings['display_on']['related_products'] ) ? $settings['display_on']['related_products'] : false, 1 ); ?>
											/>
											<?php esc_html_e( 'Related and upsell products on single product page', 'wc-price-history' ); ?>
										</label>
										<br />
										<label>
											<input
												type="checkbox"
												name="wc_price_history_settings[display_on][shop_page]"
												value="1"
												<?php checked( isset( $settings['display_on']['shop_page'] ) ? $settings['display_on']['shop_page'] : false, 1 ); ?>
											/>
											<?php esc_html_e( 'Main shop page', 'wc-price-history' ); ?>
										</label>
										<br />
										<label>
											<input
												type="checkbox"
												name="wc_price_history_settings[display_on][category_page]"
												value="1"
												<?php checked( isset( $settings['display_on']['category_page'] ) ? $settings['display_on']['category_page'] : false, 1 ); ?>
											/>
											<?php esc_html_e( 'Product category page', 'wc-price-history' ); ?>
										</label>
										<br />
										<label>
											<input
												type="checkbox"
												name="wc_price_history_settings[display_on][tag_page]"
												value="1"
												<?php checked( isset( $settings['display_on']['tag_page'] ) ? $settings['display_on']['tag_page'] : false, 1 ); ?>
											/>
											<?php esc_html_e( 'Product tag page', 'wc-price-history' ); ?>
										</label>
									</p>
									<p class="description">
										<?php
										/* translators: %s: [wc_price_history id="3" show_currency="1"] shortcode tag. */
										printf( esc_html__( '...or you can display it anywhere with shortcode %s. Note that id and show_currency parameters are optional', 'wc-price-history' ), '<code>[wc_price_history id="3" show_currency="1"]</code>' ); ?>
									</p>
								</fieldset>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Display minimal price when:', 'wc-price-history' ); ?></th>
							<td>
								<fieldset>
									<p>
										<label>
											<input
												class="wc-history-price-display-when-input"
												type="radio"
												name="wc_price_history_settings[display_when]"
												value="always"
												<?php checked( isset( $settings['display_when'] ) ? $settings['display_when'] : false, 'always' ); ?>
											/>
											<?php esc_html_e( 'Always', 'wc-price-history' ); ?>
										</label>
										<br />
										<label>
											<input
												class="wc-history-price-display-when-input"
												type="radio"
												name="wc_price_history_settings[display_when]"
												value="on_sale"
												<?php checked( isset( $settings['display_when'] ) ? $settings['display_when'] : false, 'on_sale' ); ?>
											/>
											<?php esc_html_e( 'Only when product is On sale', 'wc-price-history' ); ?>
										</label>
									</p>
									<p class="description" >
										<?php esc_html_e( "Omnibus: European Union Guidance requires displaying the minimal price if a product is on sale.", 'wc-price-history' ); ?>
									</p>
								</fieldset>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'For products being on sale, count minimal price from:', 'wc-price-history' ); ?></th>
							<td>
								<fieldset>
									<p>
										<label>
											<input
												type="radio"
												name="wc_price_history_settings[count_from]"
												value="current_day"
												<?php checked( isset( $settings['count_from'] ) ? $settings['count_from'] : false, 'current_day' ); ?>
											/>
											<?php esc_html_e( 'Current day', 'wc-price-history' ); ?>
										</label>
										<br />
										<label>
											<input
												type="radio"
												name="wc_price_history_settings[count_from]"
												value="sale_start"
												<?php checked( isset( $settings['count_from'] ) ? $settings['count_from'] : false, 'sale_start' ); ?>
											/>
											<?php esc_html_e( 'Day before product went on sale (excludes promotional price)', 'wc-price-history' ); ?>
										</label>
										<br />
										<label>
											<input
												type="radio"
												name="wc_price_history_settings[count_from]"
												value="sale_start_inclusive"
												<?php checked( isset( $settings['count_from'] ) ? $settings['count_from'] : false, 'sale_start_inclusive' ); ?>
											/>
											<?php esc_html_e( 'Day when product went on sale (includes promotional price)', 'wc-price-history' ); ?>
										</label>
									</p>
									<p class="description">
										<?php esc_html_e( 'Omnibus: European Union Guidance requires displaying the lowest price before the sale started.', 'wc-price-history' ); ?>
									</p>
									<p class="description">
										<span class="wc-price-history-warning"><?php esc_attr_e( 'Heads up!', 'wc-price-history' ); ?></span><br>
										<?php esc_html_e( 'Option "Day when product went on sale" works only for products with "Sale price dates" set on Edit product page (setting sale start date will be enough).', 'wc-price-history' ); ?>
										<br>
										<?php esc_html_e( 'If product does not have scheduled such date, minimal price will be counted from current day and this option will be ignored.', 'wc-price-history' ); ?>
										<br>
										<?php
										$admin_page_url = admin_url( 'admin.php?page=wc-status&tab=logs' );
										$a_href         = sprintf( '<a href="%s">%s</a>', $admin_page_url, esc_html__( 'WooCommerce > Status > Logs', 'wc-price-history' ) );
										/* translators: %s: URL to WooCommerce logs page, do not translate wc-price-history, it is a slug */
										printf(
											/* translators: %s: URL to WooCommerce logs page, do not translate wc-price-history, it is a slug */
											esc_html__(
												'All products which does not have set sale start date will be logged in %s (look for error log with name starting from wc-price-history).', 'wc-price-history'
											),
											wp_kses(
												$a_href,
												[
													'a' => [
														'href' => [],
													],
												]
											)
										);
										?>
									</p>
								</fieldset>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Number of days to use when counting minimal price:', 'wc-price-history' ); ?></th>
							<td>
								<fieldset>
									<p>
										<label>
											<input
												type="number"
												name="wc_price_history_settings[days_number]"
												id="wc-price-history-days-number"
												value="<?php echo isset( $settings['days_number'] ) ? (int) $settings['days_number'] : 30; ?>"
											/>
											<?php esc_attr_e( 'days', 'wc-price-history' ) ?></label>
									</p>
									<p class="description" >
										<?php esc_html_e( 'Omnibus: European Union Guidance requires displaying lowest price from the last 30 days.', 'wc-price-history' ); ?>
									</p>
								</fieldset>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Minimal price text:', 'wc-price-history' ); ?></th>
							<td>
								<fieldset>
									<p>
										<label class="wc-price-history-wide-field">
											<input
												type="text"
												name="wc_price_history_settings[display_text]"
												class="wc-price-history-wide-field"
												<?php /* translators: Do not translate {price}, it is template slug! */ ?>
												value="<?php echo isset( $settings['display_text'] ) ? esc_attr( $settings['display_text'] ) : esc_attr__( '30-day low: {price}', 'wc-price-history' ); ?>"
											/>
										</label>
									</p>
									<p class="description" >
										<?php /* translators: Do not translate {price} and {days}, those are template slugs! */ ?>
										<?php esc_html_e( 'Use placeholder {price} to display price and {days} to display number of days.', 'wc-price-history' ); ?>
									</p>
								</fieldset>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<?php
									printf(
										/* translators: %s: {days-set} template slug */
										esc_html__( 'What to do when price history is older than %s days:', 'wc-price-history' ),
										'<span class="wc-price-history-days-set">' . (int) $settings['days_number'] . '</span>'
										);
								?>
							</th>
							<td>
								<fieldset id="wc-price-history-old-history-fieldset">
									<p class="description" >
										<?php
											printf(
												/* translators: Do not translate {days-set}, it is template slug! */
												esc_html__( 'It could happen that the last change of price was more than %s days ago. In that case you can choose to hide minimal price, display current price or display custom text.', 'wc-price-history' ),
												'<span class="wc-price-history-days-set">' . (int) $settings['days_number'] . '</span>'
											);
										?>
									</p>
									<p>
										<label>
											<input
												type="radio"
												name="wc_price_history_settings[old_history]"
												value="hide"
												<?php checked( isset( $settings['old_history'] ) ? $settings['old_history'] : false, 'hide' ); ?>
											/>
											<?php esc_html_e( 'Hide minimal price', 'wc-price-history' ); ?>
										</label>
										<br />
										<label>
											<input
												type="radio"
												name="wc_price_history_settings[old_history]"
												value="current_price"
												<?php checked( isset( $settings['old_history'] ) ? $settings['old_history'] : false, 'current_price' ); ?>
											/>
											<?php esc_html_e( 'Display current price', 'wc-price-history' ); ?>
										</label>
										<br />
										<label>
											<input
												type="radio"
												name="wc_price_history_settings[old_history]"
												value="custom_text"
												<?php checked( isset( $settings['old_history'] ) ? $settings['old_history'] : false, 'custom_text' ); ?>
											/>
											<?php esc_html_e( 'Display custom text', 'wc-price-history' ); ?>:
										</label>
									</p>
									<p class="wc-price-history-old-history-custom-text-p <?php echo isset( $settings['old_history'] ) && $settings['old_history'] === 'custom_text' ? '' : 'hidden-fade'; ?>">
										<label class="wc-price-history-wide-field">
											<input
												type="text"
												name="wc_price_history_settings[old_history_custom_text]"
												class="wc-price-history-wide-field"
												<?php /* translators: Do not translate {days}, it is template slug! */ ?>
												value="<?php echo isset( $settings['old_history_custom_text'] ) ? esc_attr( $settings['old_history_custom_text'] ) : esc_attr__( 'Price in the last {days} days is the same as current', 'wc-price-history' ); ?>"
											/>
										</label>
									</p>
									<p class="description wc-price-history-old-history-custom-text-p <?php echo isset( $settings['old_history'] ) && $settings['old_history'] === 'custom_text' ? '' : 'hidden-fade'; ?>">
										<?php esc_html_e( 'If you choose to display custom text, use placeholder {days} to display number of days and {price} to display current price.', 'wc-price-history' ); ?>
									</p>
								</fieldset>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'When displaying minimal price:', 'wc-price-history' ); ?></th>
							<td>
								<fieldset>
									<p>
										<label>
											<input
												type="checkbox"
												name="wc_price_history_settings[display_line_through]"
												value="1"
												<?php checked( isset( $settings['display_line_through'] ) ? $settings['display_line_through'] : 0, 1 ); ?>
											/>
											<?php esc_attr_e( 'Apply line-through on the price', 'wc-price-history' ) ?>
										</label>
									</p>
									<p class="description" >
										<?php esc_html_e( 'If checked, price will be displayed with line-through on it.', 'wc-price-history' ); ?>
									</p>
								</fieldset>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Variable products', 'wc-price-history' ); ?></th>
							<td>
								<fieldset id="wc-price-history-variable-product-fieldset">
									<p>
										<label>
											<input
												type="checkbox"
												name="wc_price_history_settings[variable_product_defer_lowest_price]"
												id="wc-price-history-variable-product-defer"
												value="1"
												<?php checked( ! empty( $settings['variable_product_defer_lowest_price'] ), true ); ?>
											/>
											<?php esc_html_e( 'Show lowest price only after a variant is selected', 'wc-price-history' ); ?>
										</label>
									</p>
									<p class="description">
										<?php esc_html_e( 'For variable products, the main product has no sale dates or on-sale state; only variants do. Enabling this avoids showing an incorrect lowest price before the customer selects a variant.', 'wc-price-history' ); ?>
									</p>
									<p class="wc-price-history-variable-product-placeholder-p <?php echo ! empty( $settings['variable_product_defer_lowest_price'] ) ? '' : 'hidden-fade'; ?>">
										<label class="wc-price-history-wide-field">
											<input
												type="text"
												name="wc_price_history_settings[variable_product_defer_placeholder_text]"
												id="wc-price-history-variable-product-placeholder-text"
												class="wc-price-history-wide-field"
												value="<?php echo isset( $settings['variable_product_defer_placeholder_text'] ) ? esc_attr( $settings['variable_product_defer_placeholder_text'] ) : esc_attr__( 'Select a variant to see the lowest price', 'wc-price-history' ); ?>"
												<?php disabled( empty( $settings['variable_product_defer_lowest_price'] ), true ); ?>
											/>
										</label>
									</p>
									<p class="description wc-price-history-variable-product-placeholder-p <?php echo ! empty( $settings['variable_product_defer_lowest_price'] ) ? '' : 'hidden-fade'; ?>">
										<?php esc_html_e( 'Text to show next to the price before a variant is selected.', 'wc-price-history' ); ?>
									</p>
								</fieldset>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'WooCommerce REST API', 'wc-price-history' ); ?></th>
							<td>
								<fieldset>
									<p>
										<label>
											<input
												type="checkbox"
												name="wc_price_history_settings[rest_api_show_lowest_price]"
												value="1"
												<?php checked( ! empty( $settings['rest_api_show_lowest_price'] ), true ); ?>
											/>
											<?php esc_html_e( 'Expose lowest prior price in the WooCommerce REST API', 'wc-price-history' ); ?>
										</label>
									</p>
									<p class="description">
										<?php esc_html_e( 'When enabled, product and variation responses include wc_price_history.lowest (float, tax-inclusive like the storefront).', 'wc-price-history' ); ?>
									</p>
									<p>
										<label>
											<input
												type="checkbox"
												name="wc_price_history_settings[rest_api_show_full_history]"
												value="1"
												<?php checked( ! empty( $settings['rest_api_show_full_history'] ), true ); ?>
											/>
											<?php esc_html_e( 'Expose full price history in the WooCommerce REST API', 'wc-price-history' ); ?>
										</label>
									</p>
									<p class="description">
										<?php esc_html_e( 'When enabled, responses include wc_price_history.history: an object whose keys are Unix timestamps and values are prices (float). Anyone who can read products via the REST API will see this data—leave off unless you need it. Not the same as the Store API (wc/store).', 'wc-price-history' ); ?>
									</p>
								</fieldset>
							</td>
						</tr>
						<?php
						/**
						 * Action to add custom fields to settings page.
						 *
						 * @since 3.0.0
						 *
						 * @param array $settings
						 */
						do_action( 'wc_price_history_settings_page_fields', $settings );
						?>
					</table>
					<?php submit_button(); ?>
				</form>
				<table class="form-table">
					<tr>
						<th scope="row">
							<h2><?php esc_html_e( 'Danger zone', 'wc-price-history' ); ?></h2>
						</th>
						<td>
							<p>
								<?php esc_html_e( 'Use these actions with caution!', 'wc-price-history' ); ?>
							</p>
							<strong>
								<?php esc_html_e( 'It is really recommended to make a backup of your database beforehand!', 'wc-price-history' ); ?>
							</strong>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Fix history', 'wc-price-history' ); ?></th>
						<td>
							<fieldset>
								<p>
									<button
											type="button"
											name="wc_price_history_fix_history"
											id="wc-price-history-fix-history"
											value="1"
											class="button button-secondary"
										> <?php esc_html_e( 'Fix prices history', 'wc-price-history' ); ?> </button>
								</p>
								<p class="description" >
									<?php esc_html_e( 'Ever happened that you discounted a product and the price history shows the lowest price before discount same as after discount? This action will perhaps fix it.', 'wc-price-history' ); ?>
								</p>
								<p class="description" >
									<?php
										/** Translators: PL: Tak: "być może" nie brzmi zdecydowanie i nie bez powodu: powody opisanego wyżej problemu mogą być różne, więc naprawdę zalecam wykonanie kopi zapasowej. */
										esc_html_e( 'Yes: "perhaps" does not sound definite, and for good reason: the reasons for the problem described above may vary, so I really recommend taking a backup.', 'wc-price-history' );
									?>
							</fieldset>
					</tr>
					<?php $this->first_scan_section( $settings ); ?>
					<tr>
						<th scope="row"><?php esc_html_e( 'Clean history', 'wc-price-history' ); ?></th>
						<td>
							<fieldset>
								<p>
									<button
											type="button"
											name="wc_price_history_clean_history"
											id="wc-price-history-clean-history"
											value="1"
											class="button button-secondary notice-error"
										> <?php esc_html_e( 'Clean history', 'wc-price-history' ); ?> </button>
								</p>
								<p class="description" >
									<?php esc_html_e( 'This action cannot be undone (unless you have a backup). All price history data will be removed.', 'wc-price-history' ); ?>
								</p>
							</fieldset>
					</tr>

					<?php do_action( 'wc_price_history_settings_page_danger_zone' ); ?>
				</table>
			</div>
			<div class="wc-history-price-admin__right">
				<div class="wc-history-price-admin__right__box">

					<?php $this->hire_me_section(); ?>

					<?php if ( ! Pro::is_pro() ) : ?>
					<h3><?php esc_html_e( 'WC Price History PRO', 'wc-price-history' ); ?></h3>

					<p>
					<?php
					printf(
						/* translators: %1$s: WC Price History PRO */
						esc_html__( 'Upgrade to %1$s to review the full price history and edit past prices directly in your store.', 'wc-price-history' ),
						'<strong>' . esc_html__( 'WC Price History PRO', 'wc-price-history' ) . '</strong>'
					);
					?>
					</p>
					<p>
						<strong><?php esc_html_e( 'Pricing (per site, per year):', 'wc-price-history' ); ?></strong><br>
						<?php esc_html_e( '€49 — 1 license', 'wc-price-history' ); ?><br>
						<?php esc_html_e( '€45 per license — 5 licenses', 'wc-price-history' ); ?><br>
						<?php esc_html_e( '€40 per license — 20 licenses', 'wc-price-history' ); ?>
					</p>

					<button type="button"
						onclick="window.open('https://wcpricehistory.com/', '_blank')"
						class="wc-price-history-button-get-pro"
						title="<?php esc_html_e( 'Get WC Price History PRO', 'wc-price-history' ); ?>">
						<?php esc_html_e( 'Get PRO', 'wc-price-history' ); ?>
					</button>

					<?php endif; ?>
					<h3><?php esc_html_e( 'Support', 'wc-price-history' ); ?></h3>
					<p>
						<a href="https://wordpress.org/support/plugin/wc-price-history/" target="_blank">
							<?php esc_html_e( 'If you have any questions, please contact me at plugin support forum.', 'wc-price-history' ); ?>
						</a>
					</p>

					<p>
						<a href="https://wcpricehistory.com/" target="_blank">
							<?php esc_html_e( 'Visit the official website of the plugin', 'wc-price-history' ); ?>
						</a>
					</p>

					<h3><?php esc_html_e( 'Please rate the plugin! ', 'wc-price-history' ); ?></h3>
					<p class="description">
						<?php do_action( 'wc_price_history_settings_page_rate_us_text' ); ?>
					</p>

					<h3><?php esc_html_e( 'Read the EU legal acts:', 'wc-price-history' ); ?></h3>
					<p class="description">
						<a href="https://eur-lex.europa.eu/legal-content/EN/TXT/?uri=CELEX:52021XC1229(06)" target="_blank"><?php esc_html_e( 'Guidance on the Price Indication Directive (2021)', 'wc-price-history' ); ?></a>
					</p>

					<h3><?php esc_html_e( 'Status', 'wc-price-history' ); ?></h3>
					<?php
					$history_storage = new HistoryStorage();
					$uses_tables     = $history_storage->should_use_tables();
					?>
					<details class="wc-price-history-storage-status">
						<summary>
							<strong><?php esc_html_e( 'Storage method:', 'wc-price-history' ); ?></strong>
							<?php
							if ( $uses_tables ) {
								?>
								<?php esc_html_e( 'Database tables', 'wc-price-history' ); ?>
								<?php
							} else {
								?>
								<?php esc_html_e( 'Post meta (legacy)', 'wc-price-history' ); ?>
								<?php
							}
							?>
						</summary>
						<p class="description" style="margin-top: 0.5em;">
							<?php
							if ( $uses_tables ) {
								esc_html_e( 'Great! The plugin is using dedicated database tables for storing price history. This provides better performance and scalability.', 'wc-price-history' );
								$migration_status = DbMigration::get_migration_status( true );
								$table_names      = Install::get_table_names();
								?>
								</p>
								<p class="description" style="margin-top: 0.5em;">
									<strong><?php esc_html_e( 'Why tables are used:', 'wc-price-history' ); ?></strong>
									<?php
									if ( $migration_status === DbMigration::STATUS_COMPLETED ) {
										esc_html_e( 'Migration from post meta has been completed.', 'wc-price-history' );
									} else if ( $migration_status === DbMigration::STATUS_NOT_NEEDED ) {
										esc_html_e( 'Migration was not needed (e.g. fresh install with database tables).', 'wc-price-history' );
									} else if ( $migration_status === false ) {
										esc_html_e( 'Migration status is not set.', 'wc-price-history' );
								    } else {
										printf(
											esc_html__( 'Migration status is %s.', 'wc-price-history' ),
											'<strong>' . esc_attr( $migration_status ) . '</strong>'
										);
									}
									?>
								</p>
								<p class="description" style="margin-top: 0.5em;">
									<strong><?php esc_html_e( 'Tables:', 'wc-price-history' ); ?></strong>
									<?php
									global $wpdb;
									$rows = [];
									foreach ( $table_names as $table_name ) {
										// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.DirectQuery
										$exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) ) === $table_name;
										if ( $exists ) {
											// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.DirectQuery
											$count = $wpdb->get_var( "SELECT COUNT(*) FROM `{$table_name}`" );
											$rows[] = $table_name . ' (' . (string) (int) $count . ' ' . esc_html__( 'rows', 'wc-price-history' ) . ')';
										} else {
											$rows[] = $table_name . ' (' . esc_html__( 'table missing', 'wc-price-history' ) . ')';
										}
									}
									echo esc_html( implode( ', ', $rows ) );
									?>
								</p>
							<?php } else {
								esc_html_e( 'The plugin is using post meta for storing price history. Consider migrating to database tables for better performance.', 'wc-price-history' );
								?>
						</p>
							<?php }
							?>
					</details>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * First scan section.
	 *
	 * @since 3.0.0
	 *
	 * @param array<string, mixed> $settings Settings.
	 */
	private function first_scan_section( array $settings ): void {
		$first_scan_status = $settings['first_history_scan'];

		if ( $first_scan_status < 2 ) {
		// Force first scan to finish button.
		?>
		<tr>
			<th scope="row"><?php esc_html_e( 'Force First Scan to Finish', 'wc-price-history' ); ?></th>
			<td>
				<fieldset>
					<p>
						<button type="button"
							name="wc_price_history_force_first_scan_end"
							id="wc-price-history-force-first-scan-end"
							value="1" class="button button-secondary">
								<?php esc_html_e( 'Force finish scan', 'wc-price-history' ); ?>
						</button>
					</p>
					<p class="description" >
						<?php esc_html_e( 'This action will force the first scan to finish. This is useful if the scan is stuck in the middle for a long time.', 'wc-price-history' ); ?>
					</p>
				</fieldset>
		</tr>
		<?php
		} else {
		// Restart scan button.
		?>
		<tr>
			<th scope="row"><?php esc_html_e( 'Restart First Scan', 'wc-price-history' ); ?></th>
			<td>
				<fieldset>
					<p>
						<button type="button"
							class="button button-secondary"
							name="wc_price_history_restart_first_scan"
							id="wc-price-history-restart-first-scan"
							value="1"
						>
							<?php esc_html_e( 'Restart scan', 'wc-price-history' ); ?>
						</button>
					</p>
					<p class="description" >
						<?php esc_html_e( 'This action will restart the first scan. This is useful if you see there are still some products without history.', 'wc-price-history' ); ?>
					</p>
				</fieldset>
			</td>
		</tr>
		<?php
		}
	}

	/**
	 * Hire me section.
	 *
	 * @since 3.0.0
	 */
	private function hire_me_section(): void {

		$avatar   = WC_PRICE_HISTORY_PLUGIN_URL . 'assets/images/me.webp';
		$variants = [
			[
				'title' => __('Hire Me - Senior WooCommerce Engineer', 'wc-price-history'),
				'description' => '<p>15+ years building the plugins you already use: <br>ACF, WPForms, WPML, WooCommerce extensions - and even WordPress Core (Gutenberg).</p><p>
I also integrate <b>AI automation</b> directly into WP & e-commerce workflows.</p><p>
↘️ <b>Remote-only • Full-time • Ready to join your team</b> ↙️</p><p>
<b>Let’s talk:</b> <a href="https://linkedin.com/in/konrad-karpieszuk-38528b11/" target="_blank">linkedin.com/in/konrad-karpieszuk-38528b11/</a></p>',
			],
			[
				'title' => __('Need WooCommerce Expertise?', 'wc-price-history'),
				'description' => '<p>Architect of high-performance stores, complex API integrations & EU-compliant pricing.</p><p>
<b>AI automation for real business impact</b>.</p><p>
<b>Remote Senior available:</b> <a href="https://linkedin.com/in/konrad-karpieszuk-38528b11/" target="_blank">linkedin.com/in/konrad-karpieszuk-38528b11/</a></p>',
			],
			[
				'title' => __('Hire a Senior who delivers', 'wc-price-history'),
				'description' => '<p>I’ve built and maintained major WordPress ecosystem products used by millions.</p><p>
I blend engineering, performance, UX and <b>AI-enhanced workflows</b>.</p><p>
If your agency scales - I can help.</p><p>
<b>Let’s connect:</b> <a href="https://linkedin.com/in/konrad-karpieszuk-38528b11/" target="_blank">linkedin.com/in/konrad-karpieszuk-38528b11/</a></p>',
			],
			[
				'title' => __('I help WooCommerce stores sell more & break less', 'wc-price-history'),
				'description' => '<p>25+ years of coding, 10+ in large product teams.</p><p>
Expert in performance, automation & revenue-driven e-commerce improvements.</p><p>
<b>Available full-time - Remote only.</b></p><p>
<b>Let’s connect:</b> <a href="https://linkedin.com/in/konrad-karpieszuk-38528b11/" target="_blank">linkedin.com/in/konrad-karpieszuk-38528b11/</a></p>',
			],
		];

		$variant = $variants[array_rand($variants)];

		printf(
			'<h3>%s</h3>',
			$variant['title']
		);
		printf(
			'<div class="wc-price-history-hire-me-section">
				<img class="wc-price-history-hire-me-section__img" src="%s" alt="Konrad Karpieszuk" width="100" height="100">
				<div>%s</div>
			</div>',
			wp_kses_post($avatar),
			wp_kses_post($variant['description'])
		);
	}
}
