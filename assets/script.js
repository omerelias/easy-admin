jQuery(function($){
    let selectedCategories = new Set();
    let searchTimeout;
    let currentSearchTerm = '';
    
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
    
    // כפתור נקה חיפוש
    $('#clear-search').on('click', function() {
        $('#product-search').val('');
        currentSearchTerm = '';
        performSearch();
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
