(function($) {
    'use strict';

    // Handle multiselect changes
    $(document).on('change', '.gf-product-multiselect', function() {
        var $select = $(this);
        var $quantities = $select.closest('.ginput_container').find('.gf-quantity-field');
        
        // Show/hide quantity fields based on selection
        $quantities.each(function() {
            var $qty = $(this);
            var productValue = $qty.data('product');
            var isSelected = $select.find('option[value="' + productValue + '"]').is(':selected');
            
            $qty.toggle(isSelected);
            if (!isSelected) {
                $qty.find('input').val(0);
            }
        });
        
        updateTotalPrice();
    });

    // Handle checkbox changes
    $(document).on('change', '.ginput_container_product_checkbox input[type="checkbox"]', function() {
        var $checkbox = $(this);
        var $qtyField = $checkbox.closest('.gf-product-checkbox-item').find('.gf-quantity-field');
        
        $qtyField.toggle($checkbox.is(':checked'));
        if (!$checkbox.is(':checked')) {
            $qtyField.find('input').val(0);
        }
        
        updateTotalPrice();
    });

    // Handle quantity changes
    $(document).on('change', '.gf-product-quantity', function() {
        updateTotalPrice();
    });

    function updateTotalPrice() {
        var total = 0;
        
        // Calculate total for multiselect fields
        $('.gf-product-multiselect').each(function() {
            var $select = $(this);
            $select.find('option:selected').each(function() {
                var price = parseFloat($(this).data('price')) || 0;
                var qtyField = $select.closest('.ginput_container').find('.gf-quantity-field[data-product="' + $(this).val() + '"] input');
                var qty = parseInt(qtyField.val()) || 1;
                total += price * qty;
            });
        });

        // Calculate total for checkbox fields
        $('.ginput_container_product_checkbox').each(function() {
            $(this).find('input[type="checkbox"]:checked').each(function() {
                var price = parseFloat($(this).data('price')) || 0;
                var qty = parseInt($(this).closest('.gf-product-checkbox-item').find('.gf-product-quantity').val()) || 1;
                total += price * qty;
            });
        });

        // Update Gravity Forms total field
        if (window.gform) {
            // Find all total fields and update them
            $('.ginput_total').each(function() {
                $(this).val(gformFormatMoney(total));
            });
            
            // Trigger Gravity Forms calculations
            gform.total = total;
            gform.callTrigger('gform_product_total_changed');
        }
    }

    // Initialize totals on page load
    $(document).ready(function() {
        updateTotalPrice();
    });

})(jQuery);