(function($) {
    'use strict';

    class GFProductFields {
        constructor() {
            this.init();
        }

        init() {
            this.bindEvents();
            this.initializeFields();
        }

        bindEvents() {
            $(document).on('change', '.gf-product-select, .gf-product-checkbox', this.handleProductChange.bind(this));
            $(document).on('change', '.gf-product-quantity', this.handleQuantityChange.bind(this));
            $(document).on('gform_post_render', this.initializeFields.bind(this));
        }

        initializeFields() {
            $('.gf-product-field').each((i, el) => {
                const $field = $(el);
                const fieldType = $field.hasClass('gf-product-multiselect') ? 'multiselect' : 'checkbox';
                
                // Initialize quantity fields if needed
                if (fieldType === 'multiselect' && $field.find('.gf-quantity-wrapper').length) {
                    this.updateMultiSelectQuantities($field);
                }
                
                this.calculateTotal($field.closest('form'));
            });
        }

        handleProductChange(e) {
            const $field = $(e.target);
            const $wrapper = $field.closest('.gf-product-field');
            const $form = $wrapper.closest('form');

            if ($wrapper.hasClass('gf-product-multiselect')) {
                this.updateMultiSelectQuantities($wrapper);
            }

            this.calculateTotal($form);
        }

        handleQuantityChange(e) {
            const $form = $(e.target).closest('form');
            this.calculateTotal($form);
        }

        updateMultiSelectQuantities($wrapper) {
            const $select = $wrapper.find('.gf-product-select');
            const $quantityWrapper = $wrapper.find('.gf-quantity-wrapper');
            
            $quantityWrapper.empty();
            
            $select.find('option:selected').each((i, option) => {
                const value = $(option).val();
                const text = $(option).text();
                const price = $(option).data('price');
                
                $quantityWrapper.append(this.createQuantityField(value, text, price));
            });
        }

        createQuantityField(value, text, price) {
            return `
                <div class="gf-quantity-item">
                    <label>${text}</label>
                    <input type="number" 
                           class="gf-product-quantity" 
                           data-product="${value}"
                           data-price="${price}"
                           min="1" 
                           value="1">
                </div>
            `;
        }

        calculateTotal($form) {
            let total = 0;

            $form.find('.gf-product-field').each((i, field) => {
                const $field = $(field);
                
                if ($field.find('.gf-product-select').length) {
                    total += this.calculateMultiSelectTotal($field);
                } else if ($field.find('.gf-product-checkbox').length) {
                    total += this.calculateCheckboxTotal($field);
                }
            });

            // Update Gravity Forms total
            if (window.gformCalculateTotalPrice) {
                window.gformCalculateTotalPrice($form.attr('id'));
            }
        }

        calculateMultiSelectTotal($field) {
            let total = 0;
            $field.find('.gf-product-quantity').each((i, input) => {
                const $input = $(input);
                const price = parseFloat($input.data('price')) || 0;
                const quantity = parseInt($input.val()) || 0;
                total += price * quantity;
            });
            return total;
        }

        calculateCheckboxTotal($field) {
            let total = 0;
            $field.find('.gf-product-checkbox:checked').each((i, checkbox) => {
                const $checkbox = $(checkbox);
                const price = parseFloat($checkbox.data('price')) || 0;
                const $quantity = $field.find(`.gf-product-quantity[data-product="${$checkbox.val()}"]`);
                const quantity = parseInt($quantity.val()) || 1;
                total += price * quantity;
            });
            return total;
        }
    }

    // Initialize when document is ready
    $(document).ready(() => new GFProductFields());

})(jQuery);
