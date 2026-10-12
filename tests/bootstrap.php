<?php

require_once __DIR__ . '/../vendor/autoload.php';

define( 'MINUTE_IN_SECONDS', 60 );
define( 'HOUR_IN_SECONDS', 60 * MINUTE_IN_SECONDS );
define( 'DAY_IN_SECONDS', 24 * HOUR_IN_SECONDS );
define( 'WEEK_IN_SECONDS', 7 * DAY_IN_SECONDS );
define( 'MONTH_IN_SECONDS', 30 * DAY_IN_SECONDS );
define( 'YEAR_IN_SECONDS', 365 * DAY_IN_SECONDS );

if ( ! class_exists( 'WC_DateTime', false ) ) {
	class WC_DateTime extends \DateTime {}
}

if ( ! class_exists( 'WC_Product', false ) ) {
	class WC_Product {}
}

if ( ! class_exists( 'WC_Product_Simple', false ) ) {
	class WC_Product_Simple extends WC_Product {}
}

if ( ! class_exists( 'WC_Product_Variable', false ) ) {
	class WC_Product_Variable extends WC_Product {}
}

if ( ! class_exists( 'WC_Product_Variation', false ) ) {
	class WC_Product_Variation extends WC_Product_Simple {}
}

WP_Mock::setUsePatchwork( false );
WP_Mock::bootstrap();
