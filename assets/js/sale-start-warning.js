( function ( $ ) {
	'use strict';

	var config = window.wcPriceHistorySaleStartWarning;
	if ( ! config || ! config.ajaxUrl ) {
		return;
	}

	var NOTICE_ID = config.noticeId || 'wc-price-history-sale-start-warning';
	var currentProductId = 0;
	var requestSeq = 0;

	function getPathFromLocation() {
		try {
			var params = new URLSearchParams( window.location.search );
			var path = params.get( 'path' );
			if ( path ) {
				return decodeURIComponent( path );
			}
		} catch ( e ) {
			// Ignore URL parsing errors.
		}

		var hash = window.location.hash || '';
		if ( hash.indexOf( '/product/' ) !== -1 ) {
			return hash.replace( /^#/, '' );
		}

		return window.location.pathname || '';
	}

	function parseProductId( path ) {
		if ( ! path ) {
			return 0;
		}
		var match = String( path ).match( /(?:^|\/)product\/(\d+)(?:\/|$)/ );
		return match ? parseInt( match[1], 10 ) : 0;
	}

	function getNoticesDispatch() {
		if ( ! window.wp || ! wp.data || typeof wp.data.dispatch !== 'function' ) {
			return null;
		}
		try {
			return wp.data.dispatch( 'core/notices' );
		} catch ( e ) {
			return null;
		}
	}

	function removeNotice() {
		var notices = getNoticesDispatch();
		if ( notices && typeof notices.removeNotice === 'function' ) {
			notices.removeNotice( NOTICE_ID );
		}
	}

	function showNotice( title, message ) {
		var notices = getNoticesDispatch();
		if ( ! notices || typeof notices.createNotice !== 'function' ) {
			return false;
		}

		var text = title ? ( title + ' — ' + message ) : message;

		notices.createNotice( 'warning', text, {
			id: NOTICE_ID,
			isDismissible: true,
		} );
		return true;
	}

	function syncForProduct( productId ) {
		if ( productId === currentProductId ) {
			return;
		}
		currentProductId = productId;

		if ( ! productId ) {
			removeNotice();
			return;
		}

		var seq = ++requestSeq;

		$.post( config.ajaxUrl, {
			action: config.action,
			security: config.nonce,
			product_id: productId,
		} )
			.done( function ( response ) {
				if ( seq !== requestSeq ) {
					return;
				}
				if ( ! response || ! response.success || ! response.data ) {
					removeNotice();
					return;
				}
				if ( response.data.needsWarning ) {
					showNotice(
						response.data.title || config.title,
						response.data.message || config.message
					);
				} else {
					removeNotice();
				}
			} )
			.fail( function () {
				if ( seq === requestSeq ) {
					removeNotice();
				}
			} );
	}

	function onRouteMaybeChanged() {
		syncForProduct( parseProductId( getPathFromLocation() ) );
	}

	function bindSpaListeners() {
		window.addEventListener( 'popstate', onRouteMaybeChanged );
		window.addEventListener( 'hashchange', onRouteMaybeChanged );

		// WC Admin uses history.pushState; patch to observe soft navigation.
		if ( window.history && typeof window.history.pushState === 'function' ) {
			var originalPush = window.history.pushState;
			window.history.pushState = function () {
				var result = originalPush.apply( this, arguments );
				window.setTimeout( onRouteMaybeChanged, 0 );
				return result;
			};
		}
		if ( window.history && typeof window.history.replaceState === 'function' ) {
			var originalReplace = window.history.replaceState;
			window.history.replaceState = function () {
				var result = originalReplace.apply( this, arguments );
				window.setTimeout( onRouteMaybeChanged, 0 );
				return result;
			};
		}

		// Fallback poll for routers that do not go through history APIs we patched.
		window.setInterval( onRouteMaybeChanged, 1000 );
	}

	function boot() {
		bindSpaListeners();
		var initial = parseProductId( getPathFromLocation() ) || parseInt( config.initialProductId, 10 ) || 0;
		currentProductId = -1; // force first sync
		syncForProduct( initial );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', boot );
	} else {
		boot();
	}
}( window.jQuery ) );
