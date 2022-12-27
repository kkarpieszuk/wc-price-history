<?php

namespace PriorPrice;

/**
 * Shortcode class.
 * 
 * @since 1.0
 */
class Shortcode {

    /**
     * Register shortcode.
     * 
     * @since 1.0
     */
	public function registerShortcode() {
        \add_shortcode( 'prior_price', [ $this, 'shortcodeCallback' ] );
    }

    /**
     * Shortcode callback.
     * 
     * @since 1.0
     */
	public function shortcodeCallback() {
        \printf( '<div class="PriorPrice-shortcode">%s</div>', __( 'It comes from Prior Price plugin...', 'PriorPrice' ) );
    }
}