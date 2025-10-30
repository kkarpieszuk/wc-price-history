<?php

namespace PriorPrice;

use PriorPrice\Database\AdminNotice;

/**
 * Ajax class.
 *
 * @since 2.0
 */
class Ajax {

	/**
	 * First scan.
	 *
	 * @since {VERSION}
	 *
	 * @var FirstScan
	 */
	private $first_scan;

	/**
	 * Constructor.
	 *
	 * @since {VERSION}
	 *
	 * @param FirstScan $first_scan First scan.
	 */
	public function __construct( FirstScan $first_scan ) {

		$this->first_scan = $first_scan;
	}

	/**
	 * Register hooks.
	 *
	 * @since 2.0
	 */
	public function register_hooks() : void {

		add_action( 'wp_ajax_wc_price_history_first_scan_finished_notice_dismissed', [ $this, 'first_scan_finished_notice_dismissed' ] );
		add_action( 'wp_ajax_wc_price_history_clean_history', [ $this, 'clean_history' ] );
		add_action( 'wp_ajax_wc_price_history_fix_history', [ $this, 'fix_history' ] );
		add_action( 'wp_ajax_wc_price_history_force_first_scan_end', [ $this, 'force_first_scan_end' ] );
		add_action( 'wp_ajax_wc_price_history_restart_first_scan', [ $this, 'restart_first_scan' ] );
		add_action( 'wp_ajax_wc_price_history_migrate_batch', [ $this, 'migrate_batch' ] );
	}

	/**
	 * Dismiss first scan finished notice.
	 *
	 * @since 2.0
	 *
	 * @return void
	 */
	public function first_scan_finished_notice_dismissed(): void {

		if ( ! check_ajax_referer( 'wc_price_history', 'security', false ) ) {
			wp_send_json_error( [ 'message' => esc_html__( 'Invalid nonce', 'wc-price-history' ) ] );
		}

		update_user_meta( get_current_user_id(), FirstScan::USER_OPTION_NAME, true );

		wp_send_json_success();
	}

	public function clean_history(): void {

		if ( ! check_ajax_referer( 'wc_price_history', 'security', false ) ) {
			wp_send_json_error( [ 'message' => esc_html__( 'Invalid nonce', 'wc-price-history' ) ] );
		}

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( [ 'message' => esc_html__( 'You do not have permission to clean history', 'wc-price-history' ) ] );
		}

		$history_storage = new HistoryStorage();
		$history_storage->clean_history();

		wp_send_json_success();
	}

	public function fix_history(): void {

		if ( ! check_ajax_referer( 'wc_price_history', 'security', false ) ) {
			wp_send_json_error( [ 'message' => esc_html__( 'Invalid nonce', 'wc-price-history' ) ] );
		}

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( [ 'message' => esc_html__( 'You do not have permission to fix history', 'wc-price-history' ) ] );
		}

		$history_storage = new HistoryStorage();
		$history_storage->fix_history();

		wp_send_json_success();
	}

	public function force_first_scan_end(): void {

		if ( ! check_ajax_referer( 'wc_price_history', 'security', false ) ) {
			wp_send_json_error( [ 'message' => esc_html__( 'Invalid nonce', 'wc-price-history' ) ] );
		}

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( [ 'message' => esc_html__( 'You do not have permission to force first scan end', 'wc-price-history' ) ] );
		}

		$this->first_scan->force_end();

		wp_send_json_success();
	}

	public function restart_first_scan(): void {

		if ( ! check_ajax_referer( 'wc_price_history', 'security', false ) ) {
			wp_send_json_error( [ 'message' => esc_html__( 'Invalid nonce', 'wc-price-history' ) ] );
		}

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( [ 'message' => esc_html__( 'You do not have permission to restart first scan', 'wc-price-history' ) ] );
		}

		$this->first_scan->restart();

		wp_send_json_success();
	}

	/**
	 * Migrate batch of products to database tables.
	 *
	 * @since {VERSION}
	 *
	 * @return void
	 */
	public function migrate_batch(): void {

		if ( ! check_ajax_referer( 'wc_price_history', 'security', false ) ) {
			wp_send_json_error( [ 'message' => esc_html__( 'Invalid nonce', 'wc-price-history' ) ] );
		}

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( [ 'message' => esc_html__( 'You do not have permission to run migration', 'wc-price-history' ) ] );
		}

		$migration = new \PriorPrice\Database\DbMigration();
		$result = $migration->migrate_batch();

		wp_send_json_success( $result );
	}
}