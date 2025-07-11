<?php

class WC_Mobile_Dashboard {

    public function __construct() {
        add_action('admin_menu', [ $this, 'add_dashboard_menu' ]);
        add_action('admin_enqueue_scripts', [ $this, 'enqueue_assets' ]);
        add_action('admin_head', [ $this, 'add_viewport_meta' ]);
        add_action('wp_ajax_toggle_product_stock', [ $this, 'toggle_product_stock' ]);
        add_action('wp_ajax_load_products_by_category', [ $this, 'load_products_by_category' ]);
        add_action('wp_ajax_search_products_by_name', [ $this, 'search_products_by_name' ]);
        add_action('wp_ajax_search_orders', [ $this, 'search_orders' ]);
        add_action('wp_ajax_load_more_orders', [ $this, 'load_more_orders' ]);
        add_action('admin_init', [ $this, 'add_role_redirect' ]);
        add_action('admin_head', function () {
            echo '<style>
        @media (max-width: 768px) {
            #wpadminbar,
            .notice {
                display: none !important;
            }
        }
    </style>';
        });

    }

    public function add_role_redirect() {
        if (
            is_admin() &&
            !defined('DOING_AJAX')
        ) {
            // הגדרת התפקידים המותרים
            $allowed_roles = ['shop_manager'];

            // בדיקה אם לאחד מהתפקידים האלו יש למשתמש הנוכחי
            $user = wp_get_current_user();
            $has_allowed_role = array_intersect($allowed_roles, (array) $user->roles);

            if (!empty($has_allowed_role)) {
                $current_url = $_SERVER['REQUEST_URI'];

                if (strpos($current_url, '/wp-admin') !== false) {
                    if (
                        !isset($_GET['page']) ||
                        (isset($_GET['page']) && $_GET['page'] !== 'wc-mobile-dashboard')
                    ) {
                        wp_safe_redirect(admin_url('admin.php?page=wc-mobile-dashboard'));
                        exit;
                    }
                }
            }
        }
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

    public function search_orders() {
        $search_term = sanitize_text_field($_POST['search_term'] ?? '');
        $date_from = sanitize_text_field($_POST['date_from'] ?? '');
        $date_to = sanitize_text_field($_POST['date_to'] ?? '');
        $shipping_date_from = sanitize_text_field($_POST['shipping_date_from'] ?? '');
        $shipping_date_to = sanitize_text_field($_POST['shipping_date_to'] ?? '');
        $offset = intval($_POST['offset'] ?? 0);
        $limit = 10;

        // תנאים לפי מטא
        $meta_query = [];

        // חיפוש לפי שם פרטי/משפחה
        if (!empty($search_term)) {
            $meta_query[] = [
                'relation' => 'OR',
                [
                    'key'     => '_billing_first_name',
                    'value'   => $search_term,
                    'compare' => 'LIKE'
                ],
                [
                    'key'     => '_billing_last_name',
                    'value'   => $search_term,
                    'compare' => 'LIKE'
                ]
            ];
        }

        // פילטור לפי תאריך יצירת ההזמנה
        $date_query = [];
        if (!empty($date_from) || !empty($date_to)) {
            $date_query['inclusive'] = true;

            if (!empty($date_from)) {
                $date_query['after'] = $date_from . ' 00:00:00';
            }

            if (!empty($date_to)) {
                $date_query['before'] = $date_to . ' 23:59:59';
            }
        }

        // בניית השאילתה
        $args = [
            'post_type'      => 'shop_order',
            'post_status'    => ['wc-pending', 'wc-processing', 'wc-on-hold', 'wc-completed', 'wc-cancelled', 'wc-refunded'],
            'posts_per_page' => $limit * 5, // ניקח יותר כדי לסנן ידנית
            'offset'         => 0,
            'orderby'        => 'date',
            'order'          => 'DESC',
        ];

        if (!empty($meta_query)) {
            if (count($meta_query) > 1) {
                $args['meta_query'] = array_merge(['relation' => 'AND'], $meta_query);
            } else {
                $args['meta_query'] = $meta_query;
            }
        }

        if (!empty($date_query)) {
            $args['date_query'] = [$date_query];
        }

        // לוגים
        error_log('--- WP_Query Order Search Params ---');
        error_log(print_r($args, true));

        $query = new WP_Query($args);
        $orders = [];

        if ($query->have_posts()) {
            foreach ($query->posts as $post) {
                $order_id = $post->ID;
                $order = wc_get_order($order_id);

                // קבלת תאריך אספקה
                $shipping_date_raw = get_post_meta($order_id, 'ocws_shipping_info_date', true);
                $shipping_date = DateTime::createFromFormat('d/m/Y', $shipping_date_raw);

                // סינון ידני לפי תאריך אספקה
                $include = true;

                if ($shipping_date) {
                    if (!empty($shipping_date_from)) {
                        $from = DateTime::createFromFormat('Y-m-d', $shipping_date_from);
                        if ($from && $shipping_date < $from) {
                            $include = false;
                        }
                    }

                    if (!empty($shipping_date_to)) {
                        $to = DateTime::createFromFormat('Y-m-d', $shipping_date_to);
                        if ($to && $shipping_date > $to) {
                            $include = false;
                        }
                    }
                } elseif (!empty($shipping_date_from) || !empty($shipping_date_to)) {
                    // אם לא הצלחנו לפענח תאריך והמשתמש כן ביקש סינון – נפסול
                    $include = false;
                }

                if ($include) {
                    $orders[] = $order;
                    error_log("Order #{$order->get_id()} – {$order->get_billing_first_name()} {$order->get_billing_last_name()} – אספקה: $shipping_date_raw");
                }
            }
        }

        error_log('--- Orders Returned After Shipping Date Filter: ' . count($orders) . ' ---');

        // הצגת תוצאות
        if (empty($orders)) {
            echo '<div class="no-orders">לא נמצאו הזמנות</div>';
        } else {
            $orders = array_slice($orders, $offset, $limit); // הגבלת תוצאות לאחר סינון
            foreach ($orders as $order) {
                $this->render_order_item($order);
            }
        }

        wp_die();
    }
    
    public function load_more_orders() {
        $offset = intval($_POST['offset'] ?? 0);
        $limit = 10;
        
        $args = [
            'limit' => $limit,
            'offset' => $offset,
            'orderby' => 'date',
            'order' => 'DESC',
            'status' => ['wc-pending', 'wc-processing', 'wc-on-hold', 'wc-completed', 'wc-cancelled', 'wc-refunded']
        ];
        
        $orders = wc_get_orders($args);
        
        if (empty($orders)) {
            echo '<div class="no-more-orders">אין עוד הזמנות לטעינה</div>';
        } else {
            foreach ($orders as $order) {
                $this->render_order_item($order);
            }
        }
        
        wp_die();
    }
    
    private function render_order_item($order) {
        $status = $order->get_status();
        $status_class = 'order-' . $status;
        $shipping_date = get_post_meta($order->get_id(), 'ocws_shipping_info_date', true);
        
        echo '<div class="order-summary ' . $status_class . '">';
        echo '<div class="order-header">';
        echo '<strong>#' . $order->get_id() . '</strong>';
        echo '<span class="order-date">' . $order->get_date_created()->format('d/m/Y') . '</span>';
        echo '<span class="order-status">' . wc_get_order_status_name($status) . '</span>';
        echo '</div>';
        echo '<div class="order-customer">' . $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() . '</div>';
        echo '<div class="order-total">' . wc_price($order->get_total()) . '</div>';
        if ($shipping_date) {
            echo '<div class="shipping-date">תאריך אספקה: ' . $shipping_date . '</div>';
        }
        echo '<button class="toggle-order-details" data-order-id="' . $order->get_id() . '">👁️ פרטים</button>';
        echo '<div class="order-details hidden" id="order-' . $order->get_id() . '">';
        foreach ($order->get_items() as $item) {
            echo '<div>' . $item->get_name() . ' x' . $item->get_quantity() . '</div>';
        }
        echo '</div>';
        echo '</div>';
    }

    public function add_viewport_meta() {
        $screen = get_current_screen();
        if ($screen && $screen->id === 'toplevel_page_wc-mobile-dashboard') {
            echo '<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">';
        }
    }

}
