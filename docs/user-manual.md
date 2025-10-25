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
- Support for product variations and sales

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

### 4. Displaying Lowest Price
- On product page
- On shop page
- On category and tag pages
- Using shortcode

### 5. Shortcode [wc_price_history]
- Basic usage
- Shortcode parameters
- Usage examples
- Implementation locations

### 6. Price History Management
- Viewing price history
- Cleaning history
- Fixing history
- Exporting debug data

### 7. Sale Handling
- Setting sale dates
- Counting from sale start
- Omnibus Directive compliance
- Troubleshooting

### 8. Appearance Customization
- CSS styles
- CSS classes
- Template modification
- Theme integration

### 9. Troubleshooting
- Common issues
- Checking logs
- Debugging
- Technical support

### 10. FAQ - Frequently Asked Questions
- Legal compliance questions
- Technical questions
- Configuration questions
- Performance questions

### 11. Changelog and Updates
- Version history
- Update process
- Backward compatibility
- Planned features

### 12. Support and Contact
- WordPress support forum
- Official plugin website
- Bug reporting
- Feature requests

### 13. Additional Resources
- EU legal documentation links
- WooCommerce guides
- Community support
- Educational materials
