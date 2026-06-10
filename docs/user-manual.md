# WC Price History - User Manual

## Table of Contents

### 1. Introduction

#### What is WC Price History

WC Price History is a powerful WordPress plugin designed specifically for WooCommerce stores that automatically tracks and displays the lowest price of products over a configurable period (default: 30 days). The plugin helps e-commerce businesses comply with European Union regulations while providing valuable pricing information to customers.

**Key Features:**
- Automatic price history tracking for all WooCommerce products
- Displays lowest price from the last 30 days (configurable)
- Full compliance with EU Omnibus Directive (98/6/EC Article 6a)
- Flexible display options (product pages, shop, categories, tags)
- Shortcode support for custom placement
- Optional WooCommerce REST API exposure of lowest price and full history (off by default; see Plugin Configuration)
- Support for product variations and sales
- Compatibility path for WooCommerce Product Bundles when WC Price History Pro 1.0.1 or later supplies the bundle-aware pricing integration

#### Benefits of using the plugin

**For Store Owners:**
- **Legal Compliance**: Automatically meet EU requirements for price reduction announcements
- **Customer Trust**: Transparent pricing builds customer confidence
- **Sales Boost**: Highlighting price reductions can increase conversion rates
- **Easy Management**: Set-and-forget functionality with minimal configuration
- **Flexible Display**: Show pricing information exactly where you need it

**For Customers:**
- **Price Transparency**: See the lowest price in recent history
- **Informed Decisions**: Make better purchasing choices with complete price information
- **Trust Building**: Clear pricing history increases confidence in the store
- **Value Recognition**: Easily identify genuine deals and discounts

#### EU Law compliance (Omnibus Directive)

The plugin is specifically designed to help WooCommerce stores comply with the **European Union Omnibus Directive (98/6/EC Article 6a)**, which requires:

**Key Requirements:**
- Display the lowest price from the last 30 days when announcing price reductions
- Show this information clearly and prominently to customers
- Apply to all products that are on sale or have reduced prices
- Ensure the information is accurate and up-to-date

**How WC Price History Helps:**
- **Automatic Tracking**: Continuously monitors all price changes
- **30-Day Window**: Default configuration matches EU requirements
- **Sale-Specific Display**: Can show lowest price only when products are on sale
- **Flexible Counting**: Count from sale start date or current date as required
- **Clear Display**: Prominently shows the lowest price with customizable text
- **Audit Trail**: Maintains complete price history for compliance verification

**Legal Disclaimer:**
While WC Price History helps implement the technical requirements of the Omnibus Directive, store owners are responsible for ensuring full compliance with all applicable laws and regulations in their jurisdiction. Always consult with legal professionals for specific compliance requirements.

### 2. Installation and Activation

#### System Requirements

Before installing WC Price History, ensure your WordPress site meets the following requirements:

**Required:**
- WordPress 5.8 or higher
- WooCommerce 3.0 or higher (must be installed and activated)

**System Requirements:**
- PHP, MySQL, and memory requirements are the same as WordPress and WooCommerce
- If your store already runs WordPress and WooCommerce successfully, no additional system changes are needed

#### Installation from WordPress Repository

**Method 1: WordPress Admin Dashboard**
1. Log in to your WordPress admin dashboard
2. Navigate to **Plugins** → **Add New**
3. Search for "WC Price History" or "wc-price-history"
4. Click **Install Now** next to the plugin
5. Click **Activate** after installation completes

