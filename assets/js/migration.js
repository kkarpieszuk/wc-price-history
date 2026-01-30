/**
 * Migration JavaScript for WC Price History.
 *
 * @since 3.0.0
 */
(function() {
	'use strict';

	// Wait for DOM to be ready.
	document.addEventListener('DOMContentLoaded', function() {
		// Check if migration object is available.
		if (typeof wcPriceHistoryMigration === 'undefined') {
			return;
		}

		const migrateButton = document.querySelector('.wc-price-history-migrate-button');
		const progressBar = document.querySelector('.wc-price-history-migration-progress-bar');
		const progressFill = document.querySelector('.progress-bar-fill');
		const progressText = document.querySelector('.progress-text');
		const migrationNotice = document.querySelector('.wc-price-history-migration-notice');

		// Check if migration is in progress.
		// Primary check: if notice has 'notice-info' class, migration is in progress.
		const isInProgressNotice = migrationNotice && migrationNotice.classList.contains('notice-info');

		// Secondary checks: if progress bar exists and has data.
		let hasProgressData = false;
		if (progressBar && progressFill && progressText) {
			// Check if progress text has content (migration was in progress).
			const hasProgressText = progressText.textContent && progressText.textContent.trim().length > 0;

			// Check if progress fill has width set (even if hidden by CSS).
			// Check both inline style and computed style.
			const inlineWidth = progressFill.style.width;
			const computedWidth = window.getComputedStyle(progressFill).width;
			const fillWidth = inlineWidth || computedWidth;

			// Parse width value (remove 'px' or '%' and convert to number).
			let widthValue = 0;
			if (fillWidth) {
				const numericValue = parseFloat(fillWidth);
				if (!isNaN(numericValue) && numericValue > 0) {
					widthValue = numericValue;
				}
			}
			const hasProgressWidth = widthValue > 0;

			hasProgressData = hasProgressText || hasProgressWidth;
		}

		// If notice indicates in_progress status OR progress bar has data, resume migration.
		if (isInProgressNotice || hasProgressData) {
			// Ensure progress bar elements exist before proceeding.
			if (!progressBar || !progressFill || !progressText) {
				// If progress bar elements don't exist, we can't track progress.
				// This shouldn't happen if notice is in_progress, but handle gracefully.
				return;
			}

			// Migration is in progress - automatically resume.
			// Ensure progress bar is visible (override CSS display: none).
			progressBar.style.display = 'block';

			// Disable button if it exists.
			if (migrateButton) {
				migrateButton.disabled = true;
				migrateButton.textContent = wcPriceHistoryMigration.i18n.processing || 'Processing...';
			}

			// Start/resume migration automatically.
			migrateBatch();
			return;
		}

		// If button doesn't exist, we can't start migration manually.
		if (!migrateButton) {
			return;
		}

		// If progress bar elements don't exist, we can't track progress.
		if (!progressBar || !progressFill || !progressText) {
			return;
		}

		migrateButton.addEventListener('click', function(e) {
			e.preventDefault();

			// Start migration.
			migrateButton.disabled = true;
			migrateButton.textContent = wcPriceHistoryMigration.i18n.processing || 'Processing...';

			// Show progress bar.
			progressBar.style.display = 'block';

			// Start recursive AJAX calls.
			migrateBatch();
		});

		/**
		 * Migrate batch via AJAX.
		 *
		 * @param {string} status Status.
		 */
		function migrateBatch(status) {
			const data = {
				action: 'wc_price_history_migrate_batch',
				security: wcPriceHistoryMigration.nonce,
				status: status || 'start'
			};

			fetch(wcPriceHistoryMigration.ajaxurl, {
				method: 'POST',
				headers: {
					'Content-Type': 'application/x-www-form-urlencoded',
				},
				body: new URLSearchParams(data)
			})
			.then(response => response.json())
			.then(data => {
				if (!data.success) {
					showError(data.data.message || wcPriceHistoryMigration.i18n.error || 'An error occurred');
					return;
				}

				const result = data.data;

				// Update progress bar.
				updateProgress(result.percentage, result.message);

				if (result.completed) {
					// Migration completed.
					showSuccess(result.message);
				} else {
					// Continue with next batch.
					setTimeout(function() {
						migrateBatch('continue');
					}, 100);
				}
			})
			.catch(error => {
				showError(error.message || wcPriceHistoryMigration.i18n.error || 'An error occurred');
				console.error('Migration error:', error);
			});
		}

		/**
		 * Update progress bar.
		 *
		 * @since 3.0.0
		 *
		 * @param {number} percentage Percentage.
		 * @param {string} message Message.
		 */
		function updateProgress(percentage, message) {
			if (progressFill) {
				progressFill.style.width = percentage + '%';
			}
			if (progressText) {
				progressText.textContent = message;
			}

			// Update notice header if needed.
			const header = document.querySelector('.wc-price-history-migration-notice .wc-price-history-migration-header p strong');
			if (header) {
				header.textContent = wcPriceHistoryMigration.i18n.inProgress || 'Database update in progress...';
			}
		}

		/**
		 * Show success message.
		 *
		 * @param {string} message Message.
		 */
		function showSuccess(message) {
			// Reload page after 2 seconds to show completed notice.
			setTimeout(function() {
				window.location.reload();
			}, 2000);
		}

		/**
		 * Show error message.
		 *
		 * @param {string} message Message.
		 */
		function showError(message) {
			const errorDiv = document.createElement('div');
			errorDiv.className = 'notice notice-error';
			errorDiv.innerHTML = '<p><strong>' + (wcPriceHistoryMigration.i18n.errorTitle || 'Error') + ':</strong> ' + message + '</p>';

			const notice = document.querySelector('.wc-price-history-migration-notice');
			if (notice) {
				notice.insertAdjacentElement('afterend', errorDiv);
			}

			// Re-enable button if it exists.
			if (migrateButton) {
				migrateButton.disabled = false;
				migrateButton.textContent = wcPriceHistoryMigration.i18n.retry || 'Retry Migration';
			}
		}
	});
})();
