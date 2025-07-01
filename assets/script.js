    jQuery(function($){
    $('.tab-switch').on('click', function(){
        $('.tab-content').addClass('hidden');
        $('#' + $(this).data('tab')).removeClass('hidden');
    });

    $(document).on('click', '.toggle-order-details', function(){
    const id = $(this).data('order-id');
    $('#order-' + id).toggleClass('hidden');
});

    $(document).on('click', '.load-category', function(){
    alert(11);
    const catId = $(this).data('cat');
    $.post(wcMobileDashboard.ajax_url, {
    action: 'load_products_by_category',
    cat_id: catId
}, function(response){
    $('#products-container').html(response);
});
});

    $(document).on('change', '.stock-toggle', function(){
    const productId = $(this).data('product-id');
    const inStock = $(this).is(':checked');

    $.post(wcMobileDashboard.ajax_url, {
    action: 'toggle_product_stock',
    product_id: productId,
    in_stock: inStock,
    nonce: wcMobileDashboard.nonce
});
});
});
