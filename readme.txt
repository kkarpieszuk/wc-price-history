=== WC Price History ===

Contributors: Konrad Karpieszuk
Tags: WooCommerce, prices, history, prior, omnibus, european, 30days
Requires at least: 5.8
Tested up to: 6.1.1
Requires PHP: 7.2
Stable tag: 1.4
License: MIT License
License URI: https://mit-license.org/

== Description ==

Track WooCommerce© Products prior prices history and display the lowest price in the last 30 days. This plugin allows your WC shop to be compliant with European Commission Directive 98/6/EC Article 6a which specifies price reduction announcement policy.

== Installation ==

- Unzip the directory in `wp-content/plugins`
- Go to `wp-admin` > `Plugins` and activate the plugin

== Usage ==

Plugin tracks prices' changes and stores them in custom field `_wc_price_history`.
When product is displayed in front-end, the plugin adds - below the product price - information about the lowest price in the last 30 days.

Plugin is configurable via `WooCommerce` > `Price History` screen. You can configure:
- Where to display the price history information (on the single product page, on the shop page, on both)
- When to display minimal price (always or only when the product is on sale)
- How to count minimal price (the minimal from the moment product went on sale to 30 days before that moment or the minimal price from today to 30 days ago)
- How many days take into account when calculating minimal price (30 days by default)
- How to display the price history information

At the configuration screen you will find additional information how to configure the plugin to be compliant with Omnibus directive (European Commission Directive 98/6/EC Article 6a) and link to legal acts.

= How to configure plugin to be compliant with Omnibus directive =

You don't have to do anything special, the default settings are compliant with Omnibus directive!

However, in case you misconfigured the plugin, here are steps to take to make it compliant again (please note similar suggestions hints you will see on Settings screen):

1. Go to `WooCommerce` > `Price History` screen
2. Set `Display on` to `Single product page`
3. Set `Display minimal price` to `Only when product is on sale`
4. Set `Count minimal price from` to `Day when product went on sale`
5. Set `Number of days to use when counting minimal price:` to `30 days`
6. For each product being on sale, go to its edit screen and set `Sale price dates from` to the date when the product went on sale.

= Shortcode =

If you want to display the lowest products price in other place than default, you can use shortcode `wc_price_history`. A few examples:

Display the lowest price on single product page (without passing product ID as argument), currency symbol attached:
```
This product low is [wc_price_history]
```

Display the lowest price of the other product, currency symbol attached:
```
The product with ID 3 had the lowest price [wc_price_history id=3]
```

Display without currency symbol:
```
The product with ID 3 had the lowest price [wc_price_history id=3 show_currency=0]
```

== Screenshots ==

1. Lowest price information displayed on single product page.
2. WC Price History configured according to Omnibus directive.

== Frequently Asked Questions ==

= For some products being on sale, plugin shows minimal price from current day, why? =

This is because you have not set `Sale price dates from` for these products. Go to the product edit screen and set `Sale price dates from` to the date when the product went on sale.

Tip: All the products which are On sale but does not have `Sale price dates from` set will be logged to WooCommerce logs. Go to `WooCommerce` > `Status` > `Logs` to see the list of products (in right top corner preselect log which name starts with wc-price-history).

= I have a problem with the plugin, or I want to suggest a feature. Where can do this? =

Please submit the [GitHub issue](https://github.com/kkarpieszuk/wc-price-history/issues).

== Changelog ==

= 1.5 =
* Fixed problem that product had to be at least once manually saved to start tracking the history

= 1.4 =
* Handled variable products
* Handled product taxes
* Settings screen: count minimal price is not hidden now and label is adjusted to explain it applies only for products being on sale
* Handled case when WooCommerce plugin is not active
* Optimized minimal price calculation class

= 1.3 =
* New: "30-day low" text is configurable now on Settings screen
* Updated documentation and hint texts for better plugin usability

= 1.2 =
* Added wc_price_history shortcode support
* Added settings screen
* Added ability to define where the price history should be displayed
* Added ability to define how many days should be considered when calculating the lowest price
* Added ability to define if the price history should be displayed only for products with price reduction
* Added ability to define if minimal price count should start from current day or the first day of the sale
* Link to European Commission Directive 98/6/EC Article 6a added to plugin settings screen
* Added logging products which are on sale but do not have sale start date set

= 1.1 =
* Plugin rewritten to store prices log in custom fields instead of post revisions
* Added migration logic between revisions and custom fields

= 1.0 =
* Initial release.
