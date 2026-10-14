<?php

namespace PriorPrice;

use PriorPrice\Helpers\AdminScreen;

/**
 * Warn merchants when sale-start Omnibus counting cannot resolve a sale start date.
 *
 * @since 3.2.8
 */
class SaleStartWarning {

	const AJAX_ACTION = 'wc_price_history_sale_start_warning_check';

	const NOTICE_ID = 'wc-price-history-sale-start-warning';

	/**
	 * Settings data.
	 *
	 * @var SettingsData
	 */
	private $settings_data;

	/**
	 * Cached sale-start count_from check for the current request.
	 *
	 * @var bool|null
	 */
	private $sale_start_count_from_cached = null;

	/**
	 * Constructor.
	 *
	 * @since 3.2.8
	 *
	 * @param SettingsData $settings_data Settings data.
	 */
	public function __construct( SettingsData $settings_data ) {
		$this->settings_data = $settings_data;
	}

	/**
	 * Register hooks.
	 *
	 * @since 3.2.8
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_action( 'admin_notices', [ $this, 'maybe_display_product_edit_notice' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'maybe_enqueue_block_editor_notice' ] );
		add_action( 'wp_ajax_' . self::AJAX_ACTION, [ $this, 'ajax_check_product_warning' ] );
		add_filter( 'display_post_states', [ $this, 'maybe_add_product_list_state' ], 10, 2 );
	}

	/**
	 * Display warning on the classic product edit screen.
	 *
	 * @since 3.2.8
	 *
	 * @return void
	 */
	public function maybe_display_product_edit_notice(): void {
		if ( ! AdminScreen::is_product_edit_page() ) {
			return;
		}

		if ( ! $this->is_sale_start_count_from() ) {
			return;
		}

		$product_id = AdminScreen::get_edited_product_id();
		if ( $product_id <= 0 || ! $this->product_id_needs_warning( $product_id ) ) {
			return;
		}

		$this->render_classic_notice();
	}

	/**
	 * Enqueue SPA-aware core/notices script for WooCommerce Admin product editor.
	 *
	 * Loaded on all wc-admin pages when sale-start counting is enabled so soft
	 * navigation between products can show/hide/update the notice.
	 *
	 * @since 3.2.8
	 *
	 * @return void
	 */
	public function maybe_enqueue_block_editor_notice(): void {
		if ( ! AdminScreen::is_wc_admin_page() ) {
			return;
		}

		if ( ! $this->is_sale_start_count_from() ) {
			return;
		}

		if ( ! current_user_can( 'edit_products' ) ) {
			return;
		}

		wp_enqueue_script(
			'wc-price-history-sale-start-warning',
			WC_PRICE_HISTORY_PLUGIN_URL . 'assets/js/sale-start-warning.js',
			[ 'jquery', 'wp-data', 'wp-notices' ],
			WC_PRICE_HISTORY_VERSION,
			true
		);

		wp_localize_script(
			'wc-price-history-sale-start-warning',
			'wcPriceHistorySaleStartWarning',
			[
				'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
				'action'    => self::AJAX_ACTION,
				'nonce'     => wp_create_nonce( 'wc_price_history' ),
				'noticeId'  => self::NOTICE_ID,
				'title'     => __( 'Price history issues', 'wc-price-history' ),
				'message'   => $this->get_warning_message(),
				'initialProductId' => AdminScreen::get_edited_product_id(),
			]
		);
	}

	/**
	 * AJAX: whether a product needs the sale-start warning (for SPA route changes).
	 *
	 * @since 3.2.8
	 *
	 * @return void
	 */
	public function ajax_check_product_warning(): void {
		if ( ! check_ajax_referer( 'wc_price_history', 'security', false ) ) {
			wp_send_json_error( [ 'message' => esc_html__( 'Invalid nonce', 'wc-price-history' ) ], 403 );
		}

		if ( ! current_user_can( 'edit_products' ) ) {
			wp_send_json_error( [ 'message' => esc_html__( 'Forbidden', 'wc-price-history' ) ], 403 );
		}

		if ( ! $this->is_sale_start_count_from() ) {
			wp_send_json_success(
				[
					'needsWarning' => false,
				]
			);
		}

		$product_id = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing

		wp_send_json_success(
			[
				'needsWarning' => $product_id > 0 && $this->product_id_needs_warning( $product_id ),
				'title'        => __( 'Price history issues', 'wc-price-history' ),
				'message'      => $this->get_warning_message(),
			]
		);
	}

	/**
	 * Add a compact marker on the products list.
	 *
	 * @since 3.2.8
	 *
	 * @param array<string, string> $post_states Post states.
	 * @param \WP_Post              $post        Post.
	 *
	 * @return array<string, string>
	 */
	public function maybe_add_product_list_state( array $post_states, $post ): array {
		if ( ! $post instanceof \WP_Post || $post->post_type !== 'product' ) {
			return $post_states;
		}

		if ( ! $this->is_sale_start_count_from() ) {
			return $post_states;
		}

		if ( ! $this->product_id_needs_warning( (int) $post->ID ) ) {
			return $post_states;
		}

		$post_states['wc_price_history_sale_start'] = sprintf(
			'<span class="wc-price-history-issue-badge">%s</span>',
			esc_html__( 'Price history issue', 'wc-price-history' )
		);

		return $post_states;
	}

