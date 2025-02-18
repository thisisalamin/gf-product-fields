<?php if (!defined('ABSPATH')) exit; ?>

<div class="gf-product-field gf-product-checkbox-group" data-field-id="<?php echo esc_attr($field_id); ?>" id="<?php echo esc_attr($field->uniqueId); ?>">
    <?php foreach ($choices as $choice): ?>
        <div class="gf-product-checkbox-item">
            <label>
                <input type="checkbox" 
                       class="gf-product-checkbox"
                       name="input_<?php echo esc_attr($field_id); ?>[]" 
                       value="<?php echo esc_attr($choice['value']); ?>"
                       data-price="<?php echo esc_attr($choice['price']); ?>">
                <?php echo esc_html($choice['text']); ?> 
                (<?php echo GFCommon::format_number($choice['price'], 'currency'); ?>)
            </label>
            
            <?php if ($has_quantity): ?>
                <input type="number" 
                       class="gf-product-quantity" 
                       data-product="<?php echo esc_attr($choice['value']); ?>"
                       min="1" 
                       value="1">
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>
