<?php

namespace PriorPrice\C11y;

/**
 * Compatibility with WP Sheet Editor (Woo Products Bulk Editor).
 *
 * WP Sheet Editor temporarily sets regular_price to 999999999999999 in _sync_product_lookup_table()
 * to force WooCommerce to run update_lookup_table(), then restores the real price. We skip recording
 * that sentinel price only when the save is coming from WPSE (plugin active + AJAX from the spreadsheet).
 *
 * @since {VERSION}
 */
final class WPSheetEditor {

	/**
	 * Sentinel price used by WP Sheet Editor in _sync_product_lookup_table().
	 *
	 * @var float
	 */
	private const SENTINEL_PRICE = 999999999999999.0;

	/**
	 * Max price treated as valid when set by WPSE. Higher values are never recorded.
	 *
	 * @var float
	 */
	private const MAX_REASONABLE_PRICE = 1e12;

	/**
	 * True while the filter vg_sheet_editor/save_rows/response is running.
	 * WPSE sets is_saving_cells = false before calling that filter, so callbacks triggered
	 * by the filter see is_saving_cells = false. We set this flag at filter start and clear at end.
	 *
	 * @var bool
	 */
	private static $response_filter_ongoing = false;

	/**
	 * Whether the current price should be skipped for history recording.
	 *
	 * Skip only when: (1) price is the known sentinel or >= max reasonable, AND
	 * (2) WP Sheet Editor is active, AND (3) we're in an AJAX request from WPSE (is_saving_cells).
	 * This way we never skip a price that the user explicitly set (e.g. in product edit screen).
	 *
	 * @since {VERSION}
	 *
	 * @param int   $product_id Product ID (unused for now; available for future context).
	 * @param float $price      Price to check.
	 *
	 * @return bool True if this price should not be recorded.
	 */
	public function should_skip_price_recording( int $product_id, float $price ): bool {
		$is_sentinel_or_absurd = ( $price === self::SENTINEL_PRICE || $price >= self::MAX_REASONABLE_PRICE );
		if ( ! $is_sentinel_or_absurd ) {
			return false;
		}

		if ( ! $this->is_wpse_active() ) {
			return false;
		}

		if ( ! wp_doing_ajax() ) {
			return false;
		}

		return $this->is_wpse_saving_cells() || self::$response_filter_ongoing;
	}

	/**
	 * Register filters to set response_filter_ongoing around vg_sheet_editor/save_rows/response.
	 * Call once from the main plugin (e.g. Hooks).
	 *
	 * @since {VERSION}
	 */
	public static function register_response_filter_flag(): void {
		static $registered = false;
		if ( $registered ) {
			return;
		}
		$registered = true;
		add_filter( 'vg_sheet_editor/save_rows/response', [ __CLASS__, 'filter_response_set_flag_true' ], 1, 5 );
		add_filter( 'vg_sheet_editor/save_rows/response', [ __CLASS__, 'filter_response_set_flag_false' ], 99999, 5 );
	}

	/**
	 * Filter to set response_filter_ongoing flag to true.
	 *
	 * @since {VERSION}
	 *
	 * @param mixed $value Filter value.
	 * @param mixed[] $data Data.
	 * @param string $post_type Post type.
	 * @param mixed[] $spreadsheet_columns Columns.
	 * @param mixed[] $settings Settings.
	 * @return mixed
	 */
	public static function filter_response_set_flag_true( $value, array $data, string $post_type, array $spreadsheet_columns, array $settings ) {
		self::$response_filter_ongoing = true;
		return $value;
	}

	/**
	 * @param mixed $value Filter value.
	 * @param mixed[] $data Data.
	 * @param string $post_type Post type.
	 * @param mixed[] $spreadsheet_columns Columns.
	 * @param mixed[] $settings Settings.
	 *
	 * @since {VERSION}
	 *
	 * @return mixed
	 */
	public static function filter_response_set_flag_false( $value, array $data, string $post_type, array $spreadsheet_columns, array $settings ) {
		self::$response_filter_ongoing = false;
		return $value;
	}

	/**
	 * Whether WP Sheet Editor (or Woo Products Bulk Editor) is active.
	 *
	 * @since {VERSION}
	 *
	 * @return bool
	 */
	private function is_wpse_active(): bool {
		return function_exists( 'VGSE' );
	}

	/**
	 * Whether the current request is inside WPSE's save_rows flow (spreadsheet save).
	 *
	 * When WPSE runs _sync_product_lookup_table() it sets helpers->is_saving_cells = true
	 * before the temporary save, so we can detect the sentinel save context.
	 *
	 * @since {VERSION}
	 *
	 * @return bool
	 */
	private function is_wpse_saving_cells(): bool {
		if ( ! $this->is_wpse_active() ) {
			return false;
		}
		/** @var object|null $vgse Provided by WP Sheet Editor plugin when active. */
		/** @phpstan-ignore-next-line */
		$vgse = function_exists( 'VGSE' ) ? call_user_func( 'VGSE' ) : null;
		return $vgse !== null && isset( $vgse->helpers ) && ! empty( $vgse->helpers->is_saving_cells );
	}
}