**Method 2: WordPress.org Plugin Directory**
1. Visit the [WC Price History plugin page](https://wordpress.org/plugins/wc-price-history/)
2. Click the **Download** button
3. Upload the ZIP file via **Plugins** → **Add New** → **Upload Plugin**
4. Click **Install Now** and then **Activate**

#### Installation from GitHub (Development Version)

For developers or users who want the latest features:

**Prerequisites:**
- Git installed on your server
- Composer installed
- Node.js and npm installed

**Installation Steps:**
1. Clone the repository:
   ```bash
   git clone https://github.com/kkarpieszuk/wc-price-history.git
   cd wc-price-history
   ```

2. Install PHP dependencies:
   ```bash
   composer install
   ```

3. Install Node.js dependencies:
   ```bash
   npm install
   ```

4. Copy the plugin folder to your WordPress plugins directory:
   ```bash
   cp -r wc-price-history /path/to/wordpress/wp-content/plugins/
   ```

5. Activate the plugin through WordPress admin dashboard

#### Plugin Activation

**After Installation:**
1. Navigate to **Plugins** in your WordPress admin
2. Find "WC Price History" in the list
3. Click **Activate**

**First-Time Setup:**
- The plugin will automatically start scanning your existing products
- You'll see a notice about the initial scan process
- You can configure settings while the scan is running

**Verification:**
- Check that WooCommerce is active and functioning
- Visit a product page to see if price history appears
- Note: With default settings, price history will only show on products that are on sale with a set sale start date
- Access plugin settings via **WooCommerce** → **Price History**

**Troubleshooting Installation:**
- Ensure WooCommerce is installed and activated first
- Check file permissions on the plugin directory
- Check WordPress/PHP error logs for any issues
- Check if plugin is active
- If above does not help, [visit support forum](https://wordpress.org/support/plugin/wc-price-history/)


### 3. Plugin Configuration

#### Accessing Settings

To configure WC Price History settings:

1. **Navigate to Settings:**
   - Go to **WooCommerce** → **Price History** in your WordPress admin
   - Or click the "Settings" link on the plugins page

2. **Settings Overview:**
   - The settings page contains all configuration options
   - Changes are saved immediately when you click "Save Changes"
   - Settings are organized into logical sections

#### Where to Display Lowest Price

Configure where the lowest price information appears:

**Display Locations:**
- **Single Product Page**: Show on individual product pages
- **Related Products**: Include on related/upsell products on product pages
- **Main Shop Page**: Display on the main shop/archive page
- **Product Category Page**: Show on category archive pages
- **Product Tag Page**: Display on tag archive pages

**Shortcode Alternative:**
- Use `[wc_price_history]` shortcode anywhere in content
- Provides maximum flexibility for custom placement
- When "Do not display the lowest price on variable product before choosing a variation" is enabled, the shortcode also shows the placeholder text until a variant is selected (same behaviour as the default price block)

#### When to Display Lowest Price

Control when the lowest price information is shown:

**Display Options:**
- **Always**: Show on all products regardless of sale status
- **Only When Product is On Sale**: Display only when product has a sale price

**EU Compliance Note:**
- The Omnibus Directive requires showing lowest price when announcing price reductions
- "Only When Product is On Sale" setting helps ensure compliance

#### How to Calculate Lowest Price

Configure the calculation method for lowest price:

**Calculation Methods:**
- **Current Day**: Count 30 days back from today
- **Day Before Sale Started**: Count 30 days before sale start date (excludes promotional price)
- **Day When Sale Started**: Count 30 days from sale start date (includes promotional price)

**Sale Date Requirements:**
- For sale-based calculations, products must have "Sale price dates" set
- If no sale start date is set, falls back to "Current Day" method
- Products without sale dates are logged for review

#### Customizing Display Text

Personalize how the lowest price is displayed:

**Text Template:**
- Default: "30-day low: {price}"
- Use placeholders: `{price}` for price, `{days}` for number of days
- Customize to match your store's tone and language

**Styling Options:**
- **Line-through**: Apply strikethrough to the price
- **CSS Classes**: Use `.wc-price-history` and `.prior-price` for custom styling
- **Price Format**: Follows WooCommerce price formatting settings

**Old History Handling:**
When price history is older than the set period:
- **Hide**: Don't display anything
- **Current Price**: Show current price instead
- **Custom Text**: Display custom message with placeholders

#### WooCommerce REST API

Under **WooCommerce → Price History**, two optional checkboxes control whether price history data is attached to **WooCommerce REST API** product and variation responses (`/wp-json/wc/v2/products`, `/wp-json/wc/v3/products`, and the matching variation endpoints). **Both are disabled by default.**

- **Expose lowest prior price in the WooCommerce REST API** — When enabled, each response includes a `wc_price_history` object with a `lowest` property: a floating-point value that matches the tax-inclusive lowest price logic used on the storefront.
- **Expose full price history in the WooCommerce REST API** — When enabled, `wc_price_history` also includes `history`: an object whose keys are Unix timestamps (as strings in JSON) and whose values are prices (floats). Anyone who can read products through the REST API will see this data; enable only if your integrations need it. If nothing is stored yet, `history` is empty; reading products via the REST API does **not** create or backfill history rows.

This applies to the classic WooCommerce REST API, **not** the separate Store API used by some blocks (`/wc/store/...`).

For variable products, the parent product and each variation have their own history and product ID; the REST payload reflects the **same** product or variation as the endpoint, the same way debug export works per ID.

### 4. Displaying Lowest Price

The plugin adds the lowest-price message to WooCommerce price HTML after it checks both display location and sale-status settings.

**Default behavior after activation:**
- The message is enabled on the single product page and main shop page.
- It appears only for products WooCommerce reports as "on sale".
- It uses "Day before product went on sale" and 30 days by default, which is the recommended Omnibus setup.

**Display locations you can enable:**
- Single product page
- Related and upsell products on a single product page
- Main shop page
- Product category archives
- Product tag archives

If you need a custom placement inside page content, use the `[wc_price_history]` shortcode instead of enabling another automatic location. See [Display and Shortcode Guide](display-and-shortcode.md).

### 5. Shortcode [wc_price_history]

Use the shortcode when your theme, page builder, or product template needs the lowest price in a specific place.

```text
[wc_price_history]
[wc_price_history id="123"]
[wc_price_history id="123" show_currency="0"]
```

**Supported attributes:**
- `id` - Product or variation ID. If omitted on a product page, the current product is used.
- `show_currency` - `1` by default. Set to `0` to hide the currency symbol.

Important constraints:
- The shortcode outputs the formatted price only, wrapped in plugin markup. It does not use the "Minimal price text" template.
- It does not apply the automatic "Display minimal price when" visibility setting.
- It uses the configured day range and sale-start counting method.
- It returns nothing when there is no valid lowest price for the requested product; the automatic old-history fallback text is not used.
- For variable products, the defer setting also affects the shortcode: it can show the configured placeholder until the customer selects a variation.

See [Display and Shortcode Guide](display-and-shortcode.md) for variable-product examples and styling hooks.

### 6. Price History Management

Price history is stored automatically. Most stores do not need manual maintenance after setup.

**Initial product scan**
- After activation, the plugin scans published products with prices so existing products receive initial history.
- While the scan is running, the admin notice asks you not to edit products.
- The settings page can force-finish a stuck scan or restart the scan if some products still have no history.

**Storage status**
- Go to **WooCommerce -> Price History** and open the **Status** panel in the right column.
- Current versions use dedicated database tables after migration or on fresh installs.
- Legacy post meta can still be forced for troubleshooting with `WC_PRICE_HISTORY_USE_POST_META`; see [Migration to Database Tables](migration-to-db-tables.md).

**Danger zone actions**
- **Clean history** removes all stored price history. With table storage it truncates the plugin tables; with legacy storage it removes the `_wc_price_history` post meta. Make a database backup first.
- **Fix prices history** is a legacy post-meta repair action. On stores using database tables, it does not change table data.

**Export debug data**
- On a product edit screen, use the **Price History** side box to export debug data to JSON.
- The export includes product data, price history, plugin settings, storage method details, and the product permalink.
- For variable products, each variation is exported with its own history and permalink.

For support workflows and common fixes, see [Maintenance and Troubleshooting](maintenance-and-troubleshooting.md).

### 7. Sale Handling

For Omnibus-style display, set sale dates in WooCommerce for each discounted product:

1. Edit the product or variation.
2. Set a regular price and sale price.
3. Set **Sale price dates from** to the date the sale starts.
4. Save the product.

When the setting is **Day before product went on sale**, the plugin calculates the lowest price in the configured period before the sale start moment and excludes the promotional price at the sale start.

If an on-sale product has no sale start date, the plugin falls back to counting from the current day and logs the product in **WooCommerce -> Status -> Logs** with a source starting with `wc-price-history`.

For variable products, set price history inputs on each variation. The parent variable product does not have its own sale dates in the same way variations do.

### 8. Appearance Customization

The automatic storefront output uses:

```html
<div class="wc-price-history prior-price lowest">
	<span class="wc-price-history-lowest-inner">30-day low: ...</span>
</div>
```

Useful CSS classes:
- `.wc-price-history`
- `.prior-price`
- `.prior-price-value`
- `.wc-price-history-lowest-inner`
- `.wc-price-history-shortcode`
- `.line-through` when the line-through option is enabled

The plugin CSS makes the lowest-price block appear on its own line in themes that use inline or flex price layouts. For custom text, edit **Minimal price text** and use `{price}` and `{days}` placeholders.

For advanced customization with filters and shortcode markup, see [Display and Shortcode Guide](display-and-shortcode.md).

### 9. Troubleshooting

Start with these checks:

- Confirm WooCommerce is active.
- Confirm the product is published and has a positive price.
- With default settings, confirm the product is on sale and has a sale start date.
- Check **WooCommerce -> Price History** settings for display location and "Display minimal price when".
- Check **WooCommerce -> Status -> Logs** for logs whose source starts with `wc-price-history`.
- Export debug data from the product edit screen before opening a support request.

Common pitfalls and recovery steps are documented in [Maintenance and Troubleshooting](maintenance-and-troubleshooting.md).

### 10. FAQ - Frequently Asked Questions

**Are the default settings Omnibus-oriented?**
Yes. By default, the plugin shows the message for on-sale products, uses a 30-day window, and counts from the day before the sale started.

**Why is nothing displayed?**
The most common causes are: the product is not on sale, the display location is disabled, no valid price history exists yet, or the product has a zero/empty price.

**Does the plugin support variable products?**
Yes. History is tracked per variation. If the defer setting is enabled, the storefront and shortcode show placeholder text until a customer selects a variation.

**Does the plugin support WooCommerce Product Bundles?**
Yes, when bundled products are priced individually and WC Price History Pro 1.0.1 or later is active. See [Pricing Integrations and Product Bundles](pricing-integrations-and-bundles.md).

**Does the REST API expose history automatically?**
No. REST fields are off by default. Enable them under **WooCommerce -> Price History** only when an integration needs them.

**Does this replace legal advice?**
No. The plugin provides technical support for lowest-price display, but store owners remain responsible for legal compliance.

### 11. Changelog and Updates

For version history, see the WordPress.org `readme.txt` changelog or the plugin page in the WordPress repository.

When updating from a version before 3.0, review [Migration to Database Tables](migration-to-db-tables.md). The migration keeps legacy post meta for safety and can be switched back temporarily with `WC_PRICE_HISTORY_USE_POST_META`.

After any major update:
- Open **WooCommerce -> Price History** and check the **Status** panel.
- Review display settings.
- Test one simple product and one variable product if your store uses variations.
- Export debug data for any product whose history looks unexpected.

### 12. Support and Contact

Use these channels when you need help:

- [WordPress support forum](https://wordpress.org/support/plugin/wc-price-history/)
- [Official plugin website](https://wcpricehistory.com/)
- [GitHub issues](https://github.com/kkarpieszuk/wc-price-history/issues)

When reporting a product-specific issue, include:
- WordPress and WooCommerce versions
- WC Price History version
- Whether storage is **Database tables** or **Post meta (legacy)**
- Product type: simple, variable, or variation
- A debug export JSON file from the product edit screen
- Any `wc-price-history` WooCommerce log messages

### 13. Additional Resources

- [Display and Shortcode Guide](display-and-shortcode.md)
- [Maintenance and Troubleshooting](maintenance-and-troubleshooting.md)
- [Migration to Database Tables](migration-to-db-tables.md)
- [Pricing Integrations and Product Bundles](pricing-integrations-and-bundles.md)
- [European Commission guidance on price indication](https://eur-lex.europa.eu/legal-content/EN/TXT/?uri=CELEX:52021XC1229(06))
- [WooCommerce REST API documentation](https://woocommerce.github.io/woocommerce-rest-api-docs/)
