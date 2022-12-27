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
	 * $since 1.0
	 *
	 * @var \PriorPrice\Revisions|null;
	 */
	private static $revisions;

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

	public function createRevisions() {
		if ( ! self::$revisions ) {
			self::$revisions = new Revisions();
		}

		return self::$revisions;
	}
}
