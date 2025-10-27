/**
 * Migration JavaScript for WC Price History.
 *
 * @since {VERSION}
 */
(function() {
	'use strict';

	// Wait for DOM to be ready.
	document.addEventListener('DOMContentLoaded', function() {
		const migrateButton = document.querySelector('.wc-price-history-migrate-button');
		const progressBar = document.querySelector('.wc-price-history-migration-progress-bar');
		const progressFill = document.querySelector('.progress-bar-fill');
		const progressText = document.querySelector('.progress-text');

		if (!migrateButton || !progressBar || !progressFill || !progressText) {
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

			fetch(ajaxurl, {
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
				updateProgress(result.processed, result.total, result.percentage, result.message);

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
		 * @param {number} processed Processed count.
		 * @param {number} total Total count.
		 * @param {number} percentage Percentage.
		 * @param {string} message Message.
		 */
		function updateProgress(processed, total, percentage, message) {
			progressFill.style.width = percentage + '%';
			progressText.textContent = message;

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

			// Re-enable button.
			migrateButton.disabled = false;
			migrateButton.textContent = wcPriceHistoryMigration.i18n.retry || 'Retry Migration';
		}
	});
})();
