# Pricing Integrations and Product Bundles

Use this guide when prices are affected by WooCommerce Product Bundles, WC Price History Pro, or another pricing extension.

## How price capture works

WC Price History records product prices when WooCommerce creates or saves a product or variation. Before a current price is stored or used as a fallback, the plugin passes WooCommerce's raw non-taxed price through this filter:

```php
wc_price_history_price_raw_non_taxed
```

This integration point lets compatible extensions adjust the value that is stored in history. It is used by the Product Bundles compatibility path described in the plugin changelog.

Important constraints:

- Price history is saved per WooCommerce product or variation ID.
- Cart-only discounts are not stored unless they also change the product price value available during product save/history creation.
- The storefront display, shortcode, REST API lowest value, migration fallback rows, and empty-history backfill all depend on the stored history value.
- Existing history is not recalculated automatically after an integration is installed or changed.

## WooCommerce Product Bundles

WC Price History can work with WooCommerce Product Bundles when bundled products are priced individually. This compatibility requires **WC Price History Pro 1.0.1 or later**, because the free plugin provides the shared history and filter points while the Pro integration supplies the bundle-aware price adjustment.

Recommended setup:

1. Install and activate WooCommerce Product Bundles.
2. Install and activate WC Price History and WC Price History Pro 1.0.1 or later.
3. Configure the bundle according to WooCommerce Product Bundles, including whether bundled products are priced individually.
4. Set prices and sale dates on the products or variations that participate in the bundle.
5. Save the bundle product after changing its pricing setup so WooCommerce triggers a product update for the bundle itself.
6. Test the bundle product page and one bundled item page before relying on the display in production.

What to expect:

- The bundle product and each bundled product keep separate history because they are separate WooCommerce products.
- If bundled items are priced individually, the bundle's stored history should reflect the integration-adjusted bundle price when the Pro compatibility layer is active.
- If you duplicate a bundle, the duplicate starts with fresh WC Price History data like other duplicated products.

## Dynamic pricing and discount plugins

Compatibility depends on where the other plugin applies the discount.

Likely to work:

- The extension changes the WooCommerce product price before the product is saved or before empty history is backfilled.
- The extension integrates with `wc_price_history_price_raw_non_taxed`.

Likely not to be captured:

- Discounts applied only in the cart, checkout, coupons, customer-specific sessions, or temporary front-end calculations.
- Prices that depend on quantity, role, or context that is not available during product save/history creation.

For Omnibus-style display, prefer a WooCommerce sale price with **Sale price dates from** set on the product or variation.

## Troubleshooting integration prices

If a bundle or pricing integration shows an unexpected lowest price:

1. Confirm the product has stored history and a positive price.
2. Confirm sale dates are set on the discounted product or variation when using a sale-start calculation mode.
3. Save the affected product or bundle again after changing integration settings.
4. Check **WooCommerce -> Status -> Logs** for sources starting with `wc-price-history`.
5. Export debug data from the product edit screen and compare the product history with variation or bundled-item histories.
6. Temporarily test with the pricing integration disabled on a staging site to confirm whether the stored value comes from the integration.

When opening a support request, include the debug export JSON for the bundle product and one affected bundled product or variation.
