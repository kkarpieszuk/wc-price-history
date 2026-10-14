# Pricing Integrations and Bundles

Use this guide before relying on WC Price History with discount engines, bundle products, or custom code that changes WooCommerce prices.

## How WC Price History reads prices

WC Price History stores the product price WooCommerce exposes when a product or variation is created, saved, or backfilled. Product create/update flows pass the value through the `wc_price_history_price_raw_non_taxed` filter before it is written to history.

The lowest-price calculation uses stored history rows and the configured settings under **WooCommerce -> Price History**:

- history window, 30 days by default,
- current-day or sale-start counting mode,
- per-product or per-variation history.

Storefront display then applies WooCommerce tax display rules before formatting the price.

## Dynamic pricing plugins

Compatibility depends on when and how the other plugin changes product prices.

Dynamic pricing usually works best when the discount plugin changes the WooCommerce product price before WC Price History saves history. If the other plugin applies discounts only in the cart, checkout, or front-end display layer, those temporary discounts may not become stored product history.

Before launch:

1. Test on a staging site.
2. Create or update a product that the discount plugin affects.
3. Save the product or variation.
4. Export debug data from the product edit screen.
5. Confirm the stored history matches the price you expect to display as the prior lowest price.

If a discount plugin needs custom handling, a developer can adjust the value before it is stored:

```php
add_filter( 'wc_price_history_price_raw_non_taxed', function( $price, $product ) {
	// Return the non-taxed value that should be stored in price history.
	return $price;
}, 10, 2 );
```

Keep the returned value non-taxed. WC Price History applies tax later for storefront display and REST `lowest` output.

Variation note: when a parent variable product save refreshes variation history, WC Price History reads each variation's WooCommerce price directly. Save an affected variation itself during staging checks if your integration depends on the storage filter.

## Woo Discount Rules

The [Woo Discount Rules](https://wordpress.org/plugins/woo-discount-rules/) plugin has been confirmed as working with WC Price History in the plugin FAQ.

Still test your exact discount rule type on staging, especially if rules are conditional, user-role based, cart-only, or generated outside normal product save flows.

## WooCommerce Product Bundles

WC Price History includes compatibility for WooCommerce Product Bundles when bundled products are priced individually.

Constraints:

- This compatibility requires WC Price History Pro 1.0.1 or later.
- Test bundle products separately from simple and variable products.
- Confirm whether your bundle price is controlled by the bundle product itself, individual bundled items, or another pricing plugin.

Recommended check:

1. Create a bundle with individually priced bundled products.
2. Save the bundle product.
3. Export debug data for the bundle product.
4. Compare the stored price history with the price shown to customers.

## Variable products and integrations

Price history is tracked for each variation. For discount or bundle workflows that affect variations:

- set regular price, sale price, and sale start date on the affected variation,
- test the selected variation on the product page,
- use **Show lowest price only after a variant is selected** if the parent variable product should not show a lowest price before selection.

See [Display and Shortcode Guide](display-and-shortcode.md#variable-products) for front-end variation behavior.

## Support checklist for integration issues

When reporting an integration problem, include:

- the discount or bundle plugin name and version,
- whether the product is simple, variable, variation, or bundled,
- the exact WC Price History settings,
- whether **Sale price dates from** is set,
- debug export JSON for the affected product,
- screenshots of the product edit price fields and storefront output.
