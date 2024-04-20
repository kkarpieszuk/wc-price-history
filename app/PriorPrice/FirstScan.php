<?php

namespace PriorPrice;

/**
 * FirstScan class
 *
 * @since 2.0
 */
class FirstScan {

	public const SCAN_NOT_STARTED = 0;

	public const SCAN_IN_PROGRESS = 1;

	public const SCAN_FINISHED = 2;

	public const SCAN_RESTARTED = 3;

	public const OPTION_NAME = 'first_history_scan';

	public const USER_OPTION_NAME = 'wc_price_history_first_scan_notice_closed';

	/**
	 * Settings data.
	 *
	 * @var SettingsData
	 */
	private $settings_data;

	/**
	 * History storage.
	 *
	 * @var HistoryStorage
	 */
	private $history_storage;

	/**
	 * FirstScan constructor.
	 *
	 * @since 2.0
	 *
	 * @param SettingsData   $settings_data   Settings data.
	 * @param HistoryStorage $history_storage History storage.
	 */
	public function __construct( SettingsData $settings_data, HistoryStorage $history_storage ) {

		$this->settings_data   = $settings_data;
		$this->history_storage = $history_storage;
	}

	/**
	 * Register hooks.
	 *
	 * @since 2.0
	 *
	 * @return void
	 */
	public function register_hooks(): void {

		add_action( 'admin_notices', [ $this, 'maybe_display_notice' ] );
		add_action( 'admin_init', [ $this, 'maybe_start_scan' ] );
	}

	/**
	 * Maybe display notice.
	 *
	 * @since 2.0
	 *
	 * @return void
	 */
	public function maybe_display_notice(): void {

		if ( self::SCAN_FINISHED === $this->settings_data->get_first_scan_status() ) {
			// check if user already closed this notice.
			if ( get_user_meta( get_current_user_id(), self::USER_OPTION_NAME, true ) ) {
				return;
			}

			?>
			<div class="notice notice-success is-dismissible" id="wc-price-history-first-scan-finished-notice">
				<h4>
					<?php esc_html_e( 'WC Price History', 'wc-price-history' );	?>
				</h4>
				<p>
					<?php
					esc_html_e( 'Success! WC Price History plugin has finished scanning products to set their initial price history.', 'wc-price-history' );
					?>
				</p>
				<p>
					<?php
					esc_html_e( 'You can now close this message and edit products as usual.', 'wc-price-history' );
					?>
				</p>
			</div>
			<?php

			return;
		}

		if ( self::SCAN_RESTARTED === $this->settings_data->get_first_scan_status() ) {
			?>
			<div class="notice notice-warning is-dismissible" id="wc-price-history-first-scan-restarted-notice">
				<h4>
					<?php esc_html_e( 'WC Price History', 'wc-price-history' );	?>
				</h4>
				<p>
					<?php
					esc_html_e( 'The scan has been restarted. Please refrain from editing products until the scan is finished.', 'wc-price-history' );
					?>
				</p>
			</div>
			<?php

			return;
		}

		?>
		<div class="notice notice-info">
			<h4>
				<?php esc_html_e( 'WC Price History', 'wc-price-history' );	?>
			</h4>
			<p>
				<?php
				esc_html_e( 'WC Price History plugin is scanning products to set their initial price history.', 'wc-price-history' );
				?>
			</p>
			<p>
				<?php
				esc_html_e( 'While this process is going on, please refrain from editing products.', 'wc-price-history' );
				?>
			</p>
			<p>
				<?php
				esc_html_e( 'We will notify you when the scan is finished.', 'wc-price-history' );
				?>
			</p>
		</div>
		<?php
	}


	/**
	 * Maybe start scan.
	 *
	 * @since 2.0
	 *
	 * @return void
	 */
	public function maybe_start_scan(): void {

		if ( isset( $_GET['wc-price-history-restart-scan'] ) ) {
			$this->settings_data->set_first_scan_status( self::SCAN_RESTARTED );

			delete_user_meta( get_current_user_id(), self::USER_OPTION_NAME );
		}

		if ( self::SCAN_FINISHED === $this->settings_data->get_first_scan_status() ) {
			return;
		}

		$products = $this->get_products_without_history();

		if ( empty( $products ) ) {
			$this->settings_data->set_first_scan_status( self::SCAN_FINISHED );

			return;
		}

		$this->settings_data->set_first_scan_status( self::SCAN_IN_PROGRESS );

		foreach ( array_slice( $products, 0, 50 ) as $product ) {
			$this->history_storage->fill_empty_history( $product->ID, [] );
		}
	}

	/**
	 * Get products without history.
	 *
	 * @since 2.0
	 *
	 * @return array<\stdClass>
	 */
	private function get_products_without_history(): array {

		global $wpdb;

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT p.ID
				FROM {$wpdb->posts} p
				LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = %s
				WHERE p.post_type = %s
				AND p.post_status = %s
				AND pm.meta_id IS NULL",
				HistoryStorage::cf_key,
				'product',
				'publish'
			)
		);
	}
}