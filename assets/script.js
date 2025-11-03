jQuery(function($){
    let selectedCategories = new Set();
    let searchTimeout;
    let currentSearchTerm = '';
    let orderSearchTimeout;
    let currentOrderSearchTerm = '';
    let currentDateFrom = '';
    let currentDateTo = '';
    let currentShippingDateFrom = '';
    let currentShippingDateTo = '';
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
        $('#category-select').on('change', function () {
            const selected = $(this).val() || [];
            selectedCategories.clear();
            selected.forEach(id => selectedCategories.add(parseInt(id)));

            updateButtonStates();
            performSearch();

            // הורדת פוקוס משדה החיפוש של Select2
            setTimeout(() => {
                $('.select2-search__field').blur();
            }, 0);
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
            shipping_date_from: currentShippingDateFrom,
            shipping_date_to: currentShippingDateTo,
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
    
    // פילטר תאריכי אספקה
    $('#shipping-date-from, #shipping-date-to').on('change', function() {
        currentShippingDateFrom = $('#shipping-date-from').val();
        currentShippingDateTo = $('#shipping-date-to').val();
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
    $(document).on('change', '.stock-toggle', function(e){
        e.stopPropagation(); // Prevent triggering row click
        const productId = $(this).data('product-id');
        const inStock = $(this).is(':checked');

        $.post(wcMobileDashboard.ajax_url, {
            action: 'toggle_product_stock',
            product_id: productId,
            in_stock: inStock,
            nonce: wcMobileDashboard.nonce
        });
    });

    // Price Edit Modal Functions
    let currentProductId = 0;
    let currentProductData = null;

    // פתיחת חלון עריכת מחיר
    $(document).on('click', '.product-row', function(e){
        // Prevent click if clicking on switch
        if ($(e.target).closest('.switch').length) {
            return;
        }
        
        currentProductId = $(this).data('product-id');
        openPriceModal(currentProductId);
    });

    // סגירת חלון
    $('.price-modal-close, .cancel-price-btn, .price-modal-overlay').on('click', function(e){
        if ($(e.target).hasClass('price-modal-overlay') || $(e.target).hasClass('price-modal-close') || $(e.target).hasClass('cancel-price-btn')) {
            closePriceModal();
        }
    });

    function openPriceModal(productId) {
        $('#price-edit-modal').removeClass('hidden');
        $('#price-edit-content').html('');
        $('#price-edit-loading').removeClass('hidden').show();
        $('#price-modal-title').text('טוען...');

        // טעינת נתוני מוצר
        $.post(wcMobileDashboard.ajax_url, {
            action: 'get_product_prices',
            product_id: productId,
            nonce: wcMobileDashboard.nonce
        }).then(function(response) {
            // הסתרת loading בהכרח
            $('#price-edit-loading').addClass('hidden').hide();
            
            if (response && response.success) {
                currentProductData = response.data;
                renderPriceEditForm(response.data);
            } else {
                $('#price-edit-content').html('<div class="error">שגיאה בטעינת נתוני המוצר</div>');
            }
        }).catch(function(error) {
            // הסתרת loading גם במקרה של שגיאה
            $('#price-edit-loading').addClass('hidden').hide();
            $('#price-edit-content').html('<div class="error">שגיאה בטעינת נתוני המוצר</div>');
            console.error('Error loading product prices:', error);
        });
    }

    function closePriceModal() {
        $('#price-edit-modal').addClass('hidden');
        $('#price-edit-loading').addClass('hidden').hide();
        $('#price-edit-content').html('');
        currentProductId = 0;
        currentProductData = null;
    }

    function renderPriceEditForm(data) {
        $('#price-modal-title').text('עריכת מחיר: ' + data.product_name);
        
        let html = '';
        
        if (data.product_type === 'variable' && data.variations.length > 0) {
            // מצב בחירה: וריאציות / גורף
            html += '<div class="price-edit-mode" style="margin-bottom:16px;">';
            html += '  <label><input type="radio" name="price-edit-mode" value="variation" checked> שינוי לפי וריאציה</label>';
            html += '  <label style="margin-inline-start:16px;"><input type="radio" name="price-edit-mode" value="bulk"> שינוי לכל הוריאציות</label>';
            html += '</div>';
            
            // קונטיינר גורף
            html += '<div id="bulk-price-container" class="hidden">';
            html += '  <div class="price-edit-field">';
            html += '    <label>מחיר רגיל (גורף):</label>';
            html += '    <input type="number" step="0.01" id="bulk-regular-price" placeholder="השאר ריק כדי לא לשנות">';
            html += '  </div>';
            html += '  <div class="price-edit-field">';
            html += '    <label>מחיר מבצע (גורף):</label>';
            html += '    <input type="number" step="0.01" id="bulk-sale-price" placeholder="השאר ריק כדי למחוק או לא לשנות">';
            html += '  </div>';
            html += '  <div style="font-size:12px;color:#666;">הערה: אם תשאיר שדה ריק, המחיר לא ישתנה (מחיר מבצע ריק ימחק מבצע קיים).</div>';
            html += '</div>';
            
            // קונטיינר לפי וריאציה
            html += '<div id="per-variation-container">';
            data.variations.forEach(function(variation) {
                html += '<div class="variation-price-item" data-variation-id="' + variation.variation_id + '">';
                html += '<h4>' + (variation.formatted_name || 'וריאציה #' + variation.variation_id) + '</h4>';
                html += '<div class="variation-price-fields">';
                html += '<div class="price-edit-field">';
                html += '<label>מחיר רגיל:</label>';
                html += '<input type="number" step="0.01" class="variation-regular-price" value="' + (variation.regular_price || '') + '" data-variation-id="' + variation.variation_id + '">';
                html += '</div>';
                html += '<div class="price-edit-field">';
                html += '<label>מחיר מבצע:</label>';
                html += '<input type="number" step="0.01" class="variation-sale-price" value="' + (variation.sale_price || '') + '" data-variation-id="' + variation.variation_id + '">';
                html += '</div>';
                html += '</div>';
                html += '</div>';
            });
            html += '</div>';
        } else {
            // מוצר רגיל
            html += '<div class="price-edit-field">';
            html += '<label>מחיר רגיל:</label>';
            html += '<input type="number" step="0.01" id="regular-price" value="' + (data.regular_price || '') + '">';
            html += '</div>';
            html += '<div class="price-edit-field">';
            html += '<label>מחיר מבצע:</label>';
            html += '<input type="number" step="0.01" id="sale-price" value="' + (data.sale_price || '') + '">';
            html += '</div>';
        }
        
        $('#price-edit-content').html(html);
        
        // האזנה למצב עריכה
        $('input[name="price-edit-mode"]').on('change', function(){
            const mode = $('input[name="price-edit-mode"]:checked').val();
            if (mode === 'bulk') {
                $('#bulk-price-container').removeClass('hidden');
                $('#per-variation-container').addClass('hidden');
            } else {
                $('#per-variation-container').removeClass('hidden');
                $('#bulk-price-container').addClass('hidden');
            }
        });
    }

    // שמירת מחירים
    $('#save-price-btn').on('click', function() {
        if (!currentProductData) return;

        const isVariable = currentProductData.product_type === 'variable';
        const mode = $('input[name="price-edit-mode"]:checked').val() || 'variation';
        const updates = [];

        if (isVariable && mode === 'bulk') {
            // עדכון גורף לכל הוריאציות
            const bulkRegular = $('#bulk-regular-price').val();
            const bulkSale = $('#bulk-sale-price').val();
            
            currentProductData.variations.forEach(function(variation){
                updates.push({
                    variation_id: variation.variation_id,
                    regular_price: bulkRegular,
                    sale_price: bulkSale
                });
            });

            let saveIndex = 0;
            function saveNextVariation() {
                if (saveIndex >= updates.length) {
                    alert('מחירים עודכנו בהצלחה!');
                    closePriceModal();
                    performSearch();
                    return;
                }
                const update = updates[saveIndex];
                $.post(wcMobileDashboard.ajax_url, {
                    action: 'update_product_price',
                    product_id: currentProductId,
                    variation_id: update.variation_id,
                    regular_price: update.regular_price,
                    sale_price: update.sale_price,
                    nonce: wcMobileDashboard.nonce
                }).then(function(response) {
                    if (response.success) {
                        saveIndex++;
                        saveNextVariation();
                    } else {
                        alert('שגיאה בעדכון מחיר: ' + (response.data || 'שגיאה לא ידועה'));
                    }
                }).catch(function() {
                    alert('שגיאה בעדכון מחיר');
                });
            }
            saveNextVariation();
            return;
        }

        if (isVariable) {
            // שמירה פר וריאציה
            $('.variation-price-item').each(function() {
                const variationId = $(this).data('variation-id');
                const regularPrice = $(this).find('.variation-regular-price').val();
                const salePrice = $(this).find('.variation-sale-price').val();
                
                updates.push({
                    variation_id: variationId,
                    regular_price: regularPrice,
                    sale_price: salePrice
                });
            });

            let saveIndex = 0;
            function saveNextVariation() {
                if (saveIndex >= updates.length) {
                    alert('מחירים עודכנו בהצלחה!');
                    closePriceModal();
                    performSearch();
                    return;
                }

                const update = updates[saveIndex];
                $.post(wcMobileDashboard.ajax_url, {
                    action: 'update_product_price',
                    product_id: currentProductId,
                    variation_id: update.variation_id,
                    regular_price: update.regular_price,
                    sale_price: update.sale_price,
                    nonce: wcMobileDashboard.nonce
                }).then(function(response) {
                    if (response.success) {
                        saveIndex++;
                        saveNextVariation();
                    } else {
                        alert('שגיאה בעדכון מחיר: ' + (response.data || 'שגיאה לא ידועה'));
                    }
                }).catch(function() {
                    alert('שגיאה בעדכון מחיר');
                });
            }

            saveNextVariation();
        } else {
            // שמירת מוצר רגיל
            const regularPrice = $('#regular-price').val();
            const salePrice = $('#sale-price').val();

            $.post(wcMobileDashboard.ajax_url, {
                action: 'update_product_price',
                product_id: currentProductId,
                regular_price: regularPrice,
                sale_price: salePrice,
                nonce: wcMobileDashboard.nonce
            }).then(function(response) {
                if (response.success) {
                    alert('מחיר עודכן בהצלחה!');
                    closePriceModal();
                    performSearch();
                } else {
                    alert('שגיאה בעדכון מחיר: ' + (response.data || 'שגיאה לא ידועה'));
                }
            }).catch(function() {
                alert('שגיאה בעדכון מחיר');
            });
        }
    });
});
