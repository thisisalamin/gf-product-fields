<?php
$field_id = $field->id;
$field_name = "input_{$field_id}[]";
$field_value = is_array($value) ? $value : array();
?>

<div class="ginput_container ginput_container_product_checkbox">
    <table class="gf-product-table">
        <thead>
            <tr>
                <th class="gf-product-select"><?php esc_html_e('Select', 'gfproductfields'); ?></th>
                <th class="gf-product-name"><?php esc_html_e('Product', 'gfproductfields'); ?></th>
                <?php if ($has_quantity) : ?>
                    <th class="gf-product-qty"><?php esc_html_e('Quantity', 'gfproductfields'); ?></th>
                <?php endif; ?>
                <th class="gf-product-price"><?php esc_html_e('Price', 'gfproductfields'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($choices as $choice) : ?>
                <tr class="gf-product-row">
                    <td class="gf-product-select">
                        <input type="checkbox" 
                               name="<?php echo esc_attr($field_name); ?>"
                               value="<?php echo esc_attr($choice['value']); ?>"
                               data-price="<?php echo esc_attr($choice['price']); ?>"
                               <?php checked(in_array($choice['value'], $field_value), true); ?>>
                    </td>
                    <td class="gf-product-name">
                        <?php echo esc_html($choice['text']); ?>
                    </td>
                    <?php if ($has_quantity) : ?>
                        <td class="gf-product-qty">
                            <input type="number" min="0" value="0" 
                                   name="quantity_<?php echo esc_attr($field_id); ?>_<?php echo esc_attr($choice['value']); ?>"
                                   class="gf-product-quantity">
                        </td>
                    <?php endif; ?>
                    <td class="gf-product-price">
                        <?php echo GFCommon::to_money($choice['price']); ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>