<?php

class WC_Mobile_Dashboard {

    public function __construct() {
        add_action('admin_menu', [ $this, 'add_dashboard_menu' ]);
        add_action('admin_enqueue_scripts', [ $this, 'enqueue_assets' ]);
        add_action('wp_ajax_toggle_product_stock', [ $this, 'toggle_product_stock' ]);
        add_action('wp_ajax_load_products_by_category', [ $this, 'load_products_by_category' ]);
    }

    public function add_dashboard_menu() {
        add_menu_page(
            'ניהול מהיר',
            '📱 ניהול מהיר',
            'manage_woocommerce',
            'wc-mobile-dashboard',
            [ $this, 'render_dashboard_page' ],
            'dashicons-smartphone',
            3
        );
    }

    public function enqueue_assets() {
        wp_enqueue_style('wc-mobile-dashboard-style', plugin_dir_url(__FILE__) . '../assets/style.css');
        wp_enqueue_script('wc-mobile-dashboard-script', plugin_dir_url(__FILE__) . '../assets/script.js', ['jquery'], null, true);
        wp_localize_script('wc-mobile-dashboard-script', 'wcMobileDashboard', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('toggle_stock')
        ]);
    }

    public function render_dashboard_page() {
        include plugin_dir_path(__FILE__) . '../templates/dashboard-wrapper.php';
    }

    public function toggle_product_stock() {
        check_ajax_referer('toggle_stock', 'nonce');

        $product_id = intval($_POST['product_id']);
        $in_stock = $_POST['in_stock'] === 'true';

        if ($product_id) {
            $product = wc_get_product($product_id);
            if ($product) {
                $product->set_stock_status($in_stock ? 'instock' : 'outofstock');

                if ($product->is_type('variable')) {
                    foreach ($product->get_children() as $child_id) {
                        $child = wc_get_product($child_id);
                        if ($child) {
                            $child->set_stock_status($in_stock ? 'instock' : 'outofstock');
                            $child->save();
                        }
                    }
                }

                $product->save();
                wp_send_json_success();
            }
        }

        wp_send_json_error();
    }

    public function load_products_by_category() {
        $cat_id = intval($_POST['cat_id']);
        $products = wc_get_products([
            'category' => [ get_term($cat_id)->slug ],
            'limit' => -1
        ]);

        foreach ($products as $product) {
            echo '<div class="product-row">';
            echo '<img src="' . get_the_post_thumbnail_url($product->get_id(), 'thumbnail') . '" />';
            echo '<span>' . esc_html($product->get_name()) . '</span>';
            echo '<label class="switch">';
            echo '<input type="checkbox" class="stock-toggle" data-product-id="' . $product->get_id() . '" ' . checked($product->is_in_stock(), true, false) . ' />';
            echo '<span class="slider round"></span>';
            echo '</label>';
            echo '</div>';
        }

        wp_die();
    }
}
