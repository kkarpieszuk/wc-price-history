<?php

namespace PriorPrice\FirstRun;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class SettingsPage {
    public function register_hooks() {
        add_action('admin_menu', [$this, 'add_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
    }

    public function add_menu(): void {
        add_submenu_page(
            'woocommerce',
            __('First Run', 'wc-price-history'),
            __('First Run', 'wc-price-history'),
            'manage_woocommerce',
            'wc-price-history-first-run',
            [$this, 'render']
        );
    }

    public function render(): void {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('First Run Setup', 'wc-price-history'); ?></h1>
            <p><?php echo esc_html__('Click the button below to start scanning your products and initialize the price history.', 'wc-price-history'); ?></p>
            <progress id="scan-progress" value="0" max="100"></progress>
            <button id="start-scan" class="button button-primary"><?php echo esc_html__('Start Scanning', 'wc-price-history'); ?></button>
        </div>
        <?php
    }

    public function enqueue_scripts($hook): void {
        if ('woocommerce_page_wc-price-history-first-run' !== $hook) {
            return;
        }

        wp_enqueue_script('wc-price-history-first-run', plugin_dir_url(__FILE__) . 'js/first-run.js', ['jquery'], false, true);
        wp_localize_script('wc-price-history-first-run', 'wcPriceHistoryFirstRun', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wc_price_history_first_run_nonce'),
        ]);
    }
}
