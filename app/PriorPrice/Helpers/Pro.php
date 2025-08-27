<?php

namespace PriorPrice\Helpers;

class Pro {

	public static function is_pro() : bool {
		return defined( 'WC_PRICE_HISTORY_EDITOR_PLUGIN_URL' );
	}
}