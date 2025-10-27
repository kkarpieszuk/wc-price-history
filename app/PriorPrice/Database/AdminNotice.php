<?php

namespace PriorPrice\Database;

use PriorPrice\Database\DbMigration;

/**
 * AdminNotice class for displaying migration notices.
 *
 * @since 2.0.0
 */
class AdminNotice {

	/**
	 * Register hooks.
	 *
	 * @since 2.0.0
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_action( 'admin_notices', [ $this, 'display_notice' ] );
		add_action( 'admin_init', [ $this, 'handle_migration_start' ] );
		add_action( 'admin_init', [ $this, 'handle_dismiss_notice' ] );
	}

	/**
	 * Display migration notice.
	 *
	 * @since 2.0.0
	 *
	 * @return void
	 */
	public function display_notice(): void {
		if ( ! DbMigration::should_check_migration() ) {
			return;
		}

		// Only show on admin pages (not AJAX).
		if ( ! wp_doing_ajax() && is_admin() ) {
			// Check if migration is needed and set status to pending if necessary.
			$this->maybe_init_migration_status();
			$this->render_notice();
		}
	}

	/**
	 * Maybe initialize migration status.
	 *
	 * @since 2.0.0
	 *
	 * @return void
	 */
	private function maybe_init_migration_status(): void {
		$status = get_option( DbMigration::OPTION_MIGRATION_STATUS );

		// Don't check if migration already completed or in progress.
		if ( in_array( $status, [ DbMigration::STATUS_COMPLETED, DbMigration::STATUS_IN_PROGRESS ], true ) ) {
			return;
		}

		// If status is 'not_needed' or not set, check if migration is actually needed.
		if ( DbMigration::needs_migration() ) {
			update_option( DbMigration::OPTION_MIGRATION_STATUS, DbMigration::STATUS_PENDING );
		}
	}

