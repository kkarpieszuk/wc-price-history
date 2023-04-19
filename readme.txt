=== WC Price History for Omnibus ===

Plugin Name: WC Price History
Contributors: Konrad Karpieszuk
Tags: WooCommerce, prices, history, prior, omnibus, european, 30days
Requires at least: 5.8
Tested up to: 6.1.1
Requires PHP: 7.2
Stable tag: 1.7.2
License: MIT License
License URI: https://mit-license.org/
Donate link: https://buycoffee.to/wpzlecenia

== Description ==

Track WooCommerce© Products prior prices history and display the lowest price in the last 30 days. This plugin allows your WC shop to be compliant with European Commission Directive 98/6/EC Article 6a which specifies price reduction announcement policy.

== Installation ==

- Unzip the directory in `wp-content/plugins`
- Go to `wp-admin` > `Plugins` and activate the plugin

== Usage ==

The plugin works out of the box: install and activate and your discounted products will be compatible with Omnibus directive!

Plugin is configurable via `WooCommerce` > `Price History` screen. You can configure:
&#8618; Where to display the price history information:
&raquo; on the single product page
&raquo; upsell and related products
&raquo; main shop page
&raquo; product category pages
&raquo; product tag pages
&#8618; When to display minimal price (always or only when the product is on sale)
&#8618; How to count minimal price (the minimal from the moment product went on sale to 30 days before that moment or the minimal price from today to 30 days ago)
&#8618; How many days take into account when calculating minimal price (30 days by default)
&#8618; How to display the price history information

At the configuration screen you will find additional information how to configure the plugin to be compliant with Omnibus directive (European Commission Directive 98/6/EC Article 6a) and link to legal acts.

== Screenshots ==

1. Lowest price information displayed on single product page.
2. WC Price History configured according to Omnibus directive.

== Frequently Asked Questions ==

= How to configure plugin to be compliant with Omnibus directive =

You don't have to do anything special, the default settings are compliant with Omnibus directive!

However, in case you misconfigured the plugin, here are steps to take to make it compliant again (please note similar suggestions hints you will see on Settings screen):

1. Go to `WooCommerce` > `Price History` screen
2. Set `Display on` to `Single product page`
3. Set `Display minimal price` to `Only when product is on sale`
4. Set `Count minimal price from` to `Day before product went on sale`
5. Set `Number of days to use when counting minimal price:` to `30 days`
6. For each product being on sale, go to its edit screen and set `Sale price dates from` to the date when the product went on sale.

= Is plugin working well with variable products? =

Yes, the plugin is compatible with product taxes and variable products (it tracks minimal price for each variation individually).

= Is there any shortcode I could use to display minimal price? =

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

= For some products being on sale, plugin shows minimal price from current day, why? =

This is because you have not set `Sale price dates from` for these products. Go to the product edit screen and set `Sale price dates from` to the date when the product went on sale.

Tip: All the products which are On sale but does not have `Sale price dates from` set will be logged to WooCommerce logs. Go to `WooCommerce` > `Status` > `Logs` to see the list of products (in right top corner preselect log which name starts with wc-price-history).

= Can I adjust minimal price before being it displayed? =

Yes, you can use filter `wc_price_history_lowest_price_html_raw_value_taxed`:

```
add_filter( 'wc_price_history_lowest_price_html_raw_value_taxed', function( $price, $wc_product ) {
	// do something with $price
	return $price;
}, 10, 2 );
```

= I have a problem with the plugin, or I want to suggest a feature. Where can do this? =

Please submit the [GitHub issue](https://github.com/kkarpieszuk/wc-price-history/issues).

== Changelog ==

= 1.7.2 =
* Fixed: When price displayed with shortcode, and it was zero, it should not be displayed.

= 1.7.1 =
* Added filter to modify the minimal price before it is displayed

= 1.7 =
* Added option to include sale price when counting minimal price (#41)
* Added option to display line through over minimal price (#42)

= 1.6.6 =
* Fixed: Placeholder %s was displayed instead of the lowest price after plugin update. (#39)
* Improvement: Rearranged the plugin option's page.
* Improvement: Added settings link to plugins page.

= 1.6.5 =
* Fix: Wrong number of decimals in price history information when displayed with shortcode (#36)

= 1.6.4 =
* Optimization: moved class loading to plugins_loaded hook

= 1.6.3 =
* Fix: Fixed fatal error.

= 1.6.2 =
* Fix: Taxes not applied to the price when displayed with shortcode (#34).

= 1.6.1 =
* Fixed issue with timezones offsets when saving history

= 1.6 =
* Added toggle to display minimal price for related/upsell products on the single product page

= 1.5 =
* Fixed problem that product had to be at least once manually saved to start tracking the history
* Added ability to decide if minimal price should be displayed on product category pages and product tag pages

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
