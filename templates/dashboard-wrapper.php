<?php
// templates/dashboard-wrapper.php
?>
<div class="mobile-dashboard">
    <div class="tab-content" id="tab-orders">
        <!-- חיפוש הזמנות -->
        <div class="order-search-container">
            <label for="order-search">חיפוש הזמנות:</label>
            <input type="text" id="order-search" placeholder="חיפוש לפי שם לקוח או מספר הזמנה..." />
            <button id="clear-order-search" class="clear-search-btn">🗑️ נקה</button>
        </div>
        
        <!-- פילטר תאריכים -->
        <div class="date-filter-container">
            <label for="date-from">טווח תאריכים:</label>
            <div class="date-inputs">
                <input type="date" id="date-from" placeholder="מתאריך" />
                <span class="date-separator">עד</span>
                <input type="date" id="date-to" placeholder="עד תאריך" />
            </div>
        </div>
        
        <!-- כפתור טעינה נוספת -->
        <div class="load-more-container">
            <button id="load-more-orders" class="load-more-btn">טען עוד הזמנות</button>
        </div>
        
        <!-- מכולת הזמנות -->
        <div id="orders-container">
            <?php
            $orders = wc_get_orders([
                'limit' => 10,
                'orderby' => 'date',
                'order' => 'DESC'
            ]);

            foreach ($orders as $order) {
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
            ?>
        </div>
    </div>

    <div class="tab-content hidden" id="tab-stock">
        <!-- Loader -->
<!--        <div id="products-loader" class="loader hidden">-->
<!--            <div class="spinner"></div>-->
<!--            <span>טוען מוצרים...</span>-->
<!--        </div>-->



        <!-- Select2 לחיפוש קטגוריות -->
        <div class="category-search-container">
            <label for="category-select">חיפוש קטגוריה:</label>
            <select id="category-select" multiple="multiple" style="width: 100%;" placeholder="בחר או חפש קטגוריות...">
                <!-- האופציות יתווספו ב-JavaScript -->
            </select>
        </div>

        <!-- חיפוש מוצרים -->
        <div class="product-search-container">
            <label for="product-search">חיפוש מוצר:</label>
            <input type="text" id="product-search" placeholder="הקלד שם מוצר לחיפוש..." />
            <button id="clear-search" class="clear-search-btn">🗑️ נקה</button>
        </div>

        
        <!-- כפתורי קטגוריות מהירים -->
        <?php
        $terms = get_terms(['taxonomy' => 'product_cat', 'hide_empty' => false]);
        echo '<div class="categories">';
        foreach ($terms as $term) {
            echo '<button class="load-category" data-cat="' . $term->term_id . '">' . esc_html($term->name) . '</button>';
        }
        echo '</div>';
        echo '<div id="products-container"></div>';
        ?>
    </div>

    <div class="bottom-nav">
        <button class="tab-switch" data-tab="tab-orders">הזמנות</button>
        <button class="tab-switch" data-tab="tab-stock">מלאים</button>
    </div>
</div>

