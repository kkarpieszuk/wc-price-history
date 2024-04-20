<?php

namespace PriorPrice;

/**
 * Ajax class.
 *
 * @since 2.0
 */
class Ajax {

	/**
	 * Register hooks.
	 *
	 * @since 2.0
	 */
	public function register_hooks() : void {

		add_action( 'wp_ajax_wc_price_history_first_scan_finished_notice_dismissed', [ $this, 'first_scan_finished_notice_dismissed' ] );
		add_action( 'wp_ajax_wc_price_history_clean_history', [ $this, 'clean_history' ] );
		add_action( 'wp_ajax_wc_price_history_fix_history', [ $this, 'fix_history' ] );
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

		$history_storage = new HistoryStorage();
		$history_storage->clean_history();

		wp_send_json_success();
	}

	public function fix_history(): void {

		if ( ! check_ajax_referer( 'wc_price_history', 'security', false ) ) {
			wp_send_json_error( [ 'message' => esc_html__( 'Invalid nonce', 'wc-price-history' ) ] );
		}

		$history_storage = new HistoryStorage();
		$history_storage->fix_history();

		wp_send_json_success();
	}
}