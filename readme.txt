=== WC Price History ===

Contributors: Konrad Karpieszuk
Tags: WooCommerce, prices, history, prior, omnibus, european, 30days
Requires at least: 5.8
Tested up to: 6.1.1
Requires PHP: 72
Stable tag: 1.0
License: MIT License
License URI: https://mit-license.org/

== Description ==

Track WooCommerce© Products prior prices history and display the lowest price in the last 30 days. This plugin allows your WC shop to be compliant with European Commission Directive 98/6/EC Article 6a which specifies price reduction announcement policy.

== Installation ==

- Unzip the directory in `wp-content/plugins`
- Go to `wp-admin` > `Plugins` and activate the plugin

== Usage ==

The plugin does not have any configuration screens. If enabled, it adds support the post revisions for WC products and tracks prices changes in those  revisions.
When product is displayed in front-end, the plugin adds - below the product price - information about the lowest price in the last 30 days.

== Screenshots ==

1. Lowest price information displayed on single product page.

== Frequently Asked Questions ==

= I see error 'Lowest price display is not possible. Please enable WP revisions for WC products and set unlimited revisions numbers', what should I do? =

The plugin utilizes WP Revisions feature to store and track price changes. Please make sure
the feature is enabled and configured to store unlimited number of revisions, you can enable it manually by adding to wp-config.hpp file line:

`define( 'WP_POST_REVISIONS', true );`

If this does not help, it might mean some 3rd party code is disabling it or disabling it for WC products. WC Price History plugin tries  to re-enable it but 3rd party plugin or theme might force it. Please review your other plugins and theme.

== Changelog ==

= 1.0 =
* Initial release.

== Credits ==

Konrad Karpieszuk <kkarpieszuk@gmail.com>

