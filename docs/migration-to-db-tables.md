# Migration to Database Tables

## Why

Starting with version 3.0 the plugin moves price history from post meta into dedicated database tables. Post meta does not scale for long-running history: each change appends more serialized data, reads and writes require heavy PHP processing, and querying specific ranges or minimums is slow and hard to optimize. The meta field structure also limits what we can store (e.g., separate sale price, previous values, future metadata) and makes indexing impossible.

Custom tables fix this: inserts and reads are faster, indexed lookups (by product and date) make calculations like “minimal in last 30 days” cheap, and extra columns let us store more structured data (regular price, sale price, previous prices, flags) now and in the future without bending serialized arrays. This is the foundation for further features and more reliable analytics.

## Prerequisites

The migration has been tested on multiple real stores (dozens of sites), including shops with variable products and multilingual setups, without issues. Still, creating a full database backup before running it is strongly recommended.

For extra safety, the legacy post meta data is not deleted after migration, so you can revert to it if needed (details below).

## How to Migrate

1) Go to wp-admin. If migration is needed, a “WC Price History database update” notice appears with a progress bar.
2) Click “Update Database”. The process runs in AJAX batches (20 products per batch), shows live progress, and resumes automatically on page reload.
3) When finished, the notice changes to a success state; you can dismiss it. Post meta data is kept for safety (see “Returning to Post Meta”).

## Returning to Post Meta

The legacy post meta data is preserved after migration. If you need to temporarily switch back (for troubleshooting), add to `wp-config.php`:

```php
define( 'WC_PRICE_HISTORY_USE_POST_META', true );
```

With this flag set, the plugin will read/write price history from post meta instead of the new tables. Remove or set it to `false` to return to table storage. Because legacy data stays intact, you can safely test the new storage and fall back if needed.

## Database Table Details

Two tables are created:

1) `{prefix}wc_price_history` (main records)
   - `id` (bigint, PK, auto increment)
   - `product_id` (bigint, product/post ID)
   - `price` (decimal 19,4) — regular price at that time
   - `sale_price` (decimal 19,4, nullable) — sale price at that time
   - `previous_price` (decimal 19,4, nullable) — previous regular price before the change
   - `previous_sale_price` (decimal 19,4, nullable) — previous sale price before the change
   - `date` (datetime, local)
   - `date_gmt` (datetime, UTC)
   - `include_in_history` (tinyint, default 1)
   - Indexes: by `product_id`, `date_gmt`, (`product_id`, `date_gmt`); unique (`product_id`, `date_gmt`) to avoid duplicates

2) `{prefix}wc_price_history_meta` (flexible metadata)
   - `meta_id` (bigint, PK, auto increment)
   - `price_history_id` (bigint, FK to main table)
   - `meta_key` (varchar 255)
   - `meta_value` (longtext)
   - Indexes: by `price_history_id`, `meta_key`

Meta keys currently used:
- `was_on_sale` — whether the product was on sale at that moment
- `change_reason` — reserved for future/manual annotations

## Need help?

If you encounter any issues with the migration or the new table storage, please report them on the support forum: https://wordpress.org/plugins/wc-price-history/ or as a GitHub issue: https://github.com/kkarpieszuk/wc-price-history/issues
