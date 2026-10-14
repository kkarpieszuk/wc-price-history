( function () {
	'use strict';

	var config = window.wcPriceHistorySaleStartWarning;
	if ( ! config || ! config.show ) {
		return;
	}

	var NOTICE_ID = 'wc-price-history-sale-start-warning';
	var maxAttempts = 40;
	var attempt = 0;

	function showNotice() {
		if ( ! window.wp || ! wp.data || typeof wp.data.dispatch !== 'function' ) {
			return false;
		}

		try {
			var notices = wp.data.dispatch( 'core/notices' );
			if ( ! notices || typeof notices.createNotice !== 'function' ) {
				return false;
			}

			notices.createNotice( 'warning', config.message, {
				id: NOTICE_ID,
				isDismissible: true,
			} );
			return true;
		} catch ( e ) {
			return false;
		}
	}

	if ( showNotice() ) {
		return;
	}

	var timer = window.setInterval( function () {
		attempt += 1;
		if ( showNotice() || attempt >= maxAttempts ) {
			window.clearInterval( timer );
		}
	}, 250 );
}() );
