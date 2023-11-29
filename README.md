# wc-price-history
Track WooCommerce© Products prices history and display the lowest price in the last 30 days. This plugin allows your WC shop to be compliant with European Commission Directive 98/6/EC Article 6a which specifies price reduction announcement policy.

## Installation

### Standard installation (from WP repository)

This plugin is available in [WordPress repository](https://wordpress.org/plugins/wc-price-history/) for free, so you can find it there.

Alternatively, you can find it in your current WordPress installation (wp-admin &raquo; Plugins &raquo; Add new &raquo; Search ).

### Development version (from GitHub)

Clone this repository and run composer and npm:
```sh
git clone git@github.com:kkarpieszuk/wc-price-history.git
cd wc-price-history
composer install
npm install
```

## How does it work?

This plugin logs prior prices in custom field _wc_price_history. Every time you update your product the prior price will be stored in the log (if it differs from the last stored price).

On the front-end on a single product page and product listings page, just under the product price, your visitors will see a 30-day low price:

![Single Product Page screenshot](https://ps.w.org/wc-price-history/assets/screenshot-1.png?rev=2840303)

The plugin has configurable settings:
![Settings screenshot](https://ps.w.org/wc-price-history/assets/screenshot-2.png?rev=2844611)

### Shortcode

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
### Unit Tests

This plugin includes unit tests that verify the functionality of the `ProductDuplicate` and `ProductUpdates` classes. These tests ensure that the new business logic introduced in these classes works as expected.

In the `ProductDuplicate` class, the `flag_as_duplication_process()` and `delete_history_from_duplicate()` methods are tested. The `flag_as_duplication_process()` test verifies that a product is correctly flagged during the duplication process. The `delete_history_from_duplicate()` test ensures that the price history of a duplicate product is correctly deleted.

In the `ProductUpdates` class, the `update_price_history()` and `start_price_history()` methods are tested. The `update_price_history()` test checks that the price history of a product is correctly updated. The `start_price_history()` test verifies that the price history of a product is correctly started.

To run the tests, use the following command:

```sh
phpunit
```

