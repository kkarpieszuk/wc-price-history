<?php
/*
 * Plugin Name: Prior Price
 * Description: Track Woocommerce Products prices history and display the lowest price in the last 30 days. This plugin allows your WC shop to be compliant with European Commision Directive 98/6/EC Article 6a which specyfies price reduction annoucment policy.
 * Author: Konrad Karpieszuk
 * Version: 1.0
 */

use PriorPrice\Factory;
use PriorPrice\Hooks;

require_once __DIR__ . '/vendor/autoload.php';

new Hooks( new Factory() )->registerHooks();
