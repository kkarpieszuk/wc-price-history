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
});
