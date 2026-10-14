<?php

namespace PriorPrice;

use PriorPrice\Helpers\AdminScreen;

/**
 * Warn merchants when sale-start Omnibus counting cannot resolve a sale start date.
 *
 * @since 3.2.8
 */
class SaleStartWarning {

	/**
	 * Settings data.
	 *
	 * @var SettingsData
	 */
	private $settings_data;

	/**
	 * Sale start date resolver.
	 *
	 * @var SaleStartDate
	 */
	private $sale_start_date;

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
	 * @param SettingsData       $settings_data   Settings data.
	 * @param SaleStartDate|null $sale_start_date Sale start date resolver.
	 */
	public function __construct( SettingsData $settings_data, ?SaleStartDate $sale_start_date = null ) {
		$this->settings_data   = $settings_data;
		$this->sale_start_date = $sale_start_date ?? new SaleStartDate();
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

		$product = $this->get_edited_product_needing_warning();
		if ( ! $product ) {
			return;
		}

		$this->render_classic_notice();
	}

	/**
	 * Enqueue core/notices warning for the WooCommerce block product editor.
	 *
	 * Classic admin_notices are hidden on wc-admin pages; use wp.data notices instead.
	 *
	 * @since 3.2.8
	 *
	 * @return void
	 */
	public function maybe_enqueue_block_editor_notice(): void {
		if ( ! AdminScreen::is_block_product_edit_page() ) {
			return;
		}

		if ( ! $this->is_sale_start_count_from() ) {
			return;
		}

		$product = $this->get_edited_product_needing_warning();
		if ( ! $product ) {
			return;
		}

		wp_enqueue_script(
			'wc-price-history-sale-start-warning',
			WC_PRICE_HISTORY_PLUGIN_URL . 'assets/js/sale-start-warning.js',
			[ 'wp-data', 'wp-notices' ],
			WC_PRICE_HISTORY_VERSION,
			true
		);

		wp_localize_script(
			'wc-price-history-sale-start-warning',
			'wcPriceHistorySaleStartWarning',
			[
				'show'    => true,
				'message' => $this->get_warning_message(),
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

		if ( ! function_exists( 'wc_get_product' ) ) {
			return $post_states;
		}

		$product = wc_get_product( $post->ID );
		if ( ! $product instanceof \WC_Product ) {
			return $post_states;
		}

		if ( ! $this->needs_warning( $product ) ) {
			return $post_states;
		}

		$post_states['wc_price_history_sale_start'] = sprintf(
			'<span class="wc-price-history-issue-badge">%s</span>',
			esc_html__( 'Price history issue', 'wc-price-history' )
		);

		return $post_states;
	}

	/**
	 * Get the product currently being edited when it needs a warning.
	 *
	 * @since 3.2.8
	 *
	 * @return \WC_Product|null
	 */
	private function get_edited_product_needing_warning(): ?\WC_Product {
		$product_id = AdminScreen::get_edited_product_id();
		if ( $product_id <= 0 || ! function_exists( 'wc_get_product' ) ) {
			return null;
		}

		$product = wc_get_product( $product_id );
		if ( ! $product instanceof \WC_Product ) {
			return null;
		}

		if ( ! $this->needs_warning( $product ) ) {
			return null;
		}

		return $product;
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
		<div class="notice notice-warning wc-price-history-sale-start-warning">
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
			'You configured the lowest price relative to the sale start, but this product has no scheduled sale start date. Set at least a sale start date on the promotional price so Omnibus counting can work correctly.',
			'wc-price-history'
		);
	}

	/**
	 * Whether the product needs a sale-start configuration warning.
	 *
	 * Caller must already know count_from is sale-start based when performance matters
	 * (e.g. products list), or rely on the check inside this method.
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

		return $this->sale_start_date->resolve( $product ) === null;
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
