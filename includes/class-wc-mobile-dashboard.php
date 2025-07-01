<?php

class WC_Mobile_Dashboard {

    public function __construct() {
        add_action('admin_menu', [ $this, 'add_dashboard_menu' ]);
        add_action('admin_enqueue_scripts', [ $this, 'enqueue_assets' ]);
        add_action('admin_head', [ $this, 'add_viewport_meta' ]);
        add_action('wp_ajax_toggle_product_stock', [ $this, 'toggle_product_stock' ]);
        add_action('wp_ajax_load_products_by_category', [ $this, 'load_products_by_category' ]);
        add_action('wp_ajax_search_products_by_name', [ $this, 'search_products_by_name' ]);
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
        
        // הוספת Select2
        wp_enqueue_style('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css');
        wp_enqueue_script('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', ['jquery'], null, true);
        
        wp_enqueue_script('wc-mobile-dashboard-script', plugin_dir_url(__FILE__) . '../assets/script.js', ['jquery', 'select2'], null, true);
        
        // הוספת נתוני קטגוריות ל-localization
        $categories = get_terms(['taxonomy' => 'product_cat', 'hide_empty' => false]);
        $categories_data = [];
        foreach ($categories as $cat) {
            $categories_data[] = [
                'id' => $cat->term_id,
                'name' => $cat->name,
                'slug' => $cat->slug
            ];
        }
        
        wp_localize_script('wc-mobile-dashboard-script', 'wcMobileDashboard', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('toggle_stock'),
            'categories' => $categories_data
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

    public function search_products_by_name() {
        $search_term = sanitize_text_field($_POST['search_term']);
        $category_ids = isset($_POST['category_ids']) ? array_map('intval', $_POST['category_ids']) : [];
        
        if (empty($search_term) && empty($category_ids)) {
            wp_send_json_error('נדרש חיפוש או בחירת קטגוריה');
            return;
        }
        
        $args = [
            'limit' => 50,
            'status' => 'publish'
        ];
        
        // אם יש חיפוש טקסט
        if (!empty($search_term)) {
            $args['s'] = $search_term;
        }
        
        // אם יש קטגוריות נבחרות
        if (!empty($category_ids)) {
            $category_slugs = [];
            foreach ($category_ids as $cat_id) {
                $term = get_term($cat_id, 'product_cat');
                if ($term && !is_wp_error($term)) {
                    $category_slugs[] = $term->slug;
                }
            }
            if (!empty($category_slugs)) {
                $args['category'] = $category_slugs;
            }
        }
        
        $products = wc_get_products($args);
        
        if (empty($products)) {
            echo '<div class="no-products">לא נמצאו מוצרים</div>';
        } else {
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
        }
        
        wp_die();
    }

    public function add_viewport_meta() {
        $screen = get_current_screen();
        if ($screen && $screen->id === 'toplevel_page_wc-mobile-dashboard') {
            echo '<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">';
        }
    }
}
