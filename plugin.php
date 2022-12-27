<?php
/*
 * Plugin Name: Previous Prices
 * Description: Track Woocommerce Products prior prices history and display the lowest price in the last 30 days. This plugin allows your WC shop to be compliant with European Commission Directive 98/6/EC Article 6a which specifies price reduction announcement policy.
 * Author: Konrad Karpieszuk
 * Version: 1.0
 */

use PriorPrice\Factory;
use PriorPrice\Hooks;

require_once __DIR__ . '/vendor/autoload.php';

$hooks = new Hooks( new Factory() );
$hooks->registerHooks();
