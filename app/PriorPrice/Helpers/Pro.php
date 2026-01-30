<?php

namespace PriorPrice\Helpers;

/**
 * Pro helper.
 *
 * @since 3.0.0
 */
class Pro {

	/**
	 * Check if the plugin is pro.
	 *
	 * @since 3.0.0
	 *
	 * @return bool
	 */
	public static function is_pro() : bool {
		return defined( 'WC_PRICE_HISTORY_EDITOR_PLUGIN_URL' );
	}
}