	/**
	 * Whether a product ID needs the sale-start warning.
	 *
	 * Uses a light meta-based check (no get_available_variations) and warns when
	 * any on-sale variation is missing a sale start date (partial coverage).
	 *
	 * @since 3.2.8
	 *
	 * @param int $product_id Product ID.
	 *
	 * @return bool
	 */
	private function product_id_needs_warning( int $product_id ): bool {
		if ( $product_id <= 0 || ! function_exists( 'wc_get_product' ) ) {
			return false;
		}

		$product = wc_get_product( $product_id );
		if ( ! $product instanceof \WC_Product ) {
			return false;
		}

		return $this->needs_warning( $product );
	}

	/**
	 * Render classic admin notice HTML.
	 *
	 * @since 3.2.8
	 *
	 * @return void
	 */
	private function render_classic_notice(): void {
		?>
		<div class="notice notice-warning is-dismissible wc-price-history-sale-start-warning">
			<p>
				<strong><?php esc_html_e( 'Price history issues', 'wc-price-history' ); ?></strong>
			</p>
			<p>
				<?php echo esc_html( $this->get_warning_message() ); ?>
			</p>
		</div>
		<?php
	}

	/**
	 * Warning message body (shared by classic and block editor surfaces).
	 *
	 * @since 3.2.8
	 *
	 * @return string
	 */
	private function get_warning_message(): string {
		return __(
			'You configured the lowest price relative to the sale start, but this product (or at least one on-sale variation) has no scheduled sale start date. Set a sale start date on the promotional price so Omnibus counting can work correctly.',
			'wc-price-history'
		);
	}

	/**
	 * Whether the product needs a sale-start configuration warning.
	 *
	 * @since 3.2.8
	 *
	 * @param \WC_Product $product Product.
	 *
	 * @return bool
	 */
	private function needs_warning( \WC_Product $product ): bool {
		if ( ! $this->is_sale_start_count_from() ) {
			return false;
		}

		if ( ! $product->is_on_sale() ) {
			return false;
		}

		if ( $product->is_type( 'variable' ) ) {
			return $this->variable_needs_warning( $product );
		}

		return ! $this->has_sale_start_meta( $product->get_id() );
	}

	/**
	 * Variable product warning: any on-sale variation without a sale start date.
	 *
	 * Uses get_children() + post meta instead of get_available_variations().
	 *
	 * @since 3.2.8
	 *
	 * @param \WC_Product $product Variable product.
	 *
	 * @return bool
	 */
	private function variable_needs_warning( \WC_Product $product ): bool {
		$children = $product->get_children();
		if ( $children === [] ) {
			return ! $this->has_sale_start_meta( $product->get_id() );
		}

		$found_on_sale = false;

		foreach ( $children as $child_id ) {
			$child_id = (int) $child_id;
			if ( ! $this->variation_is_on_sale_light( $child_id ) ) {
				continue;
			}

			$found_on_sale = true;

			if ( ! $this->has_sale_start_meta( $child_id ) ) {
				return true;
			}
		}

		if ( ! $found_on_sale ) {
			return ! $this->has_sale_start_meta( $product->get_id() );
		}

		return false;
	}

	/**
	 * Light on-sale check for a variation via post meta (no full WC_Product_Variation load path for schedules beyond meta).
	 *
	 * @since 3.2.8
	 *
	 * @param int $variation_id Variation ID.
	 *
	 * @return bool
	 */
	private function variation_is_on_sale_light( int $variation_id ): bool {
		$sale_price = \get_post_meta( $variation_id, '_sale_price', true );
		if ( $sale_price === '' || $sale_price === null ) {
			return false;
		}

		$now = time();

		$from = \get_post_meta( $variation_id, '_sale_price_dates_from', true );
		if ( $from !== '' && $from !== null && (int) $from > $now ) {
			return false;
		}

		$to = \get_post_meta( $variation_id, '_sale_price_dates_to', true );
		if ( $to !== '' && $to !== null && (int) $to < $now ) {
			return false;
		}

		return true;
	}

	/**
	 * Whether sale start date meta is set for a product/variation.
	 *
	 * @since 3.2.8
	 *
	 * @param int $product_id Product or variation ID.
	 *
	 * @return bool
	 */
	private function has_sale_start_meta( int $product_id ): bool {
		$from = \get_post_meta( $product_id, '_sale_price_dates_from', true );

		return $from !== '' && $from !== null && (int) $from > 0;
	}

	/**
	 * Whether count_from is based on sale start.
	 *
	 * Cached for the request so products list can early-exit cheaply.
	 *
	 * @since 3.2.8
	 *
	 * @return bool
	 */
	private function is_sale_start_count_from(): bool {
		if ( $this->sale_start_count_from_cached === null ) {
			$count_from = $this->settings_data->get_count_from();

			$this->sale_start_count_from_cached = in_array(
				$count_from,
				[ 'sale_start', 'sale_start_inclusive' ],
				true
			);
		}

		return $this->sale_start_count_from_cached;
	}
}
