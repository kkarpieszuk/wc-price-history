<?php
/*
 * Plugin Name: WC Price History
 * Description: Track WooCommerce© Products prior prices history and display the lowest price in the last 30 days. This plugin allows your WC shop to be compliant with European Commission Directive 98/6/EC Article 6a which specifies price reduction announcement policy.
 * Author: Konrad Karpieszuk
 * Version: 1.1
 * Text Domain: wc-price-history
 * Domain Path: /languages/
 * Requires at least: 5.8
 * Requires PHP: 7.2
 * Plugin URI: https://github.com/kkarpieszuk/wc-price-history
 */

use PriorPrice\Hooks;

require_once __DIR__ . '/vendor/autoload.php';

require_once __DIR__ . '/constants.php';

$hooks = new Hooks();
$hooks->register_hooks();
