# WC Price History Documentation

Use these guides to configure WC Price History, troubleshoot product-specific issues, and understand how the plugin stores and displays lowest prices.

## Guides

| Guide | Use it for |
| --- | --- |
| [User Manual](user-manual.md) | Installation, default Omnibus-oriented setup, settings, display behavior, REST API options, and support checklist. |
| [Display and Shortcode Guide](display-and-shortcode.md) | Automatic placement, `[wc_price_history]`, variable products, page builders, and CSS hooks. |
| [Pricing Integrations and Bundles](pricing-integrations-and-bundles.md) | Dynamic pricing plugins, Woo Discount Rules, WooCommerce Product Bundles, and staging checks before launch. |
| [Maintenance and Troubleshooting](maintenance-and-troubleshooting.md) | First scan, table storage status, debug export, REST checks, and common problems. |
| [Migration to Database Tables](migration-to-db-tables.md) | Version 3.0+ database-table migration, rollback flag, and table structure. |

## Quick setup path

1. Install and activate the plugin.
2. Go to **WooCommerce -> Price History**.
3. Keep the default Omnibus-oriented settings unless your legal/compliance workflow requires another mode:
   - display on product and shop pages,
   - display only when the product is on sale,
   - count from **Day before product went on sale**,
   - use a 30-day window.
4. For each discounted product or variation, set **Sale price dates from** in WooCommerce.
5. Test one simple product and one variation before enabling the same setup across the store.

## Important constraints

- Lowest-price history is tracked per product or variation ID.
- Current versions normally use dedicated database tables. Legacy post meta can be forced only for troubleshooting.
- The storefront and optional WooCommerce REST API fields use the same configured day range and sale-start mode.
- The plugin helps with technical display requirements, but store owners remain responsible for legal compliance in their jurisdiction.
