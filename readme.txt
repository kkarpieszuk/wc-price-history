=== WC Price History ===

Plugin Name: WC Price History
Short Description: Show the lowest product price in the last 30 days, Omnibus compliant.
Contributors: kkarpieszuk
Tags: omnibus, WooCommerce, prices, history, lowest
Requires at least: 5.8
Tested up to: 6.9
Requires PHP: 7.2
Stable tag: {VERSION}
License: Expat
License URI: https://mit-license.org/
Donate link: https://buycoffee.to/wpzlecenia
What was the lowest price recently? Build customer trust through transparency! Track and display product price history in WooCommerce store. OMNIBUS compliant.
== Description ==

[WC Price History](https://wcpricehistory.com) plugin allows you to track WooCommerce© Products prior prices history and display the lowest price in the last 30 days. This plugin allows your WC shop to be compliant with European law which specifies price reduction announcement policy.

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
&#8618; What to do if the price didn't change in the last N days (hide price information / display current price / display custom text)

At the configuration screen you will find additional information how to configure the plugin to be compliant with Omnibus directive (European Commission Directive 98/6/EC Article 6a) and link to legal acts.

== Screenshots ==

1. Lowest price information displayed on single product page.
2. WC Price History configured according to Omnibus directive.

== Frequently Asked Questions ==

= What is Omnibus directive and how to configure this plugin to be compliant with? =

European Commission Directive 98/6/EC Article 6a - in short Omnibus directive - specifies price reduction announcement policy.

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

= Is plugin working well with discount addons? =

That depends on the addon. Some addons may not work with the plugin, some may work. For time being we recommend using the plugin with the following addon:

https://wordpress.org/plugins/woo-discount-rules/

This one was confirmed to work well with the plugin. If you know of other addons which work with the plugin, please let me know on the forum! I will add it to the list.

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
= What filters and actions I can use to affect WC Price History internal logic? =

Available filters are:
`wc_price_history_is_correct_place` (defined in `PriorPrice\Prices::is_correct_place`)
Allows to display price history on custom screens, not listed in plugins settings. Return true to make price history visible.

`wc_price_history_is_not_correct_when` (defined in `PriorPrice\Prices::is_not_correct_when`)
Allows to stop displaying price history for your own custom conditions. Return true to prevent displaying price history.

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

= Can I clean the history? =

Yes, on the plugin configuration screen click the button 'Clean history'. This will remove all the history storage for all products. Make sure you know what you are doing (in general, use this option only if you are going to remove plugin completely).

= I have a problem with the plugin, or I want to suggest a feature. Where can do this? =

Please submit the [GitHub issue](https://github.com/kkarpieszuk/wc-price-history/issues).

= I like your work, are you available for hire? =

Yes, I am available for hire. Please contact me at [LinkedIn](https://linkedin.com/in/konrad-karpieszuk-38528b11/).

== Changelog ==

= {VERSION} =
- Database Migration: Price History moved from Post Meta to Custom Tables for better performance. See https://wcpricehistory.com/tuorials-howtos/docs/migration-to-database-tables-in-3-0/?utm_source=readme&utm_medium=changelog&utm_campaign=wc-price-history (#98)
- New: Minimal price displayed with shortcode is now updated when product variant is changed. (#169)

= 2.2.2 =
- New: Added filter `wc_price_history_variations_add_history_lowest_price` to filter the lowest price for variations. (#167)

= 2.2.1 =
- Promo: Hire Me Section (#165)

= 2.2.0 =
- Fix: Plugin sometimes displayed "lowest price: 0" after 30 days when price history became empty due to time filtering. ( #159 )
- Fix: Variable products: when product had related products their prices where concatenated in the price output. (#161)

= 2.1.9 =
- Fix: Variable products: when user select variant, the main price was reset sometimes to zero. (#154)

= 2.1.8 =
* Improvement: First history scan now targets products more precisely (#148)
* New: If the first scan gets stuck in a never-ending state, it is now possible to force its termination from the settings screen (#148)

= 2.1.7 =
* New: Added action to add custom fields to settings page. (#137)
* New: Added filter for the lowest price HTML before displaying it. (#139)
* New: Added filter for the display text from template. (#139)
* Fixed: Prices displayed with shortcode were not possible to filter to remove decimals. (#141)

= 2.1.6 =
* Maintenance: Prepare for replacement of Freemius with Keygen. (#135)

= 2.1.5 =
* Maintenance: Updated content displayed on plugin page in WordPress repository. (#128)
* Maintenance: Plugin passes now Plugin Check Plugin tests. (#133)
* Reverted: Removed import feature. (#131)

= 2.1.4 =
* Maintenance: Added PHPStan rule to check if classes with register_hooks() method is instantiated only in PriorPrice\Hooks::plugins_loaded() method. (#116)

= 2.1.3 =
* Fixed: Plugin was not available on mutlisite installations. (#100)
* Fixed: Saved variable products sometimes had lowest price set to zero. (#111)
* New: Added debug feature to export product with price history to JSON file and import it back. (#109)
* Maintenance: Added build script to automate plugin release process. (#103)
* Maintenance: Fixed unit tests. (#113)

= 2.1.1 =
* Fixed: Reverted change #89 from 2.1
* Fixed: Price selection for sale start was comparing saved historicial timestamps in GMT0 with timestamp of the sale start with local offset (#96)

= 2.1 =
* New: Additional public methods for external integrations. (#91)
* Fixed: Price from sale start was incorrectly included into history checking if product was on sale for at least one day (#89)
* New: Freemius integration. (#94)
* Fixed: Displayed price history was not updated on product screen when variant selection was changed (#79)

= 2.0.0 =
* New: Plugin scans all product to start logging price history before any interaction with the product. (#84)
* New: Plugin allows to clean the whole pricing history (#87)
* New: Plugin allows to extend history by adding prices one day before the oldets one (#87)
* Fixed: Some users had incorrectly recognized first product price change, showing a new price always as lowest one (#80)

= 1.9.0 =
* New: Allow to decide what to display in case there was no price change in the tracked history span. (#77)

= 1.8.0 =
* New: Basic compatibility with dynamic pricing plugins.
* New: Displayed HTML is translatable with WPML and Polylang.
* Hooks: Added filter `wc_price_history_is_correct_place` to make it possible to display price history info in custom location.
* Hooks: Added filter `wc_price_history_is_not_correct_when` to stop displaying price history for your own conditions.
* Fixed: Duplicated product had price history starting from original product last price.
* Improvement: Do not store prices saved while product had status draft.

= 1.7.4 =
* Improvement: Start saving the price before change with timestamps for last midnight and for 1 second ago. (#58)
* Improvement: Clean history from empty values before save.
* Fixed: Do not copy product price history when duplicating product. (#50)

= 1.7.3 =
* Fixed: When price displayed with shortcode, it was not respecting sale settings and it resulted in showing the current price.

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
