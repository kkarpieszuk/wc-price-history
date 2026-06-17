# Maintenance and Troubleshooting

Use this guide when price history is missing, looks wrong, or you need to collect support data.

## First scan after activation

After activation, WC Price History scans existing published products that already have prices. This gives products initial history before their next manual edit.

During the scan:

- An admin notice says the scan is running.
- Avoid editing products until the scan finishes.
- The scan processes products in admin requests and then shows a success notice.

If the scan appears stuck, go to **WooCommerce -> Price History**:

- **Force finish scan** marks the scan as finished. Use it only when you are sure the scan will not continue.
- **Restart scan** starts the scan status over. Use it when some products still have no history.

On current database-table storage, the first price written for a product is stored for:

- the current moment,
- 24 hours earlier,
- 48 hours earlier.

These entries all use the same price. They give new or newly backfilled products usable "last 30 days" history immediately without pretending that older price changes happened.

## Database table storage

Current versions store price history in dedicated database tables after migration or on fresh installs. The settings page shows the active storage method in the **Status** panel.

Possible status values:

- **Database tables** - Normal current storage mode.
- **Post meta (legacy)** - Legacy mode, usually only for troubleshooting.

For migration steps, rollback flag, and table details, see [Migration to Database Tables](migration-to-db-tables.md).

## Danger zone actions

These actions are under **WooCommerce -> Price History**. Make a database backup before using them.

### Clean history

Removes all stored price history.

- With database tables, the plugin truncates its history tables.
- With legacy post meta, it deletes `_wc_price_history` post meta.
- This cannot be undone without a database backup.

Use this only when you intentionally want to remove all history, for example before uninstalling or rebuilding history from scratch.

### Fix prices history

This is a legacy repair action for post-meta history. It extends old stored histories backwards by one day to help with older incorrect histories.

On stores using database tables, this action does not change table data.

## Export debug data

Each product edit screen has a **Price History** side box with **Export debug data**.

Use the export when reporting issues. It includes:

- Plugin settings.
- Storage method details.
- Product regular price, sale price, sale start date, permalink, attributes, and history.
- For variable products, each variation's price data, permalink, attributes, and history.

The export is intended for debugging. Review it before sharing if your store data is sensitive.

The JSON downloaded by the browser has this outer shape:

```json
{
  "product_name": "Example product",
  "serialized": "..."
}
```

The `serialized` value is PHP-serialized data. After unserializing it, support can inspect:

- `settings` - plugin settings plus storage method details,
- `product` - exported product data and history,
- `variations` - exported variation data and history, present for variable products.

If a product has no stored history yet, exporting can trigger the same empty-history fill used by normal history reads. On database-table storage, that means the current price can be saved for now, 24 hours earlier, and 48 hours earlier.

For a local developer/support workflow, the repository includes:

```bash
php .scripts/display-price-history.php path/to/export.json
```

This helper prints the exported history in a more readable timeline.

## REST API checks

REST API fields are disabled by default. Enable them only when an integration needs them:

- **Expose lowest prior price in the WooCommerce REST API**
- **Expose full price history in the WooCommerce REST API**

When enabled, WooCommerce product and variation REST responses can include:

```json
{
  "wc_price_history": {
    "lowest": 19.99,
    "history": {
      "1717171717": 24.99
    }
  }
}
```

Notes:

- This applies to WooCommerce REST API product endpoints, not the Store API (`/wc/store/...`).
- Anyone who can read products through the WooCommerce REST API can see enabled price history fields.
- Reading a product through the REST API does not create or backfill history.
- The lowest value follows the same tax-inclusive or tax-exclusive logic as the storefront.

The repository includes a helper for local API checks:

```bash
php .scripts/fetch-wc-rest-product-price-history.php 'https://example.test/wp-json/wc/v3/products/123'
```

## Common problems

### The lowest-price message does not display

Check:

1. WooCommerce is active.
2. The product is published.
3. The product has a positive price.
4. The relevant display location is enabled.
5. If using the default settings, the product is on sale.
6. If counting from sale start, the product or variation has **Sale price dates from** set.
7. The product has usable price history. New or imported products may need the first scan or a save.

### The plugin counts from today instead of sale start

The product is probably on sale without a sale start date. Edit the product or variation and set **Sale price dates from**.

The plugin also logs these products in **WooCommerce -> Status -> Logs**. Choose a log whose source starts with `wc-price-history`.

### Variable product values look wrong

Check the variations, not only the parent product:

- Each variation should have its own regular price.
- Sale price and sale start date should be set on the discounted variation.
- If the parent product shows a value before a variation is selected, enable **Show lowest price only after a variant is selected**.
- If your single product template is built with blocks or a page builder, confirm it still renders WooCommerce variation data and the `form.variations_form` element. The front-end updater listens for WooCommerce variation-selection events on single product pages.

### A new product already has history entries

That can be expected. On database-table storage, the first price is saved for now, 24 hours earlier, and 48 hours earlier so the configured day-window calculation works from day one.

These entries should have the same price. If they do not, export debug data and include it in a support request.

### The lowest price includes or excludes the sale price unexpectedly

Check **For products being on sale, count minimal price from**:

- **Day before product went on sale** searches history before the sale start moment and excludes the promotional price at sale start.
- **Day when product went on sale** includes the sale start day.
- **Current day** ignores the sale start date and searches back from today.

Also confirm **Sale price dates from** is set. Without it, sale-start modes fall back to current-day counting and log an error under **WooCommerce -> Status -> Logs**.

### Shortcode output is empty

Check:

- The `id` attribute points to an existing product or variation.
- The current page has a product context if no `id` is provided.
- The target product has a positive price and stored history.
- For variable products, use a variation ID when you need a specific variation's history.

### A duplicated product has no old history

That is expected. The plugin does not copy price history from an original product to its duplicate, so the duplicate starts fresh.

### Draft products do not collect history

That is expected. The plugin skips draft products and starts/updates history after products are published or saved in a non-draft status.

### Dynamic pricing plugins

Compatibility depends on how the other plugin changes WooCommerce product prices. If a discount plugin does not update the values WC Price History reads, history or display may not match your promotion. Test on a staging site before relying on a dynamic-pricing workflow.

See [Pricing Integrations and Bundles](pricing-integrations-and-bundles.md) for staging checks, Woo Discount Rules notes, WooCommerce Product Bundles constraints, and the `wc_price_history_price_raw_non_taxed` filter.

## What to include in a support request

Include:

- WC Price History version.
- WordPress and WooCommerce versions.
- Active storage method from **WooCommerce -> Price History -> Status**.
- Product type: simple, variable, or variation.
- Exact settings for display location, display timing, days, and count-from mode.
- Whether the product has **Sale price dates from** set.
- Debug export JSON for the affected product.
- Relevant `wc-price-history` WooCommerce log entries.
