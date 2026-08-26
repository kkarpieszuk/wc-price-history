# WooCommerce Product Bundles (Pro)

This guide explains how **WC Price History Pro** works with the official [WooCommerce Product Bundles](https://woocommerce.com/products/product-bundles/) plugin.

## Requirements

All features described on this page require **WC Price History Pro 1.1.0 or later**.

You also need:

- **WC Price History** (free) **3.0+** with price history stored in **database tables** (not legacy post meta only)
- **WooCommerce Product Bundles** (official Woo extension)
- **WooCommerce** and a published bundle product

The free WC Price History plugin alone does **not** show Omnibus lowest-price messages on bundle pages. Since Pro 1.0.1 it can record bundle price history when components use **Priced Individually**; **display** on the storefront is a Pro 1.1.0+ feature.

## What the integration does

When configured correctly, Pro automatically:

1. **Records** the effective bundle price in price history (even when the bundle product has empty Regular / Sale price fields in WooCommerce).
2. **Displays** the lowest price message (Omnibus) on the bundle product page:
   - below the **bundle total** (next to the dynamic price calculated by Product Bundles), and
   - below **individually priced bundled items** (including variable products inside the bundle).
3. **Applies bundle discounts** when calculating the lowest price shown for bundled components (for example a 15% bundle discount on each item).
4. **Updates** bundled variable products when the shopper selects a variation (if defer-lowest-price is enabled for variable products).
5. **Re-syncs** bundle history when you change prices on bundled products or their variations.

Pro reuses your existing **WooCommerce → Price History** settings (display location, “only when on sale”, 30-day window, minimal price text, variable defer placeholder, and so on).

## Supported bundle setup

The integration targets the common Omnibus scenario:

| Setting | Expected value |
|---------|------------------|
| Bundle product type | **Bundle** |
| Bundled components | Simple and/or **variable** products |
| **Priced Individually** | Enabled on bundled items that should show their own price |
| Bundle discount | Optional percentage discount per bundled item (e.g. 15%) |
| Bundle parent prices | Often **empty** in WooCommerce — this is normal for individually priced bundles |

**Example:** A “Duvet + pillow set” bundle contains two variable products. Each item is priced individually with a 15% in-bundle discount. Shoppers see promotional line prices, a bundle total, and — with Pro — the lowest-price-in-30-days message where applicable.

## What is shown and where

### Bundle total

The Omnibus message appears **below the bundle total price** in the add-to-cart area (the block Product Bundles fills via JavaScript).

It is **not** duplicated in the theme product header by default: Pro renders the message only at the bundle total, to avoid double output.

The lowest price is taken from **price history of the bundle product itself**, using the effective bundle price (minimum configuration) recorded when the bundle or its components are saved.

### Individually priced bundled items

For each bundled product with **Priced Individually** enabled, the lowest-price message appears **under that item’s price** on the bundle page.

- **Simple bundled products:** lowest price from that product’s history, adjusted by the bundle discount % if set.
- **Variable bundled products:** same as standard variable products if **Show lowest price only after a variant is selected** is enabled in Price History settings — a placeholder until the shopper picks a variation, then the lowest price for the selected variation (with bundle discount applied).

### What is not covered

- Bundles **without** individually priced items (fixed bundle price only in WooCommerce fields) behave like a normal product for display; the bundle-total integration still applies when the bundle is treated as on sale.
- **Dynamic bundle total per variation combination:** the Omnibus message at bundle total reflects **bundle product history**, not a separate history entry for every possible variant combination. Component-level messages update per selected variation (phase 2 behaviour).
- **Composite Products** are a separate Pro compatibility (see changelog); this guide is only for **Product Bundles**.

## Store configuration checklist

1. Install and activate **WC Price History**, **WC Price History Pro 1.1.0+**, **WooCommerce**, and **Product Bundles**.
2. Confirm **WooCommerce → Price History** uses database tables (Status panel).
3. Configure display as for other products, for example:
   - **Display on:** Single product page
   - **Display minimal price when:** Only when product is on sale
   - **Count lowest price from:** Day before product went on sale (recommended for Omnibus)
4. Create or edit a **Bundle** product:
   - Add bundled products (variable allowed).
   - Enable **Priced Individually** on items that should show line prices.
   - Set **Discount %** on bundled items if you run in-bundle promotions.
5. Ensure bundled **variations** have regular/sale prices and, where needed, **sale start dates** on each variation (same as non-bundle variable products).
6. **Save** the bundle and each bundled product so price history is recorded.
7. Open the bundle on the storefront while it is **on sale** (bundle discount or component sale makes the bundle report as on sale).

## Price history and saves

| Event | Effect on history |
|-------|-------------------|
| Save bundle product | Records effective bundle price (`get_bundle_price` minimum) on the **bundle product ID** |
| Save bundled simple product | Updates that product’s history; Pro syncs parent **bundle** history |
| Save bundled variation | Updates variation history; Pro syncs parent product and parent **bundle** |

If the bundle total Omnibus value looks wrong after a price change, save the bundle once or update a bundled component price and save again to refresh synced bundle history.

## Troubleshooting

**No message on bundle page**

- Confirm **WC Price History Pro 1.1.0+** is active (not only the free plugin).
- Check **Display on → Single product page** and **Display minimal price when** (bundle must be on sale for the default “only on sale” setting).
- Open the bundle in the admin and save it once so history is not empty or zero.
- Verify **Priced Individually** and discounts match the setup this integration expects.

**Message on components but not on bundle total**

- Ensure the bundle is on sale (e.g. mandatory items have a bundle discount).
- Check that Product Bundles renders the total (`.bundle_price`); some themes override templates.

**Variable bundled item shows placeholder forever**

- Shoppers must select all required attributes so Product Bundles fires `found_variation`.
- Ensure each variation has price history (edit variation, set prices, save).
- Confirm **defer lowest price until variant selected** behaviour is intended.

**Lowest price ignores bundle discount on components**

- Requires Pro 1.1.0+; discount is applied in bundled-item context only when the item is rendered inside the bundle form.

**Logs**

- Check **WooCommerce → Status → Logs** for source `wc-price-history` (e.g. bundle on sale without sale start date when using “day before sale” mode).

For exports and deeper diagnosis, see [Maintenance and Troubleshooting](maintenance-and-troubleshooting.md).

## Related documentation

- [User Manual](user-manual.md) — global plugin settings and Omnibus defaults
- [Display and Shortcode Guide](display-and-shortcode.md) — text template, variable products, shortcode limits on bundle pages
- [WC Price History Pro changelog](https://wcpricehistory.com/) — version history for bundle features (1.0.1 storage, 1.1.0 display)

## Legal note

Product Bundles add complexity (bundle totals, per-item discounts, variant combinations). WC Price History Pro provides technical support for lowest-price display; store owners remain responsible for compliance with applicable law in their market.
