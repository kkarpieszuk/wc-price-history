<?php

namespace PriorPrice;

class AdminAssets {

	/**
	 * Register hooks.
	 *
	 * @since 1.2
	 *
	 * @return void
	 */
	public function register_hooks() {
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
	}

	/**
	 * Enqueue scripts.
	 *
	 * @since 1.2
	 *
	 * @return void
	 */
	public function enqueue_scripts() {

		wp_enqueue_script( 'wc-price-history-admin', WC_PRICE_HISTORY_PLUGIN_URL . 'assets/js/admin.js', [ 'jquery' ], WC_PRICE_HISTORY_VERSION, true );

		wp_enqueue_script( 'wc-price-history-export', WC_PRICE_HISTORY_PLUGIN_URL . 'assets/js/export.js', [ 'jquery' ], WC_PRICE_HISTORY_VERSION, true );

		$nonce = wp_create_nonce( 'wc_price_history' );

		wp_localize_script( 'wc-price-history-admin', 'wc_price_history_admin', [
			'ajax_url'                         => admin_url( 'admin-ajax.php' ),
			'first_scan_finished_notice_nonce' => $nonce,
			'clean_history_confirm'            => esc_html__( 'Are you sure you want to delete all price history?', 'wc-price-history' ),
			'clean_history_nonce'              => $nonce,
			'clean_history_success'            => esc_html__( 'Price history has been deleted.', 'wc-price-history' ),
			'clean_history_error'              => esc_html__( 'An error occurred while deleting price history.', 'wc-price-history' ),
			'fix_history_confirm'              => esc_html__( 'Are you sure you want to fix price history?', 'wc-price-history' ),
			'force_first_scan_end_confirm'     => esc_html__( 'Are you sure you want to force first scan to finish?', 'wc-price-history' ),
			'restart_first_scan_confirm'       => esc_html__( 'Are you sure you want to restart first scan?', 'wc-price-history' ),
			'fix_history_nonce'                => $nonce,
			'force_first_scan_nonce'           => $nonce,
			'fix_history_success'              => esc_html__( 'Price history has been fixed.', 'wc-price-history' ),
			'fix_history_error'                => esc_html__( 'An error occurred while fixing price history.', 'wc-price-history' ),
		] );

		wp_localize_script( 'wc-price-history-export', 'wc_price_history_export', [
			'nonce' => $nonce,
		] );

		// Enqueue migration scripts if migration is pending or in progress.
		$this->maybe_enqueue_migration_scripts( $nonce );

		if ( ! $this->is_settings_page() && ! $this->is_product_edit_page() ) {
			return;
		}

		wp_enqueue_style( 'wc-price-history-admin', WC_PRICE_HISTORY_PLUGIN_URL . 'assets/css/admin.css', [], WC_PRICE_HISTORY_VERSION );
	}

	/**
	 * Maybe enqueue migration scripts.
	 *
	 * @since {VERSION}
	 *
	 * @param string $nonce Nonce.
	 *
	 * @return void
	 */
	private function maybe_enqueue_migration_scripts( string $nonce ): void {
		$migration_status = \PriorPrice\Database\DbMigration::get_migration_status( true );

		if ( $migration_status === \PriorPrice\Database\DbMigration::STATUS_NOT_NEEDED ) {
			return;
		}

		wp_enqueue_script(
			'wc-price-history-migration',
			WC_PRICE_HISTORY_PLUGIN_URL . 'assets/js/migration.js',
			[],
			WC_PRICE_HISTORY_VERSION,
			true
		);

		wp_enqueue_style(
			'wc-price-history-migration',
			WC_PRICE_HISTORY_PLUGIN_URL . 'assets/css/migration.css',
			[],
			WC_PRICE_HISTORY_VERSION
		);

		wp_localize_script(
			'wc-price-history-migration',
			'wcPriceHistoryMigration',
			[
				'nonce' => $nonce,
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'i18n' => [
					'processing' => esc_html__( 'Processing...', 'wc-price-history' ),
					'error' => esc_html__( 'An error occurred during migration.', 'wc-price-history' ),
					'errorTitle' => esc_html__( 'Migration Error', 'wc-price-history' ),
					'retry' => esc_html__( 'Retry Migration', 'wc-price-history' ),
					'inProgress' => esc_html__( 'Database update in progress...', 'wc-price-history' ),
				],
			]
		);
	}

	private function is_product_edit_page() : bool {

		if ( ! function_exists( 'get_current_screen' ) ) {
			return false;
		}

		$screen = get_current_screen();

		if ( ! $screen ) {
			return false;
		}

		return $screen->post_type === 'product' && $screen->base === 'post';
	}

	private function is_settings_page() : bool {
		return isset( $_GET['page'] ) && $_GET['page'] === 'wc-price-history'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}
}
