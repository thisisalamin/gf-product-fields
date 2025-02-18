(function($) {
    'use strict';
    
    $(document).ready(function() {
        // Handle product field type changes
        $('#product_field_type').on('change', function() {
            var selectedType = $(this).val();
            if (typeof SetFieldProperty === 'function') {
                SetFieldProperty('inputType', selectedType);
            }
        });

        // Handle multiple select fields
        $('.gform_wrapper select[multiple]').on('change', function() {
            var total = 0;
            $(this).find('option:selected').each(function() {
                var value = parseFloat($(this).val());
                if (!isNaN(value)) {
                    total += value;
                }
            });
            // Update the form total if needed
            if (typeof gformCalculateTotalPrice === 'function') {
                gformCalculateTotalPrice();
            }
        });

        // Handle checkbox fields
        $('.gf-product-checkbox-group input[type="checkbox"]').on('change', function() {
            if (typeof gformCalculateTotalPrice === 'function') {
                gformCalculateTotalPrice();
            }
        });
    });
})(jQuery);
