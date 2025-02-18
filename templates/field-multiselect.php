<?php if (!defined('ABSPATH')) exit; ?>

<div class="gf-product-field gf-product-multiselect" data-field-id="<?php echo esc_attr($field_id); ?>" id="<?php echo esc_attr($field->uniqueId); ?>">
    <select multiple class="gf-product-select" name="input_<?php echo esc_attr($field_id); ?>[]">
        <?php foreach ($choices as $choice): ?>
            <option value="<?php echo esc_attr($choice['value']); ?>" 
                    data-price="<?php echo esc_attr($choice['price']); ?>">
                <?php echo esc_html($choice['text']); ?> 
                (<?php echo GFCommon::format_number($choice['price'], 'currency'); ?>)
            </option>
        <?php endforeach; ?>
    </select>
    
    <?php if ($has_quantity): ?>
        <div class="gf-quantity-wrapper"></div>
    <?php endif; ?>
</div>
