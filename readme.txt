=== WC Price History ===

Contributors: Konrad Karpieszuk
Tags: WooCommerce, prices, history, prior, omnibus, european, 30days
Requires at least: 5.8
Tested up to: 6.1.1
Requires PHP: 7.2
Stable tag: 1.1
License: MIT License
License URI: https://mit-license.org/

== Description ==

Track WooCommerce© Products prior prices history and display the lowest price in the last 30 days. This plugin allows your WC shop to be compliant with European Commission Directive 98/6/EC Article 6a which specifies price reduction announcement policy.

== Installation ==

- Unzip the directory in `wp-content/plugins`
- Go to `wp-admin` > `Plugins` and activate the plugin

== Usage ==

The plugin does not have any configuration screens. If enabled, it tracks prices changes and stores them in custom field `_wc_price_history`.
When product is displayed in front-end, the plugin adds - below the product price - information about the lowest price in the last 30 days.

== Screenshots ==

1. Lowest price information displayed on single product page.

== Frequently Asked Questions ==

= I have a problem with the plugin or I want to suggest a feature. Where can do this? =

Please submit the [GitHub issue](https://github.com/kkarpieszuk/wc-price-history/issues).

== Changelog ==

= 1.0 =
* Initial release.

= 1.1 =
* Plugin rewritten to store prices log in custom fields instead of post revisions.
* Added migration logic between revisions and custom fields.

= 1.2 =
* Added settings screen
* Added ability to define where the price history should be displayed
* Added ability to define how many days should be considered when calculating the lowest price
* Added ability to define if the price history should be displayed only for products with price reduction
* Added ability to define if minimal price count should start from current day or the first day of the sale
* Link to European Commission Directive 98/6/EC Article 6a added to plugin settings screen
* Added logging products which are on sale but do not have sale start date set
