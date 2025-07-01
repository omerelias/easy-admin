jQuery(function($){
    let selectedCategories = new Set();
    let searchTimeout;
    let currentSearchTerm = '';
    let orderSearchTimeout;
    let currentOrderSearchTerm = '';
    let currentDateFrom = '';
    let currentDateTo = '';
    let orderOffset = 10;
    
    // אתחול Select2
    function initSelect2() {
        $('#category-select').select2({
            placeholder: 'בחר או חפש קטגוריות...',
            allowClear: true,
            dir: 'rtl',
            data: wcMobileDashboard.categories.map(cat => ({
                id: cat.id,
                text: cat.name
            }))
        });
        
        // כשבוחרים בselect
        $('#category-select').on('change', function() {
            const selected = $(this).val() || [];
            selectedCategories.clear();
            selected.forEach(id => selectedCategories.add(parseInt(id)));
            
            updateButtonStates();
            performSearch();
        });
    }
    
    // עדכון מצב הכפתורים
    function updateButtonStates() {
        $('.load-category').removeClass('active');
        selectedCategories.forEach(catId => {
            $('.load-category[data-cat="' + catId + '"]').addClass('active');
        });
    }
    
    // הצגת/הסתרת loader
    function showLoader() {
        $('#products-loader').removeClass('hidden');
        $('#products-container').addClass('loading');
    }
    
    function hideLoader() {
        $('#products-loader').addClass('hidden');
        $('#products-container').removeClass('loading');
    }
    
    // ביצוע חיפוש מוצרים
    function performSearch() {
        if (currentSearchTerm.trim() === '' && selectedCategories.size === 0) {
            $('#products-container').html('<div class="no-selection">הקלד שם מוצר או בחר קטגוריות להצגת מוצרים</div>');
            return;
        }
        
        showLoader();
        
        $.post(wcMobileDashboard.ajax_url, {
            action: 'search_products_by_name',
            search_term: currentSearchTerm,
            category_ids: Array.from(selectedCategories)
        }).then(function(response) {
            $('#products-container').html(response);
            hideLoader();
        }).catch(function() {
            $('#products-container').html('<div class="error">שגיאה בחיפוש המוצרים</div>');
            hideLoader();
        });
    }
    
    // חיפוש מוצרים עם debounce
    function debouncedSearch() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(function() {
            performSearch();
        }, 500);
    }
    
    // ביצוע חיפוש הזמנות
    function performOrderSearch() {
        showOrderLoader();
        $.post(wcMobileDashboard.ajax_url, {
            action: 'search_orders',
            search_term: currentOrderSearchTerm,
            date_from: currentDateFrom,
            date_to: currentDateTo,
            offset: 0
        }).then(function(response) {
            $('#orders-container').html(response);
            hideOrderLoader();
            orderOffset = 10;
        }).catch(function() {
            $('#orders-container').html('<div class="error">שגיאה בחיפוש ההזמנות</div>');
            hideOrderLoader();
        });
    }
    
    // חיפוש הזמנות עם debounce
    function debouncedOrderSearch() {
        clearTimeout(orderSearchTimeout);
        orderSearchTimeout = setTimeout(function() {
            performOrderSearch();
        }, 500);
    }
    
    // הצגת/הסתרת loader הזמנות
    function showOrderLoader() {
        $('#orders-container').addClass('loading');
    }
    
    function hideOrderLoader() {
        $('#orders-container').removeClass('loading');
    }
    
    // טעינת הזמנות נוספות
    function loadMoreOrders() {
        showOrderLoader();
        
        $.post(wcMobileDashboard.ajax_url, {
            action: 'load_more_orders',
            offset: orderOffset
        }).then(function(response) {
            if (response.includes('no-more-orders')) {
                $('#load-more-orders').hide();
            } else {
                $('#orders-container').append(response);
                orderOffset += 10;
            }
            hideOrderLoader();
        }).catch(function() {
            hideOrderLoader();
        });
    }
    
    // מעבר בין טאבים
    $('.tab-switch').on('click', function(){
        $('.tab-content').addClass('hidden');
        const targetTab = $(this).data('tab');
        $('#' + targetTab).removeClass('hidden');
        
        // אתחול Select2 כשנכנסים לטאב המלאים
        if (targetTab === 'tab-stock' && !$('#category-select').hasClass('select2-hidden-accessible')) {
            setTimeout(initSelect2, 100);
        }
    });

    // פתיחה/סגירה של פרטי הזמנה
    $(document).on('click', '.toggle-order-details', function(){
        const id = $(this).data('order-id');
        $('#order-' + id).toggleClass('hidden');
    });

    // חיפוש מוצרים
    $('#product-search').on('input', function() {
        currentSearchTerm = $(this).val();
        debouncedSearch();
    });
    
    // כפתור נקה חיפוש מוצרים
    $('#clear-search').on('click', function() {
        $('#product-search').val('');
        currentSearchTerm = '';
        performSearch();
    });
    
    // חיפוש הזמנות
    $('#order-search').on('input', function() {
        currentOrderSearchTerm = $(this).val();
        debouncedOrderSearch();
    });
    
    // כפתור נקה חיפוש הזמנות
    $('#clear-order-search').on('click', function() {
        $('#order-search').val('');
        currentOrderSearchTerm = '';
        performOrderSearch();
    });
    
    // פילטר תאריכים הזמנות
    $('#date-from, #date-to').on('change', function() {
        currentDateFrom = $('#date-from').val();
        currentDateTo = $('#date-to').val();
        debouncedOrderSearch();
    });
    
    // כפתור טעינה נוספת
    $('#load-more-orders').on('click', function() {
        loadMoreOrders();
    });

    // לחיצה על כפתור קטגוריה
    $(document).on('click', '.load-category', function(){
        const catId = parseInt($(this).data('cat'));
        
        if (selectedCategories.has(catId)) {
            selectedCategories.delete(catId);
        } else {
            selectedCategories.add(catId);
        }
        
        // עדכון ה-select
        $('#category-select').val(Array.from(selectedCategories)).trigger('change');
    });

    // החלפת מצב מלאי
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
