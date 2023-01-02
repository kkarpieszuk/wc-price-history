<?php

namespace PriorPrice;

class Migrations {

	/**
	 * @var \PriorPrice\HistoryStorage
	 */
	private $history_storage;

	public function __construct( HistoryStorage $history_storage ) {

		$this->history_storage = $history_storage;
	}

	/**
	 * Register hooks.
	 *
	 * @since 1.1
	 */
	public function register_hooks(): void {

		add_action( 'plugins_loaded', [ $this, 'migrate_1_1' ] );

		$products_to_migrate = get_option( 'wc_price_history_products_to_migrate', false );
		if ( is_array( $products_to_migrate ) && ! empty( $products_to_migrate ) ) {
			add_filter( 'woocommerce_register_post_type_product', [ $this, 'enable_product_revisions' ] );
		}
	}

	/**
	 * Enable product revisions.
	 *
	 * @since 1.1
	 *
	 * @param array<array<string>> $args Register post type args.
	 *
	 * @return array<array<string>>
	 */
	public function enable_product_revisions( array $args ): array {

		$args['supports'][] = 'revisions';

		return $args;
	}

	/**
	 * Migrate data on update to version 1.1.
	 *
	 * @since 1.1
	 */
	public function migrate_1_1(): void {

		$products_to_migrate = get_option( 'wc_price_history_products_to_migrate', 'migration_never_started' );

		if ( $products_to_migrate === 'migration_never_started' ) {
			$products_to_migrate = get_posts(
				[
					'limit' => - 1,
					'status' => 'publish',
					'post_type' => 'product',
				]
			);
			update_option( 'wc_price_history_products_to_migrate', $products_to_migrate );
		} elseif ( $products_to_migrate === 'migration_finished' ) {
			return;
		} elseif( is_array( $products_to_migrate ) && empty( $products_to_migrate ) ) {
			update_option( 'wc_price_history_products_to_migrate', 'migration_finished' );
			return;
		}

		$products_to_migrate_all = $products_to_migrate;

		$products_to_migrate = array_slice( $products_to_migrate, 0, 50 );

		foreach ( $products_to_migrate as $id => $product ) {
			$revisions = wp_get_post_revisions( $product->ID, [ 'check_enabled' => false ] );
			// Migrate prices from revisions.
			foreach ( $revisions as $revision ) {
				if ( ! isset( $revision->ID ) ) {
					continue;
				}
				$revision_price = get_metadata( 'post', $revision->ID, '_price', true );
				if ( $revision_price ) {
					$revision_time = strtotime( $revision->post_modified_gmt );
					if ( $revision_time ) {
						$this->history_storage->add_historical_price( $product->ID, (float) $revision_price, $revision_time );
					}
				}
			}
			unset( $products_to_migrate_all[ $id ] );
		}

		update_option( 'wc_price_history_products_to_migrate', empty( $products_to_migrate_all ) ? 'migration_finished' : $products_to_migrate_all );
	}
}
