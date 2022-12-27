<?php

namespace PriorPrice;

/**
 * Factory class.
 * 
 * @since 1.0
 */
class Factory {

	/**
	 * $since 1.0
	 *
	 * @var \PriorPrice\Revisions|null;
	 */
	private static $revisions;

	public function createRevisions() {
		if ( ! self::$revisions ) {
			self::$revisions = new Revisions();
		}

		return self::$revisions;
	}
}
