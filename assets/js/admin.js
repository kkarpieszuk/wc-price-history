
jQuery(document).ready(function($) {

	 $('.wc-history-price-display-when-input').click(
		 function() {
			 // get the value of the radio button
			 var radioValue = $("input.wc-history-price-display-when-input:checked").val();
			 // if the value is 1 (yes) show the div
			 if( radioValue == 'on_sale' ){
				 $('.wc-history-price-settings-row.count-from').removeClass( 'wc-history-price-hidden' );
			 } else {
				 $('.wc-history-price-settings-row.count-from').addClass( 'wc-history-price-hidden' );
			 }
		 }
	 );

});