	/**
	 * Handle migration start.
	 *
	 * @since 2.0.0
	 *
	 * @return void
	 */
	public function handle_migration_start(): void {
		if ( ! isset( $_GET['wc_price_history_start_migration'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		check_admin_referer( 'wc_price_history_start_migration' );

		DbMigration::init_migration();

		wp_safe_redirect( remove_query_arg( 'wc_price_history_start_migration' ) );
		exit;
	}

	/**
	 * Handle dismiss notice.
	 *
	 * @since 2.0.0
	 *
	 * @return void
	 */
	public function handle_dismiss_notice(): void {
		if ( ! isset( $_GET['wc_price_history_dismiss_migration'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		check_admin_referer( 'wc_price_history_dismiss_migration' );

		update_user_meta( get_current_user_id(), 'wc_price_history_migration_notice_dismissed', true );

		wp_safe_redirect( remove_query_arg( [ 'wc_price_history_dismiss_migration', '_wpnonce' ] ) );
		exit;
	}

	/**
	 * Render notice HTML.
	 *
	 * @since 2.0.0
	 *
	 * @return void
	 */
	private function render_notice(): void {
		$status = get_option( DbMigration::OPTION_MIGRATION_STATUS, DbMigration::STATUS_NOT_NEEDED );

		if ( $status === DbMigration::STATUS_NOT_NEEDED ) {
			return;
		}

		// Check if user has dismissed the completed notice.
		if ( $status === DbMigration::STATUS_COMPLETED ) {
			if ( get_user_meta( get_current_user_id(), 'wc_price_history_migration_notice_dismissed', true ) ) {
				return;
			}
		}

		if ( $status === DbMigration::STATUS_PENDING ) {
			$this->render_pending_notice();
		} elseif ( $status === DbMigration::STATUS_IN_PROGRESS ) {
			$this->render_in_progress_notice();
		} elseif ( $status === DbMigration::STATUS_COMPLETED ) {
			$this->render_completed_notice();
		}
	}

	/**
	 * Render pending migration notice.
	 *
	 * @since 2.0.0
	 *
	 * @return void
	 */
	private function render_pending_notice(): void {
		$start_url = wp_nonce_url(
			add_query_arg( 'wc_price_history_start_migration', '1', admin_url() ),
			'wc_price_history_start_migration'
		);
		?>
		<div class="notice notice-warning wc-price-history-migration-notice">
			<div class="wc-price-history-migration-header">
				<p><strong><?php esc_html_e( 'WC Price History database update required', 'wc-price-history' ); ?></strong></p>
			</div>
			<div class="wc-price-history-migration-content">
				<p>
					<?php
					esc_html_e(
						'WC Price History has been updated! To keep things running smoothly, we have to update your database to the newest version.',
						'wc-price-history'
					);
					?>
					<?php
					printf(
						/* translators: %1$s: WP CLI link, %2$s: closing </a> tag */
						esc_html__( 'The database update process runs in the background and may take a little while, so please be patient. Advanced users can alternatively update via %1$sWP CLI%2$s.', 'wc-price-history' ),
						'<a href="https://developer.wordpress.org/cli/commands/">',
						'</a>'
					);
					?>
				</p>
			</div>
			<div class="wc-price-history-migration-progress-bar" style="display: none;">
				<div class="progress-bar-container">
					<div class="progress-bar-fill" style="width: 0%;"></div>
				</div>
				<p class="progress-text"></p>
			</div>
			<p class="submit">
				<a href="<?php echo esc_url( $start_url ); ?>" class="button-primary wc-price-history-migrate-button">
					<?php esc_html_e( 'Update Database', 'wc-price-history' ); ?>
				</a>
				<a href="https://wcpricehistory.com/docs/" class="button-secondary">
					<?php esc_html_e( 'Learn more about updates', 'wc-price-history' ); ?>
				</a>
			</p>
		</div>
		<?php
	}

	/**
	 * Render in progress migration notice.
	 *
	 * @since 2.0.0
	 *
	 * @return void
	 */
	private function render_in_progress_notice(): void {
		$progress = DbMigration::get_progress();
		?>
		<div class="notice notice-info wc-price-history-migration-notice">
			<div class="wc-price-history-migration-header">
				<p><strong><?php esc_html_e( 'WC Price History database update in progress', 'wc-price-history' ); ?></strong></p>
			</div>
			<div class="wc-price-history-migration-content">
				<p><?php esc_html_e( 'Please wait while we update your database...', 'wc-price-history' ); ?></p>
			</div>
			<div class="wc-price-history-migration-progress-bar">
				<div class="progress-bar-container">
					<div class="progress-bar-fill" style="width: <?php echo esc_attr( (string) $progress['percentage'] ); ?>%;"></div>
				</div>
				<p class="progress-text">
					<?php
					printf(
						/* translators: %1$d: processed, %2$d: total, %3$.2f: percentage */
						esc_html__( 'Migrating products: %1$d / %2$d (%3$.2f%%)', 'wc-price-history' ),
						$progress['processed'],
						$progress['total'],
						$progress['percentage']
					);
					?>
				</p>
			</div>
		</div>
		<?php
	}

	/**
	 * Render completed migration notice.
	 *
	 * @since 2.0.0
	 *
	 * @return void
	 */
	private function render_completed_notice(): void {
		$progress = DbMigration::get_progress();
		$dismiss_url = wp_nonce_url(
			add_query_arg( 'wc_price_history_dismiss_migration', '1', admin_url() ),
			'wc_price_history_dismiss_migration'
		);
		?>
		<div class="notice notice-success is-dismissible wc-price-history-migration-notice">
			<div class="wc-price-history-migration-header">
				<p><strong><?php esc_html_e( 'WC Price History database update completed', 'wc-price-history' ); ?></strong></p>
			</div>
			<div class="wc-price-history-migration-content">
				<p>
					<?php
					printf(
						/* translators: %d: number of products */
						esc_html__( 'Successfully migrated %d products to the new database structure.', 'wc-price-history' ),
						$progress['total']
					);
					?>
				</p>
			</div>
			<p class="submit">
				<a href="<?php echo esc_url( $dismiss_url ); ?>" class="button-secondary">
					<?php esc_html_e( 'Dismiss', 'wc-price-history' ); ?>
				</a>
			</p>
		</div>
		<?php
	}
}
