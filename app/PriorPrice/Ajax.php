<?php

namespace PriorPrice;

/**
 * Ajax class.
 * 
 * @since 1.0
 */
class Ajax {
	
	/**
	 * Enqueue and localize scripts.
	 * 
	 * @since 1.0
	 */
	public function enqueue() {
		\wp_enqueue_script( 'PriorPrice_script', \plugins_url( '../../res/js/script.js', __FILE__ ), [ 'jquery' ] );
		\wp_localize_script( 'PriorPrice_script', 'PriorPrice_data',
			[
				'ajax_url' => admin_url( 'admin-ajax.php' ),
			] );
	}

	/**
	 * Response to ajax.
	 * 
	 * @since 1.0
	 */
	public function fetchForAjax() {
		
		\wp_send_json( '' );
	}
}
