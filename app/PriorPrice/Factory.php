<?php

namespace PriorPrice;

/**
 * Factory class.
 * 
 * @since 1.0
 */
class Factory {




	/**
	 * @since 1.0
	 * 
	 * @var Shortcode|null
	 */
	private static $shortcode;


	/**
	 * @since 1.0
	 * 
	 * @var Ajax|null
	 */
	private static $ajax;




	/**
	 * Create Shortcode instance.
	 * 
	 * @since 1.0
	 * 
	 * @return Shortcode
	 */
	public function createShortcode() {
		if ( ! self::$shortcode ) {
			self::$shortcode = new Shortcode();
		}
		return self::$shortcode;
	}


	/**
	 * Create Ajax instance.
	 * 
	 * @since 1.0
	 * 
	 * @return Ajax
	 */
	public function createAjax() {
		if ( ! self::$ajax ) {
			self::$ajax = new Ajax();
		}
		return self::$ajax;
	}
}
