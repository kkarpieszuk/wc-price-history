jQuery(document).ready(function($) {

	// Toggle the custom text field when the custom text radio button is selected.
	$( '#wc-price-history-old-history-fieldset input' ).on(
		'change',
		function() {
			if ( $( '#wc-price-history-old-history-fieldset input:checked' ).val() == 'custom_text' ) {
				$( '.wc-price-history-old-history-custom-text-p' ).removeClass( 'hidden-fade' );
			} else {
				$( '.wc-price-history-old-history-custom-text-p' ).addClass( 'hidden-fade' );
			}
		}
	);

	// There are tesxts like "What to do when price history is older than {days-set} days" on the page's HTML code.
	// copy value from input field #wc-price-history-days-number to every place on page where is {days-set} placeholder in pages HTML (replace it).
	$( '#wc-price-history-days-number' ).on(
		'input',
		function() {
			$( '.wc-price-history-days-set' ).each(
				function() {
					$( this ).text( $( '#wc-price-history-days-number' ).val() );
				}
			);
		}
	);

	$( '#wc-price-history-first-scan-finished-notice .notice-dismiss' ).on( 'click', function() {
		$.post(
			ajaxurl,
			{
				action: 'wc_price_history_first_scan_finished_notice_dismissed',
				security: wc_price_history_admin.first_scan_finished_notice_nonce
			}
		);
	} );

	$( '#wc-price-history-clean-history' ).on( 'click', function() {
		$( this ).prop( 'disabled', true ).append( ' <span class="spinner is-active"></span>' );
		if ( confirm( wc_price_history_admin.clean_history_confirm ) ) {
			$.post(
				ajaxurl,
				{
					action: 'wc_price_history_clean_history',
					security: wc_price_history_admin.clean_history_nonce
				},
				function( response ) {
					$( '#wc-price-history-clean-history' ).prop( 'disabled', false ).find( '.spinner' ).remove();
					if ( response.success ) {
						alert( wc_price_history_admin.clean_history_success );
						location.reload();
					} else {
						alert( wc_price_history_admin.clean_history_error );
					}
				}
			);
		} else {
			$( '#wc-price-history-clean-history' ).prop( 'disabled', false ).find( '.spinner' ).remove();
		}
	} );

	$( '#wc-price-history-fix-history' ).on( 'click', function() {
		$( this ).prop( 'disabled', true ).append( ' <span class="spinner is-active"></span>' );
		if ( confirm( wc_price_history_admin.fix_history_confirm ) ) {
			$.post(
				ajaxurl,
				{
					action: 'wc_price_history_fix_history',
					security: wc_price_history_admin.fix_history_nonce
				},
				function( response ) {
					$( '#wc-price-history-fix-history' ).prop( 'disabled', false ).find( '.spinner' ).remove();
					if ( response.success ) {
						alert( wc_price_history_admin.fix_history_success );
						location.reload();
					} else {
						alert( wc_price_history_admin.fix_history_error );
					}
				}
			);
		} else {
			$( '#wc-price-history-fix-history' ).prop( 'disabled', false ).find( '.spinner' ).remove();
		}
	} );
});
