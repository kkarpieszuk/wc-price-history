# Display and Shortcode Guide

Use this guide when you want to control where the lowest-price message appears or place it manually with a shortcode.

## Automatic storefront display

Configure automatic display in **WooCommerce -> Price History**.

The plugin appends the lowest-price message to WooCommerce product price HTML when both checks pass:

1. The current page matches an enabled display location.
2. The product matches the "Display minimal price when" setting.

Available locations:

- Single product page
- Related and upsell products on a single product page
- Main shop page
- Product category pages
- Product tag pages

Default locations are the single product page and main shop page. By default, the message is shown only for products WooCommerce reports as on sale.

## Text template

The storefront message uses **Minimal price text** from the settings page.

Default:

```text
30-day low: {price}
```

Supported placeholders:

- `{price}` - Formatted WooCommerce price.
- `{days}` - Configured history window, 30 by default.

Example:

```text
Lowest price in the last {days} days: {price}
```

WooCommerce tax display settings are respected. If your shop prices are displayed including tax, the lowest-price value is shown including tax; if your shop displays excluding tax, it is shown excluding tax.

## Variable products

Price history is tracked per variation.

Recommended setup:

1. Edit the variable product.
2. Open each variation.
3. Set regular price, sale price, and sale start date on the variation.
4. Save changes.

If **Show lowest price only after a variant is selected** is enabled, the parent variable product shows the configured placeholder until the shopper selects a variation. When WooCommerce fires its variation-selection event, the plugin replaces the placeholder or raw lowest-price value with that variation's lowest price.

Constraints:

- The front-end variation script is loaded on single product pages.
- The parent variable product does not replace the need for per-variation prices and sale dates.
- If no variation has a usable lowest price yet, WooCommerce may still have no meaningful value to show.
- Variation data is added to WooCommerce's available-variation payload, including templates rendered with WooCommerce Blocks. The live replacement still depends on the page firing WooCommerce's `found_variation` and `reset_data` events from a `form.variations_form` element.
- If a block theme or page builder replaces the standard variation form, use the shortcode for manual placement and test variation selection on the front end.

## Blocks, page builders, and custom templates

Automatic display works by appending the lowest-price message to WooCommerce price HTML. Most themes and product templates use that price HTML, but highly customized block or page-builder layouts can move, duplicate, or replace it.

Recommended checks:

1. Enable the intended display location under **WooCommerce -> Price History**.
2. View a simple product and confirm the message appears below the price.
3. View a variable product and select a variation.
4. Confirm the displayed lowest price changes to the selected variation's value.
5. If the layout does not render the automatic output where you need it, disable that automatic location and place `[wc_price_history]` manually.

The plugin stylesheet forces the automatic message onto a new line for themes that use inline or flex price layouts. If your theme still places it beside the price, target `.wc-price-history.prior-price.lowest` in your theme CSS.

## Shortcode usage

Use `[wc_price_history]` to place the lowest price in product descriptions, page builder content, or custom templates that render shortcodes.

Examples:

```text
Lowest recent price: [wc_price_history]
Product 123 lowest recent price: [wc_price_history id="123"]
Without a currency symbol: [wc_price_history id="123" show_currency="0"]
```

Attributes:

| Attribute | Default | Meaning |
| --- | --- | --- |
| `id` | Current product ID on product pages | Product or variation ID to read history for. |
| `show_currency` | `1` | Set to `0` to hide the currency symbol. |

Shortcode behavior differs from automatic storefront display in a few intentional ways:

- It outputs the formatted lowest price only, not the full **Minimal price text** template.
- It does not check the automatic display locations.
- It does not apply the automatic "Display minimal price when" visibility setting.
- It uses the configured day range and sale-start counting method.
- It returns empty output when the product ID is missing, invalid, or has no valid lowest price; the automatic old-history fallback text is not used.
- For variable products with defer enabled, it shows the configured placeholder until a variation is selected.

## Styling hooks

Automatic output uses markup similar to:

```html
<div class="wc-price-history prior-price lowest" data-product-id="123" data-original-price="99">
	<span class="wc-price-history-lowest-inner">
		30-day low: <span class="wc-price-history prior-price-value">...</span>
	</span>
</div>
```

Shortcode output uses:

```html
<div class="wc-price-history-shortcode" data-product-id="123" data-original-price="99">...</div>
```

Common classes:

- `.wc-price-history`
- `.prior-price`
- `.lowest`
- `.prior-price-value`
- `.wc-price-history-lowest-inner`
- `.wc-price-history-shortcode`
- `.wc-price-history-shortcode--defer`
- `.line-through`

The plugin stylesheet makes the lowest-price message a block-level line below the product price. If your theme still places it incorrectly, target `.wc-price-history.prior-price.lowest` in your theme CSS.

## Common use cases

### Show the message only in a custom product template

1. Disable automatic display locations under **WooCommerce -> Price History**.
2. Add `[wc_price_history]` where your template or page builder accepts shortcodes.
3. Test an on-sale product with a sale start date.

### Show a specific variation's history

Use the variation ID:

```text
[wc_price_history id="456"]
```

This reads history for that variation, not the parent variable product.

### Integrate with a custom layout

Use automatic display when possible. If your layout cannot use WooCommerce price HTML, use the shortcode and style `.wc-price-history-shortcode`.

For dynamic pricing plugins, bundle products, and other price-changing integrations, see [Pricing Integrations and Bundles](pricing-integrations-and-bundles.md).
