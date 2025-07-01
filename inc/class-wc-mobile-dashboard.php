<?php
// templates/dashboard-wrapper.php
?>
<div class="mobile-dashboard">
    <div class="tab-content" id="tab-orders"><?php include plugin_dir_path(__FILE__) . 'tab-orders.php'; ?></div>
    <div class="tab-content hidden" id="tab-stock"><?php include plugin_dir_path(__FILE__) . 'tab-stock.php'; ?></div>

    <div class="bottom-nav">
        <button class="tab-switch" data-tab="tab-orders">הזמנות</button>
        <button class="tab-switch" data-tab="tab-stock">מלאים</button>
    </div>
</div>