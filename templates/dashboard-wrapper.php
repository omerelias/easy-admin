<?php
// templates/dashboard-wrapper.php
?>
<div class="mobile-dashboard">
    <div class="tab-content" id="tab-orders">
        <?php
        $orders = wc_get_orders([
            'limit' => 10,
            'orderby' => 'date',
            'order' => 'DESC'
        ]);

        foreach ($orders as $order) {
            $status = $order->get_status();
            $status_class = 'order-' . $status;
            
            echo '<div class="order-summary ' . $status_class . '">';
            echo '<strong>#' . $order->get_id() . '</strong> | ' . wc_price($order->get_total());
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

