<?php

namespace PriorPrice;

use PriorPrice\Database\DbMigration;
use PriorPrice\Database\Install;
use WC_Product;
use WC_Product_Variable;

/**
 * Export class.
 *
 * @since 2.1.3
 */
class Export {

	/**
	 * @var \PriorPrice\HistoryStorage
	 */
	private $history_storage;

	/**
	 * @var \PriorPrice\SettingsData
	 */
	private $settings_data;

	/**
	 * Constructor.
	 *
	 * @since 2.1.3
	 */
	public function __construct( HistoryStorage $history_storage, SettingsData $settings_data ) {

		$this->history_storage = $history_storage;
		$this->settings_data   = $settings_data;
	}

	/**
	 * Register hooks.
	 *
	 * @since 2.1.3
	 */
	public function register_hooks(): void {
		// Add metabox on product edit page.
		add_action( 'add_meta_boxes', [ $this, 'add_meta_box' ] );

		add_action( 'wp_ajax_wc_price_history_export_product_with_price_history', [ $this, 'export_product_with_price_history' ] );
	}

	/**
	 * Add metabox to product edit page.
	 *
	 * @since 2.1.3
	 */
	public function add_meta_box(): void {

		add_meta_box(
			'wc_price_history_export',
			esc_html__( 'Price History', 'wc-price-history' ),
			[ $this, 'render_meta_box' ],
			'product',
			'side',
			'default'
		);
	}

	/**
	 * Render metabox.
	 *
	 * @since 2.1.3
	 */
	public function render_meta_box(): void {

		$product = wc_get_product();

		if ( ! $product ) {
			return;
		}

		?>
		<p>
			<button type="button"
				data-product-id="<?php echo intval( $product->get_id() ); ?>"
				class="button button-secondary"
				id="wc-price-history-export-product-with-price-history">
				<?php esc_html_e( 'Export debug data', 'wc-price-history' ); ?>
			</button>
		</p>
		<p class="description">
			<?php esc_html_e( 'Export product with price history to JSON file. Use it only for debugging purposes.', 'wc-price-history' ); ?>
		</p>
		<?php
	}

	/**
	 * Export product with price history.
	 *
	 * @since 2.1.3
	 *
	 * @return void
	 */
	public function export_product_with_price_history() {

		if ( ! check_ajax_referer( 'wc_price_history', 'security', false ) ) {
			wp_send_json_error( [ 'message' => esc_html__( 'Invalid nonce', 'wc-price-history' ) ] );
		}

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( [ 'message' => esc_html__( 'You do not have permission to export data', 'wc-price-history' ) ] );
		}

		$product_id = intval( wp_unslash( $_POST['product_id'] ?? '' ) );

		if ( ! $product_id ) {
			wp_send_json_error( [ 'message' => esc_html__( 'Invalid product ID', 'wc-price-history' ) ] );
		}

		$product = wc_get_product( $product_id );

		if ( ! $product ) {
			wp_send_json_error( [ 'message' => esc_html__( 'Product not found', 'wc-price-history' ) ] );
		}

		$history = $this->history_storage->get_history( $product_id );

		$plugin_settings = array_merge(
			$this->settings_data->get_settings(),
			[ 'storage_method' => $this->get_storage_method_export_data() ]
		);

		$product_data = [
			'regular_price' => $product->get_regular_price(),
			'sale_price'    => $product->get_sale_price(),
			'product_id'    => $product_id,
			'product_name'  => $product->get_name(),
			'permalink'     => $product->get_permalink(),
			'attributes'    => $product->get_attributes( 'edit' ),
			'history'       => $history,
		];

		$export_data = [
			'settings' => $plugin_settings,
			'product'  => $product_data,
		];

		if ( $product->is_type( 'variable' ) ) {
			/** @var WC_Product_Variable $product */
			$variations = $product->get_available_variations( 'objects' );

			foreach ( $variations as $variation ) {

				/** @var WC_Product $variation */
				$variation_history = $this->history_storage->get_history( $variation->get_id() );

				$variation_data = [
					'regular_price' => $variation->get_regular_price(),
					'sale_price'    => $variation->get_sale_price(),
					'product_id'    => $variation->get_id(),
					'product_name'  => $variation->get_name(),
					'permalink'     => $variation->get_permalink(),
					'attributes'    => $variation->get_attributes( 'edit' ),
					'history'       => $variation_history,
				];

				$export_data['variations'][] = $variation_data;
			}

		}

		$result = [
			'product_name' => $product->get_name(),
			'serialized'   => serialize( $export_data ),
		];

		wp_send_json_success( $result );
	}

	/**
	 * Get storage method detailed info for export (same as in WC > Price History settings right column).
	 *
	 * @since {VERSION}
	 *
	 * @return array<string, mixed> Storage method details.
	 */
	private function get_storage_method_export_data(): array {
		$storage = new HistoryStorage();
		$uses_tables = $storage->should_use_tables();

		$data = [
			'method'        => $uses_tables ? 'database_tables' : 'post_meta',
			'method_label'  => $uses_tables
				? __( 'Database tables', 'wc-price-history' )
				: __( 'Post meta (legacy)', 'wc-price-history' ),
		];

		if ( $uses_tables ) {
			$migration_status = DbMigration::get_migration_status( true );
			$data['migration_status'] = $migration_status;
			if ( $migration_status === DbMigration::STATUS_COMPLETED ) {
				$data['migration_status_label'] = __( 'Migration from post meta has been completed.', 'wc-price-history' );
			} elseif ( $migration_status === DbMigration::STATUS_NOT_NEEDED ) {
				$data['migration_status_label'] = __( 'Migration was not needed (e.g. fresh install with database tables).', 'wc-price-history' );
			} elseif ( $migration_status === false ) {
				$data['migration_status_label'] = __( 'Migration status is not set.', 'wc-price-history' );
			} else {
				$data['migration_status_label'] = sprintf(
					__( 'Migration status is %s.', 'wc-price-history' ),
					$migration_status
				);
			}

			global $wpdb;
			$table_names = Install::get_table_names();
			$data['tables'] = [];
			foreach ( $table_names as $table_name ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.DirectQuery
				$exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) ) === $table_name;
				$row = [
					'name'   => $table_name,
					'exists' => $exists,
				];
				if ( $exists ) {
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.DirectQuery
					$row['row_count'] = (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$table_name}`" );
				} else {
					$row['row_count'] = null;
				}
				$data['tables'][] = $row;
			}
		} else {
			$data['description'] = __( 'The plugin is using post meta for storing price history. Consider migrating to database tables for better performance.', 'wc-price-history' );
		}

		return $data;
	}
}