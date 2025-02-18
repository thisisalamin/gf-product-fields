(function($) {
    'use strict';

    // Initialize fields on page load
    $(document).ready(function() {
        initializeProductFields();
        
        // Handle Multi-select product changes
        $(document).on('change', '.gfield_multiselect_product select', function() {
            calculateMultiSelectTotal($(this));
        });

        // Handle Checkbox product changes
        $(document).on('change', '.gfield_checkbox_product input[type="checkbox"]', function() {
            calculateCheckboxTotal($(this).closest('.gfield'));
        });

        // Handle quantity changes
        $(document).on('change', '.product-quantity input', function() {
            const fieldType = $(this).closest('.gfield').data('field-type');
            if (fieldType === 'multiselect_product') {
                calculateMultiSelectTotal($(this).closest('.gfield').find('select'));
            } else {
                calculateCheckboxTotal($(this).closest('.gfield'));
            }
        });
    });

    function initializeProductFields() {
        // Handle multi-select products
        $('select[multiple]').each(function() {
            initializeField($(this));
        });

        // Handle checkbox products
        $('.gfield_checkbox input[type="checkbox"]').on('change', function() {
            var $field = $(this).closest('.gfield');
            updateQuantityFields($field);
            calculateTotal($field);
        });
    }

    // Handle product selection changes
    $(document).on('change', '.gf-enhanced-product select', function() {
        initializeField($(this));
    });

    function initializeField($select) {
        var $field = $select.closest('.gfield');
        var selectedProducts = $select.val() || [];
        var $quantityContainer = $field.find('.gf-quantity-container');
        
        // Clear existing quantity fields
        $quantityContainer.empty();
        
        // Add quantity fields for selected products
        selectedProducts.forEach(function(productName) {
            var price = gfProductPrices[productName] || 0;
            $quantityContainer.append(
                '<div class="ginput_container ginput_container_number">' +
                '<label for="quantity_' + productName + '">' + productName + ' Quantity:</label>' +
                '<input type="number" min="1" value="1" ' +
                'name="quantity_' + productName + '" ' +
                'class="gf-product-quantity" ' +
                'data-product="' + productName + '" ' +
                'data-price="' + price + '">' +
                '</div>'
            );
        });

        calculateTotal($field);
    }

    // Handle quantity changes
    $(document).on('change input', '.gf-product-quantity', function() {
        calculateTotal($(this).closest('.gfield'));
    });

    function calculateTotal($field) {
        var total = 0;
        $field.find('.gf-product-quantity').each(function() {
            var price = parseFloat($(this).data('price')) || 0;
            var quantity = parseInt($(this).val()) || 1;
            total += price * quantity;
        });
        
        $field.find('.gf-total-input').val(total.toFixed(2))
            .trigger('change');
            
        // Update visible price if exists
        $field.find('.ginput_amount').val(total.toFixed(2));
    }

    function calculateMultiSelectTotal(selectElement) {
        let total = 0;
        const selected = $(selectElement).val() || [];
        const field = $(selectElement).closest('.gfield');
        
        selected.forEach(function(optionValue) {
            const price = parseFloat(optionValue.split('|')[1]);
            const quantity = field.find('.product-quantity-' + optionValue.split('|')[0]).val() || 1;
            total += price * quantity;
        });

        field.find('.product-total').text(formatPrice(total));
        updateFormTotal();
    }

    function calculateCheckboxTotal(field) {
        let total = 0;
        field.find('input[type="checkbox"]:checked').each(function() {
            const price = parseFloat($(this).val().split('|')[1]);
            const quantity = field.find('.product-quantity-' + $(this).val().split('|')[0]).val() || 1;
            total += price * quantity;
        });

        field.find('.product-total').text(formatPrice(total));
        updateFormTotal();
    }

    function formatPrice(price) {
        return '$' + price.toFixed(2);
    }

    function updateFormTotal() {
        // Implement form total calculation logic here
    }

    // Add handler for product type changes in admin
    $(document).on('gform_load_field_settings', function(event, field, form) {
        if (field.type !== 'product') return;
        
        var $typeSelect = $('#enhanced_product_field_type');
        if ($typeSelect.length) {
            $typeSelect.off('change').on('change', function() {
                var selectedType = $(this).val();
                updateFieldPreview(selectedType, field);
            });
        }
    });

    function updateFieldPreview(type, field) {
        var $preview = $('#field_' + field.id);
        
        if (type === 'multiselect') {
            $preview.find('select').prop('multiple', true);
        } else if (type === 'checkbox') {
            // Convert select to checkboxes if needed
            if ($preview.find('select').length) {
                convertSelectToCheckboxes($preview, field);
            }
        }
    }

    function convertSelectToCheckboxes($preview, field) {
        var $container = $('<div class="gfield_checkbox"></div>');
        field.choices.forEach(function(choice) {
            $container.append(
                '<div class="gchoice">' +
                '<input type="checkbox" value="' + choice.value + '">' +
                '<label>' + choice.text + ' ($' + choice.price + ')</label>' +
                '</div>'
            );
        });
        $preview.find('select').replaceWith($container);
    }
})(jQuery);
