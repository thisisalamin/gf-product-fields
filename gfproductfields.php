<?php
/**
 * Plugin Name: Gravity Forms Product Field Enhancer – Custom Field Types with Per-Choice Pricing (Enhanced Admin)
 * Description: Enhances the built‑in Gravity Forms Product field by adding extra field type options ("Checkbox" and "Multi Select") to the Field Type dropdown in the admin. When one of these is selected, the field saves that value in a hidden input while forcing the dropdown to remain "Single Product" so that the default Price inputs are visible. The front end then renders the field as a checkbox list or multi‑select (with per‑choice pricing) and calculates totals accordingly.
 * Version: 1.3
 * Author: Mohamed Alamin
 * Author URI: https://crafely.com
 * License: GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: gfproductfields
 */

// Ensure Gravity Forms is active.
add_action( 'gform_loaded', 'gf_product_field_enhancer_init', 5 );
function gf_product_field_enhancer_init() {
	if ( class_exists( 'GFForms' ) ) {
		// In the admin, add our custom options and inject extra JS.
		if ( is_admin() ) {
			add_action( 'gform_editor_js', 'gf_product_field_enhancer_editor_js' );
		}
		// Front end: modify field output.
		add_filter( 'gform_field_content', 'gf_product_field_enhancer_modify_field', 10, 5 );
		// Calculation: adjust product info on submission.
		add_filter( 'gform_product_info', 'gf_product_field_enhancer_product_info', 10, 3 );
	}
}

/**
 * Append "Checkbox" and "Multi Select" options to the Product field type dropdown in the admin,
 * and inject JavaScript so that when one of these is selected the value is stored in a hidden field
 * while the dropdown is forced back to "Single Product" (keeping the built‑in Price fields visible).
 */
function gf_product_field_enhancer_editor_js() {
	?>
	<script type="text/javascript">
		jQuery(document).ready(function($) {
			// Append our custom options to the Field Type dropdown.
			setTimeout(function(){
				var $select = $('#product_field_type');
				if ($select.length > 0) {
					if ($select.find("option[value='checkbox']").length === 0) {
						$select.append('<option value="checkbox"><?php echo esc_js( __( "Checkbox", "gfproductfields" ) ); ?></option>');
					}
					if ($select.find("option[value='multiselect']").length === 0) {
						$select.append('<option value="multiselect"><?php echo esc_js( __( "Multi Select", "gfproductfields" ) ); ?></option>');
					}
				}
			}, 500);
			
			// Ensure a hidden field exists to store our custom product field type.
			if($('#product_field_custom_type').length === 0){
				$('#product_field_type').after('<input type="hidden" id="product_field_custom_type" name="productFieldType" value="" />');
			}
			
			// When the Field Type dropdown changes...
			$('#product_field_type').on('change', function(){
				var val = $(this).val();
				// If the selected value is one of our custom options...
				if(val === 'checkbox' || val === 'multiselect'){
					// Save that custom value in the hidden field.
					$('#product_field_custom_type').val(val);
					// Force the dropdown back to "singleproduct" so the built‑in Price inputs remain visible.
					$(this).val('singleproduct');
				} else {
					// For other types, clear the hidden field.
					$('#product_field_custom_type').val('');
				}
			});
			
			// On page load, if a custom type was previously saved, ensure the hidden field is set.
			// (This is useful when editing an existing field.)
			if($('#product_field_custom_type').length === 0){
				$('#product_field_type').after('<input type="hidden" id="product_field_custom_type" name="productFieldType" value="" />');
			}
		});
	</script>
	<?php
}

/**
 * Modify the front-end output for Product fields based on the saved custom field type.
 *
 * If the Product field's custom type (stored in productFieldType) is set to "checkbox" or "multiselect",
 * render the field accordingly. Each choice’s price (entered via the built‑in Price input) is displayed and used.
 *
 * @param string   $field_content The original field HTML.
 * @param GF_Field $field         The field object.
 * @param mixed    $value         The current field value.
 * @param int      $lead_id       The entry ID.
 * @param int      $form_id       The form ID.
 * @return string  Modified HTML output for the Product field.
 */
