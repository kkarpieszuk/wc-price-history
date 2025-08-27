<?php

namespace PriorPrice\Helpers;

/**
 * Pro helper.
 *
 * @since {VERSION}
 */
class Pro {

	/**
	 * Check if the plugin is pro.
	 *
	 * @since {VERSION}
	 *
	 * @return bool
	 */
	public static function is_pro() : bool {
		return defined( 'WC_PRICE_HISTORY_EDITOR_PLUGIN_URL' );
	}
}