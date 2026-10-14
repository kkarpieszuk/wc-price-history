<?php

namespace PriorPrice;

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
		add_filter( 'display_post_states', [ $this, 'maybe_add_product_list_state' ], 10, 2 );
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
	public function needs_warning( \WC_Product $product ): bool {
		if ( ! $this->is_sale_start_count_from() ) {
			return false;
		}

		if ( ! $product->is_on_sale() ) {
			return false;
		}

		return $this->sale_start_date->resolve( $product ) === null;
	}

	/**
	 * Display warning on the product edit screen.
	 *
	 * @since 3.2.8
	 *
	 * @return void
	 */
	public function maybe_display_product_edit_notice(): void {
		if ( ! $this->is_product_edit_screen() ) {
			return;
		}

		$product_id = $this->get_edited_product_id();
		if ( $product_id <= 0 ) {
			return;
		}

		$product = wc_get_product( $product_id );
		if ( ! $product instanceof \WC_Product ) {
			return;
		}

		if ( ! $this->needs_warning( $product ) ) {
			return;
		}

		?>
		<div class="notice notice-warning wc-price-history-sale-start-warning">
			<p>
				<strong><?php esc_html_e( 'Price history issues', 'wc-price-history' ); ?></strong>
			</p>
			<p>
				<?php
				esc_html_e(
					'You configured the lowest price relative to the sale start, but this product has no scheduled sale start date. Set at least a sale start date on the promotional price so Omnibus counting can work correctly.',
					'wc-price-history'
				);
				?>
			</p>
		</div>
		<?php
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
	 * Whether count_from is based on sale start.
	 *
	 * @since 3.2.8
	 *
	 * @return bool
	 */
	private function is_sale_start_count_from(): bool {
		$count_from = $this->settings_data->get_count_from();

		return in_array( $count_from, [ 'sale_start', 'sale_start_inclusive' ], true );
	}

	/**
	 * Whether the current screen is a product edit screen.
	 *
	 * @since 3.2.8
	 *
	 * @return bool
	 */
	private function is_product_edit_screen(): bool {
		if ( ! function_exists( 'get_current_screen' ) ) {
			return false;
		}

		$screen = get_current_screen();
		if ( ! $screen ) {
			return false;
		}

		return $screen->post_type === 'product' && $screen->base === 'post';
	}

	/**
	 * Get the product ID being edited.
	 *
	 * @since 3.2.8
	 *
	 * @return int
	 */
	private function get_edited_product_id(): int {
		if ( ! isset( $_GET['post'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return 0;
		}

		return absint( $_GET['post'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}
}