function gf_product_field_enhancer_modify_field( $field_content, $field, $value, $lead_id, $form_id ) {
	// Only modify built‑in Product fields.
	if ( $field->type !== 'product' ) {
		return $field_content;
	}
	
	// Check if a custom product field type is saved (via our hidden input).
	$productFieldType = isset( $field->productFieldType ) ? $field->productFieldType : '';
	// In some cases (new fields or when editing), the custom type might be saved in the post data.
	if ( empty( $productFieldType ) && isset( $_POST['productFieldType'] ) ) {
		$productFieldType = sanitize_text_field( $_POST['productFieldType'] );
	}
	// Default remains "singleproduct" if no custom type is set.
	if ( empty( $productFieldType ) ) {
		$productFieldType = 'singleproduct';
	}
	
	// Only override the front-end output if our custom types are selected.
	if ( ! in_array( $productFieldType, array( 'checkbox', 'multiselect' ) ) ) {
		return $field_content;
	}
	
	// Get the product choices (with per-choice prices already entered in the admin).
	$choices  = isset( $field->choices ) ? $field->choices : array();
	$field_id = absint( $field->id );
	$html     = '';
	
	if ( $productFieldType === 'multiselect' ) {
		$html .= sprintf( '<select name="input_%d[]" multiple="multiple">', $field_id );
		foreach ( $choices as $choice ) {
			$price_text = '';
			if ( isset( $choice['price'] ) && $choice['price'] !== '' ) {
				$price_text = ' ($' . esc_html( $choice['price'] ) . ')';
			}
			$html .= sprintf(
				'<option value="%s">%s%s</option>',
				esc_attr( $choice['value'] ),
				esc_html( $choice['text'] ),
				$price_text
			);
		}
		$html .= '</select>';
		
		// Append quantity inputs for each choice.
		$html .= '<div class="gf-quantity-fields">';
		foreach ( $choices as $choice ) {
			$price_text = '';
			if ( isset( $choice['price'] ) && $choice['price'] !== '' ) {
				$price_text = ' ($' . esc_html( $choice['price'] ) . ')';
			}
			$html .= '<div class="gf-quantity-field">';
			$html .= sprintf(
				'<label>%s%s Qty: <input type="number" name="input_%d_qty[%s]" min="0" value="1" /></label>',
				esc_html( $choice['text'] ),
				$price_text,
				$field_id,
				esc_attr( $choice['value'] )
			);
			$html .= '</div>';
		}
		$html .= '</div>';
	} elseif ( $productFieldType === 'checkbox' ) {
		$html .= '<ul class="gf-checkbox-product">';
		foreach ( $choices as $choice ) {
			$price_text = '';
			if ( isset( $choice['price'] ) && $choice['price'] !== '' ) {
				$price_text = ' ($' . esc_html( $choice['price'] ) . ')';
			}
			$html .= '<li>';
			$html .= sprintf(
				'<input type="checkbox" name="input_%d[]" id="choice_%d_%s" value="%s" />',
				$field_id,
				$field_id,
				esc_attr( $choice['value'] ),
				esc_attr( $choice['value'] )
			);
			$html .= sprintf(
				'<label for="choice_%d_%s">%s%s</label>',
				$field_id,
				esc_attr( $choice['value'] ),
				esc_html( $choice['text'] ),
				$price_text
			);
			// Append quantity input.
			$html .= sprintf(
				'<label> Qty: <input type="number" name="input_%d_qty[%s]" min="0" value="1" /></label>',
				$field_id,
				esc_attr( $choice['value'] )
			);
			$html .= '</li>';
		}
		$html .= '</ul>';
	}
	
	return $html;
}

/**
 * Adjust the product info processing so that the total price is computed correctly when
 * the Product field is rendered as "checkbox" or "multiselect."
 *
 * For each selected option, if a per‑choice price is defined (via the built‑in Price field) it is used
 * (multiplied by the option’s quantity if provided). Otherwise, it falls back to the field's base price.
 *
 * @param array $product_info The product info array.
 * @param array $form         The form object.
 * @param array $entry        The submission entry.
 * @return array Modified product info array.
 */
function gf_product_field_enhancer_product_info( $product_info, $form, $entry ) {
	foreach ( $form['fields'] as $field ) {
		if ( $field->type !== 'product' ) {
			continue;
		}
		
		$field_id = $field->id;
		$productFieldType = isset( $field->productFieldType ) ? $field->productFieldType : '';
		if ( empty( $productFieldType ) && isset( $_POST['productFieldType'] ) ) {
			$productFieldType = sanitize_text_field( $_POST['productFieldType'] );
		}
		if ( ! in_array( $productFieldType, array( 'checkbox', 'multiselect' ) ) ) {
			continue;
		}
		
		$selected = rgar( $entry, (string) $field_id );
		if ( ! is_array( $selected ) ) {
			$selected = explode( ',', $selected );
		}
		
		$total = 0;
		if ( is_array( $selected ) ) {
			foreach ( $selected as $choice_value ) {
				$choice_price = 0;
				// Find the matching choice.
				foreach ( $field->choices as $choice ) {
					if ( $choice['value'] == $choice_value ) {
						if ( isset( $choice['price'] ) && $choice['price'] !== '' ) {
							$choice_price = floatval( $choice['price'] );
						} else {
							$choice_price = floatval( $field->price );
						}
						break;
					}
				}
				$quantity = 1;
				$qty_field = sprintf( 'input_%d_qty', absint( $field_id ) );
				if ( isset( $_POST[ $qty_field ] ) && is_array( $_POST[ $qty_field ] ) && isset( $_POST[ $qty_field ][ $choice_value ] ) ) {
					$quantity = intval( $_POST[ $qty_field ][ $choice_value ] );
					if ( $quantity < 1 ) {
						$quantity = 1;
					}
				}
				$total += $choice_price * $quantity;
			}
		}
		$product_info[ $field_id ]['price'] = $total;
	}
	return $product_info;
}
