<?php

namespace PriorPrice\FirstRun;

if (!defined('ABSPATH')) {
    exit;
}

class ProductScan {
    public function register_hooks() {
        add_action('wp_ajax_start_product_scan', [$this, 'handle_start_scan']);
        add_action('wp_ajax_nopriv_start_product_scan', [$this, 'handle_start_scan']);
        add_action('wp_ajax_process_product_batch', [$this, 'handle_process_batch']);
        add_action('wp_ajax_nopriv_process_product_batch', [$this, 'handle_process_batch']);
    }

    public function handle_start_scan() {
        check_ajax_referer('wc_price_history_scan_nonce', 'security');

        $settings = new SettingsData();
        $current_setting = $settings->get('first_history_save_done');

        if ($current_setting < 1) {
            $settings->update('first_history_save_done', 1);
            wp_send_json_success(['message' => 'Scan started']);
        } else {
            wp_send_json_error(['message' => 'Scan already in progress or completed']);
        }
    }

    public function handle_process_batch() {
        check_ajax_referer('wc_price_history_scan_nonce', 'security');

        $batch_size = 10;
        $products = $this->get_products_without_history($batch_size);

        if (empty($products)) {
            (new SettingsData())->update('first_history_save_done', 2);
            wp_send_json_success(['message' => 'Scan completed']);
            return;
        }

        $historyStorage = new HistoryStorage();
        foreach ($products as $product_id) {
            $product = wc_get_product($product_id);
            $historyStorage->add_historical_price($product_id, $product->get_price(), strtotime('yesterday'));
            $historyStorage->add_historical_price($product_id, $product->get_price(), strtotime($product->get_date_created()));
        }

        wp_send_json_success(['message' => 'Batch processed', 'remaining' => count($this->get_products_without_history())]);
    }

    private function get_products_without_history($limit = -1) {
        $args = [
            'post_type' => 'product',
            'posts_per_page' => $limit,
            'meta_query' => [
                [
                    'key' => HistoryStorage::cf_key,
                    'compare' => 'NOT EXISTS'
                ]
            ]
        ];

        $query = new \WP_Query($args);
        return wp_list_pluck($query->posts, 'ID');
    }
}
