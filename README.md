# wc-price-history
Track WooCommerce© Products prices history and display the lowest price in the last 30 days. This plugin allows your WC shop to be compliant with European Commission Directive 98/6/EC Article 6a which specifies price reduction announcement policy.

## Installation

### Standard installation (from WP repository)

This plugin is available in [WordPress repository](https://wordpress.org/plugins/wc-price-history/) for free, so you can find it there.

Alternatively, you can find it in your current WordPress installation (wp-admin &raquo; Plugins &raquo; Add new &raquo; Search ).

### Development version (from GitHub)

Clone this repository and run composer and npm:
```
git clone git@github.com:kkarpieszuk/wc-price-history.git
cd wc-price-history
composer install
npm install
```

## How does it work?

This plugin logs prior prices in WordPress revisions, so make sure you have enabled them and are not limiting their number. If you do so, the plugin will display a warning message instead of 30-day low-price info on the front-end (only for logged-in site administrators).

Every time you update your product description, regular price, or sale price, the prior price will be stored in the log.

On the front-end on a single product page and product listings page, just under the product price, your visitors will see a 30-day low price:

![Single Product Page screenshot](https://ps.w.org/wc-price-history/assets/screenshot-1.png?rev=2840303)

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

