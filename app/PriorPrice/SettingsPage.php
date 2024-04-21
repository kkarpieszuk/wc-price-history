<?php

namespace PriorPrice;

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
		if ( isset( $_GET['settings-updated'] ) ) {
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
										<span class="wc-price-history-warning"><?php esc_attr_e( 'Heads up!' ); ?></span><br>
										<?php esc_html_e( 'Option "Day when product went on sale" works only for products with "Sale price dates" set on Edit product page (setting sale start date will be enough).', 'wc-price-history' ); ?>
										<br>
										<?php esc_html_e( 'If product does not have scheduled such date, minimal price will be counted from current day and this option will be ignored.', 'wc-price-history' ); ?>
										<br>
										<?php
										$admin_page_url = admin_url( 'admin.php?page=wc-status&tab=logs' );
										$a_href         = sprintf( '<a href="%s">%s</a>', $admin_page_url, esc_html__( 'WooCommerce > Status > Logs', 'wc-price-history' ) );
										/* translators: %s: URL to WooCommerce logs page, do not translate wc-price-history, it is a slug */
										printf( esc_html__( 'All products which does not have set sale start date will be logged in %s (look for error log with name starting from wc-price-history).', 'wc-price-history' ), $a_href );
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
												value="<?php echo isset( $settings['days_number'] ) ? $settings['days_number'] : 30; ?>"
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
												value="<?php echo isset( $settings['display_text'] ) ? $settings['display_text'] : esc_html__( '30-day low: {price}', 'wc-price-history' ); ?>"
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
										'<span class="wc-price-history-days-set">' . $settings['days_number'] . '</span>'
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
												'<span class="wc-price-history-days-set">' . $settings['days_number'] . '</span>'
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
												value="<?php echo isset( $settings['old_history_custom_text'] ) ? $settings['old_history_custom_text'] : esc_html__( 'Price in the last {days} days is the same as current', 'wc-price-history' ); ?>"
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
				</table>
			</div>
			<div class="wc-history-price-admin__right">
				<div class="wc-history-price-admin__right__box">

					<h3><?php esc_html_e( 'Read the EU legal acts:', 'wc-price-history' ); ?></h3>
					<p class="description">
						<a href="https://eur-lex.europa.eu/legal-content/EN/TXT/?uri=CELEX:52021XC1229(06)" target="_blank"><?php esc_html_e( 'Guidance on the Price Indication Directive (2021)', 'wc-price-history' ); ?></a>
					</p>

					<h3><?php esc_html_e( 'Support', 'wc-price-history' ); ?></h3>
					<p>
						<a href="https://wordpress.org/support/plugin/wc-price-history/" target="_blank">
							<?php esc_html_e( 'If you have any questions, please contact me at plugin support forum.', 'wc-price-history' ); ?>
						</a>
					</p>

					<h3><?php esc_html_e( 'Please rate the plugin! ', 'wc-price-history' ); ?></h3>
					<p class="description">
						<?php do_action( 'wc_price_history_settings_page_rate_us_text' ); ?>
					</p>

				</div>
			</div>
		</div>
		<?php
	}
}
