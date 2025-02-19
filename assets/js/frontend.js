(function($) {
    'use strict';

    // Handle multiselect changes
    $(document).on('change', '.gf-product-multiselect', function() {
        var $select = $(this);
        var $quantities = $select.closest('.ginput_container').find('.gf-quantity-field');
        
        $quantities.each(function() {
            var $qty = $(this);
            var productValue = $qty.data('product');
            var isSelected = $select.find('option[value="' + productValue + '"]').is(':selected');
            
            $qty.toggle(isSelected);
            if (!isSelected) {
                $qty.find('input').val(1);
            }
        });
        
        updateTotalPrice();
    });

    // Handle checkbox changes
    $(document).on('change', '.ginput_container_product_checkbox input[type="checkbox"]', function() {
        var $checkbox = $(this);
        var $qtyField = $checkbox.closest('tr').find('.gf-product-quantity');
        
        $qtyField.prop('disabled', !$checkbox.is(':checked'));
        
        if (!$checkbox.is(':checked')) {
            $qtyField.val(1);
        }
        
        updateTotalPrice();
    });

    // Handle quantity changes
    $(document).on('change', '.gf-product-quantity', function() {
        var $qty = $(this);
        var value = parseInt($qty.val()) || 1;
        
        if (value < 1) {
            $qty.val(1);
        }
        
        updateTotalPrice();
    });

    function updateTotalPrice() {
        var total = 0;
        var formId = $('.gform_wrapper').attr('id').split('_')[2];
        
        // Calculate total for multiselect fields
        $('.gf-product-multiselect').each(function() {
            var $select = $(this);
            var fieldId = $select.closest('.gfield').attr('id').split('_')[2];
            var hiddenInputId = 'input_' + formId + '_' + fieldId;
            
            if ($('#' + hiddenInputId).length === 0) {
                $select.after('<input type="hidden" name="input_' + fieldId + '" id="' + hiddenInputId + '" />');
            }

            var selectedProducts = [];
            $select.find('option:selected').each(function() {
                var price = parseFloat($(this).data('price')) || 0;
                var qtyField = $select.closest('.ginput_container').find('.gf-quantity-field[data-product="' + $(this).val() + '"] input');
                var qty = parseInt(qtyField.val()) || 1;
                total += price * qty;
                selectedProducts.push($(this).val());
            });

            $('#' + hiddenInputId).val(selectedProducts.join(','));
        });

        // Calculate total for checkbox fields
        $('.ginput_container_product_checkbox').each(function() {
            var $container = $(this);
            var fieldId = $container.closest('.gfield').attr('id').split('_')[2];
            var hiddenInputId = 'input_' + formId + '_' + fieldId + '_total';
            
            if ($('#' + hiddenInputId).length === 0) {
                $container.append('<input type="hidden" name="input_' + fieldId + '_total" id="' + hiddenInputId + '" />');
            }

            var selectedProducts = [];
            $container.find('input[type="checkbox"]:checked').each(function() {
                var price = parseFloat($(this).data('price')) || 0;
                var qty = parseInt($(this).closest('tr').find('.gf-product-quantity').val()) || 1;
                total += price * qty;
                selectedProducts.push($(this).val());
            });

            $('#' + hiddenInputId).val(total);
        });

        // Update Gravity Forms total field
        if (window.gform) {
            $('.ginput_total').each(function() {
                $(this).val(gformFormatMoney(total));
            });
            
            $('.ginput_total_hidden').val(total);
            
            if (window.gformCalculateTotalPrice) {
                window.gformCalculateTotalPrice(formId);
            }
            
            gform.total = total;
            jQuery(document).trigger('gform_product_total_changed', [total]);
        }
    }

    // Initialize on page load
    $(document).ready(function() {
        $('.ginput_container_product_checkbox input[type="checkbox"]').each(function() {
            var $checkbox = $(this);
            var $qtyField = $checkbox.closest('tr').find('.gf-product-quantity');
            $qtyField.prop('disabled', !$checkbox.is(':checked'));
        });
        
        $('.gf-product-multiselect').each(function() {
            var $select = $(this);
            var $quantities = $select.closest('.ginput_container').find('.gf-quantity-field');
            
            $quantities.each(function() {
                var $qty = $(this);
                var productValue = $qty.data('product');
                var isSelected = $select.find('option[value="' + productValue + '"]').is(':selected');
                $qty.toggle(isSelected);
            });
        });
        
        updateTotalPrice();
    });

})(jQuery);