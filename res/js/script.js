jQuery(document).ready( function($) {

    jQuery.post( 
      PriorPrice_data.ajax_url,
      { 'action': 'fetch_PriorPrice_ajax' },
      function( response ) {
          jQuery.each( response, function() {
            
          });
        } 
      )
} );
